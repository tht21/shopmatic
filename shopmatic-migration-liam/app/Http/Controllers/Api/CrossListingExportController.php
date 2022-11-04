<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Models\ExportExcelTask;
use App\Models\Product;
use App\Jobs\ProductAttributesExportExcelJob;

class CrossListingExportController extends Controller
{

    /**
     * @param Request $request
     * @param ExportExcelTask $task
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, ExportExcelTask $task)
    {
        $this->authorize('index', Product::class);

        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        } elseif ($shop->id != $task->shop_id) {
            return $this->respondNotFound();
        }
        return $this->respond($task->toArray());
    }

    /**
     * Export to Excel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function download(Request $request)
    {
        $this->authorize('index', Product::class);

        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $accountId = $request->input('account_id', null);
        $categoryId = $request->input('category_id', null);
        $integrationCategoryId = $request->input('integration_category_id', null);

        /* Check account_id,category_id & integration_category_id is not empty. */
        if (empty($accountId)) {
            return $this->respondBadRequestError('Account Id Is Empty');
        }
        /** @var Account $account */
        $account = $shop->accounts()->find($accountId);
        $account = $account->append('has_category');

        if (empty($categoryId)) {
            return $this->respondBadRequestError('Category Id Is Empty');
        }
        if ($account->has_category && $account->has_category !== 'account' && empty($integrationCategoryId)) {
            return $this->respondBadRequestError('Integration category Id Is Empty');
        }

        $task = ExportExcelTask::create([
            'shop_id' => $shop->id,
            'user_id' => auth()->user()->id,
            'source_type' => get_class($shop),
            'source' => $shop->id,
            'settings' => $request->except('now')
        ]);

        //download product listings
        if ($request->input('now')) {
            $url = ProductAttributesExportExcelJob::dispatchNow($task->fresh());
            return $this->respondWithMessage(['url' => $url], 'Excel file generated successfully.');
        } else {
            ProductAttributesExportExcelJob::dispatch($task->fresh())->onQueue('default');
            //ProductAttributesExportExcelJob::dispatch($task->fresh())->onQueue('product_template_req');
        }

        return $this->respondWithMessage($task, 'Excel file generated successfully.');

    }
}
