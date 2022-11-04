<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\Route;

Route::any('webhook/integrations/{integration}', ['uses' => 'IntegrationController@webhook', ['as' => 'integrations.webhook']]);
Route::any('webhook/accounts/{account}', ['uses' => 'AccountController@webhook', ['as' => 'accounts.webhook']]);

Route::group(['middleware' => 'auth'], function() {
    /* UserController */
    Route::apiResource('users', 'UserController');

    /* UserManagementController */
    Route::get('shop/users', ['uses' => 'UserManagementController@index',
                                        'as' => 'shop.users.index']);

    /* AccountController */
    Route::apiResource('accounts', 'AccountController');
    Route::get('accounts/{account}/setup', ['uses' => 'AccountController@setupIndex',
                                                'as' => 'accounts.setup.index']);

    Route::any('accounts/{account}/setup/{action}', ['uses' => 'AccountController@action',
                                                'as' => 'accounts.setup.action']);

    Route::put('accounts/{account}/settings', ['uses' => 'AccountController@updateSettings',
                                                'as' => 'accounts.settings.update']);

    /* ShopController */
    Route::post('shops/assign/{users}', ['uses' => 'ShopController@assign',
                                              'as' => 'shops.assign']);
    Route::post('shops/dismiss/{users}', ['uses' => 'ShopController@dismiss',
                                               'as' => 'shops.dismiss']);
    Route::post('shops/invite', ['uses' => 'ShopController@invite',
                                      'as' => 'shops.invite']);
    Route::post('shops/switch/{shop}', ['uses' => 'ShopController@switch',
                                      'as' => 'shops.switch']);

    Route::apiResource('shops', 'ShopController');

    /* BrandController */
    Route::apiResource('brands', 'BrandController');

    /* ProductAlertController */
    Route::get('products/alerts', ['uses' => 'ProductAlertController@index',
                                        'as' => 'products.alerts.index']);
    Route::post('products/alerts/{alert}/dismiss', ['uses' => 'ProductAlertController@dismiss',
                                                         'as' => 'products.alerts.dismiss']);
    Route::post('products/alerts/dismiss', ['uses' => 'ProductAlertController@dismissAll',
                                                 'as' => 'products.alerts.dismissAll']);

    /* ProductListingController */
    Route::get('product/listing/{product}', ['uses' => 'ProductListingController@index',
                                                'as' => 'products.listing.index']);

    /* ProductExportController */
    Route::get('products/export/tasks', ['uses' => 'ProductController@exportTasks',
                                                    'as' => 'products.export.tasks']);
    Route::get('products/export/{product}/accounts/{account}/export', ['uses' => 'ProductExportController@export',
                                                                            'as' => 'products.export.export']);
    Route::get('products/export/all/accounts/{account}', ['uses' => 'ProductExportController@exportAll',
                                                        'as' => 'products.export.exportAll']);
    Route::post('products/export/{product}/save', ['uses' => 'ProductExportController@save',
                                                        'as' => 'products.export.save']);
    Route::get('products/export/download', ['uses' => 'ProductExportController@download',
                      'as' => 'products.export.download']);
    Route::put('products/export/tasks/{export_excel_task}', ['uses' => 'ProductController@updateExportTask',
                                                        'as' => 'products.export.tasks.update']);
    
    Route::apiResource('products/export', 'ProductExportController');

    /* ProductController */
    Route::get('products/import/tasks', ['uses' => 'ProductController@importTasks',
                                              'as' => 'products.import.tasks']);
    /*Route::get('products/{product}/listings/{listing}', ['uses' => 'ProductController@getListing',
                                                              'as' => 'products.listing.show']);*/
    Route::post('accounts/{account}/products', ['uses' => 'ProductController@import',
                                                     'as' => 'accounts.products.import']);
    Route::post('products/{product}/listings/{listing}', ['uses' => 'ProductController@updateProductListing',
                                                               'as' => 'products.listing.update']);
    Route::put('products/listings/{listing}/toggle-enable', ['uses' => 'ProductController@toggleEnable',
                                                                'as' => 'products.toggleEnable']);
    Route::delete('products/delete-orphan-products', ['uses' => 'ProductController@deleteOrphanedProducts',
                                                                'as' => 'products.deleteOrphanProducts']);
    Route::delete('products/delete-orphan-product-variants', ['uses' => 'ProductController@deleteOrphanedProductVariants',
                                                                'as' => 'products.deleteOrphanProductVariants']);

    Route::put('products/bulk/category/update', ['uses' => 'ProductController@bulkUpdateCategory',
                                                                'as' => 'products.bulk.category.update']);
    Route::delete('products/delete-variant', ['uses' => 'ProductController@deleteVariant', 'as' => 'products.variant.delete']);

    Route::apiResource('products', 'ProductController');

    /* AccountCategoryController */
    Route::post('accounts/{account}/categories/import', ['uses' => 'AccountCategoryController@import',
                                                       'as' => 'accounts.categories.import']);

    Route::get('accounts/{account}/categories', ['uses' => 'AccountCategoryController@index',
                                                    'as' => 'accounts.categories.index']);
    Route::post('accounts/{account}/categories', ['uses' => 'AccountCategoryController@store',
                                                    'as' => 'accounts.categories.store']);
    Route::put('accounts/{account}/categories/{account_category}', ['uses' => 'AccountCategoryController@update',
                                                    'as' => 'accounts.categories.update']);

    /* CategoryController */
    Route::get('categories/{category}/attributes', ['uses' => 'CategoryController@getAttributes',
                                                         'as' => 'categories.attributes']);

    Route::apiResource('categories', 'CategoryController');

    /* InventoryController */
    Route::get('inventory/status', ['uses' => 'InventoryController@status',
                                       'as' => 'inventory.status']);
    Route::get('inventory/logs', ['uses' => 'InventoryController@logsIndex',
                                       'as' => 'inventory.logs.index']);
    Route::get('inventory/{inventory}/logs', ['uses' => 'InventoryController@logs',
                                                   'as' => 'inventory.logs']);
    Route::get('inventory/{inventory}/listings', ['uses' => 'InventoryController@listingIndex',
                                                       'as' => 'inventory.listings.index']);
    Route::put('inventory/{inventory}/listings/{listing}', ['uses' => 'InventoryController@listingUpdate',
                                                                 'as' => 'inventory.listings.update']);
    Route::post('inventory/{inventory}/bundle', ['uses' => 'InventoryController@storeBundle',
                                                      'as' => 'inventory.store.bundle']);
    Route::post('inventory/{inventory}/bundle/remove', ['uses' => 'InventoryController@destroyBundle',
                                                             'as' => 'inventory.bundle.remove']);
    Route::put('inventory/{inventory}/bundle', ['uses' => 'InventoryController@updateBundle',
                                                     'as' => 'inventory.bundle.update']);
    Route::delete('inventory/delete-orphan', ['uses' => 'InventoryController@deleteOrphanedInventories',
                                       'as' => 'inventory.deleteOrphan']);

    Route::get('inventory/report/export', 'InventoryController@export');

    Route::apiResource('inventory', 'InventoryController');

    /* IntegrationController */
    Route::get('integrations/redirect', ['uses' => 'IntegrationController@getAuthorizationLink',
                                              'as' => 'integrations.redirect']);

    Route::apiResource('integrations', 'IntegrationController');

    /* BillingController */
    Route::post('billing/{payment_method}/default', ['uses' => 'BillingController@setAsDefault',
                                                          'as' => 'billing.default']);

    Route::apiResource('billing', 'BillingController');

    /* InvoiceController */
    Route::apiResource('invoices', 'InvoiceController');

    /* SubscriptionController */
    Route::post('subscriptions/cancel', ['uses' => 'SubscriptionController@cancel',
                                              'as' => 'subscriptions.cancel']);

    Route::apiResource('subscriptions', 'SubscriptionController');

    /* ReportController */
    Route::get('reports/monthly/sales', ['uses' => 'ReportController@monthlySales',
                                              'as' => 'reports.monthly.sales']);

    /* ProductImportController */
    Route::get('import/download/template', ['uses' => 'ProductImportController@downloadTemplate',
                                                 'as' => 'import.download.template']);
    Route::post('import/upload/excel', ['uses' => 'ProductImportController@uploadExcel',
                                             'as' => 'import.upload.excel']);

    /* TicketCategoryController */
    Route::get('tickets/category', ['uses' => 'TicketCategoryController@index',
                                         'as' => 'tickets.category.index']);
    Route::post('tickets/category', ['uses' => 'TicketCategoryController@store',
                                          'as' => 'tickets.category.store']);
    Route::put('tickets/category/{category}', ['uses' => 'TicketCategoryController@update',
                                                    'as' => 'tickets.category.update']);
    Route::delete('tickets/category/{category}', ['uses' => 'TicketCategoryController@destroy',
                                                       'as' => 'tickets.category.destroy']);

    /* TicketController */
    Route::post('tickets/{ticket}/replies', ['uses' => 'TicketController@reply',
                                                  'as' => 'tickets.reply']);
    Route::get('tickets/{ticket}/trails', ['uses' => 'TicketController@trail',
                                                'as' => 'tickets.trail']);

    Route::apiResource('tickets', 'TicketController');

    /* ArticleCategoryController */
    Route::get('articles/category', ['uses' => 'ArticleCategoryController@index',
                                          'as' => 'articles.category.index']);
    Route::post('articles/category', ['uses' => 'ArticleCategoryController@store',
                                           'as' => 'articles.category.store']);
    Route::put('articles/category/{category}', ['uses' => 'ArticleCategoryController@update',
                                                     'as' => 'articles.category.update']);

    /* ArticleTagController */
    Route::delete('articles/category/{category}', ['uses' => 'ArticleTagController@destroy',
                                                        'as' => 'articles.category.destroy']);
    Route::get('articles/tag', ['uses' => 'ArticleTagController@index',
                                     'as' => 'articles.tag.index']);

    /* ArticleController */
    Route::apiResource('articles', 'ArticleController');

    /* OrderController */
    Route::get('orders/export/tasks', ['uses' => 'OrderController@exportTasks',
                                                    'as' => 'orders.export.tasks']);
    Route::put('orders/export/tasks/{export_excel_task}', ['uses' => 'OrderController@updateExportTask',
                                                    'as' => 'orders.export.tasks.update']);
    Route::any('orders/bulk/{orders}/{integration}/{action}', ['uses' => 'OrderController@bulkAction',
                                                                'as' => 'orders.bulk.action']);
    Route::any('orders/{order}/{integration}/{action}', ['uses' => 'OrderController@action',
                                                              'as' => 'orders.action']);
    Route::post('accounts/{account}/orders', ['uses' => 'OrderController@import',
                                                     'as' => 'accounts.orders.import']);
    Route::get('orders/pickup', ['uses' => 'OrderController@pickup',
                                                    'as' => 'orders.pickup']);
    Route::get('orders/download', ['uses' => 'OrderController@download',
                                                    'as' => 'orders.download']);

    Route::apiResource('orders', 'OrderController');

    /* OrderStatusController */
    Route::get('/orderStatus/listing/{order}', 'OrderStatusController@listing');

    /* RetailDashboardController */
    Route::get('/report/retails', 'RetailDashboardController@index');
    Route::get('/report/products', 'RetailDashboardController@product');

    /* SalesReportController */
    Route::post('/report/sales', 'SalesReportController@index');
    Route::get('/report/sales/export', 'SalesReportController@export');

    /* InventoryReportController */
    Route::post('/report/inventory', 'InventoryReportController@index');

    /* ProductImportController */
    Route::post('inventory/upload/csv', ['uses' => 'InventoryImportController@uploadCsv',
        'as' => 'inventory.upload.csv']);
    Route::get('inventory/download/csv', ['uses' => 'InventoryImportController@downloadCsv',
        'as' => 'inventory.download.csv']);

    /* ChatController */
    Route::apiResource('/chat', 'ChatController');
    Route::apiResource('/chat/faq/category', 'ChatFaqController');

    Route::apiResource('logistics', 'LogisticsController');

    /* CrossListingExportController */
    Route::get('cross-listing/export/download', ['uses' => 'CrossListingExportController@download',
                                                  'as' => 'cross-listing.export.download']);
    /* CrossListingExportController */
    Route::get('cross-listing/export/{task}', ['uses' => 'CrossListingExportController@show',
                                                  'as' => 'cross-listing.export.show']);
});
