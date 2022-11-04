<?php

namespace App\Listeners;

use App\Constants\OrderType;
use App\Events\OrderUpdated;
use App\Services\IncrementalReportService;
use App\Utilities\ReportGenerate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateTemporaryOrderFields implements ShouldQueue
{

    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param OrderUpdated $event
     * @return ReportGenerate $event
     * @throws \Exception
     */
    public function handle(OrderUpdated $event)
    {
        // Technically if it should only be calculated in report if we need to modify it's stock
//        if ($event->order->shouldModifyStock()) {
//            IncrementalReportService::updateForOrder($event->order);
//        }
    }
}
