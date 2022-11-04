<?php


namespace App\Constants;

/**
 * @method static OrderLogType CREATING()
 * @method static OrderLogType UPDATING()
 * @method static OrderLogType EXPORTING()
 * @method static OrderLogType IMPORTING()
 */
class OrderLogType extends Enum
{

    private const CREATING = 0;
    private const UPDATING = 1;
    private const EXPORTING = 2;
    private const IMPORTING = 3;

}
