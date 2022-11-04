<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     * @throws AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', Ticket::class);
        return view('dashboard.tickets.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     * @throws AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Ticket::class);

        return view('dashboard.tickets.create');
    }

    /**
     * Display the specified resource.
     *
     * @param Ticket $ticket
     * @return Renderable
     * @throws AuthorizationException
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return view('dashboard.tickets.show', compact('ticket'));
    }
}
