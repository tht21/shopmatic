<?php

namespace App\Integrations\Vend;

use App\Models\Account;
use App\Models\Integration;
use Illuminate\Http\Request;

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
     * handle a webhook code
     *
     * @param Request $request
     * @throws \Exception
     */
    public function handle(Request $request)
    {

        $type = $request->get('type');
        $domainPrefix = $request->get('domain_prefix');

        if ($this->account->name == $domainPrefix) {

            $input = json_decode($request->get('payload'));
            if ($type === 'product.update') {

            } elseif ($type === 'inventory.update') {

            } elseif ($type === 'consignment.send') {

            } elseif ($type === 'sale.update') {
                $this->syncOrder($input);
            } elseif ($type === 'customer.update') {

            } elseif ($type === 'register_closure.create') {

            } elseif ($type === 'register_closure.create') {

            }
        }

    }

    /**
     * Handle product
     *
     * @param $input
     * @return void
     * @throws \Exception
     */
    public function syncOrder($input)
    {

        try {
            // Create or Update order
            $id = $input->id;
            $orderAdapter = $this->account->getOrderAdapter();
            $orderAdapter->get($id, ['deduct' => false]);
        } catch (\Exception $exception) {
            set_log_extra('order', $input);
            throw $exception;
        }

    }


}
