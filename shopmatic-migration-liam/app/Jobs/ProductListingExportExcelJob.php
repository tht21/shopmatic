<?php

namespace App\Jobs;

use App\Constants\CategoryAttributeLevel;
use App\Constants\HexColor;
use App\Constants\JobStatus;
use App\Models\Account;
use App\Models\Category;
use App\Models\ExportExcelTask;
use App\Models\Integration;
use App\Models\Product;
use App\Models\Shop;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductListingExportExcelJob implements ShouldQueue
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
     * @return void
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

    /**
     * Download Excel
     *
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
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

        $shop = Shop::find($this->task->source);

        $accountId = $this->task->settings['account_id'];
        /** @var Account $account */
        $account = $shop->accounts()->where('id', $accountId)->first();

        if (is_null($account)) {
            throw new \Exception('Account not found. ');
        }

        $attributeExist = false;
        $xStart = $xEnd;
        $xStart++;

        $attributes = [
            'attributes' => [],
            'logistics' => [],
            'prices' => [],
            'options' => null
        ];

        /* get constant attributes from integration */
        $attributes['attributes'] = $account->getIntegrationAttributes();
        /* get dynamic logistic data from account */
        $attributes['logistics'] = $account->getLogisticsAttributes();
        /* get integration available price type from account */
        $attributes['prices'] = $account->getPriceTypes();
        /* get level of options available for the integration */
        $attributes['options'] = ($account->integration->features[$account->region_id]['products']['options_level']) ?? null;

        $attributes = collect($attributes['attributes']);
        $formattedAttributes = [];

        // if dont have attributes, skip it
        if (count($attributes) > 0) {
            // Order: general-required, general-optional, sku-required, sku-optional
            $formattedAttributes = array_merge(
            // attributes group: general (main product), required attributes on the top
                $attributes->filter(function ($formattedAttribute) {
                    return $formattedAttribute['level'] === CategoryAttributeLevel::GENERAL()->getValue();
                })->sortByDesc('required')->toArray(),
                // attributes group: sku (variants), required attributes on the top
                $attributes->filter(function ($formattedAttribute) {
                    return $formattedAttribute['level'] === CategoryAttributeLevel::SKU()->getValue();
                })->sortByDesc('required')->toArray()
            );

            foreach ($formattedAttributes as $attribute) {
                $attributeExist = true;
                $value = $attribute['name'];
                if ($attribute['required'] === 1) {
                    $value = new RichText();
                    $value->createText($attribute['name']);
                    $objPayable = $value->createTextRun('*');
                    $objPayable->getFont()->setBold(true);
                    $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));
                }
                $headers[1][] = [
                    'value' => $value,
                    'style' => [
                        'background' => ($attribute['level'] === CategoryAttributeLevel::GENERAL()->getValue() ? $colorChoice['PRODUCT_ONLY'] : $colorChoice['VARIANT_ONLY'])
                    ]
                ];
                $xEnd++;
            }

            // if this account has attributes, create top header
            if ($attributeExist) {
                $integrationName = Integration::INTEGRATIONS[$account->integration_id];
                $headers[0][] = [
                    'coordinate' => $xStart . '1',
                    'value'      => $integrationName,
                    'style'      => [
                        'range'      => $xStart . '1:' . $xEnd . '1',
                        'background' => $colorChoice[strtoupper(snake_case($integrationName))],
                        'alignment'  => 'center'
                    ]
                ];
            }
        }

        /* generate headers data - END */

        $products = $shop->products()->whereHas('variants', function ($query) use ($accountId) {
            $query->whereHas('listings', function($query) use ($accountId) {
                $query->where('account_id', $accountId);
            });
        })
        ->with([
            'variants' => function($query) use ($accountId) {
                $query->whereHas('listings', function($query) use ($accountId) {
                    $query->where('account_id', $accountId);
                });
            }
        ])
        ->get();

        $data = [];

        $productCategories = $products->groupBy('category_id')->all();
        ksort($productCategories);
        foreach($productCategories as $categoryId => $products) {
            //get category sub header
            $data[] = [
                'value'      => Category::find($categoryId) ? Category::find($categoryId)->breadcrumb : '',
                'style'      => [
                    'range'      => 'A'. (3 + count($data)).':' . $xEnd. ( 3 + count($data) ) ,
                    'background' => $colorChoice['SECONDARY'],
                    'alignment'  => 'left'
                ]
            ];

            /** @var Product $product */
            foreach ($products as $product) {
                $options = array_values($product->options);
                //insert product row
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
                    count($product->options) >= 1 ? $options[0] : '',
                    count($product->options) >= 2 ? $options[1] : '',
                    count($product->options) >= 3 ? $options[2] : '',
                    $product->main_image,
                ];

                //insert product attribute
                $attributeData = array_fill(0,count($formattedAttributes), '');;
                foreach ($product->listings[0]->attributes->where('product_variant_id', null) as $attribute) {
                    $index = array_search($attribute->name,array_column($formattedAttributes, 'name'));
                    if ( $index ) {
                        $attributeData[$index] = $attribute->value;
                    }
                }
                $data[] = array_merge($rowData,$attributeData);

                foreach ($product->variants as $variant) {
                    //insert variant row
                    $rowData = [
                        $variant->name,
                        $variant->sku,
                        $product->associated_sku,
                        '',
                        '',
                        $variant->price,
                        $variant->listings[0]->stock,
                        $variant->length,
                        $variant->width,
                        $variant->height,
                        $variant->weight,
                        $variant->option_1,
                        $variant->option_2,
                        $variant->option_3,
                        $variant->main_image,
                    ];

                    //insert variant attribute
                    $attributeData = array_fill(0,count($formattedAttributes), '');
                    foreach ($variant->listings[0]->attributes as $attribute) {
                        $index = array_search($attribute->name,array_column($formattedAttributes, 'name'));
                        if ( $index ) {
                            $attributeData[$index] = $attribute->value;
                        }

                    }
                    $data[] = array_merge($rowData,$attributeData);
                }
            }
        }

        $filename = 'export/export_product_listing_' . Carbon::now()->timestamp . '.xlsx';
        Excel::store(new GenerateExcel(substr($account->name, 0, 28).'...' , $headers, $data,
            ['header_style' => ['bold' => true, 'auto_size' => true],
                'body_style' => ['warp_text' => true]]),
            $filename, 'excel', \Maatwebsite\Excel\Excel::XLSX);

        return Storage::disk('excel')->url($filename);
    }
}
