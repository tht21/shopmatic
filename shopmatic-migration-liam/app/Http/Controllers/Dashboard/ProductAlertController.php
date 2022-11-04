<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ProductAlert;

class ProductAlertController extends Controller
{

    /**
     * Show the product index
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', ProductAlert::class);
        return view('dashboard.products.alerts.index');
    }

}
