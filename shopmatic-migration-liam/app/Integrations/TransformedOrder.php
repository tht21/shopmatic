<?php


namespace App\Integrations;


use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\OrderLogType;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Events\OrderUpdated;
use App\Mail\NewOrderNotification;
use App\Models\Account;
use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Support\Facades\Mail;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;

class TransformedOrder
{

    public $externalId;
    public $externalSource;
    public $externalNumber;
    public $customerName;
    public $customerEmail;
    public $shippingAddress;
    public $billingAddress;
    public $shipByDate;
    public $currency;
    public $integrationDiscount;
    public $sellerDiscount;
    public $shippingFee;
    public $tax;
    public $tax2;
    public $commission;
    public $transactionFee;
    public $grandTotal;
    public $buyerPaid;
    public $settlementAmount;
    public $paymentStatus;
    public $paymentMethod;
    public $fulfillmentType;
    public $fulfillmentStatus;
    public $buyerRemarks;
    public $type;
    public $data;
    public $orderPlacedAt;
    public $orderUpdatedAt;
    public $orderPaidAt;
    public $items;
    private $notes;

    /**
     * TransformedOrder constructor.
     *
     *
     * @param string|null $externalId The main identifier for the order (Used for querying and etc)
     * @param string|null $externalSource Source for the order (Optional)
     * @param string|null $externalNumber Additional identifier for the order
     * @param string|null $customerName
     * @param string|null $customerEmail
     * @param TransformedAddress|null $shippingAddress
     * @param TransformedAddress|null $billingAddress
     * @param $shipByDate
     * @param string $currency
     * @param float|null $integrationDiscount
     * @param float|null $sellerDiscount
     * @param float|null $shippingFee
     * @param float|null $tax
     * @param float|null $tax2
     * @param float|null $commission
     * @param float|null $transactionFee
     * @param float $grandTotal
     * @param float|null $buyerPaid
     * @param float|null $settlementAmount
     * @param PaymentStatus $paymentStatus
     * @param string|null $paymentMethod
     * @param FulfillmentType $fulfillmentType
     * @param FulfillmentStatus $fulfillmentStatus
     * @param string|null $buyerRemarks
     * @param OrderType $type
     * @param array $data This should ONLY be attributes that's not in any of the other fields. As we'll display this to the users.
     * @param $orderPlacedAt
     * @param $orderUpdatedAt
     * @param $orderPaidAt
     * @param array $items
     *
     * @param null $notes
     * @throws \Exception
     */
    public function __construct($externalId, $externalSource, $externalNumber, $customerName, $customerEmail, $shippingAddress, $billingAddress,
            $shipByDate, $currency, $integrationDiscount, $sellerDiscount, $shippingFee, $tax, $tax2, $commission, $transactionFee, $grandTotal, $buyerPaid, $settlementAmount,
            PaymentStatus $paymentStatus, $paymentMethod, FulfillmentType $fulfillmentType, FulfillmentStatus $fulfillmentStatus,
            $buyerRemarks, OrderType $type, $data, $orderPlacedAt, $orderUpdatedAt, $orderPaidAt, $items, $notes = null)
    {

        $this->externalId = $externalId;
        $this->externalSource = $externalSource;
        $this->externalNumber = $externalNumber;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->shipByDate = $shipByDate;
        $this->currency = $currency;
        $this->integrationDiscount = $integrationDiscount;
        $this->sellerDiscount = $sellerDiscount;
        $this->shippingFee = $shippingFee;
        $this->tax = $tax;
        $this->tax2 = $tax2;
        $this->commission = $commission;
        $this->transactionFee = $transactionFee;
        $this->grandTotal = $grandTotal;
        $this->buyerPaid = $buyerPaid;
        $this->settlementAmount = $settlementAmount;
        $this->paymentStatus = $paymentStatus;
        $this->paymentMethod = $paymentMethod;
        $this->fulfillmentType = $fulfillmentType;
        $this->fulfillmentStatus = $fulfillmentStatus;
        $this->buyerRemarks = $buyerRemarks;
        $this->type = $type;
        $this->data = $data;
        $this->orderPlacedAt = $orderPlacedAt;
        $this->orderUpdatedAt = $orderUpdatedAt;
        $this->orderPaidAt = $orderPaidAt;
        $this->items = $items;
        $this->notes = $notes;

        // Validate all the data here to make sure it's correct / valid
//        if (!is_array($items) || empty($items)) {
//            set_log_extra('data', get_object_vars($this));
//            throw new \Exception('There is no items in this order.');
//        }

        foreach ($items as $item) {
            if (!($item instanceof TransformedOrderItem)) {
                set_log_extra('data', get_object_vars($this));
                throw new \Exception('Order item isn\'t of object TransformedOrderItem.');
            }
        }

        if (!is_null($billingAddress) && !($billingAddress instanceof TransformedAddress)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Billing address isn\'t of object TransformedAddress.');
        }

        if (!is_null($shippingAddress) && !($shippingAddress instanceof TransformedAddress)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Shipping address isn\'t of object TransformedAddress.');
        }

        // Just some logic and status checks

        if ($grandTotal < 0 && $paymentStatus !== PaymentStatus::PAID()) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Grand total is < 0 but status is not paid');
        }

        if (empty($orderPlacedAt)) {
            set_log_extra('data', get_object_vars($this));
            throw new \Exception('Order placed at not set');
        }
    }

    /**
     * Creates the order if it doesn't exist, or update the order if necessary
     * Also checks if we need to deduct the inventory / etc
     *
     * @param $account Account
     * @param array $options
     * @return Order
     * @throws \Exception
     */
    public function createOrder(Account $account, $options = ['import' => false, 'deduct' => true])
    {
        $shop = $account->shop;

        /** @var Order $order */
        $order = $shop->orders()->where(['account_id' => $account->id, 'external_id' => (string)$this->externalId])->first();

        // This means that it's an order we've previously pushed, so we ONLY update the `data` field
        // to make sure we don't miss anything
        if (!empty($order) && ($order->type == OrderType::SHADOW()->getValue() || !empty($order->parent_id))) {
            $order->update(['data' => $this->data]);
            return $order;
        }
        $order = Order::updateOrCreate([
            'shop_id' => $account->shop_id,
            'account_id' => $account->id,
            'integration_id' => $account->integration_id,
            'external_id' => (string)$this->externalId
        ], [

            'external_source' => $this->externalSource,
            'external_order_number' => $this->externalNumber,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'shipping_address' => $this->shippingAddress,
            'billing_address' => $this->billingAddress,
            'ship_by_date' => $this->shipByDate,
            'currency' => $this->currency,
            'integration_discount' => $this->integrationDiscount,
            'seller_discount' => $this->sellerDiscount,
            'shipping_fee' => $this->shippingFee,
            'tax' => $this->tax,
            'tax2' => $this->tax2,
            'commission' => $this->commission,
            'transaction_fee' => $this->transactionFee,
            'grand_total' => $this->grandTotal,
            'buyer_paid' => $this->buyerPaid,
            'settlement_amount' => $this->settlementAmount,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'fulfillment_type' => $this->fulfillmentType,
            'fulfillment_status' => $this->fulfillmentStatus,
            'buyer_remarks' => $this->buyerRemarks,
            'notes' => $this->notes,
            'type' => $this->type,
            'data' => $this->data,
            'order_placed_at' => $this->orderPlacedAt,
            'order_updated_at' => $this->orderUpdatedAt,
            'order_paid_at' => $this->orderPaidAt,
        ]);

        /** @var TransformedOrderItem $item */
        foreach ($this->items as $item) {
            $item->createItem($order, $options);
        }

        // Send new order notification based on setting / etc.
        // Here it's so we don't create new order notifications for older orders
        if (empty($options['import'])) {

            if ($order->wasRecentlyCreated) {
                if ($order->account->hasFeature(['orders', 'order_notification'])) {
                    if(!empty($order->external_order_number)) {
                        $order->shop->sendEmailNotification(new NewOrderNotification($order));
                    }
                }

                OrderLog::generateLog($order, 'New order synced from account.', OrderLogType::CREATING());
            } else {
                OrderLog::generateLog($order, 'Order updated from account.', OrderLogType::UPDATING());
            }

            // Fires the event to update all the temporary fields / perform any tasks related to other integrations
            //TODO: Separate out updating reporting, as we need that regardless of import or not
            event(new OrderUpdated($order));
        } else {
            if ($order->wasRecentlyCreated) {
                OrderLog::generateLog($order, 'Order imported from account.', OrderLogType::IMPORTING());
            } else {
                OrderLog::generateLog($order, 'Order updated from importing.', OrderLogType::UPDATING());
            }
        }

        $order = $order->fresh();

        return $order;
    }

}
