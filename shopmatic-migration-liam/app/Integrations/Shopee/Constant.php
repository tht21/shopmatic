<?php
namespace App\Integrations\Shopee;

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
            ProductPriceType::SELLING()
        ];
    }

    public static function ATTRIBUTES() {
        $attributes = [
            ["name" => "is_pre_order", "label" => "Is Pre Order", "data" => ["Yes", "No"], "type" => CategoryAttributeType::RADIO()->getValue()],
            ["name" => "days_to_ship", "label" => "Days to ship (Pre Order)", "data" => [], "type" => CategoryAttributeType::NUMERIC()->getValue()],
            ["name" => "condition", "label" => "Condition", "data" => ["New", "Used"], "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::SHOPEE,
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
