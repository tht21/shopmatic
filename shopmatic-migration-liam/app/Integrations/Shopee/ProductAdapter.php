<?php

namespace App\Integrations\Shopee;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\Dimension;
use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductAlertType;
use App\Constants\ProductIdentifier;
use App\Constants\ProductPriceType;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use App\Events\NewProductAlert;
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
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use App\Models\ProductInventoryTrail;
use App\Utilities\FileStorageHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Region;

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
    public function get($listing, $update = false, $itemId = null, $isSync = false, $config = null, $importTask = null)
    {
        if (!$isSync) {
            sleep(8);
        }
        $parameters = [
            'item_id_list' => ''
        ];
        if ($itemId) {
            $parameters['item_id_list'] = $itemId;
        } elseif ($listing) {
            $parameters['item_id_list'] = $listing->identifiers['external_id'];
        }

        $response = $this->client->requestv2('get', '/product/get_item_base_info', $parameters);

        if (!isset($response['error']) || $response['error'] == '') {
            $data = $response['response'];
            $product = $data['item_list'][0];

            if (isset($product['has_model']) && $product['has_model'] == true) {
                // Get variation
                $response = $this->client->requestv2('get', '/product/get_model_list', ['item_id' => $product['item_id']]);
                $product['variation_details'] = $response['response'];
            }

            try {
                unset($data['item_list']);
                $product = $this->transformProduct($product, $data, $importTask);
            } catch (\Exception $e) {
                set_log_extra('product', $product);
                if (!is_null($importTask)) {
                    event(new ProductFailedToImport($importTask, (is_array($product) ? $product['item_name'] : $product->associatedSku) . ' failed to import'));
                }
            }
            if (isset($product) && !empty($product)) {
                if (!empty($config)) {
                    $this->handleProduct($product, $config);
                } else {
                    $this->handleProduct($product, ['update' => $update, 'new' => $update]);
                }
            }
        } else {
            Log::info('le to retrieve products for ');
            Log::info($response);
            set_log_extra('parameters', $parameters);
            set_log_extra('response', $response);
            $exceptionMessage = 'Unable to retrieve products for Shopee|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Parameters' . json_encode($parameters);
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * Import all new products from shopee
     *
     * @param $importTask
     * @param $config
     * @return bool
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        $totalProducts = 0;
        $page_size = 50;
        $item_status_list = ['NORMAL', 'BANNED'];
        $item_fetched_status = ['NORMAL' => false, 'BANNED' => false];
        foreach ($item_status_list as $item_status) {
            // to limit sync, use 'update_time_from' in the filter, get the unix timestamp online
            $filters = [
                'offset' => 0,
                'page_size' => $page_size,
                'item_status' => $item_status
            ];
            if (!empty($importTask)) {
                $importTask->total_products = 0;
                $importTask->save();
            }
            $importTimeStart = microtime(true);
            $timestamp = 'taskId-' . $importTask->id . '-timestamp-' . time();
            $processUniqueId = $timestamp;
            $debugLog = '[Shopee Item Import-' . $processUniqueId . ']|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Started' . PHP_EOL;
            Log::info($debugLog);

            $level = 1;
            do {
                $loguid = $timestamp . '-' . $level;
                $debugLog = '[Shopee Item Import-' . $loguid . '-items/get]|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[items/get]|Filter[|' . json_encode($filters) . ']|Started' . PHP_EOL;
                Log::info($debugLog);
                $response = $this->client->requestv2('get', '/product/get_item_list', $filters);

                if (empty($response)) {
                    $exceptionMessage = 'Unable to retrieve products for Shopee in Import,No response|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
                    $debugLog = '[Shopee Item Import-' . $loguid . '-items/get]|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[items/get]|Filter[|' . json_encode($filters) . ']|API Exception|Unable to retrieve products for Shopee in Import,No response' . PHP_EOL;
                    Log::info($debugLog);
                    if ($item_status == 'BANNED' && isset($item_fetched_status['NORMAL']) && $item_fetched_status['NORMAL'] === true) {
                        $more = false;
                        break;
                    }
                    throw new \Exception($exceptionMessage);
                }
                if (!isset($response['response']['item']) || count($response['response']['item']) == 0) {
                    $exceptionMessage = 'No products on shopee';
                    $debugLog = '[Shopee Item Import-' . $loguid . '-get_item_list|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[get_item_list]|Filter[|' . json_encode($filters) . ']|API Exception|Unable to retrieve products for Shopee in Import,No response' . PHP_EOL;
                    Log::info($debugLog);
                    if ($item_status == 'BANNED' && isset($item_fetched_status['NORMAL']) && $item_fetched_status['NORMAL'] === true) {
                        $more = false;
                        break;
                    }
                    throw new \Exception($exceptionMessage);
                }
                if (!empty($response['error'])) {
                    $exceptionMessage = 'Unable to retrieve products for Shopee in Import|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|API Error' . $response['error'];
                    $debugLog = '[Shopee Item Import-' . $loguid . '-items/get]|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[items/get]|Filter[|' . json_encode($filters) . ']|Exception|Unable to retrieve products for Shopee in Import|Error|' . json_encode($response['error']) . PHP_EOL;
                    Log::info($debugLog);
                    throw new \Exception($exceptionMessage);
                }

                if (!empty($importTask) && empty($importTask->total_products)) {
                    $importTask->total_products = $response['response']['total_count'];
                    $importTask->save();
                    $debugLog = '[Shopee Item Import-' . $loguid . '-items/get]|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[items/get]|Filter[|' . json_encode($filters) . ']|Total Product Fetched [' . $response['response']['total_count'] . ']' . PHP_EOL;
                    Log::info($debugLog);
                }
                $innerLevel = 1;
                if (isset($response['response']['item'])) {
                    foreach ($response['response']['item'] as $key => $item) {
                        $innerLevelUid = $loguid . '-Level-' . $innerLevel;
                        $debugLog = '[Shopee Item Import-' . $innerLevelUid . '-items/get-item/get]|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[item/get]|Item Id[' . $item['item_id'] . ']|Begin' . PHP_EOL;
                        Log::info($debugLog);
                        $this->get(null, true, $item['item_id'], true, $config, $importTask);
                        $debugLog = '[Shopee Item Import-' . $innerLevelUid . '-items/get-item/get]|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Api:[item/get]|Item Id[' . $item['item_id'] . ']|End' . PHP_EOL;
                        Log::info($debugLog);
                        $totalProducts++;
                        $innerLevel++;
                    }
                } else {
                    $debugLog = '[Shopee Item Import-have no index item, |response|' . json_encode($response);
                    Log::error($debugLog);
                }
                $debugLog = '[Shopee Item Import-' . $loguid . '-Product Imported- ' . $totalProducts . ']|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . PHP_EOL;
                Log::info($debugLog);
                $more = $response['response']['has_next_page'];
                $filters['offset'] += $page_size;
                $level++;
            } while ($more == true);

            if ($config['delete']) {
                $this->removeDeletedProducts();
            }

            // update total products
            if (!empty($importTask)) {
                $importTask->total_products = $totalProducts;
                $importTask->save();
            }
            $importTimeEnd = microtime(true);
            $importExecutionTime = ($importTimeEnd - $importTimeStart);
            $debugLog = '[Shopee Item Import-' . $processUniqueId . ' Import Completed Successfully | Total Items Imported:' . $totalProducts . ']|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Import Total Execution Time[' . ($importExecutionTime) . ' seconds]' . PHP_EOL;
            Log::info($debugLog);

            if (isset($item_fetched_status[$item_status])) {
                $item_fetched_status[$item_status] = true;
            }
        }
        return true;
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @throws \Throwable
     */
    public function sync()
    {
        $page_size = 100;
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }
        $item_status_list = ["NORMAL", "BANNED"];
        foreach ($item_status_list as $item_status) {

            $filters = [
                'offset' => 0,
                'page_size' => $page_size,
                'item_status' => $item_status
            ];

            do {
                $response = $this->client->requestv2('get', '/product/get_item_list', $filters);

                if (!empty($response['error'])) {
                    set_log_extra('response', $response);
                    $exceptionMessage = 'Unable to retrieve products for Shopee in Sync|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
                    throw new \Exception($exceptionMessage);
                }

                if (isset($response['response']['item'])) {
                    foreach ($response['response']['item'] as $item) {
                        $this->get(null, true, $item['item_id'], true);
                    }
                }

                $more = $response['response']['has_next_page'];
                $filters['offset'] += $page_size;
            } while ($more == true);
        }
    }

    /**
     * Pushes the update for the ProductListing
     *
     * # Update product
     * - first check if basic info such as name, description etc exists in attributes (customize)
     * - else get from $data
     * - dimensions should get from first variant, as we stored them in variants
     * - days_to_ship ( 7 - 30 days ) if product is pre order
     * - if this is single product, then get the stock and price from first variant
     * - update variants name, and sku
     *
     * # Update variants
     * ** Update tier options
     *  - create variations and options
     *  - up to two level of variations
     *  - only first variations can have images
     *  - due to API, its either add images to all options or none, we cant add image to only one of the options
     * ** update variants price
     * ** update variant's options
     * ** add new variant
     *
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
        $isPreOrder = false;
        if (isset($data['attributes']['is_pre_order']['value']) && $data['attributes']['is_pre_order']['value'] == 'Yes') {
            $isPreOrder = true;
        }

        $productParams = [
            'item_id' => $itemId,
            'category_id' => (int)($data['category']['external_id'] ?? $product->integration_category->external_id),
            'item_name' => $data['attributes']['name']['value'] ?? $data['name'],
            'stock' => $data['stock'] ?? $firstVariant['inventory']['stock'],
            'item_sku' => $data['associated_sku'] ?? $product->identifiers['sku'],
            'variations' => [],
            'image' => ['image_id_list' => []],
            'attributes' => [],
            'logistics' => [],
            'weight' => (float) $firstVariant['weight'],
            'dimension' => [
                'package_width' => ceil($firstVariant['width']),
                'package_length' => ceil($firstVariant['length']),
                'package_height' => ceil($firstVariant['height']),
            ],
            'condition' => $data['attributes']['condition']['value'] ?? null,
            'status' => 'NORMAL',
            'is_pre_order' => $isPreOrder,
            // 'wholesales' => [] // @TODO - add the support at later
        ];

        $description = $data['html_description'] ?? ($data['attributes']['short_description']['value'] ?? $data['short_description']);
        $productParams['description_type'] = 'normal';
        $productParams['description'] = $description;
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
        $productParams['price'] = (float) ($prices[ProductPriceType::SELLING()->getValue()] ?? 0);

        $imagesParam = [
            'item_id' => $itemId,
            'images' => []
        ];

        // logistics
        if (isset($data['logistics'])) {
            if (!isset($data['logistics']['value'])) {
                $logistics = $data['logistics'];
                unset($data['logistics']);
                $data['logistics']['value'] = $logistics;
            }
            $data['logistics']['value'] = !is_array($data['logistics']['value']) ? json_decode($data['logistics']['value']) : $data['logistics']['value']; // NOTES - sometimes received in json object, need to check frontend
            foreach ($data['logistics']['value'] as $key => $value) {
                $value = (array) $value;
                $productParams['logistics'][$key] = [
                    'enabled' => (bool) $value['enabled'],
                    'is_free' => (bool) $value['is_free'],
                    'logistic_id' => $value['logistic_id'],
                    //'logistic_name' => $value['logistic_name'],
                ];
    
                if (isset($value['shipping_fee'])) {
                    $productParams['logistics'][$key]['shipping_fee'] = (float) $value['shipping_fee'];
                }
            }
        }

        // Only if product is pre order
        if ($productParams['is_pre_order']) {
            $productParams['days_to_ship'] = (int) $data['attributes']['days_to_ship']['value'] ?? 7;
        }

        // If option is empty means this is single product
        if (!isset($data['option_1']) || empty($data['option_1'])) {
            // Prices (filter duplicate type price)
            $prices = [];
            foreach (Constant::PRICES() as $priceType) {
                foreach ($firstVariant['prices'] as $price) {
                    if ($price['type'] === $priceType->getValue()) {
                        $prices[$priceType->getValue()] = $price['price'];
                        break;
                    }
                }
            }
            $productParams['price'] = (float) $prices[ProductPriceType::SELLING()->getValue()] ?? 0;
            $productParams['stock'] = (int) $firstVariant['inventory']['stock'];
        }

        $variants = collect($data['variants']);

        // Generate variations parameters for each different updates
        if ((count($data['variants']) >= 1 || !empty($data['option_1'])) && !empty($firstVariant['option_1'])) {

            /* 2 tier */
            $tierParam = [
                'item_id' => $itemId,
                'tier_variation' => []
            ];

            $images = [];
            // get options images
            foreach ($variants as $key => $variant) {
                foreach ($variant['images'] as $value) {
                    if (!isset($value['deleted'])) {
                        if (isset($value['data_url'])) {
                            $image = uploadImageFile($value['data_url'], session('shop'));
                        } else {
                            // a default options images
                            $image = $value['image_url'] ?? 'https://thumbs.dreamstime.com/b/vector-blank-transparent-sheet-paper-lower-right-curl-element-ad-other-template-187067450.jpg';
                        }
                        $images[] = $image;
                    }
                }
            }
            //filter remove images item null
            $images = array_filter($images, function ($value) {
                return !empty($value);
            });

            // Get options level by integration
            $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;
            $response_get_model_list = $this->client->requestv2('get', '/product/get_model_list', ['item_id' => $itemId]);
            if (!isset($response_get_model_list['response'])) {
                return $this->respondWithError($response_get_model_list['message']);
            }
            $variation_details = $response_get_model_list['response'];
            $key = 0;
            for ($i = 1; $i <= $optionsLevels; $i++) {
                // Make sure tier variation options is not empty or null
                if ($variants->unique('option_' . $i)->pluck('option_' . $i)->first()) {
                    $options = $variants->unique('option_' . $i)->pluck('option_' . $i)->toArray();
                    $optionList = [];
                    for ($j = 0; $j < count($options); $j++) {
                        if ($options[$j] == null) {
                            // otherwise we will get error: invalid field TierVariation.OptionList.Option: value must Not Null
                            continue;
                        }
                        if (
                            isset($variation_details['tier_variation'][$i - 1]['option_list'][$j]['image']['image_id']) &&
                            !empty($variation_details['tier_variation'][$i - 1]['option_list'][$j]['image']['image_id'])
                        ) {
                            $image_id = $variation_details['tier_variation'][$i - 1]['option_list'][$j]['image']['image_id'] ?? '';
                            $optionList[] =
                                [
                                    'option' => $options[$j],
                                    'image'  => ['image_id' => $image_id]
                                ];
                        } else {
                            $optionList[] =
                                [
                                    'option' => $options[$j]
                                ];
                        }
                    }
                    $tierParam['tier_variation'][$key] = [
                        'name' => $data['option_' . $i] ?? 'option_' . $i,
                        'option_list' => $optionList,
                    ];

                    // Images only in first tier-variation list level
                    if ($i === 1) {
                        $tierParam['tier_variation'][$key]['images_url'] = array_slice($images, 0, $variants->unique('option_' . $i)->pluck('option_' . $i)->count());
                    }
                }
                $key++;
            }

            /* Update tier */
            $response = $this->client->requestv2('post', '/product/update_tier_variation', $tierParam);
            if (!empty($response['error'])) {
                $responseMessage =  $response['message'];
                $variantMismatchError = 'The tier_variation-modification will delete variations, or tier_variation level changed, so it is blocked.';
                if (strcasecmp($responseMessage, $variantMismatchError) == 0) {
                    $responseMessage = "The variants in Shopee doesn't match with our system. Please check and make sure the sku is not repeated, and import the products again before editing.";
                }
                return $this->respondWithError($responseMessage);
            }

            // Add new variation
            $variationsParam = [
                'item_id' => $itemId,
                'variation' => []
            ];
            // Update variation options index
            $editVariationsParam = [
                'item_id' => $itemId,
                'variation' => []
            ];
            // Update variation price
            $pricesUpdateParam = [
                'price_list' => []
            ];

            foreach ($data['variants'] as $key => $variant) {
                /* Add variations param */
                $tier_index = [];
                
                $option_1 = $variant['option_1'];
                $option_2 = $variant['option_2'];
                $option_list_1 = array_pluck($tierParam['tier_variation'][0]['option_list'], 'option');
                $option_list_2 = count($tierParam['tier_variation']) >= 2 ? array_pluck($tierParam['tier_variation'][1]['option_list'], 'option') : [];
                if (!empty($option_1)) {
                    $tier_index[] = array_search($option_1, $option_list_1);
                }
                if (!empty($option_2)) {
                    $tier_index[] = array_search($option_2, $option_list_2);
                }

                if (isset($variant['identifiers'])) {
                    // Update variants name, and sku
                    $productParams['variations'][] = [
                        'variation_id' => $variant['identifiers']['external_id'],
                        //'name' => $variant['name'], // new shopee variations doesnt have custom name
                        'variation_sku' => $variant['sku']
                    ];

                    //  Variant price update parameters
                    $model_id = 0;
                    for ($j = 0; $j < count($variation_details['model']); $j++) {
                        if ($tier_index === $variation_details['model'][$j]['tier_index']) {
                            $model_id = $variation_details['model'][$j]['model_id'];
                            break;
                        }
                    }
                    
                    $pricesUpdateParam['price_list'][] = [
                        'model_id' => $model_id,
                        'original_price' => (float) $variant['prices'][0]['price']
                    ];

                    // Edit variants options index parameters
                    $editVariationsParam['variation'][] = [
                        'tier_index' => $tier_index,
                        'variation_id' => $variant['identifiers']['external_id']
                    ];
                } else {
                    // Add new variants parameters
                    $variationsParam['variation'][] = [
                        'tier_index' => $tier_index,
                        'stock' => (int) $variant['inventory']['stock'],
                        'price' => (float) $variant['prices'][0]['price'], // assume selling is always the first
                        'variation_sku' => $variant['sku']
                    ];
                }
            }
        }

        //update images variants
        if (!empty($data['variants'])) {
            foreach ($variants as $variant) {
                $productVariant = ProductVariant::where('id', $variant['id'])->first();
                $productImage = ProductImage::where('product_variant_id', $variant['id'])->first();
                foreach ($variant['images'] as $key => $value) {
                    if (!isset($value['deleted'])) {
                        if (isset($value['data_url'])) {
                            $image = uploadImageFile($value['data_url'], session('shop'));
                            if (!empty($image)) {
                                if ($productVariant && $productImage) {
                                    $productVariant->update(['main_image' => $image]);
                                    $productImage->update(['source_url' => $image, 'image_url' => $image]);
                                }
                            }
                        }
                    } else {
                        $productVariant->update(['main_image' => null]);
                        $productImage->update(['source_url' => null, 'image_url' => null]);
                    }
                }
            }
        }

        $productParams = array_filter($productParams, function ($value) {
            return (!is_countable($value) && !is_null($value)) || (is_countable($value) && count($value) > 0);
        });

        // Attributes
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $name => $value) {
                // check for external id
                if (isset($value['external_id']) && $value['value']) {
                    $productParams['attributes'][] = [
                        'attributes_id' => (int) $value['external_id'],
                        'value' => $value['value']
                    ];
                }
            }
        }

        // product images, if its new image (data_url) get a temp link
        foreach ($data['images'] as $name => $value) {
            if (!isset($value['deleted'])) {
                if (isset($value['data_url'])) {
                    $imagesParam['images'][] = uploadImageFile($value['data_url'], session('shop'));
                } else {
                    $imagesParam['images'][] = $value['image_url'];
                }
            }
        }
        $imageCount = 1;
        foreach ($imagesParam['images'] as $val) {
            if ($imageCount <= 9) {
                $imageResponse = $this->uploadImage($val);
                $val = $imageResponse['image_id'];
                $productParams['image']['image_id_list'][] = $val;
            }
            $imageCount++;
        }
        $response = $this->client->requestv2('post', '/product/update_item', $productParams);

        if (!empty($response['error'])) {
            if (strpos($response['message'], 'invalid logistic info') !== false && strpos($response['message'], 'weight or size is invalid for the open channel') !== false) {
                $message = $this->generateMessageWeightLimit($response['message']);
                if (!empty($message)) {
                    return $this->respondWithError($message);
                }
            }
            return $this->respondWithError($response['message']);
        }

        // Update the product main image url.
        if (!empty($imagesParam) && isset($imagesParam['images'][0])) {
            $imageUrl = $imagesParam['images'][0];
            if (!empty($imageUrl) && ($imageUrl != $product->product->main_image)) {
                $product->product->update(['main_image' => $imageUrl]);
            }
        }

        // Update price
        if (!isset($data['option_1']) || empty($data['option_1']) || empty($firstVariant['option_1'])) {
            $response_get_model_list = $this->client->requestv2('get', '/product/get_model_list', ['item_id' => $productParams['item_id']]);
            if (
                isset($response_get_model_list['response'])
                && isset($response_get_model_list['response']['model'])
                && isset($response_get_model_list['response']['model'][0])
                && count($response_get_model_list['response']['model']) > 0
                && count($response_get_model_list['response']['model'][0]) > 0
                && isset($response_get_model_list['response']['model'][0]['model_id'])
            ) {
                $model_id = $response_get_model_list['response']['model'][0]['model_id'];
            } else {
                $model_id = 0;
            }

            $priceParams = [
                "item_id" => $productParams['item_id'],
                "price_list" => [
                    [
                        "model_id" => $model_id,
                        "original_price" => $productParams['price']
                    ]
                ]
            ];
            $response = $this->client->requestv2('post', '/product/update_price', $priceParams);
            if (!empty($response['error'])) {
                if ($response['error'] == 'product.error_update_price_fail') {
                    return $this->respondWithError($response['response']['failure_list'][0]['failed_reason']);
                } else {
                return $this->respondWithError($response['message']);
            }
        }
        }

        /* Handle variations */
        $newVariants = [];
        if (count($data['variants']) > 1 || (isset($data['option_1']) && !empty($data['option_1']) && !empty($firstVariant['option_1']))) {
            if (isset($pricesUpdateParam) && count($pricesUpdateParam['price_list']) > 0) {
                $response = $this->client->requestv2('post', '/product/update_price', [
                    "item_id" => $productParams['item_id'],
                    "price_list" => $pricesUpdateParam['price_list']
                ]);
                if (!empty($response['error'])) {
                    return $this->respondWithError($response['message']);
                }
            }
            // if (isset($editVariationsParam) && count($editVariationsParam['variation']) > 0) {
            //     $response = $this->client->requestv2('post', 'item/tier_var/update', $editVariationsParam);
            //     if (!empty($response['error'])) {
            //         return $this->respondWithError($response['message']);
            //     }
            // }
            // if (isset($variationsParam) && count($variationsParam['variation']) > 0) {
            //     $response = $this->client->requestv2('post', 'item/tier_var/add', $variationsParam);
            //     if (!empty($response['error'])) {
            //         return $this->respondWithError($response['message']);
            //     }
            // }
        }

        $this->get($product, true);

        return $this->respond();
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
        $this->rules = [
            'name.value' => 'required|min:10',
            'short_description.value' => 'required|min:50',
        ];

        $this->variant_rules = [
            'weight' => 'required|min:0.1',
            'width' => 'required|min:1',
            'length' => 'required|min:1',
            'height' => 'required|min:1',
        ];
        $this->errors = [];

        parent::canCreate($product);

        $integrationId = Integration::SHOPEE;
        $this->preLoadProductData($product);

        /* Logistic validation */
        $logistic = $product->attributes->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->where('product_variant_id', null)
            ->where('name', 'logistics')
            ->first();

        if (!isset($logistic) || (is_array($logistic->value && empty($logistic->value))) || (!is_array($logistic->value) && count(json_decode($logistic->value)) <= 0)) {
            $this->errors[] = 'Logistic is required, please choose at least one logistic';
        }

        /* 2 tier validation */
        // Get main product attributes
        $attributes = $product->attributes->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->where('product_variant_id', null)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        /** @var ProductImage[] $images */
        $images = $product->allImages->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->take(9);
        if ($images->isEmpty()) {
            $images = $product->allImages()->whereNull('integration_id')->whereNull('product_listing_id')->whereNull('region_id')->take(9)->get();
        }
        if (count($images)) {
            foreach ($images as $key => $image) {
                // Maximum size of an image file is 2MB
                $image_url = $image->image_url;
                if (!$image->image_url && $image->source_url) {
                    $image_url = $image->source_url;
                }
                $imageHeader = array_change_key_case(get_headers($image_url, 1));
                if (!isset($imageHeader["content-length"])) {
                    $this->errors[] = 'The image ' . $image_url . ' file size is unknown, please change another image';
                    break;
                }
                $imageBytes = (is_array($imageHeader["content-length"])) ? $imageHeader["content-length"][1] : $imageHeader["content-length"];
                $imageKilobytes = $imageBytes / 1024;

                if ($imageKilobytes > 2000) {
                    $this->errors[] = 'Please make sure image ' . $image_url . ' does not exceed 2.0 MB';
                    break;
                }
            }
        }

        $productOptions = (isset($attributes['options'])) ? json_decode($attributes['options']->value, true) : $product->options;

        /*
         * If variants is more than one or first variant sku is diff with product sku
         * Then options is required to fill up
         * Shopee create variant is based on product options
         * */
        $associatedSku = $attributes['associated_sku']->value ?? $product->associated_sku;
        if ($product->variants->count() > 1 || ($product->variants->first() && $product->variants->first()->sku != $associatedSku)) {
            if (!$productOptions) {
                $this->errors[] = 'At least one product option is required';
            }
        }

        $i = 1;
        // Get options level by integration
        $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;
        foreach ($productOptions as $key => $option) {
            if ($i <= $optionsLevels) {
                $options = [];
                $images = [];
                foreach ($product->variants as $variant) {
                    // Check product attributes table first
                    /** @var ProductAttribute $optionAttribute */
                    $optionAttribute = $variant->attributes
                        ->where('integration_id', $this->account->integration_id)
                        ->where('region_id', $this->account->region_id)
                        ->where('name', 'option_' . $i)
                        ->first();
                    if ($optionAttribute) {
                        $optionValue = $optionAttribute->value;
                    } else {
                        // If does not exists then get from product variants table
                        $optionValue = $variant->{'option_' . $i};
                    }

                    // Option length need to be under length 20.
                    if (strlen($optionValue) > 20) {
                        $this->errors[] = 'Option value length cannot be more than 20';
                    }

                    // Options of tier_variation should be unique
                    if (!in_array($optionValue, $options)) {
                        $options[] = $optionValue;
                    }

                    /*
                     * Images can only be applied for the first level options.
                     * If one of the variant have image, make sure other variant also need to have one image
                     * Count variations should be equal to count images
                     * Example if there is white, black and purple color then it must have 3 images.
                     **/
                    if ($i === 1) {
                        if ($image = $variant->allImages()->where(['integration_id' => $integrationId, 'region_id' => $this->account->region_id])->whereNotNull('image_url')->first()) {
                            // Maximum size of an image file is 2MB
                            $imageHeader = array_change_key_case(get_headers($image->image_url, 1));
                            if (!isset($imageHeader["content-length"])) {
                                $this->errors[] = 'The variant image ' . $image->image_url . ' file size is unknown, please change another image';
                            }
                            $imageBytes = (is_array($imageHeader["content-length"])) ? $imageHeader["content-length"][1] : $imageHeader["content-length"];
                            $imageKilobytes = $imageBytes / 1024;

                            if ($imageKilobytes > 2000) {
                                $this->errors[] = 'Please make sure variant image ' . $image->image_url . ' does not exceed 2.0 MB';
                            }
                        }
                    }
                }

                /*
                 * If one of the variant have image, make sure other variant also need to have one image
                 * Currently the images must be equal or more than the option
                 * Then we will get the same count of images with first option value count
                 **/
                if ($i === 1 && count($images)) {
                    if (count($images) < count($options)) {
                        $this->errors[] = 'Each variant is required an image';
                    }
                }
            }
            $i++;
        }

        $variantPrices = [];
        $account = $this->account;
        foreach ($product->variants as $variant) {
            // Prices
            $prices = $variant->prices()->where('type', ProductPriceType::SELLING()->getValue())->where(function (Builder $query) use ($account, $product, $variant) {
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

            if (isset($prices[ProductPriceType::SELLING()->getValue()])) {
                $variantPrices[] = $prices[ProductPriceType::SELLING()->getValue()]->price;
            }
        }

        /*
        * Prices validation
        * Variations' price differences cannot be too large. Limitations detail: ID - 10 times; SG/MY - 7 times; TW/PH/VN - 5 times
        * Currently will be taking times 5
        * Get the smallest price among variants and other variant prices cannot be larger than times 5 from the smallest price
        **/
        if (count($variantPrices)) {
            $maximumPrice = (min($variantPrices) * 5);
            foreach ($variantPrices as $variantPrice) {
                if ($variantPrice > $maximumPrice) {
                    $this->errors[] = 'Variant price difference is too large. Cannot be more than times 5 against the smallest price from the variants';
                    break;
                }
            }
        }

        if (count($this->errors) > 0) {
            return $this->respondWithError($this->errors);
        } else {
            return $this->respond(null);
        }
    }
    public function refreshToken($account)
    {
        if (isset($account->credentials['refresh_token'])) {
            $input = [
                'refreshToken' => $account->credentials['refresh_token'],
                'shop_id' => $account->credentials['shop_id'] ??  $account->credentials['access_token'],
            ];
            $body = $this->client->refreshAccessToken($input);
            try{
                $this->client->handleRefreshTokenResponse($account, $body);
            } catch (\Exception $e) {
                Log::info('Shopee refresh token error for account|' . $account->id . '|message|' . $e->getMessage());
                throw $e;
            }
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
        $integrationId = Integration::SHOPEE;
        // pre-load required relation data
        $this->preLoadProductData($product);

        // Get main product attributes
        $attributes = $product->attributes
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->where('product_variant_id', null)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        if (isset($attributes['integration_category_id'])) {
            /** @var IntegrationCategory $integrationCategory */
            $integrationCategory = IntegrationCategory::where([
                'id' => $attributes['integration_category_id']['value'],
                'integration_id' => Integration::SHOPEE
            ])->active()->first();
        } else {
            /** @var IntegrationCategory $integrationCategory */
            $integrationCategory = $product->category->integrationCategories->first();
        }

        $categoryAttributes = $integrationCategory->attributes->toArray();
        $integrationAttributes = collect(array_merge($categoryAttributes, $this->retrieveAttributes()));

        // Get shopee price types
        $priceTypes = [];
        foreach (Constant::PRICES() as $priceType) {
            $priceTypes[] = $priceType->getValue();
        }

        /** @var ProductVariant $firstVariant */
        $account = $this->account;
        $firstVariant = $product->variants()->first();
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

        $firstVariantAttributes = $firstVariant->attributes
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        $weight = $firstVariantAttributes['weight'] ?? null;
        $width = $firstVariantAttributes['width'] ?? null;
        $length = $firstVariantAttributes['length'] ?? null;
        $height = $firstVariantAttributes['height'] ?? null;

        $associatedSku = $attributes['associated_sku']->value ?? $product->associated_sku;
        if (!empty($product->associated_sku)) {
            $associatedSku = $product->associated_sku;
        }
        $productParams = [
            'category_id' => (int) $integrationCategory->external_id,
            'item_name' => (isset($attributes['name'])) ? $attributes['name']->value : $product->name,
            'description' => (isset($attributes['short_description'])) ? $attributes['short_description']->value : $product->short_description, // HTML is not supported, but line breaking will support
            'original_price' => (isset($prices[ProductPriceType::SELLING()->getValue()])) ? (float) $prices[ProductPriceType::SELLING()->getValue()]->price : 0,
            'seller_stock' => [['stock' => $firstVariant->inventory ? (int) $firstVariant->inventory->stock : 0]], // temporary fix
            'item_sku' => $associatedSku,
            'associated_sku' => $associatedSku,
            'variations' => [],
            'image' => ['image_id_list' => []],
            'attribute_list' => [],
            'logistic_info' => [],
            'dimension' => [],
            'condition' => (isset($attributes['condition']) && $attributes['condition']->value != '[]') ? strtoupper($attributes['condition']->value) : '',
            'item_status' => 'NORMAL',
            'pre_order' => ['is_pre_order' => (isset($attributes['is_pre_order']) && $attributes['is_pre_order']->value == 'Yes') ? true : false],
            'wholesale' => []
        ];
        if ($productParams['pre_order']['is_pre_order']) {
            $productParams['pre_order']['days_to_ship'] = isset($attributes['days_to_ship']) ? (int) $attributes['days_to_ship']->value : 7;
        }

        $convertValue = function ($defaultValue) {
            $values = json_decode($defaultValue, true);

            // Check whether is single or multi dimensional array
            $isMultiDimension = is_array(current($values));

            $convertValue = [];
            foreach ($values as $value) {
                if ($isMultiDimension) {
                    $convertValue = array_merge($convertValue, array_values($value));
                } else {
                    array_push($convertValue, $value);
                }
            }
            return implode(',', $convertValue);
        };

        // Integration category attributes
        foreach ($integrationAttributes as $key => $integrationAttribute) {
            /**
             * Ignores are use to skip the product attributes which is not a part of integration category attributes.
             * (Eg: Logistics should not be pass here, because logistic is not under integration category attributes)
             **/
            $ignore = ['name', 'price', 'html_description', 'short_description', 'logistics', 'logistic_enable', 'shipping_fee', 'size_id', 'is_free', 'condition', 'is_pre_order', 'days_to_ship'];
            if (!in_array($integrationAttribute['name'], $ignore)) {
                if (isset($attributes[$integrationAttribute['name']])) {

                    // Run with image attr
                    if ($integrationAttribute['name'] == 'color_thumbnail') {
                        $attrValue = $attributes[$integrationAttribute['name']]['value'];
                        // Check whether is multi select
                        $isJson = is_string($attrValue) && is_array(json_decode($attrValue, true));
                        if ($isJson) {
                            $attrValue = $convertValue($attrValue);
                        }
                        // Upload image to S3 then get link
                        $path = 'shopee/products/variant_attributes/color-thumbnails/';
                        $attributes[$integrationAttribute['name']]['value'] = FileStorageHelper::uploadImageByBase64($attrValue, $path);
                    }

                    // @NOTE - add external_id column or use additional_data
                    if ($integrationAttribute['required'] == '1' || $attributes[$integrationAttribute['name']]['value'] != '') {
                        $productParams['attribute_list'][] = [
                            'attribute_id' => (int) $integrationAttribute['external_id'],
                            'attribute_value_list' => [[
                                'value_id' => $this->getValueIdForAttribute($integrationAttribute['additional_data'], $attributes[$integrationAttribute['name']]['value']),
                                'original_value_name' => $attributes[$integrationAttribute['name']]['value'],
                                'display_value_name' => $attributes[$integrationAttribute['name']]['value'],
                            ]],
                        ];
                    }
                }
            }
        }

        // Images
        /** @var ProductImage[] $images */
        $images = $product->allImages->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->take(9);
        if ($images->isEmpty()) {
            $images = $product->allImages()->whereNull('integration_id')->whereNull('product_listing_id')->whereNull('region_id')->take(9)->get();
        }
        if (count($images)) {
            foreach ($images as $key => $image) {
                if ($image->external_id) {
                    $productParams['image']['image_id_list'][] = $image->external_id;
                } else {
                    $imageResponse = $this->uploadImage($image->source_url, $image->id . 'jpeg');
                    Log::info('$image->id: ' . $image->id);
                    $image->external_id = $imageResponse['image_id'];
                    $productParams['image']['image_id_list'][] = $image->external_id;
                    $image->save();
                }
            }
        }
        $imageCount = 1;
        $variants_array = $product->variants;
        if (count($variants_array) > 0) {
            foreach ($variants_array as $variant_val) {
                $arrayimg = $variant_val->images;
                if ($imageCount <= 9) {
                    foreach ($arrayimg as $val) {
                        if ($imageCount <= 9) {
                            if ($val->external_id) {
                                $productParams['image']['image_id_list'][] = $val->external_id;
                            } else {
                                $imageResponse = $this->uploadImage($val->source_url, $val->id . 'jpeg');
                                $val->external_id = $imageResponse['image_id'];
                                $productParams['image']['image_id_list'][] = $val->external_id;
                                $val->save();
                            }
                            $imageCount++;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
        }

        if (count($productParams['image']['image_id_list']) == 0) {
            return $this->respondWithError('Please upload image');
        }
        // Logistic
        if (isset($attributes['logistics'])) {
            $logistics = $this->retrieveLogistics();
            $count = 0;
            foreach ($logistics as $logisticId => $value) {
                foreach (json_decode($attributes['logistics']->value) as $attributeValue) {
                    // If logistic is in product attribute
                    if (isset($attributeValue->logistic_id) && $attributeValue->logistic_id == $logisticId) {
                        $productParams['logistic_info'][$count] = [
                            'logistic_id' => $logisticId,
                            'enabled' => $attributeValue->enabled,
                            'is_free' => $attributeValue->is_free,
                        ];

                        // Check logistic type
                        if ($value['fee_type'] == 'CUSTOM_PRICE') {
                            $productParams['logistic_info'][$count]['shipping_fee'] = (float) $attributeValue->shipping_fee;
                        }
                        if ($value['fee_type'] == 'SIZE_SELECTION') {
                            $productParams['logistic_info'][$count]['size_id'] = (int) $attributeValue->size_id;
                        }
                        $count++;
                    }
                }
            }
        }
        // Remove any parameters which is empty, else shopee will return error status
        $productParams = array_filter($productParams);

        // Will append dimensions here, because array filter will remove if the dimension is under 0
        $productParams['weight'] = (float) ($weight ?  $weight->value : $product->variants[0]->weight); // get from first variant
        $productParams['dimension']['package_width'] = ceil($width ? $width->value : $product->variants[0]->width); // get from first variant
        $productParams['dimension']['package_length'] = ceil($length ? $length->value : $product->variants[0]->length); // get from first variant
        $productParams['dimension']['package_height'] = ceil($height ? $height->value : $product->variants[0]->height); // get from first variant
        if (!isset($productParams['seller_stock']) || !isset($productParams['seller_stock'][0]['stock'])) {
            $productParams['seller_stock'][0]['stock'] = 0;
        }

        // Call to api and create main item
        $filters = [
            'offset' => 1,
            'page_size' => 100,
            'status' => 1,
            'category_id' =>  (int) $integrationCategory->external_id,
            'language' => 'zh-hans'
        ];
        $brand_list = $this->client->requestv2('get', '/product/get_brand_list', $filters);
        if (!empty($brand_list['error'])) {
            return $this->respondWithError($brand_list['message']);
        }
        $brand_list = $brand_list['response']['brand_list'];
        if (!empty($brand_list)) {
            $productParams['brand'] = [
                'brand_id' => $brand_list[0]['brand_id'],
                'original_brand_name' => $brand_list[0]['original_brand_name'],
            ];
        } else {
            $productParams['brand'] = [
                'brand_id' => 0,
                'original_brand_name' => 'No Brand',
            ];
        }

        if ($productParams['dimension']['package_length'] <= 0) {
            return $this->respondWithError('package_length should bigger than 0.');
        }

        Log::info('$productParams');
        Log::info($productParams);
        $response = $this->client->requestv2('post', '/product/add_item', $productParams);

        Log::info('$productParams response');
        Log::info($response);
        if (!empty($response['error'])) {
            set_log_extra('response', $response);

            if ($response['message'] == 'All images download fail. ') {
                return $this->respondWithError('Please make sure the image you uploaded have meet shopee requirements. Each image max 2.0 MB, format accepted: JPG, JPEG. Suggested dimension: 1024x1024px');
            }

            return $this->respondWithError($response['message']);
        }
        $item_id = $response['response']['item_id'];
        $productOptions = (isset($attributes['options'])) ? json_decode($attributes['options']->value, true) : $product->options;
        if (count($productOptions) > 0) {
            $initTier = [
                'item_id' => $item_id,
                'tier_variation' => [],
                'model' => []
            ];

            $i = 1;
            // Get options level by integration
            $optionsLevels = ($this->account->integration->features[$this->account->region_id]['products']['options_level']) ?? null;
            // sleep 5 seconds to wait create product in shopee system
            sleep(5);
            foreach ($productOptions as $key => $option) {
                // Only support 2 tier_variation
                if ($i <= $optionsLevels) {
                    $options = [];
                    $images = [];
                    foreach ($product->variants as $variant) {
                        // Check product attributes table first
                        /** @var ProductAttribute $optionAttribute */
                        $optionAttribute = $variant->attributes
                            ->where('integration_id', $this->account->integration_id)
                            ->where('region_id', $this->account->region_id)
                            ->where('name', 'option_' . $i)
                            ->first();
                        if ($optionAttribute) {
                            $optionValue = $optionAttribute->value;
                        } else {
                            // If does not exists then get from product variants table
                            $optionValue = $variant->{'option_' . $i};
                        }

                        // Options of tier_variation should be unique
                        if (!in_array($optionValue, $options)) {
                            $options[] = $optionValue;
                        }
                        /*
                         * Get first image from every variant
                         * Each option only can have one image
                         * Count variations should be equal to count images
                         * Example if there is white, black and purple color then it must have 3 images.
                         * Can only be applied for the first level options.
                         **/
                        if ($i === 1) {
                            if ($image = $variant->allImages()->where(['integration_id' => $integrationId, 'region_id' => $this->account->region_id])->whereNotNull('image_url')->first()) {
                                $images[] = $image->image_url;
                            }
                        }
                    }


                    /*
                     * Image can only be applied for the first level options.
                     * If one of the variant have image, make sure other variant also need to have one image
                     * Currently the images must be equal or more than the option
                     * Then we will get the same count of images with first option value count
                     **/
                    if ($i === 1 && count($images)) {
                        if (count($images) >= count($options)) {
                            $tier['images_url'] = array_slice($images, 0, count($options));
                        }
                    }
                    $response_get_model_list = $this->client->requestv2('get', '/product/get_model_list', ['item_id' => $item_id]);
                    $variation_details = $response_get_model_list['response'];
                    $optionList = [];
                    for ($j = 0; $j < count($options); $j++) {
                        if (
                            isset($variation_details['tier_variation'][$i - 1]['option_list'][$j]['image']['image_id']) &&
                            !empty($variation_details['tier_variation'][$i - 1]['option_list'][$j]['image']['image_id'])
                        ) {

                            $image_id = $variation_details['tier_variation'][$i - 1]['option_list'][$j]['image']['image_id'] ?? ''; 
                            if ($options[$j] != null) {
                                $optionList[] = [
                                    'option' => $options[$j],
                                    'image'  => ['image_id' => $image_id]
                                ];
                            }
                        } else {
                            if ($options[$j] != null) {
                                $optionList[] = ['option' => $options[$j]];
                            }
                        }
                    }
                    $tier = [
                        'name' => $option,
                        'option_list' => $optionList,
                    ];
                    $initTier['tier_variation'][] = $tier;
                }
                $i++;
            }

            $firstTier = '';
            foreach ($product->variants as $key => $variant) {
                $tier_index = [];
                if (empty($firstTier)) {
                    $firstTier = $variant['id'];
                }

                $variantAttributes = $variant->attributes
                    ->where('integration_id', $this->account->integration_id)
                    ->where('region_id', $this->account->region_id)
                    ->mapWithKeys(function ($item) {
                        return [$item['name'] => $item];
                    });

                $option_1 = (isset($variantAttributes['option_1'])) ? $variantAttributes['option_1']->value : $variant->option_1;
                $option_2 = (isset($variantAttributes['option_2'])) ? $variantAttributes['option_2']->value : $variant->option_2;
                $option_list_1 = array_pluck($initTier['tier_variation'][0]['option_list'], 'option');
                $option_list_2 = count($initTier['tier_variation']) >= 2 ? array_pluck($initTier['tier_variation'][1]['option_list'], 'option') : [];
                if (!empty($option_1)) {
                    $tier_index[] = array_search($option_1, $option_list_1);
                }
                if (!empty($option_2)) {
                    $tier_index[] = array_search($option_2, $option_list_2);
                }

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
                $normal_stock = (isset($variant->inventory) && $variant->inventory) ? (int) $variant->inventory->stock : 0;
                $model_sku = (isset($variantAttributes['sku'])) ? $variantAttributes['sku']->value : $variant->sku;
                if ($model_sku == $associatedSku) {
                    // we dont add default variant of main product
                    continue;
                }
                if ($tier_index != null) {
                    $initTier['model'][] = [
                        'tier_index' => $tier_index,
                        'seller_stock' => [['stock' => $normal_stock]],
                        'original_price' => (float) $prices[ProductPriceType::SELLING()->getValue()]->price,
                        'model_sku' => $model_sku,
                    ];
                }
            }
            
            if (count($initTier['model']) > 0) {
                $result = $this->client->requestv2('post', '/product/init_tier_variation', $initTier);
                if (!empty($result['error'])) {
                    return $this->respondWithError($result['message']);
                }
            }
        }
        $product = $this->get(null, true, $item_id);

        return $this->respondCreated($product);
    }

    public function uploadImage($image_url, $filename = 'combinesell.jpeg')
    {
        $imageEncode = file_get_contents($image_url);
        $response = $this->client->requestv2('post', '/media_space/upload_image', [
            'multipart' => [
                [
                    'name'     => 'image',
                    'filename' => $filename,
                    'Mime-Type' => 'image/jpeg',
                    'contents' => $imageEncode
                ]
            ]
        ], true);
        if (!isset($response['error']) || empty($response['error'])) {
            return $response['response']['image_info'];
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to upload image to Shopee');
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
            $response = $this->client->requestv2('post', '/product/delete_item', ['item_id' => $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID())]);

            if (!isset($response['error']) || empty($response['error'])) {
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
                throw new \Exception('Unable to delete product from Shopee');
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product item id not found');
    }

    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        $response = $this->client->requestv2('get', '/product/get_category');
        if (isset($response['response']['category_list']) && count($response['response']['category_list'])) {
            $categories = $response['response']['category_list'];
            $results = collect($categories);
            $parents = $results->where('parent_id', 0);
            $data = [];
            foreach ($parents as $key => $parent) {
                $data[$key] = [
                    'name'          => $parent['display_category_name'],
                    'breadcrumb'    => $parent['display_category_name'],
                    'external_id'   => $parent['category_id'],
                    'is_leaf'       => $parent['has_children'] ? 0 : 1,
                    'children'      => $this->parseCategories($parent, $results, $parent['display_category_name'])
                ];
            }
            return $data;
        } else {
            Log::info("Error Response");
            Log::info($response);
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve categories for Shopee');
        }
    }


    /**
     * Recursive function to get all children of the category
     *
     * @param $parent
     * @param Collection $categories
     * @param $parentBreadcrumb
     * @return array
     */
    private function parseCategories($parent, $categories, $parentBreadcrumb)
    {
        $result = [];
        if ($parent['has_children'] == 1) {
            $childrenList = $categories->where('parent_category_id', $parent['category_id']);

            foreach ($childrenList as $child) {
                $breadcrumb = $parentBreadcrumb . ' > ' . $child['display_category_name'];

                $externalId = $child['category_id'];
                $leaf = !($child['has_children']);
                $name = $child['display_category_name'];
                $children = [];

                if (!$leaf) {
                    $children = $this->parseCategories($child, $categories, $breadcrumb);
                } elseif ($categories->where('parent_id', $child['category_id'])->count() === 1) {
                    // get leaf's child id as it's external id
                    $externalId = $categories->where('parent_id', $child['category_id'])->first()['category_id'];
                }
                $result[] = [
                    'name'          => $name,
                    'breadcrumb'    => $breadcrumb,
                    'external_id'   => $externalId,
                    'is_leaf'  => $leaf,
                    'children' => $children,
                ];
                
            }
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
        // These are the basic information attribute which we should ignore as it's already in our Product model
        $ignoreNames = [
            'name',
            'description',
            'short_description',
            'quantity',
            'price',
            'package_weight',
            'package_length',
            'package_width',
            'package_height',
            '__images__',
            'SellerSku'
        ];
        $filters = [
            'category_id' => (int) $category->external_id
        ];
        $response = $this->client->requestv2('get', '/product/get_attributes', $filters);

        // This means that there's no error on the response
        if (isset($response['response']['attribute_list'])) {
            $attributes = $response['response']['attribute_list'];
            foreach ($attributes as $key => $attribute) {

                // These fields should be ignored as they're already in the product model
                if (in_array(strtolower(preg_replace('/\s+/', '_', $attribute['display_attribute_name'])), $ignoreNames)) {
                    unset($attributes[$key]);
                    continue;
                }
                /*
                 * Validating type and level prior to creating
                 */
                $type = CategoryAttributeType::TEXT();
                if ($attribute['input_type'] == 'DROP_DOWN' || $attribute['input_type'] == 'COMBO_BOX') {
                    $type = CategoryAttributeType::OPTION();
                    // let user select brand from options or customize themselves
                    if (strtolower($attribute['display_attribute_name']) === 'brand') {
                        $type = CategoryAttributeType::SINGLE_SELECT_OR_INPUT();
                    }
                } else if ($attribute['input_type'] == 'TEXT_FILED' && ($attribute['input_validation_type'] == 'INT_TYPE' || $attribute['input_validation_type'] == 'FLOAT_TYPE')) {
                    $type = CategoryAttributeType::NUMERIC();
                }

                $level = CategoryAttributeLevel::GENERAL();

                $attributes[$key] = [
                    'external_id'               => $attribute['attribute_id'],
                    'label'                     => $attribute['original_attribute_name'],
                    'name'                      => strtolower($attribute['display_attribute_name']),
                    'required'                  => $attribute['is_mandatory'],
                    'type'                      => $type,
                    'level'                     => $level,
                    'data'                      => array_column($attribute['attribute_value_list'], 'display_value_name') ?? [],
                    'additional_data'           => json_encode($attribute['attribute_value_list'])
                ];
            }

            return $attributes;
        } else {
            set_log_extra('response', $response);
            set_log_extra('primary_category_id', $category->external_id);
            Log::info('Unable to retrieve category attributes for Shopee with category ' . $category->external_id);
        }
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
        $response = $this->client->requestv2('get', '/logistics/get_channel_list');
        $response = $response['response'];

        if (isset($response['logistics_channel_list']) && count($response['logistics_channel_list'])) {

            $logistics = collect($response['logistics_channel_list'])->mapWithKeys(function ($item) {
                return [$item['logistics_channel_id'] => $item];
            })->toArray();

            $logistics = array_filter($logistics, function ($item) {
                return isset($item['enabled']) && isset($item['mask_channel_id']) && $item['enabled'] && $item['mask_channel_id'] == 0;
            });

            if (!is_null($attributes) && !empty($attributes)) {
                // Get set logistics attribute
                $logistic = $attributes->where('name', 'logistics')->first();

                if ($logistic) {
                    foreach (json_decode($logistic->value) as $value) {
                        $shippingFees = null;
                        if (isset($value->shipping_fee)) {
                            $shippingFees = $value->shipping_fee;
                        } else if (isset($value->estimated_shipping_fee)) {
                            $shippingFees = $value->estimated_shipping_fee;
                        }

                        $logistics[$value->logistic_id]['selected'] = true;
                        $logistics[$value->logistic_id]['shipping_fee'] = (!is_null($shippingFees)) ? number_format((float) $shippingFees, 2, '.', '') : '';
                    }
                }
            }
            foreach ($logistics as $value) {
                $logistics[$value['logistics_channel_id']]['logistic_id'] = $value['logistics_channel_id'];
                $logistics[$value['logistics_channel_id']]['logistic_name'] = $value['logistics_channel_name'];
            }
            return $logistics;
        } else {
            Log::info('logistic response');
            Log::info($response);
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve logistics for Shopee');
        }
    }

    /**
     * @param $product
     *
     * @param null $data
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($product, $data = null, $importTask = null)
    {

        // Associated SKU will be the parent SKU
        $associatedSku = ($product['item_sku']) ?? null;
        $description = $product['description'] ?? null;
        if ($description == null) {
            if (
                isset($product['description_info'])
                && isset($product['description_info']['extended_description'])
                && isset($product['description_info']['extended_description']['field_list'])
                && count($product['description_info']['extended_description']['field_list']) > 0
                && isset($product['description_info']['extended_description']['field_list'][0]['text'])
            ) {
                $description = $product['description_info']['extended_description']['field_list'][0]['text'];
            }
        }
        $htmlDescription = $description;
        $name = $product['item_name'] ?? null;

        $options = [];

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = IntegrationCategory::where([
            'integration_id' => $this->account->integration_id,
            'region_id' => $this->account->region_id,
            'external_id' => $product['category_id'],
        ])->active()->first();

        if (empty($integrationCategory)) {

            // This is because somehow the category id doesn't exist on Shopee.
            // This is an actual error caused during importing, so we shouldn't throw an exception if the category doesn't exist
            set_log_extra('primary_category', $product['category_id']);
            set_log_extra('product', $product);
            set_log_extra('account', $this->account->toArray());
            $mainProduct = Product::whereAssociatedSku($associatedSku)->first();
            $message = 'Unable to find integration category with external id "' . $product['category_id'] . '" for product with name "' . $name . '" and associated SKU "' . $associatedSku . '" from Shopee ' . Region::REGIONS[$this->account->region_id] . ' (' . $this->account->name . '). Please inform the system admin about this issue.';
            if (!empty($mainProduct)) {
                event(new NewProductAlert($product, $message, ProductAlertType::WARNING(), $this->account->shop_id, $mainProduct->id));
            }

            if (!empty($importTask)) {
                event(new ProductFailedToImport($importTask, $message));
                return;
            }
        }

        // Shopee doesn't support account category
        $accountCategory = null;

        // Shopee seems to have old products using categories that are no longer valid
        if (!empty($integrationCategory)) {
            $category = $integrationCategory->category;
        } else {
            $category = null;
        }

        $weightUnit = Weight::KILOGRAMS();
        $weight = $product['weight'] ?? 0;

        $shippingType = ShippingType::MARKETPLACE();
        $dimensionUnit = Dimension::CM();
        $length = $product['dimension']['package_length'] ?? 0;
        $width = $product['dimension']['package_width'] ?? 0;
        $height = $product['dimension']['package_height'] ?? 0;
        // Shopee doesn't have a product URL for the listing, only for each SKU
        $productUrl = null;

        // Looping through and creating all the variants
        $variants = [];
        $isOutOfStock = false;
        if (isset($product['has_model']) && $product['has_model'] == true) {
            foreach ($product['variation_details']['model'] as $index => $variation) {
                if (isset($variation) && isset($variation['model_sku'])) {
                    $variantName = $variation['model_sku'];

                    // We pull the first variation as the associated_sku, if it does not exists on the main product
                    if (is_null($associatedSku)) {
                        $associatedSku = trim($variation['model_sku']);
                    }
                    // TAKE NOTE - shopee variant sku might have break line tag
                    $variantSku = trim($variation['model_sku']);

                    // Shopee doesn't support barcodes
                    $barcode = null;
                    $stock = 0;
                    if (
                        isset($variation['stock_info_v2']) && 
                        isset($variation['stock_info_v2']['seller_stock']) && 
                        isset($variation['stock_info_v2']['seller_stock'][0]) && 
                        isset($variation['stock_info_v2']['seller_stock'][0]['stock'])
                        ) {
                        $variation['stock'] = $variation['stock_info_v2']['seller_stock'][0]['stock'];
                        $stock = $variation['stock'];
                    }

                    $prices = [];

                    // Normal price
                    if (isset($variation['price_info'][0])) {
                        $variation['price'] = $variation['price_info'][0]['current_price'];
                        $prices[] = new TransformedProductPrice($this->account->currency, $variation['price'], ProductPriceType::SELLING());
                    }

                    // Remove duplicated for variants attributes
                    $variantAttributes = $variation;
                    unset($variantAttributes['model_id'], $variantAttributes['model_sku'], $variantAttributes['price'], $variantAttributes['stock'], $variantAttributes['status'], $variantAttributes['update_time'], $variantAttributes['discount_id'], $variantAttributes['create_time'], $variantAttributes['name'], $variantAttributes['reserved_stock'], $variantAttributes['original_price']);

                    $identifiers = [
                        ProductIdentifier::ITEM_ID()->getValue() => $product['item_id'],
                        ProductIdentifier::EXTERNAL_ID()->getValue() => $variation['model_id'],
                        ProductIdentifier::SKU()->getValue() => $variantSku,
                    ];

                    $option1 = null;
                    $option2 = null;
                    $option3 = null;

                    $images = [];
                    // If is 2tier item then get the images and option value
                    if (isset($product['variation_details']['tier_variation']) && count($product['variation_details']['tier_variation']) > 1) {
                        $optionData = [];
                        for ($tier = 0; $tier < 2; $tier++) {
                            if (isset($product['variation_details']['tier_variation'][$tier])) {
                                $optionName = $product['variation_details']['tier_variation'][$tier]['name'];
                                if (isset($product['variation_details']['tier_variation'][$tier]['option_list'][$variation['tier_index'][$tier]])) {
                                    $optionValue = $product['variation_details']['tier_variation'][$tier]['option_list'][$variation['tier_index'][$tier]]['option'];
                                } else {
                                    $optionValue = $product['variation_details']['tier_variation'][$tier]['option_list'][0]['option'];
                                }
                                $optionData[$optionName] = $optionValue;

                                // avoid duplicate
                                if (!in_array(title_case(str_replace('_',  ' ', $optionName)), $options)) {
                                    $options[] = title_case(str_replace('_',  ' ', $optionName)); # This is for main product's options
                                }
                            }
                        }
                        # Get variation images
                        for ($tier = 0; $tier < 2; $tier++) {
                            $tier_variation = $product['variation_details']['tier_variation'][$tier]['option_list'];

                            if (isset($product['variation_details']['tier_variation'][$tier]['option_list']) && is_array($product['variation_details']['tier_variation'][$tier]['option_list'])) {
                                foreach ($optionData as $optionName => $optionValue) {
                                    $focus_tier_variation = array_filter($tier_variation, function ($d) use ($optionValue) {
                                        return $d['option'] == $optionValue;
                                    });
                                    foreach ($focus_tier_variation as $focus_tier) {
                                        if (isset($focus_tier['image']['image_url'])) {
                                            $imageUrl = $focus_tier['image']['image_url'] ?? '';
                                            $images[] = new TransformedProductImage($imageUrl, null, null, null, $index);
                                        }
                                    }
                                }
                            }
                        }

                        $count = 1;
                        if (!empty($optionData)) {
                            foreach ($optionData as $optionName => $optionValue) {
                                ${'option' . $count++} = $optionValue;
                            }
                        }
                    }

                    $status = ProductStatus::LIVE();
                    $marketplaceStatus = MarketplaceProductStatus::LIVE();

                    // For variant it only return model_normal/model_deleted, so we need to check main product status whether is unlist.
                    $productStatus = trim(strtolower($product['item_status']));
                    if ($productStatus === 'unlist') {
                        $status = ProductStatus::DISABLED();
                        $marketplaceStatus = MarketplaceProductStatus::DISABLED();
                    }

                    // If status is live, then check if variant stock is 0 then set to out of stock
                    if (MarketplaceProductStatus::LIVE()->equals($marketplaceStatus) && $stock <= 0) {
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
                        $variation,
                        $images,
                        $marketplaceStatus
                    );

                    $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, $images);
                }
            }
        } else {
            $variation = $product;

            // Add a default variant if does not exists (data based on main product)
            $variantName = $product['item_name'];
            $variantSku = $product['item_sku'];

            // Shopee doesn't support barcodes
            $barcode = null;
            if (isset($product['stock_info_v2']) && isset($product['stock_info_v2']['seller_stock']) && isset($product['stock_info_v2']['seller_stock'][0]) && isset($product['stock_info_v2']['seller_stock'][0]['stock'])) {
                $stock = $product['stock_info_v2']['seller_stock'][0]['stock'];
            } else {
                $response_get_model_list = $this->client->requestv2('get', '/product/get_model_list', ['item_id' => $product['item_id']]);
                if (
                    isset($response_get_model_list['response'])
                    && isset($response_get_model_list['response']['model'])
                    && count($response_get_model_list['response']['model']) > 0
                    && count($response_get_model_list['response']['model'][0]) > 0
                    && isset($response_get_model_list['response']['model'][0]['stock_info_v2'])
                    && isset($response_get_model_list['response']['model'][0]['stock_info_v2']['seller_stock'])
                    && isset($response_get_model_list['response']['model'][0]['stock_info_v2']['seller_stock'][0])
                    && isset($response_get_model_list['response']['model'][0]['stock_info_v2']['seller_stock'][0]['stock'])
                ) {
                    $stock = $response_get_model_list['response']['model'][0]['stock_info_v2']['seller_stock'][0]['stock'];
                } else {
                    $stock = 0;
                }
            }
            $prices = [];
            $current_price = isset($product['price_info']) ? $product['price_info'][0]['current_price'] : 0;
            // Normal price
            $prices[] = new TransformedProductPrice($this->account->currency, $current_price, ProductPriceType::SELLING());

            // Variant attribute will be same as main product attribute, no need to duplicate
            $variantAttributes = [];

            $images = [];

            $identifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $product['item_id'],
                ProductIdentifier::SKU()->getValue() => $variantSku,
            ];

            $option1 = null;
            $option2 = null;
            $option3 = null;

            $mpStatus = trim(strtolower($product['item_status']));
            if ($mpStatus === 'normal') {
                $status = ProductStatus::LIVE();
                $marketplaceStatus = MarketplaceProductStatus::LIVE();

                if ($stock <= 0) {
                    $status = ProductStatus::OUT_OF_STOCK();
                    $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
                    $isOutOfStock = true;
                }
            } else if ($mpStatus === 'deleted') {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::BANNED();
            } else if ($mpStatus === 'banned') {
                $status = ProductStatus::BANNED();
                $marketplaceStatus = MarketplaceProductStatus::BANNED();
            } else if ($mpStatus === 'unlist') {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::DISABLED();
            } else {
                set_log_extra('product', $product);
                set_log_extra('mpStatus', $mpStatus);
                throw new \Exception('Unknown product status for Shopee');
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
                $variation,
                $images,
                $marketplaceStatus
            );

            $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, null);
        }

        $identifiers = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['item_id']];

        // Product status
        $productStatus = trim(strtolower($product['item_status']));
        if ($productStatus === 'normal') {
            $status = ProductStatus::LIVE();
            $mainListingMarketplaceStatus = MarketplaceProductStatus::LIVE();

            // If status is live, then check if variant stock is 0 then set to out of stock
            if ($isOutOfStock) {
                $status = ProductStatus::OUT_OF_STOCK();
                $mainListingMarketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
            }
        } else if ($productStatus === 'deleted') {
            $status = ProductStatus::DISABLED();
            $mainListingMarketplaceStatus = MarketplaceProductStatus::DELETED();
        } else if ($productStatus === 'banned') {
            $status = ProductStatus::BANNED();
            $mainListingMarketplaceStatus = MarketplaceProductStatus::BANNED();
        } else if ($productStatus === 'unlist') {
            $status = ProductStatus::DISABLED();
            $mainListingMarketplaceStatus = MarketplaceProductStatus::DISABLED();
        } else {
            set_log_extra('product', $product);
            throw new \Exception('Unknown product status for Shopee');
        }

        // Product images
        $images = null;
        $imageCount = count($product['image']['image_url_list']);

        for ($x = 0; $x < $imageCount; $x++) {
            $images[] = new TransformedProductImage($product['image']['image_url_list'][$x], $product['image']['image_id_list'][$x], null, null, $x);
        }

        // Price for main product
        $mainPrices = null;

        //This is so we don't save duplicated data in our database for main product attribute
        $attributes['logistics'] = '';
        if (isset($product['logistic_info']) && !empty($product['logistic_info'])) {
            $attributes['logistics'] = $product['logistic_info'];
        }
        $attributes['weight'] = "";
        // $attributes['sales'] = $product['sales'];
        if (isset($product['wholesales'])) {
            $attributes['wholesales'] = json_encode($product['wholesales']);
        }
        $attributes['condition'] = $product['condition'];
        $attributes['is_pre_order'] = ($product['pre_order']['is_pre_order'] == false) ? 'No' : 'Yes';
        $attributes['days_to_ship'] = $product['pre_order']['days_to_ship'] ?? '';

        // Loop attributes and insert cause it might got multiple
        if (isset($product['attributes']) && count($product['attributes']) > 0) {
            foreach ($product['attributes'] as $attribute) {
                if (isset($attribute['attribute_name'])) {
                    $attributes[strtolower($attribute['attribute_name'])] = $attribute['attribute_value']; # Make sure name is in lowercase format
                }
            }
        }

        $listing = new TransformedProductListing($name, $identifiers, $integrationCategory, $accountCategory, $mainPrices, $productUrl, null, $attributes, $product, $images, $mainListingMarketplaceStatus);

        $product = new TransformedProduct($name, $associatedSku, $description, $htmlDescription, null, null, $category, $status, $variants, $options, $listing, $images);

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
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null, $task = null)
    {
        $externalId = $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        $itemId = $product->getIdentifier(ProductIdentifier::ITEM_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $product);
            throw new \Exception('Shopee product does not have product external id');
        }
        if (MarketplaceProductStatus::BANNED()->same($product->status)) {
            throw new \Exception('Unable to update stock for Shopee as it is banned.');
        }

        // Shopee does not allow negative
        $stock = (int) max(0, $stock);
        $promotion_stock = $stock;
        // Stock changes will depend on the status of the promotion
        if (!empty($itemId)) {
            $promotion_info = $this->getItemPromotionInfo($itemId, $externalId);
        } else {
            $promotion_info = $this->getItemPromotionInfo($externalId);
        }
        if (!empty($promotion_info)) {
            if (isset($promotion_info['promotion_stock_info_v2']) && isset($promotion_info['promotion_stock_info_v2']['summary_info']) && isset($promotion_info['promotion_stock_info_v2']['summary_info']['total_reserved_stock'])) {

                $variant = ProductVariant::where(['id' => $product->product_variant_id])->first();
                $total_reserved_stock = (int) $promotion_info['promotion_stock_info_v2']['summary_info']['total_reserved_stock'];
                $promotion_staging = $promotion_info['promotion_staging'];
                // Promotion coming soon
                if ($promotion_staging == 'upcoming') {
                    if ($total_reserved_stock > 0 && $stock >= $total_reserved_stock) {
                        $promotion_stock = $stock;
                    }
                    if ($total_reserved_stock > 0 && $stock < $total_reserved_stock) {
                        if (!empty($variant)) {
                            $inventory = $variant->inventory()->first();
                            if (!empty($inventory)) {
                                $messageProductInventoryTrail = "Stock for listing " . $variant->sku . " (" . $product->id . ") cannot be updated to " . $stock . " as it is not greater than or equal to the stock quantity of " . $total_reserved_stock . " that has been reserved for an upcoming promotion.";

                                ProductInventoryTrail::create([
                                    'shop_id' => $this->account->shop_id,
                                    'product_inventory_id' => $inventory->id,
                                    'message' => $messageProductInventoryTrail,
                                    'old' => $product->stock,
                                    'new' => $product->stock,
                                ]);

                                if ($this->checkOnlyShopeePromotion($inventory, $itemId, $externalId)) {
                                    throw new \Exception("Unable to update inventory.");
                                }
                                return;
                            }
                        }
                    }
                }

                if ($promotion_staging == 'ongoing') {
                    if ($total_reserved_stock > 0 && $stock < $total_reserved_stock) {
                        if (!empty($variant)) {
                            $inventory = $variant->inventory()->first();
                            if (!empty($inventory)) {
                                $messageProductInventoryTrail = "Stock for listing " . $variant->sku . " (" . $product->id . ") cannot be updated to " . $stock . " as it is not greater than or equal to the stock quantity of " . $total_reserved_stock . " that has been reserved for an ongoing Shopee promotion.";
                                if (!empty($task)) {
                                    $messageProductInventoryTrail = "Unable to update the stock to " . $stock . " for listing " . $variant->sku . " (" . $product->id . ") as the main stock quantity updated is not greater than or equal to the stock quantity of " . $total_reserved_stock . " that has been reserved for an ongoing Shopee promotion.";
                                }

                                ProductInventoryTrail::create([
                                    'shop_id' => $this->account->shop_id,
                                    'product_inventory_id' => $inventory->id,
                                    'message' => $messageProductInventoryTrail,
                                    'old' => $product->stock,
                                    'new' => $product->stock,
                                ]);

                                if ($this->checkOnlyShopeePromotion($inventory, $itemId, $externalId)) {
                                    throw new \Exception("Unable to update inventory.");
                                }
                                return;
                            }
                        }
                    }
                }
            }
        }

        $endPoint = '/product/update_stock';
        $parameters = [
            'item_id' => $externalId,
            'stock_list' => [
                [
                    'seller_stock' => [
                        [
                            'stock' => (int) $promotion_stock
                        ]
                    ]
                ]
            ],
        ];

        /*
         * If is variant product call to another endpoint and with different parameters
         * NOTE - for on shopee we can only create main product without variant
         * So there will be variant that actually is main product instead of variant
         * Check whether variant is more than 1 or main product external id cannot same with the variant product external id
         * */
        if (!is_null($product->product_variant_id) && ($product->listing_variants()->whereAccountId($this->account->id)->count() > 1 || $product->listing()->whereAccountId($this->account->id)->first()->getIdentifier(ProductIdentifier::EXTERNAL_ID()) != $product->getIdentifier(ProductIdentifier::EXTERNAL_ID()))) {
            $itemId = $product->getIdentifier(ProductIdentifier::ITEM_ID()) ?? $product->listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            if (empty($itemId)) {
                set_log_extra('listing', $product);
                set_log_extra('main_product_listing', $product->listing);
                throw new \Exception('Shopee main product does not have product external id');
            }
            $parameters['item_id'] = $itemId;
        }

        try {
            $response_get_model_list = $this->client->requestv2('get', '/product/get_model_list', ['item_id' => $externalId]);
            
            if (empty($response_get_model_list['error'])) {
                if (
                    isset($response_get_model_list['response'])
                    && isset($response_get_model_list['response']['model'])
                    && count($response_get_model_list['response']['model']) > 0
                    && count($response_get_model_list['response']['model'][0]) > 0
                    && isset($response_get_model_list['response']['model'][0]['model_id'])
                ) {
                    $model_id = $response_get_model_list['response']['model'][0]['model_id'];
                } else {
                    $model_id = 0;
                }
                $parameters['item_id'] = $externalId;
                $parameters['stock_list'][0]['model_id'] = $model_id;
            } else {
                // update stock for product variant
                $parameters['stock_list'][0]['model_id'] = $externalId;
            }

            $response = $this->client->requestv2('post', $endPoint, $parameters);
            if (!empty($response['error'])) {
                if ($response['error'] === 'error_banned') {
                    $product->status = MarketplaceProductStatus::BANNED()->getValue();
                    $product->save();

                    $message = 'Unable to update stock for Shopee as it is banned';
                } elseif ($response['error'] === 'error_desc_length_min_limit') {
                    $message = 'Description must be longer than 50 characters';
                } else {
                    $message = $response['message'] ?? $response['error'];
                }

                set_log_extra('response', $response);
                set_log_extra('listing', $product);
                throw new \Exception('Unable to update stock for Shopee product listing. ' . $message);
            }

            // Wait a few seconds to update stock
            sleep(10);

            // As Shopee doesn't return the updated product, we should refresh it here
            if (!is_null($product->product_variant_id) && ($product->listing_variants()->whereAccountId($this->account->id)->count() > 1 || (!empty($product->listing()->whereAccountId($this->account->id)->first()) && $product->listing()->whereAccountId($this->account->id)->first()->getIdentifier(ProductIdentifier::EXTERNAL_ID()) != $product->getIdentifier(ProductIdentifier::EXTERNAL_ID())))) {
                $this->get($product->listing);
            } else {
                $this->get($product);
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
        $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $listing);
            throw new \Exception('Shopee product does not have product external id');
        }
        if (MarketplaceProductStatus::BANNED()->same($listing->status)) {
            throw new \Exception('Unable to toggle status for Shopee as it is banned.');
            /*event(new NewProductAlert($listing->product, 'Unable to toggle status for Shopee as it is banned. Listing ID: ' . $externalId, ProductAlertType::ERROR()));
            return false;*/
        }
        /*
         * If is listing variant then need to get the main product listing id
         * Because we only can set main product to listed/unlisted item
         * Check whether variant is more than 1 or main product external id cannot same with the variant product external id
         * */
        if (!is_null($listing->product_variant_id) && ($listing->listing_variants()->whereAccountId($this->account->id)->count() > 1 || $listing->listing()->whereAccountId($this->account->id)->first()->getIdentifier(ProductIdentifier::EXTERNAL_ID()) != $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID()))) {
            $externalId = $listing->listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            if (empty($externalId)) {
                set_log_extra('listing', $listing);
                set_log_extra('main_product_listing', $listing->listing);
                throw new \Exception('Shopee main product does not have product external id');
            }
        }

        $unlist = ($enabled) ? false : true;
        $parameters = [
            'item_list' => [
                [
                    'item_id' => $externalId,
                    'unlist' => $unlist
                ]
            ]
        ];
        try {
            $response = $this->client->requestv2('post', '/product/unlist_item', $parameters);
            if (!empty($response['error'])) {
                set_log_extra('response', $response);
                set_log_extra('listing', $listing);
                throw new \Exception('Unable to update status for Shopee product listing.');
            }

            if (isset($response['response']['failure_list'])  && count($response['response']['failure_list']) && isset($response['response']['failure_list'][0]['failed_reason'])) {
                throw new \Exception($response['response']['failure_list'][0]['failed_reason']);
            }

            // As Shopee doesn't return the updated product, we should refresh it here
            /*
             * NOTE - If the product sku is empty, then the product cannot be update on our side please make sure the product have sku.
             * Shopee can allow empty sku
             * */
            if (!is_null($listing->product_variant_id) && ($listing->listing_variants()->whereAccountId($this->account->id)->count() > 1 || $listing->listing()->whereAccountId($this->account->id)->first()->getIdentifier(ProductIdentifier::EXTERNAL_ID()) != $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID()))) {
                $this->get($listing->listing, true);
            } else {
                $this->get($listing, true);
            }
            return true;
        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $listing);
            throw $e;
        }
    }
    private function getValueIdForAttribute($dataAttribute, $name)
    {
        $dataAttribute = collect(json_decode($dataAttribute));
        $attribute = $dataAttribute->where('original_value_name', $name)->first();
        if (!empty($attribute)) {
            return $attribute->value_id;
        }
        return 0;
    }
    public function generateMessageWeightLimit($message)
    {
        $channel_id = (int) filter_var($message, FILTER_SANITIZE_NUMBER_INT);
        $logistics = $this->retrieveLogistics();
        $logistic = collect($logistics)->where('logistics_channel_id', $channel_id)->first();
        if (!empty($logistic) && isset($logistic['weight_limit']['item_max_weight']) && isset($logistic['item_max_dimension']['length']) && isset($logistic['item_max_dimension']['width']) && isset($logistic['item_max_dimension']['height']) && isset($logistic['item_max_dimension']['unit'])) {
            return 'Weight value should be no more than ' . $logistic['weight_limit']['item_max_weight'] . 'kg. Dimension(L x W x H) value should be no more than ' . $logistic['item_max_dimension']['length'] . ' x ' . $logistic['item_max_dimension']['width'] . ' x ' . $logistic['item_max_dimension']['height'] . ' ' . $logistic['item_max_dimension']['unit'];
        }
        return '';
    }

    // Get info promotion for product
    public function getItemPromotionInfo($itemId, $externalId = null)
    {
        try {
            $parameters = [
                'item_id_list' => ''
            ];

            if ($itemId) {
                $parameters['item_id_list'] = $itemId;
            }

            $response = $this->client->requestv2('get', '/product/get_item_promotion', $parameters);

            if (!isset($response['error']) || $response['error'] == '') {
                $item_promotion = null;
                $data = $response['response'];

                if (isset($data['success_list']) && isset($data['success_list'][0]) && isset($data['success_list'][0]['promotion']) && count($data['success_list'][0]['promotion']) > 0) {
                    if (!empty($externalId)) {
                        foreach ($data['success_list'][0]['promotion'] as $promotion) {
                            if ($promotion['model_id'] == $externalId) {
                                $item_promotion = $promotion;
                            }
                        }
                    } else {
                        $item_promotion = $data['success_list'][0]['promotion'][0];
                    }
                }
                return $item_promotion;
            } else {
                Log::info('Cannot get item promotion info: ');
                Log::info($response);
            }
        } catch (\Exception $e) {
            Log::error('Error when get item promotion info: ');
            Log::error($e);
        }
        return null;
    }

    // Check product only have Shopee promotion
    public function checkOnlyShopeePromotion($inventory, $itemId, $externalId)
    {
        $listingsShopee = $inventory->listings()->whereIntegrationId(Integration::SHOPEE)->get();
        $listingsNotShopee = $inventory->listings()->where('integration_id', '!=', Integration::SHOPEE)->get();
        if (count($listingsNotShopee) > 0) {
            return false;
        }
        if (count($listingsShopee) >= 1) {
            $shopee_promotion_info = null;
            foreach ($listingsShopee as $listingShopee) {
                $shopeeExternalId = $listingShopee->getIdentifier(ProductIdentifier::EXTERNAL_ID());
                $shopeeItemId = $listingShopee->getIdentifier(ProductIdentifier::ITEM_ID());
                if (!empty($shopeeItemId) && !empty($shopeeExternalId) && $shopeeItemId != $itemId && $shopeeExternalId != $externalId) {
                    $shopee_promotion_info = $this->getItemPromotionInfo($shopeeItemId, $shopeeExternalId);
                    if (empty($shopee_promotion_info)) {
                        return false;
                    }
                }
                if (!empty($shopeeExternalId) && $shopeeExternalId != $externalId) {
                    $shopee_promotion_info = $this->getItemPromotionInfo($shopeeExternalId);
                    if (empty($shopee_promotion_info)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}