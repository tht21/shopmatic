<?php

namespace App\Integrations\Shopee;

use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{
    protected $features = [
        Region::SINGAPORE => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 1,
                'import_brands' => 0,
                'create_product' => 1,
                'options_level' => 2
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
                'type' => 1,
            ]
        ],
        Region::MALAYSIA => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 1,
                'import_brands' => 1,
                'create_product' => 1,
                'options_level' => 2
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
                'type' => 1,
            ]
        ],
    ];

    /**
     * This is the value that should be overwritten in the Init for each integration.
     *
     * The first level of array should be the region - This is to support multi region
     *
     * @var array
     */
    protected $jobs = [

        /*
         * This is an example, the key name is the method name, while the value is the cron timing
         * The method name should be available in Client
         *
         */
        Region::SINGAPORE => [
            'retrieveSettlement' => '0 1 * * *',
        ]
    ];

    /**
     * Returns the integration name
     *
     * @return string
     */
    public function getName()
    {
        return 'Shopee';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::SHOPEE;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::STORE();
    }
}
