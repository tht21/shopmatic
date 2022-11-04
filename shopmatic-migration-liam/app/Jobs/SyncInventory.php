<?php

namespace App\Jobs;

use App\Constants\AccountStatus;
use App\Constants\ProductAlertType;
use App\Events\NewProductAlert;
use App\Events\ProductFailedToImport;
use App\Interfaces\ProductAdapterInterface;
use App\Models\Integration;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTrail;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;
    protected $force;
    protected $trails;
    protected $task;

    /**
     * Create a new job instance.
     *
     * @param ProductInventory $inventory
     * @param bool $force This only forces it to update even if it's the same on our system.
     *                      It does NOT update if they have turned off sync for the integration / listing
     * @param bool $trails Whether or not to add the trails for this job
     */
    public function __construct(ProductInventory $inventory, $force = false, $trails = true, $task = null)
    {
        $this->inventory = $inventory;
        $this->force = $force;
        $this->trails = $trails;
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {

        /** @var ProductInventory $inventory */
        $inventory = $this->inventory->fresh(['variants.listings']);

        if (empty($inventory)) {
            return;
        }

        if (!$inventory->enabled) {
            return;
        }

        /** @var ProductVariant $variant */
        foreach ($inventory->variants as $variant) {

            /** @var ProductListing $listing */
            foreach ($variant->listings as $listing) {
                if (empty($listing->account)) {
                    continue;
                } elseif (!$listing->account->hasFeature(['inventory', 'sync_inventory']) || !$listing->account->hasFeature(['products', 'automatic_inventory_sync'])) {
                    continue;
                } elseif (!$listing->sync_stock) {
                    continue;
                } elseif (($listing->stock == $inventory->stock || ($listing->stock <= 0 && $inventory->stock <= 0)) && !$this->force) {
                    // This is because some integrations dont allow for negative stock, and our inventory allows for negative
                    // So we stop if the listing's stock is at 0 or less
                    // TODO: Might need to add an option for integrations whether or not they allow for negative stock. Currently we don't push negative
                    continue;
                } elseif ($listing->account->status != AccountStatus::ACTIVE()) {
                    continue;
                }

                // When updateStock, create product alert, and let it continue to update for the other listings
                try {
                    // This means the stock is different and we need to update it for the listing
                    /** @var ProductAdapterInterface $adapter */
                    $adapter = $listing->account->getProductAdapter();
                    if ($listing->account->integration_id == Integration::SHOPEE) {
                        $update_stock = $adapter->updateStock($listing, $inventory->stock, $inventory, $this->task);
                    } else {
                        $update_stock = $adapter->updateStock($listing, $inventory->stock, $inventory);
                    }

                    if ($this->trails) {
                        if ($inventory->stock != $listing->stock) {
                            ProductInventoryTrail::create([
                                'shop_id' => $inventory->shop_id,
                                'product_inventory_id' => $inventory->id,
                                'message' => 'Stock for listing ' . $listing->product->name . ' (' . $listing->identifier_text . ') updated from ' . $listing->stock . ' to ' . $inventory->stock . ' as it\'s different from the main stock.',
                                'related_id' => $listing->id,
                                'related_type' => get_class($listing),
                                'old' => $listing->stock,
                                'new' => $inventory->stock,
                            ]);
                        } else {
                            ProductInventoryTrail::create([
                                'shop_id' => $inventory->shop_id,
                                'product_inventory_id' => $inventory->id,
                                'message' => 'Stock for listing ' . $listing->product->name . ' (' . $listing->identifier_text . ') updated to ' . $inventory->stock . ' via force update.',
                                'related_id' => $listing->id,
                                'related_type' => get_class($listing),
                                'old' => $listing->stock,
                                'new' => $inventory->stock,
                            ]);
                        }
                    }

                    // Update stock in listing after successfully update to marketplace, if failed, the updateStock will throw exception
                    if ($update_stock) {
                        $listing->stock = $inventory->stock;
                        $listing->save();
                    }
                } catch (\Exception $e) {
                    logger()->error('[SyncInventory Job]|Account Id: ' . $listing->account->id . '|IntegrationName: ' . $listing->account->integration->name . '|Shop Id: ' . $inventory->shop_id . '|ProductName: ' . $listing->product->name . '|Exception: ' . $e->getMessage());
                    event(new NewProductAlert($listing->product, $e->getMessage(), ProductAlertType::ERROR()));
                    if ($e->getMessage() == 'Unable to update inventory.') {
                        if (!empty($this->task)) {
                            event(new ProductFailedToImport($this->task, "Unable to update stock for listing " . $variant->sku . " (" . $listing->id . "). Please check the inventory logs for this SKU in “All Inventory” page for more information."));
                        } else {
                            throw new \Exception($e->getMessage());
                        }
                    }
                }
            }

            // Update temp fields
            $variant->updateTempFields();
        }

        // Sync all bundled / related inventories as well
        foreach ($inventory->bundledInventories as $bundle) {
            if ($bundle->id === $inventory->id) {
                $inventory->bundledInventories()->detach([$bundle->id]);
                continue;
            }
            SyncInventory::dispatch($bundle)->onQueue('sync_inventories');
        }

    }
}
