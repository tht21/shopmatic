<template>
    <div class="modal fade show" :id="'update-category-form'+data.id"  tabindex="-1" role="dialog" aria-labelledby="create-user-form" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="card bg-secondary border-0 mb-0">
                        <div class="card-header bg-info">
                            <h3 class="mb-0 text-white">Edit Category</h3>
                        </div>
                        <div class="card-body px-lg-5 py-lg-5">
                            <form :id="'update-form'+data.id" role="form" @submit.prevent="update(data)" method="POST">
                                <input name="_method" type="hidden" value="PUT">
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-th"></i></span>
                                        </div>
                                        <input name="name" class="form-control" placeholder="Name" type="text" v-model="data.name"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="button" v-if="show === 0" class="btn btn-sm btn-success" @click="showAction(true)">Active</button>
                                    <button type="button" v-else class="btn btn-sm btn-outline-success" @click="showAction(false)">Inactive</button>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-info mb-2 mt-4">Update Category</button>
                                    <button type="button" class="btn btn-danger mb-2 mt-4" @click="destroy(data)">Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    const Validator = require('Validator');

    export default {
        name: "UpdateArticleCategoryComponent",
        props: [
            'request_url', 'data'
        ],
        data() {
            return {
                show: this.data.status,
                categories: {},
                status: 0,
                selectedCategory: this.data.parent_id
            }
        },
        methods: {
            update: function(category) {
                let ctx = this;

                let formData = new FormData($('#update-form'+category.id)[0]);
                formData.append('status', ctx.status);

                axios({ method: "POST", url: ctx.request_url + "/" + category.id, data: formData }).then(function (response) {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        $("#update-category-form" + category.id).modal('hide');

                        ctx.status = 0;
                        ctx.$emit('refresh-page');

                        swal({
                            title: 'Success',
                            text: 'You have successfully updated a ticket category.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        });
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            destroy: function(category) {
                let ctx = this;

                $("#update-category-form"+category.id).modal('hide');
                $("#update-category-form"+category.id).trigger('reset');

                swal({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.value) {
                        axios({ method: "delete", url: ctx.request_url + "/" + category.id }).then(function (response) {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                swal(
                                    'Deleted!',
                                    'Your data has been deleted.',
                                    'success'
                                );
                                ctx.$emit('refresh-page');
                            }
                        }).catch(function (error) {
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                        });
                    }
                });
            },
            showAction: function(button){
                if(button) {
                    this.show = 1;
                    this.status = 1;
                } else {
                    this.show = 0;
                    this.status = 0;
                }
            },
        },
    }
</script>
