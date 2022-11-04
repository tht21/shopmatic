<?php

namespace App\Observers;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class ProductImageObserver
{
    /**
     * Handle the product image "created" event.
     *
     * @param ProductImage $productImage
     * @return void
     */
    public function created(ProductImage $productImage)
    {
        //
    }

    /**
     * Handle the product image "updated" event.
     *
     * @param ProductImage $productImage
     * @return void
     */
    public function updated(ProductImage $productImage)
    {
        //
    }

    /**
     * Handle the product image "deleted" event.
     *
     * @param ProductImage $productImage
     * @return void
     */
    public function deleted(ProductImage $productImage)
    {
        //
        Storage::disk('s3')->delete($productImage->image_url);

        // main image deleted, switch to other
        if (!empty($productImage->product) && $productImage->product->main_image == $productImage->image_url) {
            $product = $productImage->product;
            $image = $product->allImages()->first();
            if ($image) {
                $product->main_image = $image->image_url;    
            } else {
                $product->main_image = null;
            }
            
            $product->save();
        }
    }

    /**
     * Handle the product image "restored" event.
     *
     * @param ProductImage $productImage
     * @return void
     */
    public function restored(ProductImage $productImage)
    {
        //
    }

    /**
     * Handle the product image "force deleted" event.
     *
     * @param ProductImage $productImage
     * @return void
     */
    public function forceDeleted(ProductImage $productImage)
    {
        //
    }
}
