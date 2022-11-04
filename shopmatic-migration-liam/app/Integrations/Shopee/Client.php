<?php

namespace App\Integrations\Shopee;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use Psr\Http\Message\ResponseInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Client extends AbstractClient
{
    const URLS = [
        Region::SINGAPORE => 'https://partner.shopeemobile.com/api/v2/',
        Region::MALAYSIA => 'https://partner.shopeemobile.com/api/v2/',
    ];

    /**
     * SHOPEE SERVICE URL
     */
    const SERVICE_URL = 'https://partner.shopeemobile.com/api/v2';


    /*
     * This is the lists of methods that needs to be called after the account creation process.
     * This includes things such as outlets, account categories or anything else
     *
     * @var array
     */
    public $postAccountCreation = [];

    /**
     * Client constructor.
     *
     * @param Account $account
     * @param array $config
     */
    public function __construct(Account $account, $config = [])
    {
        $this->url = self::URLS[$account->region_id];

        parent::__construct($account, $config);
    }

    /**
     * Returns the URL for the authorization link
     *
     * @param $region
     *
     * @return string
     */
    /*public static function getAuthorizationLink ($region = null)
    {
        // Shopee uses the same authorization URL regardless of region
        $authUrl = 'https://partner.shopeemobile.com/api/v1/shop/auth_partner?';
        $partnerKey = config('combinesell.shopee.partner_key');
        $redirect = config('combinesell.shopee.redirect');
        $params = [
            'id' => config('combinesell.shopee.partner_id'),
            'token' => hash('sha256', $partnerKey . $redirect),
            'redirect' => $redirect
        ];

        $fullAuthLink = $authUrl . http_build_query($params);
        return filter_var($fullAuthLink, FILTER_SANITIZE_URL);
    }*/

    /**
     * Returns the URL for the authorization link
     *
     * @param $region
     *
     * @return string
     */
    public static function getAuthorizationLink($region = null)
    {
        $authUrl    = self::SERVICE_URL . '/shop/auth_partner?';
        $apiPath    = '/api/v2/shop/auth_partner';
        $partnerId = config('combinesell.shopee.partner_id');
        $secretKey = config('combinesell.shopee.partner_key');
        $redirect = config('combinesell.shopee.redirect');
        $timestamp = time();
        $baseString = $partnerId . $apiPath . $timestamp;
        $signature = hash_hmac('sha256', $baseString,  $secretKey, false);
        /**
         * Parameters
         */
        $params = [
            'partner_id' => $partnerId,
            'redirect' => $redirect,
            'timestamp' => $timestamp,
            'sign' => $signature
        ];
        $fullAuthLink = $authUrl . http_build_query($params);
        return filter_var($fullAuthLink, FILTER_SANITIZE_URL);
    }

    /**
     * Get Access Token Based on ShopId & Code from redirecturl returned post completion of authorization
     * @param input
     * @return mixed
     * @throws \Exception
     */
    public static function getAccessToken($input)
    {

        $shopId = $input['shop_id'];
        $code   =  $input['code'];
        $apiPath = '/api/v2/auth/token/get';
        $partnerId = config('combinesell.shopee.partner_id');
        $partnerKey = config('combinesell.shopee.partner_key');
        $timestamp = time();
        $baseString = $partnerId . $apiPath . $timestamp;
        $signature = hash_hmac('sha256', $baseString,  $partnerKey, false);

        $accessTokenUrl = self::SERVICE_URL . '/auth/token/get?';
        $params = [
            'partner_id' => $partnerId,
            'timestamp' => $timestamp,
            'sign' => $signature
        ];
        $fullAccessTokenUrl = $accessTokenUrl . http_build_query($params);
        $httpBody = json_encode([
            'code' => $code,
            'partner_id' => (int) config('combinesell.shopee.partner_id'),
            'shop_id' => (int) $shopId,
        ]);
        $parameters = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $httpBody,
        ];
        $client = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client->post($fullAccessTokenUrl, $parameters);

        if ($response->getStatusCode() === 200) {
            $body = json_decode($response->getBody()->getContents(), true);
            return $body;
        } else {

            set_log_extra('response', $response->getBody()->getContents());
            throw new \Exception('Unable to get access token for Shopee');
        }
    }

    /**
     * Get Refresh Token Based on ShopId & Previous RefreshToken
     * @param input
     * @return mixed
     * @throws \Exception
     */
    public static function refreshAccessToken($input)
    {

        $shopId = $input['shop_id'];
        $refreshToken   =  $input['refreshToken'];

        $apiPath = '/api/v2/auth/access_token/get';
        $partnerId = config('combinesell.shopee.partner_id');
        $partnerKey = config('combinesell.shopee.partner_key');
        $timestamp = time();
        $baseString = $partnerId . $apiPath . $timestamp;
        $signature = hash_hmac('sha256', $baseString,  $partnerKey, false);

        $refreshAccessTokenUrl = self::SERVICE_URL . '/auth/access_token/get?';
        $params = [
            'partner_id' => $partnerId,
            'timestamp' => $timestamp,
            'sign' => $signature
        ];
        $fullRefreshAccessTokenUrl = $refreshAccessTokenUrl . http_build_query($params);
        $httpBody = json_encode([
            'refresh_token' => $refreshToken,
            'partner_id' => (int) config('combinesell.shopee.partner_id'),
            'shop_id' => (int) $shopId,
        ]);
        $parameters = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $httpBody,
        ];
        $client = new \GuzzleHttp\Client(['verify' => false]);
        try {
            $response = $client->post($fullRefreshAccessTokenUrl, $parameters);

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                return $body;
            } else {
                Log::info('Shopee refresh token api|refreshAccessToken|Empty Response|RefreshToken Url|'.$fullRefreshAccessTokenUrl.'|Status Code|'.$response->getStatusCode().'|Parameters|'.$json_encode($parameters).'|Response|'.json_encode($response));
                set_log_extra('Shopee refreshAccessToken|response', $response->getBody()->getContents());
                return null;
            }
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Get Shopee Shop Info
     * @param access_token
     * @param shop_id
     * 
     * return mixed
     */

    public static function getShopInfo($accessToken, $shopId)
    {

        $accessToken    = $accessToken;
        $shopId         =  $shopId;
        $apiPath = '/api/v2/shop/get_shop_info';
        $partnerId = config('combinesell.shopee.partner_id');
        $partnerKey = config('combinesell.shopee.partner_key');
        $timestamp = time();
        /**
         * Join partner_id, api path(without the host), timestamp, access_token and shop_id to make a single string
         */
        $baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
        /**
         * Signature generated by (partner_id, api path, timestamp, access_token, shop_id and partner_key
         * via HMAC-SHA256 hashing algorithm
         */
        $signature = hash_hmac('sha256', $baseString, $partnerKey, false);
        $getShopUrl = self::SERVICE_URL . '/shop/get_shop_info?';
        $params = [
            'partner_id' => (int) $partnerId,
            'timestamp' => $timestamp,
            'access_token' => $accessToken,
            'shop_id' => (int) $shopId,
            'sign' => $signature
        ];
        $fullUrl = $getShopUrl . http_build_query($params);
        $fullUrl = filter_var($fullUrl, FILTER_SANITIZE_URL);

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client->get($fullUrl);
        if ($response->getStatusCode() === 200) {
            $body = json_decode($response->getBody()->getContents(), true);
            return $body;
        } else {
            set_log_extra('response', $response->getBody()->getContents());
            throw new \Exception('Unable to get shop info from Shopee');
        }
    }


    /**
     * Handle the redirect from shopee and generate the token.
     *
     * @param $input
     *
     * @return boolean
     * @throws \Exception
     */
    public static function handleRedirect($input)
    {
        $accessTokenResponse = self::getAccessToken($input);
        /** @var Shop $shop */
        $shop = session('shop');
        if (isset($accessTokenResponse['access_token'], $accessTokenResponse['refresh_token'])) {
            $shopInfo = self::getShopInfo($accessTokenResponse['access_token'], $input['shop_id']);
            $data = [
                'access_token' => $accessTokenResponse['access_token'],
                'refresh_token' => $accessTokenResponse['refresh_token'],
                'expire_in' => $accessTokenResponse['expire_in'],
                'shop_id' => $input['shop_id'],
                'shop_name' => isset($shopInfo['shop_name']) ? $shopInfo['shop_name'] : '',
                'country' => $shopInfo['region'],
                'shop_description' => '',
                'request_id' => $shopInfo['request_id']
            ];
            return self::handleAuthenticationResponse($data, $shop);
        } else {
            set_log_extra('response', $accessTokenResponse->getBody()->getContents());
            throw new \Exception('Unable to request token for Shopee');
        }
    }

    /**
     * Handles the authentication response for logging in and refreshing token
     *
     * @param $data
     * @param $shop
     *
     * @return mixed
     * @throws \Exception
     */
    private static function handleAuthenticationResponse($data, $shop)
    {
        try {
            $shopId = (int) $data['shop_id'];
            $name = isset($data['shop_name']) ? $data['shop_name'] : '';
            $country = $data['country'];
            $description = $data['shop_description'];
            $access_token = $data['access_token'];
            $refresh_token = $data['refresh_token'];
            $expire_in = $data['expire_in'];

            if ($country == 'SG') {
                $currency = 'SGD';
                $region = Region::SINGAPORE;
            } elseif ($country == 'MY') {
                $currency = 'MYR';
                $region = Region::MALAYSIA;
            } elseif ($country == 'ID') {
                $currency = 'IDR';
                $region = Region::INDONESIA;
            } else {
                set_log_extra('response', $data);
                throw new \Exception('Region ' . $country . ' for Shopee not supported yet');
            }

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::SHOPEE,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'shop_id'   => $shopId,
                'access_token'   => $access_token,
                'refresh_token'   => $refresh_token,
                'expire_in'   => $expire_in,
                'country'        => $country,
                'request_id'      => $data['request_id'],
                'shop_description' => $description,
                'access_token_updated_time' => time()
            ];

            // This is used in case there's other information stored in credentials
            // E.g. for a hybrid authentication
            if (!empty($account)) {
                $credentials = array_merge($account->credentials, $credentials);
            }
            $account = Account::updateOrCreate($queryData, [
                'credentials' => $credentials,
                'status'      => AccountStatus::ACTIVE()
            ]);

            $account = $account->fresh();

            return $account;
        } catch (\Exception $e) {
            set_log_extra('response', $data);
            throw $e;
        }
    }

    public static function handleRefreshTokenResponse($account, $data)
    {
        try {
            $queryData = [
                'integration_id' => Integration::SHOPEE,
                'id'      => $account->id,
            ];
            if ($data == null) {
                Log::info('handleRefreshTokenResponse with account id ' . $account->id.'| Empty data from refresh token|Disabling the shopee account');
                // token is invalid. We will disabled account then user need to re-active again.
                $account = Account::updateOrCreate($queryData, [
                    'status'      => AccountStatus::REQUIRE_AUTH()
                ]);
            } else {
                $shopId = (int) $data['shop_id'];
                $access_token = $data['access_token'];
                $refresh_token = $data['refresh_token'];
                $expire_in = $data['expire_in'];

                $credentials = [
                    'shop_id'   => $shopId,
                    'access_token'   => $access_token,
                    'refresh_token'   => $refresh_token,
                    'expire_in'   => $expire_in,
                    'request_id'      => $data['request_id'],
                    'access_token_updated_time' => time()
                ];
  
                // This is used in case there's other information stored in credentials
                // E.g. for a hybrid authentication
                if (!empty($account)) {
                    $credentials = array_merge($account->credentials, $credentials);
                }
                $account = Account::updateOrCreate($queryData, [
                    'credentials' => $credentials,
                    'status'      => AccountStatus::ACTIVE()
                ]);
            }

            $account = $account->fresh();
            Log::info('handleRefreshTokenResponse with account|' . $account->id.'|executed|updated account info|'.json_encode($account));
            return $account;
        } catch (\Exception $e) {
            set_log_extra('response', $data);
            throw $e;
        }
    }

    public function requestv2($method, $uri = '', array $options = [], bool $is_multipart = false)
    {
        $params_common = $this->getCommonParams($uri);
        $APIUrl = self::SERVICE_URL . $uri;
        $client = new \GuzzleHttp\Client(['verify' => false]);

        if (strtolower($method) == 'get') {
            $getUrl = $APIUrl . '?' . http_build_query(array_merge($params_common, $options));
            $getUrl = filter_var($getUrl, FILTER_SANITIZE_URL);
            $response = $client->get($getUrl);
        } else if (strtolower($method) == 'post') {
            $postUrl = $APIUrl . '?' . http_build_query($params_common);
            $postUrl = filter_var($postUrl, FILTER_SANITIZE_URL);
            if ($is_multipart == true) {
                $response = $client->request('POST', $postUrl, $options);
            } else {
                $response = $client->requestAsync('POST', $postUrl, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $options,
                ])->wait();
            }
        } else {
            // for other method
        }


        if ($response->getStatusCode() !== 200) {
            set_log_extra('response', $response->getBody()->getContents());
            throw new \Exception('Unable sent request to Shopee V2');
        }

        $response = json_decode($response->getBody()->getContents(), true);
        return $response;
    }

    public function getCommonParams($uri)
    {
        $accessToken = $this->account->credentials['access_token'];
        $shopId = (int) $this->account->credentials['shop_id'];
        $partnerId = config('combinesell.shopee.partner_id');
        $partnerKey = config('combinesell.shopee.partner_key');
        $timestamp = time();
        /**
         * Join partner_id, uri, timestamp, access_token and shop_id to make a single string
         */
        $baseString = $partnerId . '/api/v2' . $uri . $timestamp . $accessToken . $shopId;
        /**
         * Signature generated by (partner_id, api path, timestamp, access_token, shop_id and partner_key
         * via HMAC-SHA256 hashing algorithm
         */
        $signature = hash_hmac('sha256', $baseString, $partnerKey, false);
        return [
            'access_token' => $accessToken,
            'partner_id' => (int) $partnerId,
            'timestamp' => $timestamp,
            'shop_id' => (int) $shopId,
            'sign' => $signature,
        ];
    }

    /**
     * Request call api
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return mixed|ResponseInterface
     * @throws \Exception
     */
    public function request($method, $uri = '', array $options = [])
    {
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    /**
     * Call api url with account credentials
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri = '', array $options = [], $count = 0)
    {
        $this->logDebug('URI: ' . $uri . '. Method: ' . $method . '. Options: ' . print_r($options, true));
        $options = $this->parseCallCredentials($uri, $options);

        $response = parent::requestAsync($method, $uri, $options);

        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options, $count) {
            $rawBody = $response->getBody()->getContents();
            $json = json_decode($rawBody, true);
            if ($response->getStatusCode() !== 200) {
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method . '. Response: ' . print_r($json, true));
            } else {
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method);
            }
            if (!empty($json['error']) && $json['error'] === 'error_auth') {
                if (!empty($json['msg']) && strpos(strtolower($json['msg']), 'service error') !== false) {
                    if ($count < 5) {
                        sleep(3);
                        return $this->requestAsync($method, $uri, $options, ++$count);
                    } else {
                        throw new \Exception('Shopee\'s service error. Exceeded max attempts.');
                    }
                } else {
                    $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                    set_log_extra('response', $rawBody);
                    set_log_extra('json', $json);
                    set_log_extra('error', $json['error']);
                    set_log_extra('options', $options);
                    set_log_extra('uri', $uri);
                    set_log_extra('partner_id', config('combinesell.shopee.partner_id'));
                    throw new \Exception('Shopee authentication error.');
                }
            } else {
                return $json;
            }
        });
    }

    /**
     * Parse the account credentials
     *
     * @param $url
     * @param $data
     * @return array
     */
    public function parseCallCredentials($url, $data): array
    {
        $httpBody = json_encode(array_merge(array(
            'shopid' => (int) $this->account->credentials['access_token'],
            'partner_id' => (int) config('combinesell.shopee.partner_id'),
            'timestamp' => time(),
        ), $data));

        $signature = hash_hmac('sha256', $this->url . $url . '|' . $httpBody, config('combinesell.shopee.partner_key'));

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $signature,
            ],
            'body' => $httpBody,
            'connect_timeout' => 30,
            'timeout' => 180
        ];
        return $options;
    }

    /**
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall
     *
     *
     */
    public function isCredentialsValid($actualCall = false)
    {
    }

    /**
     * Cron job for integration set in $job init.php
     * Pull all the available marketplace fee daily based on the orders created that day
     *
     */
    public function retrieveSettlement()
    {
        $orders = $this->account->orders()->whereDate('created_at', now()->subDay()->format('Y-m-d'))->get();

        foreach ($orders as $key => $order) {
            $response = $this->request('POST', 'orders/my_income', ['ordersn' => $order->external_id]);

            $input = [
                'commission_fee' => 0,
                'settlement_amount' => 0,
                'integration_discount'  => 0,
                'seller_discount' => 0,
                'actual_shipping_fee' => 0,
                'transaction_fee' => 0
            ];

            if (!isset($response['error']) && isset($response['order'])) {
                $escrow = $response['order'];
                $input['commission_fee'] = $escrow['income_details']['commission_fee']; // The commission fee charged by Shopee platform if applicable.
                $input['settlement_amount'] = $escrow['income_details']['escrow_amount']; // The total amount that the seller is expected to receive for the order and will change before order completed.
                $input['integration_discount'] = $escrow['income_details']['seller_rebate']; // Final sum of each item Shopee discount of a specific order. This amount will rebate to seller.
                $input['seller_discount'] = $escrow['income_details']['voucher_seller']; // Final value of voucher provided by Seller for the order.

                $input['seller_shipping_fee'] = $escrow['income_details']['final_shipping_fee']; // Final adjusted amount that seller has to bear as part of escrow. This amount could be negative or positive.
                $input['integration_shipping_fee'] = $escrow['income_details']['shipping_fee_rebate']; // The platform shipping subsidy to the seller.
                $input['actual_shipping_fee'] = $escrow['income_details']['actual_shipping_cost']; // The final shipping cost of order and it is negative. For Non-integrated logistics channel is 0.
                $input['transaction_fee'] = $escrow['income_details']['credit_card_transaction_fee']; // Include buyer transaction fee and seller transaction fee.

                $order->update($input);
            }
        }
    }

    public function responseDownload($uri, $parameters, $order_external_id)
    {
        $getShopUrl = self::SERVICE_URL . $uri . '?';
        $params = $this->getCommonParams($uri);
        $fullUrl = $getShopUrl . http_build_query($params);
        $fullUrl = filter_var($fullUrl, FILTER_SANITIZE_URL);
        $httpBody = json_encode($parameters);
        $parameters = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $httpBody,
            'sink' => $order_external_id . '.pdf' // path
        ];
        $client = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client->post($fullUrl, $parameters); // download file pdf
        if ($response->getStatusCode() !== 200) {
            set_log_extra('response', $response->getBody()->getContents());
            throw new \Exception('Unable sent request to Shopee V2');
        }
        $response = json_decode($response->getBody()->getContents(), true);
        return  $response;
    }
}
