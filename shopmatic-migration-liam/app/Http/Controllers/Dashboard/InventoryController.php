<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ProductInventory;

class InventoryController extends Controller
{

    /**
     * Show the inventory index
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', ProductInventory::class);
        return view('dashboard.inventory.index');
    }

    /**
     * Show the inventory detail page
     *
     * @param ProductInventory $inventory
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(ProductInventory $inventory)
    {
        $this->authorize('view', $inventory);
        $inventory->load(['listings.account.integration', 'listings.variant']);
        return view('dashboard.inventory.show', compact('inventory'));
    }

    /**
     * Show the composite inventory index
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function composite()
    {
        $this->authorize('index', ProductInventory::class);
        return view('dashboard.inventory.composite');
    }

    /**
     * Show the inventory detail page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update()
    {
        $this->authorize('index', ProductInventory::class);
        return view('dashboard.inventory.update');
    }

}
