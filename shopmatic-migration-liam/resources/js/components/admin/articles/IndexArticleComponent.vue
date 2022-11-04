<template>
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="mb-0"> {{ title }}
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

        <div class="card-body m-0">
            <div class="table-responsive">
                <div>
                    <table class="table align-items-center">
                        <thead class="thead-light">
                        <tr>
                            <th scope="col" class="border-0">Article</th>
                            <th scope="col" class="border-0">Created At</th>
                            <th scope="col" class="border-0">Created By</th>
                            <th scope="col" class="border-0"></th>
                        </tr>
                        </thead>
                        <tbody class="list">
                            <tr class="border-bottom" v-for="article in articles">
                                <td class="border-0" style="max-width: 400px">
                                    <h4>{{ article.title }}</h4>
                                    <div class="col pl-0">
                                        <span class="badge badge-default"> {{ article.category.name }} </span>

                                    </div>
                                    <div class="col pl-0">
                                        <span class="badge bg-green text-white mr-1" v-for="tag in article.tags"> # {{ tag.name }} </span>
                                    </div>
                                </td>
                                <td class="border-0"> {{ article.created_at | formatDate }} </td>
                                <td class="completion border-0"> {{ article.user.name }} </td>
                                <td class="text-right border-0">
                                    <div class="dropdown">
                                        <a class="btn btn-sm btn-icon-only text-light" @click="editArticle(article)" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer py-4">
            <vue-pagination :pagination="pagination" v-on:change-page="changePage"></vue-pagination>
        </div>
    </div>
</template>

<script>

    import VuePagination from '../../VuePagination';

    export default {
        name: "IndexArticleComponent",
        components : {
            VuePagination
        },
        props: {
            request_url: {
                type: String,
                required: true
            },
            index_url: {
                type: String,
                required: true
            },
            title: {
                type: String
            },

        },
        filters: {
            formatDate: function (date) {
                return moment(date).format('Do MMMM YYYY, h:mm:ss a');
            },
            formatDay: function (date) {
                return moment(date).startOf('day').fromNow();
            },
        },
        data() {
            return {
                articles: {},
                pagination: {},
                filterText: '',
                perPage: 10,
                appendParams: {}
            }
        },
        methods: {
            retrieve: function()
            {
                axios({method:'GET', url: this.request_url, params: this.appendParams }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.articles = data.response.items;
                        this.pagination = data.response.pagination;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            doFilter ()
            {
                this.appendParams = {
                    'filter': this.filterText
                };

                this.retrieve();
            },
            resetFilter ()
            {
                this.filterText = '';
                this.appendParams = {
                    'filter': this.filterText
                };
                this.retrieve();
            },
            doPerPage ()
            {
                this.appendParams = {
                    'limit': this.perPage
                };
                this.retrieve();
            },
            changePage(page)
            {
                this.appendParams = {
                    page : page
                };
                this.retrieve();
            },
            editArticle: function(article)
            {
                window.location.href = this.index_url + "/" + article.id + "/edit";
            }
        },
        created() {
            this.retrieve();
        }
    }
</script>
