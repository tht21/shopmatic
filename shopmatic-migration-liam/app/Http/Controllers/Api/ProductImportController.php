<?php


namespace App\Http\Controllers\Api;


use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\HexColor;
use App\Constants\JobStatus;
use App\Jobs\ProductImportJob;
use App\Models\Category;
use App\Models\Integration;
use App\Models\IntegrationCategory;
use App\Models\ProductImportTask;
use App\Models\Shop;
use App\Utilities\Excel\GenerateExcel;
use App\Utilities\Excel\VerifyExcel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\Region;

class ProductImportController extends Controller
{
    /**
     * Generate excel template for product creation
     *
     * @param Request $request
     * @return BinaryFileResponse
     * @throws Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function downloadTemplate(Request $request)
    {
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
            ['value' => 'main_image',           'style' => ['background' => $colorChoice['PRODUCT_VARIANT']], 'required' => 1,'maximum_length'=>255],
        ];

        for ($i = 1;$i <= 12; $i++) {
            $headers[1][] = ['value' => 'image_'.$i, 'style' => ['background' => $colorChoice['PRODUCT_VARIANT']],'maximum_length'=>255];
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

        // get user's accounts id list (format: integration_id > region_id > account_id)
        $integrationToAccountId = json_decode($request->input('integration_to_account_id', '{}'), true);

        // get integration categories list
        //$integrationCategories = Category::find($request->input('category_id'))->integrationCategories()->whereIn('integration_categories.id', $request->input('integrations_category_id', []))->orderBy('integration_id')->get();
        $integrationCategories = IntegrationCategory::whereIn('id', $request->input('integrations_category_id', []))->active()->orderBy('id')->get();

        // get account integration list
        $accountCategoryIntegrations = Integration::whereIn('id', $request->input('account_category_integration_id', []))->orderBy('id')->get();

        // additional attribute for basic information column
        if ($integrationCategories->search(function ($item, $key) {
            return $item->integration_id === Integration::SHOPEE;
        }) !== false) {
            // for shopee short description need to set it as required
            if ($key = array_search('short_description', array_column($headers[1], 'value'))) {
                $headers[1][$key]['required'] = 1;
            }
        }

        foreach ($headers[1] as $key => $header) {
            if (isset($header['required']) && $header['required']) {
                $headers[1][$key]['value'] = $this->formatValue($header['value'], '*', ['bold' => true, 'color' => Color::COLOR_RED]);
            }
        }

        /** @var IntegrationCategory $integrationCategory */
        foreach ($integrationCategories as $integrationCategory) {
            // check user has any account with this integration's id or not
            if (array_key_exists($integrationCategory->integration_id, $integrationToAccountId) && array_key_exists($integrationCategory->region_id, $integrationToAccountId[$integrationCategory->integration_id])) {
                $attributeExist = false;
                $xStart = $xEnd;
                $xStart++;

                // append account id to the request
                $request->merge(['account' => $integrationToAccountId[$integrationCategory->integration_id][$integrationCategory->region_id]]);
                // retrieve attributes
                $response = (new CategoryController)->getAttributes($integrationCategory, $request);

                $attributes = [];
                $logistics = [];
                if ($response->getStatusCode() === 200) {
                    $body = json_decode($response->getContent(), true);
                    $attributes = array_key_exists('attributes', $body['response']) ? collect($body['response']['attributes']) : [];

                    // Get logistics (Currently support shopee logistic)
                    if ($integrationCategory->integration_id === Integration::SHOPEE) {
                        $logistics = array_key_exists('logistics', $body['response']) ? collect($body['response']['logistics']) : [];
                    }
                }
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
                        if ($attribute['required'] === 1 || ($integrationCategory->integration_id === Integration::LAZADA && $attribute['name'] === 'brand')) {
                            $value = new RichText();
                            $value->createText($attribute['name']);
                            $objPayable = $value->createTextRun('*');
                            $objPayable->getFont()->setBold(true);
                            $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));
                        }

                        if ($attribute['level'] !== CategoryAttributeLevel::GENERAL()->getValue()) {
                            $value = $this->formatValue($value, '_'.$integrationCategory->id.'_v', ['color' => $colorChoice['VARIANT_ONLY']]);
                        } else {
                            $value = $this->formatValue($value, '_'.$integrationCategory->id, ['color' => $colorChoice['PRODUCT_ONLY']]);
                        }

                        // Convert options data based on integration
                        $optionsData = $attribute['data'];
                        if (in_array($attribute['type'], [CategoryAttributeType::SINGLE_SELECT()->getValue(), CategoryAttributeType::OPTION()->getValue()]) && !empty($attribute['data'])) {
                            if ($integrationCategory->integration_id === Integration::LAZADA) {
                                if ($attribute['name'] !== 'brand') {
                                    $optionsData = array_column($attribute['data'], 'name');
                                }
                            } else if ($integrationCategory->integration_id === Integration::QOO10_LEGACY || $integrationCategory->integration_id === Integration::QOO10) {
                                // Qoo10 legacy will have different format (Can refer to constant file)
                                if (is_array($optionsData[0])) {
                                    $optionsData = array_column($attribute['data'], 'value');
                                }
                            }
                        }

                        $headers[1][] = [
                            'value' => $value,
                            'style' => [
                                'background' => ($attribute['level'] === CategoryAttributeLevel::GENERAL()->getValue() ? $colorChoice['PRODUCT_ONLY'] : $colorChoice['VARIANT_ONLY']),
                            ],
                            'type' => $attribute['type'],
                            'options' => $optionsData,
                        ];
                        $xEnd++;
                    }
                    // if this integration's category has attributes, create top header

                    if ($attributeExist) {
                        $integrationName = Integration::INTEGRATIONS[$integrationCategory->integration_id];

                        $region = Region::where('id',$integrationCategory->region_id)->first();

                        if (!$region) {
                            set_log_extra('integrationCategory', $integrationCategory);
                            throw new \Exception('Region not found.');
                        }

                        $regionShortCode = $region->shortcode;
                        $backgroundColorChoice = $colorChoice[strtoupper(snake_case($integrationName))];
                        $backgroundColorChoiceDec =  hexdec($backgroundColorChoice);
                        if (!empty($regionShortCode) && isset($colorChoice[strtoupper($regionShortCode)])) {
                            $backgroundColorChoiceDec = $backgroundColorChoiceDec + hexdec($colorChoice[strtoupper($regionShortCode)]);
                        }
                        $backgroundColorChoiceHex = dechex($backgroundColorChoiceDec);
                        $headers[0][] = [
                            'coordinate' => $xStart . '1',
                            'value'      => $integrationName.' '.$regionShortCode.'-'.$integrationCategory->id,
                            'style'      => [
                                'range'      => $xStart . '1:' . $xEnd . '1',
                                'background' => $backgroundColorChoiceHex,
                                'alignment'  => 'center'
                            ]
                        ];
                    }
                }

                // if dont have logistics, skip it (Currently only support for shopee)
                if (count($logistics) > 0) {
                    foreach ($logistics as $logistic) {
                        if ($logistic['enabled']) {
                            $logisticExist = true;

                            $value = new RichText();
                            $value->createText(snake_case($logistic['logistic_name']));
                            $objPayable = $value->createTextRun('*');
                            $objPayable->getFont()->setBold(true);
                            $objPayable->getFont()->setColor(new Color(Color::COLOR_RED));
                            $value = $this->formatValue($value, '_'.$integrationCategory->id.'_'.$logistic['logistic_id'].'_l1', ['color' => $colorChoice['PRODUCT_ONLY']]);

                            $headers[1][] = [
                                'value' => $value,
                                'style' => ['background' => $colorChoice['PRODUCT_ONLY']],
                                'type' => CategoryAttributeType::OPTION()->getValue(),
                                'options' => ['No', 'Yes']
                            ];
                            $xEnd++;

                            // Additional logistic attribute column
                            $additionalLogistics = [];
                            if ($integrationCategory->integration_id === Integration::SHOPEE) {
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
                        $integrationName = Integration::INTEGRATIONS[$integrationCategory->integration_id];
                        $region = Region::where('id',$integrationCategory->region_id)->first();

                        if (!$region) {
                            set_log_extra('integrationCategory', $integrationCategory);
                            throw new \Exception('Region not found.');
                        }

                        $regionShortCode = $region->shortcode;
                        $backgroundColorChoice = $colorChoice[strtoupper(snake_case($integrationName))];
                        $backgroundColorChoiceDec =  hexdec($backgroundColorChoice);
                        if (!empty($regionShortCode) && isset($colorChoice[strtoupper($regionShortCode)])) {
                            $backgroundColorChoiceDec = $backgroundColorChoiceDec + hexdec($colorChoice[strtoupper($regionShortCode)]);
                        }
                        $backgroundColorChoiceHex = dechex($backgroundColorChoiceDec);
                        $headers[0][] = [
                            'coordinate' => $xStart . '1',
                            'value'      => $integrationName.' '.$regionShortCode.'-'.$integrationCategory->id,
                            'style'      => [
                                'range'      => $xStart . '1:' . $xEnd . '1',
                                'background' => $backgroundColorChoiceHex,
                                'alignment'  => 'center'
                            ]
                        ];
                    }
                }

                //unset($integrationToAccountId[$integrationCategory->integration_id]);
            }
        }

        /** @var Integration $accountCategoryIntegration */
        foreach ($accountCategoryIntegrations as $accountCategoryIntegration) {
            // check user has any account with this integration's id or not
            if (array_key_exists($accountCategoryIntegration->id, $integrationToAccountId)) {
                $attributeExist = false;
                $xStart = $xEnd;
                $xStart++;

                // Search for region and account
                $regionId = null;
                foreach ($accountCategoryIntegration->region_ids as $region_id) {
                    if (isset($integrationToAccountId[$accountCategoryIntegration->id][$region_id])) {
                        // append account id to the request
                        $request->merge(['account' => $integrationToAccountId[$accountCategoryIntegration->id][$region_id]]);
                        $regionId = $region_id;
                        break;
                    }
                }

                // retrieve attributes
                $response = (new CategoryController)->getAttributes(null, $request);

                $attributes = [];
                $logistics = [];
                if ($response->getStatusCode() === 200) {
                    $body = json_decode($response->getContent(), true);
                    $attributes = array_key_exists('attributes', $body['response']) ? collect($body['response']['attributes']) : [];
                }
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

                        // format value append without integration category id
                        if ($attribute['level'] !== CategoryAttributeLevel::GENERAL()->getValue()) {
                            $value = $this->formatValue($value, '_v', ['color' => $colorChoice['VARIANT_ONLY']]);
                        } else {
                            $value = $this->formatValue($value, '', ['color' => $colorChoice['PRODUCT_ONLY']]);
                        }

                        // Convert options data based on integration
                        $optionsData = $attribute['data'];

                        $headers[1][] = [
                            'value' => $value,
                            'style' => [
                                'background' => ($attribute['level'] === CategoryAttributeLevel::GENERAL()->getValue() ? $colorChoice['PRODUCT_ONLY'] : $colorChoice['VARIANT_ONLY']),
                            ],
                            'type' => $attribute['type'],
                            'options' => $optionsData,
                        ];
                        $xEnd++;
                    }
                    // if this integration's category has attributes, create top header

                    if ($attributeExist) {
                        $integrationName = Integration::INTEGRATIONS[$accountCategoryIntegration->id];

                        $region = Region::where('id', $regionId)->first();

                        if (!$region) {
                            set_log_extra('accountCategoryIntegration', $accountCategoryIntegration);
                            throw new \Exception('Region not found.');
                        }

                        $regionShortCode = $region->shortcode;
                        $backgroundColorChoice = $colorChoice[strtoupper(snake_case($integrationName))];
                        $backgroundColorChoiceDec =  hexdec($backgroundColorChoice);
                        if (!empty($regionShortCode) && isset($colorChoice[strtoupper($regionShortCode)])) {
                            $backgroundColorChoiceDec = $backgroundColorChoiceDec + hexdec($colorChoice[strtoupper($regionShortCode)]);
                        }
                        $backgroundColorChoiceHex = dechex($backgroundColorChoiceDec);
                        $headers[0][] = [
                            'coordinate' => $xStart . '1',
                            'value'      => $integrationName.' '.$regionShortCode,
                            'style'      => [
                                'range'      => $xStart . '1:' . $xEnd . '1',
                                'background' => $backgroundColorChoiceHex,
                                'alignment'  => 'center'
                            ]
                        ];
                    }
                }
                //unset($integrationToAccountId[$integrationCategory->integration_id]);
            }
        }

        if (count($integrationToAccountId) > 0) {
            set_log_extra('integrations_id', implode(',', array_keys($integrationToAccountId)));
            Log::alert('Integration(s) Category not found, category didn\'t map to corresponding integration(s)');
        }
        /* generate headers data - END */
        /* integration category id */
        /*$integrationsCategories = $request->input('integrations_category_id',[]);
        $integrationsCategoryIds = implode(',',$integrationsCategories);*/
        return Excel::download(new GenerateExcel('Create Products,'.$request->input('category_id'), $headers, [], ['header_style' => ['bold' => true, 'auto_size' => true], 'body_style' => ['warp_text' => true], 'freeze_pane' => 'D3']), 'export_excel_bulk_edit_' . Carbon::now()->timestamp . '.xlsx');
    }

    /**
     * Upload Excel to server storage
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadExcel(Request $request)
    {

        if ($request->file('xlsx')->isValid()) {
            /** @var Shop $shop */
            $shop = session('shop');
            $excelFile = $request->file('xlsx');
            $fileName = 'import_' . $shop->id . '_' . Carbon::now()->timestamp . '.xlsx';

            $task = ProductImportTask::whereJsonContains('settings', ['file' => $excelFile->getClientOriginalName() ])
                        ->where(function($query) {
                            $query->where('status', JobStatus::PENDING())
                                ->orWhere('status', JobStatus::PROCESSING());
                        })->first();

            $verifyExcel = new VerifyExcel();
            Excel::import($verifyExcel, $excelFile);

            if (is_null($task) && $verifyExcel->getType() === 'Excel\CreateProducts') {
                // store it to storage/excel/import folder and rename the file to $fileName

                try {
                    $storageFile = Storage::disk('excel')->putFileAs('import', $excelFile, $fileName);
                } catch (\Throwable $th) {

                    Log::error("error storage file excel: " .$th->getMessage());
                }


                $task = ProductImportTask::create([
                    'shop_id' => $shop->id,
                    'user_id' => Auth::user()->id, 'source',
                    'source_type' => $verifyExcel->getType(),
                    'source' => 'import/'.$fileName,
                    'settings' => ['file' => $excelFile->getClientOriginalName(), 'update' => $request->input('update', false)]
                ]);

                ProductImportJob::dispatch($task->fresh())->onQueue('import_products');
                return $this->respondWithMessage(null, 'Excel file will be processing shortly.');

            } elseif (!is_null($task)) {
                return $this->respondWithError('File uploaded already exist in our server.');
            }
        }

        return $this->respondWithError('File uploaded is not valid.');
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
            info($format['color']);
            $objPayable->getFont()->setColor(new Color($format['color']));
        }

        return $value;
    }
}
