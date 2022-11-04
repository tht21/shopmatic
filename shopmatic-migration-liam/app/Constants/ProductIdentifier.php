<?php


namespace App\Constants;

/**
 * @method static ProductIdentifier EXTERNAL_ID()
 * @method static ProductIdentifier SKU()
 * @method static ProductIdentifier ISBN()
 * @method static ProductIdentifier UPC()
 * @method static ProductIdentifier ASIN()
 * @method static ProductIdentifier SPU_ID()
 * @method static ProductIdentifier SHOP_SKU()
 * @method static ProductIdentifier ITEM_ID()
 * @method static ProductIdentifier PRODUCT_ID()
 * @method static ProductIdentifier INVENTORY_ITEM_ID()
 * @method static ProductIdentifier STOCK_AVAILABLE_ID()
 */
class ProductIdentifier extends Enum
{

    private const EXTERNAL_ID = 'external_id';
    private const SKU = 'sku';
    private const ISBN = 'isbn';
    private const UPC = 'upc';
    private const ASIN = 'asin';
    private const GTIN = 'gtin';

    //Used in Lazada
    private const SPU_ID = 'spu_id';
    private const SHOP_SKU = 'shop_sku';

    //Used in Shopee
    private const ITEM_ID = 'item_id';

    // Used in Shopify
    private const PRODUCT_ID = 'product_id';
    private const INVENTORY_ITEM_ID = 'inventory_item_id';

    // Used in Amazon
    private const EAN = 'ean';
    private const GCID = 'gcid';

    // Used in PrestaShop
    private const STOCK_AVAILABLE_ID = 'stock_available_id';
}
