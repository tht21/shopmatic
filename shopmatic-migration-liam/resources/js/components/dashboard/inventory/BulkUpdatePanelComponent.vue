<template>
    <div>

        <div class="border-0">
            <h3 class="mb-3">Inventory
                <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
                <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i
                    class="fa fa-filter"></i></button>
            </h3>
        </div>
        <div id="filter" class="collapse">
            <div class="p-3" style="background: #f6f6f6;">
                <b-row>
                    <b-col md="6" class="mt-2">
                        <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                        <input id="search" v-model="search" name="search" class="form-control" type="text">
                    </b-col>
                    <b-col md="6" class="mt-2">
                        <label for="stock" class="text-muted text-uppercase">STOCK</label>
                        <b-row>
                            <b-col md="3">
                                <select v-model="stock_opt" name="stock_opt" class="form-control">
                                    <option value="=" selected>=</option>
                                    <option value="!=">!=</option>
                                    <option value=">=">>=</option>
                                    <option value="<="><=</option>
                                    <option value=">">></option>
                                    <option value="<"><</option>
                                </select>
                            </b-col>
                            <b-col md="9">
                                <input id="stock" v-model="stock" name="stock" class="form-control" type="number">
                            </b-col>
                        </b-row>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col md="12" class="text-center py-3">
                        <button class="btn btn-primary px-5" @click="retrieve">Filter</button>
                    </b-col>
                </b-row>
            </div>
        </div>

        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th class="text-center">SKU</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center" style="width: 280px">Edit</th>
                    <th class="text-center">Sync</th>
                    <th class="text-center" style="width: 100px">Low Stock</th>
                    <th class="text-center">OOS</th>
                    <th class="text-center"></th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in data">
                    <td class="text-center">{{item.sku}}</td>
                    <td class="text-center">
                        {{item.stock}} <span v-show="item.edit_stock"> > <span class="bg-info" style="padding: 2px 5px">{{editStock(item)}}</span> </span>
                    </td>
                    <td class="text-center">
                        <b-input-group>
                            <template v-slot:prepend>
                                <b-form-radio-group
                                    id="btn-radios-2"
                                    v-model="item.selected"
                                    :options="options"
                                    buttons
                                    button-variant="outline-primary"
                                    name="radio-btn-outline"
                                ></b-form-radio-group>
                            </template>
                            <b-form-input
                                :id="'edit'+index+'-input'"
                                :name="'edit'+index+'-input'"
                                v-model="item.edit_stock"
                                placeholder="Stock"
                                type="number"
                            />
                        </b-input-group>
                    </td>
                    <td class="text-center pl-6">
                        <b-form-checkbox v-model="item.edit_enabled" name="check-button" switch>
                        </b-form-checkbox>
                    </td>
                    <td class="text-center">
                        <b-form-input
                            :id="'edit'+index+'-low-stock-input'"
                            :name="'edit'+index+'-low-stock-input'"
                            v-model="item.edit_low_stock_notification"
                            placeholder="Low Stock"
                            type="number"
                        />
                    </td>
                    <td class="text-center pl-6">
                        <b-form-checkbox v-model="item.edit_out_of_stock_notification" name="check-button" switch>
                        </b-form-checkbox>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary"
                                @click="save(item)"
                                :disabled="disabledButton(item)"
                        >
                            Save
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>

            <h3 v-if="data.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is
                nothing that matches your criteria!</h3>
        </div>

        <div>
            <b-col md="12" class="text-center py-3">
                <button class="btn btn-primary px-5" @click="saveAll"
                        :disabled="disabled_save_all_button">Save All
                </button>
            </b-col>
        </div>

        <div class="card-footer py-4" v-if="!retrieving">
            <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
        </div>
    </div>
</template>

<script>
    import axios from "axios";

    export default {
        name: "BulkUpdatePanelComponent",
        data() {
            return {
                search: '',
                stock: '',
                stock_opt: '=',
                data: [],
                items: [],
                request_url: '/web/inventory',
                options: [
                    {text: 'Add', value: 'add'},
                    {text: 'Set', value: 'set'},
                ],
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 50,                    
                    limit: 5,
                },
                limit: 10,
                retrieving: false,
                sending_request: false,
                disabled_save_all_button: true,
            }
        },
        watch: {
            items() {
                this.disabled_save_all_button = this.items.length <= 0;
            },
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.data = [];
                let parameters = {
                    search: this.search,
                    stock: this.stock,
                    stock_opt: this.stock_opt,
                    page: this.pagination.current_page,
                    limit: this.limit,
                };

                axios.get(this.request_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.data = data.response.items.map((item) => {
                            item.out_of_stock_notification = item.out_of_stock_notification === 0 ? false : true;
                            item.edit_out_of_stock_notification = item.out_of_stock_notification;
                            item.edit_low_stock_notification = item.low_stock_notification;
                            item.enabled = item.enabled === 0 ? false : true;
                            item.edit_enabled = item.enabled;
                            item.selected = 'add';
                            return item;
                        });
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
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            },
            save(item) {

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Update Inventory..', 'center', 'info');

                let params = {
                    enabled: item.edit_enabled,
                    out_of_stock_notification: item.edit_out_of_stock_notification,
                    low_stock_notification: item.edit_low_stock_notification,
                };

                if (item.edit_stock) {
                    params['stock'] = this.editStock(item);
                }

                return axios({method: "put", url: this.request_url + '/' + item.id, data: params,}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');

                        this.sending_request = false;
                    } else {
                        notify('top', 'Success', "You have successfully edited the inventory.", 'center', 'info');
                        if (item.edit_stock) {
                            //item.stock = this.editStock(item);
                            item.stock = data.response.stock;
                            item.edit_stock = null;
                        }
                        item.selected = 'add';
                        item.out_of_stock_notification = item.edit_out_of_stock_notification;
                        item.low_stock_notification = item.edit_low_stock_notification;
                        item.enabled = item.edit_enabled;
                        this.sending_request = false;
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
            },
            editStock(item) {
                if (item.selected === 'add') {
                    return item.stock + parseInt(item.edit_stock);
                } else {
                    return parseInt(item.edit_stock);
                }
            },
            disabledButton(item) {

                let status = true;

                if (item.edit_stock) {
                    status = false;
                } else if (item.out_of_stock_notification !== item.edit_out_of_stock_notification) {
                    status = false;
                } else if (item.low_stock_notification !== item.edit_low_stock_notification) {
                    status = false;
                } else if (item.enabled !== item.edit_enabled) {
                    status = false;
                }

                let result = this.items.filter((d) => {
                    return item.id === d.id;
                })

                if (status) {
                    if (result.length > 0) {
                        this.items = this.items.filter((d) => {
                            return item.id !== d.id;
                        })
                    }
                } else {
                    if (result.length <= 0) {
                        this.items.push(item);
                    }
                }

                return status;
            },
            async saveAll() {

                let items = this.items
                for(let i = 0; i < items.length; i++) {
                    let item = items[i]
                    await this.save(item)
                }
            },
        },
        created() {
            this.retrieve();
        },
    }
</script>

<style scoped>

</style>
