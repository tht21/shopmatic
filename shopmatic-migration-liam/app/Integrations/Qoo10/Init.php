<?php


namespace App\Integrations\Qoo10;


use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{
    /**
     * Whether or not to show this integrations in creation
     *
     * @var array
     */
    protected $visibility = true;

    protected $features = [
        Region::GLOBAL => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 1,
                'import_brands' => 1,
                'create_product' => 1,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
                'sync_orders' => 1,
                'deduct_inventory' => 0,  
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 0,
                'fields' => [
                    ['name' => 'api_key', 'placeholder' => 'API Key', 'type' => 'text', 'required' => 1],
                    ['name' => 'username', 'placeholder' => 'Username', 'type' => 'text', 'required' => 1],
                    ['name' => 'password', 'placeholder' => 'Password', 'type' => 'password', 'required' => 1],
                ],
            ]
        ],
        Region::SINGAPORE => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 1,
                'import_brands' => 1,
                'create_product' => 1,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
                'sync_orders' => 1,
                'deduct_inventory' => 0,  
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 0,
                'fields' => [
                    ['name' => 'api_key', 'placeholder' => 'API Key', 'type' => 'text', 'required' => 1],
                    ['name' => 'username', 'placeholder' => 'Username', 'type' => 'text', 'required' => 1],
                    ['name' => 'password', 'placeholder' => 'Password', 'type' => 'password', 'required' => 1],
                ],
            ]
        ],
//        Region::MALAYSIA => [
//            'products' => [
//                'import_products' => 1,
//                'import_categories' => 1,
//                'import_brands' => 1,
//                'create_product' => 1,
//            ],
//            'inventory' => [
//                'sync_inventory' => 1,
//            ],
//            'authentication' => [
//                'enabled' => 1,
//                'type' => 0,
//                'fields' => [
//                    ['name' => 'api_key', 'placeholder' => 'API Key', 'type' => 'text', 'required' => 1],
//                    ['name' => 'username', 'placeholder' => 'Username', 'type' => 'text', 'required' => 1],
//                    ['name' => 'password', 'placeholder' => 'Password', 'type' => 'password', 'required' => 1],
//                ],
//            ],
//        ],
    ];

    /**
     * Returns the integration name
     *
     * @return string
     */
    public function getName()
    {
        return 'Qoo10';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::QOO10;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::STORE();
    }
}
