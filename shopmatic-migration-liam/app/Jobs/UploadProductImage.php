<?php

namespace App\Jobs;

use App\Constants\ProductAlertType;
use App\Events\NewProductAlert;
use App\Models\ProductImage;
use App\Utilities\ImageHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\Integration;
use Exception;

class UploadProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private $image;

    private $account;
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;


    /**
     * Create a new job instance.
     *
     * @param ProductImage $image
     */
    public function __construct(ProductImage $image, $account = null)
    {
        $this->image = $image;
        $this->account = $account;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $image = $this->image;

        if (empty($image->source_url)) {
            $image->delete();
            return;
        }

        try {
            $imageSourceUrl = $image->source_url;
            $actualImage = Image::make($imageSourceUrl);

            //Upload image to S3 and get the image url
            $accountPath = $image->source_account_id ? "accounts/" . $image->source_account_id . "/" : "";
            $uploadPath = $accountPath . "products/" . $image->product_id . '/';
            $mimeParts = explode('/', $actualImage->mime());
            $filename = $uploadPath . "$image->id." . $mimeParts[1];
            $imageStream = $actualImage->stream();
            Storage::put($filename, $imageStream->__toString(), ['visibility' => 'public']);

            $imageUrl = Storage::url($filename);

            // if empty imageUrl => save url_image = source_url
            if (empty($imageUrl)) {
                $this->saveUrlImage($image, $imageSourceUrl);
                set_log_extra('product_image_upload', $filename);
                return;
            }
            $this->saveUrlImage($image, $imageUrl);
        } catch (\Exception $e) {
            /**
             * after 2 unsuccessful attempt and trigger an event => save url_image = source_url
             */
            if ($this->attempts() > 1) {
                /**
                 * Refresh the  existing image model with fresh data.
                 * If image_url is present it means the image is successfully uploaded to S3 and update in product_images table.
                 */
                $image->fresh();
                if ($image->image_url) {
                    return;
                }
                event(new NewProductAlert($image->product, 'The product image (' . $image->source_url . ') cannot be read and has been deleted after attempt ' . $this->attempts() . '. ' . $e->getMessage(), ProductAlertType::ERROR()));

                $this->saveUrlImage($image, $imageSourceUrl);
                return;
            }
            /**
             * Enqueue this job to be exectuted in next 2 mins ie. 120 seconds from now.
             */
            $this->release(100);
        }
    }

    private function saveUrlImage($image, $urlImage)
    {
        $image->image_url = $urlImage;
        $image->save();
        $product = $image->product;
        if ($product && empty($product->main_image) && $image->position == 0) {
            $product->main_image = $urlImage;
            $product->save();
        }
    }
}
