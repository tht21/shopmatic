<?php

namespace App\Integrations\Qoo10;

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
        Region::GLOBAL => 'https://api.qoo10.com/GMKT.INC.Front.QAPIService/Giosis.qapi',
        Region::SINGAPORE => 'https://api.qoo10.sg/GMKT.INC.Front.QAPIService/Giosis.qapi',
//        Region::MALAYSIA => 'https://api.qoo10.sg/GMKT.INC.Front.QAPIService/Giosis.qapi',
    ];

    /*
     * TODO: setup import return address
     * This is the lists of methods that needs to be called after the account creation process.
     * This includes things such as outlets, account categories or anything else
     *
     * @var array
     */
    public $postAccountCreation = [
//        'importReturnAddress' => 'Import return address from Qoo10.',
    ];
    
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
     * This is only required if the integration supports fields
     *
     * @param $input
     *
     * @return Account|null|mixed The account if it's successfully created
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public static function handleManualAuth($input)
    {
        /** @var Shop $shop */
        $shop = session('shop');
        $client = new \GuzzleHttp\Client(['verify' => false]);

        $response = $client->request('GET', self::URLS[$input['region']] , ['query' => [
            'method' => 'CertificationAPI.CreateCertificationKey',
            'key' => $input['api_key'],
            'user_id' => $input['username'],
            'pwd' => $input['password']
        ]]);

        $data = json_decode($response->getBody()->getContents(), true);
        if (array_key_exists('ResultCode', $data) && $data['ResultCode'] === 0) {
            $certificationKey = $data['ResultObject'];
            $region = $input['region'];

            if ($region == Region::SINGAPORE) {
                $currency = 'SGD';
            } elseif ($region == Region::GLOBAL) {
                $currency = 'USD';
            } elseif ($region == Region::MALAYSIA) {
                $currency = 'MYR';
            } else {
                set_log_extra('response', $input);
                throw new \Exception('Region ' . $region . ' for Qoo10 not supported yet');
            }

            $account = Account::updateOrCreate([
                'name'           => $input['username'],
                'integration_id' => Integration::QOO10,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ], [
                'credentials' => [
                    'key' => $certificationKey,
                    'password' => $input['password'],
                    'username' => $input['username'],
                ],
                'status' => AccountStatus::ACTIVE()
            ]);

            return $account->fresh();
        } else {
            set_log_extra('input', $input);
            set_log_extra('response', $data);
            throw new \Exception('Bad Credential for Qoo10.');
        }
    }

    /**
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall
     *
     * @return bool
     * @throws \Exception
     */
    public function isCredentialsValid($actualCall = false)
    {
        if ($actualCall) {
            $response = $this->request('POST', 'CommonInfoLookup.SearchBrand', [
                'keyword' => 'call api test'
            ]);
            return $response['ResultCode'] === 0;
        } else {
            return array_key_exists('key', $this->account->credentials);
        }

    }

    /**
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
        $this->logDebug('URI: ' . $uri . '. Method: ' . $method . '. Options: ' . print_r($options, true));
        if (!empty($options) && !array_key_exists('query', $options)) {
            $options = [
                'query' => $options
            ];
        }
        // bind the key to param
        $options['query']['key'] = $this->account->credentials['key'];
        $response = $this->requestAsync($method, $this->url . '/' . $uri, $options)->wait();
        $data = json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() !== 200) {            
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method . '. Response: ' . print_r($data, true));
        } else {
                $this->logDebug('URI: ' . $uri . '. Status Code: ' . $response->getStatusCode() . '. Method: ' . $method);
        }

        if ($response->getStatusCode() / 100 != 2) {
            set_log_extra('response_status_code', $response->getStatusCode());
            set_log_extra('response', $data);
            throw new \Exception('Error request Qoo10.');
        } elseif (array_key_exists('ErrorCode', $data) && in_array($data['ErrorCode'], [-90001, -90004, -90005	])) {
            $this->disableAccount(AccountStatus::REQUIRE_AUTH());
            return null;
        }

        return $data;
    }
    
}
