<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Constants\HexColor;
use App\Constants\JobStatus;
use App\Constants\FulfillmentStatus;
use App\Models\ExportExcelTask;
use App\Models\Shop;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Http\Request;
use App\Models\Integration;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class OrderExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;
    
    /**
     * Will be use for filter parameters
     */
    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ExportExcelTask $task, $params)
    {
        $this->task = $task;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
	public function handle()
    {
        try{
            $this->task->status = JobStatus::PROCESSING()->getValue();
            $this->task->save();

            /** @var Shop $shop */
            //$shop = $request->session()->get('shop');
            $shop = Shop::find($this->task->source);
            $query = $this->getFilterOrderQuery($this->params);
            $orders = $query->orderBy('order_updated_at', 'DESC')->with('integration')->get();

            // headers setup
            $headersValue = [
                'Order ID',
                'Seller Order ID',
                'Marketplace',
                'Order Status',
                'Payment Method',
                'Created At',
                'Remark',
                'Discount',
                'Shipping',
                'Product Name',
                'Sku',
                'Quantity',
                'Unit Price',
                'Total Price',
                'Variation Name',
                'Product Status',
                'Customer Name',
                'Shipping Name',
                'Email',
                'Phone',
                'Address',
                'Postal Code',
                'State',
                'Country',
                'Tracking no.'
            ];

            $headers = [];
            // populate headers
            foreach ($headersValue as $value)
            {
                if (strpos($value, 'address') !== FALSE)
                {
                    $headers[] = ['value' => $value, 'style' => ['width' => 100]];
                } else {
                    $headers[] = ['value' => $value];
                }
            }

            /** Populate data and store it in a single row, and at last push it in the $data array */
            $data = [];
            foreach ($orders as $order) {
                foreach($order->items as $item) {
                    $address = '';
                    if($order->shipping_address !== null && isset($order->shipping_address['address1'])) {
                        $address = $order->shipping_address['address1'];
                    } else if($order->shipping_address !== null && isset($order->shipping_address['address2'])) {
                        $address = $order->shipping_address['address2'];
                    } else if($order->shipping_address !== null && isset($order->shipping_address['address3'])) {
                        $address = $order->shipping_address['address3'];
                    } else if($order->shipping_address !== null && isset($order->shipping_address['address4'])) {
                        $address = $order->shipping_address['address4'];
                    } else if($order->shipping_address !== null && isset($order->shipping_address['address5'])) {
                        $address = $order->shipping_address['address5'];
                    }

                    $row = [
                            $order->id, 
                            $order->external_id, 
                            $order->integration->name, 
                            FulfillmentStatus::search($order->fulfillment_status),
                            $order->payment_method, 
                            $order->created_at, 
                            $order->buyer_remarks, 
                            $order->seller_discount, 
                            $order->shipping_fee, 
                            $item->name, 
                            $item->sku, 
                            $item->quantity, 
                            $item->item_price, 
                            $order->grand_total, 
                            $item->variation_name, 
                            FulfillmentStatus::search($item->fulfillment_status), 
                            $order->customer_name, 
                            $item->shipment_provider, 
                            $order->shipping_address['email'] ?? '', 
                            $order->shipping_address['phoneNumber'] ?? '', 
                            $address,
                            $order->shipping_address['postcode'] ?? '', 
                            $order->shipping_address['state'] ?? '',
                            $order->shipping_address['country'] ?? '',
                            $item->tracking_number,
                        ];
                    $data[] = $row;
                } 
            }
            /** End of populate data */

            $filename = 'export/export_order_listing_' . Carbon::now()->timestamp . '.xlsx';
            Excel::store(
                new GenerateExcel(
                    'Incoming Order List', 
                    [$headers], 
                    $data, 
                    ['header_style' => ['background' => 'F4FF90', 'bold' => true, 'auto_size' => true]]
                ), 
                $filename, 'excel', \Maatwebsite\Excel\Excel::XLSX
            );

            $this->task->download = ['url' => Storage::disk('excel')->url($filename)];
            $this->task->save();

            $this->task->status = JobStatus::FINISHED()->getValue();
            $this->task->save();
        } catch(\Exception $exception) {
            $this->task->status = JobStatus::FAILED()->getValue();
            $this->task->downloaded_status = true;
            $this->task->save();
            throw $exception;
        }
    }

	/**
     * @param Request $request
     * @return mixed
     */
    public function getFilterOrderQuery($params)
    {
        try {
            /** @var Shop $shop */
            //$shop = session('shop');
            $shop = Shop::find($this->task->source);
            $search = $params['search'];
            $paymentStatus = $params['payment_status'] ?? '';
            $fulfillmentStatus = $params['fulfillment_status'];
            $accounts = $params['accounts'];
            $integration = $params['integration'] ?? '';
            $region = $params['region'] ?? '';
            $dateType = $params['date_type'];
            $fromDate = $params['from_date'];
            $toDate = $params['to_date'];
            $shipDate = $params['ship_date'] ?? '';

            $with = $params['with'];

            if (!empty($with)) {
                $with = explode(',', $with);
            }

            $query = $shop->orders()->select([
                'id',
                'integration_id',
                'external_id',
                'payment_method',
                'created_at',
                'buyer_remarks',
                'seller_discount',
                'shipping_fee',
                'customer_name',
                'customer_email',
                'shipping_address',
                'fulfillment_status',
                'grand_total'
            ])->with(['integration' => function($query) {
                $query->where('id', '=', $query->integration_id);
            }, 'items' => function ($query) {
                $query->select([
                    'id',
                    'order_id',
                    'name',
                    'sku',
                    'external_id',
                    'quantity',
                    'product_id',
                    'shipment_provider',
                    'item_price',
                    'variation_name',
                    'variation_sku',
                    'fulfillment_status',
                    'tracking_number'
                ]);
            }]);

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
            //$integrationType = $params['integration_type', 'in'] === 'in';
            $integrationType = $params['integration_type'] === 'in';
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
    
            $orderBy = $params['order_by'] ?? 'order_placed_at';
    
            $direction = $params['order_direction'] ?? 'DESC';
    
            $query = $query->orderBy($orderBy, $direction);
    
            return $query;
        } catch(\Exception $exception) {
            throw $exception;
        }
    }
}
