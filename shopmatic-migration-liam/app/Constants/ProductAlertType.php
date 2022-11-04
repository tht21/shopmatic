<?php


namespace App\Constants;

/**
 * @method static ProductAlertType INFO()
 * @method static ProductAlertType WARNING()
 * @method static ProductAlertType ERROR()
 */
class ProductAlertType extends Enum
{
    
    private const INFO = 0;
    private const WARNING = 1;
    private const ERROR = 2;
    
}