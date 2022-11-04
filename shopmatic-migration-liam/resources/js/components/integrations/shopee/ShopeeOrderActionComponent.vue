<template>
    <div>
        <div class="row">
            <div class="col-12">
                <b-button variant="warning" class="mr-2" @click="buyerCancellation('reject')" v-if="canBuyerCancellation"><i class="fas fa-ban"></i> Reject Cancellation</b-button>
                <b-button variant="success" class="mr-2" @click="buyerCancellation('accept')" v-if="canBuyerCancellation"><i class="fas fa-check"></i> Accept Cancellation</b-button>
                <b-button variant="info" class="mr-2" @click="bill" v-if="canBill"><i class="fas fa-file-invoice"></i> Airway Bill</b-button>
                <shopee-fullfill-order-component :order="this.order"></shopee-fullfill-order-component>
                <shopee-cancel-order-componenet :order="this.order"></shopee-cancel-order-componenet>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
            <div class="col-12 mt-3 action-guide">
                <small><a href="#shopee-help" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="lazada-help">Guide & Help <i class="fas fa-angle-double-down"></i></a></small>
                <div id="shopee-help" class="collapse mt-2">
                    <span class="text-muted">The flow for order processing for Shopee is</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import ShopeeCancelOrderComponenet from "./orders/ShopeeCancelOrderComponenet";
    import ShopeeFullfillOrderComponent from "./orders/ShopeeFullfillOrderComponent";
    export default {
        name: "ShopeeOrderActionComponent",
        components: {ShopeeFullfillOrderComponent, ShopeeCancelOrderComponenet},
        props: ['order'],
        data () {
            return {
                /*cancel_order: [],
                close_order_modal: false,*/
                sending_request: false,
            }
        },
        computed: {
            canBuyerCancellation() {
                if (this.order.fulfillment_status === 31) {
                    return true;
                }
                return false;
            },
            canBill() {
                if (this.order.fulfillment_status === 10 || this.order.fulfillment_status === 13) {
                    return true;
                }
                return false;
            }
        },
        methods: {
            bill() {
                if (this.sending_request) {
                    return;
                }
                notify('top', 'Info', 'Updating..', 'center', 'info');
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/shopee/bill', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (data.response[0].airway_bill) {
                            window.open(data.response[0].airway_bill , '_blank');
                        } else {
                            notify('top', 'Error', 'Unable to get bill url', 'center', 'danger');
                        }
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
            buyerCancellation(action) {
                let title = null;
                let text = null;
                if (action === 'reject') {
                    title = 'Are you sure to reject the cancellation?';
                    text = 'Confirm to reject?';
                } else if (action === 'accept') {
                    title = 'Are you sure to accept the cancellation?';
                    text = 'Confirm to accept?';
                } else {
                    notify('top', 'Error', 'Invalid of cancellation action, please try again', 'center', 'danger');
                    return;
                }

                swal.fire({
                    title: title,
                    text: text,
                    showCancelButton: true,
                    type: 'warning',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm!'
                }).then((result) => {
                    if (result.value) {
                        // Accept or reject the cancellation
                        if (this.sending_request) {
                            return;
                        }
                        notify('top', 'Info', 'Updating..', 'center', 'info');
                        this.sending_request = true;
                        axios.post('/web/orders/' + this.order.id + '/shopee/cancellation', {
                            action: action,
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
                                    text: 'Successfully cancelled order!',
                                    type: 'success',
                                    buttonsStyling: false,
                                    confirmButtonClass: 'btn btn-success'
                                }).then(() => {
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
                    }
                })
            },
        }
    }
</script>

<style scoped>

</style>
