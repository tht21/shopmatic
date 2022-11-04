<?php

namespace App\Listeners;

use App\Constants\AccountStatus;
use App\Events\AccountStatusUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAccountStatusNotification implements ShouldQueue
{
    
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  AccountStatusUpdated  $event
     * @return void
     */
    public function handle(AccountStatusUpdated $event)
    {
        //TODO: Add email notification
        
        $account = $event->account;
        switch ($account->status) {
            
            // This is to notify customers there are slight issues with the integration, hence it's paused
            case AccountStatus::ISSUES():
                return;
                
            // This is to notify the customer that the account requires reauthentication
            case AccountStatus::REQUIRE_AUTH():
                return;
        }
        
        // This is if it previously had issues, and we resolved it.
        if ($event->old === AccountStatus::ISSUES()) {
            if ($account->status == AccountStatus::ACTIVE()) {
                // Send notification
                return;
            }
        }
    }
}
