<template>
    <form ref="form" id="create-form" @submit.prevent="onSave">
        <div class="card card mx-md-auto col-md-10">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-body">
                            <h1 class="font-weight-light mb-3 text-center">{{ title }}</h1>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-books"></i></span>
                                    </div>
                                    <select name="ticket_categories_id" class="form-control" v-model="formData.ticket_categories_id">
                                        <option value="0">Select Category</option>
                                        <option v-for="category in categories" :value="category.id"> {{ category.name }} </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-single-copy-04"></i></span>
                                    </div>
                                    <input type="text" name="subject" v-model="formData.subject" class="form-control" placeholder="Subject">
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <textarea name="description" v-model="formData.description" class="form-control" placeholder="Tell Us More..."></textarea>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-cloud-upload-96"></i></span>
                                    </div>
                                    <div class="custom-file">
                                        <input type="file" ref="file" class="custom-file-input input-group-alternative" id="attachments" name="attachments[]" multiple="" @change="uploadFile">
                                        <label class="custom-file-label border-0" for="attachments">Choose file</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 border-left-md-1">
                        <div class="card-body">
                            <h1 class="font-weight-light mb-3 text-center">Ticket Related</h1>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-tag"></i></span>
                                    </div>
                                    <select name="related_type" class="form-control" v-model="formData.related_type" @change="getRelatedId()">
                                        <option value="0">Select Related Type</option>
                                        <option value="1"> Account </option>
                                        <option value="2"> Integration </option>
                                        <option value="3"> Order </option>
                                        <option value="4"> Product </option>
                                        <option value="5"> Inventory </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-bullet-list-67"></i></span>
                                    </div>
                                    <select name="related_id" class="form-control" v-model="formData.related_id">
                                        <option value="0">Select Related ID</option>
                                        <option v-for="id in related_id" :value="id.id"> {{ id.name }} </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 text-center pb-5">
                        <button type="submit" class="btn btn-info mb-2 mt-4 px-5">Create Ticket</button>
                    </div>
                </div>
            </div>
            <div class="card-header border-0">
                <h3 class="mb-0"></h3>
            </div>
        </div>
    </form>
</template>

<script>
    import DataFormMixin from 'vue-dataform-mixin'

    export default {
        name: 'AdminCreateTicketComponent',
        mixins:[
            DataFormMixin
        ],
        props: [
            'title', 'keys', 'request_url'
        ],
        data() {
            return {
                categories: {},
                related_type: {},
                related_id: {},
                formData: {
                    ticket_categories_id : 0,
                    subject: '',
                    description: '',
                    related_type: 0,
                    related_id: 0,
                    attachments: ''
                },
            }
        },
        methods: {
            getCategories: function() {
                axios({method:'GET', url: '/web/tickets/category' }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.categories = data.data;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            getRelatedId: function () {
                let related_type = this.formData.related_type;
                var url = '';

                switch(related_type) {
                    case '1':
                        url = "/web/accounts";
                        break;
                    case '2':
                        url = "/web/integrations";
                        break;
                    case '3':
                        url = "/web/orders";
                        break;
                    case '4':
                        url = "/web/products";
                        break;
                    case '5':
                        url = "/web/inventories";
                        break;
                }

                axios({method:'GET', url: url }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if(data.items) {
                            this.related_id = data.items;
                        }
                        else if(data.response.integrations) {
                            this.related_id = data.response.integrations;
                        }
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            onSave: function () {
                let self = this;

                self.setData(self.formData);

                axios({method:'POST', url: self.request_url, data: new FormData($('#create-form')[0]) }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                        window.location.href = "/admin/tickets";
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            uploadFile: function() {
                this.attachments = this.$refs.file.files;

                let filename = [];

                $.each(this.attachments, function(key, value) {
                    filename.push(value.name);
                });
                $('.custom-file-label').html(filename.join(', '));
            }
        },
        created() {
            this.getCategories();
            this.setData(this.formData);
        }
    }
</script>
