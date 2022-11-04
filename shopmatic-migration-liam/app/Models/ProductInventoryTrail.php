<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProductInventoryTrail
 *
 * @property int $id
 * @property int|null $product_inventory_id
 * @property int $shop_id
 * @property string|null $message
 * @property int|null $related_id
 * @property string|null $related_type
 * @property int $old
 * @property int $new
 * @property string $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereNew($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereOld($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereProductInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereRelatedType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventoryTrail whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class ProductInventoryTrail extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'product_inventory_id', 'message', 'related_id', 'related_type', 'old', 'new'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'shop_id', 'product_inventory_id'
    ];

    /**
     * Changes the created_at to a human readable time
     *
     * @param $value
     *
     * @return string
     */
    public function getCreatedAtAttribute($value)
    {
        return date_time_text($value);
    }

    /**
     * Returns the related object
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function related()
    {
        if (!is_null($this->related_type)) {
            return $this->belongsTo($this->related_type, 'related_id');
        } else {
            return null;
        }
    }

}
