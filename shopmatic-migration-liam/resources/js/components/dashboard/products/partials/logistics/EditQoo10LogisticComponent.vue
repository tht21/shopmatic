<template>
    <div>
        <multiselect
            class="mb-3"
            v-model="options"
            :options="groups"
            placeholder="-- Select A Group --"
            label="name"
            track-by="name"
            :show-labels="false"
            :allow-empty="false">
<!--            <template slot="singleLabel" slot-scope="{ option }"><strong>{{ option.name }}</strong></template>-->
        </multiselect>
        <template v-if="options.value.length > 0">
            <b-form-radio
                v-for="option in options.value"
                v-model="value"
                :value="formatSelectedLogistic(option)"
                :key="'qoo10-logistic-radio-' + option.external_id"
                @input="updateModel">
                <strong>{{ option.name }}</strong><br/>
                <b-row>
                    <b-col sm="6">
                        Delivery Fee: {{ option.delivery_fee > 0 ? '$' + option.delivery_fee : 'Free' }}<br/>
                        Delivery Fee Type: {{ option.delivery_fee_type }}<br/>
                        <template v-if="option.free_condition !== 0">
                            Free Delivery Condition: >=${{ option.free_condition }}<br/>
                        </template>
                    </b-col>
                    <b-col sm="6">
                        Shipping Method: {{ option.shipping_method }}<br/>
                        Surcharge:&nbsp;
                        <template v-if="option.surcharge.length > 0">
                            <template v-for="charge in option.surcharge">
                                <b-badge variant="primary">{{ charge }}</b-badge>
                            </template>
                        </template>
                        <template v-else>
                            <b-badge variant="secondary">none</b-badge>
                        </template>
                        <br/>
                    </b-col>
                </b-row>
            </b-form-radio>
        </template>
    </div>
</template>

<script>
    export default {
        name: "EditQoo10LogisticComponent",
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
        },
        data() {
            return {
                value: this.model,
                options: {},
                validate: this.validator,
            }
        },
        computed: {
            groups() {
                let groups = [];
                for (let groupName in this.logistics) {
                    groups.push({
                        name: groupName,
                        value: this.logistics[groupName]
                    });
                }
                return groups;
            }
        },
        beforeMount() {
            // decode json string
            if (typeof this.value === 'string') {
                this.value = JSON.parse(this.value);
            }

            // can only choose 1 qoo10 logistic, extract array 1st data as value
            if (this.value.length > 0) {
                this.value = this.value[0];
            } else {
                this.value = {};
            }

            // pre-select group
            for (let group of this.groups) {
                for (let logistic of group.value) {
                    // auto select 1st group
                    if ($.isEmptyObject(this.options)) {
                        this.options = group;
                    }
                    if (!$.isEmptyObject(this.value) && this.value.external_id === logistic.external_id) {
                        this.options = group;
                        // used to update logistic data if format changed
                        this.value = this.formatSelectedLogistic(logistic);
                    }
                }
            }
            this.updateModel();
        },
        methods: {
            updateModel() {
                if (!$.isEmptyObject(this.value)) {
                    this.$emit('update:model', [this.value]);
                } else {
                    this.$emit('update:model', []);
                }

                this.updateValidator();
            },
            updateValidator() {
                if (typeof this.validate !== 'undefined') {
                    // initialize validator
                    if ($.isEmptyObject(this.validate)) {
                        this.validate = {
                            invalid: false,
                                dirty: null
                        };
                    }

                    this.validate.invalid = $.isEmptyObject(this.value);
                    this.$emit('update:validator', this.validate);
                }
            },
            formatSelectedLogistic(logistic) {
                return {
                    external_id: logistic.external_id,
                    type: logistic.type,
                    delivery_fee_type: logistic.delivery_fee_type,
                    delivery_fee: logistic.delivery_fee,
                    free_condition: logistic.free_condition
                }
            }
        }
    }
</script>

<style scoped>
    .custom-control.custom-radio >>> .custom-control-label {
        height: auto !important;
        width: 100%;
        margin-bottom: 0.5rem;
    }

    span.badge {
        top: -1px;
    }
</style>
