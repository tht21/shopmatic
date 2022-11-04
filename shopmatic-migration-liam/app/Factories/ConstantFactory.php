<?php


namespace App\Factories;

use App\Integrations\AbstractConstant;
use App\Models\Account;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class ConstantFactory
{

    /**
     * Returns the Constant for the Account
     *
     * @param Account $account
     *
     * @return AbstractConstant|object
     * @throws \Exception
     */
    public static function create(Account $account)
    {
        $integration = Str::studly($account->integration->name);
        $target = "\App\Integrations\\$integration\Constant";

        try {
            $reflector = new ReflectionClass($target);

            $class = $reflector->newInstanceWithoutConstructor();

            if (!is_subclass_of($class, AbstractConstant::class)) {
                set_log_extra('target', $target);
                throw new \Exception('Constant not implementing AbstractConstant.');
            }

            return $class;

        } catch (ReflectionException $exception) {

        }

        // If doesn't exist, just use the empty default
        $reflector = new ReflectionClass("\App\Integrations\AbstractConstant");

        return $reflector->newInstanceWithoutConstructor();
    }

}
