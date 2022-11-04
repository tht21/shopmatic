<?php

namespace App\Jobs;

use App\Constants\ProductAlertType;
use App\Events\NewProductAlert;
use App\Interfaces\ProductAdapterInterface;
use App\Models\ProductInventoryTrail;
use App\Models\ProductListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateListingInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $listing;
    protected $stock;

    /**
     * Create a new job instance.
     *
     * @param ProductListing $listing
     * @param $stock
     */
    public function __construct(ProductListing $listing, $stock)
    {
        $this->listing = $listing;
        $this->stock = $stock;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $listing = $this->listing;

        try {
            // This means the stock is different and we need to update it for the listing
            /** @var ProductAdapterInterface $adapter */
            $adapter = $listing->account->getProductAdapter();
            $adapter->updateStock($listing, $this->stock);

            ProductInventoryTrail::create([
                'shop_id' => $listing->shop_id,
                'product_inventory_id' => $listing->variant->inventory_id,
                'message' => 'Stock for listing ' . $listing->product->name . ' (' . $listing->identifier_text . ') updated to ' . $this->stock,
                'related_id' => $listing->id,
                'related_type' => get_class($listing),
                'old' => $listing->stock,
                'new' => $this->stock,
            ]);
        } catch (\Exception $e) {
            event(new NewProductAlert($listing->product, $e->getMessage(), ProductAlertType::ERROR()));
        }
    }
}
