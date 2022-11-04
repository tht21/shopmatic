<template>
    <span>
        <b-button variant="primary" class="mr-2" @click="openPickup" v-if="canPickup"><i class="fas fa-truck-pickup"></i> Pickup</b-button>

        <b-modal id="pickup-order-modal" :ref="'pickup-order-modal-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Pickup Order</h2>
                <button type="button" class="close" @click="closePickup" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <b-form-group label="Pickup Day" label-for="dayInput">
                <b-form-select id="dayInput" v-model="form.day" :options="options.days" @change="onDayChange"></b-form-select>
            </b-form-group>

            <b-table-simple responsive bordered>
                <b-tr>
                    <b-th variant="dark">Pickup Status</b-th>
                    <b-td>{{ form.status }}</b-td>
                    <b-th variant="dark">Pickup No.</b-th>
                    <b-td>{{ form.id }}</b-td>
                </b-tr>
            </b-table-simple>

            <h3 class="border-bottom pb-2">Request For Pickup</h3>

            <h3 class="mt-4">Quantity of Parcel</h3>
            <b-form-input v-model="form.quantity" placeholder="Enter quantity of parcel"></b-form-input>

            <h3 class="mt-4">Pickup Address</h3>
            <b-form-select v-model="form.pickup_address_no" :options="options.pickupAddress"></b-form-select>

            <h3 class="mt-4">Mobile No</h3>
            <b-form-input v-model="form.mobile_no" placeholder="Enter mobile no"></b-form-input>

            <h3 class="mt-4">Memo</h3>
            <b-form-input v-model="form.memo" placeholder="Memo"></b-form-input>

             <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="closePickup">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="confirmPickup">Create</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "Qoo10_LegacyPickupOrderCompoent",
        props: ['order'],
        data() {
            return {
                sending_request: false,
                type: null,
                form : {
                    //tracking_number: 'pickup', // required by component only
                    request_date:  null,
                    type: null,
                    id: null, // seqno
                    day: null,
                    quantity: 1,
                    pickup_address_no: null,
                    memo: '',
                    mobile_no: null
                },
                options: {
                    days: [],
                    pickupAddress: []
                },

            }
        },
        computed: {
            canPickup() {
                let count = 0;
                this.order.items.forEach(function (item) {
                    if (item.fulfillment_status === 1 && (item.shipment_provider === 'Qxpress' || item.shipment_provider === 'Qprime')) {
                        count++;
                    }
                });
                return count > 0;
            }
        },
        methods: {
            emit () {
                this.$emit('input', this.form)
            },
            openPickup() {
                this.$refs['pickup-order-modal-' + this.order.id].show();
                if (this.canPickup) {
                    this.retrieveLogistic();
                }
            },
            closePickup() {
                this.$refs['pickup-order-modal-' + this.order.id].hide();
                this.form.request_date = null;
                this.form.type = null;
                this.form.id = null;
                this.form.day = null;
                this.form.quantity = 1;
                this.form.pickup_address_no = null;
                this.form.memo = '';
                this.form.mobile_no = null;
            },
            retrieveLogistic() {
                this.options.days = [];
                this.options.pickupAddress = [];
                axios.get('/web/orders/' + this.order.id + '/qoo10_legacy/getLogistic').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.type = data.type;
                        let response = data.response;
                        for (let value of response.data.workDay) {
                            this.options.days.push({
                                value: value,
                                text: value.work_date + ' (' + value.day_nm + ')'
                            })
                        }
                        for (let value of response.data.pickupAddr) {
                            this.options.pickupAddress.push({
                                value: value.addr_no,
                                text: '(' + value.zip_code + ') ' + value.addr_front + ' ' + value.addr_last
                            })
                        }

                        /* init form */
                        this.onDayChange(this.options.days[0].value)
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            onDayChange(value) {
                // When day is changed
                if (value.pickup.length > 0) {
                    this.form = {
                        status: 'Queue',
                        type: 'edit',
                        request_date: value.work_date,
                        id: value.pickup[0].seqno,
                        day: value,
                        quantity: value.pickup[0].cnt,
                        pickup_address_no: value.pickup[0].pickup_addr_no,
                        memo: value.pickup[0].memo,
                        mobile_no: value.pickup[0].hp_no
                    }
                } else {
                    this.form = {
                        status: '',
                        type: 'new',
                        request_date: value.work_date,
                        id: 0,
                        day: value,
                        quantity: 1,
                        pickup_address_no: this.options.pickupAddress[0].value,
                        memo: '',
                        mobile_no: null
                    }
                }
                this.emit();
            },
            confirmPickup() {
                if (this.sending_request) {
                    return;
                }
                if (!this.form.quantity) {
                    notify('top', 'Error', 'Please enter quantity.', 'center', 'danger');
                    return;
                }
                if (!this.form.pickup_address_no) {
                    notify('top', 'Error', 'Please select a pickup address.', 'center', 'danger');
                    return;
                }
                notify('top', 'Info', 'Updating..', 'center', 'info');

                this.sending_request = true;
                axios.post('/web/orders/' + this.order.id + '/qoo10_legacy/pickup', this.form).then((response) => {
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
                            text: 'Successfully updated order pickup!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.closePickup();
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
        }
    }
</script>

<style scoped>
    #pickup-order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
