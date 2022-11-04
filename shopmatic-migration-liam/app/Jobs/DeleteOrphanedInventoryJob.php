<?php

namespace App\Jobs;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteOrphanedInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shop;

    /**
     * Create a new job instance.
     *
     * @param Shop $shop
     */
    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shop = $this->shop;
        
        $productVariantInventoryIds = $shop->productVariants()->whereHas('listings', 
            function($query) use ($shop) {
                $query->whereShopId($shop->id)
                      ->whereColumn('product_variants.product_id', '=', 'product_listings.product_id');
        })->groupBy('inventory_id')->pluck('inventory_id')->filter();

        $orphanedInventories = $shop->inventories()->whereNotIn('id',$productVariantInventoryIds)->delete();
    }
}
