<?php


namespace App\Integrations;


use App\Constants\ProductAlertType;
use App\Constants\ProductIdentifier;
use App\Constants\ProductStatus;
use App\Events\NewProductAlert;
use App\Events\ProductUpdated;
use App\Models\Account;
use App\Models\Category;
use App\Models\Integration;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Utilities\SubscriptionHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class TransformedProduct
{

    public $name;
    public $associatedSku;
    public $shortDescription;
    public $htmlDescription;
    public $brand;
    public $model;
    public $category;
    public $status;

    public $images;
    public $variants;
    private $options;
    public $listing;
    public $customizable;

    /**
     * TransformedProduct constructor.
     *
     *
     * @param string $name Name of the product
     * @param string|null $associatedSku string The associated SKU (The listing SKU)
     * @param string|null $shortDescription string|null Short description of the product
     * @param string|null $htmlDescription string|null Full HTML description of the product
     * @param string|null $brand
     * @param string|null $model
     * @param Category|null $category
     * @param ProductStatus $status
     * @param array $variants
     *
     * @param array $options
     *
     * @param TransformedProductListing $listing
     *
     * @param array|null $images If we're importing from integrations, set this to null to avoid duplicate images
     *
     * @throws \Exception
     */
    public function __construct($name, $associatedSku, $shortDescription, $htmlDescription, $brand, $model, $category,
                                ProductStatus $status, $variants, $options, TransformedProductListing $listing, $images)
    {
        $this->name = $name;
        $this->associatedSku = trim($associatedSku);
        $this->shortDescription = $shortDescription;
        $this->htmlDescription = $htmlDescription;
        $this->brand = $brand;
        $this->model = $model;
        $this->category = $category;
        $this->status = $status->getValue();
        $this->variants = $variants;
        $this->options = $options;
        $this->listing = $listing;
        $this->images = $images;
        $this->customizable = ['name', 'shortDescription', 'htmlDescription'];

        // Validate all the data here to make sure it's correct / valid

        if (!is_array($variants) || empty($variants)) {
            set_log_extra('data', get_object_vars($this));
            $logMessage = 'There are no variants in this product with |name|' . $name . '|Associated SKU|'  .$associatedSku.  '|SKU|' .$associatedSku.  '|Variant|' . json_encode($variants);
            Log::error($logMessage);
            $this->variants = null;
        } 
        if (is_array($variants) && !empty($variants)) {
            foreach ($variants as $variant) {
                if (!($variant instanceof TransformedProductVariant)) {
                    set_log_extra('data', get_object_vars($this));
                    throw new \Exception('Variant isn\'t of object TransformedProductVariant.');
                }
            }

            if (!empty($images)) {
                foreach ($images as $image) {
                    if (!($image instanceof TransformedProductImage)) {
                        set_log_extra('data', get_object_vars($this));
                        throw new \Exception('Image isn\'t of object TransformedProductImage.');
                    }
                }
            }
        }
    }

    /**
     * Creates the product if it doesn't exist, or update the product if necessary
     *
     * @param $account Account
     * @param array $config
     * @return Product|null
     * @throws \Exception
     */
    public function createProduct(Account $account, $config) {

        $shop = $account->shop;
        $variantSkus = [];
        if (!empty($this->associatedSku)) {

            /** @var Product $product */
            $product = $shop->products()->where('associated_sku', $this->associatedSku)->first();

            // match by variants
            if ($account->integration_id === Integration::LAZADA || $account->integration_id === Integration::SHOPIFY) {
                //    $products = $shop->products()->whereHas('variants', function (Builder $query) use ($sku) {
                //        $query->where('sku', $sku);
                //    })->get();
                $variants = $shop->productVariants()->with(['product'])->where('sku', $this->associatedSku)->get();

                $variantSkus = [];
                /** @var TransformedProductVariant $variant */
                foreach ($this->variants as $variant) {
                    $variantSkus[] = trim($variant->sku);
                }

                /** @var Product $prod */
                foreach ($variants as $variant) {
                    $prod = $variant->product;
                    $notMatch = false;

                    if (!$prod) {
                        continue;
                    }
                    // only match same variant count product
                    if ($prod->variants->count() !== count($variantSkus)) {
                        continue;
                    }

                    // find same variants set product
                    /** @var ProductVariant $variant */
                    foreach ($prod->variants as $variant) {
                        if ($keys = array_keys($variantSkus, trim($variant->sku))) {
                            // throw error if array is not unique
                            if (count($keys) > 1) {
                                set_log_extra('product', $prod);
                                set_log_extra('variantSkus', $variantSkus);
                                Log::error('duplicate variant sku detected');
                            }
                        } else {
                            $notMatch = true;
                            break;
                        }
                    }

                    // matched
                    if (!$notMatch) {
                        $product = $prod;
                        break;
                    }
                }
            }

            // Continues to check if the variants are created / the listing is created,
            // but stops before updating the Product

            if (empty($config['update']) && !empty($product)) {
                // Update product listing
                try {
                    // Check if fields are customized, if yes create as attributes
                    foreach ($this->customizable as $key => $value) {
                        $pascalValue = toPascalCase($value);
                        if ($product->{$pascalValue} != $this->{$value} && !isset($this->listing->attributes[$pascalValue])) {
                            $this->listing->attributes[$pascalValue] = $this->{$value};
                        }
                    }

                    $this->listing->createProduct($product, null, $account);
                } catch (\Exception $e) {
                    Log::error('listing_product TransformedProduct line 185 ' . $e->getMessage() . '. Shop_id ' . $product['shop_id'] . '. associated_sku ' . $product['associated_sku'] . '. integration_id ' . $account['integration_id'] . '. name ' . $account['name']);
                }

                // Update product status
                if ($product->status != $this->status) {
                    $product->status = $this->status;
                } elseif ($product->status == ProductStatus::DRAFT()->getValue()) {
                    $product->status = ProductStatus::LIVE();
                }
                $product->save();

                $check_variant = $product->variants;
                foreach ($check_variant as $chk_variant) {
                    $isExist = false;
                    foreach ($this->variants as $variant) {
                        if ($chk_variant->sku == $variant->sku) {
                            $isExist = true;
                        }
                    }
                    if ($isExist == false
                    ) {
                        $chk_variant->delete();
                    }
                }

                foreach ($this->variants as $variant) {
                    /** @var TransformedProductVariant $variant */
                    $variant->createProduct($product, $account, $config);
                }
                return $product;
            }
        }

        // This is to notify only once for a product if the SKU isn't set (Only notify if it's a new product)
        $createAlert = false;

        // This means that there's no existing product
        if (empty($product)) {
            // If new = false, means we do not create new product
            if (empty($config['new'])) {
                return null;
            }
            $product = new Product();
            $product->shop_id = $account->shop_id;
            $product->associated_sku = $this->associatedSku;
            $createAlert = true;
        } elseif (empty($config['update'])) {
            // We shouldn't update the product since update = false
            return null;
        }

        $product->name = $this->name;
        $product->brand = $this->brand;
        $product->model = $this->model;
        $product->short_description = htmlentities($this->shortDescription, ENT_IGNORE, "UTF-8");
        $product->html_description = htmlentities($this->htmlDescription, ENT_IGNORE, "UTF-8");

        // if main_image = null => set main Image = first images;
        if (!empty($this->images)) {
            if($imageFirst =$this->images[0]){
                $product->main_image = $imageFirst->url;
            }
        }

        // save brand if it is empty
        if (empty($product->brand) && !empty($this->brand)) {
            $product->brand = $this->brand;
        }
        if ($product->status != $this->status) {
            $product->status = $this->status;
        }

        if (is_null($product->id)) {
            $product->options = $this->options;
        } else {
            // save customize options as attribute
            if ($product->options !== $this->options) {
                $this->listing->attributes['options'] = $this->options;
            }
            // TODO: Might want to check old products for attributes that's different from current - to create the customized attributes

        }

        if ($product->status != $this->status) {
            $product->status = $this->status;
        } elseif ($product->status == ProductStatus::DRAFT()->getValue()) {
            $product->status = ProductStatus::LIVE();
        }
        
        // Check if fields are customized, if yes create as attributes
        foreach ($this->customizable as $key => $value) {
            $pascalValue = toPascalCase($value);
            if ($product->{$pascalValue} != $this->{$value}) {
                $this->listing->attributes[$pascalValue] = $this->{$value};
            }
        }
        $product->save();

        // Commenting the bottom out to reduce database load

//        // Returns a fresh copy of the product as it might not have all the fields
//        $product = $product->fresh();

        if (!empty($this->images)) {
            /** @var TransformedProductImage $image */
            // Disabled as images should be under product listing, and not needed to be under product
            // foreach ($this->images as $image) {
            //     $image->createImage($product, $account, null, null);
            // }
        }

        if ($createAlert && empty($this->associatedSku)) {
            event(new NewProductAlert($product, 'AssociatedSKU for product is not set. When importing from other integrations, this product cannot be grouped automatically!', ProductAlertType::WARNING()));
        }

        try {
            $this->listing->createProduct($product, null, $account);
        } catch (\Exception $e) {
            Log::error('listing_product TransformedProduct line 185 ' . $e->getMessage() . '. Shop_id ' . $product['shop_id'] . '. associated_sku ' . $product['associated_sku'] . '. integration_id ' . $account['integration_id'] . '. name ' . $account['name']);
        }

        $variantExternalIds = [];
        // If previous variants listing does not exists then it should be deleted.
        foreach ($this->variants as $variant) {
            if (isset($variant->listing->identifiers[ProductIdentifier::EXTERNAL_ID()->getValue()])) {
                $variantExternalIds[] = $variant->listing->identifiers[ProductIdentifier::EXTERNAL_ID()->getValue()];
            }
        }
        if (!empty($variantExternalIds)) {
            // Deleting it directly via bulk update
            $account->listings()
                ->where('product_id', $product->id)
                ->whereNotNull('product_variant_id')
                ->whereNotIn('identifiers->' . ProductIdentifier::EXTERNAL_ID()->getValue(), $variantExternalIds)
                ->update(['deleted_at' => null]);
//            foreach ($listingsToDelete as $key => $value) {
//                // https://github.com/laravel/framework/issues/2536
//                $value->delete();
//            }
        }

        foreach ($this->variants as $variant) {

            try {
                /** @var TransformedProductVariant $variant */
                $variant->createProduct($product, $account, $config);

                $variantSkus[] = $variant->sku;
            } catch (\Exception $e) {
                set_log_extra('product', $this);
                throw $e;
            }
        }

        // check for duplicated sku of variants in a product
        if (count(array_unique($variantSkus)) < count($this->variants)) {
            event(new NewProductAlert($product, 'Variants contains duplicated sku. Please edit and import the products again to prevent any issues caused by duplicated sku.', ProductAlertType::WARNING()));
        }

        // delete variants which doesn't has listings
        if (isset($config['delete_variants']) && $config['delete_variants']) {
            $product->variants()->whereDoesntHave('listings')->delete();
        }

        // Fires the event to update all the temporary fields
        // Temporarily commenting it out as we're not using it
//        $product = $product->fresh();
        event(new ProductUpdated($product));

        return $product;
    }

}
