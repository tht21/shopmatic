<?php

namespace App\Integrations\Vend;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedAddress;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Order;
use App\Models\ProductListing;
use Carbon\Carbon;

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
            $response = $this->client->request('get', $this->client->getUri(Client::VERSION_2_0, 'sales/' . $externalId), []);
            $data = $response['data'] ?? [];
            $data['customer'] = $this->getOrderCustomer($data['customer_id']);

            foreach ($data['line_items'] as $keyItem => $item) {
                $data['line_items'][$keyItem]['order_item'] = $this->getOrderItem($item['product_id']);
            }
            $order = $this->transformOrder($data);
        } catch(\Exception $e) {
            set_log_extra('response', $e);
            throw new \Exception('Vend-'.$this->account->id.' Unable to connect and retrieve order.');
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

        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }

        $this->account->sync_data = null;
        $this->account->save();

        $this->fetchOrders($options, []);
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
        $this->fetchOrders($options, []);
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
        $response = $this->client->request('get', $this->client->getUri(Client::VERSION_2_0, 'customers/' . $orderCustomerId), []);
        return $response['data'] ?? [];
    }

    /**
     * Retrieves a single product
     *
     * @param $orderProductId
     * @return mixed
     */
    private function getOrderItem($orderProductId)
    {

        $response = $this->client->request('GET', $this->client->getUri(Client::VERSION_0_9, 'products/' . $orderProductId), []);
        $product = $response['products'];
        return count($product) > 0 ? $product[0] : [];
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

            $account = $this->account;
            $sync_data = $account->sync_data;

            $parameters['page_size'] = 100;

            if (!empty($sync_data['order_version_max'])) {
                $parameters['after'] = $account->sync_data['order_version_max'];
            }

            $response = $this->client->request('get', $this->client->getUri(Client::VERSION_2_0, 'sales'), ['query' => $parameters]);

            $orders = $response['data'] ?? [];

            foreach ($orders as $key => $order) {
                $orders[$key]['customer'] = $this->getOrderCustomer($order['customer_id']);

                foreach ($order['line_items'] as $keyItem => $item) {
                    $orders[$key]['line_items'][$keyItem]['order_item'] = $this->getOrderItem($item['product_id']);
                }

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


            if (!empty($response['version']['max'])) {
                $sync_data['order_version_max'] = $response['version']['max'];
                $account->sync_data = $sync_data;
                $account->save();
            }

        } while (count($orders) != 0);
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
        $firstName = $order['customer']['first_name'] ?? '';
        $lastName = $order['customer']['last_name'] ?? '';
        $customerName = $firstName . ' ' . $lastName;

        $customer = $order['customer'];
        $customerEmail = $customer['email'] ?? null;
        $phone = $order['phone'] ?? '';

        // Shipping address
        $shippingAddress1 = $customer['postal_address_1'] ?? '';
        $shippingAddress2 = $customer['postal_address_2'] ?? '';
        $shippingCity = $customer['postal_city'] ?? '';
        $shippingPostcode = $customer['postal_postcode'] ?? '';
        $shippingState = $customer['postal_state'] ?? '';
        $shippingCountry = $customer['postal_country_id'] ?? '';

        $shippingAddress = new TransformedAddress(null, $customerName, $shippingAddress1, $shippingAddress2, null, null, null, $shippingCity, $shippingPostcode, $shippingState, $shippingCountry, $phone);

        // Billing address
        $billingAddress1 = $customer['physical_address_1'] ?? '';
        $billingAddress2 = $customer['physical_address_2'] ?? '';
        $billingCity = $customer['physical_city'] ?? '';
        $billingPostcode = $customer['physical_postcode'] ?? '';
        $billingState = $customer['physical_state'] ?? '';
        $billingCountry = $customer['physical_country_id'] ?? '';
        $billingAddress = new TransformedAddress(null, $customerName, $billingAddress1, $billingAddress2, null, null, null,
            $billingCity, $billingPostcode, $billingState, $billingCountry, $phone
        );

        $shipByDate = null;

        $currency = $this->account->currency;
        $integrationDiscount = 0;
        $sellerDiscount = $order['total_discount'] ?? 0;
        $shippingFee = 0;
        $tax = $order['total_tax'] ?? 0;
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $totalVoucher = 0;
        $subtotal = filter_var($order['total_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $grandTotal = $subtotal - $totalVoucher + $shippingFee;

        $paymentStatus = PaymentStatus::UNPAID();
        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        $fulfillmentStatus = FulfillmentStatus::PENDING();
        if (substr_count($order['status'], 'CLOSED') || substr_count($order['status'], 'ONACCOUNT_CLOSED') || substr_count($order['status'], 'LAYBY_CLOSED') || substr_count($order['status'], 'DISPATCHED_CLOSED') || substr_count($order['status'], 'PICKED_UP_CLOSED')) {
            $paymentStatus = PaymentStatus::PAID();
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif (substr_count($order['status'], 'SAVED') || substr_count($order['status'], 'ONACCOUNT') || substr_count($order['status'], 'LAYBY')) {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } elseif (substr_count($order['status'], 'AWAITING_DISPATCH')) {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
        } elseif (substr_count($order['status'], 'AWAITING_PICKUP')) {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
        } elseif (substr_count($order['status'], 'VOIDED')) {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
        }

        $buyerRemarks = null;
        $type = OrderType::NORMAL();
        $orderPlacedAt = Carbon::parse($order['created_at']);
        $orderUpdatedAt = Carbon::parse($order['updated_at']);


        if (empty($paymentResults)) {
            $paymentStatus = PaymentStatus::UNPAID();
        }

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $grandTotal : 0;
        $settlementAmount = 0;

        $orderPaidAt = null;
        $paymentMethod = null;
        if ($paymentStatus->equals(PaymentStatus::PAID())) {
            $payments = $order['payments'] ?? [];
            if (count($payments) > 0) {
                $payment = $payments[count($payments) - 1];
                $paymentMethod = $payment['name'];
                $orderPaidAt = Carbon::parse($payment['payment_date']);
            }
        }

        $data = [
            'short_code' => $order['short_code'],
            'total_loyalty' => $order['total_loyalty'],
            'version' => $order['version'],
            'total_price_incl' => $order['total_price_incl'],
        ];

        $data[] = ['adjustments' => $order['adjustments']];

        $items = [];
        foreach ($order['line_items'] as $item) {

            $itemExternalId = $item['id'];
            $orderItem = $item['order_item'];
            $itemName = $orderItem['handle'] ?? '';
            $externalProductId = $item['product_id'];
            $sku = $orderItem['sku'];
            $variationName = $orderItem['name'] ?? 'N/A';
            $variationSku = $sku;
            $quantity = $item['quantity'];

            $itemPrice = $orderItem['price'];

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = $item['discount_total'];

            $itemShippingFee = 0;

            $itemTax = $item['tax'];
            $itemTax2 = 0;

            $itemGrandTotal = $item['price'];
            $itemBuyerPaid = $item['price_total'];

            $itemStatus = trim(strtolower($item['status']));
            $itemFulfillmentStatus = FulfillmentStatus::PENDING();
            if ($itemStatus === 'CONFIRMED') {
                $itemFulfillmentStatus = FulfillmentStatus::SHIPPED();
            }
            $shipmentProvider = null;
            $shipmentType = null;
            $shipmentMethod = null;
            $trackingNumber = null;
            $itemFulfillmentStatus = FulfillmentStatus::PROCESSING();
            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = 0;

            $itemData = [
                'tax_id' => $item['tax_id'],
                'loyalty_value' => $item['loyalty_value'],
                'note' => $item['note'],
                'price_set' => $item['price_set'],
                'sequence' => $item['sequence'],
                'gift_card_number' => $item['gift_card_number'],
                'tax_components' => $item['tax_components'],
                'promotions' => $item['promotions'],
                'unit_cost' => $item['unit_cost'],
                'unit_discount' => $item['unit_discount'],
                'unit_loyalty_value' => $item['unit_loyalty_value'],
                'is_return' => $item['is_return'],
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
        // TODO: Implement availableActions() method.
    }
}
