<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Console\Command;

class ImportBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brands:import {account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the products for an account';

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
        /** @var Account $account */
        $account = Account::find($this->argument('account'));
        if (empty($account)) {
            $this->error('Unable to find account with that ID.');
            return;
        }

        if ($account->status === AccountStatus::DISABLED()->getValue()) {
            $this->error('Account is currently disabled.');
            return;
        }

        if (!$account->hasFeature(['products', 'import_brands'])) {
            $this->error('Account does not have import brands feature.');
            return;
        }

        $this->info('Starting import for account ' . $account->id);

        $adapter = $account->getProductAdapter();
        $adapter->updateBrands();

        $this->info('Finished import for account ' . $account->id);

    }
}
