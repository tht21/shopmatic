<?php

namespace App\Integrations\IHub;

use App\Constants\Dimension;
use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductIdentifier;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use App\Integrations\AbstractProductAdapter;
use App\Integrations\TransformedProduct;
use App\Integrations\TransformedProductListing;
use App\Integrations\TransformedProductVariant;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductListing;

class ProductAdapter extends AbstractProductAdapter
{

    /**
     * Retrieves a single product
     *
     * @param ProductListing $listing
     * @param bool $update Whether or not to update the product if it already exists
     *
     * @param null $itemId
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function get(ProductListing $listing, $update = false, $itemId = null)
    {

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

        $response = $this->client->request('get', 'api/Client/GetClientItemStock', []);
        $products = $response['responseList'];

        if (!empty($products)) {

            if (!empty($importTask) && empty($importTask->total_products)) {
                $importTask->total_products = count($products);
                $importTask->save();
            }

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

        return true;
    }

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sync()
    {

    }

    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     *
     * @return mixed
     */
    public function update(ProductListing $product, array $data)
    {

    }

    /**
     * Convert frontend data to lazada's xml format
     *
     * @param $data
     * @return string
     */
    public function dataToXml($data)
    {

    }

    /**
     * @param \DOMDocument $document
     * @param \DOMElement|\DOMDocument $currentElement
     * @param array|string $xmlData
     * @param null $childName
     */
    private function arrayToDOMDoc(\DOMDocument &$document, &$currentElement, $xmlData, $childName = null)
    {

    }

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     *
     * @return boolean
     */
    public function canCreate(Product $product)
    {

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

    }

    /**
     * Upload image to lazada
     *
     * @param $images
     * @return array
     * @throws \Exception
     */
    public function uploadImage($images)
    {

    }

    /**
     * Deletes the product from the integration
     *
     * @param \App\Models\ProductListing $listing
     * @return bool
     * @throws \Exception
     */
    public function delete(ProductListing $listing)
    {

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

    }

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        $clientCode = $product['clientCode'];
        $sku = $product['itemCode'];
        $quantity = $product['availableQty'];
        $description = $product['itemDescription'] ?? null;
        $status = ProductStatus::DISABLED();
        $marketplaceStatus = MarketplaceProductStatus::DISABLED();
        $shippingType = ShippingType::MARKETPLACE();
        $weightUnit = Weight::KILOGRAMS();
        $dimensionUnit = Dimension::CM();

        $identifiers = [
            ProductIdentifier::EXTERNAL_ID()->getValue() => $sku,
            ProductIdentifier::SKU()->getValue() => $sku,
        ];

        $variantListing = new TransformedProductListing(null, $identifiers, null, null, null, null, null, [], $sku, null, $marketplaceStatus);
        $variants[] = new TransformedProductVariant(null, null, null, null, $sku, null, $quantity, null, $status, $shippingType, 0, $weightUnit, 0, 0, 0, $dimensionUnit, $variantListing, null);

        $listing = new TransformedProductListing(null, $identifiers, null, null, null, null, null, [], $sku, null, $marketplaceStatus);

        $product = new TransformedProduct($clientCode, $sku, $description, null, null, null, null, $status, $variants, null, $listing, null);

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

    }

}
