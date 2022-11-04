<template>
    <div class="col-md-4">
        <div v-show="custom" class="card card-stats mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">Sales Count</h5>
                        <span class="h2 font-weight-bold mb-0">{{ calculateSalesCount }}</span>
                    </div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                    </div>
                </div>
                <div class="progress-wrapper" v-for="(group, index) in groups">
                    <div class="progress-info">
                        <div class="progress-label">
                            <span class="bg-success text-white">{{ index }}</span>
                        </div>
                        <div class="progress-percentage">
                            <span>{{ currency }} {{ calculateByIntegration[index] }}</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-show="!custom" class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">Sales Count</h5>
                        <span class="h1 font-weight-bold mb-0">{{ calculateSalesCount }}</span>
                    </div>
                </div>
                <div class="h4 text-green"><i class="fas fa-arrow-alt-circle-up"></i> {{ percentage }} % Previous {{ type | capitalize }}</div>
                <div class="progress-wrapper" v-for="(group, index) in groups">
                    <div class="progress-info">
                        <div class="progress-label">
                            <span class="bg-success text-white">{{ index }}</span>
                        </div>
                        <div class="progress-percentage">
                            <span>{{ calculateByIntegration[index] }}</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;"></div>
                    </div>
                </div>
                <div class="chart">
                    <canvas id="chart-sales-count" class="chart-canvas" ref="canvas">

                    </canvas>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { Line } from 'vue-chartjs';

    export default {
        name: "RevenueComponent",
        extends: Line,
        props: ['labels', 'salesCount', 'currency', 'custom', 'groups', 'type'],
        filters: {
            capitalize: function (str)
            {
                let strVal = '';
                str = str.split(' ');
                for (var chr = 0; chr < str.length; chr++) {
                    strVal += str[chr].substring(0, 1).toUpperCase() + str[chr].substring(1, str[chr].length) + ' '
                }
                return strVal;
            }
        },
        computed: {
            calculateByIntegration: function ()
            {
                let arr = [];

                $.each(this.groups, function(key, data) {
                    $.each(data, function() {
                        arr[key] = data.reduce((sum, total) => {
                            return sum + parseFloat(total.total_orders);
                        }, 0)
                    });
                });

                return arr;
            },
            calculateSalesCount: function()
            {
                let sum = 0;

                $.each(this.salesCount, function()
                {
                    sum += this || 0;
                });
                this.sales_count = sum;

                return this.sales_count;
            }
        },
        watch: {
            salesCount () {
                this.data.datasets[0].data = this.salesCount;
                this.$data._chart.update();

                this.calculatePercentage();
            },
            labels()
            {
                this.data.labels = this.labels;
                this.$data._chart.update();
            },
        },
        data() {
            return {
                sales_count: 0,
                data : {
                    labels: this.labels,
                    datasets: [
                        {
                            fill: true,
                            lineTension: 0.1,
                            backgroundColor: "rgba(75,192,192,0.4)",
                            borderColor: "rgba(75,192,192,1)",
                            borderCapStyle: 'butt',
                            borderDash: [],
                            borderDashOffset: 0.0,
                            borderJoinStyle: 'miter',
                            pointBorderColor: "rgba(75,192,192,1)",
                            pointBackgroundColor: "#fff",
                            pointBorderWidth: 1,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: "rgba(75,192,192,1)",
                            pointHoverBorderColor: "rgba(220,220,220,1)",
                            pointHoverBorderWidth: 2,
                            pointRadius: 5,
                            pointHitRadius: 10,
                            data: this.salesCount,
                        }
                    ]
                },
                option : {
                    showLines: true
                },
                percentage: 0
            }
        },
        methods: {
            calculatePercentage: function()
            {
                let salesCount = this.salesCount;

                let percentage = ((salesCount[0] - salesCount[1]) / salesCount[1]) * 100;
                this.percentage = percentage ? percentage.toFixed(2) : 0;
            },
        },
        mounted() {
            this.renderChart(this.data, this.option);
        }
    }
</script>
