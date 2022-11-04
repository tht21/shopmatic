<?php

namespace App\Integrations\Qoo10Legacy;

use App\Constants\AccountStatus;
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
use App\Models\IntegrationCategory;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductImportTask;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\Facades\Image;
use App\Constants\ProductAlertType;
use App\Events\NewProductAlert;
use App\Models\ProductInventory;

class ProductAdapter extends AbstractProductAdapter
{

    // Shipping Rate/Form > Shipping Rate/Form > Shipping Rate Details > Type
    protected $deliveryFeeType = [
        'W' => 'Store Pickup',
        'F' => 'Charge',
        'X' => 'Free',
        'M' => 'Free On Condition',
    ];

    // Shipping Rate/Form > Shipping Rate/Form > Shipping Rate Details > Shipping Method
    protected $shippingMethod = [
        'NO' => 'Non-registered Mail',
        'RM' => 'Standard',
        'EX' => 'Express(DHL,EMS,Fedex,etc.)',
    ];

    // Shipping Rate/Form > Shipping Rate/Form > Shipping Rate Details
    protected $surchargesType = [
        // Weight/Quantity
        'delivery_sub_type' => [
            'w_repeat' => 'by weight',
            'qty_repeat' => 'by quantity',
        ],
        // Region
        'sz_divide_nm' => [
            '4 Division' => '4 Division',
            '6 Division' => '6 Division',
        ],
        // Oversea Shipping
        'oversea_type' => [
            'G' => 'oversea',
//            'B' => false, // B - not support oversea delivery
        ],
    ];

    /**
     * Retrieve categories
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        try {
            $categories = $this->parseCategories();
            // clean echo output buffer if retrieveCategories called from job
            if (ob_get_contents()) ob_end_clean();

            return $categories;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * get formatted categories list
     *
     * @param null $parent
     * @param string $parentBreadcrumb
     * @return array
     */
    public function parseCategories($parent = null, $parentBreadcrumb = '')
    {
        $categories = [];
        $stepName = null;
        $parentExternalId = null;
        if (is_null($parent)) {
            $stepName = 'LC';
        } else {
            // 1st to 3rd level category external id - gdlc_cd/gdmc_cd/gdsc_cd
            $parentExternalId = $parent['gdlc_cd'] ?? $parent['gdmc_cd'] ?? $parent['gdsc_cd'];

            if ($parentExternalId[0] === '1') {
                $stepName = 'MC';
            } elseif ($parentExternalId[0] === '2') {
                $stepName = 'SC';
            }
        }

        if (!is_null($stepName)) {
            if (is_null($parent)) {
                $rawCategories = $this->getCategories();
            } else {
                $rawCategories = $this->getCategories($stepName, $parentExternalId);
            }

            foreach ($rawCategories as $key => $category) {
                // 1st to 3rd level category external id - gdlc_cd/gdmc_cd/gdsc_cd
                $externalId = $category['gdlc_cd'] ?? $category['gdmc_cd'] ?? $category['gdsc_cd'];
                // 1st to 3rd level category name - gdlc_nm/gdmc_nm/gdsc_nm
                $name = $category['gdlc_nm'] ?? $category['gdmc_nm'] ?? $category['gdsc_nm'];

                // build current category's breadcrumb
                $breadcrumb = $parentBreadcrumb;
                if ($breadcrumb !== '') $breadcrumb .= ' > ';
                $breadcrumb .= $name;
                echo $breadcrumb . "\n";

                // get current category's sub categories list
                $children = $this->parseCategories($category, $breadcrumb);

                $categories[$key] = [
                    'name'          => $name,
                    'breadcrumb'    => $breadcrumb,
                    'external_id'   => $externalId,
                    'is_leaf'       => (int)empty($children),
                    'children'      => $children
                ];
            }
        }
        return $categories;
    }

    /**
     * @inheritDoc
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {
        return [];
    }

    /**
     * Import products to Combinesell
     *
     * @param ProductImportTask|null $importTask
     * @param array $config
     * @return mixed|void
     * @throws \Exception
     */
    public function import($importTask, $config)
    {

        $products = $this->getProducts();
        if (is_array($products)) {
            $importTask->total_products = count($products);
            $importTask->save();
            foreach ($products as $product) {
                try {
                    $product = $this->transformProduct($product);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }
                $this->handleProduct($product, $config);
            }
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }
    }

    /**
     * Retrieves a single product
     *
     * @param ProductListing $listing
     * @param bool $update Whether or not to update the product if it already exists
     *
     * @param null $itemId
     * @return Product|void|null
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
            $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        }

        // We can't retrieve the product by the variant. We need to retrieve via the main product
        $products = $this->getProducts($externalId);
        if (is_array($products) && count($products) > 0 && array_key_exists('gd_no', reset($products))) {
            try {
                $product = $this->transformProduct(reset($products));
            } catch (\Exception $e) {
                set_log_extra('product', reset($products));
                throw $e;
            }

            return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
        } else {
            set_log_extra('listing', $listing);
            set_log_extra('response', $products);
            throw new \Exception('Unable to retrieve products for Qoo10');
        }
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

        $products = $this->getProducts();
        if (is_array($products) && count($products) > 0 && array_key_exists('gd_no', reset($products))) {
            try {
                foreach ($products as $product) {
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
     * @inheritDoc
     * @throws \Exception
     */
    public function update(ProductListing $product, array $data)
    {
        /* Format Data - START */

        // External Id
        $externalId = $data['identifiers']['external_id'] ?? $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());

        // Category (qoo10 need parent category external id)
        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = IntegrationCategory::where([
            'integration_id' => $this->account->integration_id,
            'region_id' => $this->account->region_id,
            'external_id' => $data['category']['external_id'],
        ])->first();
        $data['category'] = [
            $integrationCategory->parent->parent->external_id,
            $integrationCategory->parent->external_id,
            $integrationCategory->external_id,
        ];

        // Attributes (flatten value of attribute)
        foreach ($data['attributes'] as $attributeName => $attribute) {
            if (array_key_exists('value', $attribute) && is_array($attribute['value']) && array_key_exists('value', $attribute['value'])) {
                $data['attributes'][$attributeName]['value'] = $attribute['value']['value'];
            }
        }

        // Logistic
        // decode logistic json string
        if (array_key_exists('logistics', $data['attributes'])) {
            $data['attributes']['logistics'] = json_decode($data['attributes']['logistics'], true)[0];
        } elseif (array_key_exists('logistics', $data)) {
            $data['attributes']['logistics'] = json_decode($data['logistics']['value'], true)[0];
        } else {
            return $this->respondWithError('Logistic is required.');
        }

        // Returned Address
        // decode location json string
        if (array_key_exists('locations', $data['attributes'])) {
            $data['attributes']['locations'] = json_decode($data['attributes']['locations'], true);
        } elseif (array_key_exists('locations', $data)) {
            $data['attributes']['locations'] = $data['locations'];
        } else {
            return $this->respondWithError('Location is required.');
        }

        // Prices (filter duplicate type price)
        $prices = [];
        foreach (Constant::PRICES(true) as $priceType) {
            foreach ($data['prices'] as $price) {
                if ($price['type'] === $priceType) {
                    $prices[$priceType] = $price['price'];
                    break;
                }
            }
        }
        $data['prices'] = $prices;

        // Images
        $images = [];
        $imageUrlIndex = 0;
        foreach ($data['images'] ?? [] as $imageIndex => $image) {
            // remove deleted image
            if (array_key_exists('deleted', $image) && $image['deleted']) {
                unset($data['images'][$imageIndex]);
                continue;
            }

            // get image url
            if (array_key_exists('data_url', $image)) {
                $imageUrl = uploadImageFile($image['data_url'], session('shop'));
            } elseif (array_key_exists('image_url', $image)) {
                $imageUrl = $image['image_url'];
            } else {
                continue;
            }

            // populate qoo10 images data
            if (!array_key_exists('main', $images)) {
                // treat first image as product's main image
                $images['main'] = [
                    '1' => $this->uploadImage($imageUrl, '1'),
                    '0' => $this->uploadImage($imageUrl, '0'),
                    'B' => $this->uploadImage($imageUrl, 'B'),
                ];
                $images['idx'] = '';
                $images['url'] = '';
            } else {
                // treat other images as enlarge images
                $images['idx'] .= sprintf("%02d", $imageUrlIndex) . '||';
                $images['url'] .= $this->uploadImage($imageUrl, 'P') . '||';
                $imageUrlIndex++;
            }
        }
        $data['images'] = $images;

        // Search Keywords
        $data['search'] = '';
        $search = preg_split('/\s+/', \Str::upper($data['attributes']['name']['value'] ?? $data['name']));
        $data['search'] = implode('!@#', array_unique(array_filter($search, function ($item) {
            return preg_match("/^[A-Z]/", $item);
        })));

        // Qoo10's Inventory No (extract it from product listing data)
//        $data['inventory_no'] = '';

        // Variants
        foreach ($data['variants'] as $variantId => $variant) {
            foreach ($variant['images'] ?? [] as $imageIndex => $image) {
                if (array_key_exists('deleted', $image) && $image['deleted']) {
                    unset($data['variants'][$variantId]['images'][$imageIndex]);
                } elseif (array_key_exists('data_url', $image)) {
                    // convert base64 data to image url
                    $data['variants'][$variantId]['images'][$imageIndex] = ['image_url' => uploadImageFile($image['data_url'], session('shop'))];
                }
            }
        }

        // first variant
        $firstVariant = reset($data['variants']);

        /* Format Data - END */

        /* Update - START */
        $error = [];

        // update main product (exclude description, image, price and stock)
        $response = $this->updateProduct($externalId, $data);

        if (!empty($response)) {
            $error[] = $response;
        }

        // get product basic data
//        $response = $this->getProductBasicInfo($externalId);
//        if (array_key_exists('gd_nm', $response)) {
//            $data['images']['link_type'] = $response['link_type'] ?? 'N';
//            $data['images']['dh_contents_no'] = $response['dh_contents_no'] ?? '';
//        }

        // update description and image
        $response = $this->updateDescriptionAndImage($externalId, $data);

        if (!empty($response)) {
            $error[] = $response;
        }

        // Update the product main image url.
        if (!empty($data['images']) && !empty($data['images']['main'])) {
            $imageUrl = $data['images']['main'][0];
            if (!empty($imageUrl) && ($imageUrl != $product->product->main_image)) {
                $product->product->update(['main_image' => $imageUrl]);
            }
        }

        // update quantity and price
        $priceAndQuantityData = $this->getPriceAndQuantity($externalId);
        if (is_array($priceAndQuantityData)) {
            $sellingPrice = $data['prices'][ProductPriceType::SELLING()->getValue()];

            if (isset($sellingPrice)) {
                // commission rate needed to calculate settle price
                $commissionRate = $this->getCommissionRate('', $sellingPrice, $data['category'], $data['attributes']['delivery_type']['value'] ?? 'BI');

                if (is_numeric($commissionRate)) {
                    $settlePrice = round(((100-$commissionRate)/100)*$sellingPrice,2);

                    // update available period, use new available period set by user
                    try {
                        if (!empty($data['attributes']['available_period']['value']) && Carbon::createFromFormat('Y-m-d H:i:s', $data['attributes']['available_period']['value']) !== FALSE) {
                            $priceAndQuantityData['expire_dt'] = $data['attributes']['available_period']['value'];
                        }
                    } catch (\InvalidArgumentException $exception) {
                        // $data['attributes']['available_period']['value'] is not a valid date string
                    }

                    $response = $this->updatePriceAndQuantity($externalId, $priceAndQuantityData, $sellingPrice, $settlePrice, $firstVariant['inventory']['stock']);

                    if (!empty($response)) {
                        $response = ($response['ResultMsg']) ?? $response;
                        $error[] = $response;
                    }
                } else {
                    $error[] = 'Unable to calculate Settle Price. ';
                }
            } else {
                $error[] = 'Missing Selling Price, unable to calculate Settle Price. ';
            }
        }

        // update variants
        $variantsData = [
            'identifiers' => ['external_id' => $externalId],
            'sellingPrice' => $data['prices'][ProductPriceType::SELLING()->getValue()],
            'variants' => $data['variants']
        ];

        if (isset($data['attributes']['option_order']) && in_array($data['attributes']['option_order']['value'], ['S', 'P', 'N'])) {
            $variantsData['option_order'] = $data['attributes']['option_order']['value'];
        }

        $response = $this->updateVariants($product, $variantsData);
        $error = array_merge($error, $response);

        /* Update - END */

        if (count($error) > 0) {
            return $this->respondWithError($error);
        }

        $this->get($product, true);

        return $this->respond();
    }

    /**
     * Update variants
     * 1. option sorting order
     * 2. inventory and image
     * 3. option group
     *
     * @param ProductListing $productListing
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function updateVariants(ProductListing $productListing, $data)
    {
        // External Id
        $externalId = $data['identifiers']['external_id'] ?? $productListing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        $error = [];

        // update qoo10 variants sorting order type
        if (isset($data['option_order'])) {
            $response = $this->updateOptionSortingType($externalId, $data['option_order']);
            if (!empty($response)) {
                $error[] = $response;
            }
        }

        // inventory_no is needed to update variants
        if (!isset($data['inventory_no'])) {
            $inventory = $this->getInventory($externalId);

            if (!isset($inventory['inventory_no'])) {
                return array_merge($error, ['Can\'t get inventory_no, update variant fail.']);
            }

            $data['inventory_no'] = trim($inventory['inventory_no']);

            if (empty($data['inventory_no'])) {
                $data['inventory_no'] = "ST$externalId";
            }
        }

        // get Q-inventory data
        foreach ($productListing->listing_variants as $variant) {
            $variantListingData = $variant->data->raw_data;
            // make sure there's data, non matrix probably doesnt has any data
            if (is_null($variantListingData)) {
                continue;
            }

            if (array_key_exists('gd_cd', $variantListingData)) {
                $data['variants'][$variant->product_variant_id]['gd_cd'] = $variantListingData['gd_cd'];
            }
            if (array_key_exists('seat_cl', $variantListingData)) {
                $data['variants'][$variant->product_variant_id]['seat_cl'] = $variantListingData['seat_cl'];
            }
        }

        // upload variant image with option's group API or variants update API
        $uploadImageToOptionGroup = count($productListing->product->options) <= 1;

        // get variants string
        $variantsString = $this->getVariantsString($productListing->product, $data['sellingPrice'], $data['variants'], !$uploadImageToOptionGroup, $this->account->id);

        // update variants stock and image
        $response = $this->updateVariantsStockAndImage($externalId, $data['inventory_no'], $variantsString);
        if (!empty($response)) {
            $error[] = $response;
        }

        // get variants' options string
        $optionsString = $this->getOptionsString($productListing->product, $data['variants'], $uploadImageToOptionGroup);

        // update variants option group
        $response = $this->updateVariantsOptionGroup($externalId, $data['inventory_no'], $optionsString);
        if (!empty($response)) {
            $error[] = $response;
        }

        return $error;
    }

    /**
     * Update stock to qoo10
     * NOTE: This should force an update of the listing after updating (Not updated locally prior to actual push)
     *
     * @param ProductListing $listing
     * @param $stock
     * @return bool
     * @throws \Exception
     */
    public function updateStock(ProductListing $listing, $stock, ?ProductInventory $productInventory = null)
    {
        // check if listing is single product
        if (!is_null($listing->product_variant_id)) {
            // get main listing
            $mainListing = $listing->listing;
            // get main listing external id
            $mainExternalId = $mainListing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            // variant's external id
            $variantExternalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            // if they are the same, this is not variant
            if ($mainExternalId == $variantExternalId) {
                // switch listing to main listing
                $listing  = $mainListing;
            }
        }

        if (!is_null($listing->product_variant_id)) {
            // get main listing
            $mainListing = $listing->listing;

            // get main listing external id
            $mainExternalId = $mainListing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            // variant's external id
            $variantExternalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            // get raw variant data from qoo10
            try {
                $variantsData = $this->getVariantsRaw($mainExternalId);
            } catch (\Exception $exception) {
                set_log_extra('listing', $listing);
                set_log_extra('mainListing', $mainListing);
                throw $exception;
            }
            if (count($variantsData) > 0) {
                $found = false;
                foreach ($variantsData as $index => $variantData) {
                    // update stock when variant is found
                    if ($variantData['seq_code'] == $variantExternalId) {
                        $variantsData[$index]['quantity'] = $stock;
                        $found = true;

                    // use back old stock if it is not selected variant
                    } else {
                        $variantsData[$index]['quantity'] = $variantData['inventory_cnt'];
                        // if want use q-inventory's stock, change to this
//                    $variantsData[$index]['quantity'] = empty($variantData['gd_cd']) ? $variantData['inventory_cnt'] : $variantData['integration_qty'];
                    }
                }

//                if (!$found) {
//                    set_log_extra('listing', $listing);
//                    set_log_extra('mainListing', $mainListing);
//                    set_log_extra('variantsData', $variantsData);
//                    throw new \Exception('Error updating Qoo10 variant quantity. Variant with sku ' . $listing->getIdentifier(ProductIdentifier::SKU()) . ' not found.');
//                }

                // update stock
                $response = $this->updateVariantsStockAndImage($mainExternalId, $variantsData[0]['inventory_no'], $this->generateVariantsString($variantsData));

                if (!empty($response)) {
                    throw new \Exception($response);
                }

            } else {
                set_log_extra('listing', $listing);
                set_log_extra('mainListing', $mainListing);
                set_log_extra('variantsData', $variantsData);
                $message = 'Stock update of main inventory is successfull.But updating Qoo10 variant quantity failed.Variant with sku '.$listing->variant->sku.' is not found.';
                event(new NewProductAlert($listing->product, $message, ProductAlertType::WARNING()));
                throw new \Exception($message);
            }

        } else {
            // get listing external id
            $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            // get raw price and quantity data from qoo10
            $priceAndQuantityData = $this->getPriceAndQuantity($externalId);

            if (is_array($priceAndQuantityData) && count($priceAndQuantityData) > 0) {

                // update stock only with updatePriceAndQuantity()
                $response = $this->updatePriceAndQuantity($externalId, $priceAndQuantityData, null, null, $stock);

                if (!empty($response)) {
                    set_log_extra('listing', $listing);
                    set_log_extra('externalId', $externalId);
                    set_log_extra('priceAndQuantityData', $priceAndQuantityData);
                    throw new \Exception($response['ResultMsg'] ?? 'Unable to update price and quantity');
                }

            } else {
                set_log_extra('listing', $listing);
                set_log_extra('priceAndQuantityData', $priceAndQuantityData);
                throw new \Exception('Error updating Qoo10 product quantity. Product with external id ' . $externalId . ' not found.');
            }

        }
        $this->get($listing);

        return true;
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
        $this->errors = [];

        parent::canCreate($product);

        /* location validation */
        $locations = $product->attributes->where('product_variant_id', null)->where('name', 'locations')->first();
        if (!isset($locations) || empty($locations->value)/*|| (is_array($logistic->value && empty($logistic->value))) || (!is_array($logistic->value) && count(json_decode($logistic->value)) <= 0)*/) {
            $this->errors[] = 'Locations is required, please choose a locations';
        }

        // Stock
        if ($product->variants) {
            foreach ($product->variants as $variant) {
                if (isset($variant->inventory) && $variant->inventory->stock <= 0) {
                    $this->errors[] = 'Stock must be more than 0';
                }
            }
        }

        if (count($this->errors) > 0) {
            return $this->respondWithError($this->errors);
        } else {
            return $this->respond(null);
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function create(Product $product)
    {
        // pre-load required relation data
        $this->preLoadProductData($product);

        // map attributes data to array with name as key
        $attributes = $product->attributes->where('product_variant_id', null)->mapWithKeys(function ($item) {
            return [$item['name'] => $item['value']];
        })->toArray();

        // Logistic
        // decode logistic json string
        if (array_key_exists('logistics', $attributes)) {
            $attributes['logistics'] = json_decode($attributes['logistics'], true);
            if (isset($attributes['logistics'][0])) {
                $attributes['logistics'] = $attributes['logistics'][0];
            } else {
                return $this->respondWithError('Logistic is required.');
            }
        } else {
            return $this->respondWithError('Logistic is required.');
        }

        // Returned Address
        // decode location json string
        if (array_key_exists('locations', $attributes)) {
            $attributes['locations'] = json_decode($attributes['locations'], true);
        } else {
            return $this->respondWithError('Location is required.');
        }

        $data = [];

        // Integration Category
        if (array_key_exists('integration_category_id', $attributes)) {
            /** @var IntegrationCategory $integrationCategory */
            $integrationCategory = IntegrationCategory::find($attributes['integration_category_id']);
        } else {
            $integrationCategory = $product->category->integrationCategories->first();
        }
        $data['category'] = [
            $integrationCategory->parent->parent->external_id,
            $integrationCategory->parent->external_id,
            $integrationCategory->external_id,
        ];

        // Prices
        $account = $this->account;
        $data['prices'] = $product->prices()->where(function (Builder $query) use ($account, $product) {
            $query->whereProductId($product->id)->whereNull('product_variant_id')->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
        })->orWhere(function (Builder $query) use ($account, $product) {
            $query->whereProductId($product->id)->whereNull('product_variant_id')->whereNull('region_id')->whereNull('integration_id');
        })
        ->orderBy('integration_id', 'asc')
        ->orderBy('region_id', 'asc')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item['type'] => $item];
        })->toArray();
        $sellingPrice = $data['prices'][ProductPriceType::SELLING()->getValue()] ?? null;
        // Calculate Settle Price
        $data['prices']['settle'] = '0';
        $commissionRate = $this->getCommissionRate('', $sellingPrice, $data['category'], $data['delivery_type'] ?? 'BI');
        if (isset($sellingPrice) && is_numeric($commissionRate)) {
            $data['prices']['settle'] = round(((100-$commissionRate)/100)*$sellingPrice,2);
        }

        // Stock
        /** @var ProductVariant $firstVariant */
        if ($firstVariant = $product->variants->first()) {
            $data['stock'] = $firstVariant->inventory->stock > 0 ? $firstVariant->inventory->stock : 1;

            // use first variant weight if goods_weight not found
            if (!array_key_exists('goods_weight', $attributes)) {
                $data['goods_weight'] = $firstVariant->weight;
            }
        }

        // Images
        $data['images'] = [
            'main' => [],
            'idx' => '',
            'url' => '',
        ];
        $imagesUrl = $product->allImages->map(function ($item) {
            return $item->image_url;
        })->toArray();

        if (count($imagesUrl)) {
            foreach ($imagesUrl as $imageUrlIndex => $imageUrl) {
                if ($imageUrlIndex === 0) {
                    // treat first image as product's main image
                    $data['images']['main'] = [
                        '1' => $this->uploadImage($imageUrl, '1'),
                        '0' => $this->uploadImage($imageUrl, '0'),
                        'B' => $this->uploadImage($imageUrl, 'B'),
                    ];
                } else {
                    // treat other images as enlarge images
                    $data['images']['idx'] .= sprintf("%02d", $imageUrlIndex - 1) . '||';
                    $data['images']['url'] .= $this->uploadImage($imageUrl, 'P') . '||';
                }
            }
        } else {
            return $this->respondWithError('Main product image is required.');
        }

        // Variants
        $data['variants'] = [
            'data' => '',
            'options' => '',
        ];

        if (!is_null($product->options) && count($product->options)) {
            // upload variant image with option's group API or variants update API
            $uploadImageToOptionGroup = count($product->options) <= 1;

            $data['variants']['data'] = $this->getVariantsString($product, $sellingPrice, [], !$uploadImageToOptionGroup);
            $data['variants']['options'] = $this->getOptionsString($product, [], $uploadImageToOptionGroup, true);
        }

        // Search Keywords
        $data['search'] = '';
        $search = preg_split('/\s+/', \Str::upper($attributes['name'] ?? $product->name));
        $data['search'] = implode('!@#', array_unique(array_filter($search, function ($item) {
            return preg_match("/^[A-Z]/", $item);
        })));

        // create qoo10 listing
        $response = $this->createProduct($product, array_merge($data, $attributes));

        // Fail
        if (isset($response[0]) && array_key_exists('value', $response[0])) {
            if (!\Str::contains($response[0]['value'], 'is saved successfully')) {
                return $this->respondWithError($response[0]['value']);
            }
        } else {
            return $this->respondWithError('There\'s an unexpected error while trying to create the product at qoo10.');
        }

        // Success
        try {
            $transformedProduct = $this->transformProduct([
                'gd_no' => $response[1]['value'] ?? str_replace('Item code [', '', str_replace('] is saved successfully.', '', $response[0]['value'])),
                'order_amt' => $data['stock'],
                'outer_gd_no' => $product->associated_sku,
                'stat_dbcode' => 'S0',
                'gd_weight' => strval($attributes['goods_weight'] ?? $data['weight'] ?? ''),
            ]);
        } catch (\Exception $e) {
            set_log_extra('product', $product);
            set_log_extra('create product response', $response);
            throw $e;
        }
        $product = $this->handleProduct($transformedProduct, ['update' => true, 'new' => true]);

        if ($product instanceof Product) {
            $product = $product->toArray();
        } else {
            $product = null;
        }
        return $this->respondCreated($product);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function delete(ProductListing $listing)
    {
        if ($listing->getIdentifier(ProductIdentifier::EXTERNAL_ID())) {
            $externalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

            $response = $this->deleteProduct($externalId, true);

            if (!empty($response)) {
                return $this->respondWithError($response);
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product item id not found');
    }

    /**
     * transform product
     *
     * @param $product
     * @param null|array $attributesData
     * @return TransformedProduct
     * @throws \Exception
     */
    public function transformProduct($product, $attributesData = null)
    {
        // used to map qoo10 status to combinesell status
        $qoo10Status = [
            'S0' => ProductStatus::DRAFT(), // Under Review
            'S1' => ProductStatus::DISABLED(), // On Queue
            'S2' => ProductStatus::LIVE(), // Available
            'S3' => ProductStatus::DISABLED(), // Suspended
            'S4' => ProductStatus::DISABLED(), // Restricted
            'S8' => ProductStatus::DISABLED(), // Rejected
        ];

        $productStatusToMarketplaceStatus = [
            //ProductStatus::DRAFT()->getValue() => MarketplaceProductStatus::DISABLED(),
            ProductStatus::DRAFT()->getValue() => MarketplaceProductStatus::PENDING(),
            ProductStatus::LIVE()->getValue() => MarketplaceProductStatus::LIVE(),
            ProductStatus::DISABLED()->getValue() => MarketplaceProductStatus::DISABLED(),
            ProductStatus::OUT_OF_STOCK()->getValue() => MarketplaceProductStatus::OUT_OF_STOCK(),
        ];

        // data that can be extracted from products list (also can extract from product page)
        $mainProductIdentifier = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['gd_no']];
        $associatedSku = $product['outer_gd_no'];
        $stock = $product['order_amt'];
        $productUrl = 'https://www.qoo10.sg/GMKT.INC/Goods/Goods.aspx?goodscode=' . $product['gd_no'];
        $status = $stock > 0 ? ($qoo10Status[$product['stat_dbcode']]) : (ProductStatus::OUT_OF_STOCK());
        $marketplaceStatus = $productStatusToMarketplaceStatus[$status->getValue()];

        // data that need to extract from product page
        $singleProductData = $this->getSingleProduct($product['gd_no']);
        $name = $singleProductData['gd_nm'];
        $shortDescription = $singleProductData['gd_bas_exp'];
        $htmlDescription = $singleProductData['html_description'];
        $prices = [new TransformedProductPrice($this->account->currency, $singleProductData['sell_price'], ProductPriceType::SELLING())];
        $images = $singleProductData['images'];
        $brand = $singleProductData['brand_nm']; // ['brand_nm'] > brand name | ['brand_no'] > brand id
        $model = $singleProductData['model_nm'];
        $logistic = [
            'external_id' => $singleProductData['delivery_group_no'],
            'type' => $singleProductData['delivery_group_type'],
            'delivery_fee' => $singleProductData['delivery_fee'],
            'delivery_fee_type' => $this->deliveryFeeType[$singleProductData['delivery_fee_condition']],
            'free_condition' => $singleProductData['basis_money'],
        ];
        $returnedAddress = [
            'external_id' => $singleProductData['return_addr_no']
        ];

        // data that need to access db to fill in
        $category = null;
        $accountCategory = null;
        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = IntegrationCategory::where([
            'integration_id' => $this->account->integration_id,
            'region_id' => $this->account->region_id,
            'external_id' => $singleProductData['gdsc_cd'],
        ])->first();
        if ($integrationCategory) {
            $category = $integrationCategory->category;
        }

        // attributes
        $attributes = $attributesData ?? [];
        // used by import
        if (is_null($attributesData)) {
            foreach (Constant::MAPATTRIBUTESKEY() as $combinesellKey => $qoo10Key) {
                if (!empty($singleProductData[$qoo10Key])) {
                    if ($combinesellKey === 'manufacture_date') {
                        $attributes['manufacture_year'] = substr($singleProductData[$qoo10Key], 0, 4);
                        $attributes['manufacture_month'] = substr($singleProductData[$qoo10Key], 4, 2);
                    } else {
                        $attributes[$combinesellKey] = strval($singleProductData[$qoo10Key]);
                    }
                }
            }
        }
        // append logistic to attributes
        $attributes['logistics'] = [$logistic];
        // append location to attributes
        $attributes['locations'] = $returnedAddress;
        /** @var Location $location */
        if ($location = $this->account->locations->where('external_id', $singleProductData['return_addr_no'])->first()) {
            // if location data can be found in locations table, replaced it
            $attributes['locations'] = $location->toArray();
        }

        $variantsAndOptions = $this->getVariants($product['gd_no'], [
            'price' => $singleProductData['sell_price'],
            'weight' => $product['gd_weight'],
            'accountCategory' => $accountCategory,
            'integrationCategory' => $integrationCategory,
            'productUrl' => $productUrl,
            'status' => $status,
            'marketplaceStatus' => $marketplaceStatus,
            'stock' => $stock
        ], true);

        $variants = $variantsAndOptions['variants'];
        $options = $variantsAndOptions['options'];

        // create 1 variant based on main product data
        if (empty($variants)) {
            $variantListing = new TransformedProductListing($name, $mainProductIdentifier, $integrationCategory, $accountCategory,
                $prices, $productUrl, $stock, [], null, $images, $marketplaceStatus
            );

            $variants[] = new TransformedProductVariant($name, null, null, null, $associatedSku, null, $stock, $prices,
                $status, ShippingType::MARKETPLACE(), $product['gd_weight'], Weight::KILOGRAMS(), 0, 0, 0, Dimension::CM(), $variantListing, null
            );
        }

        $listing = new TransformedProductListing($name, $mainProductIdentifier, $integrationCategory, $accountCategory, $prices, $productUrl, $stock, $attributes, $product, $images, $marketplaceStatus);

        $product = new TransformedProduct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $category, $status, $variants, $options, $listing, $images);

        return $product;
    }

    /**
     * get formatted single product data
     *
     * @param $externalId
     * @return array
     * @throws \Exception
     */
    public function getSingleProduct($externalId)
    {
        // use GoodsImageInfo(Images section) instead of GoodsModification(Edit Item Info section) because it load faster
        $page = $this->getProduct($externalId);
        $startPoint = strpos($page, 'GMKT.Goods.CreateWebMethod(\'GetGoodsInformations\',');

        // get product detail
        $newStart = substr($page, $startPoint);
        $urlPoint = strpos($newStart, '{"gd_eng_nm"');
        $endPoint = strpos($newStart, ',{"gd_eng_nm"');
        if(!$endPoint)
            $endPoint = strpos($newStart, ']});');
        $final = trim(substr($newStart, $urlPoint , $endPoint-$urlPoint));
        // scrap api response from page directly
        // api: GMKT.INC.Gsm.Web/swe_GoodsBizService.asmx/GetGoodsInformations
        $productInfo = json_decode($final,true);

        // try to re-login and try to get product information again
        // normally, get null result = login cache expired
        if (is_null($productInfo)) {
            if ($this->client->login()) {
                $productInfo = json_decode($final,true);
            } else {
                // disable the account and let user reauthenticate it
                $this->client->disableAccount(AccountStatus::REQUIRE_AUTH());
                throw new \Exception('Unable to login, disable account.');
            }
        }

        // html description
        // edit product page > Images > Description
        $mimeStartPoint = strpos($page, '<textarea id="gd_det_exp_new" name="gd_det_exp_new" visible="True" style="height: 650px;" >');
        $mimeNewStart = substr($page, $mimeStartPoint);
        $mimeBegin = strpos($mimeNewStart, '<');
        $mimeEnd = strpos($mimeNewStart, "</textarea");
        $tagLength = strlen('<textarea id="gd_det_exp_new" name="gd_det_exp_new" visible="True" style="height: 650px;" >');
        $htmlDescription = trim(substr($mimeNewStart, $tagLength , $mimeEnd-$mimeBegin-$tagLength));

        // images
        $images = [];
        // edit product page > Images > Images and Video > Item Image/Type
        if (isset($productInfo['bi_contents_no'])) {
            $data = $productInfo['bi_contents_no'];
            $imageBase = "https://gd.image-gmkt.com/mi/";
            // From MP getImages
            $imageNoSize = strlen($data);
            $imageURL = $imageBase . substr($data, $imageNoSize - 3, 3)
                . '/' . substr($data, $imageNoSize - 6, 3) . '/' . $data . '.jpg';

            try {
                $checkImage = Image::make($imageURL);
                $images[] = new TransformedProductImage($imageURL, null, null, null, count($images));
            } catch (\Exception $exception) {
                // use to skip image creation if image url is not valid
            }
        }
        // edit product page > Images > Images and Video > Enlarged Image
        if (isset($productInfo['ai_image_lists']) && !empty($productInfo['ai_image_lists'])) {
            // https://gd.image-gmkt.com/ai/799/850/1329850799_01.jpg
            $imageBase = "https://gd.image-gmkt.com/ai/";
            $imagesList = explode(',', $productInfo['ai_image_lists']);

            $char = (string) $productInfo['ai_contents_no'];
            $imageBase .= substr($char, -3) . '/' . substr($char, -6, 3) . '/';
            foreach ($imagesList as $image) {
                $images[] = new TransformedProductImage($imageBase.$image, null, null, null, count($images));
            }
        }

        try {
            return array_merge($productInfo, [
                // extra processed data
                'html_description' => $htmlDescription,
                'images' => $images,
            ]);
        } catch (\Exception $exception) {
            set_log_extra('page', $page);
            set_log_extra('final', $final);
            set_log_extra('product_info', $productInfo);
            set_log_extra('html_description', $htmlDescription);
            set_log_extra('images', $images);
            throw $exception;
        }
    }

    /**
     * get formatted variants data + options data
     *
     *
     * @param $externalId
     * @param $mainProduct
     * @param bool $withMainProductOptions - append options array on return data
     * @return array
     * @throws \Exception
     */
    public function getVariants($externalId, $mainProduct, $withMainProductOptions = false)
    {
        $variants = [];
        $options = [];
        $optionsData = $this->getOptions($externalId);
        $variantsData = $this->getVariantsRaw($externalId);

        if (isset($variantsData) && count($variantsData) > 0) {
            try {
                foreach ($variantsData as $variantData) {
                    $variantName = ''; // qoo10 variant dont have variant name

                    // data that can be extracted from variant's detail
                    $identifiers = [
                        ProductIdentifier::EXTERNAL_ID()->getValue() => $variantData['seq_code'],
                        ProductIdentifier::SKU()->getValue() => $variantData['simple_cd'],
                    ];
                    $variantSku = $variantData['simple_cd'];
                    $variantImages = [];
                    // if q inventory is set, use the q inventory as the stock
                    $variantStock = empty($variantData['gd_cd']) ? $variantData['inventory_cnt'] ?? $mainProduct['stock'] : $variantData['integration_qty'];

                    // attributes
                    $variantAttributes = [];
                    foreach (Constant::MAPATTRIBUTESKEY(true) as $combinesellKey => $qoo10Key) {
                        if (!empty($variantData[$qoo10Key])) {
                            $variantAttributes[$combinesellKey] = strval($variantData[$qoo10Key]);
                        }
                    }

                    // price
                    $variantPrices = [];
                    $priceValue = $mainProduct['price'];
                    if (isset($variantData['inventory_price'])) {
                        $priceValue += $variantData['inventory_price'];
                        $variantPrices[] = new TransformedProductPrice($this->account->currency, $priceValue, ProductPriceType::SELLING());
                    } elseif (isset($variantData['sel_item_price'])) {
                        //This one is handling of add-ons
                        $priceValue += $variantData['sel_item_price'];
                    }
                    $variantPrices[] = new TransformedProductPrice($this->account->currency, $priceValue, ProductPriceType::SELLING());

                    // options
                    $option = [];
                    $imageBase = 'https://gd.image-gmkt.com/li/';
                    $optionCount = 0;

                   // Multiple options
                    if (isset($variantData['sel_name1'])) {
                        // TODO: options max in qoo10 is 5, what to do if got 5 options?
                        for ($i = 1; $i <= 5; $i++) {
                            try {
                                $optionName = $variantData['sel_name' . $i];
                                $optionVal = $variantData['sel_value' . $i];
                                if (!empty($optionName) && !empty($optionVal) && (in_array($optionName, $options) || (!in_array($optionName, $options) && count($options) < 3))) {
                                    // filter repeated option's title
                                    if (!in_array($optionName, $options)) {
                                        $options[] = $optionName;
                                    }
                                    $option[] = $optionVal;
                                    $optionCount++;

                                    // image
                                    $imageUrlId = !empty($optionsData) ? $optionsData[$optionName][$optionVal]['img_url'] : '';

                                    if (!empty($imageUrlId)) {
                                        // example: https://gd.image-gmkt.com/li/005/219/1415219005.jpg
                                        $imageUrl = $image = $imageBase . substr($imageUrlId, -3) . '/' . substr($imageUrlId, -6, 3) . '/'.$imageUrlId.'.jpg';
                                        $variantImages[] = new TransformedProductImage($imageUrl, null, null, null, count($variantImages));
                                    }
                                }
                            } catch (\Exception $exception) {
                                set_log_extra('variantData', $variantData);
                                set_log_extra('optionName', $optionName);
                                set_log_extra('optionVal', $optionVal);
                                set_log_extra('optionsData', $optionsData);
                                set_log_extra('options', $options);
                                set_log_extra('option', $option);
                                throw $exception;
                            }
                        }
                    } else {
                        // Single option
                        try {
                            $optionName = $variantData['sel_name'];
                            $optionVal = $variantData['sel_value'];
                            if (!empty($optionName) && !empty($optionVal) && (in_array($optionName, $options) || (!in_array($optionName, $options) && count($options) < 3))) {
                                // filter repeated option's title
                                if (!in_array($optionName, $options)) {
                                    $options[] = $optionName;
                                }
                                $option[] = $optionVal;
                                $optionCount++;

                                // image
                                $imageUrlId = !empty($optionsData) ? $optionsData[$optionName][$optionVal]['img_url'] : '';

                                if (!empty($imageUrlId)) {
                                    // example: https://gd.image-gmkt.com/li/005/219/1415219005.jpg
                                    $imageUrl = $image = $imageBase . substr($imageUrlId, -3) . '/' . substr($imageUrlId, -6, 3) . '/'.$imageUrlId.'.jpg';
                                    $variantImages[] = new TransformedProductImage($imageUrl, null, null, null, count($variantImages));
                                }
                            }
                        } catch (\Exception $exception) {
                            set_log_extra('variantData', $variantData);
                            set_log_extra('optionName', $optionName);
                            set_log_extra('optionVal', $optionVal);
                            set_log_extra('optionsData', $optionsData);
                            set_log_extra('options', $options);
                            set_log_extra('option', $option);
                            throw $exception;
                        }
                    }

                    // used to get variant image from variant that has more than 1 options
                    if ($optionCount > 1) {
                        $variantImages = [];
                        for ($i = 1; $i <= 3; $i++) {
                            if (!empty($variantData['sel_image' . $i])) {
                                $images = explode('||', $variantData['sel_image' . $i]);

                                // only save first variant image, if qoo10 allow multi variant images, change this
                                $imageUrlId = $images[0];

                                // example: https://gd.image-gmkt.com/li/005/219/1415219005.jpg
                                $imageUrl = $image = $imageBase . substr($imageUrlId, -3) . '/' . substr($imageUrlId, -6, 3) . '/'.$imageUrlId.'.jpg';
                                $variantImages[] = new TransformedProductImage($imageUrl, null, null, null, count($variantImages));
                                break;
                            }
                        }
                    }

                    $option1 = $option[0] ?? null;
                    $option2 = $option[1] ?? null;
                    $option3 = $option[2] ?? null;

                    $variantListing = new TransformedProductListing($variantName, $identifiers, $mainProduct['integrationCategory'], $mainProduct['accountCategory'],
                        $variantPrices, $mainProduct['productUrl'], $variantStock, $variantAttributes, $variantData, $variantImages, $mainProduct['marketplaceStatus']
                    );

                    $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, null, $variantStock, $variantPrices,
                        $mainProduct['status'], ShippingType::MARKETPLACE(), $mainProduct['weight'], Weight::KILOGRAMS(), 0, 0, 0, Dimension::CM(), $variantListing, $variantImages
                    );
                }
            } catch (\Exception $exception) {
                set_log_extra('optionsData', $optionsData);
                set_log_extra('variantsData', $variantsData);
                throw $exception;
            }
        }

        // can set $withMainProductOptions to true to return options back to parent
        if ($withMainProductOptions) {
            return [
                'variants' => $variants,
                'options' => $options
            ];
        }
        return $variants;
    }

    /**
     * Get raw variants data directly from qoo10
     *
     * @param $externalId
     * @return mixed
     * @throws \Exception
     */
    public function getVariantsRaw($externalId)
    {
        // TODO: double check if $singleVariant exist, can skipped $matrix or not
        // edit product page > Options > Single Option
        $singleVariants = $this->getSingleVariants($externalId);

        // edit product page > Options > Combination (matrix) Option
        $matrix = $this->getMatrixVariants($externalId);

        if (count($singleVariants) > 0 && count($matrix) < 1) {
            return $singleVariants;
        } else {
            return $matrix;
        }
    }

    /**
     * Generate qoo10's h_opt_inventory_data based on Combinesell variants data
     *
     * @param Product $product
     * @param null|string $productSellingPrice
     * @param null|string|int $accountId
     * @param array $data
     * @param bool $uploadImage
     * @return string
     * @throws \Exception
     */
    public function getVariantsString(Product $product, $productSellingPrice = null, $data = [], $uploadImage = true, $accountId = null)
    {
        $variantsData = [];

        if ($product->variants->count()) {
            if (count($product->options) === 0) {
                $product->options = ['name'];
            }
            $options = array_values($product->options);

            // customize options
            /** @var ProductListing $productListing */
            if (!is_null($accountId) && $productListing = $product->listings->where('account_id', $accountId)->first()) {
                /** @var ProductAttribute $optionsData */
                if ($optionsData = $productListing->attributes->where('name', 'options')->first()) {
                    $options = json_decode($optionsData->value, true);
                }
            }

            if (is_null($productSellingPrice)) {
                $productSellingPrice = $product->prices->mapWithKeys(function ($item) {
                    return [$item['type'] => $item['price']];
                })->toArray()[ProductPriceType::SELLING()->getValue()];
            }

            foreach ($product->variants as $variant) {
                $hsCode = '';
                $availableShippingDate = '';
                $variantAttributes = [];
                $qInventory = [];
                if (is_null($accountId)) {
                    $variantListing = null;

                    // map attributes data to array with name as key
                    $variantAttributes = $variant->attributes->mapWithKeys(function ($item) {
                        return [$item['name'] => $item['value']];
                    })->toArray();

                    // HS Code
                    if (array_key_exists('hs_code', $variantAttributes)) {
                        $hsCode = $variantAttributes['hs_code'];
                    }
                    // Available Shipping Date
                    if (array_key_exists('available_shipping_date', $variantAttributes)) {
                        $availableShippingDate = date('M d, Y H:i:s', strtotime($variantAttributes['available_shipping_date']));
                    }

                    // create product not support Q-Inventory yet
                } else {
                    // get attributes data
                    if (isset($data[$variant->id]) && array_key_exists('attributes', $data[$variant->id])) {
                        $variantAttributes = $data[$variant->id]['attributes'];
                    } else {
                        /** @var ProductListing $variantListing */
                        if ($variantListing = $variant->listings->where('account_id', $accountId)->first()) {
                            $variantAttributes = $variantListing->attributes->mapWithKeys(function ($item) {
                                // use the same format as $data variant's format
                                return [$item['name'] => ['value' => $item['value']]];
                            })->toArray();
                        }
                    }

                    // HS Code
                    if (array_key_exists('hs_code', $variantAttributes) && !empty($variantAttributes['hs_code']['value'])) {
                        $hsCode = $variantAttributes['hs_code']['value'];
                    }
                    // Available Shipping Date
                    if (array_key_exists('available_shipping_date', $variantAttributes) && !empty($variantAttributes['available_shipping_date']['value'])) {
                        $availableShippingDate = date('M d, Y H:i:s', strtotime($variantAttributes['available_shipping_date']['value']));
                    }

                    // Q-Inventory
                    if (array_key_exists('gd_cd', $data[$variant->id])) {
                        $qInventory['gd_cd'] = $data[$variant->id]['gd_cd'];
                    }
                    if (array_key_exists('seat_cl', $data[$variant->id])) {
                        $qInventory['seat_cl'] = $data[$variant->id]['seat_cl'];
                    }
                }

                // Option
                // if option has been customized, change it
                $optionsCount = count($product->options);
                for ($x = 1; $x <= $optionsCount; $x++) {
                    // only export product support customize option
                    if (is_null($accountId) && array_key_exists('option_' . $x, $variantAttributes)) {
                        $variant->{'option_' . $x} = $variantAttributes['option_' . $x];

                        // if it is variant without option, add a default option to it
                    } elseif ($x === 1 && empty($variant->{'option_' . $x})) {
                        $variant->{'option_' . $x} = $variant->name;
                    } elseif (empty($variant->{'option_' . $x})) {
                        $variant->{'option_' . $x} = $options[$x - 1] . '_' . $x;
                    }
                }

                // Price
                $variantSellingPrice = null;
                if (isset($data[$variant->id]) && isset($data[$variant->id]['prices'])) {
                    foreach ($data[$variant->id]['prices'] as $price) {
                        if ($price['type'] === ProductPriceType::SELLING()->getValue()) {
                            $variantSellingPrice = $price['price'];
                            break;
                        }
                    }
                }

                // if $data dont have prices, get it from table
                if (is_null($variantSellingPrice)) {
                    $variantSellingPrice = $variant->prices->mapWithKeys(function ($item) {
                            return [$item['type'] => $item['price']];
                        })->toArray()[ProductPriceType::SELLING()->getValue()] ?? 0;
                }

                // Image
                $image = '';
                if ($uploadImage) {
                    // read image from data passed from frontend or get it from table
                    if (isset($data[$variant->id]) && isset($data[$variant->id]['images']) && count($data[$variant->id]['images']) > 0) {
                        $firstImage = $data[$variant->id]['images'][0];
                    } elseif (is_null($accountId)) {
                        $firstImage = $variant->allImages->first();
                    } else {
                        // if $variantListing didn't called at above, get it again
                        if (!isset($variantListing)) {
                            $variantListing = $variant->listings()->where('account_id', $accountId)->first();
                        }
                        $productListingId = $variantListing->id ?? null;
                        // get variant's listing image, if not found, use variant's image
                        $firstImage = $variant->allImages()
                            ->where('product_listing_id', $productListingId)
                            ->orderBy('integration_id', 'DESC')
                            ->orderBy('position')
                            ->first();
                    }

                    if (isset($firstImage)) {
                        if ($firstImage instanceof ProductImage) {
                            $image = $this->uploadImage($firstImage->image_url, 'P') . '||';
                        } else {
                            $image = $this->uploadImage($firstImage['image_url'], 'P') . '||';
                        }

                    }
                }

                $imgIdx = $image === '' ? 0 : 2;

                $variantsData[] = [
                    'sel_name1' => $options[0] ?? '',
                    'sel_name2' => $options[1] ?? '',
                    'sel_name3' => $options[2] ?? '',
                    'sel_name4' => $options[3] ?? '',
                    'sel_name5' => $options[4] ?? '',
                    'sel_value1' => $variant->option_1 ?? '',
                    'sel_value2' => $variant->option_2 ?? '',
                    'sel_value3' => $variant->option_3 ?? '',
                    'sel_value4' => '',
                    'sel_value5' => '',
                    'inventory_yn' => 'Y', // TODO: find out what is this
                    'quantity' => (int)$variant->inventory->stock ?? 0, // normal inventory
                    'sell_cnt' => 0,// old value: $productSellingPrice, dont no this slot now wanna put what
                    'currency' => $this->account->currency,
                    'inventory_price' => $variantSellingPrice - $productSellingPrice,
                    'integration_qty' => (int)$variant->inventory->stock ?? 0, // Q-inventory
                    'integration_price' => $variantSellingPrice - $productSellingPrice,
                    'gd_cd' => $qInventory['gd_cd'] ?? '', // Q-Inventory Code
                    'seat_cl' => $qInventory['seat_cl'] ?? '', // Q-Inventory Code
                    'option_code' => $variant->sku ?? '', // qoo10 variant can have empty sku
                    'simple_cd' => $variant->sku ?? '', // qoo10 variant can have empty sku
                    'sel_image1' => $image,
                    'sel_image2' => '', // if free baru add this
                    'sel_image3' => '', // if free baru add this
                    'sel_img_idx' => $imgIdx,
                    'gd_ind_code' => $hsCode,
                    'tir_seller_cd' => '',
                    'template_gd_no' => '',
                    'available_ship_dt' => $availableShippingDate,
                ];
            }
        }

        return $this->generateVariantsString($variantsData);
    }

    /**
     * Generate variants string based on given data
     *
     * @param $variantsData
     * @return string
     */
    public function generateVariantsString($variantsData)
    {
        $variantsString = '';
        foreach ($variantsData as $data) {
            $variantsString .= $data['sel_name1'] . "" . $data['sel_name2'] . "" . $data['sel_name3'] . "" . $data['sel_name4'] . "" . $data['sel_name5'] . "" . $data['sel_value1'] . "" . $data['sel_value2'] . "" . $data['sel_value3'] . "" . $data['sel_value4'] . "" . $data['sel_value5'] . "" . $data['inventory_yn'] . "" . $data['quantity'] . "" . $data['sell_cnt'] . "" . $data['inventory_price'] . "" . $data['option_code'] . "" . $data['simple_cd'] . "" . $data['integration_qty'] . "" . $data['integration_price'] . "" . $data['currency'] . "" . $data['gd_cd'] . "" . $data['seat_cl'] . "" . $data['sel_image1'] . "" . $data['sel_img_idx'] . "" . $data['tir_seller_cd'] . "" . $data['template_gd_no'] . "" . $data['gd_ind_code'] . "" . $data['available_ship_dt'] . "^";
        }

        return $variantsString;
    }

    /**
     * Generate qoo10's options string based on Combinesell variants data
     *
     * @param Product $product
     * @param array $data
     * @param bool $uploadImage
     * @param bool $fromCreate
     * @return string
     */
    public function getOptionsString(Product $product, $data = [], $uploadImage = false, $fromCreate = false)
    {
        // extract options data from product and variants
        $optionsData = [];

        // if from create and options has been customized, change it
        /** @var ProductAttribute $optionsAttribute */
        if ($fromCreate && $optionsAttribute = $product->attributes->where('product_variant_id', null)->where('name', 'options')->first()) {
            $product->options = json_decode($optionsAttribute->value, true);
        }
        $optionsList = array_values($product->options);
        foreach ($optionsList as $option) {
            $options[$option] = [
                'option' => []
            ];
        }

        foreach ($product->variants as $variantIndex => $variant) {
            foreach ($optionsList as $optionIndex => $option) {
                if (!array_key_exists($option, $optionsData)) {
                    $optionsData[$option] = [
                        'option' => []
                    ];
                }

                // Images
                // only add group image if it is single option variant
                $image = '';
                if ($uploadImage) {
                    $firstImage = null;
                    // read image from data passed from frontend or get it from table
                    if (isset($data[$variant->id]) && isset($data[$variant->id]['images']) && count($data[$variant->id]['images']) > 0) {
                        $firstImage = $data[$variant->id]['images'][0];
                    } elseif ($fromCreate) {
                        $firstImage = $variant->allImages->first();
//                    } elseif (!$fromCreate) {
                    } else {
                        /** @var ProductListing $productListing */
                        if ($productListing = $variant->listings()->where('account_id', $this->account->id)->first()) {
                            $productListingId = $productListing->id ?? null;
                            // get variant's listing image, if not found, use variant's image
                            $firstImage = $variant->allImages()
                                ->where('product_listing_id', $productListingId)
                                ->orderBy('integration_id', 'DESC')
                                ->orderBy('position')
                                ->first();
                        }
                    }

                    if (isset($firstImage)) {
                        if ($firstImage instanceof ProductImage) {
                            $image = $this->uploadImage($firstImage->image_url, 'P');
                        } else {
                            $image = $this->uploadImage($firstImage['image_url'], 'P');
                        }

                    }
                }

                $optionsData[$option]['option'][] = $variant['option_'.($optionIndex+1)];
                $optionsData[$option]['image'][] = $image;
                $optionsData[$option]['option'] = array_unique($optionsData[$option]['option']);
            }
        }

        $groupData = '';
        $optionType = 'I'; // T - Type, C - Color, S - Size, I - Direct Input
        $no = 1;
        foreach ($optionsData as $optionName => $optionData) {
            foreach ($optionData['option'] as $optionIndex => $option) {
                $groupData .= $no . "" . $optionName . "" . "Y" . "" . $optionType . "" . $option . "" . $optionData['image'][$optionIndex] . "" . $optionData['image'][$optionIndex] . "^";
            }
            $no++;
        }

        return $groupData;
    }

    public function retrieveLogistics($attributes = null)
    {
        $logistics = [];

        // get shipping centers list
        $shippingCenters = $this->getShippingCenters();

        foreach ($shippingCenters as $shippingCenter) {
            // shipping center name
            $shippingCenterName = $shippingCenter['delivery_bundle_nm'];
            $logistics[$shippingCenterName] = [];

            // get selected shipping center shipping rates list
            $shippingRates = $this->getShippingRates($shippingCenter['delivery_bundle_no']);

            foreach ($shippingRates as $shippingRate) {
                $surcharge = [];

                // collect surcharge of this shipping rate
                foreach ($this->surchargesType as $surchargeType => $surchargeOption) {
                    if (!empty($shippingRate[$surchargeType]) && array_key_exists($shippingRate[$surchargeType], $surchargeOption)) {
                        $surcharge[] = $surchargeOption[$shippingRate[$surchargeType]];
                    }
                }

                $logistics[$shippingCenterName][] = [
                    'external_id' => $shippingRate['delivery_group_no'],
                    'type' => $shippingRate['delivery_group_type'],
                    'name' => $shippingRate['delivery_group_nm'],
                    'delivery_fee' => $shippingRate['delivery_fee'],
                    'free_condition' => $shippingRate['basis_money'],
                    'delivery_fee_type' => $this->deliveryFeeType[$shippingRate['delivery_fee_condition']],
                    'shipping_method' => $this->shippingMethod[$shippingRate['delivery_default_code']],
                    'surcharge' => $surcharge,
                ];
            }
        }

        // Qprime
        $qprimeList = $this->getQprimes();
        foreach ($qprimeList as $qprime) {
            $surcharge = [];
            $qprime = $this->getQprime($qprime['delivery_group_no']);

            // collect surcharge of this shipping rate
            foreach ($this->surchargesType as $surchargeType => $surchargeOption) {
                if (!empty($qprime[$surchargeType]) && array_key_exists($qprime[$surchargeType], $surchargeOption)) {
                    $surcharge[] = $surchargeOption[$qprime[$surchargeType]];
                }
            }

            array_unshift($logistics['Qshipping group'], [
                'external_id' => $qprime['delivery_group_no'],
                'type' => $qprime['delivery_group_type'],
                'name' => $qprime['delivery_group_nm'],
                'delivery_fee' => $qprime['delivery_fee'],
                'free_condition' => $qprime['basis_money'],
                'delivery_fee_type' => $this->deliveryFeeType[$qprime['delivery_fee_condition']],
                'shipping_method' => $this->shippingMethod[$qprime['delivery_default_code']],
                'surcharge' => $surcharge,
            ]);
        }

        return $logistics;
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
        $mainListing = $listing;
        if (!is_null($listing->product_variant_id)) {
            // get main listing
            $mainListing = $listing->listing;
        }

        // get main listing external id
        $externalId = $mainListing->getIdentifier(ProductIdentifier::EXTERNAL_ID());

        $status = ($enabled) ? 'S2' : 'S1';

        // update status api need header and footer data
        $header = '';
        $footer = '';

        /** @var ProductAttribute $headerAttribute */
        if ($headerAttribute = $mainListing->attributes->where('name', 'header')->first()) {
            $header = $headerAttribute->value;
        }
        /** @var ProductAttribute $footerAttribute */
        if ($footerAttribute = $mainListing->attributes->where('name', 'header')->first()) {
            $footer = $footerAttribute->value;
        }

        $response = $this->updateStatus($externalId, $status, $header, $footer);

        // If success will return empty
        if (!empty($response)) {
            throw new \Exception($response);
        }

        $this->get($listing, true);

        return true;
    }

    /* API Section - START */

    /**
     * Create product
     * Source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsRegistration.aspx
     * Description: create product with api found in New Item Listing page
     *
     * @param Product $product
     * @param $data
     * @return mixed
     */
    public function createProduct(Product $product, $data)
    {
        // New Item Listing page > List Item
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsRegBizService.asmx/AddNewGoods', [
            'json' => [
                'h_cust_no' => $this->client->getSellerNo(),
                'h_cust_gr' => 'C1', // A1 or C1?? // TODO: find out what is this
                'h_login_id' => $this->client->getSellerNo(),

                'basicInfo' => [
                    // Basic Information > Category
                    'gdlc_cd' => $data['category'][0],
                    'gdmc_cd' => $data['category'][1],
                    'gdsc_cd' => $data['category'][2],

                    // Dont no the source
                    'h_selectedtrad_way' => 'T6',

                    // Basic Information > Delivery Type
                    'gd_type' => $data['delivery_type'] ?? 'BI',

                    // Additional Item Information > Manufacturer // TODO: pull manufacturer
                    'maker_nm' => '', // $data['manufacturer']['name'] selected from qoo10 account's manufacturer list
                    'maker_no' => '', // $data['manufacturer']['id'] selected from qoo10 account's manufacturer list

                    // Additional Item Information > Brand // TODO: after done brand change this
                    'brand_no' => '0', // $data['brand']['id'] selected from account's brand list
                    'brand_nm' => '', // $data['brand']['name']selected from account's brand list

                    // Basic Information > Item Title
                    'gd_nm' => $data['name'] ?? $product->name,

                    // Basic Information > Short Title
                    'gd_short_nm' => $data['short_title'] ?? '',

                    // Dont no the source
//                    'gd_eng_nm' => '',
//                    'gd_culture_nm' => '',

                    // Additional Item Information > Product Model No
                    'model_nm' => $data['product_model_no'] ?? '',

                    // Basic Information > Seller Item Code
                    'outer_gd_no' => $product->associated_sku,

                    // Additional Item Information > Industry Code
                    'gd_ind_code' => $data['industry_code'] ?? '',
                    'gd_ind_code_type' => $data['industry_code_type'] ?? 'UPC',

                    // Additional Item Information > Reference ID Code > Simple Code
                    'gd_simple_code' => $data['simple_code'] ?? '',

                    // Additional Item Information > Reference ID Code > Other Site Reference Code
                    'gd_ref_code' => $data['other_code'] ?? '',

                    // Additional Item Information > Manufacture Date
                    'txtgdmadedt' => isset($data['manufacture_year']) && isset($data['manufacture_month']) ? $data['manufacture_year'].$data['manufacture_month'] : '',

                    // Dont no the source
                    'txtgdappeardt' => '',

                    // Basic Information > Production Place
                    'gd_origin2' => $data['origin_type'] ?? 'K', // K > Domestic, F > Overseas, U > Others
                    'gd_origin' => $data['origin'] ?? ($this->account->region_id === 2 ? 'Singapore' : 'Malaysia'),

                    // Basic Information > Item Condition
                    'selgdkind1' => $data['item_condition'] ?? '10',
                    'selgdkind2' => $data['condition'] ?? '00',
                    'selgdkind3' => '01',
                    'txtoldgdusedperiod' => $data['period_of_use'] ?? '',
                    'txtoldgdusedstate' => $data['brief_explanation'] ?? '',

                    // Dont no the source
//                    'tax_yn' => 'Free',

                    // Basic Information > Adult Item?
                    'hdnadultyn' => $data['adult_item'] ?? 'N',

                    // Additional Item Information > After Sales Service
                    'as_address' => $data['address'] ?? '',
                    'as_tel_no' => $data['phone_number'] ?? '',
                    'as_email' => $data['email'] ?? '',

                    // Additional Item Information > Gift
                    'op_gd' => $data['gift'] ?? '',

                    // Dont no the source
//                    'refusal_h_gubun' => '',
//                    'refusal_e_gubun' => '',
//                    'gd_sub_type' => '',
                    'option_name' => '',
//                    'option_value' => '',
//                    'option_price' => '',
//                    'option_image' => '',

                    // Item Option > Sorting Type
                    'option_order' => $data['option_order'] ?? 'S',

                    // Dont no the source
                    'display_type' => 'T',
//                    'option_simple_code' => '',
//                    'option_detail_url' => '',
//                    'option_memo' => '',
//                    'option_qinven_no_list' => '',
//                    'option_ind_code' => '',
//                    'option_ind_code_type' => '',
//                    'request_info' => '',

                    // Item Option (needed for variant creation, if removed, variant wont be created even variant string is filled in)
                    'h_opt_inventory_no' => '',
                    'h_set_opt_inventory_yn' => 'R',

                    // Item Option > Combination (matrix) Option > Edit options
                    'h_opt_inventory_data' => $data['variants']['data'] ?? '',

                    // Item Option > Combination (matrix) Option > Set Item Type Info.
                    'h_opt_group_data' => $data['variants']['options'] ?? '',

                    // Item Option > Combination (matrix) Option > Set Option Image Viewer > Option Image Display
                    'h_inv_img_disp_type' => $data['variant_image_type'] ?? 'L', // B- Option Select Box, T - Small Thumbnail, L - Big Thumbnail, N - No Use

                    // Item Option > Combination (matrix) Option > Set Item Type Info.
                    'h_inv_set_type' => 'N', // N - Make Combination/EXCEL, O - Option Template

                    // Dont no the source
                    'h_inv_opt_selector_yn' => 'N',
//                    'add_request_yn' => 'N',
//                    'add_request_form' => '',
//                    'org_gd_no' => '',
                    'h_userlocation' => 'GSM>Listing&Edit>NewListing',
                    'h_param_src_nation' => '',
//                    'link_reg_type' => '',
//                    'h_global_goods_no' => '',

                    // Q-inventory // TODO: replace this after setup Q-inventory
                    'txt_gdcode' => $data['q_inventory']['inventory_code'] ?? '', // $qInventory->q_inventory_code < v1 code
                    'txt_seatcl' => $data['q_inventory']['option_code'] ?? '', // $qInventory->q_inventory_option_code < v1 code

                    // Dont no the source
//                    'bi_contents_no' => '',
//                    'dh_contents_no' => '',
//                    'gd_stat' => 'S2',

                    // Shipping Information > Weight input at Kg
                    'goods_weight' => strval($data['goods_weight'] ?? ''), // default 0.5kg - qoo10 min

                    // Dont no the source
//                    'auction_add_yn' => 'N',

                    // Additional Item Information > Brief Description
                    'brief_description' => $data['short_description'] ?? $product->short_description,

                    // Dont no the source
//                    'del_hopeday' => '0',

                    // Multi-language Title & Brief Description (Can do support 1 extra language, if want support more than 1, time needed very long)
                    'multilang_lang' => '',
                    'multilang_gd_nm' => '',
                    'multilang_brief_nm' => '',

                    // Dont no the source
//                    'h_goods_relation_grno' => '',

                    // Additional Item Information > Material
                    'gd_material' => $data['material'] ?? '',

                    // Search Keyword > Item Title
                    'search_keyword' => $data['search'] ?? '',
                    'search_keyword_divide_queue_yn' => 'N',

                    // Dont no the source
//                    'tag_keep_yn' => 'N',
//                    'shopping_talk_display' => 'N',
//                    'expire_dt_display_day' => '0',
//                    'cod_available_yn' => 'N',
//                    'gd_notice_msg1' => '',
//                    'gd_notice_msg2' => '',
//                    'cate_specific_nos' => '',
//                    'nego_allow_yn' => 'N',
//                    'nego_allow_price' => '',
//                    'seller_memo' => '',
//                    'gd_lang_cd' => 'en',
//                    'service_providing_url' => '',
//                    'providing_url_use_yn' => 'N',
//                    'drugs_type' => '',
//                    'add_purchase_group_types' => '',

                    // Pricing and Quantity > Minimum Order Limit
                    'min_order_qty' => $data['min_order_qty'] ?? '0',

                    // Additional Item Information > Additional Info.
                    'new_good_properties_names' => $data['additional_info_title'] ?? '',
                    'new_good_properties_values' => $data['additional_info_desc'] ?? '',

                    // Dont no the source
//                    'wholesale_disp_type' => 'A',
//                    'wholesale_disp_values' => '',
//                    'resale_use_yn' => 'Y',
//                    'purchase_unit' => '',
//                    'sellershop_category_cds' => '',
//                    'vendor_cd' => '',
//                    'vendor_fee' => '0',
//                    'recruit_reseller_yn' => 'Y',
                    'sales_unit_use_yn' => 'N',
                    'sales_unit_type' => '',
                    'sales_unit_custom' => '',
                    'sales_unit_symbol' => '',
                    'unit_pack_cnt' => '0',
                ],

                'openMarketInfo' => [
                    // Pricing and Quantity > Retail Price (S$)
                    'quotation_kind' => 'Q',
                    'quotation_price' => strval($data['prices'][ProductPriceType::RETAIL()->getValue()] ?? 0),

                    // Pricing and Quantity > Sell price per piece (S$)
                    'txtopen_sell_price' => strval($data['prices'][ProductPriceType::SELLING()->getValue()]),

                    // Pricing and Quantity > Settle Price (S$)
                    'txtopen_settle_money' => strval($data['prices']['settle']),

                    // Pricing and Quantity > Available Total Qty
                    'txtopen_order_amt' => strval($data['stock'] ?? 1),

                    // Pricing and Quantity > Available Period
                    'txtopen_expire_dt' => $data['available_period'] ?? now()->addYear()->setTimezone('Asia/Singapore')->format('Y-m-d H:i:s'),

                    // Dont no the source
                    'h_oldcate_gubun' => '',
                    'oldcateorybill_price' => '0',

                    // Pricing and Quantity > Notes To Display When Sold Out
                    'restock_memo' => $data['restock_memo'] ?? '',
                    'send_restock_mail_yn' => $data['restock_mail'] ?? 'Y',
                ],

                'auctionInfo' => [ // we doesn't support
                    // Should be Auction Information // array must contain at least 1 child
                    'auction_direct_buy_yn' => '',
//                    'auction_direct_price' => '0',
//                    'auction_direct_start_dt' => '',
//                    'auction_direct_end_dt' => '',
//                    'auction_kind' => '',
//                    'auction_start_dt' => '',
//                    'auction_start_time' => '',
//                    'auction_start_min' => '',
//                    'auction_end_dt' => '',
//                    'auction_end_time' => '',
//                    'auction_end_min' => '',
//                    'auction_reg_fee' => '0',
//                    'auction_reg_fee_type' => '',
//                    'auction_total_amt' => '0',
//                    'auction_unit_price' => '0',
//                    'auction_unit_type' => '',
//                    'auction_max_bid_cnt' => '0',
//                    'auction_direct_no' => '0',
//                    'auth_stat' => 'Y',
//                    'max_bid_price' => '0',
//                    'max_bid_early_closing_yn' => 'N',
//                    'general_start_price' => '0',
//                    'general_max_limited_cnt' => '0',
//                    'dream_bid_min' => '0',
//                    'dream_bid_max' => '0',
//                    'dream_bid_fee' => '0',
//                    'dream_bid_limit' => '0',
//                    'dream_outer_price' => '0',
//                    'dream_draw_type' => '',
//                    'dream_direct_no' => '0',
//                    'limit_etoken_eid' => '',
//                    'limit_etoken_request' => '0',
//                    'who_reg' => 'GD',
//                    'link_eid' => '0',
//                    'channel_popup_no' => '',
//                    'dream_goods_image' => '',
//                    'dream_goods_title' => '',
//                    'follow_shop_yn' => 'N',
//                    'auction_limit_type' => 'A',
//                    'auction_limit_cust_no' => '',
//                    'auction_limit_start_dt' => '',
//                    'auction_limit_end_dt' => '',
//                    'auction_limit_dt_yn' => '',
//                    'auction_limit_room_no' => '',
//                    'auction_direct_link_yn' => 'N',
//                    'auction_direct_link_no' => '',
//                    'txtOpen_sell_price_a' => '',
//                    'txtopen_order_amt_a' => '',
//                    'auction_inventory' => '',
                ],
                'deliveryInfo' => [
                    // Shipping Information > Set up standard shipping service > Seller setup shipping fee (S$)
                    'delivery_fee_condition' => array_search($data['logistics']['delivery_fee_type'], $this->deliveryFeeType) ?? '', // X - Free, M - Free on condition, F - standard

                    // Shipping Information > Set up standard shipping service > Standard Delivery Settings (SR Code)
                    'delivery_group_no' => $data['logistics']['external_id'],

                    // Shipping Information
                    'delivery_group_type' => $data['logistics']['type'], // 80 - Quick Prime Service , 70 - Qprime-S Shipping , 60 - Others

                    // Shipping Information > Set up standard shipping service > Seller setup shipping fee (S$)
                    'delivery_fee' => $data['logistics']['delivery_fee'],
                    'delivery_memo' => '',
                    'basis_money' => $data['logistics']['free_condition'], // Free Condition
                    'return_addr_no' => $data['locations']['external_id'],

                    // Dont no the source
//                    'visitable' => 'N',
//                    'visit_addr_all_yn' => 'Y',
//                    'visit_addr_no' => '0',
//                    'visit_benefit_type' => 'N',
//                    'visit_benefit_price' => '0',
//                    'visit_benefit_memo' => '',
//                    'visit_request_msg' => '',
//                    'del_type' => 'bundle',
//                    'store_coupon_type' => '',
//                    'map_local_url' => '',
                    'usable_type' => 'D',
                    'usable_days' => '',
                    'usable_sdt' => '',
                    'usable_edt' => '',
//                    'cancel_pos_days' => '',
//                    'voucher_img' => '',
//                    'where_to_use_info' => '',

                    // Shipping Information > Set up standard shipping service > Return Address
                    'pk_zipcode' => '', // TODO: fill in this after setup address for qoo10 shipping (return address postal code)

                    // Dont no the source
//                    'delivery_default_code' => 'EX',
//                    'transc_cd' => '',
//                    'transc_nm' => '',

                    // Shipping Information > Set up standard shipping service > Standard Delivery Settings > Qshipping group use settings : Country of shipment
                    'dg_domestic_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',

                    // Dont no the source
//                    'sub_delivery_group_no_list' => '',
//                    'sub_delivery_group_list' => '',
//                    'shop_location_type' => 'S',
//                    'seller_password_use_yn' => 'N',
//                    'branch_password_use_yn' => 'N',
//                    'h_visit_tir_no' => '',
//                    'voucher_code_show_yn' => 'Y',
//                    'multi_location_web_url' => '',
//                    'multi_location_mobile_url' => '',
//                    'redemption_type' => '',
//                    'voucher_auth_push_type' => 'N',
//                    'sel_send_type' => '0',
//                    'txt_send_date' => '',
//                    'txt_send_day' => '',
//                    'qprime_exception_nation_list' => '',
//                    'available_nation_cds' => '',
//                    'available_nation_type' => 'B',
                ],
                'premiumInfo' => [ // we doesn't support
                    // should be List This Item To QuuBe - B2B & Wholesale Market For Sellers
                    'h_premium_item1' => 'N',
//                    'selpremium_type' => '',
                    'txtpremium_gcash' => '',
                    'h_premium_item10' => 'N',
//                    'txtstickercnt' => '',
//                    'txtlimitstartdt' => '',
//                    'txtlimitenddt' => '',
                    'h_premium_item3' => 'N',
                    's_cost_price' => '',
                    'cost_unit' => 'R',
//                    's_sday' => '',
//                    's_eday' => '',
//                    's_shour' => '00',
//                    's_sminute' => '00',
//                    's_ehour' => '23',
//                    's_eminute' => '59',
//                    'h_pra#emium_mileage' => 'N',
                    'mileage_rate' => '',
                    'mileage_rate_type' => 'R',
                    'txtmileagestartdt' => '',
                    'txtmileageenddt' => '',
//                    'h_premium_charity' => 'N',
                    'charity_rate_type' => 'R',
                    'charity_rate' => '',
//                    'charity_fund_no' => '0',
//                    'charity_type' => 'D',
//                    'txtcharitystartdt' => '',
//                    'txtcharityenddt' => '',
                ],
                'giftSetInfo' => [ // we doesn't support
                    // Dont no the source // array must contain at least 1 child
                    'gift_set_yn' => 'N',
//                    'gender_type' => 'A',
//                    'age_all_yn' => 'Y',
//                    'age_list' => '',
//                    'gift_type_list' => '',
                ],
                'globalSetInfo' => [
                    // Dont no the source // array must contain at least 1 child
                    'global_add_goods' => 'N', // Y, N
//                    'des_gdlc_cd' => '', // if global_add_goods = Y, put category here
//                    'des_gdmc_cd' => '', // if global_add_goods = Y, put category here
//                    'des_gdsc_cd' => '', // if global_add_goods = Y, put category here
//                    'global_delivery_bundle_no' => '0',
//                    'global_delivery_group' => '0',
//                    'global_sub_delivery_group_list' => '0',
//                    'to_nation_cd' => '', // if global_add_goods = Y, fill in 'US' or other country
//                    'global_gd_nm' => '', // if global_add_goods = Y, put product's name here
//                    'auto_link_yn' => 'N',
//                    'global_delivery_link_type' => '',
//                    'global_delivery_oversea_type' => '',
//                    'global_translate_auto_yn' => 'N',
//                    'global_source_lang' => '',
//                    'global_target_lang' => '',
//                    'drugs_type' => '',
//                    'global_order_price' => '0',
//                    'global_order_amt' => '0',
                ],
                'groupBuyInfo' => [ // we doesn't support
                    // Group Buy Item Info // array must contain at least 1 child
                    'groupbuyitem' => 'N',
//                    'groupbuy_sell_price' => '',
//                    'groupbuy_settle_price' => '',
//                    'groupbuy_retail_price' => '',
//                    'groupbuy_qty' => '',
//                    'groupbuy_period' => '3',
//                    'group_buy_q_cash' => '',
//                    'groupbuy_rate' => '',
//                    'share_plus_target_cnt' => '0',
//                    'share_plus_order_per_grant_price' => '0',
//                    'share_plus_benefit_rate' => '0',
//                    'share_plus_end_dt' => '',
                ],
                'salesAgentInfo' => [ // we doesn't support
                    // Dont no the source // array must contain at least 1 child
                    'agent_add_goods' => 'N',
//                    'agent_to_nation_cd' => '',
//                    'agent_des_cust_no' => '',
//                    'agent_des_gdsc_cd' => '',
//                    'agent_category_auto_match' => '',
//                    'agent_src_gdsc_cd' => '',
//                    'agent_translate_auto_yn' => '',
//                    'agent_source_lang' => '',
//                    'agent_target_lang' => '',
//                    'agent_gd_weight' => '0',
                ],
                'contentsInfo' => [
                    // Item Description
                    'mime_value' => $data['html_description'] ?? $product->html_description,

                    // Dont no the source
//                    'goodsicon' => '',

                    // Basic Information > Item Image/Type
                    'goodscommon' => $data['images']['main']['1'] ?? '',
                    'goodszoom' => $data['images']['main']['0'] ?? '',
                    'goodsstill' => $data['images']['main']['B'] ?? '',
                    'image_type' => $data['image_type'] ?? 'S', // S - square, R - 612x800

                    // Images and Video > Header/Footer
                    'detail_header_url' => $data['header'] ?? '',
                    'detail_footer_url' => $data['footer'] ?? '',

                    // Basic Information > Item Image/Type
                    'multi_img_idx' => $data['images']['idx'] ?? '',
                    'multi_img_url' => $data['images']['url'] ?? '',

                    // Dont no the source
                    'multi_img_update_yn' => 'Y',
//                    'bi_contents_no' => '',
//                    'dh_contents_no' => '',
                    'video_info' => '',
                ],
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);
            return isset($json['d']) ? $json['d']['Rows'] : $json;
        })->wait();
    }

    /**
     * Get categories
     * Source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsRegistration.aspx
     * Description: get categories from api found in New Item Listing page
     *
     * @param string $stepName
     * @param string $categoryValue
     * @return mixed
     */
    public function getCategories($stepName = 'LC', $categoryValue = '')
    {
        // New Item Listing page > Basic Information > Category
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_CategoryBizService.asmx/GetCategoryDataMultiLang', [
            'json' => [
                'stepName' => $stepName,
                'CategoryValue' => $categoryValue,
                'svc_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
                'svc_lang_cd' => 'en',
                'is_admin' => 'N',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? $json['d']['Rows'] : $json;
        })->wait();
    }

    /**
     * extract products list
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsManagement.aspx
     * description: get products listing by using api
     *
     * @param string $externalId
     * @return mixed
     */
    public function getProducts($externalId = '')
    {
        $cache = Carbon::now()->setTimezone('Asia/Singapore')->format('D M d Y H:i:s GMT+0800 (+08)');
        // if got $externalId, means it come from create(), so status of newly created product is S0
        $statuses = empty($externalId) ? ['S2', 'S1'] : ['S2', 'S1', 'S0']; // S0 - Under Review, S1 - On Queue, S2 - Available, S3 - Suspended, S4 - Deleted, S5 - Restricted, S8 - Rejected

        // used to collect both S2 and S1 products if called by sync/import mode
        $products = [];
        foreach ($statuses as $status) {
            // Manage Listing & Edit > Search item
            $response =  $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsBizService.asmx/GetGoodsDataByCustNo', [
                // use this to test single product - status = Under Review
//            'json' => json_decode('{"gdlc_cd":"","gdmc_cd":"","local_svc_nation":"'.'SG'.'","gdsc_cd":"","srch_type":"1","srch_value":"'.'673174496'.'","goods_stat":"'.'S0'.'","trad_way":"T6","hasremain":"","cust_no":"'.$this->client->getSellerNo().'","dt_cd":"'.'0'.'","dt_fr":"'.'2012-01-01'.'","dt_to":"'.date('Y-m-d').'","lang_cd":"en","gd_type":"","ex_gd_type":"","___cache_expire___":"'.$cache.'"}')
                // use this to test products - status = Under Review
//            'json' => json_decode('{"gdlc_cd":"","gdmc_cd":"","local_svc_nation":"'.'SG'.'","gdsc_cd":"","srch_type":"1","srch_value":"'.''.'","goods_stat":"'.'S0'.'","trad_way":"T6","hasremain":"","cust_no":"'.$this->client->getSellerNo().'","dt_cd":"'.'0'.'","dt_fr":"'.'2012-01-01'.'","dt_to":"'.date('Y-m-d').'","lang_cd":"en","gd_type":"","ex_gd_type":"","___cache_expire___":"'.$cache.'"}')
                // used in production - status = Available
                'json' => json_decode('{"gdlc_cd":"","gdmc_cd":"","local_svc_nation":"'.'SG'.'","gdsc_cd":"","srch_type":"1","srch_value":"'.$externalId.'","goods_stat":"'.$status.'","trad_way":"T6","hasremain":"","cust_no":"'.$this->client->getSellerNo().'","dt_cd":"'.'0'.'","dt_fr":"'.'2012-01-01'.'","dt_to":"'.date('Y-m-d').'","lang_cd":"en","gd_type":"","ex_gd_type":"","___cache_expire___":"'.$cache.'"}')
            ])->wait();

            $response = json_decode($response->getBody(), true);

            if (isset($response['d']) && $response['d']['Rows']) {
                $products = array_merge($products, $response['d']['Rows']);
            }
        }
        return $products;
    }

    /**
     * extract single product
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsImageInfo.aspx?gd_no=$externalId
     * description: scrap data directly from qoo10 seller's edit product page
     *
     * @param $externalId
     * @return mixed
     */
    public function getProduct($externalId)
    {
        // use GoodsImageInfo(Images section) instead of GoodsModification(Edit Item Info section) because it load faster
        return $this->client->requestAsync('get', 'GMKT.INC.Gsm.Web/Goods/GoodsImageInfo.aspx?gd_no='.$externalId)->then(function($res){
            return (string) $res->getBody();
        })->wait();
    }

    /**
     * extract main product basic data
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsImageInfo.aspx?gd_no=$externalId
     * description: get main product basic data by using api found in Edit Listing page > Images tab
     *
     * @param $externalId
     * @return mixed
     */
    public function getProductBasicInfo($externalId)
    {
        // Edit Listing page > Images
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsBizService.asmx/GetGoodsInformations', [
            'json' => [
                'gd_no' => $externalId,
                'gubun' => 'BASE',
                'cust_no' => $this->client->getSellerNo(),
                'src_nation' => '',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? $json['d']['Rows'][0] : $json;
        })->wait();
    }

    /**
     * extract variants data (single)
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsSelInfo.aspx?gd_no=$externalId
     * description: get data by using api found in edit product page > Options section
     *
     * @param $externalId
     * @return mixed
     */
    public function getSingleVariants($externalId)
    {
        // edit product page > Options > Single Option
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/GetGoodsSelInfoSelect', [
            'json' => [
                'gd_no' => $externalId
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? $json['d']['Rows'] : $json;
        })->wait();
    }

    /**
     * extract variants data (matrix)
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsSelInfo.aspx?gd_no=$externalId
     * description: get data by using api found in edit product page > Options section
     *
     * @param $externalId
     * @param null|boolean $inventoryNew
     * @param int $retry // default run 2 times max
     * @return mixed
     * @throws \Exception
     */
    public function getMatrixVariants($externalId, $inventoryNew = null, $retry = 1)
    {
        if ($inventoryNew !== null) {
            // edit product page > Options > Combination (matrix) Option
            return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/GetInventoryCheckBySelinfo', [
                'json' => [
                    'gd_no'         => $externalId,
                    'inventory_new' => $inventoryNew ? 'Y' : 'N'
                ]
            ])->then(function($res){
                $json = json_decode($res->getBody(), true);

                return isset($json['d']) ? $json['d']['Rows'] : $json;
            })->wait();
        }


        $response = $this->getMatrixVariants($externalId, true);
        $response2 = $this->getMatrixVariants($externalId, false);

        // response got error or both response data mismatch > retry or throw error
        if (!is_array($response) || !is_array($response2) || isset($response['Message']) || isset($response2['Message']) || $response !== $response2) {
            // used to re-check it again in detail with sku
            // if true, means all variants sku in both side is same
            $deepCheckResult = true;
            if ($response !== $response2) {
                // get variants' sku list from 1st response
                $variantsSkuList = array_map(function ($variant) {
                    return $variant['simple_cd'];
                }, $response);

                foreach ($response2 as $index => $item) {
                    // compare it with 2nd response variants
                    if (!in_array($item['simple_cd'], $variantsSkuList)) {
                        $deepCheckResult = false;
                        break;
                    }
                }
            }

            // throw error if no more retry and variant data still mismatch
            if ($retry === 0 && !$deepCheckResult) {
                set_log_extra('externalId', $externalId);
                set_log_extra('response', $response);
                set_log_extra('response2', $response2);
                throw new \Exception('Qoo10 variants data mismatch.');
            }

            if (!$deepCheckResult) {
                return $this->getMatrixVariants($externalId, null, --$retry);
            }
        }

        return $response;
    }

    /**
     * extract options data
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsSelInfo.aspx?gd_no=$externalId
     * description: get data by using api found in edit product page > Options section
     *
     * @param $externalId
     * @return array
     */
    public function getOptions($externalId)
    {
        // edit product page > Options > Combination (matrix) Option > Set Item Type Info.
        $optionsData = $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/GetOptionGroupItemInfo', [
            'json' => [
                'gd_no'        => $externalId,
                'inventory_no' => '',
                'nation_cd'    => $this->account->region_id === 2 ? 'SG' : 'MY'
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? $json['d']['Rows'] : $json;
        })->wait();

        // edit product page > Options > Combination (matrix) Option > Set Item Type Info.
        $optionsTableData = $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/GetInventoryNamesWithDetail', [
            'json' => [
                'gd_no' => $externalId,
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return $json['d']['Rows'][0] ?? $json['d']['Rows'] ?? $json;
        })->wait();

        // create options group data base (if option's group data in $optionsData missing, will use the base generated data)
        $groupedData = [];
        if (count($optionsTableData) > 0) {
            for ($i = 1; $i <= count($optionsTableData)/2; $i++) {
                if (!empty($optionsTableData['sel_name' . $i]) && !empty($optionsTableData['sel_value' . $i])) {
                    $groupedData[$optionsTableData['sel_name'.$i]] =[];
                    $options = explode(',', $optionsTableData['sel_value' . $i]);

                    foreach ($options as $option) {
                        $groupedData[$optionsTableData['sel_name'.$i]][$option] = [
                            'level_slot_no' => $i,
                            'group_name' => $optionsTableData['sel_name'.$i],
                            'img_view_yn' => 'Y',
                            'group_type' => 'I',
                            'item_name' => $option,
                            'img_url' => '',
                            't_img_url' => '',
                            'color_code' => ''
                        ];
                    }
                }
            }
        }

        foreach ($optionsData as $optionData) {
            if (!array_key_exists($optionData['group_name'], $groupedData)) {
                $groupedData[$optionData['group_name']] = [];
            }
            $groupedData[$optionData['group_name']][$optionData['item_name']] = $optionData;
        }

        return $groupedData;
    }

    /**
     * extract inventory data
     * source: Edit Listing page > Options
     * description: get inventory data by using api found in edit product page > Options section
     *
     * @param string $externalId
     * @return array
     */
    public function getInventory($externalId)
    {
        // Options
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/GetGoodsInventoryManage', [
            'json' => [
                'gd_no' => $externalId,
                'nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? (isset($json['d']['Rows'][0]) ? $json['d']['Rows'][0] : $json['d']['Rows']) : $json;
        })->wait();
    }

    /**
     * extract seller commission rate data
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsRegistration.aspx
     * description: get data by using api found in New Item Listing page > Pricing and Quantity
     *
     * @param string $externalId
     * @param string $sellingPrice
     * @param array $categoryExternalIds
     * @param string $deliveryType
     * @return double|array
     */
    public function getCommissionRate($externalId, $sellingPrice, array $categoryExternalIds, $deliveryType = 'BI')
    {
        // New Item Listing page > Pricing and Quantity > Settle Price (S$) (After press List Item, this api will be called to calculate Settle Price)
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_CommissionBizService.asmx/GetSellerCommissionRate', [
            'json' => [
                'cust_no' => $this->client->getSellerNo(),
                'gd_no' => $externalId,
                'gdlc_cd' => $categoryExternalIds[0],
                'gdmc_cd' => $categoryExternalIds[1],
                'gdsc_cd' => $categoryExternalIds[2],
                'svc_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
                'trade_way' => 'T6',
                'sell_price' => $sellingPrice,
                'gd_type' => $deliveryType,
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? $json['d'] : $json;
        })->wait();
    }

    /**
     * extract selected product's price and quantity data
     * source: Get this api from v1, not sure about the source
     * description: get selected product's price and quantity data by using api
     *
     * @param $externalId
     * @return mixed
     */
    public function getPriceAndQuantity($externalId){
        // Not sure about the source
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_OrderBizService.asmx/GetOpenMarketGoodsInfo', [
            'json' => [
                'cust_no' => $this->client->getSellerNo(),
                'gd_no' => $externalId,
                'svc_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
//                '___cache_expire___' => date('D M d Y H:i:s O'),
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            return isset($json['d']) ? (isset($json['d']['Rows'][0]) ? $json['d']['Rows'][0] : $json['d']['Rows']) : $json;
        })->wait();
    }

    /**
     * extract shipping centers list
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/DeliveryGroupBundleModify.aspx
     * description: get list of seller's shipping centers by using api
     *
     * @return mixed
     */
    public function getShippingCenters()
    {
        // Shipping Rate/Form > Shipping Rate/Form
        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_SellerBizService.asmx/GetSellerBundleList', [
            'json' => [
                'seller_cust_no' => $this->client->getSellerNo()
//                '___cache_expire___' => date('D M d Y H:i:s O'),
            ]
        ]);

        $json = json_decode($response->getBody(), true);

        return $json['d']['Rows'] ?? $json;
    }

    /**
     * extract shipping rates list
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/DeliveryGroupBundleModify.aspx
     * description: get list of selected shipping center's shipping rates by using api
     * NOTE: alternative = GetSellerBundleAllGroupList api (this can get all except Qprime and Quick Prime)
     *
     * @param $shippingCenterId - delivery_bundle_no
     * @return mixed
     */
    public function getShippingRates($shippingCenterId)
    {
        // Shipping Rate/Form > Shipping Rate/Form >> Shipping Rate Summary
        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_SellerBizService.asmx/GetBundleDeliveryGroupListByType', [
            'json' => [
                'seller_cust_no' => $this->client->getSellerNo(),
                'delivery_bundle_no' => $shippingCenterId,
                'load_type' => 'N'
//                '___cache_expire___' => date('D M d Y H:i:s O'),
            ]
        ]);

        $json = json_decode($response->getBody(), true);

        return $json['d']['Rows'] ?? $json;
    }

    /**
     * extract Qprime list
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/DeliveryGroupBundleModify.aspx
     * description: get list of qprime by using api
     *
     * @return mixed
     */
    public function getQprimes()
    {
        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_DynamicDataService.asmx/ExecuteToDataTable', [
            'json' => [
                'id' => 'Delivery.GetQprimeDeliveryInfo',
                'paramList' => [
                    'ParamList' => [
                        [
                            'Name' => 'ship_nation_cd',
                            'Value' => 'SG',
                        ],
                        [
                            'Name' => 'svc_nation_cd',
                            'Value' => 'SG',
                        ],
                    ],
                ],
                '___cache_expire___' => time(),
            ]
        ]);

        $json = json_decode($response->getBody(), true);

        return $json['d']['ReturnData']['Rows'] ?? $json;
    }

    /**
     * extract Qprime data
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsRegistration.aspx
     * description: get qprime data by using api
     *
     * @param $externalId - delivery_group_no
     * @return mixed
     */
    public function getQprime($externalId)
    {
        // New Item Listing > Shipping Information
        $response = $this->client->request('post', 'GMKT.INC.Gsm.Web/swe_SellerBizService.asmx/GetQprimeDeliveryInfo', [
            'json' => [
                'delivery_group_no' => $externalId,
                'svc_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
                'seller_cust_no' => $this->client->getSellerNo(),
                'gd_no' => '',
            ]
        ]);

        $json = json_decode($response->getBody(), true);

        return $json['d']['Rows'][0] ?? $json['d']['Rows'] ?? $json;
    }

    /**
     * update product
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsModification.aspx?gd_no=$externalId
     * description: update product by using api found in Edit Listing page
     *
     * @param string $externalId
     * @param array $data
     * @return double|array
     */
    public function updateProduct($externalId, $data)
    {
        // Edit Listing page > Save
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsRegBizService.asmx/UpdateGoods', [
            'json' => [
                // Basic account and Listing Info
                'h_gd_no' => $externalId,
                'h_cust_no' => $this->client->getSellerNo(),
                'h_cust_gr' => 'C1', // A1 or C1?? // TODO: find out what is this
                'h_login_id' => $externalId,

                // Edit Item Info > Additional Item Information > Delivery Type
                'gd_type' => $data['attributes']['delivery_type']['value'] ?? 'BI',

                // Edit Item Info > Additional Item Information > Category
                'gdlc_cd' => $data['category'][0],
                'gdmc_cd' => $data['category'][1],
                'gdsc_cd' => $data['category'][2],

                // Dont no the source
                'h_selectedtrad_way' => 'T6',

                // Edit Item Info > Additional Item Information > Manufacturer // TODO: pull manufacturer
                'maker_nm' => '', // $data['manufacturer']['name'] selected from qoo10 account's manufacturer list
                'maker_no' => '', // $data['manufacturer']['id'] selected from qoo10 account's manufacturer list

                // Edit Item Info > Additional Item Information > Brand // TODO: after done brand change this
                'brand_nm' => '', // $data['brand']['name']selected from account's brand list
                'brand_no' => '0', // $data['brand']['id'] selected from account's brand list

                // Edit Item Info > Additional Item Information > Item Title
                'gd_nm' => $data['attributes']['name']['value'] ?? $data['name'],

                // Edit Item Info > Additional Item Information > Short Title
                'gd_short_nm' => $data['attributes']['short_title']['value'] ?? '',

                // Dont no the source
                'gd_eng_nm' => '',
                'gd_culture_nm' => '',

                // Edit Item Info > Additional Item Information > Product Model No
                'model_nm' => $data['attributes']['product_model_no']['value'] ?? '',

                // Edit Item Info > Additional Item Information > Seller Item Code
                'outer_gd_no' => $data['associated_sku'],

                // Edit Item Info > Additional Item Information > Industry Code
                'gd_ind_code' => $data['attributes']['industry_code']['value'] ?? '',
                'gd_ind_code_type' => $data['attributes']['industry_code_type']['value'] ?? 'UPC',

                // Edit Item Info > Additional Item Information > Reference ID Code > Simple Code
                'gd_simple_code' => $data['attributes']['simple_code']['value'] ?? '',

                // Edit Item Info > Additional Item Information > Reference ID Code > Other Site Reference Code
                'gd_ref_code' => $data['attributes']['other_code']['value'] ?? '',

                // Edit Item Info > Additional Item Information > Manufacture Date
                'txtgdmadedt' => isset($data['attributes']['manufacture_year']['value']) && isset($data['attributes']['manufacture_month']['value']) ? $data['attributes']['manufacture_year']['value'].$data['attributes']['manufacture_month']['value'] : '',

                // Dont no the source
                'txtgdappeardt' => '',

                // Edit Item Info > Additional Item Information > Production Place
                'gd_origin2' => $data['attributes']['origin_type']['value'] ?? 'K', // K > Domestic, F > Overseas, U > Others
                'gd_origin' => $data['attributes']['origin']['value'] ?? ($this->account->region_id === 2 ? 'Singapore' : 'Malaysia'),
                'gd_origin_cd' => $data['attributes']['origin']['value'] ?? ($this->account->region_id === 2 ? 'Singapore' : 'Malaysia'),

                // Edit Item Info > Additional Item Information > Item Condition
                'selgdkind1' => $data['attributes']['item_condition']['value'] ?? '10',
                'selgdkind2' => $data['attributes']['condition']['value'] ?? '00',
                'selgdkind3' => '01',
                'txtoldgdusedperiod' => $data['attributes']['period_of_use']['value'] ?? '',
                'txtoldgdusedstate' => $data['attributes']['brief_explanation']['value'] ?? '',

                // Dont no the source
                'tax_yn' => '1',

                // Edit Item Info > Additional Item Information > Adult Item?
                'hdnadultyn' => $data['attributes']['adult_item']['value'] ?? 'N',

                // Edit Item Info > Additional Item Information > After Sales Service
                'as_address' => $data['attributes']['address']['value'] ?? '',
                'as_tel_no' => $data['attributes']['phone_number']['value'] ?? '',
                'as_email' => $data['attributes']['email']['value'] ?? '',

                // Edit Item Info > Additional Item Information > Gift
                'op_gd' => $data['attributes']['gift']['value'] ?? '',

                // Dont no the source
                'refusal_h_gubun' => '',
                'refusal_e_gubun' => '',

                // Item Code
                'org_gd_no' => $externalId,

                // Dont no the source
                'h_userlocation' => 'GSM>Listing&Edit>EditItemInfo',

                // Edit Item Info > Additional Item Information > Delivery Type
                'gd_sub_type' => '',

                // Edit Item Info > Additional Item Information > Status
                'gd_stat' => '',

                // Edit Item Info > Additional Item Information > Brief Description
                'brief_description' => $data['attributes']['short_description']['value'] ?? $data['short_description'],

                // Multi-Language
                'multilang_lang' => '',
                'multilang_gd_nm' => '',
                'multilang_brief_nm' => '',

                // Dont no the source
                'auto_link_yn' => 'N',
                'gd_lang_cd' => 'en',

                // Edit Item Info > Additional Item Information > Material
                'gd_material' => $data['attributes']['material']['value'] ?? '',

                // Dont no the source
                'add_search_tag' => '',

                // Edit Item Info > Search Keyword > Item Title
                'search_keyword' => $data['search'] ?? '',
                'search_keyword_divide_queue_yn' => 'N',

                // Dont no the source
                'tag_keep_yn' => 'N',

                // Q-inventory // TODO: replace this after setup Q-inventory
                'txt_gdcode' => $data['q_inventory']['inventory_code'] ?? '', // $qInventory->q_inventory_code < v1 code
                'txt_seatcl' => $data['q_inventory']['option_code'] ?? '', // $qInventory->q_inventory_option_code < v1 code

                // Dont no the source
                'shopping_talk_display' => 'N',
                'expire_dt_display_day' => '0',
                'cod_available_yn' => 'N',
                'gd_notice_msg1' => '',
                'gd_notice_msg2' => '',
                'cate_specific_nos' => '',
                'nego_allow_yn' => 'N',
                'nego_allow_price' => '',
                'seller_memo' => '',
                'service_providing_url' => '',
                'providing_url_use_yn' => 'N',
                'drugs_type' => '',
                'add_purchase_group_types' => '',

                // Edit Item Info > Enter Price/Inventory > Minimum Order Limit
                'min_order_qty' => $data['attributes']['min_order_qty']['value'] ?? '0',

                // Dont no the source
                'wholesale_disp_type' => '',
                'wholesale_disp_values' => '',
                'resale_use_yn' => 'N',
                'sellershop_category_cds' => '',
                'vendor_cd' => '',
                'vendor_fee' => '',
                'recruit_reseller_yn' => 'Y',
                'sales_unit_use_yn' => 'N',
                'sales_unit_type' => '',
                'sales_unit_custom' => '',
                'sales_unit_symbol' => '',
                'unit_pack_cnt' => '0',

                // Edit Item Info > Additional Shipping Information > Set up standard shipping service > Seller setup shipping fee (S$)
                'delivery_fee_condition' => array_search($data['attributes']['logistics']['delivery_fee_type'], $this->deliveryFeeType) ?? '', // X - Free, M - Free on condition, F - standard

                // Edit Item Info > Additional Shipping Information > Set up standard shipping service > Standard Delivery Settings (SR Code) TODO: setup qoo10 shipping
                'delivery_group_no' => $data['attributes']['logistics']['external_id'], // 555651 - Qprime, 321774 - Free Shipping(MY)

                // Edit Item Info > Additional Shipping Information
                'delivery_group_type' => $data['attributes']['logistics']['type'], // 70 - Qprime-S Shipping , 60 - Others(user's defined)

                // Edit Item Info > Additional Shipping Information > Set up standard shipping service > Seller setup shipping fee (S$)
                'delivery_fee' => $data['attributes']['logistics']['delivery_fee'],
                'delivery_memo' => '',
                'basis_money' => $data['attributes']['logistics']['free_condition'], // Free Condition
                'return_addr_no' => $data['attributes']['locations']['external_id'],

                // Dont no the source
                'visitable' => 'N',
                'visit_addr_all_yn' => 'Y',
                'visit_addr_no' => '0',
                'visit_benefit_type' => 'N',
                'visit_benefit_price' => '0',
                'visit_benefit_memo' => '',
                'visit_request_msg' => '',
                'del_type' => 'bundle',
                'store_coupon_type' => '',
                'map_local_url' => '',
                'usable_type' => 'D',
                'usable_days' => '',
                'usable_sdt' => '',
                'usable_edt' => '',
                'cancel_pos_days' => '',
                'voucher_img' => '',
                'where_to_use_info' => '',
                'sel_send_type' => '0',
                'txt_send_date' => '',
                'txt_send_day' => '',

                // Edit Item Info > Additional Shipping Information > Set up standard shipping service > Return Address
                'pk_zipcode' => '', // or testing value: 608830 TODO: fill in this after setup address for qoo10 shipping (return address postal code)

                // Dont no the source
                'delivery_default_code' => '',
                'transc_cd' => '',
                'transc_nm' => '',

                // Edit Item Info > Additional Shipping Information > Weight input at Kg
                'goods_weight' => strval($data['attributes']['goods_weight']['value'] ?? ''), // default 0.5kg - qoo10 min

                // Dont no the source
                'del_hopeday' => '0',

                // Edit Item Info > Additional Shipping Information > Set up standard shipping service > Standard Delivery Settings > Qshipping group use settings : Country of shipment
                'dg_domestic_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',

                // Dont no the source
                'sub_delivery_group_no_list' => '',
                'sub_delivery_group_list' => '',
                'shop_location_type' => 'S',
                'seller_password_use_yn' => 'N',
                'branch_password_use_yn' => 'N',
                'h_visit_tir_no' => '',
                'voucher_code_show_yn' => 'Y',
                'multi_location_web_url' => '',
                'multi_location_mobile_url' => '',
                'redemption_type' => '',
                'voucher_auth_push_type' => 'N',
                'qprime_exception_nation_list' => '',
                'available_nation_cds' => '',
                'available_nation_type' => 'B',

                // Edit Item Info > Enter Price/Inventory > Retail Price (S$)
                'quotation_kind' => 'Q',
                'quotation_price' => strval($data['prices'][ProductPriceType::RETAIL()->getValue()] ?? 0),

                // Edit Item Info > Enter Price/Inventory > Notes To Display When Sold Out
                'restock_memo' => $data['attributes']['restock_memo']['value'] ?? '',
                'send_restock_mail_yn' => $data['attributes']['restock_mail']['value'] ?? 'Y',

                // Edit Item Info > Additional Item Information > Additional Info.
                'new_good_properties_names' => $data['attributes']['additional_info_title']['value'] ?? '',
                'new_good_properties_values' => $data['attributes']['additional_info_desc']['value'] ?? '',

                // Dont no the source
                'up_good_properties_seq_nos' => '',
                'up_good_properties_names' => '',
                'up_good_properties_values' => '',
                'del_good_properties_seq_nos' => '',
                'link_type' => 'N',
                'src_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if (isset($json['ExceptionType'])) {
                if (is_array($json['ExceptionType'])) {
                    return $json['ExceptionType'];
                }
                return $json['Message'] ?? 'Error update Qoo10 product.';
            }
            return [];
        })->wait();
    }

    /**
     * update qoo10's listing options stock and images
     * source: Edit Listing page > Options > Save
     * description: update qoo10's listing options stock and images by using api found in Edit Listing page
     *
     * @param string $externalId
     * @param string|int $inventoryNo
     * @param string $variantsString
     * @return string|array
     */
    public function updateVariantsStockAndImage($externalId, $inventoryNo, $variantsString)
    {
        // Options > Save
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/AddInventoryDataWithImg', [
            'json' => [
                'gd_no' => $externalId,
                'inventory_no' => trim($inventoryNo),
                'userid' => $this->client->getSellerNo(),
                'cust_no' => $this->client->getSellerNo(),
                'inventorydata' => $variantsString,
                'goodsInventoryType' => 'R',
                'userlocation' => 'GSM>Listing&Edit>Inventory',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if ($json['d']['ResultCode'] != 0) {
                return $json['d']['ResultMsg'] ?? $json;
            }
            return [];
        })->wait();
    }

    /**
     * update qoo10's listing options group
     * source: Edit Listing page > Options > Save
     * description: update qoo10's listing options group by using api found in Edit Listing page
     *
     * @param string $externalId
     * @param string|int $inventoryNo
     * @param string $optionsString
     * @return string|array
     */
    public function updateVariantsOptionGroup($externalId, $inventoryNo, $optionsString)
    {
        // Options > Save
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/OptionGroupManagement', [
            'json' => [
                'gd_no' => $externalId,
                'inventory_no' => trim($inventoryNo),
                'userid' => $this->client->getSellerNo(),
                'cust_no' => $this->client->getSellerNo(),
                'optiondata' => $optionsString,
                'userlocation' => 'GSM>Listing&Edit>Option&Inventory',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if ($json['d']['ResultCode'] != 0) {
                return $json['d']['ResultMsg'] ?? $json;
            }
            return [];
        })->wait();
    }

    /**
     * update product's price and quantity
     * source: Pricing/Qty > Edit Price / Inventory
     * description: update product's price and quantity by using api
     *
     * @param string $externalId
     * @param array $data // data extracted from getPriceAndQuantity()
     * @param $sellingPrice
     * @param $settlePrice
     * @param $stock
     * @return double|array
     */
    public function updatePriceAndQuantity($externalId, $data, $sellingPrice, $settlePrice, $stock)
    {
        // Pricing/Qty > Edit Price / Inventory
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_OrderBizService.asmx/UpdateOpenMarketOrder', [
            'json' => [
                'cust_no' => $this->client->getSellerNo(),
                'gd_no' => $externalId,
                'org_order_no' => $data['order_no'],
                'sell_money' => $sellingPrice ?? $data['sell_price'],
                'order_amt' => $stock ?? $data['order_amt'],
                'expire_dt' => $data['expire_dt'],
                'reg_id' => $data['cust_id'],
                'chg_ip' => '60.48.200.88',
                'chg_location' => 'GSM>OrderManagement>Edit Price/Inventory',
                'svc_nation_cd' => $data['src_nation_cd'],
                'settle_money' => $settlePrice ?? $data['settle_money'],
                'isDiscountDel' => 'N',
//                '___cache_expire___' => date('D M d Y H:i:s O'),
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if (isset($json['d']['ResultCode']) && $json['d']['ResultCode'] == 0) {
                // success
                return [];
            } elseif (isset($json['d']['ResultCode']) && $json['d']['ResultCode'] == '-1') {
                if ($json['d']['ResultMsg'] === 'Please select Expire Date.') {
                    return 'Item selling period has expired, you can extend it by changing the Available Period value in the Attributes section.';
                }
                return $json['d']['ResultMsg'];
            }

            return $json['d'] ?? $json;
        })->wait();
    }

    /**
     * update qoo10's listing options order
     * source: Edit Listing page > Options > Save
     * description: update qoo10's listing options order by using api found in Edit Listing page
     *
     * @param string $externalId
     * @param $optionOrder
     * @return string|array
     */
    public function updateOptionSortingType($externalId, $optionOrder)
    {
        // Options > Save
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsOptionBizService.asmx/SetOptionOrder', [
            'json' => [
                'gd_no' => $externalId,
                'order_type' => $optionOrder, // S, P, N // checkout Constant option_order for more detail info
                'display_type' => 'T',
                'opt_disp_type' => 'L',
                'inv_disp_type' => 'L',
                'inv_set_type' => 'N', // N - Make Combination/EXCEL, O - Option Template
                'inv_opt_selector_yn' => 'N',
//                '___cache_expire___' => date('D M d Y H:i:s O'),
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if (isset($json['d']) && $json['d']['returnCode'] !== 0) {
                return $json['d']['returnMessage'];
            }
            return [];
        })->wait();
    }

    /**
     * delete product
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Order/OrderManagement.aspx // not yet verify
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsModification.aspx?gd_no=$externalId
     * description: delete product by using api found in Edit Listing page
     * flow: try delete the listing directly, if fail, try delete the inventory then delete the listing again
     *
     * @param $externalId
     * @param bool $skipDeleteInventory
     * @return array // empty array means successfully deleted
     */
    public function deleteProduct($externalId, $skipDeleteInventory = true)
    {
        if (!$skipDeleteInventory) {
            // Pricing/Qty page > Delete sales info. // not yet verify
            $response = $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_OrderBizService.asmx/DeleteOpenMarketOrder', [
                'json' => [
                    'chg_location' => 'GSM>OrderManagement>Delete Sales info',
                    'cust_no' => $this->client->getSellerNo(),
                    'gd_no' => $externalId,
                    'reg_id' => $this->client->getSellerNo(),
                    'svc_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
                    'to_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
                    'chg_ip' => '0.0.0.0',
                ]
            ])->then(function($res){
                $json = json_decode($res->getBody(), true);

                if ($json['d']['ResultCode'] == 0) {
                    return null;
                } else {
                    return $json['d']['ResultMsg'] ?? $json;
                }
            })->wait();
        } else {
            // skip delete inventory for the first time
            $response = null;
        }


        if (is_null($response)) {
            // Edit Listing page > Delete
            $response = $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsBizService.asmx/DeleteGoods', [
                'json' => [
                    'cust_no' => $this->client->getSellerNo(),
                    'stat' => 'S4',
                    'gd_no' => $externalId,
                    'login_id' => $this->client->getSellerNo(),
                    'user_ip' => '0.0.0.0',
                ]
            ])->then(function($res){
                $json = json_decode($res->getBody(), true);

                if ($json['d']['ResultCode'] == 0) {
                    return null;
                } else {
                    return $json['d']['ResultMsg'] ?? $json;
                }
            })->wait();

            if (is_null($response)) {
                return [];
            } else {
                if ($skipDeleteInventory) {
                    return $this->deleteProduct($externalId, false);
                }
            }
        }
        return $response;
    }

    /**
     * convert image url to qoo10 image url
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsRegistration.aspx
     * description: get qoo10 image url by using api found in New Item Listing page > Basic Information
     *
     * @param $imageUrl
     * @param $type
     * @return double|array
     */
    public function uploadImage($imageUrl, $type)
    {
        // New Item Listing page > Basic Information > Item Image/Type
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsBizService.asmx/ProcessGoodsImage2', [
            'json' => [
                's_image' => $imageUrl,
                't_image' => '',
                'gd_no' => '',
                'chg_img_type' => $type, // B,0,1 - Item Image/Type, P - Enlarged Image
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if (isset($json['d']) && $json['d']['ResultCode'] == 0) {
                return str_replace('http://', 'https://', $json['d']['ResultObject']);
            } else {
                return null;
            }
        })->wait();
    }

    /**
     * convert image url to qoo10 image url
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsImageInfo.aspx?gd_no=$externalId
     * description: update main product description and image by using api found in Edit Listing page > Images tab
     *
     * @param $externalId
     * @param $data
     * @return null|string
     */
    public function updateDescriptionAndImage($externalId, $data)
    {
        // Edit Listing page > Images
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsRegBizService.asmx/UpdateGoodsImageInfo', [
            'json' => [
                // Basic account and Listing Info
                'h_gd_no' => $externalId,
                'h_cust_no' => $this->client->getSellerNo(),
                'h_login_id' => $this->client->getSellerNo(),
                'h_userlocation' => 'GSM>Listing&Edit>Images',

                // Category
                'gdlc_cd' => $data['category'][0],
                'gdmc_cd' => $data['category'][1],
                'gdsc_cd' => $data['category'][2],

                // Name
                'gd_nm' => $data['name'],

                // Dont no the source
                'gd_type' => null,
                'link_type' => 'N',

                // Basic account and Listing Info
                'src_nation_cd' => $this->account->region_id === 2 ? 'SG' : 'MY',
                'mime_value' => $data['html_description'],

                // Images > Images and Video > Item Image/Type
                'goodscommon' => $data['images']['main']['1'] ?? '',
                'goodszoom' => $data['images']['main']['0'] ?? '',
                'goodsstill' => $data['images']['main']['B'] ?? '',
                'image_type' => 'S', // S - square, R - 612x800

                // Images > Images and Video > Header/Footer
                'detail_header_url' => $data['attributes']['header']['value'] ?? '',
                'detail_footer_url' => $data['attributes']['footer']['value'] ?? '',

                // Images > Images and Video > Enlarged Image
                'multi_img_idx' => $data['images']['idx'] ?? '',
                'multi_img_url' => $data['images']['url'] ?? '',
                'multi_img_update_yn' => 'Y', // because always upload image to qoo10 to get new link

                // Dont no the source
                'bi_contents_no' => '',
                'dh_contents_no' => '', // $data['images']['dh_contents_no']

                // Images > Images and Video > Enlarged Image (we currently not support video upload)
                'txt_video_url' => $data['attributes']['video']['value'] ?? '',
                'video_no' => '0',
                'video_thum_img' => '',
                'video_duration' => '',
                'video_log_no' => '0',
                'df_video_img_yn' => 'N',
                'outer_video_account' => 'Q',
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if ($json['d']['ResultCode'] == 0) {
                return null;
            } else {
                return $json['d']['ResultMsg'] ?? $json;
            }
        })->wait();
    }

    /**
     * Update product status
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Goods/GoodsManagement.aspx > https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Popup/Goods/pop_GoodsBulkEdit.aspx?type=STAT
     * description: update qoo10 product status by using api found in Manage Listing & Edit page > Multi-item info. Changes
     *
     * @param $externalId
     * @param $status
     * @param string $header
     * @param string $footer
     * @return mixed
     */
    public function updateStatus($externalId, $status, $header = '', $footer = '')
    {
        // Manage Listing & Edit page > Multi-item info. Changes > Status (pop_GoodsBulkEdit)
        return $this->client->requestAsync('post', 'GMKT.INC.Gsm.Web/swe_GoodsBizService.asmx/UpdateGoodsStatusBulk', [
            'json' => [
                // Basic account and Listing Info
                'adult_yn' => '',
                'brand_no' => 0,
                'category_specific_kw_nos' => null,

                'chg_id' => '',
                'chg_ip' => '',
                'chg_location' => "GSM>PopGoodsBulkEdit>UpdateGoodsStatusBulk",

                'cod_able_yn' => '',
                'cust_no' => $this->client->getSellerNo(),
                'detail_footer_url' => $header,
                'detail_header_url' => $footer,
                'end_dt' => null,
                'exe_type' => 'STAT',

                'gd_no_array' => $externalId.'||',
                'gd_weight' => 0,
                'gdsc_cd' => "",
                'lang_cd' => "",
                'maker_no' => "",
                'material' => "",
                'mileage_rate' => null,
                'mileage_rate_type' => null,
                'min_order_qty' => null,
                'op_gd' => "",
                'order_limit_cnt' => null,
                'order_limit_end_dt' => null,
                'order_limit_period' => null,
                'order_limit_type' => "",
                'oversea_type' => "",
                'pick_addr_no' => "0",
                'sellershop_category_cds' => "",
                'send_date' => null,
                'send_day' => null,
                'send_type' => "",
                'start_dt' => null,
                'stat' => $status,
                'sticker_cnt' => null,
                'who_fee' => null
            ]
        ])->then(function($res){
            $json = json_decode($res->getBody(), true);

            if (isset($json['ExceptionType'])) {
                if (is_array($json['ExceptionType'])) {
                    return $json['ExceptionType'];
                }
                return $json['Message'] ?? 'Error update Qoo10 product status.';
            }
            return [];
        })->wait();
    }

    /**
     * KIV - - not using reference purpose
     *
     * Get short_description
     * Source: https://www.qoo10.sg/GMKT.INC/Goods/Goods.aspx?goodscode=$externalId
     * Description: scrap data directly from qoo10 marketplace's product page
     *
     * @param $externalId
     * @return string
     */
    public function getShortDescription($externalId)
    {
        $client = new \GuzzleHttp\Client();
        $page = $client->requestAsync('get', 'https://www.qoo10.sg/GMKT.INC/Goods/Goods.aspx?goodscode='.$externalId, [
            'headers' => [
                'User-Agent' => 'CombinesellBackend/v2.0',
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Cache-Control' => 'no-cache',
            ],
        ])->then(function($res){
            return (string) $res->getBody();
        })->wait();

        // find 'Brief Description' section
        $briefDescriptionSection = substr($page, strpos($page, '<th>Brief Description</th>'));
        $startPoint = strpos($briefDescriptionSection, 'itemprop="description">') + 23; // itemprop="description"> = 23 char
        $endPoint = strpos($briefDescriptionSection, '</td>');
        return trim(substr($briefDescriptionSection, $startPoint , $endPoint-$startPoint));
    }
    /* API Section - END */
}
