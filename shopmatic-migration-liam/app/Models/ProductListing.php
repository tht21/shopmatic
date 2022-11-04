<?php

namespace App\Models;

use App\Constants\MarketplaceProductStatus;
use App\Constants\ProductIdentifier;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\ProductListing
 *
 * @property int $id
 * @property int $shop_id
 * @property int $account_id
 * @property int $integration_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property int|null $integration_category_id
 * @property int|null $account_category_id
 * @property array|null $identifiers
 * @property string|null $name
 * @property int|null $stock
 * @property int $sync_stock
 * @property int $total_sold
 * @property string|null $product_url
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Account $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAttribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\ProductListingData $data
 * @property-read int $identifier_text
 * @property-read int $status_text
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductImage[] $images
 * @property-read int|null $images_count
 * @property-read \App\Models\Integration $integration
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductPrice[] $prices
 * @property-read int|null $prices_count
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Shop $shop
 * @property-read \App\Models\ProductVariant|null $variant
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductListing[] $listing_variants
 * @property-read int|null $listing_variants_count
 * @property-read \App\Models\IntegrationCategory $integration_category
 * @property-read \App\Models\ProductListing|null $listing
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductListing onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereAccountCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereIdentifiers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereIntegrationCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereProductUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereSyncStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereTotalSold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductListing whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductListing withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductListing withoutTrashed()
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class ProductListing extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes, Compoships;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   The parent ids (product_id, product_variant_id) might be changed in the event it's linked to
     *      a different product, so any models relating to this needs to be updated as well.
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'account_id', 'product_id', 'product_variant_id', 'integration_category_id', 'account_category_id',
        'stock', 'sync_stock', 'integration_id', 'identifiers', 'name', 'total_sold', 'product_url', 'status'
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'identifiers' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_text', 'identifier_text'
    ];

    /**
     * START - Helper Methods
     */

    /**
     * This updates all the ids for attributes, prices and anything that is linked directly to this listing
     * This happens in the event when a
     *
     * @return void
     */
    public function updateParentIds()
    {
        // Using query instead of foreach loop to reduce amount of database calls

        $this->prices()->update([
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id
        ]);

        $this->attributes()->update([
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id
        ]);

        $this->images()->update([
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id
        ]);
    }

    /**
     * Accessor to get the text representation of the product status
     *
     * @return int
     */
    public function getStatusTextAttribute()
    {
        return ucwords(str_replace('_', ' ', MarketplaceProductStatus::search($this->status)));
    }

    /**
     * Get identifier by type
     *
     * @param ProductIdentifier $type
     * @return mixed|null
     */
    public function getIdentifier(ProductIdentifier $type) {
        return $this->identifiers[$type->getValue()] ?? null;
    }

    /**
     * Accessor to get the text representation of the product status
     *
     * @return int
     */
    public function getIdentifierTextAttribute()
    {
        if (!empty($this->identifiers[ProductIdentifier::EXTERNAL_ID()->getValue()])) {
            return $this->identifiers[ProductIdentifier::EXTERNAL_ID()->getValue()];
        }
        if (!empty($this->identifiers)) {
            return reset($this->identifiers);
        }
        return 'N/A';
    }

    /**
     * END - Helper Methods
     */

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the integration the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'id');
    }

    /**
     * Retrieves the account the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Retrieves the product the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Retrieves the variant the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    /**
     * Retrieves the main product's listing which is under the same account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function listing()
    {
        return $this->hasOne(ProductListing::class, ['product_id', 'account_id'], ['product_id', 'account_id'])->whereNull('product_variant_id');
    }

    /**
     * Retrieves the variant the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listing_variants()
    {
        return $this->hasMany(ProductListing::class, ['product_id', 'account_id'], ['product_id', 'account_id'])->whereNotNull('product_variant_id');
    }

    /**
     * Retrieves the integration category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function integration_category()
    {
        return $this->hasOne(IntegrationCategory::class, 'id', 'integration_category_id');
    }

    /**
     * Retrieves the listing's attributes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_listing_id', 'id');
    }

    /**
     * Retrieves the listing's customized prices
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_listing_id', 'id');
    }

    /**
     * Retrieves the listing's images
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_listing_id', 'id');
    }

    /**
     * Retrieves the product the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function data()
    {
        return $this->hasOne(ProductListingData::class, 'product_listing_id', 'id');
    }

    /**
     * Retrieves the shop the product listing belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * END - Relationship Methods
     */
}
