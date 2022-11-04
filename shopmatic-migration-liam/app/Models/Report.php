<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Report
 *
 * @property int $id
 * @property int|null $integration_id
 * @property int|null $account_id
 * @property int $shop_id
 * @property array|null $order_item_ids
 * @property array|null $cancelled_order_item_ids
 * @property array|null $returned_order_item_ids
 * @property int|null $day
 * @property int|null $week
 * @property int|null $month
 * @property int|null $year
 * @property int|null $day_of_year
 * @property string|null $currency
 * @property float $total_revenue
 * @property int $total_customers
 * @property float $basket_value
 * @property float $basket_size
 * @property float $total_returned_value
 * @property float $total_cancelled_value
 * @property int $total_orders
 * @property int $total_cancelled_orders
 * @property int $total_returned_orders
 * @property float $total_discount
 * @property float $total_shipping_fees
 * @property float $gross_profit
 * @property float $cost_of_goods
 * @property float $tax
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Integration|null $integration
 * @property-read \App\Models\Shop $shop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereBasketSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereBasketValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereCancelledOrderItemIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereCostOfGoods($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereDayOfYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereGrossProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereOrderItemIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereReturnedOrderItemIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalCancelledOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalCancelledValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalCustomers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalReturnedOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalReturnedValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalRevenue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereTotalShippingFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Report whereYear($value)
 * @mixin \Eloquent
 */
class Report extends Model
{
    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   `total_customers` is inaccurate as we're not tracking individual customers at this point. TODO: Fix
     *
     */
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'integration_id', 'account_id', 'shop_id', 'order_item_ids', 'cancelled_order_item_ids', 'returned_order_item_ids',
        'day', 'week', 'month', 'year', 'day_of_year', 'currency', 'total_revenue', 'total_customers', 'basket_value', 'basket_size',
        'total_orders', 'total_cancelled_orders', 'total_cancelled_value', 'total_returned_orders', 'total_returned_value',
        'total_discount', 'total_shipping_fees', 'gross_profit', 'total_item_quantity',
    ];

    /**
     * The attributes that are hidden.
     *
     * @var array
     */
    protected $hidden = [
        'order_item_ids', 'cancelled_order_item_ids', 'returned_order_item_ids'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'order_item_ids' => 'array',
        'cancelled_order_item_ids' => 'array',
        'returned_order_item_ids' => 'array',
    ];

    /**
     * Retrieve the integration the report belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'id');
    }

    /**
     * Retrieve the account the report belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Retrieve the shop the report belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }
}
