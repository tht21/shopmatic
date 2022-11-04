<?php


namespace App\Integrations;

use App\Constants\FulfillmentStatus;
use App\Constants\InventoryStatus;
use App\Constants\PaymentStatus;
use App\Constants\ProductIdentifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductInventory;
use App\Models\ProductListing;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use App\Models\Integration;
use App\Integrations\Lazada\Constant;

class TransformedOrderItem
{

    public $externalId;
    public $externalProductId;
    public $name;
    public $sku;
    public $variationName;
    public $variationSku;
    public $quantity;

    public $itemPrice;
    public $integrationDiscount;
    public $sellerDiscount;
    public $shippingFee;
    public $tax;
    public $tax2;
    public $grandTotal;

    public $buyerPaid;
    public $fulfillmentStatus;
    public $shipmentProvider;
    public $shipmentMethod;
    public $trackingNumber;
    public $shipmentType;

    public $returnStatus;

    public $costOfGoods;

    public $actualShippingFee;

    public $data;

    /**
     * TransformedOrderItem constructor.
     *
     *
     * @param $externalId
     * @param $externalProductId
     * @param string $name Name of the variant
     * @param string $sku Actual SKU used for inventory syncing
     * @param $variationName
     * @param $variationSku
     * @param $quantity
     * @param $itemPrice
     * @param $integrationDiscount
     * @param $sellerDiscount
     * @param $shippingFee
     * @param $tax
     * @param $tax2
     * @param $grandTotal
     * @param $buyerPaid
     * @param FulfillmentStatus $fulfillmentStatus
     * @param $shipmentProvider
     * @param $shipmentType
     * @param $shipmentMethod
     * @param $trackingNumber
     * @param $returnStatus
     * @param $costOfGoods
     * @param $actualShippingFee
     *
     * @param array $data This should ONLY be attributes that's not in any of the other fields. As we'll display this to the users.
     */
    public function __construct(
        $externalId,
        $externalProductId,
        $name,
        $sku,
        $variationName,
        $variationSku,
        $quantity,
        $itemPrice,
        $integrationDiscount,
        $sellerDiscount,
        $shippingFee,
        $tax,
        $tax2,
        $grandTotal,
        $buyerPaid,
        FulfillmentStatus $fulfillmentStatus,
        $shipmentProvider,
        $shipmentType,
        $shipmentMethod,
        $trackingNumber,
        $returnStatus,
        $costOfGoods,
        $actualShippingFee,
        $data
    ) {
        $this->externalId = $externalId;
        $this->externalProductId = $externalProductId;
        $this->name = $name;
        $this->sku = $sku;
        $this->variationName = $variationName;
        $this->variationSku = $variationSku;
        $this->quantity = $quantity;

        $this->itemPrice = $itemPrice;
        $this->integrationDiscount = $integrationDiscount;
        $this->sellerDiscount = $sellerDiscount;
        $this->shippingFee = $shippingFee;
        $this->tax = $tax;
        $this->tax2 = $tax2;
        $this->grandTotal = $grandTotal;

        $this->buyerPaid = $buyerPaid;
        $this->fulfillmentStatus = $fulfillmentStatus;
        $this->shipmentProvider = $shipmentProvider;
        $this->shipmentType = $shipmentType;
        $this->shipmentMethod = $shipmentMethod;
        $this->trackingNumber = $trackingNumber;
        $this->returnStatus = $returnStatus;

        $this->actualShippingFee = $actualShippingFee;

        $this->costOfGoods = $costOfGoods;

        $this->data = $data;
    }

    /**
     * Create or updates the order item
     *
     * @param Order $order
     *
     * @param array $options
     * @return OrderItem|null
     * @throws \Exception
     */
    public function createItem(Order $order, $options)
    {
        $item = OrderItem::where([
            'order_id' => $order->id,
            'external_id' => $this->externalId,
            'external_product_id' => $this->externalProductId,
            'shop_id' => $order->shop_id,
            'account_id' => $order->account_id,
            'integration_id' => $order->integration_id
        ])->first();

        if (empty($item)) {
            $item = new OrderItem();
            $item->fill([
                'order_id' => $order->id,
                'external_id' => $this->externalId,
                'shop_id' => $order->shop_id,
                'account_id' => $order->account_id,
                'integration_id' => $order->integration_id,
                'inventory_status' => InventoryStatus::UNCHANGED()->getValue(),
            ]);
        }

        $item->fill([
            'external_product_id' => $this->externalProductId,
            'name' => $this->name,
            'sku' => $this->sku,
            'variation_name' => $this->variationName,
            'variation_sku' => $this->variationSku,
            'quantity' => $this->quantity,
            'item_price' => $this->itemPrice,
            'integration_discount' => $this->integrationDiscount,
            'seller_discount' => $this->sellerDiscount,
            'shipping_fee' => $this->shippingFee,
            'tax' => $this->tax,
            'tax_2' => $this->tax2,
            'grand_total' => $this->grandTotal,
            'buyer_paid' => $this->buyerPaid,
            'fulfillment_status' => $this->fulfillmentStatus,
            'shipment_provider' => $this->shipmentProvider,
            'shipment_type' => $this->shipmentType,
            'shipment_method' => $this->shipmentMethod,
            'tracking_number' => $this->trackingNumber,
            'return_status' => $this->returnStatus,
            'actual_shipping_fee' => $this->actualShippingFee,
            'cost_of_goods' => $this->costOfGoods,
            'data' => $this->data,
        ]);

        $listingOverride = false;
        if (empty($item->product_id)) {

            // This do while is just so we can exit it early instead of adding multiple checks to see if the product id was set
            // as most of the checks are in the inner conditions. This does NOT loop at all.
            do {
                // If we have the external ID of the product
                if (!empty($this->externalProductId)) {

                    /** @var ProductListing $listing */
                    $listing = $order->account->listings()->where('identifiers->' . ProductIdentifier::EXTERNAL_ID()->getValue(), $this->externalProductId)
                        ->whereNotNull('product_variant_id')->first();
                    if (!empty($listing)) {
                        if (!$listing->sync_stock) {
                            $listingOverride = true;
                        }
                        $item->product_id = $listing->product_id;
                        $item->product_variant_id = $listing->product_variant_id;
                        break;
                    }
                }

                // Here we check for the variation SKU if exists, and search for it in the shop's variants
                if (!empty($this->variationSku)) {
                    $variant = $order->shop->productVariants()->where('sku', $this->variationSku)->first();
                    if (!empty($variant)) {
                        $item->product_id = $variant->product_id;
                        $item->product_variant_id = $variant->id;
                        break;
                    }
                }

                // Next we search the SKU in the shop's variants
                if (!empty($this->sku)) {
                    $variant = $order->shop->productVariants()->where('sku', $this->sku)->first();
                    if (!empty($variant)) {
                        $item->product_id = $variant->product_id;
                        $item->product_variant_id = $variant->id;
                        break;
                    }
                }

                // Lastly, we search the SKU in the shop's products as at this stage we wont have a variant
                if (!empty($this->sku)) {
                    $product = $order->shop->products()->where('associated_sku', $this->sku)->first();
                    if (!empty($product)) {
                        $item->product_id = $product->id;
                        break;
                    }
                }
            } while (false);
        } else {
            $listing = $order->account->listings()->where('identifiers->' . ProductIdentifier::EXTERNAL_ID()->getValue(), $this->externalProductId)
                ->whereNotNull('product_variant_id')->first();
            if (!empty($listing)) {
                if (!$listing->sync_stock) {
                    $listingOverride = true;
                }
            }
        }

        if (empty($item->product_inventory_id)) {

            // We first check if we have the product variant ID to get the inventory ID
            if (!empty($item->product_variant_id)) {

                // Even though ID should be sufficient, this is just a double check for shop_id to prevent any mistakes
                $variant = ProductVariant::where(['id' => $item->product_variant_id, 'shop_id' => $order->shop_id])->first();

                // In case somehow the variant was deleted (?)
                $item->product_inventory_id = $variant ? $variant->inventory_id : null;
            }

            // If it still isn't set, let's search the inventory SKU
            if (empty($item->product_inventory_id) && !empty($this->sku)) {
                if ($inventory = $order->shop->inventories()->where('sku', $this->sku)->first()) {
                    $item->product_inventory_id = $inventory->id;
                }
            }
        }

        $item->save();

        $item = $item->fresh(['inventory']);

        // This checks for a valid order type (e.g. not shadow / draft / etc)
        if ($order->shouldModifyStock()) {

            // Whether or not we should deduct the stock
            if (!empty($options['deduct']) && !$listingOverride) {

                if ($item->inventory_status == InventoryStatus::UNCHANGED()->getValue()) {


                    // First check payment status
                    if ($order->payment_status == PaymentStatus::PAID() || $order->payment_status == PaymentStatus::PROCESSING()) {

                        // Then we check the order item status
                        /** @var ProductInventory $inventory */
                        if ($inventory = $item->inventory) {
                            $inventory->deductStock(
                                $item->quantity,
                                'child',
                                'Order ' . ($order->external_id ? $order->external_id : $order->id) . ($order->external_source ? ' (' . $order->external_source . ')' : ''),
                                $order->id,
                                get_class($order),
                                false
                            ); // will sync inventory after order sync complete

                            $item->inventory_status = InventoryStatus::DEDUCTED()->getValue();
                        }
                    }
                } elseif ($item->inventory_status == InventoryStatus::DEDUCTED()->getValue()) {
                    $needRestockToZero = $this->needRestockToZero($order, $item);
                    if ($needRestockToZero == true) {
                        $changed = $item->quantity;
                        $special_reason = '';
                        $productInventory = $item->inventory;
                        if (!empty($productInventory)) {
                            if ($order->integration_id == Integration::LAZADA) {
                                $item_reason = trim($item->data['reason']);
                                if ($item_reason == Constant::CANCEL_REASON_INCORRECT_PRICING || $item_reason == Constant::CANCEL_REASON_OOS) {
                                    $special_reason = ' because of lazada logic ' . $item_reason;
                                    $changed = 0 - $productInventory->stock;
                                }
                            } else if ($order->integration_id == Integration::SHOPEE) {
                                if ($order->data['cancel_reason'] == 'OUT_OF_STOCK' || $order->data['cancel_reason'] == 'Out of Stock') {
                                    $special_reason = ' because of shopee out of stock';
                                    $changed = 0 - $productInventory->stock;
                                }
                            }
                            /** @var ProductInventory $inventory */
                            if ($inventory = $item->inventory) {
                                $inventory->addStock(
                                    $changed,
                                    'child',
                                    'Restocked from order ' . ($order->external_id ? $order->external_id : $order->id) . ($order->external_source ? ' (' . $order->external_source . ')' . ' ' . $special_reason : ''),
                                    $order->id,
                                    get_class($order),
                                    false
                                ); // will sync inventory after sync order

                                $item->inventory_status = InventoryStatus::UNCHANGED()->getValue();
                            }
                        }
                    } else {
                        // We need to add back if it's cancelled or somehow payment got reversed to unpaid
                        if (
                            $order->payment_status == PaymentStatus::UNPAID() ||
                            $order->payment_status == PaymentStatus::CANCELLED()
                        ) {
                            $changed = $item->quantity;
                            $productInventory = $item->inventory;
                            if ($inventory = $item->inventory) {
                                $inventory->addStock(
                                    $changed,
                                    'child',
                                    'Restocked from order ' . ($order->external_id ? $order->external_id : $order->id) . ($order->external_source ? ' (' . $order->external_source . ')' : ''),
                                    $order->id,
                                    get_class($order),
                                    false
                                ); // will sync inventory after sync order

                                $item->inventory_status = InventoryStatus::UNCHANGED()->getValue();
                            }
                        }
                    }
                } else {
                    set_log_extra('status', $item->inventory_status);
                    throw new \Exception('Unhandled item inventory status.');
                }

                $item->save();
            }
        }

        // We should probably sync the inventory for this regardless of status of order item just in case
        /** @var ProductInventory $inventory */
        if (($inventory = $item->inventory) && !$listingOverride) {
            $items = Cache::get('account-' . $order->account_id . '-inventory-sync', []);
            $items[] = $inventory->id;

            // To make sure we don't have duplicates - as it causes an un-needed extra sync
            $items = array_unique($items);
            Cache::forever('account-' . $order->account_id . '-inventory-sync', $items);
        }

        // This probably means the listing should have been updated on their end, so we should update it here as well.
        // NOTE: Refer to SyncOrders for this part
        if (!empty($this->externalProductId)) {

            /** @var ProductListing $listing */
            $listing = $order->account->listings()
                ->where('identifiers->' . ProductIdentifier::EXTERNAL_ID()->getValue(), $this->externalProductId)
                ->whereNotNull('product_variant_id')->first();

            if (!empty($listing)) {

                $items = Cache::get('account-' . $order->account_id . '-listing-sync', []);
                $items[] = $listing->id;
                Cache::forever('account-' . $order->account_id . '-listing-sync', $items);
            }
        }

        return $item;
    }

    private function needRestockToZero($order, $item)
    {
        if ($order->integration_id == Integration::LAZADA) {
            $item_reason = trim($item->data['reason']);
            if ($item_reason == Constant::CANCEL_REASON_INCORRECT_PRICING || $item_reason == Constant::CANCEL_REASON_OOS) {
                return true;
            }
        } else if ($order->integration_id == Integration::SHOPEE) {
            if ($order->data['cancel_reason'] == 'OUT_OF_STOCK' || $order->data['cancel_reason'] == 'Out of Stock') {
                return true;
            }
        }
        return false;
    }
}
