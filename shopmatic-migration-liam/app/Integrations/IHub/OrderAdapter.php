<?php

namespace App\Integrations\IHub;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedAddress;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Integration;
use App\Models\Order;
use Carbon\Carbon;

class OrderAdapter extends AbstractOrderAdapter
{

    /**
     * Retrieves a single order
     *
     * @param $externalId
     * @param array $options
     * @return Order
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function get($externalId, $options = ['deduct' => true])
    {

    }

    /**
     * Import all orders
     *
     * @param array $options
     * @return void
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function import($options = ['deduct' => false])
    {
        // This is so it wont create new notifications
        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }
        $this->fetchOrders($options, []);
    }

    /**
     * Incremental order sync
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sync()
    {

    }

    /**
     * push orders
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function push()
    {

        $integrationId = Integration::IHUB;
        $itemStocks = $this->client->request('get', 'api/Client/GetClientItemStock', []);

        if ($itemStocks) {

            //Get existing Orders list from CS
            $orders = Order::where('integration_id', '!=', $integrationId)
                ->get();
            foreach ($orders as $key => $order) {

                $existOrder = Order::where('external_id', '=', $order->external_id)
                    ->where('integration_id', '=', $integrationId)
                    ->first();

                if (!$existOrder) {

                    $clientCode = null;
                    $orderLines = [];
                    $itemIds = [];
                    $item = null;

                    foreach ($order->items as $o) {

                        $itemCodes = collect($itemStocks['responseList']);

                        $quantity = $o->quantity;

                        if ($item = $itemCodes->first()) {

                            $orderLines[] = [
                                "clientCode" => $item['clientCode'],
                                "itemCode" => $item['itemCode'],
                                "itemDescription" => $o->name,
                                "shipmentDescription" => "string",
                                "quantity" => $quantity,
                            ];

                            $itemIds[] = $o->id;
                        }
                    }
                    $this->create($order, $item, $orderLines);
                }
            }

        }
    }

    /**
     * create order
     *
     * @param Order $order
     * @param $item
     * @param $orderLines
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function create(Order $order, $item, $orderLines)
    {

        $integrationId = Integration::IHUB;
        $clientCode = $item['clientCode'];

        if ($clientCode) {

            $deliveryOrderNo = "CS-" . $order->integration_id . "-" . $order->external_id;
            if (strlen($deliveryOrderNo) > 20) {
                $deliveryOrderNo = "CS-" . $order->external_id;
            }

            $deliveryOrder = [
                "clientCode" => $clientCode,  // Its sample ClientCode
                "deliveryOrderNo" => $deliveryOrderNo, //. "-" . Carbon::now()->timestamp,
                "deliveryMode" => "SELF", //NONE, SELF, IHUB
                "deliveryType" => "NORMAL",
                "deliveryDate" => Carbon::now()->format('d/m/Y'), //$order->created_at->format('d/m/Y') ?? Carbon::now()->format('d/m/Y'),
                "deliveryTiming" => "NONE",
                "deliveryRemarks" => $order->integration->name . ' - ' . $order->buyer_remarks,
                "customer" => [
                    "name" => isset($order->shipping_address['name']) && !empty($order->shipping_address['name']) ? $order->shipping_address['name'] : $order->first_name,
                    "phoneNo" => isset($order->shipping_address['phone']) && !empty($order->shipping_address['phone']) ? $order->shipping_address['phone'] : ' ',
                    "company" => isset($order->shipping_address['name']) && !empty($order->shipping_address['name']) ? $order->shipping_address['name'] : ' ',
                    "addressLine1" => isset($order->shipping_address['address']) && !empty($order->shipping_address['address']) ? $order->shipping_address['address'] : ' ',
                    //"addressLine2"      => "string",
                    "addressCountry" => isset($order->shipping_address['country']) && !empty($order->shipping_address['country']) ? $order->shipping_address['country'] : ' ',
                    "addressPostalCode" => isset($order->shipping_address['postcode']) && !empty($order->shipping_address['postcode']) ? $order->shipping_address['postcode'] : ' ',
                ],
                "billing" => [
                    "company" => "string",
                    "addressLine1" => isset($order->shipping_address['address']) && !empty($order->shipping_address['address']) ? $order->shipping_address['address'] : ' ',
                    //"addressLine2"      => "string",
                    "addressCountry" => isset($order->shipping_address['country']) && !empty($order->shipping_address['country']) ? $order->shipping_address['country'] : ' ',
                    "addressPostalCode" => isset($order->shipping_address['postcode']) && !empty($order->shipping_address['postcode']) ? $order->shipping_address['postcode'] : ' ',
                ],
                "orderLines" => $orderLines
            ];

            //Push Order to iHub
            $options = [
                'body' => json_encode($deliveryOrder, true)
            ];

            $response = $this->client->request('post', 'api/v1.1/DeliveryOrder/CreateDeliveryOrder' . '?DataOrigin=OTHER', $options);
            //dummy response data
            //$response = [
            //    "orderId" => 37770,
            //    "orderStatus" => "WAITING",
            //    "deliveryStatus" => "PENDING",
            //    "deliveryDate" => "30/03/2020",
            //    "proofOfDeliveryUrls" => null,
            //    "customerSignatureUrl" => null,
            //    "responseStatus" => "SUCCESS",
            //    "errors" => [],
            //];

            if (!empty($response['orderId']) && (!$response['errors'] || (isset($response['errors']) && strpos($response['errors'][0], 'Order No already existing')))) {

                //Save pushed order response for the Order Extra Attributes as "ihub"
                $newOrder = $this->transformOrder($order);
                //Overwrite data
                $newOrder->externalNumber = $order->integration_id.'-'.$order->external_id;
                $newOrder->externalId = $response['orderId'];
                $newOrder->fulfillmentStatus = FulfillmentStatus::PENDING();
                $newOrder->items[0]->externalId = $response['orderId'];
                $newOrder->items[0]->sku = $response['orderId'];
                $newOrder->items[0]->sku = $response['orderId'];
                $newOrder->items[0]->variationSku = $response['orderId'];

                $this->handleOrder($newOrder, []);

            } else {
                /* past date by due to timezone, it will be sync eventually, thus skip logging */

                if (trim($response['errors'][0] ?? '') != 'Past date is not allowed [DeliveryDate]') {
                    set_log_extra('response', '[iHub-' . $integrationId . '] Unable to push order. Response => ' . print_r($response, true) . '. Data: ' . print_r($deliveryOrder, true));
                }
            }

        }

    }

    /**
     * update order
     *
     * @param Order $order
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function update(Order $order)
    {

        $integrationId = Integration::IHUB;
        $params = [
            'orderId' => $order->external_id,
            'clientCode' => $order->customer_name,
            'deliveryOrderNo' => $order->external_id,
            'deliveryMode' => 'NONE',
            'deliveryType' => 'NORMAL',
            'deliveryDate' => Carbon::parse($order->created_at)->format('d/m/Y'),
            'deliveryTiming' => 'NONE',
            "customer" => [
                "name" => isset($order->shipping_address['name']) && !empty($order->shipping_address['name']) ? $order->shipping_address['name'] : $order->first_name,
                "phoneNo" => isset($order->shipping_address['phone']) && !empty($order->shipping_address['phone']) ? $order->shipping_address['phone'] : ' ',
                "company" => isset($order->shipping_address['name']) && !empty($order->shipping_address['name']) ? $order->shipping_address['name'] : ' ',
                "addressLine1" => isset($order->shipping_address['address']) && !empty($order->shipping_address['address']) ? $order->shipping_address['address'] : ' ',
                //"addressLine2"      => "string",
                "addressCountry" => isset($order->shipping_address['country']) && !empty($order->shipping_address['country']) ? $order->shipping_address['country'] : ' ',
                "addressPostalCode" => isset($order->shipping_address['postcode']) && !empty($order->shipping_address['postcode']) ? $order->shipping_address['postcode'] : ' ',
            ],
            "billing" => [
                "company" => "string",
                "addressLine1" => isset($order->shipping_address['address']) && !empty($order->shipping_address['address']) ? $order->shipping_address['address'] : ' ',
                //"addressLine2"      => "string",
                "addressCountry" => isset($order->shipping_address['country']) && !empty($order->shipping_address['country']) ? $order->shipping_address['country'] : ' ',
                "addressPostalCode" => isset($order->shipping_address['postcode']) && !empty($order->shipping_address['postcode']) ? $order->shipping_address['postcode'] : ' ',
            ],
            "orderLines" => [
                "clientCode" => $order->customer_name,
                "itemCode" => $order->external_id,
                "itemDescription" => $order->buyer_remarks,
                "shipmentDescription" => $order->buyer_remarks,
                "quantity" => 0,
            ]
        ];


        $options = [
            'body' => json_encode($params, true)
        ];

//        $response = $this->client->request('post', 'api/v1.1/DeliveryOrder/UpdateDeliveryOrder', $options);
        //dummy response data
        $response = [
            "orderId" => $order->external_id,
            "orderStatus" => "WAITING",
            "deliveryStatus" => "PENDING",
            "deliveryDate" => "30/03/2020",
            "proofOfDeliveryUrls" => null,
            "customerSignatureUrl" => null,
            "responseStatus" => "SUCCESS",
            "errors" => [],
        ];


        if (!empty($response['orderId']) && (!$response['errors'] || (isset($response['errors']) && strpos($response['errors'][0], 'Order No already existing')))) {

            //Save pushed order response for the Order Extra Attributes as "ihub"
            $newOrder = $this->transformOrder($order);
            //Overwrite data
            $newOrder->externalNumber = $order->integration_id.'-'.$order->external_id;
            $newOrder->externalId = $response['orderId'];
            $newOrder->fulfillmentStatus = FulfillmentStatus::PENDING();
            $newOrder->items[0]->externalId = $response['orderId'];
            $newOrder->items[0]->sku = $response['orderId'];
            $newOrder->items[0]->sku = $response['orderId'];
            $newOrder->items[0]->variationSku = $response['orderId'];

            $this->handleOrder($newOrder, []);

        } else {
            /* past date by due to timezone, it will be sync eventually, thus skip logging */

            if (trim($response['errors'][0] ?? '') != 'Past date is not allowed [DeliveryDate]') {
                set_log_extra('response', '[iHub-' . $integrationId . '] Unable to push order. Response => ' . print_r($response, true));
            }
        }
    }

    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $options
     * @param $parameters
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function fetchOrders($options, $parameters)
    {

        $response = $this->client->request('get', 'api/Client/GetClientItemStock', $parameters);
        $orders = $response['responseList'];

        if (!empty($orders)) {
            foreach ($orders as $i => $order) {
                try {
                    $order = $this->transformOrder($order);
                } catch (\Exception $e) {
                    set_log_extra('orders', $order);
                    throw $e;
                }
                $this->handleOrder($order, $options);
            }
        }
        return true;

    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {

        $clientCode = $order['clientCode'];
        $sku = $order['itemCode'];
        $quantity = $order['availableQty'] ?? 0;
        $description = $order['itemDescription'] ?? null;
        $currency = $this->account->currency;
        $integrationDiscount = 0;
        $sellerDiscount = 0;
        $shippingFee = 0;
        $tax = 0;
        $tax2 = 0;
        $grandTotal = 0;
        $buyerPaid = 0;
        $paymentStatus = PaymentStatus::PAID();
        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();
        $fulfillmentStatus = FulfillmentStatus::PENDING();
        $orderPlacedAt = Carbon::now();
        $orderUpdatedAt = Carbon::now();
        $type = OrderType::NORMAL();

        $itemFulfillmentStatus = FulfillmentStatus::PROCESSING();
        $itemIntegrationDiscount = 0;
        $itemSellerDiscount = 0;
        $itemShippingFee = 0;
        $itemTax = 0;
        $itemTax2 = 0;
        $itemGrandTotal = 0;
        $actualShippingFee = 0;

        $shippingAddress = new TransformedAddress(null, $order->shipping_address['name'] ?? null,
            $order->shipping_address['full_address'] ?? null, null, null, null, null,
            $order->shipping_address['city'] ?? null, $order->shipping_address['zipcode'] ?? null, $order->shipping_address['state'] ?? null, $order->shipping_address['country'] ?? null, $order->shipping_address['phone'] ?? null
        );

        $items[] = new TransformedOrderItem($sku, null, null, $sku, $clientCode, $sku, $quantity,
            null, $itemIntegrationDiscount, $itemSellerDiscount, $itemShippingFee, $itemTax, $itemTax2, $itemGrandTotal, null,
            $itemFulfillmentStatus, null, null, null, null, null, null, $actualShippingFee,
            null);

        $order = new TransformedOrder($sku, null, null, $clientCode, null, $shippingAddress, null,
            null, $currency, $integrationDiscount, $sellerDiscount, $shippingFee, $tax, $tax2, null, 0, $grandTotal, $buyerPaid, null,
            $paymentStatus, null, $fulfillmentType, $fulfillmentStatus, null, $type, null, $orderPlacedAt,
            $orderUpdatedAt, null, $items,$description);

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function availableActions(Order $order)
    {

    }


}
