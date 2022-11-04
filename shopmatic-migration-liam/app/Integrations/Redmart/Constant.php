<?php
namespace App\Integrations\Redmart;

use App\Constants\ProductPriceType;
use App\Integrations\AbstractConstant;

class Constant extends AbstractConstant
{
    public static function PRICES() {
        return [
            ProductPriceType::SELLING(),
            ProductPriceType::SPECIAL(),
        ];
    }
}
