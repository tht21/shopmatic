<template>
    <span>
        <b-button variant="primary" class="mr-2" @click="fulfillment">Update Status</b-button>

        <b-modal id="order-modal" :ref="'order-modal-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Update Status</h2>
                <button type="button" class="close" @click="close" aria-label="Close">
                    <span aria-hidden="true" class="text-white">×</span>
                </button>
            </template>

            <h3>Select a Status</h3>
            <multiselect
                id="order-status"
                v-model="value"
                :options="options"
                :placeholder="placeholder"
                :show-labels="false"
                :searchable="true"
                :preserve-search="true"
                :internal-search="false"
            >
                <template slot="singleLabel" slot-scope="{ option }"><strong>{{ singleLabel(option, placeholder) }}</strong></template>
                <template slot="option" slot-scope="{ option }">
                    {{ option.name }}
                </template>
                <span slot="noResult">There are currently no actions you can take for this order.</span>
            </multiselect>

            <template v-slot:modal-footer="{ ok, cancel }">
                <b-button variant="link" @click="close">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="update" :disabled="value.id === order.fulfillment_status">Update</b-button>
            </template>
        </b-modal>
    </span>
</template>

<script>
    export default {
        name: "PrestaShopOrderActionComponent",
        props: ['order'],
        data () {
            return {
                value: null,
                placeholder: 'Select a status',
                options: [],
                retrieving: false,
            }
        },
        computed: {
        },
        methods: {
            singleLabel(value, placeholder) {
                // if empty, show placeholder
                if ($.isEmptyObject(value)) {
                    return placeholder;
                }

                // if not empty, show formatted label
                return value['name'];
            },
            fulfillment() {
                this.$refs['order-modal-' + this.order.id].show();
            },
            close() {
                this.getStatus();
                this.$refs['order-modal-' + this.order.id].hide();
            },
            update() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;

                let parameters = {
                    fulfillment_status: this.value.id,
                    fulfillment_status_text: this.value.name,
                };

                notify('top', 'Updating order status..', '', 'center', 'info');
                axios.get('/web/orders/' + this.order.id + '/prestaShop/updateStatus', {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        swal({
                            title: 'Error',
                            text: data.meta.message,
                            type: 'error',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        })
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully updated the order status.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.close();
                            this.$parent.$parent.updateCurrent();
                        });
                    }

                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        swal({
                            title: 'Error',
                            text: error.response.data.meta.message,
                            type: 'error',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        })
                    } else {
                        swal({
                            title: 'Error',
                            text: error,
                            type: 'error',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        })
                    }
                });
            },
            getStatus() {
                let options = [
                    {'id': 1, 'name': 'Processing'},
                    {'id': 10, 'name': 'Ready To Ship'},
                    {'id': 11, 'name': 'Shipped'},
                    {'id': 12, 'name': 'Partially Shipped'},
                    {'id': 20, 'name': 'Delivered'},
                    {'id': 21, 'name': 'To Confirm Delivered'},
                    {'id': 30, 'name': 'Cancelled'},
                    {'id': 31, 'name': 'Request cancel'},
                    {'id': 40, 'name': 'Returned'},
                ];

                this.options = options
                this.value = {
                    'id': this.order.fulfillment_status,
                    'name': this.order.fulfillment_status_text,
                };
            },
        },
        watch: {
            order() {
                this.getStatus();
            },
        },
    }
</script>

<style scoped>
    #order-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
