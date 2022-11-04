<?php

namespace App\Models;

use App\Constants\FulfillmentStatus;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $account_id
 * @property int|null $integration_id
 * @property int|null $customer_id
 * @property string|null $external_id
 * @property string|null $external_order_number
 * @property string|null $customer_name
 * @property string|null $customer_email
 * @property mixed|null $shipping_address
 * @property mixed|null $billing_address
 * @property string|null $ship_by_date
 * @property string $currency
 * @property float $integration_discount
 * @property float $seller_discount
 * @property float $shipping_fee
 * @property float $tax
 * @property float $tax_2
 * @property float $commission_fee
 * @property float $grand_total
 * @property float $buyer_paid
 * @property float|null $settlement_amount
 * @property int $payment_status
 * @property string|null $payment_method
 * @property int $fulfillment_status
 * @property int $fulfillment_type
 * @property string|null $shipment_provider
 * @property string|null $buyer_remarks
 * @property string|null $notes
 * @property int $type
 * @property mixed|null $data
 * @property mixed|null $internal_data
 * @property string $order_placed_at
 * @property string|null $order_paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $parent_id
 * @property string|null $external_source
 * @property float $actual_shipping_fee
 * @property bool $order_updated_at
 * @property float $integration_shipping_fee
 * @property float $seller_shipping_fee
 * @property float $transaction_fee
 * @property-read \App\Models\Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read int $fulfillment_status_text
 * @property-read int $payment_status_text
 * @property-read \App\Models\Integration|null $integration
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderItem[] $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderLog[] $logs
 * @property-read int|null $logs_count
 * @property-write mixed $raw
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Order onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereActualShippingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereExternalSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereOrderUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereParentId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Order withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Order withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereBillingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereBuyerPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereBuyerRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCommissionFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereExternalOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereFulfillmentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereFulfillmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereIntegrationDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereInternalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereOrderPaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereOrderPlacedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereSellerDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereSettlementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereShipByDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereShipmentProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereShippingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereShippingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereTax2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereIntegrationShippingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereSellerShippingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Order whereTransactionFee($value)
 * @mixin \Eloquent
 */
class Order extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'account_id', 'integration_id', 'customer_id', 'external_id', 'external_order_number', 'external_source',
        'customer_name', 'customer_email', 'shipping_address', 'billing_address', 'ship_by_date', 'currency',
        'integration_discount', 'seller_discount', 'shipping_fee', 'tax', 'tax_2', 'commission_fee', 'grand_total',
        'buyer_paid', 'settlement_amount', 'payment_status', 'payment_method', 'fulfillment_status' , 'fulfillment_type',
        'buyer_remarks', 'notes', 'type', 'data', 'internal_data', 'order_placed_at', 'order_updated_at', 'order_paid_at',
        'parent_id', 'actual_shipping_fee', 'integration_shipping_fee', 'seller_shipping_fee', 'transaction_fee'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'data' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'order_placed_at', 'order_paid_at', 'ship_by_date'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'fulfillment_status_text', 'payment_status_text',
    ];

    /**
     * Accessor to get the text representation of the fulfillment status
     *
     * @return int
     */
    public function getFulfillmentStatusTextAttribute()
    {
        return ucwords(str_replace('_', ' ', FulfillmentStatus::search($this->fulfillment_status)));
    }

    public function shouldModifyStock()
    {
        $type = (!is_int($this->type) || !is_numeric($this->type)) ? $this->type->getValue() : $this->type;
        if (OrderType::POS()->getValue() == $type || OrderType::NORMAL()->getValue() == $type) {
            return true;
        }
        return false;
    }

    /**
     * Returns the color used for the status
     *
     * @return string
     */
    public function getStatusTextColor()
    {
        switch ($this->fulfillment_status) {
            // Pending
            case 0:
            // Processing
            case 1:
            // Ready to Ship
            case 10:
            // Partially Shipped
            case 12:
            // Retry Ship
            case 13:
                return 'warning';
            // Shipped
            case 11:
            // Delivered
            case 20:
            // Pending Confirmation
            case 21:
                return 'success';
            // Cancelled
            case 30:
                return 'danger';
            default:
                return 'info';
        }
    }

    /**
     * Accessor to get the text representation of the payment status
     *
     * @return int
     */
    public function getPaymentStatusTextAttribute()
    {
        return ucwords(str_replace('_', ' ', PaymentStatus::search($this->payment_status)));
    }

    /**
     * Returns the human readable order_placed_at timestamp
     *
     * @param $value
     *
     * @return boolean
     * @throws \Exception
     */
    public function getOrderPlacedAtAttribute($value)
    {
        return (new Carbon($value))->format('g:i a, jS M Y');
    }

    /**
     * Returns the human readable order_updated_at timestamp
     *
     * @param $value
     *
     * @return boolean
     * @throws \Exception
     */
    public function getOrderUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('g:i a, jS M Y');
    }

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the integration the order belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'id');
    }

    /**
     * Retrieves the account the order belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Retrieves the shop the order belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the items under this order
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Retrieves the related orders under this integration.
     * These are orders that are created using this order, but on different integrations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Order::class, 'parent_id', 'id');
    }

    /**
     * Retrieves the all the logs for this order
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id', 'id');
    }

    /**
     * END - Relationship Methods
     */
}
