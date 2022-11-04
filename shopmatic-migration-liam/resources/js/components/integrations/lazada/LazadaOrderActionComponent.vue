<template>
    <div>
        <div class="row">
            <div class="col-12">
                <button type="button" class="btn btn-primary mr-2" @click="pack()" v-if="canPack"><i class="fas fa-box-open"></i> Ready to Pack</button>
                <button type="button" class="btn btn-primary mr-2" @click="labels()" v-if="canPrintLabels"><i class="fas fa-print"></i> Print Documents</button>
                <button type="button" class="btn btn-primary mr-2" @click="pickup()" v-if="canPickup"><i class="fas fa-print"></i> Verify Pincode</button>
                <button type="button" class="btn btn-primary mr-2" @click="invoiceNumber()" v-if="canInvoiceNumber"><i class="fas fa-file-invoice"></i> Set Invoice Number</button>
                <button type="button" class="btn btn-info mr-2" @click="delivered()" v-if="canDelivered"><i class="fas fa-truck"></i> Delivered</button>
                <button type="button" class="btn btn-danger mr-2" @click="faildedDeliver()" v-if="canDelivered"><i class="fas fa-truck-loading"></i> Fail to deliver</button>
                <button type="button" class="btn btn-success mr-2" @click="rts()" v-if="canRts"><i class="fas fa-check"></i> Ready to Ship</button>
                <button type="button" class="btn btn-danger" @click="cancel()" v-if="canCancel"><i class="fa fa-times"></i> Cancel</button>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
            <div class="col-12 mt-3 action-guide">
                <small><a href="#lazada-help" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="lazada-help">Guide & Help <i class="fas fa-angle-double-down"></i></a></small>
                <div id="lazada-help" class="collapse mt-2">
                    <span class="text-muted">The flow for order processing for Lazada is</span>
                </div>
            </div>
        </div>

        <b-modal id="lazada-pack" :ref="'lazada-pack-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Pack Items</h2>
                <button type="button" class="close" @click="closePack()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in order.items" v-if="item.fulfillment_status === 0 || item.fulfillment_status === 3 " :class="'cursor-pointer ' + (selected.includes(item.id) ? 'table-success' : '')" @click="selectItem(item)">
                    <td style="width: 60px;">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" @click="selectItem(item)" :checked="selected.includes(item.id)">
                            <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                        </div>
                    </td>
                    <td>
                        <template v-if="item.product">
                            <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                        </template>
                        <template v-else>
                            {{ item.name }}
                        </template><br />
                        <span v-if="item.variation_name">{{ item.variation_name }}<br /></span>
                        <span v-if="item.sku">SKU: {{ item.sku }}</span>
                    </td>
                    <td class="text-center"><p>{{ item.quantity }}</p></td>
                </tr>
                </tbody>
            </table>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closePack()">Close</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmPack">Pack</button>
            </template>
        </b-modal>

        <b-modal id="lazada-cancel" :ref="'lazada-cancel-' + this.order.id" size="lg"
                 header-bg-variant="danger" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Cancel Order</h2>
                <button type="button" class="close" @click="closeCancel" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in order.items" v-if="item.fulfillment_status >= 0 && item.fulfillment_status <= 10" :class="'cursor-pointer ' + (selected.includes(item.id) ? 'table-success' : '')" @click="selectItem(item)">
                    <td style="width: 60px;">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" @click="selectItem(item)" :checked="selected.includes(item.id)">
                            <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                        </div>
                    </td>
                    <td>
                        <template v-if="item.product">
                            <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                        </template>
                        <template v-else>
                            {{ item.name }}
                        </template><br />
                        <span v-if="item.variation_name">{{ item.variation_name }}<br /></span>
                        <span v-if="item.sku">SKU: {{ item.sku }}</span>
                    </td>
                    <td class="text-center"><p>{{ item.quantity }}</p></td>
                </tr>
                </tbody>
            </table>
            <h3 class="mt-4">Select Reason</h3>
            <select name="reason_id" v-model="reason_id" class="form-control" required>
                <option value="" selected disabled>-- Select --</option>
                <option v-for="reason in reasons" :value="reason.reason_id">{{ reason.reason_name }}</option>
            </select>
            <h3 class="mt-4">Details</h3>
            <textarea class="form-control" rows="5" v-model="detail" placeholder="Optional"></textarea>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closeCancel()">Close</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmCancel">Cancel Order</button>
            </template>
        </b-modal>

        <b-modal id="lazada-rts" :ref="'lazada-rts-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Ready to Ship<br /><small>Mark the items as ready to ship</small></h2>
                <button type="button" class="close" @click="closeRts()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in order.items" :key="index" v-if="item.fulfillment_status === 1 || item.fulfillment_status === 2 || item.fulfillment_status === 4" :class="'cursor-pointer ' + (selected.includes(item.id) ? 'table-success' : '')" @click="selectItem(item)">
                    <td style="width: 60px;">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" @click="selectItem(item)" :checked="selected.includes(item.id)">
                            <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                        </div>
                    </td>
                    <td>
                        <template v-if="item.product">
                            <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                        </template>
                        <template v-else>
                            {{ item.name }}
                        </template><br />
                        <span v-if="item.variation_name">{{ item.variation_name }}<br /></span>
                        <span v-if="item.sku">SKU: {{ item.sku }}</span>
                    </td>
                    <td class="text-center"><p>{{ item.quantity }}</p></td>
                </tr>
                </tbody>
            </table>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closeRts()">Close</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmRts($event)">Ready</button>
            </template>
        </b-modal>

        <b-modal id="lazada-labels" :ref="'lazada-labels-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Print Documents</h2>
                <button type="button" class="close" @click="closeLabels()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in order.items" v-if="item.fulfillment_status > 0 && item.fulfillment_status < 30" :class="'cursor-pointer ' + (selected.includes(item.id) ? 'table-success' : '')" @click="selectItem(item)">
                    <td style="width: 60px;">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" @click="selectItem(item)" :checked="selected.includes(item.id)">
                            <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                        </div>
                    </td>
                    <td>
                        <template v-if="item.product">
                            <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                        </template>
                        <template v-else>
                            {{ item.name }}
                        </template><br />
                        <span v-if="item.variation_name">{{ item.variation_name }}<br /></span>
                        <span v-if="item.sku">SKU: {{ item.sku }}</span>
                    </td>
                    <td class="text-center"><p>{{ item.quantity }}</p></td>
                </tr>
                </tbody>
            </table>

            <h3 class="mt-4">Documents</h3>
            <div class="row">
                <div class="col-md-6" @click="selectDoc('invoice', $event)">
                    <div class="custom-control custom-radio mb-3">
                        <input class="custom-control-input" id="doc-invoice" :checked="documents === 'invoice'" type="radio">
                        <label class="custom-control-label" for="doc-invoice">Invoice</label>
                    </div>
                </div>
                <div class="col-md-6" @click="selectDoc('shippingLabel', $event)" v-if="!isPickup">
                    <div class="custom-control custom-radio mb-3">
                        <input class="custom-control-input" id="doc-shipping" :checked="documents === 'shippingLabel'" type="radio">
                        <label class="custom-control-label" for="doc-shipping">Shipping Label</label>
                    </div>
                </div>
            </div>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closeLabels()">Cancel</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmPrint">Print</button>
            </template>
        </b-modal>

        <b-modal id="lazada-invoice-number" :ref="'lazada-invoice-number-' + this.order.id"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Set Invoice Number</h2>
                <button type="button" class="close" @click="closeInvoiceNumber()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>
            <h3 v-if="shippingProvidersName" class="mt-4">Shipment Provider</h3>
            <span v-if="shippingProvidersName">{{ shippingProvidersName }}</span>
            <h3 class="mt-4">Invoice Number</h3>
            <b-form-input v-model="invoice_number" :disabled="isPickup" placeholder="Enter Invoice Number"></b-form-input>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closeInvoiceNumber()">Cancel</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmInvoiceNumber">Set</button>
            </template>
        </b-modal>


        <b-modal id="lazada-pickup-number" :ref="'lazada-pickup-number-' + this.order.id"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Verify Pincode</h2>
                <button type="button" class="close" @click="closePickup()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3 class="mt-4">Pincode</h3>
            <b-form-input v-model="pincode" placeholder="Enter Pincode"></b-form-input>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closePickup()">Cancel</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmPickup">Confirm</button>
            </template>
        </b-modal>

        <b-modal id="lazada-delivered" :ref="'lazada-delivered-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Delivered</h2>
                <button type="button" class="close" @click="closeDelivered()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in order.items" v-if="item.fulfillment_status > 0 && item.fulfillment_status < 30" :class="'cursor-pointer ' + (selected.includes(item.id) ? 'table-success' : '')" @click="selectItem(item)">
                    <td style="width: 60px;">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" @click="selectItem(item)" :checked="selected.includes(item.id)">
                            <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                        </div>
                    </td>
                    <td>
                        <template v-if="item.product">
                            <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                        </template>
                        <template v-else>
                            {{ item.name }}
                        </template><br />
                        <span v-if="item.variation_name">{{ item.variation_name }}<br /></span>
                        <span v-if="item.sku">SKU: {{ item.sku }}</span>
                    </td>
                    <td class="text-center"><p>{{ item.quantity }}</p></td>
                </tr>
                </tbody>
            </table>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closeDelivered()">Cancel</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmDelivered">Delivered</button>
            </template>
        </b-modal>

        <b-modal id="lazada-failedDeliver" :ref="'lazada-failedDeliver-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Failed Deliver</h2>
                <button type="button" class="close" @click="closeFailedDeliver()" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in order.items" v-if="item.fulfillment_status > 0 && item.fulfillment_status < 30" :class="'cursor-pointer ' + (selected.includes(item.id) ? 'table-success' : '')" @click="selectItem(item)">
                    <td style="width: 60px;">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" @click="selectItem(item)" :checked="selected.includes(item.id)">
                            <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                        </div>
                    </td>
                    <td>
                        <template v-if="item.product">
                            <a :href="'/dashboard/products/' + item.product.slug" target="_blank">{{ item.name }}</a>
                        </template>
                        <template v-else>
                            {{ item.name }}
                        </template><br />
                        <span v-if="item.variation_name">{{ item.variation_name }}<br /></span>
                        <span v-if="item.sku">SKU: {{ item.sku }}</span>
                    </td>
                    <td class="text-center"><p>{{ item.quantity }}</p></td>
                </tr>
                </tbody>
            </table>

            <template v-slot:modal-footer="{ ok, cancel }">
                <button type="button" class="btn btn-link" @click="closeFailedDeliver()">Cancel</button>
                <button type="button" class="btn btn-danger ml-auto" @click="confirmFailedDeliver">Failed Deliver</button>
            </template>
        </b-modal>
    </div>
</template>
<script>
    export default {
        name: "LazadaOrderActionComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                providers: [],
                reasons: [],
                selected: [],
                provider: null,
                reason_id: '',
                detail: '',
                shippingProvidersName: '',
                documents: 'invoice',
                invoice_number: null,
                pincode: '',
            }
        },
        computed: {
            canPack() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.fulfillment_status === 0 || item.fulfillment_status ===  3) {
                        count++;
                    }
                });
                return count > 0;
            },
            canPrintLabels() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.fulfillment_status > 0 && item.fulfillment_status < 30) {
                        count++;
                    }
                });
                return count > 0;
            },
            canRts() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.fulfillment_status === 1 || item.fulfillment_status === 2 || item.fulfillment_status === 4) {
                        count++;
                    }
                });
                return count > 0;
            },
            canPickup() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.shipment_method === 'pickup_in_store' && (item.fulfillment_status === 10 || item.fulfillment_status === 11)) {
                        count++;
                    }
                });
                return count > 0;
            },
            isPickup() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.shipment_method === 'pickup_in_store') {
                        count++;
                    }
                });
                return count > 0;
            },
            canCancel() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.fulfillment_status >= 0 && item.fulfillment_status <= 10) {
                        count++;
                    }
                });
                return count > 0;
            },
            canDelivered() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.fulfillment_status === 10) {
                        count++;
                    }
                });
                return count > 0;
            },
            canInvoiceNumber() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if ((item.fulfillment_status > 0 && item.fulfillment_status <  30) && !item.data.invoice_number) {
                        count++;
                    }
                });

                // TaxInvoiceRequested is not accurate
                /*let extra_attributes = {};
                try {
                    extra_attributes = JSON.parse(this.order.data[0]['extra_attributes'])
                } catch(e) {}

                if (typeof extra_attributes['TaxInvoiceRequested'] != 'undefined' && !extra_attributes['TaxInvoiceRequested']) {
                    count = 0;
                }*/
                return count > 0;
            }
        },
        methods: {
            pack: function() {
                this.retrieveShippingProviders();
                this.$refs['lazada-pack-' + this.order.id].show();
            },
            cancel: function() {
                this.retrieveReasons();
                this.$refs['lazada-cancel-' + this.order.id].show();
            },
            labels: function() {

                this.order.items.forEach((item) => {
                    if (item.fulfillment_status > 0 && item.fulfillment_status < 30) {
                        this.selected.push(item.id);
                    }
                });
                this.$refs['lazada-labels-' + this.order.id].show();
            },
            invoiceNumber: function() {

                this.order.items.forEach((item) => {
                    if ((item.fulfillment_status > 0 && item.fulfillment_status < 30) && !item.data.invoice_number) {
                        this.selected.push(item.id);
                    }
                });
                this.retrieveShippingProviders();
                this.$refs['lazada-invoice-number-' + this.order.id].show();
            },
            rts: function() {

                this.order.items.forEach((item) => {
                    if (item.fulfillment_status == 1) {
                        this.selected.push(item.id);
                    }
                });
                this.$refs['lazada-rts-' + this.order.id].show();
            },
            pickup: function() {
                this.$refs['lazada-pickup-number-' + this.order.id].show();
            },
            delivered: function() {

                this.order.items.forEach((item) => {
                    if (item.fulfillment_status > 0 && item.fulfillment_status < 30) {
                        this.selected.push(item.id);
                    }
                });
                this.$refs['lazada-delivered-' + this.order.id].show();
            },
            faildedDeliver: function() {

                this.order.items.forEach((item) => {
                    if (item.fulfillment_status > 0 && item.fulfillment_status < 30) {
                        this.selected.push(item.id);
                    }
                });
                this.$refs['lazada-failedDeliver-' + this.order.id].show();
            },
            closePack: function() {
                this.providers = [];
                this.provider = null;
                this.selected = [];

                this.$refs['lazada-pack-' + this.order.id].hide();
            },
            closeLabels: function() {
                this.$refs['lazada-labels-' + this.order.id].hide();
                this.selected = [];
            },
            closeInvoiceNumber: function() {
                this.$refs['lazada-invoice-number-' + this.order.id].hide();
                this.selected = [];
            },
            closePickup: function() {
                this.$refs['lazada-pickup-number-' + this.order.id].hide();
                this.selected = [];
            },
            closeRts: function() {
                this.$refs['lazada-rts-' + this.order.id].hide();
                this.selected = [];
            },
            closeCancel: function() {
                this.$refs['lazada-cancel-' + this.order.id].hide();
                this.selected = [];
                this.reason_id = '';
                this.detail = '';
            },
            closeDelivered: function() {
                this.$refs['lazada-delivered-' + this.order.id].hide();
                this.selected = [];
            },
            closeFailedDeliver: function() {
                this.$refs['lazada-failedDeliver-' + this.order.id].hide();
                this.selected = [];
            },
            confirmPack: function() {
                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to pack.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                axios.post('/web/orders/' + this.order.id + '/lazada/pack', {
                    order_item_ids: this.selected,
                    provider: this.provider,
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully updated!', 'center', 'success');
                        this.closePack();
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            confirmRts: function(event) {

                var buttonConfirmRtsElement =  $(event.target);

                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to pack.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                // disabled button
                buttonConfirmRtsElement.addClass('disabled');
                axios.post('/web/orders/' + this.order.id + '/lazada/rts', {
                    order_item_ids: this.selected,
                }).then((response) => {
                    buttonConfirmRtsElement.removeClass('disabled');
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.closePack();
                        notify('top', 'Success', 'Successfully updated!', 'center', 'success');
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    buttonConfirmRtsElement.removeClass('disabled');
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            confirmCancel: function() {
                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to cancel.', 'center', 'danger');
                    return;
                }
                if (!this.reason_id) {
                    notify('top', 'Error', 'You need to select the reason to cancel.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/lazada/cancel', {
                    order_item_ids: this.selected,
                    reason_id: this.reason_id,
                    reason_detail: this.detail,
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully cancelled order! Order status might take 5 - 10 minutes to get updated.', 'center', 'success');
                        this.closeCancel();
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            confirmPrint: function() {
                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to print.', 'center', 'danger');
                    return;
                }
                if (this.documents.length === 0) {
                    notify('top', 'Error', 'You need to select at least one document', 'center', 'danger');
                    return;
                }
                if (this.documents === 'invoice') {
                    /*let extra_attributes = {};
                    try {
                        extra_attributes = JSON.parse(this.order.data[0]['extra_attributes'])
                    } catch(e) {}

                    if (typeof extra_attributes['TaxInvoiceRequested'] != 'undefined' && extra_attributes['TaxInvoiceRequested']) {
                        if (!this.order.items[0].data.invoice_number) {
                            notify('top', 'Error', 'Please set invoice number first, in order to print invoice', 'center', 'danger');
                            return;
                        }
                    }*/
                    if (!this.order.items[0].data.invoice_number) {
                        notify('top', 'Error', 'Please set invoice number first, in order to print invoice', 'center', 'danger');
                        return;
                    }
                }
                if (this.sending_request) {
                    return;
                }

                this.sending_request = true;

                //this.documents.forEach((document) => {
                    axios.post('/web/orders/' + this.order.id + '/lazada/print', {
                        order_item_ids: this.selected,
                        document: this.documents,
                    }).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            let w = window.open("about:blank", this.documents);
                            w.document.write(data.response.file);
                            if (this.documents === 'invoice') {
                                w.document.write('<script>document.body.className += \'la-print-page print\';window.addEventListener("load", function(){\n' +
                                    '        window.print();\n' +
                                    '        window.onfocus=function(){ window.close();}\n' +
                                    '    });<' + '/script>');
                                w.document.write('<link href="https://lazada-slatic-g.alicdn.com/lazada/voyager-sc/0.0.213/orders-overview.css" rel="stylesheet">');
                            } else {
                                w.document.write('<script>window.addEventListener("load", function(){\n' +
                                    '        window.print();\n' +
                                    '        window.onfocus=function(){ window.close();}\n' +
                                    '    });<' + '/script>');
                            }
                            w.document.close();
                        }
                        this.sending_request = false;
                    }).catch((error) => {
                        if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }
                        this.sending_request = false;
                    });
                //});
            },
            confirmInvoiceNumber: function() {
                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to print.', 'center', 'danger');
                    return;
                }
                if (this.invoice_number.trim() == '') {
                    notify('top', 'Error', 'Please enter invoice number.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                axios.post('/web/orders/' + this.order.id + '/lazada/setInvoiceNumber', {
                    order_item_ids: this.selected,
                    invoice_number: this.invoice_number
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully set invoice number!', 'center', 'success');
                        this.closeInvoiceNumber();
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            confirmPickup: function() {
                if (this.pincode.trim() == '') {
                    notify('top', 'Error', 'Please enter pincode.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                axios.post('/web/orders/' + this.order.id + '/lazada/verifyPincode', {
                    pincode: this.pincode
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully confirmed order!', 'center', 'success');
                        this.closePickup();
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            confirmDelivered: function() {
                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to print.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                axios.post('/web/orders/' + this.order.id + '/lazada/delivered', {
                    order_item_ids: this.selected
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully cancelled order!', 'center', 'success');
                        this.closeDelivered();
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            confirmFailedDeliver: function() {
                if (this.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to print.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                axios.post('/web/orders/' + this.order.id + '/lazada/failedDelivery', {
                    order_item_ids: this.selected
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully cancelled order!', 'center', 'success');
                        this.closeFailedDeliver();
                        typeof this.$parent.$parent !=='undefined' && typeof this.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.updateCurrent() : this.$parent.updateCurrent(this.order.id);
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            selectItem: function(item) {
                if (this.selected.includes(item.id)) {
                    let index = this.selected.indexOf(item.id);
                    this.selected.splice(index, 1);
                } else {
                    this.selected.push(item.id);
                }
            },
            selectDoc: function(item, event) {
                event.preventDefault();
                event.stopPropagation();

                this.documents = item;
                /*if (this.documents.includes(item)) {
                    let index = this.documents.indexOf(item);
                    this.documents.splice(index, 1);
                } else {
                    this.documents.push(item);
                }*/
            },
            retrieveShippingProviders: function() {

                axios.get('/web/orders/' + this.order.id + '/lazada/providers').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.providers = data.response;
                         this.shippingProvidersName = data.response.map(function(elem){
                            return elem.name;
                        }).join(", ");
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveReasons: function() {

                axios.get('/web/orders/' + this.order.id + '/lazada/reasons').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.reasons = data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
        },
        created() {
            if(this.isPickup == true){
                this.invoice_number = this.order.external_id;
            }
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>

<style scoped>
    #lazada-cancel___BV_modal_outer_, #lazada-pickup-number___BV_modal_outer_, #lazada-pack___BV_modal_outer_, #lazada-rts___BV_modal_outer_, #lazada-labels___BV_modal_outer_, #lazada-invoice-number___BV_modal_outer_, #lazada-delivered___BV_modal_outer_, #lazada-failedDeliver___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
