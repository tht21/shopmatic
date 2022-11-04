<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AccountWebhook
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $account_id
 * @property string|null $external_id
 * @property string|null $type
 * @property int $status
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountWebhook whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AccountWebhook extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'external_id', 'account_id', 'type', 'data', 'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

}
