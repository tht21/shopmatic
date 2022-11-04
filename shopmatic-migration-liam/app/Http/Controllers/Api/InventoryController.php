<?php

namespace App\Http\Controllers\Api;

use App\Constants\ProductInventoryStatus;
use App\Constants\ProductPriceType;
use App\Constants\ProductStatus;
use App\Jobs\DeleteOrphanedInventoryJob;
use App\Jobs\SyncInventory;
use App\Jobs\UpdateListingInventory;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Models\Shop;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{

    /**
     * Returns the total in stock, low stock, and out of stock inventories
     *
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function status(Request $request)
    {
        $this->authorize('index', ProductInventory::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $inStock = $shop->inventories()->whereRaw('stock > low_stock_notification')->count();
        $lowStock = $shop->inventories()->whereRaw('stock <= low_stock_notification')
            ->where('low_stock_notification', '<>', 0)
            ->where('stock', '>', 0)->count();
        $outOfStock = $shop->inventories()->where('stock', '<=', 0)->count();

        return $this->respond([
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        ]);
    }
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
        $this->authorize('index', ProductInventory::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $id = $request->input('id');
        $search = $request->input('search');
        $enabled = $request->input('enabled');
        $stock = $request->input('stock');
        $stockOpt = $request->input('stock_opt');
        $lowStock = $request->input('low_stock');
        $accountId = $request->input('account_id');
        $created_date = $request->input('created_date');
        $shopId = $request->input('shop_id');
        $integrationType = $request->input('integration_type');

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT); // will always cap the limit under DEFAULT_MAX_LIMIT
        $with = $request->input('with');
        $whereHas = $request->input('where_has', []);
        $whereDoesntHave = $request->input('where_doesnt_have', []);

        $optList = ['=', '<=', '>=', '!=', '<', '>'];
        if (!is_null($stockOpt) && !in_array($stockOpt, $optList)) {
            return $this->respondBadRequestError('Invalid stock option argument.');
        }

        $query = $shop->inventories();

        $optList = ['=', '<=', '>=', '!=', '<', '>'];
        if (!is_null($stockOpt) && !in_array($stockOpt, $optList)) {
            return $this->respondBadRequestError('Invalid stock option argument.');
        }

        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($id) && $id !== '0') {
            $query = $query->where('id', $id);
        }

        if (!empty($accountId) && $accountId !== '0') {
            $query = $query->whereHas('listings', function($query) use ($accountId) {
                $query->whereAccountId($accountId);
            });
        }

        if (!empty($shopId) && $shopId !== '0') {
            $query = $query->whereHas('listings', function($query) use ($shopId) {
                $query->whereShopId($shopId);
            });
        }

        if (!empty($integrationType) && $integrationType !== '0') {
            $query = $query->whereHas('listings.integration', function($query) use ($integrationType) {
                $query->whereType($integrationType);
            });
        }

        if (!empty($created_date) ) {
            $created_date = Carbon::createFromFormat('d/m/Y', $created_date);
            $query = $query->whereDate('created_at', Carbon::parse($created_date));
        }

        if (!empty($search) && $id !== '0') {
            $query = $query->where(function($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('sku', 'LIKE', '%' . $search . '%');
            });
        } elseif (!empty($search) && $id === '0') {
            // when id = 0 and search not empty, use this api to validate add new inventory
            // check whether new sku for inventory set by user exist in the product_inventories or not
            $query = $query->where('sku', $search);
        }

        if (!is_null($stock) && !is_null($stockOpt)) {
            $query = $query->where('stock', $stockOpt, $stock);
        }

        if (!is_null($lowStock) && $lowStock === 'true') {
            $query = $query->whereColumn('stock', '<', 'low_stock_notification');
        }

        // This is to cater for no param and also the disabled status as it's 0, we can't use empty
        if (!is_null($enabled) && $enabled !== '') {
            $query = $query->where('enabled', $enabled);
        }

        if (is_array($whereHas)) {
            foreach ($whereHas as $key => $value) {
                $query = $query->whereHas($value);
            }
        }
        if (is_array($whereDoesntHave)) {
            foreach ($whereDoesntHave as $key => $value) {
                $query = $query->whereDoesntHave($value);
            }
        }

        $orderBy = $request->input('order_by', 'SKU');

        $orderDirection = $request->input('order_direction', 'asc');

        $query = $query->where('status', '!=', ProductInventoryStatus::DISABLE());
        $query = $query->orderBy($orderBy, $orderDirection);

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Returns the inventory
     *
     * @param ProductInventory $inventory
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(ProductInventory $inventory)
    {
        $this->authorize('view', $inventory);
        $inventory->load(['bundledInventories', 'listings.variant', 'listings.account.integration','listings.account.region']);
        return $this->respond($inventory->toArray());
    }

    /**
     * Returns the listings for the inventory
     *
     * @param ProductInventory $inventory
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function listingIndex(ProductInventory $inventory)
    {
        $this->authorize('view', $inventory);
        $listings = $inventory->listings()->with(['variant', 'account.integration'])->get();
        return $this->respond($listings);
    }

    /**
     * Update the stock and/or the sync_stock for the listing
     *
     * @param Request $request
     * @param ProductInventory $inventory
     * @param ProductListing $listing
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function listingUpdate(Request $request, ProductInventory $inventory, ProductListing $listing)
    {
        $this->authorize('view', $inventory);

        // The listing ID / inventory ID is incorrect. This shouldn't happen unless the user played with the URL
        if (!$listing->variant || $listing->variant->inventory_id != $inventory->id) {
            throw new ModelNotFoundException();
        }

        //TODO: Maybe refactor this into a request validation instead of manual checking
        $stock = $request->input('stock');
        $sync = $request->input('sync_stock');
        if (!ctype_digit($stock) && !is_int($stock)) {
            $this->respondBadRequestError('stock is a required field and must be an integer.');
        }
        if (is_null($sync)) {
            $this->respondBadRequestError('sync_stock is a required field.');
        }

        $listing->sync_stock = !empty($sync);
        $listing->save();

        // Update the stock with the new stock, or use the inventory stock
        try {
            if (!$listing->sync_stock) {
                UpdateListingInventory::dispatchNow($listing, $stock);
            } else {
                UpdateListingInventory::dispatchNow($listing, $inventory->stock);
            }
        } catch (\Exception $e) {
            return $this->respondInternalError($e->getMessage());
        }


        return $this->respond($listing);
    }

    /**
     * Returns the inventory
     *
     * @param Request $request
     * @param ProductInventory $inventory
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function update(Request $request, ProductInventory $inventory)
    {
        $this->authorize('update', $inventory);

        $input = $request->input();

        $input['enabled'] = !empty($input['enabled']) ? 1 : 0;
        $input['out_of_stock_notification'] = !empty($input['out_of_stock_notification']) ? 1 : 0;
        $input['name'] = $input['name'] ?? '';
        $stock = $input['stock'] ?? null;

        /*
         * Need to update the enable first, because if user switch from off to on then it should be changed before updating the inventory
         * */

        $data = [
            'enabled' => $input['enabled'],
        ];

        if($input['name']) {
            $data['name'] = $input['name'];
        }

        $inventory->update($data);
        if (!is_null($stock)) {
            if ($inventory->stock != $stock) {
                $change = $input['stock'] - $inventory->stock;
                try {
                    // This will call the sync inventory job already
                    Log::info("Inventory with SKU: " .$inventory->sku. " is manually updated by IP address: " .$request->getClientIp());
                    $inventory->modifyInventory($change, 'none', 'Manual change by ' . Auth::user()->name);
                    /**
                     * Check if product status is 30 i.e out of stock.
                     * If stock is greater than 0 and product status is 30
                     * then update the product status to live i.e 10
                     * */
                    $listing = $inventory->listings()->with(['product'])->first();
                    $product = $listing->product ?? '' ;
                    if (!empty($product) && isset($product->status) && $product->status = ProductStatus::OUT_OF_STOCK()->getValue() && $inventory->enabled && $change) {
                        $product->status = ProductStatus::LIVE();
                        $product->save();
                    }
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    return $this->respondInternalError($message);
                }
            } else {
                try {
                    SyncInventory::dispatchNow($inventory, true, true);
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    return $this->respondWithError($message);
                }
            }
        }

        // So we do not update it here again
        unset($input['stock']);

        $inventory->update($input);

        return $this->respond($inventory->fresh());
    }

    /**
     * Show the product inventory log index
     *
     * @param Request $request
     *
     * @param ProductInventory $inventory
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function logs(Request $request, ProductInventory $inventory)
    {
        $this->authorize('view', $inventory);

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = $inventory->logs()->orderBy('id', 'DESC');

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Show the product inventory log index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function logsIndex(Request $request)
    {
        $this->authorize('index', ProductInventory::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = $shop->inventoryTrails()->orderBy('id', 'DESC');

        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Store bundled product
     *
     * @param Request $request
     *
     * @param ProductInventory $inventory
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeBundle(ProductInventory $inventory, Request $request)
    {
        $this->authorize('update', $inventory);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $bundleInventoryId = $request->get('deduct_product_inventory_id');
        $amount = $request->get('deduct_amount');

        $bundleInventory = $shop->inventories()->where('id', $bundleInventoryId['id'])->first();

        if (!$bundleInventory) {
            return $this->respondBadRequestError('Selected inventory not found');
        }
        if ($bundleInventoryId === $inventory->id) {
            return $this->respondBadRequestError('You cannot add the same inventory to it\'s bundle!');
        }
        // check for 2 ways bundled
        if ($bundleInventory->bundledInventories()->where('product_inventories.id', $inventory->id)->exists()) {
            return $this->respondBadRequestError('Selected inventory is parent of current\'s inventory!');
        }

        $inventory->bundledInventories()->attach($bundleInventory, ['deduct_amount' => $amount]);

        return $this->respond($inventory->fresh());
    }

    /**
     * Update bundled product deduct amount
     *
     * @param Request $request
     *
     * @param ProductInventory $inventory
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateBundle(ProductInventory $inventory, Request $request)
    {
        $this->authorize('update', $inventory);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $bundleInventoryId = $request->get('deduct_product_inventory');
        $deduct_amount = $request->get('deduct_amount');

        $bundleInventory = $shop->inventories()->where('id', $bundleInventoryId)->first();

        if (!$bundleInventory) {
            return $this->respondBadRequestError('Selected inventory not found');
        }

        if ($bundleInventoryId === $inventory->id) {
            $inventory->bundledInventories()->detach([$bundleInventoryId]);
            return $this->respondBadRequestError('You cannot add the same inventory to it\'s bundle!');
        }

        $inventory->bundledInventories()->updateExistingPivot($bundleInventory, ['deduct_amount' => $deduct_amount]);

        return $this->respond($inventory->fresh());
    }

    /**
     * Remove bundled product
     *
     * @param Request $request
     *
     * @param ProductInventory $inventory
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroyBundle(ProductInventory $inventory, Request $request)
    {
        $this->authorize('update', $inventory);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $deduct_product_inventory = $request->get('deduct_product_inventory');

        $deduct_product_inventory = $shop->inventories()->where('id', $deduct_product_inventory['id'])->first();

        if (!$deduct_product_inventory) {
            return $this->respondBadRequestError('Selected inventory not found');
        }

        $inventory->bundledInventories()->detach($deduct_product_inventory);

        return $this->respond($inventory->fresh());
    }

    /**
     * Show the product inventory index
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $search = $request->input('search');
        $accountId = $request->input('account_id');
        $shopId = $request->input('shop_id');
        $integrationType = $request->input('integration_type');
        $created_date = $request->input('created_date');
        $with = $request->input('with');

        $query = $shop->inventories();

        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($accountId) && $accountId !== '0') {
            $query = $query->whereHas('listings', function ($query) use ($accountId) {
                $query->whereAccountId($accountId);
            });
        }

        if (!empty($shopId) && $shopId !== '0') {
            $query = $query->whereHas('listings', function ($query) use ($shopId) {
                $query->whereShopId($shopId);
            });
        }

        if (!empty($integrationType) && $integrationType !== '0') {
            $query = $query->whereHas('listings.integration', function ($query) use ($integrationType) {
                $query->whereType($integrationType);
            });
        }

        if (!empty($created_date)) {
            $created_date = Carbon::createFromFormat('d/m/Y', $created_date);
            $query = $query->whereDate('created_at', Carbon::parse($created_date));
        }

        if (!empty($search)) {
            $query = $query->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('sku', 'LIKE', '%' . $search . '%');
            });
        }

        $query = $query->get();

        $headers[0] = [
            ['value' => '', 'style' => ['range' => 'A1:D1']],
            ['value' => ''],
            ['value' => ''],
            ['value' => ''],
            ['value' => 'TOTALS BY PRODUCTS, INTEGRATION', 'style' => ['range' => 'E1:G1']],
            ['value' => ''],
            ['value' => ''],
        ];

        $headers[1] = [
            ['value' => 'PRODUCT'],
            ['value' => 'INTEGRATION'],
            ['value' => 'CATEGORY'],
            ['value' => 'CURRENT STOCK'],
            ['value' => 'STOCK VALUE'],
            ['value' => 'ITEM VALUE'],
            ['value' => 'REORDER AMOUNT'],
        ];


        for ($i = 0; $i < 2; $i++) {
            foreach ($headers[$i] as $column => $data) {
                $headers[$i][$column]['style']['alignment'] = 'center';
            }
        }

        $i = 2;

        foreach ($query as $data) {
            foreach ($data->listings as $listing) {
                $prices = $listing->prices->mapWithKeys(function ($item) {
                    return [$item['type'] => $item];
                });

                $headers[$i] = [
                    ['value' => $data->name],
                    ['value' => $listing->integration->name],
                    ['value' => $listing->integration_category->name ?? ''],
                    ['value' => $listing->stock],
                    ['value' => $listing->stock * $prices[ProductPriceType::SELLING()->getValue()]->price],
                    ['value' => $prices[ProductPriceType::SELLING()->getValue()]->price],
                    ['value' => $data->low_stock_notification],
                ];
                $i += 1;
            }
        }

        try {
            return Excel::download(new GenerateExcel('Sales report', $headers, [], ['header_style' => ['bold' => true, 'auto_size' => true], 'freeze_pane' => 'D4']), 'export_excel_bulk_edit_' . Carbon::now()->timestamp . '.xlsx');
        } catch (\Exception $e) {
            set_log_extra('shop_id', session('shop')->getId());
            Log::error($e);
            return null;
        }
    }

    /**
     * Delete account
     *
     * @param Request $request
     * @param ProductInventory $inventory
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, ProductInventory $inventory)
    {
        $this->authorize('delete', $inventory);

            if(empty($inventory->listings)) {
                $inventory->delete();
            }else {
                $inventory->update([
                    'status' => ProductInventoryStatus::DISABLE(),
                ]);
            }

        return $this->respondWithMessage(null, 'Product inventory successfully deleted.');
    }

    /**
     * Delete Orphaned Inventories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteOrphanedInventories(Request $request)
    {
        $this->authorize('index', ProductInventory::class);

        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        DeleteOrphanedInventoryJob::dispatchNow($shop);
        return $this->respondWithMessage(null, 'Successfully delete orphaned inventories.');
    }
}
