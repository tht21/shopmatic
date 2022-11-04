<template>
    <span>
        <shopify-bulk-refund-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></shopify-bulk-refund-order-component>
        <shopify-bulk-cancel-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></shopify-bulk-cancel-order-component>
    </span>
</template>

<script>
    import ShopifyBulkRefundOrderComponent from "./ShopifyBulkRefundOrderComponent";
    import ShopifyBulkCancelOrderComponent from "./ShopifyBulkCancelOrderComponent";
    export default {
        name: "ShopifyOrderBulkActionComponent",
        components: {ShopifyBulkCancelOrderComponent, ShopifyBulkRefundOrderComponent},
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                orders: this.selected_orders // Creating a local copy, so we can mutate and react to it
            }
        },
        watch: {
            // Every time parent component change value watch here
            selected_orders(val) {
                this.orders = val;
            },
            // Every time child change value watch here
            orders(val) {
                this.$emit('update:selected_orders', val);
            }
        },
    }
</script>

<style scoped>

</style>
