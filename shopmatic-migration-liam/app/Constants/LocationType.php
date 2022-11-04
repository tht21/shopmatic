<?php

namespace App\Constants;

/**
 * @method static LocationType OUTLET()
 * @method static LocationType WAREHOUSE()
 * @method static LocationType OTHERS()
 */
class LocationType extends Enum
{
    
    private const OUTLET = 0;
    private const WAREHOUSE = 1;
    private const OTHERS = 10;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   Avoid listing all the address types (E.g. from Qoo10) from integrations but instead use something that 
     *      makes sense. It also needs to be something that can be applied to other integrations.
     *      To cater to the specific integrations, we can use the `attributes` column in Location instead.
     *
     */
}