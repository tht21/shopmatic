<template>
    <div>
        <b-card-group deck>
            <b-card header-tag="header" footer-tag="footer">
                <template #header>
                    <b-card-text>Select Category</b-card-text>
                </template>

                <async-multiselect
                    class="m-2"
                    type="single_select_category"
                    :model.sync="selected_category"
                    :exportData="exportData"
                    @update:model="filterCategory()"
                />

                <template class="py-2 px-2" v-if="selected_category && selected_category.id && ![11002, 11006, 11007].includes(this.account.integration_id)">
                    <h3 class="text-muted font-weight-bold mt-2">{{ account.integration.name }}&nbsp;{{ account.region.shortcode }}&nbsp;({{ account.name }})</h3>
                    <async-multiselect
                        type="single_select_integration_category"
                        :integrationId="account.integration_id"
                        :regionId="account.region_id"
                        :model.sync="selected_integration_category"
                        @update:model="filterIntegrationCategory()"
                        :key="integration_category_key"
                    />
                </template>

                <template #footer>
                    <div>
                        <b-card-text class="float-left">Add Integration Attribute to Your Listing </b-card-text>
                        <div class="float-right">
                            <a v-if="exported_file" :href="exported_file.download.url" class="mr-2">Download</a>
                            <span v-if="pending_export != null" class="mr-2">Generating..</span>
                            <b-button variant="success" @click="generateTemplate" :disabled="pending_export != null"><i class="fas fa-file-download"></i></b-button>
                            <b-button variant="primary" v-b-toggle.upload-file><i class="fas fa-file-upload"></i></b-button>
                        </div>
                    </div>
                    <div style="clear:both;"></div>

                    <!-- Element to collapse -->
                    <b-collapse id="upload-file" class="mt-3">
                        <upload-excel-file-component/>
                    </b-collapse>
                </template>
            </b-card>
        </b-card-group>
    </div>
</template>

<script>
    export default {
        name: "ExportTableFilterComponent",
        props: ['account'],
        data() {
            return {
                pending_export: null,
                exported_file: null,
                updating_export_task: null,
                selected_category: {
                    id: null,
                    name: null,
                    label: null,
                    integration_categories: null,
                },
                selected_integration_category: {
                    id: null
                },
                exportData: {
                    account_id: this.account.id,
                    integration_id: this.account.integration_id
                },
                integration_category_key: 0
            }
        },
        methods: {
            filterCategory() {
                if (!this.selected_category) {
                    this.selected_category = {
                        id: null,
                        name: null,
                        label: null,
                        integration_categories: null,
                    }
                }
                // reset selected integration category
                this.selected_integration_category = {
                    id: null,
                };
                this.$emit('filter:category', this.selected_category);
                this.integration_category_key++;
            },
            filterIntegrationCategory() {
                if (!this.selected_integration_category) {
                    this.selected_integration_category = {
                        id: null,
                    }
                }
                this.$emit('filter:integration-category', this.selected_category, this.selected_integration_category);
            },
            updatePendingExport() {

            },
            generateTemplate() {
                if (this.pending_export) {
                    notify('top', 'Error', 'It is still currently generating your template file. Please wait.', 'center', 'danger');
                    return;
                }
                if (this.selected_category.id) {
                    if (this.selected_integration_category.id || [11002, 11006, 11007].includes(this.account.integration_id)) {
                        axios.get('/web/cross-listing/export/download', {
                            params: {
                                account_id: this.account.id,
                                category_id: this.selected_category.id,
                                integration_category_id: this.selected_integration_category.id,
                                //integration_to_account_id: this.integrationToAccountId,
                            }
                        }).then(response => {
                            if (!response.data.meta.error) {
                                this.pending_export = response.data.response;
                                this.updating_export_task = setInterval(() => {
                                    axios.get('/web/cross-listing/export/' + this.pending_export.id).then(response => {
                                        if (!response.data.meta.error) {
                                            let task = response.data.response;
                                            if (task.status == 'Finished') {
                                                this.exported_file = task;
                                                notify('top', 'Success', 'Successfully generated the template file. Click on download!', 'center', 'success');
                                                this.pending_export = null;
                                                clearInterval(this.updating_export_task);
                                            } else if (task.status == 'Failed') {
                                                this.pending_export = null;
                                                notify('top', 'Error', 'The template generation has failed.', 'center', 'danger');
                                                clearInterval(this.updating_export_task);
                                            }
                                        } else {
                                            this.pending_export = null;
                                            notify('top', 'Error', 'Unable to get status of template generation.', 'center', 'danger');
                                            clearInterval(this.updating_export_task);
                                        }
                                    }).catch(error => {
                                        this.pending_export = null;
                                        notify('top', 'Error', 'There was an error while getting the status of the template.', 'center', 'danger');
                                        clearInterval(this.updating_export_task);
                                    });
                                }, 2000);
                                notify('top', 'Processing', 'We are currently generating the template for you. This may take a while.', 'center', 'info');
                            } else {
                                notify('top', 'Error', 'Template generate fail. ', 'center', 'danger');
                            }
                            /*if (response.data.size > 0) {
                                let blob = new Blob([response.data], {type: 'application/vnd.ms-excel'});
                                let link = document.createElement('a');
                                link.href = window.URL.createObjectURL(blob);
                                link.download = 'template_' + this.selected_category.label +'_'+ Date.now() + '.xlsx';
                                link.click();
                            } else {
                                notify('top', 'Error', 'Template generate fail. ', 'center', 'danger');
                            }*/
                        }).catch(error => {
                            console.log(error);
                            notify('top', 'Error', 'There was an error when generating the excel file. ', 'center', 'danger');
                        });
                    } else {
                        notify('top', 'Error', 'Please select integration category to generate template. ', 'center', 'danger');
                    }
                } else {
                    notify('top', 'Error', 'Please select a category to generate template. ', 'center', 'danger');
                }
            }
        }
    }
</script>

<style scoped>

</style>
