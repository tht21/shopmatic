<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Integration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;
        $integrations = Integration::get();

        $view = 'dashboard.reports.'.$keyword.'.index';

        return view($view, compact('request', 'integrations'));
    }
}
