<?php

namespace App\Listeners;

use App\Models\Account;
use App\Events\OrderUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateOrderOnOtherIntegrations implements ShouldQueue
{

    use InteractsWithQueue;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   This is used to push orders to other accounts that support it / has it enabled.
     *
     * 2.   This should NOT update the order on the other accounts
     *
     */

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param OrderUpdated $event
     * @return void
     * @throws \Exception
     */
    public function handle(OrderUpdated $event)
    {
        $order = $event->order;
        if(!isset($order)) {
            return;
        }

        $shop = $order->shop;

        // To get all other integrations instead of the one it's coming from
        $accounts = $shop->accounts()->where('id', '<>', $order->account_id)->active()->get();

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->hasFeature(['orders', 'export_orders'])) {
                $account->getOrderAdapter()->createOrder($order);
            }
        }
    }
}
