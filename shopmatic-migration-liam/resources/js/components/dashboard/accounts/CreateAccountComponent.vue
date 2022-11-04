<template>
    <div class="card mx-md-auto col-md-10">
        <div class="card-header">
            <h1 class="font-weight-light mb-0 text-center text-primary">Which integration would you like to add?</h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 py-3">
                    <h3 class="font-weight-light text-muted">Regions</h3>
                    <span
                        v-bind:class="'badge mr-1 cursor-pointer px-3 py-2 mt-1 noselect ' + (!region.disabled ? 'badge-primary' : 'badge-disabled')"
                        v-for="(region, index) in regions" @click="toggleRegion(index)">{{ region.name }}</span>
                </div>
                <div class="col-md-6 py-3">

                    <h3 class="font-weight-light text-muted">Type</h3>
                    <span
                        v-bind:class="'badge mr-1 cursor-pointer px-3 py-2 mt-1 noselect ' + (!integration.disabled ? 'badge-primary' : 'badge-disabled')"
                        v-for="(integration, index) in integrations" @click="toggleIntegration(index)">{{ integration.name }}</span>
                </div>
            </div>
            <hr/>
            <div class="row">
                <div v-for="integration in filtered_integrations" class="col-6 col-md-3 mb-4"
                     @click="selectIntegration(integration)" v-if="integration.visibility">
                    <div class="marketplace-box d-flex cursor-pointer flex-column">
                        <img :src="'/images/integrations/' + integration.name.toLowerCase() + '.png'" class="full-width full-height"/>
                        <span class="d-inline-block mt-1 text-uppercase text-muted font-weight-light text-center">{{ integration.name.replace('_', ' ') }}</span>
                    </div>
                </div>
            </div>
            <h3 v-if="regions.length > 0 && filtered_integrations.length === 0"
                class="text-muted text-center font-weight-light">There is nothing that matches your criteria!</h3>
        </div>

        <!-- Modal -->
        <b-modal ref="fields-modal">
            <template v-slot:modal-title>
                <h5 class="modal-title" id="newAccountTitle">New Account<br/><small>for {{
                    selected_integration.name.replace('_', ' ') }} in {{ getRegionName(selected_region) }}</small></h5>
            </template>

            <form v-if="selected_integration" id="create-account">
                <input type="hidden" name="region" v-model="selected_region"/>
                <input type="hidden" name="integration" :value="selected_integration.id"/>
                <div class="form-group" v-for="field in fields">
                    <template v-if="field.type === 'select'">
                        <select :name="field.name" :type="field.type" class="form-control" :required="field.required">
                            <option disabled value="">{{ field.placeholder }}</option>
                            <option v-for="(value, key) in field.data" :value="key">{{ value }}</option>
                        </select>
                    </template>
                    <template v-else>
                        <input :name="field.name" :type="field.type" :placeholder="field.placeholder"
                               class="form-control" :required="field.required"/>
                    </template>
                </div>
            </form>

            <template v-slot:modal-footer>
                <button type="button" class="btn btn-secondary" @click="hideModal('fields-modal')">Close</button>
                <button type="submit" class="btn btn-primary" @click="createAccount">Create</button>
            </template>
        </b-modal>

    </div>
</template>
<script>
    const axios = require('axios').default;
    export default {
        name: "CreateAccountComponent",
        data() {
            return {
                regions: [],
                integrations: [],
                request_url: '/web/integrations',
                filtered_integrations: [],
                sending_request: false,
                fields: [],
                selected_region: null,
                selected_integration: null,
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
                        this.regions = data.response.regions;
                        this.integrations = data.response.integrations;
                        this.filter();
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            selectedRegions() {
                let selectedRegions = [];
                this.regions.forEach((region) => {
                    if (!region.disabled) {
                        selectedRegions.push(region.id);
                    }
                });
                return selectedRegions;
            },
            filter() {
                let filtered = [];
                let selectedRegions = [];
                this.regions.forEach((region) => {
                    if (!region.disabled) {
                        selectedRegions.push(region.id);
                    }
                });
                let selectedTypes = [];
                this.integrations.forEach((integration) => {
                    filtered = filtered.concat(integration.integrations);
                    if (!integration.disabled) {
                        selectedTypes.push(integration.type);
                    }
                });
                filtered = filtered.filter((integration) => {
                    let include = false;
                    selectedRegions.forEach((region) => {
                        if (integration.region_ids.includes(region)) {
                            include = true;
                        }
                    });
                    return include;
                });
                filtered = filtered.filter((integration) => {
                    let include = false;
                    selectedTypes.forEach((type) => {
                        if (integration.type == type) {
                            include = true;
                        }
                    });
                    return include;
                });
                this.filtered_integrations = filtered;
                this.$forceUpdate();
            },
            toggleRegion(index) {
                let region = this.regions[index];
                region.disabled = !region.disabled;
                this.filter();

            },
            toggleIntegration(index) {
                let integration = this.integrations[index];
                integration.disabled = !integration.disabled;
                this.filter();

            },
            getRegionName(regionId) {
                let name = '';
                this.regions.forEach((region) => {
                    if (region.id == regionId) {
                        name = region.name;
                    }
                });
                return name;
            },
            isRegionVisible(regionId) {
                // check if the region is visible
                let visible = false
                this.regions.forEach((region) => {
                    if (region.id == regionId) {
                        visible = true
                    }
                })
                return visible
            },
            selectIntegration(integration) {
                let region = null;
                if (Object.keys(integration.features).length > 1 && integration.id !== 11007) {
                    let regions = {};
                    Object.keys(integration.features).forEach((key) => {
                        let name = this.getRegionName(key)
                        this.isRegionVisible(key)
                        if (this.isRegionVisible(key)) {
                            regions[key] = this.getRegionName(key);
                        }
                    });
                    let selected = this.selectedRegions();
                    if (selected.length === 1) {
                        this.selectRegion(integration, selected[0]);
                        return;
                    }
                    swal({
                        title: 'Select Region',
                        input: 'select',
                        inputOptions: regions,
                        inputPlaceholder: 'Select a region',
                        inputClass: 'form-control region-select',
                        showCancelButton: true,
                        inputValidator: (value) => {
                            if (!value) {
                                return 'You need to select a region!'
                            }
                        }
                    }).then((result) => {
                        if (result.value) {
                            this.selectRegion(integration, result.value);
                        }
                    });
                } else {
                    region = Object.keys(integration.features)[0];
                    this.selectRegion(integration, region);
                }
            },
            reverseObject(object) {
                var newObject = {};
                var keys = [];

                for (let key in object) {
                    keys.push(key);
                }

                for (var i = keys.length - 1; i >= 0; i--) {
                    newObject[keys[i]] = object[keys[i]];
                }

                return newObject;
            },
            selectRegion(integration, region) {
                if (!integration.features[region].authentication || !integration.features[region].authentication.enabled) {
                    swal({
                        title: 'Sorry!',
                        text: 'To add this integration, you need to create a support ticket.',
                        type: 'warning',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-success'
                    });
                } else {
                    if (integration.features[region].authentication.type === 1) {
                        notify('top', 'Please wait', 'We are redirecting you to authenticate your account.', 'center', 'info');
                        axios.get('/web/integrations/redirect', {
                            params: {
                                integration_id: integration.id,
                                region: region,
                            }
                        }).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                window.location.href = data.response.redirect_url;
                            }
                        }).catch((error) => {
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                        });
                    } else {
                        this.selected_region = region;
                        this.selected_integration = integration;
                        this.fields = this.reverseObject(integration.features[region].authentication.fields);
                        if (this.fields.length === 0) {
                            notify('top', 'Error', 'There\'s an unexpected error while trying to create an account for this integration.', 'center', 'danger');
                        } else {
                            setTimeout(() => {
                                // $('#fields-modal').modal('show')
                                this.$refs['fields-modal'].show()
                            }, 300);
                        }

                    }
                }
            },
            createAccount() {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                let selected_integration = this.selected_integration;
                let selected_region = this.selected_region;

                notify('top', 'Info', 'Creating account..', 'center', 'info');

                axios.post('/web/accounts', new FormData($('#create-account')[0])).then((response) => {
                    let data = response.data;
                    let account = data.response.account;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                        this.sending_request = false;
                    } else {
                        if (selected_integration.features[selected_region].authentication.type === 2) {
                            window.location.href = data.response.redirect_url;
                        } else {
                            // $('#fields-modal').modal('hide')
                            this.$refs['fields-modal'].hide()
                            $("#create-account").trigger('reset');
                            swal({
                                title: 'Success',
                                text: 'You have successfully added the account!',
                                type: 'success',
                                buttonsStyling: false,
                                confirmButtonClass: 'btn btn-success'
                            });
                            this.sending_request = false;
                            window.location.href = '/dashboard/accounts/' + account.id + '/setup';
                        }
                    }
                }).catch((error) => {
                    this.sending_request = false;
                    if (typeof error.response != 'undefined' && typeof error.response.data != 'undefined' && typeof error.response.data.debug != 'undefined') {
                        notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (typeof error.meta != 'undefined' && typeof error.meta.message != 'undefined') {
                        notify('top', 'Error', error.meta.message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            hideModal(ref) {
                this.$refs[ref].hide();
            }
        },
        created() {
            this.retrieve();

        },
    }
</script>
