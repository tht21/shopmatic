<template>
    <b-row v-if="order && logistic" class="d-flex">
        <b-col md="9">
            <logistic-order-details-component :order="order"
                                              :selected_order_items="selected_order_items"></logistic-order-details-component>
        </b-col>
        <b-col md="3" class="sticky-top sticky-height">
            <b-card header="Courier Details" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                <b-col md="12" class="pb-3">
                    <img v-if="select_logistic.courier" :src="select_logistic.courier"
                         class="product-img-thumb">
                </b-col>
                <b-col md="12">
                    <h5>Parcel Id: {{parcel_id}}</h5>
                    <template v-if="logistic.service_type === 0">
                        <h4 v-if="form.to_country.value">County: {{form.to_country.value}}</h4>
                        <h4 v-if="form.to_state.value">State: {{form.to_state.value}}</h4>
                        <h4 v-if="form.to_postcode.value">Postcode: {{form.to_postcode.value}}</h4>
                    </template>
                    <template v-else>
                        <b-row align-v="center" align-h="center">
                            <b-col md="5">
                                <h4 v-if="form.from_country.value">County: {{form.from_country.value}}</h4>
                                <h4 v-if="form.from_state.value">State: {{form.from_state.value}}</h4>
                                <h4 v-if="form.from_postcode.value">Postcode: {{form.from_postcode.value}}</h4>
                            </b-col>
                            <b-col md="2" class="justify-content-md-center"><i class="fa fa-long-arrow-alt-right"></i></b-col>
                            <b-col md="5" class="justify-content-md-center">
                                <h4 v-if="form.to_country.value">County: {{form.to_country.value}}</h4>
                                <h4 v-if="form.to_state.value">State: {{form.to_state.value}}</h4>
                                <h4 v-if="form.to_postcode.value">Postcode: {{form.to_postcode.value}}</h4>
                            </b-col>
                        </b-row>
                    </template>
                    <h4>Service Type: {{convertServiceType(logistic.service_type).text}}</h4>
                    <h4>Weight: {{weight.toFixed(2)}}kg</h4>
                    <h3>Total: {{logistic.rate}}</h3>
                </b-col>
                <div class="sidebar-button">
                    <b-button class="w-100 mx-0 my-2" variant="primary" @click="confirm">Confirm</b-button>
                </div>
            </b-card>
        </b-col>
    </b-row>
</template>

<script>
    import LogisticOrderDetailsComponent from "./LogisticOrderDetailComponent";

    export default {
        name: "LogisticConfirmComponent",
        components: {LogisticOrderDetailsComponent},
        props: {
            select_logistic: {
                type: Object,
                default: null,
            },
            selected_order: {
                type: Object,
                default: null,
            },
            selected_order_items: {
                type: Array,
                default: null,
            },
            form: {
                type: Object,
                default: null,
            }
        },
        data() {
            return {
                order: null,
                logistic: null,
                parcel_id: "EPB-12345678",
                weight: 0,
                request_url: '/web/order/logistics ',
                sending_request: false,
            }
        },
        watch: {
            select_logistic() {
                this.logistic = null;
                this.logistic = this.select_logistic;
            },
            selected_order() {
                this.order = null;
                this.order = this.selected_order;
            },
            selected_order_items() {
                let items = this.selected_order_items
                this.weight = 0
                if (items) {
                    items.forEach((item) => {
                        let variant = item.variant
                        if (variant) {
                            this.weight += parseFloat(variant.weight);
                        }
                    })
                }
            },
        },
        methods: {
            confirm() {
                if (this.sending_request) {
                    return;
                }

                this.sending_request = true

                axios.post(this.request_url, this.setupFormBase()).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');

                        this.sending_request = false;
                    } else {

                        swal({
                            title: 'Success',
                            text: 'You have successfully confirmed the delivery of the package.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            window.location.href = '/dashboard/logistics';
                        });

                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                })
            },
            setupFormBase() {

                let form = {};

                Object.keys(this.form).map((key) => {
                    form[key] = this.form[key].value;
                });

                form['order_items'] = this.selected_order_items;
                form['logistic'] = this.logistic;
                form['parcel_id'] = this.parcel_id;

                return form;
            },
            convertServiceType(type) {

                if (type == 0) {
                    return {
                        'icon': 'fa-running',
                        'text': 'Pick Up',
                    }
                } else if (type == 1) {
                    return {
                        'icon': 'fa-truck',
                        'text': 'Drop-Off',
                    }
                }

            },
        }
    }
</script>

<style scoped>

</style>
