<?php


namespace App\Constants;

/**
 * @method static FulfillmentStatus UNPAID()
 * @method static FulfillmentStatus PENDING()
 * @method static FulfillmentStatus PROCESSING()
 * @method static FulfillmentStatus READY_TO_SHIP()
 * @method static FulfillmentStatus SHIPPED()
 * @method static FulfillmentStatus PARTIALLY_SHIPPED()
 * @method static FulfillmentStatus RETRY_SHIP()
 * @method static FulfillmentStatus DELIVERED()
 * @method static FulfillmentStatus TO_CONFIRM_DELIVERED()
 * @method static FulfillmentStatus CANCELLED()
 * @method static FulfillmentStatus REQUEST_CANCEL()
 * @method static FulfillmentStatus RETURNED()
 * @method static FulfillmentStatus READY_FOR_PICKUP()
 * @method static FulfillmentStatus RETURNED_FAILED()
 * @method static FulfillmentStatus LOST()
 * @method static FulfillmentStatus DAMAGED()
 * @method static FulfillmentStatus PACKED()
 * @method static FulfillmentStatus REPACKED()
 * @method static FulfillmentStatus READY_TO_SHIP_PENDING()
 * @method static FulfillmentStatus ON_HOLD()
 * @method static FulfillmentStatus PAID()
 */
class FulfillmentStatus extends Enum
{

    private const UNPAID = -1;
    private const PENDING = 0;
    private const PROCESSING = 1;
    private const PACKED = 2;
    private const REPACKED = 3;
    private const READY_TO_SHIP_PENDING = 4;
    private const ON_HOLD = 5;
    private const READY_TO_SHIP = 10;
    private const SHIPPED = 11;
    private const PARTIALLY_SHIPPED = 12;
    private const RETRY_SHIP = 13;
    private const DELIVERED = 20;
    private const TO_CONFIRM_DELIVERED = 21;
    private const READY_FOR_PICKUP = 22;

    private const REQUEST_CANCEL = 29;
    // NOTE: Failed orders must be above 30 while successful / pending orders / orders that should be fulfilled must be before 30
    // NOTE: Do NOT change `CANCELLED` to anything other than 30 unless there's a good reason and a proper refactor has been done.
    private const CANCELLED = 30;
    private const RETURNED = 40;
    private const RETURN_FAILED = 41;
    private const LOST = 50;
    private const DAMAGED = 60;
    private const PAID = 70;
}
