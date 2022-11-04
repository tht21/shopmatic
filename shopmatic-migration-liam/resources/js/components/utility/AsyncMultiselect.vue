<template>
    <div>
        <multiselect
            :id="id"
            :ref="id"
            :class="customClass"
            v-model="value"
            :options="options"
            :placeholder="placeholder"
            :label="label"
            :track-by="label"
            :show-labels="false"
            :allow-empty="typeof validator === 'undefined'"

            :multiple="type.startsWith('multi_select')"
            :close-on-select="!type.startsWith('multi_select')"
            :clear-on-select="!type.startsWith('multi_select')"

            :searchable="true"
            :loading="loading"
            :limit="3"
            :options-limit="optionLimit"
            :preserve-search="true"
            :internal-search="false"
            @search-change="searchChange"

            @input="updateModel"
            :disabled="customize">
            <template slot="singleLabel" slot-scope="{ option }"><strong>{{ singleLabel(option, placeholder) }}</strong></template>
            <template v-if="typeName === 'inventory'" slot="option" slot-scope="{ option }">
                SKU: {{ option.sku }}<br/>
                Name: {{ option.name }}<br/>
                Stock: {{ option.stock }}
            </template>
            <span slot="noResult">Oops! No {{ typeName }} found. Consider changing the search query.</span>
        </multiselect>

        <template v-if="typeof isCustomizable === 'boolean'">
            <b-link @click="setCustomize"><u>{{customize? 'Customize' : 'Default'}}</u></b-link>
        </template>
    </div>
</template>

<script>
    import {required} from "vuelidate/lib/validators";
    import isEqual from "lodash/isEqual";

    const axios = require('axios').default;
    export default {
        name: "AsyncMultiselect",
        // can be synced with parent model
        props: {
            model: {
                type: [Array, Object, Number, String],
                required: true
            },
            initModel: {
                type: [Array, Object, Number, String],
                default: undefined
            },
            validator: {
                type: Object,
                default: undefined
            },
            // normal props
            id: {
                type: String,
                default: 'async-multiselect'
            },
            type: {
                type: String,
                required: true
            },
            label: {
                type: String,
                default: 'name'
            },
            placeholder: {
                type: String,
                default: '-- Type to search --'
            },
            disabled: {
                type: Boolean,
                default: false
            },
            integrationId: {
                type: [Number, String],
                default: null
            },
            accountId: {
                type: Number,
                default: null
            },
            regionId: {
                type: [Number, String],
                default: null
            },
            categoryId: {
                type: [Number, String],
                default: null
            },
            // extra settings can put here
            settings: {
                type: [Object, Array, String],
                default: undefined
            },
            // for retrieve category on export
            exportData: {
                type: [Object, Array],
                default: undefined
            }
        },
        data() {
            return {
                // normal data (reference InputFieldComponent)
                value: this.model,
                options: [],
                customClass: '',
                isCustomizable: undefined,
                customize: this.disabled,
                // used for async
                loading: false,
                optionLimit: 200,
                timer: null
            }
        },
        computed: {
            extraSettings() {
                // convert array and string format settings to object
                if (Array.isArray(this.settings)) {
                    return this.settings.reduce((settings,setting) => ({...settings,[setting]:true}),{});
                } else if (typeof this.settings === 'string') {
                    return this.settings.split(',').reduce((settings,setting) => ({...settings,[setting]:true}),{});
                }
                return this.settings;
            },
            typeName() {
                return this.type.replace('single_select_', '').replace('multi_select_', '');
            }
        },
        beforeMount() {
            // generate extraSettings before mount
            if (typeof this.extraSettings === 'object' && this.extraSettings.hasOwnProperty('customize')) {
                this.isCustomizable = true;
            }
        },
        mounted() {
            // if this field is disabled, dont load options
            if (!this.customize || typeof this.value === 'number') {
                if (this.type === 'single_select_category') {
                    // set option limit here
                    this.optionLimit = 50;
                    // if model is id, call API
                    if (typeof this.value === 'number') {
                        this.getCategories(null, this.integrationId, this.value, this.exportData);
                    } else if (this.value.constructor === Object && this.value.hasOwnProperty('id') && !isNaN(this.value.id)) {
                        this.getCategories(null, this.integrationId, this.value.id, this.exportData);
                    } else if (this.value.constructor === Object && this.value.hasOwnProperty('id') && isNaN(this.value.id)) {
                        this.value = {};
                    }
                } else if (this.type === 'single_select_account_category') {
                    // set option limit here
                    this.optionLimit = 50;
                    // if model is id, call API
                    if (typeof this.value === 'number') {
                        this.getCategories(null, this.accountId, this.value);
                    } else if (this.value.constructor === Object && this.value.hasOwnProperty('id') && !isNaN(this.value.id)) {
                        this.getCategories(null, this.accountId, this.value.id);
                    } else if (this.value.constructor === Object && this.value.hasOwnProperty('id') && isNaN(this.value.id)) {
                        this.value = {};
                    }
                } else if (this.type == 'single_select_integration_category') {
                    // set option limit here
                    this.optionLimit = 50;
                    // if model is id, call API
                    if (typeof this.value === 'number') {
                        this.getCategories(null, this.integrationId,this.value);
                    } else if (this.value.constructor === Object && this.value.hasOwnProperty('id') && !isNaN(this.value.id)) {
                        this.getCategories(null, this.integrationId, this.value.id);
                    } else if (this.value.constructor === Object && this.value.hasOwnProperty('id') && isNaN(this.value.id)) {
                        this.value = {};
                    }
                } else if (this.type === 'single_select_inventory') {
                    // set option limit here
                    this.optionLimit = 100;
                    // if model is id, call API
                    if (this.value.hasOwnProperty('id')) {
                        this.getInventories(null, this.value.id);
                    }
                } else if (this.type === 'multi_select_inventory') {
                    // set option limit here
                    this.optionLimit = 50;
                    // if model is id, call API
                    if (typeof this.value === 'object') {
                        this.getInventories(null, this.value);
                    }
                } else if (this.type === 'single_select_brand') {
                    // set option limit here
                    this.optionLimit = 50;
                    // if model got id or name, call API
                    if (typeof this.value === 'object' && this.value.hasOwnProperty('id')) {
                        this.getBrands(null, this.value.id);
                    } else if (typeof this.value === 'string') {
                        this.$refs[this.id].search = this.value;
                    }
                } else if (this.type === 'single_select_integrations') {
                    // set option limit here
                    this.optionLimit = 50;
                    this.getIntegrations(null)
                } else if (this.type === 'select_all_account_categories') {
                    // set option limit here
                    this.optionLimit = 1;
                    this.getAllAccountCategories()
                }

                if (this.value.constructor === Object && $.isEmptyObject(this.value)) {
                    this.searchChange('');
                }
            }
            // update validator
            this.updateValidator();
        },
        methods: {
            updateModel() {
                this.$emit('update:model', this.value);
                this.updateValidator();

                if (typeof this.extraSettings === 'object') {
                    this.applySettings(this.value);
                }
            },
            updateValidator() {
                if (typeof this.validator !== "undefined") {
                    this.$emit('update:validator', {invalid: this.$v.value.$invalid, dirty: this.isDirty()});
                    // set multiselect component state border color
                    this.customClass = !this.$v.value.$invalid? 'state-true' : 'state-false';
                }
            },
            isDirty() {
                if (typeof this.validator !== "undefined" && typeof this.initModel !== "undefined") {
                    if (isEqual(this.value, this.initModel) && this.$v.value.$dirty) {
                        this.$v.value.$reset();
                    } else if (!isEqual(this.value, this.initModel) && !this.$v.value.$dirty) {
                        this.$v.value.$touch();
                    }
                    return this.$v.value.$dirty;
                }
                return null;
            },
            setCustomize() {
                this.customize = !this.customize;
                this.$emit('update:disabled', this.customize);
                this.$emit('customize', this.customize);
            },
            applySettings(value) {
                for (let setting in this.extraSettings) {
                    if (this.extraSettings[setting]) {
                        this.$emit(setting, value);
                    }
                }
            },
            searchChange(value) {
                this.loading = true;
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
                this.timer = setTimeout(() => {
                    if (this.type === 'single_select_category') {
                        this.getCategories(value, this.integrationId)
                    } else if (this.type === 'single_select_account_category') {
                        this.getCategories(value, this.accountId)
                    } else if (this.type === 'single_select_integration_category') {
                        this.getCategories(value, this.integrationId)
                    } else if (this.type === 'single_select_inventory' || this.type === 'multi_select_inventory') {
                        this.getInventories(value);
                    } else if (this.type === 'single_select_brand') {
                        this.getBrands(value);
                    } else if (this.type === 'single_select_integrations') {
                       console.log(value)
                       this.loading = false;
                     }
                }, 1000);
            },
            singleLabel(value, placeholder) {
                // if empty, show placeholder
                if ($.isEmptyObject(value)) {
                    return placeholder;
                }

                // if not empty, show formatted label
                if (this.typeName === 'inventory') {
                    return value.sku + ' [' + value.stock + ']';
                } else {
                    return value[this.label];
                }
            },

            /* API calls - START */
            // Category and Integration Category
            getCategories(search = null, integrationIdOrAccountId = null, categoryId = null, exportData = {}) {
                // used to determine which type of category
                let mode = 'main';
                if (typeof this.integrationId === 'number' || typeof this.integrationId === 'string') {
                    mode = 'integration';
                } else if (typeof this.accountId === 'number') {
                    mode = 'account';
                } else if (Object.keys(exportData).length !== 0) {
                    mode = 'export'
                }
                axios.get(
                    '/web/categories', {
                        params: {
                            'limit': this.optionLimit,
                            'search': search,
                            [mode + '_id']: integrationIdOrAccountId,
                            ['region_id']: mode === 'integration' ? this.regionId : null,
                            'id': categoryId,
                            'with': mode === 'main' ? 'integrationCategories' : null,
                            'category_id': this.categoryId,
                            'export': exportData
                        }
                    }
                ).then(response => {
                    if (!response.data.meta.error) {
                        this.options = response.data.response.items.map(
                            category => {
                                if (integrationIdOrAccountId === null && Object.keys(exportData).length === 0) {
                                    // get main categories list
                                    return {
                                        id: category.id,
                                        name: category.breadcrumb,
                                        label: category.name,
                                        integration_categories: category.integration_categories.map(
                                            integrationOrAccountCategory => ({
                                                id: integrationOrAccountCategory.id,
                                                name: integrationOrAccountCategory.breadcrumb,
                                                category_id: integrationOrAccountCategory.category_id,
                                                external_id: integrationOrAccountCategory.external_id,
                                                integration_id: integrationOrAccountCategory.integration_id,
                                                region_id: integrationOrAccountCategory.region_id,
                                            })
                                        )
                                    };
                                } else if (integrationIdOrAccountId === null && Object.keys(exportData).length !== 0) {
                                    return {
                                        id: category.id,
                                        name: category.breadcrumb,
                                        label: category.name,
                                    };
                                }else {
                                    // get integration categories list
                                    return {
                                        id: category.id,
                                        name: category.breadcrumb,
                                        category_id: category.category_id,
                                        external_id: category.external_id,
                                        integration_id: category.integration_id,
                                    };
                                }
                            }
                        );
                        
                        // add uncategorized into the options
                        if (this.settings == 'uncategorized') {
                            this.options.push({
                                id: -1,
                                name: 'Uncategorized',
                                category_id: null,
                                external_id: null,
                                integration_id: null,
                            })
                        }

                        if (categoryId !== null) {
                            // change id to category object
                            if (typeof this.value === 'number') {
                                this.value = this.options.find(category => parseInt(category.id, 10) === parseInt(this.value, 10)) || {};
                                this.updateModel();
                            }
                            // fill search bar with name when first loaded
                            this.$refs[this.id].search = typeof this.value !== 'undefined' && this.value !== null ? this.value.name : '';
                        }
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                    this.loading = false;
                }).catch(error => {
                    console.log(error);
                    this.loading = false;
                });
            },
            getInventories(search = null, inventoryId = null) {
                axios.get(
                    '/web/inventory', {
                        params: {
                            search: search,
                            id: inventoryId,
                            limit: this.optionLimit
                        }
                    }
                ).then(response => {
                    if (!response.data.meta.error) {
                        this.options = response.data.response.items.map(inventory => ({
                            id: inventory.id,
                            sku: inventory.sku,
                            name: inventory.name,
                            stock: inventory.stock,
                            low_stock_notification: inventory.low_stock_notification
                        }));

                        if (inventoryId !== null && typeof this.$refs[this.id] !== 'undefined') {
                            this.$refs[this.id].search = this.value.sku;
                        }
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                    this.loading = false;
                }).catch(error => {
                    console.log(error);
                    this.loading = false;
                });
            },
            getBrands(search = null, brandId = null) {
                let brandName = this.value;
                axios.get(
                    '/web/brands', {
                        params: {
                            search: search,
                            integration_id: 11001,
                            id: brandId,
                            region_id: this.regionId !== null && typeof this.regionId !=='undefined' ? this.regionId : null
                        }
                    }
                ).then(response => {
                    if (!response.data.meta.error) {
                        this.options = response.data.response.items.map(brand => ({
                            id: brand.id,
                            name: brand.name
                        }));
                        if (brandId !== null) {
                            this.$refs[this.id].search = this.value.name;
                        } else if (typeof this.value === 'string') {
                            // change brand name to brand object
                            // Check whether have same brand value or not
                            this.value = this.options.find(brand => brand.name === this.value) ? this.options.find(brand => brand.name === this.value) : this.options.find(brand => brand.name.localeCompare(this.value));
                            if (typeof this.value == "undefined" && brandName !=="undefined") {
                                this.value = this.options.find(brand => {
                                    let returnValue = brand.name.localeCompare(brandName);
                                    // if returnValue 0 means they are equivalent
                                    if (returnValue == 0) {
                                        return brand;
                                    }
                                });
                            }
                            this.updateModel();
                        }
                    } else {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    }
                    this.loading = false;
                }).catch(error => {
                    console.log(error);
                    this.loading = false;
                });
            },
            getIntegrations(search = null) {
                axios.get('/web/accounts').then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                            // Add integration options
                            data.response.items.map((account) => {
                                if (account.integration) {

                                    if (!this.options.find(integration_option => integration_option.id === account.integration.id)) {
                                        this.options.push({
                                            id: account.integration.id,
                                            name: account.integration.name.replace(/_/g, ' ')
                                        });
                                    }
                                }
                            });
                            this.$refs[this.id].search = this.value.name || '';
                    }
                    this.loading = false;
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.loading = false;
                });
            },
            getAllAccountCategories() {
                this.options.push({
                    id: 0,
                    name: 'All Categories'
                });
            }
            /* API calls - END */
        },
        validations() {
            if (typeof this.validator !== "undefined") {
                return {
                    value: {required}
                }
            }
            return {};
        },

    }
</script>

<style>
    .state-true.multiselect .multiselect__tags {
        border-color: #38c172;
    }

    .state-false.multiselect .multiselect__tags {
        border-color: #f6993f;
    }
</style>

<style lang="scss" scoped>
    ::-webkit-input-placeholder {
        text-transform: capitalize;
    }
    :-moz-placeholder {
        text-transform: capitalize;
    }
    ::-moz-placeholder {
        text-transform: capitalize;
    }
    :-ms-input-placeholder {
        text-transform: capitalize;
    }

    // set multiselect label font weight to normal
    .multiselect .multiselect__tags .multiselect__single strong {
        font-weight: normal;
    }
</style>
