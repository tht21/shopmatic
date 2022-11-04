<?php

namespace App\Integrations\Xero;

use App\Constants\AccountStatus;
use App\Integrations\AbstractClient;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Region;
use App\Models\Shop;
use Calcinai\OAuth2\Client\Provider\Xero;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Token\AccessToken;
use XeroPHP\Models\Accounting\Organisation;

class Client extends AbstractClient
{

    public $xero;

    /**
     * Client constructor.
     *
     * @param Account $account
     * @param array $config
     */
    public function __construct(Account $account, $config = [])
    {
        parent::__construct($account, $config);
        $token = new AccessToken($account->credentials['token']);
        if ($token->hasExpired()) {
            $provider = new Xero([
                'clientId'          => config('combinesell.xero.client_id'),
                'clientSecret'      => config('combinesell.xero.client_secret'),
                'redirectUri'       => config('combinesell.xero.redirect'),
            ]);
            try {
                $newAccessToken = $provider->getAccessToken('refresh_token', [
                    'refresh_token' => $token->getRefreshToken()
                ]);

                $account->credentials['api_key'] = $newAccessToken->getToken();
                $account->credentials['token'] = $newAccessToken->jsonSerialize();
                $account->save();
            } catch (\Exception $e) {
                $this->disableAccount(AccountStatus::REQUIRE_AUTH());
            }
        }

        $this->xero = new \XeroPHP\Application($token, $account->credentials['tenant_id']);
    }


    /**
     * @param null $region
     * @return string
     */
    public static function getAuthorizationLink($region = null)
    {
        $provider = new Xero([
            'clientId'          => config('combinesell.xero.client_id'),
            'clientSecret'      => config('combinesell.xero.client_secret'),
            'redirectUri'       => config('combinesell.xero.redirect'),
        ]);
        $authUrl = $provider->getAuthorizationUrl([
            'scope' => 'openid email profile offline_access accounting.transactions accounting.contacts accounting.attachments accounting.settings'
        ]);

        session()->put('XERO_STATE', $provider->getState());

        return $authUrl;
    }

    /**
     * Generates the token and updates / creates the integration
     *
     * @param $input
     *
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function handleRedirect($input)
    {
        if (empty($input['state']) || $input['state'] !== session('XERO_STATE', null)) {
            return null;
        }

        $provider = new Xero([
            'clientId'          => config('combinesell.xero.client_id'),
            'clientSecret'      => config('combinesell.xero.client_secret'),
            'redirectUri'       => config('combinesell.xero.redirect'),
        ]);

        /** @var Shop $shop */
        $shop = session('shop');

        /** @var AccessToken $token */
        try {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $input['code']
            ]);
            $tenants = $provider->getTenants($token);

            if (empty($tenants)) {
                return null;
            }

            $tenantId = null;


            foreach ($tenants as $tenant) {
                $tenantId = $tenant->tenantId;

                break;
            }

            if (empty($tenantId)) {
                return null;
            }

            $xero = new \XeroPHP\Application($token, $tenantId);;

            /** @var Organisation $organisation */
            $organisation = $xero->load(Organisation::class)->first();

            if (!empty($organisation)) {
                $name = $organisation->getName();
                $currency = $organisation->getBaseCurrency();
            } else {
                $name = 'Xero';
                $currency = 'USD';
            }

            $queryData = [
                'integration_id' => Integration::XERO,
                'region_id'      => Region::GLOBAL,
                'shop_id'        => $shop->id,
            ];

            $credentials = [
                'api_key' => $token->getToken(),
                'token' => $token->jsonSerialize(),
            ];

            $account = $shop->accounts()->where($queryData)->where('credentials->tenant_id', $tenantId)->first();

            if (empty($account)) {

                $account = Account::create($queryData + [
                    'credentials' => $credentials,
                    'status'      => AccountStatus::ACTIVE(),
                    'name'        => $name,
                    'currency'    => $currency,
                ]);

            } else {
                $account->name = $name;
                $account->currency = $currency;
                $account->credentials = $credentials;
                $account->status = AccountStatus::ACTIVE();
                $account->save();
            }
            $account = $account->fresh();

            return $account;
        } catch (\Exception $e) {
            Log::error($e);
        }
        return null;
    }

    /**
     * Performs a bulk update of the inventory
     * 
     * @throws \Exception
     */
    public function inventorySync()
    {
        if (!$this->account->hasFeature(['inventory', 'periodic_sync'])) {
            return;
        }
        /*
         * The logic for the implementation should be as follows - Feel free to change this if there's a more efficient way
         * 
         * 1. Retrieve all Inventory that has a listing in this account
         * 2. If stock of listing != main, add it to the list of products to be updated
         * 3. Bulk update the products and update locally if succeeded.
         */
        set_log_extra('integration', $this->account->integration);
        throw new \Exception('Integration does not support bulk inventory sync.');
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
            try {
                return !empty($this->xero->load(Organisation::class)->first());
            } catch (\Exception $e) {
                return false;
            }
        } else {
            $token = new AccessToken($this->account->credentials['token']);
            return !$token->hasExpired();
        }
    }

}
