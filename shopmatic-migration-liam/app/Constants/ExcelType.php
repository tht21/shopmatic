<?php

namespace App\Constants;

/**
 * Use to record excel type
 *
 * @method static ExcelType CREATE_PRODUCTS()
 * @method static ExcelType CREATE_INVENTORY()
 * @method static ExcelType UPDATE_INVENTORY()
 * @method static ExcelType DOWNLOAD_INVENTORY()
 */
class ExcelType extends Enum
{
    private const CREATE_PRODUCTS = 'Excel\CreateProducts';
    private const CREATE_INVENTORY = 'Csv\CreateInventory';
    private const UPDATE_INVENTORY = 'Csv\UpdateInventory';
    private const DOWNLOAD_INVENTORY = 'Csv\DownloadInventory';
    private const DOWNLOAD_ORDERS = 'Excel\DownloadOrders';
}
