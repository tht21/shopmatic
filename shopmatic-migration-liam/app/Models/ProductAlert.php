<?php

namespace App\Models;

use App\Constants\ProductAlertType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProductAlert
 *
 * @property int $id
 * @property int $shop_id
 * @property int $product_id
 * @property string $message
 * @property int $type
 * @property string|null $dismissed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereDismissedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductAlert whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read bool $icon
 * @property-read \App\Models\Product $product
 * @property-write mixed $raw
 */
class ProductAlert extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'product_id', 'message', 'type', 'dismissed_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'icon'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'dismissed_at' => 'datetime',
    ];

    /**
     * START - Accessor / Mutator
     */

    /**
     * Returns the human readable created_at timestamp
     *
     * @param $value
     *
     * @return boolean
     * @throws \Exception
     */
    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('g:i a, jS M Y');
    }

    /**
     * Returns the human readable created_at timestamp
     *
     * @param $value
     *
     * @return boolean
     * @throws \Exception
     */
    public function getIconAttribute()
    {
        switch ($this->type) {
            case ProductAlertType::INFO()->getValue():
                return 'fa-info-circle text-info';
            case ProductAlertType::ERROR()->getValue():
                return 'fa-exclamation-circle text-danger';
            case ProductAlertType::WARNING()->getValue():
                return 'fa-exclamation-triangle text-warning';
        }
        throw new \Exception('Unknown alert type');
    }

    /**
     * END - Accessor / Mutator
     */

    /**
     * START - Relationship Methods
     */

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
     * END - Relationship Methods
     */

}
