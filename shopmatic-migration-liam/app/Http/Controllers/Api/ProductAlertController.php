<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductAlert;
use App\Models\Shop;
use Illuminate\Http\Request;

class ProductAlertController extends Controller
{

    /**
     * Show the products index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', ProductAlert::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $search = $request->input('search');
        $type = $request->input('type');
        $dismissed = $request->input('dismissed');
        $productId = $request->input('product_id');

        $with = $request->input('with');

        if (!empty($with)) {
            $with = explode(',', $with);
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = $shop->alerts();

        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($search)) {
            $query = $query->where(function($query) use ($search) {
                $query->whereHas('product', function($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('associated_sku', 'LIKE', '%' . $search . '%')
                        ->orWhere('slug', 'LIKE', '%' . $search . '%');
                })->orWhere('message', 'LIKE', '%' . $search . '%');
            });
        }
        if (!empty($productId)) {
            $query = $query->where('product_id', $productId);
        }

        if (!is_null($type)) {
            $query = $query->where('type', $type);
        }

        if (!is_null($dismissed)) {
            if (empty($dismissed)) {
                $query = $query->whereNull('dismissed_at');
            } else {
                $query = $query->whereNotNull('dismissed_at');
            }
        }

        $query = $query->orderBy('id', 'DESC');

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Dismisses the alert
     *
     * @param ProductAlert $alert
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dismiss(ProductAlert $alert)
    {
        $this->authorize('update', $alert);
        if (empty($alert->dismissed_at)) {
            $alert->dismissed_at = now();
            $alert->save();
        }
        return $this->respond($alert->toArray());
    }

    /**
     * Dismisses all alerts
     *
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function dismissAll(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');

        $this->authorize('update', $shop);

        $shop->unreadAlerts()->update(['dismissed_at' => now()]);

        return $this->respond();
    }

}
