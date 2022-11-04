<template>
    <div class="modal fade show" id="create-category-form" tabindex="-1" role="dialog" aria-labelledby="create-user-form" aria-modal="true" @focus="getCategories">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="card bg-secondary border-0 mb-0">
                        <div class="card-header bg-info">
                            <h3 class="mb-0 text-white">{{ title }}</h3>
                        </div>
                        <div class="card-body px-lg-5 py-lg-5">
                            <form id="create-form" role="form" @submit.prevent="create">
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-th"></i></span>
                                        </div>
                                        <input name="name" class="form-control" placeholder="Name" type="text" />
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-th-large"></i></span>
                                        </div>
                                        <select name="parent_id" class="form-control">
                                            <option v-for="category in categories" :value="category.id">{{ category.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="button" v-if="showActive" class="btn btn-sm btn-success" @click="showAction(true)">Active</button>
                                    <button type="button" v-if="showInactive" class="btn btn-sm btn-outline-success" @click="showAction(false)">Inactive</button>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-info mb-2 mt-4">Create Category</button>
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
        name: "CreateTicketCategoryComponent",
        props: [
            'request_url', 'title'
        ],
        data() {
            return {
                showActive: true,
                showInactive: false,
                categories: {},
                status: 0
            }
        },
        methods: {
            create: function() {
                let ctx = this;

                let formData = new FormData($('#create-form')[0]);
                formData.append('status', ctx.status);

                axios({ method: "POST", url: ctx.request_url, data: formData }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        $('#create-category-form').modal('hide');
                        $("#create-category-form").trigger('reset');
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
                    ctx.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            showAction: function(button){
                if(button)
                {
                    this.showActive = false;
                    this.showInactive = true;
                    this.status = 1;
                }
                else
                {
                    this.showInactive = false;
                    this.showActive = true;
                    this.status = 0;
                }
            },
            getCategories: function(){
                axios({ method: "GET", url: this.request_url}).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.categories = data.response.items;
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
    }
</script>
