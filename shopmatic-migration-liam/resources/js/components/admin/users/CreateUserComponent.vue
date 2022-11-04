<template>
    <div class="modal fade show" id="create-user-form" tabindex="-1" role="dialog" aria-labelledby="create-user-form" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="card bg-secondary border-0 mb-0">
                        <div class="card-header bg-info">
                            <h3 class="mb-0 text-white">Create User</h3>
                        </div>
                        <div class="card-body px-lg-5 py-lg-5">
                            <form id="create-form" role="form" @submit="create">
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-single-02"></i></span>
                                        </div>
                                        <input name="name" class="form-control" placeholder="Name" type="text" required />
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                        </div>
                                        <input name="email" class="form-control" placeholder="Email" type="email" required />
                                    </div>
                                </div>
                                <div class="form-group" v-if="!email_notification">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                        </div>
                                        <input name="password" class="form-control" minlength="6" placeholder="Password" type="password" required />
                                    </div>
                                    <a id="generate-password" href="#" v-on:click="generatePasswordButton" class="btn btn-sm btn-info text-white mt-2">Generate Password</a>
                                </div>
                                <div class="form-group" v-if="!email_notification">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                        </div>
                                        <input name="password_confirmation" class="form-control" minlength="6" placeholder="Password Confirmation" type="password" required />
                                    </div>
                                </div>
                                <div class="form-group" v-show="!email_notification">
                                    <input id="generated-password" title="generated-password" type="text" name="generated-password" style="display: none;" class="form-control" readonly />
                                </div>
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-bell-55"></i></span>
                                        </div>
                                        <input name="phone_number" class="form-control" placeholder="Phone Number (Optional)" type="text" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="email_notification" class="d-flex align-items-top">
                                        <input name="email_notification" v-model="email_notification" id="email_notification" type="checkbox" value="1" class="mt-1">
                                        <span class="ml-2 d-inline-block">Send New Account Information<br /><small>Password will be auto generated</small></span>
                                    </label>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-info mb-2 mt-4">Create User</button>
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
    export default {
        name: "CreateUserComponent",
        props: [
            'request_url'
        ],
        data() {
            return {
                email_notification: 0,
                sending_request: false,
            }
        },
        methods: {
            create: function(event) {
                if (this.sending_request) {
                    return;
                } else {
                    this.sending_request = true;
                }
                event.preventDefault();
                let ctx = this;
                axios.post(this.request_url, new FormData($('#create-form')[0])).then(function (response) {
                    ctx.sending_request = false;
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.email_notification = 0;
                        $('#create-user-form').modal('hide');
                        $("#create-form").trigger('reset');
                        swal({
                            title: 'Success',
                            text: 'You have successfully created the user. You might need to refresh this page to see it.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        })
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
            generatePasswordButton: function(event) {
                event.preventDefault();
                var password = this.generatePassword();
                jQuery('#generated-password').val(password).show();
                jQuery('input[name=password]').val(password);
                jQuery('input[name=password_confirmation]').val(password);
            },
            generatePassword: function() {
                let text = "";
                let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

                for( var i=0; i < 16; i++ )
                    text += possible.charAt(Math.floor(Math.random() * possible.length));

                return text;
            }
        },
    }
</script>