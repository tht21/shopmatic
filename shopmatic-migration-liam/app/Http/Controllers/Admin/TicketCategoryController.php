<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Contracts\Support\Renderable;

class TicketCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', TicketCategory::class);

        $headers = json_encode([
            'Name', 'Parent Category', 'Status', 'Created At', 'Actions'
        ]);
        $fields = json_encode([
            'name', 'parent_id', 'status', 'created_at'
        ]);

        return view('admin.tickets.category.index', compact('headers', 'fields'));
    }
}
