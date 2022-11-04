<?php

namespace App\Models;

use App\Constants\JobStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ExportExcelTask
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $user_id
 * @property string $source_type
 * @property string $source
 * @property string|null $download
 * @property string|null $messages
 * @property array|null $settings
 * @property string $status
 * @property bool $created_at
 * @property bool $updated_at
 * @property-read \App\Models\Shop $shop
 * @property-read \App\Models\User|null $user
 * @property-read $sourceModel // dynamic class
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ExportExcelTask whereUserId($value)
 * @mixin \Eloquent
 */
class ExportExcelTask extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'user_id', 'source_type', 'source', 'download', 'messages', 'settings', 'status', 'downloaded_status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'download' => 'array',
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
     * Returns the human readable created_at timestamp
     *
     * @param $value
     *
     * @return boolean
     * @throws \Exception
     */
    public function getUpdatedAtAttribute($value)
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

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves user of the task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Retrieves shop of the task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves source of the task if source type is valid
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourceModel()
    {
        if (is_numeric($this->source) && class_exists($this->source_type)) {
            return $this->belongsTo($this->source_type, 'source', 'id');
        } else {
            // return empty relation if not valid
            return $this->belongsTo(Shop::class, 'shop_id', 'id')->whereNull('id');
        }
    }

    /**
     * END - Relationship Methods
     */
}
