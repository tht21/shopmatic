<?php

namespace App\Console\Commands;

use App\Jobs\OrderSyncJob;
use App\Jobs\ProcessOrderInventory;
use App\Jobs\ProcessOrderListing;
use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Console\Command;
use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\Lock;

class RefreshTokenShopeeForV1Seller extends Command
{
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:token_v1_seller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh token for shopee v1 seller account';

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
     * @throws \Exception
     */
    public function handle()
    {
       $this->info('*** Shopee refresh token job Initiated ***');
        /** @var Lock $lock */
        $lock = Cache::lock('Shopee-Refresh-Token-V1-Seller-Lock-'.Integration::SHOPEE, $this->timeout);
        $isLockAcquired = $lock->get();
        if ($isLockAcquired) {
            $this->info('*** Shopee refresh token job atomic locked acquired and started ***');
            /**
            * Only Active Shopee Accounts
            */
            $accounts = Account::active()->where('integration_id', Integration::SHOPEE)->get();
            if (empty($accounts)) {
                // Release the lock
                $lock->forceRelease();
                $this->info('*** Unable to find shopee accounts.Atomic lock released ***');
                $this->error('*** Unable to find shopee accounts.Atomic lock released ***');
                return;
            }
            foreach ($accounts as $account) {
                $credentials = $account->credentials;
                if (!empty($credentials) && !isset($credentials['access_token_updated_time'])) {
                    try {
                            $this->info('Shopee refresh token job Refresh Token Api called for account|' . $account->id);
                            $account->getProductAdapter()->refreshToken($account);
                            $this->info('Shopee refresh token job Refresh Token Api finished for account|' . $account->id );
                    } catch (\Exception $e) {
                           $this->error('Shopee refresh token job Refresh Token Api error for account|'. $account->id .'|error|'. $e->getMessage());
                    }
                }
            }
            // Release the lock
            $lock->forceRelease();
            $this->info('*** Shopee refresh token job finished.Atomic Lock Released ***');
            echo "*** Shopee refresh token job finished.Atomic Lock Released *** \n";
        } else {
            $this->info("*** Shopee refresh token job failed to acquire lock.Skipping... *** \n ");
        }
    }
}
