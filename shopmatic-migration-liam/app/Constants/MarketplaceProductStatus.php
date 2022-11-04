<?php


namespace App\Constants;

/**
 * @method static MarketplaceProductStatus PENDING()
 * @method static MarketplaceProductStatus HAS_ISSUES()
 * @method static MarketplaceProductStatus REJECTED()
 * @method static MarketplaceProductStatus DELETED()
 * @method static MarketplaceProductStatus LIVE()
 * @method static MarketplaceProductStatus DISABLED()
 * @method static MarketplaceProductStatus OUT_OF_STOCK()
 * @method static MarketplaceProductStatus BANNED()
 */
class MarketplaceProductStatus extends Enum
{

    private const PENDING = 0;
    private const HAS_ISSUES = 1;
    private const REJECTED = 2;
    private const DELETED = 3;
    private const LIVE = 10;
    private const DISABLED = 20;
    private const OUT_OF_STOCK = 30;
    private const BANNED = 40;

}
