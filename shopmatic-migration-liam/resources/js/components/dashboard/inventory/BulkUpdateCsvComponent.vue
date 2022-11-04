<template>
    <div class="d-flex align-items-center justify-content-center" style="min-height: 300px;">
        <div class="col-md-12 col-md-mx-auto">
            <div>
                <h2 class="text-center font-weight-light text-muted">Upload the File</h2>
                <b-form-file
                    :key="key"
                    v-model="file"
                    placeholder="Choose a CSV excel file or drop it here..."
                    drop-placeholder="Drop it here..."
                    accept=".csv"
                    @input="verifyFile"/>
                <b-form-checkbox
                    v-bind:key="'checkbox-inventory'"
                    v-model="is_create_inventory"
                    id="checkbox-inventory"
                    name="checkbox-inventory"
                    :value="true"
                    :unchecked-value="false"
                    class="mt-2"
                >
                <small>Create Inventory - Creates new inventory for the SKU if it does not exist</small>
                </b-form-checkbox>
                <div class="text-center mt-3">
                    <b-button class="px-5" variant="info" size="lg" @click="uploadCSV" :disabled="file === null">Upload
                    </b-button>
                </div>
            </div>
            <hr/>
            <div>   
                <b-card no-body>
                    <b-tabs pills card>
                        <b-tab active>
                            <template v-slot:title>
                                <i class="ni ni-align-left-2"></i> <strong>Download Template</strong>
                            </template>
                            <div>
                                <h2 class="text-center font-weight-light text-muted">Download Template<button class="ml-3 btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button></h2>
                                <div class="text-center mt-3">
                                    <div id="filter" class="collapse">
                                        <div class="p-3" style="background: #f6f6f6;">
                                            <div class="row">
                                                <div class="col-md-6 mt-2">
                                                    <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                                                    <input id="search" v-model="search" name="search" class="form-control" type="text">
                                                </div>
                                                <div class="col-md-6 mt-2">
                                                    <label for="enabled" class="text-muted text-uppercase">SYNC</label>
                                                    <select id="enabled" v-model="enabled" name="enabled" class="form-control">
                                                        <option value="">All</option>
                                                        <option value="1">Enabled</option>
                                                        <option value="0">Disabled</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mt-2">
                                                    <label for="stock" class="text-muted text-uppercase">STOCK</label>
                                                    <div class="row">
                                                        <div class="col-3">
                                                            <select v-model="stock_opt" name="stock_opt" class="form-control">
                                                                <option value="=" selected>=</option>
                                                                <option value="!=">!=</option>
                                                                <option value=">=">>=</option>
                                                                <option value="<="><=</option>
                                                                <option value=">">></option>
                                                                <option value="<"><</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-9">
                                                            <input id="stock" v-model="stock" name="stock" class="form-control" type="number">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <b-button class="mt-3 px-5" variant="info" size="lg" @click="download">Download
                                    </b-button>
                                </div>
                            </div>
                        </b-tab>
                        <b-tab lazy>
                            <template v-slot:title>
                                <i class="fas fa-file-download"></i>
                                <strong>Downloaded Files
                                    <b-badge variant="primary">{{ total_unread_files }}</b-badge>
                                </strong>
                            </template>
                            <div class="text-center mt-3">
                                <product-task-index-component title="Downloaded files"
                                                request_url="/web/products/export/tasks?type=excel&status=0,1,2&source_type=Csv\DownloadInventory"
                                                :fields="export_fields"
                                                :headers="export_headers"
                                                :update_download_status="1">
                                </product-task-index-component>
                            </div>
                        </b-tab>
                    </b-tabs>
                </b-card>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "BulkUpdateCsvComponent",
        data() {
            return {
                key: 'file-',
                file: null,
                is_create_inventory: false,
                search: '',
                stock: '',
                stock_opt: '=',
                enabled: '',
                total_unread_files: 0,
                export_headers: [
                    'ID', 'Download', 'Message', 'Status', 'Created At'
                ],
                export_fields: [
                    'id', 'download', 'message', 'status', 'created_at'
                ],
                requesting: false
            }
        },
        methods: {
            async uploadCSV() {
                try {
                    let form_data = new FormData();
                    form_data.append('csv', this.file);
                    form_data.append('is_create_inventory', this.is_create_inventory)

                    let response = await axios.post('/web/inventory/upload/csv', form_data, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    });

                    if (!response.data.meta.error) {
                        notify('top', 'Success', 'scheduled for processing', 'center', 'success');
                        notify('top', 'Success', response.data.meta.message, 'center', 'success');
                        this.file = null;
                        this.is_create_inventory = false;
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                } catch (error) {
                    console.log(error);
                    notify('top', 'Error', 'There was an error when uploading the excel file.', 'center', 'danger');
                }
            },
            download() {
                if (this.requesting) {
                    return
                }

                this.requesting = true
                notify('top', 'Info', 'Generating... ', 'center', 'info');

                axios.get('/web/inventory/download/csv', {
                    params: {
                        search: this.search,
                        enabled: this.enabled,
                        stock: this.stock,
                        stock_opt: this.stock_opt
                    }
                }).then(response => {
                    if (!response.data.meta.error) {
                        notify('top', 'Success', 'Csv file will be downloaded shortly. ', 'center', 'success');
                    } else {
                         notify('top', 'Error', 'Template generate fail. ', 'center', 'danger');
                    }
                }).catch(error => {
                    console.log(error);
                    notify('top', 'Error', 'There was an error when generating the excel file. ', 'center', 'danger');
                }).finally(() => {
                    this.requesting = false
                })
            },
            verifyFile() {
                if (this.file !== null && this.file.type !== 'application/vnd.ms-excel' && this.file.name.split(".").pop() != 'csv') {
                    this.file = null;
                    this.key += 1;
                    notify('top', 'Error', 'Wrong file format.', 'center', 'danger');
                    notify('top', 'Error', 'Only support excel file with .csv extension.', 'center', 'danger');
                }
            },

            retrieveUnreadFiles() {
                axios.get("/web/products/export/tasks", {
                    params: {
                        type: 'excel',
                        count_unread: 1,
                        source_type: 'Csv\\DownloadInventory'
                    }
                }).then((response) => {
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
        },
        created() {
            this.retrieveUnreadFiles();
        },
    }
</script>

<style scoped>

</style>
