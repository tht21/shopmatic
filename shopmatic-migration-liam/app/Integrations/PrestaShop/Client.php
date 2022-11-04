<?php

namespace App\Integrations\PrestaShop;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;


class Client extends AbstractClient
{
    /*
     * This is the lists of methods that needs to be called after the account creation process.
     * This includes things such as outlets, account categories or anything else
     *
     * @var array
     */
    public $postAccountCreation = [
        'importAccountCategories' => 'Import categories from PretaShop.',
    ];

    /**
     * Client constructor.
     *
     * @param Account $account
     * @param array $config
     */
    public function __construct(Account $account, $config = [])
    {
        $this->url = $account->credentials['protocol'] . $account->credentials['access_key'] .'@'. $account->credentials['store_url'] . '/api/';

        parent::__construct($account, $config);
    }

    public function getFullPath()
    {
        return $this->url;
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
        $storeUrl = $input['store_url'] ?? null;
        $accessKey = $input['access_key'] ?? null;

        if (!$storeUrl || !$accessKey) {
            set_log_extra('credentials', 'Presta Shop store url or access key not set.');
            throw new \Exception('Presta Shop store url or access key not set.');
        }

        // Make sure the storeUrl is start with scheme
        $parsed = parse_url($storeUrl);
        if (!isset($parsed['scheme']) || empty($parsed['scheme'])) {
            throw new \Exception('Please make sure store url include http or https.');
        }

        $url = explode('://', $storeUrl);
        $protocol = $url[0].'://';
        $storeUrl = $url[1];
        // Make sure last string is not /
        if (substr($storeUrl, -1) == '/') {
            $storeUrl = rtrim($storeUrl, "/");
        }
        // Replace http:// to https:// if it doesn't exist in the URL
        // $storeUrl = str_ireplace( 'http://', 'https://', $storeUrl);

        $headers = [
            'Output-Format' => 'JSON',
        ];
        $client = new \GuzzleHttp\Client(['verify' => false/*, 'headers' => $headers*/]);
        $fullUrl = $protocol . $storeUrl. '/api?&ws_key='.$accessKey;

        try {
            $response = $client->get($fullUrl);

            /** @var Shop $shop */
            $shop = session('shop');
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $data['store_url'] = $storeUrl;
                $data['access_key'] = $accessKey;
                $data['protocol'] = $protocol;

                return self::handleAuthenticationResponse($data, $shop);
            } else {
                set_log_extra('response', json_decode($response->getBody()->getContents(), true));
                throw new \Exception(json_decode($response->getBody()->getContents(), true));
            }
        } catch (\Exception $e) {
            if ($e->getCode() === 401) {
                set_log_extra('credentials', $e->getMessage());
                throw new \Exception('Presta Shop credentials is invalid.');
            } else {
                set_log_extra('response', 'Unable to make request for Presta Shop. Body: ' . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Handles the authentication response for logging in and refreshing token
     *
     * @param $data
     * @param $shop
     * @return mixed
     * @throws \Exception
     */
    private static function handleAuthenticationResponse($data, $shop)
    {
        try {
            //$name = $data->api->attributes()->shopName.''; // Need to add string behind only can get the shop name string
            $name = $data['store_url']; // Will be using store url as name cause better to be unique, not sure shop name will duplicate or not
            $region = Region::GLOBAL;
            $currency = 'SGD';

            $queryData = [
                'name'           => $name,
                'integration_id' => Integration::PRESTASHOP,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'store_url' => $data['store_url'],
                'access_key' => $data['access_key'],
                'protocol' => $data['protocol'],
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
        try {
            return $this->requestAsync($method, $uri, $options)->wait();
        } catch (ConnectException $e) {
            //Catch the guzzle connection errors over here.These errors are something
            // like the connection failed or some other network error
            $this->disableAccount(AccountStatus::REQUIRE_AUTH());
            throw new \Exception('Presta Shop connection error.');
        }

    }

    /**
     * Call api url with account credentials
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $fullUrl = $this->url.$uri;
        $options = array_merge([
            'headers' => [
                'Output-Format' => 'JSON'
            ]
        ], $options);

        $response = parent::requestAsync($method, $fullUrl, $options);

        return $response->then(function (ResponseInterface $response) use ($fullUrl) {
            if ($response->getStatusCode() === 401) {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
                set_log_extra('response', $response->getBody()->getContents());
                throw new \Exception('Presta Shop authentication error.');
            } else if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201 && $response->getStatusCode() !== 202) {
                // @NOTE - only for images api, as the API return images even though it has error
                if (strpos($fullUrl, 'api/images/products') !== false) {
                    return $response;
                }

                $response = json_decode($response->getBody()->getContents(), true);
                $errorMsg = 'Presta Shop unable to process the request.';
                if (isset($response['errors'][0]['message'])) {
                    $errorMsg = $response['errors'][0]['message'];
                }
                set_log_extra('response', $response);
                throw new \Exception($errorMsg);
            } else  {
                return $response;
            }
        });
    }

    /**
     * Upload image to presta shop
     * Based on their document
     * http://doc.prestashop.com/display/PS16/Chapter+9+-+Image+management
     * https://devdocs.prestashop.com/1.7/development/webservice/tutorials/change_product_image/
     *
     * @param string $uri
     * @param array $options
     * @return bool|string
     * @throws \Exception
     */
    public function requestImage($uri = '', array $options = [])
    {
        $fullUrl = $this->url.$uri;
        $key = $this->account->credentials['access_key'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data','Expect:'));
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_PUT, true); //edit
        curl_setopt($ch, CURLOPT_USERPWD, $key.':');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if($response === false) {
            set_log_extra('options', $options);
            set_log_extra('response', $response);
            throw new \Exception('Unable to upload image for presta shop');
        }
        return $response;
    }

    public function isCredentialsValid($actualCall = false)
    {
        $headers = [
            'Output-Format' => 'JSON',
        ];
        $client = new \GuzzleHttp\Client(['verify' => false, 'headers' => $headers]);
        try {
            $response = $client->get($this->url);

            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
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
