<?php

namespace App\Listeners;

use App\Events\IntegrationCategoryUpdated;
use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateIntegrationCategoryAttribute implements ShouldQueue
{

    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param IntegrationCategoryUpdated $event
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle(IntegrationCategoryUpdated $event)
    {
        $category = $event->category;

        //As we cant select a parent category, we should not push for an attribute update
        if (!$category->is_leaf) {
            return;
        }

        /** @var Account $account */
        $account = Account::active()->where('integration_id', $category->integration_id)
                          ->where('region_id', $category->region_id)->first();

        if (empty($account)) {
            set_log_extra('category', $category->toArray());
            throw new \Exception('Unable to fetch attributes for category as there\'s no valid account.');
        }

        $account->getProductAdapter()->updateCategoryAttribute($category);
    }
}
