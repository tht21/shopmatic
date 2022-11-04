<template>
    <div>
        <div class="card shadow">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col float-right">
                        <button type="button" v-for="(status, index) in status_array" v-if="ticket.status === index" :class="['badge text-white',
                                        {'bg-green': (ticket.status === 0)},
                                        {'bg-orange': (ticket.status === 1)},
                                        {'bg-green': (ticket.status === 2)},
                                        {'bg-orange': (ticket.status === 3)},
                                        {'bg-dark': (ticket.status === 4)}]" class="badge badge-pill  border-0 shadow-0 dropdown-toggle float-right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="dropdown-menu">
                                <button v-for="(status, index) in status_array" class="dropdown-item" type="button" @click="updateTicket(ticket, index)">{{ status }}</button>
                            </div>
                            {{ status }}
                        </button>
                    </div>
                </div>
                <div class="media">
                    <a class="mr-3" href="#">
                        <img class="avatar-sm" src="/images/user.png">
                    </a>
                    <div class="media-body">
                        <div class="media">
                            <div v-if="ticket" class="media-body">
                                <h3 class="mt-0">{{ ticket.subject }}
                                    <small class="text-muted">{{ ticket.created_at | formatDate }}</small>
                                </h3>
                                {{ ticket.description }}
                                <div class="m-0">
                                    <span v-for="attachment in ticket.attachments" class="badge badge-pill bg-dark text-white col-auto m-1"> <i class="fas fa-paperclip"></i> {{ attachment.title }} </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-auto mb-3" style="max-height: 50vh;">
            <div class="card-body pt-0">
                <div class="media">
                    <div class="media-body">
                        <div v-if="ticket.replies" v-for="reply in ticket.replies" class="card shadow p-2 mt-3">
                            <div class="media">
                                <a class="mr-3" href="#">
                                    <img class="avatar-sm" src="/images/user.png">
                                </a>
                                <div class="media-body">
                                    <h5 class="mt-0">{{ reply.user.name }} <small> {{ reply.created_at | formatDate }} </small> </h5>
                                    {{ reply.message }}
                                </div>
                            </div>
                            <div class="row ml-5">
                                <span v-for="attachment in reply.attachments" class="badge badge-pill bg-dark text-white col-auto m-1"><i class="fas fa-paperclip"></i> {{ attachment.title }} </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form ref="form" id="reply-form" @submit.prevent="sendReply(ticket.case_id)">
            <div class="card shadow p-2">
                <div class="mb-2">
                    <div class="h5">@{{ ticket.user.name }}</div>
                    <textarea v-model="formData.message" name="message" class="form-control" placeholder="Write your reply.."></textarea>
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
            </div>
        </form>
    </div>
</template>

<script>
    export default {
        name : 'AdminShowTicketDetailsComponent',
        props : ['data', 'index_url', 'status_array'],
        filters: {
            formatDate: function (date) {
                return moment(date).format('Do MMMM YYYY, h:mm:ss a');
            },
            formatHour: function (date) {
                return moment(date).startOf('hour').fromNow();
            },
        },
        data() {
            return {
                formData: {
                    message: ''
                },
                ticket: this.data,
                attachments: []
            }
        },
        methods: {
            retrieve: function() {
                axios({method:'GET', url: this.index_url + "/" + this.ticket.case_id}).then((response) => {

                    let data = response.data;

                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.ticket = data.response;
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            sendReply: function(id) {
                axios({method:'POST', url: this.index_url + "/" + id + "/replies", data: new FormData($('#reply-form')[0]) }).then((response) => {
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
            uploadFile: function() {
                this.attachments = this.$refs.file.files;

                let filename = [];

                $.each(this.attachments, function(key, value) {
                    filename.push(value.name);
                });
                $('.custom-file-label').html(filename.join(', '));
            },
            updateTicket: function (ticket, status) {
                axios({method:'PUT', url: this.index_url + "/" + ticket.case_id, data: {status : status} }).then((response) => {
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
        },
    }
</script>
