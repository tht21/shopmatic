<template>
    <div>
        <lazada-bulk-print-order-component :selected_orders="orders" :status="status"></lazada-bulk-print-order-component>
        <lazada-bulk-pack-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></lazada-bulk-pack-order-component>
        <lazada-bulk-fulfillment-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></lazada-bulk-fulfillment-order-component>
        <lazada-bulk-cancel-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></lazada-bulk-cancel-order-component>
        <lazada-bulk-deliver-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></lazada-bulk-deliver-component>
    </div>
</template>

<script>
    import LazadaBulkPrintOrderComponent from "./LazadaBulkPrintOrderComponent";
    import LazadaBulkPackOrderComponent from "./LazadaBulkPackOrderComponent";
    import LazadaBulkFulfillmentOrderComponent from "./LazadaBulkFulfillmentOrderComponent";
    import LazadaBulkCancelOrderComponent from "./LazadaBulkCancelOrderComponent";
    import LazadaBulkDeliverComponent from "./LazadaBulkDeliverComponent";
    export default {
        name: "LazadaOrderBulkActionComponent",
        components: {
            LazadaBulkDeliverComponent,
            LazadaBulkCancelOrderComponent,
            LazadaBulkFulfillmentOrderComponent, LazadaBulkPackOrderComponent, LazadaBulkPrintOrderComponent},
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
        }
    }
</script>

<style scoped>

</style>
