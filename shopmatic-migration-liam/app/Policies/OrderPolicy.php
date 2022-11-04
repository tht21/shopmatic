<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
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
     * Determine whether the user can view all their orders
     *
     * @param User $user
     * @param Order $order
     * @return mixed
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the order.
     *
     * @param User $user
     * @param Order $order
     * @return mixed
     */
    public function view(User $user, Order $order)
    {
        return $order->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create orders.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the order.
     *
     * @param User $user
     * @param Order $order
     * @return mixed
     */
    public function update(User $user, Order $order)
    {
        return $order->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @param User $user
     * @param Order $order
     * @return mixed
     */
    public function delete(User $user, Order $order)
    {
        return $order->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can restore the order.
     *
     * @param User $user
     * @param Order $order
     * @return mixed
     */
    public function restore(User $user, Order $order)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the order.
     *
     * @param User $user
     * @param Order $order
     * @return mixed
     */
    public function forceDelete(User $user, Order $order)
    {
        return false;
    }
}
