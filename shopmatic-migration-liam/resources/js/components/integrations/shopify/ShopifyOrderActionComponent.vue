<template>
    <div>
        <div class="row">
            <div class="col-12">
                <shopify-fulfill-order-component :order="order"></shopify-fulfill-order-component>
                <shopify-cancel-order-component :order="order"></shopify-cancel-order-component>
                <shopify-refund-order-component :order="order"></shopify-refund-order-component>
            </div>
            <div class="col-12" v-if="order.fulfillment_status >= 20">
                There are currently no actions you can take for this order.
            </div>
            <div class="col-12 mt-3 action-guide">
                <small><a href="#shopify-help" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="shopify-help">Guide & Help <i class="fas fa-angle-double-down"></i></a></small>
                <div id="shopify-help" class="collapse mt-2">
                    <span class="text-muted">The flow for order processing for Shopify is</span>
                </div>
            </div>
        </div>
        
    </div>
</template>
<script>
    export default {
        name: "ShopifyOrderActionComponent",
        props: [
            'order'
        ],
        data() {
            return {
                
            }
        },
        computed: {
            
        },
        methods: {
            
        },
        created() {

        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>
