<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TicketCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Calls this before any of the other functions
     *
     * @param User $user
     * @param $ability
     * @return bool
     */
    public function before(User $user, $ability)
    {
        if ($user->isA(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view all the ticket categories
     * @param User $user
     * @return bool
     */
    public function index(User $user)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can view the ticket category.
     *
     * @param User $user
     * @param TicketCategory $ticketCategory
     * @return mixed
     */
    public function view(User $user, TicketCategory $ticketCategory)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can create ticket categories.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can update the ticket category.
     *
     * @param User $user
     * @param TicketCategory $ticketCategory
     * @return mixed
     */
    public function update(User $user, TicketCategory $ticketCategory)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can delete the ticket category.
     *
     * @param User $user
     * @param TicketCategory $ticketCategory
     * @return mixed
     */
    public function delete(User $user, TicketCategory $ticketCategory)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can restore the ticket category.
     *
     * @param User $user
     * @param TicketCategory $ticketCategory
     * @return mixed
     */
    public function restore(User $user, TicketCategory $ticketCategory)
    {
        return $user->isA(User::ROLE_ADMIN);;
    }

    /**
     * Determine whether the user can permanently delete the ticket category.
     *
     * @param User $user
     * @param TicketCategory $ticketCategory
     * @return mixed
     */
    public function forceDelete(User $user, TicketCategory $ticketCategory)
    {
        return false;
    }
}
