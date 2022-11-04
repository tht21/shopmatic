<?php


namespace App\Constants;

/**
 * @method static ProductStatus DRAFT()
 * @method static ProductStatus LIVE()
 * @method static ProductStatus DISABLED()
 * @method static ProductStatus OUT_OF_STOCK()
 * @method static ProductStatus BANNED()

 */
class ProductStatus extends Enum
{

    private const DRAFT = 1;
    private const LIVE = 10;
    private const DISABLED = 20;
    private const OUT_OF_STOCK = 30;
    private const BANNED = 40;

}
