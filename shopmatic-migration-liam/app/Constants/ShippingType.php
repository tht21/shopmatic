<?php


namespace App\Constants;

/**
 * @method static ShippingType MANUAL()
 * @method static ShippingType MARKETPLACE()
 * @method static ShippingType VIRTUAL()
 */
class ShippingType extends Enum
{
    
    private const MANUAL = 0;
    private const MARKETPLACE = 1;
    private const VIRTUAL = 2;
    
}