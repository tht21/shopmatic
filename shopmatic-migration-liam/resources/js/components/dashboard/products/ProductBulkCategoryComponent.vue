<template>
    <div>
        <b-row>
            <b-col>
                <b-card>
                    <b-card-header>
                        <h2 class="mb-0">Edit Category</h2>
                    </b-card-header>

                    <b-card-body>
                        <h3 class="text-muted font-weight-light">Search Category</h3>
                        <async-multiselect
                            type="single_select_category"
                            settings="uncategorized"
                            :model.sync="selected_category"
                            @update:model="selectCategory()"
                        />
                        <a href="#" @click="showUncategorized">Show uncategorized products</a>
                    </b-card-body>

                    <b-card-body>
                        <div class="my-3">
                            <template v-if="products && products.length > 0 && !retrieving">
                                <h3 class="text-muted font-weight-light">Product List</h3>
                                <b-table
                                    id="table-product-lists"
                                    :key="products.id"
                                    responsive
                                    :fields="product_fields"
                                    :items="products"
                                    hover
                                    head-variant="light"
                                >
                                    <template #head(checkbox)="data">
                                        <b-form-checkbox
                                            v-model="selected_all"
                                            @change="selectAll(data)"
                                        />
                                    </template>
                                    <template #cell(checkbox)="data">
                                        <b-form-checkbox
                                            v-model="data.item.selected"
                                            @change="select(data.item)"
                                        />
                                    </template>
                                    <template #cell(main_image)="data">
                                        <img v-if="data.item.main_image && data.item.main_image  !== ''" :src="data.item.main_image" class="product-img-thumb">
                                        <img v-else :src="'/images/default.png'" class="product-img-thumb">
                                    </template>
                                </b-table>

                                <div class="py-4" v-if="!retrieving">
                                    <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
                                </div>
                            </template>
                        </div>
                    </b-card-body>

                    <b-card-body>
                        <div class="mt-3">
                            <template v-if="selected_products && selected_products.length > 0">
                                <h3 class="text-muted font-weight-light">Selected Products</h3>
                                <b-table
                                    v-if="selected_products && selected_products.length > 0"
                                    id="table-selected-products"
                                    :key="'selected-products-' + selected_products.id"
                                    responsive
                                    :fields="selected_product_field"
                                    :items="selected_products"
                                    hover
                                    head-variant="light"
                                >
                                    <template #cell(main_image)="data">
                                        <img v-if="data.item.main_image && data.item.main_image  !== ''" :src="data.item.main_image" class="product-img-thumb">
                                        <img v-else :src="'/images/default.png'" class="product-img-thumb">
                                    </template>
                                </b-table>
                            </template>
                        </div>
                    </b-card-body>

                    <b-card-body>
                        <div class="mt-3">
                            <template v-if="selected_products && selected_products.length > 0">
                                <h3 class="text-muted font-weight-light">Assign a category to the seleted products</h3>
                                <async-multiselect
                                    type="single_select_category"
                                    :model.sync="selected_assign_category"
                                    :key="'assign-category-' + selected_products.id"
                                />
                            </template>
                        </div>
                    </b-card-body>

                    <b-card-footer class="text-center">
                        <template v-if="selected_products && selected_products.length > 0">
                            <b-button variant="success" @click="changeCategory()" :disabled="sending_request === true">Save</b-button>
                        </template>
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>
    </div>
</template>

<script>
    export default {
        name: "ProductBulkCategoryComponent",
        data() {
            return {
                selected_category: {
                    id: null,
                    name: null,
                    label: null,
                    integration_categories: null,
                },
                selected_assign_category: {
                    id: null,
                    name: null,
                    label: null,
                    integration_categories: null,
                },
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                product_fields: [
                    { key: 'checkbox', label: ''},
                    { key: 'id', label: 'ID' },
                    { key: 'main_image', label: 'Image' },
                    { key: 'name', label: 'Name' },
                    { key: 'associated_sku', label: 'Sku' },
                ],
                selected_product_field: [
                    { key: 'id', label: 'ID' },
                    { key: 'main_image', label: 'Image' },
                    { key: 'name', label: 'Name' },
                    { key: 'associated_sku', label: 'Sku' },
                ],
                limit: 10,
                retrieving: false,
                sending_request: false,
                products: [],
                selected_all: false,
                selected_products: []
            }
        },
        methods: {
            showUncategorized() {
                Vue.set(this.selected_category, 'id', -1)
                Vue.set(this.selected_category, 'name', 'Uncategorized')
                this.retrieveProducts()
            },
            retrieveProducts() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                this.selected_all = false;
                this.products = [];

                let parameters = {
                    page: this.pagination.current_page,
                    limit: this.limit,
                    orphaned_product: 1,
                    category_id: this.selected_category.id
                };
                axios.get('/web/products', {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        //this.products = data.response.items;

                        // Restore the selected state before passing the array to b-table
                        this.products = data.response.items.map(product => {
                            // if the user is found in selectedUsers array, then set
                            // set the selected state to `true`, otherwise `false`
                            // to restore the selected state
                            product.selected = !!this.selected_products.find(p => p.id === product.id);
                            return product;
                        });
                        // Check whether current page products is all selected
                        const selected = this.products.filter(product => product.selected);
                        if (selected.length === this.products.length) {
                            this.selected_all = true
                        } else {
                            this.selected_all = false
                        }
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            selectCategory() {
                this.pagination.current_page = 1;
                this.pagination.from = 1;
                this.selected_all = false;
                this.selected_products = [];

                this.retrieveProducts();
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit;
                this.retrieveProducts();
            },
            selectAll() {
                // Change all the products selected state
                this.selected_all = !this.selected_all;
                this.products.forEach(product => product.selected = this.selected_all);

                const not_selected = this.products.filter(product => !product.selected);
                const selected = this.products.filter(product => product.selected);

                // new selected users array without current page selected users
                let selected_users_copy = this.selected_products.slice().filter(product =>
                    !not_selected.find(e => e.id === product.id)
                );
                if(not_selected.length > 0) {
                    this.selected_all = true;
                } else {
                    this.selected_all = false;
                }
                this.selected_products = [
                    ...selected_users_copy,
                    ...selected.filter(product => !selected_users_copy.find(e => e.id === product.id))
                ]
            },
            select(product) {
                // Change current product selected state
                product.selected = !product.selected;
                this.selected_all = false;

                // Check whether current page products is all selected
                const selected = this.products.filter(product => product.selected);
                if (selected.length === this.products.length) {
                    this.selected_all = true
                } else {
                    this.selected_all = false
                }
                let is_double = false;
                if (this.selected_products.find(p => p.id === product.id)) is_double = true;
                if(product.selected) {
                    if(is_double) {
                        console.log('double if user selected and isDouble', is_double);
                        console.log("object already exists", this.selected_products);
                        return;
                    } else {
                        this.selected_products.push(product);
                        return this.selected_products;
                    }
                } else {
                    /*const index = this.selected_products.indexOf(product);
                    this.selected_products.splice(index, 1);*/

                    const index = this.selected_products.findIndex(p => p.id == product.id);
                    this.selected_products.splice(index, 1);

                    console.log("removed, new array: ", this.selected_products)
                }
            },
            changeCategory() {
                if (this.sending_request) {
                    return;
                }
                if (!this.selected_assign_category.id) {
                    notify('top', 'Error', 'Please select assign category', 'center', 'danger');
                    return;
                }
                this.sending_request = true;
                let product_ids = [];
                this.selected_products.map(product =>
                    product_ids.push(product.id)
                );
                axios.put('/web/products/bulk/category/update', {
                    'products': product_ids,
                    'category_id': this.selected_assign_category.id,
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully assigned all selected products to category.', 'center', 'success');
                    }
                    this.sending_request = false;
                }).catch((error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            }
        }
    }
</script>

<style scoped>

</style>
