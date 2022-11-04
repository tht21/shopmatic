<template>
    <div id="order-index">
        <div class="card">
            <!-- Card header -->
            <div class="card-header border-0">
                <h3 class="mb-0">Orders&nbsp;
                    <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
                    <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse" @click="hideDownloadedFiles"><i class="fa fa-filter"></i></button>
                    <button class="btn btn-sm btn-info" @click="download"><i class="fa fa-download"></i></button>
                    <button class="btn btn-sm btn-info" @click="showDownloadedFiles">Downloaded Files {{ total_unread_files }}</button>
                </h3>
            </div>
            <div id="filter" class="collapse" v-if="!downloadedFiles">
                <div class="p-3" style="background: #f6f6f6;">
                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                            <input id="search" v-model="search" name="search" class="form-control">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="fulfillment_status" class="text-muted text-uppercase">FULFILLMENT STATUS</label>
                            <select id="fulfillment_status" v-model="fulfillment_status" name="fulfillment_status" class="form-control">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="packed">Packed</option>
                                <option value="repacked">Repacked</option>
                                <option value="ready_to_ship_pending">Ready to Ship Pending</option>
                                <option value="on_hold">On Hold</option>
                                <option value="ready_to_ship">Ready to Ship</option>
                                <option value="shipped">Shipped</option>
                                <option value="partially_shipped">Partially Shipped</option>
                                <option value="retry_ship">Retry Ship</option>
                                <option value="delivered">Delivered</option>
                                <option value="to_confirm_delivered">Pending Confirmation</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="text-muted text-uppercase">ACCOUNTS</label><br />
                            <input name="selected_accounts" type="hidden" :value="selected_accounts" />
                            <span :class="'badge mr-1 cursor-pointer px-2 py-2 mt-1 noselect ' + (!account.disabled ? 'badge-primary' : 'badge-disabled')" v-for="(account, index) in accounts" @click="toggleAccount(index)">
                                <img class="avatar avatar-xs rounded-circle" :alt="account.integration.name" :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'">
                                &nbsp;{{ account.name }}
                            </span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="text-muted text-uppercase">Integrations</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <b-form-select v-model="integration_type">
                                        <b-form-select-option value="in">In</b-form-select-option>
                                        <b-form-select-option value="not_in">Not In</b-form-select-option>
                                    </b-form-select>
                                </div>
                                <div class="col-md-8">
                                    <b-form-select v-model="selected_integration" :options="integration_options"></b-form-select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mt-2 offset-md-6">
                            <label class="text-muted text-uppercase">DATE TYPE</label>
                            <b-form-select v-model="date_type">
                                <b-form-select-option value="order_placed_at">Created Date</b-form-select-option>
                                <b-form-select-option value="order_updated_at">Updated Date</b-form-select-option>
                                <b-form-select-option value="ship_by_date">Ship By Date</b-form-select-option>
                            </b-form-select>
                        </div>
                        <div class="col-md-4 mt-2">
                            <label class="text-muted text-uppercase">FROM-TO DATE</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <b-form-datepicker
                                        id="from_date"
                                        placeholder="From date"
                                        v-model="from_date"
                                        today-button
                                        reset-button
                                        close-button
                                        class="mb-2"></b-form-datepicker>
                                </div>
                                <div class="col-md-6">
                                    <b-form-datepicker
                                        id="to_date"
                                        placeholder="To date"
                                        v-model="to_date"
                                        today-button
                                        reset-button
                                        close-button
                                        class="mb-2"></b-form-datepicker>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 text-center py-3">
                            <button class="btn btn-primary px-5" @click="filter">Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <order-task-index-component v-if="downloadedFiles === true" title="Downloaded files"
                                            request_url="/web/orders/export/tasks?type=excel&status=0,1,2"
                                            :fields="export_fields"
                                            :headers="export_headers"
                                            :update_download_status="1">
            </order-task-index-component>

            <!-- Light table -->
            <div v-if="!downloadedFiles" id="index-table" class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 130px;">ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Fulfillment Status</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <tr v-for="(item, index) in data" class="cursor-pointer" @click="clickOrder(item, index)">
                        <td style="width: 130px;">{{ item.external_id ? item.external_id : item.id }}<br /><span v-if="item.external_source" class="badge badge-info">{{ item.external_source }}</span></td>
                        <td style="width: 130px">{{ item.order_placed_at ? item.order_placed_at : item.created_at }}</td>
                        <td>{{ item.customer_name ? item.customer_name : 'N/A' }}</td>
                        <td><small :class="'px-3 badge badge-' + getStatusColor(item)">{{ item.fulfillment_status_text }}</small></td>
                        <td>{{ item.currency }} {{ item.grand_total ? Number(item.grand_total).toFixed(2).toLocaleString() : '-' }}<br /><small class="text-uppercase text-muted">{{ item.payment_status_text }}</small></td>
                    </tr>
                    </tbody>
                </table>

                <h3 v-if="data.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
            </div>
            <!-- Card footer -->
            <div v-if="!downloadedFiles" v-show="!retrieving" class="card-footer py-4">
                <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
            </div>
        </div>
        <div class="modal fade" id="order-details" tabindex="-1" role="dialog" aria-hidden="true" data-focus="false" data-backdrop="false" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content" v-if="order">
                    <div class="modal-header">
                        <div>
                            <span @click="closeOrder()" class="closing-right-button">&times;</span>
                            <h1 class="font-weight-light">Order {{ order.external_id }} <span :class="'px-3 badge badge-lg badge-' + getStatusColor(order)">{{ order.fulfillment_status_text }}</span> <button class="btn btn-sm btn-info ml-3" @click="updateCurrent"><i class="fa fa-sync-alt"></i></button> <small v-if="this.updating">Updating..</small></h1>
                            <span class="d-inline-block pr-3"><i class="far fa-clock mr-1"></i> Made at {{ order.order_placed_at }}</span> |
                            <span class="d-inline-block px-3"><i class="far fa-edit mr-1"></i> Updated at {{ order.order_updated_at }}</span> |
                            <span class="d-inline-block px-3"><i class="fas fa-link mr-1"></i> {{ order.external_source }}&nbsp;<span v-if="order.account">{{ order.account.region.shortcode }}&nbsp;({{ order.account.name }})</span></span>
                        </div>
                    </div>
                    <div class="modal-body">
                        <order-detail-component :order="order"></order-detail-component>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: "OrderIndexComponent",
        data() {
            return {
                downloadedFiles: false,
                total_unread_files: 0,
                export_headers: [
                    'ID', 'Download', 'Message', 'Status', 'Created At'
                ],
                export_fields: [
                    'id', 'download', 'message', 'status', 'created_at'
                ],
                data: [],
                accounts: [],
                integration_options: [
                    { value: null, text: 'All' },
                    /*{ value: '11001', text: 'Lazada' },
                    { value: '11002', text: 'Shopify' },
                    { value: '11003', text: 'Shopee' },
                    { value: '11004', text: 'Qoo10' },
                    { value: '11005', text: 'Qoo10 Legacy' },
                    { value: '11006', text: 'Woocommerce' },
                    { value: '11007', text: 'Amazon' },
                    { value: '11008', text: 'Redmart' },
                    { value: '11009', text: 'Vend' },
                    { value: '11010', text: 'Xero' },
                    { value: '11011', text: 'Ihub' },
                    { value: '11012', text: 'Presta Shop' },*/
                ],
                search: '',
                fulfillment_status: '',
                request_url: '/web/orders',
                sending_request: false,
                retrieving: false,
                updating: false,
                order: null,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                limit: 10,
                list: null,
                selected_accounts: [],
                integration_type: 'in',
                selected_integration: null,
                date_type: 'order_placed_at',
                from_date: '',
                to_date: '',
            }
        },
        methods: {
            filter: function() {
                this.selected_accounts = [];
                this.pagination.current_page = 1;
                this.accounts.forEach((account) => {
                    if (!account.disabled) {
                        this.selected_accounts.push(account.id);
                    }
                });
                if (this.selected_accounts.length > 0) {
                    this.retrieve();
                } else {
                    this.data = [];
                }
            },
            getStatusColor: function(order) {
                switch (order.fulfillment_status) {
                    // Pending
                    case 0:
                    // Processing
                    case 1:
                    // Ready to Ship
                    case 10:
                    // Partially Shipped
                    case 12:
                    // Retry Ship
                    case 13:
                    // Lost
                    case 50:
                        return 'warning';
                    // Shipped
                    case 11:
                    // Delivered
                    case 20:
                    // Pending Confirmation
                    case 21:
                        return 'success';
                    // Cancelled
                    case 30:
                        return 'danger';
                    default:
                        return 'info';
                }
            },
            toggleAccount: function(index) {
                let account = this.accounts[index];
                account.disabled = !account.disabled;
                this.filter();
            },
            retrieve: function() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    search: this.search,
                    fulfillment_status: this.fulfillment_status,
                    page: this.pagination.current_page,
                    limit: this.limit,
                    accounts: this.selected_accounts,
                    integration_type: this.integration_type,
                    integration: this.selected_integration,
                    date_type: this.date_type,
                    from_date: this.from_date,
                    to_date: this.to_date,
                    with: 'items,account',
                };
                axios.get(this.request_url, {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.pagination = data.response.pagination;
                        ctx.data = data.response.items;
                    }
                    ctx.retrieving = false;
                }).catch(function (error) {
                    ctx.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            clickOrder: function(order, pos) {
                if (this.sending_request) {
                    notify('top', 'Error', 'The order is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                this.order = order;
                this.updateCurrent();
                $('#order-details').modal('show');
            },
            closeOrder: function() {
                if (!this.order) {
                    return;
                }
                if (this.sending_request) {
                    notify('top', 'Error', 'The order is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                $('#order-details').modal('hide');
                this.editNote = false;
                this.order = null;
            },
            updateCurrent: function() {
                if (this.updating || !this.order) {
                    return;
                }
                this.updating = true;
                let ctx = this;
                axios.get('/web/orders/' + this.order.id).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.order = data.response;
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
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            },
            download() {
                //this.downloadedFiles = false;
                axios.get('/web/orders/download', {
                    params: {
                        search: this.search,
                        fulfillment_status: this.fulfillment_status,
                        page: this.pagination.current_page,
                        limit: this.limit,
                        accounts: this.selected_accounts,
                        integration_type: this.integration_type,
                        integration: this.selected_integration,
                        date_type: this.date_type,
                        from_date: this.from_date,
                        to_date: this.to_date,
                        with: 'items,account',
                    }
                }).then(response => {
                    notify('top', 'Info', 'Excel file will be downloaded shortly.', 'center', 'info');
                }).catch(error => {
                    notify('top', 'Error', 'There was an error when generating the excel file. ', 'center', 'danger');
                });
            },
            retrieveUnreadFiles() {
                axios.get('/web/orders/export/tasks?type=excel&count_unread=1').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.total_unread_files = data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            showDownloadedFiles() {
                this.downloadedFiles = !this.downloadedFiles;
            },
            hideDownloadedFiles() {
                this.downloadedFiles = false;
            }
        },
        created() {
            axios.get('/web/accounts').then((response) => {
                let data = response.data;
                if (data.meta.error) {
                    notify('top', 'Error', data.meta.message, 'center', 'danger');
                } else {
                    this.accounts = data.response.items;
                    this.filter();

                    // Add integration options
                    this.accounts.map((account) => {
                        if (account.integration) {
                            if (!this.integration_options.find(integration_option => integration_option.value == account.integration.id)) {
                                this.integration_options.push({
                                    value: account.integration.id,
                                    text: account.integration.name.replace(/_/g, ' ')
                                });
                            }
                        }
                    });
                }
            }).catch((error) => {
                if (error.response && error.response.data && error.response.data.meta) {
                    notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                } else {
                    notify('top', 'Error', error, 'center', 'danger');
                }
            });

            this.retrieveUnreadFiles();
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>
