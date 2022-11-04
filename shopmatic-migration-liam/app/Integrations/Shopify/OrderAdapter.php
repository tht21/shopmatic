<?php

namespace App\Integrations\Shopify;

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
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        $response = $this->client->request('get', '/admin/api/2020-07/orders/' . $externalId . '.json');

        if ($response->getStatusCode() === 200) {
            $order = json_decode($response->getBody()->getContents(), true);

            try {
                $order = $this->transformOrder($order['order']);
            } catch (\Exception $e) {
                set_log_extra('order', $order);
                throw $e;
            }
            return $this->handleOrder($order, $options);
        } else {
            set_log_extra('code', $response->getStatusCode());
            set_log_extra('response', $response);
            set_log_extra('body', json_decode($response->getBody()->getContents(), true));
            $exceptionMessage = 'Unable to retrieve order when call get api for Shopify|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * Import all orders
     *
     * @param array $options
     * @return boolean
     * @throws \Exception
     */
    public function import($options = ['deduct' => false])
    {
        // This is so it wont create new notifications
        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }

        $filters = [
            'updated_at_min' => date(DATE_ISO8601, strtotime(now()->subYears(2))),
            'query' => [
                'limit' => 100,
                'status' => 'any'
            ]
        ];
        $orders = $this->fetchOrders($filters, 'shopify-import-orders-' . $this->account->id);

        foreach ($orders as $order) {
            if (!empty($order)) {
                try {
                    $order = $this->transformOrder($order);
                } catch (\Exception $e) {
                    set_log_extra('order', $order);
                    throw $e;
                }
                $this->handleOrder($order, $options);
            }
        }
        return true;
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
        $debugLog = '[Shopify Order Sync]Debug Log|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
        Log::info($debugLog);
        $filters = [
            'updated_at_min' => $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now(), true)->format(\DateTime::ISO8601),
            'query' => [
                'limit' => 30,
                'status' => 'any'
            ]
        ];
        $orders = $this->fetchOrders($filters, 'shopify-sync-orders-' . $this->account->id);
        if ($orders != null) {
            foreach ($orders as $order) {
                if (!empty($order)) {
                    try {
                        $order = $this->transformOrder($order);
                    } catch (\Exception $e) {
                        set_log_extra('order', $order);
                        throw $e;
                    }
                    $this->handleOrder($order, $options);
                }
            }

            // Not update sync_orders when exceeded 2 calls per second for api client

            $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
        }
    }


    /**
     * This is used by both import and sync as their code is the same, the only difference is the timestamps
     *
     * @param $filters
     * @return array
     * @throws \Exception
     */
    private function fetchOrders($filters, $lock)
    {
        $timeout = 1800;
        $lock = Cache::lock($lock, $timeout);
        $orders = [];
        if ($lock->get()) {
            try {
                $requestUrl = '/admin/api/2020-07/orders.json';
                $nextPageToken = null;

                do {
                    if ($nextPageToken) $filters['query']['page_info'] = $nextPageToken;

                    // Exceeded 2 calls per second for api client. Reduce request rates to resume uninterrupted service.
                    sleep(1);

                    $response = $this->client->request('get', $requestUrl, $filters);
                    if ($response->getStatusCode() === 200) {
                        // support cursor-based pagination
                        $responseHeaders = $response->getHeaders();
                        $pageToken = null;
                        if (array_key_exists('Link', $responseHeaders)) {
                            // in the header response will see link : ... with rel next or previous.
                            // extract the page_info and then make another call with page_info.
                            $link = $responseHeaders['Link'][0];
                            $tokenType  = strpos($link, 'rel="next') !== false ? "next" : "previous";
                            $tobeReplace = ["<", ">", 'rel="next"', ";", 'rel="previous"'];
                            $tobeReplaceWith = ["", "", "", ""];
                            parse_str(parse_url(str_replace($tobeReplace, $tobeReplaceWith, $link), PHP_URL_QUERY), $op);
                            $pageToken[$tokenType] = trim($op['page_info']);
                        }
                    } else {
                        $exceptionMessage = 'Unable to retrieve order when call fetchOrders api for Shopify|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id;
                        Log::info($exceptionMessage);
                        Log::info('[Shopify fetchOrders Error] code: ' . $response->getStatusCode());
                        Log::info('[Shopify fetchOrders Error] filters: ' . json_encode($filters));
                        Log::info('[Shopify fetchOrders Error] body' . $response->getBody()->getContents());
                        throw new \Exception($exceptionMessage);
                    }

                    $response = json_decode($response->getBody()->getContents(), true);

                    if (isset($response['orders']) && !empty($response['orders'])) {
                        foreach ($response['orders'] as $product) {
                            $orders[] = $product;
                        }
                    }

                    // set page token and remove any filers as the filters will be applied from the first call else will return error
                    if (isset($pageToken['next']) && !empty($pageToken['next'])) {
                        $nextPageToken = $pageToken['next'];
                        $limit = $filters['query']['limit'] ?? 50;
                        $filters['query'] = ['limit' => $limit]; // only accept limit
                    } else {
                        $nextPageToken = null;
                    }
                } while ($nextPageToken != null);
            } catch (\Exception $e) {
                $lock->forceRelease();
                set_log_extra('filters', $filters);
                throw $e;
            }
            $lock->forceRelease();
        } else {
            return null;
        }
        return $orders;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order['id'];
        $externalNumber = $order['order_number'];
        $externalSource = $this->account->integration->name;

        $customerName = ($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? '');

        // Lazada no longer provides this
        $customerEmail = $order['customer']['email'] ?? null;

        $debugLog = '[Shopify transformOrder Items]Debug Log|Account Id|' . $this->account->id . '|Shop Id|' . $this->account->shop_id . '|Integration Id|' . $this->account->integration_id . '|Account Name|' . $this->account->name . '|Region|' . $this->account->region_id . '|Customer Name|' . $customerName . '|External Order Id|' . $externalId . '| External Order Number' . $externalNumber . '|External Source|' . $externalSource;
        Log::info($debugLog);
        $shippingAddress = null;
        if (isset($order['shipping_address'])) {
            $shipping = $order['shipping_address'];
            // To remove any if it's empty
            $phone = array_filter([$shipping['phone']]);
            $shippingAddress = new TransformedAddress(
                null,
                $shipping['first_name'] . ' ' . $shipping['last_name'],
                $shipping['address1'],
                $shipping['address2'],
                null,
                null,
                null,
                $shipping['city'],
                $shipping['zip'],
                $shipping['province'],
                $shipping['country'],
                $phone,
                $customerEmail
            );
        }

        $billingAddress = null;
        if (isset($order['billing_address'])) {
            $billing = $order['billing_address'];
            // To remove any if it's empty
            $phone = array_filter([$billing['phone']]);
            $billingAddress = new TransformedAddress(
                null,
                $billing['first_name'] . ' ' . $billing['last_name'],
                $billing['address1'],
                $billing['address2'],
                null,
                null,
                null,
                $billing['city'],
                $billing['zip'],
                $billing['province'],
                $billing['country'],
                $phone,
                $customerEmail
            );
        }

        //TODO: Check if this works
        $shipByDate = null;

        $paymentStatus = null;
        if ($order['financial_status'] == 'paid') {
            $paymentStatus = PaymentStatus::PAID();
        } elseif ($order['financial_status'] == 'pending' || $order['financial_status'] == 'unpaid') {
            $paymentStatus = PaymentStatus::UNPAID();
        } elseif ($order['financial_status'] == 'voided' || $order['financial_status'] == 'refunded') {
            $paymentStatus = PaymentStatus::CANCELLED();
        } elseif ($order['financial_status'] == 'partially_refunded') {
            $paymentStatus = PaymentStatus::PARTIALLY_REFUNDED();
        } elseif ($order['financial_status'] == 'partially_paid') {
            $paymentStatus = PaymentStatus::PARTIALLY_PAID();
        } elseif ($order['financial_status'] == 'authorized') {
            $paymentStatus = PaymentStatus::AUTHORIZED();
        } else {
            set_log_extra('status', $order['financial_status']);
            set_log_extra('order', $order);
            $logMessage = 'Shopify has different payment status|' . json_encode($order['financial_status']);
            throw new \Exception($logMessage);
        }

        $currency = $order['currency'];

        $integrationDiscount = 0;
        $sellerDiscount = $order['total_discounts'] ?? 0;

        $shippingFee = 0;
        foreach ($order['shipping_lines'] as $key => $value) {
            $shippingFee += $value['price'];
        }

        $tax = $order['total_tax'];
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $totalVoucher = $order['total_discounts'] ?? 0;
        $subtotal = filter_var($order['subtotal_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        //$grandTotal = $order['total_price'];
        $grandTotal = $subtotal;

        if (!$order['taxes_included']) {
            $grandTotal += $tax;
        }

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $grandTotal : 0;

        $settlementAmount = 0;

        $paymentMethod = $order['payment_gateway_names'][0] ?? null;

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if (is_null($order['fulfillment_status'])) {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } elseif ($order['fulfillment_status'] == 'fulfilled') {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif ($order['fulfillment_status'] == 'partial') {
            $fulfillmentStatus = FulfillmentStatus::PARTIALLY_SHIPPED();
        } elseif ($order['fulfillment_status'] == 'restocked') {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
            $paymentStatus = PaymentStatus::CANCELLED();
            $itemFulfillmentStatus = FulfillmentStatus::CANCELLED();
        } else {
            set_log_extra('statuses', $order['fulfillment_status']);
            set_log_extra('order', $order);
            throw new \Exception('Shopify has different fulfilment status');
        }

        if (!is_null($order['cancelled_at'])) {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
            $paymentStatus = PaymentStatus::CANCELLED();
        }

        $buyerRemarks = $order['note'];

        foreach ($order['note_attributes'] as $key => $value) {
            $buyerRemarks .= ' -- ' . $value['name'] . ': ' . $value['value'];
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::parse($order['created_at']);
        $orderUpdatedAt = Carbon::parse($order['updated_at']);

        if ($paymentStatus->equals(PaymentStatus::PAID())) {

            // We can't check this reliably, so we just use the created timestamp
            $orderPaidAt = Carbon::parse($order['created_at']);
        } else {
            $orderPaidAt = null;
        }

        // $data = [
        //     'total_voucher' => $order['voucher'],
        //     'voucher_code' => $order['voucher_code'],
        //     'branch_number' => $order['branch_number'],
        //     'tax_code' => $order['tax_code'],
        //     'national_registration_number' => $order['national_registration_number']
        // ];

        // $data[] = ['extra_attributes' => $order['extra_attributes']];
        // @NOTE - pick important data, instead of putting everything
        $data = $order;

        $items = [];
        foreach ($order['line_items'] as $item) {

            $itemExternalId = $item['id'];
            $itemName = $item['name'];
            $externalProductId = !empty($item['variant_id']) ? $item['variant_id'] : $item['product_id'];
            $sku = $item['sku'];
            $variationName = $item['variant_title'];
            $variationSku = $sku;
            $quantity = $item['quantity'];

            $itemPrice = $item['price'];

            $itemIntegrationDiscount = 0;
            $itemSellerDiscount = $item['total_discount'];

            $itemShippingFee = 0;
            $itemTax = 0;
            foreach ($item['tax_lines'] as $key => $value) {
                $itemTax += $value['price'];
            }

            $itemTax2 = 0;

            $itemGrandTotal = $item['price'] * $quantity;
            $itemBuyerPaid = $itemGrandTotal;

            $shipmentProvider = $item['fulfillment_service'];
            $shipmentType = FulfillmentType::REQUIRES_SHIPPING();
            $shipmentMethod = null;
            $trackingNumber = null;

            if (is_null($item['fulfillment_status'])) {
                $itemFulfillmentStatus = FulfillmentStatus::PENDING();
            } elseif ($item['fulfillment_status'] == 'fulfilled') {
                $itemFulfillmentStatus = FulfillmentStatus::SHIPPED();
            } elseif ($item['fulfillment_status'] == 'partial') {
                $itemFulfillmentStatus = FulfillmentStatus::PARTIALLY_SHIPPED();
            } elseif ($item['fulfillment_status'] == 'restocked') {
                    $paymentStatus = PaymentStatus::CANCELLED();
                    $fulfillmentStatus = FulfillmentStatus::CANCELLED();
                    $itemFulfillmentStatus = FulfillmentStatus::CANCELLED();
            } elseif ($item['fulfillment_status'] == 'not_eligible') {
                $itemFulfillmentStatus = FulfillmentStatus::PENDING();
                if ($item['requires_shipping']) {
                    $shipmentType = FulfillmentType::CASH_ON_DELIVERY();
                } else {
                    $shipmentType = FulfillmentType::NO_SHIPPING();
                }
            } else {
                throw new \Exception('Account Id|' . $this->account->id . 'Shopify has unsupported item status. New status' . $item['fulfillment_status'] . '| Order Id: ' . $order->id);
            }

            // @TODO - refund get from refunds
            $returnStatus = null;
            $costOfGoods = null;
            $actualShippingFee = 0;

            $itemData = $item;

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
                $trackingNumber,
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
        $general = ['providers'];

        // These are status specific in which they depend on the status of the order
        $statusSpecific = [];
        foreach ($order->items as $item) {
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
        }

        return array_merge($general, $statusSpecific);
    }

    /**
     * fulfillment
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fulfillment(Order $order, Request $request)
    {
        $selected = $request->input('selected', []);
        $trackingNumber = $request->input('tracking_number');
        $trackingCompany = $request->input('tracking_company');
        $trackingUrl = $request->input('tracking_url');
        $notifyCustomer = $request->input('notify_customer', false);
        $location_id = $order->data['location_id'] ?? '';
        if (empty($order->data['location_id'])) {
            $location_id = $this->getAssignedLocationId($order);
            if (empty($location_id)) {
                return $this->respondBadRequestError('There is no location set for the order');
            }
        }

        $parameters = [
            'fulfillment' => [
                'location_id' => $location_id,
                'tracking_number' => $trackingNumber,
                'tracking_company' => $trackingCompany,
                'tracking_url' => $trackingUrl,
                'notify_customer' => $notifyCustomer
            ]
        ];

        if (!empty($selected)) {
            $actualItems = $order->items()->whereIn('id', $selected)->get();

            if ($actualItems->count() != count($selected)) {
                return $this->respondBadRequestError('Invalid item selected');
            }

            if ($actualItems->count() != $order->items()->count()) {
                $parameters['fulfillment']['line_items'] = [];
                foreach ($actualItems as $actualItem) {
                    $parameters['fulfillment']['line_items'][] = [
                        'id' => $actualItem->external_id
                    ];
                }
            }
        }

        $response = $this->client->request('post', '/admin/api/2020-07/orders/' . $order->external_id . '/fulfillments.json', [RequestOptions::JSON => $parameters]);

        if ($response->getStatusCode() === 400) {
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->respondBadRequestError($result['error'] ?? implode('|', $result['errors']));
        } elseif ($response->getStatusCode() !== 201) {
            $result = json_decode($response->getBody()->getContents(), true);
            set_log_extra('code', $response->getStatusCode());
            set_log_extra('response', $response);
            set_log_extra('body', $result);
            $errorMsg = 'Unable to fulfill order for Shopify.';
            /**
             * 422 Unprocessable Entity
             * A 422 error code can be returned from a variety of scenarios including
             * a. Incorrectly formatted input
             * b. Fulfillment of an order that is already fulfilled.
             * c. Fulfillment of an order tah is  already refunded.
             */
            if ($response->getStatusCode() === 422) {
                if ($result['errors'] && $result['errors']['base']) {
                    $errors = implode('|', $result['errors']['base']);
                    $errorMsg .= $errors;
                    return $this->respondBadRequestError($errorMsg);
                }
            }
            throw new \Exception($errorMsg);
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Marks the items as being packed
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function print(Order $order, Request $request)
    {
        //
    }

    /**
     * Cancel order
     * Orders that have a fulfillment object can't be canceled.
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancel(Order $order, Request $request)
    {
        $selected = $request->input('selected', []);
        $note = $request->input('note');
        $reason = $request->input('reason');
        $amount = $request->input('amount');
        $email = $request->input('email', false);

        $parameters = [
            'note' => $note,
            'reason' => $reason,
            'email' => $email
        ];

        if (!empty($amount)) {
            $parameters['amount'] = $amount;
            $parameters['currency'] = $order['currency'];
        }

        if (!empty($selected)) {
            $actualItems = $order->items()->whereIn('id', $selected)->get();

            if ($actualItems->count() != count($selected)) {
                return $this->respondBadRequestError('Invalid item selected');
            }

            if ($actualItems->count() != $order->items()->count()) {
                $parameters['refund'] = [
                    'note' => $note,
                    'notify' => $email,
                    'shipping' => ['full_refund' => true],
                    'currency' => $order['currency'],
                    'refund_line_items' => []
                ];

                foreach ($actualItems as $actualItem) {
                    // Refund specified items
                    $parameters['refund']['refund_line_items'][] = [
                        'line_item_id' => $actualItem->external_id,
                        'quantity' => $actualItem->quantity,
                        'restock_type' => 'cancel',
                        'location_id' => $order->data['location_id']
                    ];
                }
            }
        }

        $response = $this->client->request('post', '/admin/api/2020-07/orders/' . $order->external_id . '/cancel.json', [RequestOptions::JSON => $parameters]);

        if ($response->getStatusCode() === 422) {
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->respondBadRequestError($result['error']);
        } elseif ($response->getStatusCode() !== 200) {
            set_log_extra('code', $response->getStatusCode());
            set_log_extra('response', $response);
            set_log_extra('body', json_decode($response->getBody()->getContents(), true));
            throw new \Exception('Unable to cancel order for Shopify');
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Calculate refund
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function calculateRefund(Order $order, Request $request)
    {
        $selected = $request->input('selected', []);
        $restock = $request->input('restock', true);
        $restock_type = $request->input('restock_type', 'return');

        if (empty($order->data['location_id'])) {
            return $this->respondBadRequestError('There is no location set for the order');
        }

        if (empty($selected)) {
            return $this->respondBadRequestError('You need to have at least one item selected.');
        }

        $parameters = [
            'refund' => [
                'currency' => $order['currency'] ?? $this->account->currency,
                'shipping' => [
                    'full_refund' => true
                ],
                'refund_line_items' => []
            ]
        ];

        $actualItems = $order->items()->whereIn('id', $selected)->get();

        if ($actualItems->count() != count($selected)) {
            return $this->respondBadRequestError('Invalid item selected');
        }

        foreach ($actualItems as $actualItem) {
            $parameters['refund']['refund_line_items'][] = [
                'line_item_id' => $actualItem->external_id,
                'quantity' => $actualItem->quantity,
                'restock_type' => $restock_type,
                'location_id' => $order->data['location_id'],
                'already_stocked' => !$restock
            ];
        }


        $response = $this->client->request('post', '/admin/api/2020-07/orders/' . $order->external_id . '/refunds/calculate.json', [RequestOptions::JSON => $parameters]);

        if ($response->getStatusCode() === 400) {
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->respondBadRequestError($result['error'] ?? implode('|', $result['errors']));
        } elseif ($response->getStatusCode() === 200) {
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->respond($result);
        } else {
            $result = json_decode($response->getBody()->getContents(), true);

            set_log_extra('code', $response->getStatusCode());
            set_log_extra('response', $response);
            set_log_extra('body', $result);
            throw new \Exception('Unable to calculate refund for Shopify');
        }
    }

    /**
     * Refund order
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refund(Order $order, Request $request)
    {
        $selected = $request->input('selected', []);
        $reason = $request->input('reason');
        $restock = $request->input('restock', true);
        $notify = $request->input('notify', true);
        $transactions = $request->input('transactions', true);
        $manualRefundAmount = $request->input('manual', true);

        $location_id = $order->data['location_id'] ?? '';

        if (empty($location_id)) {
            $location_id = $this->getAssignedLocationId($order);
            if (empty($location_id)) {
                return $this->respondBadRequestError('There is no location set for the order');
            }
        }

        $parameters = [
            'refund' => [
                'currency' => $order['currency'],
                'notify' => $notify,
                'note' => $reason,
                'shipping' => [
                    'full_refund' => true
                ],
                'refund_line_items' => [],
                'transactions' => []
            ]
        ];

        if (!empty($selected)) {
            $actualItems = $order->items()->whereIn('id', $selected)->get();
            if ($actualItems->count() != count($selected)) {
                return $this->respondBadRequestError('Invalid item selected');
            }

            $restockType = ($restock) ? 'return' : 'no_restock';
            // Refund specified items
            if ($actualItems->count() != $order->items()->count()) {
                foreach ($actualItems as $actualItem) {
                    $parameters['refund']['refund_line_items'][] = [
                        'line_item_id' => $actualItem->external_id,
                        'quantity' => $actualItem->quantity,
                        'restock_type' => $restockType,
                        'location_id' => $location_id
                    ];
                }
            }
        }
        // Order Transactions
        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                /* transactions object with "kind": "suggested_refund",
                        which must to be changed to "kind" : "refund" for the refund to be accepted.
                    **/
                if ($transaction['kind'] == 'suggested_refund') {
                    $parameters['refund']['transactions'][] = [
                        'parent_id' => $transaction['parent_id'],
                        'amount' => !empty($manualRefundAmount) && $manualRefundAmount <= $transaction['maximum_refundable'] ? $manualRefundAmount : $transaction['maximum_refundable'],
                        'kind' => 'refund',
                        'gateway' => $transaction['gateway']
                    ];
                }
            }
        }
        $response = $this->client->request('post', '/admin/api/2020-07/orders/' . $order->external_id . '/refunds.json', [RequestOptions::JSON => $parameters]);

        if ($response->getStatusCode() === 400) {
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->respondBadRequestError($result['error'] ?? implode('|', $result['errors']));
        } elseif ($response->getStatusCode() !== 201) {
            set_log_extra('response', $response);
            throw new \Exception('Unable to fulfill order for Shopify');
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }
    /**
     * Get assigned location id
     *
     * @param Order $order
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAssignedLocationId(Order $order)

    {
        $response = $this->client->request('get', '/admin/api/2020-07/orders/' . $order->external_id . '/fulfillment_orders.json');
        $response = json_decode($response->getBody()->getContents());
        if (!empty($response->fulfillment_orders)) {

            return $response->fulfillment_orders[0]->assigned_location_id;

        }
        return null;
    }
}
