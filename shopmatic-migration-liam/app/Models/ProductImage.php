<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\ProductImage
 *
 * @property int $id
 * @property string|null $image_url
 * @property string|null $source_url
 * @property int|null $source_account_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_listing_id
 * @property int|null $height
 * @property int|null $width
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $integration_id
 * @property-read \App\Models\Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereProductListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereSourceAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereSourceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImage whereWidth($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class ProductImage extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'external_id', 'source_account_id', 'product_id', 'product_variant_id', 'product_listing_id', 'integration_id',
        'source_url', 'image_url', 'height', 'width', 'position','region_id'
    ];

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the product the product image belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Retrieves the account the product image belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'source_account_id', 'id');
    }

    /**
     * END - Relationship Methods
     */
}
