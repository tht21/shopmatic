<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | This array holds the credentials for services
    |
    */
    'lazada' => [
        'app_key' => env('LAZADA_APP_KEY'),
        'secret' => env('LAZADA_SECRET'),
        'redirect' => env('LAZADA_REDIRECT_URL'),
        'campaign_sale' => env('LAZADA_CAMPAIGN_SALE', 0),
    ],
    'shopee' => [
        'partner_id' => env('SHOPEE_PARTNER_ID'),
        'partner_key' => env('SHOPEE_PARTNER_KEY'),
        'redirect' => env('SHOPEE_REDIRECT')
    ],
    'shopify' => [
        'api_key' => env('SHOPIFY_API_KEY'),
        'secret' => env('SHOPIFY_SECRET'),
        'redirect' => env('SHOPIFY_REDIRECT_URI')
    ],
    'vend' => [
        'client_id' => env('VEND_CLIENT_ID'),
        'secret' => env('VEND_CLIENT_SECRET'),
        'redirect' => env('VEND_REDIRECT_URI')
    ],
    'honestbee' => [
        'api_key' => env('HONESTBEE_API_KEY')
    ],
    'supermom' => [
        'domain'        => env('SUPERMOM_DOMAIN'),
        'client_id'     => env('SUPERMOM_CLIENT_ID'),
        'client_secret' => env('SUPERMOM_CLIENT_SECRET'),
        'redirect'      => env('SUPERMOM_REDIRECT_URI')
    ],
    'wordpress' => [
        'return_url'    => env('WORDPRESS_RETURN_URL'),
        'callback_url'  => env('SUPERMOM_CALLBACK_URL')
    ],
    'slack' => [
        'webhook'       => env('SLACK_WEBHOOK'),
        'name'          => env('SLACK_NAME'),
        'icon'          => env('SLACK_FAVICON'),
    ],
    'xero' => [
        'client_id' => env('XERO_CLIENT_ID'),
        'client_secret' => env('XERO_SECRET'),
        'redirect' => env('XERO_REDIRECT'),
    ],
    'amazon' => [
        'app_id' => env('AMAZON_APP_ID'),
        'client_id' => env('AMAZON_CLIENT_ID'),
        'client_secret' => env('AMAZON_CLIENT_SECRET'),
        'access_key' => env('AMAZON_ACCESS_KEY'),
        'secret_key' => env('AMAZON_SECRET_KEY'),
        'redirect' => env('AMAZON_REDIRECT_URI'),
        'role_arn' => env('AMAZON_ROLE_ARN')
    ],
];
