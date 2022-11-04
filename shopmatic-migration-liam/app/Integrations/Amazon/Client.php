<?php

namespace App\Integrations\Amazon;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use ClouSale\AmazonSellingPartnerAPI\Api\FeedsApi;
use ClouSale\AmazonSellingPartnerAPI\AssumeRole;
use ClouSale\AmazonSellingPartnerAPI\Configuration;
use ClouSale\AmazonSellingPartnerAPI\Models\Feeds\CreateFeedDocumentSpecification;
use ClouSale\AmazonSellingPartnerAPI\Models\Feeds\CreateFeedSpecification;
use ClouSale\AmazonSellingPartnerAPI\SellingPartnerEndpoint;
use ClouSale\AmazonSellingPartnerAPI\SellingPartnerOAuth;
use ClouSale\AmazonSellingPartnerAPI\SellingPartnerRegion;

class Client extends AbstractClient
{
    private $client;
    private $config;
    private $options;

    protected $account;
    protected $xmlVersion = '1.0';
    protected $document;

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
     * @throws \Exception
     */
    public function __construct(Account $account, $config = [])
    {
        $this->account = $account;

        $region = SellingPartnerRegion::$EUROPE;
        $endpoint = SellingPartnerEndpoint::$EUROPE;
        if ($this->account->credentials['marketplace_id'] == 'ATVPDKIKX0DER') {
            $region = SellingPartnerRegion::$NORTH_AMERICA;
            $endpoint = SellingPartnerEndpoint::$NORTH_AMERICA;
        } else if ($this->account->credentials['marketplace_id'] == 'A19VAU5U5O7RUS') {
            $region = SellingPartnerRegion::$FAR_EAST;
            $endpoint = SellingPartnerEndpoint::$FAR_EAST;
        }

        $this->options = [
            'refresh_token' => $this->account->credentials['refresh_token'], // Aztr|...
            'client_id' => config('combinesell.amazon.client_id'), // App ID from Seller Central, amzn1.sellerapps.app.cfbfac4a-......
            'client_secret' => config('combinesell.amazon.client_secret'), // The corresponding Client Secret
            'region' => $region, // or NORTH_AMERICA / FAR_EAST
            'access_key' => config('combinesell.amazon.access_key'), // Access Key of AWS IAM User, for example AKIAABCDJKEHFJDS
            'secret_key' => config('combinesell.amazon.secret_key'), // Secret Key of AWS IAM User
            'endpoint' => $endpoint, // or NORTH_AMERICA / FAR_EAST
            'role_arn' => config('combinesell.amazon.role_arn'), // AWS IAM Role ARN for example: arn:aws:iam::123456789:role/Your-Role-Name
        ];

        parent::__construct($account, $config);

        try {
            $this->refreshToken($this->options);

            $this->config = Configuration::getDefaultConfiguration();
            $this->config->setHost($this->options['endpoint']);
            $this->config->setAccessToken($this->account->credentials['access_token']);
            $this->config->setAccessKey($this->account->credentials['access_key_id']);
            $this->config->setSecretKey($this->account->credentials['secret_access_key']);
            $this->config->setRegion($this->options['region']);
            $this->config->setSecurityToken($this->account->credentials['security_token']);
        } catch (\Exception $exception) {
            set_log_extra('account', $account->toArray());
            throw $exception;
        }
    }

    /**
     * This is only required if the integration supports fields
     *
     * @param $input
     *
     * @return Account|null|mixed The account if it's successfully created
     * @throws \Exception
     */
    public static function handleManualAuth($input)
    {
        $url = config('amazon.'.$input['marketplace_id'].'.Default_URI');
        $redirect = config('combinesell.amazon.redirect');
        if (!$url) {
            set_log_extra('marketplace_id', $input['marketplace_id']);
            throw new \Exception('Invalid Amazon marketplace ID.');
        }
        $applicationId = config('combinesell.amazon.app_id');

        // Generate a state parameter value, helping to guard against cross-site request forgery attacks
        if (empty(session()->get('AMAZON_STATE'))) {
            session(['AMAZON_STATE' => bin2hex(random_bytes(32) . session('shop')->getRouteKey())]);
        }
        $state = session()->get('AMAZON_STATE');

        $url = $url.'/apps/authorize/consent?application_id='.$applicationId.'&state='.$state.'&redirect_uri='.$redirect.'/'.$input['marketplace_id'];

        // @NOTE - If you include the version=beta parameter, the workflow authorizes an application in Draft state.
        // @NOTE - If you do not include the parameter, the workflow authorizes an application published on the Marketplace Appstore.
        if (config('app.env') !== 'production') {
            $url .= '&version=beta';
        }

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
        /** @var Shop $shop */
        $shop = session('shop');
        $state = $input['state'];
        $spapiOauthCode = $input['spapi_oauth_code'];
        $sellingPartnerId = $input['selling_partner_id'];
        $marketplaceId = $input['extra_param'];

        if (!empty($state) && hash_equals(session()->get('AMAZON_STATE'), $state)) {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            session()->forget('AMAZON_STATE');

            // Exchanges the LWA authorization code for a LWA refresh token
            try {
                $response = $client->post("https://api.amazon.com/auth/o2/token", [
                    'form_params' =>
                        [
                            'grant_type'    => 'authorization_code',
                            'code'          => $spapiOauthCode,
                            'client_id'     => config('combinesell.amazon.client_id'),
                            'client_secret' => config('combinesell.amazon.client_secret')
                        ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody()->getContents(), true);

                    $data['selling_partner_id'] = $sellingPartnerId;
                    $data['marketplace_id'] = $marketplaceId;

                    $region = SellingPartnerRegion::$EUROPE;
                    if ($data['marketplace_id'] == 'ATVPDKIKX0DER') {
                        $region = SellingPartnerRegion::$NORTH_AMERICA;
                    } else if ($data['marketplace_id'] == 'A19VAU5U5O7RUS') {
                        $region = SellingPartnerRegion::$FAR_EAST;
                    }
                    // Retrieve STS token (Assume Role)
                    $assumedRole = AssumeRole::assume(
                        $region,
                        config('combinesell.amazon.access_key'),
                        config('combinesell.amazon.secret_key'),
                        config('combinesell.amazon.role_arn'),
                        43200 # 12Hours
                    );
                    $data['access_key_id'] = $assumedRole->getAccessKeyId();
                    $data['secret_access_key'] = $assumedRole->getSecretAccessKey();
                    $data['security_token'] = $assumedRole->getSessionToken();

                    return self::handleAuthenticationResponse($data, $shop);
                } else {
                    throw new \Exception($response->getBody()->getContents());
                }

            } catch (\Exception $e) {
                set_log_extra('response', 'Unable to request token for Amazon. Body: ' . $e->getMessage());
                throw $e;
            }
        } else {
            return false;
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
            // Global
            if ($data['marketplace_id'] == 'ATVPDKIKX0DER') {
                $currency = 'USD';
                $region = Region::GLOBAL;
            } elseif ($data['marketplace_id'] == 'A19VAU5U5O7RUS') {
                $currency = 'SGD';
                $region = Region::SINGAPORE;
            } else {
                set_log_extra('response', $data);
                throw new \Exception('Region ' . $data['marketplace_id'] . ' for Amazon not supported yet');
            }

            $token = $data['access_token'];
            $tokenType = $data['token_type'];
            $expiry = time() + $data['expires_in'];
            $refreshToken = $data['refresh_token'];
            $marketplaceId = $data['marketplace_id'];
            $accessKeyId = $data['access_key_id'];
            $secretAccessKey = $data['secret_access_key'];
            $securityToken = $data['security_token'];
            $securityTokenExpiry = time() + 43200;

            $queryData = [
                'name'           => $data['selling_partner_id'],
                'integration_id' => Integration::AMAZON,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $account = Account::where($queryData)->first();

            $credentials = [
                'access_token'      => $token,
                'token_type'        => $tokenType,
                'refresh_token'     => $refreshToken,
                'expiry'            => $expiry,
                'marketplace_id'    => $marketplaceId,
                'access_key_id'     => $accessKeyId,
                'secret_access_key' => $secretAccessKey,
                'security_token'    => $securityToken,
                'security_token_expiry' => $securityTokenExpiry,
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

    }

    /**
     * Check auth credential to get client
     *
     * @return Configuration
     */
    public function getSPConfig()
    {
        return $this->config;
    }

    /**
     * Check and refresh SP API security and access token
     *
     * @param $options
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function refreshToken($options)
    {
        // Check security token expiry
        if ($this->account->credentials['security_token_expiry'] <= time()) {
            $assumedRole = AssumeRole::assume(
                $options['region'],
                config('combinesell.amazon.access_key'),
                config('combinesell.amazon.secret_key'),
                config('combinesell.amazon.role_arn'),
                43200 #12 Hours
            );

            $credentials = [
                'access_key_id'     => $assumedRole->getAccessKeyId(),
                'secret_access_key' => $assumedRole->getSecretAccessKey(),
                'security_token'    => $assumedRole->getSessionToken(),
                'security_token_expiry' => time() + 43200,
            ];

            $credentials = array_merge($this->account->credentials, $credentials);

            $this->account->credentials = $credentials;
            $this->account->save();
        }

        // Check access token expiry
        if ($this->account->credentials['expiry'] <= time()) {
            $accessToken = SellingPartnerOAuth::getAccessTokenFromRefreshToken(
                $options['refresh_token'],
                $options['client_id'],
                $options['client_secret']
            );

            $credentials = [
                'access_token'  => $accessToken,
            ];

            $credentials = array_merge($this->account->credentials, $credentials);

            $this->account->credentials = $credentials;
            $this->account->save();
        }
    }

    /**
     * Create feed document
     *
     * @param $body
     * @return \ClouSale\AmazonSellingPartnerAPI\Models\Feeds\CreateFeedDocumentResponse
     * @throws \Exception
     */
    public function createFeedDocument($body)
    {
        $apiInstance = new FeedsApi($this->config);

        $body = new CreateFeedDocumentSpecification($body); // \Swagger\Client\Models\CreateFeedDocumentSpecification |

        try {
            $feedDocument = $apiInstance->createFeedDocument($body);

            return $feedDocument;
        } catch (\Exception $exception) {
            $message = $this->handleErrorMessage($exception);
            if (is_null($message)) {
                $message = $exception->getMessage();
            }
            set_log_extra('body', $body);
            throw new \Exception($message);
        }
    }

    /**
     * Create feed
     *
     * @param $body
     * @return \ClouSale\AmazonSellingPartnerAPI\Models\Feeds\CreateFeedResponse
     * @throws \Exception
     */
    public function createFeed($body)
    {
        $apiInstance = new FeedsApi($this->config);

        $body = new CreateFeedSpecification($body);
        try {
            $response = $apiInstance->createFeed($body);

            $this->logDebug('Body: ' . $body . '. Response: ' . print_r($response->getPayload(), true));

            return $response;
        } catch (\Exception $exception) {
            set_log_extra('body', $body);
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Submit feed and convert to xml format
     *
     * @param $feedType
     * @param $data
     * @return \ClouSale\AmazonSellingPartnerAPI\Models\Feeds\CreateFeedResponse
     * @throws \Exception
     */
    public function submitFeed($feedType, $data)
    {
        $this->logDebug('Feed type: ' . $feedType . '. Options: ' . print_r($data, true));

        // Create a feed document
        $feedDocument = $this->createFeedDocument([
            'content_type' => 'text/xml; charset=UTF-8'
        ]);
        $this->logDebug('Feed type: ' . $feedType . '. Response: ' . print_r($feedDocument->getPayload(), true));

        // Encrypt feed data
        $feedDocumentId = $feedDocument->getPayload()->getFeedDocumentId();
        $url = $feedDocument->getPayload()->getUrl();
        $key = $feedDocument->getPayload()->getEncryptionDetails()->getKey();
        $key = base64_decode($key, true);
        $initializationVector = base64_decode($feedDocument->getPayload()->getEncryptionDetails()->getInitializationVector(), true);

        $feed = $this->dataToXml($data);

        $feed = str_replace(
            '<AmazonEnvelope>',
            '<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">',
            $feed
        );

        $encryptedFeedData = openssl_encrypt(utf8_encode($feed), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $initializationVector);

        // Upload the feed data
        if ($this->uploadFeedData($url, $encryptedFeedData)) {
            // Submit feed
            $feedResponse = $this->createFeed([
                'feed_type' => $feedType,
                'marketplace_ids' => [$this->account->credentials['marketplace_id']],
                'input_feed_document_id' => $feedDocumentId,
            ]);
        } else {
            set_log_extra('feed', $feed);
            throw new \Exception('Unable to upload feed data for Amazon');
        }

        return $feedResponse;
    }

    /**
     * Get feed result
     *
     * @param $feedId
     * @return \ClouSale\AmazonSellingPartnerAPI\Models\Feeds\GetFeedResponse
     * @throws \Exception
     */
    public function getFeed($feedId)
    {
        $apiInstance = new FeedsApi($this->config);

        try {
            $response = $apiInstance->getFeed($feedId);

            return $response;
        } catch (\Exception $exception) {
            set_log_extra('feed_id', $feedId);
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Get feed document
     *
     * @param $feedDocumentId
     * @return \ClouSale\AmazonSellingPartnerAPI\Models\Feeds\GetFeedDocumentResponse
     * @throws \Exception
     */
    public function getFeedDocument($feedDocumentId)
    {
        $apiInstance = new FeedsApi($this->config);

        try {
            $response = $apiInstance->getFeedDocument($feedDocumentId);

            return $response;
        } catch (\Exception $exception) {
            set_log_extra('feed_document_id', $feedDocumentId);
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Upload feed data with uri
     *
     * @param $url
     * @param $encryptedFeedData
     * @return bool
     */
    public function uploadFeedData($url, $encryptedFeedData)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $encryptedFeedData,
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml',
                'Content-Type: ' . 'text/xml; charset=UTF-8',
            ],
        ));

        $response = curl_exec($curl);

        $error = curl_error($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode >= 200 && $httpCode <= 299) {
            return true;
        }
        return false;
    }

    /**
     * Convert array to XML format
     *
     * @param $data
     * @return string
     */
    public function dataToXml($data)
    {
        $data = array_merge([
            'Header' => [
                'DocumentVersion' => 1.01,
                'MerchantIdentifier' => $this->account->name
            ]
        ], $data);

        $this->document = new \DOMDocument($this->xmlVersion);

        $this->arrayToDOMDoc($data);

        return $this->document->saveXML();
    }

    private function arrayToDOMDoc($arrays, $rootElement = 'AmazonEnvelope')
    {
        $root = $this->document->createElement($rootElement);

        $this->document->appendChild($root);

        $this->convertElement($root, $arrays);
    }

    private function convertElement($element, $value)
    {
        $sequential = $this->isArrayAllKeySequential($value);

        if (!is_array($value)) {
            $value = htmlspecialchars($value);
            // Remove control characters
            $value = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

            $element->nodeValue = $value;

            return;
        }

        foreach ($value as $key => $data) {
            if (!$sequential) {
                if (($key === '_attributes') || ($key === '@attributes')) {
                    //$this->addAttributes($element, $data);
                    foreach ($data as $attrKey => $attrVal) {
                        $element->setAttribute($attrKey, $attrVal);
                    }
                } elseif ((($key === '_value') || ($key === '@value')) && is_string($data)) {
                    $element->nodeValue = htmlspecialchars($data);
                } elseif ((($key === '_cdata') || ($key === '@cdata')) && is_string($data)) {
                    $element->appendChild($this->document->createCDATASection($data));
                } elseif ((($key === '_mixed') || ($key === '@mixed')) && is_string($data)) {
                    $fragment = $this->document->createDocumentFragment();
                    $fragment->appendXML($data);
                    $element->appendChild($fragment);
                } elseif ($key === '__numeric') {
                    $this->addNumericNode($element, $data);
                } else {
                    $this->addNode($element, $key, $data);
                }
            } elseif (is_array($data)) {
                $this->addCollectionNode($element, $data);
            } else {
                $this->addSequentialNode($element, $data);
            }
        }
    }

    protected function addNode(\DOMElement $element, $key, $value)
    {
        //if ($this->replaceSpacesByUnderScoresInKeyNames) {
        $key = str_replace(' ', '_', $key);
        //}

        $child = $this->document->createElement($key);
        $element->appendChild($child);
        $this->convertElement($child, $value);
    }

    protected function addCollectionNode(\DOMElement $element, $value)
    {
        if ($element->childNodes->length === 0 && $element->attributes->length === 0) {
            $this->convertElement($element, $value);

            return;
        }

        $child = $this->document->createElement($element->tagName);
        $element->parentNode->appendChild($child);
        $this->convertElement($child, $value);
    }

    private function isArrayAllKeySequential($value)
    {
        if (!is_array($value)) {
            return false;
        }

        if (count($value) <= 0) {
            return true;
        }

        if (key($value) === '__numeric') {
            return false;
        }

        return array_unique(array_map('is_int', array_keys($value))) === [true];
    }

    private function handleErrorMessage(\Exception $exception)
    {
        $message = null;
        $start = null;
        $end = null;
        // Check for Unauthorized
        if ($exception->getCode() === 403) {
            $start = '"code":';
            $end = ',';
        } else if ($exception->getCode() === 429) { // Quote Exceeded
            $start = '"message":';
            $end = ',';
        }

        if (!is_null($start) && !is_null($end)) {
            $pattern = sprintf(
                '/%s(.+?)%s/ims',
                preg_quote($start, '/'), preg_quote($end, '/')
            );

            if (preg_match($pattern, $exception->getMessage(), $matches)) {
                list(, $match) = $matches;
                $message = ltrim(str_replace('"', '', $match));
            }
        }

        if ($message === 'Unauthorized') {
            $this->disableAccount(AccountStatus::REQUIRE_AUTH());
        }
        return $message;
    }
}
