<?php


namespace App\Integrations;

use App\Jobs\UploadProductImage;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use App\Models\Integration;

class TransformedProductImage
{

    public $url;
    public $externalId;
    public $width;
    public $height;
    public $position;

    /**
     * TransformedProduct constructor.
     *
     *
     * @param string $url
     * @param string|null $externalId
     * @param int|null $width
     * @param int|null $height
     * @param int $position
     */
    public function __construct($url, $externalId = null, $width = null, $height = null, $position = 0)
    {
        $this->url = $url;
        $this->externalId = $externalId;
        $this->width = $width;
        $this->height = $height;
        $this->position = $position;
    }

    /**
     * Creates the product if it doesn't exist, or update the product if necessary
     *
     * @param Product $product
     * @param Account $account
     * @param ProductVariant|null $variant
     * @param ProductListing|null $listing
     *
     * @return ProductImage|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object
     * @throws \Exception
     */
    public function createImage(Product $product, Account $account, ?ProductVariant $variant, ?ProductListing $listing)
    {
        if (empty($this->url)) {
            return null;
        }

        // Create from lowest level since it might be null
        if (!empty($listing)) {
            $oldImages = $listing->images();
        } elseif (!empty($variant)) {
            $oldImages = $variant->images();
        } else {
            $oldImages = $product->images();
        }

        // If we can check based on external id, we use that, otherwise we check by source url
        // Not sure if we should check the other levels if it's external ID, if there's a problem, it should be handled
        // prior to this part. Since we're creating it at the level it needs to be at

        // if (!empty($this->externalId)) {
        //     \Log::info('rnal_id at the mom');
        //     // no external_id at the moment
        //     // if ($image = $oldImages->where('external_id', $this->externalId)->first()) {
        //     //     return $image;
        //     // }
        // } else {

            /** @var ProductImage $image */
            if ($image = $oldImages->where('source_url', $this->url)->first()) {
                // if old Listing matched to new Product, fill in main_image old listing image
                // if (empty($product->main_image) || is_null($product->main_image)) {
                if ($image->position == 0 && is_null($variant)) {
                    $product->main_image = !empty($image->image_url) ? $image->image_url : $image->source_url;
                    $product->save();
                }
                // }
                // if variant main image doesnt is null, then assign to it
                if (!is_null($variant)) {
                    $variant->main_image = !empty($image->image_url) ? $image->image_url : $image->source_url;
                    $variant->save();
                }
                return $image;
            }
        // }

        $integrationCategory = $listing ? $listing->integration_category : NULL;
        $accountId = $account ? $account->id : ($listing ? $listing->account_id : NULL);
        $regionId = $account ? $account->region_id : ($integrationCategory ? $integrationCategory->region_id : NULL);

        //If there's no image, we create it here and queue it to be uploaded
        $image = ProductImage::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant ? $variant->id : null,
            'product_listing_id' => $listing ? $listing->id : null,
            'region_id' => $regionId,
            'source_account_id' =>  $accountId,
            'source_url' => $this->url,
            'position' => $this->position,
            'width' => $this->width,
            'height' => $this->height,
        ]);
        $image = $image->fresh();
        try {
            /**
             * Adding delay of 1 min only for Qoo10 ,so that image is fetchable from Qoo10 and post that image can be upload to S3.
             * Refer to jira CSM-542
             */
            UploadProductImage::dispatch($image);

        } catch (\Exception $exception) {
            set_log_extra('url', $this->url);
            set_log_extra('externalId', $this->externalId);
            set_log_extra('position', $this->position);
            set_log_extra('product', $product);
            set_log_extra('variant', $variant);
            set_log_extra('listing', $listing);
            throw $exception;
        }
        if (!is_null($variant)) {
            $variant->main_image = $this->url;
            $variant->save();
        }
        return $image;
    }

}
