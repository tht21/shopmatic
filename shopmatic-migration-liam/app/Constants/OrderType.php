<?php


namespace App\Constants;

/**
 * @method static OrderType NORMAL()
 * @method static OrderType POS()
 * @method static OrderType SHADOW()
 * @method static OrderType DRAFT()
 */
class OrderType extends Enum
{

    private const NORMAL = 0;
    private const POS = 1;

    /*
     * This means it's a replica of another order that we used to push to other integrations while also keeping a reference to the pushed order
     * This also means we shouldn't include orders with this type for reporting
     */
    private const SHADOW = 2;

    private const DRAFT = 3;

}
