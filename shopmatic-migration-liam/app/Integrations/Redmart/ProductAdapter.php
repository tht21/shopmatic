<?php

namespace App\Integrations\Redmart;

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
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductInventory;

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
    public function get($listing, $update = false, $itemId = null)
    {
        $filters = [];
        $products = collect($this->fetchAllProducts($filters));
        $externalId = ($itemId) ?? $listing->identifiers['external_id'];

        $product = $products->firstWhere('id', $externalId);

        try {
            $product = $this->transformProduct($product);
        } catch (\Exception $e) {
            set_log_extra('product', $product);
            throw $e;
        }

        $this->handleProduct($product, ['update' => $update, 'new' => $update]);
    }

    /**
     * Import all new products from redmart
     *
     * @param $importTask
     * @param array $config
     * @return bool
     * @throws \Exception
     */
    public function import($importTask, $config)
    {
        $filters = [];

        $products = collect($this->fetchAllProducts($filters));

        if (is_object($products) && !empty($products)) {
            if (!empty($importTask) && empty($importTask->total_products)) {
                $importTask->total_products = $products->count();
                $importTask->save();
            }

            foreach ($products as $product) {
                if (!empty($product)) {
                    try {
                        $product = $this->transformProduct($product);
                    } catch (\Exception $e) {
                        set_log_extra('product', $product);
                        throw $e;
                    }
                    $this->handleProduct($product, $config);
                }
            }
        }

        if ($config['delete']) {
            $this->removeDeletedProducts();
        }

        return true;
    }

    /**
     * Get all products from redmart integration
     *
     * @param array $filters
     * @return mixed
     */
    public function fetchAllProducts($filters = [])
    {
        $response = $this->client->request('GET', 'product', $filters);

        $products = json_decode($response->getBody()->getContents(), true);

        return $products;
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @throws \Exception
     */
    public function sync()
    {
        // No point syncing as there's no listings under this account yet.
        if ($this->account->listings()->count() === 0) {
            return;
        }

        $filters = [];
        $products = collect($this->fetchAllProducts($filters));

        if (is_object($products) && !empty($products)) {
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
    }

    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     *
     */
    public function update(ProductListing $product, array $data)
    {
        // Does not support
    }

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     *
     */
    public function canCreate(Product $product)
    {
         // Does not support
    }

    /**
     * Creates a new product on the account from the product model
     *
     * @param Product $product
     *
     */
    public function create(Product $product)
    {
        // Does not support
    }

    /**
     * Deletes the product from the integration
     *
     * @param ProductListing $listing
     */
    public function delete(ProductListing $listing)
    {
        // Does not support
    }

    /**
     * Retrieves all the transformed categories for the integration
     *
     */
    public function retrieveCategories()
    {

    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     *
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
        // Associated SKU will be the parent SKU
        $associatedSku = ($product['sku']) ?? null;

        $description = $htmlDescription = null;
        $name = $product['title'] ?? null;

        $options = [];

        /** @var IntegrationCategory $integrationCategory */
        $integrationCategory = null;

        $accountCategory = null;

        $category = null;

        $weightUnit = Weight::KILOGRAMS();
        $weight = 0;

        $shippingType = ShippingType::MARKETPLACE();
        $dimensionUnit = Dimension::CM();
        $length =  0;
        $width = 0;
        $height =  0;

        $productUrl = $product['uri'];

        // Redmart does not have variation, take main product as variation
        $variants = [];
        $variation = $product;

        // Add a default variant if does not exists (data based on main product)
        $variantName = $product['title'];
        $variantSku = $product['sku'];

        // Redmart barcode same as sku
        $barcode = $product['sku'];
        $stock = $product['availableStock'] ?? 0;
        $prices = [];

        // Normal price
        $prices[] = new TransformedProductPrice($this->account->currency, $product['unitPrice'], ProductPriceType::SELLING());

        // Special price
        $specialPrice = (empty($product['promotionPrice']) || $product['promotionPrice'] > 0) ? $product['unitPrice'] : $product['promotionPrice'];
        $prices[] = new TransformedProductPrice($this->account->currency, $specialPrice, ProductPriceType::SPECIAL());

        // Variant attribute will be same as main product attribute, no need to duplicate
        $variantAttributes = [];

        // Get more clearer image
        $productImage = str_replace("80x","300x",$product['image']);
        $images[] = new TransformedProductImage($productImage);

        $identifiers = [
            ProductIdentifier::EXTERNAL_ID()->getValue() => $product['id'],
            ProductIdentifier::SKU()->getValue() => $variantSku,
        ];

        $option1 = null;
        $option2 = null;
        $option3 = null;

        $mpStatus = $product['status'];
        if ($mpStatus == 1) {
            $status = ProductStatus::LIVE();
            $marketplaceStatus = MarketplaceProductStatus::LIVE();
        } else if ($mpStatus == 0) {
            $status = ProductStatus::DISABLED();
            $marketplaceStatus = MarketplaceProductStatus::DISABLED();
        } else {
            set_log_extra('product', $product);
            throw new \Exception('Unknown product status for Redmart');
        }

        // Check for stock status
        if (isset($product['stockStatus']) && $product['stockStatus'] === 'OOS') {
            $status = ProductStatus::OUT_OF_STOCK();
            $marketplaceStatus = MarketplaceProductStatus::OUT_OF_STOCK();
        }

        $variantListing = new TransformedProductListing($variantName, $identifiers, $integrationCategory,
            $accountCategory, $prices, $productUrl, $stock, $variantAttributes, $variation, $images, $marketplaceStatus);

        $variants[] = new TransformedProductVariant($variantName, $option1, $option2, $option3, $variantSku, $barcode, $stock, $prices, $status, $shippingType, $weight, $weightUnit, $length, $width, $height, $dimensionUnit, $variantListing, null);

        $identifiers = [ProductIdentifier::EXTERNAL_ID()->getValue() => $product['id']];

        // Product status
        $productStatus = trim(strtolower($product['status']));
        if ($productStatus == 1) {
            $status = ProductStatus::LIVE();
        } else if ($productStatus == 0) {
            $status = ProductStatus::DISABLED();
        } else {
            set_log_extra('product', $product);
            throw new \Exception('Unknown product status for Redmart');
        }

        // Check for stock status
        if (isset($product['stockStatus']) && $product['stockStatus'] === 'OOS') {
            $status = ProductStatus::OUT_OF_STOCK();
        }

        // Product images
        $images[] = new TransformedProductImage($productImage);

        // Price for main product
        $mainPrices = null;

        //This is so we don't save duplicated data in our database for main product attribute
        $attributes['vpc'] = $product['vpc'] ?? '';
        $attributes['stock_status'] = $product['stockStatus'] ?? '';
        $attributes['reason_code'] = $product['reasonCode'] ?? '';
        $attributes['savings_text'] = $product['savingsText'] ?? '';

        // Setting the status for the main product to live because not sure what else to set here, unless we calculate
        // based on the statuses above to see if there's any that's live, or we use the last value
        $listing = new TransformedProductListing($name, $identifiers, $integrationCategory, $accountCategory, $mainPrices, $productUrl, null, $attributes, $product, $images, MarketplaceProductStatus::LIVE());

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
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null)
    {
        $externalId = $product->getIdentifier(ProductIdentifier::EXTERNAL_ID());
        if (empty($externalId)) {
            set_log_extra('listing', $product);
            throw new \Exception('Redmart product does not have product external id');
        }

        $parameter = [
            'json' => [
                'availableStock' => $stock,
            ]
        ];

        try {
            $response = $this->client->request('PATCH', 'product/' . $externalId . '/availableStock', $parameter);

            // As Redmart doesn't return the updated product, we should refresh it here
            $this->get($product);

            return true;
        } catch (\Exception $e) {
            set_log_extra('response', $response ?? null);
            set_log_extra('listing', $product);
            throw $e;
        }
    }
}
