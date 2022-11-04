<?php

namespace App\Jobs;

use App\Factories\ClientFactory;
use App\Factories\ProductAdapterFactory;
use App\Models\Product;
use App\Constants\ProductStatus;
use App\Models\ProductListing;
use App\Models\ProductExportTask;
use App\Constants\JobStatus;
use App\Integrations\Logger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportedProductStatusSyncJob extends Logger implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *+
     * @var int
     */
    public $timeout = 9000;//150 min(s)

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 10;
    /**
     * The number of seconds after which the job's unique lock will be released.
     * @var int
     */
    public $releaseJob = 1800;// 30 min(s)

    public $processType;
    public $account;
    public $data;
    public $feedId;

    protected $client;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($processType, $account, $data, $feedId)
    {
        $this->processType = $processType;
        $this->account = $account;
        $this->data = $data;
        $this->feedId = $feedId;
    }

    /**
     * Execute the job.
     *
     * @return Product|null
     * @throws \Exception
     */
    public function handle()
    {
        $this->client = ClientFactory::create($this->account);
        $logMessage = 'ExportProductStatusSyncJob Started [Sku:'.$this->data['sku'].'] | Task Id :'.$this->data['taskId'].' | Attempt :' .$this->attempts().'| Account Id:'.$this->data['accountId'].' | Feed Id:'.$this->feedId.' | Product Id :'.$this->data['productId'];
        $this->logDebug($logMessage);
        $task = ProductExportTask::find($this->data['taskId']);

        if($this->attempts() < 10 && isset($task->id)) {
            $response = $this->feedResult($this->feedId);
            if (!empty($response)) {
                if ($response['status']) {

                    $product = Product::find($task->product_id);
                    // Create the product listing
                    $listingResponse = $this->createProductListing($product->associated_sku);
                    if (isset($listingResponse['status']) && $listingResponse['status'] === ProductStatus::LIVE()) {
                        // Update Task Status To Finished
                        $task->status = JobStatus::FINISHED();
                        $task->save();

                        // Update Product Status To Live
                        $product->status = ProductStatus::LIVE();
                        $product->save();

                        $logMessage = 'ExportProductStatusSyncJob Finished [Sku:'.$this->data['sku'].'] | Task Id :'.$this->data['taskId'].' | Attempt :' .$this->attempts().'| Account Id:'.$this->data['accountId'].' | Feed Id:'.$this->feedId.' | Product Id:'.$this->data['productId'].'| Status:'.$response['status'].'| Api Response:['.json_encode($response).'] | Task Status:'.JobStatus::FINISHED().' | Product Status:'.ProductStatus::LIVE();
                        $this->logDebug($logMessage);
                    } else {
                        $logMessage = 'ExportProductStatusSyncJob Enqueued [Sku:'.$this->data['sku'].'] | Task Id :'.$this->data['taskId'].' | Attempt :' .$this->attempts().'| Account Id:'.$this->data['accountId'].' | Feed Id:'.$this->feedId.' | Product Id:'.$this->data['productId'].' | Listing data not returned by amazon api , so en-queued back to re-try after sometime.';
                        $this->logDebug($logMessage);
                        $this->release($this->releaseJob);
                    }
                } else {

                    //Failed
                    // Update Task Status To Failed
                    $task->status = JobStatus::FAILED();
                    $task->messages = isset($response['data'],$response['data']['Message'],$response['data']['Message']['ProcessingReport']['Result']['ResultDescription']) ? ['Sku:'.$this->data['sku'].' | '.$response['data']['Message']['ProcessingReport']['Result']['ResultDescription']] :'';
                    $task->save();
                    $logMessage = 'ExportProductStatusSyncJob Failed [Sku:'.$this->data['sku'].'] | Task Id :'.$this->data['taskId'].' | Attempt :' .$this->attempts().'| Account Id:'.$this->data['accountId'].' | Feed Id:'.$this->feedId.' | Product Id:'.$this->data['productId'].'| Status:'.$response['status'].'| Api Response:['.json_encode($response).'] | Task Status:'.JobStatus::FAILED().' | Product Status:'.ProductStatus::DRAFT();
                    $this->logDebug($logMessage);
                }
                return;
            }else{
                $logMessage = 'ExportProductStatusSyncJob Enqueued  [Sku:'.$this->data['sku'].'] | Task Id :'.$this->data['taskId'].' | Attempt :' .$this->attempts().'| Account Id:'.$this->data['accountId'].' | Feed Id:'.$this->feedId.' | Product Id:'.$this->data['productId'].' | Feed data not returned by amazon api , so en-queued back to re-try after sometime.';
                $this->logDebug($logMessage);
                $this->release($this->releaseJob);
            }
        } else if(isset($task->id)) {

            $logMessage = 'ExportProductStatusSyncJob Failed [Sku:'.$this->data['sku'].'] | Task Id :'.$this->data['taskId'].'| Account Id:'.$this->data['accountId'].' | Feed Id:'.$this->feedId.' | Product Id:'.$this->data['productId'].'| Status:Failed | Reason:Maximum retry exceeded.Product listing status not received from amazon.';
            $this->logDebug($logMessage);
            $task->status = JobStatus::FAILED();
            $task->messages = ['Sku:'.$this->data['sku'].' | Maximum retry exceeded.Product listing status not received from amazon.'];
            $task->save();
        }

        return;
    }

    /**
     * Fetch FeedResult
     * @param $feedId
     * @return array
     */
    private function feedResult($feedId)
    {
        $response = $this->client->getFeed($feedId)->getPayload();

        if ($response && $response->getProcessingStatus() === 'DONE' && $response->getResultFeedDocumentId()) {
            $document = $this->client->getFeedDocument($response->getResultFeedDocumentId())->getPayload();

            $key = base64_decode($document->getEncryptionDetails()->getKey());
            $iv = base64_decode($document->getEncryptionDetails()->getInitializationVector());
            $data = openssl_decrypt(file_get_contents($document->getUrl()), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            // Convert xml to array
            $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $dataArray = json_decode($json,TRUE);
            if ($dataArray && isset($dataArray['Message']['ProcessingReport']['ProcessingSummary']['MessagesSuccessful']) && $dataArray['Message']['ProcessingReport']['ProcessingSummary']['MessagesSuccessful'] == 1) {
                return ['status'=>true,'data'=>$dataArray];
            }else{
                return ['status'=>false,'data'=>$dataArray];
            }
        }
        return [];
    }
    /**
     * Create Product Listing.
     * @param $product
     * @return array
     */
    private function createProductListing($sellerSku) {
        if (!empty($sellerSku)) {
            $productAdapter = ProductAdapterFactory::create($this->account);
            $this->client = ClientFactory::create($this->account);
            $startDate = new \DateTime('last year');
            $endDate = new \DateTime('+1 day');
            $product = $productAdapter->getProducts($startDate, $endDate, $sellerSku);
            if ($product) {
                $product = $productAdapter->transformProduct($product);
                return $productAdapter->handleProduct($product);
            }
        }
        return;
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
            $messages = [$message];
        } else {
            $messages = array_merge((array)$this->task->messages, [$message]);
        }
        return $messages;
    }
}