<?php

namespace App\Observers;

use App\Constants\AccountStatus;
use App\Jobs\OrderSyncJob;
use App\Jobs\ProcessOrderInventory;
use App\Jobs\ProcessOrderListing;
use App\Jobs\ProductListingSyncJob;
use App\Models\Account;

class AccountObserver
{

    /**
     * Handle the account "updating" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function updating(Account $account)
    {
        $status = $account->status;
        if ($status instanceof AccountStatus) {
            $status = $status->getValue();
        }
        if ($account->isDirty('status')) {
            if ($account->getOriginal('status') != AccountStatus::ACTIVE()->getValue() && $status == AccountStatus::ACTIVE()->getValue()) {
                OrderSyncJob::withChain([
                    new ProcessOrderListing($account),
                    new ProcessOrderInventory($account),
                    new ProductListingSyncJob($account)
                ])->dispatch($account);
            }
        }
    }
    
}
