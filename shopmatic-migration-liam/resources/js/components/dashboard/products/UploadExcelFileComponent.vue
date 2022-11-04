<template>
    <div>
        <h2 class="text-center font-weight-light text-muted">Upload the File</h2>
        <b-form-file
                :key="key"
                v-model="file"
                placeholder="Choose a XLSX excel file or drop it here..."
                drop-placeholder="Drop it here..."
                accept=".xlsx"
                @input="verifyFile"/>
        <div class="text-center mt-3">
            <b-button class="px-5" variant="info" size="lg" @click="uploadExcel" :disabled="file === null">Upload</b-button>
        </div>
    </div>
</template>

<script>
    const axios = require('axios').default;
    export default {
        name: "UploadExcelFileComponent",
        data: function () {
            return {
                file: null,
                key: 'file-'
            }
        },
        methods: {
            uploadExcel: async function () {
                try {
                    let form_data = new FormData();
                    form_data.append('xlsx', this.file);
                    form_data.append('update', 1);

                    let response = await axios.post('/web/import/upload/excel', form_data, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    });
                    console.log(response);

                    if (!response.data.meta.error) {
                        notify('top', 'Success', 'Excel file uploaded successfully.', 'center', 'success');
                        notify('top', 'Success', response.data.meta.message, 'center', 'success');
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                } catch (error) {
                    console.log(error);
                    notify('top', 'Error', 'There was an error when uploading the excel file.', 'center', 'danger');
                }
            },
            verifyFile() {
                console.log(this.file);
                console.log('file_type: ' + this.file.type);
                console.log(this.file.name.split('.').pop());
                if (this.file !== null && this.file.type !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && this.file.name.split('.').pop() !== 'xlsx') {
                    this.file = null;
                    this.key += 1;
                    notify('top', 'Error', 'Wrong file format.', 'center', 'danger');
                    notify('top', 'Error', 'Only support excel file with .xlsx extension.', 'center', 'danger');
                }
            }
        }
    }
</script>
