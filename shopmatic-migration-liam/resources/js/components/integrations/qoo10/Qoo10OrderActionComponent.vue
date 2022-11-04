<template>
    <div>
        <div class="row">
            <div class="col-12">
               <b-button variant="info" class="mr-2" @click="checkQXpress()" v-if="canAirwayBill"><i class="fas fa-file-invoice"></i> Qxpress Waybill</b-button>
<!--                <b-button variant="success" class="mr-2" @click="printQexpressInvoice"><i class="fas fa-file-invoice"></i> Print Qexpress Invoice</b-button>-->
                <b-button variant="success" class="mr-2" v-if="canUpdateShipping" @click="updateShippingInfo()"><i class="fas fa-check"></i> Update Shipping Info</b-button>

                <qoo10-cancel-order-component :order="this.order"></qoo10-cancel-order-component>

                <b-modal id="qoo10-qxpress-shipping" :ref="'qoo10-qxpress-shipping-' + this.order.id" size="md"
                         header-bg-variant="white" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>
                    <template v-slot:modal-header="{ close }">
                        <h2 class="mb-0 text-black">CONFIRMATION</h2>
                        <button type="button" class="close" @click="closeQXpress" aria-label="Close">
                            <span aria-hidden="true" class="text-black">×</span>
                        </button>
                    </template>
                    <hr class="mt-0">
                    <div class="alert badge-danger d-flex alert-dismissible fade show" role="alert">
                        <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                        <span class="alert-text">
                            <h3>As the delivery provider for this order is {{order.items[0].shipment_method}} and not Qxpress, clicking on this option will change the shipment provider to Qxpress. </h3>
                        </span>
                    </div>
                    <hr class="mb-0">
                    <h2 class="text-center">Are you sure you want to proceed?</h2>
                    <template v-slot:modal-footer="{ Yes, No }">
                        <b-button variant="success" class="ml-auto" @click="airwayBill">Yes</b-button>
                        <b-button variant="danger" class="mr-auto" @click="closeQXpress">No</b-button>
                    </template>
                </b-modal>

                <b-modal id="qoo10-update-shipping" :ref="'qoo10-update-shipping-' + this.order.id" size="lg"
                         header-bg-variant="success" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

                    <template v-slot:modal-header="{ close }">
                        <h2 class="mb-0 text-white">Update Shipping Info</h2>
                        <button type="button" class="close" @click="closeShipping" aria-label="Close">
                            <span aria-hidden="true" class="text-white">×</span>
                        </button>
                    </template>

                    <h3 class="mt-4">Select Provider</h3>
                    <select name="provider_id" v-model="provider_id" class="form-control" required>
                        <option :value=null selected disabled>-- Please select a provider --</option>
                        <option v-for="company in companies" :value="company.M_B_NM">{{ company.M_B_NM }}</option>
                    </select>

                    <h3 class="mt-4">Tracking No</h3>
                    <input type="text" name="tracking_id" class="form-control" v-model="tracking_id" required />

                    <template v-slot:modal-footer="{ ok, cancel }">
                        <b-button variant="link" @click="closeShipping()">Close</b-button>
                        <b-button variant="success" class="ml-auto" @click="confirmShipping">Update Order</b-button>
                    </template>
                </b-modal>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
        </div>
    </div>
</template>

<script>
    import Qoo10CancelOrderComponent from "./Qoo10CancelOrderComponent";
    export default {
        name: "Qoo10OrderActionComponent",
        components: {
            Qoo10CancelOrderComponent
        },
        props: ['order'],
        data() {
            return {
                sending_request: false,
                provider_id: '',
                tracking_id: '',
                companies: [],
            }
        },
        computed: {
            canUpdateShipping() {
                if(this.order.fulfillment_status == 11) {
                    return false;
                }
                return this.order.fulfillment_status >= 0 && this.order.fulfillment_status < 20;
            },
            canAirwayBill() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.shipment_provider !== 'Seller Delivery' && item.fulfillment_status === 1) {
                        count++;
                    }
                    if (item.fulfillment_status == 11) {
                        count++;
                    }
                });
                return count > 0;
            },
            // canAddress() {
            //     return this.order.fulfillment_status >= 0 && this.order.fulfillment_status < 30;
            // },
            canShippingStatement() {
                return this.order.fulfillment_status >= 1 && this.order.fulfillment_status < 30;
            }
        },
        methods: {
            updateShippingInfo() {
                this.retrieveCompanies();
                this.$refs['qoo10-update-shipping-' + this.order.id].show();
            },
            closeShipping() {
                this.$refs['qoo10-update-shipping-' + this.order.id].hide();
            },
            retrieveCompanies() {
                axios.get('/web/orders/' + this.order.id + '/qoo10/getShippingCompany').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.companies = data.response.rows;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            confirmShipping() {
                if (this.sending_request) {
                    return;
                }
                if (!this.provider_id) {
                    notify('top', 'Error', 'You need to select the provider.', 'center', 'danger');
                    return;
                }
                if (!this.tracking_id) {
                    notify('top', 'Error', 'You need to select the provider.', 'center', 'danger');
                    return;
                }
                notify('top', 'Info', 'Updating..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/qoo10/updateShippingInfo', {
                    'shipping_provider': this.provider_id,
                    'tracking_no': this.tracking_id,
                }).then((response) => {
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
                            text: 'Successfully updated the order!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closeShipping();
                            this.$parent.$parent.updateCurrent();
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
            printQexpressInvoice() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Printing..', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/qoo10/printQexpressInvoice', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        console.log(data.response);
                        let w = window.open("about:blank", "_blank");
                        w.document.write(data.response);
                        w.document.write('<script>window.addEventListener("load", function(){\n' +
                            '        window.print();\n' +
                            '        window.onfocus=function(){ window.close();}\n' +
                            '    });<' + '/script>');
                        w.document.close();
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

            checkQXpress() {
                this.order.items.forEach((item) => {
                    if (item.shipment_method !== 'Qxpress') {
                        this.$refs['qoo10-qxpress-shipping-' + this.order.id].show();
                    } else {
                        this.airwayBill();
                    }
                });
            },
            closeQXpress() {
                this.$refs['qoo10-qxpress-shipping-' + this.order.id].hide();
            },
            airwayBill() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Getting airway bill..', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/qoo10/airwayBill', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let w = window.open(data.response, "_blank");
                        w.document.close();
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
            address() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Getting address..', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/qoo10/printAddress', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        console.log(data.response);
                        let w = window.open("about:blank", "_blank");
                        w.document.write(data.response);
                        w.document.write('<script>window.addEventListener("load", function(){\n' +
                            '        window.print();\n' +
                            '        window.onfocus=function(){ window.close();}\n' +
                            '    });<' + '/script>');
                        w.document.close();
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
            shippingStatement() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Getting shipping statement..', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/qoo10/shippingStatement', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let w = window.open("about:blank", "_blank");
                        w.document.write(data.response);
                        w.document.close();
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
        },
    }
</script>


<style type="text/css">
#qoo10-update-shipping___BV_modal_outer_,#qoo10-qxpress-shipping___BV_modal_outer_ {
    z-index: 1051 !important;
}
</style>
