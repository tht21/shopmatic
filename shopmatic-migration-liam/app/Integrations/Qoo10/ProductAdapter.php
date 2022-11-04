<?php

namespace App\Integrations\Qoo10;

use App\Constants\Dimension;
use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductAlertType;
use App\Constants\ProductIdentifier;
use App\Constants\ProductPriceType;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use App\Events\NewProductAlert;
use App\Integrations\AbstractProductAdapter;
use App\Integrations\TransformedProduct;
use App\Integrations\TransformedProductImage;
use App\Integrations\TransformedProductListing;
use App\Integrations\TransformedProductPrice;
use App\Integrations\TransformedProductVariant;
use App\Models\Brand;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductImportTask;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Region;
use App\Events\ProductFailedToImport;
use App\Models\ProductPrice;

class ProductAdapter extends AbstractProductAdapter
{
    // Shipping Rate/Form > Shipping Rate/Form > Shipping Rate Details > Type
    protected $deliveryFeeType = [
        'W' => 'Store Pickup',
        'F' => 'Charge',
        'X' => 'Free',
        'M' => 'Free On Condition',
    ];

    private $importErrorMessage = '';

    /**
     * Retrieves a single product
     *
     * @param ProductListing|null $listing
     * @param bool $update Whether or not to update the product if it already exists
     *
     * @param null $itemId
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(?ProductListing $listing, $update = false, $itemId = null, $extraParams = [], $data = [])
    {
        if (!is_null($listing)) {
            $itemId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        }

        // get product data from Qoo10
        $product = $this->getProduct($itemId);
        try {
            $product = $this->transformProduct($product, $extraParams, $data);
        } catch (\Exception $e) {
            set_log_extra('product', $product);
            throw $e;
        }
        Log::info(json_encode($product));
        return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
    }

    public function getBySku($sku)
    {
        // get product data from Qoo10
        $product = $this->getProduct(null, $sku);
        try {
            $product = $this->transformProduct($product, [], []);
        } catch (\Exception $e) {
            set_log_extra('product', $product);
            throw $e;
        }
        
        if (($product instanceof TransformedProduct)) {
            return $this->handleProduct($product, ['update' => false, 'new' => false]);
        }
        return null;
    }

    /**
     * Import all new products
     *
     * @param ProductImportTask|null $importTask
     * @param array $config
     *
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function import($importTask, $config)
    {
        $time_start = microtime(true);
        Log::info("[Import Qoo10 product] Start with task id:" . $importTask->id);
        $products = $this->getProducts();
        $time_end = microtime(true);
        $executionTime = ($time_end - $time_start);
        Log::info("[Import Qoo10 product] Finish get all products from API with " . count($products) . " products. Execution Time (" . $executionTime . ") seconds]");
        if (!is_null($importTask)) {
            $importTask->total_products = count($products);
            $importTask->save();
        }
        foreach ($products as $product) {
                $time_start = microtime(true);
                Log::info("Processing product with ItemCode: " . $product['ItemCode']);
                Log::info("Start get product from API");
                $product = $this->getProduct($product['ItemCode']);
                $time_end = microtime(true);
                $executionTime = ($time_end - $time_start);
                Log::info("Finish get product from API. Execution Time (" . $executionTime . ") seconds]");

                try {
                    $check = true;
                    $product = $this->transformProduct($product, [], [], $importTask, $check);
                    if (empty($product)) continue;
                    $this->handleProduct($product, $config);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    if (!is_null($importTask)) {
                        event(new ProductFailedToImport($importTask, (is_array($product) ?
                            trim(preg_replace(
                                '/[\x00-\x1F\x80-\xFF]/',
                                '',
                                mb_convert_encoding($product['ItemTitle'], "UTF-8")
                            )) : $product->associatedSku) . ' failed to import'));
                    }
                    continue;
                }
        }

        if (!empty($this->importErrorMessage)) {
            $importTask->messages = $this->setTaskMessages($importTask->messages, $this->importErrorMessage);
            $importTask->save();
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }
    }


    private function setTaskMessages($messages, $message)
    {
        if (empty($messages)) {
            $messages = [$message];
        } else {
            $messages = array_merge((array)$this->task->messages, [$message]);
        }
        return $messages;
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function sync()
    {
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $products = $this->getProducts();
        if (is_array($products) && count($products) > 0) {
            try {
                foreach ($products as $product) {

                    $product = $this->getProduct($product['ItemCode']);

                    try {
                        $product = $this->transformProduct($product);
                    } catch (\Exception $e) {
                        set_log_extra('product', $product);
                        throw $e;
                    }

                    $this->handleProduct($product);
                }
            } catch (\Exception $e) {
                set_log_extra('product', reset($products));
                throw $e;
            }
        } else {
            set_log_extra('response', $products);
            throw new \Exception('Unable to retrieve products for Qoo10');
        }
    }

    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(ProductListing $product, array $data)
    {
        // Attributes (flatten value of attribute)
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attributeName => $attribute) {
                if (array_key_exists('value', $attribute) && is_array($attribute['value']) && array_key_exists('value', $attribute['value'])) {
                    $data['attributes'][$attributeName]['value'] = $attribute['value']['value'];
                }
            }
        }

        $firstVariant = reset($data['variants']);
        $stock = $data['stock'] ?? $firstVariant['inventory']['stock'];
        if (is_null($stock)) {
            set_log_extra('listing', $product);
            throw new \Exception('Unable to get stock for listing.');
        }
        $data['stock'] = $stock;

        // Logistic
        // decode logistic json string
        if (isset($data['attributes']) && array_key_exists('logistics', $data['attributes'])) {
            $data['attributes']['logistics'] = json_decode($data['attributes']['logistics'], true)[0];
        } elseif (array_key_exists('logistics', $data) && isset($data['logistics']['value'])) {
            $data['attributes']['logistics'] = json_decode($data['logistics']['value'], true)[0];
        } elseif (array_key_exists('logistics', $data)) {
            $data['attributes']['logistics'] = json_decode($data['logistics'], true)[0];
        } 

        // options
        $options = $product->product->options;

        $error = [];
        // qoo10 API not support multi level options update (max 3 level)
        if (count($options) > 0 &&  count($options) <= 3) {
            $variantsString = $this->generateVariantsString($data['variants'], $options);

            $response = $this->updateVariants($product->getIdentifier(ProductIdentifier::EXTERNAL_ID()), $variantsString);

            if (!empty($response)) {
                $error = array_merge($error, $response);
            }
        }

        if (count($error) > 0) {
            return $this->respondWithError($error);
        }

        $response = $this->updateProduct($product, $data);

        if (!empty($response)) {
            $error = array_merge($error, $response);
        }

        return $this->respond();
    }

    private function getImageUrl($image)
    {
        return $image['image_url'] ? $image['image_url'] : $image['source_url'];
    }

    private function getStandardImage($product)
    {
        $regionId = $this->account->region_id;
        $integrationId = $this->account->integration_id;
        $image = $product->allImages()->where([
            'region_id' => $regionId,
            'integration_id' => $integrationId,
        ])->first();
        if ($image) {
            return $this->getImageUrl($image);
        }
        return $product->main_image;
    }
    /**
     * Creates a new product on the account from the product model
     *
     * @param Product $product
     *
     * @return mixed
     */
    public function create(Product $product)
    {
        $integrationId = Integration::QOO10;
        $this->preLoadProductData($product);

        $attributes = $product->attributes->where('product_variant_id', null)
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        if (isset($attributes['integration_category_id'])) {
            $integrationCategory = IntegrationCategory::where([
                'id' => $attributes['integration_category_id']['value'],
                'integration_id' => $integrationId,
                'region_id' => $this->account->region_id,
            ])->active()->first();
        } else {
            $integrationCategory = $product->category->integrationCategories
                ->where('region_id', $this->account->region_id)->first();
        }

        $variantOptions = $product->options;
        $variantData = $product->variants->load('main_prices')->toArray();
        $variantImages = [];
        /**
         * Product All Images.
         */
        $productMultiImage = [];
        if (count($product->allImages)) {

            $productMultiImage = $product->allImages->toArray();
        } else {
            $productImages = $product->allImages()->whereNull('integration_id')->whereNull('product_listing_id')->whereNull('region_id')->get();
            if (count($productImages)) {
                $productMultiImage = $productImages->toArray();
            }
        }
        /**
         * Variant All Images.
         * Merging the parent item multi image and variant image
         * as we can't call updateGoodsMultiImages for each variant as
         * it will replace the previous added images in Qoo10.
         */
        $multiImageSkuCountMapping = [];
        $parentProductImages = [];
        if (!empty($productMultiImage) && !empty($variantData)) {
            $parentProductImages = $productMultiImage;
            array_shift($parentProductImages);
            $multiImageSkuCountMapping[$product->associated_sku] = ['count' => count($productMultiImage) - 1, 'images' => $parentProductImages];
            /**
             * Product Variants
             */
            foreach ($product->variants as $variant) {
                /**
                 * If parent associated sku and variant sku are identical it means the default variant.
                 * Ideally in case of default variant all parent sku images are replicated to the variant.
                 * In such a scenerio skip the variant images.
                 */
                if ($product->associated_sku === $variant->sku) continue;

                $variantImages = $variant->allImages()->where('integration_id', $integrationId)->where('region_id', $this->account->region_id)->get();
                if ($variantImages->isEmpty()) {
                    $variantImages = $variant->allImages()->whereNull('integration_id')->whereNull('product_listing_id')->whereNull('region_id')->get();
                }
                if (count($variantImages)) {
                    $multiImageSkuCountMapping[$variant->sku] = ['count' => count($variantImages), 'images' => $variantImages->toArray()];
                    $productMultiImage = array_merge($productMultiImage, $variantImages->toArray());
                }
            }
        }
        /**
         * Generate Variant Item Type String
         */
        $variantString = $this->generateVariantsString($variantData, $variantOptions);

        /** @var ProductVariant $firstVariant */
        $firstVariant = $product->variants()->first();
        $priceTypes = [];
        foreach (Constant::PRICES() as $priceType) {
            $priceTypes[] = $priceType->getValue();
        }
        //TODO: Fix issue with currency / region_id
        $prices = ProductPrice::whereProductId($product->id)->whereIn('type', $priceTypes)
            ->where('integration_id', $this->account->integration_id)->whereNull('product_variant_id')->get()
            ->mapWithKeys(function ($item) {
                return [$item['type'] => $item];
            });
        $firstVariantAttributes = $firstVariant->attributes
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });
        $valuesToAddIfNotNull = ['adult_y_n', 'brand_no', 'industrial_code_type', 'industrial_code', 'hs_code', 'video_url', 'origin_country_code', 'origin_state', 'material', 'contact_tel', 'manafacture_no', 'shipping_no', 'expire_date'];
        $variantAttributeWeight = $firstVariantAttributes['weight'] ?? round($firstVariant->weight, 2);
        if (isset($variantAttributeWeight['value'])) {
            $variantAttributeWeight = strval($variantAttributeWeight['value']);
        }
        $standardImage = $this->getStandardImage($product);
        $body = [
            'SecondSubCat' => $integrationCategory->external_id,
            'ItemTitle' => $attributes['name']->value ?? $product->name,
            'SellerCode' => $product->associated_sku,
            'StandardImage' => $standardImage,
            'ItemDescription' => $attributes['html_description']->value ?? $product->html_description,
            'AdditionalOption' => '',
            'ItemType' => (string)$variantString,
            'RetailPrice' => isset($prices[ProductPriceType::RETAIL()->getValue()]) ? round($prices[ProductPriceType::RETAIL()->getValue()]->price, 2) : 0,
            'ItemPrice' => isset($prices[ProductPriceType::SELLING()->getValue()]) ? round($prices[ProductPriceType::SELLING()->getValue()]->price, 2) : 0,
            'ItemQty' => $firstVariant->inventory ? $firstVariant->inventory->stock : 0,
            'ModelNm' => $attributes['model'] ?? $product->model,
            'Weight' => $variantAttributeWeight,
        ];

        foreach ($valuesToAddIfNotNull as $key) {
            if (isset($attributes[$key]) && !is_null($attributes[$key])) {
                // Check for brand
                if ($key === 'brand_no' && isset($attributes[$key]->value) && is_string($attributes[$key]->value)) {
                    // Retrieve brand external_id
                    $attributes[$key] = str_replace('_', '', Brand::whereIntegrationId($this->account->integration_id)->whereRegionId($this->account->region_id)->whereName($attributes[$key]->value)->active()->pluck('external_id')->first());
                }
                /**
                 * Validate the Expire Date.
                 */
                if ($key === 'expire_date' && isset($attributes[$key])) {
                    /**
                     * Validate the date format for 'expire_date'
                     * It is expected in format 'Y-m-d'
                     * If not in the specfied format, initialised expire_date as empty as the api throw error of invalid expiry date.
                     */
                    try {
                        if (empty($attributes[$key]->value) || Carbon::createFromFormat('Y-m-d', $attributes[$key]->value) === false) {
                            $attributes[$key]->value = '';
                        }
                    } catch (\Exception $e) {
                        set_log_extra('qoo10_invalid_expiry_date', $attributes[$key]);
                        $attributes[$key]->value = '';
                    }
                }
                $actualKey = str_replace(' ', '', ucwords(str_replace('_', ' ',  $key)));
                $body[$actualKey] = $attributes[$key]->value ?? $attributes[$key];
            }
        }
        foreach ($body as $key => $value) {
            if ($key != 'RetailPrice' && $key != 'ShippingNo') {
                $body[$key] = strval($value);
            }
        }
        if ($body['ItemPrice'] <= 0) {
            return $this->respondBadRequestError('Item Price must be greater than zero');
        }
        $response = $this->client->request('POST', 'ItemsBasic.SetNewGoods', $body);
        if ($response['ResultCode'] !== 0) {
            return $this->respondBadRequestError($response['ResultMsg']);
        } else {

            /**
             * Removing the firstImage i.e the main image.
             * Considering multi-images from Image1-Image12
             */
            array_shift($productMultiImage);
            if (!empty($productMultiImage)) {
                $this->updateGoodsMultiImages($response['ResultObject']['GdNo'], $productMultiImage);
            }
            /**
             * Update Goods
             * category external id is mandatory
             * */
            $productInfo = $product->toArray();
            $productInfo['category_external_id'] = $integrationCategory->external_id;
            $this->updateGoods($response['ResultObject']['GdNo'], $productInfo, null, $body);

            // Add Options if available
            /*if (!empty($variantOptions)) {
               $this->insertInventoryDataUnit($response['ResultObject']['GdNo'],$product->associated_sku,$variantData, $variantOptions);
            }*/
            $product = $this->get(null, true, $response['ResultObject']['GdNo'], ['multiImageSkuCountMapping' => $multiImageSkuCountMapping], $product);
        }
    }

    public function updateGoods($externalId, $data, $retailPrice = null, $createdBody = [])
    {
        $valuesToAddIfNotNull = ['industrial_code_type', 'industrial_code', 'hs_code', 'video_url', 'origin_country_code', 'material', 'contact_tel', 'brand_no', 'manafacture_no'];

        $shippingNo = $data['attributes']['shipping_no']['value'] ?? '';
        if (empty($shippingNo) && isset($data['logistics']['value'])) {
            $shippingNo = json_decode($data['logistics']['value'], true)[0]['external_id'];
        }

        $getExternalId = 0;
        if (isset($data['category']) && isset($data['category']['external_id'])) {
            $getExternalId = $data['category']['external_id'];
        }

        if (!empty($data['category']['id']) && !isset($data['category']['external_id'])) {
            Log::info($data['category']['id']);
            $getNameCategory = Category::where('id', $data['category']['id'])
                ->select('name')
                ->first();

            if (!empty($getNameCategory)) {
                $getExternalId = IntegrationCategory::where('name', 'LIKE', '%' . $getNameCategory->name . '%')
                    ->select('external_id')
                    ->first();
                if (!empty($getExternalId)) {
                    $getExternalId = $getExternalId->external_id;
                }
            }
        }
        $body = [
            'ItemCode' => $externalId,
            'SecondSubCat' => $data['category_external_id'] ?? $getExternalId,
            'ItemTitle' => $data['attributes']['name']['value'] ?? $data['name'],
            'BriefDescription' => $data['attributes']['short_description']['value'] ?? $data['short_description'],
            'ItemDescription' => $data['attributes']['html_description']['value'] ?? $data['html_description'],
            'SellerCode' => $data['associated_sku'],
            'AudultYN' => $data['attributes']['adult_y_n']['value'] ?? 'N',
            'Weight' => $data['weight'] ?? 0,
            'ShippingNo' => $shippingNo,
        ];

        foreach ($valuesToAddIfNotNull as $key) {
            // Check for brand
            if ($key === 'brand_no') {
                $brandNo = '';
                if (isset($createdBody['BrandNo'])) {
                    $brandNo = $createdBody['BrandNo'];
                } else {
                    if (isset($data['attributes'][$key]) && isset($data['attributes'][$key]['value']) && !is_null($data['attributes'][$key]['value'])) {
                        $brandNo = str_replace('_', '', Brand::whereIntegrationId($this->account->integration_id)->whereRegionId($this->account->region_id)->whereName($data['attributes'][$key]['value'])->active()->pluck('external_id')->first());
                    }
                }
                $body['BrandNo'] = $brandNo;
            } else {
                if (isset($data['attributes'][$key]) && isset($data['attributes'][$key]['value']) && !is_null($data['attributes'][$key]['value'])) {
                    $actualKey = str_replace(' ', '', ucwords(str_replace('_', ' ',  $key)));
                    $body[$actualKey] = $data['attributes'][$key]['value'];
                }
            }
        }

        if (!empty($retailPrice)) {
            $body['RetailPrice'] = strval($retailPrice);
        }

        if (empty($body['ShippingNo'])) {
            $body['ShippingNo'] = 0; // If set to 0, it will be set as Free Shipping
        }
        $response = $this->client->request('POST', 'ItemsBasic.UpdateGoods', $body);

        if ($response['ResultCode'] !== 0) {
            return $response['ResultMsg'];
        }

        return null;
    }


    /**
     * Deletes the product from the integration
     *
     * @param ProductListing $product
     *
     * @return mixed
     * @throws \Exception
     */
    public function delete(ProductListing $product)
    {
        if ($product->getIdentifier(ProductIdentifier::EXTERNAL_ID())) {
            $externalId = $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());
            $associatedSku = $product->product->associated_sku;

            $response = $this->deleteProduct($externalId, $associatedSku);

            if (!empty($response)) {
                return $this->respondWithError($response);
            }
            return true;
        }
        set_log_extra('listing', $product);
        throw new \Exception('Product item id not found');
    }

    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        $rawCategories = $this->getCategories();
        $categories = [];

        foreach ($rawCategories as $rawCategory) {
            // initialize 1st layer category if not exist
            if (!array_key_exists($rawCategory['CATE_L_CD'], $categories)) {
                $categories[$rawCategory['CATE_L_CD']] = [
                    'name'          => $rawCategory['CATE_L_NM'],
                    'breadcrumb'    => $rawCategory['CATE_L_NM'],
                    'external_id'   => $rawCategory['CATE_L_CD'],
                    'is_leaf'       => 0,
                    'children'      => []
                ];
            }

            // initialize 2nd layer category if not exist
            if (!array_key_exists($rawCategory['CATE_M_CD'], $categories[$rawCategory['CATE_L_CD']]['children'])) {
                $categories[$rawCategory['CATE_L_CD']]['children'][$rawCategory['CATE_M_CD']] = [
                    'name'          => $rawCategory['CATE_M_NM'],
                    'breadcrumb'    => $rawCategory['CATE_L_NM'] . ' > ' . $rawCategory['CATE_M_NM'],
                    'external_id'   => $rawCategory['CATE_M_CD'],
                    'is_leaf'       => 0,
                    'children'      => []
                ];
            }

            // add 3rd layer category
            $categories[$rawCategory['CATE_L_CD']]['children'][$rawCategory['CATE_M_CD']]['children'][$rawCategory['CATE_S_CD']] = [
                'name'          => $rawCategory['CATE_S_NM'],
                'breadcrumb'    => $rawCategory['CATE_L_NM'] . ' > ' . $rawCategory['CATE_M_NM'] . ' > ' . $rawCategory['CATE_S_NM'],
                'external_id'   => $rawCategory['CATE_S_CD'],
                'is_leaf'       => 1,
                'children'      => []
            ];
        }
        return $categories;
    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     *
     * @return array
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {
        // Qoo10 dont have category attributes
        return [];
    }

    /**
     * Retrieves shipping group information of the seller
     *

     */
    public function getShippingInfo()
    {
        $parameters = [
            'method' => 'ItemsLookup.GetSellerDeliveryGroupInfo',
        ];

        $response = $this->client->request('get', '/GMKT.INC.Front.QAPIService/Giosis.qapi', $parameters);

        if (isset($response['ResultCode']) && $response['ResultCode'] == 0) {
            return $response['ResultObject'];
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve shipping info for Qoo10');
        }
    }

    /**
     * @inheritDoc
     */
    public function retrieveLogistics($attributes = null)
    {
        $rawLogistics = $this->getLogistics();
        $logistics = [];

        foreach ($rawLogistics as $rawLogistic) {
            // 80 - Quick Prime Service , 70 - Qprime-S Shipping , 60 - Others(user's defined)
            $type = 60;
            if (starts_with($rawLogistic['ShippingName'], 'Qprime')) {
                $type = 70;
            } elseif ($rawLogistic['ShippingName'] == 'Quick Prime') {
                $type = 80;
            }

            if (!array_key_exists($rawLogistic['BundleName'], $logistics)) {
                $logistics[$rawLogistic['BundleName']] = [];
            }

            $logistics[$rawLogistic['BundleName']][] = [
                'external_id' => $rawLogistic['ShippingNo'],
                'type' => $type,
                'name' => $rawLogistic['ShippingName'],
                'delivery_fee' => $rawLogistic['ShippingFee'],
                'free_condition' => $rawLogistic['FreeCondition'],
                'delivery_fee_type' => $this->deliveryFeeType[$rawLogistic['ShippingType']],
            ];
        }

        return $logistics;
    }

    /**
     * Transform product
     *
     * @param $product
     *
     * @return TransformedProduct
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function transformProduct($product, $extraParams = [], $data = [], $importTask = null, $check = false)
    {
        // used to map qoo10 status to status
        $qoo10Status = [
            'S0' => ProductStatus::DRAFT(), // Under Review
            'S1' => ProductStatus::DISABLED(), // On Queue
            'S2' => ProductStatus::LIVE(), // Available
            'S3' => ProductStatus::DISABLED(), // Suspended
            'S4' => ProductStatus::DISABLED(), // Restricted
            'S8' => ProductStatus::DISABLED(), // Rejected
        ];

        $productStatusToMarketplaceStatus = [
            ProductStatus::DRAFT()->getValue() => MarketplaceProductStatus::PENDING(),
            ProductStatus::LIVE()->getValue() => MarketplaceProductStatus::LIVE(),
            ProductStatus::DISABLED()->getValue() => MarketplaceProductStatus::DISABLED(),
            ProductStatus::OUT_OF_STOCK()->getValue() => MarketplaceProductStatus::OUT_OF_STOCK(),
        ];

        // general data
        $externalId = $product['ItemNo'];
        $mainProductIdentifier = [
            ProductIdentifier::EXTERNAL_ID()->getValue() => $externalId
        ];
        $associatedSku = $product['SellerCode'];
        $status = $qoo10Status[$product['ItemStatus']];
        $marketplaceStatus = $productStatusToMarketplaceStatus[$status->getValue()];
        $price = $product['SellPrice'];
        $checkProduct = Product::where('associated_sku', $associatedSku)->first();
        $flag = false;
        if($check == true && !isset($checkProduct)) {
            $flag = true;
        }
        $prices = [
            new TransformedProductPrice($this->account->currency, $price, ProductPriceType::SELLING(), $flag)
        ];
        $stock = $product['ItemQty'];
        $shortDescription = $data['short_description'] ?? null; // qoo10 API cant get brief description
        $htmlDescription = $product['ItemDetail'];
        $name = trim(preg_replace(
            '/[\x00-\x1F\x80-\xFF]/',
            '',
            mb_convert_encoding($product['ItemTitle'], "UTF-8")
        ));
        $brand = $product['BrandNm'];
        $model = $data['model'] ?? null;
        $productUrl = 'https://www.qoo10.sg/GMKT.INC/Goods/Goods.aspx?goodscode=' . $externalId;

        // attributes
        $attributes = [];
        $attributes['material'] = $product['Material'];
        $attributes['phone_number'] = $product['ContactTel'];
        $attributes['adult_y_n'] = $product['AdultYN'];
        $attributes['expire_date'] = $product['ExpireDate'];
        $attributes['video_url'] = $product['VideoUrl'];
        $attributes['brand_no'] = $product['BrandNm'];
        $attributes['shipping_no'] = $product['ShippingNo'];
        $attributes['contact_tel'] = $product['ContactTel'];
        $attributes['industrial_code'] = $product['IndustrialCode'];
        $attributes['logistics'] = [[
            'external_id' => $product['ShippingNo']
        ]];

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = IntegrationCategory::where([
            'integration_id' => $this->account->integration_id,
            'region_id' => $this->account->region_id,
            'external_id' => $product['SecondSubCatCd'],
        ])->active()->first();

        // qoo10 seems to have old products using categories that are no longer valid
        if (empty($integrationCategory)) {
            set_log_extra('primary_category', $product['MainCatCd']);
            set_log_extra('product', $product);
            set_log_extra('account', $this->account->toArray());

            $mainProduct = Product::whereAssociatedSku($associatedSku)->first();
            $message = 'Unable to find integration category with external id "' . $product['SecondSubCatCd'] . '" for product with name "' . $name . '" and associated SKU "' . $associatedSku . '" from Qoo10 ' . Region::REGIONS[$this->account->region_id] . ' (' . $this->account->name . ').';
            Log::info($message);

            if (!empty($mainProduct)) {
                event(new NewProductAlert($product, $message, ProductAlertType::WARNING(), $this->account->shop_id, $mainProduct->id));
            }
            if (!empty($importTask)) {
                event(new ProductFailedToImport($importTask, $message));
            }
            return false;
        }

        $category = $integrationCategory->category;

        // qoo10 doesn't support account category
        $accountCategory = null;

        // images
        $images = [];
        if (!empty($extraParams) && isset($extraParams['multiImageSkuCountMapping'])) {
            $multiImageSkuCountMapping = $extraParams['multiImageSkuCountMapping'];
            if (!empty($multiImageSkuCountMapping[$associatedSku]['images'])) {
                foreach ($multiImageSkuCountMapping[$associatedSku]['images'] as $index => $image) {
                    $images[] = new TransformedProductImage($image['image_url'], null, null, null, $index);
                }
            }
        }

        $rawImages = explode('$$', $product['MultiImageUrl']);

        $variantMultiImages = [];
        if (!empty($extraParams) && isset($extraParams['multiImageSkuCountMapping'])) {
            if (isset($extraParams['multiImageSkuCountMapping'], $extraParams['multiImageSkuCountMapping'][$associatedSku])) {
                $multiImageSkuCountMapping = $extraParams['multiImageSkuCountMapping'];
                $parentMultiImageCount = $multiImageSkuCountMapping[$associatedSku]['count'];
                $parentOriginalMultiImages = $multiImageSkuCountMapping[$associatedSku]['images'];
                unset($multiImageSkuCountMapping[$associatedSku]);
                /**
                 * Slice the parent item raw image based on parent multi image count.
                 */
                $qoo10ParentRawImages = array_slice($rawImages, 0, $parentMultiImageCount);
                /**
                 * Variant Raw Images From Qoo10
                 */
                $variantRawImages = array_diff($rawImages, $qoo10ParentRawImages);
                /**
                 * Parent Item Raw Images From Qoo10
                 */
                $rawImages = array_values($qoo10ParentRawImages);
                /**
                 * Compare the Qoo10 images and the original image for similarity
                 * 0 -> means no match
                 * 1 -> complete match
                 */
                try {
                    foreach ($rawImages as $key => $rawImageUrl) {
                        $isImageMatching = 0;
                        $csItemOriginalImageUrl = $parentOriginalMultiImages[$key]['image_url'] ?? $parentOriginalMultiImages['image_url'];
                        if ($csItemOriginalImageUrl) {
                            $time_start = microtime(true);
                            $qoo10ImageFile = file_get_contents($rawImageUrl);
                            $csItemOriginalImageFile = file_get_contents($csItemOriginalImageUrl);
                            $isImageMatching = $this->compAvgColor($this->getAvgColor($qoo10ImageFile), $this->getAvgColor($csItemOriginalImageFile));
                            $time_end = microtime(true);
                            $execution_time = ($time_end - $time_start);
                            /**
                             * If the image is not matching then unset it from the rawImages.
                             */
                            if (!$isImageMatching) {
                                unset($rawImages[$key]);
                            }
                            Log::info("Image similarity:" . $isImageMatching . ' | Associated Sku :' . $associatedSku . ' | Qoo10 Image :' . $rawImageUrl . '| CS Original Item Image : ' . $csItemOriginalImageUrl . ' | Total comparison Time:' . ($execution_time * 1000) . ' ms');
                        }
                    }
                    $rawImages = array_values($rawImages);
                } catch (\Exception $e) {
                    set_log_extra('image_compare_parent_items', ['associated_sku' => $associatedSku, 'rawImages' => $rawImages, 'error' => $e->getMessage()] ?? null);
                }
                /**
                 * Variant Images
                 */
                foreach ($multiImageSkuCountMapping as $sku => $variantMultiInfo) {
                    $count = $variantMultiInfo['count'];
                    $variantOriginalMultiImages = $variantMultiInfo['images'];
                    $qoo10variantRawImages = array_slice($variantRawImages, 0, $count);
                    $variantRawImages = array_diff($variantRawImages, $qoo10variantRawImages);
                    $variantRawImages = array_values($variantRawImages);

                    /**
                     * Compare Qoo10 variantsImages with the original variantImage for similarity
                     *
                     */
                    $transpormedVariantImg = [];
                    foreach ($qoo10variantRawImages as $key => $rawImageUrl) {
                        try {
                            if (isset($variantOriginalMultiImages[$key]['image_url'])) {
                                $time_start = microtime(true);
                                $qoo10ImageFile = file_get_contents($rawImageUrl);
                                $csVariantImageFile = file_get_contents($variantOriginalMultiImages[$key]['image_url']);
                                $isImageMatching = $this->compAvgColor($this->getAvgColor($qoo10ImageFile), $this->getAvgColor($csVariantImageFile));
                                $time_end = microtime(true);
                                $execution_time = ($time_end - $time_start);
                                Log::info("Image similarity:" . $isImageMatching . '| Sku :' . $sku . ' | Qoo10 Image :' . $rawImageUrl . '| CS Variant Image : ' . $variantOriginalMultiImages[$key]['image_url'] . ' | Total comparison Time:' . ($execution_time * 1000) . ' ms');
                                if (!$isImageMatching) continue;
                            }
                        } catch (\Exception $e) {
                            set_log_extra('image_compare_variant_item', ['sku' => $sku, 'rawImageUrl' => $rawImageUrl, 'error' => $e->getMessage()] ?? null);
                        }
                        $transpormedVariantImg[] = new TransformedProductImage($rawImageUrl, null, null, null, count($transpormedVariantImg));
                    }
                    $variantMultiImages[$sku] = $transpormedVariantImg;
                }
            }
        }

        // variants
        $rawVariants = $this->getVariants($externalId);
        $variants = [];
        $options = [];

        $variantIndex = 0;
        foreach ($rawVariants as $rawVariant) {
            // options currently only support 3 level TODO: options max in qoo10 is 5, what to do if got 5 options?
            $option1 = $rawVariant['Value1'];
            $option2 = $rawVariant['Value2'];
            $option3 = $rawVariant['Value3'];

            if (!empty($rawVariant['Name1']) && !in_array($rawVariant['Name1'], $options)) {
                $options[] = $rawVariant['Name1'];
            }
            if (!empty($rawVariant['Name2']) && !in_array($rawVariant['Name2'], $options)) {
                $options[] = $rawVariant['Name2'];
            }
            if (!empty($rawVariant['Name3']) && !in_array($rawVariant['Name3'], $options)) {
                $options[] = $rawVariant['Name3'];
            }

            // Qoo10 does not support names for the SKU, so we should implode from the option values
            $variantName = '';
            for ($i = 0; $i < 5; $i++) {
                if (!empty(${"option$i"})) {
                    $variantName .= ${"option$i"} . ' ';
                }
            }
            $variantName = trim($variantName);
            $variantSku = $rawVariant['ItemTypeCode'];

            //Qoo10 doesn't support barcodes
            $barcode = null;

            // use q-inventory stock if available
            $variantStock = !empty($rawVariant['QinventoryCode']) ? $rawVariant['QinventoryQty'] : $rawVariant['Qty'];

            // Selling price, need to + price from product
            $variantPrices = [new TransformedProductPrice($this->account->currency, $rawVariant['Price'], ProductPriceType::SELLING(), $flag)];


            $mainVariantPrices = [];
            if (!empty($associatedSku)) {
                $mainProductVariant = ProductVariant::whereSku($associatedSku)->first();
                if (!empty($mainProductVariant)) {
                    $listMainProductVariantPrices = $mainProductVariant->prices()->whereNull('region_id')->whereNull('integration_id')->get()->toArray();
                    foreach($listMainProductVariantPrices as $price) {
                        if (!empty($price)) {
                            $mainVariantPrices[] = new TransformedProductPrice($price['currency'], $price['price'], ProductPriceType::SELLING(), $flag);
                        }
                    }
                }
            };

            // not provided by qoo10 API
            $variantAttributes = [];
            $variantImages = $variantMultiImages[$variantSku] ?? [];
            $weightUnit = Weight::KILOGRAMS();
            $weight = 0;

            // use variant name as external id since qoo10 API doesnt support get variant external id
            $identifiers = [
                ProductIdentifier::EXTERNAL_ID()->getValue() => $externalId . '-' . $variantName,
                ProductIdentifier::SKU()->getValue() => $variantSku,
            ];
            logger()->info('[Qoo10 transformProduct]|ProductName: ' . $name . '|ProductItemNo: ' . $externalId . '|VariantName: ' . $variantName);
            $variantListing = new TransformedProductListing(
                $variantName,
                $identifiers,
                $integrationCategory,
                $accountCategory,
                $variantPrices,
                $productUrl,
                $variantStock,
                $variantAttributes,
                $rawVariant,
                $variantImages,
                $marketplaceStatus
            );
            if (!empty($data) && count(array($data)) > 0) {
                $variantweight = $this->getDimensionData($data, 'weight', $variantIndex);
            } else {
                $variantweight = $product['Weight'];
            }
            $variantlength = $this->getDimensionData($data, 'length', $variantIndex);
            $variantwidth = $this->getDimensionData($data, 'width', $variantIndex);
            $variantheight = $this->getDimensionData($data, 'height', $variantIndex);
            $variants[] = new TransformedProductVariant(
                $variantName,
                $option1,
                $option2,
                $option3,
                $variantSku,
                $barcode,
                $variantStock,
                empty($mainVariantPrices) ? $variantPrices : $mainVariantPrices,
                $status,
                ShippingType::MARKETPLACE(),
                $variantweight,
                $weightUnit,
                $variantlength,
                $variantwidth,
                $variantheight,
                Dimension::CM(),
                $variantListing,
                $variantImages
            );
            $variantIndex++;
        }      
        // If there's no variant, create one
        if (empty($variants)) {
            $mainVariantPrices = [];
            $mainListingPrices = [];
            if (!empty($associatedSku)) {
                $mainProductVariant = ProductVariant::whereSku($associatedSku)->first();
                if (!empty($mainProductVariant)) {
                    $listMainProductVariantPrices = $mainProductVariant->prices()->whereNull('region_id')->whereNull('integration_id')->get()->toArray();
                    foreach($listMainProductVariantPrices as $price) {
                        if (!empty($price)) {
                            $mainVariantPrices[] = new TransformedProductPrice($price['currency'], $price['price'], ProductPriceType::SELLING(), $flag);
                        }
                    }

                    $listMainProductListingPrices = $mainProductVariant->listing_prices()->whereNull('region_id')->whereIntegrationId($this->account->integration_id)->get()->toArray();
                    foreach($listMainProductListingPrices as $price) {
                        if (!empty($price)) {
                            $mainListingPrices[] = new TransformedProductPrice($price['currency'], $price['price'], ProductPriceType::SELLING(), $flag);
                        }
                    }
                }
            };
            if (!empty($data)) {
                if (isset($data['prices']) && isset($data['prices'][0]) && !isset($data['variants'])) {
                    $priceData = $data['prices'][0];
                    $mainListingPrices = [new TransformedProductPrice($priceData['currency'], $priceData['price'], ProductPriceType::SELLING(), $flag)];
                }
            }
            $variantListing = new TransformedProductListing(
                $name,
                array_merge($mainProductIdentifier, [ProductIdentifier::SKU()->getValue() => $associatedSku]),
                $integrationCategory,
                $accountCategory,
                empty($mainListingPrices) ? $prices : $mainListingPrices,
                $productUrl,
                $stock,
                [],
                $product,
                $images,
                $marketplaceStatus
            );
            if (!empty($data) && count(array($data)) > 0) {
                $variantweight = $this->getDimensionData($data, 'weight');
            } else {
                $variantweight = $product['Weight'];
            }
            $variantlength = $this->getDimensionData($data, 'length');
            $variantwidth = $this->getDimensionData($data, 'width');
            $variantheight = $this->getDimensionData($data, 'height');
            $variants[] = new TransformedProductVariant(
                $name,
                null,
                null,
                null,
                $associatedSku,
                null,
                $stock,
                empty($mainVariantPrices) ? $prices : $mainVariantPrices,
                $status,
                ShippingType::MARKETPLACE(),
                $variantweight,
                Weight::KILOGRAMS(),
                $variantlength,
                $variantwidth,
                $variantheight,
                Dimension::CM(),
                $variantListing,
                $images
            );
        }

        $images = [];
        // Main image
        if (!empty($product['ImageUrl'])) {
            $images[] = new TransformedProductImage($product['ImageUrl'], null, null, null, 0);
        }

        $rawImages = explode('$$', $product['MultiImageUrl']);

        foreach ($rawImages as $index => $rawImage) {
            $images[] = new TransformedProductImage($rawImage, null, null, null, count($images));
        }
        // retail and settle price
        $prices[] = new TransformedProductPrice($this->account->currency, $product['RetailPrice'], ProductPriceType::RETAIL(), $flag);
        $prices[] = new TransformedProductPrice($this->account->currency, $product['SettlePrice'], ProductPriceType::COST(), $flag);

        $listing = new TransformedProductListing($name, $mainProductIdentifier, $integrationCategory, $accountCategory, $prices, $productUrl, $stock, $attributes, $product, $images, $marketplaceStatus);

        $product = new TransformedProduct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $category, $status, $variants, $options, $listing, $images);
        return $product;
    }

    public function toggleEnable(ProductListing $listing, $enabled = true)
    {
        $mainListing = $listing;
        if (!is_null($listing->product_variant_id)) {
            // get main listing
            $mainListing = $listing->listing;
        }
        $externalId = $mainListing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        $response = $this->client->request('GET', 'ItemsBasic.EditGoodsStatus', [
            'ItemCode' => $externalId,
            'Status' => $enabled ? 2 : 1 // (On queue = 1, Transaction available = 2, Transaction discontinued = 3)
        ]);

        if ($response['ResultCode'] !== 0) {
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
        return null;
    }

    public function getDimensionData($data, $dimension, $variantIndex = 0)
    {
        if (isset($data) && isset($data['variants']) && count($data['variants']) > $variantIndex && isset($data['variants'][$variantIndex][$dimension])) {
            return $data['variants'][$variantIndex][$dimension];
        }
        return 0;
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
    public function updateStock(ProductListing $productListing, $stock, ?ProductInventory $productInventory = null)
    {
        try {
            /**
             * Verify if the item is a variant.
             * If asscocaiated_sku and sku not similar,it means it's a variant.
             */
            $associatedSku = $productListing->product->associated_sku;
            $sku = $productListing->getIdentifier(ProductIdentifier::SKU());
            $isParentItem = ($associatedSku === $sku) ? true : false;
            if ($isParentItem) {
                $externalId = $productListing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
                $this->updateGoodsPriceQty($externalId, $sku, $stock, null, null);

                // CSM-1469 P1 Nutrition Pro - Qoo10 Inventory Update not working for products with 1 option
                $optionValue = $productListing->identifier_text ?? '';
                $this->updateInventoryQtyUnit($associatedSku, $sku, $stock, $optionValue);

                $this->getBySku($sku);
            } else {
                $optionValue = $productListing->identifier_text ?? '';
                $this->updateInventoryQtyUnit($associatedSku, $sku, $stock, $optionValue);
            }
            return true;
        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $productListing);
            throw $e;
        }
    }

    /**
     * Generate variants string
     *
     * @param array $variantsData
     * @param array|null $options
     *
     * @return string
     */
    public function generateVariantsString(array $variantsData, ?array $options)
    {
        // format sample
        // &itemtype=
        //Color/Size
        //||*Red/100
        //||*100
        //||*200
        //||*0
        //$$
        //Color/Size
        //||*Yellow/100
        //||*price
        //||*stock
        //||*sku
        // options type
        /*
        $optionsString = '';
        foreach ($options as $option) {
            $optionsString .= (!empty($optionsString) ? '_' : '').title_case($option);
        }
        if (empty($optionsString)) {
            return '';
        }
        */

        /**
         *  Sample Format.
         * Color||*Blue||*Size||*M||*1.0000||*3||*Variant Sku 1$$Color||*Red||*Size||*S||*1.0000||*3||*Variant Sku 2
         **/
        $variantsString = '';
        foreach ($variantsData as $variantData) {
            // options value
            $optionsValue = '';
            for ($i = 0; $i < 3; $i++) {
                if (!empty($options[$i]) && !empty($variantData['option_' . ($i + 1)])) {
                    $optionsValue .= (!empty($optionsValue) ? '||*' : '') . trim($options[$i]) . '||*' . trim($variantData['option_' . ($i + 1)]);
                }
            }

            // price
            $price = null;
            if (isset($variantData['main_prices'])) {
                foreach ($variantData['main_prices'] as $priceData) {
                    if ($priceData['integration_id'] == Integration::QOO10  && $priceData['type'] === ProductPriceType::SELLING()->getValue()) {
                        $price = $priceData['price'];
                    }
                }
            } else {
                if (isset($variantData['prices']) && isset($variantData['prices'][0])) {
                    $price = $variantData['prices'][0]['price'];
                }
            }
            // sku
            $variantSku = $variantData['sku'];

            // stock
            $variantStock = $variantData['stock'] ?? 0;
            // If variant stock empty.
            if (empty($variantStock)) {
                $variantStock = $variantData['inventory']['stock'] ?? 0;
            }

            // build variants string
            $variantsString .= (!empty($variantsString) ? '$$' : '') . $optionsValue . '||*' . $price . '||*' . $variantStock . '||*' . $variantSku;
        }
        return $variantsString;
    }

    /**
     * Update main product
     *
     * @param ProductListing $product
     * @param array $data
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateProduct(ProductListing $product, array $data)
    {
        $data['short_description'] = $product['product']['short_description'];
        // external id
        $externalId = $data['identifiers']['external_id'] ?? $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());

        // price
        $sellingPrice = null;
        $retailPrice = null;
        foreach ($data['prices'] as $priceData) {
            if ($priceData['type'] === ProductPriceType::RETAIL()->getValue()) {
                $body['RetailPrice'] = strval($priceData['price']);
                $retailPrice = strval($priceData['price']);
            } elseif ($priceData['type'] === ProductPriceType::SELLING()->getValue()) {
                $sellingPrice = strval($priceData['price']);
            }
        }

        // attribute - expired date
        $expireDate = $data['attributes']['expire_date']['value'] ?? null;

        $errors = [];

        $response = $this->updateGoods($externalId, $data, $retailPrice);

        if (!empty($response)) {
            $errors[] = $response;
        }

        $response = $this->updateGoodsPriceQty($externalId, $data['associated_sku'], $data['stock'], $sellingPrice, $expireDate);

        if (!empty($response)) {
            $errors[] = $response;
        }

        $response = $this->updateImages($externalId, $data, $product);

        if (!empty($response)) {
            $errors = array_merge($errors, $response);
        }

        return $errors;
    }

    /**
     * Update product images
     *
     * @param $externalId
     * @param $images
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateImages($externalId, $data, ProductListing $product)
    {
        $short_description = $data['short_description'];
        $errors = [];
        $proImages = [];
        if (!empty($data['images'])) {
            foreach ($data['images'] as $value) {
                if (!isset($value['deleted'])) {
                    if (isset($value['data_url'])) {
                        $value['image_url'] = uploadImageFile($value['data_url'], session('shop'));
                        $proImages[] = $value;
                    } else {
                        $proImages[] = $value;
                    }
                }
            }
        }
        $images = $proImages;

        // update first image, use first image as primary image
        $firstImage = array_shift($images);
        $response = $this->updateGoodsImage($externalId, $firstImage['image_url']);

        if (!empty($response)) {
            $errors[] = $response;
        }

        if (!empty($firstImage)) {
            $product->product->update(['main_image' => $firstImage['image_url']]);
        }
        if (!empty($data['variants'])) {
            foreach ($data['variants'] as $variant) {
                $varImages = [];
                $productVariant = ProductVariant::where('id', $variant['id'])->first();
                foreach ($variant['images'] as $value) {
                    if (!isset($value['deleted'])) {
                        if (isset($value['data_url'])) {
                            $value['image_url'] = uploadImageFile($value['data_url'], session('shop'));
                            $varImages[] = $value;
                        } else {
                            $varImages[] = $value;
                        }
                    }
                }
                if (!empty($productVariant)) {
                    if (!empty($varImages[0]['image_url'])) {
                        $productVariant->update(['main_image' => $varImages[0]['image_url']]);
                    }
                }
            }
        }
        /**
         * Parent Multi Images
         */
        $multiImages = [];
        $associatedSku = $data['associated_sku'];
        if (!empty($images)) {
            foreach ($images as $name => $value) {
                if (!isset($value['deleted'])) {
                    if (isset($value['data_url'])) {
                        $multiImages[$associatedSku][]['image_url'] = uploadImageFile($value['data_url'], session('shop'));
                    } else {
                        $multiImages[$associatedSku][]['image_url'] = $value['image_url'];
                    }
                }
            }
        }

        /**
         * Parent Item Multi Images.
         */
        $multiImageGoodImages = $multiImages[$associatedSku] ?? [];
        /**
         * Variant Multi Images
         */
        $variantData = $data['variants'];
        $variantMultiImages = [];
        if (!empty($variantData)) {
            foreach ($variantData as $data) {
                $variantSku = $data['sku'];
                /**
                 * If parent associated sku and variant sku are identical it means the default variant.
                 * Ideally in case of default variant all parent sku images are replicated to the variant.
                 * In such a scenerio skip the variant images.
                 */
                if (isset($data['images'])) {
                    foreach ($data['images'] as $name => $value) {
                        if (!isset($value['deleted'])) {
                            if (isset($value['data_url'])) {
                                $variantMultiImages[$variantSku][]['image_url'] = uploadImageFile($value['data_url'], session('shop'));
                            } else {
                                $variantMultiImages[$variantSku][]['image_url'] = $value['image_url'];
                            }
                        }
                    }
                }
            }
        }

        /**
         * Merging parent item multi images and variant multi images
         * as we can't call updateGoodsMultiImages for variants as Qoo10 will replace
         * the parent item multi images or previously added images.
         * Instead we merge both parent items multi images and variant multi images.
         */
        /*$multiImageSkuCountMapping = [];
        if (!empty($multiImages) && !empty($variantMultiImages)) {
            $multiImageSkuCountMapping[$associatedSku] = count($multiImages[$associatedSku]);
            foreach ($variantMultiImages as $variantSku => $variantImage) {
                $multiImageSkuCountMapping[$variantSku] = count($variantImage);
                $multiImageGoodImages = array_merge($multiImageGoodImages,$variantImage);
            }
        }*/

        $multiImageSkuCountMapping = [];

        if (!empty($multiImages)) {
            $multiImageSkuCountMapping[$associatedSku] = ['count' => count($multiImages[$associatedSku]), 'images' => $multiImages[$associatedSku]];
        }
        if (!empty($variantMultiImages)) {
            foreach ($variantMultiImages as $variantSku => $variantImage) {
                $multiImageSkuCountMapping[$variantSku] = ['count' => count($variantImage), 'images' => $variantImage];
            }
        }

        // update other images (max 12 images)
        $response = $this->updateGoodsMultiImages($externalId, $multiImageGoodImages);

        if (!empty($response)) {
            $errors[] = $response;
        }

        $data['short_description'] = $short_description;
        $this->get(null, true, $externalId, ['multiImageSkuCountMapping' => $multiImageSkuCountMapping], $data);
        return $errors;
    }

    /**
     * Get all categories (3rd level)
     *
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCategories()
    {
        $response = $this->client->request('POST', 'CommonInfoLookup.GetCatagoryListAll', [
            'lang_cd' => 'en'
        ]);

        if ($response['ResultCode'] === 0) {
            return $response['ResultObject'];
        }

        return null;
    }

    /**
     * Get all products (if use default parameter)
     * Get selected status and page products (with custom parameter)
     *
     * @param int $page
     * @param string[] $statuses
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProducts($page = 1, $statuses = ['S2', 'S1'])
    {
        $products = [];
        foreach ($statuses as $itemStatus) {
            $response = $this->client->request('POST', 'ItemsLookup.GetAllGoodsInfo', [
                'ItemStatus' => $itemStatus, // S0 - Under Review, S1 - On Queue, S2 - Available, S3 - Suspended, S4 - Deleted
                'Page' => $page
            ]);

            if ($response['ResultCode'] === 0) {
                $products = array_merge($products, $response['ResultObject']['Items'] ?? []);

                if ($response['ResultObject']['TotalPages'] > $page) {
                    $products = array_merge($products, $this->getProducts(++$page, [$itemStatus]));
                }
            }
        }
        return $products;
    }

    /**
     * Get product detail
     *
     * @param $externalId
     * @param null $sku // $sku will be ignore if $externalId exist
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getProduct($externalId = null, $sku = null)
    {
        if (is_null($externalId)) {
            $params = ['SellerCode' => $sku];
        } else {
            $params = ['SellerCode' => $sku, 'ItemCode' => $externalId];
        }
        $response = $this->client->request('POST', 'ItemsLookup.GetItemDetailInfo', $params);

        if ($response['ResultCode'] === 0) {
            return $response['ResultObject'][0];
        } else {
            set_log_extra('external_id', $externalId);
            set_log_extra('sku', $sku);
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
    }

    /**
     * Get product's variants
     *
     * @param $externalId
     * @param null $sku // $sku will be ignore if $externalId exist
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getVariants($externalId, $sku = null)
    {
        if (!empty($sku)) {
            $params = [
                'SellerCode' => $sku
            ];
        } else {
            $params = [
                'ItemCode' => $externalId
            ];
        }
        $response = $this->client->request('POST', 'ItemsLookup.GetGoodsInventoryInfo', $params);

        if ($response['ResultCode'] === 0) {
            return $response['ResultObject'];
        } else {
            set_log_extra('external_id', $externalId);
            set_log_extra('sku', $sku);
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
    }

    /**
     * Get product's options
     *
     * @param $externalId
     * @param null $sku // $sku will be ignore if $externalId exist
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getOptions($externalId, $sku = null)
    {
        $response = $this->client->request('POST', 'ItemsLookup.GetGoodsOptionInfo', [
            'ItemCode' => $externalId,
            'SellerCode' => $sku
        ]);

        if ($response['ResultCode'] === 0) {
            return $response['ResultObject'];
        } else {
            set_log_extra('external_id', $externalId);
            set_log_extra('sku', $sku);
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
    }

    /**
     * Get product's options
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getLogistics()
    {
        $response = $this->client->request('GET', '', [
            'method' => 'ItemsLookup.GetSellerDeliveryGroupInfo',
        ]);

        if ($response['ResultCode'] === 0) {
            return $response['ResultObject'];
        } else {
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
    }

    /**
     * @param $externalId
     * @param $sku
     * @param $stock
     * @param string|null $price
     * @param string|null $expireDate
     *
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateGoodsPriceQty($externalId, $sku, $stock, $price = null, $expireDate = null)
    {
        if (!empty($sku)) {
            $body = [
                'SellerCode' => $sku,
                'Qty' => $stock,
                'ItemCode' => $externalId,
            ];
        } else {
            $body = [
                'ItemCode' => $externalId,
                'Qty' => $stock,
            ];
        }

        if (is_numeric($price)) {
            $body['Price'] = strval($price);
        }
        if (!empty($expireDate)) {
            $body['ExpireDate'] = $expireDate;
        }

        $response = $this->client->request('POST', 'ItemsOrder.SetGoodsPriceQty', $body);

        if ($response['ResultCode'] !== 0) {
            return $response['ResultMsg'];
        }

        return null;
    }

    /**
     * Update main product 1st image
     *
     * @param $externalId
     * @param string $imageUrl
     *
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateGoodsImage($externalId, string $imageUrl)
    {
        $response = $this->client->request('POST', 'ItemsContents.EditGoodsImage', [
            'ItemCode' => $externalId,
            'StandardImage' => $imageUrl,
        ]);

        if ($response['ResultCode'] !== 0) {
            return $response['ResultMsg'];
        }

        return null;
    }

    /**
     * Update main product extra images
     *
     * @param $externalId
     * @param array $images
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateGoodsMultiImages($externalId, array $images)
    {
        $body = [
            'ItemCode' => $externalId,
        ];

        foreach ($images as $imageIndex => $image) {
            if ($imageIndex < 11) {
                $body['EnlargedImage' . ($imageIndex + 1)] = $image['image_url'];
            }
        }

        $response = $this->client->request('POST', 'ItemsContents.EditGoodsMultiImage', $body);

        if ($response['ResultCode'] !== 0) {
            return $response['ResultMsg'];
        }

        return null;
    }

    /**
     * Update variants
     *
     * @param $externalId
     * @param string $variantsString
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateVariants($externalId, string $variantsString)
    {
        $errors = [];
        $response = $this->client->request('POST', 'ItemsOptions.EditGoodsInventory', [
            'ItemCode' => $externalId,
            'InventoryInfo' => $variantsString,
        ]);

        if ($response['ResultCode'] !== 0) {
            $errors[] = $response['ResultMsg'];
        }

        return $errors;
    }

    public function retrieveBrands()
    {
        $response = $this->client->request('POST', 'CommonInfoLookup.SearchBrand', [
            'keyword' => ''
        ]);
        if (isset($response['ErrorCode']) || $response['ResultCode'] != 0) {
            set_log_extra('response', $response);
            throw new \Exception('Unable to get brands');
        }
        $brands = $response['ResultObject'];
        foreach ($brands as $key => $brand) {
            $brands[$key] = [
                'external_id' => $brand['M_B_NO'],
                'name' => $brand['M_B_NM'],
            ];
        }
        return $brands;
    }

    /**
     * Insert option information of the item already registered to Qoo10
     * @param $externalId
     * @param $associatedSku
     * @param array $variantsData
     * @param array $options
     */
    public function insertInventoryDataUnit($externalId, $associatedSku, array $variantsData, ?array $options)
    {
        if (!empty($options)) {
            $inventoryDataUnit = [];
            foreach ($variantsData as $variantData) {
                for ($i = 0; $i < 3; $i++) {
                    if (isset($options[$i])  && !empty($variantData['option_' . ($i + 1)])) {
                        $optionsValue = $variantData['option_' . ($i + 1)];
                        $price = null;
                        foreach ($variantData['prices'] as $priceData) {
                            if ($priceData['type'] === ProductPriceType::SELLING()->getValue()) {
                                $price = $priceData['price'];
                            }
                        }
                        $inventoryDataUnit[] = [
                            'ItemCode' => $externalId,
                            'SellerCode' => $associatedSku,
                            'OptionName' => $options[$i],
                            'OptionValue' => $optionsValue,
                            'OptionCode' => $variantData['sku'],
                            'Price' => $price,
                            'Qty' => $variantData['stock']
                        ];
                    }
                }
            }

            if (!empty($inventoryDataUnit)) {
                foreach ($inventoryDataUnit as $data) {
                    $response = $this->client->request('GET', 'ItemsOptions.InsertInventoryDataUnit', $data);
                    if ($response['ResultCode'] !== 0) {
                        set_log_extra('qoo10_insert_variant', $response);
                    }
                }
            }
        }
        return null;
    }
    /**
     * Update variant stock
     * @param string $associatedSku
     * @param string $sku
     * @param int $stock
     * @param string $optionValue
     * @param string $optionName
     * $param string $externalId
     */
    public function updateInventoryQtyUnit($associatedSku, $sku, $stock, $optionValue = '', $optionName = '', $externalId = '')
    {
        $response = $this->client->request('GET', 'ItemsOptions.UpdateInventoryQtyUnit', [
            'ItemCode' => $externalId,
            'SellerCode' => $associatedSku,
            'OptionCode' => $sku,
            'OptionName' => $optionName,
            'OptionValue' => $optionValue,
            'Qty' => strval($stock)
        ]);
        if ($response['ResultCode'] !== 0) {
            set_log_extra('response', $response);
            $alertMessage = 'Face error when call api UpdateInventoryQtyUnit in Qoo10 with error  ' . $response['ResultMsg'] . ' Account Id: '.$this->account->id.' Shop Id: '.$this->account->shop_id.' Integration Id: '.$this->account->integration_id. ' Item code: ' . $externalId . ' and SellerCode: ' . $associatedSku;
            Log::error($alertMessage);
            //throw new \Exception($response['ResultMsg']);
        }
        return null;
    }

    /**
     * Returns the Account Attributes
     *
     * @return array
     */
    public function getIntegrationAttributes()
    {
        $attributes = $this->retrieveAttributes();

        if ($brandKey = array_search('brand_no', array_column($attributes, 'name'))) {
            // Retrieve qoo10 brands
            $brands = Brand::whereIntegrationId(Integration::QOO10)->whereRegionId($this->account->region_id)->active()->pluck('name')->toArray();
            $attributes[$brandKey]['data'] = $brands;
        }

        return $attributes;
    }

    /**
     * Delete qoo10 product listing.
     *
     * @param $externalId
     * @param $associatedSku
     * @return |null
     * @throws \Exception
     */
    public function deleteProduct($externalId, $associatedSku)
    {
        $response = $this->client->request('GET', 'ItemsBasic.EditGoodsStatus', [
            'ItemCode' => $externalId,
            'SellerCode' => $associatedSku,
            'Status' => 3 // (On queue = 1, Transaction available = 2, Transaction discontinued = 3)
        ]);

        if ($response['ResultCode'] !== 0) {
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
        return null;
    }

    /**
     * Get Average Color
     * @param string $bin
     * @param int $size
     */
    public function getAvgColor($bin, $size = 10)
    {
        $target = imagecreatetruecolor($size, $size);
        $source = imagecreatefromstring($bin);
        imagecopyresized($target, $source, 0, 0, 0, 0, $size, $size, imagesx($source), imagesy($source));
        $r = $g = $b = 0;
        foreach (range(0, $size - 1) as $x) {
            foreach (range(0, $size - 1) as $y) {
                $rgb = imagecolorat($target, $x, $y);
                $r += $rgb >> 16;
                $g += $rgb >> 8 & 255;
                $b += $rgb & 255;
            }
        }
        unset($source, $target);
        return (floor($r / $size ** 2) << 16) +  (floor($g / $size ** 2) << 8)  + floor($b / $size ** 2);
    }

    /**
     * Compare average Color with an image tolerance
     * @param double $c1
     * @param double $c2
     * @param int $tolerance
     * Acceptable range +- from colour.  0 (trim only exact colour) to 255 (trim all colours).
     */
    public function compAvgColor($c1, $c2, $tolerance = 4)
    {
        return abs(($c1 >> 16) - ($c2 >> 16)) <= $tolerance &&
            abs(($c1 >> 8 & 255) - ($c2 >> 8 & 255)) <= $tolerance &&
            abs(($c1 & 255) - ($c2 & 255)) <= $tolerance;
    }
}
