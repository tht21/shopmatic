<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\Order;
use App\Models\Shop;
use App\Services\ReportService;
use App\Utilities\ReportGenerate;
use Illuminate\Console\Command;

class GenerateReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual Report Data Generation';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shopId = $this->ask('Enter Shop ID - Blank for all shops');
        
        $shops = [];
        if (!empty($shopId)) {
            $shop = Shop::find($shopId);
            if (empty($shop)) {
                $this->error('Unable to find shop with that ID.');
                return;
            }
            $shops[] = $shop;
        } else {
            $shops = Shop::all();
        }
        foreach ($shops as $shop) {
            $start = microtime(true);
            ReportService::recalculateForShop($shop);
            $time = microtime(true) - $start;
            $this->info('You have successfully generate report data for shop ' . $shop->name .  'in ' . $time);
        }
    }
}
