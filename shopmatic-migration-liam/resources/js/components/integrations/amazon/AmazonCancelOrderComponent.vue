<template>
    <span>
        <button type="button" class="btn btn-danger" @click="openCancel" v-if="canCancel"><i class="fa fa-times"></i> Cancel</button>

        <b-modal id="cancel-order-modal" :ref="'cancel-order-modal-' + this.order.id" size="lg"
                 header-bg-variant="danger" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Cancel Order</h2>
                <button type="button" class="close" @click="closeCancel" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Select Reason</h3>
            <b-form-select v-model="form.cancel_reason" :options="cancel_reasons">
                <b-form-select-option :value="null">Please select a reason</b-form-select-option>
            </b-form-select>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeCancel">Cancel</b-button>
                <b-button variant="danger" class="ml-auto" @click="confirmCancel">Cancel</b-button>
            </template>

        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "AmazonCancelOrderComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                cancel_reasons: [],
                form : {
                    cancel_reason: null
                }
            }
        },
        computed: {
            canCancel() {
                if (this.order.fulfillment_status <= 10) {
                    return true;
                }
                return false;
            },
        },
        methods: {
            openCancel() {
                this.$refs['cancel-order-modal-' + this.order.id].show();
                this.retrieveCancelReasons();
            },
            closeCancel() {
                this.$refs['cancel-order-modal-' + this.order.id].hide();
                this.form.cancel_reason = null;
            },
            retrieveCancelReasons() {
                axios.get('/web/orders/' + this.order.id + '/amazon/cancelReasons').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.cancel_reasons = data.response;
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
                if (!this.form.cancel_reason) {
                    notify('top', 'Error', 'You need to select a reason to cancel order.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Cancelling order...', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/amazon/cancel', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully cancelled order!', 'center', 'success');
                        this.closeCancel();
                        this.$parent.$parent.$parent.updateCurrent();
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
        }
    }
</script>

<style scoped>

</style>
