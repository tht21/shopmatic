<template>
    <div>
        <b-row v-if="sameSkuSource">
            <b-col>
                <b-card body-class="pb-2">
                    <b-alert v-if="sameSkuSource" class="p-2" variant="danger" show>
                        <h3 class="m-0">
                            <i class="mr-3 fa-lg fa fa-exclamation-circle text-danger"></i>Different listings under same account with same product SKU. Edit this product will cause problems.
                        </h3>
                    </b-alert>
                </b-card>
            </b-col>
        </b-row>
        <b-row class="d-flex">
            <template v-if="form !== null">
                <b-col md="9" :key="key.all">
                    <b-card :id="headers.category_information.id" v-if="form.category[0]" :header="headers.category_information.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.category">
                        <b-form-group v-if="selectedSource.id === 0" id="product-category-group" label-class="font-weight-600" label-for="product-category-input">
                            <template #label>
                                Category<span class="text-red"> *</span>
                            </template>
                            <async-multiselect
                                type="single_select_category"
                                id="product-category-input"
                                :model.sync="form.category[0]"
                                :init-model="initForm.category[0]"
                                :validator.sync="validator.category[0]"
                                settings="input"
                                @input="syncCategorySelection"
                            />
                        </b-form-group>

                        <div :key="key.integrationCategory">
                            <div v-for="account in accounts" v-if="account.id !== 0 && (selectedSource.id === 0 || selectedSource.id === account.id) && form.category.hasOwnProperty(account.id) && account.has_category">
                                <b-form-group :id="'integration-category-group-' + account.id" class="m-0" label-class="font-weight-600" :label-for="'integration-category-input-' + account.id">
                                    <template #label>
                                        {{ integration_name(account) }}&nbsp;{{ account.region.shortcode }}&nbsp;({{ account.name }})<span class="text-red"> *</span>
                                    </template>

                                    <template v-if="account.has_category === 'integration'">
                                        <!--<input-field-component
                                            v-if="categories.hasOwnProperty(account.id) && categories[account.id].length > 0
                                             && typeof form.category[account.id] !== 'number'
                                             && (isEmptyObject(form.category[account.id])
                                             || typeof categories[account.id].find(category => category.value.id === form.category[account.id].id) !== 'undefined'
                                             || isNaN(form.category[account.id].id))"
                                            type="radio"
                                            :id="'integration-category-input-' + account.id"
                                            :model.sync="form.category[account.id]"
                                            :init-model="initForm.category[account.id]"
                                            :options="categories[account.id]"
                                            :validator.sync="validator.category[account.id]"
                                            settings="input"
                                            @input="getAttributes(form.category[account.id], account.id)"
                                        />
                                        <async-multiselect
                                            v-else-if="form.category[0].hasOwnProperty('id')"
                                            type="single_select_integration_category"
                                            :id="'integration-category-input-' + account.id"
                                            :integration-id="accountIdToIntegrationId[account.id]"
                                            :region-id="account.region_id"
                                            :model.sync="form.category[account.id]"
                                            :init-model="initForm.category[account.id]"
                                            :validator.sync="validator.category[account.id]"
                                            settings="input"
                                            @input="getAttributes(form.category[account.id], account.id)"
                                        />-->
                                        <async-multiselect
                                            type="single_select_integration_category"
                                            :id="'integration-category-input-' + account.id"
                                            :integration-id="accountIdToIntegrationId[account.id]"
                                            :region-id="account.region_id"
                                            :model.sync="form.category[account.id]"
                                            :init-model="initForm.category[account.id]"
                                            :validator.sync="validator.category[account.id]"
                                            settings="input"
                                            @input="getAttributes(form.category[account.id], account.id)"
                                        />
                                    </template>
                                    <template v-else>
                                        <async-multiselect
                                            type="single_select_account_category"
                                            :id="'account-category-input-' + account.id"
                                            :account-id="account.id"
                                            :model.sync="form.category[account.id]"
                                            :init-model="initForm.category[account.id]"
                                            :validator.sync="validator.category[account.id]"
                                            settings="input"
                                            @input="getAttributes(form.category[account.id], account.id)"
                                        />
                                    </template>
                                </b-form-group>
                            </div>
                        </div>
                    </b-card>
                    <b-card :id="headers.basic_information.id" :header="headers.basic_information.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.basicInformation">
                        <template v-for="name in Object.keys(form)" v-if="!['category', 'prices', 'images', 'variants', 'logistics', 'locations', 'brand'].includes(name) && !name.startsWith('option_')">
                            <b-form-group :id="'product-' + name + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'product-' + name + '-input'">
                                <template #label>
                                    {{ name | idToText }}<span class="text-red"> *</span>
                                </template>
                                <input-field-component
                                    :type="getFieldType(name)"
                                    :id="'product-' + name + '-input'"
                                    :model.sync="form[name]"
                                    :init-model="initForm[name]"
                                    :validator.sync="validator[name]"
                                    :placeholder="'Enter ' + name | idToText"
                                    :disabled.sync="customize[name]"
                                    :settings="name.startsWith('option_')? 'input' : customize.hasOwnProperty(name) && name !== 'associated_sku'? 'customize' :undefined"
                                    @input="key.variants += 1"
                                    @customize="resetDefault(name, null, 'basicInformation')"
                                />
                                <!--                                {{name === 'html_description' ? form.html_description : ''}}-->
                            </b-form-group>
                        </template>

                        <b-form-group v-if="typeof accounts.find(account => account.integration_id === 11001) !== 'undefined'" id="product-brand-group" label-class="font-weight-600" label-for="product-brand-input">
                            <template #label>
                                Brand<span class="text-red"> *</span>
                            </template>
                            <async-multiselect
                                type="single_select_brand"
                                id="product-brand-input"
                                :model.sync="form.brand"
                                :init-model="initForm.brand"
                                :validator.sync="validator.brand"
                                :disabled="selectedSource.id !== 0"
                                :region-id="accountIdToRegionId['region_id']"
                            />
                        </b-form-group>
                    </b-card>
                    <b-card :id="headers.prices.id" :header="headers.prices.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.prices">
                        <template v-if="form.prices.length > 0">
                            <template v-for="(price, priceIndex) in form.prices">
                                <b-form-group :id="'product-price-' + price.type + '-group'" :label="price.type + ' price:'" label-class="font-weight-600 text-capitalize" :label-for="'product-price-' + price.type + '-input'">
                                    <template #label>
                                        {{ price.type }} price<span class="text-red">{{ typeof validator.prices[price.type] !== 'undefined' ? ' *' : '' }}</span>
                                    </template>
                                    <input-field-component
                                        formatter="formatPrice"
                                        :id="'product-price-' + price.type + '-input'"
                                        :model.sync="price.price"
                                        :init-model="initForm.prices[priceIndex].price"
                                        :validator.sync="validator.prices[price.type]"
                                        :placeholder="'Enter ' + price.type + ' price'"
                                    />
                                </b-form-group>
                            </template>
                        </template>
                        <template v-else>
                            No price record.
                        </template>
                    </b-card>
                    <b-card :id="headers.images.id" :header="headers.images.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.images">
                        <template v-if="selectedSource.id !== 0">
                            <div class="font-weight-600 mb-1">Images<span class="text-red"> *</span></div>
                            <vue-dropzone-image
                                id="product-images"
                                :model.sync="form.images"
                                :init-model="initForm.images"
                                :validator.sync="validator.images"
                            />
                        </template>
                        <template v-else>
                            Image must edit separately for each account.
                        </template>
                    </b-card>
                    <b-card v-if="!isEmptyObject(logistics[selectedSource.id])" :id="headers.logistics.id" :header="headers.logistics.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.logistics">
                        <!--                    <template v-slot:header>-->
                        <!--                        {{ headers.logistics.id | idToText }} <span class="text-red"> *</span>-->
                        <!--                    </template>-->
                        <template v-if="selectedSource.id !== 0">
                            <edit-logistic-component
                                v-if="form.logistics && form.logistics.value"
                                :with-label="true"
                                :model.sync="form.logistics.value"
                                :logistics="logistics[selectedSource.id]"
                                :integration-id="form.logistics.integration_id"
                                :validator.sync="validator.logistics[selectedSource.id]"/>
                        </template>
                        <template v-else>
                            Logistics must set separately for each account.
                        </template>
                    </b-card>
                    <b-card v-if="accounts.hasOwnProperty(accountIdToAccountIndex[selectedSource.id]) && accounts[accountIdToAccountIndex[selectedSource.id]].locations.length > 0" :id="headers.locations.id" :header="headers.locations.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.locations">
                        <b-form-group id="product-locations-group" label-class="font-weight-600" label-for="product-locations-input">
                            <template #label>
                                Returned Address<span class="text-red"> *</span>
                            </template>
                            <input-field-component
                                type="single_select"
                                id="product-locations-input"
                                :model.sync="form.locations"
                                track-by="external_id"
                                :validator.sync="validator.locations[selectedSource.id]"
                                :options="accounts[accountIdToAccountIndex[selectedSource.id]].locations"
                                placeholder="-- Select a location --"
                                customOptionTemplate="locations"
                            />
                        </b-form-group>
                    </b-card>
                    <b-card :id="headers.attributes.id" :header="headers.attributes.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" :key="key.attributes">
                        <template v-for="account in accounts" v-if="selectedSource.id === 0 || selectedSource.id === account.id">
                            <b-card v-if="getAttributesByAccount(account.id, false, false, true)" :header="account | accountName" header-class="h3 border-bottom-0 pb-0">
                                <template v-for="attribute in getAttributesByAccount(account.id, false, true)" v-if="typeof validator['attributes'] !== 'undefined'">
                                    <b-form-group
                                        v-if="typeof sourceData[account.id]['attributes'][attribute.name] !=='undefined' && typeof validator.attributes[account.id][attribute.name] !== 'undefined' && (account.integration_id === 11003 || (account.integration_id !== 11003 && attribute.name.toLowerCase() !== 'brand'))"
                                        :id="'product-attributes-' + attribute.name + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'product-attributes-' + attribute.name + '-input'">
                                        <template #label>
                                            {{ attribute.label | idToText }}<span class="text-red">{{ typeof validator.attributes[account.id][attribute.name] !== 'undefined' ? ' *' : '' }}</span>
                                        </template>
                                        <input-field-component
                                            v-if="attribute.name !== 'logistics'"
                                            :type="getFieldType(attribute.type)"
                                            :id="'product-attributes-' + attribute.name + '-input'"
                                            :model.sync="sourceData[account.id].attributes[attribute.name].value"
                                            :validator.sync="validator.attributes[account.id][attribute.name]"
                                            :options="attribute.data"
                                            :placeholder="'Enter ' + attribute.name | idToText"
                                        />
                                    </b-form-group>
                                </template>

                                <b-button block v-b-toggle="'collapse-attributes-' + account.id" class="mb-2">
                                    <span>
                                        {{ typeof $refs['collapse-attributes-' + account.id] !== 'undefined' && typeof $refs['collapse-attributes-' + account.id][0] !== 'undefined' && $refs['collapse-attributes-' + account.id][0].show ? 'Hide' : 'Show' }} Optional Attributes
                                    </span>
                                </b-button>
                                <b-collapse :id="'collapse-attributes-' + account.id" :ref="'collapse-attributes-' + account.id">
                                    <br/>
                                    <template v-for="attribute in getAttributesByAccount(account.id, false, true)" v-if="typeof validator['attributes'] !== 'undefined'">
                                        <b-form-group
                                            v-if="typeof sourceData[account.id]['attributes'][attribute.name] !=='undefined' && typeof validator.attributes[account.id][attribute.name] === 'undefined' && attribute.name.toLowerCase() !== 'brand'"
                                            :id="'product-attributes-' + attribute.name + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'product-attributes-' + attribute.name.replace(/[^a-z\d\s]+/gi, '') + '-input'">

                                            <template #label>
                                                {{ attribute.label | idToText }}<span class="text-red">{{ typeof validator.attributes[account.id][attribute.name] !== 'undefined' ? ' *' : '' }}</span>
                                            </template>
                                            <input-field-component
                                                v-if="attribute.name !== 'logistics'"
                                                :type="getFieldType(attribute.type)"
                                                :id="'product-attributes-' + attribute.name + '-input'"
                                                :model.sync="sourceData[account.id].attributes[attribute.name].value"
                                                :validator.sync="validator.attributes[account.id][attribute.name]"
                                                :options="attribute.data"
                                                :placeholder="'Enter ' + attribute.name | idToText"
                                            />
                                        </b-form-group>
                                    </template>
                                </b-collapse>
                            </b-card>
                        </template>
                    </b-card>

                    <b-card :id="headers.variants.id" :header="headers.variants.id | idToText" header-class="h2 border-bottom-0 pb-0 text-capitalize" body-class="px-0 pb-0 pt-4">

                        <div class="px-4">
                            <b-form-group id="product-variant-mode-group" label="Mode" label-class="font-weight-600 text-capitalize" label-for="product-variant-mode-input">
                                <input-field-component
                                    type="switch"
                                    id="product-variant-mode-input"
                                    :model.sync="variantMode.selected"
                                    :options="variantMode.options"
                                    settings="input"
                                    :disabled="selectedSource.id !== 0 || createdSku === form.associated_sku"
                                    @input="resetVariants"
                                />
                            </b-form-group>
                        </div>

                        <template v-if="variantMode.selected[0] === 'matrix'">
                            <div v-for="name in getOptionList()" class="px-4">
                                <b-form-group :id="'product-' + name + '-group'" :label="name | idToText(false, true)" label-class="font-weight-600 text-capitalize" :label-for="'product-' + name + '-input'">
                                    <div class="d-flex" v-if="checkShowSuggestVariantName(name) && selectedIntegrationId == 0 && isCreate">
                                        <div :class="{'w-25': isCreate, 'w-100': !isCreate}">
                                        </div>
                                        <div class="w-75 margin-left-20">
                                            {{ name == 'option_1' ? "Values For Option 1" : "Values For Option 2"}}
                                            <b-button
                                                size="sm"
                                                variant="link"
                                                v-b-tooltip.hover.v-info
                                                :title="getTitleSuggestVariantName(name)"
                                                >
                                                <i class="fas fa-info-circle"></i>
                                            </b-button>
                                        </div>
                                    </div>
                                    <div class="d-flex">
                                        <input-field-component
                                        v-if="recommended_attributes.length == 0"
                                        :class="{'w-25': isCreate, 'w-100': !isCreate}"
                                        :type="getFieldType(name)"
                                        :id="'product-' + name + '-input'"
                                        :model.sync="form[name]"
                                        :init-model="initForm[name]"
                                        :validator.sync="validator[name]"
                                        :placeholder="'Enter ' + name | idToText"
                                        :disabled.sync="customize[name]"
                                        settings="input"
                                        @input="generateVariants"
                                        :disabled="isDisabled(name)"
                                        :nameField="name"
                                        v-on:showSuggestOption="showSuggestOption"
                                        v-on:hideSuggestOption="hideSuggestOption"
                                        />
                                        <multiselect
                                            v-else
                                            :id="'product-' + name + '-input'"
                                            :class="{'w-25': isCreate, 'w-100': !isCreate}"
                                            v-model="form[name]"
                                            :options="recommended_attributes"
                                            :placeholder="'Enter ' + name | idToText"
                                            :show-labels="false"
                                            :allow-empty="false"
                                            @close="chooseValueRecommend(name)"
                                        >
                                                <template slot="option" slot-scope="{ option }" disabled>
                                                    {{ option.label }}
                                                </template>
                                        </multiselect>
                                        <input-field-component
                                            v-if="isCreate"
                                            class="w-75 margin-left-20"
                                            type="tags"
                                            :id="'product-' + name + 'options-input'"
                                            :model.sync="form[name + '_options']"
                                            :init-model="initForm[name + '_options']"
                                            placeholder="Enter options separated by enter"
                                            :disabled.sync="customize[name + '_options']"
                                            settings="input"
                                            @input="generateVariants"
                                            :disabled="isDisabled(name)"
                                        />
                                    </div>
                                </b-form-group>
                            </div>
                        </template>

                        <b-tabs v-model="tabIndex.variants" class="mt-n3" content-class="mt-3" card :key="key.variants">
                            <b-tab v-for="(variant, variantId) in form.variants" :title="variant.name.length > 0? variant.name : 'Variant ' + variantId" :key="'variant-' + variant.id + '-'" lazy @click="changeShowVariant(variantId)">
                                <template v-slot:title>
                                    {{variant.name.length > 0? variant.name : 'Variant ' + variantId}}
                                    <!-- <button type="button" aria-label="Close" class="close" @click="showModal"> ×</button> -->
                                    <button v-if="!isCreate && Object.keys(form.variants).length > 1" type="button" aria-label="Close" class="close ml-1" @click="showModal(variant.id)"> ×</button>                               </template>                                <b-card :id="headers.variants.basic_information.id" :header="headers.variants.basic_information.id | idToText(true)" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                                    <template v-for="name in Object.keys(variant)"
                                              v-if="(!['length', 'width', 'height', 'weight', 'id', 'name', 'prices', 'inventory', 'images'].includes(name) ||
                                              (variantMode.selected.length === 0 && name === 'name')) &&
                                              (!name.startsWith('option_') || (name.startsWith('option_') && form[name] !== '')) &&
                                              (name !== 'name' || (name === 'name' && generateVariantName(variantId) === ''))">
                                        <b-form-group :id="'variant-' + variant.id + '-' + name + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-' + name + '-input'">
                                            <template #label>
                                                {{ name.startsWith('option_') ? form[name] : name.replace(/_/g, ' ') }}<span class="text-red"> *</span>
                                            </template>
                                            <input-field-component
                                                :formatter="getFieldType(name) === 'price' ? 'formatPrice' : null"
                                                :type="getFieldType(name) !== 'price' ? getFieldType(name) : 'text'"
                                                :id="'variant-' + variant.id + '-' + name + '-input'"
                                                :model.sync="variant[name]"
                                                :init-model="initForm.variants[variantId][name]"
                                                :validator.sync="validator.variants[variantId][name]"
                                                :placeholder="'Enter ' + name | idToText"
                                                :disabled.sync="customize.variants[variantId][name]"
                                                :settings="customize.variants[variantId].hasOwnProperty(name) && name !== 'sku' && name !== 'name' && !name.startsWith('option_')? 'customize' :(name.startsWith('option_')? 'input' :undefined)"
                                                @input="updateVariantName(variantId, name)"
                                                @customize="resetDefault(name, variantId, 'variants')"
                                            />
                                        </b-form-group>
                                    </template>
                                    <template v-for="name in Object.keys(variant)"
                                              v-if="name == 'weight'">
                                        <b-form-group :id="'variant-' + variant.id + '-' + name + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-' + name + '-input'">
                                            <template #label>
                                                {{ name.startsWith('option_') ? form[name] : name.replace(/_/g, ' ') }}<span class="text-red"> *</span>
                                            </template>
                                            <input-field-component
                                                :formatter="getFieldType(name) === 'price' ? 'formatPrice' : null"
                                                :type="getFieldType(name) !== 'price' ? getFieldType(name) : 'text'"
                                                :id="'variant-' + variant.id + '-' + name + '-input'"
                                                :model.sync="variant[name]"
                                                :init-model="initForm.variants[variantId][name]"
                                                :validator.sync="validator.variants[variantId][name]"
                                                :placeholder="'Enter ' + name | idToText"
                                                :disabled.sync="selectedIntegrationId !== 0 ? selectedIntegrationId !== 0 : variantId !== Object.keys(form.variants)[0]"
                                                @input="updateVariantName(variantId, name)"
                                                @customize="resetDefault(name, variantId, 'variants')"
                                                @change=""
                                            />
                                        </b-form-group>
                                    </template>
                                    <template v-for="name in Object.keys(variant)"
                                              v-if="(['length','width','height'].includes(name)) && selectedIntegrationId !== 11002 && name !== 'weight'">
                                        <b-form-group :id="'variant-' + variant.id + '-' + name + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-' + name + '-input'">
                                            <template #label>
                                                {{ name.startsWith('option_') ? form[name] : name.replace(/_/g, ' ') }}<span class="text-red"> *</span>
                                                <b-button
                                                    v-if="selectedIntegrationId == 0"
                                                    size="sm"
                                                    variant="link"
                                                    v-b-tooltip.hover.v-info
                                                    title="Shopify product details do not include width, so this field will not be reflected in Shopify">
                                                    <i class="fas fa-info-circle"></i>
                                                </b-button>
                                                
                                            </template>
                                            <input-field-component
                                                :formatter="getFieldType(name) === 'price' ? 'formatPrice' : null"
                                                :type="getFieldType(name) !== 'price' ? getFieldType(name) : 'text'"
                                                :id="'variant-' + variant.id + '-' + name + '-input'"
                                                :model.sync="variant[name]"
                                                :init-model="initForm.variants[variantId][name]"
                                                :validator.sync="validator.variants[variantId][name]"
                                                :placeholder="'Enter ' + name | idToText"
                                                :disabled.sync="selectedIntegrationId !== 0 ? selectedIntegrationId !== 0 : variantId !== Object.keys(form.variants)[0]"
                                                @input="updateVariantName(variantId, name)"
                                                @customize="resetDefault(name, variantId, 'variants')"
                                            />
                                        </b-form-group>
                                    </template>
                                </b-card>
                                <b-card :id="headers.variants.prices_inventory.id" :header="headers.variants.prices_inventory.id | idToText(true)" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                                    <template v-if="typeof validator.variants[variantId].prices !== 'undefined' && variant.prices.length > 0">
                                        <template v-for="(price, priceIndex) in variant.prices">
                                            <b-form-group :class="hideCurrency(selectedIntegrationId, price.type, price.currency )" :id="'variant-' + variant.id + '-price-' + price.type + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-price' + price.type + '-input'">
                                                <template #label>
                                                    {{ price.type }} price <span class="text-blue">{{typeof price.currency !=='undefined' ? "[" + price.currency + "]":''}}</span><span class="text-red">{{ typeof validator.variants[variantId].prices[price.type] !== 'undefined' ? ' *' : '' }} </span>
                                                <b-button
                                                    v-if="selectedIntegrationId == 11004 && price.type === 'selling'"
                                                    size="sm"
                                                    variant="link"
                                                    v-b-tooltip.hover.v-info
                                                    title="Price of variant excluding main product price. This definition is specific to Qoo10">
                                                    <i class="fas fa-info-circle"></i>
                                                </b-button>
                                                <b-button
                                                    v-if="selectedIntegrationId == 0 && price.type === 'selling'"
                                                    size="sm"
                                                    variant="link"
                                                    v-b-tooltip.hover.v-info
                                                    title="Total price of the variant. This applies to all marketplaces except for Qoo10. Navigate to the Qoo10 source page to set this">
                                                    <i class="fas fa-info-circle"></i>
                                                </b-button>
                                                </template>
                                                <input-field-component
                                                    v-if="price.type === 'selling'"
                                                    formatter="formatPrice"
                                                    :id="'variant-' + variant.id + '-price' + price.type + '-input'"
                                                    :model.sync="price.price"
                                                    :init-model="initForm.variants[variantId].prices[priceIndex].price"
                                                    :validator.sync="validator.variants[variantId].prices[price.type]"
                                                    :placeholder="'Enter ' + price.type + ' price'"
                                                    :disabled.sync="customize.variants[variantId].prices"
                                                    :settings="customize.variants[variantId].hasOwnProperty('prices') ? 'customize' : undefined"
                                                    @customize="resetDefault('prices', variantId, 'variants')"
                                                />
                                                <input-field-component
                                                    v-else
                                                    formatter="formatPrice"
                                                    :id="'variant-' + variant.id + '-price' + price.type + '-input'"
                                                    :model.sync="price.price"
                                                    :init-model="initForm.variants[variantId].prices[priceIndex].price"
                                                    :validator.sync="validator.variants[variantId].prices[price.type]"
                                                    :placeholder="'Enter ' + price.type + ' price'"
                                                />
                                            </b-form-group>
                                        </template>
                                    </template>
                                    <template v-else>
                                        <p>No price record.</p>
                                    </template>
                                    <b-form-group :id="'variant-' + variant.id + '-group'" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-inventory-input'">
                                        <template #label>
                                            inventory<span class="text-red"> *</span>
                                        </template>
                                        <async-multiselect
                                            type="single_select_inventory"
                                            :id="'variant-' + variant.id + '-inventory-input'"
                                            custom-class="state-true"
                                            :model.sync="variant.inventory"
                                            :validator.sync="validator.variants[variantId].inventory"
                                            placeholder="-- Type to search (sku/name) --"
                                            :disabled.sync="customize.variants[variantId].inventory"
                                        />
                                        <template v-if="selectedSource.id === 0">
                                            <b-link @click="showInventoryModal(variantId)"><u>Add</u></b-link>
                                        </template>
                                    </b-form-group>

                                </b-card>
                                <b-card :id="headers.variants.images.id" :header="headers.variants.images.id | idToText(true)" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                                    <template v-if="selectedSource.id !== 0">
                                        <div class="font-weight-600 mb-1">Images</div>
                                        <vue-dropzone-image
                                            :id="'variant-' + variant.id + '-product-images'"
                                            :model.sync="variant.images"
                                            :init-model="initForm.variants[variantId].images"
                                        />
                                    </template>
                                    <template v-else>
                                        Image must edit separately for each account.
                                    </template>
                                </b-card>
                                <b-card :id="headers.variants.attributes.id" :header="headers.variants.attributes.id | idToText(true)" header-class="h2 border-bottom-0 pb-0 text-capitalize">
                                    <template v-for="account in accounts" v-if="selectedSource.id === 0 || selectedSource.id === account.id">
                                        <b-card v-if="getAttributesByAccount(account.id, true, false, true)" :header="account | accountName" header-class="h3 border-bottom-0 pb-0">
                                            <template v-for="attribute in getAttributesByAccount(account.id, true, true)" v-if="typeof sourceData[account.id].variants !== 'undefined' && typeof validator.variants[variantId].attributes !== 'undefined'">
                                                <b-form-group
                                                    v-if="typeof sourceData[account.id].variants[variantId]['attributes'][attribute.name] !=='undefined' && typeof validator.variants[variantId].attributes[account.id][attribute.name] !== 'undefined'"
                                                    :id="'variant-' + variant.id + '-attributes-' + attribute.name + '-group'" :label="attribute.label | idToText(false, true)" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-attributes-' + attribute.name + '-input'">
                                                    <template #label>
                                                        {{ attribute.label | idToText }}<span class="text-red">{{ typeof validator.variants[variantId].attributes[account.id][attribute.name] !== 'undefined' ? ' *' : '' }}</span>
                                                    </template>
                                                    <input-field-component
                                                        :type="getFieldType(attribute.type)"
                                                        :id="'variant-' + variant.id + '-attributes-' + attribute.name + '-input'"
                                                        :model.sync="sourceData[account.id].variants[variantId].attributes[attribute.name].value"
                                                        :validator.sync="validator.variants[variantId].attributes[account.id][attribute.name]"
                                                        :options="attribute.data"
                                                        :placeholder="'Enter ' + attribute.name | idToText"
                                                    />
                                                </b-form-group>
                                            </template>

                                            <b-button block v-b-toggle="'collapse-' + variant.id + '-attributes-' + account.id">
                                                <span>
                                                    {{ typeof $refs['collapse-' + variant.id + '-attributes-' + account.id] !== 'undefined' && typeof $refs['collapse-' + variant.id + '-attributes-' + account.id][0] !== 'undefined' && $refs['collapse-' + variant.id + '-attributes-' + account.id][0].show ? 'Hide' : 'Show' }} Optional Attributes
                                                </span>
                                            </b-button>
                                            <b-collapse :id="'collapse-' + variant.id + '-attributes-' + account.id" :ref="'collapse-' + variant.id + '-attributes-' + account.id">
                                                <br/>
                                                <template v-for="attribute in getAttributesByAccount(account.id, true, true)" v-if="typeof sourceData[account.id].variants !=='undefined' && typeof validator.variants[variantId].attributes !== 'undefined'">
                                                    <b-form-group
                                                        v-if="typeof sourceData[account.id].variants[variantId]['attributes'][attribute.name] !=='undefined' && typeof validator.variants[variantId].attributes[account.id][attribute.name] === 'undefined'"
                                                        :id="'variant-' + variant.id + '-attributes-' + attribute.name + '-group'" :label="attribute.label | idToText(false, true)" label-class="font-weight-600 text-capitalize" :label-for="'variant-' + variant.id + '-attributes-' + attribute.name + '-input'">
                                                        <template #label>
                                                            {{ attribute.label | idToText }}<span class="text-red">{{ typeof validator.variants[variantId].attributes[account.id][attribute.name] !== 'undefined' ? ' *' : '' }}</span>
                                                        </template>
                                                        <input-field-component
                                                            :type="getFieldType(attribute.type)"
                                                            :id="'variant-' + variant.id + '-attributes-' + attribute.name + '-input'"
                                                            :model="convertDataAttribute(attribute.type, sourceData[account.id].variants[variantId].attributes[attribute.name].value)"
                                                            :validator.sync="validator.variants[variantId].attributes[account.id][attribute.name]"
                                                            :options="attribute.data"
                                                            :placeholder="'Enter ' + attribute.name | idToText"
                                                        />
                                                    </b-form-group>
                                                </template>
                                            </b-collapse>
                                        </b-card>
                                    </template>
                                </b-card>
                            </b-tab>

                            <template v-if="!isCreate" v-slot:tabs-end>
                                <b-nav-item :class="{'show-border': isEmptyObject(form.variants)}" role="presentation" @click.prevent="addVariants()" href="#"><b>+</b></b-nav-item>
                            </template>

                            <template v-if="!isCreate" v-slot:empty>
                                <div class="text-center text-muted">
                                    Create a new variant using the <b>+</b> button above.
                                </div>
                            </template>
                        </b-tabs>
                    </b-card>
                </b-col>
                <b-col md="3" class="sticky-top sticky-height mt-n4">
                    <b-card class="h-100 mt-4">
                        <!--                        <h2>Source:</h2>-->
                        <input-field-component
                            class="mb-2"
                            type="single_select"
                            id="product-data-source"
                            :model.sync="selectedSource"
                            :validator="{}"
                            :options="sourceOption"
                            placeholder="-- Select a Source --"
                            settings="input"
                            @input="changeSource()"
                            :key="key.source"
                        />
                        <!--                        <h2>Sidebar Navigation</h2><br/>-->
                        <div class="sidebar-nav">
                            <b-nav pills vertical v-b-scrollspy:main-body.150> <!-- v-b-scrollspy:main-body-id.offset -->
                                <template v-for="header in headers" v-if="!['logistics', 'locations'].includes(header.id) || (header.id === 'logistics' && !isEmptyObject(logistics[selectedSource.id]) || (header.id === 'locations' && accounts.hasOwnProperty(accountIdToAccountIndex[selectedSource.id]) && accounts[accountIdToAccountIndex[selectedSource.id]].locations.length > 0))">
                                    <b-nav-item class="text-capitalize" :href="'#' + header.id">{{header.id | idToText}}</b-nav-item>

                                    <b-nav v-if="Object.keys(header).length > 2 && (header.id !== 'variants' || (header.id === 'variants' && !isEmptyObject(form.variants)))" pills vertical>
                                        <template v-for="subHeader in header" v-if="typeof subHeader === 'object'">
                                            <b-nav-item class="text-capitalize ml-3 my-1" :href="'#' + subHeader.id">{{subHeader.id | idToText(true)}}</b-nav-item>
                                        </template>
                                    </b-nav>
                                </template>
                            </b-nav></div>

                        <div class="sidebar-button">
                            <b-button v-if="isCreate" class="w-100 mx-0 my-2" variant="primary" @click="create">Create</b-button>
                            <b-button v-if="!isCreate" class="w-100 mx-0 my-2" variant="primary" @click="save">Save</b-button>
                            <b-button id="reset-button" class="w-100 mx-0 my-2" variant="danger" @click="resetForm">
                                Undo All&nbsp;<span id="reset-info" class="fa fa-info-circle"></span>
                            </b-button>
                            <b-tooltip target="reset-button" triggers="hover" placement="bottom">
                                Load last save state
                            </b-tooltip>
                        </div>
                    </b-card>
                </b-col>
            </template>

            <b-modal id="pre-select-account-modal" ref="pre-select-account-modal" size="lg" hide-header hide-footer no-close-on-backdrop no-close-on-esc no-enforce-focus>

                <h2 class="font-weight-light text-primary text-center">Where do you want to export to?</h2>
                <hr/>
                <div class="row">
                    <template v-if="accounts.length && isCreate">
                        <div v-for="account in accounts" class="col-4">
                            <div :class="{'account-box mb-0': true, 'selected-account': preSelectAccount.find(acc => acc.id === account.id)}" @click="selectAccount(account)">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <img :src="'/images/integrations/' + integration_name(account).toLowerCase() + '.png'" height="50" width="50" />
                                    </div>
                                    <div class="col-8 text-truncate">
                                        <small class="text-uppercase text-info">{{ account.name }}</small><br />
                                        <small class="text-muted">in {{ account.region.name }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="col">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                                <span class="alert-text">No Account Found</span>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="pt-3" v-if="preSelectAccount.length > 0">
                    <b-button class="float-right" variant="primary" @click="generateForm">Proceed</b-button>
                </div>
            </b-modal>

            <b-modal id="add-inventory-modal" ref="add-inventory-modal" size="sm" title="Add New Inventory" header-class="align-items-center" hide-footer no-close-on-backdrop no-close-on-esc no-enforce-focus>
                <b-form-group
                    id="add-inventory-sku-group" label="SKU:" label-class="font-weight-600 text-capitalize" label-for="add-inventory-sku-input">
                    <input-field-component
                        type="text"
                        id="add-inventory-sku-input"
                        :model.sync="newInventory.value.sku"
                        :validator="{}"
                        placeholder="Enter SKU"
                    />
                </b-form-group>
                <b-form-group
                    id="add-inventory-name-group" label="Name:" label-class="font-weight-600 text-capitalize" label-for="add-inventory-name-input">
                    <input-field-component
                        type="text"
                        id="add-inventory-name-input"
                        :model.sync="newInventory.value.name"
                        :validator="{}"
                        placeholder="Enter Name"
                    />
                </b-form-group>
                <b-form-group
                    id="add-inventory-stock-group" label="Stock:" label-class="font-weight-600 text-capitalize" label-for="add-inventory-stock-input">
                    <input-field-component
                        type="number"
                        id="add-inventory-stock-input"
                        :model.sync="newInventory.value.stock"
                        :validator="{}"
                        placeholder="Enter Stock"
                    />
                </b-form-group>
                <b-form-group
                    id="add-inventory-low-stock-notification-group" label="Low Stock Notification:" label-class="font-weight-600 text-capitalize" label-for="add-inventory-low-stock-notification-input">
                    <input-field-component
                        type="number"
                        id="add-inventory-low-stock-notification-input"
                        :model.sync="newInventory.value.lowStockNotification"
                        :validator="{}"
                        placeholder="Enter Low Stock Notification"
                    />
                </b-form-group>

                <div class="text-center">
                    <b-button class="float-right" variant="primary" @click="addInventory()">Add</b-button>
                </div>
            </b-modal>

            <b-modal id="qoo10-warning-price-modal" ref="qoo10-warning-price-modal" size="md"
                header-bg-variant="white" :no-close-on-backdrop="true">
                <template v-slot:modal-header="{ close }">
                    <h2 class="mb-0 text-black">CONFIRMATION</h2>
                </template>
                <div class="alert badge-success d-flex alert-dismissible fade show" role="alert">
                    <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                    <span class="alert-text">
                        <h3 v-if="isCreate">Definitions of the main product price and variant price under Qoo10 source are different from that of the other sources. Ensure you have set the correct prices before creating the product.</h3>
                        <h3 v-else>Definitions of the main product price and variant price under Qoo10 source are different from that of the other sources. Ensure you have set the correct prices before updating the product.</h3>
                    </span>
                </div>
                <hr class="mb-0">
                <h2 class="text-center">Do you want to proceed with product {{ isCreate ? "creation" : "updation" }}?</h2>
                <template v-slot:modal-footer="{ Yes, No }">
                        <b-button variant="success" class="ml-auto" @click="continueProgress">Yes</b-button>
                        <b-button variant="danger" class="mr-auto" @click="closeQoo10WarningPriceModel">No</b-button>
                </template>
            </b-modal>

            <b-modal id="progress-modal" ref="progress-modal" size="lg" :title="isCreate ? 'Creating Product' : 'Updating Product'" header-class="align-items-center" hide-footer no-close-on-backdrop no-close-on-esc no-enforce-focus hide-header-close>
                <div :key="key.progress">
                    <template v-for="(accountProgress, index) in progress">
                        <span class="font-weight-600 text-capitalize mb-1">{{ accountProgress.name }}</span><b-badge class="mb-1 mx-2" :variant="accountProgress.state | stateToVariant">{{ accountProgress.state }}</b-badge>
                        <b-button v-if="accountProgress.state === 'Failed'" class="p-1 badge-button" variant="info" size="sm" @click="retry(index)">Retry</b-button>
                        <b-progress max="100" height="20px" :animated="accountProgress.state === 'Processing'">
                            <b-progress-bar :value="accountProgress.value" :label="accountProgress.value + '%'"></b-progress-bar>
                        </b-progress>
                        <div v-if="accountProgress.error.length > 0" class="bg-danger rounded mb-2 p-2 text-white">
                            <template v-for="error in accountProgress.error">
                                {{ error }}<br>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="text-center">
                    <b-button v-if="(!isCreate || (isCreate && !checkProgress(true))) && (checkProgress() || enableClose)" class="float-right" variant="primary" @click="hideProgressModal">Close</b-button>
                    <b-button v-if="isCreate && checkProgress(true)" class="float-right" variant="primary" @click="window.location.href = '/dashboard/products/' + productSlug">Proceed</b-button>
                </div>
            </b-modal>
        </b-row>
        <template>
            <b-modal id="modal-delete-variant" ref="modal-delete-variant" size="md"
                header-bg-variant="white"  >
                <template v-slot:modal-header="{ close }">
                    <h2 class="mb-0 text-black">CONFIRMATION</h2>
                    <button type="button" class="close" aria-label="Close" @click="hideModal">
                        <span aria-hidden="true" class="text-black">×</span>
                    </button>
                </template>
                <hr class="mt-0">
                <div class="alert badge-danger d-flex alert-dismissible fade show" role="alert">
                    <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                    <span class="alert-text">
                        <h3>This will delete this variant option for the product on Combinesell and the marketplace(s)</h3>
                    </span>
                </div>
                <hr class="mb-0">
                <h2 class="text-center mt-3">Are you sure?</h2>
                <template v-slot:modal-footer="{ Yes, No }">
                    <b-button variant="success" class="ml-auto" @click="clearVariant">Yes</b-button>
                    <b-button variant="danger" class="mr-auto" @click="hideModal">No</b-button>
                </template>
            </b-modal>
        </template>
    </div>
</template>

<script>
    import cloneDeep from "bootstrap-vue/esm/utils/clone-deep";
    import isEqual from "lodash/isEqual";
    import mergeDeep from "lodash/merge";
    import InputFieldComponent from "../../utility/InputFieldComponent";
    import VueDropzoneImage from "../../utility/VueDropzoneImage";
    import AsyncMultiselect from "../../utility/AsyncMultiselect";
    import EditLogisticComponent from "./partials/EditLogisticComponent";

    const axios = require('axios').default;
    export default {
        name: "EditProductComponent",
        components: {
            EditLogisticComponent,
            AsyncMultiselect,
            VueDropzoneImage,
            InputFieldComponent
        },
        props: [
            'product'
        ],
        data() {
            return {
                deleteVariantId: null,
                headers: {
                    category_information: {
                        id: 'category_information',
                        invalid: 0
                    },
                    basic_information: {
                        id: 'basic_information',
                        invalid: 0
                    },
                    prices: {
                        id: 'prices',
                        invalid: 0
                    },
                    images: {
                        id: 'images',
                        invalid: 0
                    },
                    logistics: {
                        id: 'logistics',
                        invalid: 0
                    },
                    locations: {
                        id: 'locations',
                        invalid: 0
                    },
                    attributes: {
                        id: 'attributes',
                        invalid: 0
                    },
                    variants: {
                        id: 'variants',
                        invalid: 0,
                        basic_information: {
                            id: 'variant_basic_information',
                            invalid: 0
                        },
                        prices_inventory: {
                            id: 'variant_prices_and_inventory',
                            invalid: 0
                        },
                        images: {
                            id: 'variant_images',
                            invalid: 0
                        },
                        attributes: {
                            id: 'variant_attributes',
                            invalid: 0
                        },
                    },
                },
                valueChooseRecomment: null,
                // data - api
                accounts: [],
                logistics: {},
                prices: {},
                categories: {},
                attributes: {
                    integration: {},
                    account: {},
                    none: {},
                },
                suggestedValueOption1: "",
                suggestedValueOption2: "",
                // frontend data set
                sourceData: {},
                form: null,
                initForm: null,
                validator: null,
                customize: null,
                newInventory: {
                    index: null,
                    value: {
                        sku: '',
                        name: '',
                        stock: '0',
                        lowStockNotification: '5',
                    }
                },
                initCategory: null,
                productSlug: null,
                createdSku: null,
                variantMode: {
                    selected: [],
                    options: [
                        {
                            text: 'Multiple Options',
                            value: 'matrix'
                        }
                    ]
                },
                // source
                preSelectAccount: [],
                selectedSource: {id: 0, name: 'main'},
                selectedIntegrationId: 0,
                selectedAccount: {},
                is_sale_prop_require: 0,
                recommended_attributes: [],
                previousSource: {id: 0, name: 'main'},
                // progress bar
                progress: {},
                enableClose: false,
                // key
                key: {
                    all: 'key-all-',
                    category: 'key-category-',
                    integrationCategory: 'key-integration-category-',
                    basicInformation: 'key-basic-information-',
                    images: 'key-images-',
                    prices: 'key-prices-',
                    logistics: 'key-logistics-',
                    locations: 'key-locations-',
                    attributes: 'key-attributes-',
                    variants: 'key-variants-',
                    progress: 'key-progress-',
                    source: 'key-source-',
                },
                // b-tabs current index
                tabIndex: {
                    variants: 0
                },
                // validate result
                validated: false
            }
        },
        computed: {
            isCreate() {
                return typeof this.product === 'undefined';
            },
            sourceOption() {
                let sourceOption = [
                    {id: 0, name: 'main'},
                    ...this.accounts.map(
                        account => ({
                            id: account.id,
                            name: this.$options.filters.accountName(account),
                            $isDisabled: account.hasOwnProperty('exported') ? account.exported : false
                        })
                    )
                ];
                // re-render source option selector
                this.key.source += 1;
                return sourceOption;
            },
            accountIdToIntegrationId() {
                return this.accounts.reduce((accIdToIntIdList, account) => ({...accIdToIntIdList, [account.id]: account.integration_id}), {});
            },
            accountToIntegrationRegionId() {
                return this.accounts.reduce((accIdToIntIdList, account) => ({...accIdToIntIdList, [account.id]: account.region_id}), {});
            },
            accountIdToAccountIndex() {
                return this.accounts.reduce((idToIndexList, account, index) => ({...idToIndexList, [account.id]: index}), {});
            },
            accountIdToCategoryType() {
                return this.accounts.reduce((idToCategoryTypeList, account) => {
                    if (!account.has_category) {
                        return {...idToCategoryTypeList, [account.id]: 'none'};
                    }
                    return {...idToCategoryTypeList, [account.id]: account.has_category};
                }, {});
            },
            sameSkuSource() {
                if (!this.isCreate && this.product.listings.length > 0) {
                    let accountIds = [];
                    for (let id in this.product.listings) {
                        if (accountIds.includes(this.product.listings[id].account_id)) {
                            return true;
                        }
                        accountIds.push(this.product.listings[id].account_id);
                    }
                }
                return false;
            },
            accountIdToRegionId() {
                let accountInfo = this.accounts.find(account => account.integration_id === 11001);
                return accountInfo;
            }
        },
        async created() {
            await this.getAccounts();
            if (!this.isCreate) {
                this.resetForm();
                this.setupSourceData();
                this.syncCategorySelection(this.form.category[0], false);
            } else {
                this.$refs['pre-select-account-modal'].show();
            }
        },
        methods: {
            getOptionList() {
                if(this.checkHaveLazadaIntegration()) {
                    return ['option_1', 'option_2'];
                }
                return ['option_1', 'option_2', 'option_3'];
            },
            hideCurrency(selectedIntegrationId, priceType, priceCurrency) {
                if (this.selectedAccount && this.selectedAccount.region_id == 1) {
                    if(selectedIntegrationId == 11004 && priceType === 'selling' && priceCurrency == 'SGD') {
                        return "d-none";
                    }  
                }
                if(selectedIntegrationId == 0 && priceType === 'selling' && priceCurrency == 'USD') {
                        return "d-none";
                    }
                return ""
            },
            closeQoo10WarningPriceModel() {
                this.$refs['qoo10-warning-price-modal'].hide();
            },
            continueProgress() {
                if (this.isCreate) {
                    this.continueCreate();
                } else {
                    this.continueSave();
                }
            },
            checkShowSuggestVariantName(name) {
                return (name == "option_1" && this.suggestedValueOption1) || (name == "option_2" && this.suggestedValueOption2);
            },
            getTitleSuggestVariantName(name) {
                if (name == "option_1") {
                    return "Suggested values: " + this.suggestedValueOption1;
                }
                return "Suggested values: " + this.suggestedValueOption2;
            },
            chooseValueRecommend(name) {
                if (this.form[name].hasOwnProperty("label")) {
                    let dataSelected = this.form[name].data;
                    this.form[name] = this.form[name].label;
                    if (name == "option_1") {
                        this.suggestedValueOption1 = "";
                    }
                    if (name == "option_2") {
                        this.suggestedValueOption2 = "";
                    }
                    dataSelected.map((option, index) => {
                        if (name == "option_1") {
                            this.suggestedValueOption1 = index == 0 ? this.suggestedValueOption1 + option.en_name : index == dataSelected.length ? this.suggestedValueOption1 + ", " + option.en_name + "." : this.suggestedValueOption1 + ", " + option.en_name;
                        }
                        if (name == "option_2") {
                            this.suggestedValueOption2 = index == 0 ? this.suggestedValueOption2 + option.en_name : index == dataSelected.length ? this.suggestedValueOption2 + ", " + option.en_name + "." : this.suggestedValueOption2 + ", " + option.en_name;
                        }
                    });
                }
                this.recommended_attributes.map((option) => {
                    if (this.form.option_1 == option.label || this.form.option_2 == option.label) {
                        option.$isDisabled = true;
                    }
                    else if (this.form.option_1 !== option.label || this.form.option_2 !== option.label) {
                        option.$isDisabled = false;
                    }
                });
                this.generateVariants();
            },
            disableOption(option) {
                if (option.selected && option.selected == true) {
                    return true;
                } else {
                    return false;
                }
            },
            showModal(variantId) {
                this.deleteVariantId = variantId;
                this.$refs['modal-delete-variant'].show();
            },
            hideModal() {
                this.$refs['modal-delete-variant'].hide();
            },
            clearVariant(){
                let variantId = this.deleteVariantId ;
                axios
                .delete('/web/products/delete-variant',
                {
                    params: {
                        product_id: this.product.id,
                        variant_id: this.deleteVariantId
                    }
                })
                .then(response => {
                    if (!response.data.meta.error) {
                        this.key.variants += 1;
                        delete this.form.variants[variantId];
                        delete this.initForm.variants[variantId];
                        delete this.customize.variants[variantId];
                        delete this.validator.variants[variantId];
                        if (this.product && this.product.variants) {
                                this.product.variants = this.product.variants.filter(item => item.id != variantId);
                            }
                        if (this.product && this.product.listings) {
                                this.product.listings.forEach(function (item) {
                                    item.listing_variants = item.listing_variants.filter(item => item.product_variant_id != variantId);
                                });
                        }
                        for (let accountId in this.sourceData) {
                            delete this.sourceData[accountId].variants[variantId];
                        }
                        notify(
                            "top",
                            "Success",
                            response.data.meta.message,
                            "center",
                            "success"
                        );
                    } else {
                        notify(
                            "top",
                            "Error",
                            response.data.meta.message +
                                " Contact admin to resolve it.",
                            "center",
                            "danger"
                        );
                    }
                })
                .catch(error => {
                    console.log(error);
                    if (
                        error.response &&
                        error.response.data &&
                        error.response.data.debug[0] &&
                        error.response.data.debug[0].message
                    ) {
                        notify(
                            "top",
                            "Error",
                            error.response.data.debug[0].message +
                                " Contact admin to resolve it.",
                            "center",
                            "danger"
                        );
                    } else if (
                        error.response &&
                        error.response.data &&
                        error.response.data.meta
                    ) {
                        notify(
                            "top",
                            "Error",
                            error.response.data.meta.message +
                                " Contact admin to resolve it.",
                            "center",
                            "danger"
                        );
                    } else {
                        notify("top", "Error", error, "center", "danger");
                    }
                });
                this.hideModal();
                this.deleteVariantId = null;
            },

            integration_name(account) {
                if(account.integration != undefined) {
                    return account.integration.name;
                }
                return account.integration_name;
            },
            hideSuggestOption(name) {
                setTimeout(() => {
                    if (!jQuery('#suggest_'+ name).hasClass("hard-d-none")) {
                        jQuery('#suggest_'+ name).addClass("hard-d-none");
                    }
                }, 300);
            },
            showSuggestOption(name) {
                if (jQuery('#suggest_'+ name).hasClass("hard-d-none")) {
                    jQuery('#suggest_'+ name).removeClass("hard-d-none");
                }
            },
            /* API calls - START */
            async getAccounts() {
                // edit
                try {
                    if (!this.isCreate) {
                        this.accounts = this.product.listings.map(listing => ({
                            id: listing.account.id,
                            name: listing.account.name,
                            integration_id: listing.account.integration.id,
                            integration_name: listing.account.integration.name,
                            region: listing.account.region,
                            locations: listing.account.locations,
                            has_category: listing.account.has_category,
                            region_id: typeof listing.account.region_id !== 'undefined' && listing.account.region_id !== null ? listing.account.region_id : null,
                        }));

                    } else {
                        // create
                        let response = await axios.get('/web/accounts', {
                            params: {
                                limit: 50,
                                with: 'locations',
                                append: 'has_category'
                            }
                        });

                        if (!response.data.meta.error) {
                            this.accounts = response.data.response.items;
                        } else {
                            notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                        }
                    }
                } catch (error) {
                    console.log(error);
                }
            },
            getAttributes(category, accountId, preventFormat = false) {
                let categoryType = this.accountIdToCategoryType[accountId];

                this.setupInitCategory(category, accountId);
                if (category.hasOwnProperty('integration_id') && category.integration_id !== null && !this.attributes[categoryType].hasOwnProperty(category.id)) {
                    axios.get(
                        '/web/categories/' + category.id + '/attributes', {
                            params: {
                                account: accountId,
                                type: 'IntegrationCategory'
                            }
                        }
                    ).then(response => {
                        if (!response.data.meta.error) {
                            // format attributes before save it
                            this.$set(this.attributes[categoryType], category.id, this.$root.formatAttributes(response.data.response.attributes));

                            // logistics and price has been append on attributes
                            this.$set(this.logistics, accountId, response.data.response.logistics !== null ? response.data.response.logistics : {});
                            this.$set(this.prices, accountId, response.data.response.prices !== null ? response.data.response.prices : []);

                            this.is_sale_prop_require = response.data.response.is_sale_prop_require !== null ? response.data.response.is_sale_prop_require : 0;
                            // Only for Lazada
                            if (category.integration_id == 11001) {
                                this.recommended_attributes = response.data.response.recommended_attributes !== null ? response.data.response.recommended_attributes : [];
                            }
                            this.setupPrices(accountId);
                            this.setupLogistics(accountId);
                            this.setupAttributes(category.id, accountId, preventFormat);
                        } else {
                            notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                        }
                    }).catch(error => {
                        console.log(error);
                    });
                } else if (category.hasOwnProperty('integration_id') && category.integration_id !== null && this.attributes[this.accountIdToCategoryType[accountId]].hasOwnProperty(category.id)) {
                    this.setupAttributes(category.id, accountId, preventFormat);
                }
            },
            async inventoryExist(sku = null) {
                try {
                    let response = await axios.get(
                        '/web/inventory', {
                            params: {
                                search: sku,
                                id: 0
                            }
                        }
                    );

                    if (!response.data.meta.error) {
                        console.log(response.data.response.items);
                        return response.data.response.items.length > 0;
                    }
                } catch(error) {
                    notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    return true;
                }
            },
            async updateProduct(data) {
                try {
                    console.log('product data');
                    console.dir(data);
                    this.changeProgress(0, 1);
                    let response = await axios.put(
                        '/web/products/' + (!this.isCreate ? this.product.slug : this.productSlug), data
                    );

                    if (!response.data.meta.error) {
                        this.changeProgress(0, 2);
                        notify('top', 'Success', response.data.meta.message, 'center', 'success');
                        return true;
                    } else {
                        this.changeProgress(0, 3, response.data.meta.message);
                        console.log(response.data.response);
                        notify('top', 'Error', response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                        this.enableClose = true;
                    }
                } catch (error) {
                    this.changeProgress(0, 3);
                    this.enableClose = true;
                    console.log(error);
                    notify('top', 'Error', error.response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                }
                return false;
            },
            updateProductListing(id, data, accountId = null) {
                console.log('listing data');
                console.dir(data);
                this.changeProgress(accountId, 1);
                axios.post(
                    '/web/products/' + this.product.slug + '/listings/' + id, data
                ).then(response => {
                    if (!response.data.meta.error) {
                        this.changeProgress(accountId, 2);
                        notify('top', 'Success', response.data.meta.message, 'center', 'success');
                    } else {
                        console.log(response.data.response);
                        this.changeProgress(accountId, 3, response.data.meta.message);
                        notify('top', 'Error', response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                    }
                }).catch(error => {
                    this.changeProgress(accountId, 3);
                    console.log(error);
                    if (error.response && error.response.data && error.response.data.debug[0] && error.response.data.debug[0].message) {
                        notify('top', 'Error', error.response.data.debug[0].message + ' Contact admin to resolve it.', 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            async storeProduct(data) {
                // console.log(data);
                try {
                    this.changeProgress(0, 1);
                    let response = await axios.post('/web/products', data);

                    if (!response.data.meta.error) {
                        this.changeProgress(0, 2);
                        notify('top', 'Success', response.data.meta.message, 'center', 'success');
                        return response.data.response;
                    } else {
                        this.changeProgress(0, 3, response.data.meta.message);
                        this.enableClose = true;
                        notify('top', 'Error', response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                    }
                } catch(error) {
                    this.changeProgress(0, 3, error.response.data.meta.message);
                    this.enableClose = true;
                    console.log(error);
                    notify('top', 'Error', response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                }
                return null;
            },
            exportProduct(productSlug, accountId) {
                // if already exported to this account, skip it
                if (this.accounts[this.accountIdToAccountIndex[accountId]].hasOwnProperty('exported') && this.accounts[this.accountIdToAccountIndex[accountId]]['exported']) {
                    this.changeProgress(accountId, 2);
                } else {
                    this.changeProgress(accountId, 1);
                    axios.get('/web/products/export/' + productSlug + '/accounts/' + accountId + '/export', {
                        params: {create: true}
                    }).then(response => {
                        if (!response.data.meta.error) {
                            console.log(response.data.response);
                            this.changeProgress(accountId, 2);
                            notify('top', 'Success', response.data.meta.message, 'center', 'success');

                            this.$set(this.accounts[this.accountIdToAccountIndex[accountId]], 'exported', true);
                            // if selected source successfully exported, change it to main
                            if (this.selectedSource.id === accountId) {
                                this.selectedSource = {...this.sourceOption[0]};
                            }

                            // if create product and all export product success, redirect to product page
                            if (this.checkProgress(true)) {
                                setTimeout(() => window.location.href = '/dashboard/products/' + productSlug, 3000);
                            }
                        } else {
                            this.changeProgress(accountId, 3, response.data.meta.message);
                            notify('top', 'Error', response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                        }
                    }).catch(error => {
                        this.changeProgress(accountId, 3);
                        console.log(error);
                        notify('top', 'Error', response.data.meta.message + ' Contact admin to resolve it.', 'center', 'danger');
                    });
                }
            },
            /* API calls - END */

            /* Get formatted data - START */
            getSelectedCategory(categories, id) {
                if (id !== null) {
                    return categories.find(category => parseInt(category.id, 10) === parseInt(id, 10));
                } else {
                    return {};
                }
            },
            getFieldType(name) {
                // return numeric type since input-field-component can map it
                if (typeof name === 'number') {
                    return name;
                } else if (name === 'short_description') {
                    return 'multi_text';
                } else if (name === 'html_description') {
                    return 'rich_text';
                } else if (['length', 'width', 'height', 'weight'].includes(name)) {
                    // use price formatter since it is the same as lwh/weight, number with 2dp
                    return 'price';
                }
                return 'text';
            },
            getAttributesByAccount(accountId, isVariant = false, flat = false, checking = false) {
                let level = !isVariant? 'GENERAL' : 'SKU';
                if (this.form.category.hasOwnProperty(accountId) && this.attributes[this.accountIdToCategoryType[accountId]].hasOwnProperty(this.form.category[accountId].id)) {
                    if (checking) {
                        return Object.values(this.attributes[this.accountIdToCategoryType[accountId]][this.form.category[accountId].id][level]).filter(value => value.constructor !== Array).length > 0;
                    } else if (flat) {
                        let attributes = Object.values(this.attributes[this.accountIdToCategoryType[accountId]][this.form.category[accountId].id][level]).filter(value => value.constructor !== Array);
                        if (attributes.length > 0) {
                            return Object.assign({}, ...attributes);
                        }
                        // if all empty, return empty object
                        return {};
                    }
                    return this.attributes[this.accountIdToCategoryType[accountId]][this.form.category[accountId].id][level];
                } else {
                    return false;
                }
            },
            /* Get formatted data - END */

            /* Format data - START */
            formatProductAttributes() {
                if (!this.isCreate) {
                    for (let listing of this.product.listings) {
                        let categoryId = this.form.category[listing.account_id].id;
                        let flatMainAttributes = [].concat(...Object.values(this.attributes[this.accountIdToCategoryType[listing.account_id]][categoryId].GENERAL).map(attributes => Object.values(attributes)));
                        let flatVariantAttributes = [].concat(...Object.values(this.attributes[this.accountIdToCategoryType[listing.account_id]][categoryId].SKU).map(attributes => Object.values(attributes)));

                        for (let attribute of listing.attributes) {
                            if (this.sourceData[listing.account_id].attributes.hasOwnProperty(attribute.name)) {
                                let value = attribute.value;

                                // get attributes value format and apply it (Exp: change from 'value' to {name: 'name', value: 'value'})
                                let attributeData = flatMainAttributes.find(flatAttribute => flatAttribute.name === attribute.name);
                                if (attributeData) {
                                    if (Array.isArray(attributeData.data) && attributeData.data.length > 0 && typeof attributeData.data[0] === 'object') {
                                        let compareWithKey = null;

                                        // if got more type of options, add it here
                                        if (attributeData.data[0].hasOwnProperty('value')) {
                                            compareWithKey = 'value';
                                        } else if (attributeData.data[0].hasOwnProperty('name')) {
                                            compareWithKey = 'name';
                                        }

                                        if (compareWithKey !== null) {
                                            let searchResult = attributeData.data.find(option => option[compareWithKey] === value);
                                            // if cant find, dont replace it
                                            if (typeof searchResult !== 'undefined') {
                                                value = typeof searchResult === 'object' ?  searchResult.name : searchResult;
                                                // If Qoo10 replace searchResult name with value
                                                if (typeof searchResult === 'object' && listing.integration_id === 11004 ) {
                                                    value = searchResult.value;
                                                }
                                            }
                                        }
                                    }
                                }

                                // fill in the value into source data attribute
                                this.sourceData[listing.account_id].attributes[attribute.name].value = value;
                            } else if (this.form.hasOwnProperty(attribute.name)) {
                                this.sourceData[listing.account_id][attribute.name] = attribute.value;
                            } else if (attribute.name === 'locations') {
                                this.sourceData[listing.account_id][attribute.name] = JSON.parse(attribute.value);
                            }  else if (attribute.name === 'options') {
                                this.sourceData[listing.account_id][attribute.name] = JSON.parse(attribute.value);

                                let i = 1;
                                for (let option of this.sourceData[listing.account_id][attribute.name]) {
                                    this.sourceData[listing.account_id]['option_' + i] = option;
                                    i++;
                                }
                            } else {
                                console.log(attribute.name + ' has not been assign to any place');
                            }
                        }

                        for (let variant of listing.listing_variants) {
                            for (let attribute of variant.attributes) {
                                if (this.sourceData[variant.account_id].variants[variant.product_variant_id].hasOwnProperty('attributes') && this.sourceData[variant.account_id].variants[variant.product_variant_id].attributes.hasOwnProperty(attribute.name)) {
                                    let value = attribute.value;

                                    // get attributes value format and apply it (Exp: change from 'value' to {name: 'name', value: 'value'})
                                    let attributeData = flatVariantAttributes.find(flatAttribute => flatAttribute.name === attribute.name);
                                    if (attributeData) {
                                        if (Array.isArray(attributeData.data) && attributeData.data.length > 0 && typeof attributeData.data[0] === 'object') {
                                            let compareWithKey = null;

                                            // if got more type of options, add it here
                                            if (attributeData.data[0].hasOwnProperty('value')) {
                                                compareWithKey = 'value';
                                            }  else if (attributeData.data[0].hasOwnProperty('name')) {
                                                compareWithKey = 'name';
                                            }

                                            if (compareWithKey !== null) {
                                                value = attributeData.data.find(option => option[compareWithKey] === value);
                                            }
                                        }
                                    }

                                    // fill in the value into source data attribute
                                    this.sourceData[variant.account_id].variants[variant.product_variant_id].attributes[attribute.name].value = attribute.value;
                                } else if (this.form.variants[variant.product_variant_id].hasOwnProperty(attribute.name)) {
                                    this.sourceData[variant.account_id].variants[variant.product_variant_id][attribute.name] = attribute.value;
                                } else {
                                    console.log(attribute.name + ' has not been assign to any place');
                                }
                            }
                        }
                    }
                }

                // re-render attributes related element
                this.key.attributes += 1;
                this.key.variants += 1;
            },
            formatSubmitData(sourceData, skipAccount = []) {
                let data = {attributes: {}};
                // remove all reference because ltr will use delete
                sourceData = cloneDeep(sourceData);

                for (let accountId in sourceData) {
                    // main product data
                    if (parseInt(accountId, 10) === 0) {
                        // pack options
                        data['options'] = [];
                        for (let i = 1; i <= 3; i++) {
                            if (sourceData[accountId]['option_' + i] !== '' && sourceData[accountId]['option_' + i + '_options'].length > 0) {
                                data['options'].push(sourceData[accountId]['option_' + i]);
                            }
                            delete sourceData[accountId]['option_' + i];
                            delete sourceData[accountId]['option_' + i + '_options'];
                        }
                        // category
                        if (!this.isEmptyObject(sourceData[accountId].category)) {
                            data['category_id'] = sourceData[accountId].category.id;
                        }
                        delete sourceData[accountId].category;
                        // image
                        data['images'] = sourceData[accountId].images;
                        delete sourceData[accountId].images;
                        // brand
                        if (sourceData[accountId].hasOwnProperty('brand') && sourceData[accountId].brand.hasOwnProperty('name')) {
                            data['brand'] = sourceData[accountId].brand.name;
                            delete sourceData[accountId].brand;
                        }
                        // price
                        data['prices'] = sourceData[accountId].prices;
                        delete sourceData[accountId].prices;
                        // variant
                        for (let variantId in sourceData[accountId].variants) {
                            // add attributes base, ltr will flow into data
                            sourceData[accountId].variants[variantId]['attributes'] = {};
                            // Note: if wanna use this function for other place (not create), rmb add exclusion on this delete id
                            delete sourceData[accountId].variants[variantId].id;
                        }
                        // other data
                        data = {...data, ...sourceData[accountId]};
                    } else if (!skipAccount.includes(parseInt(accountId, 10))) {
                        // listing data
                        // attributes
                        if (sourceData[accountId].hasOwnProperty('attributes')) {
                            // fill in integration_id, creation of product_attributes need it
                            data.attributes[accountId] = this.appendIntegrationId(sourceData[accountId]['attributes'], accountId, true);
                            delete sourceData[accountId]['attributes'];
                        } else {
                            data.attributes[accountId] = {};
                        }
                        // category
                        if (sourceData[accountId].category.hasOwnProperty('id') && this.accountIdToCategoryType[accountId] !== 'none') {
                            data.attributes[accountId]['integration_category_id'] = {
                                integration_id: this.accountIdToIntegrationId[accountId],
                                region_id: this.accountToIntegrationRegionId[accountId],
                                value: sourceData[accountId].category.id
                            };
                        }
                        delete sourceData[accountId].category;
                        // image
                        if (sourceData[accountId].hasOwnProperty('images')) {
                            data.images = [...data.images, ...this.appendIntegrationId(sourceData[accountId].images, accountId)];
                            delete sourceData[accountId].images;
                        }
                        // price
                        if (sourceData[accountId].hasOwnProperty('prices')) {
                            data.prices = [...data.prices, ...this.appendIntegrationId(sourceData[accountId].prices, accountId)].filter(price => price.price);
                            delete sourceData[accountId].prices;
                        }
                        // logistic
                        if (sourceData[accountId].hasOwnProperty('logistics')) {
                            data.attributes[accountId]['logistics'] = {...sourceData[accountId]['logistics']};
                            delete sourceData[accountId]['logistics'];
                        }
                        // location
                        if (sourceData[accountId].hasOwnProperty('locations')) {
                            data.attributes[accountId]['locations'] = this.appendIntegrationId({value: JSON.stringify(sourceData[accountId]['locations'])}, accountId, false, true);
                            delete sourceData[accountId]['locations'];
                        }
                        // variant
                        for (let variantId in sourceData[accountId].variants) {
                            // Note: if wanna use this function for other place (not create), rmb add exclusion on this delete id
                            delete sourceData[accountId].variants[variantId].id;
                            // fill in integration_id, creation of product_attributes need it
                            data.variants[variantId].attributes[accountId] = this.appendIntegrationId(sourceData[accountId].variants[variantId]['attributes'], accountId, true);
                            delete sourceData[accountId].variants[variantId]['attributes'];
                            // image
                            if (sourceData[accountId].variants[variantId].hasOwnProperty('images')) {
                                data.variants[variantId].images = [...data.variants[variantId].images, ...this.appendIntegrationId(sourceData[accountId].variants[variantId].images, accountId)];
                                delete sourceData[accountId].variants[variantId].images;
                            }
                            // price
                            if (sourceData[accountId].variants[variantId].hasOwnProperty('prices')) {
                                data.variants[variantId].prices = [...data.variants[variantId].prices, ...this.appendIntegrationId(sourceData[accountId].variants[variantId].prices, accountId)].filter(price => price.price);
                                delete sourceData[accountId].variants[variantId].prices;
                            }
                            // merge other variant data left into attributes
                            data.variants[variantId].attributes[accountId] = {...data.variants[variantId].attributes[accountId], ...this.mergeIntoAttributes(sourceData[accountId].variants[variantId], accountId)};
                        }
                        delete sourceData[accountId].variants;
                        // other data
                        // console.log('sourceData:' + accountId);
                        // console.log(sourceData[accountId]);
                        // merge other main product data left into attributes
                        data.attributes[accountId] = {...data.attributes[accountId], ...this.mergeIntoAttributes(sourceData[accountId], accountId)};
                    }
                }

                //console.log(data);
                return data;
            },
            /* Format data - END */

            /* Setup base - START */
            setupFormBase() {
                // setup default form data
                let productProperties = ['name', 'associated_sku', 'short_description', 'html_description'];
                let variantProperties = ['id', 'name', 'sku', 'option_1', 'option_2', 'option_3', 'length', 'width', 'height', 'weight'];

                // main product
                let form = productProperties.reduce((form, property) => ({...form, [property]: !this.isCreate ? this.product[property] : ''}), {
                    'category': {'0': this.isCreate ? {} : this.initCategory !== null && this.initCategory.hasOwnProperty(0) ? cloneDeep(this.initCategory[0]) : this.product.category_id === null ? {} : parseInt(this.product.category_id, 10)},
                    'brand': !this.isCreate ? this.product.brand !== null ? this.product.brand : {} : {},
                    'prices': !this.isCreate ? this.product.prices.filter(price => price.product_listing_id === null).map(price => ({price: price.price, type: price.type})) : [{price: 0, type: 'selling'}],
                    'images': [], // main product doesnt support images function
                    // 'images': [{image_url: 'https://combinesell-seller.sgp1.digitaloceanspaces.com/production/shops/214/integrations/410/products/51940644f8a57532ebba3516d0d07cef0.jpeg', width: 500, height: 500}],
                    'variants': {}
                });

                // options
                let optionsValue = [];
                if (!this.isCreate) {
                    optionsValue = Object.values(this.product.options);
                    // optionsValue = this.product.options;
                }
                for (let i = 0; i < 3; i++) {
                    if (i < optionsValue.length) {
                        form['option_' + (i + 1)] = optionsValue[i];
                    } else {
                        form['option_' + (i + 1)] = '';

                        if (this.isCreate) {
                            form['option_' + (i + 1) + '_options'] = [];
                        }
                    }
                }

                // variant
                let variants = {};

                // edit
                if (!this.isCreate) {
                    let newVariants = {};

                    if (this.form !== null) {
                        for (let variantId in this.form.variants) {
                            if (isNaN(variantId)) {
                                newVariants[variantId] = cloneDeep(this.form.variants[variantId]);
                            }
                        }
                    }

                    let oldVariants = this.product.variants.reduce((variants, variant) => (
                        {...variants,
                            [variant.id]: variantProperties.reduce((formVariant, property) => ({...formVariant, [property]: variant[property]}), {
                                'prices': variant.prices.map(price => ({price: price.price, type: price.type,currency: price.currency})),
                                'inventory': variant.inventory !== null ? Object.keys(variant.inventory).filter(
                                    property => ['id', 'sku', 'name', 'stock', 'low_stock_notification'].includes(property)).reduce(
                                    (inventory, key) => ({...inventory, [key]: variant['inventory'][key]}), {}) : {},
                                'images': [] // main product doesnt support images function
                            })}
                    ), {});

                    variants = {...newVariants, ...oldVariants};
                }

                this.$set(form, 'variants', variants);

                // create
                // generate empty variant
                if (this.isCreate && this.sourceData.hasOwnProperty(0)) {
                    if (Object.keys(this.sourceData[0].variants).length > 0) {
                        for (let variantId in this.sourceData[0].variants) {
                            this.setupVariantBase(form, variantId);
                        }
                    }
                }

                return form;
            },
            setupVariantBase(base = this.form, variantId = 'v1', variant = {id: variantId, name: '', option_1: '', option_2: '', option_3: ''}) {
                // variant must have ['name', 'option_1', 'option_2', 'option_3']
                let newVariantProperties = ['sku', 'length', 'width', 'height', 'weight'];

                while (base.variants.hasOwnProperty(variantId)) {
                    variantId += 1;
                    variant.id = variantId;
                }

                this.$set(base.variants, variantId, newVariantProperties.reduce((properties, property) => ({...properties,
                    [property]: ''
                }), {...variant,
                    prices: [{type: 'selling', price: '0'}],
                    inventory: {},
                    images: []
                }));

                return variantId;
            },
            setupVariantValidator(variantId) {
                this.validator.variants[variantId] = Object.keys(this.form.variants[variantId]).filter(key => !key.startsWith('option_') && key !== 'id').reduce((settings,setting) => ({...settings,[setting]:{}}),{});
                if (this.form.variants[variantId].prices.length > 0) {
                    this.validator.variants[variantId].prices = this.form.variants[variantId].prices.reduce((prices,price) => ({...prices, [price.type]: {}}), {});
                } else {
                    this.form.variants[variantId].prices = [{ price: 0, type: "selling", currency: "SGD"}]
                    this.validator.variants[variantId].prices = this.form.variants[variantId].prices.reduce((prices,price) => ({...prices, [price.type]: {}}), {});
                }
            },
            setupInitCategory(category, accountId) {
                if (this.initCategory === null) {
                    this.initCategory = {};
                }

                if (!this.initCategory.hasOwnProperty(accountId)) {
                    this.initCategory[accountId] = cloneDeep(category);

                    // change initForm category id to category object
                    if (this.initForm !== null && this.isCreate) {
                        this.initForm.category[accountId] = cloneDeep(category);
                    }
                }
            },
            setupCategoryForm(base = this.form, resetAll = true, accountId = null) {
                // setup customize for category
                if (!this.customize.hasOwnProperty('category') && resetAll) {
                    this.customize['category'] = {};
                }

                if (accountId === null) {
                    for (let account of this.accounts) {
                        if (this.initCategory !== null && this.initCategory.hasOwnProperty(account.id)) {
                            base.category[account.id] = cloneDeep(this.initCategory[account.id]);
                        } else {
                            let integrationCategoryId = null;
                            if (!this.isCreate) {
                                integrationCategoryId = this.product.listings.find(listing => listing.account_id === account.id).integration_category_id;
                            }

                            // if integrationCategoryId not null, save the id to category, it will be transform to category object when go through AsyncMultiselect component
                            if (integrationCategoryId !== null) {
                                base.category[account.id] = parseInt(integrationCategoryId, 10);
                            } else {
                                // if no integrationCategoryId record, treat it as empty
                                base.category[account.id] = {};
                            }
                        }

                        // reset all things related to category
                        if (resetAll) {
                            // this.initForm.category[account.id] = cloneDeep(base.category[account.id]);
                            this.validator.category[account.id] = {
                                // category can be category object or category id (since if store id, means not empty, straight put false)
                                invalid: typeof base.category[account.id] === 'object' ? this.isEmptyObject(base.category[account.id]) : false
                            };
                            this.customize.category[account.id] = this.form.category[0].hasOwnProperty('id') || (this.form.category[0].id !== null && this.form.category[account.id].category_id === this.form.category[0].id);

                            this.getAttributes(base.category[account.id], account.id);
                        }
                    }
                } else {
                    base.category = cloneDeep(this.initCategory[accountId]);
                }
            },
            setupLogistics(accountId) {
                if (accountId !== 0 && !this.isCreate) {
                    if (!this.isEmptyObject(this.logistics[accountId])) {
                        let listing = this.product.listings.find(listing => listing.account_id === parseInt(accountId));
                        let logistic = listing.attributes.find(element => element.name === 'logistics');
                        if (typeof logistic !== 'undefined') {
                            this.sourceData[accountId]['logistics'] = {
                                integration_id: logistic.integration_id,
                                value: logistic.value
                            };
                        }
                    }
                } else {
                    if (!this.isEmptyObject(this.logistics[accountId])) {
                        this.sourceData[accountId]['logistics'] = {
                            integration_id: this.accountIdToIntegrationId[accountId],
                            region_id: this.accountToIntegrationRegionId[accountId],
                            value: []
                        };
                    }
                }

                if (!this.validator.hasOwnProperty('logistics')) {
                    this.validator['logistics'] = {};
                }
                this.validator.logistics[accountId] = {};
            },
            setupPrices(accountId) {
                // only integration's listing has special prices type
                if (accountId !== 0) {
                    // old data
                    let fromSource = null;
                    if (!this.isCreate) {
                        fromSource = this.product.listings.find(listing => listing.account_id === parseInt(accountId)).listing_variants;
                    } else {
                        fromSource = this.sourceData[accountId]['variants'];
                    }

                    // main product
                    this.setupPricesData(accountId);

                    // variants
                    for (let variantIndex in fromSource) {
                        let variantId = this.isCreate ? variantIndex : fromSource[variantIndex].product_variant_id;

                        this.setupPricesData(accountId, variantId);
                    }
                }
            },
            setupPricesData(accountId, variantId = null) {
                let prices = [];

                // used to check whether there is prices data in sourceData or not
                let missingPrice = false;

                // get sourceData and form from product or variant
                let sourceData = variantId === null ? this.sourceData[accountId] : this.sourceData[accountId]['variants'][variantId];
                let form = variantId === null ? this.form : this.form.variants[variantId];
                // base price (current price data)
                let baseSource = null;
                if (variantId === null) {
                    if (this.sourceData[0].hasOwnProperty('prices')) {
                        baseSource = this.sourceData[0];
                    } else if (this.form.hasOwnProperty('prices')) {
                        baseSource = this.form;
                    }
                } else {
                    if (this.sourceData[0]['variants'].hasOwnProperty(variantId) && this.sourceData[0]['variants'][variantId].hasOwnProperty('prices')) {
                        baseSource = this.sourceData[0]['variants'][variantId];
                    } else if (this.form['variants'][variantId].hasOwnProperty('prices')) {
                        baseSource = this.form['variants'][variantId];
                    }
                }
                for (let priceType of this.prices[accountId]) {
                    let price = '';

                    if (baseSource !== null) {
                        let mainPrice = baseSource.prices.find(price => price.type === priceType);
                        if (typeof mainPrice !== 'undefined') {
                            price = mainPrice.price;
                        }
                    }

                    prices.push({
                        price: price,
                        type: priceType
                    });

                    if (!sourceData.hasOwnProperty('prices')) {
                        missingPrice = true;
                    } else if (!missingPrice &&
                        typeof sourceData.prices.find(price => price.type === priceType) === 'undefined' &&
                        typeof form.prices.find(price => price.type === priceType) === 'undefined') {

                        sourceData.prices.push({
                            price: '',
                            type: priceType
                        });
                    }
                }

                if (missingPrice) {
                    sourceData['prices'] = prices;
                }
            },
            setupAttributes(categoryId, accountId, preventFormat = false) {
                // setup sourceData and validator for attributes
                let flatMainAttributes = [].concat(...Object.values(this.attributes[this.accountIdToCategoryType[accountId]][categoryId].GENERAL).map(attributes => Object.values(attributes)));
                let flatVariantAttributes = [].concat(...Object.values(this.attributes[this.accountIdToCategoryType[accountId]][categoryId].SKU).map(attributes => Object.values(attributes)));

                // main product
                let mainAttributes = flatMainAttributes.reduce((attributes, attribute) => ({...attributes,
                    [attribute.name]: {
                        value: '',
                        external_id: attribute.external_id
                    }
                }),{});
                if (!this.sourceData[accountId].hasOwnProperty('attributes') || !isEqual(Object.keys(this.sourceData[accountId]['attributes']), Object.keys(mainAttributes))) {
                    this.sourceData[accountId]['attributes'] = mainAttributes;
                }

                if (!this.validator.hasOwnProperty('attributes')) {
                    this.validator['attributes'] = {};
                }
                this.validator['attributes'][accountId] = flatMainAttributes.filter(attribute => this.attributes[this.accountIdToCategoryType[accountId]][categoryId].GENERAL.required.constructor === Object && this.attributes[this.accountIdToCategoryType[accountId]][categoryId].GENERAL.required.hasOwnProperty(attribute.name)).reduce((attributes, attribute) => ({...attributes, [attribute.name]: {}}),{});

                // variant
                for (let variantId in this.sourceData[accountId]['variants']) {
                    let variantAttributes = flatVariantAttributes.reduce((attributes, attribute) => ({...attributes,
                        [attribute.name]: {
                            value: '',
                            external_id: attribute.external_id
                        }
                    }),{});
                    if (!this.sourceData[accountId]['variants'][variantId].hasOwnProperty('attributes') || !isEqual(Object.keys(this.sourceData[accountId]['variants'][variantId]['attributes']), Object.keys(variantAttributes))) {
                        this.sourceData[accountId]['variants'][variantId]['attributes'] = variantAttributes;
                    }

                    if (!this.validator['variants'][variantId].hasOwnProperty('attributes')) {
                        this.validator['variants'][variantId]['attributes'] = {};
                    }
                    if (!this.validator['variants'][variantId]['attributes'].hasOwnProperty(accountId)) {
                        this.validator['variants'][variantId]['attributes'][accountId] = {};
                    }
                    this.validator['variants'][variantId]['attributes'][accountId] = flatVariantAttributes.filter(attribute => this.attributes[this.accountIdToCategoryType[accountId]][categoryId].SKU.required.constructor === Object && this.attributes[this.accountIdToCategoryType[accountId]][categoryId].SKU.required.hasOwnProperty(attribute.name)).reduce((attributes, attribute) => ({...attributes, [attribute.name]: {}}),{});
                }
                // this.sourceData[accountId].variants['attributes'] = flatAttributes.reduce((attributes, name) => ({...attributes, [name]: ''}),{})
                if (!preventFormat) this.formatProductAttributes();
            },
            setupSourceData() {
                this.sourceData = {};

                for (let source of this.sourceOption) {
                    this.sourceData[source.id] = {};

                    this.sourceData[source.id]['variants'] = {};

                    if (source.id === 0 && !this.isCreate) {
                        // always pass options data to backend, so save it to sourceData
                        if ((Array.isArray(this.product.options) && this.product.options.length > 0) ||
                            (!Array.isArray(this.product.options) && typeof this.product.options === 'object' && !this.isEmptyObject(this.product.options))) {
                            let optionIndex = 1;
                            for (let optionName in this.product.options) {
                                let option = this.product.options[optionName];
                                if (option) {
                                    this.sourceData[source.id]['option_' + optionIndex] = option;
                                    optionIndex++;
                                }
                            }
                        }

                        for (let variant of this.product.variants) {
                            this.sourceData[source.id]['variants'][variant.id] = {};
                        }
                    } else if (source.id !== 0 && !this.isCreate) {
                        /* @NOTES - all the customizable attributes, images, prices should append in sourceData here */
                        let listing = this.product.listings.find(listing => listing.account_id === source.id);
                        this.sourceData[source.id]['identifiers'] = listing.identifiers;
                        this.sourceData[source.id]['images'] = listing.images.map(image => ({id: image.id, image_url: image.image_url !== null ? image.image_url : image.source_url, width: image.width, height: image.height}));
                        this.sourceData[source.id]['prices'] = listing.prices.map(price => ({price: price.price, type: price.type}));
                        // location
                        if (this.accounts[this.accountIdToAccountIndex[source.id]].locations.length > 0) {
                            this.sourceData[source.id]['locations'] = {};
                        }

                        for (let variant of listing.listing_variants) {
                            this.sourceData[source.id]['variants'][variant.product_variant_id] = {
                                identifiers: variant.identifiers,
                                images: variant.images.map(image => ({id: image.id, image_url: image.image_url !== null ? image.image_url : image.source_url, width: image.width, height: image.height})),
                                prices: variant.prices.map(price => ({price: price.price, type: price.type,currency : price.currency}))
                            };

                            // prices (remove selling price if same as main product's selling price)
                            let productVariant = this.product.variants.find(variantValue => parseInt(variantValue.id, 10) === parseInt(variant.product_variant_id, 10));
                            let sellingPrice = productVariant.prices.find(price => (price.type === 'selling'));

                            if (typeof sellingPrice !== 'undefined') {
                                this.sourceData[source.id]['variants'][variant.product_variant_id].prices = this.sourceData[source.id]['variants'][variant.product_variant_id].prices.filter(price => {
                                    if (price.type === 'selling') {
                                        return parseInt(sellingPrice.price, 10) !== parseInt(price.price, 10);
                                    }
                                    return true;
                                });
                            }
                        }
                    } else if (source.id !== 0 && this.isCreate) {
                        // location
                        if (this.accounts[this.accountIdToAccountIndex[source.id]].locations.length > 0) {
                            this.sourceData[source.id]['locations'] = {};
                        }
                    }
                }
            },
            resetForm(preventFormatAttributes = false) {
                // this.initCategory = cloneDeep(this.form.category);
                this.form = this.setupFormBase();
                this.customize = {variants: Object.keys(this.form.variants).reduce((variants, variantId) => ({...variants, [variantId]: {}}), {})};

                // setup validator
                if (this.validator === null) {
                    this.validator = Object.keys(this.form).filter(key => !key.startsWith('option_') && key !== 'variants').reduce((settings,setting) => ({...settings,[setting]:{}}),{});
                    this.validator.category['0'] = {};
                    if (this.form.prices.length > 0) {
                        this.validator.prices = this.form.prices.reduce((prices,price) => ({...prices, [price.type]: {}}), {});
                    } else {
                        this.form.prices = [
                            {price: 0, type: "selling"},
                            {price: 0, type: "special"}
                        ]
                        this.validator.prices = this.form.prices.reduce((prices,price) => ({...prices, [price.type]: {}}), {});
                    }
                    // logistics
                    this.validator['logistics'] = {};
                    for (let accountId in this.logistics) {
                        this.validator.logistics[accountId] = {};
                    }

                    // locations
                    this.validator['locations'] = {};
                    for (let account of this.accounts) {
                        this.validator.locations[account.id] = {};
                    }

                    this.validator.variants = {};
                    for (let variantId in this.form.variants) {
                        this.setupVariantValidator(variantId);
                    }
                }
                // fill in previously saved data
                this.sourceDataToForm();
                this.sourceDataToForm(this.selectedSource.id);

                // setup category and integration category
                this.setupCategoryForm();

                // fill in previously saved data (category)
                for (let i in this.form['category']) {
                    if (typeof this.sourceData[i] !== 'undefined' && typeof this.sourceData[i]['category'] !== 'undefined') {
                        this.form['category'][i] = this.sourceData[i]['category'];

                        // if category exist, change invalid to false
                        if (typeof this.form['category'][i] === 'object' && !this.isEmptyObject(this.form['category'][i])) {
                            this.validator.category[i].invalid = false;
                        }
                    }
                }

                // re-sync account (without category) attributes
                if (!this.isEmptyObject(this.form.category[0])) {
                    this.syncCategorySelection(this.form.category[0], false, preventFormatAttributes);
                }

                // TODO: if got time, redo this part, too long
                // setup customize
                if (this.selectedSource.id !== 0) {
                    for (let name in this.form) {
                        if (!['category', 'images', 'logistics', 'locations', 'variants'].includes(name) && !name.startsWith('option_')) {
                            // if data has been updated
                            if (this.sourceData[0].hasOwnProperty(name)) {
                                this.customize[name] = isEqual(this.sourceData[0][name], this.form[name]);

                                // skip prices since main cannot edit
                            } else if (name === 'prices') {
                                this.customize[name] = false;

                                // data not updated and in edit mode
                            } else if (!this.isCreate) {
                                this.customize[name] = isEqual(this.product[name], this.form[name]);
                            } else {
                                this.customize[name] = true;
                            }
                        } else if (name.startsWith('option_')) {
                            this.customize[name] = true;
                        } else if (name === 'variants') {
                            for (let variantId in this.form.variants) {
                                for (let variantKey in this.form.variants[variantId]) {
                                    if (!['id', 'name', 'sku', 'inventory', 'images'].includes(variantKey) && !variantKey.startsWith('option_')) {
                                        // custom price customize checker, only check selling price
                                        if (variantKey === 'prices') {
                                            if (this.sourceData[0].variants[variantId].hasOwnProperty(variantKey)) {
                                                this.customize.variants[variantId][variantKey] = isEqual(this.sourceData[0].variants[variantId][variantKey][0].price, this.form.variants[variantId][variantKey].find(price => (price.type === 'selling')).price);
                                            } else if (!this.isCreate) {
                                                let productVariant = this.product.variants.find(variant => parseInt(variant.id, 10) === parseInt(variantId, 10));

                                                if (typeof productVariant !== 'undefined') {
                                                    let sellingPrice = productVariant.prices.find(price => (price.type === 'selling'));

                                                    if (typeof sellingPrice !== 'undefined') {
                                                        this.customize.variants[variantId][variantKey] = isEqual(sellingPrice.price, this.form.variants[variantId][variantKey].find(price => (price.type === 'selling')).price);
                                                    }
                                                }
                                            }

                                            // if data has been updated
                                        } else if (this.sourceData[0].variants[variantId].hasOwnProperty(variantKey)) {
                                            this.customize.variants[variantId][variantKey] = isEqual(this.sourceData[0].variants[variantId][variantKey], this.form.variants[variantId][variantKey]);

                                            // data not updated and in edit mode
                                        } else if (!this.isCreate) {
                                            let productVariant = this.product.variants.find(variant => parseInt(variant.id, 10) === parseInt(variantId, 10));
                                            // skip new variants (new variants can't be found in this.product.variants)
                                            if (typeof productVariant !== 'undefined') {
                                                this.customize.variants[variantId][variantKey] = isEqual(productVariant[variantKey], this.form.variants[variantId][variantKey]);
                                            }
                                        } else {
                                            this.customize.variants[variantId][variantKey] = true;
                                        }
                                    } else if (['name', 'sku', 'inventory'].includes(variantKey) || variantKey.startsWith('option_')) {
                                        this.customize.variants[variantId][variantKey] = true;
                                    }
                                }
                            }
                        }
                    }
                }

                // update initForm
                this.initForm = cloneDeep(this.form);

                // rerender all cards
                this.key.all += 1;
            },
            changeSource(preventFormatAttributes = false) {
                // validate dirty
                let data = {
                    ...Object.keys(this.form).reduce((form, property) => {
                        if (property !== 'category' && property !== 'variants') {
                            if (!isEqual(this.form[property], this.initForm[property]) || (this.isCreate && this.previousSource.id === 0)) {
                                return {...form, [property]: this.form[property]};
                            }
                        } else if (property === 'category') {
                            // if (this.form[property].constructor === Object) {
                            let category = Object.keys(this.form[property]).reduce((result, key) => {
                                if (!isEqual(this.form[property][key], this.initForm[property][key]) || (this.isCreate && this.previousSource.id === 0)) {
                                    return {...result, [key]: this.form[property][key]};
                                }
                                return result;
                            }, {});

                            if (Object.keys(category).length > 0 || (this.isCreate && this.previousSource.id === 0)) {
                                return {...form, [property]: category};
                            }
                        } else if (property === 'variants') {
                            let variants = Object.keys(this.form.variants).reduce((variants, variantId) => ({
                                ...variants,
                                [variantId]: Object.keys(this.form.variants[variantId]).reduce((variantForm, variantProperty) => {
                                    // if (variantProperty !== 'category') {
                                    // if (not same || create mode || edit mode new variant)
                                    if (!isEqual(this.form.variants[variantId][variantProperty], this.initForm.variants[variantId][variantProperty]) || (this.isCreate && this.previousSource.id === 0) || (!this.isCreate && this.previousSource.id === 0 && isNaN(variantId))) {
                                        return {...variantForm, [variantProperty]: this.form.variants[variantId][variantProperty]};
                                    }
                                    // } else {
                                    //     // if (this.form[variantProperty].constructor === Object) {
                                    //     let category = Object.keys(this.form.variants[variantId][variantProperty]).reduce((result, key) => {
                                    //         if (!isEqual(this.form.variants[variantId][variantProperty][key], this.initForm.variants[variantId][variantProperty][key])) {
                                    //             return {...result, [key]: this.form.variants[variantId][variantProperty][key]};
                                    //         }
                                    //         return result;
                                    //     }, {});
                                    //
                                    //     if (Object.keys(category).length > 0) {
                                    //         return {...variantForm, [variantProperty]: category};
                                    //     }
                                    // }

                                    return variantForm;
                                }, {})
                            }), {});

                            return {...form, [property]: variants};
                        }
                        return form;
                    }, {})
                };

                // save new data changes to old data record
                for (let property of Object.keys(data)) {
                    if (property === 'variants') {
                        for (let variantId in data.variants) {
                            for (let variantProperty of Object.keys(data.variants[variantId])) {
                                if (data.variants[variantId].hasOwnProperty(variantProperty)) {
                                    if (!this.sourceData[this.previousSource.id].hasOwnProperty('variants')) {
                                        this.sourceData[this.previousSource.id]['variants'] = {};
                                    }
                                    if (typeof this.sourceData[this.previousSource.id].variants[variantId] === 'undefined') {
                                        if (!this.isCreate) {
                                            this.sourceData[this.previousSource.id].variants[variantId] = {id: this.form.variants[variantId].id};
                                        } else {
                                            this.sourceData[this.previousSource.id].variants[variantId] = {};
                                        }
                                    }

                                    // only save customized data
                                    // always save prices because it might contain other prices (not selling price)
                                    if (variantProperty === 'prices' || !this.customize.variants[variantId][variantProperty]) {
                                        this.sourceData[this.previousSource.id].variants[variantId][variantProperty] = cloneDeep(data.variants[variantId][variantProperty]);
                                    }
                                }
                            }
                        }
                    } else if (property === 'category') {
                        for (let i in data.category) {
                            this.sourceData[i][property] = data.category[i];
                        }
                    } else {
                        // only save customized data
                        if (!this.customize[property]) {
                            this.sourceData[this.previousSource.id][property] = cloneDeep(data[property]);
                        }
                    }
                }
                this.previousSource = {...this.selectedSource};
                this.resetForm(preventFormatAttributes);
                this.selectedAccount = this.accounts.filter(item => item.id === this.selectedSource.id)[0] ? this.accounts.filter(item => item.id === this.selectedSource.id)[0] : {};
                this.selectedIntegrationId = this.selectedAccount.integration_id || 0;
            },
            sourceDataToForm(sourceId = 0, base = this.form) {
                // remove reference link
                let data = cloneDeep(this.sourceData[sourceId]);
                for (let key in data) {
                    if (key === 'variants') {
                        for (let variantId in data.variants) {
                            let variant = data.variants[variantId];
                            if (Object.keys(variant).length > 0) {
                                for (let variantKey in variant) {
                                    if (variantKey !== 'id' && variantKey !== 'attributes' && variantKey !== 'identifiers' && variantKey !== 'prices') {
                                        base.variants[variantId][variantKey] = variant[variantKey];
                                    } else if (variantKey === 'prices') {
                                        for (let variantPrice of variant[variantKey]) {
                                            base.variants[variantId][variantKey] = base.variants[variantId][variantKey].filter(price => price.type !== variantPrice.type);
                                            base.variants[variantId][variantKey].push(variantPrice);
                                        }
                                    }
                                }
                            }
                        }
                    } else if (key !== 'attributes' && key !== 'category' && key !== 'identifiers' && key !== 'options') {
                        base[key] = data[key];
                    }
                }
            },
            setupProgress() {
                this.enableClose = false;
                for (let source of this.sourceOption) {
                    this.progress[source.id] = {
                        name: source.name,
                        state: 'Waiting',
                        value: 0,
                        error: []
                    };

                    // if already exported to this account, mark it as success directly
                    if (source.id !== 0 && this.accounts[this.accountIdToAccountIndex[source.id]].hasOwnProperty('exported') && this.accounts[this.accountIdToAccountIndex[source.id]]['exported']) {
                        this.changeProgress(source.id, 2);
                    }
                }
            },
            /* Setup base - END */

            /* Variant generation - START */
            changeShowVariant(variantId) {
                var firstVariantKey = Object.keys(this.form.variants)[0];
                this.initForm.variants[variantId] = {
                    ...this.initForm.variants[variantId],
                    weight: this.initForm.variants[firstVariantKey].weight,
                    length: this.initForm.variants[firstVariantKey].length,
                    height: this.initForm.variants[firstVariantKey].height,
                    width: this.initForm.variants[firstVariantKey].width,
                };
                this.form.variants[variantId] = {
                    ...this.form.variants[variantId],
                    weight: this.form.variants[firstVariantKey].weight,
                    length: this.form.variants[firstVariantKey].length,
                    height: this.form.variants[firstVariantKey].height,
                    width: this.form.variants[firstVariantKey].width,
                };
            },
            generateVariants() {
                let variants = {};
                let options = [];
                // set 1 as default, if no options, this wont be used
                let totalVariantsCount = 1;
                for (let i = 1; i <= 3; i++) {
                    // filter empty options and options without option's name
                    if (this.form['option_' + i] !== '' && this.form['option_' + i + '_options'].length > 0) {
                        totalVariantsCount *= this.form['option_' + i + '_options'].length;
                        options.push(this.form['option_' + i + '_options']);
                    }
                }

                // only run if options is not empty
                if (options.length > 0) {
                    // how many times an option repeated in variant name before change to next one
                    // exp: total 4 variants, options [[a,b],[c,d]]
                    // repeat a for 2 times (4/2) before switch to b
                    // if after b, the index still less than total variants count, repeat whole process agn
                    let repeatCount = totalVariantsCount;
                    for (let optionsIndex in options) {
                        let index = 1;
                        repeatCount /= options[optionsIndex].length;
                        do {
                            for (let optionIndex in options[optionsIndex]) {
                                for (let i = 0; i < repeatCount; i++) {
                                    // create the variant base
                                    if (!variants.hasOwnProperty('v' + index)) {
                                        variants['v' + index] = {id:'v' + index, name: '', option_1: '', option_2: '', option_3: ''}
                                    } else {
                                        variants['v' + index].name += ' ';
                                    }
                                    variants['v' + index]['option_' + (parseInt(optionsIndex, 10) + 1)] = options[optionsIndex][optionIndex];
                                    variants['v' + index].name += options[optionsIndex][optionIndex];
                                    index++;
                                }
                            }
                        } while (index <= totalVariantsCount);
                    }

                    // put newly generated variants into form
                    // get new variants name list
                    let variantsName = Object.values(variants).map(variant => variant.name);
                    let repeatedName = [];
                    for (let variantId in this.form.variants) {
                        // remove all variants that not in the list
                        if (!variantsName.includes(this.form.variants[variantId].name)) {
                            delete this.form.variants[variantId];
                            delete this.customize.variants[variantId];
                            delete this.validator.variants[variantId];

                            for (let accountId in this.sourceData) {
                                delete this.sourceData[accountId].variants[variantId];
                            }
                        } else {
                            repeatedName.push(this.form.variants[variantId].name);
                        }
                    }

                    for (let variantIndex in variants) {
                        if (!repeatedName.includes(variants[variantIndex].name)) {
                            this.addVariants(variants[variantIndex], variantIndex);
                        }
                    }
                } else {
                    this.$set(this.form, 'variants', {});
                    this.$set(this.customize, 'variants', {});
                    this.$set(this.validator, 'variants', {});

                    for (let accountId in this.sourceData) {
                        this.$set(this.sourceData[accountId], 'variants', {});
                    }
                }
                this.key.variants += 1;
            },
            addVariants(variant = null, variantId = 'v1') {
                // use default create variant
                if (variant === null) {
                    variantId = this.setupVariantBase(this.form, variantId);
                } else {
                    variantId = this.setupVariantBase(this.form, variantId, variant);
                }

                this.initForm.variants[variantId] = cloneDeep(this.form.variants[variantId]);

                this.customize.variants[variantId] = {};
                if (this.selectedSource.id !== 0) {
                    this.customize.variants[variantId] = Object.keys(this.form.variants[variantId]).reduce((properties, property) => ({...properties,
                        [property]: true
                    }));
                }

                this.setupVariantValidator(variantId);

                // add variant base to sourceData
                for (let accountId in this.sourceData) {
                    if (parseInt(accountId, 10) !== 0) {
                        // create empty base if not exist
                        if (!this.sourceData[accountId].variants.hasOwnProperty(variantId)) {
                            this.sourceData[accountId].variants[variantId] = {};
                        }
                        // reference from getAttributes() function at top
                        // TODO: optimize this part
                        // console.time('addVariant - populate variant attributes');
                        if (this.form.category[accountId].hasOwnProperty('id')) {
                            let flatVariantAttributes = [].concat(...Object.values(this.attributes[this.accountIdToCategoryType[accountId]][this.form.category[accountId].id].SKU).map(attributes => Object.values(attributes)));

                            this.sourceData[accountId].variants[variantId]['attributes'] = flatVariantAttributes.reduce((attributes, attribute) => ({...attributes,
                                [attribute.name]: {
                                    value: '',
                                    external_id: attribute.external_id
                                }
                            }),{});
                            this.setupPrices(accountId);

                            if (!this.validator['variants'][variantId].hasOwnProperty('attributes')) {
                                this.validator['variants'][variantId]['attributes'] = {};
                            }
                            if (!this.validator['variants'][variantId]['attributes'].hasOwnProperty(accountId)) {
                                this.validator['variants'][variantId]['attributes'][accountId] = {};
                            }
                            this.validator['variants'][variantId]['attributes'][accountId] = flatVariantAttributes.filter(attribute => this.attributes[this.accountIdToCategoryType[accountId]][this.form.category[accountId].id].SKU.required.constructor === Object && this.attributes[this.accountIdToCategoryType[accountId]][this.form.category[accountId].id].SKU.required.hasOwnProperty(attribute.name)).reduce((attributes, attribute) => ({...attributes, [attribute.name]: {}}),{});
                        }

                        // console.timeEnd('addVariant - populate variant attributes');
                    }
                }

                // auto switch to newly added variant's tab
                if (variant === null) {
                    this.tabIndex.variants = Object.keys(this.form.variants).length - 1;
                    this.key.variants += 1;
                }
            },
            generateVariantName(variantId) {
                let name = '';

                for (let optionKey of ['option_1', 'option_2', 'option_3']) {
                    if (this.form.variants[variantId][optionKey] !== '') {
                        if (name !== '') {
                            name += ' ';
                        }
                        name += this.form.variants[variantId][optionKey];
                    }
                }

                return name;
            },
            updateVariantName(variantId) {
                let name = this.generateVariantName(variantId);

                if (name !== '') {
                    this.form.variants[variantId].name = name;
                }
            },
            /* Variant generation - END */

            /* Validate data - START */
            validateData(validator, name = null, parentName = null) {
                // console.log('name:' + name);
                // console.log(validator);
                if (validator.constructor === Object && name !== 'variants') {
                    // console.log('validator.constructor === Object');
                    if (validator.hasOwnProperty('invalid')) {
                        if (validator.invalid) {
                            if (parentName !== null && name !== null && (!isNaN(name) || !isNaN(parentName))) {
                                name = '[' + this.sourceOption.find(option => option.id === parseInt(!isNaN(name) ? name : parentName, 10)).name + '] ' + (!isNaN(name) ? parentName : name);
                            } else if (typeof name === 'string' && parentName !== null) {
                                name = '(' + parentName + ') ' + name;
                            }
                            notify('top', 'Error', 'Missing ' + name, 'center', 'danger');
                            this.validated = false;
                        }
                    } else if (Object.keys(validator).length > 0) {
                        for (let key in validator) {
                            this.validateData(validator[key], key, name);
                        }
                    }
                } else if (validator.constructor === Object && name === 'variants') {
                    for (let variantId in validator) {
                        let name = this.form.variants[variantId].name !== '' ? this.form.variants[variantId].name : 'variant ' + variantId;
                        this.validateData(validator[variantId], name);
                    }
                }
            },
            /* Validate data - END */

            /* Button function - START */

            //matchAllWithId - used to match id (if form.category[accountId] is id) with selected category's integration category
            syncCategorySelection(mainCategory, matchAllWithId = true, preventFormatAttributes = false) {
                if (!this.isEmptyObject(mainCategory)) {
                    this.setupInitCategory(mainCategory, 0);
                    // when main category changed, change integration category selection too
                    for (let accountId in this.form.category) {
                        if (parseInt(accountId, 10) !== 0) {
                            // this is for integration that dont have any category
                            if (this.accountIdToCategoryType[accountId] === 'none') {
                                this.$set(this.form.category, accountId, {id: this.accountIdToIntegrationId[accountId], integration_id: this.accountIdToIntegrationId[accountId]});
                                this.validator.category[accountId].invalid = false;
                                this.getAttributes(this.form.category[accountId], accountId, preventFormatAttributes);
                                continue;
                            }

                            let categories = this.form.category[0].integration_categories.filter(
                                integrationCategory =>
                                    parseInt(integrationCategory.integration_id, 10) === parseInt(this.accountIdToIntegrationId[accountId], 10)
                                    && parseInt(integrationCategory.region_id, 10) === parseInt(this.accounts[this.accountIdToAccountIndex[accountId]].region_id, 10)
                            );

                            if (categories.length > 0) {
                                categories = cloneDeep(categories);
                                // save mapped integration categories
                                this.categories[accountId] = categories.map(category => ({
                                    text: category.name,
                                    value: category
                                }));
                                if (matchAllWithId && typeof this.form.category[accountId] === 'number') {
                                    let selectedCategory = categories.find(category => category.id === this.form.category[accountId]);

                                    if (typeof selectedCategory !== 'undefined') {
                                        this.$set(this.form.category, accountId, selectedCategory);
                                        this.validator.category[accountId].invalid = false;
                                        this.getAttributes(selectedCategory, accountId, preventFormatAttributes);
                                    }
                                }
                            }
                        }
                    }

                    this.key.integrationCategory += 1;
                }
            },
            syncBrand() {
                if (this.form.brand && this.form.brand.hasOwnProperty('name')) {
                    for (let accountId in this.sourceData) {
                        if (parseInt(accountId, 10) !== 0) {
                            for (let attributeName in this.sourceData[accountId].attributes) {
                                if (attributeName.toLowerCase() === 'brand' && this.accountIdToIntegrationId[accountId] !== 11003) {
                                    this.sourceData[accountId].attributes[attributeName].value = this.form.brand.name;
                                    break;
                                }
                            }
                        }
                    }
                }
            },
            save() {
                this.syncBrand();
                this.changeSource(true);
                this.validated = true;
                this.validateData(this.validator);
                if (this.checkHaveQoo10Integration()) {
                    this.$refs['qoo10-warning-price-modal'].show();
                } else {
                    if (this.validated) {
                        // setup and show progress
                        this.setupProgress();
                        this.$refs['progress-modal'].show();
                        this.saveProduct();
                    }
                }
            },
            continueSave() {
                this.closeQoo10WarningPriceModel();
                if (this.validated) {
                    // setup and show progress
                    this.setupProgress();
                    this.$refs['progress-modal'].show();

                    this.saveProduct();
                }
            },
            async saveProduct(isCreate = false) {
                let processedData = cloneDeep(this.sourceData[0]);
                if (processedData.hasOwnProperty('brand') && typeof processedData.brand === 'object') {
                    processedData.brand = processedData.brand.name;
                }

                // if is from create mode, need to update images and attributes
                if (isCreate) {
                    processedData['fromCreate'] = true;

                    // skip those successfully exported account
                    let skipAccount = [];
                    for (let account of this.accounts) {
                        if (account.hasOwnProperty('exported') && account.exported) {
                            skipAccount.push(account.id);
                        }
                    }
                    let formatSubmitData = this.formatSubmitData(this.sourceData, skipAccount);

                    processedData['prices'] = formatSubmitData['prices'];
                    processedData['images'] = formatSubmitData['images'];
                    processedData['attributes'] = formatSubmitData['attributes'];

                    for (let variantId in processedData.variants) {
                        processedData.variants[variantId]['prices'] = formatSubmitData.variants[variantId].prices;
                        processedData.variants[variantId]['images'] = formatSubmitData.variants[variantId].images;
                        processedData.variants[variantId]['attributes'] = formatSubmitData.variants[variantId].attributes;
                    }
                }

                let result = await this.updateProduct(processedData);

                // temp fix TODO:find why attributes field appeared at new variant tab
                for (let variantId in this.form.variants) {
                    if (isNaN(variantId) && this.form.variants[variantId].hasOwnProperty('attributes')) {
                        delete this.form.variants[variantId]['attributes'];
                    }
                }

                // only update listing on edit mode
                if (result && !isCreate) {
                    for (let listing of this.product.listings) {
                        this.saveListing(listing);
                    }
                }
            },
            saveListing(listing, accountId = null) {
                // used for retry save
                if (listing === null && accountId !== null) {
                    listing = this.product.listings.find(lt => parseInt(lt.account_id, 10) === parseInt(accountId, 10));
                }

                let processedData = this.setupFormBase();
                this.setupCategoryForm(processedData, false, listing.account_id);
                // this.sourceDataToForm(0, processedData);
                // this.sourceDataToForm(listing.account_id, processedData);
                mergeDeep(processedData, this.sourceData[0], this.filterEmptyPrices(cloneDeep(this.sourceData[listing.account_id])));
                // console.log(processedData);

                // remove null attributes
                // processedData['attributes'] = processedData['attributes'].filter(attribute => attribute['value'] !== null);
                // for (let variantIndex in processedData['variants']) {
                //     processedData['variants'][variantIndex]['attributes'] = processedData['variants'][variantIndex]['attributes'].filter(attribute => attribute['value'] !== null);
                // }
                this.updateProductListing(listing.id, processedData, listing.account_id);
            },
            async create() {
                this.syncBrand();
                this.changeSource(true);
                this.validated = true;
                this.validateData(this.validator);
                if (this.checkHaveQoo10Integration()) {
                    this.$refs['qoo10-warning-price-modal'].show();
                } else {
                    if (this.validated) {
                        // setup and show progress
                        this.setupProgress();
                        this.$refs['progress-modal'].show();
                        await this.createProduct();
                    }
                }
            },
            async continueCreate() {
                this.closeQoo10WarningPriceModel();

                if (this.validated) {
                    // setup and show progress
                    this.setupProgress();
                    this.$refs['progress-modal'].show();

                    await this.createProduct();
                }
            },
            async createProduct() {
                // skip it if main product already successfully created previously
                if (this.createdSku !== this.form.associated_sku || this.productSlug === null) {
                    let result = await this.storeProduct(this.formatSubmitData(this.sourceData));

                    if (result !== null) {
                        this.productSlug = result.product_slug;
                        this.createdSku = this.form.associated_sku;
                    }
                } else {
                    await this.saveProduct(true);
                }

                if (this.createdSku === this.form.associated_sku) {
                    for (let account of this.accounts) {
                        this.exportProduct(this.productSlug, account.id);
                    }
                }
            },
            checkProgress(successOnly = false) {
                for (let accountId in this.progress) {
                    if (!successOnly && this.progress[accountId].state !== 'Success' && this.progress[accountId].state !== 'Failed') {
                        return false;
                    } else if (successOnly && this.progress[accountId].state !== 'Success') {
                        return false;
                    }
                }
                return true;
            },
            checkHaveQoo10Integration() {
                return this.accounts.filter(item => item.integration_id == 11004)[0] ? true : false;
            },
            checkHaveLazadaIntegration() {
                return this.accounts.filter(item => item.integration_id == 11001)[0] ? true : false;
            },
            hideProgressModal() {
                this.$refs['progress-modal'].hide();
                this.progress = {};
            },
            resetDefault(name, variantId = null, key = null) {
                let defaultData = cloneDeep(this.sourceData[0]);
                // only enable reset to default function when user edit listing data
                if (this.selectedSource.id !== 0 && variantId === null) {
                    if (this.customize.hasOwnProperty(name) && this.customize[name]) {
                        // revert back to main product updated data
                        if (defaultData.hasOwnProperty(name)) {
                            this.form[name] = defaultData[name];
                        } else {
                            if (!this.isCreate) {
                                this.form[name] = this.product[name];
                            } else {
                                this.form[name] = '';
                            }
                        }
                        // remove customize data
                        delete this.sourceData[this.selectedSource.id][name];
                        // rerender
                        this.key[key] += 1;
                    }
                } else if (this.selectedSource.id !== 0 && variantId !== null) {
                    if (this.customize.variants[variantId].hasOwnProperty(name) && this.customize.variants[variantId][name]) {
                        if (name !== 'prices') {
                            if (defaultData.variants[variantId].hasOwnProperty(name)) {
                                this.form.variants[variantId][name] = defaultData.variants[variantId][name];
                            } else {
                                if (!this.isCreate) {
                                    this.form.variants[variantId][name] = this.product.variants.find(variant => parseInt(variant.id, 10) === parseInt(variantId, 10))[name];
                                } else {
                                    this.form.variants[variantId][name] = '';
                                }
                            }
                            // remove customize data
                            delete this.sourceData[this.selectedSource.id].variants[variantId][name];
                            // rerender
                            this.key[key] += 1;

                            // custom reset
                        } else if (name === 'prices') {
                            // custom price reset
                            let rerender = false;
                            let prices = {};
                            if (defaultData.variants[variantId].hasOwnProperty(name)) {
                                prices = defaultData.variants[variantId][name].reduce((prices,price) => (price), {});
                            } else {
                                if (!this.isCreate) {
                                    prices = this.product.variants.find(variant => parseInt(variant.id, 10) === parseInt(variantId, 10))[name].reduce((prices,price) => ({...prices, [price.type]: {type: price.type, price: price.price}}), {});
                                } else {
                                    prices = {selling: {type: 'selling', price: '0'}};
                                }
                            }

                            for (let priceIndex in this.form.variants[variantId][name]) {
                                let price = this.form.variants[variantId][name][priceIndex];

                                if (prices.hasOwnProperty(price.type)) {
                                    rerender = true;
                                    this.form.variants[variantId][name][priceIndex] = prices[price.type];
                                    this.sourceData[this.selectedSource.id].variants[variantId][name] = this.sourceData[this.selectedSource.id].variants[variantId][name].filter(priceValue => (priceValue.type !== price.type));
                                }
                            }

                            if (rerender) {
                                // rerender
                                this.key[key] += 1;
                            }
                        }
                    }
                }
            },
            generateForm() {
                this.accounts = this.accounts.filter(account => this.preSelectAccount.map(acc => acc.id).includes(account.id));
                this.resetForm();
                this.setupSourceData();

                // auto append 1 variant if it is single variant mode
                if (this.variantMode.selected.length === 0 && Object.keys(this.form.variants).length === 0) {
                    this.addVariants();
                }

                this.$refs['pre-select-account-modal'].hide();
            },
            async addInventory(variantId = this.newInventory.index) {
                let validator = true;
                if (this.newInventory.value.sku === '') {
                    notify('top', 'Error', 'Missing SKU', 'center', 'danger');
                    validator = false;
                }
                if (this.newInventory.value.name === '') {
                    notify('top', 'Error', 'Missing Name', 'center', 'danger');
                    validator = false;
                }
                if (await this.inventoryExist(this.newInventory.value.sku)) {
                    notify('top', 'Error', 'SKU already exist in inventory list', 'center', 'danger');
                    validator = false;
                }
                if (this.newInventory.value.stock < 0) {
                    notify('top', 'Error', 'Stock value is invalid', 'center', 'danger');
                    validator = false;
                }
                if (this.newInventory.value.lowStockNotification < 0) {
                    notify('top', 'Error', 'Low stock notification value is invalid', 'center', 'danger');
                    validator = false;
                }

                if (validator) {
                    // this.inventories.push(cloneDeep(this.newInventory.value));
                    this.form.variants[variantId].inventory = cloneDeep(this.newInventory.value);
                    this.key.variants += 1;
                    notify('top', 'Success', 'Inventory added successfully', 'center', 'success');
                    this.$refs['add-inventory-modal'].hide();
                }
            },
            showInventoryModal(variantId) {
                this.newInventory.index = variantId;
                this.newInventory.value = {
                    sku: '',
                    name: '',
                    stock: '0',
                    lowStockNotification: '5',
                };

                this.$refs['add-inventory-modal'].show();
            },
            selectAccount(account) {
                if (!this.preSelectAccount.find(acc => acc.id === account.id)) {
                    this.preSelectAccount.push({
                        id: account.id,
                        name: this.$options.filters.accountName(account)
                    });
                } else {
                    this.preSelectAccount = this.preSelectAccount.filter(acc => acc.id !== account.id);
                }
            },
            retry(accountId) {
                if (!this.isCreate && accountId === '0') {
                    this.saveProduct();
                } else if (this.isCreate && accountId === '0') {
                    this.createProduct();
                } else if (!this.isCreate && accountId !== '0') {
                    this.saveListing(null, accountId);
                } else if (this.isCreate && accountId !== '0') {
                    this.exportProduct(this.productSlug, accountId);
                }
            },
            resetVariants() {
                // reset form, customize and validator variants
                this.form.variants = {};
                this.customize.variants = {};
                this.validator.variants = {};

                // reset sourceData's variants
                for (let accountId in this.sourceData) {
                    this.sourceData[accountId].variants = {};
                }

                // reset options field
                for (let i = 1; i <= 3; i++) {
                    this.form['option_' + i] = '';
                    this.form['option_' + i + '_options'] = [];
                }

                // create product - auto append 1 variant if it is single variant mode
                if (this.isCreate && this.variantMode.selected.length === 0) {
                    this.addVariants();
                }
            },
            isDisabled(name) {

                // Only a maximum of 2 options seem to be allowed to be created in Lazada for all categories. 
                // Change the restriction so that only the first two options can be filled up, while the last option must be greyed out.
                if (name == 'option_3' && this.is_sale_prop_require && this.is_sale_prop_require > 0) {
                    return true;
                }

                if (this.accounts.length > 1) {
                    if (this.is_sale_prop_require && parseInt(name.slice(-1)) > this.is_sale_prop_require) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
                else {
                    if (this.is_sale_prop_require && parseInt(name.slice(-1)) > this.is_sale_prop_require) {
                        return true;
                    }
                }
              },
            /* Button function - END */

            /* Utility - START */
            appendIntegrationId(objectOrArray, accountId, removeEmptyValue = false, single = false) {
                let result = objectOrArray.constructor === Array ? [] : {};

                if (!single) {
                    for (let index in objectOrArray) {
                        // skip empty value object
                        if (removeEmptyValue && objectOrArray[index].hasOwnProperty('value') && (objectOrArray[index]['value'] === null || objectOrArray[index]['value'] === '')) {
                            continue;
                        }

                        result[index] = {...objectOrArray[index], integration_id: this.accountIdToIntegrationId[accountId], region_id: this.accountToIntegrationRegionId[accountId]};
                    }
                } else {
                    // if it is only single object, append the id directly
                    result = {...objectOrArray, integration_id: this.accountIdToIntegrationId[accountId], region_id: this.accountToIntegrationRegionId[accountId]}
                }

                return result;
            },
            filterEmptyPrices(form) {
                if (form.hasOwnProperty('prices')) {
                    form.prices = form.prices.filter(price => price.price !== '');
                }

                if (form.hasOwnProperty('variants')){
                    for (let variantId in form.variants) {
                        if (form.variants[variantId].hasOwnProperty('prices')) {
                            form.variants[variantId].prices = form.variants[variantId].prices.filter(price => price.price !== '');
                        }
                    }
                }
                return form;
            },
            mergeIntoAttributes(data, accountId) {
                let attributes = {};
                for (let property in data) {
                    if (property !== 'attributes') {
                        attributes[property] = {
                            integration_id: this.accountIdToIntegrationId[accountId],
                            value: data[property]
                        };
                    }
                }
                return attributes;
            },
            changeProgress(accountId, mode, error = null) {
                if (accountId !== null && typeof this.progress[accountId] !== 'undefined') {
                    if (mode === 0) {
                        this.progress[accountId].state = 'Waiting';
                        this.progress[accountId].value = 0;
                    } else if (mode === 1) {
                        this.progress[accountId].state = 'Processing';
                        this.progress[accountId].value = 50;
                    } else if (mode === 2) {
                        this.progress[accountId].state = 'Success';
                        this.progress[accountId].value = 100;
                        this.progress[accountId].error = [];
                    } else if (mode === 3) {
                        this.progress[accountId].state = 'Failed';
                        this.progress[accountId].value = 50;
                        this.progress[accountId].error = [];

                        if (typeof error === 'string') {
                            this.progress[accountId].error.push(error);
                        } else if (error !== null && error.constructor === Array) {
                            for (let err of error) {
                                this.progress[accountId].error.push(err);
                            }
                        }
                    }
                    this.key.progress += 1;
                }
            },
            isEmptyObject(object) {
                return $.isEmptyObject(object);
            },
            snakeCase(value) {
                return value.replace(/\.?([A-Z]+)/g, '_$1').toLowerCase().replace(/(^_)|\s/g, "");
            },
            convertDataAttribute(type, value) {
                if (type == '7') {
                    value = this.convertDataImage(value)
                }
                return value;
            },
            convertDataImage(value) {
                if (!value) {
                    return [];
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    return [{"image_url": value}];
                }
            }
            /* Utility - END */
        },
        filters: {
            accountName(account, isLabel = false) {
                return (account.hasOwnProperty('integration_name') ? account.integration_name : account.integration.name) + ' ' + account.region.shortcode + ' ' + '(   ' + account.name + ')'+ (isLabel? ':' : '');
            },
            idToText(header, isVariant = false, isLabel = false) {
                if (isVariant) {
                    header = header.substring(8);
                }
                return header.replace(/_/g, ' ') + (isLabel? ':' : '');
            },
            stateToVariant(state) {
                if (state === 'Waiting') return 'secondary';
                else if (state === 'Processing') return 'primary';
                else if (state === 'Success') return 'success';
                else if (state === 'Failed') return 'danger';
                else return '';
            }
        }
    }
</script>

<style lang="scss" scoped>
    .form-group {
        margin-bottom: 0;
        margin-top: 1.5rem;
    }

    .px-4 {
        .hard-d-none {
            display: none !important;
        }
    }

    .input_text {
        height: 32px;
        width: 300px;
    }

    .margin-left-20 {
        margin-left: 20px;
    }

    .margin-top-3em {
        margin-top: 3em;
    }

    .color-pink {
        color: #cbced5;
    }

    table {
        width: calc(25% - 20px);
        border: 1px solid #eaeaea;
        box-shadow: 2px 2px 4px rgba(0,0,0,.2);
        padding: 15px 22px;
    }

    table tbody button {
        border-bottom: 1px solid #eaeaea;
    color: #777;
    /* height: 32px; */
    font-weight: 600;
    border: none;
    font-size: 0.9em;
    /* color: white; */
    background: white;
    /* margin-bottom: 60px;
    
        &:last-child {
            border-bottom: none;
        }
        
        &.active {
            color: #ef5350;
        }
    }
    .sticky-height {
        // viewpoint height - sticky card margin
        height: calc(100vh - 3rem);
        // match bottom spacing with global setting
        margin-bottom: calc(30px + 1.5rem);
    }

    .sidebar-nav {
        /*overflow-y: auto;*/
        /*height: calc(100% - 155px);*/
    }

    .sidebar-nav .nav-pills .nav-item {
        padding-right: 0;
    }

    .sidebar-nav .nav-pills .nav-item a {
        box-shadow: none;
        padding: .5rem;
        font-size: .9rem;
    }

    .sidebar-button {
        position: absolute;
        bottom: 1rem;
        // 100% width - parent padding
        width: calc(100% - 3rem);
    }

    .badge-button {
        padding-top: 1.2px !important;
        padding-bottom: 1.2px !important;
        font-size: 0.65rem !important;
        text-transform: uppercase !important;
    }

    .rotate-90 {
        transform: rotate(90deg);
    }

    .account-box .row .text-truncate {
        color: #4DC0B5;
    }

    .account-box:hover .row .text-truncate {
        color: white;
    }

    .selected-account {
        background-color: #5E72E4;
        color: white !important;

        .text-info, .text-muted {
            color: white !important;
        }
    }

    .show-border .nav-link {
        border-color: #dee2e6 #dee2e6 #f8fafc;
    }
</style>
