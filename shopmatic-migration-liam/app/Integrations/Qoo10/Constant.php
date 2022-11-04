<?php
namespace App\Integrations\Qoo10;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\ProductPriceType;
use App\Integrations\AbstractConstant;
use App\Models\Integration;

class Constant extends AbstractConstant
{
    const QOO10_CANCEL_REASON_OOS = "Out of Stock(by Seller)";
    public static function PRICES() {
        return [
            ProductPriceType::SELLING(),
            ProductPriceType::RETAIL(),
        ];
    }

    public static function ATTRIBUTES() {
        $valuesToAddIfNotNull = ['industrial_code_type', 'industrial_code', 'hs_code', 'video_url', 'origin_country_code', 'origin_state', 'material', 'contact_tel', 'adult_y_n', 'brand_no', 'manafacture_no', 'shipping_no', 'expire_date'];
        $attributes = [
            ['name' => 'material', 'label' => 'Material'],
            ['name' => 'contact_tel', 'label' => 'After Sales Service - Phone Number'],
            ['name' => 'adult_y_n', 'label' => 'Adult Item?', 'data' => [['name' => 'Y', 'value' => 'Y'], ['name' => 'N', 'value' => 'N']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'video_url', 'label' => 'Video Url'],

            // can be fill in craete and update mode but cant get these data from API
            ['name' => 'industrial_code', 'label' => 'Industrial Code'],
            ['name' => 'industrial_code_type', 'label' => 'Industrial Code Type', 'data' => [['name' => 'UPCCode', 'value' => '4'], ['name' => 'JANCode', 'value' => '1'], ['name' => 'KANCode', 'value' => '2'], ['name' => 'ISBN', 'value' => '3'], ['name' => 'EANCode', 'value' => '5'], ['name' => 'HSCode', 'value' => '6']], 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'hs_code', 'label' => 'HS Code'],
            ['name' => 'shipping_no', 'label' => 'Qoo10 Shipping Fee Code'],
            ['name' => 'origin_country_code', 'label' => 'Origin Country Code 2 letter (e.g. SG)'],
            ['name' => 'origin_state', 'label' => 'Origin State'],
            ['name' => 'brand_no', 'label' => 'Qoo10 Brand Code', 'type' => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ['name' => 'manafacture_no', 'label' => 'Qoo10 Manufacturer Code'],
            ['name' => 'expire_date', 'label' => 'Expire Date', 'type' => CategoryAttributeType::DATE()->getValue()],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $data["integration_id"] = Integration::QOO10;
            $data["name"] = $item['name'];
            $data["label"] = $item['label'];
            $data["required"] = $item['required'] ?? 0;
            $data["data"] = $item['data'] ?? null;
            $data["additional_data"] = $item['additional_data'] ?? null;
            $data["attribute_type"] = $item['attribute_type'] ?? 0;
            $data["type"] = $item['type'] ?? CategoryAttributeType::TEXT()->getValue();
            $data["level"] = $item['level'] ?? CategoryAttributeLevel::GENERAL()->getValue();

            return $data;
        })->toArray();

        return $attributes;
    }
}
