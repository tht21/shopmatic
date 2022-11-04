<?php

namespace App\Models;

use App\Constants\FulfillmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\OrderItem
 *
 * @property int $id
 * @property int $order_id
 * @property int $shop_id
 * @property int|null $integration_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property string|null $external_id
 * @property string|null $external_product_id
 * @property string|null $name
 * @property string|null $sku
 * @property string|null $variation_name
 * @property string|null $variation_sku
 * @property int $quantity
 * @property float|null $item_price
 * @property float $integration_discount
 * @property float $seller_discount
 * @property float $shipping_fee
 * @property float|null $tax
 * @property float|null $tax_2
 * @property float $grand_total
 * @property float|null $buyer_paid
 * @property float|null $refunded_amount
 * @property int $fulfillment_status
 * @property int|null $return_status
 * @property int $inventory_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $account_id
 * @property float $actual_shipping_fee
 * @property array|null $cost_of_goods
 * @property string|null $shipment_provider
 * @property string|null $shipment_type
 * @property string|null $shipment_method
 * @property string|null $tracking_number
 * @property int|null $product_inventory_id
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read int $fulfillment_status_text
 * @property-read \App\Models\Integration|null $integration
 * @property-read \App\Models\ProductInventory|null $inventory
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Shop $shop
 * @property-read \App\Models\ProductVariant|null $variant
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\OrderItem onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereActualShippingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereCostOfGoods($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereProductInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereShipmentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereShipmentProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereShipmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereTrackingNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\OrderItem withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\OrderItem withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereBuyerPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereExternalProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereFulfillmentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereIntegrationDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereInventoryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereItemPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereReturnStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereSellerDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereShippingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereTax2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereVariationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderItem whereVariationSku($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class OrderItem extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   The `cost_of_goods` is a JSON array because there's a chance it uses stocks from different batches which
     *      has different cost. This only happens if the quantity is > 1 (But we're using array regardless)
     * 
     * 2.   `fulfillment_status` needs to be below 30 if it's a successful order, and 30 or above for failed.
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id', 'shop_id', 'account_id', 'integration_id', 'product_id', 'product_variant_id',
        'external_id', 'external_product_id', 'name', 'sku', 'variation_name', 'variation_sku', 'quantity',
        'item_price', 'integration_discount', 'seller_discount', 'shipping_fee', 'tax', 'tax_2', 'grand_total',
        'buyer_paid','refunded_amount', 'fulfillment_status', 'return_status', 'inventory_status', 'product_inventory_id',
        'cost_of_goods', 'data', 'shipment_provider', 'shipment_type', 'shipment_method', 'tracking_number',
        'actual_shipping_fee'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'cost_of_goods' => 'array',
        'data' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'fulfillment_status_text'
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
     * Retrieves the account the order item belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    /**
     * Retrieves the order the order item belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Retrieves the shop the order item belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the inventory tied to this item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inventory()
    {
        return $this->belongsTo(ProductInventory::class, 'product_inventory_id', 'id');
    }

    /**
     * Retrieves the variant tied to this item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    /**
     * Retrieves the product tied to this item
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
