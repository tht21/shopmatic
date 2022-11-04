<?php

namespace App\Integrations\PrestaShop;

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
     * @return mixed
     * @throws \Exception
     */
    public function get($externalId, $options = ['deduct' => true])
    {
        $parameters = [];

        $response = $this->client->request('GET', 'orders/' . $externalId, $parameters);
        $response = json_decode($response->getBody()->getContents(), true);

        if (isset($response['order']) && count($response['order'])) {
            $order = $response['order'];

            if (isset($order['associations']['order_rows'])) {
                // Get all order details ids
                $orderDetailsIds = array_column($order['associations']['order_rows'], 'id');

                // Retrieve full order details/item
                $order['order_details'] = $this->getOrderDetails($orderDetailsIds);
            }

            // Retrieve full order delivery address
            $order['delivery_address'] = $this->getOrderAddress($order['id_address_delivery']);
            // Retrieve full order invoice address
            $order['invoice_address'] = $this->getOrderAddress($order['id_address_invoice']);
            // Retrieve full customer detail
            $order['customer'] = $this->getOrderCustomer($order['id_customer']);
            // Retrieve full order carrier
            $order['carrier'] = $this->getOrderCarrier($order['id_carrier']);
            // Retrieve full order state
            $order['state'] = $this->getOrderState($order['current_state']);

            try {
                $order = $this->transformOrder($order);
            } catch (\Exception $e) {
                set_log_extra('order', $order);
                throw $e;
            }
            return $this->handleOrder($order, $options);
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

        $parameters = [
            'query' => [
                'limit' => '0,50',
                'display' => 'full'
            ]
        ];
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

        $syncDate = $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now(), true);
        $startDate = new Carbon($syncDate);
        $startDate->format('Y-m-d H:i:s');

        $parameters = [
            'query' => [
                'limit' => '0,50',
                'display' => 'full',
                'filter[date_upd]' => "[$startDate," . now()->format('Y-m-d H:i:s') . "]",
                'date' => '1'
            ]
        ];
        $this->fetchOrders($options, $parameters);

        $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
    }

    /**
     * Retrieve order status
     *
     * @param $orderStateId
     * @param array $parameters
     * @return array
     */
    public function getOrderState($orderStateId = null, $parameters = [], $filterName = null)
    {
        if ($orderStateId) {
            $response = $this->client->request('GET', 'order_states/' . $orderStateId, $parameters);
            $response = json_decode($response->getBody()->getContents(), true);
            return $response['order_state'] ?? [];
        } else {
            $response = $this->client->request('GET', 'order_states', $parameters);
            $response = json_decode($response->getBody()->getContents(), true);
            $orderStates = $response['order_states'] ?? [];

            $states = [];
            foreach ($orderStates as $key => $status) {
                $id = $status['id'] ?? 0;
                $status = $this->getOrderState($id);
                if ($filterName) {
                    if (strtoupper($status['name']) == strtoupper($filterName)) {
                        array_push($states, $status);
                    }
                } else {
                    array_push($states, $status);
                }
            }
            return $states;
        }
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
        do {
            $orders = [];
            $response = $this->client->request('GET', 'orders', $parameters);
            $response = json_decode($response->getBody()->getContents(), true);

            if (isset($response['orders']) && count($response['orders'])) {
                $orders = $response['orders'];

                foreach ($orders as $key => $order) {
                    if (isset($order['associations']['order_rows'])) {
                        // Get all order details ids
                        $orderDetailsIds = array_column($order['associations']['order_rows'], 'id');

                        // Retrieve full order details/item
                        $orders[$key]['order_details'] = $this->getOrderDetails($orderDetailsIds);
                    }

                    // Retrieve full order delivery address
                    $orders[$key]['delivery_address'] = $this->getOrderAddress($order['id_address_delivery']);
                    // Retrieve full order invoice address
                    $orders[$key]['invoice_address'] = $this->getOrderAddress($order['id_address_invoice']);
                    // Retrieve full customer detail
                    $orders[$key]['customer'] = $this->getOrderCustomer($order['id_customer']);
                    // Retrieve full order carrier
                    $orders[$key]['carrier'] = $this->getOrderCarrier($order['id_carrier']);
                    // Retrieve full order state
                    $orders[$key]['state'] = $this->getOrderState($order['current_state']);
                }

                foreach ($orders as $key => $order) {
                    try {
                        $order = $this->transformOrder($order);
                    } catch (\Exception $e) {
                        set_log_extra('order', $order);
                        throw $e;
                    }
                    $this->handleOrder($order, $options);
                }
            }

            // Handle offset parameter
            $explode = explode(",", $parameters['query']['limit']);
            if (isset($explode[0], $explode[1])) {
                $offset = $explode[0];
                $limit = $explode[1];

                $parameters['query']['limit'] = $offset + $limit . ', ' . $limit;
            } else {
                set_log_extra('parameters', $parameters);
                throw new \Exception('Invalid parameters.');
            }
        } while (count($orders) > 0);
    }

    /**
     * Retrieve order details item based on order details ids
     *
     * @param $orderDetailIds
     * @param array $parameters
     * @return array
     */
    private function getOrderDetails(array $orderDetailIds, $parameters = [])
    {
        $orderDetails = [];
        foreach ($orderDetailIds as $orderDetailId) {
            $response = $this->client->request('GET', 'order_details/' . $orderDetailId, $parameters);
            $response = json_decode($response->getBody()->getContents(), true);

            if (isset($response['order_detail'])) {
                $orderDetails[] = $response['order_detail'];
            }
        }
        return $orderDetails;
    }

    /**
     * Retrieve address
     *
     * @param $orderAddressId
     * @param array $parameters
     * @return array
     */
    private function getOrderAddress($orderAddressId, $parameters = [])
    {
        $response = $this->client->request('GET', 'addresses/' . $orderAddressId, $parameters);
        $response = json_decode($response->getBody()->getContents(), true);

        $address = $response['address'] ?? [];

        if ($address) {
            // Retrieve state & country
            $response = $this->client->request('GET', 'states/' . $address['id_state'], $parameters);
            $response = json_decode($response->getBody()->getContents(), true);

            $address['state'] = $response['state'] ?? [];

            $response = $this->client->request('GET', 'countries/' . $address['id_country'], $parameters);
            $response = json_decode($response->getBody()->getContents(), true);

            $address['country'] = $response['country'] ?? [];
        }

        return $address;
    }

    /**
     * Retrieve customer
     *
     * @param $orderCustomerId
     * @param array $parameters
     * @return array
     */
    private function getOrderCustomer($orderCustomerId, $parameters = [])
    {
        $response = $this->client->request('GET', 'customers/' . $orderCustomerId, $parameters);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response['customer'] ?? [];
    }

    /**
     * Retrieve carrier
     *
     * @param $orderCarrierId
     * @param array $parameters
     * @return array
     */
    private function getOrderCarrier($orderCarrierId, $parameters = [])
    {
        $response = $this->client->request('GET', 'order_carriers/' . $orderCarrierId, $parameters);
        $response = json_decode($response->getBody()->getContents(), true);

        return $response['order_carrier'] ?? [];
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order['id'];
        $externalNumber = null;
        $externalSource = $this->account->integration->name;

        $firstName = $order['customer']['firstname'] ?? '';
        $lastName = $order['customer']['lastname'] ?? '';
        $customerName = $firstName . ' ' . $lastName;

        $customerEmail = $order['customer']['email'] ?? null;

        // Shipping address
        $shipping = $order['delivery_address'];
        $state = $shipping['state']['name'] ?? null;
        $country = $shipping['country']['name'] ?? null;
        $phone = (empty($shipping['phone'])) ? $shipping['phone_mobile'] : $shipping['phone'];
        // To remove any if it's empty
        $shippingAddress = new TransformedAddress($shipping['company'], $shipping['firstname'] . ' ' . $shipping['lastname'],
            $shipping['address1'], $shipping['address2'], null, null, null,
            $shipping['city'], $shipping['postcode'], $state, $country, $phone
        );
        // Billing address
        $billing = $order['invoice_address'];
        $state = $billing['state']['name'] ?? null;
        $country = $billing['country']['name'] ?? null;
        $phone = (empty($billing['phone'])) ? $billing['phone_mobile'] : $billing['phone'];
        $billingAddress = new TransformedAddress($billing['company'], $billing['firstname'] . ' ' . $billing['lastname'],
            $billing['address1'], $billing['address2'], null, null, null,
            $billing['city'], $billing['postcode'], $state, $country, $phone
        );

        if ($order['delivery_date'] != '0000-00-00 00:00:00') {
            $shipByDate = Carbon::createFromFormat('Y-m-d H:i:s', $order['delivery_date']);
        } else {
            $shipByDate = null;
        }

        $currency = $this->account->currency;

        $integrationDiscount = 0;
        $sellerDiscount = $order['total_discounts'];
        $shippingFee = filter_var($order['total_shipping'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $tax = 0;
        $tax2 = 0;

        /* Marketplace fee - escrow amount */
        $commission = 0;
        $transactionFee = 0;
        $settlementAmount = 0;

        $paymentMethod = $order['payment'];

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();
        $paymentStatus = PaymentStatus::PAID();

        /*
         * Currently only support for 3 diff kind of status
         * Because presta shop status can be customized by user
         * So we only can filter by this 3 status - delivery, shipped and paid
         * NOTE - deleted does not mean cancelled
         * */
        $fulfillmentStatus = FulfillmentStatus::PROCESSING();

        if (strtoupper($order['state']['name']) == FulfillmentStatus::PROCESSING()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::PROCESSING();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::READY_TO_SHIP()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::SHIPPED()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::PARTIALLY_SHIPPED()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::PARTIALLY_SHIPPED();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::RETRY_SHIP()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::RETRY_SHIP();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::DELIVERED()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::TO_CONFIRM_DELIVERED()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::TO_CONFIRM_DELIVERED();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::CANCELLED()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::REQUEST_CANCEL()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::REQUEST_CANCEL();
        } elseif (strtoupper($order['state']['name']) == FulfillmentStatus::RETURNED()->getKey()) {
            $fulfillmentStatus = FulfillmentStatus::RETURNED();
        } elseif (strtoupper($order['state']['name']) == PaymentStatus::PAID()->getKey()) {
            $paymentStatus = PaymentStatus::PAID();
        } elseif (strtoupper($order['state']['name']) == PaymentStatus::UNPAID()->getKey()) {
            $paymentStatus = PaymentStatus::UNPAID();
        } elseif (strtoupper($order['state']['name']) == PaymentStatus::CANCELLED()->getKey()) {
            $paymentStatus = PaymentStatus::CANCELLED();
        } elseif (strtoupper($order['state']['name']) == PaymentStatus::REFUNDED()->getKey()) {
            $paymentStatus = PaymentStatus::REFUNDED();
        } elseif (strtoupper($order['state']['name']) == PaymentStatus::PARTIALLY_REFUNDED()->getKey()) {
            $paymentStatus = PaymentStatus::PARTIALLY_REFUNDED();
        }

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $order['total_paid_real'] : 0;

        // This is customer/buyer remark or message
        $buyerRemarks = null;

        if (!empty($order['gift_message'])) {
            $buyerRemarks .= ' -- Gift Message: ' . $order['gift_message'];
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::createFromFormat('Y-m-d H:i:s', $order['date_add']);
        $orderUpdatedAt = Carbon::createFromFormat('Y-m-d H:i:s', $order['date_upd']);

        $orderPaidAt = null;

        $data = [
            'secure_key' => $order['secure_key'],
            'reference' => $order['reference'],
            'conversion_rate' => $order['conversion_rate'],
        ];

        $items = [];
        $grandTotal = 0;
        foreach ($order['order_details'] as $item) {

            $itemExternalId = $item['id'];
            $itemName = $item['product_name'];
            $externalProductId = $item['product_id'];
            $variationName = $item['product_name'] ?? 'N/A';

            $sku = $item['product_reference'] ?? '';
            $variationSku = $sku;

            $quantity = $item['product_quantity'];

            $itemPrice = $item['product_price'];

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = $item['product_quantity_discount'];

            $itemShippingFee = $item['total_shipping_price_tax_excl'];

            $itemTax = $item['ecotax'];
            $itemTax2 = 0;

            $itemPaidPrice = $item['unit_price_tax_incl'];

            $itemGrandTotal = $item['product_quantity'] * $itemPaidPrice;
            $itemBuyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $itemGrandTotal : 0;

            $grandTotal += $itemGrandTotal;

            // Presta does not have item status
            $itemFulfillmentStatus = $fulfillmentStatus;

            $shipmentProvider = null;
            $trackingNumber = $order['carrier']['tracking_number'] ?? null;
            $shipmentType = null;
            $shipmentMethod = null;

            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = $item['total_shipping_price_tax_incl'];

            $itemData = [
                'product_quantity_in_stock' => $item['product_quantity_in_stock'],
                'product_quantity_return' => $item['product_quantity_return'],
                'product_quantity_refunded' => $item['product_quantity_refunded'],
                'product_reference' => $item['product_reference'],
                'ecotax_tax_rate' => $item['ecotax_tax_rate'],
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
        $statusSpecific[] = 'updateStatus';
        return array_merge($general, $statusSpecific);
    }


    /**
     * Marks the items as being cancelled
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function updateStatus(Order $order, Request $request)
    {

        $fulfillmentStatus = $request->input('fulfillment_status');
        $status = $this->statusFormat($fulfillmentStatus);
        if (empty($status)) {
            return $this->respondBadRequestError('PrestaShop order does have this status');
        }

        $externalId = $order->external_id;
        $response = $this->client->request('GET', 'orders/' . $externalId, []);
        $response = json_decode($response->getBody()->getContents(), true);

        $data = $response['order'];
        $data['current_state'] = $status['id'];
        $xmlData['order'] = $data;
        $xmlData = $this->dataToXml($xmlData);
        $parameters = [
            'body' => $xmlData
        ];
        $response = $this->client->request('PUT', 'orders', $parameters);
        $response = json_decode($response->getBody()->getContents(), true);

        if ($response) {
            $this->get($externalId, ['deduct' => false]);
            return true;
        }

        return false;
    }

    /**
     * Convert frontend data to PrestaShop xml format
     *
     * @param $fulfillmentStatus
     * @return string
     */
    public function statusFormat($fulfillmentStatus)
    {

        $fulfillmentStatus = (int)$fulfillmentStatus;
        $filterName = '';
        if ($fulfillmentStatus === FulfillmentStatus::PROCESSING()->getValue()) {
            $filterName = 'PROCESSING';
        } elseif ($fulfillmentStatus === FulfillmentStatus::READY_TO_SHIP()->getValue()) {
            $filterName = 'READY_TO_SHIP';
        } elseif ($fulfillmentStatus === FulfillmentStatus::SHIPPED()->getValue()) {
            $filterName = 'SHIPPED';
        } elseif ($fulfillmentStatus === FulfillmentStatus::PARTIALLY_SHIPPED()->getValue()) {
            $filterName = 'PARTIALLY_SHIPPED';
        } elseif ($fulfillmentStatus === FulfillmentStatus::DELIVERED()->getValue()) {
            $filterName = 'DELIVERED';
        } elseif ($fulfillmentStatus === FulfillmentStatus::TO_CONFIRM_DELIVERED()->getValue()) {
            $filterName = 'TO_CONFIRM_DELIVERED';
        } elseif ($fulfillmentStatus === FulfillmentStatus::CANCELLED()->getValue()) {
            $filterName = 'CANCELLED';
        } elseif ($fulfillmentStatus === FulfillmentStatus::REQUEST_CANCEL()->getValue()) {
            $filterName = 'REQUEST_CANCEL';
        } elseif ($fulfillmentStatus === FulfillmentStatus::RETURNED()->getValue()) {
            $filterName = 'RETURNED';
        }

        if ($filterName) {
            $response = $this->getOrderState(null, [], $filterName);
            if ($response) {
                if (count($response) > 0) {
                    return $response[0];
                }
            }
        }
        return [];
    }

    /**
     * Convert frontend data to PrestaShop xml format
     *
     * @param $data
     * @return string
     */
    public function dataToXml($data)
    {
        /* Format Data - START */
        $xmlData['prestashop'] = $data;
        $document = new \DOMDocument();
        $this->arrayToDOMDoc($document, $document, $xmlData);
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        return $document->saveXML();
    }

    /**
     * @param \DOMDocument $document
     * @param \DOMElement|\DOMDocument $currentElement
     * @param array|string $xmlData
     * @param null $childName
     */
    private function arrayToDOMDoc(\DOMDocument &$document, &$currentElement, $xmlData, $childName = null)
    {
        // recursive fill data
        if (is_array($xmlData)) {
            foreach ($xmlData as $name => $data) {
                // special for Skus array without custom key
                try {
                    if (is_numeric($name)) $name = $childName;
                    $element = $document->createElement($name);
                    $currentElement->appendChild($element);
                    if ($name === 'order_rows') {
                        $this->arrayToDOMDoc($document, $element, $data, 'order_rows');
                    } else {
                        $this->arrayToDOMDoc($document, $element, $data);
                    }

                } catch (\Exception $e) {
                    dd($name, $data);
                }
            }
        } else {
            $currentElement->appendChild($document->createTextNode(trim($xmlData)));
        }
    }

}
