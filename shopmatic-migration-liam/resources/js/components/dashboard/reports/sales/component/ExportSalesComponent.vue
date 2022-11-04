<template>
    <div>
        <button class="btn btn-sm btn-neutral" @click="exportFile">Export</button>
    </div>
</template>

<script>
    export default {
        name: "ExportSalesComponent",
        props: ['global'],
        watch: {
            global() {
            }
        },
        methods: {
            exportFile() {
                axios.get('/web/report/sales/export', {
                    responseType: 'blob',
                    params: this.global,
                }).then((response) => {
                    if (response.data.size > 0) {
                        let blob = new Blob([response.data], {type: 'application/vnd.ms-excel'});
                        let link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'sales_report_' + Date.now() + '.xlsx';
                        link.click();
                    } else {
                        notify('top', 'Error', 'Template generate fail. ', 'center', 'danger');
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>
