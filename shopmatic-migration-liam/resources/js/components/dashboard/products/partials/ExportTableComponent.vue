<template>
    <div class="card shadow-none mb-0">
        <div class="card-header pt-0">
            <div class="d-flex">
                <div>
                    <button class="btn btn-outline-default" type="button" @click="back">
                        <span class="btn-inner--icon"><i class="fas fa-angle-double-left"></i></span>
                        <span class="btn-inner--text">Back to account</span>
                    </button>
                    <button class="btn btn-sm btn-info ml-3" @click="retrieveProducts"><i class="fa fa-sync-alt"></i></button>
                    <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                </div>
                <div class="ml-auto">
                    <b-button variant="outline-primary" @click="saveProducts()" :disabled="sending_request === true">Save All</b-button>
                    <b-button variant="success" @click="exportProducts()" :disabled="sending_request === true">Export All</b-button>
                </div>
            </div>
        </div>

        <div id="filter" class="collapse">
            <div class="p-3" style="background: #f6f6f6;">
                <b-row>
                    <b-col class="mt-2">
                        <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                        <input id="search" v-model="search" name="search" class="form-control">
                    </b-col>
                    <div class="col-12 text-center py-3">
                        <button class="btn btn-primary px-5" @click="filter">Filter</button>
                    </div>
                </b-row>
            </div>
        </div>

        <export-table-filter-component
            :account="account"
            @filter:category="filterCategory"
            @filter:integration-category="filterIntegrationCategory"
        ></export-table-filter-component>

        <div class="card">
            <button class="btn btn-info" v-if="!show_table" @click="show_table = !show_table">Show Edit Table</button>
            <button class="btn btn-info" v-if="show_table" @click="show_table = !show_table">Hide Table</button>
        </div>

        <!-- Light table -->
        <div id="index-table" class="table-responsive" v-if="show_table">
            <table class="table align-items-center table-bordered" :key="key.table">
                <template v-for="(category, category_index) in products">
                    <!-- Show grouped category breadcrumb -->
                    <thead class="thead-dark">
                    <tr>
                        <th :colspan="default_colspan" class="text-white">{{ category.category_label }}</th>
                        <th v-if="max_attribute_count > 0" :colspan="max_attribute_count"></th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <!-- End grouped category breadcrumb -->

                    <!-- Show grouped integration category breadcrumb -->
                    <template v-for="(integration_category, integration_category_index) in category.integration_categories">
                        <thead class="table-info">
                        <tr>
                            <th :colspan="default_colspan">{{ integration_category.integration_category_label }}</th>
                            <th v-if="max_attribute_count > 0" :colspan="max_attribute_count"></th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <!-- End grouped integration category breadcrumb -->

                        <!-- Show default columns and category attribute columns-->
                        <thead class="thead-light">
                        <tr>
                            <th></th>
                            <th>Status</th>
                            <th v-for="column in default_columns">{{ column.label }}</th>
                            <th v-for="column in integration_category.attribute_columns">
                                {{ column.label }} <span class="text-danger">{{ (column.required) ? '*' : '' }}</span>
                            </th>
                            <template v-for="n in (parseInt(max_attribute_count) - integration_category.attribute_columns.length)">
                                <th></th>
                            </template>
                        </tr>
                        </thead>
                        <!-- End default columns and category attribute columns-->

                        <tbody class="list">
                        <template v-for="(item, item_index) in integration_category.children">
                            <tr v-bind:class="{ 'bg-light': item.is_processing }">
                                <td class="text-right">
                                    <template v-if="!item.is_processing">
                                        <div class="dropdown">
                                            <button class="btn btn-info dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Action
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                <a class="dropdown-item" v-if="account.integration_id !== 11007" href="javascript:void(0)" @click="openModal('change-integration-category-modal', [category_index, integration_category_index, item_index])">Change Integration Category</a>
                                                <a class="dropdown-item" href="#" @click="saveProduct(item, $event)">Save</a>
                                                <a class="dropdown-item" href="#" @click="exportProduct(item, $event)">Export</a>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <template v-if="item.product_export_tasks.length">
                                        <!-- Check whether is under processing -->
                                        <template v-if="item.is_processing">
                                            <b-button v-b-tooltip.hover title="Product is under processing of exporting">
                                                <i class="fas fa-sync text-warning"></i>
                                            </b-button>
                                        </template>
                                        <template v-else>
                                            <b-button @click="openModal('export-tasks-modal', [category_index, integration_category_index, item_index])">
                                                <i class="fas fa-exclamation text-danger"></i>
                                            </b-button>
                                        </template>
                                    </template>
                                </td>
                                <td v-for="default_column in default_columns">
                                    <template v-if="default_column.field === 'basic_info'">
                                        <b-row style="width: 500px;">
                                            <b-col md="4" class="pt-2">
                                                <label class="text-capitalize">Name: </label>
                                            </b-col>
                                            <b-col md="8">
                                                <b-input
                                                    :name="'name[' + item.id + ']'"
                                                    v-model="item.name"
                                                    :id="'form-input-name-' + item.id"
                                                    class="ml-2 mb-2 mr-sm-2 mb-sm-0"
                                                    :disabled="item.is_processing"
                                                ></b-input>
                                            </b-col>
                                        </b-row>
                                        <b-row>
                                            <b-col md="4" class="pt-2">
                                                <label>SKU: </label>
                                            </b-col>
                                            <b-col md="8" class="pt-2">
                                                <b-input
                                                    :name="'associated_sku[' + item.id + ']'"
                                                    v-model="item.associated_sku"
                                                    :id="'form-input-associated-sku-' + item.id"
                                                    class="ml-2 mb-2 mr-sm-2 mb-sm-0"
                                                    :disabled="item.is_processing"
                                                ></b-input>
                                            </b-col>
                                        </b-row>
                                        <b-row>
                                            <b-col md="4" class="pt-2">
                                                <label>Description & Images <b-badge pill variant="primary">{{ item.all_images.length }}</b-badge> </label>
                                            </b-col>
                                            <b-col md="8"  class="pt-2">
                                                <b-link href="javascript:void(0)" @click="openModal('desc-modal', [category_index, integration_category_index, item_index])" class="ml-2">Edit</b-link>
                                            </b-col>
                                        </b-row>
                                        <b-row v-if="account.integration_id === 11001">
                                            <b-col md="4" class="pt-2">
                                                <label>Brand</label>
                                            </b-col>
                                            <b-col md="8" class="pt-2">
                                                <async-multiselect
                                                    type="single_select_brand"
                                                    :id="'product-attributes-'+ item.id +'-brand-input'"
                                                    :model.sync="item.brand"
                                                    :region-id="account.region_id"
                                                />
                                            </b-col>
                                        </b-row>
                                    </template>
                                    <template v-else-if="(default_column.field === 'weight' || default_column.field === 'width' || default_column.field === 'height' || default_column.field === 'length') && !item.is_processing">
                                        <b-row style="width: 180px;">
                                            <b-col class="text-center">
                                                <b-button :id="'duplicate-'+ default_column.field +'-' + item.id" tabindex="0" class="text-primary" size="sm">
                                                    Apply To All
                                                </b-button>
                                            </b-col>
                                        </b-row>
                                        <b-tooltip :target="'duplicate-'+ default_column.field +'-' + item.id" variant="primary" triggers="hover">
                                            <input type="text" @input="copyValue($event, item, default_column.field)">
                                        </b-tooltip>
                                    </template>
                                    <template v-else-if="default_column.type === 'modal'">
                                        <template v-if="default_column.field === 'logistics' && logistics_list.length !== 0 && integration_category.integration_category_id">
                                            <div class="row mb-2">
                                                <div class="col text-center">
                                                    <b-link href="javascript:void(0)" @click="openModal('logistics-modal', [category_index, integration_category_index, item_index])" class="btn btn-primary btn-sm ml-2">Please Select</b-link>
                                                </div>
                                            </div>
                                            <!--<div class="row">
                                                <div class="col text-center">
                                                    Total Selected: {{ products[productIndex]['children'][itemIndex].attributes.find(attribute => attribute.name === 'logistics').totalSelectedCount }}
                                                </div>
                                            </div>-->
                                        </template>
                                        <template v-else-if="default_column.field === 'locations' && account.locations.length !== 0 && integration_category.integration_category_id">
                                            <div class="row mb-2">
                                                <div class="col text-center">
                                                    <b-link href="javascript:void(0)" @click="openModal('locations-modal', [category_index, integration_category_index, item_index])" class="btn btn-primary btn-sm ml-2">Please Select</b-link>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            -
                                        </template>
                                    </template>
                                    <template v-else>
                                        <b-row style="width: 200px;">
                                            <b-col>
                                                <template v-if="default_column.type === 'input'">
                                                    <input v-model="item[default_column.field]" class="form-control" type="text" :name="'name[' + item.id + ']'" :disabled="item.is_processing">
                                                </template>
                                                <template v-else>
                                                    <span class="text-primary">{{ item[default_column.field] }}</span>
                                                </template>
                                            </b-col>
                                        </b-row>
                                    </template>
                                </td>
                                <template v-if="item.category_attributes.length">
                                    <td v-for="category_attribute in item.category_attributes">
                                        <b-row style="min-width: 300px;">
                                            <b-col>
                                                <input-field-component
                                                    :type="category_attribute.input"
                                                    :id="'product-attributes-'+ item.id +'-'+ category_attribute.name + '-input'"
                                                    :model.sync="category_attribute.value"
                                                    :options="category_attribute.data"
                                                    :placeholder="'Enter ' + category_attribute.name"
                                                    :disabled="item.is_processing"
                                                    settings="attribute_string_mode"
                                                />
                                            </b-col>
                                        </b-row>
                                    </td>
                                </template>
                                <template v-else v-for="n in parseInt(max_attribute_count)">
                                    <td></td>
                                </template>
                                <!--<template v-if="item.category_attributes.length <= 0">
                                    <td class="text-right">
                                        <template v-if="!item.is_processing">
                                            <div class="dropdown">
                                                <button class="btn btn-info dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                    <a class="dropdown-item" href="#" @click="saveProduct(item, $event)">Save</a>
                                                    <a class="dropdown-item" href="#" @click="exportProduct(item, $event)">Export</a>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                </template>
                                <template v-else>
                                    <td v-for="n in (parseInt(max_attribute_count) - item.category_attributes.length)"></td>
                                    <td class="text-right">
                                        <template v-if="!item.is_processing">
                                            <div class="dropdown">
                                                <button class="btn btn-info dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                    <a class="dropdown-item" href="#" @click="saveProduct(item, $event)">Save</a>
                                                    <a class="dropdown-item" href="#" @click="exportProduct(item, $event)">Export</a>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                </template>-->
                            </tr>
                            <!-- Show variants detail -->
                            <tr v-if="item.variants.length" v-for="(variant, index) in item.variants" v-bind:class="[item.is_processing ? 'bg-light' : 'table-primary']">
                                <td></td>
                                <td></td>
                                <td v-for="default_column in default_columns">
                                    <template v-if="default_column.field === 'basic_info'">
                                        <b-row>
                                            <b-col md="4" class="pt-2">
                                                <label class="text-capitalize">Name: </label>
                                            </b-col>
                                            <b-col md="8" class="pt-2">
                                                <span class="ml-2">{{ variant.name }}</span>
                                            </b-col>
                                        </b-row>
                                        <b-row>
                                            <b-col md="4" class="pt-2">
                                                <label>SKU: </label>
                                            </b-col>
                                            <b-col md="8" class="pt-2">
                                                <span class="ml-2">{{(variant.sku) ? variant.sku : '(No SKU)' }}</span>
                                            </b-col>
                                        </b-row>

                                        <b-row>
                                            <b-col md="4" class="pt-2">
                                                <label>Images <b-badge pill variant="primary">{{ variant.all_images.length }}</b-badge> </label>
                                            </b-col>
                                            <b-col md="8"  class="pt-2">
                                                <b-link href="javascript:void(0)" @click="openModal('images-modal', [category_index, integration_category_index, item_index, index])" class="ml-2">Edit</b-link>
                                            </b-col>
                                        </b-row>
                                    </template>
                                    <template v-else>
                                        <b-row>
                                            <b-col>
                                                <template v-if="default_column.type === 'input'">
                                                    <input v-model="variant[default_column.field]" class="form-control" type="text" :disabled="item.is_processing">
                                                </template>
                                                <template v-else-if="default_column.type === 'number'">
                                                    <input v-model="variant[default_column.field]" class="form-control" type="number" :disabled="item.is_processing">
                                                </template>
                                                <template v-else>
                                                    <span class="text-primary">{{ variant[default_column.field] }}</span>
                                                </template>
                                            </b-col>
                                        </b-row>
                                    </template>
                                </td>
                                <template v-for="n in item.category_attributes.length">
                                    <td></td>
                                </template>
                                <template v-if="variant.category_attributes.length">
                                    <td v-for="category_attribute in variant.category_attributes">
                                        <b-row style="min-width: 300px;">
                                            <b-col>
                                                <input-field-component
                                                    :type="category_attribute.input"
                                                    :id="'product-attributes-'+ item.id + '-' + variant.id + '-' + category_attribute.name + '-input'"
                                                    :model.sync="category_attribute.value"
                                                    :options="category_attribute.data"
                                                    :placeholder="'Enter ' + category_attribute.name"
                                                    :disabled="item.is_processing"
                                                    settings="attribute_string_mode"
                                                />
                                            </b-col>
                                        </b-row>
                                    </td>
                                </template>
                            </tr>
                            <!-- End show variants detail -->
                        </template>
                        </tbody>
                    </template>
                </template>
            </table>

            <h3 v-if="products.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
        </div>

        <div v-show="!retrieving" v-if="pagination != null && products.length > 0 && show_table">
            <pagination-component :custom-class="{limit: 'mb-0', jump_to: 'mb-0'}" :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
        </div>

        <!-- Queue Message Modal-->
        <b-modal ref="export-tasks-modal" size="lg" title="Export Task Messages" :header-bg-variant="'primary'">
            <template v-slot:modal-title>
                <h3 class="text-white">Export Task Messages</h3>
            </template>
            <div :key="key.export_message" v-if="product_index.length">
                <template v-if="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]]['product_export_tasks'].length">
                    <b-table
                        responsive
                        :sort-by.sync="sort.status.column"
                        :sort-desc.sync="sort.status.desc"
                        :fields="fields.status"
                        :items="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]]['product_export_tasks']">
                        <template v-slot:cell(#)="data">
                            {{ data.index + 1 }}
                        </template>
                    </b-table>

                </template>
            </div>
            <template v-slot:modal-footer>
                <div class="w-100">
                    <b-button
                        variant="primary"
                        class="float-right"
                        @click="hideModal('export-tasks-modal')"
                    >
                        OK
                    </b-button>
                </div>
            </template>
        </b-modal>
        <!-- End Queue Message Modal -->

        <!-- Description Modal -->
        <b-modal size="xl" ref="desc-modal" title="Edit Description & Images" :header-bg-variant="'primary'" id="description-modal">
            <template v-slot:modal-header="{ close }">
                <h3 class="text-white">Edit Description & Images</h3>
            </template>
            <div :key="key.description" v-if="product_index.length">
                <b-form-group
                    label="Short Description"
                    label-for="short-description-textarea"
                    invalid-feedback="Short Description is required"
                >
                    <b-form-textarea
                        id="short-description-textarea"
                        v-model="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]]['short_description']"
                        placeholder="Short Description"
                        rows="3"
                        max-rows="6"
                    ></b-form-textarea>
                </b-form-group>

                <b-form-group
                    label="HTML Description"
                    label-for="html-description-textarea"
                    invalid-feedback="HTML Description is required"
                >
                    <tinymce-vue :model.sync="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]]['html_description']" type="description"></tinymce-vue>
                </b-form-group>

                <b-form-group>
                   <template #label>
                               Images<span class="text-red"> *</span>
                   </template>
                    <vue-dropzone-image
                        id="images"
                        :model.sync="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]]['all_images']"
                    />
                </b-form-group>
            </div>
            <template v-slot:modal-footer>
                <div class="w-100">
                    <b-button
                        variant="primary"
                        class="float-right"
                        @click="hideModal('desc-modal')"
                    >
                        OK
                    </b-button>
                </div>
            </template>
        </b-modal>
        <!-- End Description Modal -->

        <!-- Images Modal -->
        <b-modal size="xl" ref="images-modal" title="Edit Images" :header-bg-variant="'primary'" id="image-modal">
            <template v-slot:modal-header="{ close }">
                <h3 class="text-white">Edit Images</h3>
            </template>

            <template v-if="product_index.length > 3"
            >
                <vue-dropzone-image
                id="images-variants"
                :model.sync="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]]['variants'][product_index[3]]['all_images']"
            />
            </template>

            <template v-slot:modal-footer>
                <div class="w-100">
                    <b-button
                        variant="primary"
                        class="float-right"
                        @click="hideModal('images-modal')"
                    >
                        OK
                    </b-button>
                </div>
            </template>
        </b-modal>
        <!-- End Images Modal -->

        <!-- Logistics Modal -->
        <b-modal ref="logistics-modal" size="lg" title="Logistics" :header-bg-variant="'primary'" id="logistics-modal">
            <template v-slot:modal-header="{ close }">
                <h3 class="text-white">Logistics</h3>
            </template>

            <div :key="key.logistic">
                <b-form-group>
                    <template v-if="this.logistics_list && product_index.length && products[product_index[0]]['integration_categories'][product_index[1]]['integration_category_id']">
                        <edit-logistic-component
                            :integration-id="account.integration_id"
                            :logistics="logistics_list"
                            :model.sync="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]].attributes.find(attribute => attribute.name === 'logistics').value"
                        />
                    </template>
                </b-form-group>
            </div>

            <template v-slot:modal-footer>
                <div class="w-100">
                    <b-button
                        variant="primary"
                        class="float-right"
                        @click="hideModal('logistics-modal')"
                    >
                        OK
                    </b-button>
                </div>
            </template>
        </b-modal>
        <!-- End of Logistics Modal -->

        <!-- Locations Modal -->
        <b-modal ref="locations-modal" size="lg" title="Locations" :header-bg-variant="'primary'" id="locations-modal">
            <template v-slot:modal-header="{ close }">
                <h3 class="text-white">Returned Address</h3>
            </template>

            <div :key="key.location">
                <b-form-group>
                    <template v-if="this.account.locations && product_index.length && (account.integration_id === 11004 || account.integration_id === 11005) && products[product_index[0]]['integration_categories'][product_index[1]]['integration_category_id']">
                        <input-field-component
                            type="single_select"
                            id="product-locations-input"
                            :model.sync="products[product_index[0]]['integration_categories'][product_index[1]]['children'][product_index[2]].attributes.find(attribute => attribute.name === 'locations').value"
                            track-by="external_id"
                            :options="this.account.locations"
                            placeholder="-- Select a location --"
                            customOptionTemplate="locations"
                        />
                    </template>
                </b-form-group>
            </div>

            <template v-slot:modal-footer>
                <div class="w-100">
                    <b-button
                        variant="primary"
                        class="float-right"
                        @click="hideModal('locations-modal')"
                    >
                        OK
                    </b-button>
                </div>
            </template>
        </b-modal>
        <!-- End of Locations Modal -->

        <!-- Option Notice Modal-->
        <b-modal ref="option-notify-modal" hide-footer>
            <template v-slot:modal-title>
                Notice Message
            </template>
            <div class="d-block text-center">
                <h3>Current marketplace only support {{ this.options_level }} option level. <br/>
                    There is product options more than {{ this.options_level }} level.</h3>
            </div>
            <b-button class="mt-3" @click="hideModal('option-notify-modal')">Close</b-button>
        </b-modal>
        <!-- End of Option Notice Modal-->

        <!-- Change Integration Category Modal-->
        <b-modal ref="change-integration-category-modal" size="lg">
            <template v-slot:modal-title>
                Change Integration Category
            </template>

            <div :key="key.change_integration_category" v-if="product_index.length">
                <!--<async-multiselect
                    class="d-none"
                    type="single_select_category"
                    :model.sync="change_category"
                />

                <template v-for="(regionGroup, integrationId) in integrationsCategoryList">
                    <template v-for="(integrationCategories, regionId) in regionGroup">
                        <h3 class="text-muted font-weight-bold mt-2">{{ account.integration.name }} ({{ account.region.name }})</h3>
                        &lt;!&ndash;<input-field-component
                            type="single_select"
                            :model.sync="change_integration_category"
                            :options="integrationCategories"
                        />&ndash;&gt;
                        <async-multiselect
                            type="single_select_integration_category"
                            :key="'change-integration-category-' + key.change_integration_category"
                            :model.sync="change_integration_category"
                            :integrationId="integrationId"
                            :regionId="regionId"
                        />
                    </template>
                </template>-->

                <async-multiselect
                    type="single_select_integration_category"
                    :key="'change-integration-category-' + key.change_integration_category"
                    :model.sync="change_integration_category"
                    :integrationId="account.integration.id"
                    :regionId="account.region.id"
                />
            </div>

            <template v-slot:modal-footer>
                <b-button class="mt-3" variant="danger" @click="hideModal('change-integration-category-modal')">Close</b-button>
                <b-button class="mt-3" variant="primary" @click="changeIntegrationCategory">Save</b-button>
            </template>
            <!-- End of Change Integration Category Modal-->
        </b-modal>
    </div>
</template>

<script>
    import Multiselect from 'vue-multiselect';
    import EditLogisticComponent from "./EditLogisticComponent";
    import TinymceVue from "../../../utility/TinymceVue";
    import VueDropzoneImage from "../../../utility/VueDropzoneImage";
    import AsyncMultiselect from "../../../utility/AsyncMultiselect";
    import InputFieldComponent from "../../../utility/InputFieldComponent";
    import PaginationComponent from "../../components/PaginationComponent";
    import ExportTableFilterComponent from "./ExportTableFilterComponent";
    const axios = require('axios').default;

    export default {
        name: "ExportTableComponent",
        props: ['account', 'section'],
        components: {
            ExportTableFilterComponent,
            Multiselect,
            EditLogisticComponent,
            TinymceVue,
            AsyncMultiselect,
            PaginationComponent,
            InputFieldComponent,
            VueDropzoneImage,
        },
        data () {
            return {
                show_table: false,
                default_columns: [
                    {
                        label: 'Basic Info',
                        field: 'basic_info',
                    },
                    {
                        label: 'Weight',
                        field: 'weight',
                        type: 'number'
                    },
                    {
                        label: 'Width',
                        field: 'width',
                        type: 'number'
                    },
                    {
                        label: 'Length',
                        field: 'length',
                        type: 'number'
                    },
                    {
                        label: 'Height',
                        field: 'height',
                        type: 'number'
                    },
                    {
                        label: 'Logistics',
                        field: 'logistics',
                        type: 'modal'
                    },
                    {
                        label: 'Locations',
                        field: 'locations',
                        type: 'modal'
                    },
                ],
                default_colspan: 8,
                max_attribute_count: 0,
                products: [],
                logistics_list: [],
                retrieving: false,
                skip_retrieving: false, // Added this when user change category too rapidly, then will skip the retrieving checking
                sending_request: false,
                pagination: null,
                limit: 10,
                key: {
                    table: 'table-0',
                    description: 'description-',
                    export_message: 'export-message-',
                    logistic: 'logistic-',
                    location: 'location-',
                    change_integration_category: 'change-integration-category'
                },
                search: null,
                selected_category: null,
                selected_integration_category: null,
                product_index: [],
                save_attributes: [],
                fields: {
                    status: ['#', 'status', 'messages', { key: 'updated_at', sortable: true }]
                },
                sort: {
                    status: {
                        column: 'updated_at',
                        desc: true
                    }
                },
                product_images: [],
                product_options: [],
                prices: [],
                price_types: [],
                start_index_column: 1,
                options_level: null,
                show_options_msg: false,
                change_category: null,
                change_integration_category: {}
            }
        },
        computed: {
            /*integrationsCategoryList() {
                let integrationsCategoryList = {};
                this.change_integration_category = {};

                if (this.change_category && this.change_category.integration_categories && this.account.integration !== null) {
                    for (let integrationCategory of this.change_category.integration_categories) {
                        if (this.account.integration_id !== integrationCategory.integration_id || this.account.region_id !== integrationCategory.region_id) {
                            continue;
                        }
                        if (!integrationsCategoryList.hasOwnProperty(integrationCategory.integration_id)) {
                            integrationsCategoryList[integrationCategory.integration_id] = {};
                        }
                        if (!integrationsCategoryList[integrationCategory.integration_id].hasOwnProperty(integrationCategory.region_id)) {
                            integrationsCategoryList[integrationCategory.integration_id][integrationCategory.region_id] = [];
                        }

                        integrationsCategoryList[integrationCategory.integration_id][integrationCategory.region_id].push(integrationCategory);
                    }
                }
                return integrationsCategoryList;
            }*/
        },
        methods : {
            filterCategory(category) {
                this.selected_category = this.selected_integration_category = null;
                if (category && category.id) {
                    this.selected_category = category.id;
                }
                this.skip_retrieving = true;
                this.retrieveProducts();
            },
            filterIntegrationCategory(category, integrationsCategory) {
                this.selected_category = this.selected_integration_category = this.selected_integration_category_label = null;
                if (category && category.id) {
                    this.selected_category = category.id;
                }
                if (integrationsCategory && integrationsCategory.id)  {
                    this.selected_integration_category = integrationsCategory.id;
                    this.selected_integration_category_label = integrationsCategory.name;
                }
                this.skip_retrieving = true;
                this.retrieveProducts();
            },
            filter() {
                this.pagination = null;
                this.product_index = [];
                this.retrieveProducts();
            },
            retrieveAccount: function() {
                axios.get('/web/accounts/' + this.account.id, {
                    params: this.params
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.retrieveProducts();
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveProducts() {
                if (this.retrieving && !this.skip_retrieving) {
                    return;
                }
                this.retrieving = true;
                this.product_index = [];
                let parameters = {
                    account: this.account.id,
                    integration: this.account.integration_id,
                    search: this.search,
                    category: this.selected_category,
                    integration_category: this.selected_integration_category,
                    type: 1,
                    page: this.pagination != null ? this.pagination.current_page : 1,
                    limit: this.limit,
                };
                axios.get('/web/products', { params: parameters }
                ).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        // Group product by category
                        let group_products = this.groupBy(data.response.items);
                        this.max_attribute_count = 0;
                        // Retrieve category attributes
                        group_products.products.forEach((group_product) => {
                            group_product.integration_categories.forEach((integration_product) => {
                                // TAKE NOTE - For those integration which does not have category or is account category
                                if (integration_product.integration_category_id || [11002, 11006, 11007].includes(this.account.integration_id)) {
                                    this.retrieveCategoryAttributes(integration_product.integration_category_id)
                                        .then((response) => {
                                            let attributes_length = 0;

                                            if (response.attributes) {
                                                // Remove brand in integration category attribute for lazada, use the brand data in brands table
                                                if (this.account.integration_id == 11001) {
                                                    response.attributes = response.attributes.filter((attribute) => {
                                                        return attribute.name != 'brand';
                                                    });
                                                }

                                                //response.attributes = this.formatAttributes(response.attributes);
                                                response.attributes = this.$root.formatAttributes(response.attributes);

                                                let name_keys = ['required', 'optional'];

                                                name_keys.forEach(name => {
                                                    // Push general required and optional
                                                    Object.values(response.attributes.GENERAL[name]).forEach(attribute => {
                                                        if (attribute) {
                                                            // Add category attribute to columns
                                                            integration_product.attribute_columns.push({
                                                                'label': attribute.label,
                                                                'field': attribute.name,
                                                                'required': attribute.required
                                                            });

                                                            // Convert radio data to bootstrap vue format
                                                            if (attribute.type === 10 && attribute.data) {
                                                                attribute.data = Object.keys(attribute.data).map(function(key) {
                                                                    return {'text': attribute.data[key], 'value': attribute.data[key]};
                                                                });
                                                            }

                                                            // Add category attribute to product
                                                            integration_product.children.forEach((product) => {
                                                                // Get selected value
                                                                let value = null;
                                                                // Get attribute value with integration_id and region_id
                                                                value = product.attributes.find((attr) => {
                                                                    /* Temporary hot fix for import csv file integration category attribute name refer to CSM-252 */
                                                                    if ((attr['name'] === attribute.name || attr['name'].replace(/_/g, " ") === attribute.name) && !attr['product_variant_id'] && attr['region_id'] === this.account.region_id) {
                                                                        return attr;
                                                                    }
                                                                });

                                                                // Else get attribute value with integration_id only and region id null
                                                                if (!value) {
                                                                    value = product.attributes.find((attr) => {
                                                                        /* Temporary hot fix for import csv file integration category attribute name refer to CSM-252 */
                                                                        if ((attr['name'] === attribute.name || attr['name'].replace(/_/g, " ") === attribute.name) && !attr['product_variant_id'] && !attr['region_id']) {
                                                                            return attr;
                                                                        }
                                                                    });
                                                                }

                                                                if (value) {
                                                                    value = value['value'];
                                                                } else {
                                                                    value = null;
                                                                }

                                                                // If type is multi select then default should be empty array
                                                                if (attribute.type === 3 || attribute.type === 4 || attribute.type === 9) {
                                                                    if (value) {
                                                                        // Check whether is json string
                                                                        if (this.isJsonString(value)) {
                                                                            value = JSON.parse(value);
                                                                        }
                                                                    } else {
                                                                        // If value is empty then convert to empty array
                                                                        value = [];
                                                                    }
                                                                }

                                                                // Change image type's value to array
                                                                if (attribute.type === 7) {
                                                                    value = this.toJsonParse(value)
                                                                }
                                                                // Date format/Rich text format
                                                                if (attribute.type === 1 || attribute.type === 6) {
                                                                    value = (value) ? value : '';
                                                                }

                                                                product.category_attributes.push({
                                                                    'name': attribute.name,
                                                                    'data': attribute.data,
                                                                    'input': attribute.type,
                                                                    'required': (attribute.required === 1),
                                                                    'value': value
                                                                });
                                                            });

                                                        }
                                                    });

                                                    attributes_length += Object.values(response.attributes.GENERAL[name]).length;
                                                });

                                                name_keys.forEach(name => {
                                                    // Push sku required and optional
                                                    Object.values(response.attributes.SKU[name]).forEach(attribute => {
                                                        if (attribute) {
                                                            // Add category attribute to columns
                                                            integration_product.attribute_columns.push({
                                                                'label': attribute.label,
                                                                'field': attribute.name,
                                                                'required': attribute.required
                                                            });

                                                            // Add category attribute to variant
                                                            integration_product.children.forEach((product) => {
                                                                if (product.variants.length) {
                                                                    product.variants.forEach((variant) => {
                                                                        // Get selected value
                                                                        let value = null;

                                                                        // Get attribute value with integration_id and region_id
                                                                        value = variant.attributes.find((attr) => {
                                                                            /* Temporary hot fix for import csv file integration category attribute name refer to CSM-252 */
                                                                            if ((attr['name'] === attribute.name || attr['name'].replace(/_/g, " ") === attribute.name) && attr['product_variant_id'] && attr['region_id'] === this.account.region_id) {
                                                                                return attr;
                                                                            }
                                                                        });

                                                                        // Else get attribute value with integration_id only and region id null
                                                                        if (!value) {
                                                                            value = variant.attributes.find((attr) => {
                                                                                /* Temporary hot fix for import csv file integration category attribute name refer to CSM-252 */
                                                                                if ((attr['name'] === attribute.name || attr['name'].replace(/_/g, " ") === attribute.name) && attr['product_variant_id'] && !attr['region_id']) {
                                                                                    return attr;
                                                                                }
                                                                            });
                                                                        }

                                                                        if (value) {
                                                                            value = value['value'];
                                                                        } else {
                                                                            value = null;
                                                                        }

                                                                        // If type is multi select then default should be empty array
                                                                        if (attribute.type === 3 || attribute.type === 4 || attribute.type === 9) {
                                                                            if (value) {
                                                                                // Check whether is json string
                                                                                if (this.isJsonString(value)) {
                                                                                    value = JSON.parse(value);
                                                                                }
                                                                            } else {
                                                                                // If value is empty then convert to empty array
                                                                                value = [];
                                                                            }
                                                                        }

                                                                        // Change image type's value to array
                                                                        if (attribute.type === 7) {
                                                                            value = this.toJsonParse(value);
                                                                        }
                                                                        // Rich text format/Date format
                                                                        if (attribute.type === 1 || attribute.type === 6) {
                                                                            value = (value) ? value : '';
                                                                        }

                                                                        variant.category_attributes.push({
                                                                            'name': attribute.name,
                                                                            'data': attribute.data,
                                                                            'input': attribute.type,
                                                                            'value': value
                                                                        });
                                                                    })
                                                                }
                                                            });
                                                        }
                                                    });
                                                    attributes_length += Object.values(response.attributes.SKU[name]).length;
                                                });

                                                if (attributes_length > this.max_attribute_count) {
                                                    this.max_attribute_count = attributes_length;
                                                }
                                                /*if (generalAttributeLength > this.generalAttributeCount) {
                                                    this.generalAttributeCount = generalAttributeLength;
                                                }*/

                                                integration_product.children.forEach((product) => {
                                                    // If got logistic,then check for logistic in attribute to retrieve the value
                                                    if (this.logistics_list) {
                                                        this.getLogisticAttributeValue(product);
                                                    }
                                                    // Qoo10 need to support locations
                                                    if (this.account.integration_id === 11004 || this.account.integration_id === 11005) {
                                                        this.getLocationAttributeValue(product);
                                                    }
                                                    // Get prices values and add price to product data
                                                    this.convertPrices(product);
                                                    // Get options values from attribute/product table and add append option to product data
                                                    this.convertOptions(product);

                                                    if (product.variants.length) {
                                                        product.variants.forEach((variant) => {
                                                            // Get prices values and add price to variant data
                                                            this.convertPrices(variant);
                                                        });
                                                    }
                                                });
                                            }
                                        }).catch((error)  => {
                                        this.retrieving = false;
                                        this.skip_retrieving = false;
                                        if (error.response && error.response.data && error.response.data.meta) {
                                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                                        } else {
                                            notify('top', 'Error', error, 'center', 'danger');
                                        }
                                    });
                                }
                            });
                        });

                        this.pagination = data.response.pagination;
                        this.products = group_products.products;
                    }
                    this.retrieving = false;
                    this.skip_retrieving = false;
                    this.key.table += 1;
                }).catch((error) => {
                    this.retrieving = false;
                    this.skip_retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveCategoryAttributes (integration_category_id) {
                let url = '/web/categories/'+ integration_category_id +'/attributes';
                let parameters = { account: this.account.id, type: 'IntegrationCategory' };
                return axios.get(url, { params: parameters }
                ).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (data.response.logistics && !Array.isArray(this.logistics_list) || !this.logistics_list.length) {
                            this.logistics_list = data.response.logistics;
                        }
                        if (data.response.prices && !Array.isArray(this.price_types) || !this.price_types.length) {
                            this.price_types = data.response.prices;
                            // Filter prices column, based on integration
                            this.filterPricesColumns(this.price_types);
                        }
                        // Filter options level
                        if (data.response.options && !this.options_level) {
                            this.options_level = data.response.options;
                            // Filter options column, based on integration
                            this.filterOptionsColumns(this.options_level);
                        }
                        return data.response;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            openModal(ref, product_index) {
                this.product_index = product_index;
                this.$refs[ref].show();

                /*if (ref === 'change-integration-category-modal') {
                    this.change_category = this.products[this.product_index[0]]['category_id'];
                }*/

                setTimeout(() => {
                    this.key.description += 1;
                    this.key.logistic += 1;
                    this.key.export_message += 1;
                    this.key.location += 1;
                    this.key.change_integration_category += 1;
                }, 1);
            },
            groupBy(products) {
                const product_result = {};
                var uncategorizedChildren= [];
                products.forEach(product => {
                    if (product_result[product.category_id] == undefined) {
                        product_result[product.category_id] = {
                            category_label: (product.category_breadcrumb) ? product.category_breadcrumb : '-',
                            category_id: (product.category_id) ? product.category_id : null,
                            integration_categories: {},
                        };
                    }
                    // Check whether there is integration category or uncategorized
                    if (!product.integration_category) {
                        let breadcrumb = (this.account.integration_id === 11007) ? 'No integration category' : 'Uncategorized';

                        product.integration_category = {
                            'id': 0, // 0 means is uncategorized
                            'breadcrumb': breadcrumb,
                            'name': breadcrumb,
                        };
                    }

                    if (product_result[product.category_id]['integration_categories'][product.integration_category.id] == undefined) {
                        product_result[product.category_id]['integration_categories'][product.integration_category.id] = {
                            integration_category_label: (product.integration_category.breadcrumb) ? product.integration_category.breadcrumb : '-',
                            integration_category_id: (product.integration_category.id) ? product.integration_category.id : null,
                            attribute_columns: [],
                            children: [],
                        };
                    }

                    // Check whether is under processing
                    this.filterExportTasks(product);

                    // If there is exists attributes then shift the attribute value to main field value
                    this.shiftAttributeValue(product);

                    // Shift price if there is any overwrite price
                    this.shiftPriceValue(product);

                    // Filter the images by integration_id or without integration_id
                    this.filterImages(product);

                    // Add a category attributes to main product
                    product['category_attributes'] = [];
                    if (product.variants.length) {
                        product.variants.forEach((variant) => {
                            // Shift and filter the same thing for variants
                            this.shiftAttributeValue(variant, true);
                            this.shiftPriceValue(variant, true);
                            this.filterImages(variant);

                            // Add a category attributes to variant
                            variant['category_attributes'] = [];
                        })
                    }
                    /**
                     * If selected integration category and uncategorized then don't push children
                     * to product_result integration categories children just push to uncategorizedChildren.
                     * If uncategorized item push to attribute to integration_category_id and its value.
                     */
                    if(this.selected_integration_category && product.integration_category.id == 0 && this.account.integration_id !== 11007){
                        product.attributes.push({
                            name: 'integration_category_id',
                            value: this.selected_integration_category,
                        });
                        uncategorizedChildren.push(product);
                    }else{
                        product_result[product.category_id]['integration_categories'][product.integration_category.id]['children'].push(product);
                    }
                });

                // Convert to array
                /*let result = Object.keys(product_result).map((key) => (
                    {
                        category_label: (product_result[key][0]['category_breadcrumb']) ? product_result[key][0]['category_breadcrumb'] : '-',
                        category_id: (product_result[key][0]['category_id']) ? product_result[key][0]['category_id'] : null,
                        attribute_columns: [],
                        children: product_result[key],

                    })
                );*/
                //let result = Object.keys(product_result).map((k) => product_result[k]);
                if(this.selected_integration_category && uncategorizedChildren.length > 0) {
                    const filteredByProductList = Object.keys(product_result).map((k) => product_result[k]);
                    let categorizedItemsExist = false;
                    let updatedIntegrationCategoryChildren = [];
                    filteredByProductList.forEach( (data, index) => {
                            let integration_categories = data.integration_categories;
                            for (var key of Object.keys(integration_categories)) {
                                if (key  == this.selected_integration_category && integration_categories[key]['integration_category_label'].toLowerCase() !== "uncategorized") {
                                    let children = typeof integration_categories[key]['children'] !== 'undefined' ? integration_categories[key]['children'] : [];
                                    categorizedItemsExist = typeof children !== 'undefined' && children.length > 0 ? true : false;
                                    if (children.length > 0 && typeof  updatedIntegrationCategoryChildren[key]  == 'undefined') {
                                        let mergedChildrens = children.concat(uncategorizedChildren);
                                        updatedIntegrationCategoryChildren[key] = {};
                                        updatedIntegrationCategoryChildren[key]  = mergedChildrens;
                                    }
                                }
                            }
                            if (typeof data.integration_categories !== 'undefined' && typeof data.integration_categories[0] !== 'undefined' && data.integration_categories[0]['integration_category_label'].toLowerCase() == "uncategorized" && updatedIntegrationCategoryChildren.length > 0) {
                                delete data.integration_categories[0];
                                if (typeof data.integration_categories[this.selected_integration_category] !== 'undefined' && data.integration_categories[this.selected_integration_category]['children'] !== 'undefined') {
                                    data.integration_categories[this.selected_integration_category]['children'] = updatedIntegrationCategoryChildren[this.selected_integration_category];
                                }
                            }
                    });
                    if (!categorizedItemsExist) {
                            filteredByProductList.forEach( (data, index) => {
                                let integration_categories = data.integration_categories;
                                for (var key of Object.keys(integration_categories)) {
                                    if (integration_categories[key]['integration_category_label'].toLowerCase() == "uncategorized" && uncategorizedChildren.length > 0) {
                                        integration_categories[key]['integration_category_id'] = this.selected_integration_category;
                                        integration_categories[key]['integration_category_label'] = this.selected_integration_category_label;
                                        if (typeof integration_categories[key]['children'] !== 'undefined') {
                                            integration_categories[key]['children'] = uncategorizedChildren;
                                        }
                                    }
                                }
                            });
                    }
                }

                let result = Object.keys(product_result).map((key) => {
                    return {
                        category_label: product_result[key].category_label,
                        category_id: product_result[key].category_id,
                        integration_categories: Object.keys(product_result[key].integration_categories).map((integration_key) => product_result[key].integration_categories[integration_key])
                    }
                });

                return {
                    'products': result,
                }
            },
            filterExportTasks (product) {
                product['is_processing'] = false;
                if (product.product_export_tasks.length > 0) {
                    // Filter out all the export tasks under this account
                    let accountId = this.account.id;

                    product.product_export_tasks = product.product_export_tasks.filter(function (product_export_task) {
                        return product_export_task.account_id === accountId;
                    });

                    // Check the last export task whether is processing status
                    if (product.product_export_tasks.length > 0) {
                        if (product.product_export_tasks[product.product_export_tasks.length-1].status === 'Processing') {
                            product['is_processing'] = true;
                        }
                    }
                }
            },
            async saveProducts() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Saving products draft..', 'center', 'info');

                // Loop all products
                await Promise.all(this.products.map(async (group) => {
                    if (group.integration_categories.length) {
                        await Promise.all(group.integration_categories.map(async (integration_category) => {
                            if (integration_category.children.length) {
                                await Promise.all(integration_category.children.map(async (product) => {
                                    await this.savePost(product);
                                }));
                            }
                        }));
                    }
                }));

                notify('top', 'Success', 'Products save successfully', 'center', 'success');
                this.sending_request = false;
                this.retrieveProducts();
            },
            async saveProduct(product, e) {
                e.preventDefault();

                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Saving product draft..', 'center', 'info');

                await this.savePost(product);
                this.retrieveProducts();
            },
            async changeIntegrationCategory() {
                if (this.sending_request) {
                    return;
                }
                if (!this.change_integration_category.id) {
                    notify('top', 'Error', 'Please select an integration category', 'center', 'danger');
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Changing integration category..', 'center', 'info');

                let product = this.products[this.product_index[0]]['integration_categories'][this.product_index[1]]['children'][this.product_index[2]];

                await this.savePost(product, 'change_integration_category');
                this.hideModal('change-integration-category-modal');
                this.product_index = [];
                this.retrieveProducts();
            },
            async savePost(product, type = null) {
                // Make sure product is not under processing
                if (!product.is_processing) {
                    // if type is null mean normal save product
                    if (type === null) {
                        // Attributes format transform
                        this.attributesTransform(product);
                        await axios.post('/web/products/export/'+ product.slug +'/save', {
                            'attributes': this.save_attributes,
                            'integration': this.account.integration_id,
                            'region_id': this.account.region_id,
                            'images': this.product_images,
                            'options': this.product_options,
                            'prices': this.prices,
                        }).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Product draft saved successfully', 'center', 'success');
                            }

                            this.sending_request = false
                        }).catch((error) => {
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                            this.sending_request = false
                        });
                    } else if (type === 'change_integration_category') {
                        let attributes = [];
                        attributes.push({
                            'name': 'integration_category_id',
                            'region_id': this.account.region_id,
                            'value': this.change_integration_category.id,
                        });

                        await axios.post('/web/products/export/'+ product.slug +'/save', {
                            'attributes': attributes,
                            'integration': this.account.integration_id,
                        }).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Change integration category successfully', 'center', 'success');
                            }
                            this.sending_request = false
                        }).catch((error) => {
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                            this.sending_request = false
                        });
                    }
                }
            },
            async exportProducts() {
                //await this.saveProducts();

                if (this.sending_request) {
                    return '';
                }

                // make sure user selected category and integration category
                if (!this.selected_category || (!this.selected_integration_category && ![11002, 11006, 11007].includes(this.account.integration_id))){
                    notify('top', 'Error', 'Please choose a category and integration category to export all products', 'center', 'danger');
                    return;
                }

                this.sending_request = true;
                let attempt = 0;
                let product_ids = [];
                let url = '/web/products/export/all/accounts/'+ this.account.id;

                /*this.products.map((group) => {
                    if (group.integration_categories.length) {
                        group.integration_categories.map((integration_category) => {
                            integration_category.children.map((product) => {
                                // Make sure product is not under processing
                                if (!product.is_processing) {
                                    product_ids.push(product.id);
                                }
                            })
                        });
                    }
                });*/

                await axios.get(url, {
                    params: {
                        category_id: this.selected_category,
                        integration_category_id: this.selected_integration_category
                    }
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.debug) {
                        notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                    } else if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });

                // Loop all products
                /*await Promise.all(this.products.map(async (group) => {
                    if (group.integration_categories.length) {
                        await Promise.all(group.integration_categories.map(async (integration_category) => {
                            await Promise.all(integration_category.children.map(async (product) => {
                                // Make sure product is not under processing
                                if (!product.is_processing) {
                                    // Sleep every 3 items, to avoid heavy load (Can refer to CSM-230 issue)
                                    if (attempt >= 3) {
                                        await this.sleep(1000);
                                        attempt = 0;
                                    }

                                    let url = '/web/products/export/'+ product.slug +'/accounts/'+ this.account.id +'/export';
                                    attempt++;
                                    // Call api
                                    await axios.get(url).then((response) => {
                                        let data = response.data;
                                        if (data.meta.error) {
                                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                                        }
                                    }).catch((error) => {
                                        if (error.response && error.response.data && error.response.data.debug) {
                                            notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                                        } else if (error.response && error.response.data && error.response.data.meta) {
                                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                                        } else {
                                            notify('top', 'Error', error, 'center', 'danger');
                                        }
                                    });
                                }
                            }));
                        }));
                    }
                }));*/
                notify('top', 'Success', 'Successfully queued export of product.', 'center', 'success');
                this.sending_request = false;
                this.retrieveProducts();
            },
            async exportProduct(product, e) {
                e.preventDefault();

                await this.saveProduct(product, e);

                if (this.sending_request) {
                    return '';
                }
                this.sending_request = true;

                if (!product.is_processing) {
                    let url = '/web/products/export/'+ product.slug +'/accounts/'+ this.account.id +'/export';

                    // Call api
                    await axios.get(url).then((response) => {
                        let data = response.data;
                        this.sending_request = false;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Product export queued successfully', 'center', 'success');
                        }
                    }).catch((error) => {
                        if (error.response && error.response.data && error.response.data.debug) {
                            notify('top', 'Error', error.response.data.debug[0].message, 'center', 'danger');
                        } else if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }
                        this.sending_request = false;
                    });
                    this.retrieveProducts();
                }
            },
            getLogisticAttributeValue(product) {
                // If the product dont have logistic attribute, then add
                let logistic_attribute = product.attributes.find(attribute => attribute.name === 'logistics');
                if (!logistic_attribute) {
                    product.attributes.push({
                        name: 'logistics',
                        value: "[]",
                        integration_id: this.account.integration_id,
                        totalSelectedCount: 0
                    });
                } else {
                    if (logistic_attribute.value) {
                        let value = JSON.parse(logistic_attribute.value);
                        logistic_attribute.totalSelectedCount = value.length;
                    }
                }
            },
            getLocationAttributeValue(product) {
                // If the product dont have location attribute, then add
                let location_attribute = product.attributes.find(attribute => attribute.name === 'locations');
                if (!location_attribute) {
                    product.attributes.push({
                        name: 'locations',
                        value: null,
                    });
                } else {
                    if (location_attribute.value) {
                        let value = JSON.parse(location_attribute.value);
                        // Make sure the location account id is under this account
                        if (value.account_id != this.account.id) {
                            location_attribute.value = null;
                        } else {
                            location_attribute.value = value;
                        }
                    } else {
                        location_attribute.value = null;
                    }
                }
            },
            convertOptions(product) {
                // Check attribute first, if attribute does not exists then use products table
                let options_attribute = product.attributes.find(attribute => attribute.name === 'options');
                let options = product.options;
                if (options_attribute) {
                    // Convert string to json format and append
                    options = JSON.parse(options_attribute['value']);
                }
                let count = 1;

                if (options === null || typeof options === "undefined") {
                    options = [];
                }

                Object.values(options).map((value) => {
                    product['option_' + count] = value;
                    count++;
                });

                // Make sure there is 3 options
                [1, 2, 3].forEach(function(i) {
                    let option = product['option_' + i];
                    if (!option) {
                        product['option_' + i] = '';
                    }
                });

                /*
                * Check options level and total of options if more then options level then notify user
                * Lazada has fixed variations for different category, so no need to show the message
                * */
                if (!this.show_options_msg && this.account.integration_id !== 11001) {
                    if (Object.keys(options).length > this.options_level) {
                        this.$refs['option-notify-modal'].show();
                        this.show_options_msg = true;
                    }
                }
            },
            convertPrices(product) {
                this.price_types.forEach((price_type) => {
                    let priceValue = '';
                    // If dun have price with integration_id and region, then get with integration_id, then init just get price with NULL integration_id
                    const integration_id = this.account.integration_id;
                    const region_id = this.account.region_id;

                    let filter_prices = product.prices.find(function(price) {
                        if (price.type === price_type && price.integration_id === integration_id && price.region_id === region_id) {
                            return price;
                        }
                    });

                    if (!filter_prices) {
                        filter_prices = product.prices.find(function(price) {
                            if (price.type === price_type && price.integration_id === integration_id) {
                                return price;
                            }
                        });
                    }

                    if (!filter_prices) {
                        filter_prices = product.prices.find(function(price) {
                            if (price.type === price_type) {
                                return price;
                            }
                        });
                    }

                    if (filter_prices) {
                        priceValue = filter_prices.price;
                    }
                    // If there is price with integration_id then use it, else check integration_id null, else just empty
                    /*if (filter_prices) {
                        priceValue = filter_prices.price;
                    } else {
                        filter_prices = product.prices.find(function(price) {
                            if (price.type === price_type) {
                                return price;
                            }
                        });
                        if (filter_prices) {
                            priceValue = filter_prices.price;
                        }
                    }*/

                    // get price if price_selling empty
                    if(price_type == 'selling' && !priceValue) {
                        priceValue = product.price;
                    }

                    product['price_' + price_type] = priceValue;
                });
            },
            optionsFormat(product) {
                // Convert to product options format
                this.product_options = {};
                for (let i = 1; i <= this.options_level; i++) {
                    if (product['option_' + i]) {
                        this.product_options = {
                            ...this.product_options,
                            [this.snakeCase(product['option_' + i])]: product['option_' + i]
                        };
                    }
                }
            },
            pricesFormat(product, variant_id = null) {
                // Convert to product prices format
                this.price_types.forEach((price_type) => {
                    this.prices.push({
                        'currency': this.account.currency,
                        'price': product['price_' + price_type],
                        'type': price_type,
                        'product_variant_id': variant_id
                    });
                });
            },
            shiftAttributeValue(item, $isVariant = false) {
                let attribute_names = [
                    'name',
                    'price',
                ];

                if ($isVariant) {
                    attribute_names.push('weight', 'length', 'width', 'height', 'option_1', 'option_2', 'option_3');
                } else {
                    attribute_names.push('associated_sku', 'brand', 'model', 'short_description', 'html_description');
                }

                item.attributes.forEach((attribute) => {

                    if (attribute.name && attribute.name == 'color_thumbnail') {
                        try {
                            item[attribute.name] = JSON.parse(attribute.value);
                        } catch (error) {
                             item[attribute.name] = [];
                        }
                    }

                    if ($isVariant && attribute.product_variant_id && attribute.product_variant_id == item.id) {
                        if (attribute_names.includes(attribute.name)) {
                            item[attribute.name] = attribute.value;
                        }
                    } else if (!attribute.product_variant_id) { // Exclude the variant attribute for main product
                        if (attribute_names.includes(attribute.name)) {
                            item[attribute.name] = attribute.value;
                        }
                    }
                });

                // Convert brand json string
                if (item.brand) {
                    // Check whether is json string
                    if (this.isJsonString(item.brand)) {
                        item['brand'] = JSON.parse(item.brand);
                    } else {
                        item['brand'] = {};
                    }
                } else {
                    // If value is empty then convert to empty array
                    item['brand'] = {};
                }

                // Double check brand again
                if (!item['brand'] || typeof item['brand'] === "undefined" || item['brand'] == null || item['brand'] === undefined) {
                    item['brand'] = {};
                }
            },
            shiftPriceValue(item, $isVariant = false) {
                item.prices.forEach((price) => {
                    if ($isVariant && price.product_variant_id && price.product_variant_id === item.id && price.currency === this.account.currency && price.integration_id === this.account.integration_id /*&& price.type === 'selling'*/) {
                        item['price_' + price.type] = price.price;
                    } else if (!price.product_variant_id && price.currency === this.account.currency && price.integration_id === this.account.integration_id /*&& price.type === 'selling'*/) {  // Exclude the variant prices for main product
                        item['price_' + price.type] = price.price;
                    }
                });
            },
            filterImages(product) {
                if (product.all_images.length) {
                    // If dun have images with integration_id, then init just get images with NULL integration_id
                    const integration_id = this.account.integration_id;
                    let filter_images = product.all_images.filter(function (image) {
                        return image.integration_id === integration_id;
                    });

                    // If filtered images is empty, then get all images instead
                    if (filter_images.length == 0) {
                        filter_images = product.all_images
                    }

                    // If there is images with integration_id then use it, else use integration_id null by init
                    if (filter_images.length) {
                        product.all_images = filter_images;
                    } else {
                        product.all_images = product.all_images.filter(function (image) {
                            return image.integration_id === null;
                        });
                        // If filtered images is empty, then get all images instead
                        if (product.all_images.length == 0) {
                            product.all_images = product.all_images
                        }
                    }
                }
            },
            filterPricesColumns(price_types) {
                // Add price column (Depends on integration)
                price_types.forEach((price_type) => {
                    this.default_columns.splice(this.start_index_column, 0, {
                        label: 'Price (' + price_type + ')',
                        field: 'price_' + price_type,
                        type: 'input',
                    });
                    this.start_index_column++;
                    this.default_colspan++;
                });
            },
            filterOptionsColumns(options_level) {
                // Show options by level (Depends on integration)
                for (let i = 1; i <= options_level; i++) {
                    this.default_columns.splice(this.start_index_column, 0, {
                        label: 'Option '+ i,
                        field: 'option_' + i,
                        type: 'input'
                    });
                    this.start_index_column++;
                    this.default_colspan++;
                }
            },
            formatAttributes(category_attributes) {
                let format_attributes = {
                    'GENERAL' : {
                        'required' : [],
                        'optional' : []
                    },
                    'SKU' : {
                        'required' : [],
                        'optional' : []
                    }
                };

                category_attributes.forEach((attribute) => {
                    if (attribute.level === 0 && attribute.required) {
                        format_attributes['GENERAL']['required'].push(attribute);
                    } else if (attribute.level === 0 && !attribute.required) {
                        format_attributes['GENERAL']['optional'].push(attribute);
                    } else if (attribute.level === 1 && attribute.required) {
                        format_attributes['SKU']['required'].push(attribute);
                    } else if (attribute.level === 1 && !attribute.required) {
                        format_attributes['SKU']['optional'].push(attribute);
                    }
                });

                return format_attributes;
            },
            attributesTransform(item) {
                let fixed_product_attribute_fields = [
                    'name',
                    'associated_sku',
                    'brand',
                    'price',
                    'short_description',
                    'html_description',
                    'images',
                    'options',
                ];

                let fixed_variant_attribute_fields = [
                    'name',
                    'price',
                    'weight',
                    'length',
                    'width',
                    'height',
                    'images',
                ];

                // Check for options level
                if (this.options_level) {
                    for (let i = 1; i <= this.options_level; i++) {
                        fixed_variant_attribute_fields.push('option_' + i);
                    }
                }

                this.save_attributes = [];
                this.product_images = [];
                this.prices = [];
                // Main products
                fixed_product_attribute_fields.forEach((attribute) => {
                    // If is image attribute store into images data property
                    if (attribute === 'images') {
                        item.all_images.map((image) => {
                            this.product_images.push(image);
                        });
                    } else if (attribute === 'options') {
                        // Convert to product options format
                        this.optionsFormat(item);
                    } else if (attribute === 'price') {
                        this.pricesFormat(item);
                    } else {
                        // If attribute exists then push into transform
                        if (typeof (item[attribute]) !== 'undefined') {
                            this.save_attributes.push({
                                'name': attribute,
                                'value': item[attribute],
                            });
                        }
                    }
                });

                // Check and add logistic
                if (this.logistics_list && item.attributes.find(attribute => attribute.name === 'logistics')) {
                    this.save_attributes.push({
                        'name': 'logistics',
                        'value': item.attributes.find(attribute => attribute.name === 'logistics').value,
                    });
                }

                // Add location for qoo10
                if ((this.account.integration_id === 11004 || this.account.integration_id === 11005) && item.attributes.find(attribute => attribute.name === 'locations')) {
                    this.save_attributes.push({
                        'name': 'locations',
                        'value': item.attributes.find(attribute => attribute.name === 'locations').value,
                    });
                }
 
                // Category attribute (Dynamic)
                if (item.category_attributes.length) {
                    item.category_attributes.forEach((category_attribute) => {
                        this.save_attributes.push({
                                'name': category_attribute.name,
                                'value': category_attribute.value,
                            });
                    });
                }

                // Variants
                if (item.variants.length) {
                    item.variants.forEach((variant) => {
                        fixed_variant_attribute_fields.forEach((attribute) => {
                            if (attribute === 'images') {
                                variant.all_images.map((image) => {
                                    if (!image['product_variant_id']) {
                                        image['product_variant_id'] = variant.id;
                                    }
                                    this.product_images.push(image);
                                });
                            } else if (attribute === 'price') {
                                this.pricesFormat(variant, variant.id);
                            } else {
                                // If attribute exists then push into transform
                                if (typeof (variant[attribute]) !== 'undefined') {
                                    this.save_attributes.push({
                                        'name': attribute,
                                        'value': variant[attribute],
                                        'variant_id': variant.id
                                    });
                                }
                            }
                        });

                        // Category attribute (Dynamic)
                        if (variant.category_attributes.length) {
                            variant.category_attributes.forEach((category_attribute) => {
                                this.save_attributes.push({
                                        'name': category_attribute.name,
                                        'value': category_attribute.value,
                                        'variant_id': variant.id
                                    });
                            });
                        }
                    });
                }
                // Save integration category only if selected integration category exist
                if (this.selected_integration_category && item.attributes.find(attribute => attribute.name === 'integration_category_id')) {
                    this.save_attributes.push({
                        'name': 'integration_category_id',
                        'value': item.attributes.find(attribute => attribute.name === 'integration_category_id').value,
                    });
                }
            },
            copyValue(event, item, field) {
                // Make sure there is value and got variants
                if (event.target.value && item.variants.length) {
                    item.variants.map((variant) => {
                        if (variant[field]) {
                            variant[field] = event.target.value;
                        }
                    });
                }
            },
            isJsonString(value) {
                if (typeof value !== "string") {
                    return false;
                }
                try {
                    JSON.parse(value);
                    return true;
                } catch (error) {
                    return false;
                }
            },
            toJsonParse(value) {
                if (!value) {
                    return [];
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    return [];
                }
            },
            back() {
                this.$emit('change-section', 1);
            },
            hideModal(ref) {
                this.$refs[ref].hide();
            },
            snakeCase(value) {
                return value.replace(/\.?([A-Z]+)/g, '_$1').toLowerCase().replace(/(^_)|\s/g, "");
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit;
                this.retrieveProducts();
            },
            async sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }
            /*updateParams(newProps) {
                this.serverParams = Object.assign({}, this.serverParams, newProps);
            }*/
        },
        created () {
            this.retrieveAccount();
        },
        watch: { },
    }
</script>

<style scoped>
    #description-modal___BV_modal_outer_, #image-modal___BV_modal_outer_, #logistics-modal___BV_modal_outer_ {
        z-index: 1051 !important;
    }
    #index-table {
        height: 80vh;
    }
</style>
