<?php


namespace App\Factories;

use App\Models\Account;
use App\Integrations\AbstractClient;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class ClientFactory
{

    /**
     * Returns the Client for the Account
     *
     * @param Account $account
     *
     * @return AbstractClient|object
     * @throws \Exception
     */
    public static function create(Account $account)
    {
        $integration = Str::studly($account->integration->name);
        $target = "\App\Integrations\\$integration\Client";

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
        throw new \Exception('Unknown integration for Client.');
    }

    /**
     * Returns the Client for the name of the integration
     *
     * @param string $name
     *
     * @return AbstractClient|object
     * @throws \Exception
     */
    public static function createStatic($name)
    {
        $integration = Str::studly($name);
        $target = "\App\Integrations\\$integration\Client";

        try {
            $reflector = new ReflectionClass($target);

            return $reflector->newInstanceWithoutConstructor();

        } catch (ReflectionException $exception) {
            //Just catching it here so we can add additional logs below and not have to repeat the code
        }

        set_log_extra('name', $name);
        throw new \Exception('Unknown integration for Client.');
    }
}
