<?php


namespace App\Constants;

/**
 * @method static CategoryAttributeSection GENERAL()
 * @method static CategoryAttributeSection PRICING_AND_INVENTORY()
 * @method static CategoryAttributeSection LOGISTIC()
 * @method static CategoryAttributeSection IMAGE()
 * @method static CategoryAttributeSection VARIATION()
 */
class CategoryAttributeSection extends Enum
{

    private const GENERAL = 0;
    private const PRICING_AND_INVENTORY = 1;
    private const LOGISTIC = 2;
    private const IMAGE = 3;
    private const VARIATION = 4;

}
