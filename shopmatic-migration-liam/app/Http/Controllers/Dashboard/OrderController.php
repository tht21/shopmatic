<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderController extends Controller
{

    /**
     * Show the order index
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', Order::class);
        return view('dashboard.orders.index');
    }

    /**
     * Show the order page
     *
     * @param Order $order
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items.inventory', 'account']);

        return view('dashboard.orders.show', compact('order'));
    }

    /**
     * Show the order item pickup list
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function pickup()
    {
        $this->authorize('index', Order::class);
        return view('dashboard.orders.pickup');
    }

    /**
     * Show the order index bulk
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bulk()
    {
        $this->authorize('index', Order::class);
        return view('dashboard.orders.bulk');
    }
}
