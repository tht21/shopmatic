<?php

namespace App\Console\Commands;

use App\Jobs\SyncInventory;
use App\Models\Shop;
use Illuminate\Console\Command;

class CheckInventoryDisparity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disparity:check {shop_id} {--u|update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check disparity of stock between inventories and listing';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $update = $this->option('update');
        $shopId = $this->argument('shop_id');
        $shop = Shop::find($shopId);

        // make sure shop has active subscription or is e2e
        if (!$shop->e2e && !$shop->getActiveSubscription()) {
            $this->error('Shop not active.');
            return false;
        }

        $listings = $shop->listings()->whereHas('variant', function ($query) {
            $query->whereHas('inventory', function ($query) {
                $query->whereColumn('product_listings.stock', '!=', 'product_inventories.stock');
            });
        })->get();

        $this->info('Total '.$listings->count());

        foreach ($listings as $key => $value) {

            $this->info($value->id . ' ' . $value->variant->sku . ' : ' . $value->stock . ' <-> '. $value->variant->inventory->stock);

            if ($update) {
                if (($value->stock == 0 && $value->variant->inventory->stock < 0) || ($value->stock == $value->variant->inventory->stock)) {
                    continue;
                }

                // do not force it, need to trail it
                SyncInventory::dispatchNow($value->variant->inventory, false, true);
            }
        }

        $this->info('Total '.$listings->count());
    }
}
