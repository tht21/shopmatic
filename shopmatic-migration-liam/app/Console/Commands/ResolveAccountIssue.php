<?php

namespace App\Console\Commands;

use App\Constants\AccountStatus;
use App\Models\Integration;
use Illuminate\Console\Command;

class ResolveAccountIssue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:resolve:issue {integration} {region?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is to update all the account for the integration that has issues for the region.';

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
     */
    public function handle()
    {

        $integrationId = $this->argument('integration');

        /** @var Integration $integration */
        $integration = Integration::where('id', $integrationId)->first();
        if (!empty($integration)) {
            $regionId = $this->argument('region');
            if (empty($regionId)) {
                $integration->accounts()->where('status', AccountStatus::ISSUES())
                                        ->update(['status' => AccountStatus::ACTIVE()]);
            } else {
                $integration->accounts()->where('status', AccountStatus::ISSUES())
                                        ->where('region_id', $regionId)
                                        ->update(['status' => AccountStatus::ACTIVE()]);
            }
        } else {
            $this->error('Can\'t find integration with that ID.');
        }
    }
}
