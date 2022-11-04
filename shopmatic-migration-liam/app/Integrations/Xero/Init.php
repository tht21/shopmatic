<?php


namespace App\Integrations\Xero;

use App\Constants\IntegrationType;
use App\Models\Integration;
use App\Models\Region;

class Init extends \App\Integrations\Init
{

    protected $features = [
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
        Region::GLOBAL => [
            // Running at the 48th minute to reduce load at the 0th minute (most jobs) and the daily listing sync at the 5th minute
            'inventorySync' => '48 * * * *',
        ]
    ];

    /**
     * Returns the integration name
     *
     * @return string
     */
    public function getName()
    {
        return 'Xero';
    }

    /**
     * Returns the ID
     *
     * @return integer
     */
    public function getId()
    {
        return Integration::XERO;
    }

    /**
     * @return IntegrationType
     */
    function getType()
    {
        return IntegrationType::ACCOUNTING();
    }

}
