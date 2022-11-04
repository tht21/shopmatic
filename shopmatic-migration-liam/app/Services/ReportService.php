<?php


namespace App\Services;


use App\Models\Order;
use App\Models\Report;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReportService
{

    /*
     * This is for NON incremental reporting - This forces a full recalculation of all orders
     */
    public static function recalculateForShop(Shop $shop)
    {
        // We first clear all reports
        Report::whereShopId($shop->id)->delete();
        $integrationIds = [];
        foreach ($shop->accounts as $account) {
            if (!in_array($account->integration_id, $integrationIds)) {
                $integrationIds[] = $account->integration_id;
            }
            // On average one order is ~3KB including indexing and etc. 500 x 3 ~= 1.5MB per chunk - not inclusive of current mem usage
            $account->orders()->orderBy('order_placed_at', 'ASC')->chunk(500, function($orders) {
                $dates = [];
                /** @var Order $order */
                foreach ($orders as $order) {
                    if (!empty($order->order_placed_at)) {
                        $date = Carbon::parse($order->getOriginal('order_placed_at'));
                        $date = $date->format('Y-m-d');
                    } else {
                        $date = $order->created_at->format('Y-m-d');
                    }
                    if (empty($dates[$date])) {
                        $dates[$date] = [];
                    }
                    $dates[$date][] = $order;
                }
                foreach ($dates as $date => $dayOrders) {
                    // TODO: We can probably do a bulk query / sum here instead of looping through each order
                    // But it's probably quicker now that we do this and more accurate with the incremental one
                    foreach ($dayOrders as $order) {
//                        try {
                            IncrementalReportService::updateForOrder($order);
//                        } catch (\Exception $e) {
//                            set_log_extra('order', $order);
//                            Log::error($e);
//                        }
                    }
                }
            });
        }
        
        //TODO: Calculate for custom orders / those not in any accounts
    }
    
}