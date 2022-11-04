<?php

namespace App\Jobs;

use App\Models\ProductImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class CleanS3ImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $image;

    /**
     * Create a new job instance.
     *
     * @param String $image
     */
    public function __construct($image)
    {
        $this->image = $image;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $s3BucketUrl = config('filesystems.disks.s3.url').config('filesystems.disks.s3.root');
        \Log::info('CleanS3ImagesJob|Bucket Url::'.$s3BucketUrl);
        $s3ImageRelativeUrl = '';
        if ($this->image) {
            $s3ImageRelativeUrl = str_replace($s3BucketUrl,'',$this->image);
            \Log::info("CleanS3ImagesJob|Verify Image Url::[". $this->image ."] existence in bucket::$s3BucketUrl \n");
            if(Storage::disk('s3')->exists($s3ImageRelativeUrl)){
                \Log::info("CleanS3ImagesJob Exist|Image Url::[". $this->image ."] exist in bucket::$s3BucketUrl \n");
                if (Storage::disk('s3')->delete($s3ImageRelativeUrl)) {
                   \Log::info("CleanS3ImagesJob Deleted|Image Url::[". $this->image . "] deleted from bucket::$s3BucketUrl \n");
                }
            }
        }
    }
}
