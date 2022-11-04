<?php

namespace App\Integrations;

use App\Constants\Dimension;
use App\Constants\ProductAlertType;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use App\Events\NewProductAlert;
use App\Jobs\SyncInventory;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTrail;
use App\Models\ProductVariant;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;
use App\Constants\ProductIdentifier;
class TransformedProductVariant
{

    public $name;
    public $option1;
    public $option2;
    public $option3;
    public $sku;
    public $barcode;
    public $stock;
    public $prices;
    public $status;
    public $shippingType;
    public $weight;
    public $weightUnit;
    public $length;
    public $width;
    public $height;
    public $dimensionUnit;
    public $listing;
    public $customizable;

    /**
     * TransformedProduct constructor.
     *
     *
     * @param string $name Name of the variant
     * @param string|null $option1 Option 1 of the variant if option set in Product
     * @param string|null $option2 Option 2 of the variant if option set in Product
     * @param string|null $option3 Option 3 of the variant if option set in Product
     * @param string $sku Actual SKU used for inventory syncing
     * @param string $barcode Barcode if the product supports barcode
     * @param string $stock The stock of the product (To either force a sync back to the account or to first create
     * @param array $prices
     * @param ProductStatus $status
     * @param ShippingType $shippingType
     * @param float|null $weight
     * @param Weight $weightUnit
     * @param float|null $length
     * @param float|null $width
     * @param float|null $height
     * @param Dimension $dimensionUnit
     *
     * @param TransformedProductListing $listing
     *
     * @param array|null $images IMPORTANT: If importing from integrations, set this to null to avoid duplicate images
     *
     * @throws \Exception
     */
    public function __construct($name, $option1, $option2, $option3, $sku, $barcode, $stock, $prices,
        ProductStatus $status, ShippingType $shippingType, $weight, Weight $weightUnit, $length, $width, $height,
        Dimension $dimensionUnit, TransformedProductListing $listing, $images)
    {
        $this->name = $name;
        $this->option1 = $option1;
        $this->option2 = $option2;
        $this->option3 = $option3;
        $this->sku = $sku;
        $this->barcode = $barcode;
        $this->stock = $stock;
        $this->prices = $prices;
        $this->status = $status;
        $this->shippingType = $shippingType;
        $this->weight = $weight;
        $this->weightUnit = $weightUnit;
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
        $this->dimensionUnit = $dimensionUnit;
        $this->listing = $listing;
        $this->images = $images;
        $this->customizable = ['weight', 'width', 'length', 'height', 'barcode'];


        // Validate all the data here to make sure it's correct / valid

        if (!empty($prices)) {
            foreach ($prices as $price) {
                if (!($price instanceof TransformedProductPrice)) {
                    set_log_extra('data', get_object_vars($this));
                    throw new \Exception('Price isn\'t of object TransformedProductPrice.');
                }
            }
        }

        if (empty($listing) || !($listing instanceof TransformedProductListing)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Product listing isn\'t properly set or transformed');
        }

        /*
         * NOTE: We should create the images at the listing level instead of here
         */
        if (!empty($images)) {
            foreach ($images as $image) {
                if (!($image instanceof TransformedProductImage)) {
                    set_log_extra('data', get_object_vars($this));
                    throw new \Exception('Image isn\'t of object TransformedProductImage.');
                }
            }
        }
    }

    /**
     * Creates the product if it doesn't exist, or update the product if necessary
     *
     * @param Product $product
     * @param Account $account
     * @param array $config
     * @throws \Exception
     */
    public function createProduct(Product $product, Account $account, $config)
    {
        if (!empty($this->sku)) {

            /** @var ProductVariant $variant */
            $variant = $product->variants()->where('sku', $this->sku)->first();
            try {
                // Check if fields are customized, if yes create as attributes
                if ($variant) {
                    foreach ($this->customizable as $key => $value) {
                        if ($variant->{$value} != $this->{$value} && !isset($this->listing->attributes[$value])) {
                            $this->listing->attributes[$value] = $this->{$value};
                        }
                    }
                }

                // Continues to check if the listing is created,
                // but stops before updating the ProductVariant

                if (empty($config['update']) && !empty($variant)) {
                    $this->listing->createProduct($product, $variant, $account);
                    if($account->integration_id != Integration::QOO10) {
                        return;
                    }
                }
            } catch (\Exception $e) {
                Log::error('listing_product TransformVariant line 161 ' . $e->getMessage() . '. Shop_id ' . $product['shop_id'] . '. associated_sku ' . $product['associated_sku'] . '. integration_id ' . $account['integration_id'] . '. name ' . $account['name']);
            }
        }

        // This is to notify only once for a variant if the SKU isn't set (Only notify if it's a new variant)
        $createAlert = false;

        // We do not stop if new = false here because the product already exists but it's missing a variant
        // Hence we still import the variant in here so we can link the listing
        if (empty($variant)) {
            $variant = new ProductVariant();
            $variant->product_id = $product->id;
            $variant->shop_id = $account->shop_id;
            $variant->barcode = trim($this->barcode);
            $createAlert = true;
        }
        $variant->name = trim($this->name);
        $variant->option_1 = trim($this->option1);
        $variant->option_2 = trim($this->option2);
        $variant->option_3 = trim($this->option3);
        $variant->sku = trim($this->sku);
        $variant->stock = $this->stock;
        $variant->status = $this->status;
        $variant->shipping_type = $this->shippingType;
        $variant->weight_unit = $this->weightUnit;
        $variant->dimension_unit = $this->dimensionUnit;

        //We have to keep the below 4 lines outside the above if condition. Otherwise when we will update the variants in WC
        //the dimensions and the weight will not get updated in CS.
        //And for other integrations we are passing 0,0,0 for length, width and height if there is nothing or they do not support these attributes
        $variant->weight = $this->weight;
        $variant->length = $this->length;
        $variant->width = $this->width;
        $variant->height = $this->height;

        $variant->save();
        //update old listing
        if (isset($this->listing->identifiers['external_id']) && !empty($this->listing->identifiers['external_id'])) {
            $oldListing = $account->listings()
                ->where('product_id', $product->id)
                ->whereNotNull('product_variant_id')
                ->where('identifiers->' . ProductIdentifier::EXTERNAL_ID()->getValue(), $this->listing->identifiers['external_id'])
                ->first();
            if (!empty($oldListing)) {
                $oldListing->product_variant_id = $variant->id;
                $oldListing->save();
            }
        }
        /** @var TransformedProductPrice $price */
        if (!empty($this->prices)) {
            foreach ($this->prices as $price) {
                $price->createPrice($product, $variant, null);
            }
        }

        if (!empty($this->images)) {
            /** @var TransformedProductImage $image */
            foreach ($this->images as $image) {
                $image->createImage($product, $account, $variant, NULL);
            }
        }

        /*
         *
         * Below will be the product inventory creation / importing
         *
         */
        if (!empty($this->sku)) {

            $shop = $account->shop;

            // First check if we have an existing inventory for that SKU

            /** @var ProductInventory $inventory */
            $inventory = $shop->inventories()->where('sku', $variant->sku)->first();

            if (!empty($inventory)) {

                if (empty($variant->inventory_id)) {
                    // This is because it created this from the bundle initially and as we're currently importing new products, we should
                    // replace the stock with the stock from this
                    /** @var ProductInventoryTrail $log */
                    if ($log = $inventory->logs()->latest()->first()) {
                        if (strpos($log->message, 'Inventory created from bundled variant') !== false && !empty($config['new'])) {
                            ProductInventoryTrail::create([
                                'shop_id' => $shop->id,
                                'product_inventory_id' => $inventory->id,
                                'message' => 'Inventory updated from actual variant SKU: ' . $variant->sku,
                                'related_id' => $variant->id,
                                'related_type' => get_class($variant),
                                'old' => $inventory->stock,
                                'new' => $variant->stock,
                            ]);
                            $inventory->stock = $variant->stock;
                            $inventory->save();

                            $variant->inventory_id = $inventory->id;
                            $variant->save();
                        }
                    }
                }
                if ($variant->inventory_id != $inventory->id) {

                    // This means the SKU was changed and we should update the inventory_id thus we should notify the customer in case
                    // There's a chance the user used a different inventory as well - hence we're not changing the inventory id if its previously set
                    if (!empty($variant->inventory_id)) {
                        event(new NewProductAlert($product,
                            'Inventory for "' . (!empty($variant->name) ? $variant->name : $variant->sku) . '" is using a different inventory instead of  \'' . $inventory->sku . '\'.',
                            ProductAlertType::INFO()));
                    } else {
                        $variant->inventory_id = $inventory->id;
                        $variant->save();
                    }

                }
            } elseif (!empty($variant->inventory_id) && !empty($variant->inventory)) {
                $inventory = $variant->inventory;
                // This is mainly for new products, and they've selected a different inventory than the actual SKU of the product.
                event(new NewProductAlert($product,
                    'Inventory for "' . (!empty($variant->name) ? $variant->name : $variant->sku) . '" is using a different inventory instead of  \'' . $inventory->sku . '\'.',
                    ProductAlertType::INFO()));
            } else {

                // Inventory name used only if we create the inventory
                $inventoryName = $product->name;

                // This is because certain products and variant have similar names and in certain cases it exceeds
                // our max string length (255).

                // Hence we check it here to make sure it's not too long
                if (strlen($inventoryName . $variant->name) < 120) {
                    $inventoryName = $product->name . (!empty($variant->name) ? ' - ' . $variant->name : '');
                }
                /** @var ProductInventory $inventory */
                $inventory = ProductInventory::create([
                    'shop_id' => $shop->id,
                    'sku' => $variant->sku,
                    'name' => $inventoryName,
                    'stock' => $variant->stock,
                    'enabled' => true
                ]);

                ProductInventoryTrail::create([
                    'shop_id' => $shop->id,
                    'product_inventory_id' => $inventory->id,
                    'message' => 'Inventory created from variant SKU: ' . $variant->sku,
                    'related_id' => $variant->id,
                    'related_type' => get_class($variant),
                    'old' => $variant->stock,
                    'new' => $variant->stock,
                ]);
                $variant->inventory_id = $inventory->id;
                $variant->save();
            }

            if (!empty($config['bundle'])) {
                $this->updateBundleInventory($shop, $variant, $inventory);
            }
        } elseif ($createAlert) {
            /**
             * Increment the ProductAlertCounter
            */
            $productAlertCacheKey = 'product-alert-'.$product->shop_id.'-'.$product->id;
            if (Cache::has($productAlertCacheKey)) {
                Cache::increment($productAlertCacheKey);
            } else {
                Cache::put($productAlertCacheKey, 1);
            }
            event(new NewProductAlert($product, 'SKU for product\'s variant is not set. This means the product inventory will not be synced!', ProductAlertType::ERROR()));
        }

        $variant->updateTempFields();

        try {
            $this->listing->createProduct($product, $variant, $account);

            // The necessary checks and etc will be checked in the job itself and not here. This is to ensure
            // all update inventory calls the same function / has the same check
            if (!empty($inventory)) {

                SyncInventory::dispatch($inventory)->onQueue('sync_inventories');
            }
        } catch (\Exception $e) {
            Log::error('listing_product TransformVariant line 340 ' . $e->getMessage() . '. Shop_id ' . $product['shop_id'] . '. associated_sku ' . $product['associated_sku'] . '. integration_id ' . $account['integration_id'] . '. name ' . $account['name']);
        }
    }

    /**
     * This is to check and create the bundle inventories it's linked to
     *
     * TODO: Ensure there's no cross dependency for SKU to prevent a parent / child loop
     *
     * @param Shop $shop
     * @param ProductVariant $variant
     * @param ProductInventory $inventory
     * @throws \Exception
     */
    public function updateBundleInventory($shop, $variant, $inventory)
    {

        // First process by splitting skus by ",", "/" or "+"
        $skus = preg_split("/[,\/+]+/", $variant->sku);

        // We can just loop it even if it's 1, as we'll still need to check for ## or **
        foreach ($skus as $sku) {

            // We first remove ## from the SKUs as anything behind them aren't used
            if (str_contains($sku, '##')) {
                $sku = substr($sku, 0, strpos($sku, "##"));
            }

            // Example: 3**SKUA - This is to deduct 3 of SKUA
            if (str_contains($sku, '**')) {
                $explodedSku = explode('**', $sku);
                $deductAmount = max(1, $explodedSku[0]); // To prevent negative values somehow
                $filteredSku = $explodedSku[1];
            } else {

                // These are the normal skus, which means only 1 will be deducted
                $deductAmount = 1;
                $filteredSku = $sku;
            }

            // Make sure this is not the variant SKU, otherwise there'll be an infinite loop
            if ($filteredSku == $variant->sku) {
                continue;
            }

            $linkedInventory = $shop->inventories()->where('sku', $filteredSku)->first();
            if (empty($linkedInventory)) {
                // This means the SKU it's linked to is not valid and we need to create it
                $linkedInventory = ProductInventory::firstOrCreate([
                    'shop_id' => $shop->id,
                    'sku' => $filteredSku,
                ], [
                    'name' => $filteredSku,
                    'stock' => $variant->stock * $deductAmount,
                    'enabled' => true
                ]);
                ProductInventoryTrail::create([
                    'shop_id' => $shop->id,
                    'product_inventory_id' => $linkedInventory->id,
                    'message' => 'Inventory created from bundled variant SKU: ' . $variant->sku . '. SKU: ' . $filteredSku,
                    'related_id' => $variant->id,
                    'related_type' => get_class($variant),
                    'old' => $variant->stock * $deductAmount,
                    'new' => $variant->stock * $deductAmount,
                ]);

                // prevent two ways bundled inventory
                if (!$linkedInventory->bundledInventories()->where('product_inventories.id', $inventory->id)->exists()) {
                    $inventory->bundledInventories()->attach($linkedInventory->id, ['deduct_amount' => $deductAmount]);
                }
            } elseif (!$inventory->bundledInventories()->where('deduct_product_inventory_id', $linkedInventory->id)->count() && !$linkedInventory->bundledInventories()->where('product_inventories.id', $inventory->id)->exists()) {
                // This is if the inventory was already there, but it's not linked to this
                $inventory->bundledInventories()->attach($linkedInventory->id, ['deduct_amount' => $deductAmount]);
            }

            // To ensure we have the lowest possible stock based on childs
            $inventory->recalculateLowestStock();
            //  } else { This is not needed as this means it's already linked, but we're just putting it here
            // for readability sake
        }
    }

}
