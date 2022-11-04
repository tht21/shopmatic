<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductInventory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductInventoryPolicy
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
     * Determine whether the user can view all their inventory
     *
     * @param User $user
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the productInventory.
     *
     * @param User $user
     * @param ProductInventory $inventory
     * @return mixed
     */
    public function view(User $user, ProductInventory $inventory)
    {
        return $inventory->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create inventory.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the productInventory.
     *
     * @param User $user
     * @param ProductInventory $inventory
     * @return mixed
     */
    public function update(User $user, ProductInventory $inventory)
    {
        return $inventory->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the productInventory.
     *
     * @param User $user
     * @param ProductInventory $inventory
     * @return mixed
     */
    public function delete(User $user, ProductInventory $inventory)
    {
        return $inventory->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can restore the productInventory.
     *
     * @param User $user
     * @param ProductInventory $inventory
     * @return mixed
     */
    public function restore(User $user, ProductInventory $inventory)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the productInventory.
     *
     * @param User $user
     * @param ProductInventory $inventory
     * @return mixed
     */
    public function forceDelete(User $user, ProductInventory $inventory)
    {
        return false;
    }
}
