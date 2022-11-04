<template>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 col-lg-3 py-3 pr-4">
                    <h2 class="font-weight-light text-primary text-center">Manage Bulk Products</h2>
                    <hr />
                    <div :class="'import-item ' + ((selected_tab === 1) ? 'active' : '')" @click="selectTab(1)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-cloud-download-95 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">File Download</h3>
                                    <span class="text-muted small">Download bulk Excel file.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div :class="'import-item ' + ((selected_tab === 2) ? 'active' : '')" @click="selectTab(2)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-cloud-upload-96 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Upload Files</h3>
                                    <span class="text-muted small">Upload from an Excel file.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9 py-3 pl-5 border-left-md-1">
                    <template v-if="selected_tab === 0">
                        <div class="full-height d-flex align-items-center justify-content-center">
                            <h2 class="text-center font-weight-light text-muted">Select a method of bulk product from the left!</h2>
                        </div>
                    </template>

                    <div v-show="selected_tab === 1">
                        <b-card no-body>
                            <b-tabs pills card>
                                <b-tab active>
                                    <template v-slot:title>
                                        <i class="ni ni-align-left-2"></i> <strong>CSV Files</strong>
                                    </template>

                                    <generate-excel-template-component/>
                                    <hr>
                                    <download-product-component v-if="this.env !== 'production'"/>

                                </b-tab>

                                <b-tab lazy>
                                    <template v-slot:title>
                                        <i class="fas fa-file-download"></i>
                                        <strong>Downloaded Files
                                            <b-badge variant="primary">{{ total_unread_files }}</b-badge>
                                        </strong>
                                    </template>
                                    <product-task-index-component title="Downloaded files"
                                                     request_url="/web/products/export/tasks?type=excel&status=0,1,2"
                                                     :fields="export_fields"
                                                     :headers="export_headers"
                                                     :update_download_status="1">
                                    </product-task-index-component>
                                </b-tab>
                            </b-tabs>
                        </b-card>
                    </div>

                    <div v-show="selected_tab === 2">
                        <b-card no-body>
                            <b-tabs pills card>
                                <b-tab active>
                                    <template v-slot:title>
                                        <i class="ni ni-align-left-2"></i> <strong>CSV Files</strong>
                                    </template>

                                    <upload-excel-file-component/>

                                </b-tab>

                                <b-tab lazy>
                                    <template v-slot:title>
                                        <i class="fas fa-file-upload"></i> <strong>Uploaded Files</strong>
                                    </template>
                                    <product-task-index-component title="Uploaded files"
                                                     request_url="/web/products/import/tasks"
                                                     :fields="import_fields"
                                                     :headers="import_headers">
                                    </product-task-index-component>
                                </b-tab>
                            </b-tabs>
                        </b-card>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import GenerateExcelTemplateComponent from "./GenerateExcelTemplateComponent";
    import ProductTaskIndexComponent from "./ProductTaskIndexComponent";
    export default {
        name: "BulkProductComponent",
        components: {ProductTaskIndexComponent, GenerateExcelTemplateComponent},
        data() {
            return {
                selected_tab: 0,
                total_unread_files: 0,
                export_headers: [
                    'ID', 'Download', 'Message', 'Status', 'Created At'
                ],
                export_fields: [
                    'id', 'download', 'message', 'status', 'created_at'
                ],
                import_headers: [
                    "ID", "Source", "Source Type", "Message", "Total Products", "Status", "Created At"
                ],
                import_fields: [
                    "id", "source", "source_type", "messages", "total_products", "status", "created_at"
                ],
                env: process.env.MIX_ENV
            }
        },
        methods: {
            selectTab(tab) {
                this.selected_tab = tab;
                if (this.selected_tab === 1) {
                    this.retrieveUnreadFiles();
                }
            },
            retrieveUnreadFiles() {
                axios.get('/web/products/export/tasks?type=excel&count_unread=1').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.total_unread_files = data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>
