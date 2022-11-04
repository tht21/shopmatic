<?php


namespace App\Factories;

use App\Models\Account;
use App\Integrations\AbstractProductAdapter;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class WebhookAdapterFactory
{

    /**
     * Returns the WebhookAdapter for the integration name
     *
     * @param $name
     * @return AbstractProductAdapter|object
     * @throws \Exception
     */
    public static function create($name)
    {
        $integration = Str::studly($name);
        $target = "\App\Integrations\\$integration\WebhookAdapter";

        try {
            $reflector = new ReflectionClass($target);

            if ($reflector->isInstantiable()) {
                return $reflector->newInstance();
            }

        } catch (ReflectionException $exception) {
            //Just catching it here so we can add additional logs below and not have to repeat the code
        }

        set_log_extra('integration', $integration);
        set_log_extra('name', $name);
        throw new \Exception('Unknown integration for WebhookAdapter.');
    }


    /**
     * Returns the WebhookAdapter for the Account
     *
     * @param Account $account
     *
     * @return AbstractProductAdapter|object
     * @throws \Exception
     */
    public static function createFromAccount(Account $account)
    {
        $integration = Str::studly($account->integration->name);
        $target = "\App\Integrations\\$integration\WebhookAdapter";

        try {
            $reflector = new ReflectionClass($target);

            if ($reflector->isInstantiable()) {
                return $reflector->newInstanceArgs([$account]);
            }

        } catch (ReflectionException $exception) {
            //Just catching it here so we can add additional logs below and not have to repeat the code
        }

        set_log_extra('account', $account->toArray());
        set_log_extra('integration', $integration);
        throw new \Exception('Unknown integration for WebhookAdapter.');
    }
}
