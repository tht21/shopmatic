<?php

namespace App\Models;

use App\Constants\ProductAlertType;
use App\Constants\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $category_id
 * @property string $slug
 * @property string|null $associated_sku
 * @property string $name
 * @property array|null $options
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $short_description
 * @property string|null $html_description
 * @property string|null $main_image
 * @property int $status
 * @property int $total_quantity_sold
 * @property int $total_orders
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property float|null $total_revenue
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAttribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read int $error_alerts
 * @property-read int $status_text
 * @property-read int $warning_alerts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductImage[] $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductListing[] $listings
 * @property-read int|null $listings_count
 * @property-read \App\Models\Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAlert[] $unreadAlerts
 * @property-read int|null $unread_alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAlert[] $unreadErrorAlerts
 * @property-read int|null $unread_error_alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAlert[] $unreadWarningAlerts
 * @property-read int|null $unread_warning_alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductVariant[] $variants
 * @property-read int|null $variants_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductPrice[] $prices
 * @property-read int|null $prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductImage[] $allImages
 * @property-read int|null $all_images_count
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductExportTask[] $productExportTasks
 * @property-read int|null $product_export_tasks_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereAssociatedSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereHtmlDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMainImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereShortDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTotalOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTotalQuantitySold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTotalRevenue($value)
 * @mixin \Eloquent
 */
class Product extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes, HasSlug;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   The stock, currency and price column in ProductVariant is used ONLY for displaying. This WILL NOT be used externally
     *
     * 2.   The selling price might be a combination of 2 prices (Shipping & Selling), if the integration doesn't
     *      support a shipping price. Refer to ProductPriceType
     *
     * 3.   The FINAL selling price (After Note 2), will be affected by any running campaigns.
     *
     * 4.   The selling price can be overridden per account based on product_prices with the ProductListing ID
     *
     * 5.   This model is the general product information, and regardless of number of variants, one product MUST have
     *      at least one variant.
     *
     * 6.   The `options` is an array of the variant choices (color_family, size and etc)
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'category_id', 'slug', 'associated_sku', 'name', 'options', 'brand', 'model', 'short_description',
        'html_description', 'main_image', 'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at', 'shop_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_text', 'error_alerts', 'warning_alerts'
    ];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($product) {

            foreach ($product->variants()->get() as $variant) {
                $variant->delete();
            }

            foreach ($product->listings()->get() as $listing) {
                $listing->delete();
            }
        });
    }

    /**
     * The function to generate the slug for the product
     *
     * @return mixed
     */
    public function getSlugOptions()
    {
        return SlugOptions::create()
                          ->generateSlugsFrom(['shop_id', 'associated_sku'])
                          ->saveSlugsTo('slug')
                          ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Accessor to get the number of warning alerts
     *
     * @return int
     */
    public function getWarningAlertsAttribute()
    {
        return $this->hasMany(ProductAlert::class, 'product_id', 'id')
        ->where('type', ProductAlertType::WARNING()->getValue())
        ->whereNull('dismissed_at')->count();
    }

    /**
     * Accessor to get the number of error alerts
     *
     * @return int
     */
    public function getErrorAlertsAttribute()
    {
        $productAlertCacheKey = 'product-alert-'.$this->shop_id.'-'.$this->id;
        if (Cache::has($productAlertCacheKey)) {
            $value = Cache::get($productAlertCacheKey);
            return (int)$value;
        } else {
            return $this->unreadErrorAlerts->count();
        }
    }

    /**
     * Accessor to get the text representation of the product status
     *
     * @return int
     */
    public function getStatusTextAttribute()
    {
        return ucwords(str_replace('_', ' ', ProductStatus::search($this->status)));
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }


    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the shop the product belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the variants for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id')
                    ->orderBy('position', 'asc');
    }

    /**
     * Retrieves the variants for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listings()
    {
        return $this->hasMany(ProductListing::class, 'product_id', 'id')->whereNull('product_variant_id');
    }

    /**
     * Retrieves the variants for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allListings()
    {
        return $this->hasMany(ProductListing::class, 'product_id', 'id');
    }

    /**
     * Retrieves the attributes for the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_id', 'id');
    }
    /**
     * Retrieves the images for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id')
                    ->whereNull('product_variant_id')
                    ->whereNull('product_listing_id')
                    ->whereNull('integration_id');
    }

    /**
     * Retrieves the images (all) for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id')
                    ->whereNull('product_variant_id');
    }

    public function listingImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id')
                    ->whereNotNull('product_listing_id');
    }

    /**
     * Retrieves the product prices
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id')
            ->whereNull('product_variant_id');
    }

    /**
     * Retrieves the category the product belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Retrieves the unread warning alerts for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unreadWarningAlerts()
    {
        return $this->hasMany(ProductAlert::class, 'product_id', 'id')
                    ->where('type', ProductAlertType::WARNING()->getValue())
                    ->whereNull('dismissed_at');
    }

    /**
     * Retrieves the unread warning alerts for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unreadErrorAlerts()
    {
        return $this->hasMany(ProductAlert::class, 'product_id', 'id')
                    ->where('type', ProductAlertType::ERROR()->getValue())
                    ->whereNull('dismissed_at');
    }

    /**
     * Retrieves the unread alerts for this product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unreadAlerts()
    {
        return $this->hasMany(ProductAlert::class, 'product_id', 'id')
                    ->whereNull('dismissed_at');
    }

    /**
     * Retrieves exported tasks
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productExportTasks()
    {
        return $this->hasMany(ProductExportTask::class, 'product_id', 'id');
    }

    /**
     * Retrieves the all order items in this account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function accounts()
    {
        return $this->hasManyThrough(Account::class, ProductListing::class, 'product_id', 'id', 'id', 'account_id')->whereNull('product_variant_id');
    }

    /**
     * Retrieves the attributes for the product by integration_category_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributesByIntergrationCategory()
    {
        return $this->hasMany(ProductAttribute::class, 'product_id', 'id')->where('name', 'integration_category_id');
    }

    /**
     * END - Relationship Methods
     */


}
