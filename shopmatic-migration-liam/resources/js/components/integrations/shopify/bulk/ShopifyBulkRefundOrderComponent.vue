<template>
    <span>
        <template v-if="status === 'pending'">
            <b-button variant="warning" class="mt-2" size="sm" @click="openRefund()" :class="{ disabled: disableBulkRefund() }"><i class="fas fa-undo"></i> Refund</b-button>
        </template>

        <b-modal id="refund-order-modal" ref="refund-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Refund Order</h2>
                <button type="button" class="close" @click="closeRefund" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Reason for refund</h3>
            <b-form-input v-model="form.reason"></b-form-input>
            <small>Only you and other staff can see this reason.</small>

            <h3 class="mt-4"></h3>
            <b-form-checkbox
                v-model="form.restock"
                :value=true
            >
                Restock
            </b-form-checkbox>

            <h3 class="mt-4"></h3>
            <b-form-checkbox
                v-model="form.notify"
                :value=true
                :unchecked-value=false
            >
                Send a notification to the customer
            </b-form-checkbox>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closeRefund">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmRefund">Refund</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "ShopifyBulkRefundOrderComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
                form: {
                    reason: null,
                    notify: true,
                    restock: true,
                }
            }
        },
        methods: {
            disableBulkRefund() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        if (order.data['location_id'] == null) {
                            disable = true;
                        }
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            closeRefund() {
                this.form.reason = null;
                this.form.notify = true;
                this.form.restock = true;

                this.$refs['refund-order-modal'].hide();
            },
            openRefund() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to refund.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    if (order.data['location_id'] == null) {
                        error = true;
                        let order_id = order.external_id ? order.external_id : order.id;
                        notify('top', 'Error', 'Order ['+ order_id +'] does not support refund', 'center', 'danger');
                        return false;
                    }
                });
                if (error) {
                    return;
                }

                this.$refs['refund-order-modal'].show();
            },
            confirmRefund() {
                if (this.sending_request) {
                    return;
                }

                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to refund.', 'center', 'danger');
                    return;
                }

                if (!this.form.reason) {
                    notify('top', 'Error', 'You need to enter reason to refund.', 'center', 'danger');
                    return;
                }

                this.sending_request = true;

                notify('top', 'Info', 'Refunding orders...', 'center', 'info');
                // Loop and update all selected order to refund
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/shopify/refund', this.form).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully refunded order! '+ order.id, 'center', 'success');
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

                    this.closeRefund();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
        }
    }
</script>

<style scoped>

</style>
