<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Integration;
use Illuminate\Console\Command;

class RetrieveProductBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:brands {integration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $integrationId = $this->argument('integration');
        $integration = Integration::where('id', $integrationId)->first();
        if (!empty($integration)) {
            foreach ($integration->region_ids as $regionId) {
                /** @var Account $account */
                $account = Account::active()->where('integration_id', $integrationId)->where('region_id', $regionId)->first();
                if (empty($account)) {
                    $this->error('Unable to find an active account for the integration in region ' . $regionId  .' to retrieve brands.');
                    return;
                }

                $this->info('Retrieving brands for the integration in region ' . $regionId);

                $account->getProductAdapter()->updateBrands();

                $this->info('Successfully updated the brands for the integration in region ' . $regionId);
            }

        } else {
            $this->error('Can\'t find integration with that ID.');
        }
    }
}
