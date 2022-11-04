<?php

namespace App\Observers;

use App\Constants\JobStatus;
use App\Models\ProductListing;
use App\Jobs\CleanS3ImagesJob;

class ProductListingObserver
{
    /**
     * Handle the product listing "created" event.
     *
     * @param  \App\ProductListing  $productListing
     * @return void
     */
    public function created(ProductListing $productListing)
    {
        //
    }

    /**
     * Handle the product listing "updated" event.
     *
     * @param  \App\ProductListing  $productListing
     * @return void
     */
    public function updated(ProductListing $productListing)
    {
        //
    }

    /**
     * Handle the product listing "deleted" event.
     *
     * @param  \App\ProductListing  $productListing
     * @return void
     */
    public function deleted(ProductListing $productListing)
    {
        $productListing->attributes()->delete();
        $productListing->prices()->delete();
        $productListing->images()->get()->each(function($image) {
            //Push to Queue for cleanup from S3 bucket
            \Log::info('Product Listing Observer Deleted Event Triggered|Image|'.json_encode($image).'|S3 Image Url|'.$image->image_url);
            CleanS3ImagesJob::dispatch($image->image_url)->onQueue('s3_cleanup_queue');
            $image->delete();
        });
        $productListing->data()->delete();

        if ($productListing->product && $productListing->product->productExportTasks) {
            // Delete product export tasks as well, else user cannot export the product again.
            $productListing->product->productExportTasks()->whereAccountId($productListing->account_id)
                ->whereStatus(JobStatus::FINISHED()->getValue())->delete();
        }
    }

    /**
     * Handle the product listing "restored" event.
     *
     * @param  \App\ProductListing  $productListing
     * @return void
     */
    public function restored(ProductListing $productListing)
    {
        //
    }

    /**
     * Handle the product listing "force deleted" event.
     *
     * @param  \App\ProductListing  $productListing
     * @return void
     */
    public function forceDeleted(ProductListing $productListing)
    {
        //
    }
}
