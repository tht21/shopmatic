<?php

namespace App\Integrations\PrestaShop;

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
use App\Integrations\TransformedAttribute;
use App\Integrations\TransformedProduct;
use App\Integrations\TransformedProductImage;
use App\Integrations\TransformedProductListing;
use App\Integrations\TransformedProductPrice;
use App\Integrations\TransformedProductVariant;
use App\Models\AccountCategory;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;


class ProductAdapter extends AbstractProductAdapter
{
    /**
     * Retrieves a single product
     *
     * @param $listing
     * @param bool $update
     * @param null $itemId
     *
     * @throws \Exception
     */
    public function get($listing, $update = false, $itemId = null)
    {
        $externalId = null;
        if ($itemId) {
            $externalId = $itemId;
        } elseif ($listing) {
            // Need to make sure is main product listing
            if (!empty($listing->listing) && !is_null($listing->listing)) {
                $listing = $listing->listing;
            }

            $externalId = $listing->identifiers['external_id'];
        }

        $product = $this->getProduct($externalId);

        try {
            $product = $this->transformProduct($product);
        } catch (\Exception $e) {
            set_log_extra('product', $product);
            throw $e;
        }
        $this->handleProduct($product, ['update' => $update, 'new' => $update]);
    }

    /**
     * Import all new products from presta shop
     *
     * @param $importTask
     * @param $config
     * @return void
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        $response = $this->client->request('GET', 'products');
        $response = json_decode($response->getBody()->getContents());

        if (isset($response->products)) {

            /* store total products for this import */
            if (!empty($importTask) && empty($importTask->total_products)) {
                $importTask->total_products = count($response->products);
                $importTask->save();
            }

            foreach ($response->products as $key => $value) {
                $product = $this->getProduct($value->id);

                try {
                    $product = $this->transformProduct($product);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }

                $this->handleProduct($product, $config);
            }
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve products for PrestaShop');
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }
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

        $response = $this->client->request('GET', 'products');

        $response = json_decode($response->getBody()->getContents());

        if (isset($response->products)) {
            foreach ($response->products as $key => $value) {
                $product = $this->getProduct($value->id);

                if (!is_null($product)) {
                    try {
                        $product = $this->transformProduct($product);
                    } catch (\Exception $e) {
                        set_log_extra('product', $product);
                        throw $e;
                    }

                    $this->handleProduct($product);
                }
            }
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve products for PrestaShop');
        }

    }

    /**
     * Retrieve variants, images, options for a product
     *
     * @param $id
     * @return array $product
     * @throws \Exception
     */
    public function getProduct($id)
    {
        $response = $this->client->request('GET', 'products/' . $id);
        $response = json_decode($response->getBody()->getContents());

        if (isset($response->product)) {
            $product = $response->product;
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve product for PrestaShop');
        }

        $images = [];
        $variants = [];
        $options = [];
        $stockAvailables = [];
        $categories = [];

        if (isset($product->associations)) {
            // Get images
            if (isset($product->associations->images)) {
                foreach ($product->associations->images as $key => $image) {
                    $imageUrl = 'images/products/'. $product->id .'/'. $image->id;
                    //$response = $this->client->request('GET', $imageUrl);

                    //if ($response->getStatusCode() === 200) {
                        $images[$image->id] = [
                            'id' => $image->id,
                            'url' => $this->client->getFullPath() . $imageUrl.'?ws_key='.$this->account->credentials['access_key']
                        ];
                    //}

                    // @NOTE - this prestashop has error, but still able to get the images. need to check further with other site
                    /*$temp = (array)json_decode($response->getBody()->getContents());
                    if (isset($temp[''])) {
                        foreach ($temp[''] as $key => $image) {
                            if ($key == 0) continue; // first is not the image
                            // @NOTE - the protocol
                            $images[$image->id] = [
                                'id' => $image->id,
                                'url' => $this->client->getFullPath() . $requestUrl .'/' . $image->id
                            ];
                        }
                    }*/
                }
            }
            // Get (combinations) variants
            if (isset($product->associations->combinations)) {
                foreach ($product->associations->combinations as $key => $combination) {
                    $response = $this->client->request('GET', 'combinations/' . $combination->id);
                    $response = json_decode($response->getBody()->getContents());
                    $variants[] = $response->combination;
                }
            }
            // Get options
            if (isset($product->associations->product_option_values)) {
                foreach ($product->associations->product_option_values as $key => $product_option_values) {
                    if (!is_null($product_option_values->id)) {
                        $response = $this->client->request('GET', 'product_option_values/' . $product_option_values->id);

                        $response = json_decode($response->getBody()->getContents());
                        // get options name/group
                        $group = $this->client->request('GET', 'product_options/' . $response->product_option_value->id_attribute_group);
                        $group = json_decode($group->getBody()->getContents());
                        $response->product_option_value->attribute_group = $group->product_option;

                        $options[$response->product_option_value->id] = $response->product_option_value;
                    }
                }
            }
            // stock availables
            if (isset($product->associations->stock_availables)) {
                foreach ($product->associations->stock_availables as $key => $stock_availables) {
                    $response = $this->client->request('GET', 'stock_availables/' . $stock_availables->id);

                    $response = json_decode($response->getBody()->getContents());

                    if (isset($response->stock_available)) {
                        $stockAvailables[$stock_availables->id_product_attribute] = $response->stock_available;
                    }
                }
            }
            // Categories
            if (isset($product->associations->categories)) {
                foreach ($product->associations->categories as $key => $category) {
                    $response = $this->client->request('GET', 'categories/' . $category->id);

                    $response = json_decode($response->getBody()->getContents());

                    if (isset($response->category)) {
                        $categories[$category->id] = $response->category;
                    }
                }
            }
        }

        $product->images = $images;
        $product->variants = $variants;
        $product->options = $options;
        $product->stock_availables = $stockAvailables;
        $product->categories = $categories;

        return $product;
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
        $itemId = $data['identifiers']['external_id'] ?? $product->identifiers['external_id'];
        $firstVariant = reset($data['variants']);

        $productParams = [
            'id' => $itemId,
            'name' => $data['attributes']['name']['value'] ?? $data['name'],
            'weight' => $firstVariant['weight'], // get from first variant
            'width' => $firstVariant['width'], // get from first variant
            'depth' => $firstVariant['length'], // get from first variant
            'height' => $firstVariant['height'], // get from first variant
            'ean13' => $data['attributes']['ean13']['value'] ?? '',
            'isbn' => $data['attributes']['isbn']['value'] ?? '',
            'upc' => $data['attributes']['upc']['value'] ?? '',
            'description' => strip_tags($data['attributes']['html_description']['value'] ?? $data['html_description']),
            'description_short' => strip_tags($data['attributes']['short_description']['value'] ?? $data['short_description']),
            'reference' => $data['associated_sku'] ?? '',
            'condition' => $data['attributes']['condition']['value'] ?? '',
        ];

        // Prices (filter duplicate type price)
        $prices = [];
        foreach (Constant::PRICES() as $priceType) {
            foreach ($data['prices'] as $price) {
                if ($price['type'] === $priceType->getValue()) {
                    $prices[$priceType->getValue()] = $price['price'];
                    break;
                }
            }
        }
        $productParams['price'] = (float) $prices[ProductPriceType::SELLING()->getValue()] ?? 0;
        $productParams['wholesale_price'] = (float) $prices[ProductPriceType::WHOLESALE()->getValue()] ?? 0;

        // Account Category
        if (isset($data['attributes']['categories']['value']) && !empty($data['attributes']['categories']['value'])) {
            $productParams['categories'][] = 2; // Must include the main default category
            // Decode json format - Because we save categories in json format on product_attributes table
            foreach ($data['attributes']['categories']['value'] as $category) {
                if (isset($category['id'])) {
                    $productParams['categories'][] = $category['id'];
                }
            }
        }

        // Images
        /** @var ProductImage[] $images */
        $imageData = [];
        foreach ($data['images'] as $name => $value) {
            if (!isset($value['deleted'])) {
                if (isset($value['data_url'])) {
                    $src = uploadImageFile($value['data_url'], session('shop'));
                } else {
                    $src =  $value['image_url'];
                }

                $imageData[] = [
                    'url' => $src,
                    'variant_id' => null
                ];
            }
        }

        $variantParam = [];
        foreach ($data['variants'] as $key => $variant) {
            $variantId = $variant['identifiers']['external_id'];

            // Images
            if (count($variant['images']) > 0) {
                foreach ($variant['images'] as $key => $value) {
                    if (!isset($value['deleted'])) {
                        if (isset($value['data_url'])) {
                            $src = uploadImageFile($value['data_url'], session('shop'));
                        } else {
                            $src =  $value['image_url'];
                        }

                        $imageData[] = [
                            'url' => $src,
                            'variant_id' => $variantId
                        ];
                    }
                }
            }

            $variantParam[$key] = [
                'id' => $variantId,
                'reference' => $variant['sku'] ?? '',
                'quantity' => (int) $variant['inventory']['stock'],
                'minimal_quantity' => 0,
                'weight' => $variant['attributes']['weight']['value'] ?? $variant['weight'],
                'ean13' => $variant['attributes']['ean13']['value'] ?? '',
                'isbn' => $variant['attributes']['isbn']['value'] ?? '',
                'upc' => $variant['attributes']['upc']['value'] ?? '',
            ];

            // Prices
            $prices = [];
            foreach (Constant::PRICES() as $priceType) {
                foreach ($variant['prices'] as $price) {
                    if ($price['type'] === $priceType->getValue()) {
                        $prices[$priceType->getValue()] = $price['price'];
                        break;
                    }
                }
            }
            $variantParam[$key]['price'] = (float) $prices[ProductPriceType::SELLING()->getValue()] ?? 0;
            $variantParam[$key]['wholesale_price'] = (float) $prices[ProductPriceType::WHOLESALE()->getValue()] ?? 0;

            /*
             * If there is options
             * Check product attribute options first, if there is no options in product attribute
             * then only check on products table options
             */
            // Get integration options level
            $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;

            for ($i = 1; $i <= $optionsLevels; $i++) {
                // Get all attributes (for prestashop the attribute is like an option)
                $integrationProductOptions = $this->retrieveProductOptions();

                if (isset($data['option_'.$i]) && !is_null($data['option_'.$i]) && !empty($data['option_'.$i])) {
                    $productOption = $data['option_'.$i];
                    if (array_search(strtolower($productOption), array_map('strtolower', array_column($integrationProductOptions, 'name'))) === false) {
                        // If not found in prestashop, then create it the product option
                        $this->createOption($productOption);
                    }

                    if (isset($variant['option_'.$i]) && !is_null($variant['option_'.$i]) && !empty($variant['option_'.$i])) {
                        $optionValue = $variant['option_'.$i];
                        $integrationProductOptionsKey = array_search(strtolower($productOption), array_map('strtolower', array_column($integrationProductOptions, 'name')));

                        // Check product option value exists or not
                        $existsProductOptionValue = array_search($optionValue, array_column($integrationProductOptions[$integrationProductOptionsKey]['values'], 'name'));
                        if ($existsProductOptionValue === false) {
                            // If not found in prestashop, then create it the product option value
                            $this->createOptionValue($integrationProductOptions[$integrationProductOptionsKey]['id'], $optionValue);

                            // Retrieve the product options again after create product option value
                            $integrationProductOptions = $this->retrieveProductOptions();
                        }

                        // Get the option value id
                        $productOptionValueKey = array_search($optionValue, array_column($integrationProductOptions[$integrationProductOptionsKey]['values'], 'name'));
                        if ($productOptionValueKey !== false) {
                            $variantParam[$key]['option_values'][] = $integrationProductOptions[$integrationProductOptionsKey]['values'][$productOptionValueKey]['id'];
                        }
                    }
                }
            }
        }

        // Retrieve the original product
        $response = $this->client->request('GET', 'products/'.$itemId);
        $response = json_decode($response->getBody()->getContents(), true);
        unset($response['product']['associations']);

        $productParams = array_merge($response['product'], $productParams);

        $xmlData = $this->dataToXml($productParams, 'product', true);

        $parameters = [
            'body' => $xmlData
        ];

        // Create main product first
        $response = $this->client->request('PUT', 'products', $parameters);

        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201 && $response->getStatusCode() !== 202) {
            set_log_extra('response', $response->getBody()->getContents());
            set_log_extra('product', $productParams);
            throw new \Exception('Unable to update product from Presta Shop.');
        }

        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['product'])) {
            set_log_extra('response', $response);
            set_log_extra('product', $productParams);
            throw new \Exception('Unable to update product, unable to find product param from Presta Shop.');
        }

        $productId = $response['product']['id'];

        /* Upload all images */
        if (count($imageData)) {
            $images = $this->uploadImages($productId, $imageData, true);
        }

        // Loop and create variants
        if (count($variantParam) > 0) {
            $variantStocks = [];
            foreach ($variantParam as $data) {
                // Append product id to all variant
                $data['id_product'] = $productId;

                // Append image id to variant data
                if (count($images)) {
                    foreach ($images as $image) {
                        if (!is_null($image['variant_id']) && $image['variant_id'] == $data['id']) {
                            $data['images'][] = $image['image']['id'];
                        }
                    }
                }

                // Retrieve the original combination
                $response = $this->client->request('GET', 'combinations/'.$data['id']);
                $response = json_decode($response->getBody()->getContents(), true);
                unset($response['combination']['associations']);

                $data = array_merge($response['combination'], $data);
                $xmlData = $this->dataToXml($data, 'variant', true);

                $parameters = [
                    'body' => $xmlData
                ];
                // Create variant as combination
                $response = $this->client->request('PUT', 'combinations', $parameters);

                if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201 && $response->getStatusCode() !== 202) {
                    set_log_extra('response', $response->getBody()->getContents());
                    set_log_extra('variant', $data);
                    throw new \Exception('Unable to update product variant from Presta Shop.');
                }
                $response = json_decode($response->getBody()->getContents(), true);

                // Store all the variant stock info here
                $variantStocks[$response['combination']['id']] = [
                    'combination_id' => $response['combination']['id'],
                    'quantity' => $data['quantity']
                ];
            }

            /* Update stock quantity for every variant (combination) */
            // Retrieve the product again to get the latest stock available id
            $response = $this->client->request('GET', 'products/'.$productId);
            $productResponse = json_decode($response->getBody()->getContents(), true);

            foreach ($variantStocks as $variantStock) {
                // Get the stock available id base on combination/product attribute id
                $stockAvailableKey = array_search($variantStock['combination_id'], array_column($productResponse['product']['associations']['stock_availables'], 'id_product_attribute'));
                if ($stockAvailableKey === false) {
                    set_log_extra('variant', $variantStock);
                    set_log_extra('product', $productResponse);
                    throw new \Exception('Unable to find variant stock from Presta Shop.');
                }

                $stockAvailableId = $productResponse['product']['associations']['stock_availables'][$stockAvailableKey]['id'];

                // Update stock available
                $this->updateStockAvailable($stockAvailableId, $variantStock['quantity']);
            }
        }
        $product = $this->get($product, true);

        return $this->respond($product);
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
        $this->rules = [
            'reference.value' => 'nullable|between:0,64',
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
     * Creates a new product on the account from the product model
     *
     * @param Product $product
     *
     * @return mixed
     * @throws \Exception
     */
    public function create(Product $product)
    {
        $integrationId = Integration::PRESTASHOP;
        // pre-load required relation data
        $this->preLoadProductData($product);

        // map attributes data to array with name as key
        $attributes = $product->attributes->where('product_variant_id', null)->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });

        // Get prestashop price types
        $priceTypes = [];
        foreach (Constant::PRICES() as $priceType) {
            $priceTypes[] = $priceType->getValue();
        }

        /** @var ProductVariant $firstVariant */
        $firstVariant = $product->variants()->first();
        $account = $this->account;
        $prices = $firstVariant->prices()->whereIn('type', $priceTypes)->where(function (Builder $query) use ($account, $product, $firstVariant) {
            $query->whereProductId($product->id)->whereProductVariantId($firstVariant->id)->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
        })->orWhere(function (Builder $query) use ($account, $product, $firstVariant) {
            $query->whereProductId($product->id)->whereProductVariantId($firstVariant->id)->whereNull('region_id')->whereNull('integration_id');
        })
        ->orderBy('integration_id', 'asc')
        ->orderBy('region_id', 'asc')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item['type'] => $item];
        });
        $firstVariantAttributes = $firstVariant->attributes->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });

        $weight = $firstVariantAttributes['weight'] ?? null;
        $width = $firstVariantAttributes['width'] ?? null;
        $length = $firstVariantAttributes['length'] ?? null;
        $height = $firstVariantAttributes['height'] ?? null;

        $productData = [
            'name' => $attributes['name']->value ?? $product->name,
            'weight' => ($weight ?  $weight->value : $product->variants[0]->weight), // get from first variant
            'width' => ($width ? $width->value : $product->variants[0]->width), // get from first variant
            'depth' => ($length ? $length->value : $product->variants[0]->length), // get from first variant
            'height' => ($height ? $height->value : $product->variants[0]->height), // get from first variant
            'description' => strip_tags( (isset($attributes['html_description'])) ? $attributes['html_description']->value : $product->html_description),
            'description_short' => (isset($attributes['short_description'])) ? $attributes['short_description']->value : $product->short_description,
            'price' => (float) (isset($prices[ProductPriceType::SELLING()->getValue()])) ? (float) $prices[ProductPriceType::SELLING()->getValue()]->price : 0,
            'wholesale_price' => (float) (isset($prices[ProductPriceType::WHOLESALE()->getValue()])) ? (float) $prices[ProductPriceType::WHOLESALE()->getValue()]->price : 0,
            'reference' => (isset($attributes['sku'])) ? $attributes['sku']->value : $product->associated_sku,
            'condition' => strtolower((isset($attributes['condition'])) ? $attributes['condition']->value : ''),
            'ean13' => (isset($attributes['ean13'])) ? $attributes['ean13']->value : '',
            'isbn' => (isset($attributes['isbn'])) ? $attributes['isbn']->value : '',
            'upc' => (isset($attributes['upc'])) ? $attributes['upc']->value : '',
        ];

        // Account Categories
        $retrieveAttributes = ['categories'];
        foreach ($retrieveAttributes as $retrieveAttribute) {
            if (isset($attributes[$retrieveAttribute]) && !empty($attributes[$retrieveAttribute])) {
                $productData[$retrieveAttribute][] = 2; // Must include the main default category
                // Decode json format - Because we save categories in json format on product_attributes table
                foreach (json_decode($attributes[$retrieveAttribute]->value, true) as $value) {
                    if (isset($value['id'])) {
                        $productData[$retrieveAttribute][] = $value['id'];
                    }
                }
            }
        }

        // Images
        /** @var ProductImage[] $images */
        $imageData = [];
        $images = $product->allImages->where('integration_id', $integrationId);
        if (count($images)) {
            foreach ($images as $key => $image) {
                $imageData[] = [
                    'url' => $image->image_url,
                    'variant_id' => null
                ];
            }
        }

        // Variants
        $variantData = [];
        foreach ($product->variants as $key => $variant) {
            // Get all variant attributes
            $variantAttributes = $variant->attributes->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

            // Variant prices
            $variantPrices = $variant->prices()->whereIn('type', $priceTypes)->where(function (Builder $query) use ($account, $product, $variant) {
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

            $variantData[$key] = [
                'id' => $variant->id,
                'reference' => (isset($variantAttributes['sku'])) ? $variantAttributes['sku']->value : $variant->sku,
                'quantity' => $variant->inventory->stock,
                'minimal_quantity' => 0,
                'weight' => $data['weight'] ?? '',
                'ean13' => (isset($variantAttributes['ean13'])) ? $variantAttributes['ean13']->value : '',
                'isbn' => (isset($variantAttributes['isbn'])) ? $variantAttributes['isbn']->value : '',
                'upc' => (isset($variantAttributes['upc'])) ? $variantAttributes['upc']->value : '',
            ];

            // Prices
            $variantData[$key]['price'] = (float) (isset($variantPrices[ProductPriceType::SELLING()->getValue()])) ? (float) $variantPrices[ProductPriceType::SELLING()->getValue()]->price : 0;
            $variantData[$key]['wholesale_price'] = (float) (isset($variantPrices[ProductPriceType::WHOLESALE()->getValue()])) ? (float) $variantPrices[ProductPriceType::WHOLESALE()->getValue()]->price : 0;

            /*
             * The variant price must calculate with the retail price/main product price difference
             * Because for combination price it will take the retail price and add/deduct the combination price
             * The variant price will be the impact for final price
             * Does this combination have a different price? Is it cheaper or more expensive than the default retail price?
            **/
            $variantData[$key]['price'] = ($variantData[$key]['price'] - $productData['price']);

            // Images
            $variantImages = $variant->allImages->where('integration_id', $integrationId);
            if (count($variantImages)) {
                foreach ($variantImages as $variantImage) {
                    $imageData[] = [
                        'url' => $variantImage->image_url,
                        'variant_id' => $variant->id
                    ];
                }
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
                // Get all attributes (for prestashop the attribute is like an option)
                $integrationProductOptions = $this->retrieveProductOptions();
                // Get integration options level
                $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;

                // Convert to array if options value is json string
                if (!is_array($optionsValue)) {
                    $optionsValue = json_decode($optionsValue, true);
                }

                $mainProductOptions = array_values($optionsValue);
                $mainProductOptions = array_splice($mainProductOptions, 0, $optionsLevels);

                // Check whether option exists in prestashop
                foreach ($mainProductOptions as $mainProductOption) {
                    if (array_search(strtolower($mainProductOption), array_map('strtolower', array_column($integrationProductOptions, 'name'))) === false) {
                        // If not found in prestashop, then create it the product option
                        $response = $this->createOption($mainProductOption);
                        $response = json_decode($response->getBody()->getContents(), true);

                        // Push the new option into options list
                        $integrationProductOptions[] = $response['product_option'] + ['values' => []];
                    }
                }

                for ($i = 1; $i <= $optionsLevels; $i++) {
                    $variantOptionValue = null;
                    if ($fromAttributes && isset($variantAttributes['option_'.$i])) {
                        $variantOptionValue = $variantAttributes['option_'.$i]->value;
                    } else if (!$fromAttributes && (isset($variant['option_'.$i]))) {
                        $variantOptionValue = $variant['option_'.$i];
                    }

                    if (isset($mainProductOptions[$i - 1]) && !is_null($variantOptionValue)) {
                        $integrationProductOptionsKey = array_search(strtolower($mainProductOptions[$i - 1]), array_map('strtolower', array_column($integrationProductOptions, 'name')));

                        // Check product option value exists or not
                        $existsProductOptionValue = array_search($variantOptionValue, array_column($integrationProductOptions[$integrationProductOptionsKey]['values'], 'name'));
                        if ($existsProductOptionValue === false) {
                            // If not found in prestashop, then create it the product option value
                            $response = $this->createOptionValue($integrationProductOptions[$integrationProductOptionsKey]['id'], $variantOptionValue);
                            $response = json_decode($response->getBody()->getContents(), true);

                            // Push the new option value into options list
                            $integrationProductOptions[$integrationProductOptionsKey]['values'][] = $response['product_option_value'];
                        }

                        // Get the option value id
                        $productOptionValueKey = array_search($variantOptionValue, array_column($integrationProductOptions[$integrationProductOptionsKey]['values'], 'name'));
                        if ($productOptionValueKey !== false) {
                            $variantData[$key]['option_values'][] = $integrationProductOptions[$integrationProductOptionsKey]['values'][$productOptionValueKey]['id'];
                        }
                    }
                }
            }
        }

        $xmlData = $this->dataToXml($productData, 'product');

        $parameters = [
            'body' => $xmlData
        ];

        // Create main product first
        $response = $this->client->request('POST', 'products', $parameters);

        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201 && $response->getStatusCode() !== 202) {
            set_log_extra('response', $response->getBody()->getContents());
            set_log_extra('product', $productData);
            throw new \Exception('Unable to create product from Presta Shop.');
        }

        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['product'])) {
            set_log_extra('response', $response);
            set_log_extra('product', $productData);
            throw new \Exception('Unable to find product param from Presta Shop.');
        }

        $productId = $response['product']['id'];

        /* Upload all images */
        if (count($imageData)) {
            $images = $this->uploadImages($productId, $imageData);
        }

        // Loop and create variants
        if (count($variantData) > 0) {
            $variantStocks = [];
            foreach ($variantData as $data) {
                // Append product id to all variant
                $data['id_product'] = $productId;

                // Append image id to variant data
                if (count($images)) {
                    foreach ($images as $image) {
                        if (!is_null($image['variant_id']) && $image['variant_id'] == $data['id'] && isset($image['image']['id'])) {
                            $data['images'][] = $image['image']['id'];
                        }
                    }
                }

                $xmlData = $this->dataToXml($data, 'variant');

                $parameters = [
                    'body' => $xmlData
                ];
                // Create variant as combination
                $response = $this->client->request('POST', 'combinations', $parameters);

                if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201 && $response->getStatusCode() !== 202) {
                    set_log_extra('response', $response);
                    set_log_extra('variant', $data);
                    throw new \Exception('Unable to create product variant from Presta Shop.');
                }
                $response = json_decode($response->getBody()->getContents(), true);

                // Store all the variant stock info here
                $variantStocks[$response['combination']['id']] = [
                    'combination_id' => $response['combination']['id'],
                    'quantity' => $data['quantity']
                ];
            }

            /* Update stock quantity for every variant (combination) */
            // Retrieve the product again to get the latest stock available id
            $response = $this->client->request('GET', 'products/'.$productId);
            $productResponse = json_decode($response->getBody()->getContents(), true);

            foreach ($variantStocks as $variantStock) {
                // Get the stock available id base on combination/product attribute id
                $stockAvailableKey = array_search($variantStock['combination_id'], array_column($productResponse['product']['associations']['stock_availables'], 'id_product_attribute'));
                if ($stockAvailableKey === false) {
                    set_log_extra('variant', $variantStock);
                    set_log_extra('product', $productResponse);
                    throw new \Exception('Unable to find variant stock from Presta Shop.');
                }

                $stockAvailableId = $productResponse['product']['associations']['stock_availables'][$stockAvailableKey]['id'];

                // Update stock available
                $this->updateStockAvailable($stockAvailableId, $variantStock['quantity']);
            }
        }

        //sleep(10);
        $product = $this->get(null, false, $productId);

        return $this->respondCreated($product);
    }

    /**
     * Convert frontend data to lazada's xml format
     *
     * @param $data
     * @param $type
     * @param bool $isUpdate
     * @return string
     */
    public function dataToXml($data, $type, $isUpdate = false)
    {
        $xmlData = [];
        if ($type == 'product') {
            /* Format Data - START */
            $xmlData = [
                'prestashop' => [
                    'product' =>[
                        'id_category_default' => $data['id_category_default'] ?? '',
                        'id_tax_rules_group' => $data['id_tax_rules_group'] ?? '',
                        'weight'    => $data['weight'],
                        'depth'     => $data['depth'],
                        'width'     => $data['width'],
                        'height'    => $data['height'],
                        'description_short'     => $data['description_short'],
                        'description'   => $data['description'],
                        'price' => $data['price'],
                        'wholesale_price' => $data['wholesale_price'],
                        'unit_price_ratio' => $data['unit_price_ratio'] ?? 0,
                        'additional_shipping_cost' => $data['additional_shipping_cost'] ?? 0,
                        'show_price' => 1,
                        'link_rewrite' => [
                            'language' => ''
                        ],
                        'name' => [
                            'language' => $data['name']
                        ],
                        'reference' => $data['reference'] ?? '',
                        'supplier_reference' => $data['supplier_reference'] ?? '',
                        'quantity_discount' => $data['quantity_discount'] ?? 0,
                        'location' => $data['location'] ?? '',
                        'condition' => strtolower($data['condition']),
                        'ean13' => $data['ean13'],
                        'isbn' => $data['isbn'],
                        'upc' => $data['upc'],
                        'on_sale' => $data['on_sale'] ?? 0,
                        'online_only' => $data['online_only'] ?? 0,
                        'ecotax' => $data['ecotax'] ?? 0,
                        'minimal_quantity' => $data['minimal_quantity'] ?? 0,
                        'available_date' => $data['available_date'] ?? '',
                        'advanced_stock_management' => $data['advanced_stock_management'] ?? 0,
                        'pack_stock_type' => $data['pack_stock_type'] ?? '',
                        'state' => 1,
                        'active' => $data['active'] ?? 1,
                        'available_for_order' => 1,
                    ]
                ]
            ];

            // If update need to add in product id
            if ($isUpdate){
                $xmlData['prestashop']['product']['id'] = $data['id'];
            }
            if (isset($data['categories']) && !empty($data['categories'])) {
                foreach ($data['categories'] as $category) {
                    $xmlData['prestashop']['product']['associations']['categories'][] = [
                        'id' =>  $category
                    ];
                }
            }
            if (isset($data['images']) && !empty($data['images'])) {
                foreach ($data['images'] as $image) {
                    $xmlData['prestashop']['product']['associations']['images'][] = [
                        'id' =>  $image
                    ];
                }
            }
        } else if ($type == 'variant') {
            /* Format Data - START */
            $xmlData = [
                'prestashop' => [
                    'combination' => [
                        'id_product'    => $data['id_product'],
                        'location'      => $data['location'] ?? '',
                        'reference'     => $data['reference'] ?? '',
                        'supplier_reference'    => $data['supplier_reference'] ?? '',
                        'minimal_quantity'  => $data['minimal_quantity'],
                        'price'     => $data['price'],
                        'wholesale_price' => $data['wholesale_price'],
                        'ecotax'    => $data['ecotax'] ?? 0,
                        'ean13'     => $data['ean13'],
                        'isbn'      => $data['isbn'],
                        'upc'       => $data['upc'],
                        'weight'    => $data['weight']
                    ]
                ]
            ];

            // If update need to add in combination id
            if ($isUpdate){
                $xmlData['prestashop']['combination']['id'] = $data['id'];
            }
            if (isset($data['option_values']) && !empty($data['option_values'])) {
                foreach ($data['option_values'] as $option_value) {
                    $xmlData['prestashop']['combination']['associations']['product_option_values'][] = [
                        'id' =>  $option_value
                    ];
                }
            }
            if (isset($data['images']) && !empty($data['images'])) {
                foreach ($data['images'] as $image) {
                    $xmlData['prestashop']['combination']['associations']['images'][] = [
                        'id' =>  $image
                    ];
                }
            }
        } else if ($type == 'stock_available') {
            $xmlData = [
                'prestashop' => [
                    'stock_available' => [
                        'id' => $data['id'],
                        'id_product' => $data['id_product'],
                        'id_product_attribute' => $data['id_product_attribute'],
                        'id_shop'       => $data['id_shop'],
                        'quantity'      => $data['quantity'],
                        'depends_on_stock' => $data['depends_on_stock'],
                        'out_of_stock'  => $data['out_of_stock'],
                        'location'      => $data['location'],
                    ]
                ]
            ];
        } else if ($type == 'product_option') {
            $xmlData = [
                'prestashop' => [
                    'product_option' => [
                        'is_color_group' => $data['is_color_group'] ?? 0,
                        'group_type' => $data['group_type'],
                        'name' => [
                            'language' => $data['name']
                        ],
                        'public_name' => [
                            'language' => $data['public_name']
                        ]
                    ]
                ]
            ];
        } else if ($type == 'product_option_value') {
            $xmlData = [
                'prestashop' => [
                    'product_option_value' => [
                        'id_attribute_group' => $data['id_attribute_group'],
                        'name' => [
                            'language' => $data['name']
                        ],
                    ]
                ]
            ];
        }
        /* Format Data - END */

        /* Generate XML String - START */
        $document = new \DOMDocument();
        $this->arrayToDOMDoc($document, $document, $xmlData);
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        return $document->saveXML();
    }

    /**
     * @param \DOMDocument $document
     * @param \DOMElement|\DOMDocument $currentElement
     * @param array|string $xmlData
     * @param null $childName
     */
    private function arrayToDOMDoc(\DOMDocument &$document, &$currentElement, $xmlData, $childName = null)
    {
        // recursive fill data
        if (is_array($xmlData)) {
            foreach ($xmlData as $name => $data) {
                // special for Skus array without custom key
                if (is_numeric($name)) $name = $childName;

                $element = $document->createElement($name);
                $currentElement->appendChild($element);

                if ($name === 'language') {
                    $element->setAttribute('id', 1);
                    $this->arrayToDOMDoc($document, $element, $data);
                } else if ($name === 'product_option_values') {
                    $this->arrayToDOMDoc($document, $element, $data, 'product_option_value');
                } else if ($name === 'stock_availables') {
                    $element->setAttribute('nodeType', 'stock_available');
                    $element->setAttribute('api', 'stock_availables');
                    $this->arrayToDOMDoc($document, $element, $data, 'stock_available');
                } else if ($name === 'categories') {
                    $this->arrayToDOMDoc($document, $element, $data, 'category');
                } else if ($name === 'images') {
                    $this->arrayToDOMDoc($document, $element, $data, 'image');
                } else {
                    $this->arrayToDOMDoc($document, $element, $data);
                }
            }
        } else {
            $currentElement->appendChild($document->createTextNode(trim($xmlData)));
        }
    }

    /**
     * Upload images to presta shop
     *
     * @param $productId
     * @param $images
     * @param bool $isUpdate
     * @return array
     * @throws \Exception
     */
    private function uploadImages($productId, $images, $isUpdate = false)
    {
        // If is update then remove all the images only re-upload again
        if ($isUpdate) {
            $response = $this->client->request('GET', 'products/'.$productId);
            $response = json_decode($response->getBody()->getContents(), true);
            $product = $response['product'];

            if (isset($product['associations']['images']) && !empty($product['associations']['images'])) {
                foreach ($product['associations']['images'] as $image) {
                    $response = $this->client->request('DELETE', 'images/products/'.$productId.'/'.$image['id']);

                    if ($response->getStatusCode() !== 200) {
                        set_log_extra('product', $product);
                        set_log_extra('image', $image);
                        set_log_extra('response', $response->getBody()->getContents());
                        throw new \Exception('Unable to delete image from Presta Shop.');
                    }
                }
            }
        }

        $data = [];
        if (count($images)) {
            foreach ($images as $image) {
                if (isset($image['url'])) {
                    // Store temporary image file, because presta shop only can allow to upload image on local storage
                    $filename = basename($image['url']);
                    $folder = 'temp_images';
                    $path = storage_path("app/".$folder);

                    // Create temporary folder if does not exists
                    if(!Storage::disk('local')->exists($folder)) {
                        Storage::disk('local')->makeDirectory($folder, 0775, true); //creates directory
                    }

                    $fullPath = $path.'/'.$filename;
                    Image::make($image['url'])->save($fullPath, 50);

                    if(class_exists('CURLFile')) {
                        $imageFile = new \CURLFile($fullPath);
                    } else {
                        $imageFile = '@' . realpath($fullPath);
                    }

                    $postFields = [
                        //'legend' => 'Test',
                        'image' => $imageFile
                    ];

                    $response = $this->client->requestImage('images/products/'.$productId, $postFields);
                    $response = json_encode(simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA));
                    $response = json_decode($response,TRUE);

                    $data[] = $response + [
                            'variant_id' => ($image['variant_id']) ?? null
                    ];

                    // Delete temporary image file
                    File::delete($fullPath);
                }
            }
        }
        return $data;
    }

    /**
     * Create product option
     *
     * @param $name
     * @param string $groupType
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function createOption($name, $groupType = 'select')
    {
        $data = [
            'group_type' => $groupType,
            'name' => $name,
            'public_name' => $name
        ];

        $xmlData = $this->dataToXml($data, 'product_option');

        $parameters = [
            'body' => $xmlData
        ];
        // Create product option in prestashop
        return $this->client->request('post', 'product_options', $parameters);
    }

    /**
     * Create product option value under specified option
     *
     * @param $attributeGroupId
     * @param $name
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function createOptionValue($attributeGroupId, $name)
    {
        $data = [
            'id_attribute_group' => $attributeGroupId,
            'name' => $name,
        ];

        $xmlData = $this->dataToXml($data, 'product_option_value');

        $parameters = [
            'body' => $xmlData
        ];
        // Create product option value in prestashop
        return $this->client->request('post', 'product_option_values', $parameters);
    }

    /**
     * Update product stock available
     *
     * @param $stockAvailableId
     * @param $stock
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    private function updateStockAvailable($stockAvailableId, $stock)
    {
        // Retrieve the stock available
        $stockAvailableResponse = $this->client->request('GET', 'stock_availables/'.$stockAvailableId);
        $stockAvailableResponse = json_decode($stockAvailableResponse->getBody()->getContents(), true);

        $data = [
            'id' => $stockAvailableId,
            'id_product' => $stockAvailableResponse['stock_available']['id_product'],
            'id_product_attribute' => $stockAvailableResponse['stock_available']['id_product_attribute'],
            'id_shop' => $stockAvailableResponse['stock_available']['id_shop'],
            'quantity' => $stock,
            'depends_on_stock' => $stockAvailableResponse['stock_available']['depends_on_stock'],
            'out_of_stock' => $stockAvailableResponse['stock_available']['out_of_stock'],
            'location' => $stockAvailableResponse['stock_available']['location'],
        ];

        $xmlData = $this->dataToXml($data, 'stock_available');
        $parameters = [
            'body' => $xmlData
        ];

        // Update stock available
        $response = $this->client->request('PUT', 'stock_availables', $parameters);

        if ($response->getStatusCode() !== 200) {
            set_log_extra('stock', $data);
            set_log_extra('response', $response->getBody()->getContents());
            throw new \Exception('Unable to update variant stock from Presta Shop.');
        }
        return $response;
    }

    /**
     * Retrieve product options
     *
     * @return array
     */
    public function retrieveProductOptions()
    {
        $response = $this->client->request('get', 'product_options');
        $response = json_decode($response->getBody()->getContents(), true);

        $productOptions = $response['product_options'] ?? [];

        if ($productOptions) {
            foreach ($productOptions as $optionKey => $productOption) {
                // Retrieve product option detail
                $response = $this->client->request('get', 'product_options/'.$productOption['id']);
                $response = json_decode($response->getBody()->getContents(), true);

                if (isset($response['product_option'])) {
                    $productOptions[$optionKey] += [
                        "is_color_group" => $response['product_option']['is_color_group'] ?? '',
                        "group_type" => $response['product_option']['group_type'] ?? '',
                        "position" => $response['product_option']['position'] ?? '',
                        "name" => $response['product_option']['name'] ?? '',
                        "public_name" => $response['product_option']['public_name'] ?? '',
                    ];

                    $productOptions[$optionKey]['values'] = [];
                    // Retrieve product option values
                    if (isset($response['product_option']['associations']['product_option_values']) && count($response['product_option']['associations']['product_option_values'])) {
                        $count = 0;
                        foreach ($response['product_option']['associations']['product_option_values'] as $productOptionValue) {
                            $response = $this->client->request('get', 'product_option_values/'.$productOptionValue['id']);
                            $response = json_decode($response->getBody()->getContents(), true);

                            if (isset($response['product_option_value'])) {
                                $productOptions[$optionKey]['values'][$count] = [
                                    'id' => $response['product_option_value']['id'] ?? '',
                                    "id_attribute_group" => $response['product_option_value']['id_attribute_group'] ?? '',
                                    "color" => $response['product_option_value']['color'] ?? '',
                                    "position" => $response['product_option_value']['position'] ?? '',
                                    "name" => $response['product_option_value']['name'] ?? '',
                                ];
                            }
                            $count++;
                        }
                    }
                }
            }
        }

        return $productOptions;
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
        if (!empty($listing->listing) && !is_null($listing->listing)) {
            $listing = $listing->listing;
        }

        $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $listing);
            throw new \Exception('Presta shop product does not have product external id');
        }

        try {
            $response = $this->client->request('delete', 'products/' . $externalId);

            if ($response->getStatusCode() !== 200) {
                set_log_extra('response', $response->getBody()->getContents());
                set_log_extra('listing', $listing);
                throw new \Exception('Unable to delete product from Presta Shop.');
            }

            return true;
        } catch (\Exception $exception) {
            set_log_extra('listing', $listing);
            throw new \Exception('Presta shop-' . $this->account->id . ' Unable to connect and delete product.');
        }
    }

    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategories()
    {

    }

    /**
     * Import all new categories from PrestaShop
     *
     * @param $importTask
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function importCategories($importTask)
    {
        try {
            $response = $this->client->request('get', 'categories');
            $list = json_decode($response->getBody()->getContents());

            foreach ($list->categories as $key => $value) {
                // index 0 and 1 are root, and home which are not the categories
                if ($key <= 1) continue;
                $category = $this->client->request('get', 'categories/' . $value->id);
                $categories[] = json_decode($category->getBody()->getContents())->category;
            }

            $results = collect($categories);
            $parents = $results->where('id_parent',2);
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

            if (!empty($categories)) {
                if (!empty($importTask) && empty($importTask->total_categories)) {
                    $importTask->total_categories = count($categories);
                    $importTask->save();
                }

                $this->updateAccountCategories($data);
            }
        } catch (\Exception $exception) {
            set_log_extra('exception', $exception);
            set_log_extra('account', $this->account);
            throw new \Exception('PrestaShop unable to connect and retrieve categories.');
        }
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
        $childs = $categories->where('id_parent', $parent->id);

        foreach ($childs as $child) {
            $breadcrumb = $parentBreadcrumb . ' > ' . $child->name;

            $externalId = $child->id;
            $leaf = (count($categories->where('id_parent', $child->id)) > 0) ? 0 : 1;
            $name = $child->name;
            $children = (count($categories->where('id_parent', $child->id)) > 0) ? $this->parseCategories($child, $categories, $breadcrumb) : [];

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
     * Create account category
     *
     * @param $input
     * @return AccountCategory
     * @throws \Exception
     */
    public function createAccCategory($input)
    {
        $url = $this->client->getFullPath() . 'categories';
        $name = $input['name'];
        $slug = str_replace(' ', '-', trim(strtolower($input['name'])));
        # Get parent account category external id
        $parent = AccountCategory::whereId($input['parent_id'])
                    ->whereAccountId($this->account->id)
                    ->whereIsLeaf(false)
                    ->first();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
                    <category>
                        <id_parent xlink:href="'.$url.'">'.$parent->external_id.'</id_parent>
                        <active>1</active>
                        <name><language id="1" xlink:href="'.$url.'">'.$name.'</language></name>
                        <link_rewrite><language id="1" xlink:href="'.$url.'">'.$slug.'</language></link_rewrite>
                    </category>
                    </prestashop>';

        try {
            $parameters = [
                'body' => $xml
            ];
            $response = $this->client->request('post', 'categories', $parameters);
            $response = json_decode($response->getBody()->getContents());

            return $response->category;
        } catch(\Exception $e) {
            set_log_extra('response', $response);
            throw new \Exception('Unable to create category for PrestaShop.');
        }
    }

    /**
     * Update account category
     *
     * @param $categoryid, $input
     * @return AccountCategory
     * @throws \Exception
     */
    public function updateCategory($externalId, $input)
    {
        $url = $this->client->getFullPath() . 'categories/' . $externalId;
        $name = $input['name'];
        $slug = str_replace(' ', '-', trim(strtolower($input['name'])));
        # Get parent account category external id
        $parent = AccountCategory::whereId($input['parent_id'])
            ->whereAccountId($this->account->id)
            ->whereIsLeaf(false)
            ->first();

        // to get the fields which we do not support
        $response = $this->client->request('get', 'categories/' . $externalId);
        $response = json_decode($response->getBody()->getContents());
        $category = $response->category;
        // make sure to include all the fields
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
                    <category>
                        <id>'.$externalId.'</id>
                        <id_parent xlink:href="'.$url.'">'.$parent->external_id.'</id_parent>
                        <active>'.$category->active.'</active>
                        <name><language id="1" xlink:href="'.$url.'">'.$name.'</language></name>
                        <link_rewrite><language id="1" xlink:href="'.$url.'">'.$slug.'</language></link_rewrite>


                        <position>'.$category->position.'</position>
                        <description><language id="1" xlink:href="'.$url.'">'.$category->description.'</language></description>
                        <meta_title><language id="1" xlink:href="'.$url.'">'.$category->meta_title.'</language></meta_title>
                        <meta_description><language id="1" xlink:href="'.$url.'">'.$category->meta_description.'</language></meta_description>
                        <meta_keywords><language id="1" xlink:href="'.$url.'">'.$category->meta_keywords.'</language></meta_keywords>
                    </category>
                    </prestashop>';

        $parameters = [
            'body' => $xml
        ];

        $response = $this->client->request('put', 'categories/' . $externalId, $parameters);
        if ($response->getStatusCode() == 200) {
            return AccountCategory::whereExternalId($externalId)
            ->whereAccountId($this->account->id)
            ->first();
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to update category for PrestaShop.');
        }
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
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveAccountAttributes()
    {
        $attributes = [];

        // Retrieve categories from account_categories table
        $accountCategories = $this->account->categories()->where('is_leaf', true)->get();
        $accountCategories = $accountCategories->map(function ($item) {
            return [
                'id' => $item['external_id'],
                'name' => $item['name']
            ];
        });

        // Append categories data to field attribute
        if (count($accountCategories) > 0) {
            $attribute = new TransformedAttribute(
                Integration::PRESTASHOP,
                'categories',
                'Categories',
                CategoryAttributeType::MULTI_SELECT()->getValue(),
                false,
                CategoryAttributeLevel::GENERAL()->getValue(),
                $accountCategories,
                null
            );
            $attributes[] = collect($attribute->createAndFormatAttribute())->toArray();
        }

        return $attributes;
    }

    /**
     * @param $product
     *
     * @param null $data
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($product, $data = null)
    {
        // Product
        $name = $product->name;

        // Associated SKU will be using reference is because presta shop does not have sku
        $associatedSku = $product->reference ?? null;

        $shortDescription = $product->description_short;
        $htmlDescription = $product->description;

        // This will be use if there is no any variant which means is single product
        $mainStock = $product->stock_availables[0]->quantity ?? $product->quantity;

        $brand = null;
        $model = null;

        // Presta shop does not support integration category
        $integrationCategory = null;

        /** @var AccountCategory $accountCategory */
        $accountCategory = null;
        if (isset($product->associations->categories) && isset($product->associations->categories[0])) {
            $categoryExternalId = $product->associations->categories[0]->id;
            /*
             * In presta shop when select categories must always include default category which is category_id = 2 (Home)
             * So if the first categories is id 2 then we should skip it and get the second one
             * Else all the product we get will have the same category
             **/
            if ($categoryExternalId == '2' && isset($product->associations->categories[1])) {
                $categoryExternalId = $product->associations->categories[1]->id;
            }

            $accountCategory = AccountCategory::where([
                'account_id' => $this->account->id,
                'external_id' => $categoryExternalId,
            ])->first();
        }

        $category = null;
        if (!empty($accountCategory) && !is_null($accountCategory)) {
            $category = $accountCategory->category;
        }

        $weight = $product->weight;
        $length = $product->depth;
        $width = $product->width;
        $height =  $product->height;
        $dimensionUnit = Dimension::CM();
        $weightUnit = Weight::KILOGRAMS();
        // Listing
        $identifiers = [
            ProductIdentifier::EXTERNAL_ID()->getValue() => $product->id,
            ProductIdentifier::SKU()->getValue() => $product->reference,
            ProductIdentifier::STOCK_AVAILABLE_ID()->getValue() => $product->stock_availables[0]->id ?? null
        ];
        $productUrl = null;

        $mainPrices = [];
        $mainPrices[] = new TransformedProductPrice($this->account->currency, $product->price, ProductPriceType::SELLING());
        $mainPrices[] = new TransformedProductPrice($this->account->currency, $product->wholesale_price, ProductPriceType::WHOLESALE());

        $attributes['condition'] = $product->condition;
        $attributes['ean13'] = $product->ean13;
        $attributes['isbn'] = $product->isbn;
        $attributes['upc'] = $product->upc;
        // Store account categories too, because it will be need to show the value in edit page
        /*if (isset($product->categories)) {
            $attributes['categories'] = [];
            foreach ($product->categories as $category) {
                // Skip the default category id - 2 (Home)
                if ($category->id != 2) {
                    $attributes['categories'][] = [
                        'id' => $category->id,
                        'name' => $category->name
                    ];
                }
            }
        }*/

        $options = [];
        // Create options for product
        foreach ($product->options as $key => $value) {
            $options[Str::snake($value->attribute_group->name)] = title_case($value->attribute_group->name);
        }

        $variants = []; // required transformedVariant
        $isOutOfStock = false;
        // Create variants
        foreach ($product->variants as $key => $value) {
            $variantName = null;

            // Variant options value
            $option1 = isset($value->associations->product_option_values[0]) ? $product->options[$value->associations->product_option_values[0]->id]->name : null;
            $option2 = isset($value->associations->product_option_values[1]) ? $product->options[$value->associations->product_option_values[1]->id]->name : null;
            $option3 = isset($value->associations->product_option_values[2]) ? $product->options[$value->associations->product_option_values[2]->id]->name : null;

            $variantSku = $value->reference;

            // Presta shop does not have barcode
            $barcode = null;

            // Variant stock will need to be refer to stock available
            if (isset($product->stock_availables[$value->id])) {
                $stock = $product->stock_availables[$value->id]->quantity;
            } else {
                $stock = $value->quantity;
            }

            $shippingType = ShippingType::MANUAL();

            $variantAttributes = [];
            $variantAttributes['ean13'] = $value->ean13;
            $variantAttributes['isbn'] = $value->isbn;
            $variantAttributes['upc'] = $value->upc;

            if ($product->active == 1) {
                $status = ProductStatus::LIVE();
                $marketplaceStatus = MarketplaceProductStatus::LIVE();
            } else {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::DISABLED();
            }

            // If status is live, then check if variant stock is 0 then set to out of stock
            if (MarketplaceProductStatus::LIVE()->equals($marketplaceStatus) && $stock <= 0) {
                $status = ProductStatus::OUT_OF_STOCK();
                $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
                $isOutOfStock = true;
            }

            $variantIdentifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $value->id,
                ProductIdentifier::SKU()->getValue() => $value->reference,
                ProductIdentifier::STOCK_AVAILABLE_ID()->getValue() => isset($product->stock_availables[$value->id]) ? $product->stock_availables[$value->id]->id : null
            ];

            $prices = [];
            /*
             * NOTE - For variant (combination) price will be same like qoo10
             * Need to add up with main product price only will be the final price for variant
             * Eg: 100 (product price) + 10 (variant price) = 110 (This should be the final price for the variant)
             **/
            $sellingPrice = ($product->price + $value->price);
            $prices[] = new TransformedProductPrice($this->account->currency, $sellingPrice, ProductPriceType::SELLING());
            $prices[] = new TransformedProductPrice($this->account->currency, $value->wholesale_price, ProductPriceType::WHOLESALE());

            // Variant images
            $variantImages = [];
            // Images are stored in main product, just need to get from it
            if (isset($value->associations->images)) {
                foreach ($value->associations->images as $key => $image) {
                    if (isset($product->images[$image->id])) {
                        $variantImages[] = new TransformedProductImage($product->images[$image->id]['url'], $product->images[$image->id]['id']);
                    }
                }
            }

            $variantListing = new TransformedProductListing($variantName, $variantIdentifiers, $integrationCategory,
                $accountCategory, $prices, $productUrl, $stock, $variantAttributes, $value, $variantImages, $marketplaceStatus);

            $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, $variantImages);
        }

        $images = []; // TransformedProductImage
        // Images
        foreach ($product->images as $key => $value) {
            $images[] = new TransformedProductImage($value['url'], $value['id']);
        }

        // Status
        if ($product->active == 1) {
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

        $listing = new TransformedProductListing($name, $identifiers, $integrationCategory, $accountCategory, $mainPrices, $productUrl, null, $attributes, $product, $images, $marketplaceStatus);

        // single product
        if (count($product->variants) == 0) {
            $variantIdentifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $product->id,
                ProductIdentifier::SKU()->getValue() => $product->reference,
                ProductIdentifier::STOCK_AVAILABLE_ID()->getValue() => isset($product->stock_availables[0]) ? $product->stock_availables[0]->id : null
            ];

            $prices[] = new TransformedProductPrice($this->account->currency, $product->price, ProductPriceType::SELLING());
            $prices[] = new TransformedProductPrice($this->account->currency, $product->wholesale_price, ProductPriceType::WHOLESALE());

            $variantAttributes = [];
            $variantAttributes['ean13'] = $product->ean13;
            $variantAttributes['isbn'] = $product->isbn;
            $variantAttributes['upc'] = $product->upc;

            $variantListing = new TransformedProductListing($name, $variantIdentifiers, $integrationCategory,
                $accountCategory, $prices, $productUrl, $mainStock, $variantAttributes, $product, $images, $marketplaceStatus);

            $variants[] = new TransformedProductVariant($name, null, null, null, $associatedSku, null, $mainStock, $mainPrices, $status, ShippingType::MANUAL(), $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, $images);
        }

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
        $stockAvailableId = $product->getIdentifier(ProductIdentifier::STOCK_AVAILABLE_ID());
        if (empty($stockAvailableId)) {
            set_log_extra('listing', $product);
            throw new \Exception('Presta Shop product does not have stock available id');
        }

        // Retrieve the stock available
        $response = $this->client->request('GET', 'stock_availables/'.$stockAvailableId);
        $response = json_decode($response->getBody()->getContents(), true);

        $data = [
            'id' => $stockAvailableId,
            'id_product' => $response['stock_available']['id_product'],
            'id_product_attribute' => $response['stock_available']['id_product_attribute'],
            'id_shop' => $response['stock_available']['id_shop'],
            'quantity' => $stock,
            'depends_on_stock' => $response['stock_available']['depends_on_stock'],
            'out_of_stock' => $response['stock_available']['out_of_stock'],
            'location' => $response['stock_available']['location'],
        ];

        $xmlData = $this->dataToXml($data, 'stock_available');
        $parameters = [
            'body' => $xmlData
        ];

        try {
            // Update stock available
            $response = $this->client->request('PUT', 'stock_availables', $parameters);

            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody()->getContents(), true);

                $this->get(null, false, $response['stock_available']['id_product']);
            }
            return true;
        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $product);
            throw $e;
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
         * If is listing variant then need to get the main product listing id
         * Because presta shop only can set main product active/inactive status
         * Check whether variant is more than 1 or main product external id cannot same with the variant product external id
         **/
        if (!empty($listing->listing) && !is_null($listing->listing)) {
            $listing = $listing->listing;
        }

        $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $listing);
            throw new \Exception('Presta shop product does not have product external id');
        }

        $active = ($enabled) ? 1 : 0;

        $parameters = [
            'active' => $active
        ];

        try {
            // Retrieve the original product
            $response = $this->client->request('GET', 'products/'.$externalId);
            $response = json_decode($response->getBody()->getContents(), true);
            unset($response['product']['associations']);

            $productParams = array_merge($response['product'], $parameters);

            $xmlData = $this->dataToXml($productParams, 'product', true);

            $parameters = [
                'body' => $xmlData
            ];

            // Update product
            $response = $this->client->request('PUT', 'products', $parameters);

            if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201 && $response->getStatusCode() !== 202) {
                set_log_extra('response', $response->getBody()->getContents());
                set_log_extra('product', $productParams);
                throw new \Exception('Unable to update product status from Presta Shop.');
            }

        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $listing);
            throw $e;
        }

        $this->get($listing, true);

        return true;
    }
}
