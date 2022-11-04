<?php

namespace App\Jobs;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteOrphanedProductVariantJob implements ShouldQueue
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

        $listingProductVariantIds = $shop->listings()->groupBy('product_variant_id')->pluck('product_variant_id');

        $orphanedProductVariants = $shop->productVariants()
                                    ->whereNotIn('id', $listingProductVariantIds)
                                    ->delete();
    }
}
