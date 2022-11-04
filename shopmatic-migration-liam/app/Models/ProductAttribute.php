<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\ProductAttribute
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_listing_id
 * @property int|null $integration_id
 * @property string $name
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereProductListingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAttribute whereValue($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class ProductAttribute extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'product_variant_id', 'product_listing_id', 'integration_id', 'region_id', 'name', 'value'
    ];

    /**
     * Get the user associated with the ProductAttribute
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function intergrationCategory() {
        return $this->hasOne(IntegrationCategory::class, 'id', 'value');
    }

}
