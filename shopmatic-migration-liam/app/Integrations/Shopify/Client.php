<?php

namespace App\Integrations\Shopify;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class Client extends AbstractClient
{

    protected $version = '1.0';

    protected $format = 'JSON';

    /**
     * Client constructor.
     *
     * @param Account $account
     * @param array $config
     */
    public function __construct(Account $account, $config = [])
    {
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
        $scopes = 'read_products,write_products,read_product_listings,read_orders,read_all_orders,write_orders,read_inventory,write_inventory,read_fulfillments,write_fulfillments,read_shipping,write_shipping,read_price_rules,write_price_rules,unauthenticated_read_product_listings,read_assigned_fulfillment_orders,read_merchant_managed_fulfillment_orders,read_third_party_fulfillment_orders';
        $key = config('combinesell.shopify.api_key');
        $redirect = config('combinesell.shopify.redirect');
        $shopUrl = (isset($input['shop_url'])) ? $input['shop_url'] : null;

        $pattern = ';(?:https?://)?(?:[a-zA-Z0-9.-]+?\.(?:com|net|org|gov|edu|mil)|\d+\.\d+\.\d+\.\d+);';
        if (!preg_match($pattern, $shopUrl)) {
            set_log_extra('shopify_url', $shopUrl);
            throw new \Exception('Shopify Shop URL format is invalid.');
        }

        if (empty($key) || empty($redirect)) {
            set_log_extra('credentials', 'SHOPIFY API KEY or REDIRECT URI not set.');
            throw new \Exception('Shopify API KEY or REDIRECT URI not set.');
        }
        if (empty($shopUrl) || is_null($shopUrl)) {
            set_log_extra('credentials', 'Shopify Shop URL not set.');
            throw new \Exception('Shopify Shop URL not set.');
        }

        $state = md5(time() . session('shop')->getRouteKey());
        session()->put('SHOPIFY_NONCE', $state);

        # Remove http or https
        $shopUrl = str_replace(["http://", "https://"],"", $shopUrl);

        $url = "https://$shopUrl/admin/oauth/authorize?client_id=$key&scope=$scopes&redirect_uri=$redirect&state=$state";
        return $url;
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
        $domain = $input['shop'];
        $code = $input['code'];
        $state = $input['state'];
        if ($state !== session('SHOPIFY_NONCE') || !Str::endsWith($domain, '.myshopify.com')) {
            return false;
        }

        $client = new \GuzzleHttp\Client(['verify' => false]);

        try {
            $response = $client->post("https://$domain/admin/oauth/access_token", [
                'form_params' =>
                    [
                        'code'          => $code,
                        'client_id'     => config('combinesell.shopify.api_key'),
                        'client_secret' => config('combinesell.shopify.secret')
                    ]
            ]);

            /** @var Shop $shop */
            $shop = session('shop');
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                //Get Shop Configurations
                $options = ['headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $data['access_token'],
                ]];
                $response = $client->get("https://$domain/admin/api/2020-07/shop.json", $options);

                if ($response->getStatusCode() === 200) {
                    $shopConfiguration = json_decode($response->getBody()->getContents(), true);

                    //Shop's default currency.
                    if (isset($shopConfiguration['shop'],$shopConfiguration['shop']['currency']) && !empty($shopConfiguration['shop']['currency'])) {
                            $data['shop_default_currency'] = $shopConfiguration['shop']['currency'];
                    }
                }
                return self::handleAuthenticationResponse($data, $shop, $domain);
            } else {
                throw new \Exception($response->getBody()->getContents());
            }
        } catch (ClientException $e) {
            set_log_extra('response', 'Unable to request token for Shopify. Body: ' . $e->getMessage());
            throw $e;
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
            $token = $data['access_token'];
            $scope = $data['scope'];
            $name = $domain;
            $region = Region::GLOBAL;
            $currency = $data['shop_default_currency'] ?? 'USD';

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::SHOPIFY,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'access_token' => $token,
            ];

            // This is used in case there's other information stored in credentials
            // E.g. for a hybrid authentication
            if (!empty($account)) {
                $credentials = array_merge($account->credentials, $credentials);
            }
            $account = Account::updateOrCreate($queryData, [
                    'credentials' => $credentials,
                    'additional_data' => ['scope' => $scope],
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
     * Request call api
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @param int $maxLogin
     * @return mixed|ResponseInterface
     * @throws \Exception
     */
    public function request($method, $uri = '', array $options = [], $maxLogin=1)
    {
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    /**
     *
     * @param $method
     * @param string $uri
     * @param array $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $this->logDebug('URI: ' . $uri . '. Method: ' . $method . '. Options: ' . print_r($options, true));
        $options = array_merge($options, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $this->account->credentials['access_token'],
            ]
        ]);
        $fullUrl = 'https://' . $this->account->name . '/' .$uri;

        $response = parent::requestAsync($method, $fullUrl, $options);

        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options) {
            $json = json_decode($response->getBody(), true);
            if ($response->getStatusCode() !== 200) {
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method . '. Response: ' . print_r($json, true));
            } else {
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method);
            }
            if ($response->getStatusCode() === 401) {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                set_log_extra('response', $json);
                throw new \Exception('Shopify invalid access token.');
            } else {
                // check for rate limit
                $responseHeaders = $response->getHeaders();
                if (isset($responseHeaders["X-Shopify-Shop-Api-Call-Limit"][0])) {
                    $rateLimit = explode('/', $responseHeaders["X-Shopify-Shop-Api-Call-Limit"][0]);
                    $usedLimitPercentage = (100 * $rateLimit[0]) / $rateLimit[1];
                    // if exceed rate limit then sleep 5
                    if($usedLimitPercentage > 95) {
                        sleep(5);
                    }
                }

                /*
                 * It's expected behaviour, because response body is a stream
                 * To be able to read the body again, you need to call ->getBody()->rewind() to rewind the stream to the beginning.
                 **/
                $response->getBody()->rewind();

                return $response;
            }
        });
    }
}
