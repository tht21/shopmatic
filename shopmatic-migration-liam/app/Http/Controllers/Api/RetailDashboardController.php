<?php


namespace App\Http\Controllers\Api;

use App\Models\OrderItem;
use App\Models\Report;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RetailDashboardController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->has('dashboard')) {
            return $this->respond($this->dashboard($request));
        }

        $shop = $request->session()->get('shop');
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $type = $request->type;

        if ($type == 'day') {
            $query = $this->getByDay($shop, $startDate, $endDate);
        } elseif ($type == 'week') {
            $query = $this->getByWeek($shop, $startDate, $endDate);
        } elseif ($type == 'month') {
            $query = $this->getByMonth($shop, $startDate, $endDate);
        } elseif ($type == 'year') {
            $query = $this->getByYear($shop, $startDate, $endDate);
        }

        $query = $query->get();

        return $this->respond($query);
    }


    /**
     * Retrieves sales data for dashboard
     *
     * @param Request $request
     * @return array
     */
    public function dashboard(Request $request)
    {
        $shop = $request->session()->get('shop');
        $data = [];

        //get Yearly data for this year
        $report = $shop->reports()
            ->where('year', Carbon::now()->year)
            ->where('shop_id', $shop->id)
            ->whereNull('day_of_year')
            ->whereNull('week')
            ->whereNull('month')
            ->get();

        $data['yearly_data'] = $report;

        //get daily data for today and yesterday
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $today_report = $shop->reports()
            ->where('day_of_year', $today->dayOfYear)
            ->where('year', $today->year)
            ->get();

        $yesterday_report = $shop->reports()
            ->where('day_of_year', $yesterday->dayOfYear)
            ->where('year', $yesterday->year)
            ->get();

        $today_data = [
            'total_discount' => 0,
            'total_revenue' => 0,
            'total_orders' => 0,
            'total_orders_item' => 0,
        ];

        foreach ($today_report as $item) {
            $today_data['total_discount'] += $item['total_discount'] ;
            $today_data['total_revenue'] += $item['total_revenue'];
            $today_data['total_orders'] += $item['total_orders'];
            $today_data['total_orders_item'] += count((array) $item['order_item_ids']) ;
        }
        $data['today_data'] = $today_data;

        $yesterday_data = [
            'total_discount' => 0,
            'total_revenue' => 0,
            'total_orders' => 0,
            'total_orders_item' => 0,
        ];
        foreach ($yesterday_report as $item) {

            $yesterday_data['total_discount'] += $item['total_discount'];
            $yesterday_data['total_revenue'] += $item['total_revenue'];
            $yesterday_data['total_orders'] += $item['total_orders'];
            $yesterday_data['total_orders_item'] += count((array) $item['order_item_ids'])  ;

        }
        $data['yesterday_data'] = $yesterday_data;

        //best selling products
        $data['best_selling_products'] = $this->bestSellingProducts($request);

        //sales analytics
        $end_date = Carbon::now()->endOfYear();
        $start_date = $end_date->copy()->startOfYear();

        $reports = $shop->reports()
            ->whereBetween('month', [$start_date->month, $end_date->month])
            ->where('year', $end_date->year)
            ->whereNull('day')
            ->whereNull('week')
            ->addSelect(
                DB::raw("round(sum(total_revenue), 2) as total_revenue"),
                DB::raw("sum(total_orders) as total_orders"),
                'month'
                )
            ->groupBy('month')
            ->get()
            ->toArray();

        // change key as report month
        $monthlyData = [];
        foreach ($reports as $report) {
            $monthlyData[$report['month']] = $report;
        }

        // Filled up missing month
        for ($i = $start_date->month; $i <= $end_date->month; $i++) {
            if (!isset($monthlyData[$i])) {
                $monthlyData[$i]= [
                    "total_revenue" => 0.00,
                    "total_orders" => 0,
                    "month" => $i,
                ];
            }
        }
        // sort month
        ksort($monthlyData);
        /*$monthly_data = [];
        foreach ($report as $month) {
            $item = [
                'total_revenue' => round($month->sum('total_revenue'), 2),
                'total_orders' => $month->sum('total_orders'),
            ];
            $monthly_data[] = $item;
        }*/

        $data['monthly_data'] = $monthlyData;

        return $data;
    }

    /**
     * Retrieves report data by day
     *
     * @param $shop
     * @param $startDate
     * @param $endDate
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Builder[]|Collection
     */
    public function getByDay($shop, $startDate, $endDate)
    {

        $query = Report::with('integration')
            ->where('shop_id', $shop->id)
            ->whereNotNull('day');


        if ($startDate->year == $endDate->year) {
            $query = $query->whereBetween('day_of_year', [$startDate->dayOfYear, $endDate->dayOfYear])
                ->where('year', $endDate->year);
        } else {
            $endOfYear = $startDate->copy()->endOfYear()->dayOfYear;
            $startOfYear = $endDate->copy()->startOfYear()->dayOfYear;

            $query = $query
                ->where(function ($query) use ($startDate, $endOfYear) {
                    $query->whereBetween('day_of_year', [$startDate->dayOfYear, $endOfYear])
                        ->where('year', $startDate->year);
                })->orWhere(function ($query) use ($startOfYear, $endDate) {
                    $query->whereBetween('day_of_year', [$startOfYear, $endDate->dayOfYear])
                        ->where('year', $endDate->year);
                });
        }

        $query = $query->select(['currency', 'day', 'week', 'month', 'year' ,'day_of_year', DB::raw('sum(total_revenue) total_revenue'), DB::raw('sum(total_orders) total_orders'),
            DB::raw('sum(gross_profit) gross_profit'), DB::raw('sum(total_discount) total_discount'), DB::raw('avg(basket_value) basket_value'),
            DB::raw('avg(basket_size) basket_size')]);
        $query = $query->groupBy(['currency', 'day','week', 'month', 'year', 'day_of_year']);
        $query = $query->orderBy('day_of_year', 'asc');
        $query = $query->orderBy('year', 'asc');

        return $query;
    }

    /**
     * Retrieves report data by weeks
     *
     * @param $shop
     * @param $startDate
     * @param $endDate
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Builder[]|Collection
     */
    public function getByWeek($shop, $startDate, $endDate)
    {

        $query = Report::with('integration')
            ->where('shop_id', $shop->id)
            ->whereNotNull('day');

        if ($startDate->year == $endDate->year) {
            $query = $query->whereBetween('week', [$startDate->week, $endDate->copy()->startOf('week')->week])
                ->whereNotNull('week')
                ->whereNotNull('day')
                ->where('year', $endDate->year);
        } else {
            $endOfYear = $startDate->copy()->endOfYear()->week;
            $startOfYear = $endDate->copy()->startOfYear()->week;

            $query = $query->whereNull('day')
                ->whereNotNull('week')
                ->where(function ($query) use ($startDate, $endOfYear) {
                    $query->whereBetween('week', [$startDate->week, $endOfYear])
                        ->where('year', $startDate->year);
                })->orWhere(function ($query) use ($startOfYear, $endDate) {
                    $query->whereBetween('week', [$startOfYear, $endDate->week])
                        ->where('year', $endDate->year);
                });
        }

        $query = $query->select(['currency', 'week', 'year', DB::raw('sum(total_revenue) total_revenue'), DB::raw('sum(total_orders) total_orders'),
            DB::raw('sum(gross_profit) gross_profit'), DB::raw('sum(total_discount) total_discount'), DB::raw('sum(basket_value) basket_value'),
            DB::raw('sum(basket_size) basket_size'), DB::raw('min(day) day'), DB::raw('min(month) month')]);
        $query = $query->groupBy(['currency', 'week', 'year']);
        $query = $query->orderBy('week', 'asc');
        $query = $query->orderBy('year', 'asc');

        return $query;
    }


    /**
     * Retrieves report data by months
     *
     * @param $shop
     * @param $startDate
     * @param $endDate
     * @return \Illuminate\Database\Eloquent\Builder|Builder
     */
    public function getByMonth($shop, $startDate, $endDate)
    {

        $query = Report::with('integration')
            ->where('shop_id', $shop->id);

        $query = $query->whereBetween('month', [$startDate->month, $endDate->month])
            ->whereNotNull('day')
            ->whereNotNull('week')
            ->whereNotNull('month')
            ->whereBetween('year', [$startDate->year, $endDate->year]);

        $query = $query->select(['currency', 'month', 'year', DB::raw('sum(total_revenue) total_revenue'), DB::raw('sum(total_orders) total_orders'),
            DB::raw('sum(gross_profit) gross_profit'), DB::raw('sum(total_discount) total_discount'), DB::raw('sum(basket_value) basket_value'),
            DB::raw('sum(basket_size) basket_size'), DB::raw('min(day) day'), DB::raw('min(month) month')]);
        $query = $query->groupBy(['currency', 'month', 'year']);
        $query = $query->orderBy('month', 'asc');
        $query = $query->orderBy('year', 'asc');

        return $query;
    }

    /**
     * Retrieves report data by years
     *
     * @param $shop
     * @param $startDate
     * @param $endDate
     * @return \Illuminate\Database\Eloquent\Builder|Builder
     */
    public function getByYear($shop, $startDate, $endDate)
    {
        $query = Report::with('integration')
            ->where('shop_id', $shop->id)
            ->whereNotNull('day')
            ->whereNotNull('week')
            ->whereNotNull('month')
            ->whereBetween('year', [$startDate->year, $endDate->year]);


        $query = $query->select(['currency', 'year', DB::raw('sum(total_revenue) total_revenue'), DB::raw('sum(total_orders) total_orders'),
            DB::raw('sum(gross_profit) gross_profit'), DB::raw('sum(total_discount) total_discount'), DB::raw('sum(basket_value) basket_value'),
            DB::raw('sum(basket_size) basket_size'), DB::raw('min(day) day'), DB::raw('min(month) month')]);
        $query = $query->groupBy(['currency', 'year']);
        $query = $query->orderBy('year', 'asc');

        return $query;
    }

    /**
     * Retrieves report order items product
     *
     * @param Request $request
     * @return mixed
     */
    public function product(Request $request)
    {
        $shop = $request->session()->get('shop');

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        if ($startDate->year == $endDate->year) {
            $report = Report::with('integration')
                ->whereNotNull('day')
                ->whereNotNull('order_item_ids')
                ->where('shop_id', $shop->id)
                ->whereBetween('day_of_year', [$startDate->dayOfYear, $endDate->dayOfYear])
                ->where('year', $endDate->year)
                ->get();
        } else {
            $endOfYear = $startDate->copy()->endOfYear()->dayOfYear;
            $startOfYear = $endDate->copy()->startOfYear()->dayOfYear;

            $report = Report::with('integration')
                ->where('shop_id', $shop->id)
                ->whereNotNull('day')
                ->whereNotNull('order_item_ids')
                ->where(function ($query) use ($startDate, $endOfYear) {
                    $query->whereBetween('day_of_year', [$startDate->dayOfYear, $endOfYear])
                        ->where('year', $startDate->year);
                })->orWhere(function ($query) use ($startOfYear, $endDate) {
                    $query->whereBetween('day_of_year', [$startOfYear, $endDate->dayOfYear])
                        ->where('year', $endDate->year);
                })->get();
        }

        $orderItemIds = [];
        foreach ($report as $rpt) {
            if ($rpt->order_item_ids) {
                foreach ($rpt->order_item_ids as $order_item_id) {
                    array_push($orderItemIds, $order_item_id);
                }
            }
        }

        $items = OrderItem::with('product')
            ->selectRaw('product_id, SUM(grand_total) as revenue, COUNT(id) as item_sold')
            ->whereIn('id', $orderItemIds)
            ->groupBy('product_id')
            ->orderBy('revenue', 'desc')
            ->get();

        return $this->respond($items);
    }

    public function bestSellingProducts(Request $request)
    {
        $shop_id = $request->get('shop_id');

        if (!empty($shop_id)) {
            $shop = Shop::find($shop_id);
            $this->authorize('view', $shop);
            $query = $shop->products();
        } else {
            $shop = $request->session()->get('shop');
            $query = $shop->products();
        }

        //best selling products
        $query = $query->orderBy('total_quantity_sold', 'DESC')
            ->with('listings', 'listings.account', 'listings.integration')
            ->take(10)
            ->get();


        if ($request->has('dashboard')) {
            return $query;
        }

        return $this->respond($query);
    }
}
