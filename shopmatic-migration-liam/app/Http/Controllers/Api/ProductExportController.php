<?php

namespace App\Http\Controllers\Api;

use App\Constants\JobStatus;
use App\Jobs\ProductExportExcelJob;
use App\Jobs\ProductExportJob;
use App\Jobs\ProductListingExportExcelJob;
use App\Models\Account;
use App\Models\ExportExcelTask;
use App\Models\Product;
use App\Models\ProductExportTask;
use App\Models\Shop;
use App\Models\Integration;
use App\Utilities\FileStorageHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductExportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Export
     *
     * @param Request $request
     * @param Product $product
     * @param Account $account
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function export(Request $request, Product $product, Account $account)
    {
        $this->authorize('update', $product);

        $messages = [
            JobStatus::PENDING()->getValue() => 'Product export is pending.',
            JobStatus::PROCESSING()->getValue() => 'Product export is already in progress.',
            JobStatus::FINISHED()->getValue() => 'Product is already exported.'
        ];

        if (!$account->hasFeature(['products', 'create_product'])) {
            return $this->respondBadRequestError('The integration doesn\'t support product creation.');
        }

        $task = ProductExportTask::where([
            'shop_id' => $account->shop_id,
            'user_id' => Auth::user()->id,
            'account_id' => $account->id,
            'product_id' => $product->id
        ])->latest()->first();

        if ($task && ($task->getOriginal('status') == JobStatus::PENDING()->getValue() || $task->getOriginal('status') == JobStatus::PROCESSING()->getValue() || $task->getOriginal('status') == JobStatus::FINISHED()->getValue())) {

            return $this->respondBadRequestError($messages[$task->getOriginal('status')]);
        }

        $task = ProductExportTask::create([
            'shop_id' => $account->shop_id,
            'user_id' => Auth::user()->id,
            'account_id' => $account->id,
            'product_id' => $product->id
        ]);

        if ($request->input('create', false)) {
            try {
                $response = ProductExportJob::dispatchNow($task->fresh());
                if (!empty($response)) {
                    return $this->respondWithError($response);
                }
            } catch (\Exception $e) {
                return $this->respondWithError($e->getMessage());
            }
        } else {
            ProductExportJob::dispatch($task->fresh())->onQueue('export');
        }

        return $this->respondWithMessage(null, 'Successfully queued export of product.');
    }

    public function exportAll(Request $request, Account $account)
    {
        $this->authorize('index', Product::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $query = $shop->products()->where(function ($query) use ($account) {
            $query->whereDoesntHave('listings', function (Builder $q) use ($account) {
                $q->whereAccountId($account->id);
            });
        })->whereNotNull('category_id');


        if (!empty($request->get('category_id'))) {
            $query = $query->whereCategoryId($request->get('category_id'));
        }
        if (!empty($request->get('integration_category_id'))) {
            $query  = $query->where(function (Builder $q) use ($account, $request) {
                return $q->whereHas('attributes', function($query) use ($account, $request)  {
                    $query->whereIntegrationId($account->integration_id)
                        ->whereName('integration_category_id')
                        ->whereRegionId($account->region_id)
                        ->whereValue($request->get('integration_category_id'));
                })
                ->orWhere( function ($query) use ($account) {
                    $query->whereDoesntHave('attributes', function (Builder $query) use ($account) {
                        $query->whereIntegrationId($account->integration_id)
                        ->whereRegionId($account->region_id)
                        ->whereName('integration_category_id');
                    });
                });
            });
        }

        $products = $query->orderBy('category_id', 'desc')->get();

        if ($products && count($products) > 0) {
            foreach ($products as $product) {
                // Need to skip the current processing one
                $latestExportTask = $product->productExportTasks()->whereAccountId($account->id)->latest('id')->first();
                if ($latestExportTask && $latestExportTask->status === 'Processing') {
                    continue;
                }

                $messages = [
                    JobStatus::PENDING()->getValue() => 'Product export is pending.',
                    JobStatus::PROCESSING()->getValue() => 'Product export is already in progress.',
                    JobStatus::FINISHED()->getValue() => 'Product is already exported.'
                ];

                if (!$account->hasFeature(['products', 'create_product'])) {
                    return $this->respondBadRequestError('The integration doesn\'t support product creation.');
                }

                $task = ProductExportTask::where([
                    'shop_id' => $account->shop_id,
                    'user_id' => Auth::user()->id,
                    'account_id' => $account->id,
                    'product_id' => $product->id
                ])->latest()->first();

                if ($task && ($task->getOriginal('status') == JobStatus::PENDING()->getValue() || $task->getOriginal('status') == JobStatus::PROCESSING()->getValue() || $task->getOriginal('status') == JobStatus::FINISHED()->getValue())) {

                    return $this->respondBadRequestError($messages[$task->getOriginal('status')]);
                }

                $task = ProductExportTask::create([
                    'shop_id' => $account->shop_id,
                    'user_id' => Auth::user()->id,
                    'account_id' => $account->id,
                    'product_id' => $product->id
                ]);

                // Added delay if is amazon to prevent limit exceed
                if ($account->integration_id === Integration::AMAZON) {
                    sleep(1);
                    $delay = Carbon::now()->addSeconds(90);
                    ProductExportJob::dispatch($task->fresh())->onQueue('export')->delay($delay);
                    //ProductExportJob::dispatch($task->fresh())->onQueue('export')->delay(now()->addSeconds(30));
                } else {
                    ProductExportJob::dispatch($task->fresh())->onQueue('export');
                }
            }

            return $this->respondWithMessage(null, 'Successfully queued export of product.');
        }
        return $this->respondWithError('No product to export');
    }

    /**
     * Save attributes, prices, name etc
     *
     * @param Product $product , Request $request
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function save(Product $product, Request $request)
    {
        $this->authorize('update', $product);
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $attributes = $request->input('attributes');
        $integrationId = $request->input('integration');
        $prices = $request->input('prices');
        $images = $request->input('images');
        $options = $request->input('options');
        /** Integration Region Id  */
        $regionId = $request->input('region_id', null);

        /* save attributes */
        if (!is_null($attributes)) {
            foreach ($attributes as $key => $attribute) {
                // Variant cannot change name
                if (isset($attribute['name']) && ($attribute['name'] != 'name' || !(isset($attribute['variant_id'])))) {
                    // If value is empty then store it as empty string
                    $attribute['value'] = $attribute['value'] ?? "";
                    // if attribute color_thumbnail => save image
                    if ($attribute['name'] == 'color_thumbnail' && !empty($attribute['value'])) {
                        $attribute['value'] = saveColorThumbnail($attribute['value']);
                    }
                    // If value is array convert to json
                    if (is_array($attribute['value'])) {
                        $attribute['value'] = json_encode($attribute['value']);
                    }

                    $product->attributes()->updateOrCreate(
                        [
                            'name' => $attribute['name'],
                            'integration_id' => $integrationId ?? $attribute['integration_id'] ?? null,
                            'region_id' => $regionId,
                            'product_variant_id' => $attribute['variant_id'] ?? null
                        ],
                        $attribute
                    );
                }
            }
        }

        if (!is_null($options)) {
            /* save options (for main product)*/
            if (!empty($options)) {
                $options = json_encode($options);
            } else {
                $options = "[]";
            }
            $product->attributes()->updateOrCreate(
                ['name' => 'options', 'integration_id' => $integrationId ?? null, 'region_id' => $regionId],
                ['value' => $options]
            );
        }


        /* save images */
        if (!is_null($images)) {
            
            $sumOfMainDeleted = array_reduce($images, function ($current, $image) {
                if (isset($image['deleted']) && $image['deleted']) {
                    return $current += 1;
                }
                return $current;
            }, 0);
            $sumOfMainProducts = array_reduce($images, function ($current, $image) {
                if (empty($image['product_variant_id'])) {
                    return $current += 1;
                }
                return $current;
            }, 0);
            if ($sumOfMainProducts === 0) {
                return $this->respondWithError('Can not save without images');
            }
            if ($sumOfMainProducts === $sumOfMainDeleted) {
                return $this->respondWithError('Can not delete all of images', ['arr' => $sumOfMainProducts]);
            }
            $sumOfSkuProducts = sizeof($images) - $sumOfMainProducts;
            if($sumOfSkuProducts === 0){
                return $this->respondWithError("Can not save without images of SKU");
            }

            foreach ($images as $key => $value) {
                if (isset($value['deleted']) && $value['deleted']) {
                    $productVariantId = $value['product_variant_id'] ?? null;
                    if ($productVariantId) {
                        $product->variants()->whereId($productVariantId)->first()->allImages()->where(['region_id' => $regionId, 'integration_id' => $integrationId])
                            ->orWhere(function (Builder $query) {
                                $query->whereNull('region_id')->whereNull('integration_id');
                            })->where(function ($query) use ($value) {
                                $query->where('source_url', $value['image_url'])
                                    ->orWhere('image_url', $value['image_url']);
                            })->delete();
                    } else {
                        // this is condition when get image app/Http/Controllers/Api/ProductController.php line 842
                        // ->where(['region_id' => $account->region_id,'integration_id' => $integration])
                        //   ->orWhere(function (Builder $query) {
                        //     $query->whereNull('region_id')->whereNull('integration_id');
                        // });
                        $product->allImages()
                            ->where(function ($query) use ($regionId, $integrationId) {
                                $query->where([
                                    'region_id' => $regionId,
                                    'integration_id' => $integrationId,
                                ])->orWhere([
                                    'region_id' => null,
                                    'integration_id' => null,
                                ]);
                            })->where(function ($query) use ($value) {
                                $query->where('source_url', $value['image_url'])
                                    ->orWhere('image_url', $value['image_url']);
                            })->delete();
                    }
                } else if (isset($value['data_url'])) {
                    $url = uploadImageFile($value['data_url'], $shop);

                    $product->images()->create([
                        'source_url' => $url,
                        'integration_id' => $integrationId ?? $value['integration_id'],
                        'product_variant_id' => $value['product_variant_id'] ?? null,
                        'image_url' => $url,
                        'region_id' => $regionId,
                    ]);
                } else if (isset($value['source_url'])) {
                    // Take Note, cannot use product images relationship because is using whereNull integration_id and whereNull product_variant_id
                    $productVariantId = $value['product_variant_id'] ?? null;
                    if ($productVariantId) {
                        $product->variants()->whereId($productVariantId)->first()->allImages()->whereNull('product_listing_id')->updateOrCreate([
                            'source_url' =>  $value['source_url'],
                            'integration_id' => $integrationId ?? $value['integration_id'],
                            'product_id' => $product->id,
                            'product_variant_id' => $productVariantId,
                            'region_id' => $regionId,
                        ], [
                            'image_url' => $value['image_url'],
                        ]);
                    } else {
                        $product->allImages()->whereNull('product_listing_id')->updateOrCreate([
                            'source_url' =>  $value['source_url'],
                            'integration_id' => $integrationId ?? $value['integration_id'],
                            'region_id' => $regionId,
                        ], [
                            'image_url' => $value['image_url']
                        ]);
                    }
                }
            }
        }


        /* save prices */
        if (!is_null($prices)) {
            foreach ($prices as $key => $value) {
                //if (isset($value['price'])) {
                $value['price'] = $value['price'] ?? 0;
                $productVariantId = $value['product_variant_id'] ?? null;
                if ($productVariantId) {
                    // Take Note, cannot use product prices relationship because is using whereNull product_variant_id
                    $product->variants()->whereId($productVariantId)->first()->prices()->updateOrCreate([
                        'shop_id' => $shop->id,
                        'product_listing_id' => null,
                        'integration_id' => $integrationId ?? $value['integration_id'],
                        'region_id' => $regionId,
                        'product_id' => $product->id,
                        'product_variant_id' => $productVariantId,
                        'currency' => $integrationId == Integration::SHOPIFY  && $shop->currency ? $shop->currency : $value['currency'],
                        'type' => $value['type']
                    ], [
                        'price' => $value['price']
                    ]);
                } else {
                    $product->prices()->whereNull('product_listing_id')->updateOrCreate([
                        'shop_id' => $shop->id,
                        'integration_id' => $integrationId ?? $value['integration_id'],
                        'region_id' => $regionId,
                        'currency' => $integrationId == Integration::SHOPIFY  && $shop->currency ? $shop->currency : $value['currency'],
                        'type' => $value['type']
                    ], [
                        'price' => $value['price']
                    ]);
                }
                //}
            }
        }

        return $this->respondWithMessage([], 'Saved successfully.');
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
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $this->authorize('index', Product::class);

        $task = ExportExcelTask::create([
            'shop_id' => $shop->id,
            'user_id' => auth()->user()->id,
            'source_type' => get_class($shop),
            'source' => $shop->id,
            'settings' => $request->except('now')
        ]);

        if (!empty($request->input('account_id'))) {
            //download product listings
            if ($request->input('now')) {
                $url = ProductListingExportExcelJob::dispatchNow($task->fresh());
                return $this->respondWithMessage(['url' => $url], 'Excel file generated successfully.');
            } else {
                ProductListingExportExcelJob::dispatch($task->fresh())->onQueue('export');
            }
        } else {
            //download product
            if ($request->input('now')) {
                $url = ProductExportExcelJob::dispatchNow($task->fresh());
                return $this->respondWithMessage(['url' => $url], 'Excel file generated successfully.');
            } else {
                ProductExportExcelJob::dispatch($task->fresh())->onQueue('export');
            }
        }

        return $this->respondWithMessage(null, 'Excel file will be downloaded shortly.');
    }
}
