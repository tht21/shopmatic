<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">Alerts <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button> <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                <button type="button" class="btn btn-sm btn-primary float-right" @click="dismissAll()">Mark all as read</button></h3>
        </div>
        <div id="filter" class="collapse">
            <div class="p-3" style="background: #f6f6f6;">
                <div class="row">
                    <div class="col-md-6 mt-2">
                        <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                        <input id="search" v-model="search" name="search" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="type" class="text-muted text-uppercase">TYPE</label>
                        <select id="type" v-model="type" name="type" class="form-control">
                            <option value="">All</option>
                            <option value="0">Info</option>
                            <option value="1">Warning</option>
                            <option value="2">Error</option>
                        </select>
                    </div>
                    <div class="col-12 text-center py-3">
                        <button class="btn btn-primary px-5" @click="retrieve">Filter</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Light table -->
        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th>Message</th>
                    <th>Product</th>
                    <th>When</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="item in data">
                    <td><span v-show="!item.dismissed_at" class="dot dot-sm bg-primary mr-3"></span><i :class="'mr-3 fa ' + item.icon"></i>{{ item.message }}</td>
                    <td @click="clickProduct(item.product)" class="cursor-pointer">
                        <template v-if="item.product">
                            {{ item.product.name }}<br /><small>Associated SKU: {{ item.product.associated_sku }}</small>
                        </template>
                    </td>
                    <td>{{ item.created_at }}</td>
                    <td @click="dismissAlert(item)" v-show="!item.dismissed_at"><i class="fa fa-check text-success cursor-pointer"></i></td>
                </tr>
                </tbody>
            </table>

            <h3 v-if="data.length === 0 && !retrieving" class="text-muted text-center font-weight-light py-3">There is nothing that matches your criteria!</h3>
        </div>
        <!-- Card footer -->
        <div v-show="!retrieving" class="card-footer py-4">
            <nav v-show="pagination.last_page > 1">
                <ul class="pagination justify-content-center mb-0">
                    <li v-show="pagination.current_page > 2" class="page-item">
                        <a class="page-link" @click="changePage(1)" tabindex="-1">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li v-show="pagination.current_page > 1" class="page-item">
                        <a class="page-link" @click="changePage(pagination.current_page - 1)" tabindex="-1">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    <li v-show="pagination.current_page > 1" class="page-item">
                        <a class="page-link" @click="changePage(pagination.current_page - 1)">{{ pagination.current_page - 1 }}</a>
                    </li>
                    <li class="page-item active">
                        <a class="page-link" href="#!">{{ pagination.current_page }}</a>
                    </li>
                    <li v-show="pagination.current_page + 1 <= pagination.last_page" class="page-item">
                        <a class="page-link" @click="changePage(pagination.current_page + 1)">{{ pagination.current_page + 1 }}</a>
                    </li>
                    <li v-show="pagination.current_page < pagination.last_page" class="page-item">
                        <a class="page-link" @click="changePage(pagination.current_page + 1)">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li v-show="pagination.current_page + 1 < pagination.last_page" class="page-item">
                        <a class="page-link" @click="changePage(pagination.last_page)">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="text-center mt-2">
                <small class="text-uppercase">{{ pagination.total }} alerts total. Last Page: {{ pagination.last_page }}</small>
            </div>
            <div class="float-right ml-auto">
                Jump To &nbsp;<input type="number" v-model="jump_to" placeholder="Page" :min="1" :max="pagination.last_page" class="form-control d-inline-block" @change="changePage(jump_to)" style="width: 100px" />
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: "ProductIndexComponent",
        props: ['product_id'],
        data() {
            return {
                data: [],
                search: '',
                type: '',
                request_url: '/web/products/alerts',
                retrieving: false,
                jump_to: '',
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 50,
                },
                list: null,
            }
        },
        methods: {
            filter: function() {
                this.retrieve();
            },
            changePage: function(page) {
                if (page < 1) {
                    page = 1;
                } else if (page > this.pagination.last_page) {
                    page = this.pagination.last_page;
                }
                this.pagination.current_page = page;
                this.retrieve();
            },
            clickProduct: function(product) {

                if (product) 
                    window.open('/dashboard/products/' + product.slug, '_blank');
            },
            dismissAlert: function(item) {
                //This is just to hide it from the frontend
                item.dismissed_at = 1;

                axios.post('/web/products/alerts/' + item.id + '/dismiss').then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        item.dismissed_at = 0;
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    }
                    console.log(data);
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            dismissAll: function() {
                let ctx = this;
                //This is just to hide it from the frontend
                ctx.data.forEach(function(item) {
                   item.dismissed_at = 1;
                });

                axios.post('/web/products/alerts/dismiss').then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        ctx.data.forEach(function(item) {
                            item.dismissed_at = 0;
                        });
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', 'Successfully dismissed all alerts.', 'center', 'success');
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            retrieve: function() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                let ctx = this;
                ctx.data = [];
                let parameters = {
                    search: this.search,
                    type: this.type,
                    page: this.pagination.current_page,
                    with: 'product',
                    product_id: this.product_id
                };
                axios.get(this.request_url, {
                    params: parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.pagination = data.response.pagination;
                        ctx.data = data.response.items;
                    }
                    ctx.retrieving = false;
                }).catch(function (error) {
                    ctx.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            updateList: function() {
                if (this.data.length) {
                    if (!this.list) {
                        let options = {
                            valueNames: ['id', 'name', 'total_sold'],
                        };
                        this.list = new List('index-table', options);
                    } else {
                        this.list.reIndex();
                    }
                }
            },
            importSettings: function() {
                $('#modal-notification').modal('hide');
                $('#modal-import').modal('show');
            },
            selectAccount: function(account) {
                this.selected_account = account;
                $('#modal-notification').modal('show');
            },
        },
        created() {
            this.retrieve();
        },
        updated() {
            $('[data-toggle="tooltip"]').tooltip();
            this.updateList();
        }
    }
</script>
