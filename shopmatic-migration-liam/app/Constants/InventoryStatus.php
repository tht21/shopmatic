<?php


namespace App\Constants;

/**
 * @method static InventoryStatus DEDUCTED()
 * @method static InventoryStatus UNCHANGED()
 * @method static InventoryStatus OVERRIDDEN_UNCHANGED()
 * @method static InventoryStatus OVERRIDDEN_DEDUCTED()
 */
class InventoryStatus extends Enum
{
    
    private const UNCHANGED = 0;
    private const DEDUCTED = 1;
    private const OVERRIDDEN_UNCHANGED = 2;
    private const OVERRIDDEN_DEDUCTED = 3;

}
