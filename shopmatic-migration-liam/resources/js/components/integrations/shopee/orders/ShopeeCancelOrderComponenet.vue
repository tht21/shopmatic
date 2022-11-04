<template>
    <span>
        <b-button variant="danger" class="mr-2" @click="openCancel" v-if="canCancel"><i class="fas fa-times"></i> Cancel</b-button>

        <b-modal id="cancel-order-modal" :ref="'cancel-order-modal-' + this.order.id" size="lg"
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
                    <template v-if="is_out_of_stock">
                        <th style="width: 60px;"></th>
                    </template>
                    <th>Name</th>
                    <th class="text-center">Quantity</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="item in order.items" v-if="item.fulfillment_status === 10" :class="'cursor-pointer ' + (selected_item === item.id ? 'table-success' : '')" @click="selectItem(item)">
                    <template v-if="is_out_of_stock">
                        <td style="width: 60px;">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" :id="'item-id-' + item.id" type="checkbox" :checked="selected_item === item.id" @click="selectItem(item)">
                                <label class="custom-control-label" :for="'item-id-' + item.id"></label>
                            </div>
                        </td>
                    </template>
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

            <h3 class="mt-4">Select Cancel Reason</h3>
            <select name="cancel_reason" v-model="cancel_reason" class="form-control" required>
                <option :value=null selected disabled>-- Please select a cancel reason --</option>
                <option v-for="(value, key) in reasons" :value="key">{{ value }}</option>
            </select>

             <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeCancel">Close</b-button>
                <b-button variant="danger" class="ml-auto" @click="confirmCancel">Cancel Order</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "ShopeeCancelOrderComponenet",
        props: ['order'], /*{
            // can be synced with parent model
            model: {
                type: [Array, Object],
                required: false,
                default: () => []
            },
            order: {
                type: [Array, Object],
                required: true
            },
            clearData: Boolean
        },*/
        data() {
            return {
                //cancel_order: this.model,
                selected_item: null,
                cancel_reason: null,
                reasons: [],
                is_out_of_stock: false,
            }
        },
        computed: {
            canCancel() {
                if (this.order.fulfillment_status === 10) {
                    return true;
                }
                return false;
            },
        },
        methods: {
            openCancel() {
                this.$refs['cancel-order-modal-' + this.order.id].show()
            },
            closeCancel() {
                this.$refs['cancel-order-modal-' + this.order.id].hide();
                this.selected_item = null;
                this.cancel_reason = null;
                this.is_out_of_stock = false;
            },
            retrieveReasons() {
                axios.get('/web/orders/' + this.order.id + '/shopee/reasons').then((response) => {
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
            selectItem(item) {
                // Only out of stock reason need to select item, for shopee only can choose one item
                if (this.cancel_reason === 'OUT_OF_STOCK') {
                    if (this.selected_item === item.id) {
                        this.selected_item = null;
                    } else {
                        this.selected_item = item.id;
                    }
                }
            },
            confirmCancel() {
                if (!this.cancel_reason) {
                    notify('top', 'Error', 'You need to select the reason to cancel.', 'center', 'danger');
                    return;
                }
                // If reason of cancel is out of stock, the item id is required
                if (this.cancel_reason === 'OUT_OF_STOCK') {console.log('test');
                    if (!this.selected_item) {console.log('xcvcx');
                        notify('top', 'Error', 'You need to select one item to cancel.', 'center', 'danger');
                        return;
                    }
                }
                if (this.sending_request) {
                    return;
                }
                notify('top', 'Info', 'Updating..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/shopee/cancel', {
                    cancel_reason: this.cancel_reason,
                    order_item_id: this.selected_item
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        swal({
                            title: 'Error',
                            text: data.meta.message,
                            type: 'error',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-info'
                        })
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'Successfully cancelled order! Order status might take 5 - 10 minutes to get updated.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closeCancel();
                            this.$parent.$parent.$parent.updateCurrent();
                        })
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
        },
        created() {
            if (this.canCancel) {
                this.retrieveReasons();
            }
        },
        watch: {
            cancel_reason: function (val, oldVal) {
                if (val === "OUT_OF_STOCK") {
                    this.is_out_of_stock = true;
                } else {
                    this.is_out_of_stock = false;
                    this.selected_item = null;
                }
            }
        }
    }
</script>

<style scoped>
    #cancel-order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
