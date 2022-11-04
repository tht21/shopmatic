<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntegrationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function run()
    {
        DB::transaction(function() {
            $ids = [];
            foreach (glob(base_path() . '/app/Integrations/*/Init.php') as $file) {

                //Convert to actual namespace and class name
                $file = str_replace(base_path().'/a', 'A', $file);
                $file = str_replace('/', '\\', $file);
                $file = str_replace('.php', '', $file);

                $reflector = new ReflectionClass('\\' . $file);

                if ($reflector->isInstantiable()) {

                    $initClass = $reflector->newInstanceArgs();

                    $integrationId = $initClass->getId();
                    if (in_array($integrationId, $ids)) {
                        throw new \Exception('Integration with the ID ' . $integrationId . ' already exists.');
                    }

                    $integration = $initClass->updateOrCreate();
                    $ids[] = $integration->id;

                } else {
                    throw new \Exception('Unable to initialize integration: ' . $file);
                }
            }
            \App\Models\Integration::whereNotIn('id', $ids)->delete();
        });
    }
}
