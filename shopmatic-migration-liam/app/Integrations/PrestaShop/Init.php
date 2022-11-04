<?php

namespace App\Integrations\PrestaShop;

use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{
    protected $features = [
        Region::GLOBAL => [
            'products' => [
                'import_products' => 1,
                'import_account_categories' => 1,
                'import_brands' => 0,
                'create_product' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
                'sync_orders' => 1,
                'deduct_inventory' => 1,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 0,
                'fields' => [
                    ['name' => 'store_url', 'placeholder' => 'Store URL, example: http://example.com/', 'type' => 'text', 'required' => 1],
                    ['name' => 'access_key', 'placeholder' => 'Webservice Access Key', 'type' => 'text', 'required' => 1],
                ]
            ],
        ]
    ];

    /**
     * Whether or not to show this integrations in creation
     *
     * @var array
     */
    protected $visibility = false;

    function getName()
    {
        return 'PrestaShop';
    }

    function getId()
    {
        return Integration::PRESTASHOP;
    }

    function getType()
    {
        return IntegrationType::STORE();
    }
}
