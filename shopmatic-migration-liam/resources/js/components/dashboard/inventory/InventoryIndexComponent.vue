<template>
    <div @keydown.esc="closeInventory()" tabindex="0">
        <div class="card">
            <!-- Card header -->
            <div class="card-header border-0">
                <h3 class="mb-0">Inventory
                    <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
                    <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                    <button class="btn btn-sm btn-danger" data-target="#delete" data-toggle="collapse"><i class="fa fa-trash"></i></button>
                </h3>
            </div>
            <div id="delete" class="collapse">
                <div class="p-3" style="background: #f6f6f6;">
                    <button class="btn btn-sm btn-danger" @click="deleteOrphan"><i class="fas fa-trash"></i> Delete Orphaned Inventories</button>
                </div>
            </div>
            <div id="filter" class="collapse">
                <div class="p-3" style="background: #f6f6f6;">
                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                            <input id="search" v-model="search" name="search" class="form-control" type="text">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="enabled" class="text-muted text-uppercase">SYNC</label>
                            <select id="enabled" v-model="enabled" name="enabled" class="form-control">
                                <option value="">All</option>
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="stock" class="text-muted text-uppercase">STOCK</label>
                            <div class="row">
                                <div class="col-3">
                                    <select v-model="stock_opt" name="stock_opt" class="form-control">
                                        <option value="=" selected>=</option>
                                        <option value="!=">!=</option>
                                        <option value=">=">>=</option>
                                        <option value="<="><=</option>
                                        <option value=">">></option>
                                        <option value="<"><</option>
                                    </select>
                                </div>
                                <div class="col-9">
                                    <input id="stock" v-model="stock" name="stock" class="form-control" type="number">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label for="stock" class="text-muted text-uppercase">ORDER BY</label>
                            <div class="row">
                                <div class="col-6">
                                    <select v-model="order_by" name="order_by" class="form-control">
                                        <option value="id" selected>ID</option>
                                        <option value="sku">SKU</option>
                                        <option value="stock">Stock</option>
                                        <option value="updated_at">Last Change</option>
                                        <option value="created_at">Created Time</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select v-model="order_direction" name="order_direction" class="form-control">
                                        <option value="desc" selected>Descending</option>
                                        <option value="asc">Ascending</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label for="stock" class="text-muted text-uppercase">LOW STOCK</label>
                            <div class="pt-1">
                                <label class="custom-toggle custom-toggle-primary mt-2">
                                    <input type="checkbox" @click="toggleCheckbox" :checked="low_stock">
                                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12 text-center py-3">
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
                        <th class="sort" data-sort="sku">SKU</th>
                        <th class="sort text-center" data-sort="total_sold">Stock</th>
                        <th class="sort" data-sort="total_products">Total Listings</th>
                        <th>Last Changed</th>
                        <th>Sync</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <tr v-for="(item, index) in data" class="cursor-pointer" @click="clickInventory(item, index)">
                        <td class="sku"><h3>{{ item.sku }}</h3><small>{{ item.name }}</small></td>
                        <td class="total_sold text-center">
                            <h3 :class="item.stock <= 0 ? 'text-danger' : ''">{{ item.stock }}</h3>
                            <template v-if="item.total_overrides > 0">
                                <small class="badge badge-danger">{{ item.total_overrides }} override
                                    <template v-if="item.total_overrides > 1">s</template>
                                </small>
                            </template>
                        </td>
                        <td class="total_products">{{ item.total_products }}</td>
                        <td>{{ item.last_change }}</td>
                        <td>
                            <small class="px-3 badge badge-danger" v-if="item.enabled == 0">DISABLED</small>
                            <small class="px-3 badge badge-success" v-if="item.enabled == 1">ENABLED</small>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <h3 v-if="data.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There
                    is nothing that matches your criteria!</h3>
            </div>
            <!-- Card footer -->
            <div v-show="!retrieving" class="card-footer py-4">
                <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
            </div>
        </div>
        <div class="inventory-side" v-if="inventory">
            <div class="container-fluid p-3 px-md-5 py-md-4">
                <span @click="closeInventory()" class="closing-right-button">&times;</span>
                <h1 class="font-weight-light">Managing Inventory for {{ inventory.sku }}
                    <button class="btn btn-sm btn-info ml-3" @click="updateCurrent"><i class="fa fa-sync-alt"></i>
                    </button>
                    <small v-if="this.updating">Updating..</small></h1>

                <hr/>
                <inventory-detail-component :inventory="inventory" @closeInventory="closeInventory"></inventory-detail-component>
            </div>
        </div>
    </div>
</template>
<script>
    import InventoryDetailComponent from "./InventoryDetailComponent";
    export default {
        name: "InventoryIndexComponent",
        components: {InventoryDetailComponent},
        data() {
            return {
                data: [],
                search: '',
                stock: '',
                stock_opt: '=',
                order_direction: 'asc',
                order_by: 'sku',
                low_stock: false,
                enabled: '',
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
                list: null,
            }
        },
        methods: {
            filter: function () {
                this.pagination.current_page = 1;
                this.retrieve();
            },
            retrieve: function () {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    search: this.search,
                    enabled: this.enabled,
                    stock: this.stock,
                    stock_opt: this.stock_opt,
                    page: this.pagination.current_page,
                    limit: this.limit,
                    accounts: this.selected_accounts,
                    low_stock: this.low_stock,
                    order_by: this.order_by,
                    order_direction: this.order_direction,
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
            clickInventory: function (inventory, pos) {
                if (this.sending_request) {
                    notify('top', 'Error', 'The inventory is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                this.inventory = inventory;
                this.updateCurrent();
            },
            closeInventory: function () {
                if (!this.inventory) {
                    return;
                }
                if (this.sending_request) {
                    notify('top', 'Error', 'The inventory is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                this.inventory = null;
                this.retrieve()
            },

            updateCurrent: function () {
                if (this.updating || !this.inventory) {
                    return;
                }
                this.updating = true;
                let ctx = this;
                axios.get('/web/inventory/' + this.inventory.id).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.inventory = data.response;
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
            updateList: function () {
                if (this.data.length) {
                    if (!this.list) {
                        let options = {
                            valueNames: ['sku', 'total_sold', 'total_products'],
                        };
                        this.list = new List('index-table', options);
                    } else {
                        this.list.reIndex();
                    }
                }
            },
            select: function (tab) {
                this.selected_tab = tab;
            },
            toggleCheckbox(e) {
                this.low_stock = e.target.checked;
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            },
            deleteOrphan() {
                let ctx = this;
                axios.delete('/web/inventory/delete-orphan').then(function (response) {
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
        created() {
            this.retrieve();
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
            this.updateList();
        }
    }
</script>
