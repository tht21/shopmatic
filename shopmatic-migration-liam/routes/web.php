<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', 'HomeController@index')->name('index');
Route::get('about-us', 'HomeController@aboutUs')->name('about-us.index');
//Route::get('pricing', 'HomeController@pricing')->name('pricing.index');
Route::get('contact', 'HomeController@contact')->name('contact.index');
Route::post('contact', 'HomeController@contactSend')->name('contact.store');
Route::get('integrations', 'HomeController@integrations')->name('integrations.index');
Route::get('enterprise', 'HomeController@enterprise')->name('enterprise.index');
Route::get('mep-esg-edg', 'HomeController@endToEnd')->name('end-to-end.index');
Route::post('mep-esg-edg', 'HomeController@endToEndPost')->name('end-to-end.store');
Route::get('privacy-policy', 'HomeController@privacy')->name('privacy.index');
Route::get('terms-and-conditions', 'HomeController@terms')->name('terms.index');

Route::get('knowledgebase', 'ArticleController@index')->name('articles.index');
Route::get('knowledgebase/{article}', 'ArticleController@show')->name('articles.show');

Route::post(
    'stripe/webhook',
    '\App\Http\Controllers\Webhook\StripeController@handleWebhook'
);

Route::post('api/auth/login', ['uses' => '\App\Http\Controllers\Api\AuthController@index',
                                        'as' => 'auth.login']);

if (config('app.env') === 'production') {
    Route::domain('beta.combinesell.com')->group(function () {
	    Auth::routes(['verify' => true]);
	});
} else {
	Auth::routes(['verify' => true]);
}