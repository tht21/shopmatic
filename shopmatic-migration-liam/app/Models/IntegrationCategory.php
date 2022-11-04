<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\IntegrationCategory
 *
 * @property int $id
 * @property string $name
 * @property string $breadcrumb
 * @property string $external_id
 * @property int $integration_id
 * @property int $region_id
 * @property int|null $parent_id
 * @property int|null $category_id
 * @property int $is_leaf
 * @property int $visible
 * @property int|null $flag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationCategoryAttribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationCategory[] $children
 * @property-read int|null $children_count
 * @property-read \App\Models\IntegrationCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $categories
 * @property-read int|null $categories_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereBreadcrumb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereIsLeaf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IntegrationCategory whereVisible($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class IntegrationCategory extends Model
{

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   The visible field is used when a new product is imported and the category doesn't exist on our systems,
     *      hence we save it but dont make it visible until after our sync / import.
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'breadcrumb', 'external_id', 'integration_id', 'region_id', 'parent_id', 'category_id',
        'is_leaf', 'visible', 'flag'
    ];

    /**
     * START - Accessor / Mutator
     */

    /**
     * Replace category_id with mapped category's id found in category_integration_category table
     *
     * @return int
     * @throws \Exception
     */
    public function getCategoryIdAttribute()
    {
        if ($this->category) {
            return $this->category->id;
        }
        return null;
    }

    /**
     * END - Accessor / Mutator
     */

    /**
     * START - Helper Methods
     */

    /**
     * Deletes this category and all the children under this
     *
     * @return void
     * @throws \Exception
     */
    public function deleteHierarchy()
    {
        foreach ($this->children as $child) {
            /** @var IntegrationCategory $child */
            $child->deleteHierarchy();
        }
        $this->delete();
    }

    /**
     * get attributes from cache
     *
     * @param array $ignore
     *
     * @return mixed
     */
    public function getCachedAttributes($ignore = [])
    {
        return Cache::remember('integration-category-' . $this->id . '-attributes', 7 * 24 * 60 * 60, function() use ($ignore) {
            return $this->attributes()->get();
        });
    }

    /**
     * END - Helper Methods
     */

    /**
     * START - Scopes
     */

    /**
     * Adds an active() scope for IntegrationCategory to be used in queries
     *
     * @param $query Builder
     *
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('visible', 1);
    }

    /**
     * END - Scopes
     */

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves all the children categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(IntegrationCategory::class, 'parent_id', 'id');
    }

    /**
     * Retrieves all the attributes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(IntegrationCategoryAttribute::class, 'integration_category_id', 'id');
    }

    /**
     * Retrieves the parent category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(IntegrationCategory::class, 'parent_id', 'id');
    }

    /**
     * Retrieves the first internal category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function category()
    {
        return $this->hasOneThrough(Category::class, CategoryIntegrationCategory::class, 'integration_category_id', 'id', 'id', 'category_id');
    }

    /**
     * Retrieves the list of internal category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function categories()
    {
        return $this->hasManyThrough(Category::class, CategoryIntegrationCategory::class, 'integration_category_id', 'id', 'id', 'category_id');
    }

    /**
     * END - Relationship Methods
     */

    /**
     * check attribute not have is_sale_prop = 1
     *
     */

     public function isIntegrationCategoryNotHaveAttributeIsSaleProp() {

        $integrationCategoryAttribute = $this->attributes()->get();
        if ($integrationCategoryAttribute) {
            foreach ($integrationCategoryAttribute as $attribute) {
                if ($attribute->isSaleProp()) {
                    return false;
                }
            }
            return true;
        }
        return true;
     }

}
