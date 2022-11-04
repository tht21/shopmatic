<?php
namespace App\Integrations\PrestaShop;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\ProductPriceType;
use App\Integrations\AbstractConstant;
use App\Integrations\TransformedAttribute;
use App\Models\Integration;

class Constant extends AbstractConstant
{
    public static function PRICES() {
        return [
            ProductPriceType::SELLING(),
            ProductPriceType::WHOLESALE(),
        ];
    }

    public static function ATTRIBUTES() {
        $attributes = [
            ["name" => "condition", "label" => "Condition", "data" => ["New", "Used", "Refurbished"], "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()],
            ["name" => "ean13", "label" => "EAN-13", "type" => CategoryAttributeType::TEXT()->getValue()],
            ["name" => "isbn", "label" => "ISBN", "type" => CategoryAttributeType::TEXT()->getValue()],
            ["name" => "upc", "label" => "UPC", "type" => CategoryAttributeType::TEXT()->getValue()],
            ["name" => "ean13", "label" => "EAN-13", "level" => CategoryAttributeLevel::SKU()->getValue(), "type" => CategoryAttributeType::TEXT()->getValue()],
            ["name" => "isbn", "label" => "ISBN", "level" => CategoryAttributeLevel::SKU()->getValue(), "type" => CategoryAttributeType::TEXT()->getValue()],
            ["name" => "upc", "label" => "UPC", "level" => CategoryAttributeLevel::SKU()->getValue(), "type" => CategoryAttributeType::TEXT()->getValue()],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::PRESTASHOP,
                $item['name'],
                $item['label'],
                $item['type'] ?? CategoryAttributeType::TEXT()->getValue(),
                $item['required'] ?? false,
                $item['level'] ?? CategoryAttributeLevel::GENERAL()->getValue(),
                $item['data'] ?? null,
                $item['additional_data'] ?? null
            );
            return $attribute->createAndFormatAttribute();
        })->toArray();

        return $attributes;
    }
}
