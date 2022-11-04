<template>
    <div>
        <h2 class="text-muted font-weight-light text-center">Update Existing Products<!--Products {{ listing ? 'Listings' : '' }}--></h2>
        <div>
            <div v-if="!listing" class="d-flex justify-content-center align-content-center">
                <b-form-radio-group v-model="form.type">
                    <template v-for="typeValue in type">
                        <b-form-radio class="text-capitalize" :value="typeValue">
                            {{ typeValue.replace('_', ' ') }}
                        </b-form-radio>
                    </template>
                </b-form-radio-group>
            </div>
            
            <async-multiselect
                v-if="!listing && form.type === 'by_category'"
                class="m-2"
                type="single_select_category"
                :id="id.by_category"
                :model.sync="form.category"
                placeholder='Select'
            />

            <input-field-component
                v-if="listing"
                class="mb-2"
                type="single_select"
                id="account"
                :model.sync="form.account"
                :options="accounts"
                placeholder="-- Select an Account --"
                :key="key.account"
            />
            <async-multiselect
                v-show="form.type === 'by_marketplace'"
                class="m-2"
                type="single_select_integrations"
                :id="id.by_marketplace"
                :searchable="false"
                :model.sync="selected_integration"
                placeholder='-- Select an Marketplace --'
                @update:model="filterCategoryByIntegration(selected_integration)"
            />
            <div class="pl-2"  v-if="form.type === 'by_marketplace' && Object.keys(selected_integration).length > 0 ">
                <h3 class="text-muted font-weight-bold mt-2"> Category ({{selected_integration.name}})</h3>
                <async-multiselect
                    class="m-2"
                    type="single_select_category"
                    :integrationId="selected_integration.id"
                    :model.sync="selected_integration_category"
                    placeholder='-- Select Category --'
                    :key="integration_category_key"
                />
            </div>    
            <div class="text-center mt-3">
                <b-button class="px-5" variant="info" size="lg" @click="generateExcel" :disabled="form.type === null && form.account === null">Download</b-button>
            </div>
        </div>
    </div>
</template>

<script>
    import AsyncMultiselect from "../../utility/AsyncMultiselect";
    import InputFieldComponent from "../../utility/InputFieldComponent";
    export default {
        name: "DownloadProductComponent",
        props: {
            listing: {
                default: false
            }
        },
        components: {InputFieldComponent, AsyncMultiselect},
        async created() {
            if (this.listing) {
                await this.getAccounts();
            }
        },
        data() {
            return {
                type: ['all_products', 'by_category','by_marketplace'],
                accounts: [],
                form: {
                    type: null,
                    category: {},
                    account: null,
                },
                selected_integration_category: {
                    id: null
                },
                integrationsCategory:{},
                selected_integration: [],
                integration_category_key: 0,
                key: {
                    account: 'key-account-'
                },
                id:{
                    by_category : 'id-by-category',
                    by_marketplace : 'id-by-marketplace'
                }
            }
        },
        methods: {
            async getAccounts() {
                try {
                    let response = await axios.get('/web/accounts', {
                        params: {
                            limit: 50,
                        }
                    });

                    if (!response.data.meta.error) {
                        this.accounts = response.data.response.items.map(account => ({
                            id: account.id,
                            name: this.accountName(account),
                        }));

                        this.key.account += 1;
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                } catch (error) {
                    console.log(error);
                }
            },
            generateExcel() {
                let categoryId = null;
                let integrationId = null;
                if ( this.form.type === 'by_category' ) {
                    categoryId = this.form.category.id;
                } else if (this.form.type === 'by_marketplace' && typeof this.selected_integration_category !=="undefined") {
                    categoryId = this.selected_integration_category.category_id;
                    integrationId = this.selected_integration.id;
                }
                axios.get('/web/products/export/download', {
                    params: {
                        account_id: this.listing ? this.form.account.id : null,
                        category_id: categoryId,
                        integration_id: integrationId,
                    }
                }).then(response => {
                    if (!response.data.meta.error) {
                        notify('top', 'Success', 'Excel file will be downloaded shortly. ', 'center', 'success');
                        //window.location = response.data.response.url; // open in current tab
                        // window.open(response.data.response.url); // open in new tab
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                }).catch(error => {
                    console.log(error);
                    notify('top', 'Error', 'There was an error when generating the excel file. ', 'center', 'danger');
                });
            },

            accountName(account, isLabel = false) {
                return account.name + ' (' + (account.hasOwnProperty('integration_name') ? account.integration_name : account.integration.name) + ')' + (isLabel? ':' : '');
            },
            filterCategoryByIntegration (selected_integration) {
                this.integration_category_key++;
                // reset selected integration category
                this.selected_integration_category = {
                    id: null,
                };
            },
        }
    }
</script>

<style scoped>

</style>
