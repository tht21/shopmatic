<template>
    <div class="container-fluid p-3 px-md-5 py-md-4" v-if="inventory">
        <span @click="closeInventory()" class="closing-right-button">&times;</span>
        <h1 class="font-weight-light">Managing Inventory for {{ inventory.sku }}
            <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
            <small v-if="this.updating">Updating..</small>
            <button class="btn btn-sm btn-success" data-target="#add" data-toggle="collapse">Add Product to Bundle</button>
        </h1>
        <div id="add" class="collapse">
            <div class="p-3">
                <form id="update-inventory" v-on:submit.prevent="addBundle()">
                    <div class="border-default p-3">
                        <div class="row">
                            <div class="col-6">
                                <h2>Bundle Product Inventory<br/><small><i>Inventory to be bundled.</i></small></h2>

                                <async-multiselect
                                    type="single_select_inventory"
                                    id="inventory-input"
                                    :model.sync="form.deduct_product_inventory_id"
                                    placeholder="-- Select a inventory --"
                                />

                            </div>
                            <div class="col-6">
                                <h2>Deduct amount<br/><small><i>Amount to be deducted.</i></small></h2>
                                <input type="number" v-model="form.deduct_amount" name="deduct_amount"
                                       placeholder="Enter quantity" class="form-control font-size-20 text-black" min="1"
                                       required/>
                            </div>
                        </div>
                        <button class="btn btn-success full-width mt-5">Add</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="nav-wrapper">
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0 active" id="inventories-tab" data-toggle="tab" href="#inventories-panel" role="tab" aria-controls="inventories-panel" aria-selected="true"><i class="ni ni-basket mr-2"></i>Inventories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0" id="listings-tab" data-toggle="tab" href="#listings-panel" role="tab" aria-controls="listings-panel" aria-selected="true"><i class="ni ni-key-25 mr-2"></i>Listings</a>
                </li>
            </ul>
        </div>
        <div class="card shadow mt-3">
            <div class="card-body p-0">
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="inventories-panel" role="tabpanel"
                         aria-labelledby="inventories-tab">
                        <b-table
                            show-empty
                            thead-class="thead-light"
                            :fields="fields.bundled_inventories"
                            :items="inventory.bundled_inventories">

                            <template v-slot:cell(sku)="data">
                                <h3>{{ data.item.sku }}</h3><small>{{ data.item.name }}</small>
                            </template>

                            <template v-slot:cell(action)="data">
                                <b-button
                                    size="sm"
                                    @click="selectBundle(data.item)"
                                    v-if="product_inventory_update.id != data.item.id">Edit
                                </b-button>
                                <b-button
                                    variant="danger" size="sm"
                                    @click="removeBundle(data.item)">Remove
                                </b-button>
                            </template>

                            <template v-slot:cell(deduct_amount)="data">
                                <template v-if="product_inventory_update.id != data.item.id">
                                    {{ data.item.pivot.deduct_amount }}
                                </template>

                                <template v-else>
                                    <input type="number" min="0" class="form-control mt-3" placeholder="Stock"
                                           v-model="product_inventory_update.deduct_amount"/>

                                    <b-button
                                        class="btn-white mt-2"
                                        size="sm"
                                        @click="updateBundle">Update
                                    </b-button>
                                </template>

                            </template>

                            <template v-slot:empty="scope">
                                <h3 class="text-muted text-center font-weight-light py-3">There is no listings using
                                    this inventory.</h3>
                            </template>
                        </b-table>
                    </div>

                    <div class="tab-pane fade" id="listings-panel" role="tabpanel"
                         aria-labelledby="listigns-tab">
                        <b-table
                            show-empty
                            thead-class="thead-light"
                            :fields="fields.listings"
                            :items="inventory.listings">

                            <template v-slot:cell(account)="data">
                                {{ data.item.account.name }}<br /><small>{{ data.item.integration.name }}</small>
                                
                            </template>

                            <template v-slot:cell(price)="data">
                                {{ data.item.variant.currency }} {{ data.item.variant.price }}
                            </template>

                            <template v-slot:cell(status)="data">
                                <small class="px-3 badge badge-success" v-if="data.item.status_text === 'LIVE'">{{ data.item.status_text }}</small>
                                    <small class="px-3 badge badge-danger" v-else-if="data.item.status_text === 'DISABLED' || data.item.status_text === 'OUT OF STOCK' || data.item.status_text === 'DELETED' || data.item.status_text === 'BANNED'">{{ data.item.status_text }}</small>
                                    <small class="px-3 badge badge-secondary" v-else>{{ data.item.status_text }}</small>
                            </template>

                            <template v-slot:empty="scope">
                                <h3 class="text-muted text-center font-weight-light py-3">There is no listings using
                                    this inventory.</h3>
                            </template>
                        </b-table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: "InventoryCompositeFormComponent",
        props: ['selected'],
        data() {
            return {
                data: [],
                inventory: null,
                request_url: '/web/inventory/',
                sending_request: false,
                updating: false,
                form: {
                    deduct_product_inventory_id: {},
                    deduct_amount: 0
                },
                fields: {
                    bundled_inventories: [
                        'sku',
                        'stock',
                        'deduct_amount',
                        'action'
                    ],
                    listings: [
                        { label: 'id', key: 'identifier_text'},
                        'account',
                        { label: 'sold', key: 'total_sold'},
                        'price',
                        'status'
                    ]
                },
                product_inventory_update: {
                    id: null,
                    deduct_amount: 0
                }
            }
        },
        created() {
            this.inventory = this.selected
        },
        methods: {
            closeInventory() {
                if (this.sending_request) {
                    notify('top', 'Error', 'The inventory is still updating.. Please wait.', 'center', 'danger');
                    return;
                }
                this.inventory = null;

                this.$emit('update', null)
            },
            retrieve() {
                if (this.updating || !this.inventory) {
                    return;
                }
                this.updating = true;
                axios.get(this.request_url + this.inventory.id).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.inventory = data.response;
                        this.$set(this.inventory, 'bundled_inventories', data.response.bundled_inventories)
                    }

                    this.updating = false;
                }).catch((error) => {
                    this.updating = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            addBundle() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Adding bundled inventory..', 'center', 'info');

                this.postRequest(this.request_url + this.inventory.id + '/bundle', this.form, 'Successfully added the bundled inventory.')
            },
            selectBundle(inventory) {
                this.product_inventory_update.id = inventory.id
                this.product_inventory_update.deduct_amount = inventory.pivot.deduct_amount
            },
            updateBundle() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                let parameters = {
                    _method: 'PUT',
                    deduct_product_inventory: this.product_inventory_update.id,
                    deduct_amount: this.product_inventory_update.deduct_amount
                }

                notify('top', 'Info', 'Updating bundled inventory..', 'center', 'info');

                this.postRequest(this.request_url + this.inventory.id + '/bundle', parameters, 'Successfully updated the bundled inventory.')
                this.product_inventory_update = {
                    id: null,
                    deduct_amount: 0
                }
            },
            removeBundle(inventory) {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                // @TODO - swal confirmation

                let parameters = {
                    deduct_product_inventory: inventory
                }

                swal({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    type: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                        notify('top', 'Info', 'Removing bundled inventory..', 'center', 'info');

                        this.postRequest(this.request_url + this.inventory.id + '/bundle/remove', parameters, 'Successfully removed the bundled inventory.')

                    } else {
                        this.sending_request = false;
                    }
                })


            },
            postRequest(request_url, parameters, success_message) {
                axios.post(request_url, parameters).then((response) => {
                    let data = response.data;
                    this.sending_request = false;

                    if (data.meta.error) {
                        swal({
                            title: 'Error',
                            text: data.meta.message,
                            type: 'danger',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-danger'
                        })
                    } else {
                        this.retrieve()
                        swal({
                            title: 'Success',
                            text: success_message,
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        })
                    }
                }).catch((error) => {
                    this.sending_request = false;
                    let msg = ''
                    if (error.response && error.response.data && error.response.data.meta) {
                        msg = error.response.data.meta.message
                    } else {
                        msg = error
                    }

                    swal({
                        title: 'Error',
                        text: msg,
                        type: 'danger',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    })
                });
            }
        }
    }
</script>
