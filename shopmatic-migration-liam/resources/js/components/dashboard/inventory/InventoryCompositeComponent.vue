<template>
    <div @keydown.esc="closeInventory()">
        <div class="card">
            <!-- Card header -->
            <div class="card-header border-0">
                <h3 class="mb-0">Inventory <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button> <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button></h3>
            </div>
            <div id="filter" class="collapse">
                <div class="p-3" style="background: #f6f6f6;">
                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                            <input id="search" v-model="filters.search" name="search" class="form-control" type="text">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="enabled" class="text-muted text-uppercase">SYNC</label>
                            <select id="enabled" v-model="filters.enabled" name="status" class="form-control">
                                <option value="">All</option>
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="stock" class="text-muted text-uppercase">STOCK</label>
                            <div class="row">
                                <div class="col-3">
                                    <select v-model="filters.stock_opt" name="stock_opt" class="form-control">
                                        <option value="=" selected>=</option>
                                        <option value="!=">!=</option>
                                        <option value=">=">>=</option>
                                        <option value="<="><=</option>
                                        <option value=">">></option>
                                        <option value="<"><</option>
                                    </select>
                                </div>
                                <div class="col-9">
                                    <input id="stock" v-model="filters.stock" name="stock" class="form-control" type="number">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="show" class="text-muted text-uppercase">SHOW</label>
                            <b-form-select id="show" v-model="filters.show" :options="options.show" class="form-control"></b-form-select>
                        </div>
                        <div class="col-12 text-center py-3">
                            <button class="btn btn-primary px-5" @click="retrieve">Filter</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Light table -->
            <div id="index-table" class="table-responsive">

                <b-table
                    show-empty
                    thead-class="thead-light"
                    :fields="fields.inventories"
                    :items="data"
                    selectable
                    select-mode="single"
                    selected-variant=""
                    @row-clicked="clickInventory">

                    <template v-slot:cell(sku)="data">
                        <h3>{{ data.item.sku }}</h3><small>{{ data.item.name }}</small>
                    </template>
                    <template v-slot:cell(total_bundled)="data">
                        {{ data.item.bundled_inventories.length }}
                    </template>
                    <template v-slot:cell(sync)="data">
                        <small class="px-3 badge badge-danger" v-if="data.item.enabled == 0">DISABLED</small>
                        <small class="px-3 badge badge-success" v-if="data.item.enabled == 1">ENABLED</small>
                    </template>
                </b-table>
            </div>
            <!-- Card footer -->
            <div class="card-footer py-4" v-if="!retrieving">
                <pagination-component :details="pagination" @paginated="paginate"></pagination-component>
            </div>
        </div>
        <div class="inventory-side" v-if="inventory">
            <inventory-composite-form-component :selected="inventory" @update="updateInventory"></inventory-composite-form-component>
        </div>
    </div>
</template>
<script>
    export default {
        name: "InventoryCompositeComponent",
        props: ['global'],
        data() {
            return {
                data: [],

                filters: {
                    search: '',
                    stock: '',
                    stock_opt: '=',
                    enabled: '',
                    show: 0
                },
                options: {
                    show: [
                        { value: 0, text: 'Bundled'},
                        { value: 1, text: 'Not Bundled'},
                        { value: 2, text: 'All'},
                    ]
                },
                request_url: '/web/inventory',
                sending_request: false,
                inventory: null,
                retrieving: false,
                updating: false,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 50,
                },
                limit: 10,
                form: {
                    deduct_product_inventory_id: -1,
                    deduct_amount: 0
                },
                product_inventories: [],
                fields: {
                    inventories: [
                        'sku',
                        'stock',
                        'total_bundled',
                        'sync'
                    ],
                    bundled_inventories: [
                        'sku',
                        'stock',
                        'deduct_amount',
                        'action'
                    ]
                },
                product_inventory_update: {
                    id: null,
                    deduct_amount: 0
                },

                selectLoading: false
            }
        },
        methods: {
            filter() {
                this.retrieve();
            },
            clickInventory(inventory) {
                if (this.sending_request) {
                    notify('top', 'Error', 'The inventory is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                this.inventory = inventory;
            },
            closeInventory() {
                if (this.sending_request) {
                    notify('top', 'Error', 'The inventory is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                this.inventory = null;
            },
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.data = [];
                let where_has = ['bundledInventories'];
                let where_doesnt_have = [];

                if (this.filters.show == 1) {
                    where_has = [];
                    where_doesnt_have = ['bundledInventories']
                } else if (this.filters.show == 2) {
                    where_has = [];
                    where_doesnt_have = []
                }

                let parameters = {
                    search: this.filters.search,
                    enabled: this.filters.enabled,
                    stock: this.filters.stock,
                    stock_opt: this.filters.stock_opt,
                    page: this.pagination.current_page,
                    limit: this.limit,
                    accounts: this.selected_accounts,
                    with: ['bundledInventories', 'listings.account', 'listings.integration', 'listings.variant'],
                    where_has: where_has,
                    where_doesnt_have: where_doesnt_have
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
            updateInventory(value) {
                this.retrieve()
                this.inventory = value
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            }
        },
        created() {
            this.retrieve();
        },
        watch: {
            global() {
                if (typeof this.global.filter != 'undefined' && this.global.filter) {
                    this.filters.show = 1;
                    this.retrieve();

                    this.$emit('update', {})
                }
            }
        }
    }
</script>
