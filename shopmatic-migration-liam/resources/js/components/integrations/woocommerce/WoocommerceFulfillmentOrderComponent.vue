<template>
    <span>
        <b-button variant="primary" class="mr-2" @click="openModal">Update Status</b-button>

        <b-modal id="order-fulfillment-modal" :ref="'order-fulfillment-modal-' + this.order.id" size="lg"
                 header-bg-variant="primary" hide-backdrop no-close-on-backdrop no-close-on-esc
                 no-enforce-focus>

            <template v-slot:modal-header="{ close }">
                <h2 class="mb-0 text-white">Update Status</h2>
                <button type="button" class="close" @click="closeModal" aria-label="Close">
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
                <b-button variant="link" @click="closeModal">Close</b-button>
                <b-button variant="primary" class="ml-auto" @click="fulfillment">Update</b-button>
            </template>
        </b-modal>

    </span>
</template>

<script>
    export default {
        name: "WoocommerceFulfillmentOrderComponent",
        props: ['order'],
        data() {
            return {
                sending_request: false,
                value: null,
                placeholder: 'Select a status',
                options: [
                    {'id': 0, 'name': 'Pending'},
                    {'id': 1, 'name': 'Processing'},
                    {'id': 5, 'name': 'On Hold'},
                    {'id': 11, 'name': 'Shipped'},
                ],
            }
        },
        watch: {
            order() {

                let result = this.options.filter((option) => {
                    return option.id === this.order.fulfillment_status;
                })
                if(result.length > 0) {
                    this.value = result[0];
                }
            },
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
            openModal() {
                this.$refs['order-fulfillment-modal-' + this.order.id].show();
            },
            closeModal() {
                this.$refs['order-fulfillment-modal-' + this.order.id].hide();
            },
            fulfillment() {

                if(!this.value) {
                    notify('top', 'Error', 'Please select a status', 'center', 'danger');
                    return;
                }

                let title = 'Are you sure to update the order?';
                let text = 'Confirm to update status?';

                swal.fire({
                    title: title,
                    text: text,
                    showCancelButton: true,
                    type: 'warning',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm!'
                }).then((result) => {
                    if (result.value) {
                        // Update order's fulfillment status
                        if (this.sending_request) {
                            return;
                        }
                        this.sending_request = true;

                        let parameters = {
                            fulfillment_status: this.value.id,
                        };

                        axios.post('/web/orders/' + this.order.id + '/woocommerce/fulfillment', parameters).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Successfully updated order!', 'center', 'success');
                                this.$parent.$parent.updateCurrent();
                                this.closeModal();
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
                })
            }
        },
    }
</script>

<style scoped>
    #order-fulfillment-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
</style>
