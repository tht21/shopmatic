<?php

namespace App\Integrations\Amazon;

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
use App\Jobs\AmazonProcessJob;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use ClouSale\AmazonSellingPartnerAPI\Api\CatalogApi;
use ClouSale\AmazonSellingPartnerAPI\Api\ProductPricingApi;
use ClouSale\AmazonSellingPartnerAPI\Api\ReportsApi;
use ClouSale\AmazonSellingPartnerAPI\Models\Reports\CreateReportSpecification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Events\ProductFailedToImport;

class ProductAdapter extends AbstractProductAdapter
{
    /**
     * Retrieves a single product
     *
     * @param ProductListing $listing
     * @param bool $update Whether or not to update the product if it already exists
     *
     * @param null $itemId
     * @return mixed
     * @throws \Exception
     */
    public function get(ProductListing $listing, $update = false, $itemId = null)
    {
        if (!empty($listing->product->associated_sku) && !is_null($listing->product->associated_sku)) {
            $startDate = new \DateTime('last year');
            $endDate = new \DateTime('+1 day');

            $product = $this->getProducts($startDate, $endDate, $listing->product->associated_sku);

            if ($product) {
                try {
                    $product = $this->transformProduct($product);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }
                return $this->handleProduct($product, ['update' => $update, 'new' => $update]);
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product item sku not found');
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
        $timeStart = microtime(true);
        $startDate = new \DateTime('last year');
        $endDate = new \DateTime('today');
        $products = $this->getProducts($startDate, $endDate);
        $executionTime = (microtime(true) - $timeStart) * 1000;
        $logMessage = 'Amazon Import All New Products From Amazon | Execution Time :' . $executionTime . ' Milliseconds';
        $this->logInfo($logMessage);

        try {
            if (!empty($products)) {
                if (!empty($importTask) && empty($importTask->total_products)) {
                    $importTask->total_products = count($products);
                    $importTask->save();
                }

                foreach ($products as $product) {
                    if (!empty($product)) {
                        try {
                            $product = $this->transformProduct($product);
                            $log_data[] = [$this->account->name, $product->listing->rawData['product-id'], $product->name, $this->account->integration->name, $this->account->integration->id, count($product->variants), $product->associatedSku, $product->status, $product->brand, $product->model, $product->category];
                            $this->handleProduct($product, $config);
                        } catch (\Exception $e) {
                            set_log_extra('product', $product);
                            if (!is_null($importTask)) {
                                $errorMessage = 'Amazon Import Task [' . $importTask->id . ']|Account id|' . $this->account->id . '|Message|' . $e->getMessage();
                                set_log_extra($errorMessage, $product);
                                \Log::error($errorMessage);
                                event(new ProductFailedToImport($importTask, (is_array($product) ? utf8_encode(substr($product['item-name'], 0, 200)) : $product->associatedSku) . ' failed to import'));
                            }
                            continue;
                        }
                    }
                }
            }

            if ($config['delete']) {
                $this->removeDeletedProducts();
            }
            return true;
        } catch (\Exception $e) {
            set_log_extra('response', $products);
            throw $e;
        }
        $executionTime = (microtime(true) - $timeStart) * 1000;
        $logMessage = 'Amazon Import All New Products From Amazon + CS handle | Execution Time :' . $executionTime . ' Milliseconds';
        $this->logInfo($logMessage);
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
        $timeStart = microtime(true);
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $startDate = new \DateTime('last year');
        $endDate = new \DateTime('today');

        $products = $this->getProducts($startDate, $endDate);

        if (!empty($products)) {
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
        $executionTime = (microtime(true) - $timeStart) * 1000;
        $logMessage = 'Amazon Sync Product Listing | Execution Time :' . $executionTime . ' Milliseconds';
        $this->logInfo($logMessage);
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

        $productData = [
            'sku' => $data['associated_sku'] ?? $product->identifiers['sku'],
            'title' =>  $data['attributes']['name']['value'] ?? $data['name'],
            'description' => $data['attributes']['short_description']['value'] ?? $data['short_description'],
            'product_id' => $data['attributes']['product-id']['value'],
            'product_id_type' => $data['attributes']['product-id-type']['value'],
            'condition' => $data['attributes']['condition']['value'],
            'quantity' => $data['stock'] ?? $firstVariant['inventory']['stock']
        ];

        $prices = [];
        $productPrices = ProductPrice::whereProductId($product->product_id)->whereNull('product_listing_id')->get();
        foreach (Constant::PRICES() as $priceType) {
            foreach ($productPrices as $price) {
                if ($price->type === $priceType->getValue()) {
                    $prices[$priceType->getValue()] = $price->price;
                    break;
                }
            }
        }
        $productData['price'] = number_format($prices[ProductPriceType::SELLING()->getValue()] ?? 0, 2, '.', '');

        // Create images
        /*$count = 0;
        foreach ($data['images'] as $name => $value) {
            if (!isset($value['deleted'])) {
                if (isset($value['data_url'])) {
                    $src = uploadImageFile($value['data_url'], session('shop'));
                } else {
                    $src =  $value['image_url'];
                }

                if ($count === 0) {
                    $mwsProduct->main_offer_image = $src;
                } else {
                    $property = 'offer_image'.$count;
                    $mwsProduct->{$property} = $src;
                }
            }
            $count++;
        }*/

        try {
            sleep(2);
            // Submit product first
            $productFeedResponse = $this->submitProductFeed('product', $productData);
            sleep(2);
            // Submit product inventory
            $inventoryFeedResponse = $this->submitProductFeed('inventory', $productData);
            sleep(2);
            // Submit product price
            $priceFeedResponse = $this->submitProductFeed('price', $productData);

            return $this->respondCreated(null);
        } catch (\Exception $exception) {
            set_log_extra('product_data', $productData);
            throw $exception;
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
        $this->rules = [
            'name.value' => 'required',
            'product-id.value' => 'required',
            'product-id-type.value' => 'required|in:EAN,GCID,GTIN,UPC,ASIN,ISBN',
            'condition.value' => 'required|in:New,Refurbished,UsedLikeNew,UsedVeryGood,UsedGood,UsedAcceptable',
        ];

        $this->errors = [];

        /* validation rules */
        parent::canCreate($product);

        if ((mb_strlen($product->associated_sku) < 1) || (strlen($product->associated_sku) > 40)) {
            $this->errors[] = 'Sku should be longer than 1 character and shorter than 40 characters';
        }

        $attributes = $product->attributes->where('product_variant_id', null)
            ->where('integration_id', $this->account->integration_id)
            ->where('region_id', $this->account->region_id)
            ->mapWithKeys(function ($item) {
                return [$item['name'] => $item];
            });

        $productIdLength = mb_strlen($attributes['product-id']->value ?? '');
        switch ($attributes['product-id-type']->value) {
            case 'ASIN':
                if ($productIdLength != 10) {
                    $this->errors[] = 'For ASIN product-id should be 10 characters long';
                }
                break;
            case 'UPC':
                if ($productIdLength != 12) {
                    $this->errors[] = 'For UPC product-id should be 12 characters long';
                }
                break;
            case 'EAN':
                if ($productIdLength != 13) {
                    $this->errors[] = 'For EAN product-id should be 13 characters long';
                }
                break;
            case 'JAN':
                if ($productIdLength != 13) {
                    $this->errors[] = 'For JAN product-id should be 13 characters long';
                }
                break;
            case 'GTIN':
                if ($productIdLength != 14) {
                    $this->errors[] = 'For GTIN product-id should be 14 characters long';
                }
                break;

            case 'ISBN':
                if ($productIdLength != 10 && $productIdLength != 13) {
                    $this->errors[] = 'For ISBN product-id should be 10 or 13 characters long';
                }
                break;
            case 'GCID':
                if ($productIdLength != 16) {
                    $this->errors[] = 'For GCID product-id should be 16 characters long';
                }
                break;
            default:
                $this->errors[] = 'product-id-type not one of: ASIN,UPC,EAN,ISBN,GCID,JAN';
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
        $timeStart = microtime(true);
        // Get main product attributes
        $attributes = $product->attributes()->whereIntegrationId(Integration::AMAZON)->whereNull('product_variant_id')->get()->mapWithKeys(function ($item) {
            return [$item['name'] => $item];
        });

        // Get Amazon price types
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

        // Get prices
        if (!isset($prices[ProductPriceType::SELLING()->getValue()])) {
            /** @var ProductPrice $price */
            $prices = $firstVariant->prices()->whereIn('type', $priceTypes)->get()->mapWithKeys(function ($item) {
                return [$item['type'] => $item];
            });
        }

        $productData = [
            'sku' => $product->associated_sku,
            'title' =>  $attributes['name']->value ?? $product->name,
            'description' => $attributes['short_description']->value ?? $product->short_description,
            'price' => number_format($prices[ProductPriceType::SELLING()->getValue()]->price ?? 0, 2, '.', ''),
            'product_id' => $attributes['product-id']->value,
            'product_id_type' => $attributes['product-id-type']->value ?? ProductIdentifier::ASIN()->getValue(),
            'condition' => $attributes['condition']->value ?? 'New',
            'quantity' => $firstVariant->inventory->stock ?? 0
        ];

        // Images
        /** @var ProductImage[] $images */
        /*$images = $product->allImages()->whereIntegrationId(Integration::AMAZON)->limit(9)->get();
        if (count($images)) {
            foreach ($images as $key => $image) {
                $productData['images'][$key] = $image->image_url;
            }
        }*/

        try {
            sleep(2);
            // Submit product first
            //$productFeedResponse = $this->submitProductFeed('product', $productData);
            $productFeedResponse = $this->submitProductFeed('product', $productData)->getPayload();
            sleep(2);
            // Submit product inventory
            //$inventoryFeedResponse = $this->submitProductFeed('inventory', $productData);
            $inventoryFeedResponse = $this->submitProductFeed('inventory', $productData)->getPayload();
            sleep(2);
            // Submit product price
            //$priceFeedResponse = $this->submitProductFeed('price', $productData);
            $priceFeedResponse = $this->submitProductFeed('price', $productData)->getPayload();
            $executionTime = (microtime(true) - $timeStart) * 1000;
            $logMessage = 'Amazon product creation | Execution Time :' . $executionTime . ' Milliseconds';
            $this->logInfo($logMessage);
            return $this->respondCreated(['productFeedId' => $productFeedResponse->getFeedId()]);
            //return $this->respondCreated(null);
        } catch (\Exception $e) {
            set_log_extra('product_data', $productData);
            throw $e;
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
        if (!empty($listing->product->associated_sku) && !is_null($listing->product->associated_sku)) {
            try {
                $this->submitProductFeed('delete_product', [
                    'sku' => $listing->product->associated_sku
                ]);
            } catch (\Exception $exception) {
                set_log_extra('listing', $listing);
                throw $exception;
            }
            return true;
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Product item sku not found');
    }

    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     * @throws \Exception
     */
    public function retrieveCategories()
    {
        $reportId = $this->client->getClient()->RequestReport('_GET_XML_BROWSE_TREE_DATA_');

        // Maximum retry 3 times to get report response
        $categories = false;
        $max = 3;
        for ($current = 1; $current <= $max; $current++) {
            // Wait a couple of seconds and get it's content
            sleep(10);
            $categories = $this->client->getClient()->GetReport($reportId);
            if ($categories !== false) {
                break;
            }
        }

        // This means that there's no error on the response
        if ($categories) {
            $categories = $categories['Node'];

            $results = collect($categories);
            $parents = $results->where('hasChildren', 'true');
            $data = [];

            foreach ($parents as $key => $parent) {
                $data[$key] = [
                    'name'          => $parent['browseNodeName'],
                    'breadcrumb'    => $parent['browseNodeName'],
                    'external_id'   => $parent['browseNodeId'],
                    'is_leaf'       => ($parent['hasChildren'] === 'true') ? 0 : 1,
                    'children'      => ($parent['hasChildren'] === 'true') ? $this->parseCategories($parent, $results->where('hasChildren', 'false'), $parent['browseNodeName']) : []
                ];
            }
            return $data;
        } else {
            set_log_extra('response', $categories);
            throw new \Exception('Unable to retrieve categories for Amazon');
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
        if ($parent['hasChildren'] == 'true') {
            // Some of childNodes id is not an array
            if (is_array($parent['childNodes']['id'])) {
                foreach ($parent['childNodes']['id'] as $childNode) {
                    $children = $categories->where('browseNodeId', $childNode)->first();

                    $breadcrumb = $parentBreadcrumb . ' > ' . $children['browseNodeName'];

                    $externalId = $children['browseNodeId'];
                    $leaf = ($children['hasChildren'] === 'true') ? 0 : 1;
                    $name = $children['browseNodeName'];
                    $children = $children['hasChildren'] == 1 ? $this->parseCategories($children, $categories, $breadcrumb) : [];

                    $result[] = [
                        'name'          => $name,
                        'breadcrumb'    => $breadcrumb,
                        'external_id'   => $externalId,
                        'is_leaf'  => $leaf,
                        'children' => $children,
                    ];
                }
            } else {
                $children = $categories->where('browseNodeId', $parent['childNodes']['id'])->first();

                $breadcrumb = $parentBreadcrumb . ' > ' . $children['browseNodeName'];

                $externalId = $children['browseNodeId'];
                $leaf = ($children['hasChildren'] === 'true') ? 0 : 1;
                $name = $children['browseNodeName'];
                $children = $children['hasChildren'] == 1 ? $this->parseCategories($children, $categories, $breadcrumb) : [];

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
     *
     * @return array
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category)
    {
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
        // Convert attributes and prices
        $productAttributes = json_decode($product['attributes'][0] ?? '', true);
        $productPrices = json_decode($product['prices'][0] ?? '', true);

        // Associated SKU will be the parent SKU
        $associatedSku = $product['seller-sku'];
        // Status can only be retrieved from the first variant
        $status = null;

        $mainProductIdentifier = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['product-id']];
        $shortDescription = $product['item-description'] ?? null;
        $htmlDescription = $product['item-description'] ?? null;
        $name = utf8_encode(substr($product['item-name'], 0, 200)) ?? null;
        $brand = $productAttributes['Brand'] ?? null;
        $model = $productAttributes['Model'] ?? null;

        //This is so we don't save duplicated data in our database
        $attributes = [];
        $productIdTypes = [
            1 => 'ASIN',
            2 => 'ISBN',
            3 => 'UPC',
            4 => 'EAN',
            5 => 'GCID',
            6 => 'GTIN'
        ];
        $attributes['product-id-type'] = $productIdTypes[$product['product-id-type']];
        $productConditions = [
            1 => 'UsedLikeNew',
            2 => 'UsedVeryGood',
            3 => 'UsedGood',
            4 => 'UsedAcceptable',
            5 => 'CollectibleLikeNew',
            6 => 'CollectibleVeryGood',
            7 => 'CollectibleGood',
            8 => 'CollectibleAcceptable',
            9 => 'Refurbished',
            10 => 'Refurbished',
            11 => 'New'
        ];
        $attributes['condition'] = $productConditions[$product['item-condition']];

        $attributes['product-id'] = $product['product-id'];
        $attributes['open-date'] = $product['open-date'];
        $attributes['item-note'] = $product['item-note'];
        $attributes['fulfillment-channel'] = $product['fulfillment-channel'];

        $options = [];

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = null;

        //Amazon doesn't support account category
        $accountCategory = null;

        $category = null;

        // Currently amazon sp api does not provide variants
        $variants = [];
        $productImages = [];
        //foreach ($product['detail'] as $variant) {

        // Amazon does not support names for the SKU, so we should implode from the option values, or use the default name
        $variantName = $name;

        $variantSku = $product['seller-sku'];

        // Amazon doesn't support barcodes
        $barcode = null;
        $stock = empty($product['quantity']) ? 0 : $product['quantity'];
        $prices = [];

        if (isset($productPrices['Product']['Offers'][0]['BuyingPrice']['ListingPrice']['Amount'])) {
            $sellerPrice = $productPrices['Product']['Offers'][0]['BuyingPrice']['ListingPrice']['Amount'];
        } else {
            $sellerPrice = empty($product['price']) ? 0 : $product['price'];
        }

        //Normal price
        $prices[] = new TransformedProductPrice($this->account->currency, $sellerPrice, ProductPriceType::SELLING());
        //Shipping price - Commenting the shipping price for Amazon -> CSM-576
        /*
        $shippingPrice = $productPrices['Product']['Offers'][0]['BuyingPrice']['Shipping']['Amount'] ?? 0;
        $prices[] = new TransformedProductPrice($this->account->currency, $shippingPrice, ProductPriceType::SHIPPING());
        */
        //Remove duplicated attributes
        $variantAttributes = $product;
        unset($variantAttributes['item-name'], $variantAttributes['item-description'], $variantAttributes['price'], $variantAttributes['quantity'], $variantAttributes['status'], $variantAttributes['attributes'], $variantAttributes['prices']);

        // Amazon only return small pixel image hence need change to larger pixel image
        $image = '';
        if (isset($productAttributes['SmallImage']) && isset($productAttributes['SmallImage']['URL'])) {
            $image = str_replace("SL75", "UL800", $productAttributes['SmallImage']['URL']);
        } else {
            Log::info('Amazon Product does not have SmallImage. associatedSku: ' . $associatedSku);
        }
        $images[] = new TransformedProductImage($image);
        // Amazon return one image only
        if (empty($productImages)) {
            $productImages[] = new TransformedProductImage($image);
        }
        $weightUnit = Weight::POUNDS();
        $weight = $productAttributes['PackageDimensions']['Weight']['value'] ?? 0;

        $shippingType = ShippingType::MARKETPLACE();
        $dimensionUnit = Dimension::INCH();
        $length = $productAttributes['PackageDimensions']['Length']['value'] ?? 0;
        $width = $productAttributes['PackageDimensions']['Width']['value'] ?? 0;
        $height = $productAttributes['PackageDimensions']['Height']['value'] ?? 0;

        $productUrl = null;

        $identifiers = [
            ProductIdentifier::EXTERNAL_ID()->getValue() => $product['product-id'],
            ProductIdentifier::SKU()->getValue() => $product['seller-sku'],
        ];

        // Amazon will have different kind of product id type
        /*$productIdTypes = ['ASIN', 'ISBN', 'UPC', 'EAN', 'GCID'];
        foreach ($productIdTypes as $productIdType) {
            if (isset($variant[$productIdType])) {
                if ($productIdentifier = ProductIdentifier::searchKey($productIdType)) {
                    $identifiers[$productIdentifier] = $product['product-id'];
                } else {
                    set_log_extra('product', $product);
                    throw new \Exception('Invalid Amazon product type id.');
                }
            }
        }*/

        $option1 = null;
        $option2 = null;
        $option3 = null;

        $mpStatus = trim(strtolower($product['status']));

        if ($mpStatus === 'active') {
            $status = ProductStatus::LIVE();
            $marketplaceStatus = MarketplaceProductStatus::LIVE();
        } elseif ($mpStatus === 'inactive') {
            $status = ProductStatus::DISABLED();
            $marketplaceStatus = MarketplaceProductStatus::DISABLED();
        } elseif ($mpStatus === 'incomplete') {
            $marketplaceStatus = MarketplaceProductStatus::PENDING();
            $status = ProductStatus::DRAFT();
        } else {
            set_log_extra('status', $mpStatus);
            throw new \Exception('Invalid Amazon product status.');
        }

        $rawVariantData = $variantAttributes;

        $variantListing = new TransformedProductListing(
            $variantName,
            $identifiers,
            $integrationCategory,
            $accountCategory,
            $prices,
            $productUrl,
            $stock,
            $variantAttributes,
            $rawVariantData,
            $images,
            $marketplaceStatus
        );

        $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, null);
        //}

        // Amazon doesn't have a product URL
        $productUrl = null;

        // Price for main product
        $mainPrices = null;

        $rawProduct = $attributes;

        // Setting the status for the main product to live because not sure what else to set here, unless we calculate
        // based on the statuses above to see if there's any that's live, or we use the last value
        $listing = new TransformedProductListing($name, $mainProductIdentifier, $integrationCategory, $accountCategory, $mainPrices, $productUrl, null, $attributes, $rawProduct, $productImages, MarketplaceProductStatus::LIVE());

        $product = new TransformedProduct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $category, $status, $variants, $options, $listing, $productImages);

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
    public function updateStock(ProductListing $listing, $stock, ?ProductInventory $productInventory = null)
    {
        if (!empty($listing->product->associated_sku) && !is_null($listing->product->associated_sku)) {
            try {
                $data = [
                    'sku' => $listing->product->associated_sku,
                    'quantity' => $stock
                ];

                $response = $this->submitProductFeed('inventory', $data)->getPayload();

                AmazonProcessJob::dispatch('inventory', $this->account, [
                    'listing_id' => $listing->id,
                    'stock' => $stock
                ], $response->getFeedId())->delay(now()->addMinutes(10));

                return true;
            } catch (\Exception $e) {
                set_log_extra('response', $response ?? null);
                set_log_extra('listing', $listing);
                throw $e;
            }
        } else {
            set_log_extra('listing', $listing);
            throw new \Exception('Amazon product does not have product sku');
        }
    }

    /**
     * Convert document content string into array
     *
     * @param $documentContent
     * @return array
     */
    private function convertDocumentContents($documentContent)
    {
        // Seperate break line into array
        $contents = explode("\n", $documentContent);
        $products = [];
        $headers = null;
        $count = 0;

        foreach ($contents as $contentKey => $content) {
            // First row will be the headers
            if ($contentKey === 0) {
                // Get all the headers value eg: item-title, item-description, listing-id
                $headers = explode("\t", $content);
            } else if (!empty($content) && !is_null($headers)) {
                // Second row onward will be the product
                $values = explode("\t", $content);
                foreach ($values as $keyValue => $value) {
                    $products[$count][$headers[$keyValue]] = $value;
                }
                $count++;
            }
        }
        return $products;
    }

    /**
     * Get all products
     *
     * @param $startDate
     * @param $endDate
     * @param null $sellerSku
     *
     * @return array
     * @throws \Exception
     */
    public function getProducts($startDate, $endDate, $sellerSku = null)
    {
        $spConfig = $this->client->getSPConfig();

        $reportsApiInstance = new ReportsApi($spConfig);

        $body = new CreateReportSpecification([
            'report_type' => 'GET_MERCHANT_LISTINGS_ALL_DATA',
            'data_start_time' => $startDate,
            'data_end_time' => $endDate,
            'marketplace_ids' => [$this->account->credentials['marketplace_id']]
        ]);
        $products = [];

        try {
            // Create report
            $reportId = $reportsApiInstance->createReport($body)->getPayload()->getReportId();

            // Maximum retry 3 times to get report response
            $max = 3;
            for ($current = 1; $current <= $max; $current++) {
                // Wait a couple of seconds and get it's content
                sleep(10);
                // Get report
                $report = $reportsApiInstance->getReport($reportId)->getPayload();
                Log::info('Trying to get report. Attemp: ' . $current . ' and recive status ' . $report->getProcessingStatus());
                if ($report->getProcessingStatus() === 'DONE') {
                    break;
                }
            }

            // if report still under process again, then lastly wait for another 1min
            if (is_null($report->getReportDocumentId())) {
                Log::info('Trying to get report and recive status ' . $report->getProcessingStatus());
                if ($report->getProcessingStatus() !== 'DONE') {
                    sleep(60);
                    $report = $reportsApiInstance->getReport($reportId)->getPayload();
                }
            }
            if (is_null($report->getReportDocumentId())) {
                throw new \Exception('Can not getReportDocumentId at this time. Please try again later');
            }
            // Obtain report document
            $reportDocument = $reportsApiInstance->getReportDocument($report->getReportDocumentId());

            // Decrypt document
            $key = base64_decode($reportDocument->getPayload()->getEncryptionDetails()->getKey());
            $iv = base64_decode($reportDocument->getPayload()->getEncryptionDetails()->getInitializationVector());
            $data = openssl_decrypt(file_get_contents($reportDocument->getPayload()->getUrl()), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            // Convert document raw data to array format
            $products = $this->convertDocumentContents($data);

            if (count($products) > 0) {
                $catalogInstance = new CatalogApi($spConfig);
                $pricingInstance = new ProductPricingApi($spConfig);

                // Either all products or by specific sku
                if (is_null($sellerSku)) {
                    $productSkus = [];
                    foreach ($products as $key => $product) {
                        $productSkus[] = $product['seller-sku'];
                        sleep(2);
                        $catalogItemResponse = $catalogInstance->getCatalogItem($this->account->credentials['marketplace_id'], $product['asin1']);
                        $products[$key]['attributes'] = $catalogItemResponse->getPayload()->getAttributeSets();

                        // Get pricing information for a seller's offer listings based on seller SKU or ASIN
                        // List of up to twenty seller SKU values
                        if (count($productSkus) === 20) {
                            // Seller sku cannot contain space
                            $productSkus = array_filter($productSkus, function ($productSku) {
                                return !strpos($productSku, ' ');
                            });
                            $productsPricing = $pricingInstance->getPricing($this->account->credentials['marketplace_id'], 'Sku', null, $productSkus);

                            foreach ($productsPricing->getPayload() as $priceKey => $productPricing) {
                                $key = array_search($productPricing['seller_sku'], array_column($products, 'seller-sku'));
                                if ($key !== false) {
                                    $products[$key]['prices'] = $productPricing;
                                }
                            }
                            // Refresh product skus
                            $productSkus = [];
                        }
                    }
                } else {
                    $products = collect($products);

                    if ($product = $products->where('seller-sku', $sellerSku)->first()) {
                        sleep(2);
                        $catalogItemResponse = $catalogInstance->getCatalogItem($this->account->credentials['marketplace_id'], $product['asin1']);
                        $product['attributes'] = $catalogItemResponse->getPayload()->getAttributeSets();

                        // Seller sku cannot contain space
                        if (strpos($product['seller-sku'], ' ') === false) {
                            $productPricing = $pricingInstance->getPricing($this->account->credentials['marketplace_id'], 'Sku', null, [$product['seller-sku']]);
                            $product['prices'] = $productPricing->getPayload();
                        }
                    }
                    return $product;
                }
            }
        } catch (\Exception $e) {
            set_log_extra('response', $products);
            if (isset($report)) {
                set_log_extra('report', $report);
            } else {
                set_log_extra('report error', 'Do not get report');
            }
            throw $e;
        }

        return $products;
    }


    /**
     * Submit feed for product
     *
     * @param $type
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    private function submitProductFeed($type, $data)
    {
        try {
            if ($type === 'product') {
                $feedType = 'POST_PRODUCT_DATA';
                $array = [
                    'MessageType' => 'Product',
                    'Message' => [
                        'MessageID' => rand(),
                        'OperationType' => 'Update',
                        'Product' => [
                            'SKU' => $data['sku'],
                            'StandardProductID' => [
                                'Type' => $data['product_id_type'],
                                'Value' => $data['product_id']
                            ],
                            'Condition' => [
                                'ConditionType' => $data['condition']
                            ],
                            'DescriptionData' => [
                                'Title' => $data['title'],
                                'Description' => $data['description'],
                            ]
                        ]
                    ]
                ];
            } else if ($type === 'inventory') {
                $feedType = 'POST_INVENTORY_AVAILABILITY_DATA';
                $array = [
                    'MessageType' => 'Inventory',
                    'Message' => [
                        'MessageID' => rand(),
                        'OperationType' => 'Update',
                        'Inventory' => [
                            'SKU' => $data['sku'],
                            'Quantity' => (int)$data['quantity']
                        ]
                    ]
                ];
            } else if ($type === 'price') {
                $feedType = 'POST_PRODUCT_PRICING_DATA';
                $array = [
                    'MessageType' => 'Price',
                    'Message' => [
                        'MessageID' => rand(),
                        'Price' => [
                            'SKU' => $data['sku'],
                            'StandardPrice' => [
                                '_value' => strval($data['price']),
                                '_attributes' => [
                                    'currency' => 'DEFAULT'
                                ]
                            ]
                        ]
                    ]
                ];
            } else if ($type === 'delete_product') {
                $feedType = 'POST_PRODUCT_DATA';
                $array = [
                    'MessageType' => 'Product',
                    'Message' => [
                        'MessageID' => rand(),
                        'OperationType' => 'Delete',
                        'Product' => [
                            'SKU' => $data['sku']
                        ]
                    ]
                ];
            } else {
                set_log_extra('type', $type);
                throw new \Exception('Invalid submit product feed type for Amazon');
            }

            $response = $this->client->submitFeed($feedType, $array);

            return $response;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
    /**
     * Log Info
     */
    public function logInfo($message)
    {
        $message = '[Account ID: ' . $this->account->id . '] ' . $message;
        \Log::channel(snake_case(Integration::INTEGRATIONS[$this->account->integration_id]))->info($message);
    }
}
