<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExportExcelTask;
use App\Constants\JobStatus;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use App\Utilities\Excel\GenerateCsv;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;

class InventoryDownloadCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ExportExcelTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $this->task->status = JobStatus::PROCESSING()->getValue();
        $this->task->save();
        try {
            $this->task->download = ['url' => $this->download()];
            $this->task->save();

            $this->task->status = JobStatus::FINISHED()->getValue();
            $this->task->save();

            return $this->task->download;
        } catch (\Exception $e) {
            $this->task->status = JobStatus::FAILED()->getValue();
            $this->task->messages = $e->getMessage();
            $this->task->downloaded_status = true;
            $this->task->save();
            throw $e;
        }
    }

    /**
     * Generate csv for inventories based on the settings/request/filters
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download() {

        $search  = $this->task->settings['search'];
        $enabled  = $this->task->settings['enabled'];
        $stock  = $this->task->settings['stock'];
        $stockOpt  = $this->task->settings['stock_opt'];
        $optList = ['=', '<=', '>=', '!=', '<', '>'];
        $keys = ['sku' => 1, 'stock' => 2];
        $data = [];

        $headers[0] = [
            ['value' => 'Product Name',      'style' => ['width' => 60]],
            ['value' => 'Sku',      'style' => ['width' => 30]],
            ['value' => 'Stock',    'style' => ['width' => 30]]
        ];
        $title = 'inventories_' . Carbon::now()->timestamp;
        $filename = 'export/'.$title.'.csv';


        if (!is_null($stockOpt) && !in_array($stockOpt, $optList)) {
            // return $this->respondBadRequestError('Invalid stock option argument.');
            $this->task->status = JobStatus::FAILED()->getValue();
            $this->task->messages = "Invalid stock option argument.";
            $this->task->downloaded_status = true;
            $this->task->save();
        }

        $shop = Shop::find($this->task->source);
        $query = $shop->inventories();

        if (!empty($search)) {
            $query = $query->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('sku', 'LIKE', '%' . $search . '%');
            });
        }

        if (!is_null($stock) && !is_null($stockOpt)) {
            $query = $query->where('stock', $stockOpt, $stock);
        }

        if (!is_null($enabled) && $enabled !== '') {
            $query = $query->where('enabled', $enabled);
        }

        $query->orderBy('id','asc')->chunkById(1000,function($chunk) use (&$data,$keys) {
            foreach ($chunk as $inventory) {
                if ($inventory->name == $inventory->sku) {
                    if (isset($inventory->variants()->first()->product)) {
                        $product_name = $inventory->variants()->first()->product->name;
                    } else {
                        $product_name = '';
                    }
                } else {
                    $product_name = $this->getNameProductInventory($inventory->name);
                }
                $data[] = [$product_name, $inventory->sku, $inventory->stock];
            }
        });

        Excel::store(new GenerateExcel($title, $headers, $data,
            ['header_style' => ['bold' => true, 'auto_size' => true],'body_style' => ['warp_text' => true]]),
            $filename, 'excel', \Maatwebsite\Excel\Excel::CSV);

        return Storage::disk('excel')->url($filename);
      }

    private function getNameProductInventory($name) {

        if (empty($name)) {
            return $name;
        }
        $string = substr($name, (int)(strlen($name) / 2));
        $string = trim($string, '-');
        $string = trim($string);
        if (empty($string)) {
            return $name;
        }
        if (substr_count($name, $string) == 2) {
            return $string;
        }
        return $name;
    }
}
