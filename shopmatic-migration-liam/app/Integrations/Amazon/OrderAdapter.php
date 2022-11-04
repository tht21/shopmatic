<?php

namespace App\Integrations\Amazon;

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
use ClouSale\AmazonSellingPartnerAPI\Api\OrdersApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class OrderAdapter extends AbstractOrderAdapter
{
    const STATUS_PARTIALLY_SHIPPED = 'PartiallyShipped';
    const STATUS_SHIPPED = 'Shipped';
    const STATUS_UNSHIPPED = 'Unshipped';
    const STATUS_PENDING_AVAILABILITY = 'PendingAvailability';
    const STATUS_PENDING = 'Pending';
    const STATUS_INVOICE_UNCONFIRMED = 'InvoiceUnconfirmed';
    const STATUS_CANCELED = 'Canceled';
    const STATUS_UNFULFILLABLE = 'Unfulfillable';

    /**
     * Retrieves a single order
     *
     * @param $externalId
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function get($externalId, $options = ['deduct' => true])
    {
        $order = null;
        try {
            $apiInstance = new OrdersApi($this->client->getSpConfig());
            $order = $apiInstance->getOrder($externalId)->getPayload();

            if ($order) {
                // Retrieve order info
                sleep(10);
                $order['shipping_address'] = $this->getOrderAddress($order['amazon_order_id']);
                $order['order_items'] = $this->getOrderItems($order['amazon_order_id']);
                $order['buyer_info'] = $this->getOrderBuyerInfo($order['amazon_order_id']);

                $order = $this->transformOrder($order);
            } else {
                throw new \Exception('Unable to retrieve order for amazon');
            }
        } catch (\Exception $e) {
            set_log_extra('order', $order);
            set_log_extra('order_id', $externalId);
            throw $e;
        }

        return $this->handleOrder($order, $options);
    }

    /**
     * Import all orders
     *
     * @param array $options
     * @throws \Exception
     */
    public function import($options = ['deduct' => false])
    {
        // This is so it wont create new notifications
        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }

        // Get all the orders start from last year in ISO8601 format
        $createdAfter = date('Y-m-d\TH:i:s.Z\Z', strtotime('last year'));

        // TAKE NOTE, exclude cancelled status because it does not return order quantity
        $parameters = [
            'marketplace_ids' => [$this->account->credentials['marketplace_id']],
            'created_after' => $createdAfter,
            'statuses' => [self::STATUS_PENDING, self::STATUS_UNSHIPPED, self::STATUS_PARTIALLY_SHIPPED, self::STATUS_SHIPPED, self::STATUS_CANCELED]
        ];

        $this->fetchOrders($options, $parameters, 'amazon-import-orders-' . $this->account->id);
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

        // Get all the orders start from 1 day earlier in ISO8601 format
        $lastUpdatedAfter = new Carbon($this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now()));
        $lastUpdatedAfter = $lastUpdatedAfter->subDay(1)->format('Y-m-d\TH:i:s.Z\Z');

        // TAKE NOTE, exclude cancelled status because it does not return order quantity
        $parameters = [
            'marketplace_ids' => [$this->account->credentials['marketplace_id']],
            'last_updated_after' => $lastUpdatedAfter,
            'statuses' => [self::STATUS_PENDING, self::STATUS_UNSHIPPED, self::STATUS_PARTIALLY_SHIPPED, self::STATUS_SHIPPED, self::STATUS_CANCELED]
        ];

        $this->fetchOrders($options, $parameters, 'amazon-sync-orders-' . $this->account->id);

        $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
    }

    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $options
     * @param $parameters
     * @param null $lock
     * @throws \Exception
     */
    private function fetchOrders($options, $parameters, $lock)
    {
        $timeout = 1800;
        $lock = Cache::lock($lock, $timeout);

        if ($lock->get()) {
            try {
                $orders = $this->getOrders($parameters);
            } catch (\Exception $e) {
                $lock->forceRelease();
                set_log_extra('parameters', $parameters);
                throw $e;
            }

            foreach ($orders as $order) {
                try {
                    $order = $this->transformOrder($order);
                } catch (\Exception $e) {
                    $lock->forceRelease();
                    set_log_extra('order', $order);
                    throw $e;
                }
                $this->handleOrder($order, $options);
            }
            $lock->forceRelease();
        }
    }

    /**
     * Retrieve order listing
     *
     * @param $parameters
     * @return array
     * @throws \Exception
     */
    private function getOrders($parameters)
    {
        $createdAfter = date('Y-m-d\TH:i:s.Z\Z', strtotime('last year'));
        $lastUpdatedAfter = null;
        // Either filter by created or updated datetime only
        if (isset($parameters['created_after'])) {
            $createdAfter = $parameters['created_after'];
        } else if (isset($parameters['last_updated_after'])) {
            $lastUpdatedAfter = $parameters['last_updated_after'];
            $createdAfter = null;
        }
        $marketplaceIds = $parameters['marketplace_ids'];
        $statuses = ($parameters['statuses']) ?? [self::STATUS_UNSHIPPED, self::STATUS_PARTIALLY_SHIPPED, self::STATUS_SHIPPED];

        $apiInstance = new OrdersApi($this->client->getSpConfig());
        $response = $apiInstance->getOrders($marketplaceIds, $createdAfter, null, $lastUpdatedAfter, null, $statuses)->getPayload();

        $orders = [];
        if ($response->getOrders()) {
            $orders = $response->getOrders();

            while ($response->getNextToken()) {
                sleep(10);
                $response = $apiInstance->getOrders($marketplaceIds, null, null, null, null, null, null, null, null, null, null, null, $response->getNextToken())->getPayload();
                if ($response->getOrders()) {
                    $orders = array_merge($orders, $response->getOrders());
                }
            }
        }

        // Retrieve order items, address and buyer info
        foreach ($orders as $key => $value) {
            sleep(10);
            $orders[$key]['shipping_address'] = $this->getOrderAddress($value['amazon_order_id']);
            $orders[$key]['order_items'] = $this->getOrderItems($value['amazon_order_id']);
            $orders[$key]['buyer_info'] = $this->getOrderBuyerInfo($value['amazon_order_id']);
        }

        return $orders;
    }

    /**
     * Get the specific order's items
     *
     * @param $orderId
     * @return array|\ClouSale\AmazonSellingPartnerAPI\Models\Orders\OrderItemList
     * @throws \ClouSale\AmazonSellingPartnerAPI\ApiException
     */
    private function getOrderItems($orderId)
    {
        $apiInstance = new OrdersApi($this->client->getSpConfig());
        $response = $apiInstance->getOrderItems($orderId)->getPayload();

        $orderItems = [];
        if ($response->getOrderItems()) {
            $orderItems = $response->getOrderItems();

            while ($response->getNextToken()) {
                sleep(10);
                $response = $apiInstance->getOrderItems($orderId, $response->getNextToken())->getPayload();
                if ($response->getOrderItems()) {
                    $orderItems = array_merge($orderItems, $response->getOrderItems());
                }
            }
        }

        return $orderItems;
    }

    /**
     * Get the specific order's address
     *
     * @param $orderId
     * @return array|\ClouSale\AmazonSellingPartnerAPI\Models\Orders\Address
     * @throws \ClouSale\AmazonSellingPartnerAPI\ApiException
     */
    private function getOrderAddress($orderId)
    {
        $apiInstance = new OrdersApi($this->client->getSpConfig());
        $response = $apiInstance->getOrderAddress($orderId)->getPayload();

        if ($response->getShippingAddress()) {
            return $response->getShippingAddress();
        }
        return [];
    }

    /**
     * Get the specific order's address
     *
     * @param $orderId
     * @return \ClouSale\AmazonSellingPartnerAPI\Models\Orders\OrderBuyerInfo
     * @throws \ClouSale\AmazonSellingPartnerAPI\ApiException
     */
    private function getOrderBuyerInfo($orderId)
    {
        $apiInstance = new OrdersApi($this->client->getSpConfig());
        $response = $apiInstance->getOrderBuyerInfo($orderId)->getPayload();

        return $response;
    }

    /**
     * Order Transform
     *
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order['amazon_order_id'];
        $externalNumber = null;
        $externalSource = $this->account->integration->name;

        $customerName = $order['buyer_info']['buyer_name'] ?? null;
        $customerEmail = $order['buyer_info']['buyer_email'] ?? null;

        // Shipping and Billing will be using the same
        $shipping['Name'] = $billing['Name'] = $order['shipping_address']['name'] ?? null;
        $shipping['AddressLine1'] = $billing['AddressLine1'] = $order['shipping_address']['address_line1'] ?? null;
        $shipping['City'] = $billing['City'] = $order['shipping_address']['city'] ?? null;
        $shipping['PostalCode'] = $billing['PostalCode'] = $order['shipping_address']['postal_code'] ?? null;
        $shipping['StateOrRegion'] = $billing['StateOrRegion'] = $order['shipping_address']['state_or_region'] ?? null;
        $shipping['CountryCode'] = $billing['CountryCode'] = $order['shipping_address']['country_code'] ?? null;

        // To remove any if it's empty
        $shippingAddress = new TransformedAddress(null, $shipping['Name'] ?? null,
            $shipping['AddressLine1'] ?? null, null, null, null, null,
            $shipping['City'], $shipping['PostalCode'], $shipping['StateOrRegion'], $shipping['CountryCode'], null
        );
        $billingAddress = new TransformedAddress(null, $billing['Name'] ?? null,
            $billing['AddressLine1'] ?? null, null, null, null, null,
            $billing['City'], $billing['PostalCode'], $billing['StateOrRegion'], $billing['CountryCode'], null
        );

        if (isset($order['latest_ship_date'])) {
            $shipByDate = Carbon::make($order['latest_ship_date'])->format('Y-m-d H:i:s');
        } else {
            $shipByDate = null;
        }

        $paymentStatus = PaymentStatus::PAID();
        // If order status is pending, then it will be unpaid status
        if ($order['order_status'] == self::STATUS_PENDING) {
            $paymentStatus = PaymentStatus::UNPAID();
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        //$sellerDiscount = 0;
        //$shippingFee = filter_var($order['estimated_shipping_fee'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        //$tax = 0;
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $settlementAmount = 0;

        $paymentMethod = $order['payment_method'] ?? null;

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if ($order['order_status'] == self::STATUS_PENDING) {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } elseif ($order['order_status'] == self::STATUS_SHIPPED || $order['order_status'] == self::STATUS_PARTIALLY_SHIPPED) {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif ($order['order_status'] == self::STATUS_CANCELED) {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
            $paymentStatus = PaymentStatus::CANCELLED();
        } elseif ($order['order_status'] == self::STATUS_UNSHIPPED) {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
        } else {
            set_log_extra('order_status', $order['order_status']);
            set_log_extra('order', $order);
            throw new \Exception('Amazon has different fulfilment status');
        }

        // If order is cancelled, replace back the original paid
        /*if () {

        } else {

        }*/
        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $order['order_total']['amount'] ?? 0 : 0;

        // This is customer/buyer remark or message
        $buyerRemarks = null;

        if (isset($order['shipment_service_level_category'])) {
            $buyerRemarks .= ' -- Shipping Service: ' . $order['shipment_service_level_category'];
        }
        if (isset($order['sales_channel'])) {
            $buyerRemarks .= ' -- Sales Channel: ' . $order['sales_channel'];
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::make($order['purchase_date'])->format('Y-m-d H:i:s');
        $orderUpdatedAt = Carbon::make($order['last_update_date'])->format('Y-m-d H:i:s');

        $orderPaidAt = null;

        $data = [
            'marketplace_id' => $order['marketplace_id'],
            'fulfillment_channel' => $order['fulfillment_channel'],
        ];

        $items = [];
        $grandTotal = 0;
        $sellerDiscount = 0;
        $shippingFee = 0;
        $tax = 0;
        foreach ($order['order_items'] as $item) {

            $itemExternalId = $item['order_item_id'];
            $itemName = $item['title'];
            $externalProductId = $item['order_item_id'];
            $sku = $item['seller_sku'];
            $variationName = $item['title'] ?? 'N/A';
            $variationSku = $item['seller_sku'];

            $quantity = $item['quantity_ordered'];

            $itemPrice = $item['item_price']['amount'] ?? 0;

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = filter_var($item['promotion_discount']['amount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            $itemShippingFee = $item['shipping_price']['amount'] ?? 0;
            $shippingFee += $itemShippingFee;

            $itemTax = $item['item_tax']['amount'] ?? 0;
            $itemTax2 = 0;

            $itemPaidPrice = $itemPrice;

            $itemGrandTotal = $quantity * $itemPaidPrice;
            $itemBuyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $itemGrandTotal : 0;

            $grandTotal += $itemGrandTotal;

            // Amazon does not have item status
            $itemFulfillmentStatus = $fulfillmentStatus;

            $shipmentProvider = $order['ship_service_level'];
            $trackingNumber = null;
            $shipmentType = null;
            $shipmentMethod = null;

            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = $shippingFee;

            $itemData = [
                'condition_id' => $item['condition_id'],
                'asin' => $item['asin'] ?? '',
            ];

            $items[] = new TransformedOrderItem($itemExternalId, $externalProductId, $itemName, $sku, $variationName, $variationSku, $quantity,
                $itemPrice, $itemIntegrationDiscount, $itemSellerDiscount, $itemShippingFee, $itemTax, $itemTax2, $itemGrandTotal, $itemBuyerPaid,
                $itemFulfillmentStatus, $shipmentProvider, $shipmentType, $shipmentMethod, $trackingNumber, $returnStatus, $costOfGoods, $actualShippingFee,
                $itemData);
        }

        // TAKE NOTE - Temporary grandTotal as 0.01 for cancelled order, because payment status which is not paid must greater than 0
        //$grandTotal = $paymentStatus->equals(PaymentStatus::CANCELLED()) ? 0.01 : $grandTotal;

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

        if ($order->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue()) {
            $statusSpecific[] = 'cancelReasons';
            $statusSpecific[] = 'cancel';
            $statusSpecific[] = 'fulfillment';
        } else if ($order->fulfillment_status === FulfillmentStatus::PENDING()->getValue()) {
            $statusSpecific[] = 'cancelReasons';
            $statusSpecific[] = 'cancel';
        } else if ($order->fulfillment_status === FulfillmentStatus::PARTIALLY_SHIPPED()->getValue()) {
            $statusSpecific[] = 'refundReasons';
            $statusSpecific[] = 'refund';
        } else if ($order->fulfillment_status === FulfillmentStatus::SHIPPED()->getValue()) {
            $statusSpecific[] = 'refundReasons';
            $statusSpecific[] = 'refund';
        }

        return array_merge($general, $statusSpecific);
    }

    /**
     * Retrieves all the cancellation reasons for the order
     *
     * @return mixed
     * @throws \Exception
     */
    public function cancelReasons()
    {
        $reasons = [
            ['value' => 'NoInventory', 'text' => 'No Inventory'],
            ['value' => 'ShippingAddressUndeliverable', 'text' => 'Shipping Address Undeliverable'],
            ['value' => 'CustomerExchange', 'text' => 'Customer Exchange'],
            ['value' => 'BuyerCanceled', 'text' => 'Buyer Canceled'],
            ['value' => 'GeneralAdjustment', 'text' => 'General Adjustment'],
            ['value' => 'CarrierCreditDecision', 'text' => 'Carrier Credit Decision'],
            ['value' => 'RiskAssessmentInformationNotValid', 'text' => 'Risk Assessment Information Not Valid'],
            ['value' => 'CarrierCoverageFailure', 'text' => 'Carrier Coverage Failure'],
            ['value' => 'CustomerReturn', 'text' => 'Customer Return'],
            ['value' => 'MerchandiseNotReceived', 'text' => 'Merchandise Not Received'],
            ['value' => 'CannotVerifyInformation', 'text' => 'Cannot Verify Information'],
            ['value' => 'PricingError', 'text' => 'Pricing Error'],
            ['value' => 'RejectOrder', 'text' => 'Reject Order'],
            ['value' => 'WeatherDelay', 'text' => 'Weather Delay'],
        ];

        return $reasons;
    }

    /**
     * Cancel the entire order
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function cancel(Order $order, Request $request)
    {
        //$this->get($order->external_id, ['deduct' => false]);
        // Cancel reason is required
        if (!$cancelReason = $request->input('cancel_reason')) {
            return $this->respondBadRequestError('You need to specify the reason.');
        }

        $items = [];
        foreach ($order->items as $key => $value) {
            $items[] = [
                //'amazonOrderItemCode' => $value->external_id
                'merchant_fulfillment_item_id' => $value->external_id,
            ];
        }

        $response = $this->OrderAcknowledgement([
            $order->external_id => [
                "status_code" => "Failure",
                "items" => $items,
                "cancel_reason" => $cancelReason,
                'merchant_order_id' => $order->external_id
            ]
        ]);

        /*$max = 3;
        for ($current = 1;$current <= $max; $current++) {
            // Wait a couple of seconds and get it's content
            sleep(50);
            // Get report
            $feed = $this->client->getFeed($response->getPayload()->getFeedId())->getPayload();
            if ($feed->getProcessingStatus() === 'DONE') {
                break;
            }
        }

        $document = $this->client->getFeedDocument($feed->getResultFeedDocumentId())->getPayload();

        $key = base64_decode($document->getEncryptionDetails()->getKey());
        $iv = base64_decode($document->getEncryptionDetails()->getInitializationVector());
        $data = openssl_decrypt(file_get_contents($document->getUrl()), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        dd($data);*/

        return true;
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
        // Validation
        $validator = Validator::make($request->all(), [
            'carrier_code' => 'required',
            'carrier_name' => 'required_if:carrier_code,Other',
            'shipping_method' => 'required',
            'shipping_date' => 'required',
        ]);
        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        // Carrier Type
        $carrierType = "carrier_code";
        $carrier = $request->get('carrier_code');
        // Only when carrier code is others
        if ($carrier === 'Other' && !is_null($request->get('carrier_name'))) {
            $carrierType = 'carrier_name';
            $carrier = $request->get('carrier_name');
        }

        $items = [];
        foreach ($order->items as $key => $value) {
            $items[] = [
                'amazon_order_item_code' => $value->external_id,
                //'merchantFullfillmentItemId' => $value->external_id,
                'quantity' => $value->quantity
            ];
        }

        $parameter = [
            $order->external_id => [
                $carrierType => $carrier,
                "items" => $items,
                //"merchantFulfillmentId" => $input['fulfillment_id'],
                "shipping_date" => Carbon::createFromFormat('Y-m-d', $request->get('shipping_date')),
                "shipping_method" => $request->get('shipping_method'), // required
            ]
        ];

        // Optional tracking number
        if ($request->get('tracking_number')) {
            $parameter[$order->external_id]["tracking_code"] = $request->get('tracking_number');
        }

        $response = $this->setDeliveryStatus($parameter);

        if (empty($response)) {
            set_log_extra('response', $response);
            return $this->respondBadRequestError('Failed to confirm shipping');
        }

        return true;
    }

    /**
     * Retrieves all the refund reasons for the order
     *
     * @return mixed
     * @throws \Exception
     */
    public function refundReasons()
    {
        $reasons = [
            ['value' => 'NoInventory', 'text' => 'No Inventory'],
            ['value' => 'CustomerReturn', 'text' => 'Customer Return'],
            ['value' => 'GeneralAdjustment', 'text' => 'General Adjustment'],
            ['value' => 'CouldNotShip', 'text' => 'Could Not Ship'],
            ['value' => 'DifferentItem', 'text' => 'Different Item'],
            ['value' => 'Abandoned', 'text' => 'Abandoned'],
            ['value' => 'CustomerCancel', 'text' => 'Customer Cancel'],
            ['value' => 'PriceError', 'text' => 'Price Error'],
            ['value' => 'ProductOutofStock', 'text' => 'Product Out of Stock'],
            ['value' => 'CustomerAddressIncorrect', 'text' => 'Customer Address Incorrect'],
            ['value' => 'Exchange', 'text' => 'Exchange'],
            ['value' => 'Other', 'text' => 'Other'],
            ['value' => 'CarrierCreditDecision', 'text' => 'Carrier Credit Decision'],
            ['value' => 'RiskAssessmentInformationNotValid', 'text' => 'Risk Assessment Information Not Valid'],
            ['value' => 'CarrierCoverageFailure', 'text' => 'Carrier Coverage Failure'],
            ['value' => 'TransactionRecord', 'text' => 'Transaction Record'],
            ['value' => 'Undeliverable', 'text' => 'Undeliverable'],
            ['value' => 'RefusedDelivery', 'text' => 'Refused Delivery'],
        ];

        return $reasons;
    }

    /**
     * Order refund action
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function refund(Order $order, Request $request)
    {
        // Refund reason is required
        if (!$refundReason = $request->input('refund_reason')) {
            return $this->respondBadRequestError('You need to specify the reason.');
        }
        // At least one order item for adjustment
        if (count($request->input('adjustment_items')) <= 0) {
            return $this->respondBadRequestError('There is no adjustment item.');
        }

        $items = [];
        foreach ($request->input('adjustment_items') as $key => $value) {
            $emptyRefundAmount = true;
            $items[] = [
                //'amazonOrderItemCode' => $value->external_id
                'amazon_order_item_id' => $value['external_id'],
                'adjustment_reason' => $refundReason,
                'price_adjustments' => $value['price_adjustments']
            ];

            foreach ($value['price_adjustments'] as $priceAdjustment) {
                if ($priceAdjustment['amount'] > 0) {
                    $emptyRefundAmount = false;
                }
                if ($priceAdjustment['amount'] > $order['grand_total']) {
                    return $this->respondBadRequestError('Refund must be less than product price');
                }
            }

            if ($emptyRefundAmount) {
                return $this->respondBadRequestError('Every adjustment item total refund amount must bigger than 0');
            }
        }

        $response = $this->orderAdjustment([
            $order->external_id => [
                "adjustment_items" => $items,
            ]
        ]);

        if (empty($response)) {
            set_log_extra('response', $response);
            return $this->respondBadRequestError('Failed to refund order Amazon');
        }

        // CSM-618 P2 Amazon - issue with order refund
        $order->payment_status = PaymentStatus::REFUNDED()->getValue();
        $order->fulfillment_status = FulfillmentStatus::RETURNED()->getValue();
        $order->save();

        return true;

    }

    /**
     * Order Adjustment Feed
     * Reference: https://stackoverflow.com/questions/22705910/i-am-having-some-trouble-submitting-an-order-adjustment-to-amazon-via-the-amazon
     * Reference: https://stackoverflow.com/questions/31560243/amazon-mws-issue-with-orderadjustment-feed-partial-cancellation
     * Reference: https://sellercentral.amazon.com/forums/t/error-refund-xml-feed/435786/1
     *
     * @param array $orders
     * @return array
     * @throws \Exception
     */
    private function orderAdjustment(array $orders)
    {
        if (count($orders) > 0) {
            $feedType = 'POST_PAYMENT_ADJUSTMENT_DATA';

            $feed = [
                'MessageType' => 'OrderAdjustment',
                'Message' => []
            ];

            foreach ($orders as $orderId => $data) {
                $feed['Message'][] = $this->createOrderAdjustment($orderId, $data);
            }

            return $this->client->submitFeed($feedType, $feed);
        }

        return [];
    }

    /**
     * Create order adjustment data
     *
     * @param string $orderId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function createOrderAdjustment(string $orderId, array $data)
    {
        $adjustmentMessage = [
            'MessageID' => rand(),
            'OrderAdjustment' => [
                'AmazonOrderID' => $orderId,
                'ActionType' => 'Refund',
                'AdjustedItem' => []
            ]
        ];

        // Adjustment items is required
        if (!isset($data['adjustment_items']) || empty($data['adjustment_items'])) {
            throw new \Exception('Missing required adjustment items data');
        }

        $adjustmentItems = [];
        foreach ($data['adjustment_items'] as $adjustmentItem) {
            $temp = [
                'AmazonOrderItemCode' => $adjustmentItem['amazon_order_item_id'],
                //'MerchantOrderItemID' => $item['merchantOrderItemId'] ?? null,
                'AdjustmentReason' => $adjustmentItem['adjustment_reason'] ?? 'GeneralAdjustment',
                'ItemPriceAdjustments' => [
                    'Component' => [],
                ],
            ];

            // Supported Type - Principal, Shipping, Tax, Shipping Tax
            foreach ($adjustmentItem['price_adjustments'] as $priceAdjustment) {
                if ($priceAdjustment['amount'] > 0) {
                    $temp['ItemPriceAdjustments']['Component'][] = [
                        'Type' => $priceAdjustment['type'],
                        'Amount' => $priceAdjustment['amount']
                    ];
                }
            }

            // Must have at least one price adjustment type
            if (count($temp['ItemPriceAdjustments']['Component']) <= 0) {
                throw new \Exception('Each item must have at least one price to refund');
            }

            $adjustmentItems[] = $temp;
        }

        $adjustmentMessage['OrderAdjustment']['AdjustedItem'] = $adjustmentItems;

        return $adjustmentMessage;
    }

    /**
     * Order acknowledgement
     *
     * @param array $orders
     * @return mixed
     * @throws \Exception
     */
    private function orderAcknowledgement(array $orders)
    {
        $feedType = 'POST_ORDER_ACKNOWLEDGEMENT_DATA';

        $feed = [
            'MessageType' => 'OrderAcknowledgement',
            'Message' => []
        ];

        foreach ($orders as $orderId => $data) {
            $feed['Message'][] = $this->createOrderAcknowledgement($orderId, $data);
        }

        return $this->client->submitFeed($feedType, $feed);
    }

    /**
     * Create order acknowledgement data message
     *
     * @param string $orderId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function createOrderAcknowledgement(string $orderId, array $data)
    {
        if (!isset($data['status_code']) || empty($data['status_code'])) {
            set_log_extra('data', $data);
            throw new \Exception('Missing required status code data');
        }

        $fulfillmentMessage = [
            'MessageID' => rand(),
            'OrderAcknowledgement' => [
                'AmazonOrderID' => $orderId,
                'MerchantOrderID' => $data['merchant_order_id'] ?? null,
                'StatusCode' => $data['status_code'],
                'Item' => []
            ]
        ];

        if (!empty($data['items'])) {
            $fulfillmentItems = [];
            foreach ($data['items'] as $item) {
                $temp = [
                    'AmazonOrderItemCode' => $item['merchant_fulfillment_item_id'],
                    'MerchantOrderItemID' => $item['merchant_order_item_id'] ?? null,
                ];

                if ($data['status_code'] == 'Failure') {
                    $temp['CancelReason'] = $item['cancel_reason'] ?? $data['cancel_reason'] ?? 'GeneralAdjustment';
                }

                $fulfillmentItems[] = $temp;
            }

            $fulfillmentMessage['OrderAcknowledgement']['Item'] = $fulfillmentItems;
        }

        return $fulfillmentMessage;
    }

    /**
     * Set order delivery status
     *
     * @param array $orders
     * @return array
     * @throws \Exception
     */
    private function setDeliveryStatus(array $orders)
    {
        if (count($orders) > 0) {
            $feedType = 'POST_ORDER_FULFILLMENT_DATA';

            $feed = [
                'MessageType' => 'OrderFulfillment',
                'Message' => []
            ];

            foreach ($orders as $orderId => $data) {
                $feed['Message'][] = $this->createPostOrderFulfillment($orderId, $data);
            }

            return $this->client->submitFeed($feedType, $feed);
        }

        return [];
    }

    /**
     * Create order fulfillment data message
     *
     * @param string $orderId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function createPostOrderFulfillment(string $orderId, array $data)
    {
        if ((!isset($data['carrier_code']) || empty($data['carrier_code'])) && (!isset($data['carrier_name']) || empty($data['carrier_name']))) {
            throw new \Exception('Missing required carrier data');
        }

        if (!isset($data['shipping_method'])) {
            throw new \Exception('Missing required shipping method data');
        }

        if (!isset($data['shipping_date'])) {
            $data['shipping_date'] = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z");
        } else {
            if ($data['shipping_date'] instanceof \DateTimeInterface) {
                $data['shipping_date'] = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", $data['shipping_date']->getTimestamp());
            } else {
                throw new \Exception('Invalid shipping date format');
            }
        }

        $fulfillmentMessage = [
            'MessageID' => rand(),
            'OrderFulfillment' => [
                'AmazonOrderID' => $orderId,
                // 'MerchantOrderID' => $orderId,
                //'MerchantFulfillmentID' => $data['merchantFulfillmentId'],
                'FulfillmentDate' => $data['shipping_date']
            ]
        ];

        $fulfillmentData = [];

        if (!empty($data['carrier_code'])) {
            $fulfillmentData['CarrierCode'] = $data['carrier_code'];
        } elseif (!empty($data['carrier_name'])) {
            $fulfillmentData['CarrierName'] = $data['carrier_name'];
        }

        $fulfillmentData['ShippingMethod'] = $data['shipping_method'];

        if (!empty($data['tracking_code'])) {
            $fulfillmentData['ShipperTrackingNumber'] = $data['tracking_code'];
        }

        $fulfillmentMessage['OrderFulfillment']['FulfillmentData'] = $fulfillmentData;

        if (!empty($data['items'])) {
            $fulfillmentMessage['OrderFulfillment']['Item'] = [];
            foreach ($data['items'] as $item) {
                $fulfillmentMessage['OrderFulfillment']['Item'][] = [
                    'AmazonOrderItemCode' => $item['amazon_order_item_code'],
                    // 'MerchantOrderItemID' => $item['amazonOrderItemCode'],
                    // 'MerchantFulfillmentItemID' => $item['merchantFullfillmentItemId'],
                    'Quantity' => $item['quantity'],
                    // 'Transparencycode' => ''
                ];
            }
        }

        return $fulfillmentMessage;
    }

    /*public function slip(Order $order)
    {dd($response = $this->client->getClient()->GetFeedSubmissionResult('50382018331'));
        $items = [];
        foreach ($order->items as $key => $value) {
            $items[] = [
                //'amazonOrderItemCode' => $value->external_id
                'merchantFullfillmentItemId' => $value->external_id
            ];
        }

        $response = $this->client->getClient()->OrderAcknowledgement([
            $order->external_id => [
                "statusCode" => "Success",
                "items" => $items,
                //"CancelReason" => $cancelReason
            ]
        ]);

        dd($response);
    }*/
}
