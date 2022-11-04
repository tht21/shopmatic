<?php


namespace App\Constants;

/**
 * @method static AccountStatus ACTIVE()
 * @method static AccountStatus ISSUES()
 * @method static AccountStatus REQUIRE_AUTH()
 * @method static AccountStatus DISABLED()
 */
class AccountStatus extends Enum
{
    
    private const ACTIVE = 0;
    private const ISSUES = 10;
    private const REQUIRE_AUTH = 30;
    private const DISABLED = 40;
    
}