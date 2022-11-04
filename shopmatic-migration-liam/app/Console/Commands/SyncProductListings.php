<?php

namespace App\Console\Commands;

use App\Constants\AccountStatus;
use App\Models\Account;
use Illuminate\Console\Command;

class SyncProductListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listings:sync {account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs the product listings for an account';

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
     */
    public function handle()
    {
        /** @var Account $account */
        $account = Account::find($this->argument('account'));
        if (empty($account)) {
            $this->error('Unable to find account with that ID.');
            return;
        }

        if (!$account->status->equals(AccountStatus::ACTIVE())) {
            $this->error('Account is currently not active.');
            return;
        }

        if (!$account->hasFeature(['products', 'import_products'])) {
            $this->error('Account does not have import products feature.');
            return;
        }

        $this->info('Starting syncing for account ' . $account->id);

        $adapter = $account->getProductAdapter();
        $adapter->sync();

        $this->info('Finished sync for account ' . $account->id);
    }
}
