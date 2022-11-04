<template>
    <span>
        <template v-if="status === 'ready_to_ship'">
            <b-button variant="success" class="mt-2" size="sm" @click="openDeliveredModel()" :class="{ disabled: disableDelivered() }"><i class="fas fa-truck"></i> Delivered</b-button>
            <b-button variant="danger" class="mt-2" size="sm" @click="openFailedDeliverModel()" :class="{ disabled: disableFailedDeliver() }"><i class="fas fa-truck-loading"></i> Failed to deliver</b-button>
        </template>

        <b-modal id="delivered-order-modal" ref="delivered-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Delivered Order</h2>
                <button type="button" class="close" @click="closeDelivered" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Selected Orders</h3>
            <b-list-group>
                <template v-for="order in this.selected_orders[this.status]">
                    <b-list-group-item>ID: {{ order.external_id ? order.external_id : order.id }}</b-list-group-item>
                </template>
            </b-list-group>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closeDelivered">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmDelivered">Delivered</b-button>
            </template>
        </b-modal>

        <b-modal id="failed-deliver-order-modal" ref="failed-deliver-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Failed Deliver Order</h2>
                <button type="button" class="close" @click="closeFailedDeliver" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Failed Deliver Orders</h3>
            <b-list-group>
                <template v-for="order in this.selected_orders[this.status]">
                    <b-list-group-item>ID: {{ order.external_id ? order.external_id : order.id }}</b-list-group-item>
                </template>
            </b-list-group>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closeFailedDeliver">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFailedDeliver">Failed To Deliver</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "LazadaBulkDeliverComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
            }
        },
        methods: {
            disableDelivered() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if (item.fulfillment_status !== 10) {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            disableFailedDeliver() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if (item.fulfillment_status !== 10) {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            closeDelivered() {
                this.$refs['delivered-order-modal'].hide();
            },
            closeFailedDeliver() {
                this.$refs['failed-deliver-order-modal'].hide();
            },
            openDeliveredModel() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to delivered.', 'center', 'danger');
                    return;
                }
                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order.items.every((item) => {
                        if (item.fulfillment_status !== 10) {
                            error = true;
                            let order_id = order.external_id ? order.external_id : order.id;
                            notify('top', 'Error', 'Order ['+ order_id +'] does not support delivered', 'center', 'danger');
                            return false;
                        }
                    });
                });

                if (error) {
                    return;
                }
                this.$refs['delivered-order-modal'].show();
            },
            openFailedDeliverModel() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to failed deliver.', 'center', 'danger');
                    return;
                }
                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order.items.every((item) => {
                        if (item.fulfillment_status !== 10) {
                            error = true;
                            let order_id = order.external_id ? order.external_id : order.id;
                            notify('top', 'Error', 'Order ['+ order_id +'] does not support failed deliver', 'center', 'danger');
                            return false;
                        }
                    });
                });

                if (error) {
                    return;
                }
                this.$refs['failed-deliver-order-modal'].show();
            },
            confirmDelivered() {
                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to delivered.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                // Loop and update all selected order to delivered
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    let selected_items = [];
                    order.items.forEach((item) => {
                        selected_items.push(item.id);
                    });

                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/lazada/delivered', {
                        order_item_ids: selected_items
                    }).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully updated order ID '+ order.id +'!', 'center', 'success');
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

                    this.closeDelivered();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
            confirmFailedDeliver() {
                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to failed deliver.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                // Loop and update all selected order to ship
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    let selected_items = [];
                    order.items.forEach((item) => {
                        selected_items.push(item.id);
                    });

                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/lazada/failedDelivery', {
                        order_item_ids: selected_items
                    }).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully updated order ID '+ order.id +'!', 'center', 'success');
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

                    this.closeFailedDeliver();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
        }
    }
</script>

<style scoped>

</style>
