<template>
    <div>
        <b-card no-body>
            <b-card-header class="border-0">
                <h3 class="mb-0">Sales Report
                    <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button><button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                </h3>
            </b-card-header>
            <div id="filter" class="collapse">
                <div class="p-3" style="background: #f6f6f6;">
                    <b-row class="mt-2">
                        <b-col md="6" >
                            <label for="date-range" class="text-muted text-uppercase ml-auto">Date</label>
                            <input id="date-range" class="daterangepicker-field form-control">
                            <label class="custom-toggle ml-2 mt-2">
                                <input type="checkbox" @change="changeMode">
                                <span class="custom-toggle-slider rounded-circle"></span>
                            </label>
                            <label class="text-muted text-uppercase ml-auto mb-2">{{btnCustom}}</label>
                        </b-col>
                        <b-col md="6">
                            <label for="type" class="text-muted text-uppercase ml-auto">Report Type</label>
                            <vue-multiselect id="type" v-model="valueType" :options="reportType" :show-labels="false"
                                             placeholder="Select Report Type" @select="filterByType"></vue-multiselect>
                        </b-col>
                    </b-row>
                </div>
            </div>
            <div id="index-table" class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light text-center">
                    <tr>
                        <th colspan="3" class="border">{{ year }}</th>
                        <th colspan="5" class="border">Totals By Summary</th>
                    </tr>
                    <tr>
                        <th class="border" colspan="2" v-if="valueType">{{ valueType }}</th>
                        <th class="border" colspan="2" v-else>Sales Summary</th>
                        <th>{{ date }}</th>
                        <th>Revenue</th>
                        <th>Cost of Goods</th>
                        <th>Gross Profit</th>
                        <th>Margin</th>
                        <th>Tax</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td colspan="2"><strong>Totals</strong></td>
                        <td><strong>$ {{ calculateTotals | formatCurrency }}</strong></td>
                        <td class="border"><strong>$ {{ calculateRevenue | formatCurrency }}</strong></td>
                        <td class="border"><strong>$ {{ calculateCostOfGoods | formatCurrency }}</strong>
                        </td>
                        <td class="border"><strong>$ {{ calculateGrossProfit | formatCurrency }}</strong>
                        </td>
                        <td class="border"><strong>{{ calculateMargin }} %</strong></td>
                        <td class="border"><strong>$ {{ calculateTax | formatCurrency }}</strong></td>
                    </tr>
                    <tr v-if="groups" v-for="(group, index) in groups">
                        <td colspan="2">{{ index }}</td>
                        <td>$ {{ calculateTotalsByType(index) | formatCurrency }}</td>
                        <td>$ {{ calculateRevenueByType[index] | formatCurrency }}</td>
                        <td>$ {{ calculateCostOfGoodsByType[index] | formatCurrency }}</td>
                        <td>$ {{ calculateGrossProfitByType[index] | formatCurrency }}</td>
                        <td>{{ calculateMarginsByType(index) }} %</td>
                        <td>$ {{ calculateTaxByType[index] | formatCurrency }}</td>
                    </tr>
                    <tr>
                        <td rowspan="5">
                            <div>Total By Date Range</div>
                        </td>
                        <td>Revenue</td>
                        <td>$ {{ calculateRevenue | formatCurrency }}</td>
                    </tr>
                    <tr>
                        <td>Cost of Goods</td>
                        <td>{{ calculateCostOfGoods }}</td>
                    </tr>
                    <tr>
                        <td>Gross Profit</td>
                        <td>$ {{ calculateGrossProfit | formatCurrency }}</td>
                    </tr>
                    <tr>
                        <td>Margin</td>
                        <td>{{ calculateMargin }} %</td>
                    </tr>
                    <tr>
                        <td>Tax</td>
                        <td>$ {{ calculateTax | formatCurrency }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </b-card>
    </div>
</template>

<script>

    import Multiselect from 'vue-multiselect';

    export default {
        name: 'IndexSalesComponent',
        props: ['global'],
        components: {
            'vue-multiselect': Multiselect,
        },
        filters: {
            formatCurrency: function (value) {
                if (!value) return 0;
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
                    var title = this.dateRange.startDate.format('DD/MM/YYYY');
                } else {
                    var title = this.dateRange.startDate.format('DD/MM/YYYY') + ' – ' + this.dateRange.endDate.format('DD/MM/YYYY');
                }
                el.val(title);
            },
        },
        computed: {
            calculateRevenue: function () {
                let sum = 0;

                $.each(this.revenue, function () {
                    sum += parseFloat(this) || 0;
                });
                this.total_revenue = sum;

                return this.total_revenue;
            },
            calculateCostOfGoods: function () {
                let sum = 0;

                $.each(this.costOfGoods, function () {
                    sum += parseFloat(this) || 0;
                });
                this.cost_of_goods = sum;

                return this.cost_of_goods;
            },
            calculateGrossProfit: function () {
                let sum = 0;

                $.each(this.grossProfit, function () {
                    sum += parseFloat(this) || 0;
                });
                this.gross_profit = sum;

                return this.gross_profit;
            },
            calculateMargin: function () {
                let margin = (this.calculateGrossProfit / this.calculateRevenue * 100).toFixed(2);
                if (!margin || margin === 'NaN') return 0;

                return margin;
            },
            calculateTax: function () {
                let sum = 0;

                $.each(this.totalTax, function () {
                    sum += parseFloat(this) || 0;
                });
                this.tax = sum;

                return this.tax;
            },
            calculateTotals: function () {
                return this.calculateRevenue + this.calculateGrossProfit + this.calculateCostOfGoods + this.calculateTax;
            },
            calculateRevenueByType: function () {
                let arr = [];

                $.each(this.groups, function (key, data) {
                    $.each(data, function () {
                        arr[key] = data.reduce((sum, total) => {
                            return sum + parseFloat(total.total_revenue);
                        }, 0)
                    });
                });

                return arr;
            },
            calculateCostOfGoodsByType: function () {
                let arr = [];

                $.each(this.groups, function (key, data) {
                    $.each(data, function () {
                        arr[key] = data.reduce((sum, total) => {
                            return sum + parseFloat(total.cost_of_goods);
                        }, 0)
                    });
                });

                return arr;
            },
            calculateGrossProfitByType: function () {
                let arr = [];

                $.each(this.groups, function (key, data) {
                    $.each(data, function () {
                        arr[key] = data.reduce((sum, total) => {
                            return sum + parseFloat(total.gross_profit);
                        }, 0)
                    });
                });

                return arr;
            },
            calculateTaxByType: function () {
                let arr = [];

                $.each(this.groups, function (key, data) {
                    $.each(data, function () {
                        arr[key] = data.reduce((sum, total) => {
                            return sum + parseFloat(total.tax);
                        }, 0)
                    });
                });

                return arr;
            },
        },
        props: ['integrations'],
        data() {
            return {
                valueType: '',
                valueMeasure: '',
                reportType: ['Sales Summary', 'Integration', 'Account'],
                sending_request: false,
                dateRange: {
                    startDate: moment().startOf('day').format('Do MMMM YYYY'),
                    endDate: moment().endOf('day').format('Do MMMM YYYY')
                },
                search: '',
                toggleFilter: false,
                btnFilter: 'Filters',
                opens: 'left',
                formData: {
                    type: "",
                    measure: "",
                    filter: "",
                    period: "",
                    group: ""
                },
                btnCustom: "Custom Range",
                options: {
                    single: false,
                    periods: ['day', 'week', 'month', 'year'],
                    forceUpdate: true,
                    orientation: 'left',
                    ranges: {
                        'Custom Range': 'custom'
                    },
                    startDate: moment(),
                    endDate: moment()
                },
                format: 'DD-MM-YYYY',
                period: "",
                custom: false,
                total_revenue: 0,
                sales_count: 0,
                gross_profit: 0,
                discount_value: 0,
                discount_percentage: 0,
                basket_value: 0,
                basket_size: 0,
                cost_of_goods: 0,
                tax: 0,
                revenue: {},
                salesCount: {},
                grossProfit: {},
                discount: {},
                basketValue: {},
                basketSize: {},
                costOfGoods: {},
                totalTax: {},
                year: moment().format("Y"),
                date: moment().format("DD MMM"),
                groups: {},
                totalsByType: [],
                marginsByType: []
            }
        },
        methods: {
            retrieve(form_fitler = true) {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                if(form_fitler) {
                    notify('top', 'Info', 'Generating data ..', 'center', 'info');
                }

                let period = this.period;
                if (period === "day") {
                    this.date = this.dateRange.startDate.format("DD MMM");
                } else if (period === "week") {
                    this.date = "Week " + this.dateRange.startDate.format("W");
                } else if (period === "month") {
                    this.date = this.dateRange.startDate.format("MMM");
                } else if (period === "year") {
                    this.date = this.dateRange.startDate.format("YYYY");
                }

                this.year = this.dateRange.startDate.format("YYYY");

                this.formData = {
                    period: period,
                    startDate: this.dateRange.startDate,
                    endDate: this.dateRange.endDate,
                    type: this.valueType
                };

                // If is period then end date should be same with start_date
                if (this.options.single) {
                    this.formData.endDate = moment(this.dateRange.startDate).format('DD-MM-YYYY 23:59:59');
                }
                axios.post('/web/report/sales', this.formData).then((response) => {
                    this.sending_request = false;

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if(form_fitler) {
                            notify('top', 'Success', 'Finished generating data', 'center', 'success');
                        }
                        let revenue = [];
                        let salesCount = [];
                        let grossProfit = [];
                        let discount = [];
                        let basketValue = [];
                        let basketSize = [];
                        let costOfGoods = [];
                        let totalTax = [];

                        $.each(data.response.report, function (index, value) {
                            revenue.push(!value ? 0 : value.total_revenue);
                            salesCount.push(!value ? 0 : value.total_orders);
                            grossProfit.push(!value ? 0 : value.gross_profit);
                            discount.push(!value ? 0 : value.total_discount);
                            basketValue.push(!value ? 0 : value.basket_value);
                            basketSize.push(!value ? 0 : value.basket_size);
                            costOfGoods.push(!value ? 0 : value.cost_of_goods);
                            totalTax.push(!value ? 0 : value.tax);
                            this.currency = value.currency;
                        });

                        this.revenue = revenue;
                        this.salesCount = salesCount;
                        this.grossProfit = grossProfit;
                        this.discount = discount;
                        this.basketValue = basketValue;
                        this.basketSize = basketSize;
                        this.costOfGoods = costOfGoods;
                        this.totalTax = totalTax;
                        this.groups = data.response.group;
                        this.updateGlobal();
                    }
                })
                    .catch((error) => {
                        this.sending_request = false;
                        if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }
                    });
            },
            openFilter() {
                this.toggleFilter = !this.toggleFilter;

                if (!this.toggleFilter) {
                    this.valueType = '';
                    this.retrieve();
                }

                this.btnFilter = this.toggleFilter ? 'Remove Filters' : 'Filters';
            },
            changeMode() {
                this.options.single = !this.options.single;
            },
            filterByType(selectedItems) {
                this.valueType = selectedItems;
                this.retrieve();
            },
            calculateMarginsByType(type) {
                this.marginsByType[type] = (this.calculateGrossProfitByType[type] / this.calculateRevenueByType[type] * 100).toFixed(2);
                return this.marginsByType[type];
            },
            calculateTotalsByType(type) {
                this.totalsByType[type] = this.calculateRevenueByType[type] + this.calculateGrossProfitByType[type] + this.calculateCostOfGoodsByType[type] + this.calculateTaxByType[type];
                return this.totalsByType[type];
            },
            updateGlobal() {
                this.$emit('update', {
                    year: this.year,
                    date: this.date,
                    totals: this.calculateTotals,
                    revenue: this.calculateRevenue,
                    cost_of_goods: this.calculateCostOfGoods,
                    gross_profit: this.calculateGrossProfit,
                    margin: this.calculateMargin,
                    tax: this.calculateTax,
                    groups: this.groups,
                })
            }
        },
        mounted() {
            let el = $(".daterangepicker-field");
            el.daterangepicker(
                this.options, (startDate, endDate, period) => {
                    if (this.custom) {
                        var title = startDate.format('DD/MM/YYYY');
                    } else {
                        var title = startDate.format('DD/MM/YYYY') + ' – ' + endDate.format('DD/MM/YYYY');
                    }

                    el.val(title);

                    this.dateRange = {
                        startDate: startDate,
                        endDate: endDate
                    };

                    if (!period) {
                        period = 'day'
                    }
                    ;

                    this.period = period;
                    this.retrieve(false);
                }
            );
        }
    }
</script>
