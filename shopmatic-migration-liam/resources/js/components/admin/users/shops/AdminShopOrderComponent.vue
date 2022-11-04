<template>
    <div v-if="shop">
        <b-row class="mb-3">
            <b-col md="8">
                <b-form inline>
                    <date-range-picker
                        class="datepicker-input"
                        ref="picker"
                        opens="right"
                        :alwaysShowCalendars="datePickerSettings.alwaysShowCalendars"
                        v-model="filters.dateRange"
                        v-on:update="filter"
                    ></date-range-picker>

                    <b-input-group class="ml-3">
                        <div class="input-group-prepend" style="height:43px">
                            <div class="input-group-text">
                                <span class="fa fa-search"></span>
                            </div>
                        </div>
                        <b-input
                            id="inline-form-input-search"
                            @keyup="searchTimeOut()"
                            placeholder="Search"
                            variant="outline-secondary"
                            v-model="search"
                            style="height: 43px"
                        ></b-input>
                    </b-input-group>

                    <div v-show="icon === 0">
                        <b-button variant="outline-secondary" class="ml-3" data-target="#filter" aria-expanded="false" @click="icon = 1" data-toggle="collapse" pill><i class="fa fa-plus"></i> Filter</b-button>
                    </div>
                    <div v-show="icon === 1">
                        <b-button variant="outline-secondary" class="ml-3" data-target="#filter" aria-expanded="false" @click="icon = 0" data-toggle="collapse" pill><i class="fa fa-minus" ></i> Filter</b-button>
                    </div>
                </b-form>
            </b-col>
        </b-row>
        <div>
            <div id="filter" class="collapse">
                <div class="p-3">
                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <label class="text-muted text-uppercase">Accounts</label>
                            <b-form-select v-model="filters.account">
                                <b-form-select-option  v-for="(account, index) in accounts" :value="account.id" :key="index" name="accounts">{{ account.name }}</b-form-select-option>
                            </b-form-select>
                        </div>
                        <div class="col-md-6 mt-2" >
                            <label class="text-muted text-uppercase">Integrations</label>
                            <b-form-select v-model="filters.integrations" :options="integration_options"></b-form-select>

                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="text-muted text-uppercase">Fulfillment Status</label>
                            <b-form-select v-model="filters.status" :options="options.status"></b-form-select>
                        </div>
                        <div class="col-12 text-center py-3">
                            <b-button variant="primary" class="ml-3 text-white" @click="filter" pill>Filter</b-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <order-index-table-component
            :data="data"
            :products="products"
            :retrieving="retrieving"
            :pagination="pagination"
            :limit="limit"
            @paginate="paginate"
        ></order-index-table-component>

    </div>
</template>

<script>
    import DateRangePicker from "vue2-daterange-picker";

    export default {
        name: "AdminShopOrderComponent",
        components: { DateRangePicker },
        props: ['shop'],
        data() {
            return {icon: 0,
                data: [],
                products: [],
                integration_options: [],
                accounts: [],
                search: "",
                request_url: "/web/orders",
                retrieving: false,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0
                },
                limit: 10,
                selected_accounts: [],
                options: {
                    status: [
                        {text: 'All', value: null },
                        {text: 'Pending', value: 'PENDING'},
                        {text: 'Processing', value: 'PROCESSING'},
                        {text: 'Ready To Ship', value: 'READY_TO_SHIP'},
                        {text: 'Shipped', value: 'SHIPPED'},
                        {text: 'Cancelled', value: 'CANCELLED'},
                    ],
                    date: [
                        { text: "Today", value: "day" },
                        { text: "Week", value: "day_of_year" },
                        { text: "Month", value: "month" },
                        { text: "Quarter", value: "quarter" },
                        { text: "Year", value: "year" }
                    ]
                },
                filters: {
                    days: "day",
                    status: null,
                    account: 0,
                    integrations: null,
                    dateRange: {
                        startDate: null,
                        endDate: null
                    }
                },
                datePickerSettings: {
                    alwaysShowCalendars: false
                },
            }
        },
        methods: {
            getShopAccount(){
                let parameters = {
                    shop_id: this.shop.id,
                };

                axios
                    .get("/web/accounts",
                        {params:parameters})
                    .then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify("top", "Error", data.meta.message, "center", "danger");
                        } else {
                            this.accounts = data.response.items;
                            this.filter();
                            this.accounts.map((account) => {
                                if (account.integration) {
                                    if (!this.integration_options.find(integration_option => integration_option.value === account.integration.id)) {
                                        this.integration_options.push({
                                            value: account.integration.id,
                                            text: account.integration.name.replace(/_/g, ' ')
                                        });
                                    }
                                }
                            });
                            this.accounts.unshift({
                                name: 'All',
                                id: 0,
                            });
                            this.integration_options.unshift({
                                text: 'All',
                                value: null,
                            });

                        }
                    })
                    .catch((error) => {
                        if (error.response && error.response.data && error.response.data.meta) {
                            notify(
                                "top",
                                "Error",
                                error.response.data.meta.message,
                                "center",
                                "danger"
                            );
                        } else {
                            notify("top", "Error", error, "center", "danger");
                        }
                    });
                //this.retrieveTotal();
            },
            filter: function() {
                if (this.filters.account == 0) {
                    this.selected_accounts = [];
                    this.accounts.forEach((account) => {
                        if (!account.disabled) {
                            this.selected_accounts.push(account.id);
                        }
                    });
                } else {
                    this.selected_accounts = [this.filters.account]
                }
                this.retrieve();
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit;
                this.retrieve();
            },
            retrieve: function() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.data = [];
                this.products = [];
                let parameters = {
                    search: this.search,
                    date_type: "order_placed_at",
                    from_date: this.filters.dateRange.startDate,
                    to_date:
                        this.filters.dateRange.endDate != null
                            ? this.filters.dateRange.endDate
                            : "",
                    fulfillment_status: this.filters.status,
                    integration: this.filters.integrations,
                    page: this.pagination.current_page,
                    limit: this.limit,
                    accounts: this.selected_accounts,
                    with: "items,account,items.product",
                    shop_id: this.shop.id,
                };
                axios
                    .get(this.request_url, {
                        params: parameters
                    })
                    .then((response) =>{
                        let data = response.data;
                        if (data.meta.error) {
                            notify("top", "Error", data.meta.message, "center", "danger");
                        } else {
                            this.pagination = data.response.pagination;
                            this.data = data.response.items;
                            for (var i = 0; i < this.data.length; i++) {
                                this.products.push(this.data[i].items);
                            }
                        }
                        this.retrieving = false;
                    })
                    .catch((error) => {
                        this.retrieving = false;
                        if (
                            error.response &&
                            error.response.data &&
                            error.response.data.meta
                        ) {
                            notify(
                                "top",
                                "Error",
                                error.response.data.meta.message,
                                "center",
                                "danger"
                            );
                        } else {
                            notify("top", "Error", error, "center", "danger");
                        }
                    });
            },
            searchTimeOut() {
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
                this.timer = setTimeout(() => {
                    this.filter()
                }, 2000);
            },

        },
        created() {
            if (this.shop) {
                this.getShopAccount();
            }
        },
    }
</script>
