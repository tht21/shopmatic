<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatController extends Controller
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
        return view('dashboard.chat.index');
    }
}
