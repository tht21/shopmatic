<template>
    <span>
        <template v-if="status === 'pending'">
            <b-button variant="primary" class="mt-2" size="sm" @click="openPackModel()" :class="{ disabled: disableBulkPack() }"><i class="fas fa-box-open"></i> Ready to Pack</b-button>
        </template>

        <b-modal id="pack-order-modal" ref="pack-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Pack Order</h2>
                <button type="button" class="close" @click="closePack" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Selected Orders</h3>
            <b-list-group>
                <template v-for="order in this.selected_orders[this.status]">
                    <b-list-group-item>ID: {{ order.external_id ? order.external_id : order.id }}</b-list-group-item>
                </template>
            </b-list-group>

            <h3 class="mt-4">Select Shipment Provider</h3>
            <table class="table align-items-center table-flush mb-0">
                <thead class="thead-light">
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Name</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="(item, index) in providers" :class="'cursor-pointer ' + (selected_provider === item.name ? 'table-success' : '')" @click="selected_provider = item.name">
                    <td style="width: 60px;">
                        <div class="custom-control custom-radio">
                            <input name="radio-providers" class="custom-control-input" :id="'provider-' + index" type="radio" :checked="selected_provider === item.name">
                            <label class="custom-control-label" :for="'provider-' + index"></label>
                        </div>
                    </td>
                    <td>{{ item.name }}</td>
                </tr>
                </tbody>
            </table>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closePack">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmPack">Pack</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "LazadaBulkPackOrderComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
                providers: [],
                selected_provider: null
            }
        },
        methods: {
            disableBulkPack() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if (item.fulfillment_status !== 0) {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            closePack() {
                this.providers = [];
                this.selected_provider = null;
                this.$refs['pack-order-modal'].hide();
            },
            openPackModel() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to pack.', 'center', 'danger');
                    return;
                }

                let error = false;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order.items.every((item) => {
                        if (item.fulfillment_status !== 0) {
                            error = true;
                            let order_id = order.external_id ? order.external_id : order.id;
                            notify('top', 'Error', 'Order ['+ order_id +'] does not support pack order', 'center', 'danger');
                            return false;
                        }
                    });
                });

                if (error) {
                    return;
                }

                // Retrieve shipping providers and add selected item/provider
                Object.values(this.selected_orders[this.status]).map(async (order) => {
                    if (this.providers.length <= 0) {
                        this.providers = await this.retrieveShippingProviders(order);
                    }
                });

                this.$refs['pack-order-modal'].show();
            },
            retrieveShippingProviders(order) {
                return axios.get('/web/orders/' + order.id + '/lazada/providers').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        return data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            confirmPack() {
                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to pack.', 'center', 'danger');
                    return;
                }

                if (!this.selected_provider) {
                    notify('top', 'Error', 'You need to select a shipment provider.', 'center', 'danger');
                    return;
                }

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                // Loop and update all selected order to pack
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    let selected_items = [];
                    order.items.forEach((item) => {
                        selected_items.push(item.id);
                    });

                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/lazada/pack', {
                        order_item_ids: selected_items,
                        provider: this.selected_provider,
                    }).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully updated order ID '+ order.id +'!', 'center', 'success');
                        }
                    }).catch((error) => {
                        if (error.response && error.response.data && error.response.data.debug[0] && error.response.data.debug[0].message) {
                            notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                        } else if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }

                    }));
                });

                // Close model and refresh once all is updated
                Promise.all(promisedEvents).then(() => {
                    this.sending_request = false;

                    this.closePack();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
        }
    }
</script>

<style scoped>

</style>
