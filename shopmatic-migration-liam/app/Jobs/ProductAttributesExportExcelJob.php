<?php

namespace App\Jobs;
use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\HexColor;
use App\Constants\JobStatus;
use App\Models\ExportExcelTask;
use App\Models\Product;
use App\Models\Account;
use App\Models\Integration;
use App\Models\Category;
use App\Models\IntegrationCategoryAttribute;
use App\Models\ProductAttribute;
use App\Models\Shop;
use App\Utilities\Excel\GenerateExcel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Str;
use App\Models\Region;
use App\Models\IntegrationCategory;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductAttributesExportExcelJob implements ShouldQueue
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
     * @return string|null
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
            Log::error($exception);
            $this->task->status = JobStatus::FAILED()->getValue();
            $this->task->downloaded_status = true;
            $this->task->save();
            throw $exception;
        }
    }

    public function download()
    {
        /* generate headers data - START */
        $colorChoice = HexColor::toArray();
        $headers = [0 => [], 1 => []];
        $headers[1] = [
                        ['value' => 'name','style' => ['background' => $colorChoice['PRODUCT_VARIANT'], 'width' => 30], 'required' => 1],
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

        for ($i = 1;$i <= 12; $i++) {
            $headers[1][] = ['value' => 'image_'.$i, 'style' => ['background' => $colorChoice['PRODUCT_VARIANT']]];
        }

        $xStart = 'A';
        $xEnd = 'AA';
        $headers[0][] = [
            'coordinate' => $xStart . '1',
            'value'      => 'Basic Information',
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
        $xStart = $xEnd;
        $xStart++;
        $categoryId = $accountId = $integrationCategoryId = $regionShortCode = '';
        $data = [];
        if (array_key_exists('category_id', $this->task->settings)) {
            $categoryId = $this->task->settings['category_id'];
            $accountId = $this->task->settings['account_id'];
            $integrationCategoryId = $this->task->settings['integration_category_id'] ?? null;
            $shop = Shop::find($this->task->source);
            $account = $shop->accounts()->where('id', $accountId)->first();
            $account = $account->append('has_category');
            if (is_null($account)) {
                throw new \Exception('Account not found. ');
            }
            /* Region details based on region id */
            $region = Region::where('id', $account->region_id)->first();
            if (!$region) {
                set_log_extra('productAttributesExport', $account);
                throw new \Exception('Region not found.');
            }
            if ($account->has_category && $account->has_category !== 'account' && empty($integrationCategoryId)) {
                throw new \Exception('Integration category Id Is Empty');
            }
            /* Region ShortCode */
            $regionShortCode = $region->shortcode;

            /* Account integration id */
            $accounIntegrationId = $account->integration_id;
            /* Integration category */
            if (!empty($integrationCategoryId)) {
                $integrationCategory = IntegrationCategory::find($integrationCategoryId);
                /* Integration categories attributes */
                $integrationCategoriesAttributes = IntegrationCategoryAttribute::whereIntegrationCategoryId($integrationCategoryId)->get();
            }
            /* Initialized attributes array */
            $integrationAttributes = [
                'attributes' => [],
                'logistics' => [],
                'prices' => [],
                'options' => null
            ];
            /* get constant attributes from integration */
            $integrationAttributes['attributes'] = $account->getIntegrationAttributes();
            /* get dynamic logistic data from account */
            $integrationAttributes['logistics'] = $account->getLogisticsAttributes();
            /* get integration available price type from account */
            $integrationAttributes['prices'] = $account->getPriceTypes();
            /* get level of options available for the integration */
            $integrationAttributes['options'] = ($account->integration->features[$account->region_id]['products']['options_level']) ?? null;
            $integrationAttributes['attributes']= array_merge($integrationAttributes['attributes'], isset($integrationCategoriesAttributes) ? $integrationCategoriesAttributes->toArray() : []);

            $logistics = [];
            if ($accounIntegrationId === Integration::SHOPEE) {
                $logistics = collect($integrationAttributes['logistics']);
            }

            $attributes = collect($integrationAttributes['attributes']);
            $attributeExist = false;
            if (!empty($attributes)) {
                $formattedAttributes = [];
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
                $attributesOptionRange = [];
                $attributeXStart = 'A';
                foreach ($formattedAttributes as $attribute) {
                    $attributeExist = true;
                    $value = $attribute['name'];
                    if ($attribute['required'] === 1 || ($account->integration_id === Integration::LAZADA && $attribute['name'] === 'brand')) {
                        $value = new RichText();
                        $value->createText($attribute['name']);
                        $objPayable = $value->createTextRun('*');
                        $objPayable->getFont()->setBold(true);
                        $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));
                    }

                    // Check whether has category
                    if (!empty($integrationCategory)) {
                        // format value append with integration category id
                        if ($attribute['level'] !== CategoryAttributeLevel::GENERAL()->getValue()) {
                            $value = $this->formatValue($value, '_'.$integrationCategory->id.'_v', ['color' => $colorChoice['VARIANT_ONLY']]);
                        } else {
                            $value = $this->formatValue($value, '_'.$integrationCategory->id, ['color' => $colorChoice['PRODUCT_ONLY']]);
                        }
                    } else {
                        // format value append without integration category id
                        if ($attribute['level'] !== CategoryAttributeLevel::GENERAL()->getValue()) {
                            $value = $this->formatValue($value, '_v', ['color' => $colorChoice['VARIANT_ONLY']]);
                        } else {
                            $value = $this->formatValue($value, '', ['color' => $colorChoice['PRODUCT_ONLY']]);
                        }
                    }

                    // Convert options data based on integration
                    $optionsData = $attribute['data'];
                    if (!empty($attribute['data']) && in_array($attribute['type'], [CategoryAttributeType::SINGLE_SELECT()->getValue(), CategoryAttributeType::OPTION()->getValue(),CategoryAttributeType::MULTI_ENUM()->getValue(), CategoryAttributeType::CHECKBOX()->getValue()])) {
                        if ($account->integration_id === Integration::LAZADA) {
                            if ($attribute['name'] !== 'brand') {
                                $optionsData = array_column($attribute['data'], 'name');
                            }
                        } elseif ($account->integration_id === Integration::QOO10_LEGACY || (isset($integrationCategory) && $integrationCategory->integration_id === Integration::QOO10)) {
                            // Qoo10 legacy will have different format (Can refer to constant file)
                            if (is_array($optionsData[0])) {
                                $optionsData = array_column($attribute['data'], 'value');
                            }
                        } elseif ($account->integration_id === Integration::WOOCOMMERCE) {
                            if (is_array($optionsData[0])) {
                                $attribData = array_column($attribute['data'],'text','value');
                                if (!empty($attribData)) {
                                    $optionsData = $attribData;
                                }
                            }
                         }
                    }
                    if ((!empty($attribute['data'])) && in_array($attribute['type'], [CategoryAttributeType::SINGLE_SELECT()->getValue(), CategoryAttributeType::MULTI_SELECT()->getValue(),CategoryAttributeType::RADIO()->getValue(),CategoryAttributeType::OPTION()->getValue(),CategoryAttributeType::SINGLE_SELECT_OR_INPUT()->getValue(),CategoryAttributeType::TEXT()->getValue(),CategoryAttributeType::MULTI_ENUM()->getValue(), CategoryAttributeType::NUMERIC()->getValue()])) {
                            $attributeXEnd = $attributeXStart.count($attribute['data']);
                            $attributeOptionRange = $attributeXStart.'1:'.$attributeXEnd;
                            $attributesOptionRange[$attribute['name']] = $attributeOptionRange;
                            $attributeXStart++;

                            if ($account->integration_id === Integration::LAZADA) {
                                if ($attribute['name'] !== 'brand') {
                                    $optionsData = array_column($attribute['data'], 'name');
                                }
                            } elseif ($account->integration_id === Integration::QOO10_LEGACY) {
                                // Qoo10 legacy will have different format (Can refer to constant file)
                                if (is_array($optionsData[0])) {
                                    $optionsData = array_column($attribute['data'], 'value');
                                }
                            }
                    }
                    $headers[1][] = [
                        'value' => $value,
                        'style' => [
                            'background' => ($attribute['level'] === CategoryAttributeLevel::GENERAL()->getValue() ? $colorChoice['PRODUCT_ONLY'] : $colorChoice['VARIANT_ONLY'])
                        ],
                        'type' => $attribute['type'],
                        'options' => $optionsData,
                    ];
                    $xEnd++;
                }
                // If this account has attributes, create top header
                if ($attributeExist) {
                    $integrationName = Integration::INTEGRATIONS[$account->integration_id];
                    $headers[0][] = [
                        'coordinate' => $xStart . '1',
                        'value'      => !empty($integrationCategoryId) ? ($integrationName . ' ' . $regionShortCode . '-' . $integrationCategoryId) : ($integrationName . ' ' . $regionShortCode),
                        'style'      => [
                            'range'      => $xStart . '1:' . $xEnd . '1',
                            'background' => $colorChoice[strtoupper(snake_case($integrationName))],
                            'alignment'  => 'center'
                        ]
                    ];
                }
            }
            $logisticsAttributesOptionRange = [];
            // if dont have logistics, skip it (Currently only support for shopee)
            $logisticExist = false;
            if (count($logistics) > 0) {
                foreach ($logistics as $logistic) {
                    if ($logistic['enabled']) {
                        $logisticExist = true;

                        $value = new RichText();
                        $value->createText(snake_case($logistic['logistic_name']));
                        $objPayable = $value->createTextRun('*');
                        $objPayable->getFont()->setBold(true);
                        $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));

                        // As this is currently only for Shopee, the integrationCategory won't be null
                        $value = $this->formatValue($value, '_'.$integrationCategory->id.'_'.$logistic['logistic_id'].'_l1', ['color' => $colorChoice['PRODUCT_ONLY']]);

                        $headers[1][] = [
                            'value' => $value,
                            'style' => ['background' => $colorChoice['PRODUCT_ONLY']],
                            'type' => CategoryAttributeType::OPTION()->getValue(),
                            'options' => ['No', 'Yes']
                        ];
                        $xEnd++;
                        $logisticName = str_replace(array( '(', ')' ), '', $logistic['logistic_name']);
                        /** Defining the option range */
                        $attributeXEnd = $attributeXStart.'2';
                        $attributeOptionRange = $attributeXStart.'1:'.$attributeXEnd;
                        $logisticsAttributesOptionRange[$logistic['logistic_id']] = ['option_range'=>$attributeOptionRange,'options'=>['No','Yes'],'name'=>$logisticName];
                        $attributeXStart++;

                        /** Defining the option range */
                        $attributeXEnd = $attributeXStart.'2';
                        $attributeOptionRange = $attributeXStart.'1:'.$attributeXEnd;
                        $logisticsAttributesOptionRange['is_free_'.$logistic['logistic_id']] = ['option_range'=>$attributeOptionRange,'options'=>[false,true],'name'=>$logisticName];
                        $attributeXStart++;

                        // Additional logistic attribute column
                        $additionalLogistics = [];
                        if ($accounIntegrationId === Integration::SHOPEE) {
                            $value = new RichText();
                            $value->createText('is_free');
                            $objPayable->getFont()->setBold(true);
                            $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));
                            $value = $this->formatValue($value, '_'.$integrationCategory->id.'_'.$logistic['logistic_id'].'_l2', ['color' => $colorChoice['PRODUCT_ONLY']]);

                            $additionalLogistics[] = ['value' => $value, 'type' => CategoryAttributeType::OPTION()->getValue(), 'options' => [false, true]];
                            // Check fee type
                            if (in_array($logistic['fee_type'], ['CUSTOM_PRICE', 'SIZE_SELECTION'])) {
                                $text = ($logistic['fee_type'] === 'CUSTOM_PRICE') ? 'shipping_fee' : 'size_id';

                                $value = new RichText();
                                $value->createText($text);
                                $objPayable->getFont()->setBold(true);
                                $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));
                                $value = $this->formatValue($value, '_'.$integrationCategory->id.'_'.$logistic['logistic_id'].'_l3', ['color' => $colorChoice['PRODUCT_ONLY']]);

                                $additionalLogistics[] = ['value' => $value];

                                /** Defining the option range */
                                $attributeXEnd = $attributeXStart.'2';
                                $attributeOptionRange = $attributeXStart.'1:'.$attributeXEnd;
                                $logisticsAttributesOptionRange[$text] = ['option_range'=>$attributeOptionRange,'options'=>[false,true],'name'=>$logisticName];
                                $attributeXStart++;
                            }
                        }

                        if (count($additionalLogistics)) {
                            foreach ($additionalLogistics as $additionalLogistic) {
                                $headers[1][] = [
                                    'value' => $additionalLogistic['value'],
                                    'style' => ['background' => $colorChoice['PRODUCT_ONLY']],
                                    'type' => $additionalLogistic['type'] ?? CategoryAttributeType::TEXT()->getValue(),
                                    'options' => $additionalLogistic['options'] ?? []
                                ];

                                $xEnd++;
                            }
                        }
                    }
                }
                // if this integration's category has logistic, create top header
                if ($logisticExist) {
                    $integrationName = Integration::INTEGRATIONS[$accounIntegrationId];
                    $headers[0][] = [
                        'coordinate' => $xStart . '1',
                        'value'      => $integrationName.' '.$regionShortCode.'-'.$integrationCategoryId,
                        'style'      => [
                            'range'      => $xStart . '1:' . $xEnd . '1',
                            'background' => $colorChoice[strtoupper(snake_case($integrationName))],
                            'alignment'  => 'center'
                        ]
                    ];
                }
            }

            $sellectFieldImage = (new ProductImage())->getFillable();
            $sellectFieldPrice = (new ProductPrice())->getFillable();
            $sellectFieldAttribute = (new ProductAttribute())->getFillable();
            $sellectFieldVariant = (new ProductVariant())->getFillable();
            //Query to fetch product
            $products = $shop->products()->where('category_id', $categoryId)
                                ->whereDoesntHave('listings', function ($query) use ($accountId) {
                                    $query->whereAccountId($accountId);
                                })
                                ->with([
                                    'allImages' => function($query) use ($account)  {
                                        $query->whereNull('product_variant_id')->whereNull('source_account_id')->whereNull('product_listing_id')
                                            ->orWhere(function (Builder $query) use ($account) {
                                                $query->where('region_id',$account->region_id);
                                            });
                                    },
                                    'listingImages',
                                    'listings',
                                    'prices',
                                    'attributes' => function($query) use ($account)  {
                                        $query->whereIntegrationId($account->integration_id);
                                    },
                                    'variants',
                                    'variants.images' => function($query) use ($account) {
                                        $query->whereIntegrationId($account->integration_id);
                                    },
                                    /*'variants.allImages' => function($query) use ($account) {
                                        $query->whereNull('source_account_id')->whereNull('product_listing_id')
                                            ->orWhere(function (Builder $query) use ($account) {
                                                $query->where('region_id',$account->region_id);
                                            });
                                    },*/
                                    'variants.allImages' => function($query) use ($account) {
                                        $query->where(function (Builder $query) use ($account){
                                            $query->where(function (Builder $query) use ($account){
                                                $query->whereNull('source_account_id')->whereNull('product_listing_id');
                                            })->orWhere(function (Builder $query) use ($account) {
                                                $query->where('region_id',$account->region_id);
                                            });
                                        });
                                    },
                                    'variants.listingImages',
                                    /*'variants.prices' => function ($query) use ($account) {
                                        $query->where(function (Builder $query) use ($account) {
                                            $query->whereRegionId($account->region_id)->whereIntegrationId($account->integration_id);
                                        })->orWhere(function (Builder $query) use ($account) {
                                            $query->whereNull('region_id')->whereNull('integration_id');
                                        })->orderBy('integration_id', 'asc')->orderBy('region_id', 'asc');
                                    },*/
                                    'variants.prices' => function ($query) use ($account) {
                                        $query->where(function (Builder $query) use ($account) {
                                                $query->where(function (Builder $query) use ($account) {
                                                        $query->whereRegionId('region_id')->whereIntegrationId('integration_id');
                                                    })->orWhere(function (Builder $query) use ($account) {
                                                        $query->whereNull('region_id')->whereNull('integration_id');
                                                });
                                        })->orderBy('integration_id', 'asc')->orderBy('region_id', 'asc');
                                    },
                                    'variants.attributes' => function ($query) use ($account) {
                                        $query->whereIntegrationId($account->integration_id)->whereNull('product_listing_id');
                                    }])
                                ->where(function (Builder $query) use ($account, $integrationCategoryId) {
                                    return $query->whereHas('attributes',
                                        function($query) use ($account, $integrationCategoryId)  {
                                            if (!empty($integrationCategoryId)) {
                                                $query->whereIntegrationId($account->integration_id)
                                                    ->whereName('integration_category_id')
                                                    ->whereRegionId($account->region_id)
                                                    ->whereValue($integrationCategoryId);
                                            } else {
                                                $query->whereIntegrationId($account->integration_id);
                                            }
                                        })->orWhere(function ($query) use ($account) {
                                            $query->whereDoesntHave('attributes', function (Builder $query) use ($account) {
                                                $query->whereIntegrationId($account->integration_id)
                                                ->whereRegionId($account->region_id)
                                                ->whereName('integration_category_id');
                                            });
                                        });
                                })->get();

            if (!empty($products)) {
                foreach ($products as $product) {
                    $rowData = [];
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
                        //$product->main_image,
                    ];
                    // main image + 12 images

                    $productAllImages = $product->allImages;
                    if (($productAllImages || $productAllImages->isEmpty()) && $product->listings->isNotEmpty()) {
                        $productAllImages = $product->listingImages;
                    }
                    $productImageFirst = $productAllImages->first();
                    $productLinkImage = !empty($product->main_image) ? $product->main_image : ($productImageFirst ? $productImageFirst->image_url : '');

                    $itemImages =  [];
                    $productImages = array_fill(0, 13, '');
                    for ($i = 0;$i <= 12; $i++) {
                        $itemImage = $productAllImages[$i]->source_url ?? '';
                        if (!empty($itemImage) && !in_array($itemImage,$itemImages)) {
                            $itemImages[$i] = $itemImage;
                        }
                    }
                    if(!empty($itemImages)) {
                        $productImages = array_replace($productImages,$itemImages);
                    }
                    $rowData = array_merge($rowData,$productImages);
                    $attributeData = array_map(function ($attribute) use ($attributesOptionRange,$rowData){
                            if (array_key_exists($attribute['name'],$attributesOptionRange)) {
                                if ($attribute['data'] instanceof Collection) {
                                    $attribute['data'] = $attribute['data']->toArray();
                                }
                                return ['option_name'=> $attribute['name'],'option_range'=> $attributesOptionRange[$attribute['name']],'option'=> '','option_default_data'=>$attribute['data']];
                            } else {
                                return '';
                            }
                    },$formattedAttributes);
                    /** Initialized the attributeData for logistic options  */
                    if ($accounIntegrationId === Integration::SHOPEE && $logisticExist && !empty($logisticsAttributesOptionRange)) {
                        foreach ($logisticsAttributesOptionRange as $key =>$value) {
                            array_push($attributeData,['option_name'=>$key,'option_range'=>$value['option_range'],'option'=>'']);
                        }
                    }
                    // Fetch product attributes based on account integration id
                    $attributes = $product->attributes->whereIn('region_id', [$account->region_id, null])
                                        ->sortBy('region_id')
                                        ->mapWithKeys(function ($item) {
                                            $item['value'] = is_string($item['value']) && strpos($item['value'], '[]') !== false ? str_replace('[]',"",$item['value']) : $item['value'];
                                            return [$item['name'] => $item];
                                        });
                    foreach ($attributes as $attribute) {
                        $index = array_search($attribute->name,array_column($formattedAttributes, 'name'));
                        if ($index !== FALSE) {
                            if (!empty($attribute->value)) {

                                if (array_key_exists($attribute->name, $attributesOptionRange)) {
                                    $attributeValue = $this->isJsonString($attribute->value);
                                    if (isset($attributeData[$index]['option_default_data']) && is_array($attributeData[$index]['option_default_data'])){
                                        $optionDefaultData = array_column($attributeData[$index]['option_default_data'],'name');
                                        if(empty($optionDefaultData)) {
                                            $optionDefaultData = $attributeData[$index]['option_default_data'];
                                        }
                                        $foundKey = array_search(strtolower($attributeValue), array_map('strtolower', $optionDefaultData));
                                        if (FALSE !== $foundKey) {
                                            $attributeValue =  $attributeData[$index]['option_default_data'][$foundKey] ?? '';
                                            $attributeOptionValue = is_array($attributeValue) && isset($attributeValue['name']) ? $attributeValue['name'] : $attributeValue;
                                            $attributeOptionValue = $attributeOptionValue ? $this->isJsonString($attributeOptionValue) :'';
                                            $attributeData[$index]['option'] = $attributeOptionValue;
                                        }
                                    }
                                } else {
                                    $attributeData[$index] = !empty($attribute->value) ?  $this->isJsonString($attribute->value) : '';
                                }
                            }
                        }
                        //Logistic Information For Shopee
                        if ($accounIntegrationId === Integration::SHOPEE && $logisticExist){
                            if ($attribute->name == "logistics" && !empty($attribute->value)) {
                                $logisticsData = json_decode($attribute->value,true);
                                foreach ($attributeData as $key => $value) {
                                    if (!empty($value) && isset($value['option_name'])) {
                                        $optionName = $hayStack = $value['option_name'];
                                        if (Str::startsWith($hayStack, 'is_free_')) {
                                            $logisticId = substr(strrchr($hayStack, "_"), 1);
                                            if (ctype_digit($logisticId)) {
                                                $hayStack = (int)$logisticId;
                                            }
                                        }
                                        $index = array_search($hayStack,array_column($logisticsData,'logistic_id'));
                                        if ($index !== FALSE) {
                                            if(Str::startsWith($optionName, 'is_free_')) {
                                                $attributeData[$key]['option'] = isset($logisticsData[$index]['is_free']) && $logisticsData[$index]['is_free'] ? 'TRUE' : 'FALSE';
                                            }elseif($logisticsData[$index]['enabled']) {
                                                $attributeData[$key]['option'] = 'Yes';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    /** Single Variant Product */
                    if ($product->variants->count() === 1) {
                        $variantFirst = $product->variants->first();
                        $singleVariantMainImage = $variantFirst && !empty($variantFirst->main_image) ? $variantFirst->main_image : $productLinkImage;
                        $variant = [
                            $product->variants[0]->name,
                            $product->variants[0]->sku,
                            '',
                            '',
                            '',
                            $product->variants[0]->price,
                            $product->variants[0]->stock,
                            $product->variants[0]->length,
                            $product->variants[0]->width,
                            $product->variants[0]->height,
                            $product->variants[0]->weight,
                            $product->variants[0]->option_1,
                            $product->variants[0]->option_2,
                            $product->variants[0]->option_3,
                            $singleVariantMainImage,
                            /*'',
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
                            '',*/
                        ];

                        // variant 12 images
                        $variantImages = [];
                        $variantSourceImages = [];
                        $productVariantImages = array_fill(0, 12, '');
                        foreach ($product->variants[0]->images as $index => $image) {
                            $itemVariantImageUrl = $image->image_url ?? '';
                            $variantSourceImages[] = $image->source_url ?? '';

                            if (!empty($itemVariantImageUrl)  && ($singleVariantMainImage !== $itemVariantImageUrl) && !in_array($itemVariantImageUrl,$variantImages)) {
                                $variantImages[$index] = $itemVariantImageUrl;
                            }

                            //$variant[15 + $index] = $image->image_url;
                            // support 12 max images only
                            if ($index >= 11) {
                                break;
                            }
                        }

                        if (!empty($variantImages)) {
                            $variantImages = array_values($variantImages);
                            $productVariantImages = array_replace($productVariantImages,$variantImages);
                        }
                        $variant = array_merge($variant,$productVariantImages);

                        foreach ($rowData as $key => $value) {
                            if (empty($value) && !empty($variant[$key])) {
                                $rowData[$key] = $variant[$key];
                            }
                        }
                        $data[] = array_merge($rowData,$attributeData);
                        continue;
                    }
                    $data[] = array_merge($rowData,$attributeData);
                    foreach ($product->variants as $variant) {
                        $variantImageFirst = $variant->images->first();
                        if (!$variantImageFirst && $product->listings->isNotEmpty()) {
                            $variantImageFirst = $variant->listingImages->first();
                        }
                        $variantImage = !empty($variant->main_image) ? $variant->main_image : ($variantImageFirst ? $variantImageFirst->image_url : '');
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
                             $variantImage,
                             /*'',
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
                             '',*/
                        ];

                        /*//get images
                        foreach ($variant->images()->get() as $index => $image) {
                            $rowData[15 + $index] = $image->image_url;
                            // support 12 max images only
                            if ($index >= 11) {
                                break;
                            }
                        }*/
                        $variantImages = [];
                        $variantSourceImages = [];
                        $productVariantImages = array_fill(0, 12, '');
                        foreach ($variant->images as $index => $image) {
                            $itemVariantImageUrl = $image->image_url ?? '';
                            $variantSourceImages[] = $image->source_url ?? '';

                            if (!empty($itemVariantImageUrl)  && ($variant->main_image !== $itemVariantImageUrl) && !in_array($itemVariantImageUrl,$variantImages)) {
                                $variantImages[$index] = $itemVariantImageUrl;
                            }
                            // support 12 max images only
                            if ($index >= 11) {
                                break;
                            }
                        }

                        if(!empty($variantImages)) {
                            $variantImages = array_values($variantImages);
                            $productVariantImages = array_replace($productVariantImages,$variantImages);
                        }
                        $rowData = array_merge($rowData,$productVariantImages);

                        $data[] = array_merge($rowData,$attributeData);
                    }
                }
            }elseif ($products->count() === 0) {
                $data[] = $this->setDataProductDefault();
            }

        }

        $filename = 'export/export_products_attributes_' . Carbon::now()->timestamp . '.xlsx';
        Excel::store(new GenerateExcel('Create Products,'. $categoryId, $headers, $data,
                ['header_style' => ['bold' => true, 'auto_size' => true],
                'body_style' => ['warp_text' => true], 'freeze_pane' => 'D3'],1),
                $filename, 'excel', \Maatwebsite\Excel\Excel::XLSX);
        return Storage::disk('excel')->url($filename);
    }

    /**
     * Format Excel Cell's Value
     *
     * @param string|RichText $text
     * @param string $formattedText
     * @param array $format
     * @return RichText
     * @throws Exception
     */
    public function formatValue($text, $formattedText, $format = [])
    {
        if ($text instanceof RichText) {
            $value = $text;
        } else {
            // if $text not RichText, change it to RichText
            $value = new RichText();
            $value->createText($text);
        }

        // add formatted text behind the original text
        $objPayable = $value->createTextRun($formattedText);

        if (array_key_exists('bold', $format) && $format['bold']) {
            $objPayable->getFont()->setBold(true);
        }
        if (array_key_exists('color', $format) && !empty($format['color'])) {
            $objPayable->getFont()->setColor(new Color($format['color']));
        }

        return $value;
    }
    /**
     * Detection of valid JSON string.
     * @param mixed $string
     * @return String
     */
    public function isJsonString($string = '')
    {
        if (is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) && isset(json_decode($string,true)['name'])) {
            $string = json_decode($string,true)['name'];
        }
        return $string;
    }

    private function setDataProductDefault() {
        return  [
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
            ''
        ];
    }

}
