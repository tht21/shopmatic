<?php

namespace App\Integrations\Lazada;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\IntegrationSyncData;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\Lazada\Constant;
use App\Integrations\TransformedAddress;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Order;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'order_id' => $externalId
        ];
        $response = $this->client->request('get', '/order/get', $parameters);

        if (empty($response['code'])) {
            $order = $response['data'];
            try {
                $itemResponse = $this->client->request('get', '/order/items/get', $parameters);
                if (empty($response['code'])) {
                    try {
                        $order['items'] = $itemResponse['data'];
                        $order = $this->transformOrder($order);
                    } catch (\Exception $e) {
                        set_log_extra('order', $order);
                        set_log_extra('items_response', $itemResponse);
                        throw $e;
                    }
                } else {
                    set_log_extra('response', $response);
                    set_log_extra('items_response', $itemResponse);
                    set_log_extra('order', $order);
                    throw new \Exception('Unable to retrieve order items for Lazada');
                }
            } catch (\Exception $e) {
                set_log_extra('order', $order);
                throw $e;
            }
            return $this->handleOrder($order, $options);
        } else {
            set_log_extra('response', $response);
            throw new \Exception('Unable to retrieve order for Lazada');
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

        $parameters = [
            'created_after' => date(DATE_ISO8601, strtotime(now()->subYear())),
            'sort_by' => 'created_at',
            'sort_direction' => 'ASC',
            'limit' => 100,
            'offset' => 0,
        ];
        $this->fetchOrders($options, $parameters);
    }

    /**
     * Incremental order sync
     *
     * @return mixed
     * @throws \Exception
     */
    public function sync()
    {
        $options['import'] = false;
        $options['deduct'] = $this->account->hasFeature(['orders', 'deduct_inventory']);

        $parameters = [
            'update_after' => $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now(), true)->format(DateTime::ISO8601),
            'sort_by' => 'created_at',
            'sort_direction' => 'ASC',
            'limit' => 100,
            'offset' => 0,
        ];
        $debugLog = '[Lazada Order Sync]Debug Log|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id;
        Log::info($debugLog);
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
        do {
            $orders = [];

            
            //This is put outside as it the integration fails, we don't want it to rollback
            $response = $this->client->request('get', '/orders/get', $parameters);
            if (empty($response['code'])) {
                $response = $response['data'];
                $orders = $response['orders'] ?? [];

                $items = [];
                $orderIds = [];

                // Grouping all the IDs so we can fetch all the items for these orders
                foreach ($orders as $order) {
                    $orderIds[] = $order['order_id'];
                }

                if (empty($orderIds)) {
                    return;
                }

                // Maximum retry 2 times to get order items, sometime lazada api will have Unexpected internal error
                for ($current = 1; $current <= 2; $current++) {
                    $itemsResponse = $this->client->request('get', '/orders/items/get', [
                        'order_ids' => json_encode($orderIds)
                    ]);
                    // Unexpected internal error will have code 6
                    if ($itemsResponse['code'] !== 6) {
                        break;
                    }
                }

                if (empty($itemsResponse['code'])) {
                    foreach ($itemsResponse['data'] as $data) {
                        $items[$data['order_id']] = $data['order_items'];
                    }
                } else {
                    set_log_extra('response', $response);
                    set_log_extra('items_response', $itemsResponse);
                    set_log_extra('order_ids', $orderIds);
                    set_log_extra('orders', $orders);
                    throw new \Exception('Unable to retrieve orders items for Lazada');
                }

                foreach ($orders as $order) {
                    if (!array_key_exists($order['order_id'], $items)) {
                        set_log_extra('items', $items);
                        set_log_extra('orders', $orders);
                        set_log_extra('order', $order);
                        throw new \Exception('Order items missing for Lazada');
                    }
                    try {
                        $order = $this->transformOrder($order + ['items' => $items[$order['order_id']]]);
                    } catch (\Exception $e) {
                        set_log_extra('order', $order);
                        throw $e;
                    }
                    $this->handleOrder($order, $options);
                }
                $parameters['offset'] += count($orders);
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to retrieve orders for Lazada');
            }
        } while (count($orders) != 0);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $externalId = $order['order_id'];
        $externalNumber = $order['order_number'];
        $externalSource = $this->account->integration->name;

        $customerName = $order['customer_first_name'] . ' ' . $order['customer_last_name'];
        $debugLog = '[Lazada transformOrder Items]Debug Log|Account Id|'.$this->account->id.'|Shop Id|'.$this->account->shop_id.'|Integration Id|'.$this->account->integration_id.'|Account Name|'.$this->account->name.'|Region|'.$this->account->region_id.'|Customer Name|'.$customerName.'|External Order Id|'.$externalId.'| External Order Number'.$externalNumber.'|External Source|'.$externalSource;
        Log::info($debugLog);
        // Lazada no longer provides this
        $customerEmail = null;

        $shipping = $order['address_shipping'];

        // format address function
        $formatAddress = function ($data, $phone) {
            return new TransformedAddress(
                null,
                $data['first_name'] . ' ' . $data['last_name'],
                $data['address1'],
                $data['address2'],
                $data['address3'],
                $data['address4'],
                $data['address5'],
                $data['city'],
                $data['post_code'],
                null,
                $data['country'],
                $phone
            );
        };

        // To remove any if it's empty
        $phone = array_filter([$shipping['phone'], $shipping['phone2']]);
        $shippingAddress = $formatAddress($shipping, $phone);
        $billing = $order['address_billing'];
        // To remove any if it's empty
        $phone = array_filter([$billing['phone'], $billing['phone2']]);
        $billingAddress = $formatAddress($billing, $phone);

        //TODO: Check if this works
        if (!empty($order['promised_shipping_time'])) {
            $shipByDate = Carbon::createFromFormat('Y-m-d H:i:s', $order['promised_shipping_time']);
        } else {
            $shipByDate = null;
        }

        $paymentStatus = PaymentStatus::PAID();
        if (isset($order['statuses']) && !is_array($order['statuses'])) {
            $statuses = explode(',', $order['statuses']);
        } else {
            $statuses = $order['statuses'] ?? '';

            if ($statuses) {
                // This is so it's easier for us to manipulate / check against below
                $order['statuses'] = implode(',', $order['statuses']);
            }
        }

        if (in_array('unpaid', $statuses)) {
            $paymentStatus = PaymentStatus::UNPAID();
        }

        $currency = $this->account->currency;

        $integrationDiscount = $order['voucher_platform'] ?? 0;
        $sellerDiscount = $order['voucher_seller'] ?? 0;
        $shippingFee = filter_var($order['shipping_fee'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);;
        $tax = 0;
        $tax2 = 0;
        $commission = 0;
        $transactionFee = 0;
        $totalVoucher = filter_var($order['voucher'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $subtotal = filter_var($order['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $grandTotal = $subtotal + $shippingFee;

        /*
         * buyer paid need to deduct the voucher
         * eg - grand total 1.50
         * if buyer use voucher 0.50 then need to deduct from the grand total
         * which balance is 1 will be paid by the buyer
         * https://prnt.sc/tjhs2v
         * */
        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $subtotal - $totalVoucher + $shippingFee : 0;

        $settlementAmount = 0;

        $paymentMethod = $order['payment_method'];

        $fulfillmentType = FulfillmentType::REQUIRES_SHIPPING();

        if (substr_count($order['statuses'], 'pending') || substr_count($order['statuses'], 'unpaid')) {
            $fulfillmentStatus = FulfillmentStatus::PENDING();
        } elseif (substr_count($order['statuses'], 'shipped') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::SHIPPED();
        } elseif (substr_count($order['statuses'], 'delivered') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::DELIVERED();
        } elseif (substr_count($order['statuses'], 'canceled') === count($statuses) || substr_count($order['statuses'], 'cancelled') === count($statuses) || substr_count($order['statuses'], 'failed') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::CANCELLED();
        } elseif (substr_count($order['statuses'], 'ready_to_ship') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
        } elseif (substr_count($order['statuses'], 'returned') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::RETURNED();
        } elseif (substr_count($order['statuses'], 'LOST_BY_3PL') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::LOST();
        } elseif (substr_count($order['statuses'], 'packed') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::PACKED();
        } elseif (substr_count($order['statuses'], 'repacked') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::REPACKED();
        } elseif (substr_count($order['statuses'], 'ready_to_ship_pending') === count($statuses)) {
            $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP_PENDING();
        } elseif (substr_count($order['statuses'], 'damaged_by_3pl') === count($statuses) ) {
            $fulfillmentStatus = FulfillmentStatus::DAMAGED();
        } else {
            $pending = 0;
            $cancelled = 0;
            $returned = 0;
            $delivered = 0;
            $shipped = 0;
            $lost = 0;
            $rts = 0;
            $packed = 0;
            $repacked = 0;
            $rtsp = 0;
            $damaged = 0;
            foreach ($statuses as $status) {
                if ($status === 'pending') {
                    $pending++;
                } elseif ($status === 'ready_to_ship') {
                    $rts++;
                } elseif ($status === 'shipped') {
                    $shipped++;
                } elseif ($status === 'delivered') {
                    $delivered++;
                } elseif ($status === 'returned') {
                    $returned++;
                } elseif ($status === 'canceled' || $status === 'failed') {
                    $cancelled++;
                } elseif ($status === 'LOST_BY_3PL' || $status === 'lost_by_3pl') {
                    $lost++;
                } elseif ($status === 'packed') {
                    $packed++;
                } elseif ($status === 'repacked') {
                    $repacked++;
                } elseif ($status === 'ready_to_ship_pending') {
                    $rtsp++;
                } elseif ($status === 'damaged_by_3pl') {
                    $damaged++;
                }
            }
            if ($pending) {
                $fulfillmentStatus = FulfillmentStatus::PENDING();
            } elseif ($rts) {
                $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
            } elseif ($shipped) {
                $fulfillmentStatus = FulfillmentStatus::SHIPPED();
            } elseif ($delivered) {
                $fulfillmentStatus = FulfillmentStatus::DELIVERED();
            } elseif ($cancelled) {
                $fulfillmentStatus = FulfillmentStatus::CANCELLED();
            } elseif ($returned) {
                $fulfillmentStatus = FulfillmentStatus::RETURNED();
            } elseif ($lost) {
                $fulfillmentStatus = FulfillmentStatus::LOST();
            } elseif ($packed) {
                $fulfillmentStatus = FulfillmentStatus::PACKED();
            } elseif ($repacked) {
                $fulfillmentStatus = FulfillmentStatus::REPACKED();
            } elseif ($rtsp) {
                $fulfillmentStatus = FulfillmentStatus::READY_TO_SHIP_PENDING();
            } elseif ($damaged) {
                $fulfillmentStatus = FulfillmentStatus::DAMAGED();
            } else {
                set_log_extra('statuses', $statuses);
                set_log_extra('order', $order);
                throw new \Exception('Lazada has different fulfilment statuses');
            }
        }

        $buyerRemarks = $order['remarks'];

        if (!empty($order['delivery_info'])) {
            $buyerRemarks .= ' -- Delivery Info: ' . $order['delivery_info'];
        }

        if (!empty($order['gift_option'])) {
            $buyerRemarks .= ' -- Is Gift: Yes';
        }

        if (!empty($order['gift_message'])) {
            $buyerRemarks .= ' -- Gift Message: ' . $order['gift_message'];
        }

        $type = OrderType::NORMAL();

        $orderPlacedAt = Carbon::createFromFormat('Y-m-d H:i:s T', $order['created_at']);
        $orderUpdatedAt = Carbon::createFromFormat('Y-m-d H:i:s T', $order['updated_at']);

        if ($paymentStatus->equals(PaymentStatus::PAID())) {

            // We can't check this reliably, so we just use the created timestamp
            $orderPaidAt = Carbon::createFromFormat('Y-m-d H:i:s T', $order['created_at']);
        } else {
            $orderPaidAt = null;
        }

        $data = [
            'total_voucher' => $order['voucher'],
            'voucher_code' => $order['voucher_code'],
            'branch_number' => $order['branch_number'] ?? null,
            'tax_code' => $order['tax_code'] ?? null,
            'national_registration_number' => $order['national_registration_number']
        ];

        $data[] = ['extra_attributes' => $order['extra_attributes']];

        $items = [];
        foreach ($order['items'] as $item) {

            $itemExternalId = $item['order_item_id'];
            $itemName = $item['name'];
            $externalProductId = substr(strstr($item['shop_sku'], '-'), 1);
            $sku = $item['sku'];
            $variationName = $item['variation'] ?? 'N/A';
            $variationSku = $sku;

            // Lazada splits all order items into 1 quantity
            $quantity = 1;

            $itemPrice = $item['item_price'];

            $itemIntegrationDiscount = $item['voucher_platform'];
            $itemSellerDiscount = $item['voucher_seller'];

            $itemShippingFee = $item['shipping_amount'];

            $itemTax = $item['tax_amount'];
            $itemTax2 = 0;

            $itemGrandTotal = $item['item_price'];
            $itemBuyerPaid = $item['paid_price'];

            $itemStatus = trim(strtolower($item['status']));
            if ($itemStatus === 'pending') {
                $itemFulfillmentStatus = FulfillmentStatus::PENDING();
            } elseif ($itemStatus === 'delivered' || $itemStatus === 'completed') {
                $itemFulfillmentStatus = FulfillmentStatus::DELIVERED();
            } elseif ($itemStatus === 'shipped') {
                $itemFulfillmentStatus = FulfillmentStatus::SHIPPED();
            } elseif ($itemStatus === 'canceled' || $itemStatus === 'cancelled' || $itemStatus === 'failed') {
                $itemFulfillmentStatus = FulfillmentStatus::CANCELLED();
                $paymentStatus = PaymentStatus::CANCELLED();
            } elseif ($itemStatus === 'ready_to_ship') {
                $itemFulfillmentStatus = FulfillmentStatus::READY_TO_SHIP();
            } elseif ($itemStatus === 'returned') {
                $itemFulfillmentStatus = FulfillmentStatus::RETURNED();
            } elseif (strtolower($itemStatus) === 'lost_by_3pl') {
                $itemFulfillmentStatus = FulfillmentStatus::LOST();
            } elseif (strtolower($itemStatus) === 'unpaid') {
                $itemFulfillmentStatus = FulfillmentStatus::UNPAID();
            } elseif (strtolower($itemStatus) === 'packed') {
                $itemFulfillmentStatus = FulfillmentStatus::PACKED();
            } elseif (strtolower($itemStatus) === 'repacked') {
                $itemFulfillmentStatus = FulfillmentStatus::REPACKED();
            } elseif (strtolower($itemStatus) === 'ready_to_ship_pending') {
                $itemFulfillmentStatus = FulfillmentStatus::READY_TO_SHIP_PENDING();
            } elseif (strtolower($itemStatus) === 'shipped_back_success') {
                $itemFulfillmentStatus = FulfillmentStatus::RETURNED();
            } elseif (strtolower($itemStatus) === 'shipped_back_failed') {
                $itemFulfillmentStatus = FulfillmentStatus::RETURNED_FAILED();
            } elseif (strtolower($itemStatus) === 'damaged_by_3pl') {
                $itemFulfillmentStatus = FulfillmentStatus::DAMAGED();
            } elseif (strtolower($itemStatus) === 'failed') {
                $itemFulfillmentStatus = FulfillmentStatus::PAID();
            } else {
                set_log_extra('status', $itemStatus);
                set_log_extra('order', $order);
                set_log_extra('item', $item);
                throw new \Exception('Lazada has unsupported item status');
            }

            $shipmentProvider = $item['shipment_provider'];
            $shipmentType = $item['shipping_type'];
            $shipmentMethod = $item['shipping_provider_type'];
            $trackingNumber = $item['tracking_code'];

            // As Lazada doesn't have any status changes, we need to change it here so we can reflect the actions
            if ($itemFulfillmentStatus->equals(FulfillmentStatus::PENDING()) && !empty($shipmentProvider) && !empty($trackingNumber)) {
                $itemFulfillmentStatus = FulfillmentStatus::PROCESSING();
            }

            $returnStatus = null;
            if (!empty($item['return_status'])) {
                set_log_extra('return_status', $item['return_status']);
                set_log_extra('order', $order);
                set_log_extra('item', $item);
                throw new \Exception('Lazada has unsupported return_status');
            }
            $costOfGoods = null;
            $actualShippingFee = $item['shipping_service_cost'];

            $itemData = [
                'digital_delivery_info' => $item['digital_delivery_info'],
                'reason' => $item['reason'],
                'invoice_number' => $item['invoice_number'],
                'currency' => $item['currency'],
                'order_flag' => $item['order_flag'],
                'sla_time_stamp' => $item['sla_time_stamp'],
                'is_digital' => $item['is_digital'],
                'package_id' => $item['package_id'],
                'order_type' => $item['order_type'],
                'stage_pay_status' => $item['stage_pay_status'],
                'cancel_return_initiator' => $item['cancel_return_initiator'],
                'shop_sku' => $item['shop_sku'],
                'pick_up_store_info' => $item['pick_up_store_info'] ?? null,
                'warehouse_code' => $item['warehouse_code'] ?? null,
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

        /*TaxInvoiceRequested is not accurate*/
        // check if order require user to manually set invoice number
        /*$setInvoice = false;
        $extraAttributes = json_decode($order->data[0]['extra_attributes']);
        if (!isset($extraAttributes->TaxInvoiceRequested) || $extraAttributes->TaxInvoiceRequested) {
            $setInvoice = true;
        }*/

        foreach ($order->items as $item) {
            if ($item->fulfillment_status === FulfillmentStatus::PENDING()->getValue()) {
                if (!in_array('pack', $statusSpecific)) {
                    $statusSpecific[] = 'pack';
                    $statusSpecific[] = 'cancel';
                    $statusSpecific[] = 'reasons';
                }
            } elseif ($item->fulfillment_status > FulfillmentStatus::PENDING()->getValue() && $item->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
                if (!in_array('print', $statusSpecific)) {
                    $statusSpecific[] = 'print';
                }

                if ((!isset($item->data['invoice_number']) || empty($item->data['invoice_number']))) {
                    if (!in_array('setInvoiceNumber', $statusSpecific)) {
                        $statusSpecific[] = 'setInvoiceNumber';
                    }
                }

                if (!in_array('cancel', $statusSpecific)) {
                    $statusSpecific[] = 'cancel';
                    $statusSpecific[] = 'reasons';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue() || $item->fulfillment_status === FulfillmentStatus::PACKED()->getValue() || $item->fulfillment_status === FulfillmentStatus::READY_TO_SHIP_PENDING()->getValue()) {
                if (!in_array('rts', $statusSpecific)) {
                    $statusSpecific[] = 'rts';
                }
                if (!in_array('cancel', $statusSpecific)) {
                    $statusSpecific[] = 'cancel';
                    $statusSpecific[] = 'reasons';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue()) {
                if (!in_array('cancel', $statusSpecific)) {
                    $statusSpecific[] = 'cancel';
                    $statusSpecific[] = 'reasons';
                }
                if (!in_array('delivered', $statusSpecific)) {
                    $statusSpecific[] = 'delivered';
                    $statusSpecific[] = 'failedDelivery';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::SHIPPED()->getValue()) {
                if (!in_array('verifyPincode', $statusSpecific)) {
                    $statusSpecific[] = 'verifyPincode';
                }
            }
        }

        return array_merge($general, $statusSpecific);
    }

    /**
     * Retrieves all the shipment providers available for the order
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function providers(Order $order, Request $request)
    {
        // Daily as we never know that an account might have different shipment providers / change often
        return Cache::remember('lazada-account-' . $this->account->id . '-providers', 60 * 60 * 24 * 1, function () {
            $response = $this->client->request('get', '/shipment/providers/get');

            if (empty($response['code'])) {
                $data = $response['data'];

                return $data['shipment_providers'];
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to retrieve shipment providers for Lazada');
            }
        });
    }

    /**
     * Retrieves all the cancellation reasons for the order
     *
     * @param Order $order
     * @param Request $request
     *
     * @return mixed
     */
    public function reasons(Order $order, Request $request)
    {
        // Remember for a week as it shouldn't change
        return Cache::remember('lazada-' . $this->account->id . '-' . $order->id . '-reasons', 60 * 60 * 24 * 7, function () use ($order) {
            $orderId = $order->id ?? '';
            $actualItems = [];
            if ($orderId) {
                $actualItems = $order->items()->where('order_id', $order->id)->pluck('external_id')->toArray();
            }
            $orderExternalId = $order->external_id ?? '';
            $parameters = [
                'order_id' => $orderExternalId,
                'order_item_id_list' => '[' . implode(',', $actualItems) . ']'
            ];
            $response = $this->client->request('get', '/order/reverse/cancel/validate', $parameters);
            if (empty($response['code'])) {
                return $response['data']['reason_options'];
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to retrieve failure reasons for Lazada');
            }
        });
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
    public function pack(Order $order, Request $request)
    {
        $items = $request->input('order_item_ids');
        if (empty($items)) {
            return $this->respondBadRequestError('You need to have at least one order_item_ids.');
        }
        $actualItems = $order->items()->whereIn('id', $items)->get();

        if ($actualItems->count() != count($items)) {
            return $this->respondBadRequestError('Invalid order_item_ids');
        }

        foreach ($actualItems as $item) {
            // Can only pack order if the item status is pending or ready to ship or repacked
            if (!FulfillmentStatus::PENDING()->same($item->fulfillment_status) && !FulfillmentStatus::READY_TO_SHIP()->same($item->fulfillment_status) && !FulfillmentStatus::REPACKED()->same($item->fulfillment_status)) {
                return $this->respondBadRequestError('Order item status not pending or ready to ship!');
            }
        }

        $provider = $request->input('provider');

        if (empty($provider)) {
            $provider = 'Aramax';
        }

        $parameters = [
            'order_item_ids' => json_encode($actualItems->pluck('external_id')->toArray()),
            'delivery_type' => 'dropship',
            'shipping_provider' => $provider,
        ];

        $response = $this->client->request('POST', '/order/pack', $parameters);

        if (empty($response['code'])) {

            // This is to refetch the order and get a fully updated order
            $this->get($order->external_id, ['deduct' => false]);

            return true;
        } elseif ($response['code'] == '73' || $response['code'] == '82') {

            // This is if the status of the items is not pending. (API documentation says 73, but response from a cancelled order gives 82)

            $this->get($order->external_id, ['deduct' => false]);

            set_log_extra('response', $response);
            set_log_extra('order', $order);
            set_log_extra('parameters', $parameters);
            Log::error('Status of item not pending.');
            set_log_extra('response', $response);
            return $this->respondBadRequestError('There was a problem updating this order. Please refresh the order!');
        } else {
            $message = $response['message'] ?? 'Unable to pack order for Lazada';
            set_log_extra('response', $response);
            return $this->respondBadRequestError($message);
        }
    }

    /**
     * Marks the items as ready to ship
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function rts(Order $order, Request $request)
    {
        $items = $request->input('order_item_ids');
        if (empty($items)) {
            return $this->respondBadRequestError('You need to have at least one order_item_ids.');
        }
        $actualItems = $order->items()->whereIn('id', $items)->get();

        if ($actualItems->count() != count($items)) {
            return $this->respondBadRequestError('Invalid order_item_ids');
        }

        // if item pickup_in_store => set default  shipment_provider = "Aramax"
        $actualItems = $actualItems->map( function ($item) {
            if (empty($item->shipment_provider)) {
                $item->shipment_provider = "Aramax";
                return $item;
            }
            return $item;
        });

        $pickup = false;

        foreach ($actualItems as $item) {
            // Can only pack order if the item status is pending or ready to ship or repacked
            if (!FulfillmentStatus::PROCESSING()->same($item->fulfillment_status) && !FulfillmentStatus::PACKED()->same($item->fulfillment_status) && !FulfillmentStatus::READY_TO_SHIP_PENDING()->same($item->fulfillment_status)) {
                return $this->respondBadRequestError('Order item status not pending or ready to ship!');
            }
            if ($item->shipment_method == Constant::SHIPMENT_METHOD_PICKUP_IN_STORE) {
                $pickup = true;
                continue;
            }
            if (empty($item->shipment_provider) || empty($item->tracking_number)) {
                set_log_extra('item', $item);
                set_log_extra('order', $order);
                set_log_extra('order_item_ids', $items);
                Log::error('Unable to change to RTS for Lazada because shipment provider or tracking number is empty.');
                return $this->respondBadRequestError('Unable to mark as RTS because the shipment provider and/or tracking number is invalid.');
            }
        }

        /*if ($pickup) {
            $parameters = [
                'order_item_ids' => json_encode($actualItems->pluck('external_id')->toArray()),
                'delivery_type' => 'dropship',
                'shipping_provider' => 'pickup_in_store',
            ];

            $response = $this->client->request('POST', '/order/pack', $parameters);

            if ($response['code'] == '73' || $response['code'] == '82') {

                // This is if the status of the items is not pending. (API documentation says 73, but response from a cancelled order gives 82)
                $this->get($order->external_id, ['deduct' => false]);

                set_log_extra('response', $response);
                set_log_extra('order', $order);
                set_log_extra('parameters', $parameters);
                Log::error('Status of item not pending.');
                set_log_extra('response', $response);
                return $this->respondBadRequestError('There was a problem updating this order. Please refresh the order!');
            } elseif (!empty($response['code'])) {
                $message = $response['message'] ?? 'Unable to pack order for Lazada';
                set_log_extra('response', $response);
                return $this->respondBadRequestError($message);
            }
        }*/

        // We're looping it because the API call only supports one tracking number but there might be two different API number
        foreach ($actualItems as $item) {
            $parameters = [
                'order_item_ids' => json_encode($actualItems->pluck('external_id')->toArray()),
                'delivery_type' => 'dropship',
                'shipment_provider' => $item->shipment_provider ?? '',
                'tracking_number' => $item->tracking_number,
            ];

            $response = $this->client->request('POST', '/order/rts', $parameters);

            if ($response['code'] == '73' || $response['code'] == '82') {

                // This is if the status of the items is not pending. (API documentation says 73, but response from a cancelled order gives 82)

                $this->get($order->external_id, ['deduct' => false]);

                set_log_extra('response', $response);
                set_log_extra('order', $order);
                set_log_extra('parameters', $parameters);
                set_log_extra('response', $response);
                Log::error('Status of item not pending.');
                return $this->respondBadRequestError('There was a problem updating this order. Please refresh the order!');
            } elseif (!empty($response['code'])) {
                set_log_extra('response', $response);
                Log::error('Unable to mark items as RTS for Lazada. ' . $response['message']);
                return $this->respondInternalError($response['message']);
            }
        }

        // Here means it's successful with no errors

        $this->get($order->external_id, ['deduct' => false]);
        return true;
    }

    /**
     * orders is delivered
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delivered(Order $order, Request $request)
    {
        $items = $request->input('order_item_ids');
        if (empty($items)) {
            return $this->respondBadRequestError('You need to have at least one order_item_ids.');
        }
        $actualItems = $order->items()->whereIn('id', $items)->get();

        if ($actualItems->count() != count($items)) {
            return $this->respondBadRequestError('Invalid order_item_ids');
        }

        $parameters = [
            'order_item_ids' => json_encode($actualItems->pluck('external_id')->toArray()),
        ];

        $response = $this->client->request('POST', '/order/sof/delivered', $parameters);

        if (empty($response['code'])) {

            // This is to refetch the order and get a fully updated order
            $this->get($order->external_id, ['deduct' => false]);

            return true;
        } else {
            if(str_starts_with($response['message'], 'E1007')) {
                return $this->respondBadRequestError('Only logistics can set status of this order.');
            }
            
            $message = $response['message'] ?? 'Unable to set status delivered for Lazada';
            set_log_extra('response', $response);
            return $this->respondBadRequestError($message);
        }
    }


    /**
     * orders is failed delivered
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function failedDelivery(Order $order, Request $request)
    {
        $items = $request->input('order_item_ids');
        if (empty($items)) {
            return $this->respondBadRequestError('You need to have at least one order_item_ids.');
        }
        $actualItems = $order->items()->whereIn('id', $items)->get();

        if ($actualItems->count() != count($items)) {
            return $this->respondBadRequestError('Invalid order_item_ids');
        }

        $parameters = [
            'order_item_ids' => json_encode($actualItems->pluck('external_id')->toArray()),
        ];

        $response = $this->client->request('POST', '/order/sof/failed_delivery', $parameters);

        if (empty($response['code'])) {

            // This is to refetch the order and get a fully updated order
            $this->get($order->external_id, ['deduct' => false]);

            return true;
        } else {
            $message = $response['message'] ?? 'Unable to set status failed delivered for Lazada';
            set_log_extra('response', $response);
            return $this->respondBadRequestError($message);
        }
    }

    /**
     * Get item document
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function print($orders, Request $request)
    {
        $isBulk = $request->get('is_bulk', false);

        $allOrders = [];
        // For bulk orders
        if ($isBulk) {
            foreach ($orders as $key => $order) {
                $order = Order::whereId($order)->first();
                $order['actual_items'] = $order->items;

                $allOrders[] = $order;
            }
        } else {
            // For single order
            $items = $request->input('order_item_ids');
            if (empty($items)) {
                return $this->respondBadRequestError('You need to have at least one order_item_ids.');
            }
            $actualItems = $orders->items()->whereIn('id', $items)->get();

            if ($actualItems->count() != count($items)) {
                return $this->respondBadRequestError('Invalid order_item_ids');
            }

            $orders['actual_items'] = $actualItems;
            $allOrders[] = $orders;
        }

        $docType = $request->input('document');

        if (empty($docType)) {
            return $this->respondBadRequestError('You need to specify the document.');
        }

        $documents = [];
        foreach ($allOrders as $allOrder) {
            $orderItemIds = $allOrder->actual_items->pluck('external_id')->toArray();
            $orderItemIds = array_map('intval', $orderItemIds);
            $parameters = [
                'order_item_ids' => json_encode($orderItemIds),
                'doc_type' => $docType,
            ];

            $response = $this->client->request('GET', '/order/document/get', $parameters);

            if (empty($response['code'])) {
                $documents[] = $response['data']['document'];
            } elseif ($response['code'] == '34') {

                // This is if the status of the items is not packed
                $this->get($allOrder->external_id, ['deduct' => false]);

                set_log_extra('response', $response);
                set_log_extra('order', $allOrder);
                set_log_extra('parameters', $parameters);
                Log::error('Status of item not packed.');
                return $this->respondBadRequestError('There was a problem updating this order. Please refresh order!');
            } elseif ($response['code'] == '32') {
                return $this->respondBadRequestError('The document type ' . $docType . ' is invalid.');
            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to retrieve document for Lazada');
            }
        }

        $result = null;
        if (!empty($documents)) {
            $file = '';
            foreach ($documents as $document) {
                $file .= '<div style="page-break-before: always;" class="la-print-page print-page">' . base64_decode($document['file']) . '</div>';
            }
            $result = [
                'document_type' => $docType,
                'file' => $file,
            ];
        }
        return $this->respond($result);
    }

    /**
     * Cancel order
     *
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancel(Order $order, Request $request)
    {
        $items = $request->input('order_item_ids');
        if (empty($items)) {
            return $this->respondBadRequestError('You need to have at least one order_item_ids.');
        }
        $actualItems = $order->items()->whereIn('id', $items)->get();

        if ($actualItems->count() != count($items)) {
            return $this->respondBadRequestError('Invalid order_item_ids');
        }

        $reasonId = $request->input('reason_id');

        if (empty($reasonId)) {
            return $this->respondBadRequestError('You need to specify the reason.');
        }

        foreach ($actualItems as $actualItem) {
            $parameters = [
                'order_item_id' => $actualItem->external_id,
                'reason_id' => $reasonId,
            ];
            if ($detail = $request->input('reason_detail')) {
                $parameters['reason_detail'] = $detail;
            }

            $response = $this->client->request('POST', '/order/cancel', $parameters);

            if ($response['code'] == '28') {

                // This is if the status of the items is not packed
                $this->get($order->external_id, ['deduct' => false]);

                set_log_extra('response', $response);
                set_log_extra('order', $order);
                set_log_extra('parameters', $parameters);
                Log::error('Unable to cancel order.');
                return $this->respondBadRequestError('There was a cancelling the item. Please refresh order!');
            } elseif ($response['code'] == '22') {
                set_log_extra('response', $response);
                set_log_extra('order', $order);
                set_log_extra('parameters', $parameters);
                Log::error('Reason ID is invalid.');
                return $this->respondBadRequestError('The reason id ' . $reasonId . ' is invalid.');
            } elseif (!empty($response['code'])) {
                return $this->respondBadRequestError('Unable to cancel order for Lazada. ' . $response['message'] ?? '');
            }
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Set order's invoice number
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function setInvoiceNumber(Order $order, Request $request)
    {
        $items = $request->input('order_item_ids');
        if (empty($items)) {
            return $this->respondBadRequestError('You need to have at least one order_item_ids.');
        }
        $actualItems = $order->items()->whereIn('id', $items)->get();

        if ($actualItems->count() != count($items)) {
            return $this->respondBadRequestError('Invalid order_item_ids');
        }

        $invoiceNumber = $request->input('invoice_number');

        if (empty($invoiceNumber)) {
            return $this->respondBadRequestError('You need to enter invoice number.');
        }

        foreach ($actualItems as $actualItem) {
            $parameters = [
                'order_item_id' => $actualItem->external_id,
                'invoice_number' => $invoiceNumber,
            ];

            $response = $this->client->request('POST', '/order/invoice_number/set', $parameters);


            if (!isset($response['code']) || $response['code'] != 0) {

                $message = $response['message'] ?? 'Unable to set invoice number for Lazada';
                set_log_extra('response', $response);
                return $this->respondBadRequestError($message);
            }
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }

    /**
     * Verify order's pincode for pickup
     *
     * @param Order $order
     * @param Request $request
     * @return bool|mixed
     * @throws \Exception
     */
    public function verifyPincode(Order $order, Request $request)
    {

        $pincode = $request->input('pincode');

        if (empty($pincode)) {
            return $this->respondBadRequestError('You need to enter a pincode.');
        }
        $parameters = [
            'code' => 'lazada://storepickup?code=' . $pincode
        ];
        $response = $this->client->request('POST', '/eticket/code/query', $parameters);
        if (!isset($response['code']) || $response['code'] != 0) {
            $message = $response['message'] ?? 'Unable to redeem items';
            set_log_extra('response', $response);
            return $this->respondBadRequestError($message);
        }
        if (!isset($response['data']) || $response['data']['code_status'] != 1) {

            if (isset($response['data']['code_status'])) {
                if ($response['data']['code_status'] == -1) {
                    return $this->respondBadRequestError('This order has already been redeemed.');
                } elseif ($response['ret_body']['code_status'] == -5) {
                    return $this->respondBadRequestError('The order pickup has already expired.');
                } else {
                    $message = 'Unknown status from Lazada.';
                }
            } else {
                $message = 'Unable to redeem items.';
            }
            set_log_extra('response', $response);
            return $this->respondBadRequestError($message);
        }
        $orderId = $response['data']['trade_order_id'];
        $outerId = $response['data']['outer_id'];
        $serialNum = $response['data']['serial_num'] ?? null;
        if ($orderId != $order->external_id) {
            return $this->respondBadRequestError('This pincode is not for this order. It is for ' . $orderId);
        }
        if (!empty($serialNum)) {
            return $this->respondBadRequestError('The order has already been redeemed.');
        }
        $parameters = [
            'biz_type' => 5107,
            'serial_num' => $order->id,
            'outer_id' => $outerId,
            'consume_num' => 1,
            'code' => $pincode
        ];
        $response = $this->client->request('POST', '/eticket/code/consume', $parameters);
        if (!isset($response['code']) || $response['code'] != 0) {

            $message = $response['message'] ?? 'Unable to redeem the order.';
            set_log_extra('response', $response);
            return $this->respondBadRequestError($message);
        }

        $this->get($order->external_id, ['deduct' => false]);

        return true;
    }
}
