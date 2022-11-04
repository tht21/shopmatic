<?php


namespace App\Integrations\IHub;


use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{

    protected $features = [
        Region::SINGAPORE => [
            'products' => [
                'import_products' => 1,
                'import_categories' => 0,
                'import_brands' => 0,
                'create_product' => 0,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 0,
                'fields' => [
                    ['name' => 'username', 'placeholder' => 'Username', 'type' => 'text', 'required' => 1],
                    ['name' => 'password', 'placeholder' => 'Password', 'type' => 'password', 'required' => 1],
                ],
            ]
        ],
    ];

    /**
     * Whether or not to show this integrations in creation
     *
     * @var array
     */
    protected $visibility = false;

    /**
     * Returns the integration name
     *
     * @return string
     */
    public function getName()
    {
        return 'IHub';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::IHUB;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::WAREHOUSE();
    }
}
