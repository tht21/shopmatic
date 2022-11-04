<?php

namespace App\Integrations\Redmart;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\IntegrationSyncData;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;

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
        $response = $this->client->request('get', 'order/'.$externalId);

        if ($response->getStatusCode() === 200) {
            $order = json_decode($response->getBody()->getContents(), true);
            try {
                $order = $this->transformOrder($order);
            } catch (\Exception $e) {
                set_log_extra('order', $order);
                throw $e;
            }
            return $this->handleOrder($order, $options);
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve order for Redmart');
        }
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

        // Get all the orders start from last year
        $begin = new Carbon('last year');
        $end = new Carbon('+6 hours');

        $periods = $this->getPeriodDates($begin, $end);

        $updateTimeFrom = null;
        foreach ($periods as $period) {
            if (!is_null($updateTimeFrom)) {
                $updateTimeTo = $period->setTime(0,0);

                $parameters = [
                    'query' => [
                        'isFilterByDeliveryDate' => false,
                        'page' => 1,
                        'pageSize' => 50,
                        'from' => $updateTimeFrom->timestamp.'000', // in milliseconds

                        // Adding a "till" to ensure new orders don't come in during this period
                        // The API called on web is 6 hours ahead for some reason
                        'till' => $updateTimeTo->timestamp. '999' // in milliseconds
                    ],
                ];
                $this->fetchOrders($options, $parameters);

                $updateTimeFrom = $updateTimeTo; // Replace it
            } else {
                $updateTimeFrom = $period->setTime(0,0);
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

        $begin = $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now(), true);
        $end = new Carbon('+6 hours');

        $periods = $this->getPeriodDates($begin, $end);

        if ($periods->count() > 1) {
            $periods = $periods->toArray();
            $updateTimeFrom = $periods[0]->setTime(0,0);

            foreach ($periods as $period) {
                $updateTimeTo = $period;
                $parameters = [
                    'query' => [
                        'isFilterByDeliveryDate' => false,
                        'page' => 1,
                        'pageSize' => 50,
                        'from' => $updateTimeFrom->timestamp.'000', // in milliseconds

                        // Adding a "till" to ensure new orders don't come in during this period
                        // The API called on web is 6 hours ahead for some reason
                        'till' => $updateTimeTo->timestamp. '999' // in milliseconds
                    ],
                ];
                $this->fetchOrders($options, $parameters);

                $updateTimeFrom = $updateTimeTo; // Replace it
            }
        } else {
            $updateTimeFrom = $periods->getStartDate()->setTime(0,0);
            $updateTimeTo = $periods->getEndDate();

            $parameters = [
                'query' => [
                    'isFilterByDeliveryDate' => false,
                    'page' => 1,
                    'pageSize' => 50,
                    'from' => $updateTimeFrom->timestamp.'000', // in milliseconds

                    // Adding a "till" to ensure new orders don't come in during this period
                    // The API called on web is 6 hours ahead for some reason
                    'till' => $updateTimeTo->timestamp. '999' // in milliseconds
                ],
            ];
            $this->fetchOrders($options, $parameters);
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
    public function getPeriodDates($begin, $end, $interval = '30 day')
    {
        $interval = CarbonInterval::createFromDateString($interval);
        return new CarbonPeriod($begin, $interval, $end);
    }

    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $options
     * @param $parameters
     * @return bool
     * @throws \Exception
     */
    private function fetchOrders($options, $parameters)
    {
        $totalOrders = 0;
        $currentTotal = 0;
        $orders = [];

        do {
            $response = $this->client->request('get', 'shipments', $parameters);

            if ($response->getStatusCode() === 200) {
                $orderLists = json_decode($response->getBody()->getContents(), true);

                if ($totalOrders === 0) {
                    $totalOrders = $orderLists['total'];
                }

                // Get order details
                foreach ($orderLists['items'] as $orderList) {
                    $response = $this->client->request('get', 'order/'.$orderList['orderNumber']);

                    $orderDetail = json_decode($response->getBody()->getContents(), true);
                    $orderDetail['shipmentNumber'] = $orderList['shipmentNumber'];
                    $orderDetail['deliveryStatus'] = $orderList['deliveryStatus'];
                    $orderDetail['orderDate'] = $orderList['orderDate'];

                    $orders[] = $orderDetail;
                }

                $currentTotal += count($orderLists['items']);
                $parameters['query']['page'] += 1;
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to retrieve orders for Redmart');
            }
        } while ($currentTotal < $totalOrders);

        foreach ($orders as $order) {
            // skip failed order
            if (!empty($order) && $order['deliveryStatus'] != 'failed') {

                try {
                    $order = $this->transformOrder($order);
                } catch (\Exception $e) {
                    set_log_extra('order', $order);
                    throw $e;
                }
                $this->handleOrder($order, $options);
            }
        }

        // Get pickups
        $pickupParameters = [
            'query' => [
                'from' => $parameters['query']['from'],
                'till' => $parameters['query']['till']
            ],
            'header' => [
                'Referer' => $this->client->getUrl() . 'order/pickup/search'
            ]
        ];

        $response = $this->client->request('GET', 'job', $pickupParameters);
        $data = json_decode($response->getBody()->__toString(), true);

        if ($response->getStatusCode() === 200) {
            foreach ($data as $pickup) {
                $externalIds = [];
                $products = [];

                foreach ($pickup['items'] as $item) {
                    $orderIds = [];
                    foreach ($item['shipmentsInfo'] as $info) {
                        $orderIds[] = $info['orderId'];
                        $externalIds[] = $info['orderId'];
                    }
                    $products[] = $item + ['order_external_ids' => $orderIds];
                }

                $externalIds = array_unique($externalIds);
                $orders = $this->account->orders()->whereIn('external_id', $externalIds)->get();

                // If the redmart pickup count is different with order count
                if ($orders->count() != count($externalIds)) {
                    $existingExternalId = $orders->pluck('external_id')->toArray();
                    $deliveryStatus = $pickup['status'] === 'pickedup' ? 'delivered' : 'pending';

                    foreach ($externalIds as $externalId) {
                        // Retrieve the specified missing order
                        if (!in_array($externalId, $existingExternalId)) {
                            $orderResponse = $this->client->request('GET', 'order/' . $externalId);
                            $item = json_decode($orderResponse->getBody()->getContents(), true);
                            $item = $item + [
                                    'deliveryStatus' => $deliveryStatus,
                                    'orderDate' => $item['creationDate'],
                                ];

                            // Insert the specify order
                            if (!empty($item)) {
                                try {
                                    $order = $this->transformOrder($item);
                                } catch (\Exception $e) {
                                    set_log_extra('order', $item);
                                    throw $e;
                                }
                                $this->handleOrder($order, $options);
                            }
                        }
                    }

                    $orders = $this->account->orders()->whereIn('external_id', $externalIds)->get();
                }
                // If still incorrect order count, throw error
                if ($orders->count() != count($externalIds)) {
                    throw new \Exception('Order count for redmart pickups does not match.');
                }

                //$pickup['balance'] = $orders->sum('grand_total');
                $pickup['products'] = [];
                $grandTotal = 0;
                foreach ($products as $product) {
                    // Calculate the item price + total price here based on the orders
                    $productOrders = $this->account->orders()->whereIn('external_id', $product['order_external_ids'])->get(['id'])->pluck('id')->toArray();

                    $orderItems = OrderItem::whereIn('order_id', $productOrders)->where('sku', $product['sku'])->get(['id', 'item_price', 'grand_total', 'quantity']);

                    $totalPrice = 0;
                    $totalQuantity = 0;
                    foreach ($orderItems as $orderItem) {
                        $totalPrice += $orderItem->grand_total;
                        $totalQuantity += $orderItem->quantity;

                        if ($pickup['status'] === 'pickedup') {
                            // Update the order fulfillment status to shipped
                            $orderItem->fulfillment_status = 11;
                            $orderItem->save();
                        }
                    }

                    $unitPrice = $totalQuantity == 0 ? 0 :$totalPrice / $totalQuantity;

                    $product['price'] = $unitPrice;
                    $product['balance'] = $totalPrice;
                    $grandTotal += $product['balance'];

                    unset($product['order_external_ids']);
                    $pickup['products'][] = $product;
                }
                $pickup['balance'] = $grandTotal;

                if (!empty($pickup)) {
                    try {
                        $pickup = $this->transformPickup($pickup);
                    } catch (\Exception $e) {
                        set_log_extra('pickup', $pickup);
                        throw $e;
                    }
                    $this->handleOrder($pickup, $options);
                }
            }
        } else if (!isset($data['code']) || $data['code'] != 'jobsnotfound'){ // if there is no pickup jobs found, the api will return status code 404 actually which is not an error
            set_log_extra('response', $response);
            set_log_extra('body', $data);
            throw new \Exception('Unable to retrieve orders for Redmart');
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order['id'];
        $externalNumber = $order['orderNumber'];
        $externalSource = $this->account->integration->name;

        // Redmart does not provides customer name and email
        $customerName = null;
        $customerEmail = null;

        $shippingAddress = $billingAddress = null;

        $shipByDate = Carbon::createFromTimestampMs($order['deliveryDate']);

        $paymentStatus = PaymentStatus::PAID();
        if ($order['deliveryStatus'] == 'cancelled') {
            $paymentStatus = PaymentStatus::CANCELLED();
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        $sellerDiscount = 0;
        $shippingFee = 0;
        $tax = 0;
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $grandTotal = $order['balance'];

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $grandTotal : 0;

        $settlementAmount = 0;

        $paymentMethod = null;

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if ($order['deliveryStatus'] == 'pending') {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } else if ($order['deliveryStatus'] == 'delivered') {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
        } else if ($order['deliveryStatus'] == 'undelivered') {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
        } else if ($order['deliveryStatus'] == 'partially-delivered') {
            $fulfillmentStatus = FulfillmentStatus::PARTIALLY_SHIPPED();
        } elseif ($order['deliveryStatus'] == 'cancelled') {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
        } else {
            set_log_extra('statuses', $order['deliveryStatus']);
            set_log_extra('order', $order);
            throw new \Exception('Redmart has different fulfilment status');
        }

        $buyerRemarks = null;

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::createFromTimestampMs($order['creationDate']);
        $orderUpdatedAt = Carbon::createFromTimestampMs($order['creationDate']);

        if ($paymentStatus->equals(PaymentStatus::PAID())) {
            // We can't check this reliably, so we just use the created timestamp
            $orderPaidAt = Carbon::createFromTimestampMs($order['orderDate']);
        } else {
            $orderPaidAt = null;
        }

        // @NOTE - pick important data, instead of putting everything
        $data = $order;

        $items = [];
        foreach ($order['products'] as $item) {

            $itemExternalId = $item['sku'];
            $itemName = $item['name'];
            $externalProductId = $item['rpc'];
            $sku = $item['sku'];
            $variationName = $item['name'];
            $variationSku = $sku;
            $quantity = $item['qty'];

            $itemPrice = $item['price'];

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = 0;

            $itemShippingFee = 0;
            $itemTax = 0;
            $itemTax2 = 0;

            $itemGrandTotal = $item['balance'];
            $itemBuyerPaid = $itemGrandTotal;

            $shipmentProvider = null;
            $shipmentType = FulfillmentType::REQUIRES_SHIPPING();
            $shipmentMethod = null;
            $trackingNumber = $order['shipmentNumber'] ?? null;

            if ($order['deliveryStatus'] == 'pending') {
                $itemFulfillmentStatus = FulfillmentStatus::PENDING();
            } else if ($order['deliveryStatus'] == 'delivered') {
                $itemFulfillmentStatus = FulfillmentStatus::DELIVERED();
            } else if ($order['deliveryStatus'] == 'undelivered') {
                $itemFulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
            } else if ($order['deliveryStatus'] == 'partially-delivered') {
                $itemFulfillmentStatus = FulfillmentStatus::PARTIALLY_SHIPPED();
            } elseif ($order['deliveryStatus'] == 'cancelled') {
                $itemFulfillmentStatus = FulfillmentStatus::CANCELLED();
            } else {
                set_log_extra('status', $order['deliveryStatus']);
                set_log_extra('order', $order);
                set_log_extra('item', $item);
                throw new \Exception('Redmart has unsupported item status');
            }

            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = 0;

            $itemData = $item;

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
     * @throws \Exception
     */
    public function transformPickup($order)
    {
        $externalId = $order['id'];
        $externalNumber = null;
        $externalSource = $this->account->integration->name;

        // Redmart does not provides customer name and email
        $customerName = null;
        $customerEmail = null;

        $shippingAddress = $billingAddress = null;

        $shipByDate = Carbon::createFromTimestampMs($order['scheduledAt']);

        $paymentStatus = PaymentStatus::PAID();
        if ($order['status'] == 'cancelled') {
            $paymentStatus = PaymentStatus::CANCELLED();
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        $sellerDiscount = 0;
        $shippingFee = 0;
        $tax = 0;
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $grandTotal = $order['balance'];

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $grandTotal : 0;

        $settlementAmount = 0;

        $paymentMethod = null;

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if ($order['status'] == 'pending') {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } else if ($order['status'] == 'pickedup') {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif ($order['status'] == 'cancelled') {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
        } else {
            set_log_extra('statuses', $order['status']);
            set_log_extra('order', $order);
            throw new \Exception('Redmart pickup has different fulfilment status');
        }

        $buyerRemarks = null;

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::createFromTimestampMs($order['scheduledAt']);
        $orderUpdatedAt = Carbon::createFromTimestampMs($order['scheduledAt']);

        if ($paymentStatus->equals(PaymentStatus::PAID())) {
            // We can't check this reliably, so we just use the created timestamp
            $orderPaidAt = Carbon::createFromTimestampMs($order['scheduledAt']);
        } else {
            $orderPaidAt = null;
        }

        // @NOTE - pick important data, instead of putting everything
        $data = $order;

        $items = [];
        foreach ($order['products'] as $item) {

            $itemExternalId = $item['sku'];
            $itemName = $item['name'];
            $externalProductId = $item['rpc'];
            $sku = $item['sku'];
            $variationName = $item['name'];
            $variationSku = $sku;
            $quantity = $item['qty'];

            $itemPrice = $item['price'];

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = 0;

            $itemShippingFee = 0;
            $itemTax = 0;
            $itemTax2 = 0;

            $itemGrandTotal = $item['balance'];
            $itemBuyerPaid = $itemGrandTotal;

            $shipmentProvider = null;
            $shipmentType = FulfillmentType::REQUIRES_SHIPPING();
            $shipmentMethod = null;
            $trackingNumber = null;

            if ($order['status'] == 'pending') {
                $itemFulfillmentStatus = FulfillmentStatus::PENDING();
            } else if ($order['status'] == 'pickedup') {
                $itemFulfillmentStatus = FulfillmentStatus::SHIPPED();
            } elseif ($order['status'] == 'cancelled') {
                $itemFulfillmentStatus = FulfillmentStatus::CANCELLED();
            } else {
                set_log_extra('status', $order['status']);
                set_log_extra('order', $order);
                set_log_extra('item', $item);
                throw new \Exception('Redmart pickup has unsupported item status');
            }

            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = 0;

            $itemData = $item;

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
        /*foreach ($order->items as $item) {
            if ($item->fulfillment_status === FulfillmentStatus::PENDING()->getValue()) {
                if (!in_array('cancel', $statusSpecific)) {
                    $statusSpecific[] = 'cancel';
                }
                if (!in_array('fulfillment', $statusSpecific)) {
                    $statusSpecific[] = 'fulfillment';
                }
            } elseif ($item->fulfillment_status > FulfillmentStatus::PENDING()->getValue() && $item->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
                if (!in_array('print', $statusSpecific)) {
                    $statusSpecific[] = 'print';
                }
            }

            if ($item->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
                if (!in_array('refund', $statusSpecific)) {
                    $statusSpecific[] = 'refund';
                    $statusSpecific[] = 'calculateRefund';
                }
            }
        }*/

        return array_merge($general, $statusSpecific);
    }
}
