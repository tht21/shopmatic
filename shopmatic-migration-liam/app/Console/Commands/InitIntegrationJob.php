<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Jobs\RunIntegrationJob;
use Illuminate\Console\Command;

class InitIntegrationJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integration:job {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initiate integration job';

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
        $id = $this->argument('id');
        $integration = Integration::find($id);
        foreach ($integration->jobs as $region => $jobs) {
            foreach ($jobs as $method => $cron) {
                RunIntegrationJob::dispatchNow($integration, $region, $method);
            }
        }
    }
}
