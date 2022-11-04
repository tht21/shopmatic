<?php

namespace App\Integrations\Shopee;

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
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $parameters = [
            'order_sn_list' => $externalId
        ];

        try {
            $orders = $this->getOrdersDetail($parameters);

            foreach ($orders as $order) {
                $order['escrow_detail'] = $this->getEscrowDetails($order['order_sn']);

                $order = $this->transformOrder($order);
            }
            return $this->handleOrder($order, $options);
        } catch (\Exception $e) {
            set_log_extra('order', $order ?? $orders ?? null);
            throw $e;
        }
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

        // Get all the orders start from last year
        $begin = new Carbon('last year');
        $end = new Carbon('15 days');

        $periods = $this->getPeriodDates($begin, $end);

        $updateTimeFrom = null;
        foreach ($periods as $period) {
            if (!is_null($updateTimeFrom)) {
                $updateTimeTo = $period->setTime(0, 0);

                $parameters = [
                    'time_from' => $updateTimeFrom->getTimestamp(),
                    'time_to' => $updateTimeTo->getTimestamp(),
                ];
                $this->fetchOrders($options, $parameters, true);

                $updateTimeFrom = $updateTimeTo; // Replace it
            } else {
                $updateTimeFrom = $period->setTime(0, 0);
            }
        }
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

        $begin = $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now()->subDays(15), true);
        $end = now();
        $periods = $this->getPeriodDates($begin, $end);
        $debugLog = '[Shopee Order Sync]Debug Log|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
        Log::info($debugLog);

        if ($periods->count() > 1) {
            $periods = $periods->toArray();
            $updateTimeFrom = $periods[0];

            foreach ($periods as $period) {
                $updateTimeTo = $period->toDateTime();
                $parameters = [
                    'time_from' => $updateTimeFrom->getTimestamp(),
                    'time_to' => $updateTimeTo->getTimestamp(),
                ];

                $this->fetchOrders($options, $parameters, false);

                $updateTimeFrom = $updateTimeTo; // Replace it
            }
        } else {
            $updateTimeFrom = $periods->getStartDate();
            $updateTimeTo = $periods->getEndDate();

            $parameters = [
                'time_from' => $updateTimeFrom->getTimestamp(),
                'time_to' => $updateTimeTo->getTimestamp(),
            ];
            $this->fetchOrders($options, $parameters, false);
        }

        $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
    }

    /**
     * Return interval periods date time
     *
     * @param $begin
     * @param $end
     * @param string $interval
     * @return CarbonPeriod
     */
    public function getPeriodDates($begin, $end, $interval = '15 day')
    {
        $interval = CarbonInterval::createFromDateString($interval);
        return new CarbonPeriod($begin, $interval, $end);
    }

    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $options
     * @param $parameters
     * @throws \Exception
     */
    private function fetchOrders($options, $parameters, $is_import = false)
    {
        $parameters = [
            'page_size' => 50,
            'time_range_field' => 'create_time',
        ] + $parameters;
        if ($is_import == true) {
            $parameters['time_range_field'] = 'create_time';
        } else {
            $parameters['time_range_field'] = 'update_time';
        }
        do {
            $orders = null;

            $response = $this->client->requestv2('GET', '/order/get_order_list', $parameters);
            if (empty($response['error'])) {
                if (!empty($response['response']['order_list'])) {
                    $orderIds = collect($response['response']['order_list'])->pluck('order_sn');
                    $parameters_order_detail = [
                        'order_sn_list' => $orderIds->implode(',')
                    ];
                    $orders = $this->getOrdersDetail($parameters_order_detail);
                    foreach ($orders as $key => $order) {
                        $order['escrow_detail'] = $this->getEscrowDetails($order['order_sn']);
                        try {
                            $order = $this->transformOrder($order);
                        } catch (\Exception $e) {
                            set_log_extra('order', $order);
                            throw $e;
                        }
                        $this->handleOrder($order, $options);
                    }
                }
            } else {
                set_log_extra('parameters', $parameters);
                set_log_extra('orders', $response);
                throw new \Exception('Unable to retrieve orders for Shopee');
            }

            if ($response['response']['more']) {
                $parameters['cursor'] = $response['response']['next_cursor'];
            }
        } while ($response['response']['more']);
    }

    /**
     * Get the specific order's details
     *
     * @param $parameters
     * @return array
     * @throws \Exception
     */
    private function getOrdersDetail($parameters)
    {
        $parameters['response_optional_fields'] = 'shipping_carrier,recipient_address,pay_time,payment_method,buyer_cancel_reason,cancel_by,cancel_reason,item_list';
        $response = $this->client->requestv2('get', '/order/get_order_detail', $parameters);
        if (isset($response['response']) && empty($response['error'])) {
            return $response['response']['order_list'];
        } else {
            set_log_extra('parameters', $parameters);
            set_log_extra('orders', $response);
            throw new \Exception('Unable to retrieve orders detail for Shopee');
        }
    }

    private function getEscrowDetails($orderId)
    {
        $parameter = [
            'order_sn' => $orderId
        ];

        $response = $this->client->requestv2('get', '/payment/get_escrow_detail', $parameter);

        if (isset($response['response']) && empty($response['error'])) {
            return $response['response'];
        } else {
            set_log_extra('parameters', $parameter);
            set_log_extra('orders', $response);
            throw new \Exception('Unable to retrieve escrow detail for Shopee');
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order['order_sn'];
        $externalNumber = null;
        $externalSource = $this->account->integration->name;

        $customerName = $order['escrow_detail']['buyer_user_name'];

        // Shopee no longer provides this
        $customerEmail = null;
        $debugLog = '[Shopee transformOrder Items]Debug Log|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Customer Name|' . $customerName . '|External Order Id|' . $externalId . '| External Order Number' . $externalNumber . '|External Source|' . $externalSource;
        Log::info($debugLog);
        // Shipping and Billing will be using the same recipient address
        $shipping = $order['recipient_address'];
        // To remove any if it's empty

        $shippingAddress = new TransformedAddress(
            null,
            $shipping['name'],
            $shipping['full_address'],
            null,
            null,
            null,
            null,
            $shipping['city'] ?? null,
            $shipping['zipcode'] ?? null,
            $shipping['state'] ?? null,
            $shipping['country'] ?? null,
            $shipping['phone']
        );
        $billing = $order['recipient_address'];
        $billingAddress = new TransformedAddress(
            null,
            $billing['name'],
            $billing['full_address'],
            null,
            null,
            null,
            null,
            $billing['city'] ?? null,
            $billing['zipcode'] ?? null,
            null,
            $billing['country'] ?? null,
            $billing['phone']
        );

        if (!empty($order['ship_by_date'])) {
            $shipByDate = Carbon::createFromTimestamp($order['ship_by_date']);
        } else {
            $shipByDate = null;
        }

        $paymentStatus = PaymentStatus::PAID();
        // If order status is unpaid or pay time is null, then it will be unpaid status
        if ($order['order_status'] == 'UNPAID' || is_null($order['pay_time'])) {
            $paymentStatus = PaymentStatus::UNPAID();
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        $sellerDiscount = 0;
        $shippingFee = filter_var($order['escrow_detail']['order_income']['estimated_shipping_fee'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $tax = 0;
        $tax2 = 0;

        /* Marketplace fee - escrow amount */
        $commission = 0;
        $transactionFee = 0;
        $settlementAmount = $order['escrow_detail']['order_income']['escrow_amount'];

        if (isset($order['escrow_detail']) && !empty($order['escrow_detail'])) {
            if ($order['escrow_detail']['order_income']['final_shipping_fee'] != 0) {
                $shippingFee = -$order['escrow_detail']['order_income']['final_shipping_fee'] - $order['escrow_detail']['order_income']['estimated_shipping_fee'];
            }

            $commission = $order['escrow_detail']['order_income']['commission_fee'];
            $transactionFee = $order['escrow_detail']['order_income']['credit_card_transaction_fee'];
        }

        $paymentMethod = $order['payment_method'];

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if ($order['order_status'] == 'UNPAID') {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
            // Shopee after setting tracking no doesnt change the status, thus we need to change it to processing for action (AWB)
            if (!empty($order['tracking_no'])) {
                $fulfillmentStatus = FulfillmentStatus::PROCESSING();
            }
        } elseif ($order['order_status'] == 'SHIPPED') {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif ($order['order_status'] == 'COMPLETED') {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
        } elseif ($order['order_status'] == 'IN_CANCEL') {
            $fulfillmentStatus = FulfillmentStatus::REQUEST_CANCEL();
        } elseif ($order['order_status'] == 'CANCELLED') {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
            $paymentStatus = PaymentStatus::CANCELLED();
        } elseif ($order['order_status'] == 'READY_TO_SHIP' || $order['order_status'] == 'PROCESSED') {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
        } elseif ($order['order_status'] == 'RETRY_SHIP') {
            $fulfillmentStatus = FulfillmentStatus::RETRY_SHIP();
        } elseif ($order['order_status'] == 'TO_CONFIRM_RECEIVE') {
            $fulfillmentStatus = FulfillmentStatus::TO_CONFIRM_DELIVERED();
        } elseif ($order['order_status'] == 'TO_RETURN') {
            $fulfillmentStatus = FulfillmentStatus::RETURNED();
        } else {
            set_log_extra('order_status', $order['order_status']);
            set_log_extra('order', $order);
            throw new \Exception('Shopee has different fulfilment status');
        }

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $order['escrow_detail']['order_income']['buyer_total_amount'] : 0;

        // This is customer/buyer remark or message
        $buyerRemarks = $order['message_to_seller'];

        if (!empty($order['checkout_shipping_carrier'])) {
            $buyerRemarks .= ' -- Delivery Info: ' . $order['checkout_shipping_carrier'];
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::createFromTimestamp($order['create_time']);
        $orderUpdatedAt = Carbon::createFromTimestamp($order['update_time']);

        if ($paymentStatus->equals(PaymentStatus::PAID()) && $order['pay_time']) {
            $orderPaidAt = Carbon::createFromTimestamp($order['pay_time']);
        } else {
            $orderPaidAt = null;
        }

        $data = [
            'cancel_reason' => $order['cancel_reason'],
            'cancel_by' => $order['cancel_by'],
            'buyer_cancel_reason' => $order['buyer_cancel_reason']
        ];

        $items = [];
        $grandTotal = 0;
        foreach ($order['item_list'] as $item) {

            $itemExternalId = $item['item_id'];
            $itemName = $item['item_name'];
            $externalProductId = (!empty($item['model_id']) || $item['model_id'] == 0) ? $item['model_id'] : $item['item_id'];
            $sku = $item['item_sku'];
            $variationName = $item['model_name'] ?? 'N/A';
            $variationSku = (!$item['model_sku'] || empty($item['model_sku'])) ? $sku : $item['model_sku'];

            $quantity = $item['model_quantity_purchased'];

            $itemPrice = $item['model_original_price'];

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = $item['model_discounted_price'];

            $itemShippingFee = 0;

            $itemTax = 0;
            $itemTax2 = 0;

            // If discount price and item price diff then take discount price
            if ($item['model_discounted_price'] && $item['model_discounted_price'] != 0 && $item['model_discounted_price'] != $itemPrice) {
                $itemPaidPrice = $itemSellerDiscount;
            } else {
                $itemPaidPrice = $itemPrice;
            }

            $itemGrandTotal = $item['model_quantity_purchased'] * $itemPaidPrice;
            $itemBuyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $itemGrandTotal : 0;

            $grandTotal += $itemGrandTotal;

            // Shopee does not have item status
            $itemFulfillmentStatus = $fulfillmentStatus;

            $shipmentProvider = $order['shipping_carrier'];
            // $trackingNumber = $order['tracking_no'];
            $shipmentType = null;
            $shipmentMethod = null;

            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = $shippingFee;

            $itemData = [
                'is_wholesale' => $item['wholesale'],
                'is_add_on_deal' => $item['add_on_deal'],
                'promotion_id' => $item['promotion_id'],
                'promotion_type' => $item['promotion_type'],
                'is_main_item' => $item['main_item'],
            ];

            $items[] = new TransformedOrderItem(
                $itemExternalId,
                $externalProductId,
                $itemName,
                $sku,
                $variationName,
                $variationSku,
                $quantity,
                $itemPrice,
                $itemIntegrationDiscount,
                $itemSellerDiscount,
                $itemShippingFee,
                $itemTax,
                $itemTax2,
                $itemGrandTotal,
                $itemBuyerPaid,
                $itemFulfillmentStatus,
                $shipmentProvider,
                $shipmentType,
                $shipmentMethod,
                '', // $trackingNumber
                $returnStatus,
                $costOfGoods,
                $actualShippingFee,
                $itemData
            );
        }

        $order = new TransformedOrder(
            $externalId,
            $externalSource,
            $externalNumber,
            $customerName,
            $customerEmail,
            $shippingAddress,
            $billingAddress,
            $shipByDate,
            $currency,
            $integrationDiscount,
            $sellerDiscount,
            $shippingFee,
            $tax,
            $tax2,
            $commission,
            $transactionFee,
            $grandTotal,
            $buyerPaid,
            $settlementAmount,
            $paymentStatus,
            $paymentMethod,
            $fulfillmentType,
            $fulfillmentStatus,
            $buyerRemarks,
            $type,
            $data,
            $orderPlacedAt,
            $orderUpdatedAt,
            $orderPaidAt,
            $items
        );
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

        if ($order->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue()) {
            $statusSpecific[] = 'initInfo';
            $statusSpecific[] = 'fulfillment';
            $statusSpecific[] = 'cancel';
            $statusSpecific[] = 'reasons';

            if (!in_array('bill', $statusSpecific)) {
                $statusSpecific[] = 'bill';
            }
        } else if ($order->fulfillment_status === FulfillmentStatus::REQUEST_CANCEL()->getValue()) {
            $statusSpecific[] = 'cancellation';
        } else if ($order->fulfillment_status === FulfillmentStatus::RETRY_SHIP()->getValue()) {
            $statusSpecific[] = 'initInfo';
            if (!in_array('bill', $statusSpecific)) {
                $statusSpecific[] = 'bill';
            }
        }

        return array_merge($general, $statusSpecific);
    }

    /**
     * Retrieves all the cancellation reasons for the order
     *
     * @return mixed
     * @throws \Exception
     */
    public function reasons()
    {
        return [
            'COD_NOT_SUPPORTED' => 'COD Not Supported',
            'CUSTOMER_REQUEST' => 'Customer Request',
            'OUT_OF_STOCK' => 'Out Of Stock',
            'UNDELIVERABLE_AREA' => 'Undeliverable Area',
        ];
    }

    /**
     * Marks the items as being cancelled
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function cancel(Order $order, Request $request)
    {
        // Cancel reason is required
        if (!$cancelReason = $request->input('cancel_reason')) {
            return $this->respondBadRequestError('You need to specify the reason.');
        }
        $parameters = [
            'order_sn' => $order->external_id,
            'cancel_reason' => $cancelReason,
        ];

        // If out of stock, item id is required
        if ($cancelReason === 'OUT_OF_STOCK') {
            $item = $request->input('order_item_id');
            if (empty($item)) {
                return $this->respondBadRequestError('You need to have one order item');
            }

            // Item is exists
            $actualItem = $order->items()->where('id', $item)->first();

            if (!$actualItem) {
                return $this->respondBadRequestError('Invalid order item');
            }

            $parameters['item_list'][] = [
                'item_id' => (int) $actualItem->external_id,
                'model_id' => (int) $actualItem->external_product_id ?? 0
            ];
        }

        $response = $this->client->requestv2('POST', '/order/cancel_order', $parameters);

        if (!empty($response['error'])) {
            set_log_extra('response', $response);
            set_log_extra('parameters', $parameters);

            if (!empty($response['message'])) {
                return $this->respondBadRequestError($response['message']);
            } else {
                throw new \Exception('Unable to cancel order for Shopee');
            }
        }

        $this->get($order->external_id, ['deduct' => false]);
        // CSM-1204 P1 Cancellation of orders that result in 0 stock is not updated in CS - Shopee
        if ($cancelReason === 'OUT_OF_STOCK' || $cancelReason == 'Out of Stock') {
            $special_reason = ' because of shopee out of stock (cancelled from CS system)';
            $productInventory = $actualItem->inventory;
            $changed = 0 - $productInventory->stock;
            $message = 'Restocked from order ' . ($order->external_id ? $order->external_id : $order->id) . ($order->external_source ? ' (' . $order->external_source . ')' . ' ' . $special_reason : '');
            if ($inventory = $actualItem->inventory) {
                $inventory->addStock(
                    $changed,
                    'child',
                    $message,
                    $order->id,
                    get_class($order),
                    false
                ); // will sync inventory after sync order
            }
        }


        return true;
    }

    /**
     * Perform cancellation action from buyer side
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function cancellation(Order $order, Request $request)
    {
        // Action is required
        if (!$action = $request->input('action')) {
            return $this->respondBadRequestError('Accept or reject is required.');
        }

        if ($action === 'accept') {
            $operation = 'ACCEPT';
        } elseif ($action === 'reject') {
            $operation = 'REJECT';
        } else {
            return $this->respondBadRequestError('Invalid of cancellation action.');
        }

        $parameters = [
            'order_sn' => $order->external_id,
            'operation' => $operation
        ];
        $response = $this->client->requestv2('POST', '/order/handle_buyer_cancellation', $parameters);

        if (!empty($response['error'])) {
            set_log_extra('response', $response);
            set_log_extra('parameters', $parameters);

            if (!empty($response['message'])) {
                return $this->respondBadRequestError($response['message']);
            } else {
                throw new \Exception('Unable to update buyer cancellation for Shopee');
            }
        }

        $this->get($order->external_id, ['deduct' => false]);
        return true;
    }

    /**
     * Get logistic info for init
     *
     * @param Order $order
     * @return array|mixed
     * @throws \Exception
     */
    public function initInfo(Order $order)
    {
        $parameters = ['order_sn' => $order->external_id];
        $response = $this->client->requestv2('get', '/logistics/get_shipping_parameter', $parameters);

        if (!empty($response['error'])) {
            set_log_extra('response', $response);
            set_log_extra('parameters', $parameters);

            if (!empty($response['message'])) {
                return $this->respondBadRequestError($response['message']);
            } else {
                throw new \Exception('Unable to get logistic info for Shopee');
            }
        }

        /*$result = [];
        if (isset($response['info_needed']['pickup']) && !empty($response['info_needed']['pickup'])) { // Pickup logistic
            $logistic = 'pickup';
            $result[$logistic] = [];

            // If contain address_id, then get logistics address
            if (in_array('address_id', $response['info_needed']['pickup']) && isset($response['pickup']['address_list']) && !empty($response['pickup']['address_list'])) {
                $result[$logistic]['address_list'] = $response['pickup']['address_list'];
            }
        } else if (isset($response['info_needed']['dropoff']) && !empty($response['info_needed']['dropoff'])) {
            $logistic = 'dropoff';
            $result[$logistic] = [];

            // If contain branch_id, then get branch list
            if (in_array('branch_id', $response['info_needed']['dropoff']) && isset($response['dropoff']['branch_list']) && !empty($response['dropoff']['branch_list'])) {
                $result[$logistic] = $response['dropoff']['branch_list'];
            }

            if (in_array('sender_real_name', $response['info_needed']['dropoff'])) {
                $result[$logistic]['sender_real_name'] = true;
            }
            if (in_array('tracking_no', $response['info_needed']['dropoff'])) {
                $result[$logistic]['tracking_no'] = true;
            }
        } else if (isset($response['info_needed']['non_integrated'])) { // Non integrated type
            $logistic = 'non_integrated';
            $result[$logistic] = [];

            if (in_array('tracking_no', $response['info_needed']['non_integrated'])) {
                $result[$logistic]['tracking_no'] = true;
            }
        }*/

        return $this->respond($response['response']);
    }

    /**
     * Update order's shipping
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function fulfillment(Order $order, Request $request)
    {
        // Type validation
        if (!$type = $request->input('type')) {
            return $this->respondBadRequestError('Shipping type is required');
        }
        if ($type !== 'pickup' && $type != 'dropoff' && $type != 'non_integrated') {
            return $this->respondBadRequestError('Invalid of shipping type');
        }

        $parameters = [
            'order_sn' => $order->external_id,
            $type => []
        ];

        // Pickup validation
        if ($type === 'pickup') {
            if ($request->input('address_id')) {
                $parameters['pickup']['address_id'] = (int) $request->input('address_id');
            }
            if ($request->input('pickup_time_id')) {
                $parameters['pickup']['pickup_time_id'] = (string) $request->input('pickup_time_id');
            }
            // if its empty, is will be treated as array instead of object
            if (empty($parameters['pickup'])) {
                $parameters['pickup'] = (object) [];
            }
        }

        // Dropoff validation
        if ($type === 'dropoff') {
            if ($request->input('branch_id')) {
                $parameters['dropoff']['branch_id'] = (int) $request->input('branch_id');
            }

            if ($request->input('sender_real_name')) {
                $parameters['dropoff']['sender_real_name'] = (string) $request->input('sender_real_name');
            } else if ($request->input('tracking_no')) {
                $parameters['dropoff']['tracking_no'] = (string) $request->input('tracking_no');
            }
            // if its empty, is will be treated as array instead of object
            if (empty($parameters['dropoff'])) {
                $parameters['dropoff'] = (object) [];
            }
        }

        // Non Integrated validation
        if ($type === 'non_integrated') {
            $parameters['non_integrated']['tracking_no'] = (string) $request->input('tracking_no');
        }
        /*$parameters['pickup']['address_id'] = 0;
        $parameters['pickup']['pickup_time_id'] = null;*/
        $response = $this->client->requestv2('POST', '/logistics/ship_order', $parameters);
        if (isset($response['error']) && $response['error'] != '') {
            if ($response['error'] === 'logistics.ship_order_not_ready_to_ship') {
                $get_tracking_info_response = $this->client->requestv2('GET', '/logistics/get_tracking_info', ['order_sn' => $order->external_id]);
                if (isset($get_tracking_info_response['response']) && isset($get_tracking_info_response['response']['logistics_status']) && $get_tracking_info_response['response']['logistics_status'] == "LOGISTICS_REQUEST_CREATED") {
                    $message = 'Logistics already created for this order';
                    throw new \Exception($message);
                }
                else {
                    $message = (isset($get_tracking_info_response['message']) && !empty($get_tracking_info_response['message'])) ? $get_tracking_info_response['message'] : 'Unable to update shipping for Shopee';
                    return $this->respondBadRequestError($message);
                }
            } else {
                $message = (isset($response['message']) && !empty($response['message'])) ? $response['message'] : 'Unable to update shipping for Shopee';
                return $this->respondBadRequestError($message);
            }
        }
        // make sure when we fetch order, we are getting the updated status
        sleep(3);
        /*
         * @NOTE - Once we updated fulfillment, when we retrieve again from shopee it does not reflect the latest order status
         * But only prod will use job to get the order again.
         * */
        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Retrieve airway bill
     *
     * @param Order $order
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function bill($order, Request $request)
    {
        $isBulk = $request->get('is_bulk', false);

        // For bulk orders
        if ($isBulk) {
            $parameters = [
                'order_list' => []
            ];

            foreach ($order as $orderId) {
                $ord = Order::whereId($orderId)->first();
                $parameters['order_list'][] = [
                    'order_sn' => $ord->external_id,
                ];
            }
        } else {
            // Single order
            $parameters = [];
            $parameters['order_list'][] = [
                'order_sn' => $order->external_id,

            ];
        }
        if (count($parameters['order_list']) > 0) {
            foreach ($parameters['order_list']  as $key => $oderList) {
                $response_get_tracking_number = $this->client->requestv2('get', '/logistics/get_tracking_number', ['order_sn' => $oderList['order_sn']]);
                if (!empty($response_get_tracking_number['error'])) {
                    $message = (!empty($response_get_tracking_number['message'])) ? $response_get_tracking_number['message'] : 'Unable to get_tracking_number';
                    set_log_extra('parameters', $parameters);
                    return $this->respondBadRequestError($response_get_tracking_number['message']);
                }
                $parameters['order_list'][$key]['tracking_number'] = $response_get_tracking_number['response']['tracking_number'];
            }
        }
        $response_create_shipping_document = $this->client->requestv2('post', '/logistics/create_shipping_document', $parameters);
        if (!empty($response_create_shipping_document['error'])) {
            $message = (!empty($response_create_shipping_document['message'])) ? $response_create_shipping_document['message'] : 'Unable to create_shipping_document';
            if(stripos($message,'please check result_list for more details') >= 0
            && count($response_create_shipping_document['response']['result_list']) > 0) {
                return $this->respondBadRequestError($response_create_shipping_document['response']['result_list'][0]['fail_message']);
            } 
            else {
                return $this->respondBadRequestError($message);
            }
        }
        $response = $this->client->responseDownload('/logistics/download_shipping_document', $parameters, $order->external_id);

        if ($response) {
            set_log_extra('response', $response);
            set_log_extra('parameters', $parameters);
            return $this->respondBadRequestError($response['message']);
        }

        $response =
            [
                ['airway_bill' => '/' . $order->external_id . '.pdf']
            ];
        return $this->respond($response);
    }
}
