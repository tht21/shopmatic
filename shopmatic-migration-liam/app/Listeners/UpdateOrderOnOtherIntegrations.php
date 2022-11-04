<?php

namespace App\Listeners;

use App\Constants\AccountStatus;
use App\Events\OrderUpdated;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateOrderOnOtherIntegrations implements ShouldQueue
{

    use InteractsWithQueue;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   This is used to update any existing order on the integration (If supported)
     *
     * 2.   This does NOT create/push the order to other integrations
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
        /** @var Order $child */
        foreach ($order->children as $child) {
            if ($child->account->status->equals(AccountStatus::ACTIVE())) {
                $child->account->getOrderAdapter()->updateOrder($child);
            }
        }
    }
}
