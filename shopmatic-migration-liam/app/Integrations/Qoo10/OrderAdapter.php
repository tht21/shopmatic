<?php

namespace App\Integrations\Qoo10;

use App\Constants\FulfillmentStatus;
use App\Constants\FulfillmentType;
use App\Constants\IntegrationSyncData;
use App\Constants\OrderType;
use App\Constants\PaymentStatus;
use App\Constants\ProductIdentifier;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\TransformedAddress;
use App\Integrations\TransformedOrder;
use App\Integrations\TransformedOrderItem;
use App\Models\Order;
use App\Models\ProductListing;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Models\Integration;
use App\Jobs\SyncInventory;

class OrderAdapter extends AbstractOrderAdapter
{
    /**
     * Get single order
     *
     * @param string $externalId
     * @param array|bool[] $options
     *
     * @return bool
     * @throws \Throwable
     */
    public function get($externalId, $options = ['deduct' => true])
    {
        try {
            $rawData = $this->getOrder($externalId);

            // qoo10 get order API has lesser data than get orders API
            /** @var Order $orderRecord */
            if ($orderRecord = $this->account->orders()->where('external_id', $externalId)->first()) {
                $rawData['Addr1'] = $orderRecord->shipping_address['address1'];
                $rawData['Addr2'] = $orderRecord->shipping_address['address2'];
                $rawData['SellerDiscount'] = $rawData['Cart_Discount_Seller'] > 0 ? 0 : $orderRecord->seller_discount;
                $rawData['ShippingRate'] = $orderRecord->shipping_fee;
                $rawData['SettlePrice'] = $orderRecord->settlement_amount;
                $rawData['DeliveryCompany'] = $orderRecord->data['DeliveryCompany'] ?? $rawData['deliveryCompany'];
                $rawData['TrackingNo'] = $orderRecord->data['TrackingNo'] ?? $rawData['trackingNo'];
            } else {
                $rawData['Addr1'] = '';
                $rawData['Addr2'] = '';
                $rawData['SellerDiscount'] = 0;
                $rawData['ShippingRate'] = 0;
                $rawData['SettlePrice'] = 0;
                $rawData['DeliveryCompany'] = $rawData['deliveryCompany'];
                $rawData['TrackingNo'] = $rawData['trackingNo'];
            }

            $order = $this->transformOrder($rawData);
        } catch (\Exception $e) {
            set_log_extra('order', $rawData ?? null);
            set_log_extra('order', $order ?? null);
            throw $e;
        }
        $this->handleOrder($order, $options);

        return true;
    }

    /**
     * Import recent 90 days orders
     *
     * @param array|false[] $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function import($options = ['deduct' => false])
    {
        // This is so it wont create new notifications
        $options['import'] = true;
        if (!isset($options['deduct'])) {
            $options['deduct'] = false;
        }

        // if want more than 90 days, loop it, qoo10 API max limit 90 days
        $orders = $this->getOrders(now()->subDays(89)->format('Ymd'), now()->format('Ymd'));

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

    /**
     * Incremental order sync
     *
     * @return mixed|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function sync()
    {
        $options['import'] = false;
        $options['deduct'] = $this->account->hasFeature(['orders', 'deduct_inventory']);

        $from = $this->account->getSyncData(IntegrationSyncData::SYNC_ORDERS(), now()->subHour(), true);
        $to = now();
        $qoo10RegionInUsed = "";
        if($this->account->region_id === Region::SINGAPORE) {
            $qoo10RegionInUsed = "SG";
        }
        else if($this->account->region_id === Region::GLOBAL) {
            $qoo10RegionInUsed = "US";
        }
        Log::info('qoo10RegionInUsed: ' . $qoo10RegionInUsed . ' with account id ' . $this->account->id);

        // Since qoo10 is not filtering by updated_datetime so will be using multiple of condition to filter it by datetime, else couldnt retrieve the latest updated orders.
        $conditions = [1, 2, 3, 4];  // 1:Order Date 2:Delivery Request Date 3: Shipping Date 4: Delivered Date
        foreach ($conditions as $condition) {
            // TAKE NOTE - Hours must be in 24 format
            $orders = $this->getOrders($from->format('YmdHis'), $to->format('YmdHis'), [1, 2, 3, 4, 5], $condition);
            foreach ($orders as $key => $order) {
                $paymentNation = $order["PaymentNation"];
                $orderNo = $order["orderNo"];
                try {
                    if ($paymentNation == $qoo10RegionInUsed) {
                        $order = $this->transformOrder($order);
                    }
                    else {
                        Log::info('Order ' . $orderNo . ' is not imported because it is ' . $paymentNation . ' but active account is ' . $qoo10RegionInUsed);
                    }
                } catch (\Exception $e) {
                    set_log_extra('order', $order);
                    throw $e;
                }
                if ($paymentNation == $qoo10RegionInUsed) {
                    $this->handleOrder($order, $options);
                }
            }
        }

        $conditions = [1, 2, 3]; // 1:Order date 2: Date to request a claim 3: completed date of cancellation/refund
        foreach ($conditions as $condition) {
            // Get cancelled orders
            $this->getCancelOrders($from->subDays(7)->format('YmdHis'), $to->format('YmdHis'), [1, 2, 3, 4, 5, 6], $condition);
        }

        $this->account->setSyncData(IntegrationSyncData::SYNC_ORDERS(), now());
    }

    /**
     * Transform order
     *
     * @param $order
     *
     * @return TransformedOrder
     * @throws \Exception
     */
    public function transformOrder($order)
    {
        $statusToFulfillmentStatus = [
            1 => FulfillmentStatus::PENDING(),
            2 => FulfillmentStatus::PROCESSING(),
            3 => FulfillmentStatus::READY_TO_SHIP(),
            4 => FulfillmentStatus::SHIPPED(),
            5 => FulfillmentStatus::DELIVERED(),
        ];

        $externalId = $order['orderNo'];
        $externalSource = $this->account->integration->name;

        $customerName = $order['buyer_gata'];
        $customerEmail = $order['buyerEmail'];

        $shippingAddress = new TransformedAddress(
            null,
            $order['receiver_gata'],
            $order['Addr1'],
            $order['Addr2'],
            null,
            null,
            null,
            null,
            $order['zipCode'],
            null,
            $order['shippingCountry'],
            $order['receiverMobile']
        );
        $billingAddress = new TransformedAddress(
            null,
            $order['buyer_gata'],
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $order['buyerMobile']
        );

        if (!empty($order['EstShippingDate'])) {
            $shipByDate = Carbon::createFromFormat('Y-m-d h:i:s', $order['EstShippingDate']);
        } else {
            $shipByDate = null;
        }

        $status = substr($order['shippingStatus'], -2, 1);
        $paymentStatus = $status === '1' ? PaymentStatus::UNPAID() : $paymentStatus = PaymentStatus::PAID();

        $currency = $this->account->currency;

        $integrationDiscount = $order['Cart_Discount_Qoo10'];
        $sellerDiscount = $order['Cart_Discount_Seller'] > 0 ? $order['Cart_Discount_Seller'] : $order['SellerDiscount'];
        $shippingFee = $order['ShippingRate'];

        $settlementAmount = abs($order['SettlePrice']);

        $paymentMethod = $order['PaymentMethod'];

        $fulfillmentType = $order['shippingRateType'] === 'Store Pickup' ? FulfillmentType::SELF_COLLECTION() : FulfillmentType::REQUIRES_SHIPPING();
        $fulfillmentStatus = $statusToFulfillmentStatus[$status];

        $buyerPaid = $paymentStatus->equals(PaymentStatus::PAID()) ? $settlementAmount : 0;
        $grandTotal = $order['orderPrice'];

        // This is customer/buyer remark or message
        $buyerRemarks = $order['ShippingMsg'];

        $type = OrderType::NORMAL();

        $orderPlacedAt = new Carbon($order['orderDate']);
        $orderUpdatedAt = new Carbon($order['PaymentDate']);
        $orderPaidAt = new Carbon($order['PaymentDate']);

        $data = [
            'packNo' => $order['packNo'],
            'PackingNo' => $order['PackingNo'],
            'DeliveryCompany' => $order['DeliveryCompany'],
            'SellerDeliveryNo' => $order['SellerDeliveryNo'],
            'TrackingNo' => $order['TrackingNo'],
        ];

        $sku = !empty($order['optionCode']) ? $order['optionCode'] : $order['sellerItemCode']; // variant sku ?? main sku
        $name = str_limit($order['itemTitle'], 200);
        // order product id
        $externalProductId = $order['itemCode'];
        $variationName = str_limit($order['option'], 200);
        $variationSku = $order['optionCode'];

        // if order product id and sku found
        if (!empty($externalProductId) && !empty($sku)) {
            // find listing with qoo10 order product id
            $productListing = ProductListing::whereAccountId($this->account->id)
                ->whereIntegrationId($this->account->integration_id)
                ->where('identifiers->external_id', $externalProductId)
                ->first();

            if ($productListing) {
                // find variant listing with same sku as qoo10 order product
                /** @var ProductListing $productListingVariant */
                $productListingVariant = $productListing->listing_variants->where('identifiers->sku', $sku)->first();
                if ($productListingVariant) {
                    $externalProductId = $productListingVariant->getIdentifier(ProductIdentifier::EXTERNAL_ID());
                    $variationName = $productListingVariant->name;
                    $variationSku = $productListingVariant->getIdentifier(ProductIdentifier::SKU());
                }
            }
        }

        $quantity = $order['orderQty'];
        $itemPrice = abs($order['orderPrice']) / $quantity;

        $shipmentType = $order['shippingRateType'];
        $shipmentMethod = $order['DeliveryCompany'];
        if ($shipmentMethod === null || trim($shipmentMethod) === '') {
            $shipmentProvider = 'Store Pickup';
        } else {
            $shipmentProvider = $shipmentMethod . ' - ' . $shipmentType;
        }
        $trackingNumber = $order['TrackingNo'];

        $itemData = [];
        $items = [];

        $skus = explode(',', $sku);
        // If account have handle bundled sku feature and sku contains , means its bundled sku
        if ($this->account->hasFeature(['orders', 'handle_bundled_sku']) && count($skus) > 1) {
            foreach ($skus as $key => $value) {
                // new to have a dummy external Id, else it will not be created
                $variantExternalId = null;
                $variantName = $value;
                // get listing based on the sku
                $listing = ProductListing::whereAccountId($this->account->id)
                    ->whereIntegrationId($this->account->integration_id)
                    ->where('identifiers->sku', $value)
                    ->first();
                if ($listing) {
                    $variantExternalId = $listing->getIdentifier(ProductIdentifier::EXTERNAL_ID());
                    $variantName = $listing->name;
                }

                $items[] = new TransformedOrderItem(
                    $externalProductId,
                    $variantExternalId,
                    $name,
                    $sku,
                    $variantName,
                    $value,
                    $quantity,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    $fulfillmentStatus,
                    $shipmentProvider,
                    $shipmentType,
                    $shipmentMethod,
                    $trackingNumber,
                    null,
                    0,
                    0,
                    $itemData
                );
            }
        } else {
            $items[] = new TransformedOrderItem(
                $externalProductId,
                $externalProductId,
                $name,
                $sku,
                $variationName,
                $variationSku,
                $quantity,
                $itemPrice,
                $integrationDiscount,
                $sellerDiscount,
                $shippingFee,
                0,
                0,
                $grandTotal,
                $buyerPaid,
                $fulfillmentStatus,
                $shipmentProvider,
                $shipmentType,
                $shipmentMethod,
                $trackingNumber,
                null,
                null,
                $shippingFee,
                $itemData
            );
        }

        $order = new TransformedOrder(
            $externalId,
            $externalSource,
            null,
            $customerName,
            $customerEmail,
            $shippingAddress,
            $billingAddress,
            $shipByDate,
            $currency,
            $integrationDiscount,
            $sellerDiscount,
            $shippingFee,
            0,
            0,
            0,
            0,
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
     * @param Order $order
     * @return array|string[]
     */
    public function availableActions(Order $order)
    {
        $statusSpecific = [];
        $general = ['getShippingCompany'];


        if ($order->fulfillment_status === FulfillmentStatus::PENDING()->getValue()) {
            $statusSpecific[] = 'reasons';
            $statusSpecific[] = 'printQexpressInvoice';
            $statusSpecific[] = 'updateShippingInfo';
        } else if ($order->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue()) {
            $statusSpecific[] = 'reasons';
            $statusSpecific[] = 'airwayBill';
            $statusSpecific[] = 'printQexpressInvoice';
        }

        if ($order->fulfillment_status >= FulfillmentStatus::PENDING()->getValue() && $order->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
            $statusSpecific[] = 'printAddress';
            $statusSpecific[] = 'updateShippingInfo';
        }

        if (
            $order->fulfillment_status === FulfillmentStatus::PENDING()->getValue()
            || $order->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue()
            || $order->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue()
            || $order->fulfillment_status === FulfillmentStatus::REQUEST_CANCEL()->getValue()
        ) {
            $statusSpecific[] = 'printAddress';
            $statusSpecific[] = 'updateShippingInfo';
            $statusSpecific[] = 'cancel';
        }

        if ($order->fulfillment_status >= FulfillmentStatus::PROCESSING()->getValue() && $order->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
            $statusSpecific[] = 'shippingStatement';
        }

        foreach ($order->items as $item) {
            // seller delivery should not be able to print shipping statement, else it will be changed to qxpress
            if ($item->shipment_provider === 'Seller Delivery') {
                $key = array_search('shippingStatement', $statusSpecific);
                if ($key !== false) {
                    unset($statusSpecific[$key]);
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue() && $item->shipment_provider === 'Seller Delivery') {
                if (!in_array('updateEstimatedShippingDate', $statusSpecific)) {
                    $statusSpecific[] = 'updateEstimatedShippingDate';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::READY_TO_SHIP()->getValue() && $item->shipment_provider === 'Seller Delivery') {
                if (!in_array('getDeliveryCompanyList', $statusSpecific)) {
                    $statusSpecific[] = 'getDeliveryCompanyList';
                }
                if (!in_array('fulfillment', $statusSpecific)) {
                    $statusSpecific[] = 'fulfillment';
                }
            }
            if ($item->fulfillment_status === FulfillmentStatus::PROCESSING()->getValue() && ($item->shipment_provider === 'Qxpress' || $item->shipment_provider === 'Qprime')) {
                if (!in_array('getLogistic', $statusSpecific)) {
                    $statusSpecific[] = 'getLogistic';
                }
                if (!in_array('pickup', $statusSpecific)) {
                    $statusSpecific[] = 'pickup';
                }
            }
        }
        return array_merge($statusSpecific, $general);
    }

    public function getShippingCompany(Order $order, Request $request)
    {
        return $this->client->request('POST', 'ShippingBasic.GetDeliveryCompanyInfo', [
            'invoice_type' => 'ST', //ST - standard. CP - compact
            'svc_nation_cd' => $this->account->region_id === Region::SINGAPORE ? 'SG' : 'MY', //Fix
            'ref_type' => 'C', // B - packing no, C - order no,
            'ref_value' => $order->external_order_number,
        ]);
    }

    public function updateShippingInfo(Order $order, Request $request)
    {
        $provider = $request->input('shipping_provider');
        $trackingNo = $request->input('tracking_no');
        if (empty($provider)) {
            return $this->respondBadRequestError('Shipping provider not set.');
        }
        if (empty($trackingNo)) {
            return $this->respondBadRequestError('Tracking no not set.');
        }
        $response = $this->client->request('GET', 'ShippingBasic.SetSendingInfo', [
            'ShippingCorp' => $provider,
            'OrderNo' => $order->external_id,
            'TrackingNo' => $trackingNo,
        ]);

        if (isset($response['ErrorCode'])) {
            return $this->respondBadRequestError($response['ErrorMsg']);
        }
        if ($response['ResultCode'] === 0) {
            return $this->respond($response['ResultMsg']);
        }
        return $this->respondBadRequestError($response['ResultMsg']);
    }

    public function cancel(Order $order, Request $request)
    {
        $memo = $request->input('memo');
        $reason = $request->input('reason');
        $refundFee = $request->input('refund_fee');
        $data =  [
            'ContrNo' => $order->external_id,
            'CancelReason' => !empty($reason) ? $reason : '',
        ];
        if (!empty($memo)) {
            $data['SellerMemo'] = $memo;
        }
        if (!empty($refundFee)) {
            $data['returnFeeStat'] = $refundFee;
        }
        $response = $this->client->request('POST', 'Claim.SetCancelProcess', $data);
        if (isset($response['ErrorCode'])) {
            return $this->respondBadRequestError($response['ErrorMsg']);
        }
        if ($response['ResultCode'] === 0) {
            return $this->respond($response['ResultMsg']);
        }
        return $this->respondBadRequestError($response['ResultMsg']);
    }

    public function printQexpressInvoice(Order $order)
    {
        $response = $this->client->request('POST', 'ShippingBasic.PrintQxpressInvoice', [
            'invoice_type' => 'ST', //ST - standard. CP - compact
            'svc_nation_cd' => $this->account->region_id === Region::SINGAPORE ? 'SG' : 'MY', //Fix
            'ref_type' => 'C', // B - packing no, C - order no,
            'ref_value' => $order->external_order_number,
            'file_type' => 'pdf',
        ]);
        return $response;
    }

    /**
     * Get orders
     *
     * @param $from
     * @param $to
     * @param array $statuses
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrders($from, $to, $statuses = [1, 2, 3, 4, 5], $condition = 1)
    {
        $orders = [];
        foreach ($statuses as $status) {
            $response = $this->client->request('POST', 'ShippingBasic.GetShippingInfo_v2', [
                'ShippingStat' => $status, // 1: Delivery Preparation, 2: Delivery Requested, 3: Delivery Confirmed, 4: On delivery, 5: Delivery complete
                'search_Sdate' => $from,
                'search_Edate' => $to,
                'search_condition' => $condition, // 1:Order Date, 2:Delivery Request Date, 3: Shipping Date, 4: Delivered Date
            ]);

            if ($response['ResultCode'] === 0 && !is_null($response['ResultObject'])) {
                $orders = array_merge($orders, $response['ResultObject']);
            }
        }

        return $orders;
    }

    /**
     * Get Claim Orders
     *
     * @param $from
     * @param $to
     * @param array $statuses
     * @param int $condition
     * @return array
     */
    public function getCancelOrders($from, $to, $statuses = [1, 2, 3, 4, 5, 6], $condition = 1)
    {
        $cancelOrders = [];
        foreach ($statuses as $status) {
            $response = $this->client->request('POST', 'ShippingBasic.GetClaimInfo_v2', [
                'ClaimStat' => $status, // 1:Cancel Requestion, 2:Cancel Processing, 3:Cancel Complete, 4:Return Request, 5:Return Processing, 6:Return Complete,11: Exchange request, 12: Exchange processing, 13: Re-delivering, 14: Non receipt request, 15: Non receipt processing
                'search_Sdate' => $from,
                'search_Edate' => $to,
                'search_condition' => $condition, // 1:Order date 2: Date to request a claim 3: completed date of cancellation/refund
            ]);

            if ($response['ResultCode'] !== 0 && !is_null($response['ResultMsg'])) {
                set_log_extra('response', $response);
                throw new \Exception($response['ResultMsg']);
            } else {
                $cancelOrders = array_merge($cancelOrders, $response['ResultObject']);
            }
        }

        foreach ($cancelOrders as $cancelOrder) {
            $externalId = $cancelOrder['orderNo'];
            $reason = $cancelOrder['reason'];
            $this->updateOrderCancel($externalId, $reason);
        }
    }

    /**
     * Update order to cancel
     *
     * @param $externalId
     */
    public function updateOrderCancel($externalId, $reason = '')
    {
        /** @var Order $order */
        $order = Order::where([
            'external_id' => $externalId,
            'account_id' => $this->account->id
        ])->first();

        if ($order) {
            $order->payment_status = PaymentStatus::CANCELLED()->getValue();
            $order->fulfillment_status = FulfillmentStatus::CANCELLED()->getValue();
            $order->save();

            $order->items()->update([
                'fulfillment_status' => FulfillmentStatus::CANCELLED()->getValue()
            ]);

            if ($reason == Constant::QOO10_CANCEL_REASON_OOS) {
                $items = $order->items()->get();
                foreach ($items as $item) {
                    if ($productInventory = $item->inventory) {
                        $special_reason = ' because of qoo10 logic ' . $reason;
                        $changed = 0 - $productInventory->stock;
                        $productInventory->addStock(
                            $changed,
                            'child',
                            'Restocked from order ' . ($order->external_id ? $order->external_id : $order->id) . ($order->external_source ? ' (' . $order->external_source . ')' . ' ' . $special_reason : ''),
                            $order->id,
                            get_class($order),
                            false
                        ); 
                        SyncInventory::dispatch($productInventory)->onQueue('sync_inventories');
                    }
                    else {
                        Log::info('Can not find inventory of order item ' . $item->id);
                    }
                }
            }
        }
    }

    /**
     * Get order detail
     *
     * @param $externalId
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getOrder($externalId)
    {
        $response = $this->client->request('POST', 'ShippingBasic.GetShippingAndClaimInfoByOrderNo', [
            'OrderNo' => $externalId,
        ]);

        if ($response['ResultCode'] === 0 && is_array($response['ResultObject']) && count($response['ResultObject']) > 0) {
            return $response['ResultObject'][0];
        } else {
            set_log_extra('external_id', $externalId);
            set_log_extra('response', $response);
            throw new \Exception($response['ResultMsg']);
        }
    }

    private function getLegacyAdapter()
    {
        $client = new \App\Integrations\Qoo10Legacy\Client($this->account);
        return new \App\Integrations\Qoo10Legacy\OrderAdapter($this->account, $client);
    }

    /*
     * BEGIN
     * LEGACY Methods - Utilizing Qoo10_Legacy
     */

    public function airwayBill($order, Request $request)
    {
        $response = $this->client->request('GET', 'ShippingBasic.PrintQxpressInvoice', [
            'invoice_type' => 'ST',
            'ref_type' => 'C',
            'ref_value' => $order->external_id,
        ]);

        if (isset($response['ErrorCode'])) {
            return $this->respondBadRequestError($response['ErrorMsg']);
        }
        if ($response['ResultCode'] === 0) {
            return $this->respond($response['ResultMsg']);
        }
        return $this->respondBadRequestError($response['ResultMsg']);
    }

    public function printAddress($orders, Request $request)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->printAddress($orders, $request);
    }

    public function shippingStatement($orders, Request $request)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->shippingStatement($orders, $request);
    }

    public function getLogistic($order)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->getLogistic($order);
    }

    public function generatePickupInfo()
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->generatePickupInfo();
    }

    public function updateEstimatedShippingDate($orders, Request $request)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->updateEstimatedShippingDate($orders, $request);
    }



    public function pickup($order, Request $request)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->pickup($order, $request);
    }

    public function getDeliveryCompanyList(Request $request)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->getDeliveryCompanyList();
    }

    public function getSellerPickupAddress(Request $request)
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->getSellerPickupAddress();
    }

    public function reasons()
    {
        $adapter = $this->getLegacyAdapter();
        return $adapter->reasons();
    }

    /*
     * END
     * LEGACY Methods - Utilizing Qoo10_Legacy
     */
}
