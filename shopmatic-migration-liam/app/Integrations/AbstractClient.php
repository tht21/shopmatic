<?php

namespace App\Integrations;

use App\Models\Account;
use App\Constants\AccountStatus;
use App\Events\AccountStatusUpdated;
use App\Models\Integration;
use App\Notifications\AccountDisabled;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;

abstract class AbstractClient extends Client implements ClientInterface
{

    /*
     * This is the base URL to be used for all the requests
     *
     * @var string
     */
    protected $url;

    protected $account;

    /*
     * This is the lists of methods that needs to be called after the account creation process.
     * This includes things such as outlets, account categories or anything else
     *
     * @var array
     */
    public $postAccountCreation = [
        /*
        'functionName' => 'Function explanation to users'
        */
    ];

    /**
     * AbstractClient constructor.
     *
     * @param Account $account
     * @param array $config
     */
    public function __construct(Account $account, $config = [])
    {
        $shop = $account->shop;

        // This is used for user tracking in sentry
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($shop) {
            if (!empty($shop)) {
                $scope->setUser([
                    'id' => $shop->id,
                    'username' => $shop->name,
                    'email' => $shop->email,
                ]);
            }
        });
        $this->account = $account;

        parent::__construct(array_merge([
            'base_uri'  => $this->url,
            'verify' => config('app.env') === 'production',
            // This is to prevent guzzle from throwing an exception from HTTP error codes
            'http_errors' => false
        ], $config));

    }

    /**
     * Checks to ensure that the credentials are still valid and not expired
     *
     * @param bool $actualCall This is whether or not an actual API call should be made to test
     *
     * @return bool
     *
     */
    public abstract function isCredentialsValid($actualCall = false);

    /**
     * Disables the account either from invalid credentials or manual turning off or other scenarios
     *
     * @param AccountStatus $status The status it should changed to
     *
     * @return void
     */
    public function disableAccount(AccountStatus $status)
    {
        
        // Fix duplicate email sending
        $this->account = $this->account->fresh();
        $oldStatus = $this->account->status;
        if ($oldStatus instanceof AccountStatus) {
            $oldStatus = $oldStatus->getValue();
        }
        if ($status->getValue() == $oldStatus) {
            return;
        }
        event(new AccountStatusUpdated($this->account, $this->account->status));
        
        
        try {
            $shopEmail = $this->account->shop;
            $this->account->shop->notify(new AccountDisabled($this->account));
            foreach ($this->account->shop->users as $user) {
                if (strtolower($user->email) != strtolower($shopEmail)) {
                    $user->notify(new AccountDisabled($this->account));
                }
            }
        } catch (\Exception $e) {
            set_log_extra('account', $this->account);
            Log::error($e);
        }
        
        $this->account->status = $status;
        $this->account->save();
    }

    /**
     * This is only required if the integration supports OAuth
     *
     *
     * @param null $region
     *
     * @return string The url to redirect to
     * @throws \Exception
     */
    public static function getAuthorizationLink($region = null) {
        throw new \Exception('App authorization link not set.');
    }

    /**
     * This is only required if the integration supports OAuth
     *
     * @param $input
     *
     * @return Account|null|mixed The account if it's successfully created
     * @throws \Exception
     */
    public static function handleRedirect($input) {
        throw new \Exception('App redirect handling not set.');
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
        throw new \Exception('Manual auth handling not set.');
    }
    
    public function logInfo($message)
    {
        $message = '[Account ID: ' . $this->account->id . '] ' . $message;
        Log::channel(snake_case(Integration::INTEGRATIONS[$this->account->integration_id]))->info($message);
    }
    
    public function logDebug($message)
    {
        $message = '[Account ID: ' . $this->account->id . '] ' . $message;
        Log::channel(snake_case(Integration::INTEGRATIONS[$this->account->integration_id]))->debug($message);
    }
    
    public function logNotice($message)
    {
        $message = '[Account ID: ' . $this->account->id . '] ' . $message;
        Log::channel(snake_case(Integration::INTEGRATIONS[$this->account->integration_id]))->notice($message);
    }
    
    public function logWarning($message)
    {
        $message = '[Account ID: ' . $this->account->id . '] ' . $message;
        Log::channel(snake_case(Integration::INTEGRATIONS[$this->account->integration_id]))->warning($message);
    }
    
    public function logError($message)
    {
        $message = '[Account ID: ' . $this->account->id . '] ' . $message;
        Log::channel(snake_case(Integration::INTEGRATIONS[$this->account->integration_id]))->error($message);
    }

}
