<?php

namespace App\Policies;

use App\Models\ProductListing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductListingPolicy
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
     * Determine whether the user can view all their product listing
     *
     * @param User $user
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the product listing
     *
     * @param User $user
     * @param ProductListing $productListing
     * @return mixed
     */
    public function view(User $user, ProductListing $productListing)
    {
        return $productListing->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create products listing
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the product listing
     *
     * @param User $user
     * @param ProductListing $productListing
     * @return mixed
     */
    public function update(User $user, ProductListing $productListing)
    {
        return $productListing->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the product.
     *
     * @param User $user
     * @param ProductListing $productListing
     * @return mixed
     */
    public function delete(User $user, ProductListing $productListing)
    {
        return $productListing->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can restore the product.
     *
     * @param User $user
     * @param ProductListing $productListing
     * @return mixed
     */
    public function restore(User $user, ProductListing $productListing)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the product.
     *
     * @param User $user
     * @param ProductListing $productListing
     * @return mixed
     */
    public function forceDelete(User $user, ProductListing $productListing)
    {
        return false;
    }
}
