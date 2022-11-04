<?php


namespace App\Constants;

/**
 * @method static Subscription TYPE()
 *
 * @method static Subscription STARTER()
 * @method static Subscription ADVANCE()
 * @method static Subscription PROFESSIONAL()
 * @method static Subscription E2E()
 * 
 * @method static Subscription STARTER_USER_LIMIT()
 * @method static Subscription PROFESSIONAL_USER_LIMIT()
 * @method static Subscription ADVANCE_USER_LIMIT()
 * 
 * @method static Subscription STARTER_ACCOUNT_LIMIT()
 * @method static Subscription PROFESSIONAL_ACCOUNT_LIMIT()
 * @method static Subscription ADVANCE_ACCOUNT_LIMIT()
 * 
 * @method static Subscription STARTER_SKU_LIMIT()
 * @method static Subscription PROFESSIONAL_SKU_LIMIT()
 */
class Subscription extends Enum
{

    private const TYPE = 'saas';

    private const STARTER = 0;
    private const PROFESSIONAL = 1;
    private const ADVANCE = 2;
    private const E2E = 3;

    private const STARTER_USER_LIMIT = 0;
    private const PROFESSIONAL_USER_LIMIT = 3;
    private const ADVANCE_USER_LIMIT = 10;

    private const STARTER_ACCOUNT_LIMIT = 10;
    private const PROFESSIONAL_ACCOUNT_LIMIT = 25;
    private const ADVANCE_ACCOUNT_LIMIT = 25;

    private const STARTER_SKU_LIMIT = 1000;
    private const PROFESSIONAL_SKU_LIMIT = 8000;

}
