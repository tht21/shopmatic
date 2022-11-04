<?php

namespace App\Integrations\Shopify;

use App\Constants\Dimension;
use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductIdentifier;
use App\Constants\ProductPriceType;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use App\Events\ProductFailedToImport;
use App\Integrations\AbstractProductAdapter;
use App\Integrations\TransformedProduct;
use App\Integrations\TransformedProductImage;
use App\Integrations\TransformedProductListing;
use App\Integrations\TransformedProductPrice;
use App\Integrations\TransformedProductVariant;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Shop;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\Log;

class ProductAdapter extends AbstractProductAdapter
{
    /**
     * Retrieves a single product
     *
     * @param ProductListing $listing
     * @param bool $update Whether or not to update the product if it already exists
     * @param null $itemId
     * @return mixed
     * @throws \Exception
     */
    public function get($listing, $update = false, $itemId = null)
    {
        $externalId = null;
        if ($itemId) {
            $externalId = $itemId;
        } elseif ($listing) {
            /*
            * Check whether it have main product external id
            * If there is same sku, then it might have chance getting the wrong external id
            * So will check the main product external id first
            **/
            $externalId = $listing->getIdentifier(ProductIdentifier::PRODUCT_ID());

            if (empty($externalId)) {
                // Need to make sure is main product listing
                if (!empty($listing->listing) && !is_null($listing->listing)) {
                    $listing = $listing->listing;
                }

                $externalId = $listing->identifiers['external_id'];
            }
        }

        if (!empty($externalId)) {
            $response = $this->client->request('get',  '/admin/api/2020-07/products/'.$externalId.'.json');
        } else {
            set_log_extra('identifiers', $externalId);
            set_log_extra('item_id', $itemId);
            set_log_extra('listing', $listing);
            throw new \Exception('No product id found, unable to retrieve product from shopify');
        }

        if ($response->getStatusCode() === 200) {
            $product = json_decode($response->getBody()->getContents(), true);

            try {
                $product = $this->transformProduct($product['product']);
            } catch (\Exception $e) {
                set_log_extra('product', $product);
                throw $e;
            }

            return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
        } else {
            set_log_extra('identifiers', $externalId);
            set_log_extra('response', $response);
            $exceptionMessage ='Unable to retrieve product for Shopify|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id.'|ExternalId'.$externalId;
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * Import all products from shopify
     *
     * @param $importTask
     * @param array $config
     * @return boolean
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        $filters = [
            'limit' => 100,
            'published_status' => 'any',
            'updated_at_min' => date(DATE_ISO8601, strtotime(now()->subYears(2)))
        ];

        $products = $this->fetchProducts($filters);

        /* store total products for this import */
        if (!empty($importTask) && empty($importTask->total_products)) {
            $importTask->total_products = count($products);
            $importTask->save();
        }

        foreach ($products as $product) {
            if (!empty($product)) {
                try {
                    $product = $this->transformProduct($product);
                    $this->handleProduct($product, $config);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    \Sentry\captureException($e);

                    event(new ProductFailedToImport($importTask, (is_array($product) ? $product['title'] : $product->associatedSku) . ' failed to import'));

                    continue;
                }
            }
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }

        return true;
    }

    /**
     * Import all new categories from shopify
     *
     * @param $importTask
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function importCategories($importTask)
    {
        /*try {
            $categories = $this->retrieveCategories();

            if (!empty($categories)) {
                DB::transaction(function() use ($importTask, $categories) {
                    if (!empty($importTask) && empty($importTask->total_categories)) {
                        $importTask->total_categories = count($categories);
                        $importTask->save();
                    }

                    $this->updateAccountCategories($categories);
                });
            }
            return true;
        } catch (\Exception $exception) {
            set_log_extra('account_category', $exception);
            throw new \Exception('Shopify-'.$this->account->id.' Unable to connect and retrieve categories.');
        }*/
    }

    /**
     * Get products from shopify integration
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function fetchProducts($filters = [])
    {
        $nextPageToken = null;
        $products = [];

        do{
            if ($nextPageToken) $filters['page_info'] = $nextPageToken;

            $response = $this->client->request('get', 'admin/api/2020-07/products.json', ['query' => $filters]);

            if ($response->getStatusCode() === 200) {
                // support cursor-based pagination
                $responseHeaders = $response->getHeaders();
                $pageToken = null;
                if(array_key_exists('Link', $responseHeaders)) {
                    // in the header response will see link : ... with rel next or previous.
                    // extract the page_info and then make another call with page_info.
                    $link = $responseHeaders['Link'][0];
                    $tokenType  = strpos($link,'rel="next') !== false ? "next" : "previous";
                    $tobeReplace = ["<",">",'rel="next"',";",'rel="previous"'];
                    $tobeReplaceWith = ["", "", "", ""];
                    parse_str(parse_url(str_replace($tobeReplace, $tobeReplaceWith, $link),PHP_URL_QUERY),$op);
                    $pageToken[$tokenType] = trim($op['page_info']);
                }
            } else {
                set_log_extra('code', $response->getStatusCode());
                set_log_extra('response', $response);
                set_log_extra('body', json_decode($response->getBody()->getContents(), true));
                $exceptionMessage ='Unable to retrieve products for Shopify|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id;
                throw new \Exception($exceptionMessage);
            }
            $response = json_decode($response->getBody()->getContents(), true);

            if (isset($response['products']) && !empty($response['products'])) {
                foreach ($response['products'] as $product) {
                    $products[] = $product;
                }
            }

            // set page token and remove any filers as the filters will be applied from the first call else will return error
            if (isset($pageToken['next']) && !empty($pageToken['next'])) {
                $nextPageToken = $pageToken['next'];
                $limit = $filters['limit'] ?? 50;
                $filters = ['limit' => $limit]; // only accept limit
            } else {
                $nextPageToken = null;
            }
        } while ($nextPageToken != null);

        return $products;
    }

    /**
     * Get custom collections from shopify integration
     *
     * @param array $filters
     * @param int $page
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|\Exception
     */
    public function getCustomCollectionsByPage($filters = [], $page = 1)
    {
        $filters = array_merge($filters, ['limit' => 250, 'page' => $page]);
        try {
            $response = $this->getCustomCollections($filters);
        } catch (\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Shopify-'.$this->account->id.' Unable to connect and retrieve custom collections.');
        }

        if ($response->getStatusCode() === 200) {
            $response = json_decode($response->getBody()->getContents(), true);

            return $response['custom_collections'];
        }
        return [];
    }

    /**
     * Get category listing
     *
     * @param array $filters
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function getCategories($filters = [])
    {
        return $this->client->request('get', 'admin/api/2020-07/custom_collections.json', ['query' => $filters]);
    }

    /**
     * Get custom collection count
     *
     * @param array $filters
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function getCustomCollectionCount($filters = [])
    {
        return $this->client->request('get', 'admin/api/2020-07/custom_collections/count.json', ['query' => $filters]);
    }

    /**
     * Get custom collection listing
     *
     * @param array $filters
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getCustomCollections($filters = [])
    {
        return $this->client->request('get', 'admin/api/2020-07/custom_collections.json', ['query' => $filters]);
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function sync()
    {
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $filters = [
            'limit' => 100,
            'published_status' => 'any',
            'updated_at_min' => date(DATE_ISO8601, strtotime(now()->subYears(2)))
        ];

        $products = $this->fetchProducts($filters);

        foreach ($products as $product) {
            if (!empty($product)) {
                try {
                    $product = $this->transformProduct($product);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }
                $this->handleProduct($product);
            }
        }
    }

    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function update(ProductListing $product, array $data)
    {
        $itemId = $data['identifiers']['external_id'] ?? $product->identifiers['external_id'];

        $productParams = [
            'id' => $itemId,
            'title' => $data['attributes']['name']['value'] ?? $data['name'],
            'body_html' => $data['attributes']['html_description']['value'] ?? $data['html_description'],

            'variants' => [],
            'images' => [],

            'product_type' => $data['attributes']['product_type']['value'],
            'tags' => $data['attributes']['tags']['value'],
            'vendor' => $data['attributes']['vendor']['value']
        ];

        // create images
        foreach ($data['images'] as $name => $value) {
            if (!isset($value['deleted'])) {
                if (isset($value['data_url'])) {
                    $src = uploadImageFile($value['data_url'], session('shop'));
                } else {
                    $src =  $value['image_url'];
                }

                $productParams['images'][] = [
                    'src' => $src
                ];
            }
        }

        $variants = collect($data['variants']);

        // create options
        $optionsParam = [];
        $i = 1;
        foreach ($product->product->options as $key => $value) {
            $optionsParam[] = [
                'name' => $value,
                'values' => $variants->unique('option_'.$i)->pluck('option_'.$i)->toArray()
            ];
            $i++;
        }

        $variantsParam = [];
        foreach ($data['variants'] as $key => $variant) {

            // upload variant image to get the id
            if (count($variant['images']) > 0) {
                $imageId = null;
                foreach ($variant['images'] as $key => $value) {
                    if (!isset($value['deleted'])) {
                        $imageId = $this->uploadImage($product, $value);
                    }
                }

                if (!is_null($imageId)) {
                    $productParams['images'][] = ['id' => $imageId];
                }
            }

            $prices = collect($variant['prices']);
            $sellingPrice = $prices->where('type', 'selling')->first()['price'];
            $variantParam = [
                'option1' => $variant['name'],
                'stock' => (int) $variant['inventory']['stock'],
                'price' => (float) $sellingPrice,
                'compare_at_price' => (isset($prices->where('type', 'retail')->first()['price']) && $prices->where('type', 'retail')->first()['price'] > 0) ? (float) $prices->where('type', 'retail')->first()['price'] : '',
                'sku' => $variant['sku'],
                'image_id' => $imageId ?? null
            ];
            if (isset($variant['attributes'])) {
                $variantParam['weight'] = $variant['attributes']['weight']['value'] ?? $variant['weight'];
                $variantParam['weight_unit'] = $variant['attributes']['weight_unit']['value'] ?? '';
                $variantParam['barcode'] = $variant['attributes']['barcode']['value'] ?? '';
                $variantParam['inventory_policy'] = $variant['attributes']['inventory_policy']['value'] == 'Yes' ? 'continue' : 'deny';
                $variantParam['inventory_management'] = $variant['attributes']['inventory_management']['value'] == 'Yes' ? 'shopify' : '';
            }
            // id for edit
            if (isset($variant['identifiers'])) {
                $variantParam['id'] = $variant['identifiers']['external_id'];
            }
            // options
            for ($i = 1; $i <= 3; $i++) {
                if (!empty($variant['option_'.$i])) {
                    $variantParam['option'.$i] = $variant['option_'.$i];
                }
            }

            $variantsParam[] = $variantParam;

            // update inventory (add new variant no need use this)
            if (isset($variant['identifiers'])) {
                // @TODO - update inventory (cost, and physical product)
                $variantListing = $product->listing_variants()->whereJsonContains('identifiers->external_id', $variant['identifiers']['external_id'])->first();

                $inventoryItemId = $variantListing->data->raw_data['inventory_item_id'];
                $variantInventoryParam = [
                    'inventory_item' => [
                        'id' => $inventoryItemId,
                        'requires_shipping' => $variant['attributes']['requires_shipping']['value'] == 'Yes' ? true : false
                    ]
                ];

                $r = $this->client->request('PUT', '/admin/api/2020-07/inventory_items/'.$inventoryItemId.'.json', [RequestOptions::JSON => $variantInventoryParam]);
            }
        }

        if(count($optionsParam) > 0) {
            $productParams['options'] = count($optionsParam) === 0 ? null : $optionsParam;
        }
        else {
            Log::info('Shopify update product ' . $itemId . ' has no options');
        }
        $productParams['variants'] = $variantsParam;

        // @TODO - update collection

        $response = $this->client->request('PUT', 'admin/api/2020-07/products/'.$itemId.'.json', [
            RequestOptions::JSON => ['product' => $productParams]
        ]);
        $content = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200) {
            set_log_extra('response', $content);
            $message = null;
            foreach ($content['errors'] as $error) {
                if (!$message) {
                    $message = $error[0];
                }
            }
            return $this->respondWithError($message);
        }
        $this->get($product, true);

        return $this->respond($content);
    }

    /**
     * Upload image to product
     *
     * @param Product $product
     * @param array $image
     *
     * @return id | null
     */
    private function uploadImage($product, $image)
    {
        if (isset($image['data_url'])) {
            $src = uploadImageFile($image['data_url'], session('shop'));
        } else {
            $src =  $image['image_url'];
        }

        $variantImage = [
            'image' => [
                'src' => $src
            ]
        ];

        $response = $this->client->request('post', 'admin/api/2020-07/products/'.$product->identifiers['external_id'].'/images.json', [
            RequestOptions::JSON => $variantImage
        ]);

        if ($response->getStatusCode() === 200) {
            $response = json_decode($response->getBody()->getContents(), true);

            return $response['image']['id'];
        }

        return null;
    }

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     *
     * @return array|bool
     * @throws \Exception
     */
    public function canCreate(Product $product)
    {
        parent::canCreate($product);

        // If there is multiple variants then user must provide options
        if ($product->variants->count() > 1) {
            $attributes = $product->attributes->where('product_variant_id', null)
                ->where('integration_id', $this->account->integration_id)
                ->where('region_id', $this->account->region_id)
                ->mapWithKeys(function ($item) {
                    return [$item['name'] => $item];
                });

            $productOptions = (isset($attributes['options'])) ? json_decode($attributes['options']->value, true) : $product->options;
            if (count($productOptions) <= 0) {
                $this->errors[] = 'If there is more than one variant please provide at least one options.';
            }
        }

        if (count($this->errors) > 0) {
            return $this->respondWithError($this->errors);
        } else {
            return $this->respond(null);
        }
    }

    /**
     * Creates a new product on the account from the product model
     *
     * @param \App\Models\Product $product
     * @return mixed
     * @throws \Exception
     */
    public function create(Product $product)
    {
        $integrationId = Integration::SHOPIFY;
        // pre-load required relation data
        $this->preLoadProductData($product);

        // Get main product attributes
        $attributes = $product->attributes->where('product_variant_id', null)
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });
        $productTitle = $attributes['name']->value ?? $product->name;

        $productSku = $attributes['sku']->value ?? $product->associated_sku;

        if(!empty($product->associated_sku)){
            $productSku = $product->associated_sku;
        }

        $productParams['product'] = [
            'title' => $productTitle,
            'body_html' => strip_tags($attributes['html_description']->value ?? $product->html_description),
            'vendor' => $attributes['vendor']->value ?? '',
            'product_type' => $attributes['product_type']->value ?? '',
            'images' => [],
            'sku' => $productSku,
            'associated_sku' => $productSku,
            'barcode' => $attributes['barcode']->value ?? '',
        ];

        // TAKE NOTE, tags input need change to multi tags
        if (isset($attributes['tags'])) {
            $productParams['product']['tags'][] = $attributes['tags']->value;
        }

        // If dun have images with integration_id and region_id, then init just get images with NULL integration_id and NULL region_id
        $images = $product->allImages()->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->get();
        if ($images->isEmpty()) {
            $images = $product->allImages()->where('integration_id', null)->where('region_id', null)->get();
        }
        if (count($images)) {
            foreach ($images as $key => $image) {
                $productParams['product']['images'][] = [
                    'src' => $image->image_url
                ];
            }
        }

        // Variants
        $variantImageIndexes = [];
        $priceTypes = [];
        foreach (Constant::PRICES() as $priceType) {
            $priceTypes[] = $priceType->getValue();
        }
        $account = $this->account;
        foreach ($product->variants as $key => $variant) {
            // Get all variant attributes
            $variantAttributes = $variant->attributes
                ->where('integration_id', $this->account->integration_id)
                ->where('region_id', $this->account->region_id)
                ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

            // Variant prices
            $prices = $variant->prices()->whereIn('type', $priceTypes)->where(function (Builder $query) use ($account, $product, $variant) {
                $query->whereProductId($product->id)->whereProductVariantId($variant->id)->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
            })->orWhere(function (Builder $query) use ($account, $product, $variant) {
                $query->whereProductId($product->id)->whereProductVariantId($variant->id)->whereNull('region_id')->whereNull('integration_id');
            })
            ->orderBy('integration_id', 'asc')
            ->orderBy('region_id', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item['type'] => $item];
            });

            // Convert for category attribute
            $inventory_policy = null;
            if (isset($variantAttributes['inventory_policy']->value) && $variantAttributes['inventory_policy']->value == 'Yes') {
                $inventory_policy = 'continue';
            }
            $requires_shipping = false;
            if (isset($variantAttributes['requires_shipping']->value) && $variantAttributes['requires_shipping']->value == 'Yes') {
                $requires_shipping = true;
            }
            $productParams['product']['variants'][$key] = [
                'option1' => $variantAttributes['name']->value ?? $variant->name,
                'inventory_quantity' => (int) $variant->inventory->stock ?? 0,
                'sku' => $variant->sku,
                'price' => (float) (isset($prices[ProductPriceType::SELLING()->getValue()])) ? $prices[ProductPriceType::SELLING()->getValue()]->price : 0,
                'compare_at_price' => (float) (isset($prices[ProductPriceType::RETAIL()->getValue()]) && $prices[ProductPriceType::RETAIL()->getValue()]->price > 0) ? (float) $prices[ProductPriceType::RETAIL()->getValue()]->price : '',
                'weight' => ($variantAttributes['weight']->value) ?? $variant->weight,
                'weight_unit' => (!isset($variantAttributes['weight_unit']->value) || $variantAttributes['weight_unit']->value === '[]') ? '' : $variantAttributes['weight_unit']->value,
                'barcode' => $variantAttributes['barcode']->value ?? '',
                "inventory_management" => 'shopify',
                "inventory_policy" => $inventory_policy,
                "requires_shipping" => $requires_shipping,
            ];
            // For inventory policy, if is not continue need to unset it cannot pass anything even use 'deny' also cannot
            if (!$productParams['product']['variants'][$key]['inventory_policy']) {
                unset($productParams['product']['variants'][$key]['inventory_policy']);
            }

            // If dun have variant images with integration_id and region_id, then init just get images with NULL integration_id and NULL region_id
            $variantImages = $variant->allImages()->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->get();
            if ($variantImages->isEmpty()) {
                $variantImages = $variant->allImages()->where('integration_id', null)->where('region_id', null)->get();
            }

            if (count($variantImages)) {
                // Get the last index plus one
                $imageIndex = (!empty($productParams['product']['images'])) ? array_key_last($productParams['product']['images']) + 1: 0;
                $variantImageIndexes[] = [
                    'variantIndex' => $key,
                    'imageIndex' => $imageIndex
                ];

                // Add into main product image then associate to variant
                foreach ($variantImages as $image) {
                    $productParams['product']['images'][] = [
                        'src' => $image->image_url
                    ];
                }
            }
        }

        $productOptions = (isset($attributes['options'])) ? json_decode($attributes['options']->value, true) : $product->options;
        // Get options level by integration
        $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;
        if (count($productOptions) > 0) {
            $i = 1;
            // Loop main product option
            foreach ($productOptions as $optionKey => $optionName) {
                // Support until 3 levels
                if ($i <= $optionsLevels) {
                    $optionValues = [];
                    // Get variant option
                    foreach ($product->variants as $key => $variant) {
                        // Get from product attributes, if does not exists then retrieve from product variants
                        $optionAttribute = $variant->attributes->where('integration_id', $this->account->integration_id)
                            ->where('region_id', $this->account->region_id)
                            ->where('name', 'option_'.$i)
                            ->first();
                        if ($optionAttribute) {
                            $option = $optionAttribute->value;
                        } else {
                            // If does not exists then get from product variant table
                            $option = $variant->{'option_'.$i};
                        }
                        $productParams['product']['variants'][$key]['option'.$i] = $optionValues[] = $option;
                    }
                    // Assign options main product options
                    $productParams['product']['options'][] = [
                        'name' => $optionName,
                        'values' => $optionValues
                    ];
                }
                $i++;
            }
        }

        // Shopify - Exceeded 2 calls per second for api client, so sleep for 1 sec only create product
        sleep(1);
        $response = $this->client->request('POST',  '/admin/api/2020-07/products.json', [RequestOptions::JSON => $productParams]);
        if ($response->getStatusCode() !== 201) {
            if ($response->getStatusCode() == 422) {
                $errorResponse = json_decode($response->getBody()->getContents(), true);
                if(isset($errorResponse['errors'],$errorResponse['errors']['handle'])) {
                    $errorMessage = sprintf('Product with title:%s is already created.Please provide a different title for product sku: %s.',$productTitle,$productSku);
                    return $this->respondWithError($errorMessage);
                }
            }
            return $this->respondWithError($response->getBody()->getContents());
        } else {
            $item = json_decode($response->getBody()->getContents(), true);
            if (!empty($variantImageIndexes)) {
                $this->updateVariantImage($item, $variantImageIndexes);
            }

            // Shopify - Exceeded 2 calls per second for api client, so sleep for 1 sec only call to retrieve product
            sleep(1);
            $product = $this->get(null, true, $item['product']['id']);
            return $this->respondCreated($product);
        }
    }

    /**
     * Update variant images
     *
     * @param $product
     * @param $imageIndexes
     * @return bool
     */
    public function updateVariantImage($product, $imageIndexes)
    {
        $variants = $product['product']['variants'];
        $images = $product['product']['images'];

        foreach ($imageIndexes as $imageIndex) {
            if (!is_null($imageIndex['imageIndex'])) {
                $variantId = $variants[$imageIndex['variantIndex']]['id'] ?? null;
                $imageId = $images[$imageIndex['imageIndex']]['id'] ?? null;

                if ($variantId && $imageId) {
                    $data['variant'] = [
                        'id' => $variantId,
                        'image_id' => $imageId
                    ];

                    $response = $this->client->request('PUT',  '/admin/api/2020-07/variants/'.$variantId.'.json', [RequestOptions::JSON => $data]);
                    /*if ($response->getStatusCode() === 200) {

                    } else {
                        return $this->respondWithError($response->getBody()->getContents());
                    }*/
                }
            }
        }
        return true;
    }

    /**
     * Deletes the product from the integration
     *
     * @param ProductListing $listing
     * @return bool
     * @throws \Exception
     */
    public function delete(ProductListing $listing)
    {
        if ($listing->getIdentifier(ProductIdentifier::EXTERNAL_ID())) {
            $response = $this->client->request('delete',  '/admin/api/2022-07/products/' . $listing->getIdentifier(ProductIdentifier::PRODUCT_ID()) . '/variants/' . $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID()) . '.json');
            if ($response->getStatusCode() === 200) {
                try {
                    // Delete product listing variant
                    $listing->listing_variants()->delete();

                    // Delete product listing
                    $listing->delete();
                } catch (\Exception $e) {
                    set_log_extra('product', $listing);
                    throw $e;
                }
                return true;
            } else {
                set_log_extra('listing', $listing);
                set_log_extra('response', $response);
                throw new \Exception('Unable to delete product from Shopify');
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product item id not found');
    }

    /**
     * Retrieves all the transformed categories for the account
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retrieveCustomCollections()
    {
        $filters = [
            'query' => ['published_status' => 'any'],
        ];

        // Get count of custom collections
        $collectionCount = 0;
        $request = $this->getCustomCollectionCount();
        if ($request->getStatusCode() === 200) {
            $request = json_decode($request->getBody()->getContents(), true);
            $collectionCount = $request['count'];
        }

        $totalPages = ceil($collectionCount / 250);

        $collections = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            try {
                $collections = array_merge($collections, $this->getCustomCollectionsByPage($filters, $i));
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $collections;
    }

    /**
     * Retrieves all the categories for the IntegrationCategory
     *
     */
    public function retrieveCategories()
    {

    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     * @throws \Exception
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {

    }

    /**
     * @param $product
     *
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($product)
    {
        //Associated SKU can only be set later from the first variant as Shopify does not have a "parent" SKU
        $associatedSku = null;
        //Status can only be retrieved from the first variant
        $status = null;

        $shortDescription = null;
        $htmlDescription = $product['body_html'] ?? null;
        $name = $product['title'] ?? null;
        $brand = null;
        $model = null;

        $options = [];
        if (count($product['options']) > 0) {
            foreach ($product['options'] as $option) {
                $options[] = title_case(str_replace('_',  ' ' , $option['name']));
            }
        }

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = null;

        // Shopify support account category
        $accountCategory = null;
        $category = null;

        //Looping through and creating all the variants
        $variants = [];
        $isOutOfStock = false;
        foreach ($product['variants'] as $variant) {
            // Shopify does not support names for the SKU, so we should implode from the option values, or use the default name
            $variantName = $variant['title'];

            // We pull the first variation as the associated_sku
            if (empty($associatedSku)) {
                $associatedSku = $variant['sku'];
            }
            $variantSku = $variant['sku'];

            $barcode = $variant['barcode'];
            $stock = $variant['inventory_quantity'];
            $prices = [];

            // Variant prices
            // Consider the shop currency in place of account currency.
            $shopCurrency = isset($this->account->shop->currency) && $this->account->shop->currency ? $this->account->shop->currency : $this->account->currency;
            // Selling Price
            $prices[] = new TransformedProductPrice($shopCurrency, $variant['price'], ProductPriceType::SELLING());
            // Retail price
            if(isset($variant['compare_at_price']) && $variant['compare_at_price']) {
                $prices[] = new TransformedProductPrice($shopCurrency, $variant['compare_at_price'] ?? 0, ProductPriceType::RETAIL());
            }

            // Remove duplicated for variants attributes
            $variantAttributes['weight_unit'] = $variant['weight_unit'];
            $variantAttributes['inventory_policy'] = $variant['inventory_policy'] == 'continue' ? 'Yes' : 'No';
            $variantAttributes['inventory_management'] = $variant['inventory_management'] == 'shopify' ? 'Yes' : 'No';
            $variantAttributes['requires_shipping'] = $variant['requires_shipping'] ? 'Yes' : 'No';

            # Get variant image url by id
            $images = [];
            if (!empty($variant['image_id']) || !is_null($variant['image_id'])) {
                foreach ($product['images'] as $index => $image) {
                    if ($image['id'] === $variant['image_id']) {
                        $images[] = new TransformedProductImage($image['src'], null, $image['width'], $image['height'], 0);

                        // remove it from main product images
                        unset($product['images'][$index]);
                        break;
                    }
                }
            }

            // weight unit
            if ($variant['weight_unit'] == 'kg') {
                $weightUnit = Weight::KILOGRAMS();
            } else if ($variant['weight_unit'] == 'g') {
                $weightUnit = Weight::GRAMS();
            } else if ($variant['weight_unit'] == 'oz') {
                $weightUnit = Weight::OUNCE();
            } else if ($variant['weight_unit'] == 'lb') {
                $weightUnit = Weight::POUNDS();
            }

            $weight = $variant['weight'];

            // Shopify is not marketplace, thus its either manual or virtual
            if ($variant['requires_shipping']) {
                $shippingType = ShippingType::MANUAL();
            } else {
                $shippingType = ShippingType::VIRTUAL();
            }

            $dimensionUnit = Dimension::CM();
            $length = 0;
            $width = 0;
            $height = 0;

            $productUrl = null;

            $identifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $variant['id'],
                ProductIdentifier::SKU()->getValue() => $variantSku,
                ProductIdentifier::PRODUCT_ID()->getValue() => $variant['product_id'],
                ProductIdentifier::INVENTORY_ITEM_ID()->getValue() => $variant['inventory_item_id'],
            ];

            $option1 = (is_null($variant['option1'])) ? null : $variant['option1'];
            $option2= (is_null($variant['option2'])) ? null : $variant['option2'];
            $option3 = (is_null($variant['option3'])) ? null : $variant['option3'];

            // Status
            if (!is_null($product['published_at'])) {
                $status = ProductStatus::LIVE();
                $marketplaceStatus = MarketplaceProductStatus::LIVE();
            } else {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::DISABLED();
            }

            // If status is live, then check if variant stock is 0 then set to out of stock
            if (MarketplaceProductStatus::LIVE()->equals($marketplaceStatus) && $variant['inventory_quantity'] <= 0) {
                $status = ProductStatus::OUT_OF_STOCK();
                $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
                $isOutOfStock = true;
            }

            $variantListing = new TransformedProductListing(
                $variantName,
                $identifiers,
                $integrationCategory,
                $accountCategory,
                $prices,
                $productUrl,
                $stock,
                $variantAttributes,
                $variant,
                $images,
                $marketplaceStatus
            );
            $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, null);
        }

        $identifiers = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['id']];

        //Shopify doesn't have a product URL for the listing, only for each SKU
        $productUrl = null;

        // Main image for main product
        $images = null;
        /*
        if (!empty($product['image'])) {
            $images[] = new TransformedProductImage($product['image']['src'], $product['image']['id'], $product['image']['width'], $product['image']['height'], 0);
        }*/
        if (!empty($product['images'])) {
            foreach($product['images'] as $image) {
                /** if variant_ids is empty means its the main product image. */
                if(isset($image['variant_ids']) && empty($image['variant_ids'])) {
                    $images[] = new TransformedProductImage($image['src'], $image['id'], $image['width'], $image['height'], 0);
                }
            }
        }

        //No prices for main product
        $mainPrices = null;

        //This is so we don't save duplicated data in our database for main product attribute
        $attributes['vendor'] = $product['vendor'];
        $attributes['product_type'] = $product['product_type'];
        $attributes['tags'] = $product['tags'];

        // Status
        if (!is_null($product['published_at'])) {
            $status = ProductStatus::LIVE();
            $marketplaceStatus = MarketplaceProductStatus::LIVE();
        } else {
            $status = ProductStatus::DISABLED();
            $marketplaceStatus = MarketplaceProductStatus::DISABLED();
        }
        // If there is one variant out of stock then it should be in out of stock status for main product
        if (MarketplaceProductStatus::LIVE()->equals($marketplaceStatus) && $isOutOfStock) {
            $status = ProductStatus::OUT_OF_STOCK();
            $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
        }

        // Setting the status for the main product to live because not sure what else to set here, unless we calculate
        // based on the statuses above to see if there's any that's live, or we use the last value
        $listing = new TransformedProductListing($name, $identifiers, $integrationCategory, $accountCategory, $mainPrices, $productUrl, null, $attributes, $product, $images, $marketplaceStatus);

        $product = new TransformedProduct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $category, $status, $variants, $options, $listing, $images);

        return $product;
    }

    /**
     * Pushes the update for the stock in ProductListing.
     * NOTE: This should force an update of the listing after updating (Not updated locally prior to actual push)
     *
     * @param ProductListing $product
     * @param $stock
     * @throws \Exception
     */
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null)
    {
        $inventoryItemId = $product->getIdentifier(ProductIdentifier::INVENTORY_ITEM_ID());
        if (empty($inventoryItemId)) {
            set_log_extra('listing', $product);
            throw new \Exception('Shopify product does not have product inventory item id');
        }

        // Retrieve location with inventory item id
        $parameters = [
            'query' => [
                'inventory_item_ids' => $inventoryItemId
            ]
        ];

        $response = $this->client->request('get',  '/admin/api/2020-07/inventory_levels.json', $parameters);

        if ($response->getStatusCode() === 200) {
            $response = json_decode($response->getBody()->getContents(), true);

            if ($response['inventory_levels']) {
                foreach ($response['inventory_levels'] as $item) {
                    // Sleep one second to prevent rate limit
                    sleep(3);
                    // Update to every inventory level
                    $parameters = [
                        'query' => [
                            'location_id' => $item['location_id'],
                            'inventory_item_id' => $item['inventory_item_id'],
                            'available' => (int) $stock
                        ]
                    ];

                    try {
                        $inventoryResponse = $this->client->request('POST',  '/admin/api/2020-07/inventory_levels/set.json', $parameters);

                        if ($inventoryResponse->getStatusCode() !== 200) {
                            throw new \Exception('Unable to update stock for Shopify product listing.');
                        }
                    } catch (\Exception $e) {
                        set_log_extra('response', $response ?? null);
                        set_log_extra('listing', $product);
                        throw $e;
                    }
                }

                // Shopify - Exceeded 2 calls per second for api client, so sleep for 1 sec only call to retrieve product
                sleep(1);
                $this->get($product);
            }
        } else {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $product);
            $exceptionMessage ='Unable to retrieve products location for Shopify|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id;
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * Change listing variant status enable/disable
     *
     * @param ProductListing $listing
     * @param bool $enabled
     * @return bool
     * @throws \Exception
     */
    public function toggleEnable(ProductListing $listing, $enabled = true)
    {
        /*
         * Check whether it have main product external id
         * If there is same sku, then it might have chance getting the wrong external id
         * So will check the main product external id first
         **/
        $externalId = $listing->getIdentifier(ProductIdentifier::PRODUCT_ID());

        if (empty($externalId)) {
            // Need to make sure is main product listing
            if (!empty($listing->listing) && !is_null($listing->listing)) {
                $listing = $listing->listing;
            }

            $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
            if (empty($externalId)) {
                set_log_extra('listing', $listing);
                throw new \Exception('Shopify product does not have product external id');
            }
        }

        $publishedAt = ($enabled) ? date(DATE_ISO8601, strtotime(now())) : null;

        // Parameters
        $parameters = [
            'published_at' => $publishedAt,
        ];

        try {
            $response = $this->client->request('PUT',  '/admin/api/2020-07/products/'.$externalId.'.json', [
                RequestOptions::JSON => ['product' => $parameters]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Unable to update status for Shopify product listing.');
            }
        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $listing);
            throw $e;
        }

        // Shopify - Exceeded 2 calls per second for api client, so sleep for 1 sec only call to retrieve product again
        sleep(1);
        $this->get($listing, true);

        return true;
    }
}
