<?php


namespace App\Integrations\Vend;

use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{

    protected $features = [
        Region::GLOBAL => [
            'products' => [
                'import_products' => 1,
                'create_product' => 1,
            ],
            'orders' => [
                'import_orders' => 1,
                'sync_orders' => 1,
            ],
            'inventory' => [
                'sync_inventory' => 1,
            ],
            'authentication' => [
                'enabled' => 1,
                'type' => 1,
            ],
        ]
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
        return 'Vend';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::VEND;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::POS();
    }

}
