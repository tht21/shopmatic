<template>
    <div>
        <b-table :fields="fields" :items="data" striped show-empty selectable select-mode="single" @row-clicked="selectAccount">

            <template v-slot:cell(status)="data">
                {{ data.value | getStatus}}
            </template>

        </b-table>

        <!--Modals-->
        <b-modal id="account-details" title="Account Details" hide-footer centered size="lg">
            <template v-if="selected_account">
                <b-row>
                    <b-col cols="12">
                        <p>Account name: {{ selected_account.name }}</p>
                    </b-col>
                    <b-col cols="6">
                        <p>Integration: {{ selected_account.integration.name }}</p>
                        <p>Status: {{ selected_account.status | getStatus }}</p>
                    </b-col>
                    <b-col cols="6">
                        <p>Region: {{ selected_account.region.name }}</p>
                        <p>Currency: {{ selected_account.currency }}</p>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col>
                        <b-button variant="primary" @click="importProductSettings">
                            Import Products
                        </b-button>
                        <b-button variant="primary" @click="importOrders">
                            Import Orders
                        </b-button>
                        <template v-if="selected_account.status === 40">
                            <b-button variant="success" @click="toggleAccountStatus(0)">
                                Enable Account
                            </b-button>
                        </template>
                        <template v-if="selected_account.status === 0">
                            <b-button variant="danger" @click="toggleAccountStatus(40)">
                                Disable Account
                            </b-button>
                        </template>
                        <template v-if="selected_account.status === 30">
                            <b-button variant="info" @click="reactivate">
                                Reactivate
                            </b-button>
                        </template>
                    </b-col>
                </b-row>
            </template>
        </b-modal>

        <b-modal id="modal-import" hide-footer title="Import Settings" centered >
            <form id="import-products" role="form">
                <div class="custom-control custom-checkbox mb-0">
                    <input name="new_products" class="custom-control-input" id="new-products" type="checkbox" checked="" value="1">
                    <label class="custom-control-label text-info" for="new-products">New Products</label>
                </div>
                <small class="text-info">Whether or not to import new products.</small>
                <div class="custom-control custom-checkbox mb-0">
                    <input name="update_products" class="custom-control-input" id="update-products" type="checkbox" checked="" value="1">
                    <label class="custom-control-label text-info" for="update-products">Update Products</label>
                </div>
                <small class="text-info">Whether or not we should override any existing product details.</small>
                <div class="custom-control custom-checkbox mb-0">
                    <input name="bundle_products" class="custom-control-input" id="bundle-products" type="checkbox" value="1">
                    <label class="custom-control-label text-info" for="bundle-products">Bundle Products</label>
                </div>
                <small class="text-info">Bundle products is the splitting of inventories based on the SKU ('**', ',', '##', '/', '+')<br />
                    <span class="text-danger">
                                            If you do not have bundle products, do not enable this as it might cause issues.<br />
                                            NOTE: Check your stock for bundled products via "Inventory > Composite Inventory" as there might discrepancies for stocks related solely to bundles.
                                        </span>
                </small>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-primary my-2" @click="importProducts">Import</button>
                </div>
            </form>
        </b-modal>

        <template v-if="selected_account">
            <b-modal ref="fields-modal" centered>
                <template v-slot:modal-title>
                    <h5 class="modal-title" id="newAccountTitle">New Account<br/><small>for {{
                        selected_account.integration.name.replace('_', ' ') }} in {{ selected_account.region.name }}</small></h5>
                </template>

                <form id="create-account">
                    <input type="hidden" name="region" v-model="selected_account.region.id"/>
                    <input type="hidden" name="integration" :value="selected_account.integration.id"/>
                    <div class="form-group" v-for="field in selected_integration_fields">
                        <template v-if="field.type === 'select'">
                            <select :name="field.name" :type="field.type" class="form-control" :required="field.required">
                                <option disabled value="">{{ field.placeholder }}</option>
                                <option v-for="(value, key) in field.data" :value="key">{{ value }}</option>
                            </select>
                        </template>
                        <template v-else>
                            <input :name="field.name" :type="field.type" :placeholder="field.placeholder"
                                   class="form-control" :required="field.required"/>
                        </template>
                    </div>
                </form>

                <template v-slot:modal-footer>
                    <button type="button" class="btn btn-secondary" @click="hideModal('fields-modal')">Close</button>
                    <button type="submit" class="btn btn-primary" @click="createAccount">Create</button>
                </template>
            </b-modal>
        </template>
    </div>
</template>

<script>
    export default {
        name: "AdminShopAccountComponent",
        props: [
            'shop'
        ],
        data() {
            return {
                request_url: '/web/accounts',
                data: [],
                retrieving: false,
                sending_request: false,
                fields: [
                    { key: 'id', label: 'Id', sortable: true },
                    { key: 'name', label: 'Name', sortable: true },
                    { key: 'integration.name', label: 'Integration', sortable: true },
                    { key: 'region.name', label: 'Region', sortable: true },
                    { key: 'currency', label: 'Currency', sortable: true },
                    { key: 'status', label: 'Status', sortable: true },
                ],
                selected_account: null,
                selected_integration: null,
                selected_integration_fields: null,
            }
        },
        created() {
            if (this.shop) {
                this.retrieve();
            }
        },
        filters: {
            getStatus(status){
                switch (status) {
                    case 0:
                        return 'Active';
                    case 10:
                        return 'Issues';
                    case 30:
                        return 'Require Auth';
                    case 40:
                        return 'Disabled';
                }
            }
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                } else {
                    this.retrieving = true;
                }

                this.data = [];

                let parameter = {
                    'shop_id': this.shop.id,
                }

                axios.get(this.request_url , {
                    params: parameter
                }).then((response) => {
                    let data = response.data;
                    this.retrieving = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.data = data.response.items;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.retrieving = false;
                });
            },
            selectAccount(account){
                this.selected_account = account;
                this.getIntegrationFeatures();
                this.$bvModal.show('account-details');
            },
            importProductSettings: function () {
                this.$bvModal.hide('account-details');
                this.$bvModal.show('modal-import');
            },
            importProducts: function () {
                this.$bvModal.hide('modal-import');

                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }

                let accountName = this.selected_account.name;

                axios.post('/web/accounts/' + this.selected_account.id + '/products', new FormData($('#import-products')[0])).then( (response) => {
                    let data = response.data;
                    this.sending_request = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            html: 'We are now importing the products for account ' + accountName + ' .',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                    }

                }).catch( (error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
                this.selected_account = null;
            },
            importOrders() {
                this.$bvModal.hide('account-details');

                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }

                let accountName = this.selected_account.name;

                axios.post('/web/accounts/' + this.selected_account.id + '/orders').then( (response) => {
                    let data = response.data;
                    this.sending_request = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            html: 'We are now importing orders for account ' + accountName + ' .',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                    }

                }).catch( (error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
                this.selected_account = null;
            },
            reactivate() {
                this.$bvModal.hide('account-details');

                if (this.selected_integration.features[this.selected_account.region.id].authentication.type === 1) {

                    if (this.sending_request) {
                        return;
                    } else {
                        this.sending_request = true;
                    }

                    notify('top', 'Please wait', 'We are redirecting you to authenticate your account.', 'center', 'info');

                    axios.get('/web/integrations/redirect', {
                        params: {
                            integration_id: this.selected_account.integration.id,
                            region: this.selected_account.region.id,
                        }
                    }).then((response) => {
                        this.sending_request = false;
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            window.location.href = data.response.redirect_url;
                        }
                    }).catch((error) => {
                        this.sending_request = false;
                        if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }
                    });

                    this.selected_account = null;
                    this.selected_integration = null;
                } else {
                    this.selected_integration_fields = this.reverseObject(this.selected_integration.features[this.selected_account.region.id].authentication.fields);
                    if (this.selected_integration_fields.length === 0) {
                        notify('top', 'Error', 'There\'s an unexpected error while trying to create an account for this integration.', 'center', 'danger');
                    } else {
                        setTimeout(() => {
                            // $('#fields-modal').modal('show')
                            this.$refs['fields-modal'].show()
                        }, 300);
                    }

                }

            },

            createAccount() {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                let selected_integration = this.selected_integration;
                let selected_region = this.selected_account.region.id;

                notify('top', 'Info', 'Creating account..', 'center', 'info');

                axios.post('/web/accounts', new FormData($('#create-account')[0])).then((response) => {
                    let data = response.data;
                    this.sending_request = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (selected_integration.features[selected_region].authentication.type === 2) {
                            window.location.href = data.response.redirect_url;
                        } else {
                            // $('#fields-modal').modal('hide')
                            this.$refs['fields-modal'].hide()
                            $("#create-account").trigger('reset');
                            swal({
                                title: 'Success',
                                text: 'You have successfully added the account!',
                                type: 'success',
                                buttonsStyling: false,
                                confirmButtonClass: 'btn btn-success'
                            });
                        }
                    }
                }).catch((error) => {
                    this.sending_request = false;
                    if (typeof error.response != 'undefined' && typeof error.response.data != 'undefined' && typeof error.response.data.debug != 'undefined') {
                        notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (typeof error.meta != 'undefined' && typeof error.meta.message != 'undefined') {
                        notify('top', 'Error', error.meta.message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });

                this.selected_account = null;
                this.selected_integration = null;
            },

            getIntegrationFeatures() {
                axios.get('/web/integrations/' + this.selected_account.integration_id).then( (response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            this.selected_integration = data.response;
                        }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            reverseObject(object) {
                var newObject = {};
                var keys = [];

                for (let key in object) {
                    keys.push(key);
                }

                for (var i = keys.length - 1; i >= 0; i--) {
                    newObject[keys[i]] = object[keys[i]];
                }

                return newObject;
            },

            hideModal(ref) {
                this.$refs[ref].hide();
            },

            toggleAccountStatus(status) {
                this.$bvModal.hide('account-details');

                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }

                let parameters = {
                    'status': status,
                }

                let accountName = this.selected_account.name;

                notify('top', 'Info', 'Submitting...', 'center', 'info');

                axios.post('/web/accounts/' + this.selected_account.id + '/status', parameters).then( (response) => {
                    let data = response.data;
                    this.testing = response;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            html: accountName + ' has been changed to ' + this.$options.filters.getStatus(status) + '.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                        this.retrieve();
                    }
                    this.sending_request = false;

                }).catch( (error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
                this.selected_account = null;
            }
        }
    }
</script>
