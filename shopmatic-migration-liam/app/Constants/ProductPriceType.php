<?php


namespace App\Constants;

/**
 * @method static ProductPriceType RETAIL()
 * @method static ProductPriceType SELLING()
 * @method static ProductPriceType SPECIAL()
 * @method static ProductPriceType COST()
 * @method static ProductPriceType SHIPPING()
 * @method static ProductPriceType WHOLESALE()
 */
class ProductPriceType extends Enum
{
    
    private const RETAIL = 'retail';
    private const SELLING = 'selling';
    private const SPECIAL = 'special';
    private const COST = 'cost';
    private const SHIPPING = 'shipping';
    private const WHOLESALE = 'wholesale';
    
    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   If there's both selling and shipping price, prompt customer that the final selling price will be inclusive
     *      of the shipping price
     *
     */
    
}
