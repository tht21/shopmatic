<template>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <button type="button" class="btn btn-secondary btn-block" @click="clearAllFilter">
                    <span class="float-left"><i class="fas fa-inbox"></i> &nbsp; Inbox</span>
                    <span class="badge badge-info float-right mr-2"> {{ count_ticket }} </span>
                </button>
                <div>
                    <ul class="list-group mt-2">
                        <li v-for="group in group_ticket" class="list-group-item border-0">
                            <a href="#" @click="filterGroup(group.ticket_categories_id)">
                                <h4 class="card-title text-default mb-0" v-if="group.category">{{ group.category.name }}
                                    <span class="badge badge-info mr-2">&nbsp; {{ group.total }}</span>
                                </h4>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-8">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <div class="input-group mb-4">
                                <input type="text" class="form-control form-control-sm mb-0" placeholder="Search and enter..." v-model="filterText" @keyup.enter="doFilter">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <button class="btn btn-sm btn-info float-right m-0" @click="clearAllFilter"><i class="fa fa-sync-alt"></i></button>
                    <strong> &nbsp; Inbox </strong>
                </div>

                <ul class="list-group">
                    <li v-for="ticket in tickets" class="list-group-item" >
                        <div class="row">
                            <div class="col">
                                <h4 class="card-title text-default mb-0">{{ ticket.subject }}</h4>
                                <div class="row">
                                    <div class="col-auto">
                                <span class="badge badge-pill border text-gray" v-if="ticket.user">
                                    <i class="fas fa-user-circle"></i> &nbsp; {{ ticket.user.name }}
                                </span>

                                        <span class="badge badge-pill border text-gray">
                                    <i class="fas fa-calendar-alt"></i> &nbsp; {{ ticket.created_at | formatDate }}
                                </span>

                                        <span class="badge badge-pill border text-gray">
                                    <i class="fas fa-clock"></i> &nbsp; {{ ticket.created_at | formatDay }}
                                </span>
                                    </div>
                                </div>
                                <div class="h5 text-gray mt-2 mb-0">{{ ticket.description }}</div>
                            </div>
                            <div class="col-auto">
                                <span class="badge badge-pill bg-gray text-white float-right" v-if="ticket.category"><i class="fas fa-th-list"></i> {{ ticket.category.name }}</span><br>
                                <span :class="['float-right badge badge-pill text-white border-0 shadow-0',
                                    {'bg-green': (ticket.status === 0)},
                                    {'bg-orange': (ticket.status === 1)},
                                    {'bg-green': (ticket.status === 2)},
                                    {'bg-orange': (ticket.status === 3)},
                                    {'bg-dark': (ticket.status === 4)}]"
                                >
                                    <span v-for="(status, index) in status_array" v-if="ticket.status === index"> {{ status }} </span>
                                </span>
                            </div>
                            <div class="col-auto border-left">
                                <a href="#" @click="showMessage(ticket)" class="text-light float-right btn-sm">
                                    <i class="fas fa-comment-dots fa-2x"></i>
                                </a>
                            </div>
                        </div>

                        <div class="mt-3 mb-0 text-muted text-sm">
                            <span class="btn-group mb-2">
                                <button type="button" class="badge badge-pill bg-gray text-white border-0 shadow-0 dropdown-toggle mr-2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" @click="getStaffList">
                                    Assign To
                                </button>

                                <a href="#" v-if="assign_user.user" class="badge badge-secondary border mr-1" v-for="assign_user in ticket.assign_users" @click="updateTicket([ticket, assign_user], 'remove_assign')">{{ assign_user.user.name }} &nbsp; <i class="fas fa-times"></i> </a>

                                <div class="dropdown-menu">
                                    <button v-for="staff in staffs" class="dropdown-item" type="button" @click="updateTicket([ticket, staff], 'assign_user')" v-if="staff">{{ staff.name }}</button>
                                </div>
                            </span>
                            <br>
                            <span class="btn-group">
                                <button type="button" class="badge badge-pill bg-default text-white border-0 shadow-0 dropdown-toggle mr-2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Set Priority
                                </button>

                                <span :class="['badge text-white',
                                    {'bg-default': (ticket.priority === 0)},
                                    {'bg-green': (ticket.priority === 1)},
                                    {'bg-orange': (ticket.priority === 2)},
                                    {'bg-orange': (ticket.priority === 3)},
                                    {'bg-red': (ticket.priority === 4)}]"
                                >
                                    <span v-for="(priority, index) in priority_array" v-if="ticket.priority === index">{{ priority }}</span>
                                </span>

                                <div class="dropdown-menu">
                                    <button v-for="(priority, index) in priority_array" class="dropdown-item" type="button" @click="updateTicket([ticket, index], 'priority')">{{ priority }}</button>
                                </div>
                            </span>
                        </div>
                    </li>
                </ul>

                <div class="card-footer py-4">
                    <vue-pagination :pagination="pagination" v-on:change-page="changePage"></vue-pagination>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import VuePagination from "../../VuePagination";

    export default {
        name: 'AdminIndexTicketComponent',
        components: {
            VuePagination
        },
        filters: {
            capitalize: function (value) {
                if (!value) return '';
                value = value.toString();
                return value.charAt(0).toUpperCase() + value.slice(1);
            },
            formatDate: function (date) {
                return moment(date).format('Do MMMM YYYY, h:mm:ss a');
            },
            formatDay: function (date) {
                return moment(date).startOf('day').fromNow();
            },
        },
        props: [
            'request_url',
            'priority_array',
            'status_array',
            'count_ticket',
            'group_ticket'
        ],
        data() {
            return {
                tickets: {},
                filterText: '',
                appendParams: {
                    filter: '',
                },
                staffs: {},
                assignUsers: {},
                pagination: {}
            }
        },
        methods: {
            retrieve: function() {

                let self = this;

                axios({method:'GET', url: self.request_url, params: self.appendParams }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        self.tickets = data.data;
                        self.pagination = data.links.pagination;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            updateTicket: function (arr, flag) {
                let sendData = {};

                if (flag === 'assign_user') {
                    sendData = { assign_user: arr[1] };
                }
                else if (flag === 'remove_assign') {
                    sendData = { remove_assign: arr[1] };
                }
                else if (flag === 'priority') {
                    sendData = { priority: arr[1] };
                }

                axios({method:'PUT', url: this.request_url + "/" + arr[0].case_id, data: sendData }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                        this.retrieve();
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            doFilter () {
                this.appendParams.filter = this.filterText.trim();

                this.retrieve();
            },
            showMessage: function(ticket) {
                window.location.href = "/admin/tickets/" + ticket.case_id;
            },
            changePage(page) {
                this.appendParams = {
                    page : page
                };

                this.retrieve();
            },
            filterGroup: function (id) {
                this.appendParams = {
                    ticket_categories_id : id
                };
                this.retrieve();
            },
            clearAllFilter () {
                this.appendParams = { };
                this.retrieve();
            },
            getStaffList: function()
            {
                axios({method:'GET', url: "/web/users" }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.staffs = data.response.items;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
        },
        created() {
            this.retrieve();
        },
    }
</script>
