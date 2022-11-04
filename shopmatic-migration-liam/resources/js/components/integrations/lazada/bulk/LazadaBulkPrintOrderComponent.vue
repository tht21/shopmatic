<template>
    <span>
        <template v-if="status === 'pending' || status === 'processing' || status === 'ready_to_ship'">
            <b-button variant="info" size="sm" class="mr-0 mt-2" @click="confirmPrintBulk('invoice')" :class="{ disabled: disableBulkPrint() }"><i class="fas fa-print"></i> Print Invoice</b-button>
            <b-button variant="info" class="mt-2"size="sm" @click="confirmPrintBulk('shippingLabel')" :class="{ disabled: disableBulkPrint() }"><i class="fas fa-print"></i> Print Shipping Label</b-button>
        </template>
    </span>
</template>

<script>
    export default {
        name: "LazadaBulkPrintOrderComponent",
        props: ['selected_orders', 'status'],
        data() {
            return {
                sending_request: false,
            }
        },
        methods: {
            disableBulkPrint() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if (item.fulfillment_status === 0 || item.fulfillment_status >= 30) {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            confirmPrintBulk(document) {
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to print.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order.items.every((item) => {
                        if (item.fulfillment_status === 0 || item.fulfillment_status >= 30) {
                            error = true;
                            let order_id = order.external_id ? order.external_id : order.id;
                            notify('top', 'Error', 'Order ['+ order_id +'] does not support print', 'center', 'danger');
                            return false;
                        }
                    });
                });
                if (error) {
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                let order_ids = [];
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order_ids.push(order.id);
                });

                axios.post('/web/orders/bulk/' + order_ids.join(",") + '/lazada/print', {
                    document: document,
                    is_bulk: true
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let w = window.open("about:blank", document);
                        w.document.write(data.response.file);
                        if (document === 'invoice') {
                            w.document.write('<script>window.addEventListener("load", function(){\n' +
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
                    if (error.response && error.response.data && error.response.data.debug[0] && error.response.data.debug[0].message) {
                        notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
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
