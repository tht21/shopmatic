<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProductListing;
use Illuminate\Support\Facades\Log;

class DeleteOldProductlistingQoo10 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oldProductListingQoo10:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old duplicated product listing qoo10';

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
        echo "Start deleting old duplicated product listings qoo10 \n";
        DB::table('product_listings')
            ->where('integration_id', 11004)
            ->whereNull('deleted_at')
            ->orderby('id', 'ASC')
            ->chunk(500, function ($listingArray) {
                foreach ($listingArray as $listing) {
                    Log::info(json_encode($listing));
                    $existNewerListing = ProductListing::where('integration_id', 11004)
                        ->where('account_id', $listing->account_id)
                        ->whereNull('deleted_at')
                        ->where('product_variant_id', $listing->product_variant_id)
                        ->where('id', '>', $listing->id)
                        ->exists();

                    if ($existNewerListing) {
                        ProductListing::where('id', $listing->id)->delete();
                        echo "Deleted records id " . $listing->id . "\n";
                    }
                }
            });
        echo "Finish deleting old duplicated product listings qoo10 \n";
    }
}
