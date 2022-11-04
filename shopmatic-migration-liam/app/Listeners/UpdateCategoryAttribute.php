<?php

namespace App\Listeners;

use App\Events\IntegrationCategoryLinked;
use App\Models\Category;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCategoryAttribute implements ShouldQueue
{

    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param IntegrationCategoryLinked $event
     *
     * @return void
     * @throws \Exception
     */
    public function handle(IntegrationCategoryLinked $event)
    {
        $integrationCategory = $event->category;

        /** @var Category $category */
        $category = $integrationCategory->category;

        // Ensure that the IntegrationCategory is linked to a Category
        if (is_null($category)) {
            return;
        }

        $attributes = [];
        foreach ($category->integrationCategories as $otherCategories) {
            $attributes += $otherCategories->attributes->toArray();
        }
        $category->category_attributes = $attributes;
        $category->save();
    }
}
