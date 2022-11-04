<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\IncrementalReportService;
use Illuminate\Console\Command;

class OrderIncremental extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:incremental {order}';

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
     */
    public function handle()
    {
        $id = $this->argument('order');
        $order = Order::find($id);
        if (empty($order)) {
            $this->error('Order not found.');
            return;
        }
        IncrementalReportService::updateForOrder($order);
    }
}
