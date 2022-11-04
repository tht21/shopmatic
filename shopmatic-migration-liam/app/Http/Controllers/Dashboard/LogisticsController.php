<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{

    /**
     * Show the logistics index
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
//        $this->authorize('index', Order::class);
        return view('dashboard.logistics.index');
    }

    /**
     * Create logistics page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('dashboard.logistics.create');
    }

}
