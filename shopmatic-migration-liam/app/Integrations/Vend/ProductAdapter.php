<?php

namespace App\Integrations\Vend;

use App\Constants\Dimension;
use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductIdentifier;
use App\Constants\ProductPriceType;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use App\Integrations\AbstractProductAdapter;
use App\Integrations\TransformedProduct;
use App\Integrations\TransformedProductImage;
use App\Integrations\TransformedProductListing;
use App\Integrations\TransformedProductPrice;
use App\Integrations\TransformedProductVariant;
use App\Models\AccountCategory;
use App\Models\Integration;
use App\Models\Product;
use App\Models\ProductListing;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Models\ProductInventory;

class ProductAdapter extends AbstractProductAdapter
{

    /**
     * Retrieves a single product
     *
     * @param ProductListing|null $listing
     * @param bool $update Whether or not to update the product if it already exists
     *
     * @param null $itemId
     * @return mixed
     * @throws \Exception
     */
    public function get($listing, $update = true, $itemId = null)
    {
        $parameters = [
            'handle' => $listing ? $listing->product->associated_sku : $itemId,
        ];
        $response = $this->client->request('GET', $this->client->getUri(Client::VERSION_0_9, 'products'), ['query' => $parameters]);
        $product = $response['products'];
        try {
            $product = $this->transformProduct([$product]);
        } catch (\Exception $e) {
            set_log_extra('response', $response);
            set_log_extra('listing', $listing);
            throw $e;
        }
        return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
    }

    /**
     * Import all products
     *
     * @param $importTask
     * @param array $config
     * @return mixed
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        $totalPages = 0;
        $totalMainProducts = 0;
        $parameters = [
            'order_by' => 'id',
            'order_direction' => 'asc',
            'page_size' => 3,
            'page' => 1
        ];

        do {
            //This is put outside as it the integration fails, we don't want it to rollback
            $response = $this->client->request('GET', $this->client->getUri(Client::VERSION_0_9, 'products'), ['query' => $parameters]);
            if (empty($totalPages)) {
                $totalPages = $response['pagination']['pages'] ?? 1;
            }

            // Filtered are the products that we are actually going to transform and create
            // These are mainly main products (Non variants) as Vend returns variants as an individual product
            // Hence we are filtering it here and fetching the variants here
            // Please take note - we will be using 0.9 version instead of 2.0 is because 0.9 support variant filtered param.
            $filtered = [];
            $products = $response['products'];
            foreach ($products as $product) {

                // Avoiding weird discounts auto created by the system
                if ($product['sku'] === 'vend-discount') continue;

                // We're skipping variants because we'll fetch variants for each product
                if (!empty($product['variant_parent_id'])) {
                    continue;
                }

                $product['variants'] = [];

                // Get the variants and append it to the main product data
                if ($product['has_variants']) {
                    $productParams = [
                        'handle' => $product['handle']
                    ];

                    // Retrieve variant products
                    $response = $this->client->request('GET', $this->client->getUri(Client::VERSION_0_9, 'products'), ['query' => $productParams]);

                    $variants = $response['products'];
                    foreach ($variants as $variant) {
                        // This will also include the main product, which is good as each product need to have a variant regardless
                        $product['variants'][] = $variant;
                    }
                } else {
                    // This is so we can create the simple product using the same variant loop
                    $product['variants'] = [$product];
                }
                $filtered[] = $product;
            }

            $totalMainProducts += count($filtered);
            foreach ($filtered as $product) {
                try {
                    $transformed = $this->transformProduct([$product]);
                    $this->handleProduct($transformed, $config);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }
            }
            $parameters['page'] += 1;
        } while ($parameters['page'] <= $totalPages);

        // We only count the total main products instead of count all with the variant product
        if (!empty($importTask) && empty($importTask->total_products)) {
            $importTask->total_products = $totalMainProducts;
            $importTask->save();
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException|\Exception
     */
    public function sync()
    {
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $totalPages = 0;
        $parameters = [
            'order_by' => 'id',
            'order_direction' => 'asc',
            'page_size' => 3,
            'page' => 1
        ];

        do {
            //This is put outside as it the integration fails, we don't want it to rollback
            $response = $this->client->request('GET', $this->client->getUri(Client::VERSION_0_9, 'products'), ['query' => $parameters]);
            if (empty($totalPages)) {
                $totalPages = $response['pagination']['pages'] ?? 1;
            }

            // Filtered are the products that we are actually going to transform and create
            // These are mainly main products (Non variants) as Vend returns variants as an individual product
            // Hence we are filtering it here and fetching the variants here
            // Please take note - we will be using 0.9 version instead of 2.0 is because 0.9 support variant filtered param.
            $filtered = [];
            $products = $response['products'];
            foreach ($products as $product) {

                // Avoiding weird discounts auto created by the system
                if ($product['sku'] === 'vend-discount') continue;

                // We're skipping variants because we'll fetch variants for each product
                if (!empty($product['variant_parent_id'])) {
                    continue;
                }

                $product['variants'] = [];

                // Get the variants and append it to the main product data
                if ($product['has_variants']) {
                    $productParams = [
                        'handle' => $product['handle']
                    ];

                    // Retrieve variant products
                    $response = $this->client->request('GET', $this->client->getUri(Client::VERSION_0_9, 'products'), ['query' => $productParams]);

                    $variants = $response['products'];
                    foreach ($variants as $variant) {
                        // This will also include the main product, which is good as each product need to have a variant regardless
                        $product['variants'][] = $variant;
                    }
                } else {
                    // This is so we can create the simple product using the same variant loop
                    $product['variants'] = [$product];
                }
                $filtered[] = $product;
            }

            foreach ($filtered as $product) {
                try {
                    $transformed = $this->transformProduct([$product]);
                    $this->handleProduct($transformed);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }
            }
            $parameters['page'] += 1;
        } while ($parameters['page'] <= $totalPages);
    }

    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function update(ProductListing $product, array $data)
    {
        foreach ($data['variants'] as $key => $variant) {
            $variantId = $variant['identifiers']['external_id'] ?? '';
            $prices = array_column($data['prices'], 'price', 'type');
            $prices = (string)($variant['attributes'][ProductPriceType::SELLING()->getValue()]['value'] ?? $prices[ProductPriceType::SELLING()->getValue()]);

            $variantParam = [
                'sku' => $variant['sku'],
                'handle' => $product->product->associated_sku,
                'name' => $data['attributes']['name']['value'] ?? $data['name'],
                'description' => $variant['description'] ?? strip_tags($data['attributes']['html_description']['value'] ?? $data['html_description']),
                'brand_name' => $variant['brand'] ?? $data['attributes']['brand']['value'] ?? $data['brand'] ?? '',
                'tags' => $variant['tags'] ?? $data['attributes']['tags']['value'] ?? $data['tags'] ?? '',
                'retail_price' => $prices,
            ];

            if ($variantId) {
                $variantParam['id'] = $variantId;
            }

            $mainProductOptions = array_values($product->product->options);
            $optionsKeys = ['one', 'two', 'three'];

            foreach ($mainProductOptions as $i => $option) {
                $variantParam['variant_option_' . $optionsKeys[$i] . '_name'] = $option;
                $variantParam['variant_option_' . $optionsKeys[$i] . '_value'] = $data['attributes']['option_' . ($i + 1)]['value'] ?? $variant['option_' . ($i + 1)];
            }

            $variantParam['inventory'] = [
                'outlet_id' => $variant['inventory']['sku'],
                'outlet_name' => $variant['inventory']['name'],
                'count' => $variant['inventory']['stock'],
                'reorder_point' => $variant['inventory']['low_stock_notification'],
            ];
            $response = $this->client->request('POST', $this->client->getUri(Client::VERSION_0_9, 'products'), [RequestOptions::JSON => $variantParam]);
            if ($response) {
                if(isset($response['status'])) {
                    return $this->respondWithError($response['details']);
                }
                $this->uploadImage($response['product']['id'], $data['images']);
            }
        }

        $this->get($product, true);

        return $this->respond();
    }

    /**
     * Upload image to product
     *
     * @param $itemId
     * @param array $images
     *
     * @return void
     */
    private function uploadImage($itemId, $images)
    {
        if ($images && !empty($images)) {
            foreach ($images as $image) {
                $contents = '';
                if (isset($image['data_url'])) {
                    $contents = uploadImageFile($image['data_url'], session('shop'));
                } elseif (isset($image['src'])) {
                    $contents = $image['src'];
                }

                if ($contents) {
                    $data = [
                        'multipart' => [
                            [
                                'name' => 'image',
                                'contents' => fopen($contents, 'r'),
                            ]
                        ],
                    ];
                    $this->client->request('POST', $this->client->getUri(Client::VERSION_2_0, 'products/' . $itemId . '/actions/image_upload'), $data);
                }
            }
        }
    }

    /**
     * Creates a new product on the account from the product model
     *
     * @param Product $product
     * @return mixed
     * @throws \Exception
     */
    public function create(Product $product)
    {
        $integrationId = Integration::VEND;
        $this->preLoadProductData($product);

        // Get main product attributes
        $attributes = $product->attributes->where('product_variant_id', null)->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });

        // Variants
        $priceTypes = [];
        foreach (Constant::PRICES() as $priceType) {
            $priceTypes[] = $priceType->getValue();
        }
        $account = $this->account;
        $prices = $product->prices()->whereIn('type', $priceTypes)->where(function (Builder $query) use ($account, $product) {
            $query->whereProductId($product->id)->whereNull('product_variant_id')->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
        })->orWhere(function (Builder $query) use ($account, $product) {
            $query->whereProductId($product->id)->whereNull('product_variant_id')->whereNull('region_id')->whereNull('integration_id');
        })
        ->orderBy('integration_id', 'asc')
        ->orderBy('region_id', 'asc')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item['type'] => $item];
        });

        $productParams = [
            'name' => $product->name,
            'sku' => $attributes['sku']->value ?? $product->associated_sku,
            'handle' => $attributes['sku']->value ?? $product->associated_sku,
            'retail_price' => (float)(isset($prices[ProductPriceType::SELLING()->getValue()])) ? $prices[ProductPriceType::SELLING()->getValue()]->price : 0,
            'tags' => $attributes['tags']->value ?? '',
            'brand_name' => $attributes['brand_name']->value ?? '',
        ];

        foreach ($product->variants as $key => $variant) {
            // Get all variant attributes
            $variantAttributes = $variant->attributes->mapWithKeys(function ($item) {
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

            $productParams['variants'][$key] = [
                'handle' => $attributes['sku']->value ?? $product->associated_sku,
                'name' => $variantAttributes['name']->value ?? $variant->name,
                'description' => $variantAttributes['description']->value ?? $product['html_description'] ?? '',
                'sku' => $variant->sku,
                'retail_price' => (float)(isset($prices[ProductPriceType::SELLING()->getValue()])) ? $prices[ProductPriceType::SELLING()->getValue()]->price : 0,
            ];

            $optionsValue = null;
            $fromAttributes = true;
            if (isset($attributes['options'])) {
                $optionsValue = $attributes['options']->value;
            } else if (!is_null($product->options) && !empty($product->options)) {
                $optionsValue = $product->options;
                $fromAttributes = false;
            }

            if ($optionsValue && !empty($optionsValue) && !is_null($optionsValue)) {

                $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;

                if (!is_array($optionsValue)) {
                    $optionsValue = json_decode($optionsValue, true);
                }

                $mainProductOptions = array_values($optionsValue);
                $mainProductOptions = array_splice($mainProductOptions, 0, $optionsLevels);
                $optionsKeys = ['one', 'two', 'three'];

                if (count($mainProductOptions) < $optionsLevels) {
                    $optionsLevels = count($mainProductOptions);
                }

                for ($i = 1; $i <= $optionsLevels; $i++) {
                    $variantOptionValue = null;
                    if ($fromAttributes && isset($variantAttributes['option_' . $i])) {
                        $variantOptionValue = $optionsValue['option_' . $i]->value;
                    } else if (!$fromAttributes && (isset($variant['option_' . $i]))) {
                        $variantOptionValue = $variant['option_' . $i];
                    }

                    $productParams['variants'][$key]['variant_option_' . $optionsKeys[$i - 1] . '_name'] = $mainProductOptions[$i - 1];
                    $productParams['variants'][$key]['variant_option_' . $optionsKeys[$i - 1] . '_value'] = $variantOptionValue;
                }
            } else {
                $productParams['variants'][$key]['variant_option_one_name'] = $productParams['name'];
                $productParams['variants'][$key]['variant_option_one_value'] = $productParams['sku'];
            }

            $inventory = $variant->inventory;
            if ($inventory) {
                $productParams['variants'][$key]['inventory'] = [
                    [
                        'outlet_id' => $inventory->sku,
                        'outlet_name' => $inventory->name,
                        'count' => $inventory->stock,
                        'reorder_point' => $inventory->low_stock_notification,
                        'restock_level' => $inventory->out_of_stock_notification
                    ]
                ];
            }

            // Variant images
            $variantImages = $variant->allImages->where('integration_id', $integrationId);
            if (count($variantImages)) {

                // Add into main product image then associate to variant
                foreach ($variantImages as $image) {
                    $productParams['variants'][$key]['images'][] = [
                        'src' => $image->image_url
                    ];
                }
            }
        }
        $response = $this->client->request('POST', $this->client->getUri(Client::VERSION_0_9, 'products'), [RequestOptions::JSON => Arr::except($productParams, 'variants')]);

        if ($response) {
            if (count($productParams['variants'])) {
                foreach ($productParams['variants'] as $variant) {
                    $responseVariants = $this->client->request('POST', $this->client->getUri(Client::VERSION_0_9, 'products'), [RequestOptions::JSON => Arr::except($variant, 'images')]);
                    if (isset($responseVariants['status'])) {
                        return $this->respondWithError($responseVariants['details']);
                    } else {
                        $this->uploadImage($responseVariants['product']['id'], $variant['images'] ?? []);
                    }
                }
            }
        } else {
            set_log_extra('response', $response);
            set_log_extra('product', $productParams);
            throw new \Exception('Unable to create product from Vend.');
        }

        $product = $this->get(null, true, $response['product']['handle']);
        return $this->respondCreated($product);
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
        $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

        $response = $this->client->request('DELETE', $this->client->getUri(Client::VERSION_0_9, 'products/' . $externalId));

        if ($response->getStatusCode() === Response::HTTP_OK) {
            return true;
        } else {
            set_log_extra('listing', $listing);
            set_log_extra('response', $response);
            throw new \Exception('Unable to delete product from vend');
        }
    }

    /**
     * @param $products
     *
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($products)
    {
        if (count($products) > 1) {
            $product = array_filter($products, function ($d) {
                return $d['has_variants'];
            });
        } else {
            $product = $products;
        }
        $product = array_values($product);
        $product = $product[0] ?? [];
        $associatedSku = $product['handle'] ?? [];
        $status = !empty($product['active']) ? ProductStatus::LIVE() : ProductStatus::DISABLED();
        $mainProductIdentifier = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['id']];
        $shortDescription = null;
        $htmlDescription = $product['description'] ?? null;
        $name = $product['base_name'] ?? null;
        $brand = $product['brand_name'] ?? null;
        $model = null;

        $attributes = [];

        $options = [];

        if (!empty($product['variant_option_one_name'])) {
            $options[] = $product['variant_option_one_name'];
        }
        if (!empty($product['variant_option_two_name'])) {
            $options[] = $product['variant_option_two_name'];
        }
        if (!empty($product['variant_option_three_name'])) {
            $options[] = $product['variant_option_three_name'];
        }

        // Vend does not have an integration level category
        $integrationCategory = null;

        // Vend does not have product category, but they have something similar called type
        // Hence we'll be using type as category
        $accountCategory = AccountCategory::whereAccountId($this->account->id)->where('name', $product['type'])->first();

        $mainListing = null;

        // Looping through and creating all the variants
        $variants = [];

        foreach ($products as $sku) {

            $variantName = $sku['name'];

            $variantSku = $sku['sku'];

            $barcode = null;

            $stock = 0;
            if (!empty($sku['inventory'])) {
                foreach ($sku['inventory'] as $inventory) {
                    $stock += $inventory['count'];
                }
            }

            $prices = [];

            // Price is excluding tax, so we add it here to get the proper price
            $sellingPrice = round($sku['price'] + $sku['tax'], 4);
            $prices[] = new TransformedProductPrice($this->account->currency, $sellingPrice, ProductPriceType::SELLING());
            $prices[] = new TransformedProductPrice($this->account->currency, $sku['supply_price'], ProductPriceType::COST());

            //Remove attributes thats already stored so we dont keep a big data
            $variantAttributes = $sku;
            $toRemove = [
                'brand_name', 'name', 'variant_option_one_name', 'variant_option_two_name', 'variant_option_three_name',
                'description', 'active', 'handle', 'supply_price'
            ];
            foreach ($toRemove as $remove) {
                unset($variantAttributes[$remove]);
            }

            //Don't create it at the global level as it might create duplicates / mess things up.
            //We create it at the listing level
            $images = [];

            foreach ($sku['images'] as $index => $image) {
                if (!empty($image)) {
                    $images[] = new TransformedProductImage($image['links']['original'], $image['id'], null, null, $index);
                }
            }

            $weightUnit = Weight::KILOGRAMS();
            $weight = 0;

            $shippingType = ShippingType::MANUAL();
            $dimensionUnit = Dimension::CM();
            $length = 0;
            $width = 0;
            $height = 0;

            $productUrl = null;


            $identifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $sku['id'],
                ProductIdentifier::SKU()->getValue() => $variantSku,
            ];

            $option1 = !empty($sku['variant_option_one_value']) ? $sku['variant_option_one_value'] : null;
            $option2 = !empty($sku['variant_option_two_value']) ? $sku['variant_option_two_value'] : null;
            $option3 = !empty($sku['variant_option_three_value']) ? $sku['variant_option_three_value'] : null;

            $marketplaceStatus = !empty($product['active']) ? MarketplaceProductStatus::LIVE() : MarketplaceProductStatus::DISABLED();

            $variantListing = new TransformedProductListing($variantName, $identifiers, $integrationCategory,
                $accountCategory, $prices, $productUrl, $stock, $attributes, $sku, $images, $marketplaceStatus);

            if ($sku['has_variants'] || count($products) <= 1) {
                $mainListing = $variantListing;
            }
            $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, null);
        }
        $product = new TransformedProduct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $accountCategory ? $accountCategory->category : null, $status, $variants, $options, $mainListing, null);
        return $product;
    }

    /**
     * Pushes the update for the stock in ProductListing.
     * NOTE: This should force an update of the listing after updating (Not updated locally prior to actual push)
     *
     * @param ProductListing $product
     *
     * @param $stock
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null)
    {
        $outlets = $this->getItemInventory($product->getIdentifier(ProductIdentifier::EXTERNAL_ID()))['data'] ?? [];
        if (empty($outlets)) {
            set_log_extra('listing', $product);
            set_log_extra('outlets', $outlets);
            throw new \Exception('Inventory data not found for Vend');
        }

        $data = [
            'id' => $product->getIdentifier(ProductIdentifier::EXTERNAL_ID()),
            'inventory' => []
        ];
        if (count($outlets) === 1) {
            $data['inventory'] = [
                'outlet_id' => $outlets[0]['outlet_id'],
                'count' => $stock
            ];
        } else {
            $totalStock = 0;
            foreach ($outlets as $outlet) {
                $totalStock += $outlet['count'];
            }

            // We're doing this to cater to the deduction of stock
            // If there's multiple outlets with the stock, and if we need to deduct more than the stock in one outlet,
            // We'll need to loop through the outlets to slowly deduct
            $change = $totalStock - $stock;
            foreach ($outlets as $outlet) {

                //Checks if we can directly decrease from the current outlet, if we can, we stop the loop
                if ($outlet['current_amount'] >= $change) {
                    $data['inventory'][] = [
                        'outlet_id' => $outlet['outlet_id'],
                        'count' => $outlet['current_amount'] - $change
                    ];
                    break;
                } else {
                    //If we can't directly decrase from the current outlet, we need to decrease the change and also set the count to 0
                    $change = $change - $outlet['current_amount'];
                    $data['inventory'][] = [
                        'outlet_id' => $outlet['outlet_id'],
                        'count' => 0
                    ];
                }
            }
        }
        $response = $this->client->request('POST', $this->client->getUri(Client::VERSION_0_9, 'products'), [RequestOptions::JSON => $data]);

        // Update the product locally to make sure everything is correct
        try {
            $transformedProduct = $this->transformProduct($response['product']);
            $this->handleProduct($transformedProduct);
        } catch (\Exception $e) {
            set_log_extra('response', $response);
            set_log_extra('listing', $product);
            set_log_extra('stock', $stock);
            set_log_extra('outlets', $outlets);
            Log::error('Unable to update stock for Vend.');
            return false;
        }
        return true;
    }

    /**
     * Retrieves the inventory level for the product in different stores
     *
     * @param $productId
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function getItemInventory($productId)
    {
        return $this->client->request('GET', $this->client->getUri(Client::VERSION_2_0, "products/$productId/inventory"));
    }

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     *
     * @return boolean
     * @throws \Exception
     */
    public function canCreate(Product $product)
    {
        $this->variant_rules = [
            'weight' => 'required|min:0.1',
            'width' => 'required|min:0.1',
            'length' => 'required|min:0.1',
            'height' => 'required|min:0.1',
        ];
        $this->errors = [];

        parent::canCreate($product);

        if (count($this->errors) > 0) {
            return $this->respondWithError($this->errors);
        } else {
            return $this->respond(null);
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
        $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $listing);
            throw new \Exception('Vend product does not have product external id');
        }

        $active = ($enabled) ? "1" : "0";

        // NOTE - Main product id or variant id also pass at 'id'
        $parameters = [
            'id' => $externalId,
            'active' => $active
        ];

        $response = $this->client->request('POST', $this->client->getUri(Client::VERSION_0_9, 'products'), [RequestOptions::JSON => $parameters]);

        if ($response) {
            $this->get($listing, true);
        }
        return true;
    }
}
