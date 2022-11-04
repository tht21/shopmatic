<?php


namespace App\Utilities;

use App\Constants\FulfillmentStatus;
use App\Models\Report;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ReportGenerate
{
    protected $order;

    /**
     * ReportGenerate constructor.
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Insert report data for each order
     *
     * @return mixed
     */
    public function generate()
    {
        $date = Carbon::createFromFormat('g:i a, jS M Y', $this->order->order_placed_at);

        $default = [
            'integration_id' => $this->order->integration_id,
            'account_id' => $this->order->account_id,
            'shop_id' => $this->order->shop_id,
            'day' =>  $date->day,
            'week' => $date->isoWeek,
            'month' => $date->month,
            'year' => $date->year,
            'day_of_year' => $date->dayOfYear,
        ];

        $report = Report::where($default)->first();

        if (empty($report)) {
            //create and load with default values
            $report = Report::create(array_merge($default, ['currency' => $this->order->currency]))->fresh();
        }

        foreach ($this->order->items as $item) {

            $paid = Arr::except(FulfillmentStatus::toArray(),['CANCELLED', 'RETURNED']);

            if(in_array($item->fulfillment_status, $paid)) {
                $this->statusPaid($report, $item);
            }
            elseif ($item->fulfillment_status == FulfillmentStatus::CANCELLED()->getValue()) {
                $this->statusCancelled($report, $item);
            }
            elseif ($item->fulfillment_status == FulfillmentStatus::RETURNED()->getValue()) {
                $this->statusReturned($report, $item);
            }
        }
        return $report;
    }

    /**
     * Retrieve order items paid status
     *
     * @param $report
     * @param $item
     * @return mixed
     */
    public function statusPaid($report, $item)
    {
        //if order items id is not in order_item_ids, insert order items id in order_items_id

        if(!empty($report->order_item_ids) && in_array($item->id, $report->order_item_ids)) {
            return $report;

        } elseif (!empty($report->cancelled_order_item_ids) && in_array($item->id, $report->cancelled_order_item_ids)) {
            $report->order_item_ids = array_merge((array)$report->order_item_ids, [$item->id]);
            $report->cancelled_order_item_ids = array_diff($report->cancelled_order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementAll($report, $this->order);
            ReportCalculation::decrementCancelledOrder($report, $this->order);

        } elseif (!empty($report->returned_order_item_ids) && in_array($item->id, $report->returned_order_item_ids)) {
            $report->order_item_ids = array_merge((array)$report->order_item_ids, [$item->id]);
            $report->returned_order_item_ids = array_diff($report->returned_order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementAll($report, $this->order);
            ReportCalculation::decrementReturnedOrder($report, $this->order);

        } else {
            $report->order_item_ids = array_merge((array)$report->order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementAll($report, $this->order);
        }

        return $report;
    }

    /**
     * Retrieve order items cancelled status
     *
     * @param $report
     * @param $item
     * @return mixed
     */
    public function statusCancelled($report, $item)
    {
        //if order items is not in cancelled_order_item_ids, insert order items id in cancelled_order_item_ids

        if(!empty($report->order_item_ids) && in_array($item->id, $report->order_item_ids)) {
            $report->cancelled_order_item_ids = array_merge((array)$report->cancelled_order_item_ids, [$item->id]);
            $report->order_item_ids = array_diff($report->order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::decrementAll($report, $this->order);
            ReportCalculation::incrementCancelledOrder($report, $this->order);

        } elseif (!empty($report->cancelled_order_item_ids) && in_array($item->id, $report->cancelled_order_item_ids)) {
            return $report;

        } elseif (!empty($report->returned_order_item_ids) && in_array($item->id, $report->returned_order_item_ids)) {
            $report->cancelled_order_item_ids = array_merge((array)$report->cancelled_order_item_ids, [$item->id]);
            $report->returned_order_item_ids = array_diff($report->returned_order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::decrementReturnedOrder($report, $this->order);
            ReportCalculation::incrementCancelledOrder($report, $this->order);
        }
        else {
            $report->cancelled_order_item_ids = array_merge((array)$report->cancelled_order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementCancelledOrder($report, $this->order);
        }

        return $report;
    }

    /**
     * Retrieve order items returned status
     *
     * @param $report
     * @param $item
     * @return mixed
     */
    public function statusReturned($report, $item)
    {
        //if order items id is not in returned_order_item_ids, insert order items id in returned_order_item_ids

        if(!empty($report->order_item_ids) && in_array($item->id, $report->order_item_ids)) {

            $report->returned_order_item_ids = array_merge((array)$report->returned_order_item_ids, [$item->id]);
            $report->order_item_ids = array_diff($report->order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementReturnedOrder($report, $this->order);
            ReportCalculation::decrementAll($report, $this->order);

        } elseif (!empty($report->cancelled_order_item_ids) && in_array($item->id, $report->cancelled_order_item_ids)) {
            $report->returned_order_item_ids = array_merge((array)$report->returned_order_item_ids, [$item->id]);
            $report->cancelled_order_item_ids = array_diff($report->cancelled_order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementReturnedOrder($report, $this->order);
            ReportCalculation::decrementCancelledOrder($report, $this->order);

        } elseif (!empty($report->returned_order_item_ids) && in_array($item->id, $report->returned_order_item_ids)) {
            return $report;

        } else {
            $report->returned_order_item_ids = array_merge((array)$report->returned_order_item_ids, [$item->id]);
            $report->save();

            ReportCalculation::incrementReturnedOrder($report, $this->order);
        }

        return $report;
    }

    /**
     * Calculate based on period for current data in reports table
     */
    public function calculateAll()
    {
        $startYear = 2019;
        $today = Carbon::now();

        for ($year = $startYear; $year <= $today->year; $year++) {
            ReportCalculation::calculateYearly(Carbon::createFromDate($year));
        }

        for ($year = $startYear; $year <= $today->year; $year++) {
            for ($month = 1; $month <=12; $month++) {
                ReportCalculation::calculateMonthly(Carbon::createFromDate($year, $month));
            }
        }

        for ($year = $startYear; $year <= $today->year; $year++) {
            for ($week = 1; $week <= $today->isoWeeksInYear; $week++) {
                ReportCalculation::calculateWeekly($today->setISODate($year,$week)->startOfWeek());
            }
        }
    }


    /**
     * Insert all periods and ids for days
     *
     * @param $shops
     */
    public function insertDaily($shops)
    {
        $today = Carbon::now();
        $startYear = 2019;

        for ($year = $startYear; $year <= $today->year; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                for ($day = 1; $day <= Carbon::createFromDate($year, $month)->endOfMonth()->day; $day++) {
                    foreach ($shops as $keyShop => $shop) {
                        $integrations = $shop->groupBy('integration_id');
                        foreach ($integrations as $integrationKey => $integration) {
                            $accounts = $integration->groupBy('account_id');
                            foreach ($accounts as $accountKey => $account) {
                                $default = [
                                    'day' => $day,
                                    'week' => Carbon::createFromDate($year, $month, $day)->isoWeek,
                                    'month' => $month,
                                    'year' => $year,
                                    'day_of_year' => Carbon::createFromDate($year, $month, $day)->dayOfYear,
                                    'shop_id' => $keyShop,
                                    'integration_id' => $integrationKey,
                                    'account_id' => $accountKey
                                ];

                                $report = Report::where($default)->first();

                                if (empty($report)) {
                                    $report = Report::create($default);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Insert all periods and ids for week
     *
     * @param $shops
     */
    public function insertWeekly($shops)
    {
        $today = Carbon::now();
        $startYear = 2019;

        for ($year = $startYear; $year <= $today->year; $year++) {
            for ($week = 1; $week <= $today->isoWeeksInYear; $week++) {
                foreach ($shops as $keyShop => $shop) {
                    $integrations = $shop->groupBy('integration_id');
                    foreach ($integrations as $integrationKey => $integration) {
                        $accounts = $integration->groupBy('account_id');
                        foreach ($accounts as $accountKey => $account) {
                            $report = Report::whereNull('day')
                                ->where('year',  $year)
                                ->where('month', $today->setISODate($year, $week)->month)
                                ->where('week',  $week)
                                ->where('shop_id', $keyShop)
                                ->where('integration_id', $integrationKey)
                                ->where('account_id', $accountKey)
                                ->first();

                            if (empty($report)) {
                                $report = Report::create([
                                    'year' => $year,
                                    'month' => $today->setISODate($year, $week)->month,
                                    'week' => $week,
                                    'shop_id' => $keyShop,
                                    'integration_id' => $integrationKey,
                                    'account_id' => $accountKey
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Insert all periods and ids for months
     *
     * @param $shops
     */
    public function insertMonthly($shops)
    {
        $today = Carbon::now();
        $startYear = 2019;

        $default = [
            'day' => null,
            'week' => null,
        ];

        for ($year = $startYear; $year <= $today->year; $year++) {
            for($month = 1; $month <= 12; $month++) {
                foreach ($shops as $keyShop => $shop) {
                    $integrations = $shop->groupBy('integration_id');
                    foreach ($integrations as $integrationKey => $integration) {
                        $accounts = $integration->groupBy('account_id');
                        foreach ($accounts as $accountKey => $account) {
                            $report = Report::whereNull('day')
                                ->whereNull('week')
                                ->where('year',  $year)
                                ->where('month',  $month)
                                ->where('shop_id', $keyShop)
                                ->where('integration_id', $integrationKey)
                                ->where('account_id', $accountKey)
                                ->first();

                            if (empty($report)) {
                                $report = Report::create([
                                    'year' => $year,
                                    'month' => $month,
                                    'shop_id' => $keyShop,
                                    'integration_id' => $integrationKey,
                                    'account_id' => $accountKey
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Insert all periods and ids for years
     *
     * @param $shops
     */
    public function insertYearly($shops)
    {
        $today = Carbon::now();
        $startYear = 2019;

        $default = [
            'day' => null,
            'week' => null,
            'month' => null,
        ];

        for ($year = $startYear; $year <= $today->year; $year++) {
            foreach ($shops as $keyShop => $shop) {
                $integrations = $shop->groupBy('integration_id');
                foreach ($integrations as $integrationKey => $integration) {
                    $accounts = $integration->groupBy('account_id');
                    foreach ($accounts as $accountKey => $account) {
                        $report = Report::where($default)
                            ->where('year',  $year)
                            ->where('shop_id', $keyShop)
                            ->where('integration_id', $integrationKey)
                            ->where('account_id', $accountKey)
                            ->first();

                        if (empty($report)) {
                            $report = Report::create([
                                'year' => $year,
                                'shop_id' => $keyShop,
                                'integration_id' => $integrationKey,
                                'account_id' => $accountKey,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
