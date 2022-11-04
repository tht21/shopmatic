<template>
    <div v-if="order">
        <b-row class="d-flex">
            <b-col md="8">
                <logistic-order-details-component :order="order"
                                                  @orderItems="orderItems"></logistic-order-details-component>
            </b-col>
            <b-col md="4">
                <b-card header="Get Instant Quotes" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                    <b-tabs fill v-model="tabIndex" v-on:activate-tab="tabActivated" content-class="mt-3"
                            active-nav-item-class="font-weight-bold text-uppercase text-white bg-primary"
                            active-tab-class="font-weight-bold">
                        <b-tab title="Drop-off">
                            <label>Log</label>
                        </b-tab>
                        <b-tab title="Pick Up">
                            <b-form-group label-cols="2" label="From:"
                                          label-for="logistic-from-country-input">
                                <b-form-input
                                    id="logistic-from-country-input"
                                    v-model="form.from_country.value"
                                    placeholder="Country"
                                    name="logistic-from-country-input"
                                />
                            </b-form-group>
                            <b-row>
                                <b-col md="6">
                                    <b-form-group label-for="logistic-from-state-input">
                                        <b-form-input
                                            id="logistic-from-state-input"
                                            v-model="form.from_state.value"
                                            placeholder="State"
                                            name="logistic-from-state-input"
                                        />
                                    </b-form-group>
                                </b-col>
                                <b-col md="6">
                                    <b-form-group label-for="logistic-from-postcode-input">
                                        <b-form-input
                                            id="logistic-from-postcode-input"
                                            v-model="form.from_postcode.value"
                                            placeholder="Postcode"
                                            name="logistic-from-postcode-input"
                                            type="number"
                                        />
                                    </b-form-group>
                                </b-col>
                            </b-row>
                        </b-tab>
                    </b-tabs>
                    <b-form-group label-cols="2" label="To:"
                                  label-for="logistic-to-country-input">
                        <b-form-input
                            id="logistic-to-country-input"
                            v-model="form.to_country.value"
                            placeholder="Country"
                            name="logistic-to-country-input"
                            :disabled="true"
                        />
                    </b-form-group>
                    <b-row>
                        <b-col md="6">
                            <b-form-group label-for="logistic-to-state-input">
                                <b-form-input
                                    id="logistic-to-state-input"
                                    v-model="form.to_state.value"
                                    placeholder="State"
                                    name="logistic-to-state-input"
                                    :disabled="true"
                                />
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="logistic-to-postcode-input">
                                <b-form-input
                                    id="logistic-to-postcode-input"
                                    v-model="form.to_postcode.value"
                                    placeholder="Postcode"
                                    name="logistic-to-postcode-input"
                                    :disabled="true"
                                />
                            </b-form-group>
                        </b-col>
                    </b-row>
                    <b-form-group label-cols="2" label="Weight:"
                                  label-for="logistic-weight-input">
                        <b-form-input
                            id="weight-input"
                            v-model="form.weight.value.toFixed(2) + ' KG'"
                            placeholder="kg(eg:0.1)"
                            name="weight-input"
                            :disabled="true"
                        />
                    </b-form-group>
                </b-card>
            </b-col>
        </b-row>
        <template v-if="logistics !== null">
            <b-col>
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="mb-0">Logistics
                            <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i
                                class="fa fa-sync-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse">
                                <i
                                    class="fa fa-filter"></i></button>
                        </h3>
                    </div>
                    <div id="filter" class="collapse">
                        <div class="p-3" style="background: #f6f6f6;">
                            <div class="row">
                                <div class="col-md-6 mt-2">
                                    <label for="status" class="text-muted text-uppercase">Promo Rate For</label>
                                    <select id="status" v-model="filter.promo_rate" name="status"
                                            class="form-control">
                                        <option value="">EP 20</option>
                                        <option value="1">EP 50</option>
                                        <option value="10">EP 100</option>
                                        <option value="20">EP 500</option>
                                        <option value="30">EP 1000</option>
                                    </select>
                                </div>
                                <div class="col-12 text-center py-3">
                                    <button class="btn btn-primary px-5" @click="retrieve">Filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="index-table" class="table-responsive">
                        <table class="table align-items-center">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 130px;" class="sort desc" data-sort="id">No.</th>
                                <th>Courier</th>
                                <th class="sort" data-sort="service_type">Service Type</th>
                                <th>Estimated <br> Delivery Duration
                                    <b-link href="#" v-b-tooltip.hover
                                            title="There may be a slight delay in delivery due to peak season or unexpected circumstances."
                                            pla><i class="far fa-question-circle"></i></b-link>
                                </th>
                                <th class="sort" data-sort="rate">Your Rate</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody class="list">
                            <tr v-for="item in logistics" class="cursor-pointer">
                                <td style="width: 130px;" class="id">{{ item.id }}</td>
                                <td style="width: 130px"><img v-if="item.courier" :src="item.courier"
                                                              class="product-img-thumb"></td>
                                <td class="name">
                                    <b-link href="#" v-if="item.service_type===0" v-b-tooltip.hover
                                            title="Click to view logistic address"><span
                                        class="text-blue"><i class="text-black mr-1 font-size-20 fas fa-running"></i>Drop-off<i
                                        class="fas fa-info-circle font-size-15 pl-1"></i></span></b-link>
                                    <span v-else>><i class="text-black mr-1 font-size-20 fas fa-truck-pickup"></i>Pick Up</span>
                                    <template v-show="item.service_requires_min > 0"><br><small
                                        class="badge badge-warning">Requires min {{item.service_requires_min}}
                                        parcel(s)</small></template>
                                    <br>
                                    <i :class="['text-black '+ (item.service_rating > i ? 'fas fa-star' : 'far fa-star')]"
                                       v-for="i in total_ratings" style="color: #FFD700"></i>
                                    <br><small class="text-red font-weight-bolder">{{item.service_rating.toFixed(2)}}
                                    / {{total_ratings.toFixed(2)}}</small>
                                </td>
                                <td>{{item.estimated_delivery_duration}} working day(s)</td>
                                <td class="rate text-red font-weight-bolder text-uppercase">{{item.rate}}</td>
                                <td>
                                    <button class="btn btn-default px-5" @click="confirmBook(item)">Book
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <h3 v-if="logistics.length === 0 && !retrieving"
                            class="text-muted text-center font-weight-light py-3">There is nothing that matches your
                            criteria!</h3>
                    </div>
                    <div class="card-footer py-4" v-if="!retrieving">
                        <pagination-component :details="pagination" @paginated="paginate"></pagination-component>
                    </div>
                </div>
            </b-col>
        </template>
        <b-modal size="lg" :ref="model_name.logistic" title="Notice" :header-bg-variant="'primary'">
            <template v-slot:modal-header="{ close }">
                <h3 class="text-white">Notice</h3>
            </template>
            <template v-if="select_logistics">
                <div class="col-12 text-center" v-if="select_logistics.image_notice">
                    <img :src="select_logistics.image_notice" style="width: 50%">
                </div>
                <template v-if="select_logistics.notices && select_logistics.notices.length > 0">
                    <div class="row pt-3" v-for="notice in select_logistics.notices">
                        <div class="col-3 text-center">
                            <img :src="notice.image" style="max-height: 100px">
                        </div>
                        <div class="col-9">
                            <h2>{{notice.title}}</h2>
                            <h5>{{notice.content}}</h5>
                        </div>
                    </div>
                    <div class="col-9 ml-auto">
                        <input-field-component
                            type="checkBox"
                            id="remind-checkBox-input"
                            :model.sync="checkbox_remind"
                            :options="checkBox"
                        />
                    </div>
                </template>
            </template>
            <template slot="modal-footer">
                <b-btn variant="default" @click="hideModal(model_name.logistic)">
                    Cancel
                </b-btn>
                <b-btn variant="primary" @click="confirmOk('OK')">
                    OK
                </b-btn>
            </template>
        </b-modal>
    </div>
</template>

<script>
    import InputFieldComponent from "../../../utility/InputFieldComponent";
    import OrderDetailComponent from "../../orders/OrderDetailComponent";
    import LogisticOrderDetailsComponent from "./LogisticOrderDetailComponent";

    export default {
        name: "LogisticQuoteComponent",
        components: {LogisticOrderDetailsComponent, OrderDetailComponent, InputFieldComponent},
        props: {
            selected_order: {
                type: Object,
                default: null,
            },
        },
        data() {
            return {
                filter: {
                    promo_rate: null,
                    service_type: 0,
                },
                checkBox: [
                    {text: 'Do not remind me again', value: 'do_not_remind_me_again'}
                ],
                form: {
                    from_country: {
                        value: null,
                        required: true,
                    },
                    from_state: {
                        value: null,
                        required: true,
                    },
                    from_postcode: {
                        value: null,
                        required: true,
                    },
                    to_country: {
                        value: null,
                        required: false,
                    },
                    to_state: {
                        value: null,
                        required: false,
                    },
                    to_postcode: {
                        value: null,
                        required: false,
                    },
                    weight: {
                        value: 0,
                        required: false,
                    },
                },
                retrieving: false,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                order: null,
                logistics: null,
                select_logistics: null,
                total_ratings: 5,
                checkbox_remind: null,
                request_logistics_url: '/web/logistics',
                request_order_url: '/web/orders/',
                service_type: "PICK_UP",
                selected_order_items: null,
                tabIndex: 0,
                model_name: {
                    logistic: "pre-logistic-modal"
                }
            }
        },
        watch: {
            selected_order() {
                if (this.selected_order) {
                    this.order = null;
                    this.selected_order_items = null
                    this.retrieveOrder()
                }
            },
            order() {
                this.appendValue()
            },
        },
        async created() {
            this.retrieve()
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.data = [];
                let parameters = {
                    service_type: this.service_type,
                };
                axios.get(this.request_logistics_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.logistics = data.response.items;
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                })
            },
            retrieveOrder() {
                axios.get(this.request_order_url + this.selected_order.id, {
                    params: null
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.order = data.response
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                })
            },
            appendValue() {
                let order = this.order
                if (order) {
                    let shipping_address = order.shipping_address;
                    if (shipping_address) {
                        this.form.to_country.value = shipping_address.city;
                        this.form.to_postcode.value = shipping_address.postcode;
                    }
                } else {
                    this.form.from_country.value = null;
                    this.form.from_state.valu = null;
                    this.form.from_postcode.value = null;
                    this.form.to_country.value = null;
                    this.form.to_state.valu = null;
                    this.form.to_postcode.value = null;
                    this.form.weight.value = 0;
                }
            },
            convertServiceType(type) {

                if (type == 0) {
                    return {
                        'icon': 'fa-running',
                        'text': 'Pick Up',
                        'name': 'PICK_UP',
                    }
                } else if (type == 1) {
                    return {
                        'icon': 'fa-truck',
                        'text': 'Drop-Off',
                        'name': 'DROP_OFF',
                    }
                }

            },
            orderItems(items) {
                this.form.weight.value = 0;
                this.selected_order_items = items;
                if (items) {
                    items.forEach((item) => {
                        let variant = item.variant
                        if (variant) {
                            this.form.weight.value += parseFloat(variant.weight);
                        }
                    })
                }
            },
            confirmBook(logistic) {
                if (this.validateData()) {
                    this.select_logistics = logistic
                    this.$refs[this.model_name.logistic].show();
                }
            },
            alert() {
            },
            hideModal(ref) {
                this.remind = null
                this.$refs[ref].hide();
            },
            confirmOk() {
                this.$emit('selectBookLogistic', this.select_logistics, this.selected_order_items, this.form)
                this.hideModal(this.model_name.logistic)
            },
            paginate(value) {
                this.pagination = value;
                this.retrieve();
            },
            tabActivated(newTabIndex, oldTabIndex, event) {
                this.service_type = this.convertServiceType(newTabIndex).name
                this.retrieve()
            },
            validateData() {

                let validate = true;
                if (!this.selected_order_items || this.selected_order_items.length <= 0) {
                    notify('top', 'Error', "Missing select order items", 'center', 'danger');
                    validate = false;
                }
                if (this.tabIndex === 1) {
                    Object.keys(this.form).map((key) => {
                        let data = this.form[key];
                        if (data.required && !data.value) {
                            notify('top', 'Error', "Missing " + key.replace("_", " "), 'center', 'danger');
                            validate = false;
                        }
                    });
                }
                return validate
            }
        }
    }
</script>

<style scoped>
    .promo-rate:hover {
        color: red;
    }
</style>
