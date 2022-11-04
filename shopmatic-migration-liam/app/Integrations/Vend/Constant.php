<?php
namespace App\Integrations\Vend;

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
            ProductPriceType::COST(),
        ];
    }

    public static function ATTRIBUTES() {

        $attributes = [
            ["name" => "tags", "label" => "Tags"],
            ["name" => "brand_name", "label" => "Brand name"],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::VEND,
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
