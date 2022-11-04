<?php

namespace App\Jobs;

use App\Constants\HexColor;
use App\Constants\JobStatus;
use App\Models\ExportExcelTask;
use App\Models\Shop;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductExportExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout ;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries ;

    protected $task;

    /**
     * Create a new job instance.
     *
     * @param ExportExcelTask $task
     */
    public function __construct(ExportExcelTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return string
     * @throws \Exception
     */
    public function handle()
    {
        $this->task->status = JobStatus::PROCESSING()->getValue();
        $this->task->save();

        try {
            //$this->task->download = (is_array($this->download())) ? $this->download() : [$this->download()];
            $this->task->download = ['url' => $this->download()];
            $this->task->save();

            $this->task->status = JobStatus::FINISHED()->getValue();
            $this->task->save();

            return $this->task->download;
        } catch (\Exception $exception) {
            $this->task->status = JobStatus::FAILED()->getValue();
            $this->task->downloaded_status = true;
            $this->task->save();
            throw $exception;
        }
    }

    public function download() {
        /* generate headers data - START */
        $colorChoice = HexColor::toArray();
        $headers = [0 => [], 1 => []];
        $headers[1] = [
            ['value' => 'name',                 'style' => ['background' => $colorChoice['PRODUCT_VARIANT'], 'width' => 30], 'required' => 1],
            ['value' => 'sku',                  'style' => ['background' => $colorChoice['PRODUCT_VARIANT'], 'width' => 14], 'required' => 1],
            ['value' => 'associated_sku',       'style' => ['background' => $colorChoice['VARIANT_ONLY'], 'width' => 14], 'required' => 1],
            ['value' => 'short_description',    'style' => ['background' => $colorChoice['PRODUCT_ONLY'], 'width' => 50]],
            ['value' => 'html_description',     'style' => ['background' => $colorChoice['PRODUCT_ONLY'], 'width' => 50], 'required' => 1],
            ['value' => 'price',                'style' => ['background' => $colorChoice['PRODUCT_VARIANT']], 'required' => 1],
            ['value' => 'stock',                'style' => ['background' => $colorChoice['VARIANT_ONLY']], 'required' => 1],
            ['value' => 'length',               'style' => ['background' => $colorChoice['VARIANT_ONLY']], 'required' => 1],
            ['value' => 'width',                'style' => ['background' => $colorChoice['VARIANT_ONLY']], 'required' => 1],
            ['value' => 'height',               'style' => ['background' => $colorChoice['VARIANT_ONLY']], 'required' => 1],
            ['value' => 'weight',               'style' => ['background' => $colorChoice['VARIANT_ONLY']], 'required' => 1],
            ['value' => 'option_1',             'style' => ['background' => $colorChoice['PRODUCT_VARIANT']]],
            ['value' => 'option_2',             'style' => ['background' => $colorChoice['PRODUCT_VARIANT']]],
            ['value' => 'option_3',             'style' => ['background' => $colorChoice['PRODUCT_VARIANT']]],
            ['value' => 'main_image',           'style' => ['background' => $colorChoice['PRODUCT_VARIANT']], 'required' => 1],
        ];

        $xStart = 'A';
        $xEnd = 'O';
        $headers[0][] = [
            'coordinate' => $xStart . '1',
            'value'      => 'basic_information',
            'style'      => [
                'range'      => $xStart . '1:' . $xEnd . '1',
                'background' => $colorChoice['BASIC'],
                'alignment'  => 'center',
                'width'      => 35
            ]
        ];

        foreach ($headers[1] as $key => $header) {
            if (isset($header['required']) && $header['required']) {
                $value = new RichText();
                $value->createText($header['value']);
                $objPayable = $value->createTextRun('*');
                $objPayable->getFont()->setBold(true);
                $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));

                $headers[1][$key]['value'] = $value;
            }
        }
        /* generate headers data - END */


        $shop = Shop::find($this->task->source);
        if( array_key_exists('category_id', $this->task->settings)  ) {
            //download by category
            $categoryId = $this->task->settings['category_id'];
            $with = 'variants';
            //download by integration_id
            if (isset($this->task->settings['integration_id']) && !empty($this->task->settings['integration_id'])) {
                $integrationId = $this->task->settings['integration_id'];
                $with = ['variants','attributes'=> function($query) use ($integrationId)  {
                    $query->whereIntegrationId($integrationId);
                }];
            }
            $products = $shop->products()->where('category_id',$categoryId)->with($with)->get();
        } else {
            //download all product
            $categoryId = '';
            $products = $shop->products()->with('variants')->get();
        }

        $data = [];
        foreach ($products as $product) {
            // change associative array to sequential array
            if (!is_null($product->options) && is_associative($product->options)) {
                $product->options = array_values($product->options);
            }

            $rowData = [
                $product->name,
                $product->associated_sku,
                '',
                $product->short_description,
                $product->html_description,
                $product->price,
                '',
                '',
                '',
                '',
                '',
                ($product->options && count($product->options) >= 1) ? $product->options[0] : '',
                ($product->options && count($product->options) >= 2) ? $product->options[1] : '',
                ($product->options && count($product->options) >= 3) ? $product->options[2] : '',
                $product->main_image,
            ];
            $data[] = $rowData;
            foreach ($product->variants as $variant) {
                $rowData = [
                     $variant->name,
                     $variant->sku,
                     $product->associated_sku,
                     '',
                     '',
                     $variant->price,
                     $variant->stock,
                     $variant->length,
                     $variant->width,
                     $variant->height,
                     $variant->weight,
                     $variant->option_1,
                     $variant->option_2,
                     $variant->option_3,
                     $variant->main_image,
                ];
                $data[] = $rowData;
            }
        }

        if ($products->count() === 0) {
            $data[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        $filename = 'export/export_products_' . Carbon::now()->timestamp . '.xlsx';
        Excel::store(new GenerateExcel('Create Products,'. $categoryId, $headers, $data,
                ['header_style' => ['bold' => true, 'auto_size' => true],
                'body_style' => ['warp_text' => true], 'freeze_pane' => 'D3']),
                $filename, 'excel', \Maatwebsite\Excel\Excel::XLSX);

        return Storage::disk('excel')->url($filename);
    }
}
