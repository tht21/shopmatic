<?php

namespace App\Integrations\Lazada;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\Log;

class Client extends AbstractClient
{

    const URLS = [
        Region::SINGAPORE => 'https://api.lazada.sg/rest',
        Region::MALAYSIA => 'https://api.lazada.com.my/rest',
    ];

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
    public static function getAuthorizationLink($region = null)
    {
        // Lazada uses the same authorization URL regardless of region
        $auth_url = 'https://auth.lazada.com/oauth/authorize?';
        $state = md5(time() . session('shop')->getRouteKey());
        session()->put('LAZADA_STATE', $state);
        $params = [
            'response_type' => 'code',
            'client_id' => config('combinesell.lazada.app_key'),
            'force_auth' => false,
            'redirect_uri' => config('combinesell.lazada.redirect'),
            'state' => $state
        ];
        return filter_var($auth_url.http_build_query($params), FILTER_SANITIZE_URL);
    }

    /**
     * Generates the token and updates / creates the integration
     *
     * @param $input
     *
     * @return boolean
     * @throws \Exception
     */
    public static function handleRedirect($input)
    {

        $code = $input['code'];
        $state = $input['state'];
        if ($state !== session('LAZADA_STATE')) {
            return false;
        }
        session()->remove('LAZADA_STATE');
        $client = new \GuzzleHttp\Client(['verify' => false]);

        $parameters = [
            'app_key' => config('combinesell.lazada.app_key'),
            'timestamp' => time() . '000',
            'code' => $code,
            'sign_method' => 'sha256',
        ];
        ksort($parameters);
        $encoded = [];
        foreach ($parameters as $name => $value) {
            $encoded[] = $name . $value;
        }
        $concatenated = implode('', $encoded);
        //signature
        $parameters['sign'] = strtoupper(hash_hmac('sha256', '/auth/token/create' . $concatenated, config('combinesell.lazada.secret')));
        $res = $client->post('https://auth.lazada.com/rest/auth/token/create',
            [
                'form_params' => $parameters
            ]
        );
        /** @var Shop $shop */
        $shop = session('shop');
        if ($res->getStatusCode() === 200) {
            $data = json_decode($res->getBody()->getContents(), true);

            return self::handleAuthenticationResponse($data, $shop);
        } else {
            set_log_extra('response', $res->getBody()->getContents());
            throw new \Exception('Unable to request token for Lazada');
        }
    }

    /**
     * Refreshes the token if it's close to expiry
     *
     * @throws \Exception
     */
    private function refreshTokenIfExpiring()
    {
        if (!$this->isCredentialsValid()) {
            $this->disableAccount(AccountStatus::REQUIRE_AUTH());
            set_log_extra('credentials', $this->account->toArray());
            throw new \Exception('Lazada credentials expired and is unable to be refreshed.');
        }
        $expiry = $this->account->credentials['expiry'];
        $expirySeconds = $expiry - time();

        //If there's more than 1 day remaining, don't refresh
        if ($expirySeconds > 86400) {
            return;
        }
        $this->refreshToken();
    }

    /**
     * Refreshes the token
     *
     * @throws \Exception
     */
    private function refreshToken() {
        $client = new \GuzzleHttp\Client();
        $parameters = $this->getParameters('/auth/token/refresh', ['refresh_token' => $this->account->credentials['refresh_token']]);
        $res = $client->post('https://auth.lazada.com/rest/auth/token/refresh',
            [
                'form_params' => $parameters
            ]
        );
        $data = json_decode($res->getBody()->getContents(), true);

        if ($res->getStatusCode() === 200) {
            if ($data['code'] === 'IllegalAccessToken' || $data['code'] === 'IllegalRefreshToken') {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                return false;
            }
            $shop = $this->account->shop;
            $this->account = self::handleAuthenticationResponse($data, $shop);

            return true;
        } else {
            set_log_extra('response', $data);
            throw new \Exception('Unable to refresh token for Lazada');
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
            // This is their success
            if ($data['code'] != "0") {
                if ($data['code'] === 'IllegalAccessToken') {
                    return null;
                }
                set_log_extra('response', $data);
                throw new \Exception('Unable to handle authentication response for Lazada');
            }
            $token = $data['access_token'];
            $expiry = time() + $data['expires_in'];
            $refreshToken = $data['refresh_token'];
            $refreshExpiry = time() + $data['refresh_expires_in'];
            $country = $data['country'];

            if ($country == 'sg') {
                $currency = 'SGD';
                $region = Region::SINGAPORE;
            } elseif ($country == 'my') {
                $currency = 'MYR';
                $region = Region::MALAYSIA;
            } elseif ($country == 'id') {
                $currency = 'IDR';
                $region = Region::INDONESIA;
            } else {
                set_log_extra('response', $data);
                throw new \Exception('Region ' . $country . ' for Lazada not supported yet');
            }

            $queryData = [
                'name'           => $data['account'],
                'integration_id' => Integration::LAZADA,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'access_token'   => $token,
                'refresh_token'  => $refreshToken,
                'expiry'         => $expiry,
                'refresh_expiry' => $refreshExpiry,
                'country'        => $country,
                'user_info'      => $data['country_user_info'] ?? $data['country_user_info_list'] ?? null,
                'platform'       => $data['account_platform'] ?? ''
            ];

            // This is used in case there's other information stored in credentials
            // E.g. for a hybrid authentication
            if (!empty($account)) {
                $credentials = array_merge($account->credentials, $credentials);
            }
            $account = Account::updateOrCreate($queryData, [
                    'credentials' => $credentials,
                    'status'      => AccountStatus::ACTIVE()
                ]
            );
            $account = $account->fresh();

            return $account;
        } catch (\Exception $e) {
            set_log_extra('response', $data);
            throw $e;
        }
    }

    /**
     * Retrieve the parameters with signature used for the API
     *
     * @param $action
     * @param array $data
     *
     * @return array
     */
    private function getParameters($action, $data = [])
    {

        $parameters = array_merge([
            'app_key' => config('combinesell.lazada.app_key'),
            'access_token' => $this->account->credentials['access_token'],
            'sign_method' => 'sha256',
            'timestamp' => time() . '000',
        ], $data);

        // exclude it for refresh token, else it will fail
        if ($action === '/auth/token/refresh') {
            unset($parameters['access_token']);
        }

        ksort($parameters);
        $encoded = [];
        foreach ($parameters as $name => $value) {
            $encoded[] = $name . $value;
        }
        $concatenated = implode('', $encoded);
        //signature
        $parameters['sign'] = strtoupper(hash_hmac('sha256', $action . $concatenated, config('combinesell.lazada.secret')));

        return $parameters;
    }

    /**
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall
     *
     * @return bool
     *
     */
    public function isCredentialsValid($actualCall = false)
    {
        if ($actualCall) {
            $endpoint = '/seller/get';
            $parameters = $this->getParameters($endpoint, []);

            $endpoint .= '?';
            foreach ($parameters as $key => $value) {
                $endpoint .= "$key=" . rawurlencode($value) . "&";
            }
            $endpoint = substr($endpoint, 0, -1);
            $response = parent::requestAsync('GET', $this->url . $endpoint, [])->wait();
            $json = json_decode($response->getBody(), true);
            if ($json['code'] === 'IllegalAccessToken') {
                return false;
            } elseif ($json['code'] == 0) {
                return true;
            }

            return false;
        } else {
            $expiry = $this->account->credentials['expiry'];
            $expirySeconds = $expiry - time();

            if ($expirySeconds <= 0) {
                $refreshExpiry = $this->account->credentials['refresh_expiry'] - time();
                if ($refreshExpiry <= 0) {
                    return false;
                }
            }
            return true;
        }

    }

    /**
     * @param $method
     * @param $endpoint
     * @param $body
     *
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestXml($method, $endpoint, $body)
    {
        return $this->request($method, $endpoint, ['payload' => $body]);

    }

    /**
     * Calls the function to upload files
     *
     * @param $method
     * @param $endpoint
     * @param $body
     *
     * @return mixed|ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function callWithFile($method, $endpoint, $body)
    {

        $parameters = $this->getParameters($endpoint, []);

        $endpoint .= '?';
        foreach ($parameters as $key => $value)
        {
            $endpoint .= "$key=" . rawurlencode($value) . "&";
        }
        $endpoint = substr($endpoint, 0, -1);

        $params = [];
        foreach ($parameters as $key => $value) {
            $params[] = [
                'name' => $key,
                'contents' => $value
            ];
        }
        $params[] = [
            'name' => 'image',
            'contents' => $body,
            'filename' => 'anyname.jpg'
        ];

        $options = [
            'multipart' => $params
        ];

        /*
         *
         * We're creating a new client here as opposed to using the existing one because this is multipart VS normal request.
         * This is so we can put the code to sign and etc inside requestAsync / request to standardize all calls
         *
         * TODO: Check if we can refactor all signing and etc into one function (requestAsync)
         */

        $uri = $this->url.$endpoint;

        $client = new \GuzzleHttp\Client();
        $res =  $client->request($method, $uri, $options);

        if ($res->getStatusCode() === 200) {
            $data = json_decode($res->getBody()->getContents(), true);

            return $data;
        } else {
            set_log_extra('response', $res->getBody()->getContents());
            throw new \Exception('Unable to upload image for Lazada');
        }
    }

    /**
     *
     * @param $method
     * @param string $uri
     * @param array $options
     *
     * @param int $calls
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function requestAsync($method, $uri = '', array $options = [], $calls = 1)
    {
        $this->logDebug('URI: ' . $uri . '. Method: ' . $method . '. Options: ' . print_r($options, true));
        // This means it already slept for over 30 seconds and we're still hitting the limit. Abort it
        if ($calls > 15) {
            set_log_extra('method', $method);
            set_log_extra('uri', $uri);
            set_log_extra('account', $this->account->toArray());
            throw new \Exception('Lazada call frequency limit reached. Failed after 15 tries');
        }
        $this->refreshTokenIfExpiring();

        // Technically we should allow for token updating
        // The below will throw an exception if the token is expired, this shouldn't happen on production
        // as we're refreshing the tokens periodically. But this may fail in local development.

        if (!$this->account->status->equals(AccountStatus::ACTIVE())) {
            set_log_extra('uri', $uri);
            set_log_extra('options', $options);
            set_log_extra('account', $this->account->toArray());
            $this->logError('Lazada account trying to make a request even though it is not active.');
            throw new \Exception('Lazada account trying to make a request even though it is not active.');
        }

        $parameters = $this->getParameters($uri, $options);

        $newUri = $uri . '?';

        if (strtolower($method) == 'get') {
            foreach ($parameters as $key => $value)
            {
                $newUri .= "$key=" . rawurlencode($value) . "&";
            }
            $newUri = substr($newUri, 0, -1);
        } else {
            $newOptions = ['form_params' => $parameters];
        }

        $response = parent::requestAsync($method, $this->url . $newUri, $newOptions ?? $options);

        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options, $calls) {
            $json = json_decode($response->getBody(), true);
            if ($response->getStatusCode() !== 200) {            
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method . '. Response: ' . print_r($json, true));
            } else {
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method);
            }
            if ($response->getStatusCode() !== 200) {
                if ($response->getStatusCode() === 502) {
                    $this->refreshToken();
                    Log::info('Invalid token has been refreshed for Account ' . $this->account->id . ' and Shop ' . $this->account->shop_id);
                }

                Log::info('Lazada invalid status exception|Method|' . $method . '|URI|' . $uri . '|Status Code|' . $response->getStatusCode() . '|Response|' . $response->getBody());
                throw new \Exception('Lazada invalid status code: ' . $response->getBody());
            } elseif ($json['code'] === 'IllegalAccessToken') {
                $result = $this->refreshToken();

                // This means it already slept for over 30 seconds and we're still hitting the limit. Abort it
                if ($calls >= 15) {
                    set_log_extra('response', $json);
                    throw new \Exception('Lazada invalid access token.');
                }

                // TODO: Proper handling of this
                if ($result) {
                    sleep(rand(1, 4));
                    return $this->requestAsync($method, $uri, $options, $calls + 1);
                } else {
                    throw new \Exception('Lazada invalid access token. Refresh token failed.');
                }

            } elseif ($json['code'] === 'ApiCallLimit') {
                // This means it already slept for over 30 seconds and we're still hitting the limit. Abort it
                if ($calls >= 15) {
                    set_log_extra('response', $json);
                    set_log_extra('method', $method);
                    set_log_extra('uri', $uri);
                    set_log_extra('options', $options);
                    set_log_extra('account', $this->account->toArray());
                    throw new \Exception('Lazada call frequency limit reached. Failed after 15 tries');
                }

                // TODO: Proper handling of this
                sleep(rand(1, 4));
                return $this->requestAsync($method, $uri, $options, $calls + 1);
            }
            return $json;
        });
    }


    /**
     * Retrieves all the data from a paginated response
     *
     * @param $method
     * @param $endpoint
     * @param array $data
     *
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function requestPaging($method, $endpoint, $data = [])
    {
        $results = [];
        $filters = ['offset' => 0, 'limit' => 100];
        do {
            $results[] = $this->request($method, $endpoint, array_merge($data, $filters));
            $filters['offset'] += $filters['limit'];
            if (!isset(last($results)['SuccessResponse'])) {
                set_log_extra('response', last($results));
                set_log_extra('endpoint', $endpoint);
                set_log_extra('request_data', array_merge($data, $filters));
                throw new \Exception('ErrorResponse for requestPaging in Lazada');
            }
        } while(last($results)['TotalCount'] == $filters['Limit']);

        return $results;
    }

    /**
     * Cron job for integration set in $job init.php
     * Pull all the available marketplace fee of the day
     *
     */
    public function retrieveSettlement()
    {
        $limit = 500;
        $filters = [
            'start_time' => date(DATE_ISO8601, strtotime(now()->subDay()->startOfDay())),
            'end_time' => date(DATE_ISO8601, strtotime(now()->subDay()->endOfDay())),
            'limit' => $limit,
            'offset' => 0
        ];

        do {
            Log::info('[retrieveSettlement] Request: ' . json_encode($filters));
            $response = $this->request('get', '/finance/transaction/details/get', $filters);
            Log::info('[retrieveSettlement] Response: ' . json_encode($response));

            if (empty($response['code'])) {
                $data = collect($response['data'])->groupBy('order_no');

                foreach ($data as $externalId => $value) {
                    $transactionFee = $value->where('fee_name', 'Payment Fee')->first();
                    $commissionFee = $value->where('fee_name', 'Commission')->first();
                    $sellerShippingFee = $value->where('fee_name', 'Shipping Fee Paid by Seller')->first();
                    $actualShippingFee = $value->where('fee_name', 'Shipping Fee (Paid By Customer)')->first();

                    $fee = $value->sum('amount');

                    $input = [
                        'settlement_amount' => $fee,
                        'commission_fee' => abs($commissionFee['amount'] ?? 0),
                        'seller_shipping_fee' => abs($sellerShippingFee['amount'] ?? 0),
                        'actual_shipping_fee' => abs($actualShippingFee['amount'] ?? 0),
                        'transaction_fee' => abs($transactionFee['amount'] ?? 0)
                    ];

                    $this->account->orders()->where('external_id', $externalId)->update($input);
                }

            } else {
                set_log_extra('response', $response);
                throw new \Exception('Unable to retrieve finance transaction for Lazada');
            }

        } while (count($data) >= $limit);
    }
}
