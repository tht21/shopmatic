<template>
    <span>
        <button type="button" class="btn btn-primary" @click="fulfillment" v-if="canFulfill"><i class="fas fa-check"></i> Fulfillment</button>

        <b-modal id="fulfill-order-modal" :ref="'fulfill-order-modal-' + this.order.id" size="lg"
            header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Fulfill Order</h2>
                <button type="button" class="close" @click="closeFulfill" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>


            <template v-if="order.shipping_address === null">
                <b-alert show variant="warning">This order does not have a shipping address and will be marked as manually fulfilled.</b-alert>
                <h3 class="mt-4">No shipping required</h3>
            </template>

            <h3>Select Items</h3>
            <b-table head-variant="light" class="align-items-center" :fields="fields" :items="items" @row-selected="selectItem" selectable select-mode="multi" selected-variant="success">
                <template v-slot:cell(check)="data">
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

            </b-table>

            <h3 class="mt-4">Tracking Number</h3>
            <b-form-input v-model="form.tracking_number" placeholder=""></b-form-input>

            <template v-if="order.shipping_address != null">
                <h3 class="mt-4">Shipping Carrier</h3>
                <b-form-select v-model="form.tracking_company" :options="tracking_company"></b-form-select>

                <template v-if="form.tracking_company === 'Other'">
                    <h3 class="mt-4">Tracking Url</h3>
                    <b-form-input v-model="form.tracking_url" placeholder=""></b-form-input>
                </template>

                <h3 class="mt-4">Notify customer of shipment</h3>
                <b-form-checkbox
                    v-model="form.notify_customer"
                    :value=true
                    :unchecked-value=false
                    >
                    Send shipment details to your customer now
                    </b-form-checkbox>
            </template>


            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeFulfill">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFulfill">Fulfill</b-button>
            </template>

        </b-modal>
    </span>
</template>
<script>
    export default {
        name: "ShopifyFulfillOrderComponent",
        props: [
            'order'
        ],
        data() {
            return {
                sending_request: false,
                fields: [{key: 'check', label: ''}, 'name', 'quantity'],

                form: {
                    selected: [],
                    tracking_number: '',
                    tracking_company: '',
                    tracking_url: '',
                    notify_customer: true
                },

                tracking_company: ['None','4PX','APC','Amazon Logistics UK','Amazon Logistics US','Anjun Logistics','Australia Post','Bluedart','Canada Post','China Post','Chukou1','Correios','DHL Express','DHL eCommerce','DHL eCommerce Asia','DPD','DPD Local','DPD UK','Delhivery','Eagle','FSC','FedEx','GLS','GLS (US)','Globegistics','Japan Post (EN)','Japan Post (JA)','La Poste','New Zealand Post','Newgistics','PostNL','PostNord','Purolator','Royal Mail','SF Express','SFC Fulfillment','Sagawa (EN)','Sagawa (JA)','Singapore Post','TNT','UPS','USPS','Whistl','Yamato (EN)','Yamato (JA)','YunExpress','Other']
            }
        },
        computed: {
            canFulfill() {
                let count = 0;
                this.order.items.forEach((item) => {
                    if (item.fulfillment_status <= 10) {
                        count++;
                    }
                });
                if (this.order.fulfillment_status >= 30) {
                    return false;
                }
                return count > 0;
            },
            items() {
                let items = []
                for(let item of this.order.items) {
                    if (item.fulfillment_status <= 10) {
                        items.push(item)
                    }
                }

                return items
            }
        },
        methods: {
            fulfillment() {
                this.$refs['fulfill-order-modal-' + this.order.id].show();
            },
            closeFulfill() {
                this.$refs['fulfill-order-modal-' + this.order.id].hide();
                this.form.selected = [];
            },
            confirmFulfill() {
                if (this.form.selected.length === 0) {
                    notify('top', 'Error', 'You need to select at least one item to fulfill.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Fulfilling order...', 'center', 'info');

                axios.post('/web/orders/' + this.order.id + '/shopify/fulfillment', this.form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully fulfilled order!', 'center', 'success');
                        this.closeFulfill();
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
            },
        },
        created() {

        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }
</script>
<style type="text/css">
    #fulfill-order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
