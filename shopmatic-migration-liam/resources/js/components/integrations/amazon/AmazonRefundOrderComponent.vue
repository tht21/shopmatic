<template>
    <span>
        <button type="button" class="btn btn-danger" @click="openRefund" v-if="canRefund"><i class="fa fa-undo"></i> Refund</button>

        <b-modal id="refund-order-modal" :ref="'refund-order-modal-' + this.order.id" size="lg"
                 header-bg-variant="danger" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Refund Order</h2>
                <button type="button" class="close" @click="closeRefund" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select Reason</h3>
            <b-form-select v-model="form.refund_reason" :options="refund_reasons">
                <b-form-select-option :value="null">Please select a reason</b-form-select-option>
            </b-form-select>

            <h3 class="mt-4">Select Items</h3>
            <table class="table align-items-center table-flush mb-0">
                <template v-for="item in form.adjustment_items">
                    <thead class="thead-light">
                        <tr>
                            <th colspan="2">
                                <b-row>
                                    <b-col>Name: {{ item.name }}</b-col>
                                    <b-col class="text-center">Quantity: {{ item.quantity }}</b-col>
                                </b-row>
                            </th>
                        </tr>
                    </thead>
                    <thead class="thead-light">
                        <tr>
                            <th>Type</th>
                            <th>Amount to refund</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        <template v-for="price_adjustment in item.price_adjustments">
                            <tr>
                                <td>{{ price_adjustment.type }}</td>
                                <td><b-form-input v-model="price_adjustment.amount" placeholder="Enter an amount"></b-form-input></td>
                            </tr>
                        </template>

                    </tbody>
                </template>
            </table>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeRefund">Cancel</b-button>
                <b-button variant="danger" class="ml-auto" @click="confirmRefund">Refund</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "AmazonRefundOrderComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                refund_reasons: [],
                price_adjustment_types: [
                    'Principal', 'Shipping', 'Tax', 'Shipping Tax'
                ],
                form : {
                    refund_reason: null,
                    adjustment_items: []
                }
            }
        },
        computed: {
            canRefund() {
                if (this.order.fulfillment_status >= 11 &&this.order.fulfillment_status <= 12) {
                    return true;
                }
                return false;
            },
        },
        methods: {
            openRefund() {
                this.$refs['refund-order-modal-' + this.order.id].show();
                this.retrieveRefundReasons();
                this.createForm();
            },
            closeRefund() {
                this.$refs['refund-order-modal-' + this.order.id].hide();
                this.form.refund_reason = null;
            },
            retrieveRefundReasons() {
                axios.get('/web/orders/' + this.order.id + '/amazon/refundReasons').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.refund_reasons = data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            createForm() {
                this.order.items.map((item, key) => {
                    this.form.adjustment_items[key] = {
                        id: item.id,
                        name: item.name,
                        quantity: item.quantity,
                        external_id: item.external_id,
                        price_adjustments: []
                    };

                    // Push price adjustment type
                    this.price_adjustment_types.map((price_adjustment_type) => {
                        this.form.adjustment_items[key].price_adjustments.push({
                            type: price_adjustment_type,
                            amount: 0
                        })
                    });
                });
            },
            confirmRefund() {
                if (!this.form.refund_reason) {
                    notify('top', 'Error', 'You need to select a reason to refund order.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Refunding order...', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/amazon/refund', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully refunded order!', 'center', 'success');
                        this.closeRefund();
                        this.$parent.$parent.$parent.updateCurrent();
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
        }
    }
</script>

<style scoped>

</style>
