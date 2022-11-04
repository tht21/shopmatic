<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;

class ShopController extends Controller
{

    /**
     * Show the user page
     *
     * @param User $user
     * @param Shop $shop
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(User $user, Shop $shop)
    {
        $this->authorize('view', [$user, $shop]);

        $shop->makeVisible('e2e');

        return view('admin.shops.show', compact('user', 'shop'));
    }
}
