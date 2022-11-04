<?php

use Illuminate\Database\Seeder;

class TicketCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ticketCategories = [
            [
                'id' => 1,
                'name' => 'Bugs',
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Feature requests',
                'status' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Technical questions',
                'status' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Billing issues',
                'status' => 1,
            ],
            [
                'id' => 5,
                'name' => 'How to\'s',
                'status' => 1,
            ]
        ];

        foreach ($ticketCategories as $ticketCategory) {
            \App\Models\TicketCategory::updateOrCreate(['id' => $ticketCategory['id']], $ticketCategory);
        }
    }
}
