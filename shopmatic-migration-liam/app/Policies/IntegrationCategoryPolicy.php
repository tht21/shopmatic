<?php

namespace App\Policies;

use App\Models\IntegrationCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegrationCategoryPolicy
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
     * Determine whether the user can view the category
     *
     * @param User $user
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the category.
     *
     * @param User $user
     * @param IntegrationCategory $integrationCategory
     * @return mixed
     */
    public function view(User $user, IntegrationCategory $integrationCategory)
    {
        return true;
    }

    /**
     * Determine whether the user can create integration category.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the integration category.
     *
     * @param User $user
     * @param IntegrationCategory $integrationCategory
     * @return mixed
     */
    public function update(User $user, IntegrationCategory $integrationCategory)
    {
        return false;
    }

    /**
     * Determine whether the user can delete the integration category.
     *
     * @param User $user
     * @param IntegrationCategory $integrationCategory
     * @return mixed
     */
    public function delete(User $user, IntegrationCategory $integrationCategory)
    {
        return false;
    }

    /**
     * Determine whether the user can restore the integration category.
     *
     * @param User $user
     * @param IntegrationCategory $integrationCategory
     * @return mixed
     */
    public function restore(User $user, IntegrationCategory $integrationCategory)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the integration category.
     *
     * @param User $user
     * @param IntegrationCategory $integrationCategory
     * @return mixed
     */
    public function forceDelete(User $user, IntegrationCategory $integrationCategory)
    {
        return false;
    }
}
