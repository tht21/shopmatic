<template>
    <div>
        <b-card no-body>
            <b-card-header class="border-0">
                <h3 class="mb-0">Retail Report
                    <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
                    <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i
                        class="fa fa-filter"></i></button>
                </h3>
            </b-card-header>
            <div id="filter" class="collapse show">
                <div class="p-3" style="background: #f6f6f6;">
                    <b-row class="mt-2">
                        <b-col md="6">
                            <label for="date-range" class="text-muted text-uppercase ml-auto">Date</label>
                            <input id="date-range" class="daterangepicker-field form-control">
                        </b-col>
                        <b-col>
                            <label class="text-muted text-uppercase">{{btnCustom}}</label>
                            <div>
                                <label class="custom-toggle mt-2">
                                    <input type="checkbox" @change="changeMode">
                                    <span class="custom-toggle-slider rounded-circle"></span>
                                </label>
                            </div>
                        </b-col>
                        <b-col md="12" class="text-center py-3">
                            <button class="btn btn-primary px-5" @click="retrieve">Generate</button>
                        </b-col>
                    </b-row>
                </div>
            </div>

            <b-row class="pt-4 mx-2">
                <b-col md="4" v-for="(item, index) in items" v-bind:key="'details-'+index">
                    <retail-details-component :item="item" :start_date="date_range.start_date" :end_date="date_range.end_date" :lists="item.data" :custom="custom" :type="type"></retail-details-component>
                </b-col>
            </b-row>

            <div id="index-table" class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                    <tr>
                        <th>Product</th>
                        <th>Revenue</th>
                        <th>Item Sold</th>
                        <th>Trend</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="product in products">
                        <td>{{ product.product ? product.product.name : '' }}</td>
                        <td>{{ currency }} {{ product.revenue | formatCurrency }}</td>
                        <td>{{ product.item_sold }}</td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
                <h3 v-if="products.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
            </div>
        </b-card>
    </div>
</template>

<script>
    export default {
        name: 'IndexRetailComponent',
        props: ['integrations'],
        filters: {
            formatCurrency: function (value) {
                if (!value) return '';
                return parseFloat(value, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
            }
        },
        watch: {
            'options.single': function (newVal, oldVal) {
                let el = $(".daterangepicker-field");
                el.data('daterangepicker').single(newVal);

                this.custom = !this.custom;
                this.btnCustom = !this.custom ? 'Custom Range' : 'By Period';

                if (this.custom) {
                    var title = this.date_range.start_date.format('DD/MM/YYYY');
                    //el.data('daterangepicker').periods(['day']);
                } else {
                    var title = this.date_range.start_date.format('DD/MM/YYYY') + ' – ' + this.date_range.end_date.format('DD/MM/YYYY');
                    //el.data('daterangepicker').periods(['day', 'week', 'month', 'year']);
                }
                el.val(title);

                //this.btnCustom = !this.custom ? 'Custom Range' : 'By Period';
            }
        },
        data() {
            return {
                items: {
                    'revenue': {
                        name: 'Revenue',
                        icon: 'fas fa-hand-holding-usd',
                        color: 'bg-success',
                        total: '',
                        digits: 2,
                        data: [],
                    },
                    'sales_count': {
                        name: 'Sales Count',
                        icon: 'fas fa-calculator',
                        color: 'bg-teal',
                        total: '',
                        data: [],
                    },
                    'gross_profit': {
                        name: 'Gross Profit',
                        icon: 'fas fa-coins',
                        color: 'bg-default',
                        total: '',
                        data: [],
                    },
                    'discount_percentage': {
                        name: 'Discount Percentage',
                        icon: 'fas fa-percentage',
                        color: 'bg-cyan',
                        total: '',
                        data: [],
                    },
                    'basket_value': {
                        name: 'Basket Value',
                        icon: 'fas fa-shopping-basket',
                        color: 'bg-warning',
                        total: '',
                        data: [],
                    },
                    'basket_size': {
                        name: 'Basket Size',
                        icon: 'fas fa-swatchbook',
                        color: 'bg-info',
                        total: '',
                        data: [],
                    }
                },
                type: '',
                retrieving: false,
                formData: {},
                currency: '',
                labels: [],
                date_range: {
                    start_date: moment().startOf('day'),
                    end_date: moment().endOf('day')
                },
                options: {
                    single: false,
                    periods: ['day', 'week', 'month', 'year'],
                    forceUpdate: true,
                    orientation: 'left',
                    ranges: {
                        'Custom Range': 'custom'
                    },
                    startDate: moment('2020-04-08'),
                    endDate: moment(),
                },
                format: 'DD-MM-YYYY',
                products: {},
                custom: false,
                groups: {},
                btnCustom: "Custom Range",
                labelCheck: [].length,
                retrieving: false,
            };
        },
        methods: {
            getReport: function () {

                let type = this.type;

                let formData = {
                    start_date: moment(this.date_range.start_date).format('DD-MM-YYYY 00:00:00'),
                    end_date: moment(this.date_range.end_date).format('DD-MM-YYYY 00:00:00'),
                    type: type
                };

                // If is period then end date should be same with start_date
                if (this.options.single) {
                    formData.end_date = moment(this.date_range.start_date).format('DD-MM-YYYY 00:00:00');
                }

                axios.get('/web/report/retails', {params: formData}).then((response) => {

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let revenue = [];
                        let salesCount = [];
                        let grossProfit = [];
                        let discount = [];
                        let basketValue = [];
                        let basketSize = [];
                        let newData = data.response;

                        $.each(newData, (idx, value) => {

                            if(value.currency) {
                                this.currency = value.currency
                            }

                            let date = {
                                month: value.month,
                                day: value.day,
                                year: value.year,
                                week:  value.week,
                                month:  value.month,
                            }
                            revenue.push({
                                'date': date,
                                'value': parseFloat(value.total_revenue),
                            });

                            salesCount.push({
                                'date': date,
                                'value': parseFloat(value.total_orders),
                            });

                            grossProfit.push({
                                'date': date,
                                'value': parseFloat(value.gross_profit),
                            });

                            discount.push({
                                'date': date,
                                'value': parseFloat(value.total_discount),
                            });

                            basketValue.push({
                                'date': date,
                                'value': parseFloat(value.basket_value),
                            });

                            basketSize.push({
                                'date': date,
                                'value': parseFloat(value.basket_size),
                            });

                        })

                        this.items.revenue.data = revenue;
                        this.items.sales_count.data = salesCount;
                        this.items.gross_profit.data = grossProfit;
                        this.items.discount_percentage.data = discount;
                        this.items.basket_value.data = basketValue;
                        this.items.basket_size.data = basketSize;

                        this.items.revenue.total = this.currency + " " + this.calculateTotal(this.items.revenue.data).toFixed(2);
                        this.items.sales_count.total = this.calculateTotal(this.items.sales_count.data);
                        this.items.gross_profit.total = this.currency + " " + this.calculateTotal(this.items.gross_profit.data).toFixed(2);
                        this.items.discount_percentage.total = this.calculateTotal(this.items.discount_percentage.data);
                        this.items.basket_value.total = this.currency + " " + this.calculateTotal(this.items.basket_value.data).toFixed(2);
                        this.items.basket_size.total = this.calculateTotal(this.items.basket_size.data).toFixed(2);
                    }

                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieve(form_fitler = true) {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                if(form_fitler) {
                    notify('top', 'Info', 'Generating data ..', 'center', 'info');
                }
                this.getReport();
                let formData = {
                    start_date: moment(this.date_range.start_date).format('DD-MM-YYYY 00:00:00'),
                    end_date: moment(this.date_range.end_date).format('DD-MM-YYYY 00:00:00'),
                };

                this.custom = this.options.single;
                // If is period then end date should be same with start_date
                if (this.custom) {
                    formData.end_date = moment(this.date_range.start_date).format('DD-MM-YYYY 00:00:00');
                }
                axios.get('/web/report/products', {params: formData}).then((response) => {
                    this.retrieving = false;

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if(form_fitler) {
                            notify('top', 'Success', 'Finished generating data', 'center', 'success');
                        }
                        this.products = data.response;
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
            changeMode: function () {
                this.options.single = !this.options.single;
            },
            calculateTotal(items) {
                return items.reduce((total, item) => {
                    return total + parseFloat(item.value);
                }, 0);
            },
        },
        mounted() {
            let el = $(".daterangepicker-field");
            el.daterangepicker(
                this.options, (start_date, end_date, period) => {
                    if (this.custom) {
                        var title = start_date.format('DD/MM/YYYY');
                    } else {
                        var title = start_date.format('DD/MM/YYYY') + ' – ' + end_date.format('DD/MM/YYYY');
                    }
                    el.val(title);

                    this.date_range = {
                        start_date: start_date,
                        end_date: end_date
                    };

                    this.type = period;
                }
            );
            this.retrieve(false);
        }
    }
</script>

