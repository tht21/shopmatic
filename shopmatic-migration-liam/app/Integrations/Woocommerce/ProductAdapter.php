<?php

namespace App\Integrations\Woocommerce;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
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
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductListing;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\ProductInventory;
use App\Events\ProductFailedToImport;
class ProductAdapter extends AbstractProductAdapter
{
    /**
     * List of HTML tags allowed
     */
    private $allowedHtmlTags = '<p><span><h1><h2><h3><h4><h5><h6><em><u><sup><sub><code><blockquote><div><pre><img><a><strong><ul><ol><li>';

    /**
     * Retrieves a single product
     *
     * @param $listing
     * @param bool $update
     * @param null $itemId
     * @return mixed
     * @throws \Exception
     */
    public function get($listing, $update = false, $itemId = null)
    {
        $externalId = [];
        if ($itemId) {
            $externalId = $itemId;
        } elseif ($listing) {
            // Need to make sure is main product listing
            if (!empty($listing->listing) && !is_null($listing->listing)) {
                $listing = $listing->listing;
            }
            $externalId = $listing->identifiers['external_id'];
        }

        $product = $this->client->request('get', 'products/'.$externalId, []);

        if ($product) {
            $page = 1;
            $limit = 100;
            $variations = [];

            // Get variations
            do {
                $response = $this->getVariations($product->id, ['per_page' => $limit, 'page' => $page]);
                $variations = array_merge($variations, $response);
                $page++;
            } while (count($response) >= $limit);
            $product->variation_details = $variations;

            try {
                $product = $this->transformProduct($product);
            } catch (\Exception $e) {
                set_log_extra('product', $product);
                throw $e;
            }
            return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
        } else {
            set_log_extra('identifiers', $externalId);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to retrieve product for WooCommerce.');
        }
    }

    /**
     * Import all products from Woocommerce
     *
     * @param $importTask
     * @param array $config
     * @return bool
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        $page = 1;
        $limit = 10;

        if (!empty($importTask)) {
            $importTask->total_products = 0;
            $importTask->save();
        }

        do {
            $products = $this->client->request('get', 'products', ['per_page' => $limit, 'page' => $page, 'status' => 'any']);

            if (!empty($importTask)) {
                $importTask->total_products += count($products);
                $importTask->save();
            }

            //DB::transaction(function() use ($config, $products) {
                foreach ($products as $key => $product) {
                    $variationPage = 1;
                    $variationLimit = 10;
                    $variations = [];

                    // Get variations
                    do {
                        $response = $this->getVariations($product->id, ['per_page' => $variationLimit, 'page' => $variationPage]);
                        $variations = array_merge($variations, $response);
                        $variationPage++;
                    } while (count($response) >= $variationLimit);
                    $product->variation_details = $variations;


                    try {
                        $product = $this->transformProduct($product);
                        $this->handleProduct($product, $config);
                    } catch (\Exception $e) {
                        set_log_extra('product', $product);
                        if (!is_null($importTask)) {
                            $associatedSku = isset($product->sku) ? $product->sku : $product->associatedSku;
                            $name = $product->name ?? null;
        
                            $errorMessage = 'Woocomerce Import Task [' .$importTask->id. ']|Account id|' . $this->account->id .'|Message|' .$e->getMessage();
                            set_log_extra($errorMessage, $product);
                            \Log::error($errorMessage);
                            event(new ProductFailedToImport($importTask, (!empty($name) ? $name : $associatedSku) . ' failed to import'));
                        }
                        continue;
                    }
                }
            
            //});
            $page++;
        } while (count($products) >= $limit);

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }

        return true;
    }

    /**
     * Import all new categories from Woocommerce
     *
     * @param $importTask
     * @return void
     * @throws \Exception
     */
    public function importCategories($importTask)
    {
        try {
            $categories = $this->retrieveCategories();

            if (!empty($categories)) {
                if (!empty($importTask) && empty($importTask->total_categories)) {
                    $importTask->total_categories = count($categories);
                    $importTask->save();
                }

                $this->updateAccountCategories($categories);
            }
        } catch (\Exception $exception) {
            set_log_extra('account_category', $exception);
            set_log_extra('account', $this->account);
            throw new \Exception('Woocommerce unable to connect and retrieve categories.');
        }
    }

    /**
     * Get variations under product
     *
     * @param $productId
     * @param array $filters
     * @return mixed
     */
    public function getVariations($productId, $filters = [])
    {
        return $this->client->request('get', "products/$productId/variations", $filters);
    }

    /**
     * Get category listing
     *
     * @param array $filters
     * @return mixed
     */
    public function getCategories($filters = [])
    {
        return $this->client->request('get', 'products/categories', $filters);
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @throws \Throwable
     */
    public function sync()
    {
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $page = 1;
        $limit = 50;

        do {
            $products = $this->client->request('get', 'products', ['per_page' => $limit, 'page' => $page, 'status' => 'publish']);

            DB::transaction(function() use ($products) {
                foreach ($products as $key => $product) {
                    $variationPage = 1;
                    $variationLimit = 100;
                    $variations = [];

                    // Get variations
                    do {
                        $response = $this->getVariations($product->id, ['per_page' => $variationLimit, 'page' => $variationPage]);
                        $variations = array_merge($variations, $response);
                        $variationPage++;
                    } while (count($response) >= $variationLimit);
                    $product->variation_details = $variations;

                    try {
                        $product = $this->transformProduct($product);
                    } catch (\Exception $e) {
                        set_log_extra('product', $product);
                        throw $e;
                    }
                    $this->handleProduct($product);
                }
            });
            $page++;
        } while (count($products) >= $limit);
    }

    public function getDimensionData(string $dimention_name, $data, $firstVariant) {
        $dimension = 0;
        if(isset($data['attributes'][$dimention_name]['value'])) {
            $dimension = $data['attributes'][$dimention_name]['value'];
        }
        else if(isset($data[$dimention_name])) {
            $dimension = $data[$dimention_name];
        }
        else if(isset($firstVariant) && isset($firstVariant[$dimention_name])) {
            $dimension = $firstVariant[$dimention_name];
        }
        return $dimension;
    }
    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     * @return array|mixed
     * @throws \Exception
     */
    public function update(ProductListing $product, array $data)
    {
        $itemId = $data['identifiers']['external_id'] ?? $product->identifiers['external_id'];
        $firstVariant = reset($data['variants']);
        // Html Description
        $htmlDescription = $data['attributes']['html_description']['value'] ?? $data['html_description'] ?? '';
        $sku = $data['associated_sku'] ?? $product->identifiers['sku'];
        $weight = $this->getDimensionData('weight', $data, $firstVariant);
        $length = $this->getDimensionData('length', $data, $firstVariant);
        $width = $this->getDimensionData('width', $data, $firstVariant);
        $height = $this->getDimensionData('height', $data, $firstVariant);

        $productParams = [
            'name' => $data['attributes']['name']['value'] ?? $data['name'],
            'description' => strip_tags($htmlDescription,$this->allowedHtmlTags),
            'short_description' => strip_tags($data['attributes']['short_description']['value'] ?? $data['short_description'] ?? $htmlDescription),
            'weight' => (string) ($weight),
            'images' => [],
            'categories' => [],
            'tax_status' => $data['attributes']['tax_status']['value'] ?? $data['tax_status'],
            'purchase_note' => strip_tags($data['attributes']['purchase_note']['value'] ?? $data['purchase_note'] ?? ''),
            'stock_quantity' => $data['stock'] ?? $firstVariant['inventory']['stock'],
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

        // Parent Dimensions
        $productParams['dimensions'] = [
            'length' => (string) ($length),
            'width' => (string) ($width),
            'height' => (string) ($height),
        ];

        $productPrices = ProductPrice::whereProductId($product->product_id)->whereNull('product_listing_id')->whereNull('product_variant_id')->whereNull('integration_id')->get();
        foreach (Constant::PRICES() as $priceType) {
            foreach ($productPrices as $price) {
                if ($price->type === $priceType->getValue()) {
                    $prices[$priceType->getValue()] = $price->price;
                    break;
                }
            }
        }
        // Prices
        $productParams['regular_price'] = (string) ($prices[ProductPriceType::SELLING()->getValue()] ?? 0);
        $productParams['sale_price'] = (string) ($prices[ProductPriceType::SPECIAL()->getValue()] ?? '');

        // Categories
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $category) {
                if (isset($category['id'])) {
                    $productParams['categories'][] = [
                        'id' => $category['id']
                    ];
                }
            }
        }

        // Tags
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                if (isset($tag['id'])) {
                    $productParams['tags'][] = [
                        'id' => $tag['id']
                    ];
                }
            }
        }

        $mainProductAttributes = [];
        $productAssociateSku = $product->product->associated_sku;
        foreach ($data['variants'] as $key => $variant) {
            // Check this variant is not belong to current product
            if ($productAssociateSku != $sku) {
                continue;
            }
            if (empty($variant['identifiers'])) {
                $variantId = null;
            } else {
                $variantId = $variant['identifiers']['external_id'];
            }
            $variantParam = [
                'description' => $variant['description'] ?? '',
                'sku' => $variant['sku'],
                'weight' => (string) ($variant['attributes']['weight']['value'] ?? $variant['weight']),
                'images' => [],
            ];

            // upload variant image to get the url
            if (count($variant['images']) > 0) {
                $imageUrl = null;
                foreach ($variant['images'] as $key => $value) {
                    if (!isset($value['deleted'])) {
                        if (isset($value['data_url'])) {
                            $imageUrl = uploadImageFile($value['data_url'], session('shop'));
                        } else {
                            $imageUrl =  $value['image_url'];
                        }
                    }
                }

                if (!is_null($imageUrl)) {
                    $variantParam['images'][] = ['src' => $imageUrl];
                }
            }

            // Variant Dimensions
            $variantParam['dimensions'] = [
                'length' => (string) ($variant['attributes']['length']['value'] ?? $variant['length'] ?? 0),
                'width' => (string) ($variant['attributes']['width']['value'] ?? $variant['width'] ?? 0),
                'height' => (string) ($variant['attributes']['height']['value'] ?? $variant['height'] ?? 0),
            ];
            //Parent Dimensions
            if (empty($productParams['dimensions']['length']) && empty($productParams['dimensions']['height']) && empty($productParams['dimensions']['width'])) {
                $productParams['dimensions'] = [
                    'length' => (string) ($variant['attributes']['length']['value'] ?? $variant['length'] ?? 0),
                    'width' => (string) ($variant['attributes']['width']['value'] ?? $variant['width'] ?? 0),
                    'height' => (string) ($variant['attributes']['height']['value'] ?? $variant['height'] ?? 0),
                ];
            }
            // Parent Weigth
            if(empty($productParams['weight'])) {
                $productParams['weight'] = (string) ($variant['attributes']['weight']['value'] ?? $variant['weight']);
            }

            $variantPrices = [];
            foreach (Constant::PRICES() as $priceType) {
                foreach ($variant['prices'] as $price) {
                    if ($price['type'] === $priceType->getValue()) {
                        $variantPrices[$priceType->getValue()] = $price['price'];
                        break;
                    }
                }
            }
            $variantParam['regular_price'] = (string) ($variantPrices[ProductPriceType::SELLING()->getValue()] ?? $variant['attributes'][ProductPriceType::SELLING()->getValue()]['value'] ?? 0);
            $variantParam['sale_price'] = (string) ($variantPrices[ProductPriceType::SPECIAL()->getValue()] ?? $variant['attributes'][ProductPriceType::SPECIAL()->getValue()]['value'] ?? '');

            // Stock
            $variantParam['stock_quantity'] = $variant['inventory']['stock'] ?? 0;

            // Get all predefined attributes (for woocommerce the attribute is like an option)
            $predefinedAttributes = $this->retrieveProductAttributes(false);

            $variant['attributes'] = ProductAttribute::where('product_id', $product->product_id)->where('product_variant_id', $variant['id'])->pluck('value', 'name')->toArray();
            foreach ($predefinedAttributes as $predefinedAttribute) {
                if (isset($variant['attributes'][strtolower($predefinedAttribute->name)])) {

                    // Push into options if already exists
                    $key = array_search($predefinedAttribute->id, array_column($mainProductAttributes, 'id'));
                    if ($key !== false) {
                        array_push($mainProductAttributes[$key]['options'], $variant['attributes'][strtolower($predefinedAttribute->name)]);
                    } else { // Create new
                        $mainProductAttributes[] = [
                            'id' => (string) $predefinedAttribute->id,
                            'variation' => true, // for variation products in case you would like to use it for variations
                            'options' => [
                                $variant['attributes'][strtolower($predefinedAttribute->name)],
                            ],
                        ];
                    }

                    // Variant attributes
                    $variantParam['attributes'][] = [
                        'id' => (string) $predefinedAttribute->id,
                        'option' => $variant['attributes'][strtolower($predefinedAttribute->name)]
                    ];
                }
            }
            if (!empty($variantId)) {
                $this->client->request('put',  'products/'.$itemId.'/variations/'.$variantId, $variantParam);
            } else {
                // Create variations
                $this->client->request('post',  'products/'.$itemId.'/variations', $variant);
            }
        }

        $productParams['attributes'] = $mainProductAttributes;
        //$productParams['variants'] = $variantsParam;

        $response = $this->client->request('put',  'products/'.$itemId, $productParams);

        if ($response) {
            $this->get($product, true);

            return $this->respond();
        } else {
            set_log_extra('response', $response);
            set_log_extra('product', $productParams);
            return $this->respondWithError('Unable to update product from Woocommerce.');
        }
    }

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     * @return array|mixed
     * @throws \Exception
     */
    public function canCreate(Product $product)
    {
        /* validation rules */
        parent::canCreate($product);

        $attributes = $product->attributes->where('product_variant_id', null)->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });

        /* Variant image validation */
        $images = $product->allImages->where('integration_id', Integration::WOOCOMMERCE)->count();
        // If no Wordpress specific images - use main images
        if ($images <= 0) {
            $images = $product->allImages()->whereNull('integration_id')->count();
        }
        if ($images <= 0) {
            $images = $product->allImages()->where('integration_id', null)->where('region_id', null)->count();
        }
        if ($images <= 0) {
            $this->errors[] = 'Please make sure main product have at least one image';
        }

        /* SKU validation */
        if (count($product->variants)) {
            // Cannot have duplicate sku
            $variants = $product->variants;
            $variantSkus = array_column(json_decode($variants, true), 'sku');

            if (count(array_unique($variantSkus)) < count($variantSkus)) {
                $this->errors[] = 'Please make sure there is no duplicate sku on variants';
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
     * @param Product $product
     * @return mixed
     * @throws \Exception
     */
    public function create(Product $product)
    {
        $integrationId = Integration::WOOCOMMERCE;
        // pre-load required relation data
        $this->preLoadProductData($product);

        // Get main product attributes
        $attributes = $product->attributes->where('product_variant_id', null)->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });
        $firstVariant = $product->variants()->first();
        $isVirtual = false;
        if (isset($attributes['virtual']->value) && strtolower($attributes['virtual']->value) === 'true') {
            $isVirtual = true;
        }
        $taxStatus = 'taxable';
        if (isset($attributes['tax_status']->value) && !empty($attributes['tax_status']->value) && strpos($attributes['tax_status']->value, '[]') === false) {
            $taxStatus = $attributes['tax_status']->value;
        }
        // Html Description
        $htmlDescription = $attributes['html_description']->value ?? $product->html_description ?? '';

        $weight = $this->getDimensionData('weight', $product, $firstVariant);
        $productParams = [
            'name' => $attributes['name']->value ?? $product->name,
            'type' => 'variable',
            'sku' => $attributes['associated_sku']->value ?? $product->associated_sku,
            'description' => strip_tags($htmlDescription,$this->allowedHtmlTags),
            'short_description' => strip_tags($attributes['short_description']['value'] ?? $attributes['short_description'] ?? $htmlDescription),
            'weight' => (string) ($weight),
            'images' => [],
            'categories' => [],
            'manage_stock' => 1, //Hardcoding this otherwise there won't be stock in woocommerce / CS
            'stock_quantity' => ($firstVariant && $firstVariant->inventory) ? $firstVariant->inventory->stock : 0,
            'tax_status' => $taxStatus,
            'purchase_note' => strip_tags($attributes['purchase_note']->value ?? ''),
            'virtual' => $isVirtual,
        ];
        // Images
        $images = $product->allImages->where('integration_id', $integrationId);

        // If don't have images with integration_id and region_id, then init just get images with NULL integration_id and NULL region_id
        if (count($images) <= 0) {
            $images = $product->allImages()->where('integration_id', null)->where('region_id', null)->where('source_account_id', null)->get();
        }
        if (count($images)) {
            foreach ($images as $key => $image) {
                $productParams['images'][] = [
                    'src' => $image->image_url
                ];
            }
        }


        // Get woocommerce price types
        $priceTypes = [];
        foreach (Constant::PRICES() as $priceType) {
            $priceTypes[] = $priceType->getValue();
        }
        $account = $this->account;
        $prices = $product->prices()->whereIn('type', $priceTypes)->where(function (Builder $query) use ($account, $product) {
            $query->whereProductId($product->id)->whereNull('product_variant_id')->whereIntegrationId($account->integration_id);
        })->orWhere(function (Builder $query) use ($account, $product) {
            $query->whereProductId($product->id)->whereNull('product_variant_id')->whereNull('region_id')->whereNull('integration_id');
        })
        ->orderBy('integration_id', 'asc')
        ->orderBy('region_id', 'asc')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item['type'] => $item];
        });
        // Parent Dimensions
        $length = $this->getDimensionData('length', $product, $firstVariant);
        $width = $this->getDimensionData('width', $product, $firstVariant);
        $height = $this->getDimensionData('height', $product, $firstVariant);
        $productParams['dimensions'] = [
            'length' => (string) ($length),
            'width' => (string) ($width),
            'height' => (string) ($height),
        ];

        // Categories & Tags
        $retrieveAttributes = ['categories', 'tags'];
        foreach ($retrieveAttributes as $retrieveAttribute) {
            if (isset($attributes[$retrieveAttribute]) && !empty($attributes[$retrieveAttribute])) {
                foreach (json_decode($attributes[$retrieveAttribute]->value, true) as $value) {
                    if (isset($value['id'])) {
                        $productParams[$retrieveAttribute][] = [
                            'id' => $value['id']
                        ];
                    }
                }
            }
        }

        // Variants
        $mainProductAttributes = [];
        foreach ($product->variants as $key => $variant) {
            // If variants sku same with main sku then skip, cannot have duplicate sku
            if ($variant->sku != $productParams['sku']) {
                // Get all variant attributes
                $variantAttributes = $variant->attributes->mapWithKeys(function ($item) {
                    return [$item['name'] => $item];
                });

                // Variant prices
                $variantPrices = $variant->prices()->whereIn('type', $priceTypes)->where(function (Builder $query) use ($account, $product, $variant) {
                    $query->whereProductId($product->id)->whereProductVariantId($variant->id)->whereIntegrationId($account->integration_id);
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
                    'description' => $variantAttributes['description']->value ?? '',
                    'sku' => $variant->sku,
                    'weight' => (string) ($variantAttributes['weight']->value ?? $variant->weight ?? 0),
                    'image' => [],
                ];
                $productParams['variants'][$key]['manage_stock'] = true;
                $productParams['variants'][$key]['stock_quantity'] = $variant->inventory ? $variant->inventory->stock : 0;

                // Prices
                $regular_price = (string) ($variantPrices[ProductPriceType::SELLING()->getValue()]->price ?? '');
                if($regular_price == '') {
                    $regular_price = $variant->price;
                }
                $productParams['variants'][$key]['regular_price'] = $regular_price;

                $price = $variantPrices[ProductPriceType::SPECIAL()->getValue()]->price ?? '';
                if ($price > 0) {
                    $productParams['variants'][$key]['sale_price'] = (string) $price;
                }
                // Dimensions
                if (!isset($attributes['length']->value,$attributes['width']->value,$attributes['height']->value)) {
                    $productParams['dimensions'] = [
                        'length' => (string) ($variant->length ?? ''),
                        'width' => (string) ($variant->width ?? ''),
                        'height' => (string) ($variant->height ?? ''),
                    ];
                }
                // Parent Weight
                if(!isset($attributes['weight']->value)) {
                    $productParams['weight'] = (string) ($variant->weight ?? 0);
                }
                // Variant Images
                $variantImage = $variant->allImages()->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->first();
                if (is_null($variantImage)) {
                    $variantImage = $variant->allImages()->whereNull('integration_id')->whereNull('product_listing_id')->whereNull('region_id')->first();
                }
                // For variant image only able to upload one
                if ($variantImage) {
                    $productParams['variants'][$key]['image']['src'] = $variantImage->image_url;
                }

                /*
                 * If there is options
                 * Check product attribute options first, if there is no options in product attribute
                 * then only check on products table options
                 */
                $optionsValue = null;
                $fromAttributes = true;
                if (isset($attributes['options'])) {
                    $optionsValue = $attributes['options']->value;
                } else if (!is_null($product->options) && !empty($product->options)) {
                    $optionsValue = $product->options;
                    $fromAttributes = false;
                }

                if ($optionsValue && !empty($optionsValue) && !is_null($optionsValue)) {
                    // Get all predefined attributes (for woocommerce the attribute is like an option)
                    $predefinedAttributes = $this->retrieveProductAttributes(false);
                    // Get integration options level
                    $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;

                    // Convert to array if options value is json string
                    if (!is_array($optionsValue)) {
                        $optionsValue = json_decode($optionsValue, true);
                    }

                    $mainProductOptions = array_values($optionsValue);
                    $mainProductOptions = array_splice($mainProductOptions, 0, $optionsLevels);

                    // Check whether option exists in woocommerce
                    foreach ($mainProductOptions as $mainProductOption) {
                        if (array_search(strtolower($mainProductOption), array_map('strtolower', array_column($predefinedAttributes, 'name'))) === false) {
                            // If not found in woocommere, then create it
                            $parameters = [
                                'name' => $mainProductOption,
                                'order_by' => 'menu_order',
                                'has_archives' => true
                            ];
                            // Create attribute in woocommerce
                            $this->client->request('post', 'products/attributes', $parameters);
                        }
                    }

                    // Retrieve the product attribute again
                    $predefinedAttributes = $this->retrieveProductAttributes(false);
                    // If there is attribute it also must include in main product then only variant
                    for ($i = 1; $i <= $optionsLevels; $i++) {
                        $variantOptionValue = null;
                        if ($fromAttributes && isset($variantAttributes['option_'.$i])) {
                            $variantOptionValue = $variantAttributes['option_'.$i]->value;
                        } else if (!$fromAttributes && (isset($variant['option_'.$i]))) {
                            $variantOptionValue = $variant['option_'.$i];
                        }

                        if (isset($mainProductOptions[$i - 1]) && !is_null($variantOptionValue)) {
                            $predefinedAttributeKey = array_search(strtolower($mainProductOptions[$i - 1]), array_map('strtolower', array_column($predefinedAttributes, 'name')));

                            // Push into options if already exists
                            $mainProductAttributeKey = array_search($predefinedAttributes[$predefinedAttributeKey]->id, array_column($mainProductAttributes, 'id'));
                            if ($mainProductAttributeKey !== false) {
                                array_push($mainProductAttributes[$mainProductAttributeKey]['options'], $variantOptionValue);
                            } else {
                                $mainProductAttributes[] = [
                                    'id' => (string) $predefinedAttributes[$predefinedAttributeKey]->id,
                                    'variation' => true, // for variation products in case you would like to use it for variations
                                    'options' => [
                                        $variantOptionValue,
                                        //'SuperBig' // if the attribute term doesn't exist, it will be created
                                    ],
                                ];
                            }

                            // Variant attributes
                            $productParams['variants'][$key]['attributes'][] = [
                                'id' => (string) $predefinedAttributes[$predefinedAttributeKey]->id,
                                'option' => $variantOptionValue
                            ];
                        }
                    }
                }
                // without options in product
                else {
                    $predefinedAttributes = $this->retrieveProductAttributes(false);
                    // create a attribtues name simple
                    if (array_search(strtolower('simple'), array_map('strtolower', array_column($predefinedAttributes, 'name'))) === false) {
                        // If not found in woocommere, then create it
                        $parameters = [
                            'name' => 'simple',
                            'order_by' => 'menu_order',
                            'has_archives' => true
                        ];
                        // Create attribute in woocommerce
                        $this->client->request('post', 'products/attributes', $parameters);

                        // Retrieve the product attribute again
                        $predefinedAttributes = $this->retrieveProductAttributes(false);
                    }

                    $variantOptionValue = $variant->sku;

                    // Redundant code, can improvise
                    $predefinedAttributeKey = array_search('simple', array_map('strtolower', array_column($predefinedAttributes, 'name')));

                    // Push into options if already exists
                    $mainProductAttributeKey = array_search($predefinedAttributes[$predefinedAttributeKey]->id, array_column($mainProductAttributes, 'id'));
                    if ($mainProductAttributeKey !== false) {
                        array_push($mainProductAttributes[$mainProductAttributeKey]['options'], $variantOptionValue);
                    } else {
                        $mainProductAttributes[] = [
                            'id' => (string) $predefinedAttributes[$predefinedAttributeKey]->id,
                            'variation' => true, // for variation products in case you would like to use it for variations
                            'options' => [
                                $variantOptionValue,
                                //'SuperBig' // if the attribute term doesn't exist, it will be created
                            ],
                        ];
                    }

                    // Variant attributes
                    $productParams['variants'][$key]['attributes'][] = [
                        'id' => (string) $predefinedAttributes[$predefinedAttributeKey]->id,
                        'option' => $variantOptionValue
                    ];
                }
            }
        }
        $productParams['attributes'] = $mainProductAttributes;
        // If does not have variant
        if (!isset($productParams['variants']) || (isset($productParams['variants']) && count($productParams['variants']) <= 0)) {
            $productParams['type'] = 'simple';
            /*
             * If no variant only pass price on main product
             * If every time pass the main product price, woocommerce will replace the variant price as main product price
            */

            $productParams['regular_price'] = (string) ($prices[ProductPriceType::SELLING()->getValue()]->price ?? '');

            // if regular_price is empty, we will get price of first variants.
            if($productParams['regular_price'] == '') {
                $productParams['regular_price'] =  (string) ($firstVariant->price ?? '');
            }

            $price = $prices[ProductPriceType::SPECIAL()->getValue()]->price ?? 0;
            if ($price > 0) {
                $productParams['sale_price'] = (string) $price;
            }
        }

        // Create main product
        $response = $this->client->request('post',  'products', Arr::except($productParams, 'variants'));

        if ($response) {
            if (isset($productParams['variants']) && count($productParams['variants'])) {
                foreach ($productParams['variants'] as $variant) {
                    $this->client->request('post',  'products/'.$response->id.'/variations', $variant);
                }
            }
        } else {
            set_log_extra('response', $response);
            set_log_extra('product', $productParams);
            throw new \Exception('Unable to create product from Woocommerce.');
        }

        $product = $this->get(null, true, $response->id);

        return $this->respondCreated($product);
    }

    /**
     * Create Account Category
     *
     * @param $input
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function createAccCategory($input)
    {
        $name = $input['name'];
        # Get parent account category external id
        $parent = AccountCategory::whereId($input['parent_id'])
                    ->whereAccountId($this->account->id)
                    ->whereIsLeaf(false)
                    ->first();

        try {
            $category = $this->client->request('post', 'products/categories', ['name' => $name, 'parent' => $parent->external_id]);

            return $category;
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and create category.');
        }
    }

    /**
     * Update Account Category
     *
     * @param $categoryId
     * @param $input
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function updateCategory($categoryId, $input)
    {
        $name = $input['name'];
        # Get parent account category external id
        $parent = AccountCategory::whereId($input['parent_id'])
            ->whereAccountId($this->account->id)
            ->whereIsLeaf(false)
            ->first();

        try {
            $category = $this->client->request('post', 'products/categories/'.$categoryId, ['name' => $name, 'parent' => $parent->external_id]);

            return $category;
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and update category.');
        }
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
            try {
                $response =  $this->client->request('delete', 'products/' . $listing->product_id . '/variations/' . $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID()), []);

                return true;
            } catch (\Exception $exception) {
                set_log_extra('product', $listing);
                throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and delete product.');
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product item id not found');
    }

    /**
     * Retrieve account categories from WooCommerce API
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveCategoriesFromApi()
    {
        $page = 1;
        $limit = 50;
        $categories = [];

        do {
            try {
                $getCategories = $this->getCategories(['per_page' => $limit, 'page' => $page]);
            } catch(\Exception $e) {
                set_log_extra('response', $e);
                throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve categories.');
            }
            $categories = array_merge($categories, $getCategories);
            $page++;
        } while (count($getCategories) > 0);

        return $categories;
    }

    /**
     * Retrieves all the transformed categories for the account
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        $categories = $this->retrieveCategoriesFromApi();

        $results = collect($categories);
        $parents = $results->where('parent',0);
        $data = [];
        foreach ($parents as $key => $parent) {
            $childs = $this->parseCategories($parent, $results, $parent->name);
            $is_leaf = (count($childs) > 0) ? 0 : 1;

            $data[$key] = [
                'name'          => $parent->name,
                'breadcrumb'    => $parent->name,
                'external_id'   => $parent->id,
                'is_leaf'       => $is_leaf,
                'children'      => $childs
            ];
        }
        return $data;
    }

    /**
     * Recursive function to get all children of the category
     *
     * @param $parent
     * @param $categories
     * @param $parentBreadcrumb
     * @return array
     */
    private function parseCategories($parent, $categories, $parentBreadcrumb)
    {
        $result = [];
        $childs = $categories->where('parent', $parent->id);

        foreach ($childs as $child) {
            $breadcrumb = $parentBreadcrumb . ' > ' . $child->name;

            $externalId = $child->id;
            $leaf = (count($categories->where('parent', $child->id)) > 0) ? 0 : 1;
            $name = $child->name;
            $children = (count($categories->where('parent', $child->id)) > 0) ? $this->parseCategories($child, $categories, $breadcrumb) : [];

            $result[] = [
                'name'          => $name,
                'breadcrumb'    => $breadcrumb,
                'external_id'   => $externalId,
                'is_leaf'  => $leaf,
                'children' => $children,
            ];
        }
        return $result;
    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     * @return array
     * @throws \Exception
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {

    }

    /**
     * Retrieves all the field attributes
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveAttributes()
    {
        $attributes = Constant::ATTRIBUTES();

        $accountAttributes = $this->retrieveAccountAttributes();

        return array_merge($attributes, $accountAttributes);
    }

    /**
     * Retrieves all the field attributes for Account
     * NOTE: Every array item here MUST be converted to array - if you're using collect() always do a ->toArray()
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveAccountAttributes()
    {
        return [];
    }

    /**
     * Retrieves all the product tags for the account
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveProductTags()
    {
        $page = 1;
        $limit = 50;
        $productTags = [];

        do {
            try {
                $getProductTags = $this->client->request('get', 'products/tags', ['per_page' => $limit, 'page' => $page]);
            } catch(\Exception $e) {
                set_log_extra('response', $e);
                throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve product tags.');
            }
            $productTags = array_merge($productTags, $getProductTags);
            $page++;
        } while (count($getProductTags) > 0);

        return $productTags;
    }

    /**
     * Retrieves all the product attributes for the account
     *
     * @param bool $withAttributeTerms
     * @return array
     * @throws \Exception
     */
    public function retrieveProductAttributes($withAttributeTerms = true)
    {
        $productAttributes = $this->client->request('get', 'products/attributes');

        # If with attribute terms and there is product attributes only retrieve the terms as well.
        if ($withAttributeTerms && count($productAttributes) > 0) {
            foreach ($productAttributes as $productAttribute) {
                $attributeTerms = $this->retrieveAttributeTerms($productAttribute->id);

                $productAttribute->attribute_terms = $attributeTerms;
            }
        }
        return $productAttributes;
    }

    /**
     * Retrieves retrieve all terms from a product attribute for the account
     *
     * @param $productAttributeId
     * @return array
     * @throws \Exception
     */
    public function retrieveAttributeTerms($productAttributeId)
    {
        $page = 1;
        $limit = 50;
        $attributeTerms = [];

        do {
            try {
                $getAttributeTerms = $this->client->request('get', "products/attributes/$productAttributeId/terms", ['per_page' => $limit, 'page' => $page]);
            } catch(\Exception $e) {
                set_log_extra('response', $e);
                throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve product attributes.');
            }
            $attributeTerms = array_merge($attributeTerms, $getAttributeTerms);
            $page++;
        } while (count($getAttributeTerms) > 0);

        return $attributeTerms;
    }

    /**
     * Retrieves logistics
     *
     * @param null $attributes
     * @return array
     * @throws \Exception
     */
    public function retrieveLogistics($attributes = null)
    {
        $page = 1;
        $limit = 50;
        $shippingClasses = [];

        do {
            try {
                $rawShippingClass = $this->client->request('get', "products/shipping_classes", ['per_page' => $limit, 'page' => $page]);
            } catch(\Exception $e) {
                set_log_extra('response', $e);
                throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve shipping class.');
            }
            $shippingClasses = array_merge($shippingClasses, $rawShippingClass);
            $page++;
        } while (count($rawShippingClass) > 0);

        if (count($shippingClasses)) {

            $logistics = collect($shippingClasses)->mapWithKeys(function ($item) {
                return [$item->id => $item];
            })->toArray();

            if (!is_null($attributes)) {
                // Get set logistics attribute
                $logistic = $attributes->where('name', 'shipping_class_id')->first();
                if (!empty($logistic)) {
                    $logistics[$logistic->value]['selected'] = true;
                }
            }
            return $logistics;
        }
        return [];
    }

    /**
     * @param $product
     *
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($product)
    {
        // Set product associated SKU and status
        $associatedSku = $product->sku ?? null;

        $shortDescription = $product->short_description ?? null;
        $htmlDescription = $product->description ?? null;
        $name = $product->name ?? null;
        $brand = null;
        $model = null;

        $options = [];

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = null;

        // Woocommerce support account category
        $accountCategory = null;
        if (isset($product->categories) && isset($product->categories[0])) {
            $categoryExternalId = $product->categories[0]->id;

            $accountCategory = AccountCategory::where([
                'account_id' => $this->account->id,
                'external_id' => $categoryExternalId,
            ])->first();
        }

        $category = null;
        if (!empty($accountCategory) && !is_null($accountCategory)) {
            $category = $accountCategory->category;
        }

        // Looping through and creating all the variants
        $variants = [];
        if (!empty($product->variation_details)) {
            foreach ($product->variation_details as $variant) {
                // Woocommerce does not support names for the variant, so we should use the default name
                $variantName = $variant->sku;

                // We pull the first variation as the associated_sku
                if (empty($associatedSku)) {
                    $associatedSku = $variant->sku;
                }
                $variantSku = $variant->sku;

                $barcode = null;
                $stock = $variant->stock_quantity ?? 0;
                $prices = [];

                //Normal price
                $variantPrice = (empty($variant->price)) ? 0 : $variant->regular_price;
                $variantSalePrice = (empty($variant->sale_price)) ? '' : $variant->sale_price;
                $prices[] = new TransformedProductPrice($this->account->currency, $variantPrice, ProductPriceType::SELLING());
                if ($variantSalePrice != '') {
                    $prices[] = new TransformedProductPrice($this->account->currency, $variantSalePrice, ProductPriceType::SPECIAL());
                }

                //Remove duplicated for variant attributes
                $variantAttributes['weight'] = $variant->weight;
                $variantAttributes['dimensions'] = (array) $variant->dimensions;
                $variantAttributes['description'] = $variant->description;

                $option1 = null;
                $option2 = null;
                $option3 = null;

                // Loop vartiant attributes and insert cause it might got multiple
                if (count($variant->attributes) > 0) {
                    foreach ($variant->attributes as $attribute) {
                        $variantAttributes[strtolower($attribute->name)] = $attribute->option;
                        if (empty($option1)) {
                            $option1 = $attribute->option;
                        } elseif (empty($option2)) {
                            $option2 = $attribute->option;
                        } elseif (empty($option3)) {
                            $option3 = $attribute->option;
                        }
                    }
                }

               // Variant images
                $images = [];
                if ($variant->image) {
                    $images[] = new TransformedProductImage($variant->image->src, $variant->image->id, null, null, 0);
                }

                $weightUnit = Weight::KILOGRAMS();
                $weight = (empty($variant->weight)) ? 0 : $variant->weight;

                $shippingType = ShippingType::MARKETPLACE();
                $dimensionUnit = Dimension::CM();
                $length = (empty($variant->dimensions->length)) ? 0 : $variant->dimensions->length;
                $width = (empty($variant->dimensions->width)) ? 0 : $variant->dimensions->width;
                $height = (empty($variant->dimensions->height)) ? 0 : $variant->dimensions->height;

                $productUrl = $variant->permalink;

                $identifiers = [
                    ProductIdentifier::EXTERNAL_ID()->getValue() => $variant->id,
                    ProductIdentifier::SKU()->getValue() => $variantSku
                ];

                if ($variant->status === 'publish') {
                    if ($variant->stock_quantity > 0) {
                        $status = ProductStatus::LIVE();
                        $marketplaceStatus = MarketplaceProductStatus::LIVE();
                    } else {
                        $status = ProductStatus::OUT_OF_STOCK();
                        $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
                    }
                } else if ($variant->status === 'draft' || $variant->status === 'future') {
                    $status = ProductStatus::DRAFT();
                    $marketplaceStatus = MarketplaceProductStatus::PENDING();
                } else if ($variant->status === 'pending') {
                    $status = ProductStatus::DISABLED();
                    $marketplaceStatus = MarketplaceProductStatus::PENDING();
                } else if ($variant->status === 'private') {
                    $status = ProductStatus::DISABLED();
                    $marketplaceStatus = MarketplaceProductStatus::DISABLED();
                } else {
                    set_log_extra('product_variant', $variant);
                    throw new \Exception('Unknown product variant status for Woocommerce');
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
        } else {
            $variant = $product;

            # If no variant then create own by using main product
            // Woocommerce does not support names for the variant, so we should use the default name
            $variantName = $product->name;

            // We pull the first variation as the associated_sku
            if (is_null($associatedSku)) {
                $associatedSku = $product->sku;
            }
            $variantSku = $product->sku;

            $barcode = null;
            $stock = (is_null($product->stock_quantity) || empty($product->stock_quantity)) ? 0 : $product->stock_quantity;
            $prices = [];

            //Normal price
            $productPrice = (empty($variant->regular_price)) ? 0 : $variant->regular_price;
            $variantSalePrice = (empty($variant->sale_price)) ? '' : $variant->sale_price;
            $prices[] = new TransformedProductPrice($this->account->currency, $productPrice, ProductPriceType::SELLING());
            if ($variantSalePrice != '') {
                $prices[] = new TransformedProductPrice($this->account->currency, $variantSalePrice, ProductPriceType::SPECIAL());
            }

            // Variant attribute will be same as main product attribute, no need to duplicate
            $variantAttributes = [];

            // Variant images will be same as main product images, no need to duplicate
            $images = [];

            $weightUnit = Weight::KILOGRAMS();
            $weight = (empty($product->weight)) ? 0 : $product->weight;

            $shippingType = ShippingType::MARKETPLACE();
            $dimensionUnit = Dimension::CM();
            $length = (empty($product->dimensions->length)) ? 0 : $product->dimensions->length;
            $width = (empty($product->dimensions->width)) ? 0 : $product->dimensions->width;
            $height = (empty($product->dimensions->height)) ? 0 : $product->dimensions->height;

            $productUrl = $product->permalink;

            $identifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $product->id,
                ProductIdentifier::SKU()->getValue() => $variantSku
            ];

            $option1 = null;
            $option2 = null;
            $option3 = null;

            if ($product->status === 'publish') {
                if ($product->stock_quantity > 0) {
                    $status = ProductStatus::LIVE();
                    $marketplaceStatus = MarketplaceProductStatus::LIVE();
                } else {
                    $status = ProductStatus::OUT_OF_STOCK();
                    $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
                }
            } else if ($product->status === 'draft' || $variant->status === 'future') {
                $status = ProductStatus::DRAFT();
                $marketplaceStatus = MarketplaceProductStatus::PENDING();
            } else if ($product->status === 'pending') {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::PENDING();
            } else if ($product->status === 'private') {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::DISABLED();
            } else {
                set_log_extra('product_variant', $product);
                throw new \Exception('Unknown product variant status for Woocommerce (Main product as variant)');
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


        $identifiers = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product->id];

        //Woocommerce doesn't have a product URL for the listing, only for each SKU
        $productUrl = $product->permalink;

        //This is so we don't save duplicated data in our database for main product attribute
        $attributes['type'] = $product->type;
        $attributes['shipping_required'] = $product->shipping_required;
        $attributes['shipping_taxable'] = $product->shipping_taxable;
        $attributes['shipping_class'] = $product->shipping_class;
        $attributes['tax_status'] = $product->tax_status;
        $attributes['tax_class'] = $product->tax_class;
        $attributes['sold_individually'] = $product->sold_individually;
        $attributes['purchase_note'] = $product->purchase_note;
        $attributes['menu_order'] = $product->menu_order;
        $attributes['reviews_allowed'] = $product->reviews_allowed;
        $attributes['weight'] = $product->weight;
        $attributes['dimensions'] = (array) $product->dimensions;

        // Loop attributes and insert cause it might got multiple
        if (count($product->attributes) > 0) {
            foreach ($product->attributes as $attribute) {
                $attributes[strtolower($attribute->name)] = $attribute->options;
            }
        }

        // Loop tags and insert cause it might got multiple
        if (count($product->tags) > 0) {
            foreach ($product->tags as $tag) {
                $attributes['tags'][] = $tag->name;
            }
        }

        //Woocommerce does not have images for the listing, only for each SKU
        $images = null;
        foreach($product->images as $index => $image) {
            if (!empty($image)) {
                $images[] = new TransformedProductImage($image->src, $image->id, null, null, $index);
            }
        }

        // Prices for main product
        $mainPrices = null;

        if ($product->status === 'publish') {
            if ($product->stock_quantity > 0) {
                $status = ProductStatus::LIVE();
                $marketplaceStatus = MarketplaceProductStatus::LIVE();
            } else {
                $status = ProductStatus::OUT_OF_STOCK();
                $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
            }
        } else if ($product->status === 'draft' || $product->status === 'future') {
            $status = ProductStatus::DRAFT();
            $marketplaceStatus = MarketplaceProductStatus::PENDING();
        } else if ($product->status === 'pending') {
            $status = ProductStatus::DISABLED();
            $marketplaceStatus = MarketplaceProductStatus::PENDING();
        } else if ($product->status === 'private') {
            $status = ProductStatus::DISABLED();
            $marketplaceStatus = MarketplaceProductStatus::DISABLED();
        } else {
            set_log_extra('product_variant', $product);
            throw new \Exception('Unknown product variant status for Woocommerce (Main product as variant)');
        }

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
     * @return bool
     * @throws \Exception
     */
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null)
    {
        $externalId = $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $product);
            throw new \Exception('Woocommerce product does not have product external id');
        }
        // Check whether is main or variant product
        if (!is_null($product->product_variant_id)) {
            if ($product->listing()->whereAccountId($this->account->id)->first()) {
                $endpoint = 'products/'. $product->listing()->whereAccountId($this->account->id)->first()->getIdentifier(ProductIdentifier::EXTERNAL_ID()) .'/variations/'. $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());
            } else {
                set_log_extra("Can't find product listing :", $product->product_id);
                return true;
            }
        } else {
            $endpoint = 'products/' . $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        }

        $parameters = ['stock_quantity' => $stock];
        $response = $this->client->request('put', $endpoint, $parameters);  

        $this->get($product);

        return true;
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
            throw new \Exception('Woocommerce product does not have product external id');
        }

        // Need to make sure is main product listing
        if (!empty($listing->listing) && !is_null($listing->listing)) {
            $mainProductId = $listing->listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            if (empty($mainProductId)) {
                set_log_extra('listing', $listing->listing);
                throw new \Exception('Woocommerce product does not have main product external id');
            }
            $endpoint = 'products/'. $mainProductId .'/variations/'. $externalId;
        } else {
            $endpoint = 'products/' . $externalId;
        }

        $status = ($enabled) ? 'publish' : 'private';

        $parameters = ['status' => $status];
        $response = $this->client->request('put', $endpoint, $parameters);

        // As Woocommerce doesn't return the updated product, we should refresh it here
        $this->get($listing, true);

        return true;
    }
}
