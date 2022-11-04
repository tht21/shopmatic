<template>
    <div>
        <b-row class="mt-3">
            <b-col>
                <b-card no-body>
                    <b-card-header>
                        <h3 class="mb-0">{{ title }} 
                            <b-button size="sm" variant="info" class="ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></b-button>
                            <button class="btn btn-sm btn-primary" data-target="#filter" data-toggle="collapse"><i class="fa fa-filter"></i></button>
                        </h3>
                    </b-card-header>

                    <div id="filter" class="collapse">
                        <div class="p-3" style="background: #f6f6f6;">
                            <div class="row">
                                <div class="col-12 mt-2">
                                    <label for="search" class="text-muted text-uppercase ml-auto">SEARCH</label>
                                    <input id="search" v-model="search" name="search" class="form-control">
                                </div>
                                <div class="col-12 text-center py-3">
                                    <button class="btn btn-info px-5" @click="reset">Reset</button>
                                    <button class="btn btn-primary px-5" @click="filter">Filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--user table-->
                    <b-table :fields="fields" :items="data" striped show-empty selectable select-mode="single" @row-clicked="selectUser">
                        <template v-slot:cell(status)="data">
                            <span class="text-capitalize">{{ user_status[data.value] }}</span>
                        </template>
                        <template v-slot:cell(actions)="data">
                            <template v-if="user_status[data.item.status] == 'active'">
                                <b-button variant="danger" @click="editUserStatus(data.item, 10)">Ban</b-button>
                            </template>
                            <template v-else-if="user_status[data.item.status] == 'banned'">
                                <b-button variant="warning" @click="editUserStatus(data.item, 0)">Unban</b-button>
                            </template>
                            <template v-else>
                                {{ data.item.status }}
                            </template>
                        </template>
                    </b-table>

                    <b-card-footer v-if="!retrieving && data.length !== 0">
                        <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>

        <create-user-component request_url="/web/users" @created="retrieve"></create-user-component>
    </div>
</template>

<script>
    export default {
        name: "AdminUserIndexComponent",
        data() {
            return {
                title:'All Users',
                count:0,
                retrieving: false,
                data: [],
                limit: 10,
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 1,
                    total: 1,
                },
                list: null,
                request_url: '/web/users',
                search: '',
                fields: [
                    { key: 'id', label: 'Id', sortable: true },
                    { key: 'name', label: 'Name', sortable: true },
                    { key: 'email', label: 'Email', sortable: true },
                    { key: 'created_at', label: 'Created At', sortable: true },
                    { key: 'role', label: 'Role'},
                ],
                user_status: {
                    0: 'active',
                    10: 'banned'
                }
            }
        },
        methods: {
            retrieve: function() {
                if (this.retrieving) {
                    return
                }
                this.retrieving = true;

                let parameters = {
                    limit: this.limit,
                    search: this.search,
                    page: this.pagination.current_page,
                }

                axios.get(this.request_url, {
                    params: parameters
                }).then( (response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.data = data.response.items;
                        this.pagination = data.response.pagination;
                        //this.updateList();
                        this.retrieving = false;
                    }
                }).catch( (error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },

            reset() {
                this.search = '';
            },
            filter() {
                this.pagination.current_page = 1;
                this.retrieve();
            },

            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            },
            selectUser(user) {
                window.location.href = '/admin/user/' + user.id;
            },
/*            updateList: function() {
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
            },*/
            editUserStatus(user, status) {
                let parameters = {
                    status: status,
                }

                notify('top', 'Info', 'Submitting...', 'center', 'info');
                
                axios.post('/web/user/' + user.id + '/status', parameters).then( (response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                        this.retrieve();
                    }
                }).catch( (error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            }
        },
/*        updated() {
            this.updateList();
        },*/
        created() {
            this.retrieve();

/*            if (this.auto_refresh > 0) {
                this.interval = setInterval(() => {
                    this.retrieve();
                }, this.auto_refresh);
            }*/

        },

        filters: {
            formatCurrency: function(value)
            {
                if(!value) return '0';
                return value.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
            },
        },
    }
</script>
