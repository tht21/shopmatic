<template>
    <div class="col-md-12">
        <div v-show="custom" class="card card-stats mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">{{item.name}}</h5>
                        <span class="h2 font-weight-bold mb-0">{{item.total}}</span>
                    </div>
                    <div class="col-auto">
                        <div :class="'icon icon-shape ' + item.color + ' text-white rounded-circle shadow'">
                            <i :class="item.icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-show="!custom" class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">{{item.name}}</h5>
                        <span class="h1 font-weight-bold mb-0">{{item.total}}</span>
                    </div>
                </div>
                <div class="h4 text-green"><i class="fas fa-arrow-alt-circle-up"></i> {{ percentage }} % Previous {{ type | capitalize }}</div>
                <div class="chart">
                    <canvas id="chart-orders" class="chart-canvas" ref="canvas">
                    </canvas>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { Line } from 'vue-chartjs';

    export default {
        name: "RetailDetailsComponent",
        extends: Line,
        props: ['item', 'start_date', 'end_date', 'lists', 'custom', 'type'],
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
        watch: {
            lists() {
                this.data.labels = [];
                this.data.datasets[0].data = [];
                this.$data._chart.update();
                this.calculatePercentage();
                this.updateChart();
            },
        },
        data() {
            return {
                data : {
                    labels: [],
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
                            data: [],
                        }
                    ]
                },
                option : {
                    showLines: true,
                    responsive: true,
                    legend: {
                        display: false
                    }
                },
                percentage: 0,
            }
        },
        methods: {
            calculatePercentage() {
                let lists = this.lists;
                if(lists.length > 1) {
                    let percentage = ((lists[0].value - lists[1].value) / lists[1].value) * 100;
                    this.percentage = isFinite(percentage) ? percentage ? percentage.toFixed(2) : 0 : 0;
                } else {
                    this.percentage = 0;
                }
            },
            updateChart() {

                let lists = this.lists;

                if(lists.length <= 0) {
                    return
                }


                switch (this.type) {
                    case 'day':
                        this.getDataByDay()
                        break;
                    case 'week':
                        this.getDataByWeek()
                        break;
                    case 'month':
                        this.getDataByMonth()
                        break;
                    case 'year':
                        this.getDataByYear()
                        break;
                }

                this.$data._chart.update();
            },
            getDataByDay() {

                let lists = this.lists;

                let data = lists.map((d) => {
                    return d.value
                });
                let labels = lists.map((d) => {
                    return moment(d.date.month + "/" + d.date.day + "/" + d.date.year, 'MM/DD/YYYY').format('dd D')
                });

                let list = lists[0];
                let date =  moment(list.date.month + "/" + list.date.day + "/" + list.date.year, 'MM/DD/YYYY');

                if(!this.isSameDay(this.start_date, date)) {
                    data.unshift(0)
                    labels.unshift(this.start_date.format('dd D'))
                }

                list = lists[lists.length - 1];
                date =  moment(list.date.month + "/" + list.date.day + "/" + list.date.year, 'MM/DD/YYYY');

                if(!this.isSameDay(this.end_date, date)) {
                    data.push(0)
                    labels.push(this.end_date.format('dd D'))
                }

                this.data.labels = labels;
                this.data.datasets[0].data = data;
            },
            getDataByWeek() {

                let lists = this.lists;

                let data = lists.map((d) => {
                    return d.value
                });
                let labels = lists.map((d) => {
                    return d.date.year + '(' + d.date.week + ')';
                });

                let list = lists[0];
                console.log(this.start_date.format('YYYY'))
                if(this.start_date.format('w') != list.date.week || this.start_date.format('YYYY') != list.date.year) {
                    data.unshift(0)
                    labels.unshift(this.start_date.format('YYYY(w)'))
                }

                list = lists[lists.length - 1];
                if(this.end_date.format('w') != list.date.week || this.end_date.format('YYYY') != list.date.year) {
                    data.push(0)
                    labels.push(this.end_date.format('YYYY(w)'))
                }

                this.data.labels = labels;
                this.data.datasets[0].data = data;
            },
            getDataByMonth() {

                let lists = this.lists;

                let data = lists.map((d) => {
                    return d.value
                });
                let labels = lists.map((d) => {
                    return moment(d.date.month + "/" + d.date.day + "/" + d.date.year, 'MM/DD/YYYY').format('MMM-YYYY')
                });

                let list = lists[0];

                if(this.start_date.format('M') != list.date.month || this.start_date.format('YYYY') != list.date.year) {
                    data.unshift(0)
                    labels.unshift(this.start_date.format('MMM-YYYY'))
                }

                list = lists[lists.length - 1];
                if(this.end_date.format('M') != list.date.month || this.end_date.format('YYYY') != list.date.year) {
                    data.push(0)
                    labels.push(this.end_date.format('MMM-YYYY'))
                }

                this.data.labels = labels;
                this.data.datasets[0].data = data;
            },
            getDataByYear() {

                let lists = this.lists;

                let data = lists.map((d) => {
                    return d.value
                });
                let labels = lists.map((d) => {
                    return moment(d.date.month + "/" + d.date.day + "/" + d.date.year, 'MM/DD/YYYY').format('YYYY')
                });

                this.data.labels = labels;
                this.data.datasets[0].data = data;
            },
            isSameDay(date_1, date_2, format = 'MM/D/YYYY') {
                return date_1.format(format) === date_2.format(format);
            },
        },
        mounted() {
            this.renderChart(this.data, this.option);
        }
    }
</script>
