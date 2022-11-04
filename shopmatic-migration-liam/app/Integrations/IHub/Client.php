<?php

namespace App\Integrations\IHub;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use Psr\Http\Message\ResponseInterface;

class Client extends AbstractClient
{

    const URLS = [
        Region::SINGAPORE => 'https://vls.ihubsolutions.com/'
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
        $this->url = self::URLS[$account->region_id];

        parent::__construct($account, $config);

    }

    /**
     * This is only required if the integration supports fields
     *
     * @param $input
     *
     * @return Account|null|mixed The account if it's successfully created
     * @throws \Exception
     */
    public static function handleManualAuth($input) {

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'cache-control' => 'no-cache'
            ],
            'body' => json_encode([
                'username' => $input['username'],
                'password' => $input['password']
            ])
        ];

        $client = new \GuzzleHttp\Client();

        try {

            $response = $client->request('POST', self::URLS[$input['region']].'api/Jwt' , $options);
            $shop = session('shop');
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                return self::handleAuthenticationResponse($input, $data, $shop);
            }else {
                throw new \Exception($response->getBody()->getContents());
            }
        } catch (\Exception $e) {
            set_log_extra('response', 'Unable to request token for IHub. Body: ' . $e->getMessage());
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
    private static function handleAuthenticationResponse($input, $data, $shop)
    {

        try {
            // This is their success
            $token = $data['access_token'];
            $expiry = time() + $data['expires_in'];
            $name = $input['username'];
            $region = Region::SINGAPORE;
            $currency = 'SGD';

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::IHUB,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];


            $account = Account::where($queryData)->first();

            $credentials = [
                'access_token'   => $token,
                'expiry'         => $expiry,
                'username' => $input['username'],
                'password' => $input['password'],
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
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall
     *
     * @return bool
     *
     */
    public function isCredentialsValid($actualCall = false)
    {
        $expiry = $this->account->credentials['expiry'];
        $expirySeconds = $expiry - time();

        if ($expirySeconds <= 0) {
            return false;
        }
        return true;
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

        if ($this->account->status != AccountStatus::ACTIVE()) {
            set_log_extra('uri', $uri);
            set_log_extra('options', $options);
            set_log_extra('account', $this->account->toArray());
            throw new \Exception('IHub account trying to make a request even though it is not active.');
        }

        $options = array_merge($options, [
            'headers' => [
                'Authorization' => 'Bearer '. $this->account->credentials['access_token'],
                'Content-Type'  => 'application/json',
            ]
        ]);

        $response = parent::requestAsync($method, $this->url . $uri, $options);

        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options) {

            $json = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200) {
                set_log_extra('response', $json);
                set_log_extra('response_status_code', $response->getStatusCode());
                throw new \Exception('IHub invalid access token.');
            }

            return $json;
        });
    }
}
