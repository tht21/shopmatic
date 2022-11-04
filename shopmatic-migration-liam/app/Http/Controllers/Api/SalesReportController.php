<?php

namespace App\Http\Controllers\Api;

use App\Models\Report;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->session()->get('shop');

        $result = $this->getReport($request, $shop);

        return $this->respond($result);
    }

    public function getReport($request, $shop)
    {
        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);
        $report = collect();

        if ($startDate->year == $endDate->year && $request->period != "year") {
            $condition = [];
            $null = null;
            $notNull = null;

            if ($request->period == 'day' || $request->period == "custom") {
                $condition = [
                    'day_of_year' => [$startDate->dayOfYear, $endDate->dayOfYear],
                ];
                $notNull = 'day';
            } elseif ($request->period == 'week') {
                $condition = [
                    'week' => [$startDate->week, $endDate->week],
                ];
                $null = 'day';
                $notNull = 'week';
            } elseif ($request->period == 'month') {
                $condition = [
                    'month' => [$startDate->month, $endDate->month]
                ];
                $null = ['day', 'week'];
                $notNull = 'month';
            }

            $key = (array_keys($condition))[0] ?? null;

            $report = Report::where('shop_id', $shop->id)
                ->when($condition, function ($query) use ($condition, $key) {
                    if (!is_null($key) && count($condition) > 0) {
                        return $query->whereBetween($key, $condition[$key]);
                    }
                    return $query;
                })
                ->when($notNull, function ($query) use ($notNull) {
                    if (!is_null($notNull)) {
                        return $query->whereNotNull($notNull);
                    }
                    return $query;
                })
                ->when($null, function ($query) use ($null) {
                    if (is_array($null)) {
                        $arr = [];
                        foreach ($null as $nl) {
                            array_push($arr, $query->whereNull($nl));
                        }
                        return $query->whereNull($null);
                    } elseif (!is_null($null)) {
                        return $query->whereNull($null);
                    }
                    return $query;
                })
                ->where('year', $endDate->year)
                ->orderByDesc('id')
                ->get();
        } else {
            if ($request->period == 'day') {
                $endOfYear = $startDate->copy()->endOfYear()->dayOfYear;
                $startOfYear = $endDate->copy()->startOfYear()->dayOfYear;

                $report = Report::where('shop_id', $shop->id)
                    ->whereNotNull('day')
                    ->where(function ($query) use ($startDate, $endOfYear) {
                        $query->whereBetween('day_of_year', [$startDate->dayOfYear, $endOfYear])
                            ->where('year', $startDate->year);
                    })->orWhere(function ($query) use ($startOfYear, $endDate) {
                        $query->whereBetween('day_of_year', [$startOfYear, $endDate->dayOfYear])
                            ->where('year', $endDate->year);
                    })
                    ->orderByDesc('id')
                    ->get();
            } elseif ($request->period == 'week') {
                $endOfYear = $startDate->copy()->endOfYear()->week;
                $startOfYear = $endDate->copy()->startOfYear()->week;

                $report = Report::with('integration')
                    ->where('shop_id', $shop->id)
                    ->whereNull('day')
                    ->whereNotNull('week')
                    ->where(function ($query) use ($startDate, $endOfYear) {
                        $query->whereBetween('week', [$startDate->week, $endOfYear])
                            ->where('year', $startDate->year);
                    })->orWhere(function ($query) use ($startOfYear, $endDate) {
                        $query->whereBetween('week', [$startOfYear, $endDate->week])
                            ->where('year', $endDate->year);
                    })
                    ->orderByDesc('id')
                    ->get();
            } elseif ($request->period == 'month') {
                $endOfYear = $startDate->copy()->endOfYear()->month;
                $startOfYear = $endDate->copy()->startOfYear()->month;

                $report = Report::with('integration')
                    ->where('shop_id', $shop->id)
                    ->whereNull('day')
                    ->whereNull('week')
                    ->whereNotNull('month')
                    ->where(function ($query) use ($startDate, $endOfYear, $startOfYear, $endDate) {
                        $query->where(function ($query) use ($startDate, $endOfYear) {
                            $query->whereBetween('month', [$startDate->month, $endOfYear])
                                ->where('year', $startDate->year);
                        })->orWhere(function ($query) use ($startOfYear, $endDate) {
                            $query->whereBetween('month', [$startOfYear, $endDate->month])
                                ->where('year', $endDate->year);
                        });
                    })
                    ->orderByDesc('id')
                    ->get();
            } elseif ($request->period == 'year') {
                $report = Report::with('integration')
                    ->where('shop_id', $shop->id)
                    ->whereNull('day')
                    ->whereNull('week')
                    ->whereNull('month')
                    ->where('year', $endDate->year)
                    ->orderByDesc('id')
                    ->get();
            }
        }

        if ($request->type == "Integration") {
            $group = $report->groupBy('integration.name');
            return ["report" => $report, "group" => $group];
        } elseif ($request->type == "Account") {
            $group = $report->groupBy('account.name');
            return ["report" => $report, "group" => $group];
        }

        return ["report" => $report, "group" => ""];
    }

    public function export(Request $request)
    {
        $year = $request->input('year');
        $date = $request->input('date');
        $totals = $request->input('totals');
        $revenue = $request->input('revenue');
        $cost_of_goods = $request->input('cost_of_goods');
        $gross_profit = $request->input('gross_profit');
        $margin = $request->input('margin');
        $tax = $request->input('tax');

        $headers[0] = [
            ['value' => $year, 'style' => ['range' => 'A1:C1']],
            ['value' => ''],
            ['value' => ''],
            ['value' => 'TOTALS BY SUMMARY', 'style' => ['range' => 'D1:H1']],
            ['value' => ''],
            ['value' => ''],
            ['value' => ''],
            ['value' => ''],
        ];

        $headers[1] = [
            ['value' => 'sales summary', 'style' => ['range' => 'A2:B2']],
            ['value' => ''],
            ['value' => strtoupper($date)],
            ['value' => 'revenue'],
            ['value' => 'cost of goods'],
            ['value' => 'gorss profit'],
            ['value' => 'margin'],
            ['value' => 'tax'],
        ];

        $headers[2] = [
            ['value' => 'total', 'style' => ['range' => 'A3:B3']],
            ['value' => ''],
            ['value' => sprintf('$ %.2f', (float)$totals)],
            ['value' => sprintf('$ %.2f', (float)$revenue)],
            ['value' => sprintf('$ %.2f', (float)$cost_of_goods)],
            ['value' => sprintf('$ %.2f', (float)$gross_profit)],
            ['value' => sprintf('%.2f ', (float)$margin) . '%'],
            ['value' => sprintf('$ %.2f', (float)$tax)],
        ];

        $arr = [
            [['value' => 'Revenue'],  ['value' => sprintf('$ %.2f', (float)$revenue)]],
            [['value' => 'Cost of Goods'], ['value' => sprintf('$ %.2f', (float)$cost_of_goods)]],
            [['value' => 'Gross Profit'], ['value' => sprintf('$ %.2f', (float)$gross_profit)]],
            [['value' => 'Margin'], ['value' => sprintf('%.2f ', (float)$margin) . '%']],
            [['value' => 'Tax'], ['value' => sprintf('$ %.2f', (float)$tax)]],
        ];

        foreach ($arr as $index => $col) {

            $headers[$index + 3] = $col;

            if ($index == 0) {
                array_unshift($headers[$index + 3], [
                    'value' => 'Total By Date Range',
                    'style' => [
                        'range' => 'A4:A' . (count($arr) + 3),
                        'vertical_alignment' => 'center'
                    ]
                ]);
            } else {
                array_unshift($headers[$index + 3], [
                    'value' => '',
                ]);
            }

        }

        for ($i = 0; $i < 2; $i++) {
            foreach ($headers[$i] as $column => $data) {
                $headers[$i][$column]['style']['alignment'] = 'center';
            }
        }

        try {
            return Excel::download(new GenerateExcel('Sales report', $headers, [], ['header_style' => ['bold' => true, 'auto_size' => true], 'freeze_pane' => 'D4']), 'sales_report_' . Carbon::now()->timestamp . '.xlsx');
        } catch (\Exception $e) {
            set_log_extra('shop_id', session('shop')->getId());
            Log::error($e);
            return null;
        }
    }
}
