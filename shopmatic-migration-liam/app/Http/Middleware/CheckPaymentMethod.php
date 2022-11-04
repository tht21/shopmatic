<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;

class CheckPaymentMethod
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');

        if (!$shop) {
            flash()->info('<i class="ni ni-bulb-61"></i> &nbsp;Let\'s start by creating a shop!');
            return redirect(route('dashboard.shops.create'));
        }

        /* skip for e2e*/
        if ($shop->e2e) {
            return $next($request);
        }

        if (!$shop->hasStripeId()) {
            $shop->createAsStripeCustomer();
        }

        try {
            if (!$shop->subscribed('saas')) {
                if ($shop->paymentMethods()->count() == 0) {
                    flash()->info('<i class="ni ni-bulb-61"></i> &nbsp;Please add a payment method to continue!');
                    return redirect(route('dashboard.billing.create'));
                } else if (!$shop->defaultPaymentMethod()) {
                    flash()->info('<i class="ni ni-bulb-61"></i> &nbsp;Please set a default payment method!');
                    return redirect(route('dashboard.billing.index'));
                }
            }
        } catch (\Exception $e) {
            flash()->info('<i class="ni ni-bulb-61"></i> &nbsp;Unable to connect Stripe. Please check your internet connection!');
                return redirect(route('dashboard.billing.index'));
        }

        return $next($request);
    }
}
