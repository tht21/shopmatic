<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessOrderInventory implements ShouldQueue
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
        /** @var Account $account */
        $account = $this->account;

        $inventoryIds = Cache::get('account-' . $account->id . '-inventory-sync', []);

        $inventoryIds = array_unique($inventoryIds);

        if (!empty($inventoryIds)) {
            $inventories = $account->shop->inventories()->whereIn('id', $inventoryIds)->get();
            foreach ($inventories as $inventory) {
                SyncInventory::dispatch($inventory)->onQueue('sync_inventories');
            }
        }
        Cache::forget('account-' . $account->id . '-inventory-sync');

    }
}
