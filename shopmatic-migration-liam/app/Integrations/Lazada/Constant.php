<?php
namespace App\Integrations\Lazada;

use App\Constants\CategoryAttributeLevel;
use App\Constants\CategoryAttributeType;
use App\Constants\ProductPriceType;
use App\Integrations\AbstractConstant;
use App\Integrations\TransformedAttribute;
use App\Models\Integration;

class Constant extends AbstractConstant
{
    const CODE_API_APP_LIMIT = '998';

    const FIELD_PET_CATEGORY = ['animal_pets', 'pet_food_size', 'pets_flavor'];
    const SHIPMENT_METHOD_PICKUP_IN_STORE = "pickup_in_store";

    const CANCEL_REASON_OOS = "Seller was unable to reserve your stock";
    const CANCEL_REASON_INCORRECT_PRICING = "Seller cancelled due to incorrect pricing";

    public static function PRICES() {
        return [
            ProductPriceType::SELLING(),
            ProductPriceType::SPECIAL()
        ];
    }

    public static function ATTRIBUTES() {
        $attributes = [
            ["name" => "delivery_option_store_pick_up", "label" => "Allow Store Pickup", "data" => [["name" => "No", "value" => "No"], ["name" => "Yes", "value" => 'Yes']], "type" => CategoryAttributeType::SINGLE_SELECT()->getValue()],
        ];

        $attributes = collect($attributes)->map(function ($item, $key) {
            $attribute = new TransformedAttribute(
                Integration::LAZADA,
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
