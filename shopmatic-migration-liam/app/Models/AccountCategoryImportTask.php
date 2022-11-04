<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AccountCategoryImportTask
 *
 * @property int $id
 * @property int $source
 * @property string $source_type
 * @property array $messages
 * @property array $settings
 * @property int $status
 * @property int $shop_id
 * @property int|null $user_id
 * @property int $total_categories
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereTotalCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategoryImportTask whereUserId($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class AccountCategoryImportTask extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'user_id', 'source', 'source_type', 'total_categories', 'messages', 'settings', 'status'
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
     * END - Accessor / Mutator
     */
}
