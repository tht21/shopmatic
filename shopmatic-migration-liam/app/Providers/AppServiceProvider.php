<?php

namespace App\Providers;

use App\Console\Commands\ModelMakeCommand;
use App\Constants\JobStatus;
use App\Jobs\ProductImportJob;
use App\Models\Account;
use App\Models\Order;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Observers\AccountObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductInventoryObserver;
use App\Observers\ProductListingObserver;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use URL;
use Illuminate\Support\Str;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->extend('command.model.make', function ($command, $app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.env') === 'production') {
            \Bouncer::cache();
            URL::forceScheme('https');
        }
        ProductImage::observe(ProductImageObserver::class);
        ProductListing::observe(ProductListingObserver::class);
        ProductInventory::observe(ProductInventoryObserver::class);
        Account::observe(AccountObserver::class);
        Order::observe(OrderObserver::class);
        Queue::failing(function (JobFailed $event) {
            $job = $event->job;
            if ($job instanceof ProductImportJob) {
                $status = $job->task->status;
                if ($status instanceof JobStatus) {
                    $status = $status->getValue();
                }
                if ($status != JobStatus::FAILED()->getValue()) {
                    $job->status = JobStatus::FAILED()->getValue();
                    $job->save();
                }
            }
        });

        /**
         * environment local , staging, production
         * Log query
         * App::environment()
         *
        */
        DB::listen(function ($query) {
            $data = $this->mappingParamQuery($query);
            /*try {
                Log::channel('querylog')->info($data);
            } catch (\Throwable $th) {}*/
        });

    }

    function mappingParamQuery($query) {
        $result = '';
        $bindings = array_map(function ($item) {
            if (!is_numeric($item)) {
                return "'" . $item . "'";
            }
            return $item;
        }, $query->bindings);
        $result .= Str::replaceArray('?', $bindings, $query->sql) . " \n ";
        $result .= 'Time: '  . $query->time . " \n";
        return $result;
    }

}
