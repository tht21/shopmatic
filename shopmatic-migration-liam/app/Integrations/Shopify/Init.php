<?php


namespace App\Integrations\Shopify;

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
                    ['name' => 'shop_url', 'placeholder' => 'user.myshopify.com', 'type' => 'text', 'required' => 1]
                ],
            ],
        ]
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
        return 'Shopify';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::SHOPIFY;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::STORE();
    }
}
