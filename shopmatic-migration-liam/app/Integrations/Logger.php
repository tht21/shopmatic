<?php

namespace App\Integrations;

use App\Models\Account;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;

abstract class Logger 
{
    /**
     * Logger constructor.
     *
     * @param Account $account
     */
    public function __construct(Account $account, $config = [])
    {
        $this->account = $account;
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
