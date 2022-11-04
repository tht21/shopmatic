<template>
    <b-card no-body :class="[!isMobile() ? 'h-100' : 'my-2']">
        <template v-if="data">
            <b-card-header class="p-2 text-center">
                <b-button v-if="isMobile()" block href="#" v-b-toggle.client-details-accordion variant="primary">Client Details</b-button>
                <b-collapse id="client-details-accordion" :visible="!isMobile()">
                    <img width="10%" :src="data.image" class="rounded-circle">
                    <h3>{{data.name}}</h3>
                </b-collapse>
            </b-card-header>
            <b-collapse class="position-relative overflow-auto p-0" id="client-details-accordion"
                        :visible="!isMobile()">
                <b-card-body :class="['p-0', isMobile() ? 'card-body-height' : '']">
                    <ul v-if="data.orders" class="list-group list-group-flush px-0 my-2">
                        <li v-for="(item, index)  in data.orders"
                            :class="'list-group-item p-3'">
                            <h3>ID: {{ item.external_id ? item.external_id : item.id }}
                                <small :class="'px-3 float-right badge badge-' + getStatusColor(item)">{{
                                    item.fulfillment_status_text
                                    }}</small>
                            </h3>
                            <h5>DATE: {{ item.order_placed_at ? item.order_placed_at :
                                item.created_at }}</h5>
                            <h5>TOTAL: {{ item.currency }} {{ item.grand_total ?
                                Number(item.grand_total).toFixed(2).toLocaleString() : '-' }} {{
                                item.payment_status_text
                                }}</h5>
                        </li>
                    </ul>
                </b-card-body>
            </b-collapse>
        </template>
    </b-card>
</template>

<script>
    export default {
        name: "ChatDetailsComponent",
        props: {
            id: {
                type: Number,
                default: null,
            }
        },
        filters: {},
        data() {
            return {
                request_url: '/web/chat/',
                data: null,
            }
        },
        watch: {
            id() {
                this.data = null;
                if (this.id != null) {
                    this.retrieve();
                }
            },
        },
        created() {
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                axios.get(this.request_url + this.id, {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (data.response) {
                            this.data = data.response.client;
                        }
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
            getStatusColor: function (order) {
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
            isMobile() {
                if (/Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    return true
                } else {
                    return false
                }
            },
        }
    }
</script>

<style scoped>
    .card-body-height {
        min-height: 25px;
        max-height: 500px
    }
</style>

