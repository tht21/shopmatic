<?php

namespace App\Jobs;

use App\Constants\AccountStatus;
use App\Constants\JobStatus;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductExportTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Integration;
use App\Jobs\ExportedProductStatusSyncJob;

class ProductExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 7200;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    protected $task;

    /**
     * Create a new job instance.
     *
     * @param ProductExportTask $task
     */
    public function __construct(ProductExportTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return array
     * @throws \Exception
     */
    public function handle()
    {
        $this->task->status = JobStatus::PROCESSING()->getValue();
        $this->task->save();

        return $this->create();
    }

    /**
     * Fetches the products from the account
     *
     * @throws \Exception
     */
    private function create()
    {
        try {
            /** @var Account $account */
            $account = Account::find($this->task->account_id);

            // Validation prior to actual processing

            if (empty($account)) {
                throw new \Exception('Account not found or valid.');
            }
            if (!$account->hasFeature(['products', 'create_product'])) {
                throw new \Exception('Integration does not support create product.');
            }

            if (!$account->status->equals(AccountStatus::ACTIVE())) {
                throw new \Exception('Account is not active.');
            }

            // Actual processing
            $adapter = $account->getProductAdapter();

            $product = Product::find($this->task->product_id);

            if (!$product) {
                throw new \Exception('Product not found.');
            }

            $product->load([
                'prices',
                'variants',
                'variants.prices',
                'variants.attributes' => function ($query) use ($account) {
                    $query->where('integration_id', $account->integration_id)->whereNull('product_listing_id');
                },
                'attributes' => function ($query) use ($account) {
                    $query->where('integration_id', $account->integration_id)->whereNull('product_listing_id');
                }
            ]);

            // integration validate
            $response = $adapter->canCreate($product);
            if (!$response['meta']['error']) {
                $response = $adapter->create($product);
                \Log::info("Amazon Product Created::".json_encode($response));
            }

            if (isset($response['meta']) && isset($response['meta']['error']) && $response['meta']['error']) {
                $this->task->status = JobStatus::FAILED();
                $this->task->messages = $this->setTaskMessages($this->task->messages, $response['meta']['message']);
                $this->task->save();

                return $response['meta']['message'];
            } else {
                /**
                     * Since in Amazon,product exported listing status is not known immediately,
                     * we  are scheduling a task to check the listing status,which will execute as
                     * a background job after 300 secs of product export to Amazon Marketplace.
                     * For Amazon exported product , the task will be in processing state unless we get
                     * status from Amazon about the product listing.
                */
                if (in_array($account->integration_id,[Integration::AMAZON]) && isset($response['response'],$response['response']['productFeedId']) && !empty($response['response']['productFeedId'])) {
                    \Log::info("Amazon Product Status Sync:: sku|".$product->associated_sku.'|product feed id|'.$response['response']['productFeedId']);
                    ExportedProductStatusSyncJob::dispatch('exported_products_status',
                        $account, [ 'taskId'=>$this->task->id,
                                    'productId' => $this->task->product_id,
                                    'accountId'=> $this->task->account_id,
                                    'shopId'=>$account->shop_id,
                                    'regionId'=>$account->region_id,
                                    'integrationId'=>$account->integration_id,
                                    'timestamp' => now()->timestamp,
                                    'sku' => $product->associated_sku], $response['response']['productFeedId'])->onQueue('sync_amazon_exported_products_status')->delay(now()->addSeconds(100));
                    return [];
                }
                $this->task->status = JobStatus::FINISHED();
                $this->task->save();

                return [];
            }

        } catch (\Exception $exception) {
            set_log_extra('task', $this->task->toArray());
            $this->task->messages = $this->setTaskMessages($this->task->messages, 'Internal Server Error.');
            $this->task->status = JobStatus::FAILED();
            $this->task->save();
            throw $exception;
        }
    }


    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        set_log_extra('task', $this->task->toArray());
        $this->task->messages = $this->setTaskMessages($this->task->messages, $exception->getMessage());
        $this->task->status = JobStatus::FAILED();
        $this->task->save();
    }


    /**
     * Set task messages based on different condition
     *
     * @param string|array $messages
     * @param string $message
     * @return array
     */
    public function setTaskMessages($messages, $message)
    {
        if (empty($messages)) {
            if (!is_array($message)) {
                $message = [$message];
            }

            $messages = $message;
        } else {
            if (!is_array($message)) {
                $message = [$message];
            }

            $messages = array_merge((array)$this->task->messages, $message);
        }
        return $messages;
    }
}
