<?php

namespace App\Integrations\Lazada;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\Dimension;
use App\Constants\FulfillmentStatus;
use App\Constants\IntegrationSyncData;
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
use App\Models\ProductVariant;
use App\Models\IntegrationCategoryAttribute;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Utilities\FileStorageHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Region;
use Exception;
use App\Jobs\CleanS3ImagesJob;

class ProductAdapter extends AbstractProductAdapter
{

    /**
     * Retrieves a single product
     *
     * @param ProductListing|null $listing
     * @param bool $update Whether or not to update the product if it already exists
     *
     * @param null $itemId
     *
     * @return mixed
     * @throws \Exception
     */
    public function get($listing, $update = false, $itemId = null)
    {
        $parameters = [];
        if ($itemId) {
            $parameters['item_id'] = (int)$itemId;
        } else {
            // We can't retrieve the product by the variant's id. We need to retrieve via the main product
            if (!empty($listing->listing) && !is_null($listing->listing)) {
                $listing = $listing->listing;
            }
            $parameters['item_id'] = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        }
        $response = $this->client->request('get', '/product/item/get', $parameters);
        if (isset($response['code']) && $response['code'] == 0 && isset($response['data'])) {
            try {
                $product = $response['data'];
                if (empty($product)) {
                    return null;
                }
                $product = $this->transformProduct($product);
            } catch (\Exception $e) {
                set_log_extra('parameters', $parameters);
                set_log_extra('response', $response);
                set_log_extra('product', $product ?? null);
                throw $e;
            }
            return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
        } elseif (isset($response['code']) && $response['code'] == 208) {
            set_log_extra('parameters', $parameters);
            set_log_extra('response', $response);
            $logMessage = 'Unable to retrieve product for Lazada|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Parameters' . json_encode($parameters);
            Log::error($logMessage);
        } else {
            set_log_extra('parameters', $parameters);
            set_log_extra('response', $response);
            $exceptionMessage = 'Unable to retrieve products for Lazada|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Parameters' . json_encode($parameters);
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * Import all new products
     *
     * @param $importTask
     * @param array $config
     * @return mixed
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        // Check if we have all the necessary data to import

        // First check if we have the categories imported so we can map it
        if (empty($this->account->integration->getSyncData($this->account->region_id, IntegrationSyncData::IMPORT_CATEGORIES()))) {
            throw new \Exception('Lazada category not imported yet.');
        }

        $parameters = [
            'filter' => 'all',
            'limit' => 50,
            'offset' => 0,
        ];
        $repeatedItems = [];

        do {
            // If lazada api failed, retry 3 times
            $max = 3;
            for ($current = 1; $current <= $max; $current++) {
                // After first time failed add a delay
                if ($current >= 2) {
                    sleep(1);
                }
                //This is put outside as it the integration fails, we don't want it to rollback
                $response = $this->client->request('get', '/products/get', $parameters);

                if (isset($response['code']) && $response['code'] == 0) {
                    if (!empty($importTask) && empty($importTask->total_products)) {
                        if (isset($response['data']) && isset($response['data']['total_products'])) {
                            $importTask->total_products = $response['data']['total_products'];
                            $importTask->save();
                        } else {
                            $errorMessage = 'Lazada Import Task [' .$importTask->id. ']|Account id|' . $this->account->id .'|response|' .json_encode($response);
                            Log::error($errorMessage);
                            $exceptionMessage ='Lazada API is not providing any data. Please contact the admin to look into the issue.';
                            throw new \Exception($exceptionMessage);
                        }
                    }
                    $response = $response['data'] ?? [];
                    $products = $response['products'] ?? [];
                    foreach ($products as $product) {

                        /*
                         * Lazada api will return repeated items with different skus
                         * If there is any repeated item need to call get function
                         * In order to retrieve the accurate data
                         * */
                        if (isset($repeatedItems[$product['item_id']])) {
                            $repeatedItems[$product['item_id']]++;
                        } else {
                            $repeatedItems[$product['item_id']] = 1;
                        }
                        try {
                            $product = $this->transformProduct($product, $importTask);
                            if (isset($product) && !empty($product)) {
                                $this->handleProduct($product, $config);
                            }
                        } catch (\Exception $e) {
                            set_log_extra('product', $product);
                            \Sentry\captureException($e);

                            if (!is_null($importTask)) {
                                $errorMessage = 'Lazada Import Task [' .$importTask->id. ']|Account id|' . $this->account->id .'|Message|' .$e->getMessage();
                                set_log_extra($errorMessage, $product);
                                Log::error($errorMessage);
                                event(new ProductFailedToImport($importTask, (is_array($product) ? $product['attributes']['name'] : $product->associatedSku) . ' failed to import'));
                            }

                            continue;
                        }
                    }
                    $parameters['offset'] += count($products);
                    break;
                }
            }
        } while (!empty($products));

        // If one of the api call retry more than 3 then throw error
        if ($current >= 4) {
            set_log_extra('response', $response);
            set_log_extra('parameters', $parameters);
            $exceptionMessage ='Unable to retrieve products for Lazada|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id.'|Parameters'.json_encode($parameters);
            throw new \Exception($exceptionMessage);
        }

        foreach ($repeatedItems as $itemId => $repeatedCount) {
            // If repeated count more than 1 then need call again to retrieve.
            if ($repeatedCount >= 2) {
                $this->get(null, $config['update'] ?? false, $itemId);
            }
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }

        return true;
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @return void
     * @throws \Throwable
     */
    public function sync()
    {
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $parameters = [
            'filter' => 'all',
            'limit' => 50,
            'offset' => 0,
        ];

        do {
            // If lazada api failed, retry 3 times
            $max = 3;
            for ($current = 1; $current <= $max; $current++) {
                // After first time failed add a delay
                if ($current >= 2) {
                    sleep(2);
                }
                //This is put outside as it the integration fails, we don't want it to rollback
                $response = $this->client->request('get', '/products/get', $parameters);

                if (isset($response['code']) && $response['code'] == 0) {
                    $response = $response['data'] ?? [];
                    $products = $response['products'] ?? [];
                    foreach ($products as $product) {
                        try {
                            $product = $this->transformProduct($product);
                        } catch (\Exception $e) {
                            set_log_extra('product', $product);
                            throw $e;
                        }
                        $this->handleProduct($product);
                    }
                    $parameters['offset'] += count($products);
                    break;
                }
            }
        } while (count($products) > 0);

        // If one of the api call retry more than 3 then throw error
        if ($current >= 4) {
            set_log_extra('response', $response);
            set_log_extra('parameters', $parameters);
            $exceptionMessage ='Unable to retrieve products for Lazada|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id.'|Parameters'.json_encode($parameters);
            throw new \Exception($exceptionMessage);
        }
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
        $htmlDescription = $data['html_description'];
        $data['html_description'] = $this->processSPUImage($htmlDescription);
        $data['brand'] = $data['brand']['name'] ?? $product->product->brand;
        $data['model'] = $data['model'] ?? $product->product->model;

        $associatedSku = '';
        $uploadedImages = [];
        if (!empty($data['images'])) {
            foreach ($data['images'] as $image) {
                if (empty($image['deleted'])) {
                    if (!empty($image['data_url'])) {
                        $uploadedImages[] = uploadImageFile($image['data_url'], session('shop'));
                    } else if (!empty($image['image_url'])) {
                        $uploadedImages[] = $image['image_url'];
                    }
                }
            }
        }
        // if uploadedImages empty => set main_image
        if (empty($uploadedImages)) {
            $uploadedImages = [$product->product->main_image];
        }

        foreach ($data['variants'] as $variantKey => $variant) {


            // If variant not exist then create
            if (isset($variant['new']) && $variant['new']) {
                $createData = $data;
                $createData['associated_sku'] = $associatedSku;
                $createData['variants'] = [];
                $createData['variants'][$variantKey] = $variant;

                $xmlData = $this->dataToXml($createData);

                $response = $this->client->requestXml('post', '/product/create', $xmlData);

                if ($response['code'] != 0) {
                    $messages = $response['message'];
                    if (isset($response['detail']) && $response['detail']) {
                        Log::info('Lazada update product failed' . json_encode($response['detail']));

                        $messages = [];
                        foreach ($response['detail'] as $detail) {
                            $detailMessage = explode(":", $detail['message']);
                            $message = $detail['message'];
                            if (isset($detailMessage[1])) {
                                if ($detailMessage[1] == 'THD_IC_ERR_F_IC_INFRA_PRODUCT_036') {
                                    $skuError = $this->getSkuErrorFromString($message) ?? '';
                                    $message = 'There is currently a product in Lazada with SKU "' .$skuError. '", so we cannot export another product with the same SKU to Lazada. Please edit the SKU before exporting it again.';
                                }
                                if ($detailMessage[1] == 'BIZ_CHECK_SELLER_SKU_DB_DUPLICATE') {
                                    $message = 'There is duplicate of product sku at Lazada';
                                }
                                if ($detailMessage[1] == 'CHK_SKU_PROPS_DUPLICATE' && isset($detailMessage[2]) && $detailMessage[2] == 'Duplicate attribute .') {
                                    $message = 'The current lazada category only support one variant.';
                                }
                                else if ($detailMessage[0] == 'BIZ_CHECK_EXIST_OUTER_IMAGE') {
                                    $message = 'External URLs are not allowed for main product images. Contact admin to fix this.';
                                }
                                else if($detailMessage[0] == 'BIZ_CHECK_MAIN_IMAGE_REQUIRE') {
                                    $message = 'A main product image is required.';
                                }
                            }
                            $messages[] = $message;
                        }
                        $messages = implode(',', $messages);
                    }
                    return $this->respondWithError($messages);
                }
            }
            // parent sku get from previous variant, on the assumption that at least first variant is exist in Lazada. Else this product has issue
            $associatedSku = $variant['sku'];
            // Upload images to lazada
            if (isset($variant['images'])) {
                foreach ($variant['images'] as $key => $image) {
                    // Unset the deleted image
                    if (isset($image['deleted']) && $image['deleted']) {
                        // Push to Queue for cleanup  from S3 bucket
                        $s3ImageUrl = $data['variants'][$variantKey]['images'][$key]['image_url'] ?? '';
                        if ($s3ImageUrl) {
                            CleanS3ImagesJob::dispatch($s3ImageUrl)->onQueue('s3_cleanup_queue');
                        }
                        unset($data['variants'][$variantKey]['images'][$key]);
                    } elseif (isset($image['data_url'])) {
                        $data['variants'][$variantKey]['images'][$key] = [];
                        $data['variants'][$variantKey]['images'][$key]['image_url'] = uploadImageFile($image['data_url'], session('shop'));
                    }
                }
                $variantImages = Arr::pluck($data['variants'][$variantKey]['images'], 'image_url');
                $imageUrls = $this->uploadImage($variantImages, $product->product);
                $urlImage = [];
                foreach ($imageUrls as $key => $item) {
                    $urlImage[] = $this->migrateImage($item, $product, $data);
                }

                $data['variants'][$variantKey]['images'] = $urlImage;

                if (empty($uploadedImages)) {
                    $uploadedImages = array_shift(array_values($variantImages));
                }
            }
        }

        foreach ($uploadedImages as $index => $item) {
            $uploadedImages[$index] = $this->migrateImage($item, $product, $data);
        }
        $data['images'] = $uploadedImages;
        unset($data['associated_sku']);
        $xmlData = $this->dataToXml($data);

        $response = $this->client->requestXml('post', '/product/update', $xmlData);

        if (!isset($response['code']) || (isset($response['code']) && $response['code'] != 0)) {
            $message = $response['message'];
            if (isset($response['detail']) && count($response['detail']) && isset($response['detail'][0]['message'])) {
                $messages = [];
                foreach ($response['detail'] as $detail) {
                    $detailMessage = explode(":", $detail['message']);
                    $message = $detail['message'];
                    if (isset($detailMessage[1])) {
                        if ($detailMessage[0] == 'BIZ_CHECK_EXIST_OUTER_IMAGE') {
                            $message = 'External URLs are not allowed for main product images. Contact admin to fix this.';
                        } else if ($detailMessage[0] == 'BIZ_CHECK_MAIN_IMAGE_REQUIRE') {
                            $message = 'A main product image is required.';
                        }
                    }
                    $messages[] = $message;
                }
                $messages = implode(',', $messages);
            }
            return $this->respondWithError($message);
        }

        // Update the product main image url.
        if (!empty($uploadedImages)) {
            $image = $uploadedImages[0];
            $imageUrl = $image->image_url ?? $image;
            if (!empty($imageUrl) && ($imageUrl != $product->product->main_image)) {
                $product->product->update(['main_image' => $imageUrl]);
            }
        }

        $this->get($product, true);

        return $this->respond();
    }

    public function getSrcImage($htmlDescription)
    {
        $result = [];
        $doc = new \DOMDocument();
        $doc->loadHTML($htmlDescription);
        $imageTags = $doc->getElementsByTagName('img');
        foreach ($imageTags as $tag) {
            $fileInfo = pathinfo($tag->getAttribute('src'));
            /**
             * Check extension [jpg || png]. 
             */
            if (!isset($fileInfo['extension']) || !in_array($fileInfo['extension'], ['jpg', 'png']))  throw new \Exception('Unable to upload image to Lazada');
            $result[] = $tag->getAttribute('src');
        }
        return $result;
    }
    public function processSPUImage($htmlDescription)
    {
        // get all images
        $images = $this->getSrcImage($htmlDescription);
        // convert image to spu image
        foreach ($images as $image) {
            $spuImage = $this->migrateImage($image, null, null);
            $htmlDescription = str_replace($image, $spuImage, $htmlDescription);
        }
        return $htmlDescription;
    }

    public function migrateImage($image, $product, $data)
    {
        $start_time = microtime(true);
        $response = $this->client->requestXml('post', '/image/migrate', '<Request>
        <Image>
         <Url>' . $image . '</Url>
        </Image>
       </Request>');

        if (isset($response['code'])) {
            if ($response['code'] === 'IllegalAccessToken') {
                set_log_extra('response', $response);
                throw new \Exception('Invalid access token.');
            } else if ($response['code'] === "0") {

                $end_time  = microtime(true);
                $diff_time = $end_time - $start_time;
                $image_URL = $response['data']['image']['url'];
                $log_array = [
                    'diff_time'         => $diff_time,
                    'error_code'        => $response['code'],
                    'associated_sku'    => isset($data['associated_sku']) ? $data['associated_sku'] : "",
                    'image_URL'         => $image_URL,
                    'account_email'     => $this->account->name,
                    'shop'              => !empty($product) && !empty($product->shop) ? $product->shop->email : (!empty($data) && !empty($product->shop) ? $data['shop_id'] : ""),
                ];
                Log::info('log_migrateimage', $log_array);
                
                return $response['data']['image']['url'];
            } else if ($response['code'] === "5") {
                event(new NewProductAlert($product, 'The format of the request URL to migrate image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada is not valid. Please edit it.' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else if ($response['code'] === "6") {
                event(new NewProductAlert($product, 'Unexpected internal error to migrate image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada. Please try again.' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else if ($response['code'] === "30") {
                event(new NewProductAlert($product, 'The request is not complete to migrate image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada. Please edit it.' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else if ($response['code'] === "301") {
                event(new NewProductAlert($product, '1: Failed to migrate image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada. Please try again. 2: Failed to migrate image(s) for product name' . $data['name'] . 'and associated SKU' . $data['associated_sku'] . 'to Lazada. Please try again. Other products have been created/edited successfully. ' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else if ($response['code'] === "302") {
                event(new NewProductAlert($product, 'The image URL to migrate image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada is not supported. Please edit it. ' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } elseif ($response['code'] === "303") {
                event(new NewProductAlert($product, 'The size of the migrated image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada exceeds the 1M limit. Please edit it.' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else if ($response['code'] === "901") {
                event(new NewProductAlert($product, 'Failed to return the requested data for the migrated image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada, due to high calling frequency or disabled functionality. Please try again later.' . $response['detail'][0]['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else if ($response['code'] === "1000") {
                event(new NewProductAlert($product, 'Internal system error for the migrated image(s) for product name ' . $data['name'] . ' and associated SKU ' . $data['associated_sku'] . ' to Lazada. Please try again.' . $response['message'] ?? 'N/A', ProductAlertType::WARNING()));
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to upload image to Lazada');
            }
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to upload image to Lazada');
        }
    }

    private function getStorePickup($rawData)
    {
        try {
            if($rawData == "")
            {
                return 'No';
            }
            if (is_array($rawData)) {
                if(!empty($rawData['value']) && !is_array($rawData['value']) && (strtolower($rawData['value']) == 'yes') || $rawData['value'] == '1'){
                    return 'Yes';     
                }
                if (!empty($rawData['value']) && !empty($rawData['value']['value']) && (strtolower($rawData['value']['value']) == 'yes') || $rawData['value']['value'] == '1'){
                    return 'Yes';
                }
                return 'No';
            } elseif (is_object($rawData)) {
                if (isset($rawData['value']['name'])) {
                    return strcmp($rawData['value']['name'], 'Yes') === 0 ? 'Yes' : 'No';
                } else if ((int)$rawData['value'] === 1 || strcmp($rawData['value'], 'Yes') === 0) {
                    return 'Yes';
                } else {
                    return 'No';
                }
            } else {
                if ($rawData == 1 || strcmp($rawData, 'Yes') === 0) {
                    return 'Yes';
                }
            }
            return 'No';
        } catch (\Exception $e) {
            return 'No';
        }
    }
    /**
     * Convert frontend data to lazada's xml format
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function dataToXml($data)
    {
        try {
            $delivery_option_store_pick_up = isset($data['delivery_option_store_pick_up']) ? $data['delivery_option_store_pick_up'] : '';

            /* Format Data - START */
            $xmlData = [
                'Request' => [
                    'Product' => [
                        //'PrimaryCategory' => $data['integration_category_id'] ?? $data['category']['external_id'],
                        'AssociatedSku' => $data['associated_sku'] ?? '',
                        'PrimaryCategory' => $data['category']['external_id'],
                        'Attributes' => [
                            'name' => isset($data['name']) ? $data['name'] : '',
                            'description' => $data['html_description'],
                            'short_description' => $data['short_description'],
                            'brand' => $data['brand'] == 'NoBrand' ? 'No Brand' : $data['brand'],
                            'model' => $data['model'],
                            'delivery_option_store_pick_up' => $this->getStorePickup($delivery_option_store_pick_up)
                        ],
                        'Skus' => [],
                        'Images' => $data['images'] ?? [],
                    ]
                ]
            ];
            if (isset($data['attributes'])) {
                foreach ($data['attributes'] as $attributeName => $attribute) {
                    // use this to temporary bypass empty brand checking
                    // if ($attributeName === 'brand') $attribute['value']['name'] = 'No Brand';
                    if ($attributeName === 'delivery_option_store_pick_up') {
                        $attribute['value'] = $this->getStorePickup($attribute);
                    }

                    $attributeNameArray = [];
                    if (is_array($attribute['value'])) {
                        // Ex: [Dog, Cat]
                        foreach ($attribute['value'] as $item) {
                            if (is_array($item)) {
                                // Ex: {"name":"Dog"}
                                $attributeNameArray[] = $item['name'];
                            } else {
                                // Ex: Fish
                                $attributeNameArray[] = $item;
                            }
                        }
                        $xmlData['Request']['Product']['Attributes'][$attributeName] = implode(',', $attributeNameArray);
                    } else $xmlData['Request']['Product']['Attributes'][$attributeName] = isset($attribute['value']['name']) ? $attribute['value']['name'] : $attribute['value'];
                }
            }

            foreach ($data['variants'] as $variant) {
                $variantXmlData = [
                    'SellerSku' => $variant['sku'],
                    'quantity' => $variant['inventory']['stock'] ?? 0,
                    'package_length' => $variant['length'],
                    'package_width' => $variant['width'],
                    'package_height' => $variant['height'],
                    'package_weight' => $variant['weight'],
                ];

                foreach ($variant['prices'] as $price) {
                    if ($price['type'] === 'selling') {
                        $variantXmlData['price'] = (float) $price['price'];
                    } else {
                        if ($price['price'] > 0) {
                            $variantXmlData[$price['type'] . '_price'] = (float)$price['price'];
                        }
                    }
                }

                if (isset($variant['attributes'])) {
                    foreach ($variant['attributes'] as $attributeName => $attribute) {
                        if (!empty($attribute['value']['name'] ?? $attribute['value'])) {
                            $variantXmlData[$attributeName] = $attribute['value']['name'] ?? $attribute['value'];
                            if ($attributeName == 'color_thumbnail') {
                                $dataColorThumbnail = saveColorThumbnail($attribute['value']);
                                $variantXmlData[$attributeName] = array_map(function ($item) {
                                    return $item['image_url'];
                                }, $dataColorThumbnail);
                            }
                        }
                    }
                }


                $variantXmlData['Images'] = [];
                if (isset($variant['images'])) {
                    foreach ($variant['images'] as $image) {
                        if (isset($image['data_url'])) {
                            $image = uploadImageFile($image['data_url'], session('shop'));
                        } elseif (isset($image['image_url'])) {
                            $image = $image['image_url'];
                        }
                        $variantXmlData['Images'][] = $image;
                    }
                }

                $xmlData['Request']['Product']['Skus'][] = $variantXmlData;
            }

            // Log::info($xmlData);
            /* Format Data - END */

            /* Generate XML String - START */
            $document = new \DOMDocument();
            $this->arrayToDOMDoc($document, $document, $xmlData);
            $document->preserveWhiteSpace = false;
            $document->formatOutput = true;

            return $document->saveXML();
        } catch (\Exception $exception) {
            set_log_extra('data', $data);
            throw $exception;
        }
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

                $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
                if (empty($name)) {
                    continue;
                }
                $element = $document->createElement($name);
                $currentElement->appendChild($element);
                if ($name === 'Skus') {
                    $this->arrayToDOMDoc($document, $element, $data, 'Sku');
                } elseif ($name === 'Images') {
                    $this->arrayToDOMDoc($document, $element, $data, 'Image');
                } elseif ($name === 'color_thumbnail') {
                    $this->arrayToDOMDoc($document, $element, $data[0]);
                } else {
                    $this->arrayToDOMDoc($document, $element, $data);
                }
            }
        } else {
            $currentElement->appendChild($document->createTextNode(trim($xmlData)));
        }
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
        /**/
        $this->rules = [
            'name.value' => 'required',
            'html_description.value' => 'required',
        ];

        $this->variant_rules = [];

        $this->errors = [];

        parent::canCreate($product);

        $attributes = $product->attributes->where('product_variant_id', null)
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        // check is_sale_prop integration_category_id
        if (isset($attributes['integration_category_id']) && count($product->variants) > 1) {
            $integrationCategoryModel = IntegrationCategory::find($attributes['integration_category_id']['value']);
            if ($integrationCategoryModel && $integrationCategoryModel->isIntegrationCategoryNotHaveAttributeIsSaleProp()) {
                $this->errors[] = 'Category Integration does not allow for products with more than one variant to be created';
            }
        }
        /* Brand validation */
        if (!isset($attributes['brand'])) {
            $this->errors[] = 'Brand is required.';
        } else {
            $isJson = is_string($attributes['brand']->value) && is_array(json_decode($attributes['brand']->value, true)) ? true : false;
            $brand = ($isJson) ? json_decode($attributes['brand']->value, true) : $attributes['brand']->value;

            if (empty($brand)) {
                $this->errors[] = 'Brand is required.';
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
        $integrationId = Integration::LAZADA;
        // pre-load required relation data
        $this->preLoadProductData($product);

        // ignore not real attributes data
        /**
         * Ignores are use to skip the product attributes which is not a part of integration category attributes.
         * (Eg: integration_category_id should not be pass here, because options is not under integration category attributes)
         **/
        $ignore = ['integration_category_id', 'name', 'brand', 'html_description', 'short_description', 'length', 'width', 'height', 'weight'];
        // map attributes data to array with name as key
        $attributes = $product->attributes->where('product_variant_id', null)
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        /* Transform brand */
        $isJson = is_string($attributes['brand']->value) && is_array(json_decode($attributes['brand']->value, true));
        if ($isJson) {
            $values = json_decode($attributes['brand']->value, true);
            $brand = $values['name'];
        } else {
            $brand = $attributes['brand']->value;
        }

        if (isset($attributes['integration_category_id'])) {
            $integrationCategory = IntegrationCategory::where([
                'id' => $attributes['integration_category_id']['value'],
                'integration_id' => $this->account->integration_id,
                'region_id' => $this->account->region_id,
            ])->active()->first();
        } else {
            $integrationCategory = $product->category->integrationCategories
                ->where('region_id', $this->account->region_id)->first();
        }

        // get product Image;
        $productImages =  $product->allImages->pluck('image_url')->toArray();
        if (empty($productImages) && !empty($product->main_image)) {
            $productImages = [$product->main_image];
        }
        foreach ($productImages as $index => $item) {
            $productImages[$index] = $this->migrateImage($item, 'err', $product);
        }

        /* Transform product data */
        $delivery_option_store_pick_up = isset($attributes['delivery_option_store_pick_up']) ? $attributes['delivery_option_store_pick_up'] : '';
        $data = [
            'category' => $integrationCategory->toArray(),
            'name' => $attributes['name']->value ?? $product->name,
            'html_description' => $attributes['html_description']->value ?? $product->html_description,
            'short_description' => $attributes['short_description']->value ??  $product->short_description,
            'brand' => $brand,
            'model' => $attributes['model']->value ?? $product->model,
            'attributes' => [],
            'variants' => [],
            'delivery_option_store_pick_up' => $delivery_option_store_pick_up,
            'images'  => $productImages,
        ];
        $htmlDescription = $data['html_description'];
        $data['html_description'] = $this->processSPUImage($htmlDescription);
        $convertValue = function ($defaultValue) {
            $values = json_decode($defaultValue, true);

            // Check whether is single or multi dimensional array
            $isMultiDimension = is_array(current($values));

            $convertValue = [];
            foreach ($values as $value) {
                if ($isMultiDimension || is_array($value)) {
                    $convertValue = array_merge($convertValue, array_values($value));
                } else {
                    array_push($convertValue, $value);
                }
            }
            return implode(',', $convertValue);
        };

        foreach ($attributes as $attributeName =>  $attribute) {
            if (!in_array($attribute['name'], $ignore)) {
                // Check whether is json string
                $isJson = is_string($attribute['value']) && is_array(json_decode($attribute['value'], true));

                if ($isJson && $attribute['name'] != 'options') {
                    $attribute['value'] = $this->getNameObjecAttribute($attribute['value']);
                }

                $data['attributes'][$attribute['name']] = $attribute;
            }
        }
        foreach ($product->variants as $variant) {
            $variantAttributes = $variant->attributes()
            ->where(function (Builder $query) use ($product) {
                $query->whereProductId($product->id)
                    ->whereRegionId($this->account->region_id)
                    ->whereIntegrationId($this->account->integration_id);
            })
            ->get()->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });
            // Price
            $priceTypes = [];
            foreach (Constant::PRICES() as $priceType) {
                $priceTypes[] = $priceType->getValue();
            }
            $account = $this->account;
            $prices = $variant->prices()->whereIn('type', $priceTypes)
                ->where(function (Builder $query) use ($account, $product, $variant) {
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

            $variantData = [
                'sku' => $variant->sku,
                'length' => $variantAttributes['length']->value ?? $variant->length,
                'width' => $variantAttributes['width']->value ?? $variant->width,
                'height' => $variantAttributes['height']->value ?? $variant->height,
                'weight' => $variantAttributes['weight']->value ?? $variant->weight,
                'inventory' => $variant->inventory,
                'prices' => $prices,
                'images' => [],
                'attributes' => []
            ];
            // If there is only one variant and without variant images, then we should use the main product images
            if (count($product->variants) == 1 && !$variant->allImages->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->count()) {
                $variantImages = $product->allImages->where('integration_id', $integrationId)->where('region_id', $this->account->region_id);
                // if there's no images under this integration, then pick from main
                if ($variantImages->isEmpty()) {
                    $variantImages = $product->allImages()->whereNull('integration_id')->whereNull('product_listing_id')->whereNull('region_id')->get();
                }
            } else {
                $variantImages = $variant->allImages->where('integration_id', $integrationId)->where('region_id', $this->account->region_id);
            }
            // Upload images
            $imageUrls = $this->uploadImage($variantImages, $product);
            $urlImage = [];
            foreach ($imageUrls as $key => $item) {
                $urlImage[] = $this->migrateImage($item, 'err', $product);
            }
            $variantData['images'] = array_unique($urlImage);

            foreach ($variantAttributes as $variantAttributeName => $variantAttribute) {
                if (!in_array($variantAttribute['name'], $ignore)) {
                    // if attribute is color_thumbnail => check data has url => not exist url => upload s3 =>save url;
                    if ($variantAttributeName == 'color_thumbnail') {
                        $colorThumbnails = saveColorThumbnail($variantAttribute['value']);
                        $variantAttribute['value'] = $colorThumbnails;
                        // update images color_thumbnail to lazada;
                        $colorThumbnails =  $this->uploadImage(array_map(function ($item) {
                            return $item['image_url'];
                        }, $colorThumbnails), $product);
                        // insert color_thumbnail into variant Images;
                        $variantData['images'] = array_merge($variantData['images'], $colorThumbnails);
                    }
                    // Check whether is multi select
                    $isJson = is_string($variantAttribute['value']) && is_array(json_decode($variantAttribute['value'], true));

                    if ($isJson) {
                        $variantAttribute['value'] = $this->getNameObjecAttribute($attribute['value']);
                    }

                    $variantData['attributes'][$variantAttribute['name']] = $variantAttribute;
                }
            }

            $data['variants'][] = $variantData;
        }
        unset($data['associated_sku']);
        $xmlData = $this->dataToXml($data);
        $response = $this->client->requestXml('post', '/product/create', $xmlData);

        if ($response['code'] != 0) {
            $messages = $response['message'];
            if (isset($response['detail']) && $response['detail']) {
                Log::info('Lazada create product failed' . json_encode($response['detail']));

                $messages = [];
                foreach ($response['detail'] as $detail) {
                    $detailMessage = explode(":", $detail['message']);
                    $message = $detail['message'];
                    if (isset($detailMessage[1])) {
                        if ($detailMessage[0] == 'THD_IC_ERR_F_IC_INFRA_PRODUCT_036' || $detailMessage[1] == 'THD_IC_ERR_F_IC_INFRA_PRODUCT_036') {
                            $skuError = $this->getSkuErrorFromString($message) ?? '';
                            $message = 'There is currently a product in Lazada with SKU "' .$skuError. '", so we cannot export another product with the same SKU to Lazada. Please edit the SKU before exporting it again.';
                        }
                        if ($detailMessage[1] == 'BIZ_CHECK_SELLER_SKU_DB_DUPLICATE') {
                            $message = 'There is duplicate of product sku at Lazada';
                        }
                        if ($detail['code'] == 'CHK_SKU_PROPS_DUPLICATE' && isset($detailMessage[2]) && $detailMessage[2] == 'Duplicate sales attribute, the repeated attribute is ') {
                            $message = 'The Lazada category "'. $integrationCategory->breadcrumb . '" only supports one variant.';
                        }
                        if ($detailMessage[0] == 'THD_IC_F_IC_INFRA_PRODUCT_036') {
                            $message = 'Sku already exist at Lazada';
                        } else if ($detailMessage[0] == 'BIZ_CHECK_EXIST_OUTER_IMAGE') {
                            $message = 'External URLs are not allowed for main product images. Contact admin to fix this.';
                        } else if ($detailMessage[0] == 'BIZ_CHECK_MAIN_IMAGE_REQUIRE') {
                            $message = 'A main product image is required.';
                        }
                        // If user creates 2 options and Lazada returns us with an error telling us the max sales prop is 1, 
                        // the error message shown in UI should be 'Only 1 option is allowed to be created for the Lazada category chosen'
                        if ($detailMessage[0] == 'BIZ_CHECK_TOTAL_SALE_PROP_MAX_ITEMS' || (isset($detailMessage[1]) && $detailMessage[1] == 'BIZ_CHECK_TOTAL_SALE_PROP_MAX_ITEMS')) {
                            $message = 'Only 1 option is allowed to be created for the Lazada category chosen.';
                        }
                    }
                    $messages[] = $message;
                }
                $messages = implode(',', $messages);
            }
            return $this->respondWithError($messages);
        }
        $product = $this->get(null, true, $response['data']['item_id']);

        return $this->respondCreated($product);
    }

    /**
     * Upload image to lazada
     *
     * @param $images
     * @return array
     * @throws \Exception
     */
    public function uploadImage($images, $product)
    {
        $imageUrls = [];

        foreach ($images as $image) {
            // Retry to upload image to Lazada if code 302, because sometime it works when give another attempt
            $attempt = 3;
            while ($attempt > 0) {
                if (is_object($image)) {
                    if ($image->image_url != null) {
                        $imageUrl = $image->image_url;
                    } else if ($image->source_url != null) {
                        $imageUrl = $image->source_url;
                    } else {
                        $imageUrl = $image;
                    }
                } else {
                    $imageUrl = $image; // in case $image is string url like 'https://dfvoaf3po8a4g.cloudfront.net/local/temp/shops/1103/4XLhgOTM5d.jpeg'
                }

                // Maximum size of an image file is 1MB
                $imageHeader = array_change_key_case(get_headers($imageUrl, 1));
                $imageBytes = 0;
                /**
                 * Check if image content-header is set.
                 */
                if (isset($imageHeader["content-length"])) {
                    $imageBytes = (is_array($imageHeader["content-length"])) ? $imageHeader["content-length"][1] : $imageHeader["content-length"];
                }
                $imageKilobytes = $imageBytes / 1024;
                if ($imageKilobytes > 0 && $imageKilobytes <= 1000) {
                    $imageEncode = file_get_contents($imageUrl);

                    try {
                        $response = $this->client->callWithFile('POST', '/image/upload', $imageEncode);
                        if (isset($response['code'])) {
                            if ($response['code'] === 'IllegalAccessToken') {
                                set_log_extra('response', $response);
                                throw new \Exception('Invalid access token.');
                            } else if ($response['code'] === "0") {
                                $imageUrls[] = $response['data']['image']['url'];
                                $attempt = 0;
                            } elseif (isset($response['code']) && $response['code'] === "302") {
                                // alert if its last attempt
                                if ($attempt <= 1) {
                                    event(new NewProductAlert($product, 'The image URL to migrate image(s) for product name ' . $product['name'] . ' and associated SKU ' . $product['associated_sku'] . ' to Lazada is not supported. Please edit it. ', ProductAlertType::WARNING()));
                                }
                                $attempt--;
                            } else {
                                set_log_extra('response', $response);
                                throw new \Exception('Unable to upload image to Lazada');
                            }
                        } else {
                            set_log_extra('response', $response);
                            throw new \Exception('Unable to upload image to Lazada');
                        }
                    } catch (\Exception $exception) {
                        // alert if its last attempt
                        if ($attempt <= 1) {
                            event(new NewProductAlert($product, 'Unable to create image for Lazada. Message: ' . $exception->getMessage() ?? 'N/A', ProductAlertType::WARNING()));
                        }
                        $attempt--;
                    }
                } else {
                    set_log_extra('image_kb_size', $imageKilobytes);
                    set_log_extra('image_header_info', ['url' => $imageUrl, 'header' => $imageHeader]);
                    throw new \Exception('The size of the migrated image(s) for product name ' . $product['name'] . ' and associated SKU ' . $product['associated_sku'] . ' to Lazada exceeds the 1M limit. Please edit it.');
                }
            }
        }

        return $imageUrls;
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
        $sellerSku = [];
        // If is main product then get all listing variant's sku
        if (is_null($listing->product_variant_id)) {
            foreach ($listing->listing_variants as $listing_variant) {
                array_push($sellerSku, $listing_variant->getIdentifier(ProductIdentifier::SKU()));
            }
        } else {
            // Is listing variant
            array_push($sellerSku, $listing->getIdentifier(ProductIdentifier::SKU()));
        }

        if (!empty($sellerSku)) {
            $response = $this->client->request('post', '/product/remove', ['seller_sku_list' => json_encode($sellerSku)]);

            if (isset($response['code']) && $response['code'] == 0) {
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
                throw new \Exception('Unable to delete product from Lazada');
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product sku not found');
    }

    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        $response = $this->client->request('GET', '/category/tree/get', []);

        //This means that there's no error on the response
        if (isset($response['code']) && $response['code'] == 0) {
            $response = $response['data'];
            foreach ($response as $key => $category) {
                $response[$key] = [
                    'name'          => $category['name'],
                    'breadcrumb'    => $category['name'],
                    'external_id'   => $category['category_id'],
                    'is_leaf'       => $category['leaf'] ? 1 : 0,
                    'children'      => !empty($category['children']) ? $this->parseCategories($category['children'], $category['name']) : []
                ];
            }
            return $response;
        } else {
            set_log_extra('response', $response);
            $exceptionMessage ='Unable to retrieve categories for Lazada|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id;
            throw new \Exception($exceptionMessage);
        }
    }


    /**
     * Recursive function to get all children of the category
     *
     * @param $children
     *
     * @param $parentName
     *
     * @return array
     */
    private function parseCategories($children, $parentName)
    {
        $result = [];
        if (!empty($children)) {
            foreach ($children as $child) {
                $breadcrumb = $parentName . ' > ' . $child['name'];
                $result[] = [
                    'name'  => $child['name'],
                    'breadcrumb'  => $breadcrumb,
                    'external_id' => $child['category_id'],
                    'is_leaf'  => $child['leaf'] ? 1 : 0,
                    'children' => !empty($child['children']) ? $this->parseCategories($child['children'], $breadcrumb) : [],
                ];
            }
        }
        return $result;
    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {

        $attributeTypeMap = [
            'text' => CategoryAttributeType::TEXT(),
            'richText' => CategoryAttributeType::RICH_TEXT(),
            'option' => CategoryAttributeType::OPTION(),
            'singleSelect' => CategoryAttributeType::SINGLE_SELECT(),
            'multiSelect' => CategoryAttributeType::MULTI_SELECT(),
            'numeric' => CategoryAttributeType::NUMERIC(),
            'date' => CategoryAttributeType::DATE(),
            'img' => CategoryAttributeType::IMAGE(),
            'autocomplete' => CategoryAttributeType::AUTOCOMPLETE(),
            'multiEnumInput' => CategoryAttributeType::MULTI_ENUM(),
            'enumInput' => CategoryAttributeType::MULTI_ENUM(),
            'radio' => CategoryAttributeType::RADIO(),
            'multiText' => CategoryAttributeType::MULTI_TEXT(),
        ];
        $levelMap = [
            'normal' => CategoryAttributeLevel::GENERAL(),
            'sku' => CategoryAttributeLevel::SKU(),
        ];

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
            'SellerSku',
            'special_price'
        ];
        $response = $this->client->request('get', '/category/attributes/get', [
            'primary_category_id' => $category->external_id,
        ]);

        //This means that there's no error on the response
        if (isset($response['code']) && $response['code'] == 0) {
            $attributes = $response['data'];
            foreach ($attributes as $key => $attribute) {

                //These fields should be ignored as they're already in the product model
                if (in_array($attribute['name'], $ignoreNames)) {
                    unset($attributes[$key]);
                    continue;
                }
                /*
                 * Validating type and level prior to creating
                 */
                if (array_key_exists($attribute['input_type'], $attributeTypeMap)) {
                    $type = $attributeTypeMap[$attribute['input_type']];

                    // TAKE NOTE for color family only due to lazada new version amendment
                    if ($attribute['name'] == 'color_family') {
                        $type = (isset($attribute['options']) && !empty($attribute['options'])) ? $attributeTypeMap['singleSelect'] : $attributeTypeMap['text'];
                    }
                } else {
                    set_log_extra('attribute', $attribute);
                    set_log_extra('primary_category_id', $category->external_id);
                    throw new \Exception('Unable to map category attributes type for Lazada');
                }
                if (array_key_exists($attribute['attribute_type'], $levelMap)) {
                    $level = $levelMap[$attribute['attribute_type']];
                } else {
                    set_log_extra('attribute', $attribute);
                    set_log_extra('primary_category_id', $category->external_id);
                    throw new \Exception('Unable to map category attributes level for Lazada');
                }

                $required = 0;
                if (!empty($attribute['is_mandatory']) || $attribute['is_sale_prop'] == 1) {
                    $required = 1;
                }

                $attributes[$key] = [
                    'label'                     => $attribute['label'],
                    'name'                      => $attribute['name'],
                    'required'                  => $required,
                    'type'                      => $type,
                    'level'                     => $level,
                    'data'                      => $attribute['options'] ?? [],
                    'additional_data'           => ['is_sale_prop' => $attribute['is_sale_prop']],
                    'is_sale_prop'              => isset($attribute['is_sale_prop']) && $attribute['is_sale_prop'] == 1 ? 1 : 0,
                ];
            }

            return $attributes;
        } else {
            set_log_extra('response', $response);
            set_log_extra('primary_category_id', $category->external_id);
            throw new \Exception('Unable to retrieve category attributes for Lazada');
        }
    }

    /**
     * Retrieves all the brands for the integration
     *
     * @return array
     * @throws \Exception
     */
    public function retrieveBrands()
    {
        $offset = 0;
        $limit = 1000;
        //$limit = 100;
        $getBrands = [];
        $brands = [];

        do {
            try {
                $response = $this->client->request('get', '/brands/get', ['offset' => $offset, 'limit' => $limit]);
            } catch (\Exception $e) {
                set_log_extra('response', $e);
                throw new \Exception('Lazada-' . $this->account->id . ' Unable to connect and retrieve brands.');
            }
            $getBrands = array_merge($getBrands, $response['data']);

            $offset += $limit;
        } while (count($response['data']) > 0);

        foreach ($getBrands as $key => $getBrand) {
            $brands[$key] = [
                'name'          => $getBrand['name_en'] ?? $getBrand['name'],
                'external_id'   => $getBrand['brand_id'],
                //'global_identifier'    => $category['global_identifier'],
            ];
        }
        return $brands;
    }

    /**
     * @param $product
     *
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($product, $importTask = null)
    {
        //Associated SKU can only be set later from the first variant as Lazada does not have a "parent" SKU
        $associatedSku = null;
        //Status can only be retrieved from the first variant
        $status = null;

        $mainProductIdentifier = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['item_id']];
        $shortDescription = $product['attributes']['short_description'] ?? null;
        $htmlDescription = $product['attributes']['description'] ?? null;
        $name = $product['attributes']['name'] ?? null;
        $brand = $product['attributes']['brand'] ?? null;
        $model = $product['attributes']['model'] ?? null;


        //This is so we don't save duplicated data in our database
        $attributes = $product['attributes'];
        unset($attributes['description'], $attributes['short_description'], $attributes['name']);

        $options = [];

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = IntegrationCategory::where([
            'integration_id' => $this->account->integration_id,
            'region_id' => $this->account->region_id,
            'external_id' => $product['primary_category'],
        ])->active()->first();

        if (empty($integrationCategory)) {
            set_log_extra('primary_category', $product['primary_category']);
            set_log_extra('product', $product);
            set_log_extra('account', $this->account->toArray());
            $mainProduct = null;
            if (isset($product['skus']) && isset($product['skus'][0])) {
                $associatedSku = $product['skus'][0]['SellerSku'];
                $mainProduct = Product::whereAssociatedSku($associatedSku)->first();
            }
            $message = 'Unable to find integration category with external id "' . $product['primary_category'] . '" for product with name "' . $name . '" and associated SKU "' . $associatedSku . '" from Lazada ' . Region::REGIONS[$this->account->region_id] . ' (' . $this->account->name . '). Please inform the system admin about this issue.';
            if (!empty($mainProduct)) {
                event(new NewProductAlert($product, $message, ProductAlertType::WARNING(), $this->account->shop_id, $mainProduct->id));
            }
            if (!empty($importTask)) {
                event(new ProductFailedToImport($importTask, $message));
                return;
            }
        }


        //Lazada doesn't support account category
        $accountCategory = null;

        //Lazada seems to have old products using categories that are no longer valid
        if (!empty($integrationCategory)) {
            $category = $integrationCategory->category;
        } else {
            $category = null;
        }

        //Looping through and creating all the variants
        $variants = [];
        $productImages = [];
        if (!empty($product['images'])) {
            foreach ($product['images'] as $index => $image) {
                $productImages[] =  new TransformedProductImage($image, null, null, null, $index);
            }
        }

        foreach ($product['skus'] as $sku) {

            // Lazada does not support names for the SKU, so we should implode from the option values, or use the default name
            $variantName = '';

            // We pull the first variation as the associated_sku
            if (empty($associatedSku)) {
                $associatedSku = $sku['SellerSku'];
            }
            $variantSku = $sku['SellerSku'];
            // Get variantName from CSs
            $variant = ProductVariant::where(['sku' => $variantSku])->first();
            if (isset($variant) && isset($variant['name'])) {
                $variantName = $variant->name;
            } else {
                $variantName = (isset($sku['saleProp']) && count($sku['saleProp'])>0) ? implode(",", array_values($sku['saleProp'])) : $product['attributes']['name'];
            }
            //Lazada doesn't support barcodes
            $barcode = null;
            $stock = $sku['quantity'];
            $prices = [];

            //Normal price
            $prices[] = new TransformedProductPrice($this->account->currency, $sku['price'], ProductPriceType::SELLING());

            //Remove duplicated attributes
            $variantAttributes = $sku;
            unset($variantAttributes['SkuId'], $variantAttributes['SellerSku'], $variantAttributes['price'], $variantAttributes['quantity'], $variantAttributes['Status'], $variantAttributes['Images'], $variantAttributes['multiWarehouseInventories'], $variantAttributes['special_price'], $variantAttributes['Available']);

            //This is to check if the special price is valid or not.
            //If it is valid, add it to the price, otherwise just skip it.
            //TODO: Review this logic
            if (!empty($sku['special_price'])) {
                if (!empty($sku['special_from_time'])) {
                    $from = Carbon::createFromFormat('Y-m-d H:i', $sku['special_from_time']);
                    if (!empty($sku['special_to_time'])) {
                        $to = Carbon::createFromFormat('Y-m-d H:i', $sku['special_to_time']);
                        if (now()->lte($to)) {
                            $prices[] = new TransformedProductPrice($this->account->currency, $sku['special_price'], ProductPriceType::SPECIAL());
                        }
                    } elseif (now()->gte($from)) {
                        $prices[] = new TransformedProductPrice($this->account->currency, $sku['special_price'], ProductPriceType::SPECIAL());
                    }
                }
            }


            //Don't create it at the global level as it might create duplicates / mess things up.
            //We create it at the listing level
            $images = [];

            foreach ($sku['Images'] as $index => $image) {
                if (!empty($image)) {
                    $images[] = new TransformedProductImage($image, null, null, null, $index);
                }
                // if productImages => Take first sku images as main product images
                if (empty($productImages)) {
                    $productImages[] = new TransformedProductImage($image, null, null, null, $index);
                }
            }

            $weightUnit = Weight::KILOGRAMS();
            $weight = $sku['package_weight'] ?? 0;

            $shippingType = ShippingType::MARKETPLACE();
            $dimensionUnit = Dimension::CM();
            $length = $sku['package_length'] ?? 0;
            $width = $sku['package_width'] ?? 0;
            $height = $sku['package_height'] ?? 0;
            $productUrl = $sku['Url'];


            $identifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $sku['SkuId'],
                ProductIdentifier::SKU()->getValue() => $variantSku,
                ProductIdentifier::SHOP_SKU()->getValue() => $sku['ShopSku']
            ];

            $option1 = null;
            $option2 = null;
            $option3 = null;

            if (!empty($integrationCategory)) {
                //Find all the variation attributes and update the options here
                $variationOptionAttributes = $integrationCategory->attributes()->where([
                    'level'    => CategoryAttributeLevel::SKU(),
                    //'type'     => CategoryAttributeType::MULTI_ENUM(),
                    'required' => 1
                ])->take(3)->get(); // Currently only support maximum of 3 options

                $count = 1;

                foreach ($variationOptionAttributes as $attribute) {
                    if (!empty($sku[$attribute->name])) {
                        if (!in_array($attribute->label, $options)) {
                            $options[] = $attribute->label;
                        }
                        ${'option' . $count++} = $sku[$attribute->name];

                        // Option need to be store in product attribute, because it will need to be show in create/edit page attribute part
                        $variantAttributes[$attribute->name] = $sku[$attribute->name];
                    }
                }

                // More than 4 because 3 options will turn it into 4. So we check for > 4
                if ($count > 4) {
                    set_log_extra('product', $product);
                    set_log_extra('category_attributes', $variationOptionAttributes->toArray());
                    throw new \Exception('There is more than 3 variation option attributes!');
                }
            }

            $mpStatus = trim(strtolower($sku['Status']));

            if ($mpStatus === 'active') {
                $status = ProductStatus::LIVE();
                $marketplaceStatus = MarketplaceProductStatus::LIVE();
            } elseif ($mpStatus === 'inactive') {
                $status = ProductStatus::DISABLED();
                $marketplaceStatus = MarketplaceProductStatus::DISABLED();
            } elseif ($mpStatus === 'deleted') {
                $marketplaceStatus = MarketplaceProductStatus::DELETED();
                $status = ProductStatus::DISABLED();
            } else {
                set_log_extra('status', $mpStatus);
                throw new \Exception('Invalid Lazada product status.');
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
                $sku,
                $images,
                $marketplaceStatus
            );

            $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, $images);
        }

        //Lazada doesn't have a product URL for the listing, only for each SKU
        $productUrl = null;

        //No prices for main product
        $prices = null;

        // Setting the status for the main product to live because not sure what else to set here, unless we calculate
        // based on the statuses above to see if there's any that's live, or we use the last value
        $listing = new TransformedProductListing($name, $mainProductIdentifier, $integrationCategory, $accountCategory, $prices, $productUrl, null, $attributes, $product, $productImages, MarketplaceProductStatus::LIVE());

        $product = new TransformedProduct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $category, $status, $variants, $options, $listing, $productImages);


        return $product;
    }

    public function shouldUpdateProduct($sku)
    {
        $status = $this->getProductStatus($sku);
        $status_lower = strtolower($status);
        if ($status_lower == 'active' || $status_lower == 'inactive') {
            return '';
        }
        $message = 'Product ' . $sku . ' is has status ' . $status_lower . ' then it will not updated.';
        return $message;
    }

    public function getProductStatus($sku)
    {
        $parameters = [];
        $parameters['seller_sku'] = $sku;
        $response = $this->client->request('get', '/product/item/get', $parameters);

        if (isset($response['code']) && $response['code'] == 0 && isset($response['data'])) {
            try {
                $product = $response['data'];
                if (empty($product)) {
                    return null;
                }
                return $product["status"];
            } catch (\Exception $e) {
                throw $e;
            }
        } elseif (isset($response['code']) && $response['code'] == 208) {
            $logMessage = 'Unable to retrieve product for Lazada|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Parameters' . json_encode($parameters);
            Log::error($logMessage);
        } else {
            $exceptionMessage = 'Unable to retrieve products for Lazada|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Parameters' . json_encode($parameters);
            throw new \Exception($exceptionMessage);
        }
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
     */

    public function getOccupiedStock($sku)
    {
        $parameters = [];
        $parameters['seller_sku'] = $sku;
        $response = $this->client->request('get', '/product/item/get', $parameters);
        if (
            isset($response['code']) && $response['code'] == 0
            && isset($response['data'])
        ) {
            if (
                isset($response['data']["skus"])
                && count($response['data']["skus"]) > 0
                && isset($response['data']["skus"][0]["multiWarehouseInventories"])
                && count($response['data']["skus"][0]["multiWarehouseInventories"]) > 0
                && isset($response['data']["skus"][0]["multiWarehouseInventories"][0]['occupyQuantity'])
            ) {
                return $response['data']["skus"][0]["multiWarehouseInventories"][0]['occupyQuantity'];
            }
        }
        else {
            set_log_extra('parameters', $parameters);
            set_log_extra('Lazada GetOccupiedStock Error', $response);
            $logMessage = 'Failed to get occupied stock Lazada|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Sku' . $sku . '|Parameters|' . json_encode($parameters) . '|Response|' . json_decode($response);
            Log::error($logMessage);
        }
        return 0;
    }
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null)
    {
        $datasku = $product->getIdentifier(ProductIdentifier::SKU());

        if (preg_match("/&/", $datasku)) {
            $sku = str_replace("&", "&amp;", $datasku);
        } else if (preg_match("/</", $datasku)) {
            $sku = str_replace("<", "&lt;", $datasku);
        } else if (preg_match("/>/", $datasku)) {
            $sku = str_replace(">", "&gt;", $datasku);
        } else if (preg_match("/=/", $datasku)) {
            $sku = str_replace("=", "&quot;", $datasku);
        } else if (preg_match("/'/", $datasku)) {
            $sku = str_replace("'", "&apos;", $datasku);
        } else {
            $sku = $datasku;
        }
        $isCampaignSale = config('combinesell.lazada.campaign_sale');

        // For Lazada, we cannot deduct it below the pending order stock as we'll get
        // "Negative sellable stock over sale. Negative. Reserved stock 3 and allocate stock 0"

        $stock = max(0, $stock);
        if (empty($sku)) {
            set_log_extra('listing', $product);
            throw new \Exception('Lazada product does not have seller sku');
        }

        // get the occupied stock from pending order items based on sku, as Lazada is keeping track of occupied stock
        // $occupiedStock = $this->account->orderItems()->where('sku', $sku)->whereIn('order_items.fulfillment_status', [FulfillmentStatus::PENDING(), FulfillmentStatus::PROCESSING()])->sum('quantity');
        $occupiedStock = $this->getOccupiedStock($sku);
        $stock += $occupiedStock;

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Request><Product><Skus>';
        $xml .= '<Sku><SellerSku>' . $sku . '</SellerSku><Quantity>' . $stock . '</Quantity><Price/><SalePrice/><SaleStartDate/><SaleEndDate/></Sku>';
        $xml .= '</Skus></Product></Request>';
        $message = $this->shouldUpdateProduct($sku);
        if ($message != '') {

            $message .= '|Sku|' . $sku . '|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
            Log::info($message);
            return false;
        }
        try {
            if (!$isCampaignSale) {
                $response = $this->client->requestXml('POST', '/product/price_quantity/update', $xml);
                $code = (int)($response['code'] ?? 1);
                $actualResponseCode = (string)$response['code'] ?? '';
            } else {
                $code = 1;
                $actualResponseCode = Constant::CODE_API_APP_LIMIT;
            }
            /**
             * When "updatepricequantity" API is downgraded during campaign , the api returns "code": "appcalllimit"
             * and in such cases need to use API "UpdateSellableQuantity"
             * to update sellable quantity of one or more existing products. The maximum number of products that can be updated is 50, but 20 is recommended.
             */
            if (!empty($code) && $actualResponseCode && strtolower($actualResponseCode) == Constant::CODE_API_APP_LIMIT) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?><Request><Product><Skus>';
                $xml .= '<Sku><SellerSku>' . $sku . '</SellerSku><SellableQuantity>' . $stock . '</SellableQuantity></Sku>';
                $xml .= '</Skus></Product></Request>';
                //$response = $this->client->requestXml('POST', '/product/stock/sellable/update', $xml);
                $response = $this->client->requestXml('POST', '/product/stock/sellable/adjust', $xml);
                $code = (int)($response['code'] ?? 1);
            }
            if (!empty($code) && $code != 0) {
                set_log_extra('response', $response);
                set_log_extra('listing', $product);
                /**
                 * Log Error If Error Code is 501
                 */
                if ($code === 501) {
                    Log::info('Error Unable to update stock for Lazada product listing.|Sku|' . $sku . '|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Response|' . json_encode($response) ?? $code);
                }
                throw new \Exception('Unable to update stock for Lazada product listing. ' . $response['message'] ?? $code);
            }

            /**
             * Call API Success
             * If $actualResponseCode = appcalllimit => update productInventory
             */
            if ($actualResponseCode && strtolower($actualResponseCode) == Constant::CODE_API_APP_LIMIT && $productInventory) {
                $getStockLastProductInventory = $productInventory->logs()->orderBy('created_at', 'desc')->first();
                $stockByApiUpdateSellableQuantity = $stock + ($getStockLastProductInventory ? $getStockLastProductInventory->old : 0);
                $productInventory->modifyInventory(0, 'none', 'Manual change by ' . Auth::user()->name, null, null, false, $stockByApiUpdateSellableQuantity);
            }


            // As Lazada doesn't return the updated product, we should refresh it here
            $this->get($product);

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
        $sku = $listing->getIdentifier(ProductIdentifier::SKU());

        if (empty($sku)) {
            set_log_extra('listing', $listing);
            throw new \Exception('Lazada product does not have seller sku');
        }

        $status = ($enabled) ? 'active' : 'inactive';

        $xml = '<?xml version="1.0" encoding="UTF-8"?><Request><Product><Skus>';
        $xml .= '<Sku><SellerSku>' . $sku . '</SellerSku><Status>' . $status . '</Status></Sku>';
        $xml .= '</Skus></Product></Request>';

        try {
            $response = $this->client->requestXml('POST', '/product/update', $xml);

            $code = (int)($response['code'] ?? 1);

            if (!empty($code)) {
                set_log_extra('response', $response);
                set_log_extra('listing', $listing);
                throw new \Exception('Unable to update status for Lazada product listing.');
            }

            // As Lazada doesn't return the updated product, we should refresh it here
            $this->get($listing, true);

            return true;
        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $listing);
            throw $e;
        }
    }

    public function transformAttributes($attributes)
    {
        foreach ($attributes as &$attribute) {
            $options = [];
            if ($attribute['type'] == CategoryAttributeType::MULTI_ENUM()->getValue()) {
                foreach ($attribute['data'] as $data) {
                    $options[] = $data['name'];
                }
                $attribute['data'] = $options;
            }
        }
        Log::error(print_r($attributes, true));
        return $attributes;
    }

    private function getNameObjecAttribute($attributeValue)
    {

        $result = '';
        try {
            $value = json_decode($attributeValue, true);
            if (!empty($value[0]) && !empty($value[0]['name'])) {
                $result = $value[0]['name'];
            } elseif (!empty($value['name'])) {
                $result = $value['name'];
            }
        } catch (\Exception $e) {
        }

        return $result;
    }
    
    private function getSkuErrorFromString($message) {

        $checkSkuStr = 'outer ID:';

        foreach (explode(",", $message) as $data) {

            if (strpos($data, $checkSkuStr) !== false) {

                return substr($data, strlen($checkSkuStr));

            }

        }

    }
}
