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

class RefreshTokenShopee extends Command
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
    protected $signature = 'account:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh token for shopee account';

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
        $lock = Cache::lock('Shopee-Refresh-Token-Lock-'.Integration::SHOPEE, $this->timeout);
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
                if (!empty($credentials) && isset($credentials['access_token_updated_time'])) {
                    $now = time();
                    $accessTokenLastUpdatedTime = $credentials['access_token_updated_time'];
                    $differenceInSeconds = abs($now - $accessTokenLastUpdatedTime);
                    /**
                     * If the access token last update time is more than 3.5 hours i.e 11880 seconds then get the new access token
                    */
                    if ($differenceInSeconds >= 11880) {
                        try {
                            $this->info('Shopee refresh token job Refresh Token Api called for account|' . $account->id);
                            $account->getProductAdapter()->refreshToken($account);
                            $this->info('Shopee refresh token job Refresh Token Api finished for account|' . $account->id);
                        } catch (\Exception $e) {
                            $this->info('Shopee refresh token job error for account|' . $account->id .'|'. $e->getMessage());
                            $this->error('Shopee refresh token error for account|'. $account->id .'|message|'. $e->getMessage());
                        }
                    } else {
                           $this->info('Shopee refresh token job for account|' . $account->id .'|Token Refresh Api not called as the access token is still active|Access Token last updated timestamp:'.$accessTokenLastUpdatedTime.'|Access Token updated :'.($differenceInSeconds/3600). ' hours ago');
                    }
                } 
                /*else if (!empty($credentials) && !isset($credentials['access_token_updated_time'])) {
                    try {
                            $this->info('Shopee refresh token job Refresh Token Api called for account|' . $account->id);
                            $account->getProductAdapter()->refreshToken($account);
                            $this->info('Shopee refresh token job Refresh Token Api finished for account|' . $account->id );
                    } catch (\Exception $e) {
                           $this->error('Shopee refresh token job Refresh Token Api error for account|'. $account->id .'|error|'. $e->getMessage());
                    }
                }*/
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
