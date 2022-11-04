<template>
    <form id="create-form" role="form" @submit="send">
        <div :class="['card ', shop ? 'col-md-12 mb-0' : 'mx-md-auto col-md-10']">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-body">
                            <h1 class="font-weight-light mb-3 text-center">Store Details</h1>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-shop"></i></span>
                                    </div>
                                    <b-form-input
                                        name="name"
                                        placeholder="Name"
                                        v-model="form.name"
                                        type="text"
                                        :required="true"
                                    />
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                    </div>
                                    <input name="email" class="form-control" placeholder="Contact Email" type="email"
                                           v-model="form.email" required/>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-bell-55"></i></span>
                                    </div>
                                    <input name="phone_number" class="form-control" placeholder="Contact Number"
                                           v-model="form.phone_number" type="text" />
                                </div>
                            </div>
                            <div v-if="shop" class="form-group mb-3">
                                <label>Shop Image</label>
                                <input-field-component
                                    name="logo"
                                    :model.sync="form.shop_image"
                                    type="image"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 border-left-md-1">

                        <div class="card-body">
                            <h1 class="font-weight-light mb-3 text-center">Additional Settings</h1>
                            <div class="row">
                                <div class="col-9">
                                    Multi Currency?<br/><small class="text-muted">You can still change this later
                                    on.</small><br/>
                                </div>
                                <div class="col-3">

                                    <label class="custom-toggle mt-2">
                                        <input name="multi_currency" type="checkbox" v-model="form.multi_currency"
                                               value="1">
                                        <span class="custom-toggle-slider rounded-circle" data-label-off="No"
                                              data-label-on="Yes"></span>
                                    </label>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mt-2" v-show="!form.multi_currency">
                                        <input type="hidden" name="currency" :value="form.currency"
                                               v-if="!form.multi_currency"/>
                                        <select v-model="form.currency" class="form-control" data-toggle="select">
                                            <option>SGD</option>
                                            <option>MYR</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div :class="['col-12 text-center ', is_modal? '' : 'pb-5']">
                        <button type="submit" class="btn btn-info mb-2 mt-4 px-5">{{this.shop ? 'Update' : 'Create'}}
                            Shop
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="e2e" value="1">
    </form>
</template>
<script>
    import InputFieldComponent from "../../utility/InputFieldComponent";

    export default {
        name: "CreateShopComponent",
        components: {InputFieldComponent},
        props: {
            is_modal: {
                type: Boolean,
                default: false,
            },
            shop: {
                type: Object,
                default: null,
            }
        },
        data() {
            return {
                request_url: '/web/shops',
                redirect_url: '/dashboard',
                form: {
                    name: null,
                    email: null,
                    phone_number: null,
                    multi_currency: null,
                    currency: 'SGD',
                    shop_image: [],
                }
            }
        },
        created() {
            if (this.shop) {
                this.form.name = this.shop.name;
                this.form.email = this.shop.email;
                this.form.phone_number = this.shop.phone_number;
                this.form.multi_currency = !this.shop.is_multi_currency;
                this.form.currency = this.shop.currency ? this.shop.currency : 'SGD';
            }
        },
        methods: {
            send: function (event) {
                if (this.shop) {
                    this.update(event)
                } else {
                    this.create(event)
                }
            },
            create: function (event) {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                event.preventDefault();
                let ctx = this;
                axios.post(this.request_url, new FormData($('#create-form')[0])).then(function (response) {
                    ctx.sending_request = false;
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully created the shop.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            if (ctx.is_modal) {
                                ctx.$emit('hideModal');
                            } else {
                                window.location = ctx.redirect_url;
                            }
                        });
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
            update: function (event) {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                notify('top', 'Info', 'Update Shop..', 'center', 'info');
                event.preventDefault();
                let ctx = this;

                let params = {
                    name: this.form.name,
                    email: this.form.email,
                    phone_number: this.form.phone_number,
                    currency: this.form.currency,
                    multi_currency: this.form.multi_currency,
                    shop_image: this.form.shop_image,
                }

                if (this.form.multi_currency) {
                    params['currency'] = null;
                }

                axios({
                    method: "put",
                    url: this.request_url + "/" + this.shop.id,
                    data: params
                }).then(function (response) {
                    ctx.sending_request = false;
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
                        }).then(() => {
                            if (ctx.is_modal) {
                                ctx.$emit('hideModal');
                            } else {
                                // window.location = ctx.redirect_url;
                            }
                        });
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
            generatePasswordButton: function (event) {
                event.preventDefault();
                var password = this.generatePassword();
                jQuery('#generated-password').val(password).show();
                jQuery('input[name=password]').val(password);
            },
            generatePassword: function () {
                let text = "";
                let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

                for (var i = 0; i < 16; i++)
                    text += possible.charAt(Math.floor(Math.random() * possible.length));

                return text;
            },
            uploadFile() {
                this.attachments = this.$refs.file.files;

                let filename = [];

                $.each(this.attachments, function (key, value) {
                    filename.push(value.name);
                });
                $('.custom-file-label').html(filename.join(', '));
            },
        },
    }
</script>
