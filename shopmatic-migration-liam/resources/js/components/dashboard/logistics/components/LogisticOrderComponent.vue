<template>
    <div class="card">
        <div class="card-header border-0">
            <h3 class="mb-0">Logistic <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button><button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button></h3>
        </div>
        <div id="filter" class="collapse">
            <div class="p-3" style="background: #f6f6f6;">
                <div class="row">
                    <div class="col-md-6 mt-2">
                        <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                        <input id="search" v-model="search" name="search" class="form-control">
                    </div>
                    <div class="col-12 text-center py-3">
                        <button class="btn btn-primary px-5" @click="retrieve">Filter</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th style="width: 130px;">ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Fulfillment Status</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in data" class="cursor-pointer" @click="selectOrder(item, index)">
                    <td style="width: 130px;">{{ item.external_id ? item.external_id : item.id }}<br /><span v-if="item.external_source" class="badge badge-info">{{ item.external_source }}</span></td>
                    <td style="width: 130px">{{ item.order_placed_at ? item.order_placed_at : item.created_at }}</td>
                    <td>{{ item.customer_name ? item.customer_name : 'N/A' }}</td>
                    <td><small :class="'px-3 badge badge-' + getStatusColor(item)">{{ item.fulfillment_status_text }}</small></td>
                    <td>{{ item.currency }} {{ item.grand_total ? Number(item.grand_total).toFixed(2).toLocaleString() : '-' }}<br /><small class="text-uppercase text-muted">{{ item.payment_status_text }}</small></td>
                </tr>
                </tbody>
            </table>

            <h3 v-if="data.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
        </div>
        <!-- Card footer -->
        <div class="card-footer py-4" v-if="!retrieving">
            <pagination-component :details="pagination" @paginated="paginate"></pagination-component>
        </div>
    </div>
</template>


<script>
    export default {
        name: "LogisticOrderComponent",
        data() {
            return {
                data: [],
                request_url: '/web/orders',
                search: '',
                fulfillment_status: "PENDING,READY_TO_SHIP",
                retrieving: false,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
            }
        },
        created() {
            this.retrieve()
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.data = [];
                let parameters = {
                    search: this.search,
                    fulfillment_status: this.fulfillment_status,
                    page: this.pagination.current_page,
                };
                axios.get(this.request_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.data = data.response.items;
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                })
            },
            getStatusColor: function(order) {
                switch (order.fulfillment_status) {
                    // Pending
                    case 0:
                    // Processing
                    case 1:
                    // Ready to Ship
                    case 10:
                    // Partially Shipped
                    case 12:
                    // Retry Ship
                    case 13:
                        return 'warning';
                    // Shipped
                    case 11:
                    // Delivered
                    case 20:
                    // Pending Confirmation
                    case 21:
                        return 'success';
                    // Cancelled
                    case 30:
                        return 'danger';
                    default:
                        return 'info';
                }
            },
            selectOrder(order, pos) {
                this.$emit('selectOrder', order)
            },
            paginate(value) {
                this.pagination = value;
                this.retrieve();
            }
        }
    }
</script>

<style scoped>

</style>
