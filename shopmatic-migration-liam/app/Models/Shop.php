<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Facades\Mail;



/**
 * App\Models\Shop
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $logo
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $currency
 * @property string|null $braintree_id
 * @property string|null $paypal_email
 * @property string|null $card_brand
 * @property string|null $card_last_four
 * @property string|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $stripe_id
 * @property int $e2e
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Account[] $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAlert[] $alerts
 * @property-read int|null $alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read bool $is_multi_currency
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductInventory[] $inventories
 * @property-read int|null $inventories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductImportTask[] $productImportTasks
 * @property-read int|null $product_import_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Cashier\Subscription[] $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAlert[] $unreadAlerts
 * @property-read int|null $unread_alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductInventoryTrail[] $inventoryTrails
 * @property-read int|null $inventory_trails_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductListing[] $listings
 * @property-read int|null $listings_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductVariant[] $productVariants
 * @property-read int|null $product_variants_count
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Contact[] $contacts
 * @property-read int|null $contacts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductExportTask[] $productExportTasks
 * @property-read int|null $product_export_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ExportExcelTask[] $exportExcelTasks
 * @property-read int|null $export_excel_tasks_count
 * @property-write mixed $raw
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereStripeId($value)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Shop onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereBraintreeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereCardBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereCardLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop wherePaypalEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Shop withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Shop withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Shop whereE2e($value)
 * @mixin \Eloquent
 */
class Shop extends Model implements Auditable
{

    use Billable, \OwenIt\Auditing\Auditable, SoftDeletes, HasSlug, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'phone_number', 'currency', 'main_account_id', 'logo', 'e2e', 'total_sku_count', 'total_orders_count',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'braintree_id', 'paypal_email', 'card_brand', 'card_last_four', 'trial_ends_at', 'e2e', 'stripe_id'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_multi_currency'
    ];

    /**
     * Get the options for generating the slug.
     *
     * @return SlugOptions
     */
    public function getSlugOptions()
    {
        return SlugOptions::create()
                          ->generateSlugsFrom(['id', 'name'])
                          ->saveSlugsTo('slug')
                          ->doNotGenerateSlugsOnUpdate();
    }


    /**
     * Retrieves the current active subscription for the shop
     *
     * @return Subscription|null
     */
    public function getActiveSubscription()
    {
        /** @var Subscription $subscription */
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->valid()) {
                return $subscription;
            }
        }
        return null;
    }

    /**
     * Checks to see if the subscription is active
     *
     * @return bool
     */
    public function hasActiveSubscription()
    {
        return $this->getActiveSubscription() !== null;
    }

    /**
     * START - Accessor / Mutator
     */

    /**
     * Returns whether or not the shop uses multi currency
     *
     * @return boolean
     */
    public function getIsMultiCurrencyAttribute()
    {
        return !is_null($this->currency);
    }

    /**
     * END - Accessor / Mutator
     */

    /**
     * Send email notifications to the shop's email and all user's email id
     * Email notifications => Low stock | Out of stock | New order
     */
    public function sendEmailNotification($class)
    {
        $emails = $this->users->pluck('email')->toArray();
        array_push($emails, $this->email);
        $emails = array_unique($emails);
        Mail::to($emails)->queue($class);
    }

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the all accounts in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(Account::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the all users in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_shop_pivot', 'shop_id', 'user_id');
    }

    /**
     * Retrieves all products in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all products in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all listings in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listings()
    {
        return $this->hasMany(ProductListing::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all orders in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all order items in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all inventories in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->hasMany(ProductInventory::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all inventories in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventoryTrails()
    {
        return $this->hasMany(ProductInventoryTrail::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all product alerts in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function alerts()
    {
        return $this->hasMany(ProductAlert::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all product alerts in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unreadAlerts()
    {
        return $this->hasMany(ProductAlert::class, 'shop_id', 'id')
                    ->whereNull('dismissed_at');
    }

    /**
     * Retrieves the all product import tasks in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productImportTasks()
    {
        return $this->hasMany(ProductImportTask::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the all product export tasks in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productExportTasks()
    {
        return $this->hasMany(ProductExportTask::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the all export excel tasks in this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exportExcelTasks()
    {
        return $this->hasMany(ExportExcelTask::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the all contacts associated with this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'shop_id', 'id');
    }

    /**
     * Retrieves all reports associated with this shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'shop_id', 'id');
    }
    
    /**
     * END - Relationship Methods
     */

}
