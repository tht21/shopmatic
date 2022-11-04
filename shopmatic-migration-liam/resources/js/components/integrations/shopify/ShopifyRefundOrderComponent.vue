<template>
    <span>
        <button type="button" class="btn btn-danger" @click="refund" v-if="canRefund"><i class="fa fa-times"></i> Refund</button>

        <b-modal id="refund-order-modal" :ref="'refund-order-modal-' + this.order.id" size="lg"
            header-bg-variant="danger" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Refund Order</h2>
                <button type="button" class="close" @click="closeRefund" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <b-table head-variant="light" class="align-items-center" :fields="fields" :items="items" @row-selected="onRowSelect" selectable select-mode="multi" selected-variant="success">
                <template v-slot:cell(check)="data">
                    <!-- @TODO - improve the checkbox -->
                    <input type="checkbox" @click="selectItem(data.item)" :checked="form.selected.includes(data.item.id)"/>
                </template>
                <template v-slot:cell(name)="data">
                    <template v-if="data.item.product">
                        <a :href="'/dashboard/products/' + data.item.product.slug" target="_blank">{{ data.item.name }}</a>
                    </template>
                    <template v-else>
                        {{ data.item.name }}
                    </template><br />
                    <span v-if="data.item.variation_name">{{ data.item.variation_name }}<br /></span>
                    <span v-if="data.item.sku">SKU: {{ data.item.sku }}</span>
                </template>
                <template v-slot:cell(grand_total)="data">
                    {{ order.currency }} {{ data.item.grand_total ? Number(data.item.grand_total).toFixed(2).toLocaleString() : '-' }}
                </template>

                <!-- custom formatted footer cell -->
                <!-- <template v-slot:foot(name)="data"></template>
                <template v-slot:foot(quantity)="data">
                    Total available refund
                </template><template v-slot:foot(quantity)="data">
                    Total available refund2
                </template>
                <template v-slot:foot(grand_total)="data">
                    {{ order.currency }} {{ available_refund.toFixed(2).toLocaleString() }}
                </template> -->

                <template slot="custom-foot" slot-scope="data">
                    <b-tr variant="secondary">
                        <b-th></b-th>
                        <b-th></b-th>
                        <b-th>Shipping</b-th>
                        <b-th>{{ order.currency }} {{ calculate.shipping }}</b-th>
                    </b-tr>
                    <b-tr variant="secondary">
                        <b-th></b-th>
                        <b-th></b-th>
                        <b-th>Tax</b-th>
                        <b-th>{{ order.currency }} {{ calculate.tax }}</b-th>
                    </b-tr>
                    <b-tr variant="secondary">
                        <b-th></b-th>
                        <b-th></b-th>
                        <b-th>Total Available Refund</b-th>
                        <b-th>{{ order.currency }} {{ calculate.total }}</b-th>
                    </b-tr>
                </template>


            </b-table>

            <h3 class="mt-4">Refund with: Manual</h3>
            <b-form-input v-model="form.manual" :placeholder="order.currency"></b-form-input>

            <h3 class="mt-4">Reason for refund</h3>
            <b-form-input v-model="form.reason"></b-form-input>
            <small>Only you and other staff can see this reason.</small>

            <h3 class="mt-4"></h3>
            <b-form-checkbox
                v-model="form.restock"
                value="true"
                >
                Restock
                </b-form-checkbox>

            <h3 class="mt-4"></h3>
            <b-form-checkbox
                v-model="form.notify"
                :value=true
                :unchecked-value=false
                >
                Send a notification to the customer
                </b-form-checkbox>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeRefund">Cancel</b-button>
                <b-button variant="danger" class="ml-auto" @click="confirmRefund">Refund</b-button>
            </template>

        </b-modal>
    </span>
</template>
<script>
    export default {
        name: "ShopifyRefundOrderComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                reasons: [
                    { value: '', text: '-- Select --', disabled: true },
                    'customer', 'inventory', 'fraud', 'declined', 'other'
                ],
                fields: [{key: 'check', label: ''}, 'name', 'quantity', 'grand_total'],

                form: {
                    selected: [],
                    reason: '',
                    notify: true,
                    restock: true,
                    manual: 0,
                    transactions: []
                },
                available_refund: 0,
                calculate: {
                    total: 0,
                    shipping: 0,
                    tax: 0
                }
            }
        },
        computed: {
            canRefund() {
                if (this.order.data['location_id'] != null) {
                    return true;
                }
                return false;
            },
            items() {
                //return this.order.items
                let items = [];
                for(let item of this.order.items) {
                    if (item.fulfillment_status < 30) {
                        items.push(item)
                    }
                }

                return items;
            }
        },
        methods: {
            refund() {
                this.$refs['refund-order-modal-' + this.order.id].show();
            },
            closeRefund() {
                this.$refs['refund-order-modal-' + this.order.id].hide();
                this.form.selected = [];
            },
            confirmRefund() {
                if (this.form.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to refund.', 'center', 'danger');
                    return;
                }
                if (!this.form.reason) {
                    notify('top', 'Error', 'You need to select the reason to refund.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'refunding order...', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/shopify/refund', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully refunded order!', 'center', 'success');
                        this.closeRefund();
                        typeof this.$parent.$parent.$parent !=='undefined' && typeof this.$parent.$parent.$parent.updateCurrent === 'function' ? this.$parent.$parent.$parent.updateCurrent() : this.$parent.$parent.updateCurrent(this.order.id);
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
            selectItem(item) {
                if (this.form.selected.includes(item.id)) {
                    let index = this.form.selected.indexOf(item.id);
                    this.form.selected.splice(index, 1);
                } else {
                    this.form.selected.push(item.id);
                }

                this.calculateRefund();
                /*axios.post('/web/orders/' + this.order.id + '/shopify/calculateRefund', this.form).then((response) => {
                    console.log(response);
                    this.reset();

                    let refund = response.data.response.refund;
                    this.calculate.shipping = parseFloat(refund.shipping.amount);
                    for (let item of refund.refund_line_items) {
                        this.calculate.tax += parseFloat(item.total_tax);
                        this.calculate.total += parseFloat(item.discounted_total_price);
                    }

                    this.calculate.total += this.calculate.shipping + this.calculate.tax;
                    this.form.manual = this.calculate.total;
                })*/
            },
            onRowSelect(items) {
                this.form.selected = items.map(item => item.id);

                this.calculateRefund();
            },
            calculateRefund() {
                axios.post('/web/orders/' + this.order.id + '/shopify/calculateRefund', this.form).then((response) => {
                    console.log(response);
                    this.reset();

                    let refund = response.data.response.refund;
                    let transactions = [];
                    if (refund) {
                        this.calculate.shipping = parseFloat(refund.shipping.amount);
                        for (let item of refund.refund_line_items) {
                            this.calculate.tax += parseFloat(item.total_tax);
                            this.calculate.total += parseFloat(item.discounted_total_price);
                        }
                        transactions = refund.transactions;
                    }


                    this.calculate.total += this.calculate.shipping + this.calculate.tax;
                    this.form.manual = this.calculate.total;
                    this.form.transactions = transactions;
                })
            },
            reset() {
                this.calculate = {
                    total: 0,
                    shipping: 0,
                    tax: 0
                }
            }
        },
        created() {
            this.available_refund = this.items.map(item => parseFloat(item.grand_total)).reduce((a, b) => a + b, 0)
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>
<style type="text/css">
    #refund-order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
