<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ArticleCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $headers = json_encode([
            'Name', 'Status', 'Created At', 'Actions'
        ]);
        $fields = json_encode([
            'name', 'status', 'created_at'
        ]);

        return view('admin.articles.category.index', compact('headers', 'fields'));
    }
}
