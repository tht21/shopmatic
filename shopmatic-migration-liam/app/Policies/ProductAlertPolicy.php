<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductAlert;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductAlertPolicy
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
     * Determine whether the user can view all their products
     *
     * @param User $user
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the product alert.
     *
     * @param User $user
     * @param ProductAlert $productAlert
     * @return mixed
     */
    public function view(User $user, ProductAlert $productAlert)
    {
        return $productAlert->product->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create product alerts.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the product alert.
     *
     * @param User $user
     * @param ProductAlert $productAlert
     * @return mixed
     */
    public function update(User $user, ProductAlert $productAlert)
    {
        return $productAlert->product->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the product alert.
     *
     * @param User $user
     * @param ProductAlert $productAlert
     * @return mixed
     */
    public function delete(User $user, ProductAlert $productAlert)
    {
        return false;
    }

    /**
     * Determine whether the user can restore the product alert.
     *
     * @param User $user
     * @param ProductAlert $productAlert
     * @return mixed
     */
    public function restore(User $user, ProductAlert $productAlert)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the product alert.
     *
     * @param User $user
     * @param ProductAlert $productAlert
     * @return mixed
     */
    public function forceDelete(User $user, ProductAlert $productAlert)
    {
        return false;
    }
}
