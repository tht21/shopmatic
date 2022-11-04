<template>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-lg-3 py-3 pr-4">
                    <h2 class="font-weight-light text-primary text-center">Bulk Update</h2>
                    <hr/>
                    <div :class="'import-item ' + ((selected_tab === 0) ? 'active' : '')" @click="select(0)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-shop text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Panel</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div :class="'import-item ' + ((selected_tab === 1) ? 'active' : '')" @click="select(1)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-align-left-2 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">CSV</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-10 col-lg-9 py-3 pl-5 border-left-md-1">
                    <b-col md="12" v-show="selected_tab === 0">
                        <bulk-update-panel-component></bulk-update-panel-component>
                    </b-col>
                    <b-col md="12" v-show="selected_tab === 1">
                        <bulk-update-csv-component></bulk-update-csv-component>
                    </b-col>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    import BulkUpdateCsvComponent from "./BulkUpdateCsvComponent";
    import BulkUpdatePanelComponent from "./BulkUpdatePanelComponent";
    export default {
        name: "BulkUpdateComponent",
        components: {BulkUpdatePanelComponent, BulkUpdateCsvComponent},
        data() {
            return {
                selected_tab: 0,
            };
        },
        methods: {
            select(tab) {
                this.selected_tab = tab;
            },
        }
    }
</script>

<style scoped>

</style>
