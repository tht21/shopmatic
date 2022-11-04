<template>
    <span>
        <template v-if="status === 'processing'">
            <b-button variant="primary" class="mt-2" size="sm" @click="openEstimated()" :class="{ disabled: disableBulkEstimated() }"><i class="fas fa-calendar"></i> Update Estimated Shipping Date</b-button>
        </template>

        <b-modal id="estimated-order-modal" ref="estimated-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Update Estimated Date Order</h2>
                <button type="button" class="close" @click="closeEstimated" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Select estimated shipping date</h3>
            <b-form-input type="date" :min="today" v-model="form.estimated_date"></b-form-input>

            <h3 class="mt-4">Select reason for shipping delay</h3>
            <b-form-select v-model="form.delay_reason" :options="reason_options"></b-form-select>


            <h3 class="mt-4">Reason for shipping delay</h3>
            <b-form-textarea
                v-model="form.delay_reason_description"
                placeholder="Optional"
                rows="5"
                max-rows="10"
            ></b-form-textarea>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closeEstimated">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmEstimated">Update</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "Qoo10_LegacyBulkEstimatedDateOrderComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
                today: new Date().toISOString().slice(0, 10),
                reason_options : [
                    { text : 'Please select a reason', value : null },
                    { text : 'Preparing', value : 'PR' },
                    { text : 'Advance', value : 'OM' },
                    { text : 'Customer Request', value : 'CR' },
                    { text : 'Others', value : 'NT' },
                ],
                form : {
                    estimated_date: null,
                    delay_reason: null,
                    delay_reason_description: '',
                }
            }
        },
        methods: {
            disableBulkEstimated() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if (item.shipment_provider !== 'Seller Delivery' || item.fulfillment_status !== 1) {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            closeEstimated() {
                this.form.estimated_date = null;
                this.form.delay_reason = null;
                this.form.delay_reason_description = '';

                this.$refs['estimated-order-modal'].hide();
            },
            openEstimated() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to update estimated shipping date.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order.items.every((item) => {
                        if (item.shipment_provider !== 'Seller Delivery' || item.fulfillment_status !== 1) {
                            error = true;
                            let order_id = order.external_id ? order.external_id : order.id;
                            notify('top', 'Error', 'Order ['+ order_id +'] does not support this update estimated shipping date', 'center', 'danger');
                            return false;
                        }
                    });
                });

                if (error) {
                    return;
                }

                this.$refs['estimated-order-modal'].show();
            },
            confirmEstimated() {
                if (this.sending_request) {
                    return;
                }
                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to update estimated shipping date.', 'center', 'danger');
                    return;
                }

                if (!this.form.estimated_date) {
                    notify('top', 'Error', 'You need to select estimated date.', 'center', 'danger');
                    return;
                }
                if (!this.form.delay_reason) {
                    notify('top', 'Error', 'You need to select delay reason.', 'center', 'danger');
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Updating..', 'center', 'info');
                // Loop and update all selected order to update estimated shipping date
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/qoo10_legacy/updateEstimatedShippingDate', this.form).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully updated estimated date for order! '+ order.id, 'center', 'success');
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

                    this.closeEstimated();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
        }
    }
</script>

<style scoped>

</style>
