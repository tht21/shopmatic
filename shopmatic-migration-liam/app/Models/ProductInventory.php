<?php

namespace App\Models;

use App\Jobs\SyncInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Shop;
use App\Mail\OutOfStockNotification;
use App\Mail\LowStockNotification;

/**
 * App\Models\ProductInventory
 *
 * @property int $id
 * @property int $shop_id
 * @property string $sku
 * @property string|null $name
 * @property int $manage_stock
 * @property int $stock
 * @property int $low_stock_notification
 * @property int $out_of_stock_notification
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $status
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read mixed $total_overrides
 * @property-read mixed $total_products
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductListing[] $listings
 * @property-read int|null $listings_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductInventoryTrail[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductVariant[] $variants
 * @property-read int|null $variants_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductInventory[] $bundledInventories
 * @property-read int|null $bundled_inventories_count
 * @property-read int $last_change
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductInventory[] $parentInventories
 * @property-read int|null $parent_inventories_count
 * @property-write mixed $raw
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereLowStockNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereManageStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereOutOfStockNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductInventory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductInventory withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProductInventory withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductInventory whereStatus($value)
 * @mixin \Eloquent
 */
class ProductInventory extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable, SoftDeletes;

    /**
     *      ---------
     *      | NOTES |
     *      ---------
     *
     * 1.   `enabled` is whether or not we should update / sync this inventory
     *
     * 2.   `low_stock_notification` is the amount of stock if it deducts to this amount, it will send the notification.
     *      0 to disable this
     *
     *
     */


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id', 'sku', 'name', 'stock', 'low_stock_notification', 'out_of_stock_notification', 'enabled', 'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'shop_id'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'total_products', 'total_overrides', 'last_change'
    ];

    /**
     * Retrieving the total listings count
     *
     * @return int
     */
    public function getTotalProductsAttribute()
    {
        return $this->listings()->count();
    }

    /**
     * Retrieving the total listings count
     *
     * @return int
     */
    public function getLastChangeAttribute()
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * Retrieving the total listings NOT using the stock from the inventory
     *
     * @return int
     */
    public function getTotalOverridesAttribute()
    {
        return $this->listings()->where('sync_stock', 0)->count();
    }

    /**
     * Shorter helper method for clarity
     *
     * @param $amount
     * @param string $direction
     * @param null $message
     * @param null $relatedId
     * @param null $relatedType
     * @param bool $sync
     * @throws \Exception
     */
    public function addStock($amount, $direction = 'child', $message = null, $relatedId = null, $relatedType = null, $sync = true)
    {
        $this->modifyInventory($amount, $direction, $message, $relatedId, $relatedType, $sync);
    }

    /**
     * Shorter helper method for clarity
     *
     * @param $amount
     * @param string $direction
     * @param null $message
     * @param null $relatedId
     * @param null $relatedType
     * @param bool $sync
     * @param bool $email Whether or not to send email notification
     * @throws \Exception
     */
    public function deductStock($amount, $direction = 'child', $message = null, $relatedId = null, $relatedType = null, $sync = true, $email = true)
    {
        if ($email) {
            $newStock = $this->stock - $amount;

            // If it's 0, it's disabled
            if ($this->low_stock_notification > 0) {

                // We only notify if this change brings it down below the threshold
                // If it's 0, we do not notify, as that will be out of stock
                if ($this->stock > $this->low_stock_notification && $newStock <= $this->low_stock_notification && $newStock != 0) {
                    $this->shop->sendEmailNotification(new LowStockNotification($this, $newStock));
                }
            }
            if ($newStock <= 0 && $this->stock > 0) {
                if ($this->out_of_stock_notification) {
                    $this->shop->sendEmailNotification(new OutOfStockNotification($this));
                }
            }
        }
        $this->modifyInventory(-$amount, $direction, $message, $relatedId, $relatedType, $sync);

    }

    /**
     * Modifies the inventory for
     *
     * @param $change
     * @param string $direction Accepted values - 'child', 'parent', 'none'.
     *                          This means whether we should update the parent bundle, children, or none at all.
     * @param null $message
     * @param null $relatedId
     * @param null $relatedType
     * @param bool $sync Whether or not we should trigger a SyncInventory
     * @throws \Exception
     */
    public function modifyInventory($change, $direction = 'child', $message = null, $relatedId = null, $relatedType = null, $sync = true, $stockByApiUpdateSellableQuantity = 0, $task = null)
    {
        $direction = strtolower($direction);

        // Should we just ignore this?
//        Cache::lock('inventory-' . $this->id)->block(10, function () use ($change, $relatedId, $relatedType) {

            // Refresh just in case it's old
            $this->fresh();

            $old = $this->stock;

            $stockNew = $this->stock + $change;
            if ($stockByApiUpdateSellableQuantity > 0){
                $stockNew = $stockByApiUpdateSellableQuantity;
            }
            $this->stock = $stockNew;

            if (!empty($message)) {
                ProductInventoryTrail::create([
                    'shop_id' => $this->shop_id,
                    'product_inventory_id' => $this->id,
                    'message' => $message,
                    'related_id' => $relatedId,
                    'related_type' => $relatedType,
                    'old' => $old,
                    'new' => $stockNew,
                ]);
            }
            $this->save();

            // update variants stock
            foreach ($this->variants as $key => $variant) {
                $variant->stock = $this->stock;
                $variant->save();
            }

//        });

        // TODO: Currently we do NOT update the parent bundle, this behaviour needs to be checked against whether or not we should
        if ($direction === 'child') {
            foreach ($this->bundledInventories as $inventory) {

                // This amount is the amount that needs to be changed for EACH stock of the current inventory
                $amount = $inventory->pivot->deduct_amount * $change;
                // filter same message to shorten message length
                if (strpos($message, 'Bundle from ' . $this->sku . '. ') !== false) {
                    $message = 'Bundle from ' . $this->sku . '. ' . $message;
                }
                $inventory->modifyInventory($amount, $direction, $message, $relatedId, $relatedType, false);
            }
        }

        // This is to reduce the stock for parent inventory to the lowest possible one based on the bundle
        foreach ($this->parentInventories as $inventory) {
            $inventory->recalculateLowestStock(true, $direction !== 'none');
        }

        // Don't allow them to set more than the stock allowed for child
        if ($this->bundledInventories->count()) {
            $this->recalculateLowestStock(!$sync, $direction !== 'none');
        }

        if ($sync) {
            // Sync without leaving a trail
            SyncInventory::dispatchNow($this, true, true, $task);
        }

    }

    /**
     * Recalculates the stock for this in case the children's stock changes
     *
     * @param bool $sync
     * @param bool $email
     * @throws \Exception
     */
    public function recalculateLowestStock($sync = false, $email = true)
    {
        $lowestStock = $this->stock;
        $affectedBy = null;
        foreach ($this->bundledInventories as $inventory) {
            $available = floor($inventory->stock / $inventory->pivot->deduct_amount);
            if ($available < $lowestStock) {
                $lowestStock = $available;
                $affectedBy = $inventory;
            }
        }

        if ($lowestStock != $this->stock && $lowestStock < $this->stock) {
            // Direction none so it does not reduce the child stock here
            $this->deductStock($this->stock - $lowestStock, 'none', 'Stock reduced as stock for ' . $affectedBy->sku . ' changed to ' . $affectedBy->stock, $affectedBy->id, get_class($affectedBy), $sync, $email);
        }
    }

    /**
     * START - Relationship Methods
     */

    /**
     * Retrieves the listings for this inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function listings()
    {
        return $this->hasManyThrough(ProductListing::class, ProductVariant::class, 'inventory_id', 'product_variant_id');
    }

    /**
     * Retrieves the variants for this inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'inventory_id', 'id');
    }

    /**
     * Retrieves the variants for this inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
        return $this->hasMany(ProductInventoryTrail::class, 'product_inventory_id', 'id');
    }

    /**
     * Retrieves the shop for this inventory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    /**
     * Retrieves the related inventories for this
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bundledInventories()
    {
        // return $this->hasMany(ProductInventoryPivot::class);
        return $this->belongsToMany(ProductInventory::class, 'product_inventory_pivot', 'product_inventory_id', 'deduct_product_inventory_id')->withPivot('deduct_amount')->withTimestamps();
    }

    /**
     * Retrieves the bundle this inventory belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parentInventories()
    {
        return $this->belongsToMany(ProductInventory::class, 'product_inventory_pivot', 'deduct_product_inventory_id', 'product_inventory_id')->withPivot('deduct_amount')->withTimestamps();
    }

    /**
     * END - Relationship Methods
     */

}
