<?php


namespace App\Integrations;

use App\Constants\IntegrationType;
use App\Factories\ClientFactory;
use App\Models\Integration;
use App\Models\Region;
use Cron\CronExpression;

abstract class Init
{

    /**
     * This is the default settings for all integrations.
     *
     * @var array
     */
    protected $default = [
        'products' => [
            'import_products' => 0,

            /*
             * `import_categories` is for categories that is integration & region tied,
             * which means for all integration of that region, it is the same
             */
            'import_categories' => 0,

            /*
             * `import_account_categories` is for account level categories (Differs among Account)
             */
            'import_account_categories' => 0,

            /*
             * Whether or not the integrations has a fixed list of brands that the user needs to use for the products
             */
            'import_brands' => 0,

            /*
             * Whether or not we're able to create product (Export / etc should all check against this as well)
             */
            'create_product' => 0,

            /*
             * options_level is the number of options used for variations
             * Set the level by - 0,1,2,3
             * 0 mean does not have any variations
             */
            'options_level' => 3
        ],
        'inventory' => [
            /*
             * NOTE: This should be the opposite of periodic_sync - If we are syncing inventory, we shouldn't enable
             * periodic_sync
             * 
             * `sync_inventory` is whether or not we should update the stock automatically if it's a different amount
             *  or if there's a new order / deduction from other places.
             */
            'sync_inventory' => 0,
            /*
             * NOTE: This should be the opposite of sync_inventory - If we are syncing inventory, we shouldn't enable
             * periodic_sync
             * 
             * This is if there are limitations to the API and we're not able to sync in real time, and we need to do it
             * in bulk at an interval. 
             */
            'periodic_sync' => 0,
        ],
        'orders' => [
            'import_orders' => 0,

            /*
             * The difference is that sync_orders can be turned off, but the ability to import_orders can still be on
             */
            'sync_orders' => 0,

            /*
             * This is if there's a separate endpoint for returns
             */
            'import_returns' => 0,

            /*
             * Whether or not we can process orders / perform actions related to the order - e.g. logistics
             */
            'process_orders' => 0,

            /*
             * This is whether or not we can actually change the status / update the returns on the integration (not locally / stock related)
             */
            'process_returns' => 0,

            /*
             * This is if we should push orders to the integration
             */
            'export_orders' => 0,

            /*
             * This is whether or not we should deduct the stock for inventory from orders automatically
             */
            'deduct_inventory' => 0,

            /*
             * This is whether or not we can use other third party logistics (our partners) for this integration
             */
            'third_party_logistics_allowed' => 0,
        ],
        'messaging' => [
            'import_messages' => 0,
            'create_message' => 0,
        ],
        'authentication' => [
            'enabled' => 0,

            /*
             * Type is the type of authentication it's used
             *
             * 0 - Fields
             * 1 - OAuth
             * 2 - Both
             */
            'type' => 0,

            /*
             * The `fields` column is only used if `type` is 0 or `type` is 2
             */
            'fields' => [
                ['name' => 'username', 'placeholder' => 'Username', 'type' => 'text', 'required' => 1],
                ['name' => 'password', 'placeholder' => 'Password', 'type' => 'password', 'required' => 1],
            ],
        ],
        'default_settings' => [
            // Default integration setting
            /*
             * Current support type - text, checkbox, radio
             *
             */
            'products' => [
                'automatic_inventory_sync' => [
                    'name' => 'automatic_inventory_sync',
                    'label' => 'Automatic Inventory Sync',
                    'note' => 'This is whether or not orders from other integrations will deduct the stock from this integration',
                    'type' => 'checkbox',
                    'required' => 1,
                    'requires' => ['import_products'],
                    'value' => false,
                ],
            ],
            'orders' => [
                'deduct_inventory' => [
                    'name' => 'deduct_inventory',
                    'label' => 'Deduct Stock',
                    'note' => 'This is whether or not we should deduct stock from incoming orders automatically',
                    'type' => 'checkbox',
                    'required' => 1,
                    'value' => true,
                    'requires' => ['import_orders'],
                ],
                'order_notification' => [
                    'name' => 'order_notification',
                    'label' => 'Send New Order Notification',
                    'note' => 'This is whether or not we should send you an email notification when there\'s new orders.',
                    'type' => 'checkbox',
                    'required' => 1,
                    'value' => true,
                ],
            ],
        ]
    ];

    /**
     * This is the value that should be overwritten in the Init for each integration.
     *
     * The first level of array should be the region - This is to support multi region
     *
     * @var array
     */
    protected $features = [];

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
        Region::GLOBAL => [
            'retrieveSettlement' => '* * * * *',
        ],

        */
    ];

    /**
     * Whether or not to show this integrations in creation
     *
     * @var array
     */
    protected $visibility = true;

    /**
     * Creates or updates the integration if it's already exists.
     *
     * @throws \Exception
     */
    public function updateOrCreate()
    {
        /*
         * Validation
         */
        foreach ($this->features as $region => $features) {
            if (!array_key_exists($region, Region::REGIONS)) {
                throw new \Exception('Region for integration does not exist: ' . $region);
            }
        }
        foreach ($this->jobs as $region => $jobs) {
            if (!array_key_exists($region, Region::REGIONS)) {
                throw new \Exception('Region for integration does not exist: ' . $region);
            }
            foreach ($jobs as $method => $cron) {
                if (!CronExpression::isValidExpression($cron)) {
                    throw new \Exception('Cron timing for integration not valid: ' . $cron);
                }
                $client = ClientFactory::createStatic($this->getName());
                if (!method_exists($client, $method)) {
                    throw new \Exception('Job method does not exist: ' . $method);
                }
            }
        }
        if (!IntegrationType::isValid($this->getType())) {
            throw new \Exception('Invalid integration type: ' . $this->getType());
        }

        // This is to get the full list of features (The default values) and the region ids
        $regions = [];
        $features = $this->features;
        foreach ($this->features as $region => $feature) {
            $features[$region] = $this->getFullFeatures($feature);
            $regions[] = $region;
        }

        return Integration::updateOrCreate(['id' => $this->getId()], [
            'name' => $this->getName(),
            'region_ids' => $regions,
            'visibility' => $this->visibility,
            'type' => $this->getType(),
            'features' => $features,
            'jobs' => $this->jobs
        ]);
    }

    /**
     * This is to get the full list of features (regardless of status), so we only need to include enabled features
     * in the Init for integrations.
     *
     * @param $features array
     *
     * @return array
     * @throws \Exception
     */
    private function getFullFeatures($features)
    {
        $ret = $this->default;
        $defaultSettings = $ret['default_settings'] ?? [];
        foreach ($features as $key => $feature) {
            if (array_key_exists($key, $ret)) {
                $ret[$key] = $this->updateValue($ret[$key], $feature);
            } else {
                throw new \Exception('Unknown feature: ' . $key);
            }
        }
        return $ret;
    }

    /**
     * Only replaces the values where the features are set.
     *
     * @param $original array
     * @param $newVal mixed
     *
     * @return array
     * @throws \Exception
     */
    private function updateValue($original, $newVal)
    {
        if (is_array($original) != is_array($newVal)) {
            throw new \Exception('Expected array for init features for ' . $this->getName());
        }
        if (is_array($newVal)) {
            foreach ($newVal as $key => $item) {
                // if authentication type is 2, and fields is set by integration
                if ($key === 'fields' && $original['type'] == 2 && count($item) > 0 && count($original[$key]) != count($item)) {
                    $original[$key] = [];
                }

                // For dropdown data used
                if ($key === 'data') {
                    $original[$key] = $item;
                }

                if (array_key_exists($key, $original)) {
                    $original[$key] = $this->updateValue($original[$key], $item);
                } else {
                    // Default settings is based on integration.
                    $original[$key] = $item;
                    //throw new \Exception('Unknown feature: ' . $key);
                }
            }
            return $original;
        }
        return $newVal;
    }

    /**
     * Returns the integration name
     *
     * @return string
     */
    abstract function getName();

    /**
     * Returns the ID
     *
     * @return integer
     */
    abstract function getId();

    /**
     * @return IntegrationType
     */
    abstract function getType();

}
