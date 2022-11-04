<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\AccountStatusUpdated' => [
            'App\Listeners\SendAccountStatusNotification',
        ],

        /*
         * Product related events
         */
        'App\Events\NewProductAlert' => [
            'App\Listeners\CreateProductAlert',
        ],
        'App\Events\ProductUpdated' => [
            'App\Listeners\UpdateTemporaryProductFields',
        ],

        'App\Events\OrderUpdated' => [
            /*
             * This is to update the order on any of the integrations if it's previously pushed
             */
            'App\Listeners\UpdateOrderOnOtherIntegrations',

            /*
             * This is to create the order on the integration if it hasn't been pushed yet
             */
            'App\Listeners\CreateOrderOnOtherIntegrations',

            /*
             * This is mainly used to update any reporting fields / temporary fields that uses orders
             */
            'App\Listeners\UpdateTemporaryOrderFields',
        ],

        /*
         * Category related events
         */
        'App\Events\IntegrationCategoryUpdated' => [
            'App\Listeners\UpdateIntegrationCategoryAttribute',
        ],
        'App\Events\IntegrationCategoryLinked' => [

            /*
             * This event is when an IntegrationCategory is linked to our internal Category.
             * This should update the `attributes` field
             */

            'App\Listeners\UpdateCategoryAttribute',
        ],
        'App\Events\ProductFailedToImport' => [

            /*
             * This event is when an IntegrationCategory is linked to our internal Category.
             * This should update the `attributes` field
             */

            'App\Listeners\UpdateProductImportTaskMessage',
        ],
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
        ],
        'Illuminate\Auth\Events\Logout' => [
            'App\Listeners\LogSuccessfulLogout',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
