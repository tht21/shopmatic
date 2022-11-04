<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AccountCategory
 *
 * @property int $id
 * @property string $name
 * @property string $breadcrumb
 * @property string $external_id
 * @property int $account_id
 * @property int|null $parent_id
 * @property int|null $category_id
 * @property int $is_leaf
 * @property int|null $flag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AccountCategory[] $children
 * @property-read int|null $children_count
 * @property-read \App\Models\AccountCategory|null $parent
 * @property-read \App\Models\Category|null $category
 * @property-write mixed $raw
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereBreadcrumb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereIsLeaf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccountCategory whereName($value)
 * @mixin \Eloquent
 */
class AccountCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'breadcrumb', 'parent_id', 'external_id', 'is_leaf', 'flag', 'category_id'
    ];

    /**
     * Retrieves the parent category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(AccountCategory::class, 'parent_id', 'id');
    }

    /**
     * Retrieves the Category it's linked to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Retrieves all the children categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(AccountCategory::class, 'parent_id', 'id');
    }

    /**
     * Retrieves the account the category belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'id', 'account_id');
    }

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
}
