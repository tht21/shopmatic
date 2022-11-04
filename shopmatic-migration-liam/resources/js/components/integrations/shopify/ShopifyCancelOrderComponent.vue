<template>
    <span>
        <button type="button" class="btn btn-danger" @click="cancel" v-if="canCancel"><i class="fa fa-times"></i> Cancel</button>

        <b-modal id="cancel-order-modal" :ref="'cancel-order-modal-' + this.order.id" size="lg"
            header-bg-variant="danger" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Cancel Order</h2>
                <button type="button" class="close" @click="closeCancel" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Items</h3>
            <b-table head-variant="light" class="align-items-center" :fields="fields" :items="items" @row-selected="selectItem" selectable select-mode="multi" selected-variant="success" foot-clone>
                <template v-slot:cell(check)="data">
                    <!-- @TODO - improve the checkbox -->
                    <input type="checkbox" :checked="form.selected.includes(data.item.id)" @click="checkboxSelectItem(data.item)"/>
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
                <template v-slot:cell(grand_total)="data">asd
                    {{ order.currency }} {{ data.item.grand_total ? Number(data.item.grand_total).toFixed(2).toLocaleString() : '-' }}
                </template>

                <!-- custom formatted footer cell -->
                <template v-slot:foot(name)="data"></template>
                <template v-slot:foot(quantity)="data">
                    Total available refund
                </template>
                <template v-slot:foot(grand_total)="data">
                    {{ order.currency }} {{ available_refund.toFixed(2).toLocaleString() }}
                </template>
            </b-table>

            <h3 class="mt-4">Refund with: Manual</h3>
            <b-form-input v-model="form.manual" :placeholder="order.currency"></b-form-input>

            <h3 class="mt-4">Select Reason</h3>
            <b-form-select v-model="form.reason" :options="reasons"></b-form-select>

            <h3 class="mt-4">Notes</h3>
            <b-form-textarea
                v-model="form.note"
                placeholder="Optional"
                rows="5"
                max-rows="10"
                ></b-form-textarea>

            <h3 class="mt-4"></h3>
            <b-form-checkbox
                v-model="form.email"
                value="true"
                unchecked-value="not_accepted"
                >
                Send a notification to the customer
                </b-form-checkbox>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeCancel">Cancel</b-button>
                <b-button variant="danger" class="ml-auto" @click="confirmCancel">Cancel</b-button>
            </template>

        </b-modal>
    </span>
</template>
<script>
    export default {
        name: "ShopifyCancelOrderComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                reasons: [
                    { value: '', text: '-- Select --', disabled: true },
                    { value: 'customer', text: 'Customer changed/canceled order' },
                    { value: 'inventory', text: 'Items unavailable' },
                    { value: 'fraud', text: 'Fraudulent order' },
                    { value: 'declined', text: 'Payment declined' },
                    { value: 'other', text: 'Other' }
                ],
                fields: [{key: 'check', label: ''}, 'name', 'quantity', 'grand_total'],

                form: {
                    selected: [],
                    reason: '',
                    note: '',
                    manual: 0,
                    email: true
                },
                available_refund: 0
            }
        },
        computed: {
            canCancel() {
                if (this.order.fulfillment_status <= 10) {
                    return true
                }
                return false;
            },
            items() {
                let items = [];
                for(let item of this.order.items) {
                    if (item.fulfillment_status === 0) {
                        items.push(item)
                    }
                }

                return items
            }
        },
        methods: {
            cancel() {
                this.$refs['cancel-order-modal-' + this.order.id].show();
            },
            closeCancel() {
                this.$refs['cancel-order-modal-' + this.order.id].hide();
                this.form.selected = [];
            },
            confirmCancel() {
                if (this.form.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to cancel.', 'center', 'danger');
                    return;
                }
                if (!this.form.reason) {
                    notify('top', 'Error', 'You need to select the reason to cancel.', 'center', 'danger');
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Cancelling order...', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/shopify/cancel', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully cancelled order!', 'center', 'success');
                        this.closeCancel();
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
            selectItem(items) {
                this.form.selected = items.map(item => item.id)
                this.form.manual = items.map(item => parseFloat(item.grand_total)).reduce((a, b) => a + b, 0)
            },
            checkboxSelectItem(item) {
                if (this.form.selected.includes(item.id)) {
                    let index = this.form.selected.indexOf(item.id);
                    this.form.selected.splice(index, 1);
                } else {
                    this.form.selected.push(item.id);
                }
                //this.form.manual = this.items.map(item => parseFloat(item.grand_total)).reduce((a, b) => a + b, 0)
            },
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
    #cancel-order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
