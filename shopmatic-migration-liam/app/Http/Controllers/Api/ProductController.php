<?php

namespace App\Http\Controllers\Api;

use App\Constants\JobStatus;
use App\Constants\ProductPriceType;
use App\Constants\ProductStatus;
use App\Http\Requests\Api\UpdateProduct;
use App\Http\Requests\StoreProduct;
use App\Jobs\DeleteOrphanedProductJob;
use App\Jobs\DeleteOrphanedProductVariantJob;
use App\Jobs\ProductImportJob;
use App\Models\Account;
use App\Models\Category;
use App\Models\ExportExcelTask;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductImportTask;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTrail;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Constants\ExcelType;
use App\Models\Integration;
use App\Models\IntegrationCategoryAttribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
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
        $this->authorize('index', Product::class);
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $accounts = $request->input('accounts');
        $integration = $request->input('integration');
        $integrationType = $request->input('integration_type', 'in') === 'in';
        $orphanedProduct = $request->input('orphaned_product', false);
        $categoryId = $request->input('category_id', false);
        $type = $request->input('type', 0); // 0 - default listing, 1 - export listing
        $with = $request->input('with'); // to load related model, string separated by comma

        if (!empty($with)) {
            //$with = explode(',', $with);
            $with = ['listings.integration', 'listings.account', 'listings.account.region'];
        }
        
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = $shop->products();

        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($search)) {
            $query = $query->where(function($query) use ($search, $type) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('brand', 'LIKE', '%' . $search . '%')
                    ->orWhere('model', 'LIKE', '%' . $search . '%')
                    ->orWhere('associated_sku', 'LIKE', '%' . $search . '%')
                    ->orWhere('slug', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('variants', function($query) use ($search) {
                        $query->where('name', 'LIKE', '%' . $search . '%')
                            ->orWhere('sku', 'LIKE', '%' . $search . '%');
                    });

                if ($type == 1) {
                    $query->orWhereHas('attributes', function (Builder $query) use ($search, $type) {
                        $query->where([
                            ['value', 'like', '%' . $search . '%'],
                            ['name', '=', 'name'],
                        ])->orWhere([
                            ['value', 'like', '%' . $search . '%'],
                            ['name', '=', 'brand'],
                        ])->orWhere([
                            ['value', 'like', '%' . $search . '%'],
                            ['name', '=', 'model'],
                        ])->orWhere([
                            ['value', 'like', '%' . $search . '%'],
                            ['name', '=', 'associated_sku'],
                        ])->orWhere([
                            ['value', 'like', '%' . $search . '%'],
                            ['name', '=', 'slug'],
                        ]);
                    });
                }
            });
        }

        if (!empty($categoryId)) {
            if ($categoryId < 0) {
                $query->where('category_id', null);
            } else {
                $query->where('category_id', $categoryId);
            }
        }

        if (!empty($status)) {
            $query = $query->where('status', $status);
        }

        // type 0 is default
        if ($type == 0) {
            // doesn't belongs to any account or not in all integrations = orphaned products
            if (empty($accounts) || (empty($integration) && !$integrationType)) {
                // Category id is for bulk product update category, hence if there is category id then should return all products without filter doesnt have listings
                if (empty($categoryId)) {
                    $query = $query->whereDoesntHave('listings');
                }

                // not orphaned products
                if (!$orphanedProduct) {
                    $query->whereHas('listings');
                }

            // account and integration filter
            } elseif (!empty($accounts) || !empty($integration)) {
                $operator = $integrationType ? '=' : '<>';

                $query->where(function($query) use ($accounts, $integration, $operator, $orphanedProduct) {
                    $query->whereHas('listings', function ($query) use ($accounts, $integration, $operator, $orphanedProduct) {
                        if (!empty($accounts)) {
                            $query->whereIn('account_id', $accounts);
                        }
                        if (!empty($integration)) {
                            $query->where('integration_id', $operator, $integration);
                        }
                    });

                    // selected account or selected integration + show orphaned products
                    if ($orphanedProduct) {
                        $query->orWhereDoesntHave('listings');
                    }
                });
            }

            $query = $query->orderBy('id', 'DESC');
        }

        // query for export products, type 1
        $type = $request->input('type', 0);
        if ($type == 1) {
            $query = $this->exportQuery($query, $request);
            if (isset($query->updateItemCounter) && $query->updateItemCounter) {
                $query = $query->items();
            }
            return $this->respondPagination($request, $query);
        }
        return $this->respondPagination($request, $query->paginate($limit));
    }

    /**
     * Creates the product
     *
     * @param StoreProduct $request
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(StoreProduct $request)
    {
        $this->authorize('create', Product::class);

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $productData = $request->except('variants');

        if ($shop->products()->whereAssociatedSku($productData['associated_sku'])->exists()) {
            return $this->respondBadRequestError('Product sku already exists');
        }

        /** @var Product $product */
        $product = DB::transaction(function () use ($request, $shop, $productData) {
            /** @var Product $product */
            $product = $shop->products()->create(array_filter($productData, function ($data, $key) {
                // filter all array or object out except options (options data type is json)
                return (gettype($data) !== 'array' && gettype($data) !== 'object') || $key === 'options';
            }, ARRAY_FILTER_USE_BOTH));

            $this->handleImages($productData['images'], $shop, $product);
            $this->handlePrices($productData['prices'], $shop, $product);
            $this->handleAttributes($productData['attributes'], $product);

            foreach ($request->input('variants') as $key => $value) {
                $this->createVariant($shop, $product, $value);
            }

            return $product;
        });

        $product->refresh();
        $product->load('variants');

        // used to debug duplicate options bug
        if (count(array_count_values($product->options)) !== count($product->options)) {
            set_log_extra('request', $request->all());
            set_log_extra('product', $product);
            Log::info('Duplicate options detected.');
            $product->options = array_unique($product->options);
            $product->save();
        }

        return $this->respondWithMessage(['product_slug' => $product->slug, 'product' => $product], 'Product created successfully');
        // basic idea of the expected data
        // {
        //     'name':'',
        //     'short_description':'',
        //     'html_description':'',
        //     // and the rest of the basic property, take note below

        //     'options': {'size':'Size','color':'Color'},
        //     'category_id': 123,
        //     'price',

        //     'prices': [
        //         {
        //             'integration_id'
        //             'account_id'
        //         }
        //     ],

        //     'images': [
        //         {
        //             'data_url'
        //             // need below two
        //             'integration_id'
        //             'account_id'
        //         }
        //     ],

        //     'attributes' : [
        //         //contains all attribute from different accounts
        //     ]

        //     'variants': [
        //         {
        //             'name'
        //             'option_1'
        //             'option_2'
        //             // and the rest of the basic

        //             'inventory_id',
        //             'images': [
        //                 'data_url'
        //                 // need below two
        //                 'integration_id'
        //                 'account_id'
        //             ],

        //             'attributes' : [
        //                 //contains all attribute from different accounts
        //             ]
        //         }
        //     ]
        // }
    }

    /**
     * Shows the product
     *
     * @param Product $product
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load(['variants', 'variants.listings','variants.listings.images', 'attributes', 'listings' => function ($query) {
            $query->has('account');
        }, 'listings.account.integration', 'images', 'prices', 'unreadAlerts']);

        return $this->respond($product);
    }

    /**
     * Update product
     *
     * @param UpdateProduct $request
     * @param Product $product
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(UpdateProduct $request, Product $product)
    {
        $this->authorize('update', $product);

        // @TODO - validations
        $request->validated();

        /** @var Shop $shop */
        $shop = $request->session()->get('shop');

        $mainProductData = $request->except('variants', 'fromCreate');
        if (!empty($mainProductData)) {
            $updateProductData = [];
            $options = null;
            foreach ($mainProductData as $dataKey => $dataValue) {
                if (in_array($dataKey, ['option_1', 'option_2', 'option_3'])) {
                    if (!empty($dataValue)) {
                        $options[] = $dataValue;
                    }
                } elseif (!in_array($dataKey, ['category', 'prices', 'images', 'attributes'])) {
                    $updateProductData[$dataKey] = $dataValue;
                } elseif ($dataKey === 'category') {
                    if (isset($dataValue['id'])) {
                        $updateProductData['category_id'] = $dataValue['id'];
                    }
                }
            }

            // used to debug duplicate options bug
            if (is_array($options) && count(array_count_values($options)) !== count($options)) {
                set_log_extra('request', $request->all());
                \Log::error('Duplicate options detected.');
                $options = array_unique($options);
            }
            if (!is_null($options)) {
                $updateProductData['options'] = $options;
            }

            // update product
            $product->update(array_filter($updateProductData, function ($data, $key) {
                // filter all array or object out except options (options data type is json)
                return (gettype($data) !== 'array' && gettype($data) !== 'object') || $key === 'options';
            }, ARRAY_FILTER_USE_BOTH));

            // update name for all integration
            if (isset($updateProductData['name']) && isset($mainProductData['category']['integration_categories'])) {
                foreach ($mainProductData['category']['integration_categories'] as $integration_category) {
                    $product->attributes()
                        ->where('product_id', $product->id)
                        ->where('integration_id', $integration_category['integration_id'])
                        ->where('region_id', $integration_category['region_id'])
                        ->where('name', 'name')
                        ->update([
                            'value' => $updateProductData['name']
                        ]);
                }
            }
        }

        // Prices
        if (isset($mainProductData['prices'])) {
            foreach ($mainProductData['prices'] as $priceData) {
             $product->prices()->updateOrCreate([
                    'shop_id' => $shop->id,
                    'product_listing_id' => null,
                    'integration_id' => $priceData['integration_id'] ?? null,
                    'product_id' => $product->id,
                    'type' => $priceData['type'],
                    'currency' => $product->shop->currency ?? 'SGD'
                ], [
                    'price' => $priceData['price']
                ]);
            }
        }

        // from create mode
        $fromCreate = $request->input('fromCreate', false);
        if ($fromCreate) {
            // update images
            $this->handleImages($mainProductData['images'], $shop, $product, null, true);
            // update attributes
            $this->handleAttributes($mainProductData['attributes'], $product);
        }

        if ($request->has('variants')) {
            foreach ($request->input('variants') as $key => $value) {
                /** @var ProductVariant $variant */
                $variant = $product->variants()->find($key);

                if (is_null($variant)) {
                    $variant = $product->variants()->where('sku', $value['sku'])->first();
                }

                if (is_null($variant)) {
                    $variant = $product->variants()->find($value['id']);
                }

                if ($variant) {
                    // Variant Inventory
                    if (isset($value['inventory'])) {
                        if (isset($value['inventory']['id'])) {
                            $value['inventory_id'] = $value['inventory']['id'];
                            $value['stock'] = $value['inventory']['stock'];
                        } else {
                            /** @var ProductInventory $inventory */
                            $inventory = $product->shop->inventories()->where('sku', $value['inventory']['sku'])->first();
                            if (empty($inventory)) {
                                $inventory = ProductInventory::create($value['inventory'] + ['shop_id' => $shop->id, 'enabled' => true]);

                                ProductInventoryTrail::create([
                                    'shop_id' => $shop->id,
                                    'product_inventory_id' => $inventory->id,
                                    'message' => 'Inventory created from new product SKU: ' . $variant->sku,
                                    'related_id' => $variant->id,
                                    'related_type' => get_class($variant),
                                    'old' => $value['inventory']['stock'],
                                    'new' => $value['inventory']['stock'],
                                ]);
                            }
                            $value['inventory_id'] = $inventory->id;
                            $value['stock'] = $inventory->stock;
                        }
                    }

                    // Variant Prices
                    if (isset($value['prices'])) {
                        foreach ($value['prices'] as $key => $priceData) {
                            $variant->prices()->updateOrCreate([
                                'shop_id' => $shop->id,
                                'product_listing_id' => null,
                                'integration_id' => $priceData['integration_id'] ?? null,
                                'product_id' => $product->id,
                                'type' => $priceData['type'],
                                'currency' => $value['prices'][$key]['currency'] ?? 'SGD',
                            ], [
                                'price' => $priceData['price']
                            ]);
                        }
                    }

                    $variant->updateTempFields(false);

                    // Variant General
                    $variant->update($value);

                    // from create mode
                    if ($fromCreate) {
                        // update images
                        $this->handleImages($value['images'], $shop, $product, $variant, true);
                        // update attributes
                        $this->handleAttributes($value['attributes'], $product, $variant);
                    }
                } else {
                    $this->createVariant($shop, $product, array_merge($value, ['attributes' => []]));
                }
            }
        }
        $product->refresh();

        return $this->respondWithMessage(['product' => $product], 'Product updated successfully');
    }
  /**
     * Delete product variant
     *
     * @param Request $request
     * @param \App\Models\Product $product
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function deleteVariant(Request $request)
    {
        $product_id = $request->input('product_id');
        $deleteVariantId = $request->input('variant_id');
        if(!empty($deleteVariantId) && !empty($deleteVariantId)) {
            $product = Product::whereId($product_id)->first();
            if (empty($product)) {
                return $this->respondWithError('Cannot find product.');  
            }
            $deleteVariant = $product->variants->where('id', $deleteVariantId)->first();
            $skuNeedDelete = $deleteVariant->sku;
            if (empty($deleteVariant)) {
                return $this->respondWithError('Cannot find product variant.'); 
            }
            //Delete variant listing
            Log::info('start delete listing');
            $deleteVariantListings = $product->allListings->where('product_variant_id', $deleteVariantId);
            if($deleteVariantListings->count() > 0) {
                foreach ($deleteVariantListings as $listing) {
                    // delete listing on mp
                    // $skuNeedDelete
                    Log::info('skuNeedDelete: ' . $skuNeedDelete);
                    $adapter = $listing->account->getProductAdapter();
                    $result = $adapter->delete($listing);
                    Log::info('result: ' . $result);
                }
            }
            $deleteVariant->delete();
            return $this->respondWithMessage(null, 'Successfully delete product variant.');
        }
        return $this->respondWithError('Delete product variant failed.');    
    }

    /**
     * Delete product
     *
     * @param Request $request
     * @param \App\Models\Product $product
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(Request $request, Product $product)
    {
        $this->authorize('delete', $product);

        $deleteLocal = $request->input('delete_local');
        $deleteListingIds = $request->input('delete_listing_ids');

        if(!empty($deleteListingIds)) {

            foreach ($deleteListingIds as $id) {

                $listing = $product->listings()->where('id', $id)->first();
                if ($listing) {
                    $adapter = $listing->account->getProductAdapter();
                    $result = $adapter->delete($listing);

                    if ($result) {
                        // assume each account has one product only, cant cater if account has products with same sku
                        $product->allListings()->where('account_id', $listing->account_id)->delete();

                        // If product does not have listings set status back to draft
                        if (!$product->listings()->exists()) {
                            $product->status = ProductStatus::DRAFT();
                            $product->save();
                        }

                        if ($product->productExportTasks) {
                            // Delete product export tasks as well, else user cannot export the product again.
                            $product->productExportTasks()->whereAccountId($listing->account_id)
                                ->whereStatus(JobStatus::FINISHED()->getValue())->delete();
                        }
                    }
                }
            }
        }

        if($deleteLocal) {
            $product->delete();
        }

        return $this->respondWithMessage(null, 'Successfully queued import of products.');
    }

    /**
     * @param Request $request
     * @param Product $product
     * @param ProductListing $listing
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function updateProductListing(Request $request, Product $product, ProductListing $listing)
    {
        $this->authorize('update', $product);

        $data = $request->all();

        // replace temporary variant id with real variant id
        if (array_key_exists('variants', $data)) {
            foreach ($data['variants'] as $variantId => $variant) {
                if (!is_numeric($variantId)) {
                    /** @var ProductVariant $productVariant */
                    if ($productVariant = $product->variants->where('sku', $variant['sku'])->first()) {
                        $data['variants'][$productVariant->id] = $data['variants'][$variantId];
                        $data['variants'][$productVariant->id]['new'] = true;

                        unset($data['variants'][$variantId]);
                    }
                }
            }
        }

        $adapter = $listing->account->getProductAdapter();
        $response = $adapter->update($listing, $data);

        if ($response['meta']['error']) {
            return $this->respondWithError('Product Listing update failed.', $response);
        }

        return $this->respondWithMessage(null, 'Product Listing updated successfully');
    }

    /**
     * Handle product attributes
     *
     * @param array $attributesList
     * @param Product $product
     * @param null $variant
     * @return void
     */
    private function handleAttributes(Array $attributesList, Product $product, $variant = null)
    {
        $instance =  !is_null($variant) ? $variant : $product;

        foreach ($attributesList as $accountId => $attributes) {
            foreach ($attributes as $name => $attribute) {
                if (is_array($attribute['value'])  && !empty($attribute['value'])) {
                    if (is_associative($attribute['value'])) {
                        $attribute['value'] = $attribute['value']['value'] ?? $attribute['value']['name'] ?? null;
                    } else {
                        // if it is array of array, convert it to array of value
                        $attribute['value'] = json_encode($attribute['value']);
                    }
                }
                if (!is_null($attribute['value']) && !empty($attribute['value'])) {
                    $instance->attributes()->updateOrCreate([
                        'product_id'         => $product->id,
                        'product_variant_id' => $variant->id ?? null,
                        'product_listing_id' => $attribute['product_listing_id'] ?? null,
                        'integration_id'     => $attribute['integration_id'] ?? null,
                        'region_id'          => $attribute['region_id'] ?? null,
                        'name'               => $name,
                    ], [
                        'value'              => $attribute['value'],
                    ]);
                }
            }
        }
    }

    /**
     * Handle product images
     *
     * @param array $images
     * @param Shop $shop
     * @param Product $product
     * @param null $variant
     * @param bool $removeOld
     * @return void
     */
    private function handleImages(Array $images, Shop $shop, Product $product, $variant = null, $removeOld = false)
    {
        $instance =  !is_null($variant) ? $variant : $product;

        if ($removeOld) {
            $instance->allImages()->whereNull('product_listing_id')->delete();
        }
        foreach ($images as $key => $value) {
            if (isset($value['data_url'])) {
                $image = uploadImageFile($value['data_url'], $shop);
                // testing locally use this
//                $image = 'https://cdn.shopify.com/s/files/1/0246/4137/2234/products/bba0066034d523c12c251cd08ec847d80_cea1d791-dbea-4c48-a5d0-2273b326ec93.jpg';
                $instance->images()->create([
                    'product_id' => $product->id,
                    'image_url' => $image,
                    'source_url' => $image,
                    'position' => $key,
                    'integration_id' => $value['integration_id'] ?? null,
                    'region_id' => isset($value['integration_id']) && !empty($value['integration_id']) ? $value['region_id'] : null,
                ]);

                if ($key == 0) {
                    $instance->update([
                        'main_image' => $image
                    ]);
                }
            } else if (isset($value['deleted'])) {
                $instance->images()->whereId($value['id'])->delete();
            }

        }
    }

    /**
     * Handle product prices
     *
     * @param array $prices
     * @param Shop $shop
     * @param Product $product
     * @param null $variant
     * @return void
     */
    private function handlePrices(Array $prices, Shop $shop, Product $product, $variant = null)
    {
        $instance =  !is_null($variant) ? $variant : $product;

        foreach ($prices as $value) {
            $instance->prices()->create([
                'product_id' => $product->id,
                'currency' => $shop->currency ?? '$',
                'price' => $value['price'],
                'type' => $value['type'],
                'shop_id' => $shop->id,
                'integration_id' => $value['integration_id'] ?? null
            ]);

            if (!is_null($variant) && $value['type'] === ProductPriceType::SELLING()) {
                $instance->update([
                    'price' => $value['price'],
                    'currency' => $shop->currency,
                ]);
            }
        }
    }

    private function createVariant(Shop $shop, Product $product, array $variantData)
    {
        $variantData['shop_id'] = $shop->id;
        // create inventory if inventory not found
        if (isset($variantData['inventory']['id']) && $shop->inventories()->whereId($variantData['inventory']['id'])->exists()) {
            $variantData['inventory_id'] = $variantData['inventory']['id'];
            $variantData['stock'] = $variantData['inventory']['stock'];
        } else {
            /** @var ProductInventory $inventory */
            $inventory = $shop->inventories()->where('sku', $variantData['inventory']['sku'])->first();
            if (empty($inventory)) {
                $inventory = ProductInventory::create($variantData['inventory'] + ['shop_id' => $shop->id, 'enabled' => true]);

                ProductInventoryTrail::create([
                    'shop_id' => $shop->id,
                    'product_inventory_id' => $inventory->id,
                    'message' => 'Inventory created from new product SKU: ' . ($variantData['sku'] ?? ('-. Product ID: ' . $product->id)),
                    'related_id' => $product->id,
                    'related_type' => get_class($product),
                    'old' => $variantData['inventory']['stock'],
                    'new' => $variantData['inventory']['stock'],
                ]);
            }
            $variantData['inventory_id'] = $inventory->id;
            $variantData['stock'] = $inventory->stock;
        }

        /** @var ProductVariant $variant */
        $variant = $product->variants()->create($variantData);
        $this->handleImages($variantData['images'], $shop, $product, $variant);
        $this->handlePrices($variantData['prices'], $shop, $product, $variant);
        $this->handleAttributes($variantData['attributes'], $product, $variant);
    }

    /**
     * Imports the products for the account
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
        $this->authorize('create', Product::class);

        $task = ProductImportTask::where([
            'source_type' => get_class($account),
            'source' => $account->id,
        ])->where(function($query) {
            $query->where('status', JobStatus::PENDING())
                  ->orWhere('status', JobStatus::PROCESSING());
        })->count();
        if (!empty($task)) {
            return $this->respondBadRequestError('You have already queued to import products for this integration! Please wait for it to finish before attempting again.');
        }

        # Create a product import task
        if ($account->hasFeature(['products', 'import_products'])) {
            $task = ProductImportTask::create([
                'shop_id' => $account->shop_id,
                'user_id' => Auth::user()->id,
                'source_type' => get_class($account),
                'source' => $account->id,
                'settings' => $request->input()
            ]);

            ProductImportJob::dispatch($task->fresh())->onQueue('import_products');
        }

        return $this->respondWithMessage(null, 'Successfully queued import of products for account.');
    }

    /**
     * Returns the listing of past import tasks and their status
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function importTasks(Request $request)
    {
        $this->authorize('create', Product::class);

        $shop = $request->session()->get('shop');

        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
        $users = $shop->productImportTasks()->latest()->paginate($limit);
        return $this->respondPagination($request, $users);
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
        $this->authorize('create', Product::class);
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');

        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);

        $query = ($request->get('type') === 'excel') ? $shop->exportExcelTasks() : $shop->productExportTasks();

        if ($request->get('status')) {
            $query->whereIn('status', explode(',', $request->get('status')));
        }

        if ($request->get('type') === 'excel') {
            if ($request->get('source_type') && $request->get('source_type') == ExcelType::DOWNLOAD_INVENTORY()->getValue()) {
                $query->where('source_type',ExcelType::DOWNLOAD_INVENTORY()->getValue());
            } else {
                $query->where('source_type','<>',ExcelType::DOWNLOAD_INVENTORY()->getValue());
            }
        }

        if ($request->get('count_unread')) {
            return $this->respond($query->where('downloaded_status', false)->count());
        } else {
            $users = $query->latest()->paginate($limit);
        }

        return $this->respondPagination($request, $users);
    }

    public function updateExportTask(ExportExcelTask $exportExcelTask, Request $request)
    {
        if ($exportExcelTask->update($request->all())) {
            return $this->respondWithMessage(null, 'Successfully update task.');
        }
        return $this->respondWithError('Unable to update task');

    }

    /**
     * Query to get products for export
     * Get categories from cache
     *
     * @param $query
     * @param Request $request
     * @return
     */
    public function exportQuery($query, Request $request)
    {
        $account = Account::find($request->input('account'));
        $integration = $request->input('integration');
        $limit = min(intval($request->get('limit', 10)), DEFAULT_MAX_LIMIT);
        $integrationCategory = $request->input('integration_category', false);

        $query = $query->with([
            'allImages' => function($query) use ($account,$integration) {
                $query->select('id', 'product_id', 'source_url', 'image_url');
                $query->where(function (Builder $query) use ($integration, $account) {
                    $query->where(['region_id' => $account->region_id,'integration_id' => $integration])
                          ->orWhere(function (Builder $query) {
                            $query->whereNotNull('product_listing_id')->whereNull('product_variant_id');
                        });
                })
                ->orderBy('integration_id', 'asc')
                ->orderBy('region_id', 'asc');
            },
            'prices' => function ($query) use ($integration, $account) {
                $query->select('id', 'product_id', 'product_variant_id', 'currency', 'price', 'type', 'updated_at');
                $query->where(function (Builder $query) use ($integration, $account) {
                    $query->where(['region_id' => $account->region_id, 'integration_id' => $integration])->orWhere(function (Builder $query) {
                        $query->whereNull('region_id')->whereNull('integration_id');
                    });
                })
                ->orderBy('updated_at', 'desc');
            },
            'attributes' => function($query) use ($integration,$account)  {
                $query->select('id', 'product_id', 'product_listing_id', 'integration_id', 'region_id', 'name', 'value');

                $query->whereIntegrationId($integration)->whereNull('product_listing_id')

                ->where('region_id', $account->region_id);
                // $query->whereIntegrationId($integration)->whereNull('product_listing_id')->where('region_id', $account->region_id);
            },
            'variants',
            'variants.images' => function($query) use ($integration) {
                $query->select('id', 'product_id', 'source_url', 'image_url');

                $query->whereIntegrationId($integration);
            },
            'variants.allImages' => function ($query) use ($account,$integration) {
                $query->select('id', 'product_id', 'product_variant_id', 'source_url', 'image_url');

                $query->where(function (Builder $query) use ($integration, $account) {
                    $query->where(function (Builder $query) {
                            $query->whereNotNull('product_listing_id')->whereNotNull('region_id');
                        });
                })
                ->orderBy('integration_id', 'asc')
                ->orderBy('region_id', 'asc');
            },
            'variants.prices' => function ($query) use ($integration, $account) {
                $query->select('id', 'product_id', 'product_variant_id', 'currency', 'price', 'type', 'updated_at');
                $query->where(function (Builder $query) use ($integration, $account) {
                    $query->where(['region_id' => $account->region_id, 'integration_id' => $integration])
                    ->orWhere(function (Builder $query) {
                        $query->whereNull('region_id')->whereNull('integration_id');
                    });
                })
                ->orderBy('updated_at', 'desc');
            },
            'variants.attributes' => function ($query) use ($integration,$account) {
                $query->whereIntegrationId($integration)->where('region_id',$account->region_id);
            },
            'productExportTasks',

            'attributesByIntergrationCategory.intergrationCategory' => function($query) use ($account) {

                $query->where('integration_id', $account->integration_id)

                    ->where('region_id', $account->region_id);

            },])->where(function ($query) use ($account) {
                $query->whereDoesntHave('listings', function (Builder $q) use ($account) {
                    $q->whereAccountId($account->id);
                });
        });

        if ($request->get('category')) {
            $query = $query->whereCategoryId($request->get('category'));
        }

        if ($request->get('integration_category')) {
            $query  = $query->where( function (Builder $q) use ($account, $integration,$request) {
                return $q->whereHas('attributes', function($query) use ($account, $integration, $request)  {
                    $query->whereIntegrationId($integration)
                        ->whereName('integration_category_id')
                        ->whereRegionId($account->region_id)
                        ->whereValue($request->get('integration_category'));
                })
                ->orWhere( function ($query) use ($integration,$account) {
                    $query->whereDoesntHave('attributes', function (Builder $query) use ($integration,$account) {
                        $query->whereIntegrationId($integration)
                        ->whereRegionId($account->region_id)
                        ->whereName('integration_category_id');
                    });
                });
            });
        }

        if ($integration == Integration::LAZADA && $integrationCategory) {
            $integrationCategoryModel = IntegrationCategory::find($integrationCategory);
            if ($integrationCategoryModel && $integrationCategoryModel->isIntegrationCategoryNotHaveAttributeIsSaleProp()) {
                $query->whereHas('variants', function () {
                }, '=', 1);
            }
        }

        $products = $query->addSelect([
            'category_breadcrumb' => Category::select('breadcrumb')->whereColumn('category_id', 'categories.id')
            ])
            ->whereNotNull('category_id')
            ->orderBy('category_id', 'desc')
            ->paginate($limit);
        // retrieve integration category id
        $updateItemCounter = 0;
        foreach ($products as $key=>$product) {
            $integrationCategory = $product->attributes()->where('integration_id',$account->integration_id)->where('name', 'integration_category_id')->where(function (Builder $query) use ($account) {
                $query->whereRegionId($account->region_id)->orWhereNull('region_id');
            })->orderBy('region_id', 'desc')->first();

            if ($integrationCategory) {
                $integrationCategory = IntegrationCategory::where([
                    'id' => $integrationCategory->value,
                    'integration_id' => $account->integration_id,
                    'region_id' => $account->region_id,
                ])->active()->first();
                if($request->get('integration_category') && isset($integrationCategory->id) && $integrationCategory->id != $request->get('integration_category')) {
                  unset($products[$key]);
                  $updateItemCounter = 1;
                }
            }
            $product->integration_category = $integrationCategory;
        }
        if(!empty($updateItemCounter)){
            $products->updateItemCounter = $updateItemCounter;
        }
        return $products;
    }

    /**
     * Toggle product variant listing status enable/disable
     *
     * @param Request $request
     * @param ProductListing $listing
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function toggleEnable(Request $request, ProductListing $listing)
    {
        $this->authorize('update', $listing);
        $enabled = $request->input('enabled', true);

        try {
            $adapter = $listing->account->getProductAdapter();
            $adapter->toggleEnable($listing, $enabled);
        } catch (\Exception $exception) {
            return $this->respondBadRequestError($exception->getMessage());
        }

        return $this->respondWithMessage(null, 'Successfully update status.');
    }

    /**
     * Delete Orphaned Products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteOrphanedProducts(Request $request)
    {
        $this->authorize('index', Product::class);

        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        DeleteOrphanedProductJob::dispatchNow($shop);
        return $this->respondWithMessage(null, 'Successfully delete orphaned products.');
    }

    /**
     * Delete Orphaned Product Variants
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteOrphanedProductVariants(Request $request)
    {
        $this->authorize('index', Product::class);

        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        DeleteOrphanedProductVariantJob::dispatchNow($shop);
        return $this->respondWithMessage(null, 'Successfully delete orphaned product variants.');
    }

    /**
     * Bulk upddate products category
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bulkUpdateCategory(Request $request)
    {
        $this->authorize('index', Product::class);
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        $products = $request->input('products', true);
        $categoryId = $request->input('category_id', true);

        $products = $shop->products()->whereIn('id', $products)->update([
            'category_id' => $categoryId
        ]);

        return $this->respondWithMessage(null, 'Successfully assigned all selected products to category.');
    }
}
