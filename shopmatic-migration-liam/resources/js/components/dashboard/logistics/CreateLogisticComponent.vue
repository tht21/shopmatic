<template>
    <b-card>
        <form-wizard step-size="sm" :hide-buttons="true" title="" subtitle="" color="#5e72e4" ref="wizard" @on-change="onChange">
            <tab-content title="Order">
                <logistic-order-component @selectOrder="selectOrder"></logistic-order-component>
            </tab-content>
            <tab-content title="Logistic">
                <logistic-quote-component :selected_order="selected_order" @selectBookLogistic="selectBookLogistic"></logistic-quote-component>
            </tab-content>
            <tab-content title="Confirm">
                <logistic-confirm-component :selected_order="selected_order" :select_logistic="select_logistic" :selected_order_items="select_order_items" :form="form"></logistic-confirm-component>
            </tab-content>
        </form-wizard>
    </b-card>
</template>

<script>
    import VueFormWizard from 'vue-form-wizard'
    import LogisticOrderComponent from "./components/LogisticOrderComponent";
    import LogisticQuoteComponent from "./components/LogisticQuoteComponent";
    import LogisticConfirmComponent from "./components/LogisticConfirmComponent";

    export default {
        name: "CreateLogisticComponent",
        components: {
            LogisticConfirmComponent, LogisticQuoteComponent, LogisticOrderComponent, VueFormWizard},
        data() {
            return {
                selected_order: null,
                select_logistic: null,
                select_order_items: null,
                form: null,
            }
        },
        mounted() {
        },
        methods: {
            selectOrder(item) {
                this.selected_order = item;
                this.$refs.wizard.changeTab(0,1)
            },
            selectBookLogistic(logistic, select_order_items, form) {
                this.select_logistic = logistic;
                this.select_order_items = select_order_items;
                this.form = form;
                this.$refs.wizard.changeTab(1,2)
            },
            onChange(prevIndex, nextIndex) {
                this.$refs.wizard.maxStep = nextIndex;
                this.$refs.wizard.tabs.forEach((tab, index) => {
                    if(index > nextIndex) {
                        this.$refs.wizard.tabs[index].checked = false;
                    }
                })
            }
        }
    }
</script>

<style scoped>

</style>
