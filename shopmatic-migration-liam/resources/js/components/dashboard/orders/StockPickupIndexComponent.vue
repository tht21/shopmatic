<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">Pickup List <button class="btn btn-sm btn-info ml-3" @click="retrieveItems"><i class="fa fa-sync-alt"></i></button></h3>
        </div>
        <div id="filter">
            <div class="p-3" style="background: #f6f6f6;">
                <b-row>
                    <b-col>
                        <button
                            type="button"
                            class="btn btn-size"
                            @click="selectDate('')"
                            :class="[selected_date === '' ? 'btn-primary' : 'btn-info' ]"
                        >
                            All
                        </button>
                        <template v-for="day in days">
                            <button
                                type="button"
                                class="btn btn-size"
                                @click="selectDate(day.date)"
                                :class="[selected_date === day.date ? 'btn-primary' : 'btn-info' ]"
                            >
                                {{ day.day }} <br/>
                                {{ day.date }}
                            </button>
                        </template>
                    </b-col>
                </b-row>

                <b-alert class="mt-3" variant="warning" v-if="selected_date === ''" show>
                    Include orders from marketplaces which doesn't have ship by date.
                </b-alert>
            </div>
        </div>

        <!-- Light table -->
        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th>Products</th>
                    <th>Order ID</th>
                    <th>Variations</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                    <template v-if="retrieving">
                        <tr class="text-center">
                            <td colspan="3">
                                <i class="fas fa-spinner fa-pulse font-size-30"></i>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr v-for="item in items">
                            <td>
                                Product: {{ item.name }} <br/>
                                SKU: <b>{{ (item.sku) ? item.sku : '-' }}</b>
                            </td>
                            <td>{{ item.order_ids }}</td>
                            <td>{{ item.variation_name }}</td>
                            <td class="text-center">{{ item.total_quantity }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <h3 v-if="items.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
        </div>

        <!-- Card footer -->
        <div class="card-footer py-4" v-if="!retrieving">
            <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
        </div>
    </div>
</template>

<script>
    export default {
        name: "StockPickupIndexComponent",
        data() {
            return {
                items: [],
                retrieving: false,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                limit: 10,
                days: [],
                selected_date: ''
            }
        },
        methods: {
            selectDate(date) {
                this.selected_date = date;
                this.retrieveItems();
            },
            retrieveItems() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.items = [];
                let parameters = {
                    ship_date: this.selected_date,
                    page: this.pagination.current_page,
                    limit: this.limit,
                };
                axios.get('/web/orders/pickup', {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.items = data.response.items;
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit;
                this.retrieveItems();
            },
            getWeek() {
                this.days = [];
                for (let i = 0; i < 9; i++) {
                    let today = new Date;
                    let day = new Date(today.setDate(today.getDate() + i)); // Add day to today
                    //let day = new Date(today.setDate(first)).toISOString().slice(0, 10)

                    // Exclude weekend
                    if (day.getDay() != 0 && day.getDay() != 6) {
                        this.days.push({
                            day: day.toDateString().slice(0, 3),
                            date: day.toLocaleDateString()
                        });
                    }
                }
            }
        },
        created() {
            this.retrieveItems();
            this.getWeek();
        },
    }
</script>

<style scoped>
    .btn-size {
        width: 120px;
        height: 65px;
    }
</style>
