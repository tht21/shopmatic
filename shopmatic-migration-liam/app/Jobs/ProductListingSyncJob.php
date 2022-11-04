<?php

namespace App\Jobs;

use App\Constants\AccountStatus;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductListingSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 7200;

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
        $account = $this->account;

        // if account not active, dont proceed
        if (!$account->status->equals(AccountStatus::ACTIVE())) {
            return;
        }

        if (!$account->hasFeature(['products', 'import_products'])) {
            set_log_extra('account', $account->toArray());
            throw new \Exception('Account does not support import products, but job called.');
        }

        // To ensure we have only one instance of the sync running, it's the same as
        // the timeout to ensure that we release it if it does timeout

        /** @var Lock $lock */
        $lock = Cache::lock('product-listing-sync-' . $account->id, $this->timeout);

        if ($lock->get()) {

            $adapter = $account->getProductAdapter();

            if (empty($adapter)) {
                set_log_extra('account', $account->toArray());
                throw new \Exception('Unable to get product adapter for account.');
            }

            $adapter->sync();

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
