<?php

namespace App\Http\Controllers\Webhook;

use App\Constants\AccountStatus;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeController extends CashierController
{

    /**
     * Disable accounts in shop
     *
     * @param  Shop  $shop
     */
    private function disableAccounts($shop)
    {
        foreach ($shop->accounts as $account) {
            $account->getClient()->disableAccount(AccountStatus::DISABLED());
        }
    }

	/**
     * Handle a cancelled customer from a Stripe subscription.
     * Disabled all account once subscription cancelled
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted($payload)
    {
        if ($shop = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $this->disableAccounts($shop);
        }

        parent::handleCustomerSubscriptionDeleted($payload);

        return $this->successMethod();
    }

    /**
     * Handle deleted customer.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerDeleted($payload)
    {
        if ($shop = $this->getUserByStripeId($payload['data']['object']['id'])) {
            $this->disableAccounts($shop);
        }

        parent::handleCustomerDeleted($payload);

        return $this->successMethod();
    }

    /**
     * Handle automatic payment on a subscription fails
     * Disabled all account once subscription payment failed
     * http://prntscr.com/rp747j
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleChargeFailed($payload)
    {   
        if ($shop = $this->getUserByStripeId($payload['data']['object']['id'])) {
            $this->disableAccounts($shop);
        }

        return $this->successMethod();
    }
}
