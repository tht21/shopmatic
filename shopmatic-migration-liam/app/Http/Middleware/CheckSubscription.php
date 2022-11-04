<?php

namespace App\Http\Middleware;

use Closure;

class CheckSubscription
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

        /* skip for e2e*/
        if ($shop->e2e) {
            return $next($request);
        }

        if (!$shop->subscribed('saas')) {
            flash()->info('<i class="ni ni-bulb-61"></i> &nbsp;Please subscribe to continue!');
            return redirect(route('dashboard.subscriptions.index'));
        }

        return $next($request);
    }
}
