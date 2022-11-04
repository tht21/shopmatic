<template>
    <div>
        <h1 class="pb-2 text-center">Configure the settings</h1>
        <template v-if="Object.keys(settings).length > 0">
            <form ref="form" @submit.stop.prevent="saveSetting()" id="update-account-form">
                <b-row>
                    <div :class="['col ', is_modal ? 'col-md-10 mx-md-auto' : 'col-md-12']"
                         v-for="(features, key) in settings" v-bind:key="key">
                        <h3 class="text-capitalize">{{ key }}</h3>
                        <div v-for="(setting) in features">
                            <template v-if="setting.type === 'checkbox'">
                                <div class="form-group custom-control custom-checkbox mb-3">
                                    <input class="custom-control-input" :id="setting.name+'-'+key"
                                           type="checkbox"
                                           :name="setting.name" v-model="setting.value">
                                    <label class="custom-control-label" :for="setting.name+'-'+key"
                                           :aria-describedby="key + '_help'">{{ setting.label
                                        }}</label>
                                    <small :id="key + '_help'" class="form-text text-muted"
                                           v-if="setting.note && is_modal">{{ setting.note }}</small>
                                </div>
                            </template>
                            <template v-else-if="setting.type === 'radio'">
                                <div class="form-group">
                                    <label>{{ setting.label }}</label><span class="text-danger">{{(setting.required) ? ' *' : '' }}</span>
                                    <div class="custom-control custom-radio mb-3"
                                         v-for="(value, key) in setting.data">
                                        <input :name="setting.name" class="custom-control-input"
                                               :id="setting.name+'-'+key" type="radio" :value="key"
                                               v-model="setting.value">
                                        <label class="custom-control-label" :for="setting.name+'-'+key">{{
                                            value
                                            }}</label>
                                    </div>
                                    <small :id="key + '_help'" class="form-text text-muted"
                                           v-if="setting.note && is_modal">{{ setting.note }}</small>
                                </div>
                            </template>
                            <template v-else>
                                <div class="form-group">
                                    <label :for="setting.name+'-'+key">{{ setting.label }}</label><span
                                    class="text-danger">{{(setting.required) ? ' *' : '' }}</span>
                                    <input :name="setting.name" type="text" class="form-control"
                                           :id="setting.name+'-'+key" :aria-describedby="key + '_help'"
                                           v-model="setting.value">
                                    <small :id="key + '_help'" class="form-text text-muted"
                                           v-if="setting.note & is_modal">{{ setting.note }}</small>
                                </div>
                            </template>
                        </div>
                    </div>
                </b-row>
                <h3 v-if="!is_modal">You can change the setting at the account page later</h3>
                <b-row>
                    <b-col class="mt-3 text-center">
                        <b-link v-if="is_modal" class="mt-2 text-danger text-underline float-left"
                                @click="deleteConfirmation()">Delete
                        </b-link>
                        <b-button variant="success" type="submit" :class="[is_modal ? 'float-right' : '']">
                            {{is_modal ? 'Save' : 'Save & Next'}}
                        </b-button>
                        <input type="hidden" name="mode" value="setting">
                        <input type="hidden" name="_method" value="put">
                    </b-col>
                </b-row>
            </form>
        </template>
        <template v-else>
            <div v-if="retrieving" class="text-center">
                <i class="fas fa-spinner fa-pulse  font-size-40"></i>
            </div>
            <div v-else>
                <h3>No Settings Available</h3>
                <b-button v-if="!is_modal" variant="success" class="mt-3" type="button" @click="nextPage">
                    Next
                </b-button>
            </div>
        </template>

        <!-- Delete Account Modal -->
        <b-modal id="delete-account-modal" ref="delete-account-modal" header-bg-variant="danger" hide-footer>
            <template v-slot:modal-title>
                <h3 class="mt-2 text-white">Delete Account for: {{ (account) ? account.name : '' }}</h3>
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
    export default {
        name: "AccountSettingComponent",
        props: {
            is_modal: {
                type: Boolean,
                default: false,
            },
            account: {
                type: Object,
                default: null,
            },
            currectIndex: {
                type: Number,
                default: 0,
            },
        },
        created() {
            this.retrieve()
        },
        data() {
            return {
                retrieving: false,
                selected_account: null,
                settings: [],
                sending_request: false,
                delete_options: [
                    //{ text: 'Disable all integration products', value: 'disable_integration_products' },
                    {
                        text: 'Delete all integration products',
                        value: 'delete_integration_products',
                        html: 'Delete all integration products <br/><span class="text-warning">(This action cannot be reversed!)</span>'
                    },
                ],
            }
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                axios.get('/web/integrations/' + this.account.integration.id, {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let features = data.response.features
                        this.accountSettings(features);
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            accountSettings(features) {
                let account = this.account;
                let feature = features[account.region_id];
                this.settings = feature.default_settings;

                // If there is any account setting value replace it from initial
                if (account.settings) {

                    for (let [key, settings] of Object.entries(this.settings)) {
                        for (let [name, setting] of Object.entries(settings)) {
                            let display = 0;
                            if (setting['requires'] !== undefined) {
                                setting['requires'].forEach((require) => {
                                    if (feature[key][require] !== undefined && feature[key][require] == 1)
                                        display = 1;
                                })
                            }else {
                                display = 1;
                            }

                            if (display === 0) {
                                delete this.settings[key][name];
                            } else {
                                if (account.settings[key] !== undefined && account.settings[key][name] !== undefined) {
                                    this.settings[key][name].value = account.settings[key][name];
                                }
                            }
                        }

                        if(this.settings[key] === undefined || Object.keys(this.settings[key]).length <= 0) {
                            delete this.settings[key];
                        }
                    }
                }

            },
            saveSetting(is_back = false) {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;
                notify('top', 'Info', 'Updating account settings..', 'center', 'info');

                axios.post('/web/accounts/' + this.account.id + '/settings', new FormData($('#update-account-form')[0])).then((response) => {
                    let data = response.data;
                    this.sending_request = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'You have successfully updated the account setting!', 'center', 'success');
                        if(this.is_modal) {
                            this.$emit('closeModel');
                        }else {
                            this.nextPage();
                        }
                    }
                }).catch((error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            deleteConfirmation() {
                this.$refs['delete-account-modal'].show();
            },
            nextPage() {
                this.$emit('onChange', this.currectIndex, this.currectIndex + 1);
            },
            closeCancel() {
                this.$refs['delete-account-modal'].hide();
                this.selected_account = null;
                this.selected_delete_options = null;
            },
            confirmDelete() {
                if (this.sending_request) {
                    return;
                }
                this.saving = true;
                let parameters = {
                    action: 'delete_account',
                };
                if (this.selected_delete_options) {
                    parameters['options'] = this.selected_delete_options;
                }
                notify('top', 'Info', 'Deleting..', 'center', 'info');
                axios.delete('/web/accounts/' + this.account.id, {
                    data: parameters
                }).then((response) => {
                    let data = response.data;
                    this.saving = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.closeCancel();
                        swal({
                            title: 'Success',
                            text: 'You have successfully initated the account deletion!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            this.$emit('closeModel');
                            this.retrieve()
                        });
                    }
                }).catch((error) => {
                    this.saving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
        },
    }
</script>

<style scoped>

</style>
