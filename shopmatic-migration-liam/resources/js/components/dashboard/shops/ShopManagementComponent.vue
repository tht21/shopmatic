<template>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 col-lg-3 py-3 pr-4">
                    <h2 class="font-weight-light text-primary text-center">Shop Management</h2>
                    <hr/>
                    <div :class="'import-item ' + ((selected_tab === 0) ? 'active' : '')" @click="select(0)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-shop text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Shops</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div :class="'import-item ' + ((selected_tab === 1) ? 'active' : '')" @click="select(1)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-circle-08 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Users</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9 py-3 pl-5 border-left-md-1">
                    <b-row v-show="selected_tab === 0">
                        <b-col md="4" class="mb-3" v-for="(shop, index) in shops" v-bind:key="'shop-'+index">
                            <b-card no-body>
                                <b-card-body>
                                    <b-row class="justify-content-between align-items-center">
                                        <b-col>
                                            <div class="bg-lightest">
                                                <img v-if="shop.logo" :src="shop.logo"
                                                     class="shop-logo"/>
                                                <img v-else src="/images/default.png"
                                                     class="shop-logo"/>
                                            </div>
                                        </b-col>
                                    </b-row>
                                    <b-row class="mt-4 mb-1">
                                        <b-col>
                                            <h6 class="surtitle text-muted">Shop Name</h6>
                                            <h3 class="d-block">{{shop.name}} <b-badge variant="primary"
                                                v-if="current_shop.id === shop.id">current</b-badge></h3>
                                        </b-col>
                                    </b-row>
                                    <b-row class="mb-1">
                                        <b-col>
                                            <h6 class="surtitle text-muted">Currency</h6>
                                            <h3 class="d-block">{{shop.currency ? shop.currency : '-'}}</h3>
                                        </b-col>
                                    </b-row>
                                    <b-row class="mb-1">
                                        <b-col>
                                            <h6 class="surtitle text-muted">Email</h6>
                                            <h3 class="d-block">{{shop.email}}</h3>
                                        </b-col>
                                    </b-row>
                                    <b-row>
                                        <b-col>
                                            <h6 class="surtitle text-muted">Phone Number</h6>
                                            <h3 class="d-block">{{shop.phone_number ? shop.phone_number : '-'}}</h3>
                                        </b-col>
                                    </b-row>
                                </b-card-body>
                                <b-card-footer>
                                    <b-button variant="primary" size="sm" v-if="current_shop.id !== shop.id"
                                              @click="switchShop(shop)">
                                        Switch
                                    </b-button>
                                    <b-link v-if="current_shop.id !== shop.id"
                                            href="#" v-b-tooltip.hover
                                            class="float-right"
                                            @click="removeShop(shop.id)"
                                            title="Click to remove shop">
                                        <i class="fa fa-trash text-muted"></i>
                                    </b-link>
                                    <b-link href="#" v-b-tooltip.hover
                                            class="float-right mr-2"
                                            @click="settingShop(shop)"
                                            title="Click to edit shop">
                                        <i class="fa fa-cog text-muted"></i>
                                    </b-link>
                                </b-card-footer>
                            </b-card>
                        </b-col>
                    </b-row>
                    <b-col md="12" v-show="selected_tab === 1">
                        <user-management-component :ref="ref_name.create_user" :auth_user="auth_user"
                                                   :is_modal="true"></user-management-component>
                    </b-col>
                </div>
            </div>
        </div>

        <b-modal size="lg" :ref="ref_name.shop" title="Shop details" :header-bg-variant="'primary'" :hide-footer="true">
            <template v-slot:modal-header="{ close }">
                    <span>
                        <h3 class="text-white">{{selected_shop ? 'Edit Shop' : 'Create Shop'}}</h3>
                        <h4 class="text-white" v-if="selected_shop">{{selected_shop.name}}</h4>
                    </span>
            </template>
            <create-shop-component :is_modal="true" @hideModal="hideShopModal"
                                   :shop="selected_shop"></create-shop-component>
        </b-modal>

        <b-modal size="lg" :ref="ref_name.user" title="User details" :header-bg-variant="'primary'" :hide-footer="true">
            <template v-slot:modal-header="{ close }">
                    <span>
                        <h3 class="text-white">Create User</h3>
                    </span>
            </template>
            <create-user-management-component :is_modal="true"
                                              @hideModal="hideUserModal"></create-user-management-component>
        </b-modal>
    </div>
</template>
<script>

    export default {
        name: "ShopManagementComponent",
        props: {
            auth_user: {
                type: Object,
                default: null,
            },
            current_shop: {
                type: Object,
                default: null,
            },
            global: {
                type: Object,
                default: null,
            }
        },
        data() {
            return {
                request_url: {
                    shop: '/web/shops',
                },
                selected_tab: 0,
                selected_shop: 0,
                retrieving: {
                    shop: false,
                    user: false,
                },
                subscriptions: null,
                shops: [],
                sending_request: false,
                pagination: null,
                ref_name: {
                    shop: "pre_shop_modal",
                    user: "pre_user_modal",
                    create_user: "pre_create_user_modal",
                }
            }
        },
        watch: {
            global() {
                if (typeof this.global.toggle != 'undefined' && this.global.toggle) {
                    if (this.selected_tab === 0) {
                        this.openShopModal();
                    } else {
                        this.isSubscriptionMaxUser()
                        this.openUserModal();
                    }
                }
            }
        },
        created() {
            this.retrieveShop();
            this.retrieveSubscriptions();
        },
        methods: {
            retrieveSubscriptions() {
                axios.get('/web/subscriptions').then((response) => {
                    response = response.data
                    if (response.meta.error) {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    } else {
                        this.subscriptions = response.response
                    }

                })
            },
            retrieveShop() {
                if (this.retrieving.shop) {
                    return;
                }
                this.retrieving.shop = true;
                let parameters = {}
                axios.get(this.request_url.shop, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.shops = data.response.items;
                    }
                    this.retrieving.shop = false;
                }).catch((error) => {
                    this.retrieving.shop = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            currectUserShops(id) {
                return !this.auth_user.shops.filter((shop) => {
                    return shop.id == id;
                });
            },
            select(tab) {
                this.selected_tab = tab;
            },
            openShopModal() {
                this.selected_shop = null;
                this.$refs[this.ref_name.shop].show();
            },
            hideShopModal() {
                this.selected_shop = null;
                this.$refs[this.ref_name.shop].hide();
                this.retrieveShop();
            },
            removeShop(id) {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                swal({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    type: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                        notify('top', 'Info', 'Removing user..', 'center', 'info');

                        axios({method: "delete", url: this.request_url.shop + "/" + id}).then((response) => {
                            response = response.data;
                            this.sending_request = false;
                            if (response.meta.error) {
                                notify('top', 'Error', response.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Remove successfully', 'center', 'success');
                                this.retrieveShop();
                            }
                        }).catch((error) => {
                            this.sending_request = false;
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                        });

                    } else {
                        this.sending_request = false;
                    }
                })
            },
            isSubscriptionMaxUser() {
              if(!this.subscriptions.checkUserLimit) {
                  notify('top', 'Error', 'your subscription plan creation user quota has been exhausted', 'center', 'danger');
              }
            },
            switchShop(shop) {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                swal({
                    title: 'Are you sure?',
                    text: 'Switch to ' + shop.name,
                    type: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                        notify('top', 'Info', 'Switching user..', 'center', 'info');
                        axios.post(this.request_url.shop + '/switch/' + shop.id, {}).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Switch successfully', 'center', 'success');
                                this.current_shop = shop;
                            }
                            this.retrieving.shop = false;
                            this.sending_request = false;
                        }).catch((error) => {
                            this.retrieving.shop = false;
                            this.sending_request = false;
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                        });
                    } else {
                        this.sending_request = false;
                    }
                })
            },
            settingShop(shop) {
                this.$refs[this.ref_name.shop].show();
                this.selected_shop = shop;
            },
            openUserModal() {
                this.$refs[this.ref_name.user].show();
            },
            hideUserModal() {
                this.$refs[this.ref_name.user].hide();
                this.$refs[this.ref_name.create_user].retrieve();
            },
        }
    }
</script>
<style scoped>
    .shop-logo {
        height: 100px;
        width: 100%;
        -o-object-fit: cover;
        object-fit: cover;
    }
</style>
