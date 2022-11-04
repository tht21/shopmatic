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

                                    <b-form-select
                                        v-model="formData.ticket_categories_id"
                                        :options="categories"
                                        value-field="id"
                                        text-field="name"
                                        name="ticket_categories_id"
                                    >
                                        <!-- This slot appears above the options from 'options' prop -->
                                        <template v-slot:first>
                                            <b-form-select-option :value="null" disabled>-- Select Category --</b-form-select-option>
                                        </template>
                                    </b-form-select>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-single-copy-04"></i></span>
                                    </div>
                                    <input name="subject" class="form-control" placeholder="Subject" type="text" v-model="formData.subject" required />
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <textarea name="description" class="form-control form-control-alternative" v-model="formData.description" rows="3" placeholder="Tell us more ..."></textarea>

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
                                        <option value="App\Models\Account"> Account </option>
                                        <option value="App\Models\Integration"> Integration </option>
                                        <option value="App\Models\Order"> Order </option>
                                        <option value="App\Models\Product"> Product </option>
                                        <option value="App\Inventory"> Inventory </option>
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
        name: 'UserCreateTicketComponent',
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
                    ticket_categories_id : null,
                    subject: '',
                    description: '',
                    related_type: 0,
                    related_id: 0
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
            getRelatedId: function () {
                let related_type = this.formData.related_type;
                var url = '';
                this.related_id = {};

                switch(related_type) {
                    case 'App\\Models\\Account':
                        url = "/web/accounts";
                        break;
                    case 'App\\Models\\Integration':
                        url = "/web/integrations";
                        break;
                    case 'App\\Models\\Order':
                        url = "/web/orders";
                        break;
                    case 'App\\Models\\Product':
                        url = "/web/products";
                        break;
                    case 'App\\Inventory':
                        url = "/web/inventory";
                        break;
                }

                axios({method:'GET', url: url }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if(data.items) {
                            this.related_id = data.items;
                        } else if(data.response.integrations) {
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
                        notify('top', 'Success', 'Created ticket successfully', 'center', 'success');
                        window.location.href = "/dashboard/tickets";
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            uploadFile: function () {
                this.attachments = this.$refs.file.files;

                let filename = [];

                $.each(this.attachments, function(key, value) {
                    filename.push(value.name);
                });
                $('.custom-file-label').html(filename.join(', '));
            },
        },
        created() {
            this.getCategories();
            this.setData(this.formData);
        }
    }
</script>
