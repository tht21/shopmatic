<template>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5 col-lg-4 py-3 pr-4">
                    <h2 class="font-weight-light text-primary text-center">Where do you want to export to?</h2>
                    <hr />
                    <div :class="'import-item ' + ((selected_tab === 1) ? 'active' : '')" @click="select(1)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-ui-04 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Account</h3>
                                    <span class="text-muted small">Export to an account you have integrated.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--<div :class="'import-item ' + ((selected_tab === 2) ? 'active' : '')" @click="select(2)">
                        <div class="row">
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                <i class="ni ni-align-left-2 text-info" style="font-size: 50px;"></i>
                            </div>
                            <div class="col-9 d-flex align-items-center">
                                <div>
                                    <h3 class="font-weight-light">Excel</h3>
                                    <span class="text-muted small">Export to an Excel file.</span>
                                </div>
                            </div>
                        </div>
                    </div>-->
                </div>
                <div :class="{'col-md-7 col-lg-8 border-left-md-1': true, 'py-3': selected_tab !== 1}">
                    <template v-if="selected_tab === 0">
                        <div class="full-height d-flex align-items-center justify-content-center">
                            <h2 class="text-center font-weight-light text-muted">Select a method of exporting from the left!</h2>
                        </div>

                    </template>
                    <div v-show="selected_tab === 1">

                        <export-product-component/>

                    </div>
                    <!--<div v-show="selected_tab === 2">

                        <div class="d-flex align-items-center justify-content-center" style="min-height: 300px;">
                            <div class="col-md-8 col-md-mx-auto">

                                <download-product-component/>

                                <hr/>

                                &lt;!&ndash;<download-product-component :listing="true"/>&ndash;&gt;

                            </div>
                        </div>
                    </div>-->

                </div>
            </div>
        </div>

    </div>
</template>
<script>
    import ExportProductComponent from "./ExportProductComponent";
    import DownloadProductComponent from "./DownloadProductComponent";
    const axios = require('axios').default;
    export default {
        name: "ImportProductComponent",
        components: {
            DownloadProductComponent,
            ExportProductComponent
        },
        data() {
            return {
                selected_tab: 0,
                bladeValue: ''
            }
        },
        methods: {
            select (tab) {
                this.selected_tab = tab;
            },
        },
        watch: {
            selected_tab(val) {
                // update global
                this.$emit('update', val);
            }
        }
    }
</script>
