<template>
    <div class="row">
        <div class="col-12">
            <h2>Actions</h2>
            <template v-if="order.account">
                <component v-bind:is="orderActionComponent" :order="order" v-if="order.account.status === 0"></component>
            </template>
            <template v-else>
                You cannot take any actions as your account is not active. <a href="/dashboard/accounts">View Accounts</a> for more details.
            </template>
            <hr />
        </div>
        <div class="col-md-7">
            <h2>Items</h2>
            <div class="table-responsive bg-shadow bg-white">
                <div class="p-3">
                    <h4>Customer's Remarks</h4>
                    <span v-if="order.buyer_remarks">{{ order.buyer_remarks }}</span>
                    <span v-else><em class="text-muted">There are no remarks for this order.</em></span>
                </div>
                <table class="table align-items-center table-flush mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th class="text-right">Total ({{ order.items.length }} items)</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <tr v-for="(item, index) in order.items">
                        <td>
                            <p>
                                <template v-if="item.product">
                                    <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                                </template>
                                <template v-else>
                                    {{ item.name }}
                                </template><br />
                                <span v-if="item.variation_name">{{ item.variation_name }}</span>
                            </p>
                            <span v-if="item.sku">SKU: {{ item.sku }}</span>

                            <div v-if="showInventory" class="mt-2">
                                <template v-if="item.inventory">
                                    <span>Inventory: <a :href="'/dashboard/inventory/' + item.product_inventory_id" target="_blank">{{ item.inventory.name }}</a></span><br />
                                    <span>Stock Remaining: {{ item.inventory.stock }}</span><br />
                                </template>
                                Stock Deducted? <strong :class="item.inventory_status === 1 ? '' :'text-danger'">{{ item.inventory_status === 1 ? 'Yes' : 'No' }}</strong>
                            </div>
                        </td>
                        <td>
                            <span :class="'px-3 badge badge-' + getStatusColor(item)">{{ item.fulfillment_status_text }}</span>
                            <template v-if="item.shipment_provider">
                                <br />
                                <small class="text-uppercase">Provider</small> : <b>{{ item.shipment_provider }}</b>
                                <br />
                                <small class="text-uppercase">T. Number</small> : <b>{{ item.tracking_number }}</b>
                            </template>
                            <template v-if="item.shipment_method == 'pickup_in_store'">
                                <br />
                                <small class="text-uppercase">Provider</small> : <b>Store Pickup</b>
                            </template>
                        </td>
                        <td class="text-right"><p>{{ order.currency }} {{ item.grand_total ? Number(item.grand_total).toFixed(2).toLocaleString() : '-' }}<br />x <strong>{{ item.quantity }}</strong></p></td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <div class="order-inventory-actions">
                                <a href="#!" @click="toggleInventory"><small>{{ showInventory ? 'Hide' : 'Show' }} Inventory Status</small></a>

                                <button v-if="showInventory" class="btn btn-danger btn-sm float-right d-none">Edit Inventory</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" :class="'text-white ' + (order.payment_status === 1 ? 'bg-teal' : 'bg-danger')">
                            <div class="row">
                                <div class="col-8">
                                    <p>Shipping Fee</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p>{{ order.currency }} {{ Number(order.shipping_fee).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div v-if="order.integration_discount > 0" class="row mt-1">
                                <div class="col-8">
                                    <p>Integration Discount</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p>{{ order.currency }} {{ Number(order.integration_discount).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="row mt-1" v-if="order.seller_discount > 0">
                                <div class="col-8">
                                    <p>Seller Discount</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p>{{ order.currency }} {{ Number(order.seller_discount).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="row mt-1" v-if="order.tax > 0">
                                <div class="col-8">
                                    <p>Tax</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p>{{ order.currency }} {{ Number(order.tax).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="row mt-1" v-if="order.tax_2 > 0">
                                <div class="col-8">
                                    <p>Tax 2</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p>{{ order.currency }} {{ Number(order.tax_2).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-8 font-weight-bold">
                                    <p>Grand Total</p>
                                </div>
                                <div class="col-4 text-right font-weight-bold ">
                                    <p>{{ order.currency }} {{ Number(order.grand_total).toFixed(2).toLocaleString() }}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-8">
                                    <p class="mb-0">Buyer Paid</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p class="mb-0">{{ order.currency }} {{ Number(order.buyer_paid).toFixed(2).toLocaleString() }}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-8">
                                    <p>Payment Method</p>
                                </div>
                                <div class="col-4 text-right">
                                    <p>{{ order.payment_method }}<br />
                                        <span :class="'px-3 badge badge-' + (order.payment_status === 1 ? 'success' : 'danger')">{{ order.payment_status_text }}</span></p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 mt-md-0 col-md-5">
            <h2>Notes</h2>
            <div class="card">
                <div class="card-body">
                    <div v-if="!editNote" @click="editNote = true" class="cursor-pointer">
                        <template v-if="order.notes"><div style="white-space: pre-wrap;">{{ order.notes }}</div><small><i>Click to edit</i></small></template>
                        <template v-else><span class="text-muted">You have not added any notes for this order.<br /> Click here to add.</span></template>
                    </div>
                    <form id="update-notes" v-show="editNote" v-on:submit.prevent="updateNotes()">
                        <textarea id="notes" name="notes" class="form-control mb-2" rows="6">{{ order.notes }}</textarea>
                        <div class="text-right">
                            <small><a href="#!" @click="editNote = false">Cancel</a></small><button type="submit" class="btn btn-primary btn-sm ml-3">Update</button>
                        </div>
                    </form>
                </div>
            </div>
            <h2>Details</h2>
            <div class="card">
                <div class="card-body">
                    <div v-if="order.ship_by_date" class="mb-1">
                        <small class="text-uppercase text-muted">Ship By</small><br />
                        <p :class="getStatusColor(order) === 'warning' ? 'text-danger font-weight-bold' : ''">{{ order.ship_by_date }}</p>
                    </div>
                    <div v-if="order.customer_name" class="mb-1">
                        <small class="text-uppercase text-muted">Customer Name</small><br />
                        <p>{{ order.customer_name }}</p>
                    </div>
                    <div v-if="order.billing_address && order.billing_address.phoneNumber" class="mb-1">
                        <small class="text-uppercase text-muted">Customer Phone Number</small><br />
                        <p>{{ order.billing_address.phoneNumber }}</p>
                    </div>
                    <div v-if="order.customer_email" class="mb-1">
                        <small class="text-uppercase text-muted">Customer Email</small><br />
                        <p>{{ order.customer_email }}</p>
                    </div>
                    <div v-if="order.billing_address" class="mb-1">
                        <small class="text-uppercase text-muted">Billing Address</small><br />
                        <template v-if="order.billing_address.company">{{ order.billing_address.company }}<br /></template>
                        <template v-if="order.billing_address.name">{{ order.billing_address.name }}<br /></template>
                        <template v-if="order.billing_address.address1">{{ order.billing_address.address1 }}<br /></template>
                        <template v-if="order.billing_address.address2">{{ order.billing_address.address2 }}<br /></template>
                        <template v-if="order.billing_address.address3 && order.billing_address.country !== order.billing_address.address3">{{ order.billing_address.address3 }}<br /></template>
                        <template v-if="order.billing_address.address4 && order.billing_address.address4 !== order.billing_address.address3">
                            {{ order.billing_address.address4 }}<br />
                        </template>
                        <template v-if="order.billing_address.address5">{{ order.billing_address.address5 }}<br /></template>
                        <template v-if="order.billing_address.country">{{ order.billing_address.country }}<br /></template>
                        <div class="mt-1" v-if="order.billing_address.phone_number || order.billing_address.email ">{{ order.billing_address.phone_number }}<br />{{order.billing_address.email }}</div>
                    </div>
                    <div v-if="order.shipping_address" class="mt-4 mb-1">
                        <small class="text-uppercase text-muted">Shipping Address</small><br />
                        <template v-if="order.shipping_address.company">{{ order.shipping_address.company }}<br /></template>
                        <template v-if="order.shipping_address.name">{{ order.shipping_address.name }}<br /></template>
                        <template v-if="order.shipping_address.address1">{{ order.shipping_address.address1 }}<br /></template>
                        <template v-if="order.shipping_address.address2">{{ order.shipping_address.address2 }}<br /></template>
                        <template v-if="order.shipping_address.address3 && order.shipping_address.country !== order.shipping_address.address3">{{ order.shipping_address.address3 }}<br /></template>
                        <template v-if="order.shipping_address.address4 && order.shipping_address.address4 !== order.shipping_address.address3">
                            {{ order.shipping_address.address4 }}<br />
                        </template>
                        <template v-if="order.shipping_address.address5">{{ order.shipping_address.address5 }}<br /></template>
                        <template v-if="order.shipping_address.country">{{ order.shipping_address.country }}<br /></template>
                        <div class="mt-1" v-if="order.shipping_address.phone_number || order.shipping_address.email ">{{ order.shipping_address.phone_number }}<br />{{order.shipping_address.email }}</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Integration fees -->
        <div class="col-md-12">
            <h2>Integration Fees</h2>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                        Commission Fee
                        </div>
                    <div class="col-md-2"><b>{{ order.currency }} {{ Number(order.commission_fee).toFixed(2).toLocaleString() }}</b></div>
                    
                    <div class="col-md-4">
                        Transaction Fee
                    </div>
                    <div class="col-md-2"><b>{{ order.currency }} {{ Number(order.transaction_fee).toFixed(2).toLocaleString() }}</b></div>
                    <div class="col-md-4">
                        Integration Shipping Fee
                    </div>
                    <div class="col-md-2"><b>{{ order.currency }} {{ Number(order.integration_shipping_fee).toFixed(2).toLocaleString() }}</b></div>
                    <div class="col-md-4">
                        Seller Shipping Fee
                    </div>
                    <div class="col-md-2"><b>{{ order.currency }} {{ Number(order.seller_shipping_fee).toFixed(2).toLocaleString() }}</b></div>
                    <div class="col-md-4">
                        Integration Discount
                    </div>
                    <div class="col-md-2"><b>{{ order.currency }} {{ Number(order.integration_discount).toFixed(2).toLocaleString() }}</b></div>
                    <div class="col-md-4">
                        Seller Discount
                    </div>
                    <div class="col-md-2"><b>{{ order.currency }} {{ Number(order.seller_discount).toFixed(2).toLocaleString() }}</b></div>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: "OrderDetailComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                updating: false,
                editNote: false,
                showInventory: false,
            }
        },
        computed: {
            orderActionComponent() {
                let name = null;
                if (this.order.external_source && this.order.account_id) {
                    name = this.order.external_source + 'OrderActionComponent';
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
        methods: {
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
            toggleInventory: function() {
                this.showInventory = !this.showInventory;
            },
            updateCurrent: function() {
                if (this.updating || !this.order) {
                    return;
                }
                this.updating = true;
                let ctx = this;
                axios.get('/web/orders/' + this.order.id).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.order = data.response;
                    }
                    ctx.updating = false;
                }).catch(function (error) {
                    ctx.updating = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            updateNotes: function() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                let ctx = this;
                let data = new FormData($('#update-notes')[0]);
                // This is because axios.put doesn't support FormData directly, might be some setting, but this works
                data.append("_method", "put");
                notify('top', 'Info', 'Updating notes..', 'center', 'info');
                axios.post('/web/orders/' + this.order.id, data).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                        ctx.sending_request = false;
                    } else {
                        ctx.updateCurrent();
                        ctx.editNote = false;
                        notify('top', 'Success', 'Successfully updated the notes.', 'center', 'success');
                        ctx.sending_request = false;
                    }
                }).catch(function (error) {
                    ctx.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            }
        },
    }
</script>
