<template>
    <span>
        <b-button variant="primary" class="mr-2" @click="fulfillment()" v-if="canFulfill"><i class="fas fa-check"></i> Fulfillment</b-button>

        <b-modal id="fulfill-order-modal" :ref="'fulfill-order-modal-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Fulfill Order</h2>
                <button type="button" class="close" @click="closeFulfill" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Select delivery company</h3>
            <b-form-select v-model="selected_delivery_company" :options="delivery_company" @input="fillInForm"></b-form-select>

            <h3 class="mt-4">Tracking number</h3>
            <b-form-input v-model="form.songjang_no" placeholder="Enter tracking number"></b-form-input>

             <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeFulfill">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFulfill">Fulfill</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "Qoo10_LegacyFulfillOrderComponent",
        props: ['order'],
        data() {
            return {
                sending_request: false,
                form : {
                    transc_cd: null,
                    takbae_nm: null,
                    songjang_no: null,
                },
                delivery_company: [],
                selected_delivery_company: null,
            }
        },
        computed: {
            canFulfill() {
                let count = 0;
                this.order.items.forEach(function (item) {
                    if ((item.fulfillment_status === 10 && (item.shipment_provider === 'Qxpress' || item.shipment_provider === 'Qprime')) || (item.fulfillment_status === 10 && item.shipment_provider === 'Seller Delivery')) {
                        count++;
                    }
                });
                return count > 0;
            }
        },
        methods: {
            emit () {
                this.$emit('input', this.form)
            },
            fulfillment() {
                this.$refs['fulfill-order-modal-' + this.order.id].show();
            },
            closeFulfill() {
                this.$refs['fulfill-order-modal-' + this.order.id].hide();
                this.form.transc_cd = null;
                this.form.takbae_nm = null;
                this.form.songjang_no = null;
            },
            retrieveDeliveryCompany() {
                axios.get('/web/orders/' + this.order.id + '/qoo10_legacy/getDeliveryCompanyList').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {

                        this.delivery_company = response.data.response.map(company => ({
                            text: company.transc_nm,
                            value: {
                                [company.transc_cd] : company.transc_nm
                            }
                        }))
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            confirmFulfill() {
                if (this.sending_request) {
                    return;
                }
                if (!this.form.transc_cd || !this.form.takbae_nm) {
                    notify('top', 'Error', 'You need to select delivery company.', 'center', 'danger');
                    return;
                }
                notify('top', 'Info', 'Updating..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/qoo10_legacy/fulfillment', this.form).then((response) => {
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
                            text: 'Successfully updated order fulfillment!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closeFulfill();
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
            fillInForm() {
                let entries = Object.entries(this.selected_delivery_company)[0];
                this.form.transc_cd = entries[0];
                this.form.takbae_nm = entries[1];
                this.emit();
            }
        },
        created() {
            if (this.canFulfill) {
                this.retrieveDeliveryCompany();
            }
        },
    }
</script>

<style scoped>
    #fulfill-order___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
