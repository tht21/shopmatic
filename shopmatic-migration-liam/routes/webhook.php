<?php

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
*/

use Illuminate\Support\Facades\Route;

Route::post('stripe/webhook','WebhookController@handleWebhook');
