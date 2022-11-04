<template>
    <div>
<!--        <div class="row">-->
<!--            <div class="col-12">-->
<!--                <div class="card bg-default">-->
<!--                    <div class="card-header bg-transparent">-->
<!--                        <div class="row align-items-center">-->
<!--                            <div class="col">-->
<!--                                <h6 class="text-light text-uppercase ls-1 mb-1">Overview</h6>-->
<!--                                <h5 class="h3 text-white mb-0">Total Sales</h5>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <div class="card-body">-->
<!--                        &lt;!&ndash; Chart &ndash;&gt;-->
<!--                        <div class="chart">-->
<!--                            &lt;!&ndash; Chart wrapper &ndash;&gt;-->
<!--                            <canvas id="chart-sales" class="chart-canvas"></canvas>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
        <div class="row">
<!--            <div class="col-md-6 d-flex">-->
<!--                &lt;!&ndash; Members list group card &ndash;&gt;-->
<!--                <div class="card flex-grow-1">-->
<!--                    &lt;!&ndash; Card header &ndash;&gt;-->
<!--                    <div class="card-header bg-gradient-info mb-3">-->
<!--                        &lt;!&ndash; Title &ndash;&gt;-->
<!--                        <h6 class="text-uppercase text-light ls-1 mb-1">PENDING</h6>-->
<!--                        <h5 class="h3 mb-0 text-white">Unanswered Enquiries</h5>-->
<!--                    </div>-->
<!--                    &lt;!&ndash; Card body &ndash;&gt;-->
<!--                    <div class="card-body p-0">-->
<!--                        &lt;!&ndash; List group &ndash;&gt;-->
<!--                        <ul class="list-group list-group-flush list my&#45;&#45;3">-->
<!--                            <li class="list-group-item p-3">-->
<!--                                <div class="row align-items-center">-->
<!--                                    <div class="col-auto">-->
<!--                                        &lt;!&ndash; Avatar &ndash;&gt;-->
<!--                                        <a href="#" class="avatar rounded-circle">-->
<!--                                            <img alt="Image placeholder" src="/images/user.png">-->
<!--                                        </a>-->
<!--                                    </div>-->
<!--                                    <div class="col ml&#45;&#45;2">-->
<!--                                        <h4 class="mb-0">-->
<!--                                            <a href="#!">John Michael</a>-->
<!--                                        </h4>-->
<!--                                        <small>Are you able to help me ship the item by today?</small>-->
<!--                                    </div>-->
<!--                                </div>-->
<!--                            </li>-->
<!--                            <li class="list-group-item p-3">-->
<!--                                <div class="row align-items-center">-->
<!--                                    <div class="col-auto">-->
<!--                                        &lt;!&ndash; Avatar &ndash;&gt;-->
<!--                                        <a href="#" class="avatar rounded-circle">-->
<!--                                            <img alt="Image placeholder" src="/images/user.png">-->
<!--                                        </a>-->
<!--                                    </div>-->
<!--                                    <div class="col ml&#45;&#45;2">-->
<!--                                        <h4 class="mb-0">-->
<!--                                            <a href="#!">John Michael</a>-->
<!--                                        </h4>-->
<!--                                        <small>Are you able to help me ship the item by today?</small>-->
<!--                                    </div>-->
<!--                                </div>-->
<!--                            </li>-->
<!--                        </ul>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
            <div class="col-md-6 d-flex">
                <div class="card flex-grow-1">
                    <div class="card-header bg-gradient-blue mb-3">
                        <h6 class="text-uppercase text-light ls-1 mb-1">PENDING</h6>
                        <h5 class="h3 mb-0 text-white">Recent Orders</h5>
                    </div>
                    <div class="card-body text-center p-0">
                        <img src="/images/loading.gif" width="40" height="40" class="p-3" v-show="retrieving_orders" />

                        <ul class="list-group list-group-flush list my--3 text-left">
                            <li  v-for="(order, index) in orders" class="list-group-item p-3">
                                <template v-if="index <= 2">
                                    <span  :ref="'dashboard-order-'+order.id">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <h4 class="mb-0">
                                                    <a :href="'/dashboard/orders/' + order.id" target="_blank">{{ order.external_id ? order.external_id : order.id }}</a>
                                                    </h4>
                                                    <small><strong>Items: </strong><span v-for="(item, index) in order.items">{{ item.name }} x {{ item.quantity }}<span v-if="index < order.items.length - 1">, </span></span></small>
                                                </div>
                                                <div class="col-auto" v-if="order.external_source">
                                                    <span class="badge badge-info">{{ order.external_source }}</span>
                                                </div>
                                            </div>
                                            <div class="small-actions mt-3">
                                                <template v-if="order.account && order.account.status === 0">
                                                    <component v-bind:is="orderActionComponent(order)" :order="order"></component>
                                                </template>
                                            </div>
                                    </span>
                                </template>
                                <template v-else>
                                    <div class="text-center p-2">
                                        There are more pending orders! <a href="/dashboard/orders" target="_blank">View All</a>
                                    </div>
                                </template>
                            </li>
                        </ul>
                        <div v-show="!retrieving_orders && orders.length === 0" class="text-center p-3">
                            <img src="/images/confetti.svg" width="70px" class="my-3 d-block mx-auto" /><br />
                            You have fulfilled all your orders!
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 d-flex">
                <div class="card flex-grow-1">
                    <div class="card-header bg-transparent">
                        <h6 class="text-uppercase text-muted ls-1 mb-1">CURRENT</h6>
                        <h5 class="h3 mb-0">Inventory Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="/images/loading.gif" width="40" height="40" v-show="retrieving_inventory" />
                        
                        <canvas id="inventory-status" width="100%" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6 d-flex">
                <div class="card flex-grow-1">
                    <div class="card-header bg-transparent">
                        <h3 class="mb-0">Product Alerts</h3>
                    </div>
                    <div class="card-body text-center">
                        <img src="/images/loading.gif" width="40" height="40" v-show="retrieving_alerts" />
                        <div v-show="!retrieving_alerts && alerts.length === 0" class="text-center">
                            You have no new product alerts!
                        </div>
                        <div v-show="alerts.length > 0" class="timeline timeline-one-side" data-timeline-content="axis" data-timeline-axis-style="dashed">
                            <div class="timeline-block" v-for="alert in alerts">
                                <span class="timeline-step badge-info">
                                    <i :class="'fa ' + alert.icon"></i>
                                </span>
                                <div class="timeline-content">
                                    <small class="text-muted font-weight-bold">{{ alert.created_at }}</small>
                                    <p class=" text-sm mt-1 mb-0">{{ alert.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 d-flex">
                <div class="card flex-grow-1">
                    <div class="card-header bg-transparent">
                        <h3 class="mb-0">Inventory Changes</h3>
                    </div>
                    <div class="card-body text-center">
                        <img src="/images/loading.gif" width="40" height="40" v-show="retrieving_logs" />
                        <div v-show="!retrieving_logs && logs.length === 0" class="text-center">
                            You have no recent inventory changes!
                        </div>
                        <div class="timeline timeline-one-side" data-timeline-content="axis" data-timeline-axis-style="dashed">
                            <div class="timeline-block" v-for="log in logs">
                                <span class="timeline-step badge-info">
                                    <i class="ni ni-bell-55"></i>
                                </span>
                                <div class="timeline-content">
                                    <small class="text-muted font-weight-bold">{{ log.created_at }}</small>
                                    <p class=" text-sm mt-1 mb-0">{{ log.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
        </div>
    </div>
</template>
<script>
    export default {
        name: "DashboardIndexComponent",
        data() {
            return {
                data: [],
                search: '',
                type: '',
                request_url: '/web/products/alerts',
                retrieving: false,
                retrieving_orders: false,
                retrieving_alerts: false,
                retrieving_logs: false,
                retrieving_inventory: false,
                orders: [],
                alerts: [],
                logs: [],
                total_sales: []
            }
        },
        methods: {
            retrieve: function() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    search: this.search,
                    with: 'product',
                };
                axios.get(this.request_url, {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {

                    }
                    ctx.retrieving = false;
                }).catch(function (error) {
                    ctx.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            updateCurrent: function(orderId) {
                let indx = this.orders.findIndex(order => order.id === orderId);
                this.$set(this.orders[indx],'action_performed',true);
                let selector = 'dashboard-order-' + orderId;
                this.$refs[selector][0].parentElement.classList.add("d-none");
            },
            retrieveOrders: function() {
                if (this.retrieving_orders) {
                    return;
                }
                this.retrieving_orders = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    fulfillment_status: 'pending,processing,ready_to_ship',
                    payment_status: 'paid',
                    page: 1,
                    limit: 4,
                    with: 'items,account.integration',
                };
                axios.get('/web/orders', {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.orders = data.response.items;
                    }
                    ctx.retrieving_orders = false;
                }).catch(function (error) {
                    ctx.retrieving_orders = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveAlerts: function() {
                if (this.retrieving_alerts) {
                    return;
                }
                this.retrieving_alerts = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    page: 1,
                    dismissed: 0,
                    limit: 5,
                    with: 'product',
                };
                axios.get('/web/products/alerts', {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.alerts = data.response.items;
                    }
                    ctx.retrieving_alerts = false;
                }).catch(function (error) {
                    ctx.retrieving_alerts = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveInventory: function() {
                if (this.retrieving_inventory) {
                    return;
                }
                this.retrieving_inventory = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    page: 1,
                    dismissed: 0,
                    limit: 5,
                    with: 'product',
                };
                axios.get('/web/inventory/status', {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        data = data.response;
                        var $chart = $('#inventory-status');
                        var inventoryChart = new Chart($chart, {
                            type: 'doughnut',
                            data: {
                                labels: ["In Stock", "Low Stock", "Out of Stock"],
                                datasets: [
                                    {
                                        label: 'In Stock',
                                        data: [data.in_stock, data.low_stock, data.out_of_stock],
                                        backgroundColor:["rgb(54, 162, 235)","rgb(255, 205, 86)","rgb(255, 99, 132)"]
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                legend: {
                                    position: 'top',
                                },
                            }
                        });

                        $chart.data('chart', inventoryChart);
                    }
                    ctx.retrieving_inventory = false;
                }).catch(function (error) {
                    ctx.retrieving_alerts = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveLogs: function() {
                if (this.retrieving_logs) {
                    return;
                }
                this.retrieving_logs = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    page: 1,
                    limit: 5,
                    with: 'product',
                };
                axios.get('/web/inventory/logs', {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.logs = data.response.items;
                    }
                    ctx.retrieving_logs = false;
                }).catch(function (error) {
                    ctx.retrieving_logs = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveTotalSales: function() {
                let ctx = this;

                axios.get('/web/report/retails', { params: {dashboard: 'dashboard'}}).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.total_sales = data.response;

                        let monthly_data = ctx.total_sales.monthly_data
                        let monthly_sales = [];
                        for (let i in monthly_data) {
                            monthly_sales.push(monthly_data[i].total_revenue)
                        }
                        var $chart = $('#chart-sales');
                        var salesChart = new Chart($chart, {
                            type: 'line',
                            options: {
                                scales: {
                                    yAxes: [{
                                        gridLines: {
                                            color: Charts.colors.gray[700],
                                            zeroLineColor: Charts.colors.gray[700]
                                        },
                                        ticks: {

                                        }
                                    }]
                                },
                                legend: {
                                    display: false,
                                },
                            },
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                datasets: [{
                                    label: 'Performance',
                                    data: monthly_sales
                                }]
                            },
                        });

                        // Save to jQuery object

                        $chart.data('chart', salesChart);
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            orderActionComponent: function(order) {
                let name = null;
                if (order.external_source && order.account_id) {
                    name = order.external_source + 'OrderActionComponent';
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

        created() {
            this.retrieve();
            this.retrieveOrders();
            this.retrieveAlerts();
            this.retrieveLogs();
            this.retrieveInventory();
            // this.retrieveTotalSales();
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>
