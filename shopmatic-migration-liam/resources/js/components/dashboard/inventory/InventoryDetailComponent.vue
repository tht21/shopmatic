<template>
    <div>
        <form id="update-inventory" v-on:submit.prevent="updateInventory()">
            <div class="border-default p-3">
                <div class="row">
                    <div class="col-12">
                        <h2>Name</h2>
                        <input v-model="data.name" name="name" placeholder="Enter name" class="form-control font-size-20 text-black" min="0" required />
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-6">
                        <h2>Stock<br /><small><i>This main stock used. This can be overridden at the listing level below.</i></small></h2>
                        <input type="number" v-model="data.stock" name="stock" placeholder="Enter quantity" class="form-control font-size-20 text-black" min="0" required />
                    </div>
                    <div class="col-6">
                        <h2>Low stock notification<br /><small><i>Sends a notification when stock reaches this. Set to 0 to disable.</i></small></h2>
                        <input type="number" v-model="data.low_stock_notification" name="low_stock_notification" placeholder="Enter quantity" class="form-control font-size-20 text-black" min="0" required />
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-6">
                        <h2>Sync<br /><small><i>This is to ensure the stock on all accounts are the same.</i></small></h2>
                        <label class="custom-toggle mt-3">
                            <input type="checkbox" name="enabled" v-model="data.enabled" value="1">
                            <span class="custom-toggle-slider rounded-circle"></span>
                        </label>
                        <span style="margin-left: 65px; margin-top: -30px; display: block;">{{ data.enabled ? 'On' : 'Off'}}</span>
                    </div>
                    <div class="col-6">
                        <h2>Out of stock notification<br /><small><i>Sends a notification when the stock drops to 0.</i></small></h2>
                        <label class="custom-toggle mt-3">
                            <input type="checkbox" name="out_of_stock_notification" v-model="data.out_of_stock_notification" value="1">
                            <span class="custom-toggle-slider rounded-circle"></span>
                        </label>
                        <span style="margin-left: 65px; margin-top: -30px; display: block;">{{ data.out_of_stock_notification ? 'On' : 'Off'}}</span>
                    </div>
                </div>
                <button class="btn btn-success full-width mt-5 mb-2">Update</button>
                <small class="text-muted">Last updated {{ data.last_change }}</small>
            </div>
        </form>
        <hr />
        <div class="nav-wrapper">
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0 active" id="stock-text-tab" data-toggle="tab" href="#stock-text" role="tab" aria-controls="stock-text" aria-selected="true"><i class="ni ni-key-25 mr-2"></i>Listings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0" id="override-text-tab" data-toggle="tab" href="#override-text" role="tab" aria-controls="override-text" aria-selected="false"><i class="ni ni-controller mr-2"></i>Override <span v-show="data.total_overrides > 0" class="badge badge-danger">{{ data.total_overrides }}</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0" id="log-text-tab" data-toggle="tab" href="#log-text" role="tab" aria-controls="log-text" aria-selected="false"><i class="ni ni-single-copy-04 mr-2"></i>Logs</a>
                </li>
            </ul>
        </div>
        <div class="card shadow mt-3">
            <div class="card-body p-0">
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="stock-text" role="tabpanel" aria-labelledby="stock-text-tab">

                        <table class="table align-items-center table-flush">
                            <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Account</th>
                                <th>Stock</th>
                                <!--<th>Sold</th>-->
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody class="list">
                            <tr v-for="item in data.listings">
                                <td>{{ item.identifier_text }}</td>
                                <td>
                                    <span v-if="item.account != null">
                                        {{ item.account.integration.name }}&nbsp;{{ item.account.region.shortcode }}&nbsp;({{ item.account.name }})
                                    </span>
                                    <span v-if="item.account == null">Deleted Account</span>
                                </td>
                                <td>{{ item.stock }}</td>
                                <!--<td class="total_sold">{{ item.total_sold ? item.total_sold : 0 }}</td>-->
                                <td>{{ item.variant.currency }} {{ item.variant.price }}</td>
                                <td>

                                    <small class="px-3 badge badge-warning" v-if="item.account && item.account.status_text != 'ACTIVE'" variant="warning">
                                        Account {{ item.account.status_text }}
                                    </small>
                                    <small class="px-3 badge badge-success" v-if="item.status_text === 'LIVE'">{{ item.status_text }}</small>
                                    <small class="px-3 badge badge-danger" v-else-if="item.status_text === 'DISABLED' || item.status_text === 'OUT OF STOCK' || item.status_text === 'DELETED' || item.status_text === 'BANNED'">{{ item.status_text }}</small>
                                    <small class="px-3 badge badge-secondary" v-else>{{ item.status_text }}</small>

                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <h3 v-if="data.listings && data.listings.length === 0" class="text-muted text-center font-weight-light py-3">There is no listings using this inventory.</h3>
                    </div>
                    <div class="tab-pane fade" id="override-text" role="tabpanel" aria-labelledby="override-text-tab">
                        <table class="table align-items-center table-flush">
                            <thead class="thead-light">
                            <tr>
                                <th>Listing</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody class="list">
                            <tr v-for="item in data.listings" :class="(!item.sync_stock ? 'bg-danger text-white' : '')">
                                <td>
                                    <span v-if="item.account != null">{{ item.account.name }}<br /><small>{{ item.account.integration.name }}</small><br /></span>
                                    <span v-if="item.account == null">Deleted Account</span>
                                    <strong>{{ item.identifier_text }}</strong></td>
                                <td>
                                    <label class="custom-toggle mt-3">
                                        <input type="checkbox" name="sync_stock" v-model="item.sync_stock" value="1" @change="toggleSync(item, $event)">
                                        <span class="custom-toggle-slider rounded-circle"></span>
                                    </label>
                                    <template>
                                        <b-modal id="override-to-sync-listing" ref="override-to-sync-modal" size="md"
                                            header-bg-variant="white" :no-close-on-backdrop="true">
                                            <template v-slot:modal-header="{ close }">
                                                <h2 class="mb-0 text-black">INFORMATION</h2>
                                            </template>
                                            <div class="alert badge-success d-flex alert-dismissible fade show" role="alert">
                                                <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                                                <span class="alert-text" v-if="item.account != null">
                                                    <h3>Turning off sync for this inventory in {{item.account.integration.name}} {{item.account.region.name}} ({{item.account.name}}). Please wait…</h3>
                                                </span>
                                            </div>
                                            <template v-slot:modal-footer="{ Yes, No }">
                                                <b-button class="d-none">Yes</b-button>
                                                <b-button class="d-none">No</b-button>
                                            </template>
                                        </b-modal>
                                    </template>
                                    <template>
                                        <b-modal id="override-to-sync-write-listing" ref="override-to-sync-write-modal" size="md"
                                            header-bg-variant="white" :no-close-on-backdrop="true">
                                            <template v-slot:modal-header="{ close }">
                                                <h2 class="mb-0 text-black">INFORMATION</h2>
                                            </template>
                                            <div class="alert badge-success d-flex alert-dismissible fade show" role="alert">
                                                <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                                                <span class="alert-text">
                                                    <h3>Updating stock… Please wait for the success message to confirm the update.</h3>
                                                </span>
                                            </div>
                                            <template v-slot:modal-footer="{ Yes, No }">
                                                <b-button class="d-none">Yes</b-button>
                                                <b-button class="d-none">No</b-button>
                                            </template>
                                        </b-modal>
                                    </template>
                                    <span style="margin-left: 65px; margin-top: -23px; display: block;">{{ item.sync_stock ? 'Sync' : 'Override'}}</span>
                                    <input type="number" min="0" class="form-control mt-3" v-if="!item.sync_stock" v-model="item.stock" placeholder="Stock" />
                                    <button v-if="!item.sync_stock" type="button" :disabled="sending_request" class="btn btn-white btn-sm mt-2" @click="updateListing(item)">Update</button>
                                </td>
                                <td>{{ item.variant.currency }} {{ item.variant.price }}</td>
                                <td>
                                    <small class="px-3 badge badge-success" v-if="item.status_text === 'LIVE'">{{ item.status_text }}</small>
                                    <small class="px-3 badge badge-danger" v-if="item.status_text === 'DISABLED' || item.status_text === 'OUT OF STOCK' || item.status_text === 'DELETED'">{{ item.status_text }}</small>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <h3 v-if="data.listings && data.listings.length === 0" class="text-muted text-center font-weight-light py-3">There is no listings using this inventory.</h3>

                        <hr />
                        <div class="px-3 pb-3 d-flex">
                            <i class="fa fa-info-circle mt-2"></i> <p class="pl-3 d-inline-block">Overriding stocks will still deduct the stock if there's a new order from that listing.
                            <br /> We will not update the stock if there's an order from other marketplaces.
                            <br /> Updating the main stock does not update any overridden stocks.</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="log-text" role="tabpanel" aria-labelledby="log-text-tab">
                        <div class="list-group list-group-flush">

                            <div v-for="log in logs" class="list-group-item list-group-item-action p-3">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h3 class="mb-0">
                                            <i v-if="log.message.includes('upcoming promotion') || log.message.includes('ongoing Shopee promotion')" class="fa fa-exclamation-circle text-danger"></i>
                                            {{ log.message }}
                                        </h3>
                                        <h5>Changed from {{ log.old }} to {{ log.new }}</h5>
                                    </div>
                                    <div class="col-md-4 text-md-right">
                                        <small>{{ log.created_at }}</small>
                                    </div>
                                </div>
                            </div>
                            <div v-if="logs.length === 0 && !retrieving_logs" class="text-center p-4">
                                There are no logs available
                            </div>
                            <div v-show="!retrieving_logs" class="card-footer py-4">
                                <nav v-show="logs_pagination.last_page > 1">
                                    <ul class="pagination justify-content-center mb-0">
                                        <li v-show="logs_pagination.current_page > 2" class="page-item">
                                            <a class="page-link" @click="changeDataPage(1)" tabindex="-1">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li v-show="logs_pagination.current_page > 1" class="page-item">
                                            <a class="page-link" @click="changeDataPage(logs_pagination.current_page - 1)" tabindex="-1">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>
                                        <li v-show="logs_pagination.current_page > 1" class="page-item">
                                            <a class="page-link" @click="changeDataPage(logs_pagination.current_page - 1)">{{ logs_pagination.current_page - 1 }}</a>
                                        </li>
                                        <li class="page-item active">
                                            <a class="page-link" href="#!">{{ logs_pagination.current_page }}</a>
                                        </li>
                                        <li v-show="logs_pagination.current_page + 1 <= logs_pagination.last_page" class="page-item">
                                            <a class="page-link" @click="changeDataPage(logs_pagination.current_page + 1)">{{ logs_pagination.current_page + 1 }}</a>
                                        </li>
                                        <li v-show="logs_pagination.current_page < logs_pagination.last_page" class="page-item">
                                            <a class="page-link" @click="changeDataPage(logs_pagination.current_page + 1)">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li v-show="logs_pagination.current_page + 1 < logs_pagination.last_page" class="page-item">
                                            <a class="page-link" @click="changeDataPage(logs_pagination.last_page)">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <div class="text-center mt-2">
                                    <small class="text-uppercase">{{ logs_pagination.total }} leads total. Last Page: {{ logs_pagination.last_page }}</small>
                                </div>
                                <div class="float-right ml-auto">
                                    Jump To &nbsp;<input type="number" v-model="jump_to" placeholder="Page" :min="1" :max="logs_pagination.last_page" class="form-control d-inline-block" @change="changeDataPage(jump_to)" style="width: 100px" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <b-link class="mt-2 text-danger text-underline float-right"
                @click="deleteConfirmation()">Delete
        </b-link>

        <!-- Delete Account Modal -->
        <b-modal id="delete-account-modal" ref="delete-inventory-modal" header-bg-variant="danger" hide-footer>
            <template v-slot:modal-title>
                <h3 class="mt-2 text-white">Delete data for: {{ (data) ? inventory.sku : '' }}</h3>
            </template>

            <h3>Are you sure to delete?</h3>

            <div class="mt-3">
                <b-button variant="link" @click="closeCancel()">Close</b-button>
                <b-button variant="danger" class="ml-auto float-right" @click="confirmDelete">Delete</b-button>
            </div>
        </b-modal>
    </div>
</template>
<script>
    export default {
        name: "InventoryDetailComponent",
        props: [
            'inventory'
        ],
        watch: {
            inventory: function(newInv, prev) {
                if (newInv && newInv.id) {
                    this.retrieveLogs();
                    this.data = this.inventory;
                }
            },
        },
        data() {
            return {
                sending_request: false,
                updating: false,
                jump_to : '',
                logs_pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                retrieving_logs: false,
                logs: [],
            }
        },
        created() {
            this.data = this.inventory;
            this.retrieveLogs();
        },
        methods: {
            hideModalListing() {
                this.$refs['override-to-sync-modal'][0].hide();
                this.$refs['override-to-sync-write-modal'][0].hide();
            },
            checkUpdate(listing) {
                if(listing.sync_stock !== false && listing.sync_stock !== true) {
                    this.$refs['override-to-sync-write-modal'][0].show();
                }
            },
            updateListing(listing) {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                let ctx = this;
                let data = {
                    '_method': 'PUT',
                    'sync_stock': listing.sync_stock ? 1 : 0,
                    'stock': listing.stock,
                };
                this.checkUpdate(listing);
                axios.post('/web/inventory/' + this.data.id + '/listings/' + listing.id, data).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                        ctx.sending_request = false;
                    } else {

                        if (ctx.$parent) {
                            ctx.$parent.retrieve();
                        }
                        ctx.updateCurrent();
                        ctx.$refs['override-to-sync-write-modal'][0].hide();
                        ctx.sending_request = false;
                        if (listing.sync_stock !== false) {
                            notify('top', 'Success', 'Successfully updated the listing.', 'center', 'success');
                        }
                    }
                }).catch(function (error) {
                    ctx.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            toggleSync(listing, event) {
                if (this.sending_request) {
                    return;
                }
                if (listing.sync_stock == false) {
                    this.$refs['override-to-sync-modal'][0].show();
                    this.updateListing(listing);
                } else {
                    this.updateListing(listing);
                    notify('top', 'Success', 'Successfully updated the listing.', 'center', 'success');
                }
            },
            updateInventory() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                let ctx = this;
                let data = new FormData($('#update-inventory')[0]);
                // This is because axios.put doesn't support FormData directly, might be some setting, but this works
                data.append("_method", "put");
                notify('top', 'Info', 'Updating inventory..', 'center', 'info');
                axios.post('/web/inventory/' + this.data.id, data).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                        ctx.sending_request = false;
                    } else {
                        notify('top', 'Success', 'Successfully updated the inventory.', 'center', 'success');
                        ctx.updateCurrent();
                        if (ctx.$parent) {
                            ctx.$parent.retrieve();
                        }
                        ctx.sending_request = false;
                    }
                }).catch(function (error) {
                    ctx.sending_request = false;
                    if (typeof error.response != 'undefined' && typeof error.response.data != 'undefined' && typeof error.response.data.meta != 'undefined' && error.response.data.meta.error && typeof error.response.data.meta.status_code != 'undefined' && error.response.data.meta.status_code == 500 ) {
                        if (error.response.data.meta.message.includes("Unable to update inventory")) {
                            notify('top', 'Error', 'Unable to update inventory.', 'center', 'danger');
                        } else {
                            swal({
                                title: 'Error',
                                text: error.response.data.meta.message,
                                type: 'warning',
                                buttonsStyling: false,
                                confirmButtonClass: 'btn btn-danger'
                            })
                        }
                        //notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveLogs() {
                if (this.retrieving_logs) {
                    return;
                }
                this.retrieving_logs = true;
                let ctx = this;
                ctx.logs = [];
                let parameters = {
                    page: this.logs_pagination.current_page,
                    limit: 20,
                };
                axios.get('/web/inventory/' + this.data.id + '/logs', {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.logs_pagination = data.response.pagination;
                        ctx.logs = data.response.items;
                    }
                    ctx.retrieving_logs = false;
                }).catch(function (error) {
                    ctx.retrieving_logs = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            changeDataPage(page) {
                if (page < 1) {
                    page = 1;
                } else if (page > this.logs_pagination.last_page) {
                    page = this.logs_pagination.last_page;
                }
                this.logs_pagination.current_page = page;
                this.retrieveLogs();
            },
            updateCurrent() {
                if (this.updating || !this.data) {
                    return;
                }
                this.updating = true;
                let ctx = this;
                axios.get('/web/inventory/' + this.data.id).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.hideModalListing();
                        ctx.data = data.response;
                        ctx.retrieveLogs();
                    }
                    ctx.updating = false;
                }).catch(function (error) {
                    ctx.updating = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            deleteConfirmation() {
                this.$refs['delete-inventory-modal'].show();
            },
            closeCancel() {
                this.$refs['delete-inventory-modal'].hide();
            },
            confirmDelete() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Deleting..', 'center', 'info');
                axios.delete('/web/inventory/' + this.data.id, {}).then((response) => {
                    let data = response.data;
                    this.sending_request = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully deleted the product inventory!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closeCancel();
                            this.$emit('closeInventory');
                        });
                    }
                }).catch((error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                })
            }
        },
    }
</script>
