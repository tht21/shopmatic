<?php

namespace App\Models;

use App\Constants\IntegrationSyncData;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Integration
 *
 * @property int $id
 * @property array|null $region_ids
 * @property string $name
 * @property string|null $thumbnail_image
 * @property array|null $sync_data
 * @property array|null $features
 * @property array|null $settings
 * @property int $type
 * @property int $visibility
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property array|null $jobs
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Account[] $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationCategory[] $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Brand[] $brands
 * @property-read int|null $brands_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereRegionIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereSyncData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereThumbnailImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Integration whereJobs($value)
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class Integration extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    const LAZADA = 11001;
    const SHOPIFY = 11002;
    const SHOPEE = 11003;
    const QOO10 = 11004;
    const QOO10_LEGACY = 11005;
    const WOOCOMMERCE = 11006;
    const AMAZON = 11007;
    const REDMART = 11008;
    const VEND = 11009;
    const XERO = 11010;
    const IHUB = 11011;
    const PRESTASHOP = 11012;

    /*
     * This is used mainly for validation so we don't have to query the DB
     */
    const INTEGRATIONS = [
        self::LAZADA => 'Lazada',
        self::SHOPIFY => 'Shopify',
        self::SHOPEE => 'Shopee',
        self::QOO10 => 'Qoo10',
        self::QOO10_LEGACY => 'Qoo10 Legacy',
        self::WOOCOMMERCE => 'Woocommerce',
        self::AMAZON => 'Amazon',
        self::REDMART => 'Redmart',
        self::VEND => 'Vend',
        self::XERO => 'Xero',
        self::IHUB => 'IHub',
        self::PRESTASHOP => 'PrestaShop',
    ];

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   The `features` should only be updated via the Init.php class for the integration.
     *
     * 2.   If certain features needs to be dynamically disabled / enabled, the `settings` should be used.
     *
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'features', 'region_ids', 'sync_data', 'visibility', 'settings', 'jobs', 'type', 'position'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'region_ids' => 'array',
        'features' => 'array',
        'sync_data' => 'array',
        'settings' => 'array',
        'jobs' => 'array'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'settings', 'jobs', 'region_id', 'sync_data', 'deleted_at', 'features'
    ];

    /**
     * START - Helper Methods
     */

    /**
     * Returns whether or not the integration supports the feature
     *
     * @param $region
     * @param $feature
     *
     * @return bool
     */
    public function hasFeature($region, array $feature)
    {
        if (!array_key_exists($region, $this->features)) {
            return false;
        }

        return !empty(array_retrieve($this->features[$region], $feature));
    }

    /**
     * Returns the value stored for the feature
     *
     * @param $region
     * @param $feature
     *
     * @return bool
     */
    public function getFeature($region, array $feature)
    {
        if (!array_key_exists($region, $this->features)) {
            return false;
        }

        return array_retrieve($this->features[$region], $feature);
    }

    /**
     * Updates the value for the key for the region in sync_data
     *
     * @param $regionId
     * @param IntegrationSyncData $key
     * @param $value
     */
    public function setSyncData($regionId, IntegrationSyncData $key, $value)
    {
        // Retrieve all sync data so we won't remove anything else
        $syncData = $this->sync_data;

        // If the sync_data somehow isn't an array
        if (!is_array($syncData)) {
            $syncData = [
                $regionId => []
            ];
        }
        if (!array_key_exists($regionId, $syncData)) {
            $syncData[$regionId] = [];
        }

        $syncData[$regionId][$key->getValue()] = $value;

        $this->sync_data = $syncData;
        $this->save();
    }

    /**
     * Retrieves the sync data or returns the default if not set
     *
     * @param $regionId
     * @param IntegrationSyncData $key
     * @param $default
     *
     * @return mixed|null
     */
    public function getSyncData($regionId, IntegrationSyncData $key, $default = null)
    {
        $syncData = $this->sync_data;

        if (empty($syncData)) {
            return $default;
        }
        if (!array_key_exists($regionId, $syncData)) {
            return $default;
        }
        return $syncData[$regionId][$key->getValue()] ?? $default;
    }

    /**
     * END - Helper Methods
     */

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves all the categories under this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany(IntegrationCategory::class, 'integration_id', 'id');
    }

    /**
     * Retrieves all the accounts under this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(Account::class, 'integration_id', 'id');
    }

    /**
     * Retrieves all the categories under this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function brands()
    {
        return $this->hasMany(Brand::class, 'integration_id', 'id');
    }

    /**
     * END - Relationship Methods
     */
}
