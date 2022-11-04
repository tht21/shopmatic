<template>
    <div>
        <div class="row">
            <div class="col-12">
                <div v-if="showByOrder">
                    <div id="index-table " class="table-responsive">
                        <table class="table align-items-center table-flush">
                            <caption></caption>
                            <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Order ID</th>
                                <th>Sales</th>
                                <th>Client</th>
                                <th>Created Date</th>
                                <th>Order Status</th>
                                <th>Details</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template v-for="(item, index) in data">
                                <tr class="cursor-pointer active" v-bind:key="item.id" @click="clickOrder(item, index)">
                                    <td>
                                        <span v-if="item.external_source">
                                            <img :src="'/images/integrations/' + item.external_source.toLowerCase() + '.png'" width="32px" alt="random" />
                                        </span>
                                        {{ item.external_source }}
                                    </td>
                                    <td>Order #{{ item.external_id ? item.external_id : item.id }}</td>
                                    <td>{{ item.currency }} {{ item.grand_total ?
                                        Number(item.grand_total).toFixed(2).toLocaleString() : '-' }}
                                    </td>
                                    <td>{{ item.customer_name }}</td>
                                    <td>{{ item.order_placed_at ? item.order_placed_at : item.created_at }}</td>
                                    <td>
                                        <b-badge
                                            :variant="getStatusColor(item)"
                                        >{{item.fulfillment_status_text}}
                                        </b-badge>
                                    </td>
                                    <td @click="productDetail(item)" @click.stop.handler class="text-center">
                                      <span>
                                        <span v-show="productCollapsed == item.id"><i class="fas fa-chevron-up"/></span>
                                        <span v-show="productCollapsed != item.id || productCollapsed == ''"><i
                                            class="fas fa-chevron-down"/></span>
                                      </span>
                                    </td>
                                </tr>


                                <tr style="background-color: white" v-show="productCollapsed == item.id">

                                    <td>Product Image</td>
                                    <td>Product Name</td>
                                    <td>SKU ID</td>
                                    <td>Update</td>
                                    <td>Price</td>
                                    <td>QTY</td>
                                    <td></td>
                                </tr>

                                <tr
                                    style="background-color: white"
                                    v-for="(items) in item.items"
                                    v-bind:key="items.id"
                                    v-show="productCollapsed == item.id"
                                    class="cursor-pointer"
                                >
                                    <td>
                                        <img v-if="items.product" :src="items.product.main_image" width="64px" alt="random"/>
                                    </td>
                                    <td>{{ items.name }}</td>
                                    <td>{{items.sku}}</td>
                                    <td>{{items.updated_at}}</td>
                                    <td>{{items.item_price}}</td>
                                    <td>{{items.quantity}}</td>
                                    <td></td>

                                </tr>

                            </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3
                    v-if="data.length === 0 && !retrieving"
                    class="text-muted text-center font-weight-light py-3"
                >There is nothing that matches your criteria!</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <!-- Card footer -->
                <div v-show="!retrieving">
                    <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <b-modal
            id="order-table-details"
            size="xl"
            title="Order Details"
            no-enforce-focus
            hide-footer >

            <order-detail-component :order="order"></order-detail-component>
        </b-modal>
    </div>
</template>

<script>
export default {
    name: "OrderIndexTableComponent",
    props:[
        'data',
        'products',
        'retrieving',
        'pagination',
        'limit',
    ],
    data() {
        return {
            showByOrder: true,
            showByProduct: false,
            productCollapsed: "",
            updating: false,
            order: null,
        }
    },
    methods: {
        getStatusColor: function(order) {
            switch (order.fulfillment_status) {
                // Pending
                case 0:
                    return "light";
                // Processing
                case 1:
                // Ready to Ship
                case 10:
                // Partially Shipped
                case 12:
                // Retry Ship
                case 13:
                    return "warning";
                // Shipped
                case 11:
                // Delivered
                case 20:
                // Pending Confirmation
                case 21:
                    return "success";
                // Cancelled
                case 30:
                    return "danger";
                default:
                    return "info";
            }
        },
        toggleShowByOrder: function() {
            this.showByOrder = true;
            this.showByProduct = false;
        },
        toggleShowByProduct: function() {
            this.showByOrder = false;
            this.showByProduct = true;
        },
        clickOrder: function(order, pos) {
            if (this.sending_request) {
                notify(
                    "top",
                    "Error",
                    "The order is still updating.. Please wait.",
                    "center",
                    "danger"
                );
                return;
            }
            this.order = order;
            this.updateCurrent();
            this.$bvModal.show('order-table-details')

        },
        closeOrder: function() {
            if (!this.order) {
                return;
            }
            if (this.sending_request) {
                notify(
                    "top",
                    "Error",
                    "The order is still updating.. Please wait.",
                    "center",
                    "danger"
                );
                return;
            }
            this.$bvModal.hide('order-table-details')
            this.editNote = false;
            this.order = null;
        },
        updateCurrent: function() {
            if (this.updating || !this.order) {
                return;
            }
            this.updating = true;
            axios
                .get("/web/orders/" + this.order.id)
                .then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify("top", "Error", data.meta.message, "center", "danger");
                    } else {
                        this.order = data.response;
                    }
                    this.updating = false;
                })
                .catch((error) =>{
                    this.updating = false;
                    if (
                        error.response &&
                        error.response.data &&
                        error.response.data.meta
                    ) {
                        notify(
                            "top",
                            "Error",
                            error.response.data.meta.message,
                            "center",
                            "danger"
                        );
                    } else {
                        notify("top", "Error", error, "center", "danger");
                    }
                });
        },
        productDetail: function(items) {
            this.selectedItems = items;
            if (this.productCollapsed != items.id) this.productCollapsed = items.id;
            else this.productCollapsed = "";
        },
        paginate(value, limit) {
            this.$emit('paginate', value, limit);
        },
    }
}
</script>
