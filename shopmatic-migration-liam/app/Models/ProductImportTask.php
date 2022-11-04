<?php

namespace App\Models;

use App\Constants\JobStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProductImportTask
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $user_id
 * @property string $source_type
 * @property string $source
 * @property string $messages
 * @property array|null $settings
 * @property int $total_products
 * @property string $status
 * @property bool $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Shop|null $shop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereTotalProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductImportTask whereUserId($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class ProductImportTask extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'user_id', 'source', 'source_type', 'total_products', 'messages', 'settings', 'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'messages' => 'array',
        'settings' => 'array',
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
     * Mutates the status to a human readable status
     *
     * @param $value
     *
     * @return string
     */
    public function getStatusAttribute($value)
    {
        return ucwords(strtolower(JobStatus::search($value)));
    }

    /**
     * Implodes the messages array into a string
     *
     * @param $value
     *
     * @return string
     */
    public function getMessagesAttribute($value)
    {
        if (empty($value)) {
            return '';
        }
        return implode(', ', json_decode($value, true));
    }

    /**
     * END - Accessor / Mutator
     */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    public function sourceable()
    {
        return $this->morphTo();
    }
}
