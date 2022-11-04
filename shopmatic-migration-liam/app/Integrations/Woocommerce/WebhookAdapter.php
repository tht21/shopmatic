<?php

namespace App\Integrations\Woocommerce;

use App\Models\Account;

class WebhookAdapter
{
    /**
     * ProductAdapter constructor.
     *
     * @param Account|null $account
     *
     * @throws \Exception
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * The function to check and handle the actions for product/order
     *
     * @param $request
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function handle($request)
    {
        //if ($this->isValidSignature($request)) {
            $resource = $request->header('x-wc-webhook-resource');
            $event = $request->header('x-wc-webhook-event');
            $input = (object) $request->all();

            /*
             * To differentiate the webhook resource is either for product or order
             * */
            if ($resource === 'product') {
                $this->syncProduct($input, $event);
            } else if ($resource === 'order') {
                $this->syncOrder($input);
            }
        //}
    }

    /**
     * Handle product
     *
     * @param $product
     * @param $event
     * @return bool
     * @throws \Exception
     */
    public function syncProduct($product, $event)
    {
        // If product parent_id is equal to 0 meaning this will be main product else it will be the variant
        $externalId = ($product->parent_id == 0) ? $product->id : $product->parent_id;

        $update = false;
        if ($event === 'updated') {
            $update = true;
        }

        try {
            // Create or Update product
            $productAdapter = new ProductAdapter($this->account);
            $productAdapter->get(null, $update, $externalId);

        } catch (\Exception $exception) {
            set_log_extra('product', $product);
            throw $exception;
        }

        return true;
    }

    /**
     * Handle Order
     *
     * @param $order
     * @return bool
     * @throws \Exception
     */
    public function syncOrder($order)
    {
        try {
            // Create or Update order
            $productAdapter = new OrderAdapter($this->account);
            $productAdapter->get($order->id, ['deduct' => false]);

        } catch (\Exception $exception) {
            set_log_extra('order', $order);
            throw $exception;
        }

        return true;

    }

    /**
     * Verify the woocommerce webhook's signature to authenticity
     *
     * @param $request
     * @return bool
     */
    public function isValidSignature($request)
    {
        // Signature provide by woocommerce
        $signature = $request->header('x-wc-webhook-signature');
        $payload = $request->getContent();

        $calculatedHmac = base64_encode(hash_hmac('sha256', $payload, '', true));

        if ($signature != $calculatedHmac) {
            return false;
        }
        return true;
    }
}
