<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', 'IndexController@index')->name('index');

Route::get('/users', 'UserController@index')->name('users.index');
Route::get('/user/{user}', 'UserController@show')->name('users.show');
Route::get('/user/{user}/shop/{shop}', 'ShopController@show')->name('shops.show');

Route::get('/shops', 'ShopController@index')->name('shops.index');

Route::get('tickets/category', 'TicketCategoryController@index')->name('tickets.category.index');

Route::get('tickets/', 'TicketController@index')->name('tickets.index');
Route::get('tickets/create', 'TicketController@create')->name('tickets.create');
Route::get('tickets/{ticket}', 'TicketController@show')->name('tickets.show');

Route::get('articles/category', 'ArticleCategoryController@index')->name('articles.category.index');

Route::get('articles', 'ArticleController@index')->name('articles.index');
Route::get('articles/create', 'ArticleController@create')->name('articles.create');
Route::get('articles/{article}/edit', 'ArticleController@edit')->name('articles.edit');



//Reporting
Route::get('/reports/{keyword}', 'ReportController@index')->name('reports.index');
