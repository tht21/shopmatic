<?php

namespace App\Integrations\Woocommerce;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class Client extends AbstractClient
{
    const URLS = [];

    protected $version = '1.0';

    protected $format = 'JSON';

    const OPTIONS = [
        'wp_api'    => true,
        'timeout'   => 60,
        'version'   => 'wc/v3',
        'verify_ssl' => false,
        'follow_redirects' => false,
        'query_string_auth' => true
    ];

    /*
     * This is the lists of methods that needs to be called after the account creation process.
     * This includes things such as outlets, account categories or anything else
     *
     * @var array
     */
    public $postAccountCreation = [
        'importAccountCategories' => 'Import categories from Woocommerce.',
    ];

    /**
     * Client constructor.
     *
     * @param Account $account
     * @param array $config
     */
    public function __construct(Account $account, $config = [])
    {
        //$this->url = self::URLS[$account->region_id];

        parent::__construct($account, $config);
    }

    /**
     * Handle the manual auth and returns the URL for the authorization link
     *
     * @param $input
     * @return Account|mixed|null|string
     * @throws \Exception
     */
    public static function handleManualAuth($input)
    {
        $woocommerce = new \Automattic\WooCommerce\Client(
            $input['store_url'],
            $input['consumer_key'],
            $input['consumer_secret'],
            self::OPTIONS
        );

        try {
            # Check for credential
            $woocommerce->get('data');

            /** @var Shop $shop */
            $shop = session('shop');
            $domain = $input['store_url'];

            return self::handleAuthenticationResponse($input, $shop, $domain);
        } catch (HttpClientException $e) {
            set_log_extra('credentials', $e->getMessage());
            throw new \Exception('Woocommerce credentials is invalid.');
        }
    }

    /**
     * Handles the authentication response for logging in and refreshing token
     *
     * @param $data
     * @param $shop
     * @param $domain
     *
     * @return mixed
     * @throws \Exception
     */
    private static function handleAuthenticationResponse($data, $shop, $domain)
    {
        try {
            $token = $data['consumer_secret'];
            $name = $domain;
            $region = Region::GLOBAL;
            $currency = 'SGD';

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::WOOCOMMERCE,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'access_key' => $data['consumer_key'],
                'access_token' => $token,
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
     * Checks to ensure that the credentials are valid.
     *
     * @param bool $actualCall
     * @return bool
     */
    public function isCredentialsValid($actualCall = false)
    {
        try {
            $response = $this->request('get', 'data');
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Request call api
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function request($method, $uri = '', array $options = [])
    {
        $client = $this->parseCallCredentials($options);

        $this->logDebug('URI: ' . $uri . '. Method: ' . $method . '. Options: ' . print_r($options, true));
        
        try {
            if (strtolower($method) == 'get') {
                $response = $client->get($uri, $options);
            } else if (strtolower($method) == 'post') {
                $response = $client->post($uri, $options);
            } else if (strtolower($method) == 'put') {
                $response = $client->put($uri, $options);
            } else if (strtolower($method) == 'delete') {
                $response = $client->delete($uri, $options);
            }

            $this->logDebug('URI: ' . $uri . '. Status Code: 200. Method: ' . $method);

            return $response;
        } catch (\Exception $e) {
            $this->logDebug('URI: ' . $uri . '. Status Code: 500. Method: ' . $method . '. Response: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Parse the account credentials
     *
     * @param $data
     * @return mixed
     */
    public function parseCallCredentials($data)
    {
        $options = array_merge($data, self::OPTIONS);

        $woocommerce = new \Automattic\WooCommerce\Client(
            $this->account->name,
            $this->account->credentials['access_key'],
            $this->account->credentials['access_token'],
            $options
        );
        return $woocommerce;
    }

    /**
     * Defined feature in $features init.php
     * Import account categories from PrestaShop during setup
     *
     * @param Account $account
     * @param Request $request
     * @return \GuzzleHttp\Promise\PromiseInterface
     */

    public function importAccountCategories(Account $account, $request)
    {
        $account->getProductAdapter()->importCategories(null);
    }
}
