<template>
    <span>
        <b-button variant="primary" class="mt-2" size="sm" @click="openFulfillmentModel()" :class="{ disabled: disableBulkFulfillment() }"><i class="fas fa-check"></i> Update Status</b-button>

        <b-modal id="fulfillment-order-modal" ref="fulfillment-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Update Status</h2>
                <button type="button" class="close" @click="closeFulfillment" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Selected Orders</h3>
            <b-list-group>
                <template v-for="order in this.selected_orders[this.status]">
                    <b-list-group-item>ID: {{ order.external_id ? order.external_id : order.id }}</b-list-group-item>
                </template>
            </b-list-group>

            <h3 class="mt-3">Select a Status</h3>
            <b-form-select v-model="fulfillment_status" :options="fulfillment_options"></b-form-select>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeFulfillment">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFulfillment">Fulfillment</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "PrestaShopBulkFulfillmentOrderComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
                fulfillment_options:  [
                    { value: null, text: 'Please select a status'},
                    { value: 1, text: 'Processing'},
                    { value: 10, text: 'Ready To Ship'},
                    { value: 11, text: 'Shipped'},
                    { value: 12, text: 'Partially Shipped'},
                    { value: 20, text: 'Delivered'},
                    { value: 21, text: 'To Confirm Delivered'},
                    { value: 30, text: 'Cancelled'},
                    { value: 31, text: 'Request cancel'},
                    { value: 40, text: 'Returned'},
                ],
                fulfillment_status: null,
            }
        },
        methods: {
            disableBulkFulfillment() {
                let disable = false;
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    disable = true;
                }
                return disable;
            },
            openFulfillmentModel() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to update status.', 'center', 'danger');
                    return;
                }

                this.$refs['fulfillment-order-modal'].show();
            },
            closeFulfillment() {
                this.fulfillment_status = null;
                this.$refs['fulfillment-order-modal'].hide();
            },
            confirmFulfillment() {
                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to update status.', 'center', 'danger');
                    return;
                }

                let title = 'Are you sure to update the orders?';
                let text = 'Confirm to update status?';

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
                        // Update order's fulfillment status
                        if (this.sending_request) {
                            return;
                        }
                        this.sending_request = true;

                        notify('top', 'Info', 'Updating status..', 'center', 'info');

                        // Loop and update all selected order status
                        let promisedEvents = [];

                        Object.values(this.selected_orders[this.status]).forEach((order) => {
                            let selected_items = [];
                            order.items.forEach((item) => {
                                selected_items.push(item.id);
                            });

                            promisedEvents.push(axios.post('/web/orders/' + order.id + '/prestaShop/updateStatus', {
                                fulfillment_status: this.fulfillment_status,
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

                            this.closeFulfillment();
                            this.$emit('update:selected_orders', {});
                            this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                        })
                    }
                })
            },
        }
    }
</script>

<style scoped>

</style>
