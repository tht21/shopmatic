<?php


namespace App\Integrations;

use App\Constants\ProductPriceType;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductPrice;
use App\Models\ProductVariant;

class TransformedProductPrice
{

    public $currency;
    public $price;
    public $type;
    public $flag;

    /**
     * TransformedProduct constructor.
     *
     *
     * @param $currency
     * @param $price
     * @param $type
     */
    public function __construct($currency, $price, ProductPriceType $type, $flag = false)
    {
        $this->currency = $currency;
        $this->price = $price;
        $this->type = $type;
        $this->flag = $flag;

    }

    /**
     * Creates the product if it doesn't exist, or update the product if necessary
     *
     * @param Product $product
     * @param ProductVariant $variant
     * @param ProductListing|null $listing
     * @throws \Exception
     */
    public function createPrice(Product $product, $variant, $listing) {

        try {
            // Create only if the price is different from the variant's prices
            // This is to prevent unwanted customizations
            if (!empty($listing)) {
                if (!empty($variant)) {
                    $variantPrices = $variant->prices;
                    $existingPrice = $variantPrices->where('currency', $this->currency)
                        ->where('type', $this->type)
                        ->first();
                        
                } else {
                    $productPrices = $product->prices;
                    $existingPrice = $productPrices->where('currency', $this->currency)
                        ->where('type', $this->type)
                        ->first();
                    if ($this->flag == true) {
                        ProductPrice::updateOrCreate([
                            'product_id' => $product->id,
                            'shop_id' => $product->shop_id,
                            'product_variant_id' => null,
                            'product_listing_id' => null,
                            'currency' => $this->currency,
                            'type' => $this->type,
                        ], [
                            'price' => $this->price
                        ]);
                    }
                }
                /*if (!empty($variant)) {
                    $variantPrices = $variant->prices;
                    $existingPrice = $variantPrices->where('currency', $this->currency)
                        ->where('type', $this->type)
                        ->first();
                }*/
                ProductPrice::updateOrCreate([
                    'product_id' => $product->id,
                    'shop_id' => $product->shop_id,
                    'product_variant_id' => $variant ? $variant->id : null,
                    'product_listing_id' => $listing ? $listing->id : null,
                    'currency' => $this->currency,
                    'type' => $this->type,
                ], [
                    'price' => $this->price
                ]);
            } else if($this->flag == true) {
                ProductPrice::updateOrCreate([
                    'product_id' => $product->id,
                    'shop_id' => $product->shop_id,
                    'product_variant_id' => $variant ? $variant->id : null,
                    'product_listing_id' => null,
                    'currency' => 'SGD',
                    'type' => $this->type,
                ], [
                    'price' => $this->price
                ]);
            }
        } catch (\Exception $exception) {
            set_log_extra('product', $product);
            if (isset($variant)) {
                set_log_extra('$variant', $variant);
            }
            throw $exception;
        }
    }
}