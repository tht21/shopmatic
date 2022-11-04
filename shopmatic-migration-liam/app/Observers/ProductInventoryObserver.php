<?php

namespace App\Observers;

use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;

class ProductInventoryObserver
{
    /**
     * Listen to the Inventory created event.
     *
     * @param  ProductInventory  $inventory
     * @return void
     */
    public function created(ProductInventory $inventory)
    {
        DB::transaction(function () use ($inventory) {
            $inventory->shop->total_sku_count += 1;
            $inventory->shop->save();
        }, 3);
    }

    /**
     * Listen to the User deleting event.
     *
     * @param  ProductInventory  $inventory
     * @return void
     */
    public function deleting(ProductInventory $inventory)
    {
        DB::transaction(function () use ($inventory) {
            $inventory->shop->total_sku_count -= 1;
            $inventory->shop->save();
        }, 3);
    }
}
