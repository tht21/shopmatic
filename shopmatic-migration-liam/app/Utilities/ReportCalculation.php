<?php

namespace App\Utilities;

use App\Models\Report;
use App\Models\Order;
use Carbon\Carbon;

class ReportCalculation
{
    public static function calculateRevenue($order)
    {
        return $order->grand_total;
    }

    public static function countOrder($order)
    {
        return $order->items()->sum('quantity');
    }

    public static function countCustomers($order)
    {
        return 0;
    }

    public static function calculateGrossProfit($order)
    {
        $gross_profit = 0;

        foreach($order->items as $item) {
            $gross_profit += ($item->item_price - $item->cost_of_goods) * $item->quantity;
        }

        return $gross_profit;
    }

    public static function calculateDiscount($order)
    {
        return $order->seller_discount;
    }

    public static function calculateBasketValue($order)
    {
        $basket_value = 0;

        foreach($order->items as $item) {
            $basket_value += $item->grand_total / $item->quantity;
        }

        return $basket_value;

    }

    public static function calculateBasketSize($order)
    {
        $orders = Order::where('integration_id', $order->integration_id)
            ->where('shop_id', $order->shop_id)
            ->where('account_id', $order->account_id)
            ->whereDate('created_at', Carbon::parse($order->created_at))
            ->with('items')
            ->get();

        $items = $orders->pluck('items')->flatten();

        $quantity = 0;

        foreach ($items as $item) {
            $quantity += $item->quantity;
        }

        return $quantity/$orders->count();
    }

    public static function calculateDiscountPercentage($order)
    {
        $percentage = 0;

        foreach ($order->items as $item) {
            $percentage += (($item->seller_discount / $item->item_price) * 100 ) * $item->quantity;
        }

        return $percentage;
    }

    public static function calculateCostOfGoods($order)
    {
        $cost_of_goods = 0;

        foreach($order->items as $item) {
            $cost_of_goods += $item->cost_of_goods;
        }

        return $cost_of_goods;
    }

    public static function calculateTax($order)
    {
        $tax = 0;

        foreach($order->items as $item) {
            $tax += $item->tax;
        }

        return $tax;
    }

    public static function incrementCancelledOrder($report, $order)
    {
        return $report->update(
            [
                'total_cancelled_orders' => $report->total_cancelled_orders + 1,
                'total_cancelled_value' => $report->total_cancelled_value + self::calculateRevenue($order),
            ]
        );
    }

    public static function incrementReturnedOrder($report, $order)
    {
        return $report->update(
            [
                'total_returned_orders' => $report->total_returned_orders + 1,
                'total_returned_value' => $report->total_returned_value + self::calculateRevenue($order),
            ]
        );
    }

    public static function decrementCancelledOrder($report, $order)
    {
        return $report->update(
            [
                'total_cancelled_orders' => $report->total_cancelled_orders - 1,
                'total_cancelled_value' => $report->total_cancelled_value - self::calculateRevenue($order),
            ]
        );
    }

    public static function decrementReturnedOrder($report, $order)
    {
        return $report->update(
            [
                'total_returned_orders' => $report->total_returned_orders - 1,
                'total_returned_value' => $report->total_returned_value - self::calculateRevenue($order),
            ]
        );
    }

    public static function incrementAll($report, $order)
    {
        return $report->update(
            [
                'total_revenue' => str_replace(',', '', $report->total_revenue) + self::calculateRevenue($order),
                'total_orders' => $report->total_orders + self::countOrder($order),
                'total_customers' => $report->total_customers + self::countCustomers($order),
                'basket_value' => $report->basket_value + self::calculateBasketValue($order),
                'basket_size' => $report->basket_size + self::calculateBasketSize($order),
                'total_discount' => $report->total_discount + self::calculateDiscount($order),
                'gross_profit' => $report->gross_profit + self::calculateGrossProfit($order),
                'cost_of_goods' => $report->cost_of_goods + self::calculateCostOfGoods($order),
                'tax' => $report->tax + self::calculateTax($order),
            ]
        );
    }

    public static function decrementAll($report, $order)
    {
        return $report->update(
            [
                'total_revenue' => str_replace(',', '', $report->total_revenue) - self::calculateRevenue($order),
                'total_orders' => $report->total_orders - self::countOrder($order),
                'total_customers' => $report->total_customers - self::countCustomers($order),
                'basket_value' => $report->basket_value - self::calculateBasketValue($order),
                'basket_size' => $report->basket_size - self::calculateBasketSize($order),
                'total_discount' => $report->total_discount - self::calculateDiscount($order),
                'gross_profit' => $report->gross_profit - self::calculateGrossProfit($order),
                'cost_of_goods' => $report->cost_of_goods - self::calculateCostOfGoods($order),
                'tax' => $report->tax - self::calculateTax($order),
            ]
        );
    }

    public static function incrementDuration($report, $duration)
    {
        return $report->update([
            'total_revenue' => str_replace(',', '', $report->total_revenue) + $duration->total_revenue,
            'total_orders' => $report->total_orders + $duration->total_orders,
            'total_customers' => $report->total_customers + $duration->total_customers,
            'basket_value' => $report->basket_value + $duration->basket_value,
            'basket_size' => $report->basket_size + $duration->basket_size,
            'total_discount' => $report->total_discount + $duration->total_discount,
            'gross_profit' => $report->gross_profit + $duration->gross_profit,
            'cost_of_goods' => $report->cost_of_goods + $duration->cost_of_goods,
            'tax' => $report->tax + $duration->tax,
            'total_cancelled_orders' => $report->total_cancelled_orders + $duration->total_cancelled_orders,
            'total_cancelled_value' => $report->total_cancelled_value + $duration->total_cancelled_value,
            'total_returned_orders' => $report->total_returned_orders + $duration->total_returned_orders,
            'total_returned_value' => $report->total_returned_value + $duration->total_returned_value,
        ]);
    }

    public static function calculateWeekly($date)
    {
        $condition = [
            'week' => $date->week,
            'month' => $date->month,
            'year' => $date->year,
        ];

        $durations = Report::where($condition)->get();

        $report = [];

        foreach ($durations as $duration) {
            $report = Report::where([
                'day' => null,
                'week' => $duration->week,
                'shop_id' => $duration->shop_id,
                'integration_id' => $duration->integration_id,
                'account_id' => $duration->account_id
            ])->first();


            if(empty($report)) {
                $report = Report::create(array_merge($condition, [
                    'day' => null,
                    'shop_id' => $duration->shop_id,
                    'integration_id' => $duration->integration_id,
                    'account_id' => $duration->account_id
                ]));
            }

            self::incrementDuration($report, $duration);
        }

        return $report;
    }

    public static function calculateMonthly($date)
    {
        $durations = Report::where('month', $date->month)->where('year', $date->year)->get();

        $report = [];

        foreach ($durations as $duration) {
            $report = Report::where([
                'day' => null,
                'week' => null,
                'month' => null,
                'year' => $duration->year,
                'shop_id' => $duration->shop_id,
                'integration_id' => $duration->integration_id,
                'account_id' => $duration->account_id
            ])->first();

            if(empty($report)) {
                $report = Report::create([
                    'day' => null,
                    'month' => $date->month,
                    'year' => $date->year,
                    'shop_id' => $duration->shop_id,
                    'integration_id' => $duration->integration_id,
                    'account_id' => $duration->account_id
                ]);
            }

            self::incrementDuration($report, $duration);
        }

        return $report;
    }

    public static function calculateYearly($date)
    {
        $durations = Report::where('year', $date->year)->get();
        $report = [];

        foreach ($durations as $duration) {
            $report = Report::where([
                'day' => null,
                'week' => null,
                'month' => null,
                'year' => $duration->year,
                'shop_id' => $duration->shop_id,
                'integration_id' => $duration->integration_id,
                'account_id' => $duration->account_id
            ])->first();

            if(empty($report)) {
                $report = Report::create([
                    'day' => null,
                    'shop_id' => $duration->shop_id,
                    'integration_id' => $duration->integration_id,
                    'account_id' => $duration->account_id,
                    'year', $date->year
                ]);
            }

            self::incrementDuration($report, $duration);
        }

        return $report;
    }
}
