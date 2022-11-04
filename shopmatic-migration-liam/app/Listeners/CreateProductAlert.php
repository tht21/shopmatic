<?php

namespace App\Listeners;

use App\Events\NewProductAlert;
use App\Models\ProductAlert;
use Illuminate\Support\Facades\Log;

class CreateProductAlert
{

    /**
     * Handle the event.
     *
     * @param  NewProductAlert  $event
     * @return void
     */
    public function handle(NewProductAlert $event)
    {
        try {
            $shopId = (isset($event->product->shop_id) && !empty($event->product->shop_id)) ? $event->product->shop_id : $event->shopId;
            $productId = (isset($event->product->id) && !empty($event->product->id)) ? $event->product->id : $event->productId;
            ProductAlert::create([
                'shop_id'  => $shopId,
                'product_id' => $productId,
                'message' => $event->message,
                'type' => $event->type,
            ]);
        } catch (\Throwable $th) {
            Log::error("NewProductAlert Error: " . $th->getMessage());
        }
    }
}
