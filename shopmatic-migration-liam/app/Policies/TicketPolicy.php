<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
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
     * Determine whether the user can view all the tickets
     * @param User $user
     * @return bool
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function view(User $user, Ticket $ticket)
    {
        return $ticket->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can create tickets.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function update(User $user, Ticket $ticket)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can delete the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function delete(User $user, Ticket $ticket)
    {
        return $ticket->shop->users->contains($user->id);
    }

    /**
     * Determine whether the user can reply the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function reply(User $user, Ticket $ticket)
    {
        return true;
    }

    /**
     * Determine whether the user can display the activity of the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function trail(User $user, Ticket $ticket)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function restore(User $user, Ticket $ticket)
    {
        return $user->isA(User::ROLE_ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the ticket.
     *
     * @param User $user
     * @param Ticket $ticket
     * @return mixed
     */
    public function forceDelete(User $user, Ticket $ticket)
    {
        return false;
    }
}
