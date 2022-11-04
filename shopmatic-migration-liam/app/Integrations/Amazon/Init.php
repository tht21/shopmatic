<?php


namespace App\Integrations\Amazon;

use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{

    protected $features = [
        Region::GLOBAL => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 0,
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
                'type' => 2,
                'fields' => [
                    ['name' => 'marketplace_id', 'placeholder' => 'Marketplace ID', 'type' => 'select', 'data' => [
                        "ATVPDKIKX0DER" => "US",
                        "A19VAU5U5O7RUS" => "Singapore"
                    ], 'required' => 1],
                ],
            ],
        ],
        Region::SINGAPORE => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 0,
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
                'type' => 2,
                'fields' => [
                    ['name' => 'marketplace_id', 'placeholder' => 'Marketplace ID', 'type' => 'select', 'data' => [
                        "ATVPDKIKX0DER" => "US",
                        "A19VAU5U5O7RUS" => "Singapore"
                    ], 'required' => 1],
                ],
            ],
        ],
        /*Region::MALAYSIA => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 0,
                'import_brands' => 0,
                'create_product' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 2,
                'fields' => [
                    ['name' => 'marketplace_id', 'placeholder' => 'Marketplace ID', 'type' => 'select', 'data' => [
                        "ATVPDKIKX0DER" => "US",
                        "A19VAU5U5O7RUS" => "Singapore"
                    ], 'required' => 1],
                ],
            ],
        ],
        Region::INDONESIA => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 0,
                'import_brands' => 0,
                'create_product' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 2,
                'fields' => [
                    ['name' => 'marketplace_id', 'placeholder' => 'Marketplace ID', 'type' => 'select', 'data' => [
                        "ATVPDKIKX0DER" => "US",
                        "A19VAU5U5O7RUS" => "Singapore"
                    ], 'required' => 1],
                ],
            ],
        ]*/
    ];

    /**
     * Whether or not to show this integrations in creation
     *
     * @var array
     */
    protected $visibility = true;

    /**
     * Returns the integration name
     *
     * @return string
     */
    public function getName()
    {
        return 'Amazon';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::AMAZON;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::STORE();
    }
}
