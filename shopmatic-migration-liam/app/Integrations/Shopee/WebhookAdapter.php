<?php

namespace App\Integrations\Shopee;

use App\Constants\FulfillmentStatus;
use App\Constants\MarketplaceProductStatus;
use App\Models\Order;
use App\Models\ProductListing;
use App\Utilities\InternalResponse;
use Illuminate\Http\Request;

class WebhookAdapter extends InternalResponse {

    /**
     * handle a webhook code
     *
     * @param $request
     * @return array
     * @throws \Exception
     */
    public function handle($request)
    {
        $code = $request->input('code');

        if($code) {
            switch ($code) {
                case 3:
                    return $this->orderStatusUpdate($request);
                case 6:
                    return $this->bannedItem($request);
                default:
                    break;
            }
        }else {
            set_log_extra('Shopee Webhook','Unable to find code for Shopee Webhook');
        }
    }

    /**
     * Update a order status
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function orderStatusUpdate($request) {

        $data = $request->input('data');
        $data = json_decode($data);
        $ordersn = $data->ordersn;
        $status = $data->status;

        $order = Order::whereExternalId($ordersn)->first();

        if ($status == 'UNPAID') {
            $status = FulfillmentStatus::PENDING()->getValue();
        } elseif ($status == 'SHIPPED') {
            $status = FulfillmentStatus::SHIPPED()->getValue();
        } elseif ($status == 'COMPLETED') {
            $status = FulfillmentStatus::DELIVERED()->getValue();
        } elseif ($status == 'IN_CANCEL') {
            $status = FulfillmentStatus::REQUEST_CANCEL()->getValue();
        } elseif ($status == 'CANCELLED') {
            $status = FulfillmentStatus::CANCELLED()->getValue();
        } elseif ($status == 'READY_TO_SHIP') {
            $status = FulfillmentStatus::READY_TO_SHIP()->getValue();
        } elseif ($status == 'RETRY_SHIP') {
            $status = FulfillmentStatus::RETRY_SHIP()->getValue();
        } elseif ($status == 'TO_CONFIRM_RECEIVE') {
            $status = FulfillmentStatus::TO_CONFIRM_DELIVERED()->getValue();
        } elseif ($status == 'TO_RETURN') {
            $status = FulfillmentStatus::RETURNED()->getValue();
        } else {
            set_log_extra('order_status', $status);
            throw new \Exception('Shopee has different fulfilment status');
        }

        $order->update([
            'fulfillment_status' => $status
        ]);

        return $this->respond();
    }

    /**
     * Banned product item
     *
     * @param Request $request
     * @return array
     */
    public function bannedItem($request) {

        $data = $request->input('data');
        $data = json_decode($data);
        $itemId = $data->item_id;

        $listing = ProductListing::where('identifiers->external_id', $itemId)->first();

        $listing->update([
           'status' => MarketplaceProductStatus::BANNED()->getValue()
        ]);

        return $this->respond();
    }

}
