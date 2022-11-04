<?php
namespace App\Integrations\Woocommerce;

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
            ProductPriceType::SPECIAL(),
        ];
    }

    public static function ATTRIBUTES() {
        $attributes = [
            ["name" => "tax_status", "label" => "Tax Status",
                "data" => ["taxable", "shipping", "none"],
                "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()
            ],
            ["name" => "catalog_visibility", "label" => "Catalog Visibility",
                "data" => ["visible", "catalog", "search", "hidden"],
                "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()
            ],
            ["name" => "backorders", "label" => "Backorders",
                "data" => ["yes", "no", "notify"],
                "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()
            ],
            ["name" => "featured", "label" => "Featured", "type" => CategoryAttributeType::CHECKBOX()->getValue(),
                "data" =>
                    ["true"],
            ],
            ["name" => "reviews_allowed", "label" => "Reviews Allowed", "type" => CategoryAttributeType::CHECKBOX()->getValue(),
                "data" =>
                    ["true"],
            ],
            ["name" => "virtual", "label" => "Virtual Product", "type" => CategoryAttributeType::CHECKBOX()->getValue(),
                "data" =>
                    ["true"],
                
            ],
            ["name" => "shipping_class", "label" => "Shipping Class slug"],
            ["name" => "purchase_note", "label" => "Purchase Note", "type" => CategoryAttributeType::MULTI_TEXT()->getValue()],
            //["name" => "sold_individually", "label" => "Sold individually", "data" => ['checked'], "type" => CategoryAttributeType::CHECKBOX()->getValue()],
            //["name" => "tax_class", "label" => "Tax Class", "data" => ["standard"], "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::WOOCOMMERCE,
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
