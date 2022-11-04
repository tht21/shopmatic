<?php

namespace App\Jobs;

use App\Constants\ExcelType;
use App\Constants\IntegrationSyncData;
use App\Constants\JobStatus;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImportTask;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTrail;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\Shop;
use App\Utilities\Excel\ExtractExcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use App\Integrations\TransformedProductImage;
use App\Models\ProductImage;
use App\Jobs\UploadProductImage;
use App\Constants\ProductAlertType;
use App\Events\NewProductAlert;
use App\Models\IntegrationCategoryAttribute;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;


class ProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *+
     * @var int
     */
    public $timeout = 21600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    public $total_products;
    public $task;
    protected $unformattedVariantAttributes;
    protected $mandatoryAttributes;
    protected $mandatoryAttributesVariants;

    /**
     * Maximum allowed length for main image url.
    */
    const MAX_LENGTH_IMAGE_URL = 255;

    /*
     * The columns that's checked to make sure it's not empty
     */
    const REQUIRED_FIELDS = ['name', 'sku', 'price', 'stock'];
    const FIELD_OPTION_YES_NO = ['delivery_option_store_pick_up'];
    const CHECKBOX_FIELDS = ['featured', 'reviews_allowed', 'virtual'];
    const FIELD_OPTION_ONE_TWO = [];
    /*
     * if product has variant => allow FILED NULL
    */
    const FILEDS_ALOW_HAS_VARIANT = ['price', 'stock'];

    /**
     * Create a new job instance.
     *
     * @param ProductImportTask $task
     */
    public function __construct(ProductImportTask $task)
    {
        $this->task = $task;
        $this->total_products = 0;
        $this->unformattedVariantAttributes = [];
        $this->mandatoryAttributes = ['name','sku'];
        $this->mandatoryAttributesVariants = ['name','sku','price','stock','length','width','height','width','main_image'];
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return [
            ProductImportTask::class.':'.$this->task->id,
            Shop::class.':'.$this->task->shop->id,
            $this->task->source_type.':'.$this->task->source
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->task->status = JobStatus::PROCESSING()->getValue();
        $this->task->save();

        try {
            switch ($this->task->source_type) {
                case '.csv':
                    return $this->handleCsv();
                case '.xlsx':
                    return $this->handleExcel();
                case Account::class:
                    return $this->handleAccount();
                case ExcelType::CREATE_PRODUCTS()->getValue():
                    return $this->handleExcel(ExcelType::CREATE_PRODUCTS()->getValue());
                default:
                    throw new \Exception('Unhandled source for import product task - ' . $this->task->source_type);
            }
        } catch (\Exception $exception) {
            $this->task->status = JobStatus::FAILED()->getValue();
            $this->task->save();
            throw $exception;
        }
    }

    /**
     * @param null $excelType
     * @throws \Exception
     */
    private function handleCsv($excelType = null)
    {

    }

    /**
     * Fetch the product from excel and import to Combinesell
     *
     * @param string|null $excelType
     * @throws \Exception
     */
    private function handleExcel($excelType = null)
    {
        switch ($excelType) {
            case ExcelType::CREATE_PRODUCTS()->getValue():
                $headersGroup = $this->extractHeaders(2);
                $extractedExcel = new ExtractExcel(2);
                Excel::import($extractedExcel, $this->task->source, 'excel', \Maatwebsite\Excel\Excel::XLSX);

                // put ...[0] behind because this excel only has 1 sheet, so straight point it to that sheet
                $sheetName = $extractedExcel->getSheetNames()[0];
                $sheetsData = $extractedExcel->getSheetsData();
                // skip it if it is empty
                if (!empty($sheetsData)) {
                    //$sheetData = $sheetsData[0];

                    $sheetData = $this->formatSheetData($sheetsData[0]);
                    $this->extractUnformattedVariantHeaders(2);
                    $this->createProducts($headersGroup, $sheetData, $sheetName);

                    $this->task->status = JobStatus::FINISHED()->getValue();
                    $this->task->save();
                }
        }
    }

    /**
     * As the reader for both CSV and Excel is different, we call this handleArray after we parse it to array
     * This is where it actually handles the creating and importing of the product
     *
     */
    private function handleArray()
    {

    }

    /**
     * Fetches the products from the account
     *
     * @throws \Exception
     */
    private function handleAccount()
    {
        /** @var Account $account */
        $account = Account::find($this->task->source);

        // Validation prior to actual processing

        if (empty($account)) {
            throw new \Exception('Account not found or valid.');
        }
        if (!$account->hasFeature(['products', 'import_products'])) {
            throw new \Exception('Integration does not support importing of products.');
        }
        // Actual processing
        $adapter = $account->getProductAdapter();

        $config = [
            'update' => !empty($this->task->settings['update_products']),
            'new' => !empty($this->task->settings['new_products']),
            'bundle' => !empty($this->task->settings['bundle_products']),
            'delete' => !empty($this->task->settings['remove_deleted_products']),
            'delete_variants' => !empty($this->task->settings['remove_deleted_product_variants'])
        ];
        $adapter->import($this->task, $config);


        // Updating the timestamp for the last import time
        $account->setSyncData(IntegrationSyncData::IMPORT_PRODUCTS(), now());
        $account->save();

        $this->task->status = JobStatus::FINISHED()->getValue();
        $this->task->save();
    }


    /**
     * The job failed to process.
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        set_log_extra('task', $this->task->toArray());
        Log::error($exception);
        $this->task->messages = $this->setTaskMessages($this->task->messages, $exception->getMessage());
        $this->task->status = JobStatus::FAILED()->getValue();
        $this->task->save();
    }

    /**
     * Extract headers data from excel
     *
     * @param int $headingRowCount
     * @return array
     * @throws \Exception
     */
    public function extractHeaders($headingRowCount = 1)
    {
        try {
            // raw headers data extract from excel
            $rawHeaders = [];
            // use to record grouped headers data
            $headersGroup = [];
            for ($i = 1; $i <= $headingRowCount; $i++) {
                // ...[0] = first sheet, currently only support single sheet headers data extract
                // if there's no_read_options sheet, the original sheet index will be 1, else it will be in 0
                $sheets = (new HeadingRowImport($i))->toArray($this->task->source, 'excel', \Maatwebsite\Excel\Excel::XLSX);
                $n = 0;
                if (isset($sheets[1])) {
                    $n = 1;
                }
                $rawHeaders[] = $sheets[$n][0];
            }


            // headers row and column total count
            $maxRowCount = count($rawHeaders);
            $maxColumnCount = count($rawHeaders[0]);
            // reference or parent node of current level
            $referenceRecord = [&$headersGroup];
            // loop through column
            for ($columnIndex = 0; $columnIndex < $maxColumnCount; $columnIndex++) {
                // loop through row
                for ($rowIndex = 0; $rowIndex < $maxRowCount; $rowIndex++) {
                    // current level node value
                    $currentValue = $rawHeaders[$rowIndex][$columnIndex];
                    $currentValue = str_replace("*",'',$currentValue);
                    // if current level node has child
                    if (!empty($rawHeaders[$rowIndex][$columnIndex]) && $rowIndex !== $maxRowCount - 1) {
                        // create an array to store next level data
                        $referenceRecord[$rowIndex][$currentValue] = [];
                        // set a new parent reference for child node
                        $referenceRecord[$rowIndex + 1] = &$referenceRecord[$rowIndex][$currentValue];
                    } elseif (!empty($rawHeaders[$rowIndex][$columnIndex]) && $rowIndex === $maxRowCount - 1) {
                        // last level, straight store value
                        $referenceRecord[$rowIndex][] = $currentValue;
                    }
                }
            }

            return $headersGroup;
        } catch (\Exception $e) {
            set_log_extra('headingRowCount', $headingRowCount);
            set_log_extra('rawHeaders', $rawHeaders);
            set_log_extra('headersGroup', $headersGroup);
            throw $e;
        }
    }

    /**
     * Create products based on given data extracted from excel
     *
     * @param array $headersGroup
     * @param array $sheetData
     * @param string $sheetName
     * @throws \Exception
     */
    public function createProducts(array $headersGroup, $sheetData, string $sheetName)
    {
        // if product exist, update it or not
        $update = isset($this->task->settings['update']) ? json_decode($this->task->settings['update']) : false;

        $row = 2;
        foreach ($sheetData as $key => $data) {
            $row++;
            // skip empty row
            if (empty(array_filter($data))) continue;
            try {
                // use to store product data and product's integration attributes data
                $productData = [];
                $attributesData = [];
                $integrationName = '';
                $regionName = '';
                foreach ($headersGroup as $group => $headers) {
                    $integrationCategoryId = $integrationCategoryName = '';
                    if (snake_case(strtolower($group)) !== 'basic_information') {
                        /** Integration Category Name */
                        $integrationNameAndRegionNameAndIntegrationCategoryId = str_replace(' ', '_', trim($group));
                        $integrationNameAndRegionNameAndIntegrationCategoryId = str_replace('-', '_', trim($group));
                        $integrationNameAndRegionNameAndIntegrationCategoryId = explode('_', $integrationNameAndRegionNameAndIntegrationCategoryId);
                        $integrationCategoryName = $integrationNameAndRegionNameAndIntegrationCategoryId[0] ?? '';
                        $regionName = $integrationNameAndRegionNameAndIntegrationCategoryId[1] ?? '';
                        $integrationName = $integrationCategoryName;

                        /** Integration Category Id */
                        if (in_array(strtolower($integrationCategoryName),['qoo10'])) {
                            $integrationCategoryGroup = explode('_',$group)[2];
                            $integrationCategoryId = abs(filter_var($integrationCategoryGroup, FILTER_SANITIZE_NUMBER_INT));
                            
                        } else {
                            $integrationCategoryId = abs(filter_var($group, FILTER_SANITIZE_NUMBER_INT));
                        }
                        // Skip if has no category
                        $hasNoCategory = true;
                        if (!in_array(strtolower($integrationCategoryName), ['shopify', 'amazon', 'woocommerce'])) {
                            $group = $integrationCategoryName;
                            $hasNoCategory = false;

                            // Check integration category
                            if ($integrationCategory = IntegrationCategory::find($integrationCategoryId)) {
                                $regionId = $integrationCategory->region_id;
                            } else {
                                set_log_extra('integration_category_id', $integrationCategoryId);
                                set_log_extra('integration_category_name', $integrationCategoryName);
                                \Log::error('Could not retrieve integration category.');
                                throw new \Exception('Invalid integration category '.$integrationCategoryId);
                            }
                        }
                    }
                    foreach ($headers as $header) {

                        // basic_information contains product data
                        if (snake_case(strtolower($group)) == 'basic_information') {
                            // only record data that has value
                            if (!is_null($data[$header]) && trim($data[$header]) != '') {
                                $productData[$header] = $data[$header];
                                // use to setup main product options column
                                if (starts_with($header, 'option_') && is_numeric(str_after($header, 'option_')) && !empty($data[$header])) {
                                    $productData['options'][] = title_case(str_replace('_', ' ', $data[$header]));
                                }
                            }
                        } else {
                            // only record data that has value
                            if (!empty($data[$header])) {
                                $attributesData[$integrationCategoryId][$header] = $data[$header];
                            }
                            // Append integration category id here
                            if ((ctype_digit($integrationCategoryId) || is_numeric($integrationCategoryId)) && $integrationCategoryId !== 0 && !$hasNoCategory) {
                                $attributesData[$integrationCategoryId]['integration_category_id'] = $integrationCategoryId;
                            } else {
                                // For no category integration will manually set the integration id and region id here
                                switch (strtolower($integrationCategoryName)) {
                                    case "shopify":
                                        $integrationId = Integration::SHOPIFY;
                                        break;
                                    case "amazon":
                                        $integrationId = Integration::AMAZON;
                                        break;
                                    case "woocommerce":
                                        $integrationId = Integration::WOOCOMMERCE;
                                        break;
                                    case "lazada":
                                        $integrationId = Integration::LAZADA;
                                        break;
                                    case "shopee":
                                        $integrationId = Integration::SHOPEE;
                                        break;
                                    default:
                                        set_log_extra('integration_category_name', $integrationCategoryName);
                                        throw new \Exception('Invalid integration name: '.$integrationCategoryName);
                                }
                                $integration = Integration::find($integrationId);
                                // Get region
                                $regionId = $integration->region_ids[0];
                                if (strpos($group, 'global') !== false) {
                                    $regionId = Region::GLOBAL;
                                } else if (strpos($group, 'sg') !== false) {
                                    $regionId = Region::SINGAPORE;
                                } else if (strpos($group, 'my') !== false) {
                                    $regionId = Region::MALAYSIA;
                                } else if (strpos($group, 'id') !== false) {
                                    $regionId = Region::INDONESIA;
                                }
                                // Two extra attribute for handle attribute data used
                                $attributesData[$integrationCategoryId]['integration_id_n'] = $integration->id;
                                $attributesData[$integrationCategoryId]['region_id_n'] = $regionId;
                                $productData['integration_id'] = $integration->id;
                            }
                        }
                    }
                }

                // skip if row doesnt contain any product data
                if (empty($productData)) continue;

                /**
                 * get integration id by integration name
                 */
                $productData['integration_id'] = $this->getIntegrationIdByIntegrationName($integrationName);
                $productData['region_id'] = $this->getRegionByIntegrationName($regionName);

                /**
                 * Lazada
                 * check is_sale_prop
                 */
                if (count($this->getAllVariantInSheetData($productData['sku'] ?? '', $sheetData)) > 1 && strtolower($integrationCategoryName) == 'lazada' && $integrationCategoryId) {
                    $integrationCategoryModel = IntegrationCategory::find($integrationCategoryId);
                    if ($integrationCategoryModel && $integrationCategoryModel->isIntegrationCategoryNotHaveAttributeIsSaleProp()) {
                        $this->addMessage("Product " .($productData['name'] ?? ' ') ." with SKU " . ($productData['sku'] ?? ' ') . " cannot be exported to Lazada under category " . ($integrationCategoryModel ? $integrationCategoryModel->name : $integrationCategoryId) . " as this category does not allow for products with more than one variant to be created. \n ");
                        continue;
                    }
                }

                /**
                 * Validate Image Url
                */
                $invalidImageReason = '';
                if(!empty($productData['main_image'])){
                    $isValidImage = $this->validateImageUrl($productData['main_image']);
                    if (!$isValidImage['isValid']) {
                        $invalidImageReason = $isValidImage['message'] ?? '';
                        $invalidImageReason .= '| Image Url ['.$productData['main_image'].']';
                        unset($productData['main_image']);
                    }
                }

                //skip if row doesnt contains the mandatory attributes
                $isMandatoryAttributeMissing = false;
                $missingFields = [];

                // product and variants have different basic mandatory fields
                $mandatoryAttributes = $this->mandatoryAttributes;
                if (array_key_exists('associated_sku', $productData) && !empty($productData['associated_sku'])) {
                    $mandatoryAttributes = $this->mandatoryAttributesVariants;
                }

                foreach($this->mandatoryAttributes as $mAttrib) {
                    if(!isset($productData[$mAttrib]) || is_null($productData[$mAttrib]) || trim($productData[$mAttrib]) == ''){
                        $isMandatoryAttributeMissing = true;
                        $missingFields[] = $mAttrib;
                    }
                }
                if($isMandatoryAttributeMissing) {
                    $pName = $productData['name'] ?? '';
                    $pSku = $productData['sku'] ?? '';
                    $errorMessage = '( Unable to create this product. Name  - '. $pName .' | Sku - '. $pSku .',as mandatory basic info are missing.Missing info - '.implode(",",$missingFields).')';
                    $errorMessage .= $invalidImageReason;
                    $this->task->messages = $this->setTaskMessages($this->task->messages, $errorMessage);
                    $this->task->save();
                    continue;
                }
                // add-on data setup here
                $productData['shop_id'] = $this->task->shop_id;
                $productData['category_id'] = (int)explode(',', $sheetName)[1];
                $productData['currency'] = $this->task->shop->currency;
                $productData['options'] = $productData['options'] ?? [];
                $productData['html_description'] = $productData['html_description'] ?? '';
                $productData['main_image'] = $productData['main_image'] ?? '';
                if (!empty($productData['integration_id']) && $productData['integration_id'] == Integration::SHOPEE) {
                    $productData['short_description'] = $data['short_description'] ?? '';
                }
                $fields = [];
                $dataVariantFirst = $this->getVariantInSheetData($productData['sku'] ?? '', $sheetData);
                foreach (self::REQUIRED_FIELDS as $field) {
                    // if product has variant => Allow fields;
                    if (in_array($field, self::FILEDS_ALOW_HAS_VARIANT) && !empty($dataVariantFirst)) {
                        $productData['price'] = !empty($productData['price']) ? $productData['price'] : ($dataVariantFirst['price'] ?? 0);
                        $productData['stock'] = !empty($productData['stock']) ? $productData['stock'] : ($dataVariantFirst['stock'] ?? 0);
                    } elseif (!isset($productData[$field]) || is_null($productData[$field]) || trim($productData[$field]) == '') {
                        $fields[] = $field;
                    }
                }
                if (!empty($fields)) {
                    $this->addMessage(implode(', ', $fields) . ' not set.', $row);
                    continue;
                }
                // create product
                if (!array_key_exists('associated_sku', $productData) || empty($productData['associated_sku'])){
                    $this->createProduct($productData, $attributesData, $update, $headersGroup, $row);

                    $this->total_products++;
                    $this->task->total_products = $this->total_products;
                    $this->task->save();

                    // check if the next row is variant or not
                    if ($this->isProductNotHasVariant($productData['sku'], $sheetData)) {

                        /**
                         * with product not variant
                         * get product by associated_sku.
                         *
                         * if product not exist or product not variant => set associated_sku = productdata sku
                         *
                         * if product single, product created variant => set sku =  sku of variantFirst
                         *
                         */
                        $productBySku = Product::where('associated_sku', $productData['sku'])->first();
                        $productData['associated_sku'] = $productData['sku'];
                        if ($productBySku && $variantFirst = $productBySku->variants->first()) {
                            $productData['associated_sku'] = $productBySku->associated_sku;
                            $productData['sku'] = $variantFirst->sku;
                        }
                        // if next row is not variant means this is single product, so we will create a variant using main product
                        $this->createVariant($productData, $attributesData, $update, $row);
                    }
                } else {
                    $this->createVariant($productData, $attributesData, $update, $row);
                }
            } catch (\Exception $e) {
                set_log_extra('data', $data);
                set_log_extra('productData', $productData);
                throw $e;
            }
        }
    }


    private function setStatusIfAttributeNameOptionNotSelect($attributesData){

        if(is_array($attributesData)){
            $attributesData = array_map(function($attributeOption){

                $attributeOption['featured'] =  $attributeOption['featured'] ?? '';
                $attributeOption['reviews_allowed'] =  $attributeOption['reviews_allowed'] ?? '';
                $attributeOption['virtual'] =  $attributeOption['virtual'] ?? '';
                return $attributeOption;

            },$attributesData);
        }
        return $attributesData;
    }
    /**
     * Create Main Product
     *
     * @param array $productData
     * @param array $attributesData
     * @param boolean|int|null $update
     * @param array $headersGroup
     */
    private function createProduct($productData, $attributesData, $update, $headersGroup = ['Default'], $row = null)
    {
        // used to debug duplicate options bug
        if (count(array_count_values($productData['options'])) !== count($productData['options'])) {
            set_log_extra('header', $headersGroup);
            set_log_extra('product', $productData);
            Log::error('Duplicate options detected.');
            $productData['options'] = array_unique($productData['options']);
        }

        // duplicate check. validate image checked image when call function createProducts(). If it doesn't pass. It was unset
        // $productMainImage = $productData['main_image'];
        // $skipProductMainImage = false;
        // if(isset($productData['main_image']) && !empty($productData['main_image'])){
        //     // If the main image url length is exceeded then alert to user
        //     if (strlen($productData['main_image']) > self::MAX_LENGTH_IMAGE_URL) {
        //         $skipProductMainImage = true;
        //         unset($productData['main_image']);
        //     }
        //     // Validate the main image url $productData['sku']
        //     $isValidImage = $this->validateImageUrl($productData['main_image']);
        //     $invalidImageReason = '';
        //     if (!$isValidImage['isValid']) {
        //         $invalidImageReason = $isValidImage['message'] ?? '';
        //         $invalidImageReason .= '| Image Url ['.$productData['main_image'].'] | Sku:'.$productData['sku'];
        //         unset($productData['main_image']);
        //     }
        // }

        if (!$update) {
            $product = Product::firstOrCreate([
                'shop_id' => $productData['shop_id'],
                'associated_sku' => $productData['sku']
            ], $productData);
        } else {
            $product = Product::updateOrCreate([
                'shop_id' => $productData['shop_id'],
                'associated_sku' => $productData['sku']
            ], $productData);
        }

        // if ($skipProductMainImage) {
        //     event(new NewProductAlert($product,'The product main image (' . $productMainImage . ') is exceeding maximum character limit ('.self::MAX_LENGTH_IMAGE_URL.' characters).',ProductAlertType::ERROR()));
        // }
        // // Create an alert if invalid image url
        // if ($invalidImageReason) {
        //     event(new NewProductAlert($product,$invalidImageReason,ProductAlertType::ERROR()));
        // }
        /**
         * Create an entry into product image and upload the source url image into S3.
         * After Successfull upload , assign the S3 image url to main_image.
         */
        /*if ($skipProductMainImage == false && !empty($productMainImage)) {
            $queryData = [
                'shop_id'        => $this->task->shop_id,
                'currency'       =>  $this->task->shop->currency ?? 'SGD',
            ];
            $account = Account::where($queryData)->first();
            $image = new TransformedProductImage($productMainImage);
            $image->createImage($product, $account, null, null);
            $product = $product->fresh();
            $productData['main_image'] = $product->main_image ??  '';
        }*/
        /**
         * Trigger alert event if url exceeds max character limit.
        */
        /*elseif ($skipProductMainImage && $createAlert && $productMainImage) {
            event(new NewProductAlert($product,'The product main image (' . $productMainImage . ') is exceeding maximum character limit ('.self::MAX_LENGTH_IMAGE_URL.' characters).',ProductAlertType::ERROR()));
        }*/
        $this->handleRelatedTableData($product, $productData, $attributesData, $update, $row);
    }

    /**
     * Create Variant
     *
     * @param array $productData
     * @param array $attributesData
     * @param boolean|int|null $update
     */
    private function createVariant($productData, $attributesData, $update, $row)
    {
        /** @var Product $parentProduct */
        $parentProduct = Product::where([
            'shop_id' => $productData['shop_id'],
            'associated_sku' => $productData['associated_sku']
        ])->first();

        if ($parentProduct) {
            //$productData['main_image'] = $parentProduct['main_image'] ?? $productData['main_image'];
            $productData['main_image'] = $productData['main_image'] ?? $parentProduct['main_image'];

            // duplicate check. validate image checked image when call function createProducts(). If it doesn't pass. It was unset
            // $productMainImage = $productData['main_image'];
            // $skipProductMainImage = false;
            // if(isset($productData['main_image']) && !empty($productData['main_image'])){
            //     // If the main image url length is exceeded then alert to user
            //     if (strlen($productData['main_image']) > self::MAX_LENGTH_IMAGE_URL) {
            //         $skipProductMainImage = true;
            //         unset($productData['main_image']);
            //     }
            //     // Validate Main Image Url
            //     $isValidImage = $this->validateImageUrl($productData['main_image']);
            //     $invalidImageReason = '';
            //     if (!$isValidImage['isValid']) {
            //         $invalidImageReason = $isValidImage['message'] ?? '';
            //         $invalidImageReason .= '| Image Url ['.$productData['main_image'].'] | Sku:'.$productData['sku'];
            //         unset($productData['main_image']);
            //     }
            // }

            if (!$update) {
                $variant = ProductVariant::firstOrCreate([
                    'product_id' => $parentProduct->id,
                    'shop_id' => $productData['shop_id'],
                    'sku' => $productData['sku']
                ], $productData);
            } else {
                $variant = ProductVariant::updateOrCreate([
                    'product_id' => $parentProduct->id,
                    'shop_id' => $productData['shop_id'],
                    'sku' => $productData['sku']
                ], $productData);
            }

            // if ($skipProductMainImage) {
            //     $variant = $variant->fresh();
            //     event(new NewProductAlert($parentProduct,'The product variant ('.$variant->name.') main image (' . $productMainImage . ') is exceeding maximum character limit ('.self::MAX_LENGTH_IMAGE_URL.' characters).',ProductAlertType::ERROR()));
            // }

            // // Create an alert if invalid image url
            // if ($invalidImageReason) {
            //     event(new NewProductAlert($parentProduct,$invalidImageReason,ProductAlertType::ERROR()));
            // }

            // Handle variant inventory
            $this->handleInventoryData($variant, $productData);

            $this->handleRelatedTableData($variant, $productData, $attributesData, $update, $row);
        } else {
            $this->task->messages = $this->setTaskMessages($this->task->messages, 'Main product with sku:(' . $productData['associated_sku'] . ') not found on row ' . $row . '.');
            $this->task->save();
        }
    }

    /**
     * Handle other table data that has relationship with Product/ProductVariant
     *
     * @param Product|ProductVariant $product
     * @param array $productData
     * @param array $attributesData
     * @param boolean|int|null $update
     */
    private function handleRelatedTableData($product, $productData, $attributesData, $update, $row)
    {
        if ($product->wasRecentlyCreated || $update) {
            $productId = $product instanceof Product ? $product->id : $product->product_id;
            $variantId = $product instanceof ProductVariant ? $product->id : null;

            // price need to be created in price table
            if (array_key_exists('price', $productData) && is_numeric($productData['price'])) {

                $product->prices()->updateOrCreate([
                    'shop_id' => $this->task->shop_id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'product_listing_id' => null,
                    'integration_id' => $productData['integration_id'] ?? null,
                    'region_id' => $productData['region_id'] ?? null,
                    'currency' => $this->task->shop->currency ?? 'SGD',
                    'type' => 'selling'
                ], [
                    'price' => (float)$productData['price'],
                ]);
            }


            // main image need to be created in image table
            if (!empty($productData['main_image'])) {

                // Make sure  the main image url length is not exceeded
                if (strlen($productData['main_image']) <= self::MAX_LENGTH_IMAGE_URL) {
                    // delete image
                    $product->images()->delete();

                    // create product images
                    $image =  $product->images()->updateOrCreate(                        [
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'product_listing_id' => null,
                    ],
                    [
                        'source_url' => $productData['main_image'],
                    ]);
                    UploadProductImage::dispatchNow($image);

                }

            }

            // image 1 - 12 need to be created in image table
            for ($i = 1;$i <= 12; $i++) {
                if (array_key_exists('image_'.$i, $productData) && !empty($productData['image_'.$i])) {
                    /**
                    * Validate the length of the image url.
                    * If it exceeds max length skip it.Don't create/update and don't upload to S3 Bucket as well.
                    * Trigger an alert event to notify image url exceeding maximum chacater limit.
                    */
                    if (!empty($productData['image_'.$i]) && strlen($productData['image_'.$i]) > self::MAX_LENGTH_IMAGE_URL) {
                        event(new NewProductAlert($product,'The product image_'.$i.'  (' . $productData['image_'.$i] . ') is exceeding maximum character limit ('.self::MAX_LENGTH_IMAGE_URL.' characters).',ProductAlertType::ERROR()));
                        continue;
                    } else {
                        // Validate the image url

                        $isValidImage = $this->validateImageUrl($productData['image_'.$i]);
                        $invalidImageReason = '';
                        if (!$isValidImage['isValid']) {
                            $invalidImageReason = $isValidImage['message'] ?? '';
                            $invalidImageReason .= '| Image_'.$i.' Url ['.$productData['image_'.$i].'] | Sku:'.$productData['sku'];
                            event(new NewProductAlert($product,$invalidImageReason,ProductAlertType::ERROR()));
                            continue;
                        }
                        $productImage = [
                            'product_id' => $productId,
                            'product_variant_id' =>  $variantId,
                            'product_listing_id' =>  null,
                            'source_account_id' =>   null,
                            'source_url' => $productData['image_'.$i],
                            // 'integration_id' => $productData['integration_id'] ?? NULL,
                            'position' => 0,
                            'width' => null,
                            'height' => null,
                        ];
                        $image = $product->images()->updateOrCreate($productImage);
                        UploadProductImage::dispatchNow($image);
                        $image = $image->fresh();
                        $productData['image_'.$i] = $image->image_url ??  '';
                    }

				}
            }

            if (empty($attributesData)) return;

            $logisticsData = [];

            // attribute need to created in attribute table
            foreach ($attributesData as $integrationCategoryId => $attributes) {
                // To retrieve the integration and region id
                $integrationCategory = IntegrationCategory::find($integrationCategoryId);

                // price need to be created in price table
                if (array_key_exists('price', $productData) && !is_null($productData['price']) && is_float($productData['price'])) {
                    // If integration category id is 0 means the integration does not have category
                    $regionId = ($integrationCategoryId !== 0) ? $integrationCategory->region_id : $attributes['region_id_n'];
                    $region = Region::find($regionId);

                    if (!$region) {
                        set_log_extra('response', $integrationCategory);
                        throw new \Exception('Region ID' . $regionId . ' not supported yet');
                    }
                    //$currency = $region->currency;
                    $currency = $this->task->shop->currency ?? 'SGD';
                    $product->prices()->updateOrCreate([
                        'shop_id' => $this->task->shop_id,
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'product_listing_id' => null,
                        'currency' => $currency,
                        'type' => 'selling',
                        'integration_id' => $integrationCategory->integration_id ?? $attributes['integration_id_n'],
                        'region_id' => $integrationCategory->region_id ?? $attributes['region_id_n'],
                    ], [
                        'price' => $productData['price']
                    ]);
                }
                $attributeIntegrationId  = $attributes['integration_id_n'] ?? '';
                foreach ($attributes as $attributeName => $attribute) {
                    if (in_array($attributeName, ['integration_id_n', 'region_id_n'])) {
                        continue;
                    }
                    // Convert the excel header attribute name to same with integration category attribute name.
                    $attributeName = $this->unformattedVariantAttributes[$attributeName] ?? $attributeName;
                    // Remove integration category string from attribute name
                    $attributeName = str_replace("_" . $integrationCategoryId, "", $attributeName);

                    // for lazada brand will be searching name input
                    if ($integrationCategoryId !== 0 && $integrationCategory->integration_id === Integration::LAZADA && $attributeName === 'brand') {
                        $attribute = json_encode(Brand::whereIntegrationId(Integration::LAZADA)->whereName($attribute)->get()->map(function ($item, $key) {
                            return ['id' => $item['external_id'], 'name' => $item['name']];
                        })->first());
                    }

                    // check if is logistic attribute
                    if (in_array(substr($attributeName, -3), ['_l1', '_l2', '_l3'])) {
                        //currently only support for shopee
                        if ($integrationCategory->integration_id === Integration::SHOPEE) {
                            $logisticsData[$integrationCategory->integration_id][$integrationCategory->region_id][$attributeName] = $attribute;
                        }
                    } else {
                        // check if it is variant attribute
                        $isVariantAttribute = false;
                        if (substr($attributeName, -2) === '_v') {
                            $isVariantAttribute = true;
                            $attributeName = substr($attributeName, 0, -2);
                            //$attributeName = substr($attributeName, 0, -2);
                        }

                        if (($isVariantAttribute && !is_null($variantId)) || (!$isVariantAttribute && is_null($variantId))) {
                            /**
                             * If Qoo10 and attribute is 'expire_date'
                             * Validate the date format with 'm/d/Y' || 'm/d/y'
                             * From excel expecting the date format either as mm/dd/yy or mm/dd/yyyy
                             */
                            if ($attributeName === 'expire_date' && !empty($attribute) && $integrationCategory->integration_id === Integration::QOO10) {
                                if (date('Y-m-d',strtotime($attribute)) == date($attribute) || date('y-m-d',strtotime($attribute)) == date($attribute)) {
                                    $attribute = date('Y-m-d',strtotime($attribute));
                                }else {
                                    $attribute = '';
                                }
                            }

                            if ( in_array($attributeName, self::CHECKBOX_FIELDS)) {
                                if (strtolower($attribute) === 'yes' || strtolower($attribute) === 'true' || $attribute === true ) {
                                    $attribute = 'true';
                                } else {
                                    $attribute = '';
                                }
                            }
                            if ( in_array($attributeName, self::FIELD_OPTION_ONE_TWO)) {
                                if (strtolower($attribute) === 'yes' || strtolower($attribute) === 'true') {
                                    $attribute = 1;
                                } else {
                                    $attribute = 0;
                                }
                            }
                            if ( in_array($attributeName, self::FIELD_OPTION_YES_NO)) {
                                if (strtolower($attribute) === 'yes' || strtolower($attribute) === 'true') {
                                    $attribute = 'Yes';
                                }elseif (strtolower($attribute) === 'no' || strtolower($attribute) === 'false') {
                                    $attribute = 'No';
                                }else {
                                    $attribute = '';
                                }
                            }

                            ProductAttribute::updateOrCreate([
                                'product_id' => $productId,
                                'product_variant_id' => $variantId,
                                'product_listing_id' => null,
                                //'integration_id' => array_flip(Integration::INTEGRATIONS)[title_case(str_replace('_', ' ', $group))],
                                'integration_id' => $integrationCategory->integration_id ?? $attributes['integration_id_n'],
                                'region_id' => $integrationCategory->region_id ?? $attributes['region_id_n'],
                                'name' => $attributeName,
                            ],[
                                'value' => $attribute
                            ]);
                        }
                    }
                }

            }

            // Handle logistic data
            if (count($logisticsData)) {
               $this->handleLogisticsData($productId, $logisticsData);
            }
        } else {
            $this->task->messages = $this->setTaskMessages($this->task->messages, ($product instanceof Product ? 'Product' : 'Variant') . ' with sku:(' . $productData['sku'] . ') already exist. Row: ' . $row);
            $this->task->save();
        }
    }

    private function addMessage($message, $row = null) {
        $this->task->messages = $this->setTaskMessages($this->task->messages, $message . (!empty($row) ? ' Row: ' . $row : ''));
    }

    /**
     * Handle logistics table data
     *
     * @param $productId
     * @param $logisticsData
     */
    private function handleLogisticsData($productId, $logisticsData)
    {
        $logisticValue = [];
        $count = 0;
        foreach ($logisticsData as $integrationId => $regionIds) {
            foreach ($regionIds as $regionId => $logistics) {
                foreach ($logistics as $logistic => $value) {
                    if (substr($logistic, -3) === '_l1' && $value === 'Yes') {
                        $pregLogistic = preg_replace( '/_[^_]*$/', '', $logistic);
                        $logisticId = substr($pregLogistic, strrpos($pregLogistic, '_') + 1);
                        $logisticName = title_case(str_replace('_', ' ', preg_replace( '/_[^_]*$/', '', $pregLogistic)));

                        $logisticValue[$count] = [
                            'logistic_name' => $logisticName,
                            'is_free' => (isset($logistics['is_free_'.$logisticId.'_l2']) && $logistics['is_free_'.$logisticId.'_l2']) ? true : false,
                            'logistic_id' => $logisticId,
                            'enabled' => true
                        ];

                        // shipping_fee or size_id
                        if (isset($logistics['shipping_fee_'.$logisticId.'_l3'])) {
                            $logisticValue[$count]['shipping_fee'] = $logistics['shipping_fee_'.$logisticId.'_l3'];
                        } else if (isset($logistics['size_id_'.$logisticId.'_l3'])) {
                            $logisticValue[$count]['size_id'] = $logistics['size_id_'.$logisticId.'_l3'];
                        }
                        $count++;
                    }
                }

                ProductAttribute::updateOrCreate([
                    'product_id' => $productId,
                    'product_variant_id' => null,
                    'product_listing_id' => null,
                    'integration_id' => $integrationId,
                    'region_id' => $regionId,
                    'name' => 'logistics',
                ],[
                    'value' => json_encode($logisticValue)
                ]);
            }
        }


    }

    public function handleInventoryData($variant, $productData)
    {
        $inventory = $this->task->shop->inventories()->where('sku', $variant->sku)->first();
        if (empty($inventory)) {
            // Inventory name used only if we create the inventory
            $inventoryName = $productData['name'];

            // This is because certain products and variant have similar names and in certain cases it exceeds
            // our max string length (255).

            // Hence we check it here to make sure it's not too long
            if (strlen($inventoryName . $variant->name) < 120) {
                $inventoryName = $inventoryName . (!empty($variant->name) ? ' - ' . $variant->name : '');
            }
            /** @var ProductInventory $inventory */
            $inventory = ProductInventory::firstOrCreate([
                'shop_id' => $this->task->shop_id,
                'sku' => $variant->sku,
            ], [
                'name' => $inventoryName,
                'stock' => $variant->stock ?? 0,
                'enabled' => true
            ]);

            ProductInventoryTrail::create([
                'shop_id' => $this->task->shop_id,
                'product_inventory_id' => $inventory->id,
                'message' => 'Inventory created from variant SKU: ' . $variant->sku,
                'related_id' => $variant->id,
                'related_type' => get_class($variant),
                'old' => $variant->stock,
                'new' => $variant->stock,
            ]);
        }
        $variant->inventory_id = $inventory->id;
        $variant->save();
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

    /**
     *  Extract Unformatted Variant Attributes Headers
     */
    public function extractUnformattedVariantHeaders($headingRowCount = 1)
    {
        try {
            // raw headers data extract from excel
            $rawHeaders = [];
            // use to record grouped headers data
            $headersGroup = [];
            HeadingRowFormatter::default('none');
            for ($i = 1; $i <= $headingRowCount; $i++) {
                // ...[0] = first sheet, currently only support single sheet headers data extract
                // if there's no_read_options sheet, the original sheet index will be 1, else it will be in 0
                $sheets = (new HeadingRowImport($i))->toArray($this->task->source, 'excel', \Maatwebsite\Excel\Excel::XLSX);
                $n = 0;
                if (isset($sheets[1])) {
                    $n = 1;
                }
                $rawHeaders[] = $sheets[$n][0];
            }
            // headers row and column total count
            $maxRowCount = count($rawHeaders);
            $maxColumnCount = count($rawHeaders[0]);
            // reference or parent node of current level
            $referenceRecord =[&$headersGroup];
            // loop through column
            for ($columnIndex = 0; $columnIndex < $maxColumnCount; $columnIndex++) {
                // loop through row
                for ($rowIndex = 0; $rowIndex < $maxRowCount; $rowIndex++) {
                    // current level node value
                    $currentValue = $rawHeaders[$rowIndex][$columnIndex];
                    $currentValue = str_replace('*','',$currentValue);
                    if (snake_case(strtolower($currentValue)) == 'basic_information') {
                        continue;
                    }
                    // if current level node has child
                    if (!empty($rawHeaders[$rowIndex][$columnIndex]) && $rowIndex !== $maxRowCount - 1) {
                        // create an array to store next level data
                        $referenceRecord[$rowIndex][$currentValue] = [];
                        // set a new parent reference for child node
                        $referenceRecord[$rowIndex + 1] = &$referenceRecord[$rowIndex][$currentValue];
                    } elseif (!empty($rawHeaders[$rowIndex][$columnIndex]) && $rowIndex === $maxRowCount - 1) {
                        // last level, straight store value
                        $referenceRecord[$rowIndex][] = $currentValue;
                    }
                }
            }
            $unformattedVariantAttributes = [];
            foreach($headersGroup as  $data) {
                foreach($data as $value) {
                    $key = snake_case(strtolower($value));
                    $key = str_replace('-','_',$key);
                    $unformattedVariantAttributes[$key]  = $value;
                }
            }
            $this->unformattedVariantAttributes = $unformattedVariantAttributes;
        } catch (\Exception $e) {
            set_log_extra('unformated_headingRowCount', $headingRowCount);
            set_log_extra('unformated_rawHeaders', $rawHeaders);
            set_log_extra('unformated_headersGroup', $headersGroup);
            throw $e;
        }
    }

    /**
     * Format Sheet Data
     *
     * @param array $sheetData
     * @return array
     */
    public function formatSheetData($sheetData=[]) {
        $sheetDataFormatted = array_map(function($data){
            $formattedData = [];
            foreach($data as $key=>$value) {
                $key = str_replace('*','',$key);
                $formattedData[$key] = $value;
            }
            return $formattedData;
        },$sheetData);
        return $sheetDataFormatted;
    }
    /**
     * Validate Image Url
     * @param string $imageUrl
     * @return string
     */
    private function validateImageUrl($imageUrl) {
        $return = ['isValid' => true, 'message' => 'Ok'];
        if (!empty($imageUrl)) {
            // check lenght string
            if (strlen($imageUrl) > self::MAX_LENGTH_IMAGE_URL) {
                $return['isValid'] = false;
                $return['message'] = 'Image link exceeds 255 characters';
                return $return;
            }
            $client = new Client();
            try {
                $response = $client->request('GET', $imageUrl, ['timeout' => 20]);
                if ($response->getStatusCode() == 200) {
                    // Validate if the url is actually an image url by validating the header
                    $imageHeader = array_change_key_case(get_headers($imageUrl, 1));
                    if (!isset($imageHeader["content-length"]) && !(isset($imageHeader["x-powered-by"]) && $imageHeader["x-powered-by"] === "PrestaShop Webservice")) {
                        $return['isValid'] = false;
                        $return['message'] = 'The file size is unknown, please change to another image';
                    }
                } else {
                    $statusCode = $response->getStatusCode();
                    $return['isValid'] = false;
                    $return['message'] = sprintf("Not able to fetch file header information.Status code [%s] returned, please change to another image",$statusCode);
                }
            } catch (ConnectException $e) {
                // Connection exceptions are not caught by RequestException
                $return['isValid'] = false;
                $return['message'] = "Networking error(connection timeout, DNS errors, etc).Failed to get header information";

            } catch (RequestException $e) {
                $return['isValid'] = false;
                $return['message'] = "Networking error(connection timeout, DNS errors, etc).Failed to get header information";
            }
        }
        return $return;
    }

    private function getVariantInSheetData($sku, array $sheetData): array {

        $result = [];
        // get variants && check FIELD required variant;
        $variants = array_filter($sheetData, function ($item) use ($sku) {
            return isset($item['associated_sku']) && $item['associated_sku'] == $sku && $this->checkRequiredField($item);
        });
        if (empty($variants)) {
            return $result;
        }
        $result = reset($variants);
        return $result;
    }

    private function checkRequiredField(array $item): bool {

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($item[$field])) {
                return false;
            }
        }
        return true;
    }

    private function getIntegrationIdByIntegrationName($intergrationName) {

        $integrationId = null;
        switch (strtoupper($intergrationName)) {
            case "LAZADA":
                $integrationId = Integration::LAZADA;
                break;
            case "SHOPIFY":
                $integrationId = Integration::SHOPIFY;
                break;
            case "SHOPEE":
                $integrationId = Integration::SHOPEE;
                break;
            case "QOO10":
                $integrationId = Integration::QOO10;
                break;
            case "QOO10_LEGACY":
                $integrationId = Integration::QOO10_LEGACY;
                break;
            case "WOOCOMMERCE":
                $integrationId = Integration::WOOCOMMERCE;
                break;
            case "AMAZON":
                $integrationId = Integration::AMAZON;
                break;
            case "REDMART":
                $integrationId = Integration::REDMART;
                break;
            case "VEND":
                $integrationId = Integration::VEND;
                break;
            case "XERO":
                $integrationId = Integration::XERO;
                break;
            case "IHUB":
                $integrationId = Integration::IHUB;
                break;
            case "PRESTASHOP":
                $integrationId = Integration::PRESTASHOP;
                break;
        }
        return $integrationId;

    }
    private function getRegionByIntegrationName($regionName) {

        $regionId = null;
        $regionName = strtolower($regionName);

        switch (strtolower($regionName)) {
            case "global":
                $regionId = Region::GLOBAL;
                break;
            case "sg":
                $regionId = Region::SINGAPORE;
                break;
            case "my":
                $regionId = Region::MALAYSIA;
                break;
            case "id":
                $regionId = Region::INDONESIA;
                break;
        }
        return $regionId;
    }

    private function isProductNotHasVariant($sku, array $sheetData): bool {

        // get variants && check FIELD required variant;
        $variants = array_filter($sheetData, function ($item) use ($sku) {
            return !empty($item['associated_sku']) && $item['associated_sku'] == $sku;
        });
        if (empty($variants)) {
            return true;
        }
        return false;
    }

    private function getAllVariantInSheetData($sku, array $sheetData): array {

        $result = [];
        // get variants && check FIELD required variant;
        $variants = array_filter($sheetData, function ($item) use ($sku) {
            return isset($item['associated_sku']) && $item['associated_sku'] == $sku && $this->checkRequiredField($item);
        });
        if (empty($variants)) {
            return $result;
        }
        return $variants;
    }

}
