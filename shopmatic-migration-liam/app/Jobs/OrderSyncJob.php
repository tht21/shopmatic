<?php

namespace App\Jobs;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    
    public $timeout = 1800;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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

        // This is in case it's queued but the account got deleted / disabled
        $account = $this->account->fresh();

        if (empty($account)) {
            return;
        }
        if (empty($this->account->shop)) {
            $account->delete();
            return;
        }

        if (!$account->hasFeature(['orders', 'import_orders'])) {
            set_log_extra('account', $account->toArray());
            throw new \Exception('Account does not support import order, but job called.');
        }

        if (!$account->hasFeature(['orders', 'sync_orders'])) {
            set_log_extra('account', $account->toArray());
            throw new \Exception('Account turned off sync_order, but job called.');
        }

        // This might happen when the account got disabled or required re-authentication right before this was executed
        if (!$account->status->equals(AccountStatus::ACTIVE())) {
            return;
        }

        // To ensure we have only one instance of the sync running, it's the same as
        // the timeout to ensure that we release it if it does timeout

        /** @var Lock $lock */
        $lock = Cache::lock('account-order-sync-' . $account->id, $this->timeout);

        if ($lock->get()) {

            $adapter = $account->getOrderAdapter();

            if (empty($adapter)) {
                $lock->forceRelease();
                set_log_extra('account', $account->toArray());
                throw new \Exception('Unable to get order adapter for account.');
            }
            $time_start = microtime(true);
            $adapter->sync();

            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start)*1000;
            $debugLogEnd = '[OrderSyncJob]Debug Log|Account Id|'.$account->id.'|Shop Id|'.$account->shop_id.'|Integration Id|'.$account->integration_id.'|Account Name|'.$account->name.'|Region|'.$account->region_id.'|End At|'.date('Y-m-d H:i:s').'|Total Execution Time|'.$execution_time.' Milliseconds';
            Log::info($debugLogEnd);
            // Release the lock once it's done
            $lock->forceRelease();
        }

    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        set_log_extra('account', $this->account->toArray());
        Log::error($exception);
    }

}
