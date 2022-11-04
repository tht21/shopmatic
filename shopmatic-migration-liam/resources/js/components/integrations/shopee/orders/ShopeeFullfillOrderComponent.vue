<template>
    <span>
        <b-button variant="primary" class="mr-2" @click="initInfo()" v-if="canFulfill"><i class="fas fa-shipping-fast"></i> Fulfillment</b-button>

        <b-modal id="order-logistic-modal" :ref="'order-logistic-modal-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Update Shipment</h2>
                <button type="button" class="close" @click="closeFulfill" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <template v-if="this.logistic_params">
                <!-- Logistic Types -->
                <template v-if="this.logistic_types">
                    <b-form-select v-model="selected_logistic">
                            <!-- This slot appears above the options from 'options' prop -->
                            <template v-slot:first>
                                <b-form-select-option :value="null" disabled>-- Logistic Type --</b-form-select-option>
                            </template>
                            <option v-for="logistic_type in this.logistic_types" :value="logistic_type">
                                {{ logistic_type }}
                            </option>
                        </b-form-select>
                </template>

                <!-- Pickup Type -->
                <template v-if="this.selected_logistic === 'pickup'">
                    <template v-if="this.logistic_params.info_needed.pickup.includes('address_id') && this.logistic_params.pickup.address_list">
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
                <!-- End of Pickup Type -->

                <!-- Dropoff Type -->
                <template v-else-if="this.selected_logistic === 'dropoff'">
                    <template v-if="this.logistic_params.info_needed.dropoff.includes('branch_id') && this.logistic_params.dropoff.branch_list">
                        <h3 class="mt-4">Drop Off Branch</h3>
                        <b-form-select
                            v-model="selected_dropoff_branch"
                        >
                            <!-- This slot appears above the options from 'options' prop -->
                            <template v-slot:first>
                                <b-form-select-option :value="null" disabled>-- Branch --</b-form-select-option>
                            </template>
                             <option v-for="branch in this.logistic_params.dropoff.branch_list" :value="branch">
                                {{ branch.country  }} - ({{ branch.address }})
                            </option>
                        </b-form-select>
                    </template>

                    <template v-if="this.logistic_params.info_needed.dropoff.includes('sender_real_name')">
                        <h3 class="mt-4">Sender Real Name</h3>
                        <b-form-input v-model="sender_real_name" placeholder="Enter sender real name"></b-form-input>
                    </template>

                    <template v-if="this.logistic_params.info_needed.dropoff.includes('tracking_no')">
                         <h3 class="mt-4">Tracking No</h3>
                        <b-form-input v-model="tracking_no" placeholder="Enter tracking number"></b-form-input>
                    </template>

                    <template v-if="this.logistic_params.info_needed.dropoff.length <= 0">
                        <h3 class="mt-4">Confirm to update shipping?</h3>
                    </template>
                </template>
                <!-- End of Dropoff Type -->

                <!-- Non Integrated Type -->
                <template v-else-if="this.selected_logistic === 'non_integrated'">
                    <template>
                         <h3 class="mt-4">Tracking No</h3>
                        <b-form-input v-model="tracking_no" placeholder="Enter tracking number (Optional)"></b-form-input>
                    </template>
                </template>
                <!-- End of Non Integrated Type -->
            </template>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closeFulfill">Cancel</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmFulfill">Update Shipping</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "ShopeeOrderLogisticComponent",
        props: ['order'],
        data () {
            return {
                logistic_params: null,
                logistic_types: [],
                selected_logistic: null,
                selected_logistic_address: null,
                selected_logistic_timeslot: null,
                selected_dropoff_branch: null,
                sender_real_name: null,
                tracking_no: null
            }
        },
        computed: {
            canFulfill() {
                let count = 0;
                this.order.items.forEach(function(item) {
                    if ((item.fulfillment_status === 10 || item.fulfillment_status === 13) && !item.tracking_number) {
                        count++;
                    }
                });
                return count > 0;
            },
        },
        methods: {
            formatDate(date) {
                return moment.unix(date).format('DD-MM-YYYY');
            },
            closeFulfill() {
                this.$refs['order-logistic-modal-' + this.order.id].hide();
                this.selected_logistic_type = null;
                this.selected_logistic = null;
                this.selected_logistic_address = null;
                this.selected_logistic_timeslot = null;
                this.selected_dropoff_branch = null;
                this.sender_real_name = null;
                this.tracking_no = null;
            },
            initInfo() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/shopee/initInfo', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.logistic_params = data.response;
                        this.logistic_types = Object.keys(this.logistic_params.info_needed);
                        /*if (this.logistic_params.dropoff) {
                            this.selected_logistic = 'dropoff';
                        } else if (this.logistic_params.pickup) {
                            this.selected_logistic = 'pickup';
                        } else if (this.logistic_params.non_integrated) {
                            this.selected_logistic = 'non_integrated';
                        }*/
                        this.$refs['order-logistic-modal-' + this.order.id].show();
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
            confirmFulfill() {
                if (this.sending_request) {
                    return;
                }
                notify('top', 'Info', 'Updating..', 'center', 'info');
                this.sending_request = true;
                // Logistic type validation
                if (this.selected_logistic !== 'dropoff' && this.selected_logistic !== 'pickup' && this.selected_logistic !== 'non_integrated') {
                    notify('top', 'Error', 'Invalid type of shipping', 'center', 'danger');
                    this.sending_request = false;
                    return;
                }

                let parameters = {
                    type: this.selected_logistic
                };

                // Pickup type
                if (this.selected_logistic === 'pickup') {
                    if (this.logistic_params.pickup.address_list) {
                        if (!this.selected_logistic_address || !this.selected_logistic_timeslot) {
                            notify('top', 'Error', 'Please select shipping address and timeslot', 'center', 'danger');
                            this.sending_request = false;
                            return;
                        }
                        parameters['address_id'] = this.selected_logistic_address.address_id;
                        parameters['pickup_time_id'] = this.selected_logistic_timeslot.pickup_time_id;
                    }
                } else if (this.selected_logistic === 'dropoff') { // Dropoff type
                    if (this.logistic_params.dropoff.branch_list) {
                        if (!this.selected_dropoff_branch) {
                            notify('top', 'Error', 'Please select a branch', 'center', 'danger');
                            this.sending_request = false;
                            return;
                        }
                        parameters['branch_id'] = this.selected_dropoff_branch.branch_id;
                    }

                    if (this.logistic_params.dropoff.sender_real_name) {
                        if (!this.sender_real_name) {
                            notify('top', 'Error', 'Please enter sender real name', 'center', 'danger');
                            this.sending_request = false;
                            return;
                        }
                        parameters['sender_real_name'] = this.sender_real_name;
                    }
                    if (this.logistic_params.dropoff.tracking_no) {
                        if (!this.tracking_no) {
                            notify('top', 'Error', 'Please enter tracking no', 'center', 'danger');
                            this.sending_request = false;
                            return;
                        }
                        parameters['tracking_no'] = this.tracking_no;
                    }
                } else if (this.selected_logistic === 'non_integrated') {
                    if (this.tracking_no != '') {
                        parameters['tracking_no'] = this.tracking_no;
                    }
                }

                axios.post('/web/orders/' + this.order.id + '/shopee/fulfillment', parameters).then((response) => {
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
                            text: 'Successfully fulfilled order, please wait approx 5 minutes to update the order status!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closeFulfill();
                            this.$parent.$parent.$parent.updateCurrent();
                        })
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    if (typeof error.response != 'undefined' && typeof error.response.data != 'undefined' && typeof error.response.data.debug != 'undefined') {
                        notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (typeof error.meta != 'undefined' && typeof error.meta.message != 'undefined') {
                        notify('top', 'Error', error.meta.message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            }
        }
    }
</script>

<style scoped>
    #order-logistic-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
