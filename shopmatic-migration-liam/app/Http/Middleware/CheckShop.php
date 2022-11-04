<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
	    if (Auth::user()->shops->count() <= 0) {
            flash()->info('<i class="ni ni-bulb-61"></i> &nbsp;Let\'s start by creating a shop!');
	        return redirect(route('dashboard.shops.create'));
        }

        return $next($request);
    }
}
