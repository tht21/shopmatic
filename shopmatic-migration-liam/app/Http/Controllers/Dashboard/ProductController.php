<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;

class ProductController extends Controller
{

    /**
     * Show the product index
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', Product::class);
        return view('dashboard.products.index');
    }

    /**
     * Show the product page
     *
     * @param Product $product
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load(['variants', 'variants.listings', 'listings.account.integration', 'images', 'unreadAlerts']);

        return view('dashboard.products.show', compact('product'));
    }

    /**
     * Create product page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('dashboard.products.create');
    }

    /**
     * Edit the product page
     *
     * @param Product $product
     *
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->load([
            'prices' => function ($query) {
                /** @var ProductPrice $query */
                $query->where(function ($subQuery) {
                    /** @var ProductPrice $subQuery */
                    $subQuery->whereNull('integration_id');
                })->orderBy('type')->orderBy('integration_id');
            }, 'attributes', 'images', 'variants',
            'variants.attributes' => function ($query) {
                /** @var ProductAttribute $query */
                $query->where(function ($subQuery) {
                    /** @var ProductAttribute $subQuery */
                    $subQuery->whereNull('product_listing_id');
                });
            }, 
            'variants.prices' => function ($query) {
                /** @var ProductPrice $query */
                $query->where(function ($subQuery) {
                    /** @var ProductPrice $subQuery */
                    $subQuery->whereNull('integration_id');
                })->orderBy('type')->orderBy('integration_id');
            }, 'variants.images', 'variants.inventory',

            'listings.prices', 'listings.images', 'listings.attributes', 'listings.listing_variants', 'listings.listing_variants.prices', 'listings.listing_variants.images', 'listings.listing_variants.attributes', 'listings.account.integration', 'listings.account.locations', 'listings.account.region'
        ]);
        foreach ($product->listings as $listing) {
            if ($listing->account) {
                $listing->account->append('has_category');
            }
        }

        return view('dashboard.products.edit', compact('product'));
    }

    /**
     * Show the bulk application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function bulk()
    {
        return view('dashboard.products.bulk');
    }

    /**
     * Show the bulk categories application dashboard.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function bulkCategories()
    {
        return view('dashboard.products.categories.bulk');
    }
}
