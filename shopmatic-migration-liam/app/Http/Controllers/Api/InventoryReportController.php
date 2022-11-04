<?php

namespace App\Http\Controllers\Api;

use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->session()->get('shop');

        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);

        $type = $request->type;
        $measure = $request->measure;
        $period = $request->period;
        $filter = $request->filter;

        $result = $this->getReport($period, $shop, $startDate, $endDate);

        return $this->respond($result);
    }

    public function getReport($period, $shop, $startDate, $endDate)
    {
        
    }

    public function export()
    {
        //todo: export to csv
    }
}
