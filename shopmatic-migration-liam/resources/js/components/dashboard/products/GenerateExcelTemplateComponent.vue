<template>
    <div>
        <h2 class="text-muted font-weight-light text-center">Import New Products</h2>
        <div>
            <async-multiselect
                class="m-2"
                type="single_select_category"
                :model.sync="selected"
            />

            <div class="pl-2">
                <template v-if="selected.id != null" v-for="integration in integrations">
                    <template v-for="region in integration.regions">
                        <h3 class="text-muted font-weight-bold mt-2">{{ integration.name }}&nbsp;{{ region.shortcode }}&nbsp;({{ integration.accountname }})</h3>

                        <async-multiselect
                            :id="'async-multiselect-' + integration.id + '-' + region.id"
                            type="select_all_account_categories"
                            :key="integration.id + '-' + region.id"
                            :model.sync="integrationsCategory[integration.id][region.id]"
                            v-if="integration.id === 11002 || integration.id === 11006 || integration.id === 11007"
                        />

                        <async-multiselect
                            :id="'async-multiselect-' + integration.id + '-' + region.id"
                            type="single_select_integration_category"
                            :key="integration.id + '-' + region.id"
                            :model.sync="integrationsCategory[integration.id][region.id]"
                            :integrationId="integration.id"
                            :regionId="region.id"
                            v-else
                        />
                    </template>
                </template>
            </div>

            <div class="text-center mt-3">
                <b-button class="px-5" variant="info" size="lg" @click="generateTemplate" :disabled="selected.id === null">Generate</b-button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "GenerateExcelTemplateComponent",
        data() {
            return {
                integrations: null,
                categories: [],
                selected: {
                    id: null,
                    name: null,
                    label: null,
                    integration_categories: null,
                },
                integrationsCategory: {},
                integrationToAccountId: null,
                key: {},
                integration_options: [],
                key_integration: 'key-integration-0',
            }
        },
        async created() {
            try {
                let response = await axios.get('/web/accounts', {
                    params: {
                        limit: 50,
                        active: 0,
                        feature: ['products', 'create_product']
                    }
                });

                if (!response.data.meta.error) {
                    this.integrations = {};
                    this.integrationToAccountId = {};
                    for (let account of response.data.response.items) {

                        if (!this.integrations.hasOwnProperty(account.integration.id)) {
                            this.integrations[account.integration.id] = {...account.integration, regions: {[account.region.id]: account.region}, accountname: account.name};
                            // create integration category for the integration
                            this.integrationsCategory[account.integration_id] = {}
                        } else if (!this.integrations[account.integration.id].regions.hasOwnProperty(account.region.id)) {
                            this.integrations[account.integration.id].regions[account.region.id] = account.region;
                        }

                        if (!this.integrationToAccountId.hasOwnProperty(account.integration.id)) {
                            this.integrationToAccountId[account.integration.id] = {[account.region.id]: account.id};
                        } else if (!this.integrationToAccountId[account.integration.id].hasOwnProperty(account.region.id)) {
                            this.integrationToAccountId[account.integration.id][account.region.id] = account.id;
                        }
                        // add region into integration
                        if (!this.integrationsCategory[account.integration.id].hasOwnProperty(account.region_id)) {
                            this.integrationsCategory[account.integration.id][account.region_id] = {};
                        }

                        // Add integration options
                        if (account.integration) {
                            if (!this.integration_options.find(integration_option => integration_option.value === account.integration.id + '/' + account.region_id) && [11001, 11003, 11005].includes(account.integration_id)) {
                                this.integration_options.push({
                                    value: account.integration.id + '/' + account.region_id,
                                    integration_id: account.integration.id,
                                    region_id: account.region_id
                                });
                            }
                        }
                    }
                } else {
                    notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                }
            } catch (error) {
                console.log(error);
            }
        },
        methods: {
            generateTemplate() {
                // Separate integration category and account category
                let integration_category_ids = [];
                let account_category_ids = []; // This will be storing the integration id eg: 11002 or 11006
                for (let integrationId in this.integrationsCategory) {
                    for (let regionId in this.integrationsCategory[integrationId]) {
                        if (!$.isEmptyObject(this.integrationsCategory[integrationId][regionId])) {
                            if (integrationId == 11002 || integrationId == 11006 || integrationId == 11007) {
                                account_category_ids.push(integrationId);
                            } else {
                                integration_category_ids.push(this.integrationsCategory[integrationId][regionId].id);
                            }
                        }
                    }
                }
                axios.get('/web/import/download/template', {
                    responseType: 'blob',
                    params: {
                        category_id: this.selected.id,
                        integrations_category_id: integration_category_ids,
                        integration_to_account_id: this.integrationToAccountId,
                        account_category_integration_id: account_category_ids
                    }
                }).then(response => {
                    if (response.data.size > 0) {
                        let blob = new Blob([response.data], {type: 'application/vnd.ms-excel'});
                        let link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'template_' + this.selected.label +'_'+ Date.now() + '.xlsx';
                        link.click();
                    } else {
                        notify('top', 'Error', 'Template generate fail. ', 'center', 'danger');
                    }
                }).catch(error => {
                    console.log(error);
                    notify('top', 'Error', 'There was an error when generating the excel file. ', 'center', 'danger');
                });
            }
        }
    }
</script>

<style scoped>

</style>
