<template>
    <span>
        <b-button variant="danger" class="mr-2" @click="cancel"><i class="fas fa-times"></i> Cancel</b-button>

        <!--<b-modal id="refund-order-modal" :ref="'refund-order-modal-' + this.order.id"
                 header-bg-variant="info" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Refund Order</h2>
                <button type="button" class="close" @click="closeCancel" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Refund Amount</h3>
            <b-form-input v-model="amount" placeholder="Enter your refund amount"></b-form-input>

             <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeCancel">Close</b-button>
                <b-button variant="warning" class="ml-auto" @click="confirmRefund">Refund</b-button>
            </template>
        </b-modal>-->
    </span>
</template>

<script>
    export default {
        name: "WoocommerceCancelOrderComponent",
        props: ['order'],
        data() {
            return {
                amount: null,
                sending_request: false
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
            closeCancel() {
                this.$refs['refund-order-modal-' + this.order.id].hide();
                this.amount = null;
            },
            cancel() {
                let title = 'Are you sure to cancel the order?';
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
                        if (this.sending_request) {
                            return;
                        }
                        this.sending_request = true;
                        notify('top', 'Info', 'Cancelling order...', 'center', 'info');
                        axios.post('/web/orders/' + this.order.id + '/woocommerce/cancel', {}).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Successfully cancelled order!', 'center', 'success');
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
                    }
                })
            }
        },
    }
</script>

<style scoped>
    #refund-order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
