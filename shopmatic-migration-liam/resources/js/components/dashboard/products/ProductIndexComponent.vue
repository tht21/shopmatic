<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">Products
                <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
                <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                <button class="btn btn-sm btn-danger" data-target="#delete" data-toggle="collapse"><i class="fa fa-trash"></i></button>
            </h3>
        </div>
        <div id="delete" class="collapse">
            <div class="p-3" style="background: #f6f6f6;">
                <button class="btn btn-sm btn-danger" @click="deleteOrphanedProducts"><i class="fas fa-trash"></i> Delete Orphaned Products</button>
                <button class="btn btn-sm btn-danger" @click="deleteOrphanedProductVariants"><i class="fas fa-trash"></i> Delete Orphaned Variants</button>
            </div>
        </div>
        <div id="filter" class="collapse">
            <div class="p-3" style="background: #f6f6f6;">
                <div class="row">
                    <div class="col-md-6 mt-2">
                        <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                        <input id="search" v-model="filter_form.search" name="search" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="status" class="text-muted text-uppercase">STATUS</label>
                        <select id="status" v-model="filter_form.status" name="status" class="form-control">
                            <option value="">All</option>
                            <option value="1">Draft</option>
                            <option value="10">Live</option>
                            <option value="20">Disabled</option>
                            <option value="30">Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-6 mt-2">
                        <label class="text-muted text-uppercase">ACCOUNTS</label>
                        <b-button
                            size="sm"
                            variant="link"
                            v-b-tooltip.hover.v-info
                            title="Select account wont affect orphaned product,
                            orphaned product only will be affected by the orphaned product switch below">
                            <i class="fas fa-info-circle"></i>
                        </b-button>
                        <br />
                        <span :class="'badge mr-1 cursor-pointer px-2 py-2 mt-1 noselect ' + (!account.disabled ? 'badge-primary' : 'badge-disabled')" v-for="(account, index) in accounts" @click="toggleAccount(index)">
                            <img class="avatar avatar-xs rounded-circle" :alt="account.integration.name" :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'">
                            &nbsp;{{ account.integration.name }}&nbsp;{{ account.region.shortcode }}&nbsp;({{ account.name }})
                        </span>
                    </div>
                    <div class="col-md-6 mt-2">
                        <label class="text-muted text-uppercase">Integrations</label>
                        <b-button
                            size="sm"
                            variant="link"
                            v-b-tooltip.hover.v-info
                            title="Select integration wont affect orphaned product,
                            orphaned product only will be affected by the orphaned product switch below">
                            <i class="fas fa-info-circle" ></i>
                        </b-button>
                        <div class="row">
                            <div class="col-md-4">
                                <b-form-select v-model="filter_form.integration_type">
                                    <b-form-select-option value="in">In</b-form-select-option>
                                    <b-form-select-option value="not_in">Not In</b-form-select-option>
                                </b-form-select>
                            </div>
                            <div class="col-md-8">
                                <b-form-select v-model="filter_form.integration" :options="integration_options"></b-form-select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2">
                        <label class="text-muted text-uppercase">Orphaned Products</label>
                        <b-form-checkbox class="pl-switch" v-model="filter_form.orphaned_product" switch>
                            Show Orphaned Products
                        </b-form-checkbox>
                    </div>
                    <div class="col-12 text-center py-3">
                        <button class="btn btn-info px-5" @click="reset">Reset</button>
                        <button class="btn btn-primary px-5" @click="filter">Filter</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Light table -->
        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th style="width: 130px;" class="sort desc" data-sort="id">ID</th>
                    <th style="width: 130px">Image</th>
                    <th style="width: 600px;" class="sort" data-sort="name">Name</th>
                    <!--<th class="sort" data-sort="total_sold">Sold</th>-->
                    <th>Accounts</th>
                    <th>Status</th>
                    <th>Alerts</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="item in data">
                    <td style="width: 130px;" class="id cursor-pointer" @click="clickProduct(item)">{{ item.id }}</td>
                    <td style="width: 130px" class="cursor-pointer" @click="clickProduct(item)"><img v-if="item.main_image && item.main_image  !== ''" :src="item.main_image" class="product-img-thumb"><img v-else :src="'/images/default.png'" class="product-img-thumb"></td>
                    <td style="white-space: pre-wrap;word-break: break-word;" class="name cursor-pointer" @click="clickProduct(item)">{{ item.name }}<br /><small><b>Associated SKU: {{ item.associated_sku }}</b></small></td>
                    <!--<td class="total_sold cursor-pointer" @click="clickProduct(item)">{{ item.total_sold ? item.total_sold : 0 }}</td>-->
                    <td class="cursor-pointer" @click="clickProduct(item)">
                        <div class="avatar-group">
                            <a href="#" class="avatar avatar-sm rounded-circle" data-toggle="tooltip" :data-original-title="listing.integration.name  + ' ' + listing.account.region.shortcode + ' ' + '(' + listing.account.name + ')'" v-for="listing in item.listings" v-if="listing.account">
                                <img :alt="listing.integration.name" :src="'/images/integrations/' + listing.integration.name.toLowerCase() + '.png'">
                            </a>
                        </div>
                    </td>
                    <td class="cursor-pointer" @click="clickProduct(item)">
                        <small class="px-3 badge badge-info" v-if="item.status_text === 'DRAFT'">{{ item.status_text }}</small>
                        <small class="px-3 badge badge-primary" v-if="item.status_text === 'LIVE'">{{ item.status_text }}</small>
                        <small class="px-3 badge badge-danger" v-if="item.status_text === 'DISABLED' || item.status_text === 'BANNED' || item.status_text === 'OUT OF STOCK'">{{ item.status_text }}</small>
                    </td>
                    <template v-if="item.warning_alerts > 0 || item.error_alerts > 0">
                        <td class="cursor-pointer" @click="clickAlert(item)">
                            <span v-show="item.warning_alerts > 0">
                                {{ item.warning_alerts }} <i class="fa fa-exclamation-triangle text-warning"></i>
                            </span> &nbsp;
                                <span v-show="item.error_alerts > 0">
                                {{ item.error_alerts }} <i class="fa fa-exclamation-circle text-danger"></i>
                            </span>
                        </td>
                    </template>
                    <template v-else>
                        <td></td>
                    </template>
                </tr>
                </tbody>
            </table>

            <h3 v-if="data.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
        </div>
        <!-- Card footer -->
        <div class="card-footer py-4" v-if="!retrieving">
            <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
        </div>

    </div>
</template>
<script>
    import PaginationComponent from "../components/PaginationComponent";
    export default {
        name: "ProductIndexComponent",
        components: {PaginationComponent},
        data() {
            return {
                data: [],
                accounts: [],
                categories: [],
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
                request_url: '/web/products',
                sending_request: false,
                retrieving: false,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                limit: 10,
                list: null,
                filter_form: {
                    search: '',
                    status: '',
                    accounts: [],
                    integration: null,
                    integration_type: 'in',
                    orphaned_product: true
                }
            }
        },
        created() {
            axios.get('/web/accounts').then((response) => {
                let data = response.data;
                if (data.meta.error) {
                    notify('top', 'Error', data.meta.message, 'center', 'danger');
                } else {
                    this.accounts = data.response.items;
                    this.filterAccounts();

                    // Add integration options
                    this.accounts.map((account) => {
                        if (account.integration) {
                            if (!this.integration_options.find(integration_option => integration_option.value === account.integration.id + '/' + account.region_id)) {
                                this.integration_options.push({
                                    value: account.integration.id + '/' + account.region_id,
                                    text: account.integration.name.replace(/_/g, ' ') + ' ' + account.region.name
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
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
            this.updateList();
        },
        methods: {
            filterAccounts() {
                this.filter_form.accounts = [];
                this.accounts.forEach((account) => {
                    if (!account.disabled) {
                        this.filter_form.accounts.push(account.id);
                    }
                });
                this.retrieve();
            },
            clickProduct(product) {
                window.open('/dashboard/products/' + product.slug, '_blank');
            },
            clickAlert(product) {
                window.open('/dashboard/products/alerts?product_id=' + product.id, '_blank');
            },
            toggleAccount(index) {
                let account = this.accounts[index];
                account.disabled = !account.disabled;
                this.filterAccounts();
            },
            reset() {
                this.filter_form.search = this.filter_form.status = '';
                this.filter_form.accounts = [];
                this.accounts.forEach((account) => {
                    account.disabled = false;
                });
                this.filter_form.integration = null;
                this.filter_form.integration_type = 'in';
                this.filter_form.orphaned_product = true;

                this.filterAccounts();
            },
            filter() {
                this.pagination.current_page = 1;
                this.retrieve();
            },
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.data = [];
                let parameters = {
                    search: this.filter_form.search,
                    status: this.filter_form.status,
                    page: this.pagination.current_page,
                    accounts: this.filter_form.accounts,
                    integration: this.filter_form.integration != null ? this.filter_form.integration.split('/')[0] : null,
                    region: this.filter_form.integration != null ? this.filter_form.integration.split('/')[1] : null,
                    integration_type: this.filter_form.integration_type,
                    orphaned_product: +this.filter_form.orphaned_product,
                    limit: this.limit,
                    with: 'listings.integration,listings.account',
                };
                axios.get(this.request_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.data = data.response.items;
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
            updateList() {
                if (this.data.length) {
                    if (!this.list) {
                        let options = {
                            valueNames: ['id', 'name', 'total_sold'],
                        };
                        this.list = new List('index-table', options);
                    } else {
                        this.list.reIndex();
                    }
                }
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            },
            deleteOrphanedProducts() {
                let ctx = this;
                axios.delete('/web/products/delete-orphan-products').then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                        ctx.retrieve();
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            deleteOrphanedProductVariants() {
                let ctx = this;
                axios.delete('/web/products/delete-orphan-product-variants').then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                        ctx.retrieve();
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
        },
    }
</script>

<style lang="scss" scoped>
    .pl-switch {
        padding-left: 3.5rem !important;
    }
</style>
