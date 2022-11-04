<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Console\Command;

class ImportOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:import {account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the order for an account';

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

        if (!$account->hasFeature(['orders', 'import_orders'])) {
            $this->error('Account does not have import orders feature.');
            return;
        }

        $this->info('Starting import for account ' . $account->id);

        $adapter = $account->getOrderAdapter();
        $adapter->import();

        $this->info('Finished import for account ' . $account->id);

    }
}
