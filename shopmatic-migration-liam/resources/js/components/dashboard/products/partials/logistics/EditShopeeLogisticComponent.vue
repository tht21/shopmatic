<template>
    <ul :class="['list-group', 'list-group-flush', {'my--3': !withLabel}]">
        <li class="list-group-item clearfix" v-for="(logistic, index) in logisticsList" v-if="logistic.enabled">
            <template>
                <div class="row">
                    <div class="col">
                        {{ logistic.logistic_name }}
                        <span class="float-right">
                            <label class="custom-toggle">
                                <input type="checkbox" @click="toggleCheckbox($event, index, 'select_logistic')" :checked="logistic.selected">
                                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"/>
                            </label>
                        </span>
                    </div>
                </div>

                <div class="row" v-if="logistic.selected">
                    <template v-if="logistic.fee_type === 'CUSTOM_PRICE'">
                        <div class="col">
                            <input type="text" class="form-control" placeholder="Shipping Fee" v-model="logistic.shipping_fee" :disabled="logistic.is_free" @input="updateShippingFee($event, index)">
                        </div>
                    </template>
                    <template v-if="logistic.fee_type === 'SIZE_SELECTION'">
                        <div class="col">
                            <input type="text" class="form-control" placeholder="Size Id" v-model="logistic.size_id" @input="updateModel">
                        </div>
                    </template>

                    <div class="col">
                        <div class="custom-control custom-checkbox mb-3">
                            <input class="custom-control-input" :id="'logistic-free-shipping-checkbox-[' + index + ']'" type="checkbox" :checked="logistic.is_free" @click="toggleCheckbox($event, index, 'free_shipping')">
                            <label class="custom-control-label" :for="'logistic-free-shipping-checkbox-[' + index + ']'">Free Shipping Fees</label>
                        </div>
                    </div>
                </div>
            </template>
        </li>
    </ul>
</template>

<script>
    export default {
        name: "EditShopeeLogisticComponent",
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
            withLabel: {
                type: Boolean,
                default: false
            }
        },
        data () {
            return {
                logisticsList: [],
                attributeLogistics: this.model,
                selectedLogistics: [],
                validateResult: this.validator
            }
        },
        beforeMount () {
            // If attribute logistic is string type then convert to object
            if (typeof (this.attributeLogistics) === "string") {
                this.attributeLogistics = JSON.parse(this.attributeLogistics);
            }
            Object.values(this.logistics).map((logistic) => {
                // Set default value first
                logistic['selected'] = false;
                logistic['shipping_fee'] = 0;
                logistic['is_free'] = false;
                logistic['size_id'] = null;

                // If got attribute then need append to logistic list
                if (this.attributeLogistics.length) {
                    let attributeLogistic = this.attributeLogistics.find(attributeLogistic => attributeLogistic.logistic_id == logistic.logistic_id);
                    if (attributeLogistic) {
                        let includedKeys = ['shipping_fee', 'is_free', 'size_id'];

                        Object.keys(attributeLogistic).map((key) => {
                            includedKeys.map((includedKey) => {
                                if (key === includedKey) {
                                    logistic[key] = attributeLogistic[key];
                                }
                            });
                        });
                        logistic['selected'] = true;
                    }
                }
                this.logisticsList.push(logistic);
            });
            this.updateModel();
        },
        methods: {
            toggleCheckbox (e, index, action) {
                // Make a copy of the row
                const logistic = this.logisticsList[index];

                // Update the value
                if (e.target.checked) {
                    if (action === 'select_logistic') {
                        logistic.selected = true;
                    }
                    if (action === 'free_shipping') {
                        logistic.is_free = true;
                    }
                } else {
                    if (action === 'select_logistic') {
                        logistic.selected = false;
                    }
                    if (action === 'free_shipping') {
                        logistic.is_free = false;
                    }
                }
                // Update it in the logistic list
                this.$set(this.logisticsList, index, logistic);

                this.updateModel();
            },
            updateShippingFee(e, index) {
                // Make a copy of the row
                const logistic = this.logisticsList[index];

                logistic.shipping_fee = e.target.value;

                // Update it in the logistic list
                this.$set(this.logisticsList, index, logistic);

                this.updateModel();
            },
            updateModel() {
                this.selectedLogistics = [];
                this.logisticsList.map((logistic) => {
                    // Convert the selected & enabled logistic format
                    if (logistic.selected && logistic.enabled) {
                        let format = {
                            enabled:logistic.enabled,
                            is_free: logistic.is_free,
                            logistic_id: logistic.logistic_id,
                            logistic_name: logistic.logistic_name,
                        };

                        if (logistic.fee_type === 'CUSTOM_PRICE') {
                            format['shipping_fee'] = logistic.shipping_fee;
                        }
                        if (logistic.fee_type === 'SIZE_SELECTION') {
                            format['size_id'] = logistic.size_id;
                        }
                        this.selectedLogistics.push(format);
                    }
                });
                this.$emit('update:model', this.selectedLogistics);
                this.updateValidator();
            },
            updateValidator() {
                if (typeof this.validator !== "undefined") {
                    if (typeof this.validateResult === undefined || Object.keys(this.validateResult).length === 0) {
                        this.validateResult = {
                            invalid: false,
                            dirty: null
                        };
                    }
                    if (this.selectedLogistics.length > 0) {
                        this.validateResult.invalid = false;
                        for (let logistic of this.selectedLogistics) {
                            if (logistic.hasOwnProperty('shipping_fee') && logistic.shipping_fee === '' && !logistic.is_free) {
                                this.validateResult.invalid = true;
                            }
                        }
                    } else {
                        this.validateResult.invalid = true;
                    }
                    this.$emit('update:validator', this.validateResult);
                }
            },
        },
        watch: {

        },
    }
</script>
