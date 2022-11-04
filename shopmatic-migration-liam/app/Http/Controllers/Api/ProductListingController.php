<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductListing;
use Illuminate\Http\Request;

class ProductListingController extends Controller
{
    /**
     * Returns the product listings.
     *
     * @param Product $product
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function index(Product $product)
    {
        $this->authorize('view', $product);
        // @TODO - need to refactor, should be dynamic and in show function
        $listings = $product->listings->mapWithKeys(function ($listing) {
            return [$listing['id'] => $listing];
        })->load([
            'product',
            'attributes', 'images', 'integration',
            'listing_variants', 'listing_variants.prices', 'listing_variants.variant.prices'
        ]);

        // Get logistics
        foreach ($listings as $key => $listing) {
            if ($listing->account) {
                $adapter = $listing->account->getProductAdapter();
                $attributes = $listing->attributes;

                $listings[$listing->id]['logistic'] = $adapter->retrieveLogistics($attributes);
            } else {
                // Remove the listing if account have been deleted
                $listings->forget($key);
            }
        }

        return $this->respond($listings);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Product $product
     * @param ProductListing $productListing
     * @return void
     */
    public function show(Product $product, ProductListing $productListing)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
