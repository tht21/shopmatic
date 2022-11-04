<?php

namespace App\Integrations\Shopify;

use App\Models\Account;
use App\Models\Integration;
use App\Utilities\InternalResponse;

class WebhookAdapter extends InternalResponse
{


    /**
     * ProductAdapter constructor.
     *
     * @param Account|null $account
     *
     * @throws \Exception
     */
    public function __construct($account = null)
    {
        $this->account = $account;
    }

    /**
     * handle a webhook code
     *
     * @param $request
     * @return array
     * @throws \Exception
     */
    public function handle($request)
    {
        // From account webhook
        if (!is_null($this->account)) {
            $resource = $request->header('x-shopify-topic');
            $domain = $request->header('x-shopify-shop-domain');

            if ($this->account->name == $domain) {
                $input = (object)$request->all();
                if ($resource === 'orders/create' || $resource === 'orders/updated' || $resource === 'orders/cancelled' || $resource === 'orders/fulfilled' || $resource === 'orders/paid') {
                    $this->orderUpdate($input, $resource);
                } elseif ($resource === 'products/create' || $resource === 'products/updated') {
                    $this->productUpdate($input);
                } else {
                    set_log_extra('Shopify Webhook', 'Unable to find code for shopify Webhook');
                }
            }
        } else {
            // From integration webhook (Currently only for gdpr webhook)
            if ($request->get('action') != 'customer-redact' && $request->get('action') != 'customers-data-request' && $request->get('action') != 'shop-redact') {
                set_log_extra('input',$request->all());
                throw new \Exception('Invalid webhook action');
            }
            return $this->respond();
        }

    }

    /**
     * Create or update a order
     *
     * @param $input
     * @param $resource
     * @throws \Exception
     */
    public function orderUpdate($input, $resource)
    {

        try {
            $options = ['deduct' => false];
            if ($resource === 'orders/create') {
                $options['deduct'] = true;
            }
            // Create or Update order
            $id = $input->id;
            $orderAdapter = $this->account->getOrderAdapter();
            $orderAdapter->get($id, $options);

        } catch (\Exception $exception) {
            set_log_extra('order', $input);
            throw $exception;
        }

    }

    /**
     * Create or Update a product
     *
     * @param $input
     * @throws \Exception
     */
    public function productUpdate($input)
    {

        try {
            // Create or Update product
            $id = $input->id;
            $orderAdapter = $this->account->getProductAdapter();
            $orderAdapter->get(null, true, $id);

        } catch (\Exception $exception) {
            set_log_extra('order', $input);
            throw $exception;
        }
    }

}
