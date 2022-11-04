<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

class ProductImportController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('dashboard.products.import');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function tasks()
    {
        $headers = json_encode([
            'ID', 'Source', 'Source Type', 'Message', 'Total Products', 'Status', 'Created At'
        ]);
        $fields = json_encode([
            'id', 'source', 'source_type', 'messages', 'total_products', 'status', 'created_at'
        ]);
        return view('dashboard.products.import.logs', compact('headers', 'fields'));
    }

}
