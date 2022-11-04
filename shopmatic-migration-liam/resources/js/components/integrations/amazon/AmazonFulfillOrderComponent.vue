<template>
    <span>
        <button type="button" class="btn btn-primary" @click="fulfillment" v-if="canFulfill"><i class="fas fa-check"></i> Fulfillment</button>

        <b-modal id="fulfill-order-modal" :ref="'fulfill-order-modal-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Fulfill Order</h2>
                <button type="button" class="close" @click="closeFulfill" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Carrier Code</h3>
            <b-form-select v-model="form.carrier_code" :options="options.carrier_code"></b-form-select>

            <template v-if="form.carrier_code === 'Other'">
                <h3 class="mt-4">Carrier Name</h3>
                <b-form-input v-model="form.carrier_name" placeholder="Enter carrier name"></b-form-input>
            </template>

            <h3 class="mt-4">Tracking Number</h3>
            <b-form-input v-model="form.tracking_number" placeholder="Enter tracking number"></b-form-input>

            <h3 class="mt-4">Shipping Method</h3>
            <b-form-input v-model="form.shipping_method" placeholder="Enter shipping method"></b-form-input>

            <h3 class="mt-4">Shipping Date</h3>
            <b-form-input type="date" v-model="form.shipping_date" placeholder="Enter shipping date"></b-form-input>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeFulfill">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFulfill">Fulfill</b-button>
            </template>

        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "AmazonFullfilmentComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                options: {
                    carrier_code: [
                        'USPS','UPS','UPSMI','FedEx','DHL','Fastway','GLS','GO!','Hermes Logistik Gruppe','Royal Mail','Parcelforce','City Link','TNT','Target','SagawaExpress','NipponExpress','YamatoTransport','DHL Global Mail','UPS Mail Innovations','FedEx SmartPost','OSM','OnTrac','Streamlite','Newgistics','Canada Post','Blue Package','Chronopost','Deutsche Post','DPD','La Poste','Parcelnet','Poste Italiane','SDA','Smartmail','FEDEX_JP','JP_EXPRESS','NITTSU','SAGAWA','YAMATO','BlueDart','AFL/Fedex','Aramex','India Post','Professional','DTDC','Overnite Express','First Flight','Delhivery','Lasership','Yodel','Other','Amazon Shipping'
                    ]
                },
                form : {
                    shipping_date: null,
                    carrier_code: null,
                    carrier_name: null,
                    tracking_number : null,
                    shipping_method: null
                }
            }
        },
        computed: {
            canFulfill() {
                if (this.order.fulfillment_status <= 10) {
                    return true;
                }
                return false;
            },
        },
        methods: {
            fulfillment() {
                this.$refs['fulfill-order-modal-' + this.order.id].show();
            },
            closeFulfill() {
                this.$refs['fulfill-order-modal-' + this.order.id].hide();
                this.form.shipping_date = null;
                this.form.carrier_code = null;
                this.form.carrier_name = null;
                this.form.tracking_number = null;
                this.form.shipping_method = null;
            },
            confirmFulfill() {
                if (!this.form.carrier_code) {
                    notify('top', 'Error', 'You need to select carrier code to fulfill.', 'center', 'danger');
                    return;
                }
                if (this.form.carrier_code === 'Other' && !this.form.carrier_name) {
                    notify('top', 'Error', 'Please enter carrier name to fulfill.', 'center', 'danger');
                    return;
                }
                if (!this.form.shipping_method) {
                    notify('top', 'Error', 'Please enter shipping method to fulfill.', 'center', 'danger');
                    return;
                }
                if (!this.form.shipping_date) {
                    notify('top', 'Error', 'Please select shipping date to fulfill.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Fulfilling order...', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/amazon/fulfillment', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully fulfilled order!', 'center', 'success');
                        this.closeFulfill();
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
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>

<style scoped>

</style>
