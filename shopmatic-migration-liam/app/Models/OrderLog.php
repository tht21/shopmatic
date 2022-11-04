<?php

namespace App\Models;

use App\Constants\OrderLogType;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\OrderLog
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $account_id
 * @property int|null $integration_id
 * @property int $shop_id
 * @property string|null $related_type
 * @property string|null $related_id
 * @property string $message
 * @property int|null $user_id
 * @property int $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Integration|null $integration
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Shop $shop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereRelatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereRelatedType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderLog whereUserId($value)
 * @mixin \Eloquent
 */
class OrderLog extends Model
{

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   We're not currently using `type`, but this might be good to have in case we want to differentiate the logs
     *      in the future. E.g. pushing of orders, updating of orders, creating of orders. As we'll be able to monitor
     *      the logs at a global level for shop / integration and potentially identify issues when there are no logs
     *      for a certain action.
     *
     */

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = [
        'order_id', 'account_id', 'integration_id', 'shop_id', 'related_type', 'related_id', 'message', 'user_id', 'type'
    ];

    /**
     * Helper method to generate log for the order
     *
     * @param $order
     * @param $message
     * @param $type
     * @param null $userId
     * @param null $relatedType
     * @param null $relatedId
     * @return
     */
    public static function generateLog($order, $message, OrderLogType $type, $userId = null, $relatedType = null, $relatedId = null)
    {
        return OrderLog::create([
            'order_id' => $order->id,
            'shop_id' => $order->shop_id,
            'account_id' => $order->account_id,
            'integration_id' => $order->integration_id,
            'type' => $type->getValue(),
            'message' => $message,
            'user_id' => $userId,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
    }


    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the integration the order item belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'id');
    }

    /**
     * Retrieves the account the log belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Retrieves the order the log belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Retrieves the shop the log belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * END - Relationship Methods
     */

}
