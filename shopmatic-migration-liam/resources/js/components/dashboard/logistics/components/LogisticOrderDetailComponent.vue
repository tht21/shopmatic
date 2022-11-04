<template>
    <b-card>
        <b-row>
            <b-col md="4">
                <b-card header="Receiver Details" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                    <template class="pt-0 pb-0">
                        <div v-if="order.ship_by_date" class="mb-1">
                            <small class="text-uppercase text-muted">Ship By</small><br/>
                            <p :class="getStatusColor(order) === 'warning' ? 'text-danger font-weight-bold' : ''">{{
                                order.ship_by_date }}</p>
                        </div>
                        <div v-if="order.customer_name" class="mb-1">
                            <small class="text-uppercase text-muted">Customer Name</small><br/>
                            <p>{{ order.customer_name }}</p>
                        </div>
                        <div v-if="order.customer_email" class="mb-1">
                            <small class="text-uppercase text-muted">Customer Email</small><br/>
                            <p>{{ order.customer_email }}</p>
                        </div>
                        <div v-if="order.shipping_address" class="mt-4 mb-1">
                            <small class="text-uppercase text-muted">Shipping Address</small><br/>
                            <template v-if="order.shipping_address.company">{{ order.shipping_address.company
                                }}<br/>
                            </template>
                            <template v-if="order.shipping_address.name">{{ order.shipping_address.name }}<br/>
                            </template>
                            <template v-if="order.shipping_address.address1">{{ order.shipping_address.address1
                                }}<br/>
                            </template>
                            <template v-if="order.shipping_address.address2">{{ order.shipping_address.address2
                                }}<br/>
                            </template>
                            <template
                                v-if="order.shipping_address.address3 && order.shipping_address.country !== order.shipping_address.address3">
                                {{ order.shipping_address.address3 }}<br/></template>
                            <template
                                v-if="order.shipping_address.address4 && order.shipping_address.address4 !== order.shipping_address.address3">
                                {{ order.shipping_address.address4 }}<br/>
                            </template>
                            <template v-if="order.shipping_address.address5">{{ order.shipping_address.address5
                                }}<br/>
                            </template>
                            <template v-if="order.shipping_address.country">{{ order.shipping_address.country
                                }}<br/>
                            </template>
                            <div class="mt-1"
                                 v-if="order.shipping_address.phone_number || order.shipping_address.email ">{{
                                order.shipping_address.phone_number }}<br/>{{order.shipping_address.email }}
                            </div>
                        </div>
                    </template>
                </b-card>
            </b-col>
            <b-col md="8" v-if="selected_order_items || order.items">
                <b-card no-body header="Order Details" header-class="h2 border-bottom-0 pd-0 text-caplitalize">
                    <b-card-body class="pt-0 pb-0">
                        <div id="index-order-items" class="table-responsive">
                            <table class="table align-items-center table-flush mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 10px;" v-if="!selected_order_items"></th>
                                    <th>Name</th>
                                    <th>Weight</th>
                                    <th class="text-right">Total ({{ selected_order_items ? selected_order_items.length
                                        : order.items.length }} items)
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="list">
                                <template
                                    v-for="(item, index) in (selected_order_items ? selected_order_items: order.items)">
                                    <tr>
                                        <td style="width: 10px;" v-if="!selected_order_items">
                                            <b-form-checkbox
                                                :id="'item-'+ index +'-checkbox'"
                                                v-model="checked_order_items"
                                                :name="'item-'+ index +'-checkbox'"
                                                :value="item"
                                            />
                                        </td>
                                        <td>
                                            <p>
                                                <template v-if="item.product">
                                                    <a :href="'/dashboard/products/' + item.product.slug"
                                                       target="_blank">{{item.name }}</a>
                                                </template>
                                                <template v-else>
                                                    {{ item.name }}
                                                </template>
                                                <br/>
                                                <span v-if="item.variation_name">{{ item.variation_name }}</span>
                                            </p>
                                            <span v-if="item.sku">SKU: {{ item.sku }}</span>
                                        </td>
                                        <td><p>{{item.variant ? Number(item.variant.weight).toFixed(2) +' KG' :
                                            'N/A'}}</p></td>
                                        <td class="text-right"><p>{{ order.currency }} {{ item.grand_total ?
                                            Number(item.grand_total).toFixed(2).toLocaleString() : '-' }}<br/>x <strong>{{item.quantity
                                                }}</strong></p></td>
                                    </tr>
                                    <tr v-if="checked_order_items.indexOf(item) >= 0">
                                        <td colspan="4" class="text-white bg-lightest">
                                            <b-row>
                                                <b-col md="6">
                                                    <b-row class="align-items-center pb-2">
                                                        <b-col md="3">
                                                            <label class="text-black">Length</label>
                                                        </b-col>
                                                        <b-col md="9">
                                                            <b-input-group>
                                                                <b-form-input
                                                                    :id="'order-item-length-'+index+'-input'"
                                                                    v-model="checked_order_items[checked_order_items.indexOf(item)].variant.length"
                                                                    placeholder="length"
                                                                    :name="'order-item-length-'+index+'-input'"
                                                                    type="number"
                                                                    :disabled="selected_order_items ? true : false"
                                                                />
                                                                <b-input-group-append>
                                                                    <b-input-group-text>{{getDimensionUnit(checked_order_items[checked_order_items.indexOf(item)].variant.dimension_unit)}}</b-input-group-text>
                                                                </b-input-group-append>
                                                            </b-input-group>
                                                        </b-col>
                                                    </b-row>
                                                    <b-row class="align-items-center pb-2">
                                                        <b-col md="3">
                                                            <label class="text-black">Width</label>
                                                        </b-col>
                                                        <b-col md="9">
                                                            <b-input-group>
                                                                <b-form-input
                                                                    :id="'order-item-width-'+index+'-input'"
                                                                    v-model="checked_order_items[checked_order_items.indexOf(item)].variant.width"
                                                                    placeholder="width"
                                                                    :name="'order-item-width-'+index+'-input'"
                                                                    type="number"
                                                                    :disabled="selected_order_items ? true : false"
                                                                />
                                                                <b-input-group-append>
                                                                    <b-input-group-text>{{getDimensionUnit(checked_order_items[checked_order_items.indexOf(item)].variant.dimension_unit)}}</b-input-group-text>
                                                                </b-input-group-append>
                                                            </b-input-group>
                                                        </b-col>
                                                    </b-row>
                                                </b-col>
                                                <b-col md="6">
                                                    <b-row class="align-items-center pb-2">
                                                        <b-col md="3">
                                                            <label class="text-black">Height</label>
                                                        </b-col>
                                                        <b-col md="9">
                                                            <b-input-group>
                                                                <b-form-input
                                                                    :id="'order-item-height-'+index+'-input'"
                                                                    v-model="checked_order_items[checked_order_items.indexOf(item)].variant.height"
                                                                    placeholder="height"
                                                                    :name="'order-item-height-'+index+'-input'"
                                                                    type="number"
                                                                    :disabled="selected_order_items ? true : false"
                                                                />
                                                                <b-input-group-append>
                                                                    <b-input-group-text>{{getDimensionUnit(checked_order_items[checked_order_items.indexOf(item)].variant.dimension_unit)}}</b-input-group-text>
                                                                </b-input-group-append>
                                                            </b-input-group>
                                                        </b-col>
                                                    </b-row>
                                                    <b-row class="align-items-center pb-2">
                                                        <b-col md="3">
                                                            <label class="text-black">Weight</label>
                                                        </b-col>
                                                        <b-col md="9">
                                                            <b-input-group>
                                                                <b-form-input
                                                                    :id="'order-item-weight-'+index+'-input'"
                                                                    v-model="checked_order_items[checked_order_items.indexOf(item)].variant.weight"
                                                                    placeholder="weight"
                                                                    :name="'order-item-weight-'+index+'-input'"
                                                                    type="number"
                                                                    :disabled="selected_order_items ? true : false"
                                                                />
                                                                <b-input-group-append>
                                                                    <b-input-group-text>{{getWeightUnit(checked_order_items[checked_order_items.indexOf(item)].variant.weight_unit)}}</b-input-group-text>
                                                                </b-input-group-append>
                                                            </b-input-group>
                                                        </b-col>
                                                    </b-row>
                                                </b-col>
                                            </b-row>
                                            <template v-if="item.product && !selected_order_items">
                                                <b-row>
                                                    <b-col md="12">
                                                        <b-form-checkbox
                                                            :id="'item-update-dimensions-'+ index +'-checkbox'"
                                                            v-model="checked_order_items[checked_order_items.indexOf(item)].variant.update"
                                                            :name="'item-update-dimensions-'+ index +'-checkbox'"
                                                            :value="'item-update-dimensions-'+ index +'-checkbox'"
                                                            class="text-black"
                                                        >
                                                            Update product dimensions with these dimensions
                                                        </b-form-checkbox>
                                                    </b-col>
                                                </b-row>
                                                <b-row
                                                    v-if="checked_order_items[checked_order_items.indexOf(item)].variant.update">
                                                    <b-col md="3">
                                                        <button class="btn btn-primary px-5 mt-1"
                                                                @click="updateProduct(item)">Update
                                                        </button>
                                                    </b-col>
                                                </b-row>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>
    </b-card>
</template>

<script>
    export default {
        name: "LogisticOrderDetailsComponent",
        props: {
            order: {
                type: Object,
                default: null,
            },
            selected_order_items: {
                type: Array,
                default: null,
            }
        },
        data() {
            return {
                checked_order_items: [],
            }
        },
        created() {
            if (this.selected_order_items) {
                this.checked_order_items = this.selected_order_items
            }
        },
        watch: {
            checked_order_items: {
                handler() {
                    this.checked_order_items.map((item, index) => {
                        if (item.variant == null) {
                            this.checked_order_items[index].variant = {
                                length: null,
                                width: null,
                                height: null,
                                weight: null,
                            }
                        } else if (item.variant.update == null) {
                            this.checked_order_items[index].update = false
                        }
                    })
                    this.$emit('orderItems', this.checked_order_items)
                },
                deep: true
            },
        },
        methods: {
            getStatusColor(order) {
                switch (order.fulfillment_status) {
                    // Pending
                    case 0:
                    // Processing
                    case 1:
                    // Ready to Ship
                    case 10:
                    // Partially Shipped
                    case 12:
                    // Retry Ship
                    case 13:
                        return 'warning';
                    // Shipped
                    case 11:
                    // Delivered
                    case 20:
                    // Pending Confirmation
                    case 21:
                        return 'success';
                    // Cancelled
                    case 30:
                        return 'danger';
                    default:
                        return 'info';
                }
            },
            getDimensionUnit(unit) {
                switch (unit) {
                    case 0:
                        return 'mm';
                    case 1:
                        return 'cm';
                    case 2:
                        return 'inch';
                    case 3:
                        return 'ft';
                    case 4:
                        return 'm';
                    default:
                        return 'cm';
                }
            },
            getWeightUnit(unit) {
                switch (unit) {
                    case 0:
                        return 'lbs';
                    case 1:
                        return 'g';
                    case 2:
                        return 'kg';
                    case 3:
                        return 'oz';
                    default:
                        return 'g';
                }
            },
            async updateProduct(item) {

                let parameters = {
                    length: item.variant.length,
                    width: item.variant.width,
                    height: item.variant.height,
                    weight: item.variant.weight,
                };

                await axios.put(
                    '/web/products/' + item.product.slug, {
                        variants: parameters
                    }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
        }
    }
</script>

<style scoped>

</style>
