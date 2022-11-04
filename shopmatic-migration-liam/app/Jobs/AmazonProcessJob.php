<?php

namespace App\Jobs;

use App\Factories\ClientFactory;
use App\Factories\ProductAdapterFactory;
use App\Models\Product;
use App\Models\ProductListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AmazonProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *+
     * @var int
     */
    public $timeout = 7200;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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

        // Check for feed submission result
        if ($this->feedResult($this->feedId)) {
            switch ($this->processType) {
                case "inventory":
                    return $this->handleInventory();
                default:
                    throw new \Exception('Unhandled process type for amazon.'.$this->processType);
            }
        }
    }

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
                return true;
            } else {
                set_log_extra('response', $document);
                set_log_extra('feed_submission_id', $feedId);
                throw new \Exception('Unable to update stock for Amazon product listing.');
            }
        } else {
            set_log_extra('response', $response);
            set_log_extra('feed_submission_id', $feedId);
            throw new \Exception('Unable to update stock for Amazon product listing.');
        }
    }

    private function handleInventory()
    {
        $productAdapter = ProductAdapterFactory::create($this->account);
        /** @var ProductListing $listing */
        $listing = ProductListing::find($this->data['listing_id']);
        $stock = $this->data['stock'];

        /*
         * As Amazon takes around 30 mins to 1 hour to reflect the latest data, so we need to manually update the inventory stock
         * Did not use adapter 'get' function cause we need to manually pass the stock
        */
        if (!empty($listing->product->associated_sku) && !is_null($listing->product->associated_sku)) {
            $startDate = new \DateTime('last year');
            $endDate = new \DateTime('+1 day');

            $product = $productAdapter->getProducts($startDate, $endDate, $listing->product->associated_sku);

            if ($product) {
                // @TAKE NOTE - Manually pass stock here
                $product['quantity'] = $stock;

                try {
                    $product = $productAdapter->transformProduct($product);
                } catch (\Exception $e) {
                    set_log_extra('product', $product);
                    throw $e;
                }
                return $productAdapter->handleProduct($product);
            }
        }
        set_log_extra('listing', $listing);
        throw new \Exception('Unable to update stock, product item sku not found');
    }
}
