<template>
    <span>
        <template v-if="status !== 'pending'">
            <b-button variant="info" class="mt-2" size="sm" @click="confirmBill" :class="{ disabled: disableBulkBill() }"><i class="fas fa-file-invoice"></i> Airway Bill</b-button>
        </template>
        <!-- <b-button variant="info" class="mt-2" size="sm" @click="confirmAddress" :class="{ disabled: disableBulkAddress() }"><i class="fas fa-address-book"></i> Address</b-button>
        <template v-if="status === 'processing' || status === 'ready_to_ship'">
            <b-button variant="info" class="mt-2" size="sm" @click="confirmShippingStatement" :class="{ disabled: disableBulkShippingStatement() }"><i class="fas fa-tasks"></i> Shipping Statement</b-button>
        </template> -->
        <qoo10_-legacy-bulk-estimated-date-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></qoo10_-legacy-bulk-estimated-date-order-component>
        <qoo10_-legacy-bulk-cancel-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></qoo10_-legacy-bulk-cancel-order-component>
    </span>
</template>

<script>
    import Qoo10_LegacyBulkEstimatedDateOrderComponent from "./Qoo10_LegacyBulkEstimatedDateOrderComponent";
    import Qoo10_LegacyBulkCancelOrderComponent from "./Qoo10_LegacyBulkCancelOrderComponent";
    export default {
        name: "Qoo10_LegacyOrderBulkActionComponent",
        components: {Qoo10_LegacyBulkCancelOrderComponent, Qoo10_LegacyBulkEstimatedDateOrderComponent},
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
        methods: {
            disableBulkBill() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if (item.fulfillment_status !== 1 || item.shipment_provider === 'Seller Delivery') {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            disableBulkAddress() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        if (order.fulfillment_status >= 30) {
                            disable = true;
                        }
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            disableBulkShippingStatement() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        if (order.fulfillment_status < 1 || order.fulfillment_status >= 30) {
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
                    order.items.forEach((item) => {
                        if (item.fulfillment_status !== 1 || item.shipment_provider === 'Seller Delivery') {
                            error = true;
                            let order_id = order.external_id ? order.external_id : order.id;
                            notify('top', 'Error', 'Order ['+ order_id +'] does not support this airway bill', 'center', 'danger');
                            return false;
                        }
                    });
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
                axios.post('/web/orders/bulk/' + order_ids.join(",") + '/qoo10_legacy/airwayBill', {
                    is_bulk: true
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let w = window.open("about:blank", "_blank");
                        w.document.write(data.response);
                        w.document.write('<script>window.addEventListener("load", function(){\n' +
                            '        window.print();\n' +
                            '        window.onfocus=function(){ window.close();}\n' +
                            '    });<' + '/script>');
                        w.document.close();
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
            },
            confirmAddress() {
                // Make sure there is at least one order
                if (Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    if (order.fulfillment_status >= 30) {
                        error = true;
                        let order_id = order.external_id ? order.external_id : order.id;
                        notify('top', 'Error', 'Order ['+ order_id +'] does not support address', 'center', 'danger');
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

                notify('top', 'Info', 'Getting address..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/bulk/' + order_ids.join(",") + '/qoo10_legacy/printAddress', {
                    is_bulk: true
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let w = window.open("about:blank", "_blank");
                        w.document.write(data.response);
                        w.document.close();
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
            },
            confirmShippingStatement() {
                // Make sure there is at least one order
                if (Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    if (order.fulfillment_status < 1 || order.fulfillment_status >= 30) {
                        error = true;
                        let order_id = order.external_id ? order.external_id : order.id;
                        notify('top', 'Error', 'Order ['+ order_id +'] does not support this shipping statement', 'center', 'danger');
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

                notify('top', 'Info', 'Getting shipping statement..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/bulk/' + order_ids.join(",") + '/qoo10_legacy/shippingStatement', {
                    is_bulk: true
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let w = window.open("about:blank", "_blank");
                        w.document.write(data.response);
                        w.document.close();
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
