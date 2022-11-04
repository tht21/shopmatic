<template>
    <div>
        <template v-if="['text', 'number'].includes(typeLowerCase)">
            <b-form-input
                :id="id"
                :type="typeLowerCase"
                v-model.trim="value"
                :step="formatter === 'formatPrice' ? '0.01' : null"
                :placeholder="getPlaceholder"
                :state="getState"
                :required="validator !== 'undefined'"
                :formatter="stringToFunction(formatter)"
                :lazy-formatter="typeof formatter === 'string'"
                @blur="updateModelFromBlur"
                @input="updateModel"
                @focus="focusInputOption"
                :disabled="customize"
                :readonly="readonly"
            />
        </template>
        <template v-else-if="typeLowerCase === 'date'">
            <b-form-datepicker
                :id="id"
                :date-format-options="{ year: 'numeric', month: 'long', day: 'numeric'}"
                v-model="value"
                placeholder="Choose a date"
                :required="validator !== 'undefined'"
                @input="updateModel"
                :disabled="customize"
            />
        </template>
        <template v-else-if="typeLowerCase === 'datetime'">
            <b-form-datepicker
                :id="id + '-date'"
                :date-format-options="{ year: 'numeric', month: 'long', day: 'numeric'}"
                v-model="multiValue.date"
                placeholder="Choose a date"
                :required="validator !== 'undefined'"
                @input="updateDateTime"
                :disabled="customize"
            />
            <b-form-timepicker
                :id="id + '-time'"
                v-model="multiValue.time"
                placeholder="Choose a time"
                :required="validator !== 'undefined'"
                @input="updateDateTime"
                :disabled="customize"
                show-seconds
            />
        </template>
        <template v-else-if="typeLowerCase === 'multi_text'">
            <b-form-textarea
                :id="id"
                v-model.trim="value"
                :placeholder="getPlaceholder"
                rows="2"
                max-rows="4"
                :state="getState"
                :required="validator !== 'undefined'"
                @input="updateModel"
                :disabled="customize"
            />
        </template>
        <template v-else-if="typeLowerCase === 'radio'">
            <b-form-radio-group
                :id="id"
                v-model="value"
                :options="options"
                :state="getState"
                @input="updateModel"
                :disabled="customize"
            />
        </template>
        <template v-else-if="typeLowerCase === 'checkbox' || typeLowerCase === 'switch'">
            <b-form-checkbox-group
                :id="id"
                :class="{'pl-switch': typeLowerCase === 'switch'}"
                v-model="value"
                :options="options"
                :state="getState"
                @input="updateModel"
                :disabled="customize"
                :switches="typeLowerCase === 'switch'"
            />
        </template>
        <template v-else-if="typeLowerCase === 'tags'">
            <b-form-tags
                tag-variant="primary"
                tag-pills
                :id="id"
                v-model="value"
                :placeholder="getPlaceholder"
                :state="getState"
                @input="updateModel"
                :disabled="customize"
            />
        </template>
        <template v-else-if="typeLowerCase === 'rich_text'">
            <tinymce-vue
                :model.sync="value"
                :init-model="initModel"
                :validator.sync="customValidator"
                type="description"
                @input="updateModel"
                :disabled="customize"
            />
        </template>
        <template v-else-if="typeLowerCase === 'image'">
            <vue-dropzone-image
                :id="id"
                :model.sync="value"
                :validator.sync="customValidator"
                @input="updateModel"
            />
        </template>
        <template v-else-if="['single_select', 'multi_select', 'multi_enum', 'single_select_or_input'].includes(typeLowerCase) && typeof options !== 'undefined' && typeof options[0] === 'object'">
            <multiselect
                :id="id"
                :class="customClass"
                v-model="value"
                :options="customOptions"
                :placeholder="getPlaceholder"
                :label="label"
                :track-by="getTrackBy"
                :show-labels="false"
                :allow-empty="typeof validator === 'undefined'"
                :multiple="typeLowerCase === 'multi_select' || typeLowerCase === 'multi_enum'"
                :close-on-select="typeLowerCase !== 'multi_select' && typeLowerCase !== 'multi_enum'"
                :clear-on-select="typeLowerCase !== 'multi_select' && typeLowerCase !== 'multi_enum'"
                @input="updateModel"
                @search-change="searchInputToOption"
                :disabled="customize"
                :loading="loading"
                >
                <template v-if="customOptionTemplate === undefined" slot="singleLabel" slot-scope="{ option }"><strong>{{ singleLabel(option, getPlaceholder) }}</strong></template>

                <template v-if="customOptionTemplate === 'locations'" slot="singleLabel" slot-scope="{ option }">
                    <template v-if="!isEmptyObject(option)">
                        <span class="force-strong">{{ option.name }}</span><br>
                        <small>
                            {{ option.address_1 }}&nbsp;{{ option.address_2 }},&nbsp;{{ option.postcode }},&nbsp;{{ option.country }}<br>
                            {{ option.contact_number }}
                        </small>
                    </template>
                    <template v-else>
                        <strong>{{ getPlaceholder }}</strong>
                    </template>
                </template>

                <template v-if="customOptionTemplate === 'locations'" slot="option" slot-scope="{ option }">
                    <span class="force-strong">{{ option.name }}</span><br>
                    <small>
                        {{ option.address_1 }}&nbsp;{{ option.address_2 }},&nbsp;{{ option.postcode }},&nbsp;{{ option.country }}<br>
                        {{ option.contact_number }}
                    </small>
                </template>
            </multiselect>
        </template>
        <template v-else-if="['single_select', 'multi_select', 'multi_enum', 'option', 'single_select_or_input'].includes(typeLowerCase) && typeof options !== 'undefined' && ((options.constructor === Array && options.length === 0) || typeof options[0] === 'string')">
            <multiselect
                :id="id"
                :class="customClass"
                v-model="value"
                :options="customOptions"
                :placeholder="getPlaceholder"
                :show-labels="false"
                :allow-empty="typeof validator === 'undefined'"
                :multiple="typeLowerCase === 'multi_select' || typeLowerCase === 'multi_enum'"
                :close-on-select="typeLowerCase !== 'multi_select' && typeLowerCase !== 'multi_enum'"
                :clear-on-select="typeLowerCase !== 'multi_select' && typeLowerCase !== 'multi_enum'"
                @input="updateModel"
                @search-change="searchInputToOption"
                :disabled="customize"
            />
        </template>

        <template v-if="typeof isCustomizable === 'boolean'">
            <b-link @click="setCustomize"><u>{{customize? 'Customize' : 'Default'}}</u></b-link>
        </template>
    </div>
</template>

<script>
    import { required } from 'vuelidate/lib/validators';
    import cloneDeep from "bootstrap-vue/esm/utils/clone-deep";
    import isEqual from 'lodash/isEqual';
    import TinymceVue from "./TinymceVue";
    import VueDropzoneImage from "./VueDropzoneImage";
    export default {
        name: "InputFieldComponent",
        components: {VueDropzoneImage, TinymceVue},
        props: {
            // can be synced with parent model
            model: {
                // type: [String, Boolean, Object, Number], // missing type null, so use custom validator
                validator: prop => prop === null || typeof prop === 'string' || typeof prop === 'boolean' || typeof prop === 'object' || typeof prop === 'number',
                required: true
            },
            initModel: {
                validator: prop => prop === null || typeof prop === 'string' || typeof prop === 'boolean' || typeof prop === 'object' || typeof prop === 'number',
                default: undefined
            },
            validator: {
                type: Object,
                default: undefined
            },
            // normal props
            id: {
                type: String
            },
            type: {
                type: [String, Number],
                default: 'text'
            },
            placeholder: {
                type: String,
                default: undefined
            },
            disabled: {
                type: Boolean,
                default: false
            },
            readonly: {
                type: Boolean,
                default: false
            },
            formatter: {
                type: String,
                default: null
            },
            // vue-multiselect
            options: {
                type: Array
            },
            label: {
                type: String,
                default: 'name'
            },
            trackBy: {
                type: String,
                default: undefined
            },
            customOptionTemplate: {
                type: String,
                default: undefined
            },
            // extra settings can put here
            // accepted structure:
            // Object: {setting_name_1: true, setting_name_2: true} >> change to false if wanna prevent using it, user can make it works dynamically by playing with the boolean
            // Array: ['setting_name_1', 'setting_name_2'] >> all settings listed in array will be set to true
            // String: 'setting_name_1'(single) 'setting_name_1,setting_name_2'(multiple)  >> all settings listed in string will be set to true
            settings: {
                type: [Object, Array, String],
                default: undefined
            },
            nameField: {
                type: String,
                default: undefined
            }
        },
        data() {
            return {
                value: this.model,
                previousValue: this.model,
                multiValue: {},
                customValidator: undefined,
                customClass: '',
                customOptions: this.options,
                isCustomizable: undefined,
                customize: this.disabled,
                timer: null,
                loading: false,

            }
        },
        watch: {
            // Every time parent component change value watch here
            model(val) {
                if (['option_1', 'option_2', 'option_3'].includes(this.nameField)) {
                    this.value = val;
                    this.updateModelFromWatch();
                }
            },
        },
        computed: {
            typeLowerCase() {
                if (typeof this.type === 'string') {
                    // custom type mapping
                    if (this.type.toLowerCase() === 'numeric' || this.formatter === 'formatPrice') {
                        return 'number';
                    } else if (this.type.toLowerCase() === 'autocomplete') {
                        return 'text';
                    }
                    return this.type.toLowerCase();
                } else {
                    let type = {
                        0: 'text',
                        1: 'rich_text',
                        2: 'option',
                        3: 'single_select',
                        4: 'multi_select',
                        5: 'number',
                        6: 'date',
                        7: 'image',
                        8: 'text',
                        9: 'multi_enum',
                        10: 'radio',
                        11: 'multi_text',
                        12: 'checkbox_with_input', // left this no do
                        13: 'checkbox',
                        14: 'datetime',
                        15: 'switch',
                        16: 'single_select_or_input'
                    };

                    return type[this.type];
                }
            },
            extraSettings() {
                // convert array and string format settings to object
                if (Array.isArray(this.settings)) {
                    return this.settings.reduce((settings,setting) => ({...settings,[setting]:true}),{});
                } else if (typeof this.settings === 'string') {
                    return this.settings.split(',').reduce((settings,setting) => ({...settings,[setting]:true}),{});
                }
                return this.settings;
            },
            getPlaceholder() {
                // set default placeholder
                if (typeof this.placeholder === 'undefined') {
                    if (this.typeLowerCase === 'text') {
                        return 'Enter text';
                    } else if (this.typeLowerCase === 'single_select') {
                        return '-- Select an option --';
                    } else {
                        return '';
                    }
                }
                return this.placeholder;
            },
            getState() {
                if (typeof this.validator !== "undefined") {
                    return !this.$v.value.$invalid;
                }
                return null;
            },
            getTrackBy() {
                return this.trackBy !== undefined ? this.trackBy : this.label;
            }
        },
        beforeMount() {
            // setup custom validator if using other custom component
            // rich_text use tinymce-vue custom component
            if (this.typeLowerCase === 'rich_text') {
                this.customValidator = this.validator;
            } else if (this.typeLowerCase === 'image') {
                // change model value to empty array if it is string
                // image ise vue-dropzone-image custom component
                if (typeof this.value === 'string') {
                    this.value = [];
                }
            } else if (this.typeLowerCase === 'checkbox') {
                if (this.value === null) {
                    this.value = [];
                }
            } else if (this.typeLowerCase === 'datetime') {
                this.multiValue = {
                    date: '',
                    time: '00:00:00'
                };

                if (typeof this.value === 'string' && this.value !== '') {
                    let datetime = new Date(this.value);
                    let year = datetime.getFullYear();
                    let month = ('0' + (datetime.getMonth() + 1)).slice(-2); //month: 0-11
                    let day = ('0' + (datetime.getDate() + 1)).slice(-2);
                    let hour = ('0' + datetime.getHours()).slice(-2);
                    let minute = ('0' + datetime.getMinutes()).slice(-2);
                    let second = ('0' + datetime.getSeconds()).slice(-2);

                    this.multiValue.date = year + '-' + month + '-' + day;
                    this.multiValue.time = hour + ":" + minute + ":" + second;
                }
            } else if (this.typeLowerCase === 'multi_enum' || this.typeLowerCase === 'single_select' || this.typeLowerCase === 'single_select_or_input') {
                // auto match string value with array object main key's value, and replace it with the object
                // exp: 'def' >> {name: 'abc', value: 'def"}
                if (typeof this.value === 'string' || typeof this.value === 'number') {

                    if (this.customOptions.length > 0 && typeof this.customOptions[0] === 'object') {
                        // currently only support 2 type of object structure
                        // 1. {name: 'abc', value: 'def'}
                        // 2. {name: 'abc'}
                        let key = null;
                        if (this.customOptions[0].hasOwnProperty('value')) {
                            key = 'value';
                        } else if (this.customOptions[0].hasOwnProperty('name')) {
                            key = 'name';
                        }

                        if (key !== null) {
                            // search options
                            let newValue = this.customOptions.find(option => option[key] == this.value);
                            if (typeof newValue !== 'undefined') {
                                this.value = newValue;
                            } else if (this.typeLowerCase === 'single_select_or_input') {
                                // clone deep and add value that doesn't exist if necessary
                                this.customOptions = cloneDeep(this.options);

                                this.searchInputToOption(this.value);
                            }
                        }
                    } else if (this.typeLowerCase === 'single_select_or_input' && (this.customOptions.length === 0 || (this.customOptions.length > 0 && typeof this.customOptions[0] === 'string'))) {
                        // clone deep and add value that doesn't exist if necessary
                        this.customOptions = cloneDeep(this.options);

                        let findValue = this.customOptions.find(option => option == this.value);

                        if (typeof findValue === 'undefined') {
                            this.searchInputToOption(this.value);
                        }
                    }
                }
            } else if (this.typeLowerCase === 'multi_select') {
                if (typeof this.value === 'string') {
                    this.value = this.value.split(/\s*,\s*/);

                    // string array element to object array element
                    let valueArray = [];
                    for (let option of this.options) {
                        if (option.hasOwnProperty('name') && this.value.includes(option.name)) {
                            valueArray.push(option);
                        }
                    }
                    this.value = valueArray;
                }
            }

            // generate extraSettings before mount
            if (typeof this.extraSettings === 'object' && this.extraSettings.hasOwnProperty('customize')) {
                this.isCustomizable = true;
            }
            // update validator
            this.updateValidator();
        },
        mounted() {
            if (this.typeLowerCase === 'tags') {
                document.getElementById(this.id).classList.remove('h-auto');
            }
        },
        methods: {
            focusInputOption() {
                if (this.nameField) {
                    this.$emit('showSuggestOption', this.nameField);
                }
            },
            updateModelFromWatch() {
                if (this.nameField) {
                    this.$emit('hideSuggestOption', this.nameField);
                }
                this.$emit('update:model', this.value);
                this.previousValue = cloneDeep(this.value);
            },
            updateModelFromBlur() {
                if (this.nameField) {
                    this.$emit('hideSuggestOption', this.nameField);
                }
                this.$emit('update:model', this.value);
                this.updateValidator();

                if (typeof this.extraSettings === 'object') {
                    this.applySettings(this.value, this.previousValue);
                }

                this.previousValue = cloneDeep(this.value);
            },
            updateModel() {
                this.$emit('update:model', this.value);
                this.updateValidator();

                if (typeof this.extraSettings === 'object') {
                    this.applySettings(this.value, this.previousValue);
                }

                this.previousValue = cloneDeep(this.value);
            },
            updateValidator() {
                if (typeof this.validator !== "undefined" && typeof this.customValidator === "undefined") {
                    this.$emit('update:validator', {invalid: this.$v.value.$invalid, dirty: this.isDirty()});
                } else if (typeof this.validator !== "undefined" && typeof this.customValidator !== "undefined") {
                    // if value has been validate, send custom validated data back
                    this.$emit('update:validator', this.customValidator);
                }

                // set multiselect component state border color
                if (['single_select', 'multi_select', 'multi_enum', 'option'].includes(this.typeLowerCase) && typeof this.validator !== "undefined") {
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
            applySettings(value, previousValue) {
                for (let setting in this.extraSettings) {
                    if (this.extraSettings[setting]) {
                        // force attribute return string to parent model
                        if (setting === 'attribute_string_mode') {
                            // only re-update the model with string if needed
                            if (value.constructor === Object) {
                                // remove this also will work, just to prevent re-update model with same content
                                let diffChecker = false;
                                // currently only support 2 type of object structure
                                // 1. {name: 'abc', value: 'def'} >> return 'def'
                                // 2. {name: 'abc'} >> return 'abc'
                                if (value.hasOwnProperty('value')) {
                                    diffChecker = true;
                                    value = value.value;
                                } else if (value.hasOwnProperty('name')) {
                                    diffChecker = true;
                                    value = value.name;
                                }

                                if (diffChecker) {
                                    this.$emit('update:model', value);
                                }
                            }

                        // custom event: changes >> will pass new value tgt with previous value
                        } else if (setting === 'changes') {
                            this.$emit(setting, previousValue, value);
                        } else {
                            this.$emit(setting, value);
                        }
                    }
                }
            },
            singleLabel(value, placeholder) {
                // if empty, show placeholder
                if (this.isEmptyObject(value)) {
                    return placeholder;
                } else {
                    return value[this.label];
                }
            },
            updateDateTime() {
                if (this.multiValue.date !== '' && this.multiValue.time !== '') {
                    this.value = this.multiValue.date + ' ' + this.multiValue.time;
                    this.updateModel();
                }
            },
            searchInputToOption(value) {
                if (value !== '' && this.typeLowerCase === 'single_select_or_input') {
                    if (this.customOptions.length > 0 && this.customOptions[0].constructor === Object) {
                        if (this.customOptions[0].hasOwnProperty('value') && this.customOptions[0].hasOwnProperty('name')) {
                            value = {
                                name: value,
                                value: value
                            };
                        } else if (this.customOptions[0].hasOwnProperty('name')) {
                            value = {
                                name: value
                            };
                        }
                    }

                    if ((this.customOptions.length === 0 && this.customOptions === []) || this.customOptions.length === this.options.length) {
                        this.customOptions.push(value);
                    } else {
                        this.customOptions[this.options.length] = value;
                    }
                }
            },

            // Utility
            isEmptyObject(object) {
                return $.isEmptyObject(object);
            },
            stringToFunction(functionName) {
                return this[functionName];
            },
            formatPrice(value) {
                if (!isNaN(parseFloat(value)) && value.length > 0) {
                    return parseFloat(value).toFixed(2);
                } else {
                    return null;
                }
            },
        },
        validations() {
            if (typeof this.validator !== "undefined") {
                return {
                    value: {required}
                }
            }
            return {};
        }
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

    .force-strong {
        font-weight: bold !important;
    }

    .pl-switch {
        padding-left: 1.75rem !important;
    }
</style>
