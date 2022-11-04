<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">All Accounts
                <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3" v-for="account in data">
                    <div class="card">
                        <!-- Card body -->
                        <div class="card-body">
                            <div class="row justify-content-between align-items-center">
                                <div class="col">
                                    <img :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'"
                                         class="account-integration-logo" :title="account.id"/>
                                </div>
                                <div class="col-auto text-center">
                                    <span v-if="account.status === 0" class="badge badge-lg badge-success">Active</span>
                                    <template v-if="account.status === 30">
                                        <span class="badge badge-lg badge-danger">Inactive</span><br/>
                                        <a :href="'/dashboard/accounts/' + account.id + '/reactivate'"
                                           class="small text-uppercase">Reactivate</a>
                                    </template>
                                    <span v-if="account.status === 40" class="badge badge-lg badge-dark text-white">Disabled</span>
                                </div>
                            </div>
                            <div class="mt-4 mb-3">
                            <span class="h6 surtitle text-muted">
                              Name
                            </span>
                                <div class="h1">{{ account.name}}</div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <span class="h6 surtitle text-muted">Region (Currency)</span>
                                    <span class="d-block h3">{{ account.region.name }} ({{ account.currency }})</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
<!--                            <span class="float-right cursor-pointer " @click="deleteConfirmation(account)"><i-->
<!--                                class="fa fa-trash text-muted"></i></span>-->
                            <span class="float-right cursor-pointer mr-2" @click="accountSetting(account)"><i
                                class="fa fa-cog text-muted"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer py-4 text-center text-muted text-uppercase">
            {{ data.length }} account(s)
        </div>

        <!-- Account Setting Modal -->
        <b-modal ref="account-setting" hide-footer size="lg" header-bg-variant="primary">
            <template v-slot:modal-title>
                <h3 class="mt-2 text-white">Account Setting for: {{ selected_account.name }}</h3>
            </template>
            <account-setting-component :account="selected_account" :is_modal="true" @closeModel="closeAccountSettingModel"></account-setting-component>
        </b-modal>

        <!-- Delete Account Modal -->
        <b-modal id="delete-account-modal" ref="delete-account-modal" header-bg-variant="danger" hide-footer>
            <template v-slot:modal-title>
                <h3 class="mt-2 text-white">Delete Account for: {{ (selected_account) ? selected_account.name : '' }}</h3>
            </template>

            <h3>Are you sure to delete?</h3>
            <!--<b-form-group label="Delete account settings">
                <b-form-checkbox-group
                    v-model="selected_delete_options"
                    :options="delete_options"
                    class="mb-3"
                ></b-form-checkbox-group>
            </b-form-group>-->

            <div class="mt-3">
                <b-button variant="link" @click="closeCancel()">Close</b-button>
                <b-button variant="danger" class="ml-auto float-right" @click="confirmDelete()">Delete</b-button>
            </div>
        </b-modal>
    </div>
</template>
<script>
    import AccountSettingComponent from "./component/AccountSettingComponent";
    export default {
        name: "AccountComponent",
        components: {AccountSettingComponent},
        props: [],
        data() {
            return {
                request_url: '/web/accounts',
                data: [],
                settings: [],
                selected_account: null,
                saving: false,
                sending_request: false,
                delete_options: [
                    //{ text: 'Disable all integration products', value: 'disable_integration_products' },
                    { text: 'Delete all integration products', value: 'delete_integration_products', html: 'Delete all integration products <br/><span class="text-warning">(This action cannot be reversed!)</span>' },
                ],
                selected_delete_options: null
            }
        },
        methods: {
            retrieve() {
                this.data = [];
                axios.get(this.request_url, {
                    params: this.parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.data = data.response.items;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            accountSetting(account) {
                this.selected_account = account;
                this.$refs['account-setting'].show();
            },
            closeAccountSettingModel() {
                this.$refs['account-setting'].hide();
                this.retrieve()
            },
            // deleteConfirmation(account) {
            //     this.selected_account = account;
            //     this.$refs['delete-account-modal'].show();
            // },
            closeCancel() {
                this.$refs['delete-account-modal'].hide();
                this.selected_account = null;
                this.selected_delete_options = null;
            },
            // confirmDelete() {
            //     if (this.sending_request) {
            //         return;
            //     }
            //     this.saving = true;
            //     let parameters = {
            //         action: 'delete_account',
            //     };
            //     if (this.selected_delete_options) {
            //         parameters['options'] = this.selected_delete_options;
            //     }
            //     notify('top', 'Info', 'Deleting..', 'center', 'info');
            //     axios.delete('/web/accounts/' + this.selected_account.id, {
            //         data: parameters
            //     }).then((response) => {
            //         let data = response.data;
            //         this.saving = false;
            //         if (data.meta.error) {
            //             notify('top', 'Error', data.meta.message, 'center', 'danger');
            //         } else {
            //             this.closeCancel();
            //             swal({
            //                 title: 'Success',
            //                 text: 'You have successfully initated the account deletion!',
            //                 type: 'success',
            //                 buttonsStyling: false,
            //                 confirmButtonClass: 'btn btn-success'
            //             }).then(() => {
            //                 this.retrieve()
            //             });
            //         }
            //     }).catch((error) => {
            //         this.saving = false;
            //         if (error.response && error.response.data && error.response.data.meta) {
            //             notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
            //         } else {
            //             notify('top', 'Error', error, 'center', 'danger');
            //         }
            //     });
            // }
        },
        created() {
            this.retrieve();
        },
    }
</script>
