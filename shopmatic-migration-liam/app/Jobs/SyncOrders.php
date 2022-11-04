<?php

namespace App\Jobs;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
     * This is used to start all the jobs to sync the orders as opposed to starting it individually in the Kernel.
     *
     * TODO: Add in interval for each integration (e.g. qoo10 - every 30 mins, lazada - every 5 mins) instead of a fixed interval for all
     */

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $accounts = Account::where('status',  AccountStatus::ACTIVE()->getValue())->get();

        /** @var Account $account */
        foreach ($accounts as $account) {

            // First check if it can even import orders
            if (!$account->hasFeature(['orders', 'import_orders'])) {
                continue;
            }

            // Next check to see if the user turned off sync_orders
            if (!$account->hasFeature(['orders', 'sync_orders'])) {
                continue;
            }
            $time_start = microtime(true);
            // We're retrieving the listing here first, so we wouldn't need to update the stock if it's correct.
            // Tentatively, it should already be correct after deduction.
            // Next, we sync the inventory with order accounts
            OrderSyncJob::withChain([
                new ProcessOrderListing($account),
                new ProcessOrderInventory($account),
            ])->dispatch($account)->onQueue('sync_orders');

            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start)*1000;
            $debugLogEnd = '[SyncOrders]Debug Log|Account Id|'.$account->id.'|Shop Id|'.$account->shop_id.'|Integration Id|'.$account->integration_id.'|Account Name|'.$account->name.'|Region|'.$account->region_id.'|End At|'.date('Y-m-d H:i:s').'|Total Execution Time|'.$execution_time.' Milliseconds';
            Log::info($debugLogEnd);
        }
    }
}
