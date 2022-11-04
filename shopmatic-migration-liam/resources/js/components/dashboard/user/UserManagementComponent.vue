<template>
    <div>
        <div class="row justify-content-center">
            <div :class="['card col-sm-12', is_modal ? 'col-md-12' : 'col-md-8']">
                <!-- Card header -->
                <div class="card-header" v-if="!is_modal">
                    <!-- Title -->
                    <div class="row align-items-center">
                        <div class="col-8">
                            <!-- Title -->
                            <h5 class="h3 mb-0">User Management</h5>
                        </div>
                    </div>
                </div>
                <!-- Card body -->
                <div class="card-body">

                    <b-list-group v-if="users.length > 0" class="list-group-flush list my--3">
                        <b-list-group-item v-for="(user, index) in users" :key="index">
                            <b-row>
                                <b-col cols="10">
                                    <h4>
                                        {{ user.name }}
                                    </h4>
                                    <small>
                                        [{{ user.email }}]
                                    </small>
                                </b-col>
                                <b-col cols="2">
                                    <button type="button" v-if="user.id === auth_user.id" class="btn btn-sm btn-primary" @click="edit(user.id)">
                                        Setting
                                    </button>
                                    <button type="button" v-if="user.id !== auth_user.id" class="btn btn-sm btn-danger"
                                            @click="remove(user.id)">
                                        Remove
                                    </button>
                                </b-col>
                            </b-row>
                        </b-list-group-item>
                    </b-list-group>

                    <h3 v-else class="card-title mb-3">There's no user under shop currently</h3>

                    <div class="card-footer py-4" v-if="pagination != null && users.length > 0">
                        <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script>
    import PaginationComponent from "../components/PaginationComponent";
    import CreateUserManagementComponent from "./CreateUserManagementComponent";

    export default {
        name: "UserManagementComponent",
        components: {CreateUserManagementComponent, PaginationComponent},
        props: ['auth_user', 'is_modal'],
        data() {
            return {
                sending_request: false,
                users: [],
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 0,
                },
                limit: 10,
                selected_user: null,
            }
        },

        mounted() {
            this.retrieve();
        },

        methods: {
            retrieve() {
                axios.get('/web/shop/users', {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.limit,
                    }
                }).then((response) => {
                    response = response.data;
                    if (response.meta.error) {
                        notify('top', 'Error', response.meta.message, 'center', 'danger');
                    } else {
                        this.users = response.response.items;
                        this.pagination = response.response.pagination;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            remove(id) {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                swal({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    type: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                        notify('top', 'Info', 'Removing user..', 'center', 'info');

                        axios.post('/web/shops/dismiss/' + id + '', {}).then((response) => {
                            response = response.data;
                            this.sending_request = false;
                            if (response.meta.error) {
                                notify('top', 'Error', response.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Success', 'Remove successfully', 'center', 'success');
                                this.retrieve();
                            }
                        }).catch((error) => {
                            this.sending_request = false;
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                        });
                    } else {
                        this.sending_request = false;
                    }
                })
            },
            edit(id) {
                window.location = '/dashboard/shop/users/'+id;
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            }
        }
    }
</script>

<style scoped>

</style>
