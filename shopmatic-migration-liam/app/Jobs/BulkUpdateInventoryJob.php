<?php

namespace App\Jobs;

use App\Constants\ExcelType;
use App\Constants\JobStatus;
use App\Models\ProductImportTask;
use App\Models\ProductInventory;
use App\Utilities\Excel\ExtractExcel;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class BulkUpdateInventoryJob implements ShouldQueue
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
    protected $ip_address;

    /**
     * Create a new job instance.
     *
     * @param ProductImportTask $task
     */
    public function __construct(ProductImportTask $task, $ip_address = null)
    {
        $this->task = $task;
        $this->ip_address = $ip_address;
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

        switch ($this->task->source_type) {
            case ExcelType::UPDATE_INVENTORY()->getValue():
                return $this->handleCsv(ExcelType::UPDATE_INVENTORY()->getValue());
            default:
                throw new \Exception('Unhandled source for import Inventory task - ' . $this->task->source_type);
        }
    }

    /**
     * @param null $excelType
     * @throws \Exception
     */
    private function handleCsv($excelType = null)
    {

        switch ($excelType) {
            case ExcelType::UPDATE_INVENTORY()->getValue():
                $headings = (new HeadingRowImport(1))->toArray($this->task->source, 'excel', \Maatwebsite\Excel\Excel::CSV)[0][0];
                $extractedExcel = new ExtractExcel(1);
                Excel::import($extractedExcel, $this->task->source, 'excel', \Maatwebsite\Excel\Excel::CSV);
                $sheetsData = $extractedExcel->getSheetsData();
                if (!empty($sheetsData)) {
                    $dataRows = $sheetsData[0];
                    $this->task->total_products = count($dataRows);
                    $this->task->save();
                    $this->updateInventory($headings, $dataRows);

                    $this->task->status = JobStatus::FINISHED();
                    $this->task->save();
                }
                break;
        }
    }

    /**
     *
     * Update inventory based on given data extracted from excel
     *
     * @param array $headers
     * @param array $dataRows
     * @throws \Exception
     */
    public function updateInventory($headers, $dataRows)
    {
        $shop = $this->task->shop;
        $user = $this->task->user;
        if (empty($shop)) {
            throw new \Exception('There is no shop selected');
        }

        foreach ($dataRows as $data) {
            try {

                // skip empty row
                if (empty(array_filter($data))) continue;
                $inventory = 0;
                if (isset($data['sku'])) {
                    $inventory = $shop->inventories()->whereSku($data['sku'])->first();
                }
                // skip if stock is not int
                if (!is_int($data['stock'])) {
                    continue;
                }

                if ($inventory) {
                    $change = $data['stock'] - $inventory->stock;
                    // This will call the sync inventory job already
                    Log::info("Inventory with SKU: " .$data['sku']. " is manually updated by IP address: " .$this->ip_address);
                    $inventory->modifyInventory($change, 'none', 'Manual change by ' . $user->name);
                } else {
                    if(array_key_exists('is_create_inventory', $this->task->settings) && $this->task->settings['is_create_inventory'] == 'true') {
                        $inventory = ProductInventory::create([
                            'shop_id' => $this->task->shop_id,
                            'sku' => $data['sku'],
                            'name' => isset($data['Product Name']) ? $data['Product Name'] : '',
                            'stock' => $data['stock'],
                            'enabled' => true
                        ]);

                        $change = $data['stock'] - $inventory->stock;
                        // This will call the sync inventory job already
                        Log::info("Inventory with SKU: " .$data['sku']. " is manually updated by IP address: " .$this->ip_address);
                        $inventory->modifyInventory($change, 'none', 'Manual change by ' . $user->name);
                    }
                }
            } catch (\Exception $e) {
                set_log_extra('data', $data);
                set_log_extra('auth', $user);
                throw $e;
            }
        }
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
        set_log_extra('auth', $this->task->user);
        Log::error($exception);
        $this->task->messages = $this->setTaskMessages($this->task->messages, $exception->getMessage());
        $this->task->status = JobStatus::FAILED()->getValue();
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
            $messages = [$message];
        } else {
            $messages = array_merge((array)$this->task->messages, [$message]);
        }
        return $messages;
    }
}
