<?php

namespace App\Integrations\Redmart;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use Psr\Http\Message\ResponseInterface;

class Client extends AbstractClient
{
    const URLS = [
        Region::SINGAPORE => 'https://mp-api.redmart.com/',
    ];

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
        $config['verify'] = false;
        $this->url = self::URLS[$account->region_id].'subvendor/' . $account->credentials['store_id'] . '/';

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
        $client = new \GuzzleHttp\Client(['verify' => false]);

        try {
            $response = $client->post("https://mp-api.redmart.com/login", [
                'json' => [
                    'email' => $input['email'],
                    'password' => $input['password']
                ],
                'headers' => [
                    'Origin' => 'https://partners.redmart.com',
                    'Referer' => 'https://partners.redmart.com/login/index',
                    'Accept' => 'application/json, text/plain, */*',
                    'Content-Type' => 'application/json;charset=UTF-8',
                    'Sec-Fetch-Mode' => 'cors',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36'
                ]
            ]);

            /** @var Shop $shop */
            $shop = session('shop');
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $data['access_token'] = $response->getHeader('x-access-token');
                $data['store_id'] = $input['store_id'];

                return self::handleAuthenticationResponse($data, $shop);
            } else {
                throw new \Exception($response->getBody()->getContents());
            }
        } catch (\Exception $e) {
            set_log_extra('response', 'Unable to request token for Redmart. Body: ' . $e->getMessage());
            throw $e;
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
            $token = $data['access_token'][0];
            $name = $data['member']['email'];
            $region = Region::SINGAPORE;
            $currency = 'SGD';

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::REDMART,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'access_token' => $token,
                'store_id' => $data['store_id'],
                'session' => $data['session'],
                'referral_token' => $data['member']['referral_token'] ?? '',
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
     * @throws \Exception
     */
    public function isCredentialsValid($actualCall = false)
    {
        return $this->request('GET', 'https://mp-api.redmart.com/subvendor/' . $this->account->credentials['store_id'])->getStatusCode() === 200;
    }

    /**
     * Returns the URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Request call api
     *
     * @param $method
     * @param string $uri
     * @param array $options
     *
     * @return mixed|ResponseInterface
     * @throws \Exception
     */
    public function request($method, $uri = '', array $options = [])
    {
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    /**
     * Parse the account credentials
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $options = array_merge($options, [
            'headers' => [
                'Accept' => 'application/json, text/plain, */*',
                'Authorization' => $this->account->credentials['access_token'],
                'X-Seller-id' => $this->account->credentials['store_id'],
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36',
                'Sec-Fetch-Mode' => 'cors',
                'Origin' => 'https://partners.redmart.com',
            ]
        ]);

        $response = parent::requestAsync($method, $uri, $options);

        // Execute call
        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options) {
            if ($response->getStatusCode() === 200) {
                return $response;
            } else if ($response->getStatusCode() === 401) {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                set_log_extra('response', json_decode($response->getBody(), true));
                throw new \Exception('Redmart invalid access token.');
            } else {
                $content = json_decode($response->getBody()->getContents(), true);
                //dd($response->getBody()->__toString());
                // if there is no pickup jobs found, the api will return status code 404 actually which is not an error
                if (isset($content['code']) && $content['code'] == 'jobsnotfound') {
                    return $response;
                }
                set_log_extra('response', json_decode($response->getBody(), true));
                set_log_extra('options', $options);
                throw new \Exception($content['msg'] ?? 'Redmart invalid action call');
            }
        });
    }
}
