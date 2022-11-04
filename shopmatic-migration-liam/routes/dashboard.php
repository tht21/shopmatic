<?php

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
 * The user need to has a shop first prior to getting here
 */

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'check.shop'], function() {

    /*
     * This checks whether or not they have added a payment method.
     * This is right after shop creation and is required prior to adding subscription
     *
     */
    Route::group([], function() {
        /*
         * This checks to make sure they have a valid plan or subscription prior to continuing
         */
        Route::group([], function() {
            Route::get('/', ['uses' => 'IndexController@index',
                'as' => 'index']);

            Route::get('notifications', ['uses' => 'IndexController@notifications',
                'as' => 'notifications.index']);

            Route::get('accounts', ['uses' => 'AccountController@index',
                'as' => 'accounts.index']);
            Route::get('accounts/create', ['uses' => 'AccountController@create',
                                                'as' => 'accounts.create']);

            /*
             * This is `any` because some integrations might POST instead of a normal GET redirect
             */
            Route::any('accounts/{integration}/redirect/{param?}', ['uses' => 'AccountController@handleRedirect',
                                                                'as' => 'accounts.redirect']);
            Route::get('accounts/{account}/reactivate', ['uses' => 'AccountController@handleReactivation',
                                                              'as' => 'accounts.reactivate']);
            Route::get('accounts/{account}/setup', ['uses' => 'AccountController@setup',
                                                                'as' => 'accounts.setup']);

            Route::get('account/categories', ['uses' => 'AccountCategoryController@index',
                'as' => 'account.categories.index']);
            Route::get('account/categories/create', ['uses' => 'AccountCategoryController@create',
                'as' => 'account.categories.create']);
            Route::get('account/categories/{account_category}/edit', ['uses' => 'AccountCategoryController@edit',
                'as' => 'account.categories.edit']);

            /*Route::get('orders/pickup', ['uses' => 'OrderController@pickup',
                                                    'as' => 'orders.pickup']);*/
            Route::get('orders/bulk', ['uses' => 'OrderController@bulk',
                                                'as' => 'orders.bulk']);

            Route::resource('orders', 'OrderController');

            Route::get('products/bulk', ['uses' => 'ProductController@bulk',
                'as' => 'products.bulk']);
            Route::get('products/bulk/categories', ['uses' => 'ProductController@bulkCategories',
                'as' => 'products.bulk.categories']);

            Route::get('products/import', ['uses' => 'ProductImportController@index',
                'as' => 'products.import']);
            Route::get('products/import/tasks', ['uses' => 'ProductImportController@tasks',
                'as' => 'products.import.tasks']);

            Route::get('products/export', ['uses' => 'ProductExportController@index',
                                                'as' => 'products.export']);
            Route::get('products/export/tasks', ['uses' => 'ProductExportController@tasks',
                                                'as' => 'products.export.tasks']);

            Route::get('products/alerts', ['uses' => 'ProductAlertController@index',
                'as' => 'products.alerts.index']);
            Route::resource('products', 'ProductController');

            /* inventory */
            Route::get('inventory', ['uses' => 'InventoryController@index',
                'as' => 'inventory.index']);

            Route::get('inventory/composite', ['uses' => 'InventoryController@composite',
                                          'as' => 'inventory.composite.index']);
            Route::get('inventory/update', ['uses' => 'InventoryController@update',
                                          'as' => 'inventory.update.index']);
            Route::get('inventory/{inventory}', ['uses' => 'InventoryController@show',
                                          'as' => 'inventory.show']);

            /* Shops User Management */
            Route::get('shop/users', ['uses' => 'UserManagementController@index',
                'as' => 'shop.users.index']);
            Route::get('shop/users/create', ['uses' => 'UserManagementController@create',
                'as' => 'shop.users.create']);

            /* User Management */
            Route::get('users/profile', ['uses' => 'UserController@index',
                'as' => 'users.index']);

            Route::get('tickets', ['uses' => 'TicketController@index',
                'as' => 'tickets.index']);
            Route::get('tickets/create', ['uses' => 'TicketController@create',
                'as' => 'tickets.create']);
            Route::get('tickets/{ticket}', ['uses' => 'TicketController@show',
                'as' => 'tickets.show']);

            Route::get('/report/{keyword}', 'ReportController@index')->name('reports.index');


            /* Shops User Management */
          Route::get('shop/users', ['uses' => 'UserManagementController@index',
                                      'as' => 'shop.users.index']);
          Route::get('shop/users/create', ['uses' => 'UserManagementController@create',
                                      'as' => 'shop.users.create']);
          Route::get('shop/users/{user}', ['uses' => 'UserManagementController@show',
                                      'as' => 'shop.users.show']);

        });

        Route::get('subscriptions', ['uses' => 'SubscriptionController@index',
            'as' => 'subscriptions.index']);

        Route::get('subscriptions/create', ['uses' => 'SubscriptionController@create',
            'as' => 'subscriptions.create']);
    });
    /* Billing */
    Route::get('billing', ['uses' => 'BillingController@index',
        'as' => 'billing.index']);
    Route::get('billing/create', ['uses' => 'BillingController@create',
                                       'as' => 'billing.create']);
    /* Report */
    Route::get('/reports/{keyword}', 'ReportController@index')->name('reports.index');

    /* Shop */
    Route::get('shops', ['uses' => 'ShopController@index',
                                      'as' => 'shops.index']);

    /* Logistic */
    Route::resource('logistics', 'LogisticsController');

    /* Chat */
    Route::get('chat', ['uses' => 'ChatController@index',
        'as' => 'chat.index']);
});

Route::get('shops/create', ['uses' => 'ShopController@create',
    'as' => 'shops.create']);
