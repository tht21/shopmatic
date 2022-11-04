<template>
    <div class="col-md-4">
        <div v-show="custom" class="card card-stats mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">Gross Profit</h5>
                        <span class="h2 font-weight-bold mb-0">{{ currency }} {{ calculateGrossProfit | currencyFormat }}</span>
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
                            <span>{{ currency }} {{ calculateByIntegration[index] | currencyFormat }}</span>
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
                        <h5 class="card-title text-uppercase text-muted mb-0">Gross Profit</h5>
                        <span class="h1 font-weight-bold mb-0">{{ currency }} {{ calculateGrossProfit | currencyFormat }}</span>
                    </div>
                </div>
                <div class="h4 text-green"><i class="fas fa-arrow-alt-circle-up"></i> {{ percentage }} % Previous {{ type | capitalize}} </div>
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
                    <canvas id="chart-gross-profit" class="chart-canvas" ref="canvas">

                    </canvas>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { Line } from 'vue-chartjs';

    export default {
        name: "GrossProfitComponent",
        extends: Line,
        props: ['labels', 'currency', 'grossProfit', 'custom', 'groups', 'type'],
        filters: {
            currencyFormat: function(value)
            {
                if(!value) return '0';
                return value.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
            },
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
                            return sum + parseFloat(total.gross_profit);
                        }, 0)
                    });
                });

                return arr;
            },
            calculateGrossProfit: function ()
            {
                let sum = 0;

                $.each(this.grossProfit, function()
                {
                    sum += parseFloat(this) || 0;
                });

                this.gross_profit = sum;

                return this.gross_profit;
            }
        },
        watch: {
            grossProfit () {
                this.data.datasets[0].data = this.grossProfit;
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
                gross_profit: 0.00,
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
                            data: this.grossProfit,
                        }
                    ]
                },
                option : {
                    showLines: true,
                    responsive: true,
                },
                percentage: 0
            }
        },
        methods: {
            calculatePercentage: function()
            {
                let grossProfit = this.grossProfit;

                let percentage = ((grossProfit[0] - grossProfit[1]) / grossProfit[1]) * 100;
                this.percentage = percentage ? percentage.toFixed(2) : 0;
            },
        },
        mounted() {
            this.renderChart(this.data, this.option);
        }
    }
</script>
