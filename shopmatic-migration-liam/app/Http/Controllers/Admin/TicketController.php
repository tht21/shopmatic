<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ticket;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

        $priority_array = Ticket::PRIORITY_ARRAY;
        $status_array = Ticket::STATUS_ARRAY;

        $count_ticket = Ticket::where('user_id', Auth::user()->id)->where('status', 0)->count();

        $group_ticket = Ticket::select('ticket_categories_id', \DB::raw('count(*) as total'))
            ->with('category')
            ->groupBy('ticket_categories_id')
            ->where('user_id', Auth::user()->id)
            ->get();

        return view('admin.tickets.index', compact('priority_array', 'status_array', 'count_ticket', 'group_ticket'));
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

        return view('admin.tickets.create');
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

        $status_array = Ticket::STATUS_ARRAY;

        return view('admin.tickets.show', compact('ticket', 'status_array'));
    }
}
