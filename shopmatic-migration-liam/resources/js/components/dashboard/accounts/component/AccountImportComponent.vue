<template>
    <div v-if="account">
        <div class="text-center">
            <h1 class="text-center">Start Importing Products & Orders</h1>
            <b-row>
                <template v-if="Object.keys(imports).length > 0">
                    <b-col md="6" v-for="(item, key) in imports" v-bind:key="key">
                        <b-button variant="primary" type="button" @click="handle(key)" target="_blank">{{item.title}}
                        </b-button>
                        <h3 class="mt-3">{{item.content}}</h3>
                    </b-col>
                </template>
                <template v-else>
                    <h3>No Import Available</h3>
                </template>
            </b-row>
            <b-button variant="success" class="mt-5" type="button" @click="complete()">Complete
            </b-button>
        </div>

        <div class="modal fade" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="modal-notification"
             aria-hidden="true" style="display: none;">
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
                                <li>We will automatically group your orders using the listing.</li>
                                <li>You will need to run this import again if you have a new orders.</li>
                                <li>If the orders already exists, this will not update.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" @click="importOrders()">Ok, Got it</button>
                        <button type="button" class="btn btn-link text-white ml-auto" data-dismiss="modal">Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        name: "AccountImportComponent",
        props: ['account', 'currectIndex'],
        data() {
            return {
                keys: {
                    'import_products': {
                        'id': 'products',
                        'title': 'Import Products',
                        'content': 'Import all your existing products from your account',
                    },
                    'import_orders': {
                        'id': 'orders',
                        'title': 'Import Orders',
                        'content': 'Import all your existing orders from your account',
                    },
                },
                retrieving: false,
                imports: {},
            };
        },
        created() {
            this.retrieve()
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                axios.get('/web/integrations/' + this.account.integration.id, {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let features = data.response.features
                        this.setImportData(features);
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            handle(key) {
                switch (key) {
                    case 'import_orders':
                        this.importOrders();
                        break;
                    case 'import_products':
                        this.importProducts();
                        break;
                    default:
                        break;
                }
            },
            setImportData(features) {
                let data = {};

                Object.keys(this.keys).map((key) => {
                    let item = this.keys[key];

                    Object.keys(features).map((rows) => {
                        let feature = features[rows]
                        Object.keys(feature).map((row) => {
                            let items = feature[row];

                            if (item.id === row) {
                                let results = Object.keys(items).filter((d) => {
                                    return d === key;
                                })

                                if (results.length > 0) {
                                    data[key] = item
                                }
                            }
                        })
                    })

                });

                this.imports = data;
            },
            importProducts() {
                window.open('/dashboard/products/import', '_blank');
            },
            clickImportOrders() {
                $('#modal-notification').modal('show');
            },
            importOrders() {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                axios.post('/web/accounts/' + this.account.id + '/orders', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            html: 'We are now importing the orders from your account.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                    }
                    this.sending_request = false;

                }).catch((error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            complete() {
                window.location.replace('/dashboard/accounts/')
            },
        }
    }
</script>

<style scoped>

</style>
