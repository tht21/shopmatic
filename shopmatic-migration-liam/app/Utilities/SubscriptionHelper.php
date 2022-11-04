<?php

namespace App\Utilities;

use App\Constants\Subscription;
use Illuminate\Support\Facades\Log;

class SubscriptionHelper {

    /**
     * Check integration account limit
     * STARTER plan can add up to 3 account
     *
     * @return boolean
     */
    public static function checkUserLimit()
    {
        $shop = session('shop');
        // e2e
        if ($shop->e2e) {
            return true;
        }

        /* subscription checking */
        $type = Subscription::TYPE()->getValue();

        if ($shop->subscribed(Subscription::TYPE()->getValue()) &&
            $shop->subscription($type)->stripe_plan == "starter_monthly" &&
            $shop->users()->count() >= Subscription::STARTER_USER_LIMIT()->getValue()) {
            return false;
        } elseif ($shop->subscribed(Subscription::TYPE()->getValue()) &&
            $shop->subscription($type)->stripe_plan == "professional_monthly" &&
            $shop->users()->count() >= Subscription::PROFESSIONAL_USER_LIMIT()->getValue()) {
            return false;
        } elseif ($shop->subscribed(Subscription::TYPE()->getValue()) &&
            $shop->subscription($type)->stripe_plan == "advance_monthly" &&
            $shop->users()->count() >= Subscription::ADVANCE_USER_LIMIT()->getValue()) {
            return false;
        }

        return true;
    }

    /**
     * Check integration account limit
     * STARTER plan can add up to 3 account
     *
     * @return boolean
     */
    public static function checkAccountLimit()
    {
        $shop = session('shop');
        // e2e
        if ($shop->e2e) {
            return true;
        }

        /* subscription checking */
        $type = Subscription::TYPE()->getValue();
        if ($shop->subscribed(Subscription::TYPE()->getValue()) &&
            $shop->subscription($type)->stripe_plan == "starter_monthly" &&
            $shop->accounts()->count() >= Subscription::STARTER_ACCOUNT_LIMIT()->getValue()) {
            return false;
        } elseif ($shop->subscribed(Subscription::TYPE()->getValue()) &&
            $shop->subscription($type)->stripe_plan == "professional_monthly" &&
            $shop->accounts()->count() >= Subscription::PROFESSIONAL_ACCOUNT_LIMIT()->getValue()) {
            return false;
        } elseif ($shop->subscribed(Subscription::TYPE()->getValue()) &&
            $shop->subscription($type)->stripe_plan == "advance_monthly" &&
            $shop->accounts()->count() >= Subscription::ADVANCE_ACCOUNT_LIMIT()->getValue()) {
            return false;
        }
        return true;
     }

     /**
     * Check sku (inventories) limit
     * STARTER plan allowed up to 1000 sku
     * ADVANCE plan allowed up to 8000 sku
     *
     * @return boolean
     */
     public static function checkSkuLimit($shop, $product)
     {
        // e2e
        if ($shop->e2e) {
            return true;
        }
        /* subscription checking */
        $type = Subscription::TYPE()->getValue();
        $limit = 0;

        /* get plan allowed sku limit */
        if ($shop->subscription($type)->stripe_plan == "starter_monthly") {
            $limit = Subscription::STARTER_SKU_LIMIT()->getValue();
        } elseif ($shop->subscription($type)->stripe_plan == "professional_monthly") {
            $limit = Subscription::PROFESSIONAL_SKU_LIMIT()->getValue();
        } elseif ($shop->subscription($type)->stripe_plan == "advance_monthly") {
            return true;
        }

        /* start checking only  if inventories count is more/equal than limit */
        if ($shop->total_sku_count >= $limit) {
            /* get all sku, include those in variants */
            $skus = [$product->associatedSku];
            foreach ($product->variants as $key => $value) {
                if (!in_array($value->sku, $skus)) {
                    $skus[] =  $value->sku;
                }
            }
            $skus = array_filter($skus);

            /* check only if there's new sku */
            if ($shop->inventories()->whereIn('sku', $skus)->count() < count($skus)) {
                return false;
            }
        }
        return true;
     }
}
