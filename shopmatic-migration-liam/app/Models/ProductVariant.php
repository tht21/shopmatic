<?php

namespace App\Models;

use App\Constants\ProductPriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\ProductVariant
 *
 * @property int $id
 * @property int $product_id
 * @property int $shop_id
 * @property mixed|string $name
 * @property string|null $option_1
 * @property string|null $option_2
 * @property string|null $option_3
 * @property int|null $inventory_id
 * @property string|null $sku
 * @property string|null $barcode
 * @property string|null $main_image
 * @property int $stock
 * @property string|null $currency
 * @property float|null $price
 * @property int $position
 * @property int $status
 * @property int $shipping_type
 * @property float $weight
 * @property int $weight_unit
 * @property float $length
 * @property float $width
 * @property float $height
 * @property int $dimension_unit
 * @property int $total_quantity_sold
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property float|null $total_revenue
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAttribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductImage[] $images
 * @property-read int|null $images_count
 * @property-read \App\Models\ProductInventory|null $inventory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductListing[] $listings
 * @property-read int|null $listings_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductPrice[] $prices
 * @property-read int|null $prices_count
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductImage[] $allImages
 * @property-read int|null $all_images_count
 * @property-write mixed $raw
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductVariant onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereDimensionUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereMainImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereOption1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereOption2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereOption3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereShippingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereTotalQuantitySold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereWeightUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereWidth($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductVariant withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductVariant withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductVariant whereTotalRevenue($value)
 * @mixin \Eloquent
 */
class ProductVariant extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     *      Please read and add all notes to the Product model instead of here unless absolutely necessary.
     *
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'shop_id', 'name', 'option_1', 'option_2', 'option_3', 'inventory_id', 'sku', 'barcode',
        'main_image', 'stock', 'currency', 'price', 'position', 'status', 'shipping_type', 'weight', 'weight_unit',
        'length', 'width', 'height', 'dimension_unit'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'name'
    ];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function($variant) {
            if (!empty($variant->inventory_id)) {
                $productVariant = ProductVariant::whereInventoryId($variant->inventory_id)
                ->where('id', '!=', $variant->id)
                ->whereNull('deleted_at')
                ->count();

                if(empty($productVariant)) {
                    $variant->inventory()->delete();
                }
            }
        });
    }

    /**
     * This is called to update the currency, price, and stock
     */
    public function updateTempFields($save = true)
    {
        $price = $this->prices()->where('type', ProductPriceType::SELLING()->getValue())->first();
        if ($price) {
            $this->price = $price->price;
            $this->currency = $price->currency;
        }
        if ($inventory = $this->inventory) {
            $this->stock = $inventory->stock;
        }
        if ($save) {
            $this->save();
        }
    }

    /**
     * Mutator to
     *
     * @param $value
     *
     * @return mixed|string
     */
    public function getNameAttribute($value)
    {
        $name = $this->attributes['name'];
        if (!empty($name)) {
            return $name;
        }
        $name = $this->option_1;
        if (empty($name)) {
            $name = $this->option_2;
        } else {
            $name .= ' ' . $this->option_2;
        }
        if (empty($name)) {
            $name = $this->option_3;
        } else {
            $name .= ' ' . $this->option_3;
        }
        return $name;
    }

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the product the variant belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Retrieves the listings for the variants
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listings()
    {
        return $this->hasMany(ProductListing::class, 'product_variant_id', 'id');
    }

    /**
     * Retrieves the attributes that's not listing specific
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_variant_id', 'id');
    }

    /**
     * Retrieves the variant prices
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id', 'id')
                    ->whereNull('product_listing_id');
    }

    /**
     * Retrieves the main variant prices
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function main_prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id', 'id')
                    ->whereNull('product_listing_id')->whereNotNull('integration_id');
    }

    /**
     * Retrieves the main variant prices
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listing_prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id', 'id')->whereNotNull('integration_id');
    }

    /**
     * Retrieves the variant images
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id', 'id')
                    ->whereNull('product_listing_id')
                    ->whereNull('integration_id');
    }

    /**
     * Retrieves the variant images (all)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allImages()
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id', 'id');
    }

    public function listingImages()
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id', 'id')
                    ->whereNotNull('product_listing_id');
    }

    /**
     * Retrieves the shop the product variant belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the inventory the product variant belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inventory()
    {
        return $this->belongsTo(ProductInventory::class, 'inventory_id', 'id');
    }

    /**
     * END - Relationship Methods
     */


}
