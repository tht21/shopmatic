<template>
    <div>
        <b-row>
            <template v-for="status in statuses">
                <b-col>
                    <b-card :class="status.color">
                        <div class="row">
                            <div class="col">
                                <h5 class="card-title text-uppercase text-muted mb-0 text-white">TOTAL {{ status.label }}</h5>
                                <span class="h2 font-weight-bold mb-0 text-white">{{ (total_count[status.key]) ? total_count[status.key] : 0 }}</span>
                            </div>
                            <div class="col-auto">
                                <div class="icon icon-shape bg-white text-dark rounded-circle shadow">
                                    <i :class="status.icon"></i>
                                </div>
                            </div>
                        </div>
                    </b-card>
                </b-col>
            </template>
        </b-row>

        <div class="row">
            <div class="col-md-12 col-lg-12 py-3 pr-4">
                <order-bulk-table-component :statuses="statuses" :total_count.sync="total_count"></order-bulk-table-component>
            </div>
        </div>
    </div>
</template>

<script>
    import OrderBulkTableComponent from "./OrderBulkTableComponent";
    export default {
        name: "OrderBulkIndexComponent",
        components: {OrderBulkTableComponent},
        data() {
            return {
                statuses: [
                    {key: 'pending', label: 'Pending', color: 'bg-gradient-cyan', icon: 'ni ni-time-alarm'},
                    {key: 'processing', label: 'Processing', color: 'bg-gradient-blue', icon: 'ni ni-app'},
                    {key: 'ready_to_ship', label: 'Ready To Ship', color: 'bg-gradient-info', icon: 'ni ni-active-40'},
                ],
                total_count: {}
            }
        },
    }
</script>

<style scoped>

</style>
