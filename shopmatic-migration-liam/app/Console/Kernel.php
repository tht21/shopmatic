<?php

namespace App\Console;

use App\Models\Integration;
use App\Jobs\RunIntegrationJob;
use App\Jobs\SyncOrders;
use App\Jobs\SyncProductListing;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        //$schedule->job(new SyncOrders(), 'sync_orders')->withoutOverlapping(30)->everyFiveMinutes();
        $schedule->job(new SyncOrders(), 'sync_orders')->withoutOverlapping(30)->onOneServer()->everyFiveMinutes();
        // We're doing this because we need to ensure all the orders are synced first.
        // Hence we're doing it on the 5th minute (Order should finish within 5 minutes as it will timeout in 5 mins)
        // Daily at 12am because this is a heavy task
        $schedule->job(new SyncProductListing())->cron('5 0 * * *');

        // Schedule all the integration jobs

        // The reason why we do this instead of scheduling all accounts here is it might take a while for it to queue here
        // So it's better if we push the scheduling of all accounts in a job.
        $integrations = Integration::all();
        foreach ($integrations as $integration) {
            if (!empty($integration->jobs)) {
                foreach ($integration->jobs as $region => $jobs) {
                    foreach ($jobs as $method => $cron) {
                        $schedule->job(new RunIntegrationJob($integration, $region, $method))->cron($cron);
                    }
                }
            }
        }

        // Snapshot to get horizon metrix
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // Command to archive all data in `audits` table older than 7 days - run it at 3am
        $schedule->command('audits:archive')->cron('5 3 * * *');

        // Shopee Refresh Access Token
        $schedule->command('account:token')->cron('*/5 * * * *');

        //Import Categories Form MP's on Friday morning 4:00 AM SGT
        $schedule->command('init:mpcategories')->weeklyOn(5, '4:00')->onOneServer();

        //Import Categories from Shopee on Friday morning 4:30 AM SGT
        $schedule->command('init:category Shopee')->weeklyOn(5, '4:30')->onOneServer();
   
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
