<template>
    <span>
        <template v-if="status === 'ready_to_ship'">
            <b-button variant="info" class="mr-0 mt-2" size="sm" @click="confirmBill" :class="{ disabled: disableBulkBill() }"><i class="fas fa-file-invoice"></i> Airway Bill</b-button>
        </template>
        <shopee-bulk-cancel-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></shopee-bulk-cancel-order-component>
        <shopee-bulk-fulfillment-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></shopee-bulk-fulfillment-order-component>
    </span>
</template>

<script>
    import ShopeeBulkCancelOrderComponent from "./ShopeeBulkCancelOrderComponent";
    import ShopeeBulkFulfillmentOrderComponent from "./ShopeeBulkFulfillmentOrderComponent";
    export default {
        name: "ShopeeOrderBulkActionComponent",
        components: {ShopeeBulkFulfillmentOrderComponent, ShopeeBulkCancelOrderComponent},
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
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
        methods: {
            disableBulkBill() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        if (order.fulfillment_status !== 10 && order.fulfillment_status !== 13) {
                            disable = true;
                        }
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            confirmBill() {
                // Make sure there is at least one order
                if (Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    if (order.fulfillment_status !== 10 && order.fulfillment_status !== 13) {
                        error = true;
                        let order_id = order.external_id ? order.external_id : order.id;
                        notify('top', 'Error', 'Order ['+ order_id +'] does not support print airway bill', 'center', 'danger');
                        return false;
                    }
                });

                if (error) {
                    return;
                }

                if (this.sending_request) {
                    return;
                }

                let order_ids = [];
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order_ids.push(order.id);
                });

                notify('top', 'Info', 'Getting airway bill..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/bulk/' + order_ids.join(",") + '/shopee/bill', {
                    is_bulk: true
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (data.response.airway_bills[0]) {
                            window.open(data.response.airway_bills[0], '_blank');
                        } else {
                            notify('top', 'Error', 'Unable to get bill url', 'center', 'danger');
                        }
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.debug[0] && error.response.data.debug[0].message) {
                        notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            }
        }
    }
</script>

<style scoped>

</style>
