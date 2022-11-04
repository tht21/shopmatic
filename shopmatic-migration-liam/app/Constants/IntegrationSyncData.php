<?php


namespace App\Constants;

/**
 * @method static IntegrationSyncData IMPORT_ACCOUNT_CATEGORIES()
 * @method static IntegrationSyncData IMPORT_CATEGORIES()
 * @method static IntegrationSyncData IMPORT_BRANDS()
 * @method static IntegrationSyncData IMPORT_PRODUCTS()
 * @method static IntegrationSyncData IMPORT_ORDERS()
 * @method static IntegrationSyncData SYNC_ORDERS()
 * @method static IntegrationSyncData EXPORT_ORDERS()
 */
class IntegrationSyncData extends Enum
{
    
    private const IMPORT_ACCOUNT_CATEGORIES = 'import_account_categories';
    private const IMPORT_CATEGORIES = 'import_categories';
    private const IMPORT_BRANDS = 'import_brands';
    private const IMPORT_PRODUCTS = 'import_products';
    private const IMPORT_ORDERS = 'import_orders';
    private const SYNC_ORDERS = 'sync_orders';
    private const EXPORT_ORDERS = 'export_orders';
    
}
