<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\ProductPrice
 *
 * @property int $id
 * @property int $shop_id
 * @property int $product_id
 * @property int $product_variant_id
 * @property int|null $product_listing_id
 * @property string $currency
 * @property float $price
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereProductListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int|null $integration_id
 * @property-write mixed $raw
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductPrice whereIntegrationId($value)
 */
class ProductPrice extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'shop_id', 'product_variant_id', 'product_listing_id', 'currency', 'price', 'type', 'integration_id', 'region_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'product_id', 'shop_id',
    ];

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
     * END - Relationship Methods
     */
}
