<?php

namespace App\Models;

use App\Constants\JobStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DeleteAccountTask
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $user_id
 * @property int $account_id
 * @property string $messages
 * @property array|null $settings
 * @property string $status
 * @property bool $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeleteAccountTask whereUserId($value)
 * @mixin \Eloquent
 */
class DeleteAccountTask extends Model
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'user_id', 'account_id', 'messages', 'settings', 'status'
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
}
