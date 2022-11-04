<template>
    <div>
        <div class="card">
            <div class="row">
                <div class="col-md-8 col-sm-4">
                    <div class="card-body">
                        <div class="media mb-3">
                            <a class="mr-3" href="#">
                                <img class="avatar-sm" src="/images/user.png">
                            </a>
                            <div v-if="field" class="media-body">
                                <h5 v-if="field.user">{{ field.user.name }}
                                    <small class="ml-2 text-muted">{{ field.created_at | formatDate }}</small>
                                </h5>
                                {{ field.description }}
                                <div class="m-0">
                                    <span v-for="attachment in field.attachments" class="badge badge-pill bg-dark text-white col-auto m-1"> <i class="fas fa-paperclip"></i> {{ attachment.title }} </span>
                                </div>
                            </div>
                        </div>
                        <div v-if="field.replies" class="overflow-auto" style="max-height: 50vh;">
                            <div v-for="reply in field.replies" class="card shadow p-2 ml-4">
                                <div class="media">
                                    <a class="mr-3" href="#">
                                        <img class="avatar-sm" src="/images/user.png">
                                    </a>
                                    <div class="media-body">
                                        <h5 v-if="reply.user">{{ reply.user.name }}
                                            <small class="ml-2 text-muted">{{ reply.created_at | formatDay }}</small>
                                        </h5>
                                        {{ reply.message }}
                                        <div class="m-0">
                                            <span v-for="attachment in reply.attachments" class="badge badge-pill bg-dark text-white col-auto m-1"> <i class="fas fa-paperclip"></i> {{ attachment.title }} </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="field" class="card shadow p-4">
                            <form ref="form" id="reply-form" @submit.prevent="onSave(field)">
                                <div class="h5" v-if="field.user">@{{ field.user.name }}</div>
                                <div class="mb-2">
                                    <textarea name="message" class="form-control" placeholder="Write your reply.." v-model="formData.message"></textarea>
                                    <label v-if="attachments" v-for="attachment in attachments" class="m-1"><small class="badge badge-sm badge-pill badge-secondary border custom-label">{{ attachment.name }}</small></label>
                                </div>
                                <div class="text-right">
                                    <label v-if="attachments.length === 0" class="mr-2"><small>No File Selected.</small></label>
                                    <label class="btn btn-sm btn-primary m-0" for="my-file-selector">
                                        <i class="fas fa-paperclip m-0"></i>
                                        <input ref="file" name="attachments[]" id="my-file-selector" multiple type="file" class="d-none" @change="uploadFile">
                                    </label>
                                    <button type="submit" class="btn btn-sm btn-info">Reply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-2">
                    <div class="card-header h4">
                        Ticket Activities
                    </div>
                    <div v-if="trails" class="card-body pt-0">
                        <ul class="list-group list-group-flush">
                            <ul class="list-group">
                                <li v-for="trail in trails" class="list-group-item">
                                    <span class="badge badge-circle bg-dark text-white" v-if="trail.user"><i class="fas   fa-arrow-right"></i></span> <small>{{ trail.description }} at <strong> {{ trail.created_at | formatDate }} </strong> by <strong> {{ trail.user.name }} </strong> </small>
                                </li>
                            </ul>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    export default {
        name : 'UserShowTicketComponent',
        props : ['index_url', 'request_url'],
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
                field: {},
                appendParams: {},
                formData: {
                    message : ''
                },
                trails: {},
                attachments: []
            }
        },
        methods: {
            retrieve: function() {
                return axios({method:'GET', url: this.request_url, params: this.appendParams }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.field = data.response;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            onSave: function(ticket) {
                axios({method:'POST', url: this.index_url + "/" + ticket.case_id + "/replies", data: new FormData($('#reply-form')[0]) }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Success', data.meta.message, 'center', 'success');
                        this.retrieve();
                        this.getTrails();
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            getTrails: function() {
                axios({method:'GET', url: "/web/tickets/" + this.field.case_id + "/trails" }).then((response) => {
                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.trails = data.response.trails;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            uploadFile()
            {
                this.attachments = this.$refs.file.files;
            }
        },
        async created () {
            await this.retrieve();
            this.getTrails();
        }
    }
</script>
