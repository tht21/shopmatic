<template>
    <editor
        :class="borderStyle"
        v-model="content"
        :initial-value="model"
        :plugins="plugins"
        :toolbar="toolbar"
        :init="{...defaultInit, ...customInit}"
        @input="updateModel"
        :disabled="disabled"
    />
</template>

<script>
    import { required } from 'vuelidate/lib/validators'
    import isEqual from "lodash/isEqual";
    import 'tinymce/plugins/advlist/plugin'
    import 'tinymce/plugins/anchor/plugin'
    import 'tinymce/plugins/autolink/plugin'
    import 'tinymce/plugins/autoresize/plugin'
    import 'tinymce/plugins/autosave/plugin'
    import 'tinymce/plugins/charmap/plugin'
    import 'tinymce/plugins/code/plugin'
    import 'tinymce/plugins/codesample/plugin'
    import 'tinymce/plugins/directionality/plugin'
    import 'tinymce/plugins/emoticons/js/emojis'
    import 'tinymce/plugins/emoticons/plugin'
    import 'tinymce/plugins/fullpage/plugin'
    import 'tinymce/plugins/fullscreen/plugin'
    import 'tinymce/plugins/help/plugin'
    import 'tinymce/plugins/hr/plugin'
    import 'tinymce/plugins/image/plugin'
    import 'tinymce/plugins/imagetools/plugin'
    import 'tinymce/plugins/importcss/plugin'
    import 'tinymce/plugins/insertdatetime/plugin'
    import 'tinymce/plugins/link/plugin'
    import 'tinymce/plugins/lists/plugin'
    import 'tinymce/plugins/media/plugin'
    import 'tinymce/plugins/nonbreaking/plugin'
    import 'tinymce/plugins/noneditable/plugin'
    import 'tinymce/plugins/pagebreak/plugin'
    import 'tinymce/plugins/paste/plugin'
    import 'tinymce/plugins/preview/plugin'
    import 'tinymce/plugins/print/plugin'
    import 'tinymce/plugins/quickbars/plugin'
    import 'tinymce/plugins/save/plugin'
    import 'tinymce/plugins/searchreplace/plugin'
    import 'tinymce/plugins/spellchecker/plugin'
    import 'tinymce/plugins/table/plugin'
    import 'tinymce/plugins/template/plugin'
    import 'tinymce/plugins/textpattern/plugin'
    import 'tinymce/plugins/toc/plugin'
    import 'tinymce/plugins/visualblocks/plugin'
    import 'tinymce/plugins/visualchars/plugin'
    import 'tinymce/plugins/wordcount/plugin'
    import 'tinymce/icons/default'

    // check if content only has <p></p>, space or next line only
    const validateHtml = (value) => value.replace(/<p>|&nbsp;| |\n|<\/p>/g,'') !== '';

    export default {
        name: "TinymceVue",
        props: {
            // can be synced with parent model
            model: {
                type: String,
                required: true
            },
            initModel: {
                type: String,
                default: undefined
            },
            validator: {
                type: Object,
                default: undefined
            },
            // normal props
            plugins: {
                type: String,
                default: ''
            },
            toolbar: {
                type: String,
                default: ''
            },
            menubar: {
                type: [String, Boolean],
                default: false
            },
            contextmenu: {
                type: String,
                default: ''
            },
            height: {
                type: [Number, String],
                default: 300
            },
            customInit: {
                type: Object,
                default: () => ({})
            },
            type: {
                type: String,
                default: ''
            },
            disabled: {
                type: Boolean,
                default: false
            },
        },
        data() {
            return {
                defaultInit: {
                    skin: false,
                    height: this.height,
                    menubar: this.menubar,
                    contextmenu: this.contextmenu
                },
                content: this.model,
                borderStyle: ''
            }
        },
        beforeMount() {
            // set type here and store the init setting into defaultInit
            if (this.type === 'article') {
                this.defaultInit = {...this.defaultInit, ...{
                    selector: '.tinymce',
                    plugins: 'print preview fullpage paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
                    menubar: 'file edit view insert format tools table help',
                    toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
                    toolbar_sticky: true,
                    autosave_ask_before_unload: true,
                    autosave_interval: "30s",
                    autosave_prefix: "{path}{query}-{id}-",
                    autosave_restore_when_empty: false,
                    autosave_retention: "2m",
                    image_advtab: true,
                    importcss_append: true,
                    height: 400,
                    image_caption: true,
                    quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
                    noneditable_noneditable_class: "mceNonEditable",
                    toolbar_drawer: 'sliding',
                    contextmenu: "link image imagetools table",
                }};
            } else if (this.type === 'description') {
                this.defaultInit = {...this.defaultInit, ...{
                    contextmenu: "link image imagetools",
                    plugins: [
                        'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
                        'searchreplace visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
                        'save table directionality emoticons template paste imagetools'
                    ],
                    toolbar: 'insertfile undo redo | styleselect image link code preview | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent'
                }};
            }

            // update validator
            this.updateValidator();
            // setup border style
            this.setBorder();
        },
        methods: {
            updateModel(content) {
                // send content back to parent component after changes
                this.$emit('update:model', content);
                this.updateValidator();
                this.$emit('input', content);
                this.setBorder();
            },
            updateValidator() {
                if (typeof this.validator !== 'undefined') {
                    this.$emit('update:validator', {invalid: this.$v.content.$invalid, dirty: this.isDirty()});
                }
            },
            isDirty() {
                if (typeof this.validator !== "undefined" && typeof this.initModel !== "undefined") {
                    return !isEqual(this.content, this.initModel);
                }
                return null;
            },
            setBorder() {
                if (typeof this.validator !== 'undefined') {
                    if (!this.$v.content.$invalid) {
                        this.borderStyle = 'state-true';
                    } else {
                        this.borderStyle = 'state-false';
                    }
                }
            }
        },
        validations() {
            if (typeof this.validator !== 'undefined') {
                return {
                    content: {required, validateHtml}
                }
            }
            return {}
        }
    }
</script>

<style>
    .state-true + .tox.tox-tinymce {
        border-color: #38c172;
    }

    .state-false + .tox.tox-tinymce {
        border-color: #f6993f;
    }
</style>

<style scoped>

</style>

<!--
Sample: use tinymce-vue...
1) without any plugins or toolbar
<tinymce-vue :model.sync="bodyContent"/>
note: any changes in content will reflect to bodyContent, can just treat :model.sync like v-model

2) with custom height only
<tinymce-vue :model.sync="bodyContent" height="1000"/>

3) with custom plugins only
<tinymce-vue :model.sync="bodyContent" plugins="autolink link image"/>

4) with custom toolbar only
<tinymce-vue :model.sync="bodyContent" toolbar="image link"/>

5) with custom menubar only
<tinymce-vue :model.sync="bodyContent" menubar="file edit view"/>

6) with custom plugins, toolbar and menubar
<tinymce-vue :model.sync="bodyContent" plugins="autolink link image" toolbar="image link" menubar="file edit view"/>

7) with pre-defined init settings (recognize by `type` props)
<tinymce-vue :model.sync="description" type="description"/>
note: if wanna set a pre-defined init settings, can put ur code in beforeMount()

Priority: customInit > type > height, plugins, toolbar, menubar
Note:
- customInit will be loaded last, anything you set in customInit will cover all other small props such as height, plugins, toolbar, menubar
- customInit will also cover any default init grouping by type
- exp: if you wanna set height with `height` props, dont set it at customInit or type(default init)
-->
