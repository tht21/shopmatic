<template>
    <div>
        <b-row class="mb-3">
            <b-col>
                <div>
                    <b-form-radio-group
                        id="btn-radios-status"
                        v-model="section"
                        :options="options"
                        buttons
                        name="radios-btn-default"
                    ></b-form-radio-group>
                </div>
                <div>
                    <div class="card mt-5">
                        <div class="card-body">

                            <b-form id="create-form" role="form">
                                <div class="row">
                                    <div class="col-md-8 col-lg-12 py-3 pr-4">
                                        <template v-if="section === 0">
                                            <div class="row">
                                                <div class="col">
                                                    <b-form-group label="Name:" label-for="name-input">

                                                        <b-form-input
                                                            id="name-input"
                                                            v-model="form.name"
                                                            required
                                                            placeholder="Name"
                                                            name="name"
                                                            :disabled="!edit"
                                                        ></b-form-input>
                                                    </b-form-group>
                                                </div>
                                                <div class="col">
                                                    <b-form-group label="Phone Number:" label-for="phone-number-input">
                                                        <b-form-input
                                                            id="phone-number-input"
                                                            v-model="form.phone_number"
                                                            placeholder="Phone Number (Optional)"
                                                            name="phone_number"
                                                            :disabled="!edit"
                                                        ></b-form-input>
                                                    </b-form-group>
                                                </div>
                                            </div>
                                            <b-form-group label="Email:" label-for="email-input">
                                                <b-form-input
                                                    id="email-input"
                                                    v-model="form.email"
                                                    type="email"
                                                    required
                                                    placeholder="Email"
                                                    name="email"
                                                    :disabled="!edit"
                                                ></b-form-input>
                                            </b-form-group>
                                            <div class="text-right">
                                                <template v-if="!edit">
                                                    <b-button variant="primary" @click="edit = true">Edit</b-button>
                                                </template>
                                                <template v-else>
                                                    <b-button variant="danger" :disabled="sending_request" @click="deleteUser()"
                                                              class="float-left">Delete
                                                    </b-button>
                                                    <b-button variant="primary" :disabled="sending_request" @click="update()">Save</b-button>
                                                    <b-button variant="default" @click="getForm();edit = false">Cancel</b-button>
                                                </template>
                                            </div>
                                        </template>


                                        <template v-if="section === 1">
                                            <b-form-group label="Password:" label-for="password-input">
                                                <b-form-input
                                                    id="password-input"
                                                    v-model="formPassword.password"
                                                    type="password"
                                                    placeholder="Password"
                                                    name="password"
                                                ></b-form-input>
                                            </b-form-group>

                                            <b-form-group label="Confirm Password:" label-for="password-confirmation-input">
                                                <b-form-input
                                                    id="password-confirmation-input"
                                                    v-model="formPassword.password_confirmation"
                                                    type="password"
                                                    placeholder="Confirm Password"
                                                    name="password_confirmation"
                                                ></b-form-input>
                                            </b-form-group>
                                            <div class="text-right">
                                                <b-button @click="resetPassword" variant="info">
                                                    Send Password Reset Link
                                                </b-button>
                                                <b-button variant="primary" :disabled="sending_request" @click="updatePassword()">Save
                                                </b-button>
                                                <b-button variant="default" @click="getForm(); section = 0">Cancel</b-button>
                                            </div>
                                        </template>

                                    </div>
                                </div>
                            </b-form>
                        </div>
                    </div>
                </div>
            </b-col>
        </b-row>
        <b-row class="">
            <b-col>
                <admin-shop-index-component :user="user"></admin-shop-index-component>
            </b-col>
        </b-row>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: ['user'],
    data() {
        return {
            section: 0,
            edit: false,
            form: {
                name: '',
                email: '',
                phone_number: '',
            },
            formPassword: {
                name: '',
                email: '',
                phone_number: '',
                old_password: '',
                password: '',
                password_confirmation: '',
            },
            sending_request: false,
            options: [
                {text: 'User Profile', value: 0},
                {text: 'Change Password', value: 1},
            ],
        }
    },
    created() {
        if (this.user) {
            this.getForm();
        }

    },
    methods: {
        getForm() {
            this.form.name = this.user.name;
            this.form.email = this.user.email;
            this.form.phone_number = this.user.phone_number;
            this.formPassword.name = this.user.name;
            this.formPassword.email = this.user.email;
            this.formPassword.phone_number = this.user.phone_number;
        },
        update() {
            if (this.sending_request) {
                return;
            }
            this.sending_request = true;

            notify('top', 'Info', 'Submitting...', 'center', 'info');

            axios({
                method: "put",
                url: "/web/users/" + this.user.id,
                data: this.form
            }).then((response) => {
                let data = response.data;
                if (data.meta.error) {
                    swal({
                        title: 'Error',
                        text: data.meta.message.replace(/^\w/, (c) => c.toUpperCase()),
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    });
                    this.sending_request = false;
                } else {
                    swal({
                        title: 'Success',
                        text: 'You have successfully updated your details.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-success'
                    }).then(() => {
                        this.edit = false;
                        this.section = 0;
                        this.sending_request = false;
                    });
                }
            }).catch((error) => {
                if (error.response && error.response.data && error.response.data.meta) {
                    swal({
                        title: 'Error',
                        text: error.response.data.meta.message.replace(/^\w/, (c) => c.toUpperCase()),
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    });
                }
                this.sending_request = false;
            });
        },

        updatePassword() {
            if (this.sending_request) {
                return;
            }
            this.sending_request = true;

            notify('top', 'Info', 'Submitting...', 'center', 'info');

            axios({
                method: "put",
                url: "/web/users/" + this.user.id,
                data: this.formPassword
            }).then((response) => {
                let data = response.data;
                if (data.meta.error) {
                    swal({
                        title: 'Error',
                        text: data.meta.message.replace(/^\w/, (c) => c.toUpperCase()),
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    });

                    this.sending_request = false;
                } else {
                    swal({
                        title: 'Success',
                        text: 'You have successfully updated your password.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-success'
                    }).then(() => {
                        this.formPassword.old_password = '',
                            this.formPassword.password = '',
                            this.formPassword.password_confirmation = ''
                        this.sending_request = false;
                        this.edit = false;
                        this.section = 0;
                    });
                }
            }).catch((error) => {
                if (error.response && error.response.data && error.response.data.meta) {
                    swal({
                        title: 'Error',
                        text: error.response.data.meta.message.replace(/^\w/, (c) => c.toUpperCase()),
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    });
                }
                this.sending_request = false;
            });
        },

        deleteUser() {
            swal({
                title: 'Are you sure you want to delete this user?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {
                    axios({method: 'DELETE', url: "/web/users/" + this.user.id}).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Success', 'Successfully deleted the user.', 'center', 'success');
                            window.location.href = "/admin/users";
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

        resetPassword() {
            let form = {
                'email': this.form.email
            };

            notify('top', 'Info', 'Submitting...', 'center', 'info');

            axios({
                method: "post",
                url: '/web/password/email',
                data: form
            }).then((response) => {
                let data = response.data;
                if (data.meta.error) {
                    swal({
                        title: 'Error',
                        text: data.meta.message.replace(/^\w/, (c) => c.toUpperCase()),
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    });
                    this.sending_request = false;
                } else {
                    swal({
                        title: 'Success',
                        text: 'Password reset link sent.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-success'
                    }).then(() => {
                        this.sending_request = false;
                    });
                }
            }).catch((error) => {
                if (error.response && error.response.data && error.response.data.meta) {
                    swal({
                        title: 'Error',
                        text: error.response.data.meta.message.replace(/^\w/, (c) => c.toUpperCase()),
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger'
                    });
                }
                this.sending_request = false;
            });
        },

    }
}
</script>
