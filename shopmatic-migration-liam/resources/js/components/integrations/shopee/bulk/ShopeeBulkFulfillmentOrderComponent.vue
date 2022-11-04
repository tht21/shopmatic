<template>
    <span>
        <template v-if="status === 'ready_to_ship'">
            <b-button variant="primary" class="mt-2" size="sm" @click="openFulfillment()" :class="{ disabled: disableBulkFulfillment() }"><i class="fas fa-shipping-fast"></i> Fulfillment</b-button>
        </template>

        <b-modal id="fulfillment-order-modal" ref="fulfillment-order-modal" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Fulfillment Order</h2>
                <button type="button" class="close" @click="openFulfillment" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <template v-if="this.logistic_params">
                <template v-if="this.logistic_params.pickup && this.logistic_params.pickup.address_list">
                        <h3 class="mt-4">Shipping Address</h3>
                        <b-form-select v-model="selected_logistic_address">
                            <!-- This slot appears above the options from 'options' prop -->
                            <template v-slot:first>
                                <b-form-select-option :value="null" disabled>-- Logistic Address --</b-form-select-option>
                            </template>
                            <option v-for="address in this.logistic_params.pickup.address_list" :value="address">
                                {{ address.address }}
                            </option>
                        </b-form-select>

                        <h3 class="mt-4">Shipping Pickup Time</h3>
                        <b-form-select
                            v-model="selected_logistic_timeslot"
                        >
                            <!-- This slot appears above the options from 'options' prop -->
                            <template v-slot:first>
                                <b-form-select-option :value="null" disabled>-- Logistic Timeslot --</b-form-select-option>
                            </template>
                            <template v-if="this.selected_logistic_address && this.selected_logistic_address.time_slot_list">
                                <option
                                    v-for="timeslot in this.selected_logistic_address.time_slot_list"
                                    :value="timeslot"
                                >
                                {{ formatDate(timeslot.date) }} - {{ timeslot.time_text }}
                            </option>
                            </template>
                        </b-form-select>
                    </template>
                    <template v-else>
                         <h3 class="mt-4">Confirm to update shipping?</h3>
                    </template>
            </template>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="danger" @click="closeFulfillment">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFulfillment">Fulfillment</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "ShopeeBulkFulfillmentOrderComponent",
        props: ['selected_orders', 'selected_account', 'status'],
        data() {
            return {
                sending_request: false,
                logistic_params: null,
                selected_logistic_address: null,
                selected_logistic_timeslot: null,
                form: {
                    type: null,
                    address_id: null,
                    pickup_time_id: null,
                }
            }
        },
        methods: {
            disableBulkFulfillment() {
                let disable = false;
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length > 0) {
                    Object.values(this.selected_orders[this.status]).map((order) => {
                        order.items.forEach((item) => {
                            if ((item.fulfillment_status !== 10 && item.fulfillment_status !== 13) || item.tracking_number) {
                                disable = true;
                            }
                        });
                    });
                } else {
                    disable = true;
                }
                return disable;
            },
            formatDate(date) {
                return moment.unix(date).format('DD-MM-YYYY');
            },
            closeFulfillment() {
                this.logistic_params = null;
                this.selected_logistic_address = null;
                this.selected_logistic_timeslot = null;
                this.form.type = null;
                this.form.address_id = null;
                this.form.pickup_time_id = null;

                this.$refs['fulfillment-order-modal'].hide();
            },
            openFulfillment() {
                if (this.selected_orders[this.status] && Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to fulfillment.', 'center', 'danger');
                    return;
                }

                let error = false;
                // Check status
                Object.values(this.selected_orders[this.status]).map((order) => {
                    if (order.fulfillment_status !== 10 && order.fulfillment_status !== 13) {
                        error = true;
                        let order_id = order.external_id ? order.external_id : order.id;
                        notify('top', 'Error', 'Order ['+ order_id +'] does not support fulfillment', 'center', 'danger');
                        return false;
                    }
                });

                // All order must be under same logistic
                let logistic = null;
                Object.values(this.selected_orders[this.status]).map((order) => {
                    order.items.every((item) => {
                        if (logistic && logistic != item.shipment_provider) {
                            error = true;
                            notify('top', 'Error', 'Please make sure all the order must be under same logistic', 'center', 'danger');
                            return false;
                        } else {
                            logistic = item.shipment_provider;
                        }
                    });
                });

                if (error) {
                    return;
                }

                // Loop and get all selected order logistic
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/shopee/initInfo', {}).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            if (!this.logistic_params) {
                                this.logistic_params = data.response;
                            }

                            if (data.response.dropoff) {
                                order['selected_logistic'] = 'dropoff';
                            } else if (data.response.pickup) {
                                order['selected_logistic'] = 'pickup';
                            } else if (data.response.non_integrated) {
                                order['selected_logistic'] = 'non_integrated';
                            }
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

                // Check all orders selected logistic (Currently bulk only support for pickup type)
                Promise.all(promisedEvents).then(() => {
                    let error = false;
                    Object.values(this.selected_orders[this.status]).forEach((order) => {
                        if (order.selected_logistic !== 'pickup') {
                            error = true;
                            notify('top', 'Error', 'Order ' + order.id + ' is not pickup mode. Currently bulk action only support order which is pickup mode', 'center', 'danger');
                        }
                    });

                    if (!error) {
                        this.form.type = 'pickup';
                        this.$refs['fulfillment-order-modal'].show();
                    }
                })
            },
            confirmFulfillment() {
                if (this.sending_request) {
                    return;
                }

                // Make sure there is at least one order
                if (!this.selected_orders[this.status] || Object.values(this.selected_orders[this.status]).length <= 0) {
                    notify('top', 'Error', 'You need to select at least one order to fulfillment.', 'center', 'danger');
                    return;
                }

                if (!this.selected_logistic_address || !this.selected_logistic_timeslot) {
                    notify('top', 'Error', 'Please select shipping address and timeslot', 'center', 'danger');
                    return;
                }

                this.form.address_id = this.selected_logistic_address.address_id;
                this.form.pickup_time_id = this.selected_logistic_timeslot.pickup_time_id;
                this.sending_request = true;

                notify('top', 'Info', 'Updating orders...', 'center', 'info');
                // Loop and update all selected order to cancel
                let promisedEvents = [];

                Object.values(this.selected_orders[this.status]).forEach((order) => {
                    promisedEvents.push(axios.post('/web/orders/' + order.id + '/shopee/fulfillment', this.form).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully fulfilled order! '+ order.id, 'center', 'success');
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

                    this.closeFulfillment();
                    this.$emit('update:selected_orders', {});
                    this.$parent.$parent.$parent.$parent.selectAccount(this.selected_account);
                })
            },
        }
    }
</script>

<style scoped>

</style>
