<?php
namespace App\Interfaces;

use App\Integrations\TransformedProduct;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductImportTask;
use App\Models\ProductInventory;
use App\Models\ProductListing;

interface ProductAdapterInterface
{

    /**
     * Retrieves all the transformed categories for the integration
     *
     * @return mixed
     */
    public function retrieveCategories();

    /**
     * Retrieves all the attributes for the IntegrationCategory
     *
     * @param IntegrationCategory $category
     *
     * @return mixed
     */
    public function retrieveCategoryAttribute(IntegrationCategory $category);

    /**
     * Import all new products
     *
     * @param ProductImportTask|null $importTask The import task if it's linked to any
     * @param array $config
     * @return mixed
     */
    public function import($importTask, $config);

    /**
     * Retrieves a single product
     *
     * @param ProductListing|null $listing
     * @param bool $update Whether or not to update the product if it already exists
     * @param null $itemId Retrieve by external id
     *
     * @return mixed
     */
    public function get(ProductListing $listing, $update = false, $itemId = null);

    /**
     * Syncs the product listing to ensure the stock is correct, deleted products are removed and also for
     * the product status to be accurate
     *
     * @return mixed
     */
    public function sync();

    /**
     * Pushes the update for the ProductListing
     *
     * @param ProductListing $product
     * @param array $data
     *
     * @return mixed
     */
    public function update(ProductListing $product, array $data);

    /**
     * Pushes the update for the stock in ProductListing.
     * NOTE: This should force an update of the listing after updating (Not updated locally prior to actual push)
     *
     * @param ProductListing $product
     *
     * @param $stock
     * @return mixed
     */
    public function updateStock(ProductListing $product, $stock, ?ProductInventory $productInventory = null);

    /**
     * Returns whether the product can be created for the integration
     * This normally should check locally if all the attributes are set and is valid
     *
     * @param Product $product
     *
     * @return boolean
     */
    public function canCreate(Product $product);

    /**
     * Creates a new product on the account from the product model
     *
     * @param Product $product
     *
     * @return mixed
     */
    public function create(Product $product);

    /**
     * Deletes the product from the integration
     *
     * @param ProductListing $product
     *
     * @return mixed
     */
    public function delete(ProductListing $product);

    /**
     * @param $product
     *
     * @return TransformedProduct
     */
    public function transformProduct($product);

}
