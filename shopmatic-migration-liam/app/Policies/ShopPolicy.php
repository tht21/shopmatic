<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShopPolicy
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
     * Determine whether the user can view all the shops
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
     * Determine whether the user can view the shop.
     *
     * @param User $user
     * @param Shop $shop
     * @return mixed
     */
    public function view(User $user, Shop $shop)
    {
        return $shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create shops.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the shop.
     *
     * @param User $user
     * @param Shop $shop
     * @return mixed
     */
    public function update(User $user, Shop $shop)
    {
        return $shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the shop.
     *
     * @param User $user
     * @param Shop $shop
     * @return mixed
     */
    public function delete(User $user, Shop $shop)
    {
        return $shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can restore the shop.
     *
     * @param User $user
     * @param Shop $shop
     * @return mixed
     */
    public function restore(User $user, Shop $shop)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the shop.
     *
     * @param User $user
     * @param Shop $shop
     * @return mixed
     */
    public function forceDelete(User $user, Shop $shop)
    {
        return false;
    }
}
