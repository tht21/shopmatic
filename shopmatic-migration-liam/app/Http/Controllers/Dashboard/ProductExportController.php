<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductExportController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('dashboard.products.export');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function tasks(Request $request)
    {
        // Export excel tasks
        if ($request->get('type') === 'excel') {
            $headers = json_encode([
                'ID', 'Source Type', 'Source ID', 'Download', 'Message', 'Status', 'Created At'
            ]);
            $fields = json_encode([
                'id', 'source_type', 'source', 'download', 'message', 'status', 'created_at'
            ]);
        } else {
            // Product export tasks
            $headers = json_encode([
                'ID', 'Account ID', 'Message', 'Product ID', 'Status', 'Created At'
            ]);
            $fields = json_encode([
                'id', 'account_id', 'messages', 'product_id', 'status', 'created_at'
            ]);
        }

        return view('dashboard.products.export.logs', compact('headers', 'fields'));
    }
}
