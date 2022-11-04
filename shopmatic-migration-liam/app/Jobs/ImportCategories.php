<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ImportCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 7200;

    protected $integration;
    protected $region_id;

    /**
     * Create a new job instance.
     *
     * @param $integration
     */
    public function __construct($integration = null, $region_id = null)
    {
        $this->integration = $integration;
        $this->region_id = (int)$region_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', "-1");

        $integrations = $this->integration;
        if (!empty($integrations)) {
            if (!is_array($integrations)) {
                $integrations = [$integrations];
            }
        } else {
            $integrations = Integration::all();
        }
        /** @var Integration $integration */
        foreach ($integrations as $integration) {
            foreach ($integration->region_ids as $regionId) {
                if ($integration->hasFeature($regionId, ['products', 'import_categories'])) {
                        try {
                            /** @var Account $account */
                            $account = Account::active()->where('integration_id', $integration->id)->where('region_id',
                                $regionId)->first();

                            // No valid credentials to fetch the categories
                            if (empty($account)) {
                                continue;
                            }
                            echo "**** Import Categories Started|Integration Id|$integration->id|Region Id|$regionId ****\n";
                            Log::info('Import Categories Started|Integration Id|'.$integration->id.'|Region Id|'. $regionId);
                            $account->getProductAdapter()->updateCategories();
                            Log::info('Import Categories Ended|Integration Id|'.$integration->id.'|Region Id|'. $regionId);
                            echo "**** Import Categories Ended|Integration Id|$integration->id|Region Id|$regionId ****\n";
                        } catch (\Exception $e) {
                            echo "**** Import Categories Started|Integration Id|$integration->id|Region Id|$regionId|Message|". $e->getMessage() ." ****\n";
                            Log::info('Import Categories Error|Integration Id|'.$integration->id.'|Region Id|'. $regionId. '|Message|' .$e->getMessage());
                        }
                    }
            }
        }
    }
}
