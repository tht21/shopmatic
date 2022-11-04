<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessOrderListing implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
     * This is used to start all the jobs to sync the listing as opposed to starting it individually in the Kernel.
     *
     */
    protected $account;

    /**
     * Create a new job instance.
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $account = $this->account;

        $listingIds = Cache::get('account-' . $account->id . '-listing-sync', []);

        $listingIds = array_unique($listingIds);

        if (!empty($listingIds)) {
            $listings = $account->shop->listings()->whereIn('product_listings.id', $listingIds)->get();
            foreach ($listings as $listing) {
                try {
                    $account->getProductAdapter()->get($listing);
                } catch (\Exception $e) {
                    set_log_extra('account', $account);
                    set_log_extra('listing', $listing);
                    set_log_extra('listing_ids', $listingIds);
                    $debugLog = '[ProcessOrderListing]Unable to retrieve product details after order sync|Account Id|'.$account->id.'|Shop Id|'.$account->shop_id.'|Integration Id|'.$account->integration_id.'|Account Name|'.$account->name.'|Region|'.$account->region_id.'|'.json_encode($listing).'|Error Message|'.$e->getMessage();
                    Log::error($debugLog);
                }
            }
        }
        Cache::forget('account-' . $account->id . '-listing-sync');

    }
}
