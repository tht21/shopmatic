<template>
    <span>
        <b-button variant="primary" class="mr-2" @click="estimatedDate()" v-if="canEstimatedDate"><i class="fas fa-calendar"></i> Update Estimated Shipping Date</b-button>

        <b-modal id="estimated-date-order" :ref="'estimated-date-order-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Update Estimated Date Order</h2>
                <button type="button" class="close" @click="closeEstimatedDate" aria-label="Close">
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
                <b-button variant="link" @click="closeEstimatedDate">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmEstimatedDate">Update</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "Qoo10_LegacyEstimatedDateOrderComponent",
        props: ['order'],
        data() {
            return {
                sending_request: false,
                today: new Date().toISOString().slice(0,10),
                reason_options : [
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
        computed: {
            canEstimatedDate() {
                let count = 0;
                this.order.items.forEach(function (item) {
                    if (item.shipment_provider === 'Seller Delivery' && item.fulfillment_status === 1) {
                        count++;
                    }
                });
                return count > 0;
            }
        },
        methods: {
            estimatedDate() {console.log(this.order.id);
                this.$refs['estimated-date-order-' + this.order.id].show();
            },
            closeEstimatedDate() {
                this.$refs['estimated-date-order-' + this.order.id].hide();
                this.form.estimated_date = null;
                this.form.delay_reason = null;
                this.form.delay_reason_description = '';
            },
            confirmEstimatedDate() {
                if (this.sending_request) {
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
                notify('top', 'Info', 'Updating..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/qoo10_legacy/updateEstimatedShippingDate', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        swal({
                            title: 'Error',
                            text: data.meta.message,
                            type: 'error',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-info'
                        })
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'Successfully updated estimated date for order!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closeEstimatedDate();
                            this.$parent.$parent.$parent.updateCurrent();
                        })
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
    #estimated-date-order___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
