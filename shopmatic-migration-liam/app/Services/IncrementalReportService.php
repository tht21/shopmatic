<?php

namespace App\Services;

use App\Constants\FulfillmentStatus;
use App\Constants\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IncrementalReportService
{
    /*
     * This class is to reduce the load for reporting as it's per order basis instead of ALL orders
     */

    /**
     * Returns the daily report for either the shop, account, or integration
     *
     * @param $date
     * @param $currency
     * @param $shop
     * @param bool $createIfEmpty whether or not we should create the entry if it doesn't exist
     * @param null $accountId not null if report by account
     * @param null $integrationId not null if report by integration
     * @return
     * @throws \Exception
     */
    public static function getDayReport($date, $currency, $shop, $createIfEmpty = false, $accountId = null, $integrationId = null)
    {
        if (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }
        if (empty($currency)) {
            throw new \Exception('Currency required to pull reporting.');
        }
        $parameters = [
            'day' => $date->day,
            'week' => $date->week,
            'month' => $date->month,
            'day_of_year' => $date->dayOfYear,
            'year' => $date->year,
            'currency' => strtoupper(trim($currency))
        ];
        $parameters['account_id'] = $accountId;
        $parameters['integration_id'] = $integrationId;
        $parameters['shop_id'] = $shop->id;
        if ($createIfEmpty) {
            $report = Report::firstOrCreate($parameters);
            if ($report->wasRecentlyCreated) {
                // This is to make sure we get all the default values / etc as the proper values instead of null
                $report = $report->fresh();
            }
            return $report;
        }
        return Report::where($parameters)->first();
    }

    /**
     * Cascadi
     *
     * @param Order $order
     * @throws \Exception
     */
    public static function updateForOrder(Order $order)
    {
        // Use created at date if no order placed at date
        if (!empty($order->order_placed_at)) {
            $date = Carbon::parse($order->getOriginal('order_placed_at'));
        } else {
            $date = $order->created_at;
        }

        // Currency is required as that's how we're calculating
        if (empty($order->currency)) {
            set_log_extra('integration', $order->integration->toArray());
            set_log_extra('order', $order->toArray());
            throw new \Exception('Currency not set for order.');
        }

        $report = self::getDayReport($date, $order->currency, $order->shop, true, $order->account_id, $order->integration_id);

        $increase = false;
        $decrease = false;
        if (PaymentStatus::PAID()->same($order->payment_status) || PaymentStatus::PARTIALLY_REFUNDED()->same($order->payment_status)) {
            $increase = true;
        }

        // Technically we can use else here, but this is safer in case there are new payment statuses(?) - who knows
        if ($order->payment_status == PaymentStatus::CANCELLED()->getValue() || $order->payment_status == PaymentStatus::PROCESSING()->getValue() || $order->payment_status == PaymentStatus::UNPAID()->getValue() || $order->payment_status == PaymentStatus::REFUNDED()->getValue()) {
            $decrease = true;
        }

        // In a way it should never get here, as it should either return before this, or it MUST increase / decrease
        if (!$increase && !$decrease) {
            set_log_extra('order', $order->toArray());
            set_log_extra('shop', $order->shop->toArray());
            throw new \Exception('Error - No action taken for order for reporting.');
        }

        self::cascadeChange($report, $order, $increase);

    }

    /**
     * @param OrderItem $item
     *
     * @param $fulfillmentStatus
     * @param bool $forReset Whether or not this change is used to detect the negation
     * @return array
     * @retrun array
     */
    private static function getChangeForOrderItem(OrderItem $item, $fulfillmentStatus, $forReset = false)
    {
        $change = [];
        // if status < 30 means it's successful
        $revenue = $item->grand_total - $item->refunded_amount + $item->integration_discount;
        if ($fulfillmentStatus < FulfillmentStatus::CANCELLED()->getValue() && ($item->order->fulfillment_status != FulfillmentStatus::CANCELLED()->getValue() || $forReset)) {
            // We're adding integration_discount as that means the integration (Shopee / Lazada) is paying on behalf of the customer
            $change['total_revenue'] = $revenue;
            $change['total_discount	'] = $item->seller_discount;
            $change['total_shipping_fees'] = $item->actual_shipping_fee;
            $change['cost_of_goods'] = $item->cost_of_goods;
            $change['gross_profit'] = $change['total_revenue'] - $item->cost_of_goods;
            $change['order_item_ids'] = [$item->id];
        } elseif ($fulfillmentStatus == FulfillmentStatus::CANCELLED()->getValue() || $item->order->fulfillment_status == FulfillmentStatus::CANCELLED()->getValue()) {
            // This is because if the order is cancelled, all the items should be cancelled
            $change['total_cancelled_value'] = $revenue;
            $change['cancelled_order_item_ids'] = [$item->id];
        } elseif ($fulfillmentStatus == FulfillmentStatus::RETURNED()->getValue()) {
            $change['total_returned_value'] = $revenue;
            $change['returned_order_item_ids'] = [$item->id];
        }
        return $change;
    }

    /**
     * @param Order $order
     * @param Report $report
     * @return array
     */
    public static function getChangeForOrder(Order $order, Report $report)
    {
        $change = [];
        foreach ($order->items as $item) {
            if (!empty($report->order_item_ids) && in_array($item->id, $report->order_item_ids)
                && $item->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()
                && $order->fulfillment_status < FulfillmentStatus::CANCELLED()->getValue()) {
                continue;
            } elseif (!empty($report->cancelled_order_item_ids) && in_array($item->id, $report->cancelled_order_item_ids)
                && ($item->fulfillment_status == FulfillmentStatus::CANCELLED()->getValue()
                || $order->fulfillment_status == FulfillmentStatus::CANCELLED()->getValue())) {
                continue;
            } elseif (!empty($report->returned_order_item_ids) && in_array($item->id, $report->returned_order_item_ids)
                && $item->fulfillment_status == FulfillmentStatus::RETURNED()->getValue()) {
                continue;
            }
            $change = merge_and_sum_array($change, self::getChangeForOrderItem($item, $item->fulfillment_status));
        }
        return $change;
    }

    /**
     * This makes sure it changes / creates for the day, week, month and year
     *
     * @param $report
     * @param Order $order
     * @param $increase
     */
    private static function cascadeChange(Report $report, Order $order, $increase)
    {
        $change = [];

        $newItems = 0;
        $existing = 0;

        // We first perform a "reset" for the item if it exists
        foreach ($order->items as $item) {
            if ($increase) {
                if (!empty($report->order_item_ids) && in_array($item->id, $report->order_item_ids)) {
                    $existing++;
                    continue;
                } elseif (!empty($report->cancelled_order_item_ids) && in_array($item->id, $report->cancelled_order_item_ids)) {
                    $existing++;
                    $change = merge_and_sum_array($change, negate_array_values(self::getChangeForOrderItem($item, FulfillmentStatus::CANCELLED()->getValue(), true)));
                } elseif (!empty($report->returned_order_item_ids) && in_array($item->id, $report->returned_order_item_ids)) {
                    $existing++;
                    $change = merge_and_sum_array($change, negate_array_values(self::getChangeForOrderItem($item, FulfillmentStatus::RETURNED()->getValue(), true)));
                } else {
                    $newItems++;
                }
            } else {
                if (!empty($report->order_item_ids) && in_array($item->id, $report->order_item_ids)) {
                    $existing++;
                    // We need to reset this item first
                    // The status set here doesn't matter because we just want to get the negated change from the previous addition
                    $change = merge_and_sum_array($change, negate_array_values(self::getChangeForOrderItem($item, FulfillmentStatus::PENDING()->getValue(), true)));
                    continue;
                } elseif (!empty($report->cancelled_order_item_ids) && in_array($item->id, $report->cancelled_order_item_ids)) {
                    $existing++;
                } elseif (!empty($report->returned_order_item_ids) && in_array($item->id, $report->returned_order_item_ids)) {
                    $existing++;
                } else {
                    $newItems++;
                }
            }
        }
        // For new orders - we add "fixed" values for each order
        $change = merge_and_sum_array($change, self::getChangeForOrder($order, $report));
        $newVal = [];
        if ($existing <= 0) {
            if ($order->payment_status == PaymentStatus::PAID()->getValue() || $order->payment_status == PaymentStatus::PARTIALLY_REFUNDED()->getValue()) {
                $change['total_orders'] = 1;
                $change['total_discount'] = ($change['total_discount'] ?? 0) + $order->seller_discount;
                $change['total_shipping_fees'] = ($change['total_shipping_fees'] ?? 0) + $order->actual_shipping_fee;
            } elseif ($order->payment_status == PaymentStatus::CANCELLED()->getValue()) {
                $change['total_cancelled_orders'] = 1;
            } elseif ($order->payment_status == PaymentStatus::REFUNDED()->getValue()) {
                $change['total_returned_orders'] = 1;
            }
            $totalOrders = $report->total_orders + $report->total_cancelled_orders + $report->total_returned_orders;
            $change['total_item_quantity'] = $order->items->sum('quantity');
            $newVal['basket_value'] = $report->basket_value + ($order->grand_total - $report->basket_value) / ($totalOrders + 1);
            $newVal['basket_size'] = $report->basket_size + ($order->items->sum('quantity') - $report->basket_size) / ($totalOrders + 1);
            $change['total_customers'] = 1;
        }

        //TODO: We need to fix the counter for returned / cancelled / paid orders if they were the other status

        $change['total_discount'] = $order->seller_discount;

        $toChange = [];
        // Changing to make the change be "incremental" instead
        foreach ($change as $key => $value) {
            if (!is_array($value)) {
                $change[$key] = DB::raw($key . ' + ' . $value);
            } else {
                $toChange[$key] = $value;
                unset($change[$key]);
            }
        }
        // Changing to make the change be "incremental" instead
        foreach ($newVal as $key => $value) {
            $change[$key] = $value;
        }

        /*
         * NOTE: This part updates the same period twice, one is for the specific account & integration, while the other
         * is the account and integration being null, this allows us to easily get the shop's total for the period.
         */

        // Updating for newly selected day
        foreach ($toChange as $key => $value) {
            foreach ($value as $val) {
                if ($val < 0) {
                    $report->{$key} = array_diff($report->{$key} ?? [], [-$val]);
                } else {
                    $report->{$key} = array_merge($report->{$key} ?? [], [$val]);
                }
            }
        }
        $report->update($change);

        // Updating for week
        Report::updateOrCreate([
            'shop_id' => $order->shop_id,
            'day' => null,
            'day_of_year' => null,
            'week' => $report->week,
            'month' => $report->month,
            'year' => $report->year,
            'integration_id' => $order->integration_id,
            'account_id' => $order->account_id,
            'currency' => $report->currency,
        ])->update($change);

        // Updating for month
        Report::updateOrCreate([
            'shop_id' => $order->shop_id,
            'day' => null,
            'day_of_year' => null,
            'week' => null,
            'month' => $report->month,
            'year' => $report->year,
            'integration_id' => $order->integration_id,
            'account_id' => $order->account_id,
            'currency' => $report->currency,
        ])->update($change);

        // Updating for year
        Report::updateOrCreate([
            'shop_id' => $order->shop_id,
            'day' => null,
            'day_of_year' => null,
            'week' => null,
            'month' => null,
            'year' => $report->year,
            'integration_id' => $order->integration_id,
            'account_id' => $order->account_id,
            'currency' => $report->currency,
        ])->update($change);
    }

}
