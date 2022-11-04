<template>
    <span>
        <template v-if="status === 'pending' || status === 'processing'">
            <b-button variant="danger" class="mt-2" size="sm" @click="openCancel()" :class="{ disabled: disableBulkCancel() }"><i class="fas fa-times"></i> Cancel</b-button>
        </template>

        <b-modal id="cancel-order-modal" ref="cancel-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Cancel Order</h2>
                <button type="button" class="close" @click="closeCancel" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Select Cancel Reason</h3>
            <b-form-select v-model="form.reason" :options="reason_options">
                <template v-slot:first>
                    <b-form-select-option :value="null" disabled>Please select a reason</b-form-select-option>
                </template>
            </b-form-select>

            <h3 class="mt-4">Note</h3>
            <b-form-textarea
                v-model="form.note"
                placeholder="Optional"
                rows="5"
                max-rows="10"
            ></b-form-textarea>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closeCancel">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmCancel">Confirm Cancel</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "Qoo10_LegacyBulkCancelOrderComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
                reason_options: [],
                form: {
                    reason: null,
                    note: '',
                }
            }
        },
        methods: {
            disableBulkCancel() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        if (order.fulfillment_status !== 0 && order.fulfillment_status !== 1) {
                            disable = true;
                        }
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            closeCancel() {
                this.form.reason = null;
                this.form.note = '';
                this.reason_options = [];

                this.$refs['cancel-order-modal'].hide();
            },
            openCancel() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to cancel.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    if (order.fulfillment_status !== 0 && order.fulfillment_status !== 1) {
                        error = true;
                        let order_id = order.external_id ? order.external_id : order.id;
                        notify('top', 'Error', 'Order ['+ order_id +'] does not support this cancel order', 'center', 'danger');
                        return false;
                    }
                });

                if (error) {
                    return;
                }

                // Retrieve reasons
                Object.values(this.selected_orders[this.status]).map(async (order) => {
                    if (this.reason_options.length <= 0) {
                        this.reason_options = await this.retrieveReasons(order);
                    }
                });

                this.$refs['cancel-order-modal'].show();
            },
            retrieveReasons(order) {
                return axios.get('/web/orders/' + order.id + '/qoo10_legacy/reasons').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        return data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            confirmCancel() {
                if (this.sending_request) {
                    return;
                }
                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to cancel.', 'center', 'danger');
                    return;
                }

                if (!this.form.reason) {
                    notify('top', 'Error', 'You need to select cancel reason.', 'center', 'danger');
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Cancelling..', 'center', 'info');
                // Loop and update all selected order to cancel
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/qoo10_legacy/cancel', this.form).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully cancelled for order! '+ order.id, 'center', 'success');
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

                    this.closeCancel();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
        }
    }
</script>

<style scoped>

</style>
