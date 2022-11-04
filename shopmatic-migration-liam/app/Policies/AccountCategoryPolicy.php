<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountCategoryPolicy
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
     * @param AccountCategory $accountCategory
     * @return mixed
     */
    public function view(User $user, AccountCategory $accountCategory)
    {
        return $accountCategory->account->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create category.
     *
     * @param User $user
     * @param Account $account
     * @return mixed
     */
    public function create(User $user, Account $account)
    {
        return $account->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can update the category.
     *
     * @param User $user
     * @param AccountCategory $accountCategory
     * @return mixed
     */
    public function update(User $user, AccountCategory $accountCategory)
    {
        return $accountCategory->account->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the category.
     *
     * @param User $user
     * @param AccountCategory $accountCategory
     * @return mixed
     */
    public function delete(User $user, AccountCategory $accountCategory)
    {
        return $accountCategory->account->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can restore the category.
     *
     * @param User $user
     * @param AccountCategory $accountCategory
     * @return mixed
     */
    public function restore(User $user, AccountCategory $accountCategory)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the category.
     *
     * @param User $user
     * @param AccountCategory $accountCategory
     * @return mixed
     */
    public function forceDelete(User $user, AccountCategory $accountCategory)
    {
        return false;
    }
}
