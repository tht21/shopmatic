<template>
    <div>
        <div class="row">
            <div class="col-12">
                <woocommerce-fulfillment-order-component :order="order"></woocommerce-fulfillment-order-component>
                <woocommerce-cancel-order-component :order="order"></woocommerce-cancel-order-component>
                <woocommerce-refund-order-component :order="order"></woocommerce-refund-order-component>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
            <div class="col-12 mt-3 action-guide">
                <small><a href="#woocommerce-help" data-toggle="collapse" role="button" aria-expanded="false"
                          aria-controls="woocommerce-help">Guide & Help <i
                    class="fas fa-angle-double-down"></i></a></small>
                <div id="woocommerce-help" class="collapse mt-2">
                    <span class="text-muted">The flow for order processing for Woocommerce is</span>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    import WoocommerceFulfillmentOrderComponent from "./WoocommerceFulfillmentOrderComponent";
    export default {
        name: "WoocommerceOrderActionComponent",
        components: {WoocommerceFulfillmentOrderComponent},
        props: ['order'],
    }
</script>

<style scoped>

</style>
