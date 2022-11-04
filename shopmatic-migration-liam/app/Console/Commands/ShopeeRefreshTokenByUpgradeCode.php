<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Integration;

class ShopeeRefreshTokenByUpgradeCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:refresh_token_by_upgrade_code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shopee Get Fresh Token By Upgrade Code';


    protected $url = 'https://partner.shopeemobile.com/api/v2/public/get_refresh_token_by_upgrade_code';

    protected $upgradeCode = '565347634d5671544b4a6a4f5275636e73774169696658624579564168516770';

    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        /**
         * Get Shop List
        */
        $integrationId = Integration::SHOPEE;
        $shopeeMerchantShops = [];
        $accounts = Account::active()->where('integration_id', $integrationId)->get();
        foreach ($accounts as $integration) {
            if (!empty($integration['credentials'])) {
                if (isset($integration['credentials']['access_token'])) {
                    $shopeeMerchantShops[$integration['credentials']['access_token']] = $integration;
                }
            }
        }
        
        if (!empty($shopeeMerchantShops)) {
            $mpShopIds = array_keys($shopeeMerchantShops);
            foreach (array_chunk($mpShopIds, 100)as $shopIdList) {
                $partnerKey = config('combinesell.shopee.partner_key');
                $partnerId = (int) config('combinesell.shopee.partner_id');
                $apiPath = '/api/v2/public/get_refresh_token_by_upgrade_code';
                $timestamp = time();
                $baseString = $partnerId.$apiPath.$timestamp;
                $signature = hash_hmac('sha256', $baseString,  $partnerKey);
                $params = [
                    'partner_id' => (int) $partnerId,
                    'timestamp' => $timestamp,
                    'sign' => $signature,
                    
                ];
                $endpointUrl = $this->url . '?' . http_build_query($params);
                $httpBody = json_encode([
                    'upgrade_code' => $this->upgradeCode,
                    'shop_id_list' => $shopIdList
                ]);

                $parameters = [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'body' => $httpBody,
                ];
                $client = new \GuzzleHttp\Client(['verify' => false]);
                $response = $client->post($endpointUrl, $parameters);
                if ($response->getStatusCode() === 200) {
                        $json = json_decode($response->getBody()->getContents(), true);
                        \Log::info('Shopee Refresh Token By Upgrade Code Response|'.json_encode($json));
                        echo PHP_EOL.'Shopee Refresh Token By Upgrade Code Response|'.json_encode($json).PHP_EOL;
                        if(!empty($json['response']) && !($json['error'])) {
                            $successShopList = $json['response']['success_shop_id_list'] ?? [];
                            $refreshToken =  $json['response']['refresh_token'] ?? '';
                            if (!empty($successShopList) && $refreshToken) {
                                \Log::info('Shopee Refresh Token By Upgrade Code Success Shop List|'.json_encode($successShopList).'|Refresh Token|'.json_encode($refreshToken));
                                echo PHP_EOL.'Shopee Refresh Token By Upgrade Code Success Shop List|'.json_encode($successShopList).'|Refresh Token|'.json_encode($refreshToken).PHP_EOL;
                                //Update Refresh Token For Shopee 
                                foreach ($successShopList as $mpShopId) {
                                    $accountInfo = $shopeeMerchantShops[$mpShopId] ?? '';
                                    if ($accountInfo) {
                                        $credentials = array_merge($accountInfo['credentials'], ['refresh_token' => $refreshToken ]);
                                        if (isset($credentials['access_token'], $credentials['refresh_token'])) {
                                            Account::where('id',$accountInfo['id'])->update(['credentials'=> $credentials]);
                                            echo PHP_EOL.'Shopee Refresh Token Updated Successfully [By Upgrade Code] For CS V2 Account Id|'.$accountInfo['id'].'|Credentials|'.json_encode($credentials).PHP_EOL
                                            \Log::info('Shopee Refresh Token Updated Successfully [By Upgrade Code] For CS V2 Account Id|'.$accountInfo['id'].'|Credentials|'.json_encode($credentials));
                                        }
                                    }
                                }
                                $failedShopList = $json['response']['failure_list'] ?? [];
                                if (!empty($failedShopList)) {
                                    \Log::info('Shopee Refresh Token By Upgrade Code Failed Shop List|'.json_encode($failedShopList));
                                }
                            } else {
                                    set_log_extra('response', $response->getBody()->getContents());
                                    echo PHP_EOL.'Unable to get access token for Shopee by upgrade code.Response:'.json_encode($response->getBody()->getContents()).PHP_EOL;
                                    \Log::info('Unable to get access token for Shopee by upgrade code.Response:'.json_encode($response->getBody()->getContents()));
                            }
                        } else {
                            set_log_extra('response', $response->getBody()->getContents());
                            echo PHP_EOL.'Unable to get access token for Shopee by upgrade code.Response:'.json_encode($response->getBody()->getContents()).PHP_EOL;
                            \Log::info('Unable to get access token for Shopee by upgrade code.Response:'.json_encode($response->getBody()->getContents()));
                        }
                }
            }
        }
    }
}
