<?php
namespace App\Http\Controllers\Api;

use App\Constants\FulfillmentStatus;
use App\Constants\PaymentStatus;
use App\Events\OrderUpdated;
use App\Jobs\OrderImportJob;
use App\Models\Account;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ExportExcelTask;
use App\Jobs\OrderExport;
use App\Constants\ExcelType;

class OrderController extends Controller
{

    /**
     * Show the product inventory index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', Order::class);

        /** @var Shop $shop */
        $shopId = $request->input('shop_id');
        if (!empty($shopId)) {
            //if User is admin
            if (Auth::user()->canAccessAdmin()) {
                $shop = Shop::find($shopId);
            }
        } else {
            $shop = $request->session()->get('shop');
        }
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $search = $request->input('search');
        $paymentStatus = $request->input('payment_status');
        $fulfillmentStatus = $request->input('fulfillment_status');
        $accounts = $request->input('accounts');
        $integration = $request->input('integration');
        $region = $request->input('region');
        $dateType = $request->input('date_type');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $shipDate = $request->input('ship_date');

        $with = $request->input('with');

        if (!empty($with)) {
            $with = explode(',', $with);
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = $shop->orders();

        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($search)) {
            $query = $query->where(function ($query) use ($search) {
                $query->where('customer_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('customer_email', 'LIKE', '%' . $search . '%')
                    ->orWhere('external_order_number', 'LIKE', '%' . $search . '%')
                    ->orWhere('id', 'LIKE', '%' . $search . '%')
                    ->orWhere('external_id', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('items', function($query) use ($search) {
                        $query->where('name', 'LIKE', '%' . $search . '%')
                            ->orWhere('sku', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        if (!empty($accounts)) {
            $query = $query->whereIn('account_id', $accounts);
        }

        if (!is_null($fulfillmentStatus)) {
            try {
                $fulfillmentStatusArr = [];
                foreach (explode(",", $fulfillmentStatus) as $status) {
                    array_push($fulfillmentStatusArr, FulfillmentStatus::searchKey(strtoupper($status)));
                }
            } catch (\Exception $e) {
                if ($e->getCode() === 0) {
                    return $this->respondBadRequestError('Invalid fulfillment_status');
                }
            }
            $query = $query->whereIn('fulfillment_status', $fulfillmentStatusArr);
        }

        if (!is_null($paymentStatus) && $paymentStatus !== '') {
            $paymentStatusArr = [];
            if(filter_var($paymentStatus, FILTER_VALIDATE_INT)) {
                array_push($paymentStatusArr,abs($paymentStatus));
            } else {
                try{
                    foreach (explode(",", $paymentStatus) as $status) {
                        array_push($paymentStatusArr, PaymentStatus::searchKey(strtoupper($status)));
                    }
                } catch(\Exception $e) {
                    if ($e->getCode() === 0) {
                        return $this->respondBadRequestError('Invalid payment_status');
                    }
                }
            }
            $query = $query->whereIn('payment_status', $paymentStatusArr);
        }

        // Integration filter
        $integrationType = $request->input('integration_type', 'in') === 'in';
        if(!empty($integration) && !is_null($integration)) {
            if ($integrationType) {
                $query = $query->where('integration_id', $integration);

                if (!empty($region)) {
                    $query->where('region_id', $region);
                }
            } else {
                $query = $query->where('integration_id', '!=', $integration);
                if (!empty($region)) {
                    $query->where('region_id', '!=', $region);
                }
            }
        }

        // Date filter
        if (!is_null($fromDate) && !empty($fromDate)) {
            $query = $query->whereDate($dateType, '>=', $fromDate);
        }
        if (!is_null($toDate) && !empty($toDate)) {
            $query = $query->whereDate($dateType, '<=', $toDate);
        }

        // Ship Date filter
        if (!is_null($shipDate) && !empty($shipDate)) {
            $query = $query->whereDate('ship_by_date', '=', $shipDate);
        }

        $orderBy = $request->input('order_by', 'order_placed_at');

        $direction = $request->input('order_direction', 'DESC');

        $query = $query->orderBy($orderBy, $direction);

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Returns the inventory
     *
     * @param Order $order
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['items.product', 'items.variant', 'items.inventory', 'account.integration','account.region']);
        return $this->respond($order->toArray());
    }

    /**
     * Updates the order
     *
     * @param Request $request
     * @param Order $order
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        if (!empty($order->account_id)) {
            // Currently we only support updating notes as all the other fields are from the integration
            $input = $request->only(['notes']);
        } else {
            // This is for locally created orders and aren't link to any integrations, hence we allow for editing of all fields
            $input = $request->input();
        }

        $order->update($input);

        event(new OrderUpdated($order));

        return $this->respond($order->fresh());
    }

    /**
     * Performs the action for the order.
     * This is only used if the order is tied to an integration
     *
     * @param Request $request
     * @param Order $order
     * @param $integration
     * @param $action
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function action(Request $request, Order $order, $integration, $action)
    {
        $this->authorize('update', $order);

        // As we do not have any default actions for local orders yet
        if (empty($order->account)) {
            return $this->respondBadRequestError('This order does not have an integration to support your actions.');
        }

        $adapter = $order->account->getOrderAdapter();

        if (empty($adapter)) {
            return $this->respondBadRequestError('This integration does not support any actions.');
        }

        if (!method_exists($adapter, $action) || !in_array($action, $adapter->availableActions($order))) {
            return $this->respondBadRequestError('This action is not supported');
        }

        $response = $adapter->{$action}($order, $request);

        if (!is_bool($response)) {
            return $this->respond($response);
        }

        return $this->respond($order->fresh());
    }

    /**
     * Performs the action for bulk orders.
     * This is only used if the order is tied to an integration
     *
     * @param Request $request
     * @param $orders
     * @param $integration
     * @param $action
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function bulkAction(Request $request, $orders, $integration, $action)
    {
        $orders = explode(',', $orders);
        if (empty($orders)) {
            return $this->respondBadRequestError('Please select an order.');
        }

        $account = null;
        foreach ($orders as $order) {
            $order = Order::whereId($order)->first();
            $this->authorize('update', $order);

            // As we do not have any default actions for local orders yet
            if (empty($order->account)) {
                return $this->respondBadRequestError('This order does not have an integration to support your actions.');
            }

            if (is_null($account)) {
                $account = $order->account;
            }

            // Make sure all order is under same account
            if ($account->id !== $order->account_id) {
                return $this->respondBadRequestError('All orders must under the same account.');
            }

            $adapter = $account->getOrderAdapter();

            if (empty($adapter)) {
                return $this->respondBadRequestError('This integration does not support any actions.');
            }

            if (!method_exists($adapter, $action) || !in_array($action, $adapter->availableActions($order))) {
                return $this->respondBadRequestError('This action is not supported');
            }
        }
        $adapter = $account->getOrderAdapter();

        $response = $adapter->{$action}($orders, $request);

        if (!is_bool($response)) {
            return $this->respond($response);
        }

        return $this->respond($orders);
    }

    /**
     * Imports the orders for the account
     *
     * @param Request $request
     * @param Account $account
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function import(Request $request, Account $account)
    {
        $this->authorize('view', $account);
        $this->authorize('create', Order::class);

        if ($account->hasFeature(['orders', 'import_orders'])) {
            OrderImportJob::dispatch($account);
        } else {
            return $this->respondBadRequestError('This feature is not supported');
        }

        return $this->respondWithMessage(null, 'Successfully queued import of orders for account.');
    }

    /**
     * Retrieve order's items pickup list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function pickup(Request $request)
    {
        $this->authorize('index', Order::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $shipDate = $request->input('ship_date', null);
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = $shop->orderItems();

        // Filter by ship date
        if ($shipDate) {
            $shipDate = date('Y-m-d', strtotime($shipDate));
            $query = $query->whereHas('order', function (Builder $query) use ($shipDate) {
                $query->whereDate('orders.ship_by_date', '=', $shipDate);
            });
        } else {
            // Filter by 7 days only + those without ship date (Exclude weekend)
            $days = [];
            for ($i = 0; $i <= 6; $i++) {
                $today = new Carbon();
                $day = new Carbon($today->addDay($i)); // Add day to today

                // Exclude weekend
                if ($day->dayOfWeek != 0 && $day->dayOfWeek != 6) {
                    $days[] = $day->toDateString();
                }
            }
            $query = $query->whereHas('order', function (Builder $query) use ($days) {
                $query->whereIn(DB::raw("DATE(orders.ship_by_date)"), $days)
                    ->orWhereNull('orders.ship_by_date');
            });
        }

        $query = $query->groupBy('sku', 'name', 'variation_sku', 'variation_name')
                    ->select('sku', 'name', 'variation_sku', 'variation_name', DB::raw('SUM(quantity) AS total_quantity'), DB::raw('GROUP_CONCAT(DISTINCT id SEPARATOR ", ") AS order_ids'));

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Retrieve incoming order list
     * 
     * @param Request $request
     */
	public function download(Request $request)
	{
		/** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('index', Order::class);

        $task = ExportExcelTask::create([
            'shop_id' => $shop->id,
            'user_id' => auth()->user()->id,
            'source_type' => ExcelType::DOWNLOAD_ORDERS()->getValue(),
            'source' => $shop->id,
            'settings' => $request->except('now')
        ]);	
        
        OrderExport::dispatch($task->fresh(), $request->input())->onQueue('export');
	}

    /**
     * Returns the listing of past export tasks and their status
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function exportTasks(Request $request)
    {
        $this->authorize('create', Order::class);
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');

        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = ($request->get('type') === 'excel') ? $shop->exportExcelTasks() : $shop->orderExportTasks();

        if ($request->get('status')) {
            $query->whereIn('status', explode(',', $request->get('status')));
        }


        if ($request->get('type') === 'excel') {
            $query->where('source_type',ExcelType::DOWNLOAD_ORDERS()->getValue());
        }
        

        if ($request->get('count_unread')) {
            return $this->respond($query->where('downloaded_status', false)->count());
        } else {
            $users = $query->latest()->paginate($limit);
        }

        return $this->respondPagination($request, $users);
    }

    /**
     * Update the downloaded_status of a task of the sorce_type 'Excel\DownloadOrders' for a particular task
     */
    public function updateExportTask(ExportExcelTask $exportExcelTask, Request $request)
    {
        if ($exportExcelTask->update($request->all())) {
            return $this->respondWithMessage(null, 'Successfully update task.');
        }
        return $this->respondWithError('Unable to update task');
    }
}
