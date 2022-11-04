<?php

namespace App\Jobs;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncProductListing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
     * This is used to start all the jobs to sync the listing as opposed to starting it individually in the Kernel.
     *
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

            // First check if it can even import products
            if (!$account->hasFeature(['products', 'import_products'])) {
                continue;
            }

            ProductListingSyncJob::dispatch($account);
        }
    }
}
