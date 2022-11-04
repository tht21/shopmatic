<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Category
 *
 * @property int $id
 * @property string $name
 * @property string $breadcrumb
 * @property int|null $parent_id
 * @property int $is_leaf
 * @property int|null $flag
 * @property array|null $category_attributes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationCategory[] $integrationCategories
 * @property-read int|null $integration_categories_count
 * @property-read \App\Models\Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AccountCategory[] $accountCategories
 * @property-read int|null $account_categories_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereBreadcrumb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereCategoryAttributes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereIsLeaf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class Category extends Model
{

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   The `category_attributes` field is updated via the event IntegrationCategoryLinked. This field is basically
     *      the combined attributes of all the integrations.
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'breadcrumb', 'parent_id', 'is_leaf', 'flag', 'category_attributes'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'category_attributes' => 'array',
    ];

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
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    /**
     * Retrieves the parent category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * Retrieves the internal category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function integrationCategories()
    {
        return $this->hasManyThrough(IntegrationCategory::class, CategoryIntegrationCategory::class, 'category_id', 'id', 'id', 'integration_category_id');
    }

    /**
     * Retrieves the account category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accountCategories()
    {
        return $this->hasMany(AccountCategory::class, 'category_id', 'id');
    }

    /**
     * END - Relationship Methods
     */

    /**
     * get categories from cache
     *
     * @return mixed $categories
     */
    public static function getCachedCategories()
    {
        return Cache::rememberForever('categories', function() {
            return Category::whereHas('integrationCategories')->get()->pluck('breadcrumb', 'id');
        });
    }}
