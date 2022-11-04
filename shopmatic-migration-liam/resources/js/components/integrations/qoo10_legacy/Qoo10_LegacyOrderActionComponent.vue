<template>
    <div>
        <div class="row">
            <div class="col-12">
                <b-button variant="info" class="mr-2" @click="airwayBill" v-if="canAirwayBill"><i class="fas fa-file-invoice"></i> Airway Bill</b-button>
                <!-- <b-button variant="info" class="mr-2" @click="address" v-if="canAddress"><i class="fas fa-address-book"></i> Address</b-button>
                <b-button variant="info" class="mr-2" @click="shippingStatement" v-if="canShippingStatement"><i class="fas fa-tasks"></i> Shipping Statement</b-button> -->
                <qoo10_-legacy-estimated-date-order-component :order="this.order"></qoo10_-legacy-estimated-date-order-component>
                <qoo10_-legacy-pickup-order-compoent :order="this.order"></qoo10_-legacy-pickup-order-compoent>
                <qoo10_-legacy-fulfill-order-component :order="this.order"></qoo10_-legacy-fulfill-order-component>
                <qoo10_-legacy-cancel-order-component :order="this.order"></qoo10_-legacy-cancel-order-component>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
            <div class="col-12 mt-3 action-guide">
                <small><a href="#qoo10-legacy-help" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="qoo10-legacy-help">Guide & Help <i class="fas fa-angle-double-down"></i></a></small>
                <div id="qoo10-legacy-help" class="collapse mt-2">
                    <span class="text-muted">The flow for order processing for Qoo10 Legacy is</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import Qoo10_LegacyCancelOrderComponent from "./Qoo10_LegacyCancelOrderComponent";
    import Qoo10_LegacyEstimatedDateOrderComponent from "./Qoo10_LegacyEstimatedDateOrderComponent";
    import Qoo10_LegacyFulfillOrderComponent from "./Qoo10_LegacyFulfillOrderComponent";
    import Qoo10_LegacyPickupOrderCompoent from "./Qoo10_LegacyPickupOrderCompoent";
    export default {
        name: "Qoo10_LegacyOrderActionComponent",
        components: {
            Qoo10_LegacyPickupOrderCompoent,
            Qoo10_LegacyFulfillOrderComponent,
            Qoo10_LegacyEstimatedDateOrderComponent, Qoo10_LegacyCancelOrderComponent},
        props: ['order'],
        data() {
            return {
                sending_request: false,
            }
        },
        computed: {
            canAirwayBill() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.shipment_provider !== 'Seller Delivery' && item.fulfillment_status === 1) {
                        count++;
                    }
                });
                return count > 0;
            },
            canAddress() {
                if (this.order.fulfillment_status >= 0 && this.order.fulfillment_status < 30) {
                    return true;
                }
                return false;
            },
            canShippingStatement() {
                if (this.order.fulfillment_status >= 1 && this.order.fulfillment_status < 30) {
                    return true;
                }
                return false;
            }
        },
        methods: {
            airwayBill() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Getting airway bill..', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/qoo10_legacy/airwayBill', {}).then((response) => {
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
            },
            address() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Getting address..', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/qoo10_legacy/printAddress', {}).then((response) => {
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

                axios.post('/web/orders/' + this.order.id + '/qoo10_legacy/shippingStatement', {}).then((response) => {
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
        }
    }
</script>

<style scoped>

</style>
