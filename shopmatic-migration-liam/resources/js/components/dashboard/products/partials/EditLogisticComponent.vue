<template>
    <div>
        <div v-if="withLabel && integrationId === 11003" class="font-weight-600 mb-2">Shipping Fee <span class="text-red"> *</span></div>
        <edit-shopee-logistic-component
            v-if="integrationId === 11003"
            :model.sync="value"
            :logistics="logistics"
            :validator.sync="validate"
            :with-label="withLabel"/>

        <div v-if="withLabel && integrationId === 11005" class="font-weight-600 mb-2">Shipping Information <span class="text-red"> *</span></div>
        <edit-qoo10-logistic-component
            v-if="integrationId === 11005"
            :model.sync="value"
            :logistics="logistics"
            :validator.sync="validate"/>
    </div>
</template>

<script>
    import EditShopeeLogisticComponent from "./logistics/EditShopeeLogisticComponent";
    import EditQoo10LogisticComponent from "./logistics/EditQoo10LogisticComponent";
    export default {
        name: "EditLogisticComponent",
        components: {
            EditQoo10LogisticComponent,
            EditShopeeLogisticComponent,
        },
        props: {
            // can be synced with parent model
            model: {
                type: [Array, String],
                default: []
            },
            validator: {
                type: Object,
                default: undefined
            },
            logistics: {
                type: [Array, Object],
                required: true
            },
            integrationId: {
                type: Number,
                required: true
            },
            withLabel: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                value: this.model,
                validate: this.validator
            }
        },
        watch: {
            value() {
                this.$emit('update:model', JSON.stringify(this.value));
            },
            validate() {
                if (typeof this.validate !== 'undefined') {
                    this.$emit('update:validator', this.validate);
                }
            },
        }
    }
</script>

<style scoped>

</style>
