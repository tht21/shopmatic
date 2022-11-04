<?php

use Illuminate\Database\Seeder;

class RegionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $regions = [
            [
                'id' => \App\Models\Region::GLOBAL,
                'name' => 'Global',
                'shortcode' => 'Global',
                'currency' => 'USD',
                'position' => 0,
                'visibility' => 1,
            ],
            [
                'id' => \App\Models\Region::SINGAPORE,
                'name' => 'Singapore',
                'shortcode' => 'SG',
                'currency' => 'SGD',
                'position' => 1,
                'visibility' => 1,
            ],
            [
                'id' => \App\Models\Region::MALAYSIA,
                'name' => 'Malaysia',
                'shortcode' => 'MY',
                'currency' => 'MYR',
                'position' => 2,
                'visibility' => 1,
            ],
            [
                'id' => \App\Models\Region::INDONESIA,
                'name' => 'Indonesia',
                'shortcode' => 'ID',
                'currency' => 'IDR',
                'position' => 3,
                'visibility' => 1,
            ],
        ];

        foreach ($regions as $region) {
            \App\Models\Region::updateOrCreate(['id' => $region['id']], $region);
        }

    }
}
