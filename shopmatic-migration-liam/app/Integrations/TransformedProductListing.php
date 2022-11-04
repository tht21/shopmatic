<?php


namespace App\Integrations;


use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductAlertType;
use App\Constants\ProductIdentifier;
use App\Events\NewProductAlert;
use App\Events\OrderUpdated;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductListing;
use App\Models\ProductListingData;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;
use App\Models\Integration;
use App\Models\Region;
use App\Jobs\CleanS3ImagesJob;

class TransformedProductListing
{

    public $name;
    public $identifiers;
    public $prices;
    public $attributes;
    public $rawData;
    public $productUrl;
    public $stock;
    public $category;
    public $integrationCategory;
    public $accountCategory;
    public $images;
    public $status;

    /**
     * TransformedProduct constructor.
     *
     *
     * @param string|null $name string Name of the product
     * @param array $identifiers The identifiers of the product
     * @param IntegrationCategory|null $integrationCategory
     * @param AccountCategory|null $accountCategory
     * @param $prices array
     *
     * @param string|null $productUrl
     * @param int $stock The current stock in the listing
     * @param array $attributes
     *
     * @param array $rawData
     *
     * @param array $images
     *
     * @param $status
     * @throws \Exception
     */
    public function __construct(
        $name,
        $identifiers,
        $integrationCategory,
        $accountCategory,
        $prices,
        $productUrl,
        $stock,
        $attributes,
        $rawData,
        $images,
        MarketplaceProductStatus $status
    ) {
        $this->name = $name;
        $this->identifiers = $identifiers;
        $this->integrationCategory = $integrationCategory;
        $this->accountCategory = $accountCategory;
        $this->prices = $prices;
        $this->attributes = $attributes;
        $this->rawData = $rawData;
        $this->productUrl = $productUrl;
        $this->stock = $stock;
        $this->images = $images;
        $this->status = $status;

        // Validate all the data here to make sure it's correct / valid

        if (!is_array($identifiers) || empty($identifiers)) {
            set_log_extra('data', get_object_vars($this));
            $log_string = 'There is no identifiers for this product listing. Case of import without listing with details has marketplace '  .  Integration::INTEGRATIONS[$integrationCategory->integration_id ] . ' Product Name ' . $this->name . ' ShopSku ' . $attributes['ShopSku'] . ' associated_sku ' . $rawData['SkuId'] .' status value '.json_encode($status);
            Log::error($log_string);
            throw new \Exception('There is no identifiers for this product listing.');
        }

        foreach ($identifiers as $identifier => $value) {
            if (!ProductIdentifier::isValid($identifier)) {
                set_log_extra('data', get_object_vars($this));
                $log_string = 'Identifier not recognized. Case of import without listing with details has marketplace ' .  Integration::INTEGRATIONS[$integrationCategory->integration_id ] . ' Product Name ' . $this->name . ' ShopSku ' . $attributes['ShopSku'] . ' associated_sku ' . $rawData['SkuId'] .' status value '.json_encode($status);
                Log::error($log_string);
                throw new \Exception('Identifier not recognized.');
            }
        }

        if (!empty($images)) {
            foreach ($images as $image) {
                if (!($image instanceof TransformedProductImage)) {
                    set_log_extra('data', get_object_vars($this));
                    $log_string = 'Image isn\'t of object TransformedProductImage. Case of import without listing with details has marketplace ' .  Integration::INTEGRATIONS[$integrationCategory->integration_id ] . ' Product Name ' . $this->name . ' ShopSku ' . $attributes['ShopSku'] . ' associated_sku ' . $rawData['SkuId'] .' status value '.json_encode($status);
                    Log::error($log_string);
                    throw new \Exception('Image isn\'t of object TransformedProductImage.');
                }
            }
        }

        if (!empty($prices)) {
            foreach ($prices as $price) {
                if (!($price instanceof TransformedProductPrice)) {
                    set_log_extra('data', get_object_vars($this));
                    $log_string = 'Price isn\'t of object TransformedProductPrice. Case of import without listing with details has marketplace ' .  Integration::INTEGRATIONS[$integrationCategory->integration_id ] . ' Product Name ' . $this->name . ' ShopSku ' . $attributes['ShopSku'] . ' associated_sku ' . $rawData['SkuId'] .' status value '.json_encode($status);
                    Log::error($log_string);
                    throw new \Exception('Price isn\'t of object TransformedProductPrice.');
                }
            }
        }
    }

    /**
     * Creates and/or update the listing raw data or attributes
     *
     * @param Product $product
     * @param ProductVariant|null $variant
     * @param Account $account
     *
     * @return ProductListing
     * @throws \Exception
     */
    public function createProduct(Product $product, $variant, Account $account)
    {
        if (empty($product) || empty($product->id)) {
            $log_string = 'Product /ID is NULL Case of import without listing with details has marketplace ' . Integration::INTEGRATIONS[$this->integrationCategory->integration_id] . ' Account ID ' . $account->id . ' Shop ID ' . $account->shop_id;
            Log::error($log_string);
            throw new \Exception('Product /ID is NULL');
        }
        if (!empty($variant) && empty($variant->id)) {
            $log_string = 'Product Variant ID is NULL Case of import without listing with details has marketplace ' . Integration::INTEGRATIONS[$this->integrationCategory->integration_id] . ' Account ID ' . $account->id . ' Shop ID ' . $account->shop_id;
            Log::error($log_string);
            throw new \Exception('Product Variant ID is NULL');
        }

        // This is for most integrations, however, Amazon DOES NOT have an external ID
        // Also, we're checking ACCOUNT wide listings to make sure it doesn't already exist
        $externalId = $this->identifiers[ProductIdentifier::EXTERNAL_ID()->getValue()];
        if (!empty($externalId)) {
            /** @var ProductListing $listing */
            $listing = $account->listings()->where('identifiers->' . ProductIdentifier::EXTERNAL_ID()->getValue(), $externalId);
            if (!empty($variant)) {
                $listing = $listing->whereNotNull('product_variant_id');
            } else {
                $listing = $listing->whereNull('product_variant_id');
            }
            $listing = $listing->first();
        } else {
            // This is if we don't have the external ID, we search based on all identifiers available
            $query = $account->listings();
            if (!empty($variant)) {
                $query = $query->whereNotNull('product_variant_id');
            } else {
                $query = $query->whereNull('product_variant_id');
            }
            foreach ($this->identifiers as $identifier => $value) {
                $query = $query->where('identifiers->' . $identifier, $value);
            }
            $listing = $query->first();

            set_log_extra('listing matched', $listing->toArray());
            set_log_extra('data', get_object_vars($this));
            set_log_extra('account', $account->toArray());
            set_log_extra('product', $product->toArray());
            set_log_extra('variant', $variant ? $variant->toArray() : 'null');
            Log::error('Missing external id');
        }

        // This is where we check to make sure it's the same variant
        if (!empty($listing)) {
            if ($listing->product_id != $product->id) {
                $oldProduct = $listing->product;
                $listing->product_id = $product->id;
                if (!empty($variant)) {
                    $listing->product_variant_id = $variant->id;
                }
                $listing->save();
                $listing->updateParentIds();

                // Possibility of oldProduct being deleted
                if ($oldProduct) {
                    $alertMessageForOldProduct = 'Product with name "'.$oldProduct->name.'" and associated SKU "'.$oldProduct->associated_sku.'" has been unlinked from '.Integration::INTEGRATIONS[$listing->integration_id].' '.Region::REGIONS[$account->region_id].' '.$account->name.' as it no longer found in the marketplace.';
                    $alertMessage = 'One of the listings (ID listing: ' . $listing->id . ' ) in marketplace: ' .Integration::INTEGRATIONS[$listing->integration_id].' for this product has been unlinked from ' . $oldProduct->name . ' (ID: ' . $oldProduct->id . ') and has been linked to ' . $product->name . '(ID: ' . $product->id . ')';
                    Log::error($alertMessage);
                    event(new NewProductAlert($oldProduct, $alertMessageForOldProduct, ProductAlertType::WARNING()));

                    // force delete old product if it become orphan after listings move to other product
                    if ($oldProduct->listings()->count() === 0) {
                        $oldProduct->forceDelete();
                    }
                }
            }
        }

        if (empty($listing)) {

            // delete other listings of product within the same account, one product should contain only one main listing from one account
            if (is_null($variant)) {
                $product->listings()->where('account_id', $account->id)->whereNull('product_variant_id')->get()->each(function ($listing) {
                    // https://github.com/laravel/framework/issues/2536
                    $listing->delete();
                });
            }

            $listing = new ProductListing();
            $listing->shop_id = $account->shop_id;
            $listing->account_id = $account->id;
            $listing->integration_id = $account->integration_id;
            $listing->product_id = $product->id;
            $listing->product_variant_id = $variant ? $variant->id : null;
        }

        // This is where we check to see if it's different for this account
        // If it's the same, leave the values as null
        if ($this->name != (!empty($variant) ? $variant->name : $product->name)) {
            $listing->name = $this->name;
        } else {
            $listing->name = null;
        }
        $listing->identifiers = $this->identifiers;
        $listing->product_url = $this->productUrl;
        $listing->stock = $this->stock;
        $listing->status = $this->status->getValue();

        if (!empty($this->integrationCategory)) {
            $listing->integration_category_id = $this->integrationCategory->id;
        } else {
            $listing->integration_category_id = null;
        }

        if (!empty($this->accountCategory)) {
            $listing->account_category_id = $this->accountCategory->id;
        } else {
            $listing->account_category_id = null;
        }

        $listing->save();

        // This is if the product was just imported
        if ($listing->wasRecentlyCreated) {

            // Let's first check if there's any orders tied to this product if there's an external ID
            if (!empty($externalId)) {
                $items = $account->orderItems()->where('external_product_id', $externalId)->whereNull('product_id')->get();

                foreach ($items as $item) {
                    $item->product_id = $listing->product_id;
                    if (!empty($listing->product_variant_id)) {
                        $item->product_variant_id = $listing->product_variant_id;
                    }
                    $item->save();
                    event(new OrderUpdated($item->order));
                }
            }
        }

        /** @var ProductListing $listing */
        $listing = $listing->fresh();

        if (!empty($listing->data)) {
            $listing->data->raw_data = $this->rawData;
            $listing->data->save();
        } else {
            ProductListingData::create([
                'product_listing_id'    => $listing->id,
                'raw_data'              => $this->rawData
            ]);
        }

        // We only need to delete the images at this level because it MUST match the marketplace
        if (!empty($this->images)) {

            $currentImageIds = [];

            /** @var TransformedProductImage $image */
            foreach ($this->images as $image) {
                $image = $image->createImage($product, $account, $variant, $listing);
                if ($image) {
                    $currentImageIds[] = $image->id;
                }
            }
            $listing->images()->whereNotIn('product_images.id', $currentImageIds)->get()->each(function ($image) {
                //Push to Queue for cleanup  from S3 bucket
                if ($image && isset($image->image_url)) {
                    CleanS3ImagesJob::dispatch($image->image_url)->onQueue('s3_cleanup_queue');
                }
                $image->delete();
            });
        } else {

            // If the listing has no image, all should be deleted, no point checking if there's any as that'll be 2 calls
            $listing->images()->get()->each(function ($image) {
                //Push to Queue for cleanup  from S3 bucket
                if ($image && isset($image->image_url)) {
                    CleanS3ImagesJob::dispatch($image->image_url)->onQueue('s3_cleanup_queue');
                }
                $image->delete();
            });
        }

        /** @var TransformedProductPrice $price */
        if (!empty($this->prices)) {
            foreach ($this->prices as $price) {
                $price->createPrice($product, $variant, $listing);
            }
        }

        // Creates or update the attributes for the listing
        // However, does not create if there's the same attribute for the upper level and if it's the same value
        // This is to prevent unwanted customization

        $existingAttributes = $listing->attributes;

        foreach ($this->attributes as $attrName => $attrVal) {

            /*
             * This is so it'll store it as a string.
             * We need to check when retrieving to decode it back into an array
             */
            if (is_array($attrVal)) {
                $attrVal = json_encode($attrVal);
            }

            /*
             * Value cannot be null.
             */
            if (is_null($attrVal)) {
                $attrVal = "";
            }

            // If the value is the same or if the value exist in the variant, we can skip creating it
            if ($existingAttribute = $existingAttributes->where('name', $attrName)->first()) {
                if ($existingAttribute->value != $attrVal) {
                    $existingAttribute->value = htmlentities($attrVal, ENT_IGNORE, "UTF-8");
                    $existingAttribute->save();
                }
            } elseif (!empty($variant)) {

                $variantAttributes = $variant->attributes;
                $existingAttribute = $variantAttributes->where('name', $attrName)->first();
                // If this is not the same as the variant's attribute, create a new one
                if ($existingAttribute) {
                    // update listing id, as product created through our system doesn't has the listing id yet
                    $existingAttribute->product_listing_id = $listing->id;
                    $existingAttribute->save();

                    if ($existingAttribute->value != $attrVal) {
                        ProductAttribute::create([
                            'product_id'         => $product->id,
                            'product_variant_id' => $variant->id,
                            'product_listing_id' => $listing->id,
                            'integration_id'     => $account->integration_id,
                            'region_id'          => $account->region_id,
                            'name'               => $attrName,
                            'value'              => htmlentities($attrVal, ENT_IGNORE, "UTF-8"),
                        ]);
                    }  // Else ignore as it's the same value
                } else {
                    ProductAttribute::create([
                        'product_id'         => $product->id,
                        'product_variant_id' => $variant->id,
                        'product_listing_id' => $listing->id,
                        'integration_id'     => $account->integration_id,
                        'region_id'          => $account->region_id,
                        'name'               => $attrName,
                        'value'              => htmlentities($attrVal, ENT_IGNORE, "UTF-8"),
                    ]);
                }
            } else {
                $existingAttribute = $existingAttributes->where('name', $attrName)->first();

                // If this is not the same as the variant's attribute, create a new one
                if (empty($existingAttribute) || $existingAttribute->value != $attrVal) {
                    // Take note, some attribute return might be array (Eg: Shopee logistics)
                    if (is_array($attrVal)) {
                        $attrVal = json_encode($attrVal);
                    }
                    if ($attrName == 'delivery_option_store_pick_up') {
                        if ($attrVal == 1) {
                            $attrVal = 'Yes';
                        } else {
                            $attrVal = 'No';
                        }
                    }
                    ProductAttribute::updateOrCreate([
                        'product_id'         => $product->id,
                        'product_listing_id' => $listing->id,
                        'integration_id'     => $account->integration_id,
                        'region_id'          => $account->region_id,
                        'name'               => $attrName,
                    ], [
                        'value' => htmlentities($attrVal, ENT_IGNORE, "UTF-8")
                    ]);
                }
            }
        }

        return $listing->fresh();
    }
}
