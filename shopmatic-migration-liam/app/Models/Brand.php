<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Brand
 *
 * @property int $id
 * @property string $name
 * @property string $external_id
 * @property int $integration_id
 * @property int $region_id
 * @property int $visible
 * @property int|null $flag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand active()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Brand whereVisible($value)
 * @mixin \Eloquent
 */
class Brand extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'external_id', 'integration_id', 'region_id', 'visible', 'flag'
    ];

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
     * get categories from cache
     *
     * @return mixed $categories
     */
    public static function getCachedBrands($integrationId,$regionId = '')
    {
        $key = 'brands-'.$integrationId;
        if (!empty($regionId)) {
            $key.= '-'.$regionId;
        }
        return Cache::rememberForever($key, function() use ($integrationId,$regionId) {
            $query = Brand::whereIntegrationId($integrationId);
            if (!empty($regionId)) {
                 $query = $query->whereRegionId($regionId);
            }
            return $query->select(['id', 'external_id', 'integration_id', 'name'])->get();
        });
    }

    /**
     * START - Relationship Methods
     */

    /**
     * END - Relationship Methods
     */
}
