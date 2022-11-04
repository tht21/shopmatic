<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductAlert;
use App\Models\Shop;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    /**
     * Returns an array of the monthly sales for the given year, if no year is given, the current year will be used
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function monthlySales(Request $request)
    {

    }
}
