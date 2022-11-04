<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Account;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
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
     * Determine whether the user can view all accounts.
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
     * Determine whether the user can view the account.
     *
     * @param User $user
     * @param Account $account
     * @return mixed
     */
    public function view(User $user, Account $account)
    {
        return $account->shop->users->contains($user->id);

    }

    /**
     * Determine whether the user can create accounts.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->shops()->count();
    }

    /**
     * Determine whether the user can update the account.
     *
     * @param User $user
     * @param Account $account
     * @return mixed
     */
    public function update(User $user, Account $account)
    {
        return $account->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the account.
     *
     * @param User $user
     * @param Account $account
     * @return mixed
     */
    public function delete(User $user, Account $account)
    {
        return $account->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can restore the account.
     *
     * @param User $user
     * @param Account $account
     * @return mixed
     */
    public function restore(User $user, Account $account)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the account.
     *
     * @param User $user
     * @param Account $account
     * @return mixed
     */
    public function forceDelete(User $user, Account $account)
    {
        return false;
    }
}
