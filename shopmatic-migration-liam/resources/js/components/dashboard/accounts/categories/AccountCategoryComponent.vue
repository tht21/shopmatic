<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">Account Categories</h3>
        </div>

        <!-- Accounts Body -->
        <template v-if="data.length > 0">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6" v-for="account in data">
                        <div class="card card-stats" style="cursor:pointer" @click="retrieve(account, true)" :class="{ 'bg-primary': card_active_state === account.id, 'bg-light': account.status !== 0 }" v-on:click="card_active_state = account.id">
                            <!-- Card body -->
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title h6 text-uppercase text-muted mb-0" :class="{ 'text-white': card_active_state === account.id }">Account Name</h5>
                                        <span class="h3 font-weight-bold mb-0" :class="{ 'text-white': card_active_state === account.id }">{{ account.name }}</span>
                                    </div>
                                    <div class="col-auto">
                                        <img :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'" class="account-integration-logo" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Light table -->
            <div id="index-table" class="table-responsive">
                <table class="table align-items-center table-flush">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 130px;" class="sort desc" data-sort="id">ID</th>
                        <th class="sort" data-sort="name">Name</th>
                        <th>Parent</th>
                        <th>Leaf</th>
                        <th>Mapped</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody class="list">
                    <tr v-for="item in categoryData" class="cursor-pointer">
                        <td style="width: 130px;" class="id">{{ item.id }}</td>
                        <td class="name">{{ item.name }}</td>
                        <td>{{ item.parent ? item.parent.name : '-' }}</td>
                        <td>{{ item.is_leaf ? 'Yes' : 'No' }}</td>
                        <td>{{ item.breadcrumb }}</td>
                        <td>
                            <a class="btn btn-primary btn-icon text-white" :href="'/dashboard/account/categories/' + item.id + '/edit'">
                                <span class="btn-inner--icon"><i class="fas fa-pencil-alt"></i></span>
                                <span class="nav-link-inner--text">Edit</span>
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <h3 v-if="categoryData.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
            </div>

            <!-- Card footer -->
            <div class="card-footer py-4" v-if="!retrieving">
                <pagination-component :details="pagination" @paginated="paginate"></pagination-component>
            </div>
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
        name: "AccountCategoryComponent",
        props: [
            'request_url'
        ],
        data() {
            return {
                data: [],
                categoryData: [],
                selected_account: [],
                retrieving: false,
                jump_to: '',
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 50,
                },
                card_active_state: null
            }
        },
        methods: {
            isShown(i) {
                return this.show[i]
            },
            retrieveAccounts() {
                this.data = [];
                axios.get(this.request_url, {
                    params: this.parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.data = data.response.items;
                        this.selected_account = this.data.length ? this.data[0] : null;
                        if (this.selected_account) {
                            this.card_active_state = this.selected_account.id;

                            this.retrieve(this.selected_account);
                        }
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieve(selected_account, refreshPagination = false) {
                if (this.retrieving || !selected_account || selected_account.status !== 0) { // Make sure is not retrieving category and no empty account_id
                    return;
                }
                // update global
                this.$emit('update', selected_account)

                if (refreshPagination) { // To reset pagination when click on a new account
                    this.pagination = {
                        current_page: 1,
                        from: 1,
                        last_page: 1,
                        to: 10,
                        total: 50,
                    }
                }
                this.retrieving = true;
                this.card_active_state = selected_account.id;
                this.selected_account = selected_account;
                this.categoryData = [];
                let parameters = {
                    page: this.pagination.current_page
                };
                axios.get('/web/accounts/'+ selected_account.id +'/categories', {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.pagination = data.response.pagination;
                        this.categoryData = data.response.items;
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
            updateList() {
                if (this.data.length) {
                    if (!this.list) {
                        let options = {
                            valueNames: ['id', 'name'],
                        };
                        this.list = new List('index-table', options);
                    } else {
                        this.list.reIndex();
                    }
                }
            },
            paginate(value) {
                this.pagination = value;
                this.retrieve();
            }
        },
        created() {
            this.retrieveAccounts();
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
            this.updateList();
        }
    }
</script>

<style scoped>

</style>
