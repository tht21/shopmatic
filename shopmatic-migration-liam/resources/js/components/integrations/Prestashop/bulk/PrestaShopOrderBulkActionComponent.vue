<template>
    <div>
        <presta-shop-bulk-fulfillment-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></presta-shop-bulk-fulfillment-order-component>
    </div>
</template>

<script>
    import PrestaShopBulkFulfillmentOrderComponent from "./PrestaShopBulkFulfillmentOrderComponent";
    export default {
        name: "PrestaShopOrderBulkActionComponent",
        components: {PrestaShopBulkFulfillmentOrderComponent},
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
