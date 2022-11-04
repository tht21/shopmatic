<template>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5 col-lg-4 py-3 pr-4">
                    <h2 class="font-weight-light text-primary text-center">Where do you want to import from?</h2>
                    <hr />
                    <div :class="'import-item ' + ((selected_tab === 1) ? 'active' : '')" @click="select(1)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-ui-04 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Account</h3>
                                    <span class="text-muted small">Import from an account you have integrated.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--<div :class="'import-item ' + ((selected_tab === 2) ? 'active' : '')" @click="select(2)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-align-left-2 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Excel</h3>
                                    <span class="text-muted small">Import from an Excel file.</span>
                                </div>
                            </div>
                        </div>
                    </div>-->
                </div>
                <div class="col-md-7 col-lg-8 py-3 pl-5 border-left-md-1">
                    <template v-if="selected_tab === 0">
                        <div class="full-height d-flex align-items-center justify-content-center">
                            <h2 class="text-center font-weight-light text-muted">Select a method of importing from the left!</h2>
                        </div>

                    </template>
                    <div v-show="selected_tab === 1">

                        <div class="row">

                            <div v-for="account in accounts" class="col-6 mb-4">
                                <div class="account-box" @click="selectAccount(account)">
                                    <div class="row">
                                        <div class="col-4 text-center">
                                            <img :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'" height="50" width="50" />
                                        </div>
                                        <div class="col-8">
                                            <small class="text-uppercase text-info">{{ account.name }}</small><br />
                                            <small class="text-muted">in {{ account.region.name }}</small>
                                        </div>
                                    </div>
                                    <hr style="margin-top: 1rem; margin-bottom: 1rem;" />
                                    <div class="row">
                                        <div class="col-6 text-right"><small class="text-uppercase">Last Import</small></div>
                                        <div class="col-6 text-info"><small>{{ (account.sync_data != null && typeof(account.sync_data.import_products) !== 'undefined') ? formatDate(account.sync_data.import_products) : 'N/A'}}</small></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="accounts.length === 0" class="d-flex align-items-center justify-content-center full-height">
                            <div class="text-center">

                                <h2 class="text-muted font-weight-light">You do not have any active accounts to import from!</h2>

                                <a href="/dashboard/accounts/create" class="btn btn-info px-5">Add Account</a>
                            </div>
                        </div>
                    </div>
                    <!--<div v-show="selected_tab === 2">

                        <div class="d-flex align-items-center justify-content-center" style="min-height: 300px;">
                            <div class="col-md-8 col-md-mx-auto">

                                <upload-excel-file-component/>

                                <hr/>

                                <generate-excel-template-component/>

                            </div>
                        </div>
                    </div>-->

                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-danger modal-dialog-centered modal-" role="document">
                <div class="modal-content bg-gradient-danger">
                    <div class="modal-header">
                        <h6 class="modal-title" id="modal-title-notification">Your attention is required</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="py-3 text-center">
                            <i class="ni ni-bell-55 ni-3x"></i>
                            <h4 class="heading mt-4">You should read this!</h4>
                            <ol class="text-left">
                                <li>Please ensure that the SKU set for your listing is unique!</li>
                                <li>We will automatically group your products using the listing/parent SKU.</li>
                                <li>You will need to run this import again if you add a new product in the integration.</li>
                                <li>If the product already exists, this will not update the stock.</li>
                                <li>If inventory sync for the SKU is enabled, it will <strong>overwrite the stock</strong> using the stock in CombineSell's inventory</li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" @click="importSettings()">Ok, Got it</button>
                        <button type="button" class="btn btn-link text-white ml-auto" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-import" tabindex="-1" role="dialog" aria-labelledby="modal-import" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-danger modal-dialog-centered modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <div class="card border-0 mb-0">
                            <div class="card-header">
                                <h3 class="mb-0">Import Settings</h3>
                            </div>
                            <div class="card-body p-4">
                                <form id="import-products" role="form" @change="updateCanImport">
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
                                    <div class="custom-control custom-checkbox mb-0">
                                        <input name="remove_deleted_products" class="custom-control-input" id="remove-deleted-products" type="checkbox" value="1">
                                        <label class="custom-control-label text-info" for="remove-deleted-products">Remove Deleted Products</label>
                                    </div>
                                    <div class="custom-control custom-checkbox mb-0">
                                        <input name="remove_deleted_product_variants" class="custom-control-input" id="remove-deleted-product-variants" type="checkbox" value="1">
                                        <label class="custom-control-label text-info" for="remove-deleted-product-variants">Remove Deleted Variants</label>
                                    </div>
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-primary my-2" :disabled="!can_import" @click="importProducts">Import</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
<script>
    import GenerateExcelTemplateComponent from "./GenerateExcelTemplateComponent";
    import UploadExcelFileComponent from "./UploadExcelFileComponent";
    const axios = require('axios').default;
    export default {
        name: "ImportProductComponent",
        components: {UploadExcelFileComponent, GenerateExcelTemplateComponent},
        data() {
            return {
                accounts: [],
                request_url: '/web/accounts?status=active',
                sending_request: false,
                selected_tab: 0,
                selected_account: null,
                can_import: true
            }
        },
        created() {
            this.retrieve();
        },
        methods: {
            retrieve() {
                let ctx = this;
                ctx.data = [];
                axios.get(this.request_url, {
                    params: this.parameters
                }).then(response => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.accounts = data.response.items;
                    }
                }).catch(error => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            select (tab) {
                this.selected_tab = tab;
            },
            importSettings () {
                $('#modal-notification').modal('hide');
                $('#modal-import').modal('show');
            },
            importProducts () {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                let ctx = this;
                $('#modal-import').modal('hide');
                axios.post('/web/accounts/' + this.selected_account.id + '/products', new FormData($('#import-products')[0])).then(response => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            html: 'We are now importing the products from your account.<br /><br /> You can check the status of this import under <span class="text-success font-weight-bold">Past Imports</span>.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                    }
                    ctx.sending_request = false;

                }).catch(error => {
                    ctx.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
                this.selected_account = null;
            },
            selectAccount: function (account) {
                this.selected_account = account;
                $('#modal-notification').modal('show');
            },
            formatDate: function (date) {
                return moment.unix(date).format('MMMM Do YYYY, h:mm:ss a')
            },
            updateCanImport() {
                this.can_import = Object.keys(Object.fromEntries(new FormData($('#import-products')[0]))).length > 0;
            }
        }
    }
</script>
