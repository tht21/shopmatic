<?php


namespace App\Constants;

/**
 * Use to record color hex code used in all excel
 *
 * @method static HexColor LAZADA()
 * @method static HexColor SHOPEE()
 * @method static HexColor SHOPIFY()
 * @method static HexColor BASIC()
 * @method static HexColor SECONDARY()
 * @method static HexColor PRODUCT_REQUIRED()
 * @method static HexColor GENERAL_REQUIRED()
 * @method static HexColor GENERAL_OPTIONAL()
 * @method static HexColor SKU_REQUIRED()
 * @method static HexColor SKU_OPTIONAL()
 */
class HexColor extends Enum
{
    private const LAZADA = 'FFA500';
    private const SHOPIFY = '95BF46';
    private const SHOPEE = 'FF7B00';
    private const QOO10 = 'FFDA61';
    private const QOO10_LEGACY = 'FFDA61';
    private const WOOCOMMERCE = 'B64DF7';
    private const AMAZON = '005DCF';
    private const REDMART = 'FF2929';
    private const VEND = '3FAA4A';
    private const XERO = '00B2DD';
    private const IHUB = '537D80';
    private const PRESTASHOP = 'DD226A';
    private const BASIC = 'FF5543';
    private const SECONDARY = 'F5F5DC';
    private const PRODUCT_ONLY = 'E1EDFF';
    private const VARIANT_ONLY = 'EDFFE1';
    private const PRODUCT_VARIANT = 'FFE4E1';

    /** Regions */
    private const SG = 'FFC733';
    private const MY = 'FF7D33';
    private const ID = 'F633FF';



}
