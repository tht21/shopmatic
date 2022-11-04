<?php

namespace App\Integrations\Woocommerce;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\IntegrationSyncData;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedAddress;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderAdapter extends AbstractOrderAdapter
{
    /**
     * Retrieves a single order
     *
     * @param $externalId
     * @param array $options
     * @return Order
     * @throws \Exception
     */
    public function get($externalId, $options = ['deduct' => true])
    {
        try {
            $response = $this->client->request('get', 'orders/'.$externalId);

            $order = $this->transformOrder($response);
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve order.');
        }

        return $this->handleOrder($order, $options);
    }

    /**
     * Import all orders
     *
     * @param array $options
     * @return void
     * @throws \Exception
     */
    public function import($options = ['deduct' => false])
    {
        // This is so it wont create new notifications
        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }

        $parameters = [
            'status' => 'any'
        ];
        $this->fetchOrders($options, $parameters);

        // Any status does not include trash
        $parameters['status'] = 'trash';
        $this->fetchOrders($options, $parameters);
    }

    /**
     * Incremental order sync
     *
     * @throws \Exception
     */
    public function sync()
    {
        $options['import'] = false;
        $options['deduct'] = $this->account->hasFeature(['orders', 'deduct_inventory']);

        // Sub 24 hours in case of timestamp
        $parameters = [
            'status' => 'any',
            'after' => $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now(), true)->subDays(14)->format('Y-m-d\TH:i:s')
        ];
        $this->fetchOrders($options, $parameters);

        // Any status does not include trash
        $parameters['status'] = 'trash';
        $this->fetchOrders($options, $parameters);

        $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
    }


    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $options
     * @param $parameters
     * @throws \Exception
     */
    private function fetchOrders($options, $parameters)
    {
        $parameters['page'] = 1;
        $parameters['per_page'] = 50;
        $orders = [];

        do {
            try {
                $response = $this->client->request('get', 'orders', $parameters);
            } catch(\Exception $e) {
                set_log_extra('response', $e);
                throw new \Exception('Woocommerce-'.$this->account->id.' Unable to connect and retrieve orders.');
            }
            $orders = array_merge($orders, $response);
            $parameters['page']++;
        } while (count($response) > 0);

        foreach ($orders as $order) {
            try {
                $order = $this->transformOrder($order);

            } catch (\Exception $e) {
                set_log_extra('order', $order);
                throw $e;
            }
            $this->handleOrder($order, $options);
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order->id;
        $externalNumber = $order->number;
        $externalSource = $this->account->integration->name;

        $customerName = $order->billing->first_name . ' ' . $order->billing->last_name;
        $customerEmail = $order->billing->email;

        // Shipping and Billing will be using the same recipient address
        $shipping = $order->shipping;
        // To remove any if it's empty
        $shippingAddress = new TransformedAddress($shipping->company, $shipping->first_name . ' ' . $shipping->last_name,
            $shipping->address_1, $shipping->address_2, null, null, null,
            $shipping->city, $shipping->postcode, $shipping->state, $shipping->country, null
        );
        $billing = $order->billing;
        $billingAddress = new TransformedAddress($billing->company, $billing->first_name . ' ' . $billing->last_name,
            $billing->address_1, $billing->address_2, null, null, null,
            $billing->city, $billing->postcode, $billing->state, $billing->country, $billing->phone, $billing->email
        );


        $shipByDate = null;

        if (is_null($order->date_paid)) {
            $paymentStatus = PaymentStatus::UNPAID();
        } else {
            $paymentStatus = PaymentStatus::PAID();
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        $sellerDiscount = $order->discount_total;
        $shippingFee = filter_var($order->shipping_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $tax = $order->total_tax;
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $grandTotal = $order->total;

        $settlementAmount = 0;

        $paymentMethod = $order->payment_method;

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if ($order->status == 'pending') {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
            $paymentStatus = PaymentStatus::UNPAID();
        } elseif ($order->status == 'processing') {
            $fulfillmentStatus = FulfillmentStatus::PROCESSING();
        } elseif ($order->status == 'on-hold') {
            $paymentStatus = PaymentStatus::PROCESSING();
            $fulfillmentStatus = FulfillmentStatus::ON_HOLD();
        } elseif ($order->status == 'completed') {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif ($order->status == 'cancelled' || $order->status == 'failed' || $order->status == 'trash') {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
            $paymentStatus = PaymentStatus::CANCELLED();
        } elseif ($order->status == 'refunded') {
            $fulfillmentStatus = FulfillmentStatus::RETURNED();
            $paymentStatus = PaymentStatus::REFUNDED();
        } elseif ($order->status == 'ready-pickup') {
            $fulfillmentStatus = FulfillmentStatus::READY_FOR_PICKUP();
            $paymentStatus = PaymentStatus::PAID();
        } elseif ($order->status == 'delivered') {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
            $paymentStatus = PaymentStatus::PAID();
        } else {
            set_log_extra('order_status', $order->status);
            set_log_extra('order', $order);
            throw new \Exception('Woocommerce has different fulfilment status');
        }

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $order->total : 0;

        // This is customer/buyer remark or note
        $buyerRemarks = $order->customer_note;

        if (!empty($order->shipping_lines)) {
            foreach ($order->shipping_lines as $shipping_line) {
                $buyerRemarks .= ' -- Delivery Info: ' . $shipping_line->method_title;
            }
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::parse($order->date_created);
        $orderUpdatedAt = Carbon::parse($order->date_modified);

        if ($paymentStatus->equals(PaymentStatus::PAID()) && $order->date_paid) {
            $orderPaidAt = Carbon::parse($order->date_paid);
        } else {
            $orderPaidAt = null;
        }

        $data = [
            'order_key' => $order->order_key,
            'transaction_id' => $order->transaction_id,
        ];

        $items = [];
        foreach ($order->line_items as $item) {

            $itemExternalId = $item->id;
            $itemName = $item->name;
            $externalProductId = $item->product_id;
            $sku = $item->sku;
            $variationName = 'N/A';
            $variationSku = $sku;

            $quantity = $item->quantity;

            $itemPrice = $item->price;

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = 0;

            $itemShippingFee = 0;

            $itemTax = $item->total_tax;
            $itemTax2 = 0;

            $itemGrandTotal = $item->total;
            $itemBuyerPaid = $itemGrandTotal;

            // Woocommerce does not have item status
            $itemFulfillmentStatus = $fulfillmentStatus;

            $shipmentProvider = null;
            $trackingNumber = null;
            $shipmentType = null;
            $shipmentMethod = null;

            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = $shippingFee;

            $itemData = [
                'variation_id' => $item->variation_id,
            ];

            $items[] = new TransformedOrderItem($itemExternalId, $externalProductId, $itemName, $sku, $variationName, $variationSku, $quantity,
                $itemPrice, $itemIntegrationDiscount, $itemSellerDiscount, $itemShippingFee, $itemTax, $itemTax2, $itemGrandTotal, $itemBuyerPaid,
                $itemFulfillmentStatus, $shipmentProvider, $shipmentType, $shipmentMethod, $trackingNumber, $returnStatus, $costOfGoods, $actualShippingFee,
                $itemData);
        }

        $order = new TransformedOrder($externalId, $externalSource, $externalNumber, $customerName, $customerEmail, $shippingAddress, $billingAddress,
            $shipByDate, $currency, $integrationDiscount, $sellerDiscount, $shippingFee, $tax, $tax2, $commission, $transactionFee, $grandTotal, $buyerPaid, $settlementAmount,
            $paymentStatus, $paymentMethod, $fulfillmentType, $fulfillmentStatus, $buyerRemarks, $type, $data, $orderPlacedAt,
            $orderUpdatedAt, $orderPaidAt, $items);
        return $order;
    }

    /**
     * @inheritDoc
     */
    public function availableActions(Order $order)
    {
        // General actions are those that can be called regardless of status
        $general = [''];

        // These are status specific in which they depend on the status of the order
        $statusSpecific = [];

//        if ($order->fulfillment_status === FulfillmentStatus::PENDING()->getValue()) {
//            $statusSpecific[] = 'fulfillment';
//        } elseif ($order->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue()) {
//            $statusSpecific[] = 'fulfillment';
//        } elseif ($order->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue()) {
//            $statusSpecific[] = 'fulfillment';
//        } elseif ($order->fulfillment_status === FulfillmentStatus::SHIPPED()->getValue()) {
//            $statusSpecific[] = 'fulfillment';
//        }

        $statusSpecific[] = 'fulfillment';
        $statusSpecific[] = 'cancel';
        $statusSpecific[] = 'refund';

        return array_merge($general, $statusSpecific);
    }

    /**
     * Update order's status
     *
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function fulfillment(Order $order, Request $request)
    {

        if (!isset($request['fulfillment_status'])) {
            return $this->respondBadRequestError('Please select a status');
        }

        $fulfillmentStatus = $request->input('fulfillment_status');
        $status = '';

        if($fulfillmentStatus === FulfillmentStatus::PENDING()->getValue()) {
            $status = 'pending';
        } elseif ($fulfillmentStatus === FulfillmentStatus::PROCESSING()->getValue()) {
            $status = 'processing';
        } elseif ($fulfillmentStatus === FulfillmentStatus::ON_HOLD()->getValue()) {
            $status = 'on-hold';
        } elseif ($fulfillmentStatus === FulfillmentStatus::SHIPPED()->getValue()) {
            $status = 'completed';
        }

        if ($status == '') {
            return $this->respondBadRequestError('Please select a status');
        }

        $parameters = [
            'status' => $status
        ];

        try {
            $response = $this->client->request('put', 'orders/'.$order->external_id, $parameters);
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to update shipping orders.');
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Refund order
     *
     * @param Order $order
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function refund(Order $order, Request $request)
    {

        $parameters = [
            'status' => 'refunded'
        ];
        try {
            $response = $this->client->request('post', 'orders/'.$order->external_id, $parameters);
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to refund orders.');
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Refund order
     *
     * @param Order $order
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function cancel(Order $order, Request $request)
    {

        $parameters = [
            'status' => 'cancelled'
        ];

        try {
            $response = $this->client->request('put', 'orders/'.$order->external_id, $parameters);
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Woocommerce-'.$this->account->id.' Unable to refund orders.');
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }
}
