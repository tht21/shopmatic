<?php

namespace App\Integrations\Qoo10Legacy;

use App\Constants\AccountStatus;
use App\Constants\LocationType;
use App\Integrations\AbstractClient;
use App\Integrations\DBCookieJar;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use App\Utilities\DeathByCaptcha_SocketClient;
use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;

class Client extends AbstractClient
{

    const URLS = [
        Region::SINGAPORE => 'https://qsm.qoo10.sg',
        Region::MALAYSIA => 'https://qsm.qoo10.my',
    ];
    
    /*
     * NOTES:
     * 
     * 1.   We currently support creating this client / adapters from "Qoo10" account integration as to keep the code 
     *      separate as this is the reversed engineered method.
     * 
     * 2.   We currently don't run cron jobs in Qoo10Legacy if it's created using a "Qoo10" account
     * 
     */

    /*
     * This is the lists of methods that needs to be called after the account creation process.
     * This includes things such as outlets, account categories or anything else
     *
     * @var array
     */
    public $postAccountCreation = [
        'importReturnAddress' => 'Import return address from Qoo10.',
    ];

    protected $version = '1.0';

    protected $format = 'JSON';

    /**
     * Client constructor.
     *
     * @param Account $account
     * @param array $config
     * @throws \App\Utilities\DeathByCaptcha_InvalidCaptchaException
     */
    public function __construct(Account $account, $config = [])
    {
        $config['verify'] = false;
        $this->url = self::URLS[$account->region_id] . '/';
        $this->region = ($account->region_id == Region::SINGAPORE) ? 'SG' : 'MY';

        parent::__construct($account, array_merge([
            'cookies' => new DBCookieJar($account)
        ], $config));

        //TODO: create account nid get it 1 time first
        if (empty($this->account->credentials['cookies'])) {
            $this->login();
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
    public static function handleManualAuth($input) {
        // GET /GMKT.INC.Front.QAPIService/Giosis.qapi
        // ?v=1.0
        // &returnType=[xml] or [json(Default)]
        // &method=CertificationAPI.CreateCertificationKey
        // &key=[Seller Api Key]
        // &user_id=String&pwd=String

        //get page
        $client = new \GuzzleHttp\Client();
        $data = $client->request('get', self::URLS[$input['region']].'/GMKT.INC.Gsm.Web/login.aspx')->getBody();

        //get form value
        libxml_use_internal_errors(true);
        $crawler = new \DomDocument();
        $crawler->loadHTML($data);
        $reqNo = $crawler->getElementById('captcha_req_no')->attributes->getNamedItem('value')->value;
        $viewState = $crawler->getElementById('__VIEWSTATE')->attributes->getNamedItem('value')->value;
        $generator = $crawler->getElementById('__VIEWSTATEGENERATOR')->attributes->getNamedItem('value')->value;
        $eventval = $crawler->getElementById('__EVENTVALIDATION')->attributes->getNamedItem('value')->value;

        //get captcha
        $tmp = @tempnam(storage_path('captcha'), 'CAP');
        $client->requestAsync('get', self::URLS[$input['region']].'/GMKT.INC.Gsm.Web/Common/Page/qcaptcha.ashx?qcaptchr_req_no='.$reqNo, [
            'sink'  => $tmp
        ])->wait();

        //decode captcha
        $solver = new DeathByCaptcha_SocketClient('combinesell', 'combinesell1234');
        //$solver = new \Combinesell\Captcha\DeathByCaptcha_SocketClient('jincongho', 'q4Y-oeu-zcq-Pxb');
        $captcha = $solver->decode($tmp)['text'];

        //post login
        /** @var \GuzzleHttp\Psr7\Response $login */
        $login = $client->request('post', self::URLS[$input['region']].'/GMKT.INC.Gsm.Web/login.aspx', [
            'form_params' => [
                'captcha_req_no'            => $reqNo,
                'recaptcha_response_field'  => $captcha,
                '__VIEWSTATE'               => $viewState,
                '__VIEWSTATEGENERATOR'      => $generator,
                '__EVENTVALIDATION'         => $eventval,
                'txtLoginID'                => $input['username'],
                'txtLoginPwd'               => $input['password']
            ]
        ]);

        if (!self::isNotLogined($login)) {
            /** @var SHOP $shop */
            $shop = session('shop');
            $region = $input['region'];

            if ($region == Region::SINGAPORE) {
                $currency = 'SGD';
            } elseif ($region == Region::MALAYSIA) {
                $currency = 'MYR';
            } else {
                set_log_extra('response', $input);
                throw new \Exception('Region ' . Region::REGIONS[$region] . ' for Qoo10 not supported yet');
            }

            $queryData = [
                'name'           => $input['username'],
                'integration_id' => Integration::QOO10_LEGACY,
                'region_id'      => $region,
                'shop_id'        => $shop->id,
                'currency'       => $currency,
            ];

            $credentials = [
                'username' => $input['username'],
                'password' => $input['password']
            ];

//            // This is used in case there's other information stored in credentials
//            // E.g. for a hybrid authentication
//            if (!empty($account)) {
//                $credentials = array_merge($account->credentials, $credentials);
//            }
            $account = Account::updateOrCreate($queryData, [
                    'credentials' => $credentials,
                    'status'      => AccountStatus::ACTIVE()
                ]
            );

            // Get has features from integration region
            $hasFeatures = ($account->integration->features[$region]["has_features"]) ?? null;

            // Update new setting, if the setting already exist do not update the VALUE
            if ($hasFeatures) {
                $settings = $account->settings;
                $newSettings = [];

                foreach ($hasFeatures as $name => $feature) {
                    // If exists just update except value
                    if ($settings && array_key_exists($name, $settings)) {
                        // Remove default value, to avoid update user set value
                        unset($feature['value']);
                        $newSettings[$name] = array_replace($settings[$name], $feature);
                    } else {
                        // Else add new
                        $newSettings[$name] = $feature;
                    }
                }

                $account->settings = $newSettings;
                $account->save();
            }

            $account = $account->fresh();

            return $account;
        } else {
            set_log_extra('input', $input);
            throw new \Exception('Bad Credential for Qoo10 Legacy');
        }
    }

    /**
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall
     *
     * @return bool
     * @throws \App\Utilities\DeathByCaptcha_InvalidCaptchaException
     */
    public function isCredentialsValid($actualCall = false)
    {
        return $this->login();
    }

    /**
     * Request
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param int $maxLogin
     *
     * @return mixed|ResponseInterface
     */
    public function request($method, $uri = '', array $options = [], $maxLogin=1)
    {
        return $this->requestAsync($method, $uri, $options, $maxLogin)->wait();
    }

    /**
     *
     * @param $method
     * @param string $uri
     * @param array $options
     *
     * @param int $maxLogin
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri = '', array $options = [], $maxLogin = 1)
    {
        $options = array_merge(['headers' => ['referer' => 'https://qsm.qoo10.sg']], $options);
        $response = parent::requestAsync($method, $uri, $options);

        //check logined
        return $response->then(function (ResponseInterface $response) use ($method, $uri, $options, $maxLogin) {
            $body = json_decode($response->getBody(), true);
            if (isset($body['ExceptionType']) && $body['ExceptionType'] == 'System.UnauthorizedAccessException') {
                throw new \Exception('Qoo10 UnauthorizedAccessException. Account ID: ' . $this->account->id);
            }

            //TODO: Fix this if. Not possible to check state of login here.
            if($this->isNotLogined($response) && ($maxLogin > 0)){

                //This part works, so it should disable the integration
                if (!$this->login()) {
                    // TODO: disable account
//                    $this->disableIntegration(true);
                    throw new \Exception('Can\'t login user. Logging out. Account ID: ' . $this->account->id);
                }
                sleep(5);
                return $this->requestAsync($method, $uri, $options, --$maxLogin);
            }else{
                return $response;
            }
        });
    }

    /**
     * Try login into qoo10
     *
     * @return bool
     * @throws \App\Utilities\DeathByCaptcha_InvalidCaptchaException
     */
    public function login()
    {
        //get page
        $data = $this->request('get', 'GMKT.INC.Gsm.Web/login.aspx', [], 0)->getBody();

        //get form value
        libxml_use_internal_errors(true);
        $crawler = new \DomDocument();
        $crawler->loadHTML($data);
        $reqNo = $crawler->getElementById('captcha_req_no')->attributes->getNamedItem('value')->value;
        $viewState = $crawler->getElementById('__VIEWSTATE')->attributes->getNamedItem('value')->value;
        $generator = $crawler->getElementById('__VIEWSTATEGENERATOR')->attributes->getNamedItem('value')->value;
        $eventval = $crawler->getElementById('__EVENTVALIDATION')->attributes->getNamedItem('value')->value;

        //get captcha
        $tmp = @tempnam(storage_path('captcha'), 'CAP');
        $this->request('get', 'GMKT.INC.Gsm.Web/Common/Page/qcaptcha.ashx?qcaptchr_req_no='.$reqNo, [
            'sink'  => $tmp
        ], 0);
        //decode captcha
        $solver = new DeathByCaptcha_SocketClient('combinesell', 'combinesell1234');
        //$solver = new \Combinesell\Captcha\DeathByCaptcha_SocketClient('jincongho', 'q4Y-oeu-zcq-Pxb');
        $captcha = $solver->decode($tmp)['text'];

        //post login
        /** @var \GuzzleHttp\Psr7\Response $login */
        $login = $this->request('post', 'GMKT.INC.Gsm.Web/login.aspx', [
            'form_params' => [
                'captcha_req_no'            => $reqNo,
                'recaptcha_response_field'  => $captcha,
                '__VIEWSTATE'               => $viewState,
                '__VIEWSTATEGENERATOR'      => $generator,
                '__EVENTVALIDATION'         => $eventval,
                'txtLoginID'                => $this->account->credentials['username'],
                'txtLoginPwd'               => $this->account->credentials['password']
            ],
        ], 0);

        return !self::isNotLogined($login);
    }

    /**
     * Check if user is able to login
     *
     * @param $response \GuzzleHttp\Psr7\Response
     *
     * @return bool
     */
    public static function isNotLogined($response)
    {
        //Probably this is better way - Not sure if it encompasses all cases
        //TODO: Check if it covers all use case
        if ($response->getStatusCode() == Response::HTTP_UNAUTHORIZED) {
            return true;
        }

        if(!array_key_exists(0,$response->getHeader('Content-Type')) ||  strpos($response->getHeader('Content-Type')[0] ?? '', 'json') === false)  {
            libxml_use_internal_errors(true);
            $dom = new \DomDocument();
            $dom->loadHTML((string) $response->getBody());
            $title = $dom->getElementsByTagName('title')->item(0);

            return (isset($title) && trim($title->nodeValue) == 'QSM LOGIN');
        }else{
            $json = json_decode($response->getBody());
            return (isset($json) && isset($json->Message) && ($json->Message == 'Authentication failed.'));
        }
    }

    /**
     * Get Seller No
     *
     * @return string|null
     */
    public function getSellerNo(){
        if (!empty($this->account->credentials['cookies'])) {
            foreach ($this->account->credentials['cookies'] as $value) {
                if ($value['Name'] == 'qsm_cust_no') {
                    return $value['Value'];
                }
            }
        }
        return null;
    }

    /**
     * Get Login ID
     * source: https://qsm.qoo10.sg/GMKT.INC.Gsm.Web/Seller/EditMyInfo.aspx
     *
     * @return string|null
     */
    public function getLoginId()
    {
        $custNo = $this->getSellerNo();
        if ($custNo) {
            $response = $this->request('post', 'GMKT.INC.Gsm.Web/swe_SellerBizService.asmx/GetSellerConfirmInfo', [
                'json' => [
                    'cust_no' => $custNo
                ]
            ]);

            $json = json_decode($response->getBody(), true);

            if (isset($json['d']) && array_key_exists('Rows', $json['d']) && count($json['d']['Rows']) > 0 && array_key_exists('login_id', $json['d']['Rows'][0])) {
                return $json['d']['Rows'][0]['login_id'];
            }
        }
        return $custNo;
    }

    /**
     * Cron job for integration set in $job init.php
     * Pull all the available marketplace fee of the day
     *
     * @throws \Exception
     */
    public function retrieveSettlement()
    {
        $shippingFeesBySeller = $this->getSellingReportRecvDetailList();
        $commissionFees = $this->getSellingReportDetailList();

        foreach ($shippingFeesBySeller as $key => $value) {
            $this->account->orders()->whereHas('items', function($query) use ($value) {
                $query->where('tracking_number', $value['no_songjang']);
            })->update([
                'actual_shipping_fee' => $value['acc_recv_amount']
            ]);
        }

        foreach ($commissionFees as $key => $value) {
            $this->account->orders()->where('external_id', $value['contr_no'])->update([
                'commission_fee' => $value['commission']
            ]);
        }
    }

    /**
     * getSellingReportRecvDetailList contains shipping fee paid by seller
     *
     * @throws \Exception
     */
    private function getSellingReportRecvDetailList()
    {
        $cache = substr(str_replace(".", "", microtime(true)),0,-1);
        $response = $this->request('post', 'GMKT.INC.Gsm.Web/swe_DynamicDataService.asmx/ExecuteToDataTable', [
            'json' => [
                'id' => 'Account.GetSellingReportRecvDetailList',
                'paramList' => [
                    'ParamList' => [
                        [
                            'Name' => 'cust_no',
                            'Value' => $this->getSellerNo()
                        ],
                        [
                            'Name' => 'srch_dt',
                            'Value' => 'DT1'
                        ],
                        [
                            'Name' => 'sday',
                            'Value' => now()->subYear()->format("M d, Y")
                        ],
                        [
                            'Name' => 'eday',
                            'Value' => now()->format("M d, Y")
                        ],
                        [
                            'Name' => 'srch_type',
                            'Value' => 'REF_NO'
                        ],
                        [
                            'Name' => 'srch_value',
                            'Value' => ''
                        ],
                        [
                            'Name' => 'srch_kind',
                            'Value' => 'REF_NO'
                        ],
                        [
                            'Name' => 'svc_nation_cd',
                            'Value' => $this->account->region
                        ],
                        [
                            'Name' => 'currency_cd',
                            'Value' => $this->account->currency
                        ],
                        [
                            'Name' => 'sttl_stat',
                            'Value' => ''
                        ],
                        [
                            'Name' => 'jp_yn',
                            'Value' => 'N'
                        ]
                    ]
                ],
                '___cache_expire___' => $cache
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        if(isset($body['d']) && isset($body['d']['ReturnData'])){
            return $body['d']['ReturnData']['Rows'];
        } else {
            set_log_extra('response', $body);
            throw new \Exception('Unable to retrieve selling report recv for Qoo10');
        }
    }

    /**
     * getSellingReportRecvDetailList contains shipping fee paid by seller
     *
     * @return mixed
     * @throws \Exception
     */
    private function getSellingReportDetailList()
    {
        $cache = substr(str_replace(".", "", microtime(true)),0,-1);
        $response = $this->request('post', 'GMKT.INC.Gsm.Web/swe_DynamicDataService.asmx/ExecuteToDataTable', [
            'json' => [
                'id' => 'Account.GetSellingReportDetailList',
                'paramList' => [
                    'ParamList' => [
                        [
                            'Name' => 'cust_no',
                            'Value' => $this->getSellerNo()
                        ],
                        [
                            'Name' => 'srch_dt',
                            'Value' => 'DT1'
                        ],
                        [
                            'Name' => 'sday',
                            'Value' => now()->subYear()->format("M d, Y")
                        ],
                        [
                            'Name' => 'eday',
                            'Value' => now()->format("M d, Y")
                        ],
                        [
                            'Name' => 'srch_type',
                            'Value' => 'REF_NO'
                        ],
                        [
                            'Name' => 'srch_value',
                            'Value' => ''
                        ],
                        [
                            'Name' => 'srch_kind',
                            'Value' => 'REF_NO'
                        ],
                        [
                            'Name' => 'svc_nation_cd',
                            'Value' => $this->region
                        ],
                        [
                            'Name' => 'currency_cd',
                            'Value' => $this->account->currency
                        ],
                        [
                            'Name' => 'sttl_stat',
                            'Value' => ''
                        ],
                        [
                            'Name' => 'cod_type',
                            'Value' => 'N'
                        ],
                        [
                            'Name' => 'jp_yn',
                            'Value' => 'N'
                        ]
                    ]
                ],
                '___cache_expire___' => $cache
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        if(isset($body['d']) && isset($body['d']['ReturnData'])){
            return $body['d']['ReturnData']['Rows'];
        } else {
            set_log_extra('response', $body);
            throw new \Exception('Unable to retrieve selling report recv for Qoo10');
        }
    }

    /**
     *
     * @param Account $account
     * @param array $request
     *
     * @return array
     */
    public function importReturnAddress(Account $account, $request)
    {
//        try {
            $cache = substr(str_replace(".", "", microtime(true)), 0, -1);
            $response = $this->request('post', 'GMKT.INC.Gsm.Web/swe_SellerBizService.asmx/GetCustomAddress', [
                'json' => [
                    'cust_no' => $this->getSellerNo(),
                    'addr_type' => '-1',
                    '___cache_expire___' => $cache
                ]
            ]);

            $response = json_decode($response->getBody(), true);
            $results = [];
//            dump($response);

            if (isset($response['d'])) {
                $locations = $response['d']['Rows'];
                foreach ($locations as $key => $location) {
                    $results[] = $account->locations()->updateOrCreate([
                        'shop_id' => $account->shop_id,
                        'external_id' => $location['addr_no']
                    ], [
                        'label' => $location['recv_nm'],
                        'name' => $location['recv_nm'],
                        'address_1' => $location['addr_front'],
                        'address_2' => $location['addr_last'],
                        'city' => $location['city'],
                        'state' => $location['state'],
                        'postcode' => $location['zip_code'],
                        'country' => $location['nation_cd'],
                        'type' => LocationType::WAREHOUSE(), // for returned address
                        'attributes' => $location,
                        'contact_name' => $location['recv_nm'],
                        'contact_number' => $location['hp_no'],
                        'contact_email' => $location['email']
                    ]);
                }
            }

            return $results;
//        } catch (\Exception $exception) {
//            dump($exception);
//        }
    }
}
