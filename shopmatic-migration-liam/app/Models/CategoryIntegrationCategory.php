<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CategoryIntegrationCategory
 *
 * @property int $id
 * @property int $category_id
 * @property int $integration_category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\IntegrationCategory $integrationCategory
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory whereIntegrationCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CategoryIntegrationCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CategoryIntegrationCategory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_integration_category';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'integration_category_id'
    ];

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Retrieves the integration category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integrationCategory()
    {
        return $this->belongsTo(IntegrationCategory::class);
    }

    /**
     * END - Relationship Methods
     */
}
