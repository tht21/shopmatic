<?php

namespace App\Events;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class AccountStatusUpdated
{
    use Dispatchable, SerializesModels;

    public $account;

    public $old;

    /**
     * Create a new event instance.
     *
     * @param Account $account
     * @param AccountStatus $oldStatus
     */
    public function __construct(Account $account, AccountStatus $oldStatus)
    {
        $this->account = $account;
        $this->old = $oldStatus;
    }
}
