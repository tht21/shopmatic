<?php
namespace App\Integrations\Shopify;

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
            ProductPriceType::SELLING(), ProductPriceType::RETAIL()
        ];
    }

    public static function ATTRIBUTES() {
        $attributes = [
            ["name" => "product_type", "label" => "Product Type"],
            ["name" => "vendor", "label" => "Vendor"],
            ["name" => "tags", "label" => "Tags"],

            ["name" => "inventory_management", "label" => "Track Quantity", "data" => ["Yes", "No"], "type" => CategoryAttributeType::RADIO()->getValue(), "level" => CategoryAttributeLevel::SKU()->getValue()],
            ["name" => "inventory_policy", "label" => "Continue Selling When Out Of Stock", "data" => ["Yes", "No"], "type" => CategoryAttributeType::RADIO()->getValue(), "level" => CategoryAttributeLevel::SKU()->getValue()],
            ["name" => "requires_shipping", "label" => "This Is A Physical Product", "data" => ["Yes", "No"], "type" => CategoryAttributeType::RADIO()->getValue(), "level" => CategoryAttributeLevel::SKU()->getValue()],
            ["name" => "barcode", "label" => "Barcode", "level" => CategoryAttributeLevel::SKU()->getValue()],
            //["name" => "weight", "label" => "Weight", "level" => CategoryAttributeLevel::SKU()->getValue()],
            ["name" => "weight_unit", "label" => "Weight Unit", "data" => ["kg", "g", "oz", "lb"], "type" => CategoryAttributeType::SINGLE_SELECT()->getValue(), "level" => CategoryAttributeLevel::SKU()->getValue()]
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::SHOPIFY,
                $item['name'],
                $item['label'],
                $item['type'] ?? CategoryAttributeType::TEXT()->getValue(),
                $item['required'] ?? false,
                $item['level'] ?? CategoryAttributeLevel::GENERAL()->getValue(),
                $item['data'] ?? null,
                $item['additional_data'] ?? null,
            );
            return $attribute->createAndFormatAttribute();
        })->toArray();

        return $attributes;
    }
}
