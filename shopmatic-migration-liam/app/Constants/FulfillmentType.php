<?php


namespace App\Constants;

/**
 * @method static FulfillmentType REQUIRES_SHIPPING()
 * @method static FulfillmentType SELF_COLLECTION()
 * @method static FulfillmentType NO_SHIPPING()
 */
class FulfillmentType extends Enum
{

    private const REQUIRES_SHIPPING = 0;
    private const SELF_COLLECTION = 1;
    private const CASH_ON_DELIVERY = 2;
    private const NO_SHIPPING = 10;

}
