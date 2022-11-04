<?php


namespace App\Constants;

/**
 * @method static IntegrationType STORE()
 * @method static IntegrationType LOGISTIC()
 * @method static IntegrationType WAREHOUSE()
 * @method static IntegrationType POS()
 * @method static IntegrationType MESSAGING()
 * @method static IntegrationType ACCOUNTING()
 * @method static IntegrationType COMPLETE()
 */
class IntegrationType extends Enum
{
    
    private const STORE = 0;
    private const LOGISTIC = 1;
    private const WAREHOUSE = 2;
    private const POS = 3;
    private const MESSAGING = 4;
    private const ACCOUNTING = 5;
    private const COMPLETE = 10;
    
}