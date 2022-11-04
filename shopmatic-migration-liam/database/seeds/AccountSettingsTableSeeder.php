<?php

use Illuminate\Database\Seeder;

class AccountSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*$accounts = \App\Models\Account::all();

        foreach ($accounts as $account) {
            $region = $account->region_id;

            // Get has features from integration region
            $hasFeatures = ($account->integration->features[$region]["has_features"]) ?? null;

            // Update new setting, if the setting already exist do not update the VALUE
            if ($hasFeatures) {
                $settings = $account->settings;
                $newSettings = [];

                foreach ($hasFeatures as $name => $feature) {
                    // If exists just update except value
                    if ($settings && array_key_exists($name, $settings)) {
                        // Remove default value, to avoid update user set value
                        unset($feature['value']);
                        $newSettings[$name] = array_replace($settings[$name], $feature);
                    } else {
                        // Else add new
                        $newSettings[$name] = $feature;
                    }
                }

                $account->settings = $newSettings;
                $account->save();
            }
        }*/
    }
}
