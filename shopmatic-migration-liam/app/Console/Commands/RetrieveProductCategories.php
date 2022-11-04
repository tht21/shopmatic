<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Integration;
use Illuminate\Console\Command;

class RetrieveProductCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:categories {integration} {region_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves all the product categories for the integration';


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

            $region_id = $this->argument('region_id');

            foreach ($integration->region_ids as $regionId) {

                if (!empty($region_id) && $region_id != $regionId) {
                    continue;
                }

                /** @var Account $account */
                $account = Account::active()->where('integration_id', $integrationId)->where('region_id', $regionId)->first();
                if (empty($account)) {
                    $this->error('Unable to find an active account for the integration in region ' . $regionId  .' to retrieve categories.');
                    return;
                }

                $this->info('Retrieving categories for the integration in region ' . $regionId);

                $account->getProductAdapter()->updateCategories();

                $this->info('Successfully updated the categories for the integration in region ' . $regionId);
            }

        } else {
            $this->error('Can\'t find integration with that ID.');
        }
    }
}
