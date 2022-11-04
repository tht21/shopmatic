<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SetShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
	    if (!Auth::guest()) {
            $shopId = $request->input('shop_id');
	        if (empty($shopId) || $request->route()->getName() == 'dashboard.accounts.redirect') {
                $shop = $request->session()->get('shop');
                if (empty($shop)) {

                    $shop = Auth::user()->shops()->orderBy('id')->first();
                    if ($shop) {
                        $request->session()->put('shop', $shop);
                    }
                } else {
                    try {
                        $newShop = Auth::user()->shops()->where('shops.id', $shop->id)->first();
                        if ($newShop) {
                            $request->session()->put('shop', $newShop);
                        } else {
                            //Shop deleted (?)
                            $request->session()->remove('shop');
                        }
                    } catch (\Exception $exception) {
                        // remove session if error occurred
                        $request->session()->remove('shop');
                    }
                }
            } else {
                $newShop = Auth::user()->shops()->where('shops.id', $shopId)->first();
                if ($newShop) {
                    $request->session()->put('shop', $newShop);
                } else {
                    $request->session()->remove('shop');
                }
            }

	    }
        return $next($request);
    }
}
