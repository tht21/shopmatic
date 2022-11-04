<?php


namespace App\Constants;

/**
 * @method static ContactType CUSTOMER()
 * @method static ContactType SUPPLIER()
 * @method static ContactType WAREHOUSE()
 * @method static ContactType LOGISTICS()
 */
class ContactType extends Enum
{

    private const CUSTOMER = 0;
    private const SUPPLIER = 1;
    private const WAREHOUSE = 2;
    private const LOGISTICS = 3;

}
