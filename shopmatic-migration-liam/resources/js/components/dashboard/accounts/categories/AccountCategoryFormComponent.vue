<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">{{ is_edit ? 'Edit' : 'Create' }} Account Categories</h3>
        </div>

        <template v-if="accounts.length > 0">
            <form id="account-category-form" @submit.prevent="submitAccountCategory()">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-control-label">Accounts</label>
                        <!-- @TODO - just show the account, and dont need switching -->
                        <select disabled="" class="form-control" name="account_id" v-model='account_category_form.account_id' v-on:change="retrieveAccountCategories(account_category_form.account_id); retrieveCategories(account_category_form.account_id);">
                            <option v-for="account in accounts" :value='account.id'>{{ account.name }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">Parent</label>
                        <select class="form-control" name="parent_id" v-model='account_category_form.parent_category'>
                            <option
                                v-for="parent_category in parent_categories"
                                :value='{ id: parent_category.id, breadcrumb: parent_category.breadcrumb }'
                            >{{ parent_category.breadcrumb }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">Label</label>
                        <input type="text" class="form-control" name="name" placeholder="Name" v-model="account_category_form.name">
                    </div>
                    <div class="custom-control custom-checkbox mb-3">
                        <input class="custom-control-input" name="is_leaf" type="checkbox" id="isLeaf" :checked="account_category_form.is_leaf" v-model="account_category_form.is_leaf">
                        <label class="custom-control-label" for="isLeaf">Is Leaf</label>
                    </div>
                </div>

                <!-- Card header -->
                <div class="card-header border-0">
                    <h3 class="mb-0">Mapped Category</h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <label class="form-control-label">Category</label>
                        <!-- @TODO - check this, should load a default list  -->
                        <async-multiselect
                            type="single_select_category"
                            :id="'category-input'"
                            :model.sync="category"
                        />
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary mt-4" :disabled="create_request === true">Save</button>
                </div>
            </form>
        </template>
        <template v-else>
            <div class="card-body">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                    <span class="alert-text">
                        <strong>There is no account support category.</strong>
                        <a href="/dashboard/accounts/create">Please click here to add account</a>
                    </span>
                </div>
            </div>
        </template>

    </div>
</template>

<script>
    export default {
        name: "AccountCategoryFormComponent",
        props: [
            'mode',
            'account_category'
        ],
        data() {
            return {
                accounts_request_url: '/web/accounts?feature=account_categories&limit=100',
                categories_request_url: '/web/categories?mode=all',
                accounts: [],
                parent_categories: [],
                categories: [],
                retrieving_categories: false,
                create_request: false,
                account_category_form: {
                    name: '',
                    account_id: null,
                    parent_category: null,
                    parent_id: null,
                    category_id: null,
                    is_leaf: 0,
                    breadcrumb: null,
                },
                is_edit: false,
                category: 0
            }
        },
        methods: {
            retrieveAccounts: function() {
                this.accounts = [];
                axios.get(this.accounts_request_url, {
                    params: this.parameters
                }).then((response)  => {
                    let data = response.data.response;
                    if (response.data.meta.error) {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    } else {
                        this.accounts = data.items;
                        if (this.accounts.length > 0) {
                            // If in edit mode then select the actual data
                            if (this.mode === 'edit' && this.account_category) {
                                this.account_category_form.account_id = this.account_category.account_id;
                            } else {
                                this.account_category_form.account_id = data.items[0].id;
                            }
                            this.retrieveAccountCategories(this.account_category_form.account_id);
                            //this.retrieveCategories(this.accountCategory.account_id);
                        }
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveAccountCategories: function (account) {
                if (this.retrieving_categories || !account) { // Make sure is not retrieving category and no empty account_id
                    return;
                }
                this.retrieving_categories = true;
                this.parent_categories = [];
                let parameters = {
                    limit: 200,
                    is_leaf: 0
                };
                axios.get('/web/accounts/'+ account +'/categories', {
                    params: parameters
                }).then((response) => {
                    let data = response.data.response;
                    if (response.data.meta.error) {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    } else {
                        this.parent_categories = data.items;
                        if (this.parent_categories.length > 0) {
                            // If in edit mode then select the actual data
                            if (this.mode === 'edit' && this.account_category) {
                                let selected_account_category = this.parent_categories.find(account_category => account_category.id === this.account_category.parent_id);
                                if (selected_account_category) {
                                    this.account_category_form.parent_category = {
                                        id: selected_account_category.id,
                                        breadcrumb: selected_account_category.breadcrumb
                                    };
                                }
                                // Remove current account category
                                this.parent_categories = this.parent_categories.filter((account_category) => {
                                    return account_category.id != this.account_category.id
                                });
                                this.account_category_form.parent_id = this.account_category.parent_id;
                            } else {
                                this.account_category_form.parent_category = {
                                    id: data.items[0].id,
                                    breadcrumb: data.items[0].breadcrumb
                                };
                                this.parent_categories.parent_id = data.items[0].id;
                            }
                        }
                    }
                    this.retrieving_categories = false;
                }).catch((error) => {
                    this.retrieving_categories = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieveCategories: function(account) {
                this.categories = [];
                let parameters = {
                    account_id: account
                };
                axios.get(this.categories_request_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data.response;
                    if (response.data.meta.error) {
                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
                    } else {
                        this.categories = data;
                        if (this.categories.length > 0) {
                            // If in edit mode then select the actual data
                            if (this.mode === 'edit' && this.account_category) {
                                this.account_category_form.category_id = this.account_category.category_id;
                            } else {
                                this.account_category_form.category_id = data[0].id;
                            }
                        }
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            submitAccountCategory: function() {
                if (this.create_request) {
                    return;
                }
                this.create_request = true;
                let $msg = (this.is_edit) ? 'Updating account category' : 'Creating account category';
                let $url = '/web/accounts/'+ this.account_category_form.account_id +'/categories';
                this.account_category_form.breadcrumb = (this.account_category_form.parent_category && this.account_category_form.parent_category.breadcrumb) ? this.account_category_form.parent_category.breadcrumb + ' > ' + this.account_category_form.name : this.account_category_form.name;
                this.account_category_form.parent_id = this.account_category_form.parent_category.id;
                this.account_category_form.category_id = this.category.id;

                notify('top', 'Info', $msg, 'center', 'info');
                if (this.is_edit) {
                    this.account_category_form['_method'] = "put";
                    $url = '/web/accounts/'+ this.account_category_form.account_id +'/categories/' + this.account_category.id;
                }
                axios.post($url, this.account_category_form).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                        this.create_request = false;
                    } else {
                        let action = (this.is_edit) ? 'updated' : 'added';
                        swal({
                            title: 'Success',
                            text: 'You have successfully '+action+' the account category!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                        this.create_request = false;
                        window.location.replace('/dashboard/account/categories');
                    }
                }).catch((error) => {
                    this.create_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
        },
        created() {
            this.retrieveAccounts();

            // If in edit mode then select the actual data
            if (this.mode === "edit" && this.account_category != null) {
                this.account_category_form = {
                    name: this.account_category.name,
                    account_id: this.account_category.account_id,
                    parent_id: this.account_category.parent_id,
                    category_id: this.account_category.category_id,
                    is_leaf: this.account_category.is_leaf,
                };

                this.category = this.account_category.category_id;
                this.is_edit = true;
            }

            if (this.category == null || typeof this.category == 'undefined') {
                // null will cause error
                this.category = 0
            }
        },
        updated() {
            if (this.category == null || typeof this.category == 'undefined') {
                // null will cause error
                this.category = 0
            }
        }
    }
</script>

<style scoped>

</style>
