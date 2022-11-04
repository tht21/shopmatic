<template>
    <div>
        <b-card no-body>
            <b-card-header class="border-0">
                <h3 class="mb-0">Inventory Report
                    <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button><button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                </h3>
            </b-card-header>
            <div id="filter" class="collapse">
                <div class="p-3" style="background: #f6f6f6;">
                    <b-row>
                        <b-col md="6" class="mt-2">
                            <label for="date-range" class="text-muted text-uppercase ml-auto">Date</label>
                            <input id="date-range" class="daterangepicker-field form-control">
                        </b-col>
                        <b-col md="6" class="mt-2">
                            <label for="select-report-type" class="text-muted text-uppercase ml-auto">Select Report
                                Type</label>
                            <vue-multiselect id="select-report-type" v-model="valueType" :options="reportType"
                                             :show-labels="false"
                                             placeholder="Select Report Type"></vue-multiselect>
                        </b-col>
                        <b-col md="6" class="mt-2">
                            <label for="filter-by" class="text-muted text-uppercase ml-auto">Filter By</label>
                            <vue-multiselect id="filter-by" v-model="valueFilter" :options="reportFilter"
                                             :show-labels="false"
                                             placeholder="Filter By"></vue-multiselect>
                        </b-col>
                        <b-col md="6" class="mt-2">
                            <label for="select" class="text-muted text-uppercase ml-auto">Select</label>
                            <vue-multiselect id="select" v-model="valueFilterBy" :options="reportFilterBy" label="name"
                                             track-by="name" :show-labels="false"
                                             placeholder="Please Select"></vue-multiselect>
                        </b-col>
                        <b-col md="6" class="mt-2">
                            <label for="search" class="text-muted text-uppercase ml-auto">search</label>
                            <div class="input-group input-group-alternative">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                </div>
                                <input id="search" v-model="search" class="form-control form-control-alternative"
                                       placeholder="Start typing product name or other keyword to filter your report..."
                                       type="text" v-on:blur="filterProduct" v-on:keyup.enter="filterProduct">
                            </div>
                        </b-col>
                        <b-col md="12" class="text-center py-3">
                            <button class="btn btn-primary px-5" @click="retrieve">Generate</button>
                        </b-col>
                    </b-row>
                </div>
            </div>

            <div id="index-table" class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light text-center">
                    <tr>
                        <th colspan="4"></th>
                        <th colspan="4" class="border">Totals By Products, Integration</th>
                    </tr>
                    <tr>
                        <th>Product</th>
                        <th>Integration</th>
                        <th>Category</th>
                        <th class="border">Current Stock</th>
                        <th class="border">Stock Value</th>
                        <th class="border">Item Value</th>
                        <th class="border">Reorder Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th colspan="3" class="border"><strong>Totals</strong></th>
                        <td class="border"><strong>{{ calculateCurrentStock }}</strong></td>
                        <td class="border text-right"><strong>${{ calculateStockValue | formatCurrency
                            }}</strong></td>
                        <td class="border text-right"><strong>${{ calculateItemValue | formatCurrency
                            }}</strong></td>
                        <td class="border"><strong>{{ calculateReorderAmount}}</strong></td>
                    </tr>
                    <tr v-for="product in products">
                        <template v-for="listing in product.listings">
                            <td><a :href="listing.product_url" target="_blank">{{ product.name | capitalize }}</a></td>
                            <td>{{ listing.integration_id }}</td>
                            <td>{{ listing.integration_category_id }}</td>
                            <td class="border">{{ product.stock }}</td>
                            <td class="border text-right">{{ (listing.data.raw_data ? listing.data.raw_data.price : 0 ) * product.stock |
                                formatCurrency}}
                            </td>
                            <td class="border text-right">{{ (listing.data.raw_data ? listing.data.raw_data.price : 0 ) |
                                formatCurrency}}
                            </td>
                            <td class="border">{{ product.low_stock_notification }}</td>
                        </template>
                    </tr>
                    </tbody>
                </table>
            </div>


            <div class="card-footer py-4" v-if="!retrieving">
                <pagination-component :details="pagination" @paginated="paginate"></pagination-component>
            </div>
        </b-card>
    </div>
</template>

<script>

    import Multiselect from 'vue-multiselect';
    import VuePagination from "../../../VuePagination";

    export default {
        name: 'IndexInventoryComponent',
        components: {
            'vue-multiselect': Multiselect,
            'vue-pagination': VuePagination
        },
        filters: {
            formatCurrency(value) {
                if (!value) return 0;
                return parseFloat(value, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
            },
            capitalize(str) {
                let strVal = '';
                str = str.split(' ');
                for (var chr = 0; chr < str.length; chr++) {
                    strVal += str[chr].substring(0, 1).toUpperCase() + str[chr].substring(1, str[chr].length) + ' '
                }
                return strVal;
            }
        },
        props: ['integrations'],
        watch: {
            valueFilter(newVal, oldVal) {
                this.valueFilterBy = "";
                this.getFilterBy(newVal);
            },
            toggleFilter(newVal, oldVal) {
                if (newVal === false) return this.removeFilters();
            }
        },
        computed: {
            calculateCurrentStock() {
                let sum = 0;

                $.each(this.products, () => {
                    sum += parseFloat(this.stock) || 0;
                });

                return sum;
            },
            calculateStockValue() {
                let sum = 0;

                $.each(this.products, (index, value) => {
                    $.each(value.listings, (idx, val) => {
                        sum += parseFloat(val.data.raw_data ? val.data.raw_data.price : 0) * value.stock || 0;
                    });
                });

                return sum;
            },
            calculateItemValue() {
                let sum = 0;

                $.each(this.products, (index, value) => {
                    $.each(value.listings, (idx, val) => {
                        sum += parseFloat(val.data.raw_data ? val.data.raw_data.price : 0) || 0;
                    });
                });

                return sum;
            },
            calculateReorderAmount() {
                let sum = 0;

                $.each(this.products, () => {
                    sum += parseFloat(this.low_stock_notification) || 0;
                });

                return sum;
            }
        },
        data() {
            return {
                valueType: '',
                valueFilter: '',
                valueFilterBy: '',
                reportType: ['Inventory On Hand', 'Low Stock'],
                reportFilter: ['Integration', 'Account', 'Shop'],
                reportFilterBy: [],
                retrieving: false,
                dateRange: {
                    startDate: moment().format("DD MMM YYYY"),
                    endDate: moment().format("DD MMM YYYY")
                },
                search: '',
                toggleFilter: false,
                btnFilter: 'Filters',
                options: {
                    single: true,
                    forceUpdate: true,
                    orientation: 'left',
                    startDate: moment(),
                    endDate: moment(),
                    periods: ['day']
                },
                format: 'DD-MM-YYYY',
                products: {},
                inventories: {},
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                search: '',
            }
        },
        methods: {
            retrieve(form_fitler = true) {
                if (this.retrieving) {
                    return;
                } else {
                    this.retrieving = true;
                }

                if(form_fitler) {
                    notify('top', 'Info', 'Generating data ..', 'center', 'info');
                }

                let params = {
                    'search': this.search,
                    'page': this.pagination.current_page,
                    'with': ['listings.data', 'listings.integration'],
                    'report_type': this.valueType,
                    'created_date': $(".daterangepicker-field").val(),
                };

                if (this.valueFilter === "Account") {
                    params['account_id'] = this.valueFilterBy.id;
                } else if (this.valueFilter === "Shop") {
                    params['shop_id'] = this.valueFilterBy.id;
                } else if (this.valueFilter === "Integration") {
                    params['integration_type'] = this.valueFilterBy.type;
                }

                axios.get('/web/inventory', {params: params}).then((response) => {
                    this.retrieving = false;

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if(form_fitler) {
                            notify('top', 'Success', 'Finished generating data', 'center', 'success');
                        }
                        this.products = data.response.items;
                        this.pagination = data.response.pagination;
                        this.updateGlobal();

                    }
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            openFilter() {
                this.toggleFilter = this.toggleFilter ? false : true;
                this.btnFilter = this.toggleFilter ? 'Remove Filters' : 'Filters';
            },
            paginate(value) {
                this.pagination = value;
                this.retrieve(false);
            },
            getFilterBy(filter) {
                if (this.retrieving) {
                    return;
                } else {
                    this.retrieving = true;
                }

                axios.get('/web/' + filter.toLowerCase() + 's').then((response) => {
                    this.retrieving = false;

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (filter.toLowerCase() === 'integration') {
                            this.reportFilterBy = data.response.integrations;
                        } else {
                            this.reportFilterBy = data.response.items;
                        }
                    }
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            filterProduct() {
                this.search = this.search;
                this.retrieve();
            },
            updateGlobal() {
                let data = {
                    'search': this.search,
                    'with': ['listings.data', 'listings.integration', 'listings.integration_category'],
                    'report_type': this.valueType,
                    'created_date': $(".daterangepicker-field").val(),
                }

                if (this.valueFilter === "Account") {
                    data['account_id'] = this.valueFilterBy.id;
                } else if (this.valueFilter === "Shop") {
                    data['shop_id'] = this.valueFilterBy.id;
                } else if (this.valueFilter === "Integration") {
                    data['integration_type'] = this.valueFilterBy.type;
                }

                this.$emit('update', data);
            },
        },
        mounted() {
            let el = $(".daterangepicker-field");
            el.daterangepicker(
                this.options, (startDate, endDate) => {
                    var title = startDate.format('DD/MM/YYYY');
                    el.val(title);

                    this.dateRange = {
                        startDate: startDate,
                        endDate: endDate
                    };
                }
            );

            this.retrieve(false);
        }
    }
</script>
