<?php

namespace App\Observers;

use App\Constants\OrderType;
use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the order "created" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function created(Order $order)
    {
        $type = $order->type;
        if ($type instanceof OrderType) {
            $type = $type->getValue();
        }
        if ($type != OrderType::SHADOW()->getValue()) {
            $order->shop->total_orders_count += 1;
            $order->shop->save();
        }
    }

    /**
     * Handle the order "deleted" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function deleted(Order $order)
    {
        $type = $order->type;
        if ($type instanceof OrderType) {
            $type = $type->getValue();
        }
        if ($type != OrderType::SHADOW()->getValue()) {
            $order->shop->total_orders_count -= 1;
            $order->shop->save();
        }
    }

    /**
     * Handle the order "restored" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function restored(Order $order)
    {
        $type = $order->type;
        if ($type instanceof OrderType) {
            $type = $type->getValue();
        }
        if ($type != OrderType::SHADOW()->getValue()) {
            $order->shop->total_orders_count += 1;
            $order->shop->save();
        }
    }

}
