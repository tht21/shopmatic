<template>
    <div>
        <div class="card">
            <!-- Card header -->
            <div class="card-header border-0">
                <div class="row">
                    <div class="col-6">
                        <h3 class="mb-0">Orders
                            <!--<button class="btn btn-sm btn-info" @click="retrieveOrders"><i class="fa fa-sync-alt"></i></button>-->
                            <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                        </h3>
                    </div>
                </div>
            </div>
            <div id="filter" class="collapse">
                <div class="p-3" style="background: #f6f6f6;">
                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <label class="text-muted text-uppercase">SHIP-BY DATE</label>
                            <b-form-datepicker
                                id="ship_date"
                                placeholder="Ship date"
                                v-model="ship_date"
                                today-button
                                reset-button
                                close-button
                                class="mb-2">
                            </b-form-datepicker>
                        </div>
                        <div class="col-12 text-center py-3">
                            <button class="btn btn-primary px-5" @click="filter(false)">Filter</button>
                            <button class="btn btn-info px-5" @click="filter(true)">All</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Tabs -->
            <b-tabs pills card lazy>
                <template v-for="account in accounts">
                    <b-tab @click="selectAccount(account)" title-item-class="mb-3">
                        <template v-slot:title>
                            <img :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'" height="15" width="15" />
                            {{ account.integration.name }}&nbsp;{{ account.region.shortcode }}&nbsp;({{ account.name }})
                        </template>
                        <template v-if="!is_inactive_account">
                            <div v-if="retrieving" class="text-center p-3">
                                <i class="fas fa-spinner fa-pulse font-size-30"></i>
                            </div>
                            <div v-else>
                                <!-- Group by status -->
                                <template v-for="status in statuses">
                                    <b-card header-bg-variant="primary">
                                        <template v-slot:header>
                                            <h3 class="mb-0 text-white">{{ status.label }}</h3>
                                        </template>
                                        <b-row>
                                            <template v-if="orders[status.key] && orders[status.key].length">
                                                <b-col>
                                                    <!-- Light table -->
                                                    <div id="index-table" class="table-responsive">
                                                        <table class="table align-items-center table-flush table-hover">
                                                            <tbody class="list">
                                                            <tr v-for="(order, index) in orders[status.key]" :class="'cursor-pointer ' + (selected_orders[status.key] && selected_orders[status.key][order.id] ? 'table-success' : '')" @click="toggleSelectOrder(order, status.key)">
                                                                <td>
                                                                    <h4>ORDER ID: {{ order.external_id ? order.external_id : order.id }} </h4>
                                                                    <h5>{{ order.order_placed_at ? order.order_placed_at : order.created_at }} </h5>
                                                                    <template v-for="item in order.items">
                                                                        SKU: {{ item.sku }} x <strong>{{ item.quantity }}</strong> <br/>
                                                                    </template>
                                                                    <template
                                                                        v-if="order.items[0] && order.items[0].shipment_provider"
                                                                    >
                                                                        Logistic: {{ order.items[0].shipment_provider }}
                                                                    </template>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- Card footer -->
                                                    <div v-if="pagination[status.key] && limit[status.key]" class="card-footer py-4">
                                                        <nav v-show="pagination[status.key].last_page > 1">
                                                            <ul class="pagination justify-content-center mb-0">
                                                                <li v-show="pagination[status.key].current_page > 2" class="page-item">
                                                                    <a class="page-link" @click="changePage(1, pagination[status.key], status.key)" tabindex="-1">
                                                                        <i class="fas fa-angle-double-left"></i>
                                                                    </a>
                                                                </li>
                                                                <li v-show="pagination[status.key].current_page > 1" class="page-item">
                                                                    <a class="page-link" @click="changePage(pagination[status.key].current_page - 1, pagination[status.key], status.key)" tabindex="-1">
                                                                        <i class="fas fa-angle-left"></i>
                                                                    </a>
                                                                </li>
                                                                <li v-show="pagination[status.key].current_page > 1" class="page-item">
                                                                    <a class="page-link" @click="changePage(pagination[status.key].current_page - 1, pagination[status.key], status.key)">{{ pagination[status.key].current_page - 1 }}</a>
                                                                </li>
                                                                <li class="page-item active">
                                                                    <a class="page-link" href="#!">{{ pagination[status.key].current_page }}</a>
                                                                </li>
                                                                <li v-show="pagination[status.key].current_page + 1 <= pagination[status.key].last_page" class="page-item">
                                                                    <a class="page-link" @click="changePage(pagination[status.key].current_page + 1, pagination[status.key], status.key)">{{ pagination[status.key].current_page + 1 }}</a>
                                                                </li>
                                                                <li v-show="pagination[status.key].current_page < pagination[status.key].last_page" class="page-item">
                                                                    <a class="page-link" @click="changePage(pagination[status.key].current_page + 1, pagination[status.key], status.key)">
                                                                        <i class="fas fa-angle-right"></i>
                                                                    </a>
                                                                </li>
                                                                <li v-show="pagination[status.key].current_page + 1 < pagination[status.key].last_page" class="page-item">
                                                                    <a class="page-link" @click="changePage(pagination[status.key].last_page, pagination[status.key], status.key)">
                                                                        <i class="fas fa-angle-double-right"></i>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </nav>
                                                        <div class="text-center mt-2">
                                                            <small class="text-uppercase">{{ pagination[status.key].total }} total. Last Page: {{ pagination[status.key].last_page }}</small>
                                                        </div>
                                                    </div>
                                                </b-col>
                                                <b-col cols="4">
                                                    <b-form-checkbox
                                                        :id="'select-all-checkbox-'+ status.key"
                                                        v-model="all_selected[status.key]"
                                                        :value=true
                                                        :unchecked-value=false
                                                        class="mb-1"
                                                        @change="toggleAll($event, status.key)"
                                                    >
                                                        Select All
                                                    </b-form-checkbox>
                                                    <h3>{{ (selected_orders[status.key]) ? Object.keys(selected_orders[status.key]).length : 0 }} Selected</h3>
                                                    <component v-bind:is="orderBulkActionComponent" :selected_orders.sync="selected_orders" :selected_account="selected_account" :status="status.key"></component>
                                                </b-col>
                                            </template>
                                            <template v-else>
                                                <b-col>
                                                    No order found.
                                                </b-col>
                                            </template>
                                        </b-row>
                                    </b-card>
                                </template>
                            </div>
                        </template>
                        <template v-else>
                            <b-alert variant="warning" show>
                                The selected account is inactive!
                            </b-alert>
                        </template>

                    </b-tab>
                </template>
            </b-tabs>
        </div>
    </div>
</template>

<script>
    import axios from "axios";
    import PaginationComponent from "../components/PaginationComponent";

    export default {
        name: "OrderBulkTableComponent",
        components: {PaginationComponent},
        props: ['statuses', 'total_count'],
        data() {
            return {
                accounts: [],
                selected_account: null,
                selected_orders: {},
                is_inactive_account: false,
                orders: {},
                pagination: {
                    pending: {
                        current_page: 1,
                        from: 1,
                        last_page: 1,
                        to: 10,
                        total: 0,
                    },
                    processing: {
                        current_page: 1,
                        from: 1,
                        last_page: 1,
                        to: 10,
                        total: 0,
                    },
                    ready_to_ship: {
                        current_page: 1,
                        from: 1,
                        last_page: 1,
                        to: 10,
                        total: 0,
                    }
                },
                limit: {
                    pending: 10,
                    processing: 10,
                    ready_to_ship: 10
                },
                all_selected: {
                    pending: false,
                    processing: false,
                    ready_to_ship: false
                },
                ship_date: null,
                retrieving: false,
            }
        },
        computed: {
            orderBulkActionComponent() {
                let name = null;
                if (this.selected_account.integration.name) {
                    name = this.selected_account.integration.name + 'OrderBulkActionComponent';
                }
                if (name) {
                    if (Vue.options.components[name]) {
                        return name;
                    } else {
                        return 'NoOrderActionComponent';
                    }
                }
                return 'NoOrderActionComponent';
            },
        },
        methods : {
            retrieveAccounts() {
                this.accounts = [];
                let parameters = {};

                axios.get('/web/accounts', {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.accounts = data.response.items;
                        // Retrieve the first account order
                        if (this.accounts.length > 0) {
                            this.selected_account = this.accounts[0];

                            if (this.retrieving) {
                                return;
                            }
                            this.retrieving = true;

                            // Loop and retrieve all orders under the account
                            let promisedEvents = [];

                            // Retrieve by account and fulfillment status
                            this.statuses.map((status) => {
                                promisedEvents.push(this.retrieveOrders(this.accounts[0], status.key));
                            });

                            Promise.all(promisedEvents).then(() => {
                                this.retrieving = false;
                            });
                        }
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            selectAccount (account) {
                this.selected_account = account;
                this.selected_orders = {};
                this.is_inactive_account = false;

                // Only active account
                if (this.selected_account.status === 0) {
                    if (this.retrieving) {
                        return;
                    }
                    this.retrieving = true;

                    // Loop and retrieve all orders under the account
                    let promisedEvents = [];

                    // Retrieve all orders
                    this.statuses.map((status) => {
                        // Reset pagination data
                        this.$set(this.pagination, status.key,  {
                            current_page: 1,
                            from: 1,
                            last_page: 1,
                            to: 10,
                            total: 0,
                        });
                        promisedEvents.push(this.retrieveOrders(this.selected_account, status.key));
                    });

                    Promise.all(promisedEvents).then(() => {
                        this.retrieving = false;
                    });
                } else {
                    this.is_inactive_account = true;
                    notify('top', 'Warning', 'The selected account is not active', 'center', 'warning');
                }
            },
            filter(isReset) {
                if (isReset) {
                    this.ship_date = null;
                }
                // Retrieve all orders
                this.statuses.map((status) => {
                    // Reset pagination data
                    this.$set(this.pagination, status.key,  {
                        current_page: 1,
                        from: 1,
                        last_page: 1,
                        to: 10,
                        total: 0,
                    });
                    this.retrieveOrders(this.selected_account, status.key);
                });
            },
            async retrieveOrders(account, status) {
                let parameters = {
                    fulfillment_status: status,
                    payment_status: 1,
                    page: this.pagination[status].current_page,
                    limit: this.limit[status],
                    accounts: [account.id],
                    ship_date: this.ship_date,
                    with: 'items,account',
                };
                await axios.get('/web/orders', {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        // Set orders
                        this.$set(this.orders, status, data.response.items);
                        // Set pagination
                        this.$set(this.pagination, status, data.response.pagination);
                        // Set total of orders by status
                        this.$set(this.total_count, status, data.response.pagination.total);
                        // Reset the selected orders by status
                        this.$set(this.selected_orders, status, {});
                        // Reset the all selected by status
                        this.$set(this.all_selected, status, false);
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            toggleSelectOrder(order, status) {
                if (this.selected_orders[status]) {
                    if (this.selected_orders[status][order.id]) {
                        this.$delete(this.selected_orders[status], order.id);
                    } else {
                        this.selected_orders[status] = {
                            ...this.selected_orders[status],
                            [order.id]: order
                        }
                    }
                } else {
                    this.$set(this.selected_orders, status, {[order.id]: order});
                }
            },
            changePage(page, pagination, status) {
                if (page < 1) {
                    page = 1;
                } else if (page > pagination.last_page) {
                    page = pagination.last_page;
                }
                pagination.current_page = page;

                this.retrieveOrders(this.selected_account, status);
            },
            toggleAll(checked, status) {
                if (checked) {
                    if (this.orders[status] && this.orders[status].length) {
                        this.orders[status].map((order) => {
                            this.selected_orders[status] = {
                                ...this.selected_orders[status],
                                [order.id]: order
                            }
                        })
                    }
                } else {
                    this.selected_orders[status] = {}
                }
            }
        },
        created() {
            this.retrieveAccounts();
        },
    }
</script>

<style scoped>
    .nav-item {
        padding-bottom: 10px;
    }
</style>
