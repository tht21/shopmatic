<?php


namespace App\Factories;

use App\Models\Account;
use App\Integrations\AbstractProductAdapter;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class ProductAdapterFactory
{

    /**
     * Returns the ProductAdapter for the Account
     *
     * @param Account $account
     *
     * @return AbstractProductAdapter|object
     * @throws \Exception
     */
    public static function create(Account $account)
    {
        $integration = Str::studly($account->integration->name);
        $target = "\App\Integrations\\$integration\ProductAdapter";

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
        throw new \Exception('Unknown integration for ProductAdapter.');
    }
}
