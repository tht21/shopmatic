<?php


namespace App\Constants;

/**
 * @method static PaymentStatus PAID()
 * @method static PaymentStatus UNPAID()
 * @method static PaymentStatus PROCESSING()
 * @method static PaymentStatus CANCELLED()
 * @method static PaymentStatus REFUNDED()
 * @method static PaymentStatus PARTIALLY_REFUNDED()
 * @method static PaymentStatus PARTIALLY_PAID()
 */
class PaymentStatus extends Enum
{

    private const PAID = 1;
    private const UNPAID = 10;
    private const PROCESSING = 11;
    private const CANCELLED = 20;
    private const REFUNDED = 30;
    private const PARTIALLY_REFUNDED = 31;
    private const PARTIALLY_PAID = 40;
    private const AUTHORIZED = 41;
}
