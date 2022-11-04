<template>
    <div>
        <b-card v-if="account" :class="[is_modal ? 'col-md-12 mb-0' : 'mx-md-auto col-md-8']">
            <b-card-header v-if="!is_modal">
                <img :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'"
                     class="account-integration-logo float-left pr-3" :title="account.id"/>
                <div>
                    <h2>Successfully added account</h2>
                    <h3>Please wait while we're setting up your account</h3>
                </div>
            </b-card-header>
            <form-wizard step-size="sm" :hide-buttons="true" title="" subtitle="" color="#5e72e4" ref="wizard">
                <tab-content title="Setup">
                    <account-setup-component :account="account" :currectIndex="0"
                                             @onChange="onChange"></account-setup-component>
                </tab-content>
                <tab-content title="Setting">
                    <account-setting-component :account="account" :currectIndex="1"
                                               @onChange="onChange"></account-setting-component>
                </tab-content>
                <tab-content title="Import">
                    <account-import-component :account="account" :currectIndex="2"></account-import-component>
                </tab-content>
            </form-wizard>
        </b-card>
    </div>
</template>

<script>
    import VueFormWizard from 'vue-form-wizard'
    import AccountSettingComponent from "./component/AccountSettingComponent";

    export default {
        name: "AccountSettingsComponent",
        components: {AccountSettingComponent},
        props: {
            is_modal: {
                type: Boolean,
                default: false,
            },
            account: {
                type: Object,
                default: null,
            }
        },
        data() {
            return {
                selected_account: null,
                settings: [],
                sending_request: false,
                saving: false
            }
        },
        created() {
        },
        methods: {
            onChange(currectIndex, nextIndex) {
                this.$refs.wizard.changeTab(currectIndex, nextIndex)
            },
        },
    }
</script>

<style scoped>

</style>
