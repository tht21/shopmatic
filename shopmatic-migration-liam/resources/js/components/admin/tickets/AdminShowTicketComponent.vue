<template>
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="nav-wrapper">
                        <div class="nav nav-tabs nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                            <a class="nav-item nav-link mb-sm-3 mb-md-0 text-left" v-for="(data, index) in datas" :class="{active: currentTab === index}" @click="currentTab = index" :key="index">
                                <img class="avatar-xs" src="/images/user.png">
                                <span style="height: 10px; width: 10px; background-color: limegreen; border-radius: 50%; display: inline-block; position: relative; top: 12px; right: 8px"></span>
                                &nbsp; {{ data.subject }}<button type="button" class="btn btn-sm btn-outline-secondary float-right" @click="removeTab(data, index)"><i class="fas fa-times"></i></button>
                            </a>
                            <span class="ml-2">
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#open-new-message" @click="getInbox"><i class="fas fa-plus"></i> &nbsp; New</button>
                </span>
                        </div>
                    </div>
                    <template v-for="(data, index) in datas" v-if="currentTab === index">
                        <admin-show-ticket-details-component :key="index" :data="data" :index_url="index_url" :status_array="status_array"></admin-show-ticket-details-component>
                    </template>
                </div>
                <div class="modal fade show" id="open-new-message" tabindex="-1" role="dialog" aria-labelledby="open-new-message" aria-modal="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-body p-0">
                                <div class="card bg-secondary border-0 mb-0">
                                    <div class="card-header bg-info">
                                        <h3 class="mb-0 text-white text-left">Open New Message</h3>
                                    </div>
                                    <div class="card-body px-md-5 py-md-5">
                                        <div class="form-group">
                                            <div class="input-group mb-4">
                                                <input type="text" class="form-control form-control-sm mb-0" placeholder="Search and enter..." v-model="filterText" @keyup.enter="doFilter">
                                            </div>
                                        </div>
                                        <table class="table table-hover" id="inbox-table">
                                            <thead>
                                            <th>Inbox</th>
                                            </thead>
                                            <tbody class="list">
                                            <tr v-for="ticket in inbox" @click="selectMessage(ticket)">
                                                <td style="word-wrap: break-word; min-width: 160px; max-width: 160px; white-space:normal;">
                                                    <div class="media align-items-center">
                                                        <a href="#" class="avatar rounded-circle mr-3">
                                                            <img alt="Image placeholder" src="/images/user.png">
                                                        </a>
                                                        <div class="media-body">
                                                            <div class="mb-0 text-sm"><h5>{{ ticket.subject }}</h5></div>
                                                            <span class="mb-0 text-sm">{{ ticket.description}}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer">
                                        <vue-pagination :pagination="pagination" v-on:change-page="changePage"></vue-pagination>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mt-2">
                <div class="card-body">
                    <div class="media">
                        <a class="mr-3" href="#">
                            <img class="avatar-sm" src="/images/user.png">
                        </a>
                        <div v-if="ticket.user" class="media-body">
                            <h5 class="m-0">{{ ticket.user.name }}</h5>
                            <small>{{ ticket.user.email }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-comment-dots text-blue"></i> &nbsp; {{ openConversation }} Open Conversation(s)
                </div>
                <div class="card-body">
                    <div class="border col-sm-12 mb-2" v-for="data in datas">
                        <div class="media">
                            <div class="media-body">
                                <h5 class="m-0">{{ data.user.name }}</h5>
                                <small> {{ data.subject }} </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import VuePagination from "../../VuePagination";
    import AdminShowTicketDetailsComponent from "./AdminShowTicketDetailsComponent";

    export default {
        name: 'AdminShowTicketComponent',
        components: {
            AdminShowTicketDetailsComponent,
            VuePagination
        },
        props: [
            'request_url', 'index_url', 'status_array'
        ],
        filters: {
            capitalize: function (value) {
                if (!value) return '';
                value = value.toString();
                return value.charAt(0).toUpperCase() + value.slice(1);
            }
        },
        data(){
            return{
                ticket: {},
                inbox: {},
                datas: [],
                show: false,
                openConversation : 1,
                currentTab: 0,
                appendParams: {},
                pagination: {},
                per_page: 5,
                filterText: ''
            }
        },
        methods: {
            retrieve: function() {
                axios({method:'GET', url: this.request_url, params: this.appendParams}).then((response) => {

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.ticket = data.response;
                        this.datas.push(data.response);
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            getInbox: function() {
                axios({method:'GET', url: "/web/tickets", params: {...{limit: this.per_page}, ...this.appendParams} }).then((response) => {

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.inbox = data.data;
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
            countConversation: function() {
                this.openConversation = this.datas.length;
            },
            selectMessage: function(ticket) {

                axios({method:'GET', url: this.index_url+ "/" +ticket.case_id}).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.datas.push(data.response);
                        this.countConversation();
                        this.currentTab = this.datas.length - 1;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });

                $('#open-new-message').modal('hide');
            },
            removeTab: function(ticket, index){
                let self = this;

                if (self.datas.length > 1) {
                    self.datas.pop(ticket);
                }
            },
            changePage(page) {
                this.appendParams = {
                    page : page
                };
                this.getInbox();
            },
            doFilter () {
                this.$events.fire('filter-set', this.filterText);
            },
            onFilterSet (filterText) {
                this.appendParams.filter = filterText.trim();
                this.getInbox();
            },
        },
        created: function() {
            this.retrieve();
        },
    }
</script>
