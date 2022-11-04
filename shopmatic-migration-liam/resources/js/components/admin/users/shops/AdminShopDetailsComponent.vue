<template>
    <div v-if="shop">
        <b-row>
            <b-col>
                <b-form-radio-group
                    id="btn-radios-status"
                    v-model="section"
                    :options="options"
                    buttons
                    name="radios-btn-default"
                ></b-form-radio-group>
            </b-col>
        </b-row>
        <b-row>
            <b-col>
                <div class="card mt-3">
                    <div class="card-body">
                        <b-form id="create-form" role="form">
                            <div class="row">
                                <div class="col-md-8 col-lg-12 py-3 pr-4">
                                    <template  v-if="section === 0">
                                        <div class="row">
                                            <div class="col">
                                                <b-form-group label="Name:" label-for="name-input">

                                                    <b-form-input
                                                        id="name-input"
                                                        v-model="form.name"
                                                        required
                                                        placeholder="Name"
                                                        name="name"
                                                        :disabled="!edit"
                                                    ></b-form-input>
                                                </b-form-group>
                                            </div>
                                            <div class="col">
                                                <b-form-group label="Phone Number:" label-for="phone-number-input">
                                                    <b-form-input
                                                        id="phone-number-input"
                                                        v-model="form.phone_number"
                                                        placeholder="Phone Number (Optional)"
                                                        name="phone_number"
                                                        :disabled="!edit"
                                                    ></b-form-input>
                                                </b-form-group>
                                            </div>
                                        </div>
                                        <b-form-group label="Email:" label-for="email-input">
                                            <b-form-input
                                                id="email-input"
                                                v-model="form.email"
                                                type="email"
                                                required
                                                placeholder="Email"
                                                name="email"
                                                :disabled="!edit"
                                            ></b-form-input>
                                        </b-form-group>
                                        <div class="text-right">
                                            <template v-if="!edit">
                                                <b-button variant="primary"  @click="edit = true">Edit</b-button>
                                            </template>
                                            <template v-else>
                                                <b-button variant="primary"  :disabled="sending_request" @click="update">Save</b-button>
                                                <b-button variant="default"  @click="getForm();edit = false">Cancel</b-button>
                                            </template>
                                        </div>
                                    </template>

                                    <template  v-if="section === 1">

                                        <b-row>
                                            <b-col cols="12" md="6">
                                                <b-row align-v="center">
                                                    <b-col cols="9" class="my-auto">
                                                        Multi Currency?
                                                    </b-col>
                                                    <b-col cols="3" class="my-auto">
                                                        <label class="custom-toggle mt-2">
                                                            <input name="multi_currency" type="checkbox" v-model="form.multi_currency"
                                                                   value="1" >
                                                            <span class="custom-toggle-slider rounded-circle" data-label-off="No"
                                                                  data-label-on="Yes"></span>
                                                        </label>
                                                    </b-col>
                                                    <b-col cols="12" class="my-auto">
                                                        <div class="form-group mt-2 border border-light" v-show="!form.multi_currency">
                                                            <input type="hidden" name="currency" :value="form.currency"
                                                                   v-if="!form.multi_currency" class="" />
                                                            <select v-model="form.currency" class="form-control " data-toggle="select" >
                                                                <option>SGD</option>
                                                                <option>MYR</option>
                                                            </select>
                                                        </div>
                                                    </b-col>
                                                </b-row>
                                                <b-row >
                                                    <b-col cols="9" class="my-auto">
                                                        E2E?
                                                    </b-col>
                                                    <b-col cols="3" class="my-auto">
                                                        <label class="custom-toggle mt-2">
                                                            <input name="e2e" type="checkbox" v-model="form.e2e"
                                                                   value="1" >
                                                            <span class="custom-toggle-slider rounded-circle" data-label-off="No"
                                                                  data-label-on="Yes"></span>
                                                        </label>
                                                    </b-col>
                                                </b-row>
                                                <b-row >
                                                    <b-col cols="9" class="my-auto">
                                                        Batch
                                                    </b-col>
                                                    <b-col cols="3" class="my-auto">
                                                        <label class="custom-toggle mt-2">
                                                            <input name="batch" type="checkbox" v-model="form.batch"
                                                                   value="1" >
                                                            <span class="custom-toggle-slider rounded-circle" data-label-off="No"
                                                                  data-label-on="Yes"></span>
                                                        </label>
                                                    </b-col>
                                                </b-row>
                                            </b-col>
                                            <b-col cols="12" md="6">
                                                <div class="form-group mb-3">
                                                    <label>Shop Image</label>
                                                    <template >
                                                        <input-field-component
                                                            name="logo"
                                                            :model.sync="form.shop_image"
                                                            type="image"
                                                        />
                                                    </template>
                                                </div>
                                            </b-col>
                                        </b-row>

                                        <div class="text-right">

                                            <b-button variant="primary"  :disabled="sending_request" @click="update">Save</b-button>
                                            <b-button variant="default"  @click="getForm(); edit = false; section = 0">Cancel</b-button>
                                        </div>
                                    </template>

                                </div>
                            </div>
                        </b-form>
                    </div>
                </div>
            </b-col>
        </b-row>

        <b-row>
            <b-col>
                <b-card no-body class="border-0 bg-transparent">
                    <b-tabs card >
                        <b-tab title="Accounts" lazy>
                            <admin-shop-account-component :shop="shop"></admin-shop-account-component>
                        </b-tab>
                        <b-tab title="Orders" lazy>
                            <admin-shop-order-component :shop="shop"></admin-shop-order-component>
                        </b-tab>
                        <!-- <b-tab no-body title="Products" lazy>
            
                         </b-tab>-->
                    </b-tabs>
                </b-card>
            </b-col>
        </b-row>
    </div>
</template>

<script>
export default {
    name: "AdminShopDetailsComponent",
    props: [
        'shop',
        'user'
    ],
    data() {
        return {
            request_url: '/web/shops',
            section: 0,
            edit: false,
            sending_request: false,
            options: [
                { text: 'Store Details', value: 0 },
                { text: 'Additional Settings', value: 1 },
            ],
            data: [],
            pagination: {
                current_page: 1,
                from: 1,
                last_page: 1,
                to: 10,
                total: 0,
            },
            limit: 10,
            form: {
                name: null,
                email: null,
                phone_number: null,
                multi_currency: null,
                currency: 'SGD',
                batch: null,
                shop_image: [],
                e2e: null,
            },
        }
    },
    created() {
        if( this.shop ){
            this.getForm();
        }
    },
    methods: {
        getForm() {
            this.form.name = this.shop.name;
            this.form.email = this.shop.email;
            this.form.phone_number = this.shop.phone_number;
            this.form.multi_currency = !this.shop.is_multi_currency;
            this.form.currency = this.shop.currency ? this.shop.currency : 'SGD';
            this.form.batch = this.shop.batch;
            this.form.e2e = this.shop.e2e ;
        },
        update: function (event) {
            if (this.sending_request) {
                return;
            } else {
                this.sending_request = true;
            }
            notify('top', 'Info', 'Update Shop..', 'center', 'info');
            event.preventDefault();

            let params = {
                name: this.form.name,
                email: this.form.email,
                phone_number: this.form.phone_number,
                currency: this.form.currency,
                multi_currency: this.form.multi_currency,
                batch: this.form.batch,
                shop_image: this.form.shop_image,
                e2e: this.form.e2e,
            }

            if (this.form.multi_currency) {
                params['currency'] = null;
            }

            axios({
                method: "put",
                url: this.request_url + "/" + this.shop.id,
                data: params
            }).then( (response) => {
                this.sending_request = false;
                let data = response.data;
                if (data.meta.error) {
                    notify('top', 'Error', data.meta.message, 'center', 'danger');
                } else {
                    swal({
                        title: 'Success',
                        text: 'You have successfully edited the shop.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-success'
                    })
                    this.section = 0;
                }
            }).catch((error) => {
                this.sending_request = false;
                if (error.response && error.response.data && error.response.data.meta) {
                    notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                } else {
                    notify('top', 'Error', error, 'center', 'danger');
                }
            });
        },
    }
}
</script>
