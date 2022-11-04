<template>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">Customer Count</h5>
                        <span class="h1 font-weight-bold mb-0">$20.6K</span>
                    </div>
                </div>
                <div class="h4 text-green"><i class="fas fa-arrow-alt-circle-up"></i> 916% Previous Month</div>
                <div class="progress-wrapper">
                    <div class="progress-info">
                        <div class="progress-label">
                            <span class="bg-info text-white">Queen Outlet</span>
                        </div>
                        <div class="progress-percentage">
                            <span>$7.85K</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 70%;"></div>
                    </div>
                </div>
                <div class="progress-wrapper">
                    <div class="progress-info">
                        <div class="progress-label">
                            <span class="bg-info text-white">Main Outlet</span>
                        </div>
                        <div class="progress-percentage">
                            <span>$11.4K</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 98%;"></div>
                    </div>
                </div>
                <div class="progress-wrapper">
                    <div class="progress-info">
                        <div class="progress-label">
                            <span class="bg-info text-white">Premium Outlet</span>
                        </div>
                        <div class="progress-percentage">
                            <span>$1.35K</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 13.5%;"></div>
                    </div>
                </div>
                <div class="chart">
                    <!-- Chart wrapper -->
                    <canvas id="chart-customer-count" class="chart-canvas">

                    </canvas>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "RevenueComponent",
        data() {
            return {

            }
        },
        methods: {

        },
        mounted() {
            var canvas = document.getElementById('chart-customer-count');
            var data = {
                labels: ["January", "February", "March", "April", "May", "June", "July"],
                datasets: [
                    {
                        label: "My First dataset",
                        fill: false,
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
                        data: [65, 59, 80, 0, 56, 55, 40],
                    }
                ]
            };

            function adddata(){
                myLineChart.data.datasets[0].data[7] = 60;
                myLineChart.data.labels[7] = "Newly Added";
                myLineChart.update();
            }

            var option = {
                showLines: true
            };
            var myLineChart = Chart.Line(canvas,{
                data:data,
                options:option
            });
        }
    }
</script>
