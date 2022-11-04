<?php

namespace App\Events;

use App\Models\IntegrationCategory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class IntegrationCategoryLinked
{
    use Dispatchable, SerializesModels;

    public $category;

    /**
     * Create a new event instance.
     *
     * @param IntegrationCategory $category
     */
    public function __construct(IntegrationCategory $category)
    {
        $this->category = $category;
    }
}
