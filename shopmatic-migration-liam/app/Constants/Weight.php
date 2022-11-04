<?php


namespace App\Constants;

/**
 * @method static Weight POUNDS()
 * @method static Weight GRAMS()
 * @method static Weight KILOGRAMS()
 * @method static Weight OUNCE()
 */
class Weight extends Enum
{
    
    private const POUNDS = 0;
    private const GRAMS = 1;
    private const KILOGRAMS = 2;
    private const OUNCE = 3;
    
}