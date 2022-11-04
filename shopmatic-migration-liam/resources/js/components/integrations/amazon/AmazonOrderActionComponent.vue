<template>
    <div>
        <div class="row">
            <div class="col-12">
                <amazon-fulfill-order-component :order="order"></amazon-fulfill-order-component>
                <!--<button type="button" class="btn btn-info" @click="printSlip"><i class="fa fa-times"></i> Print Package Slip</button>-->
                <amazon-cancel-order-component :order="order"></amazon-cancel-order-component>
                <amazon-refund-order-component :order="order"></amazon-refund-order-component>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
            <div class="col-12 mt-3 action-guide">
                <small><a href="#amazon-help" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="amazon-help">Guide & Help <i class="fas fa-angle-double-down"></i></a></small>
                <div id="amazon-help" class="collapse mt-2">
                    <span class="text-muted">The flow for order processing for Amazon is</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import AmazonFulfillOrderComponent from "./AmazonFulfillOrderComponent";
    import AmazonCancelOrderComponent from "./AmazonCancelOrderComponent";
    import AmazonRefundOrderComponent from "./AmazonRefundOrderComponent";
    export default {
        name: "AmazonOrderActionComponent",
        components: {AmazonRefundOrderComponent, AmazonCancelOrderComponent, AmazonFulfillOrderComponent},
        props: ['order'],
        data () {
            return {}
        },
        computed: {
        },
        methods: {
            /*printSlip() {
                axios.get('/web/orders/' + this.order.id + '/amazon/slip').then((response) => {
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
            }*/
        }
    }
</script>

<style scoped>

</style>
