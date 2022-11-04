<template>
    <div>
        <woocommerce-bulk-fulfillment-order-component :selected_orders.sync="orders" :selected_account="selected_account" :status="status"></woocommerce-bulk-fulfillment-order-component>
        <b-button variant="info" class="ml-2 mt-2" size="sm" @click="confirmRefund()" :class="{ disabled: disableBulkRefund() }"><i class="fas fa-redo"></i> Refund</b-button>
        <b-button variant="danger" class="mt-2" size="sm" @click="confirmCancel()" :class="{ disabled: disableBulkCancel() }"><i class="fas fa-times"></i> Cancel</b-button>
    </div>
</template>

<script>
    import WoocommerceBulkFulfillmentOrderComponent from "./WoocommerceBulkFulfillmentOrderComponent";
    export default {
        name: "WoocommerceOrderBulkActionComponent",
        components: {WoocommerceBulkFulfillmentOrderComponent},
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
            disableBulkRefund() {
                let disable = false;
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    disable = true;
                }
                return disable;
            },
            disableBulkCancel() {
                let disable = false;
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    disable = true;
                }
                return disable;
            },
            confirmRefund() {
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to refund.', 'center', 'danger');
                    return;
                }

                let title = 'Are you sure to refund the orders?';
                let text = 'Confirm to refunds?';

                swal.fire({
                    title: title,
                    text: text,
                    showCancelButton: true,
                    type: 'warning',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm!'
                }).then((result) => {
                    if (result.value) {
                        // Update order's refund status
                        if (this.sending_request) {
                            return;
                        }
                        this.sending_request = true;

                        notify('top', 'Info', 'Refunding..', 'center', 'info');

                        // Loop and update all selected order to refund
                        let promisedEvents = [];

                        Object.values(this.selected_orders[this.status]).forEach((order) => {
                            let selected_items = [];
                            order.items.forEach((item) => {
                                selected_items.push(item.id);
                            });

                            promisedEvents.push(axios.post('/web/orders/' + order.id + '/woocommerce/refund', {}).then((response) => {
                                let data = response.data;
                                if (data.meta.error) {
                                    notify('top', 'Error', data.meta.message, 'center', 'danger');
                                } else {
                                    notify('top', 'Success', 'Successfully refunded order! '+ order.id +'!', 'center', 'success');
                                }
                            }).catch((error) => {
                                if (error.response && error.response.data && error.response.data.debug[0] && error.response.data.debug[0].message) {
                                    notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                                } else if (error.response && error.response.data && error.response.data.meta) {
                                    notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                                } else {
                                    notify('top', 'Error', error, 'center', 'danger');
                                }
                            }));
                        });

                        // Close model and refresh once all is updated
                        Promise.all(promisedEvents).then(() => {
                            this.sending_request = false;

                            this.orders = {};
                            this.$parent.$parent.$parent.selectAccount(this.selected_account);
                        });
                    }
                })
            },
            confirmCancel() {
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to cancel.', 'center', 'danger');
                    return;
                }

                let title = 'Are you sure to cancel the orders?';
                let text = 'Confirm to cancel?';

                swal.fire({
                    title: title,
                    text: text,
                    showCancelButton: true,
                    type: 'warning',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm!'
                }).then((result) => {
                    if (result.value) {
                        // Update order's refund status
                        // Update order's fulfillment status
                        if (this.sending_request) {
                            return;
                        }
                        this.sending_request = true;

                        notify('top', 'Info', 'Cancelling Orders..', 'center', 'info');

                        // Loop and update all selected order to cancel
                        let promisedEvents = [];

                        Object.values(this.selected_orders[this.status]).forEach((order) => {
                            let selected_items = [];
                            order.items.forEach((item) => {
                                selected_items.push(item.id);
                            });

                            promisedEvents.push(axios.post('/web/orders/' + order.id + '/woocommerce/cancel', {}).then((response) => {
                                let data = response.data;
                                if (data.meta.error) {
                                    notify('top', 'Error', data.meta.message, 'center', 'danger');
                                } else {
                                    notify('top', 'Success', 'Successfully cancelled order! '+ order.id +'!', 'center', 'success');
                                }
                            }).catch((error) => {
                                if (error.response && error.response.data && error.response.data.debug[0] && error.response.data.debug[0].message) {
                                    notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                                } else if (error.response && error.response.data && error.response.data.meta) {
                                    notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                                } else {
                                    notify('top', 'Error', error, 'center', 'danger');
                                }

                            }));
                        });

                        // Close model and refresh once all is updated
                        Promise.all(promisedEvents).then(() => {
                            this.sending_request = false;

                            this.orders = {};
                            this.$parent.$parent.$parent.selectAccount(this.selected_account);
                        });
                    }
                })
            }

        }
    }
</script>

<style scoped>

</style>
