<?php
namespace App\Integrations\Amazon;

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
        ];
    }

    public static function ATTRIBUTES() {
        $attributes = [
            ["name" => "product-id", "label" => "Product ID", "required" => true],
            ["name" => "product-id-type", "label" => "Product ID Type",
                "data" => ["ASIN", "UPC", "EAN", "ISBN","GCID","GTIN"],
                "type" => CategoryAttributeType::SINGLE_SELECT()->getValue(),
                "required" => true
            ],
            ["name" => "condition", "label" => "Condition",
                "data" => ["New", "Refurbished", "UsedLikeNew", "UsedVeryGood", "UsedGood", "UsedAcceptable"],
                "type" => CategoryAttributeType::SINGLE_SELECT()->getValue(),
                "required" => true
            ],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::AMAZON,
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
