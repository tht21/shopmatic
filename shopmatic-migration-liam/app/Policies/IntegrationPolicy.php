<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Integration;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegrationPolicy
{
    use HandlesAuthorization;

    /**
     * Calls this before any of the other functions
     *
     * @param User $user
     * @param $ability
     *
     * @return bool
     */
    public function before(User $user, $ability)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view all integrations.
     *
     * @param User $user
     *
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the integration.
     *
     * @param User $user
     * @param Integration $integration
     * @return mixed
     */
    public function view(User $user, Integration $integration)
    {
        return true;
    }

    /**
     * Determine whether the user can create integrations.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the integration.
     *
     * @param User $user
     * @param Integration $integration
     * @return mixed
     */
    public function update(User $user, Integration $integration)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can delete the integration.
     *
     * @param User $user
     * @param Integration $integration
     * @return mixed
     */
    public function delete(User $user, Integration $integration)
    {
        return false;
    }

    /**
     * Determine whether the user can restore the integration.
     *
     * @param User $user
     * @param Integration $integration
     * @return mixed
     */
    public function restore(User $user, Integration $integration)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the integration.
     *
     * @param User $user
     * @param Integration $integration
     * @return mixed
     */
    public function forceDelete(User $user, Integration $integration)
    {
        return false;
    }
}
