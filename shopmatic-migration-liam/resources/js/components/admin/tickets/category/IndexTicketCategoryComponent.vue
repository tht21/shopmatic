<template>
    <div class="card">
        <div class="card-header border-0 pb-2">
            <h3 class="mb-0">{{ title }}
                <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button>
            </h3>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group form-inline">
                        Show &nbsp;
                        <select @change="doPerPage" v-model="perPage" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        &nbsp; Items
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="input-group input-group-alternative mb-4">
                            <input type="text" class="form-control form-control-sm" placeholder="Search and enter..." v-model="filterText" @keyup.enter="doFilter">
                            <div class="input-group-append" @click="resetFilter">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th :class="'sort ' + (index === 0 ? 'desc' : '')" v-for="(header, index) in headers" :data-sort="fields[index]">{{ header }}</th>
                </tr>
                </thead>
                <tbody class="list">
                <tr v-for="item in data">
                    <td>{{ item['name'] }}</td>
                    <td v-if="item['parent']">{{ item['parent'].name }}</td>
                    <td v-else></td>
                    <td>
                        <span v-if="!(item['status'])" class="badge badge-success">Active</span>
                        <span v-else class="badge badge-danger">Inactive</span>
                    </td>
                    <td>{{ item['created_at'] }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn-icon-only" data-toggle="modal" :data-target="'#update-category-form'+item.id"><i class="fas fa-edit"></i></button>
                    </td>
                    <update-ticket-category-component :request_url="request_url" :data.sync="item" :key="item.id" v-on:refresh-page="retrieve" ></update-ticket-category-component>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer py-4">
            <vue-pagination :pagination="pagination" v-on:change-page="changePage"></vue-pagination>
        </div>
    </div>
</template>
<script>
    import VuePagination from "../../../VuePagination";
    import CreateTicketCategoryComponent from "./CreateTicketCategoryComponent";

    export default {
        name: "IndexTicketCategoryComponent",
        components: {
            VuePagination,
            CreateTicketCategoryComponent
        },
        props: [
            'title', 'request_url', 'headers', 'fields'
        ],
        data() {
            return {
                data: {},
                pagination: [],
                list: null,
                appendParams: {},
                showModal: false,
                active: 0,
                filterText: '',
                perPage: 10,
            }
        },
        methods: {
            retrieve: function() {
                let ctx = this;
                ctx.data = [];
                axios.get(this.request_url, {
                    params: this.appendParams
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.data = data.response.items;
                        ctx.pagination = data.response.pagination;

                        ctx.updateList();
                    }
                }).catch(function (error) {
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
                            valueNames: this.fields,
                        };
                        this.list = new List('index-table', options);
                    } else {
                        this.list.reIndex();
                    }
                }
            },
            changePage(page) {
                this.appendParams = {
                    page : page
                };
                this.retrieve();
            },
            doFilter () {
                this.appendParams = {
                    'filter': this.filterText.trim()
                };
                this.retrieve();
            },
            resetFilter () {
                this.filterText = '';

                this.appendParams = {
                    filter : this.filterText
                };
                Vue.nextTick( () => this.$refs.vuetable.refresh());
            },
            doPerPage ()
            {
                this.appendParams = {
                    'limit': this.perPage
                };
                Vue.nextTick( () => this.$refs.vuetable.refresh());
            },
            statusLabel (value) {
                return value === 0
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>';
            },
        },
        mounted() {
            this.retrieve();
        },
    }
</script>
