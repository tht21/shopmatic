<template>
    <div class="card">
        <div class="card-header border-0">
            <h3 class="mb-0">{{ title }} <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button></h3>
            <hr class="mt-3 mb-3">
            <div class="row">
                <div class="col-md-4 offset-8">
                    <div class="form-group mb-0">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm mb-0" placeholder="Search and enter..." v-model="filterText" @keyup.enter="doFilter">
                            <div class="input-group-append" @click="resetFilter">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body m-0">
            <ul class="list-group list-group-flush" ref="vuetable">
                <ul class="list-group">
                    <li v-for="field in fields" class="list-group-item">
                        <a :href="'/dashboard/tickets/' + field.case_id ">
                            <div class="row">
                                <div class="col">
                                    <h4 class="card-title text-default mb-0">{{ field.subject }}</h4>
                                    <div class="row">
                                        <div class="col-auto">
                                            <span class="badge badge-pill border text-gray" v-if="field.user">
                                                <i class="fas fa-user-circle"></i> &nbsp; {{ field.user.name }}
                                            </span>

                                            <span class="badge badge-pill border text-gray">
                                                <i class="fas fa-calendar-alt"></i> &nbsp; {{ field.created_at | formatDate }}
                                            </span>

                                            <span class="badge badge-pill border text-gray">
                                                <i class="fas fa-clock"></i> &nbsp; {{ field.created_at | formatDay }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="h5 text-gray mt-2 mb-0">{{ field.description }}</div>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge badge-pill bg-success text-white">OPEN</span>
                                </div>
                                <div class="col-auto border-left">
                                    <a class="text-light float-right btn-sm">
                                        <i class="fas fa-comment-dots fa-2x"></i>
                                    </a>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="badge badge-pill bg-info text-white" v-if="field.category"><i class="fas fa-th-list"></i> {{ field.category.name }}</span>
                            </p>
                        </a>
                    </li>
                </ul>
            </ul>
        </div>
        <div class="card-footer py-4">
            <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
        </div>
    </div>
</template>

<script>

    import VuePagination from "../../VuePagination";

    export default {
        name: "UserIndexTicketComponent",
        components : {
          VuePagination
        },
        props: {
            request_url: {
                type: String,
                required: true
            },
            autoRefresh: {
                type: Boolean
            },
            keys: {
                type: Array
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
                fields: {},
                filterText: '',
                perPage: 10,
                appendParams: {},
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                limit: 10,
            }
        },
        methods: {
            retrieve: function() {
                axios({method:'GET', url: this.request_url, params: this.appendParams }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.fields = data.data;
                        this.pagination = data.links.pagination;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            doFilter() {
                this.appendParams = {
                    'filter': this.filterText.trim()
                };
                this.retrieve();
            },
            resetFilter () {
                this.appendParams = {
                    'filter': ''
                };
                this.retrieve();
            },
            paginate(value, limit) {
                this.appendParams = {
                    page: value.current_page,
                    limit: limit,
                };
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            }
        },
        created() {
            this.retrieve();
        }
    }
</script>
