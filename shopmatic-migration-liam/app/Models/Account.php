<?php

namespace App\Models;

use App\Constants\AccountStatus;
use App\Constants\IntegrationSyncData;
use App\Factories\ClientFactory;
use App\Factories\OrderAdapterFactory;
use App\Factories\ProductAdapterFactory;
use App\Integrations\AbstractClient;
use App\Integrations\AbstractOrderAdapter;
use App\Integrations\AbstractProductAdapter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\Account
 *
 * @property int $id
 * @property int $shop_id
 * @property int $integration_id
 * @property int $region_id
 * @property string $name
 * @property string|null $currency
 * @property array|null $credentials
 * @property array|null $sync_data
 * @property array|null $additional_data
 * @property array|null $settings
 * @property AccountStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\Integration $integration
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductListing[] $listings
 * @property-read int|null $listings_count
 * @property-read \App\Models\Region $region
 * @property-read \App\Models\Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AccountCategory[] $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $locations
 * @property-read int|null $locations_count
 * @property-read string|boolean $has_category
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account active()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Account onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereCredentials($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereIntegrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereSyncData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Account withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Account withoutTrashed()
 * @mixin \Eloquent
 * @property-write mixed $raw
 */
class Account extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    protected $productAdapter = null;
    protected $orderAdapter = null;

    protected $client = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'integration_id', 'region_id', 'name', 'currency', 'credentials', 'sync_data', 'additional_data',
        'settings', 'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'credentials', 'deleted_at', 'additional_data'/*, 'settings'*/
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'credentials' => 'array',
        'sync_data' => 'array',
        'additional_data' => 'array',
        'settings' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * all value = ['has_category']
     * @var array
     */
    protected $appends = [
        'status_text'
    ];

    /**
     * Overwrite model boot
     *
     */
    public static function boot()
    {
        parent::boot();

        self::creating(function($model){
            /**
             * Check if account has been added in another shop
             */
            if (Account::where('name', $model->name)
                ->where('integration_id', $model->integration_id)
                ->where('shop_id', '!=', $model->shop_id)
                ->exists())
            {
                throw new \Exception('This account has been added by other shop.');
            }
        });
    }

    /**
     * START - Accessor / Mutator
     */

    /**
     * Converts the status for account to a constant
     *
     * @param $value
     *
     * @return AccountStatus
     */
    public function getStatusAttribute($value)
    {
        return new AccountStatus($value);
    }

    /**
     * Accessor to get the text representation of the product status
     *
     * @return int
     */
    public function getStatusTextAttribute()
    {
        return ucwords(str_replace('_', ' ', AccountStatus::search($this->status->getValue())));
    }

    /**
     * Record whether account has import integration/account category feature or not
     *
     * @return string|bool
     */
    public function getHasCategoryAttribute()
    {
        if ($this->hasFeature(['products', 'import_categories'])) {
            return 'integration';
        } elseif ($this->hasFeature(['products', 'import_account_categories'])) {
            return 'account';
        }
        return false;
    }

    /**
     * END - Accessor / Mutator
     */

    /**
     * START - Helper Methods
     */

    /**
     * Returns whether or not the account supports this feature
     * This also checks the setting to see if it's enabled or not
     *
     * @param array $feature
     *
     * @return boolean
     */
    public function hasFeature(array $feature)
    {
        $settingVal = array_retrieve($this->settings, $feature);

        //This means it's not in the settings, so we check the integration instead
        if (is_null($settingVal)) {
            return $this->integration->hasFeature($this->region_id, $feature);
        }

        //This is if there is a value set, but we want a boolean, so we check if it's empty since 0 = false anyway
        return !empty($settingVal);
    }

    /**
     * Returns the setting value
     *
     * @param array $feature
     *
     * @param null $default
     * @return boolean
     */
    public function getSetting($feature, $default = null)
    {
        $settingVal = array_retrieve($this->settings, $feature);
        if (!is_null($settingVal)) {
            return $settingVal;
        }
        return $default;
    }

    /**
     * Returns the ProductAdapter based on the integration
     *
     * @return AbstractProductAdapter
     * @throws \Exception
     */
    public function getProductAdapter()
    {
        if (empty($this->productAdapter)) {
            $this->productAdapter = ProductAdapterFactory::create($this);
        }
        return $this->productAdapter;
    }

    /**
     * Returns the ProductAdapter based on the integration
     *
     * @return AbstractOrderAdapter
     * @throws \Exception
     */
    public function getOrderAdapter()
    {
        if (empty($this->orderAdapter)) {
            $this->orderAdapter = OrderAdapterFactory::create($this);
        }
        return $this->orderAdapter;
    }

    /**
     * Returns the Client based on the integration
     *
     * @return AbstractClient
     * @throws \Exception
     */
    public function getClient()
    {
        if (empty($this->client)) {
            $this->client = ClientFactory::create($this);
        }
        return $this->client;
    }

    /**
     * Get integration attributes from adapter
     *
     * @return array
     * @throws \Exception
     */
    public function getIntegrationAttributes()
    {
        $adapter = $this->getProductAdapter();
        return $adapter->getIntegrationAttributes();
    }

    /**
     * Get integration attributes from adapter
     *
     * @return array
     * @throws \Exception
     */
    public function transformAttributes($attributes)
    {
        $adapter = $this->getProductAdapter();
        return $adapter->transformAttributes($attributes);
    }

    /**
     * Get integration attributes from adapter
     *
     * @return array
     * @throws \Exception
     */
    public function getLogisticsAttributes()
    {
        return Cache::remember('account-' . $this->id . '-logistics', 7 * 24 * 60 * 60, function() {
            $adapter = $this->getProductAdapter();
            return $adapter->retrieveLogistics();
        });
    }

    /**
     * Returns the supported price types for the integration / account
     *
     * @return array
     * @throws \Exception
     */
    public function getPriceTypes()
    {
        return Cache::remember('account-' . $this->id . '-prices', 7 * 24 * 60 * 60, function() {
            $adapter = $this->getProductAdapter();
            return $adapter->getPriceTypes();
        });
    }

    /**
     * Updates the value for the key for the region in sync_data
     *
     * @param IntegrationSyncData $key
     * @param $value
     * @param bool $carbon Convert a Carbon object into a proper timestamp that can be retrieved as an object
     */
    public function setSyncData(IntegrationSyncData $key, $value, $carbon = false)
    {
        // Retrieve all sync data so we won't remove anything else
        $syncData = $this->sync_data;

        // If the sync_data somehow isn't an array
        if (!is_array($syncData)) {
            $syncData = [];
        }

        if ($carbon || $value instanceof Carbon) {
            /** @var Carbon $value */
            $value = $value->getTimestamp();
        }
        $syncData[$key->getValue()] = $value;

        $this->sync_data = $syncData;
        $this->save();
    }

    /**
     * Updates the value for the key for the region in sync_data
     *
     * @param IntegrationSyncData $key
     * @param $default
     * @param bool $carbon Whether or not to convert to a Carbon object
     * @return mixed
     */
    public function getSyncData(IntegrationSyncData $key, $default, $carbon = false)
    {
        // Retrieve all sync data so we won't remove anything else
        if (isset($this->sync_data[$key->getValue()])) {
            if (!$carbon) {
                return $this->sync_data[$key->getValue()];
            }
            return Carbon::createFromTimestamp($this->sync_data[$key->getValue()]);
        }

        return $default;
    }

    /**
     * END - Helper Methods
     */


    /**
     * START - Scopes
     */

    /**
     * Adds an active() scope for Account to be used in queries
     *
     * @param $query Builder
     *
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', AccountStatus::ACTIVE());
    }

    /**
     * END - Scopes
     */

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the region the account belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    /**
     * Retrieves the region the account belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'id');
    }

    /**
     * Retrieves the all listings in this account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listings()
    {
        return $this->hasMany(ProductListing::class, 'account_id', 'id');
    }

    /**
     * Retrieves the all orders in this account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'account_id', 'id');
    }

    /**
     * Retrieves the all order items in this account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, Order::class, 'account_id', 'order_id', 'id', 'id');
    }

    /**
     * Retrieves the shop the account belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all the account categories under this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany(AccountCategory::class, 'account_id', 'id');
    }

    /**
     * Retrieves all the locations under this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function locations()
    {
        return $this->hasMany(Location::class, 'account_id', 'id');
    }

    public function product_import_tasks()
    {
        return $this->morphMany(ProductImportTask::class, 'sourceable');
    }

    /**
     * END - Relationship Methods
     */

}
