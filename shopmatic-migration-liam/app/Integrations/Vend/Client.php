<?php

namespace App\Integrations\Vend;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class Client extends AbstractClient
{

    const VERSION_0_9 = 'https://%s.vendhq.com/api/';
    const VERSION_2_0 = 'https://%s.vendhq.com/api/2.0/';

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
     * Returns the URI with domain prefix of the user
     *
     * @param $base
     *
     * @param $action
     *
     * @return string
     */
    public function getUri($base, $action) {
        return sprintf($base, $this->account->credentials['domain']) . $action;
    }


    public static function getAuthorizationLink($region = null)
    {
        // TODO: Maybe add a state so we can verify / etc
        return 'https://secure.vendhq.com/connect?response_type=code&client_id='
            . config('combinesell.vend.client_id') . ' &redirect_uri=' . config('combinesell.vend.redirect');
    }

    /**
     * Generates the token and updates / creates the integration
     *
     * @param $input
     *
     * @return boolean
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function handleRedirect($input)
    {
        // these 2 inputs are required, if its missing means authentication failed from Vend side
        if (!isset($input['domain_prefix']) || !isset($input['code'])) {
            return null;
        }

        $domainPrefix = $input['domain_prefix'];
        $code = $input['code'];
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $res = $client->post("https://$domainPrefix.vendhq.com/api/1.0/token",
            ['form_params' =>
                [
                    'code' => $code,
                    'client_id' => config('combinesell.vend.client_id'),
                    'client_secret' => config('combinesell.vend.secret'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('combinesell.vend.redirect')
                ]
            ]
        );
        /** @var Shop $shop */
        $shop = session('shop');
        //Success
        if ($res->getStatusCode() === 200) {
            $data = json_decode($res->getBody()->getContents(), true);
            return self::handleAuthenticationResponse($data, $shop, $domainPrefix);
        } else {
            set_log_extra('response', json_decode($res->getBody()->getContents(), true));
            Log::error('Unable to request token for Vend.');
        }
        return null;
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function handleAuthenticationResponse($data, $shop, $domain)
    {
        try {
            $token = $data['token_type'] . ' ' . $data['access_token'];
            $expiry = $data['expires'];
            $refreshToken = $data['refresh_token'] ?? null;
            $name = $domain;

            // We're doing this to get the currency, as there's no other way to get the currency
            $client = new \GuzzleHttp\Client(['http_errors' => false]);
            $outletsResponse = $client->request('GET', sprintf(self::VERSION_2_0, $domain) .'outlets', [
                'headers' => [
                    'Authorization' => $token
                ],
                'query' => [
                    'page_size' => 1
                ]
            ]);
            if ($outletsResponse->getStatusCode() !== 200) {
                set_log_extra('outlets', $outletsResponse);
                throw new \Exception('Unable to get outlets for Vend');
            }
            $outlets = json_decode($outletsResponse->getBody()->getContents(), true);
            if (count($outlets['data']) === 0) {
                set_log_extra('outlets', $outlets);
                throw new \Exception('Unable to get outlets for Vend');
            }

            // Get first outlet, then get the currency
            $currency = $outlets['data'][0]['currency'];

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::VEND,
                'region_id'      => Region::GLOBAL,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $credentials = [
                'domain' => $domain,
                'access_token' => $token,
                'expires' => $expiry,
            ];

            // Vend mentions it will only provide the refresh_token if it's different from before
            if (!empty($refreshToken)) {
                $credentials['refresh_token'] = $refreshToken;
            }

            $account = Account::where($queryData)->first();

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

    /**
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall
     *
     * @return bool
     */
    public function isCredentialsValid($actualCall = false)
    {
        if ($actualCall) {
            $options = [
                'headers' => [
                    'Authorization' => $this->account->credentials['access_token'],
                ]
            ];
            $response = parent::requestAsync('GET', $this->getUri(Client::VERSION_2_0, 'outlets'), $options)->wait();
            if ($response->getStatusCode() === 401) {
                return false;
            } else if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                $data = json_decode($response->getBody(), true);
                return !empty($data['data']);
            }
            return false;
        } else {
            $expirySeconds = $this->account->credentials['expires'] - time();
            return $expirySeconds > 0;
        }
    }

    /**
     * @param $method
     * @param string $uri
     * @param array $options
     *
     * @return mixed|ResponseInterface
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($method, $uri = '',  $options = [])
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $this->refreshTokenIfExpiring();

        // Technically we should allow for token updating
        // The below will throw an exception if the token is expired, this shouldn't happen on production
        // as we're refreshing the tokens periodically. But this may fail in local development.

        if ($this->account->status != AccountStatus::ACTIVE()) {
            set_log_extra('uri', $uri);
            set_log_extra('options', $options);
            set_log_extra('account', $this->account->toArray());
            throw new \Exception('Vend account trying to make a request even though it is not active.');
        }
        $options = array_merge($options, [
            'headers' => [
                'Authorization' => $this->account->credentials['access_token'],
            ]
        ]);

        $response = parent::requestAsync($method, $uri, $options);

        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options) {
            $json = json_decode($response->getBody(), true);
            if ($response->getStatusCode() === 401) {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                set_log_extra('response', $json);
                throw new \Exception('Vend invalid access token.');
            }
            return $json;
        });
    }

    /**
     * Checks to see if we should refresh the token, if yes, do it here.
     *
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function refreshTokenIfExpiring()
    {
        $expirySeconds = $this->account->credentials['expires'] - time();
        $expirySeconds = 500;

        // Refresh if expiring in less than 5 minutes in case we need to make multiple calls (paging and etc)
        if ($expirySeconds < 300) {
            $client = new \GuzzleHttp\Client(['http_errors' => false]);
            $res = $client->post('https://' . $this->account->credentials['domain'] . '.vendhq.com/api/1.0/token',
                [
                    'form_params' =>
                        [
                            'refresh_token' => $this->account->credentials['refresh_token'],
                            'client_id'     => config('combinesell.vend.client_id'),
                            'client_secret' => config('combinesell.vend.secret'),
                            'grant_type'    => 'refresh_token',
                        ]
                ]
            );

            //Success
            if ($res->getStatusCode() === 200 || $res->getStatusCode() === 201) {
                $data = json_decode($res->getBody()->getContents(), true);
                return self::handleAuthenticationResponse($data, $this->account->shop, $this->account->credentials['domain']);
            } else {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                set_log_extra('account', $this->account->toArray());
                set_log_extra('credentials', $this->account->credentials);
                throw new \Exception('Vend integration token cannot be refreshed');
            }
        }
    }
}
