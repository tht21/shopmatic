<template>
    <div class="card">
        <div class="card-body">
            <b-form id="create-form" role="form" @submit.prevent="send()">
                <!-- @NOTE - enable only we have shop switching -->
                <!-- <b-form-group label="Choose Add Type">
                    <b-form-radio-group
                        v-model="type"
                        :options="options"
                        name="type"
                    ></b-form-radio-group>
                </b-form-group> -->

                <div class="row">
                    <div class="col-md-12 col-lg-12 py-3 pr-4">
                        <template v-if="type === 1">
                            <b-form-group label="Name:" label-for="name-input">
                                <b-form-input
                                    id="name-input"
                                    v-model="form.name"
                                    required
                                    placeholder="Name"
                                    name="name"
                                ></b-form-input>
                            </b-form-group>

                            <b-form-group label="Email:" label-for="email-input">
                                <b-form-input
                                    id="email-input"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="Email"
                                    name="email"
                                    :disabled="shop_users != null && !is_create"
                                ></b-form-input>
                            </b-form-group>


                            <template v-if="shop_users">
                                <template v-if="!is_create">
                                    <b-form-group label="Old Password:" label-for="old-password-input">
                                        <b-form-input
                                            id="old-password-input"
                                            v-model="form.old_password"
                                            type="password"
                                            placeholder="Old Password"
                                            name="old-password"
                                        ></b-form-input>
                                        <h5 class="ml-1 mt-1">Leave blank if not going to change</h5>
                                    </b-form-group>
                                </template>

                                <b-form-group label="Password:" label-for="password-input">
                                    <b-form-input
                                        id="password-input"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Password"
                                        name="password"
                                    ></b-form-input>
                                </b-form-group>

                                <b-form-group label="Confirm Password:" label-for="password-confirmation-input">
                                    <b-form-input
                                        id="password-confirmation-input"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Confirm Password"
                                        name="password_confirmation"
                                    ></b-form-input>
                                </b-form-group>

                            </template>
                            <template v-else>

                                <b-form-group label="Password:" label-for="password-input">
                                    <b-form-input
                                        id="password-input"
                                        v-model="form.password"
                                        required
                                        type="password"
                                        placeholder="Password"
                                        name="password"
                                    ></b-form-input>
                                </b-form-group>

                                <b-form-group label="Confirm Password:" label-for="password-confirmation-input">
                                    <b-form-input
                                        id="password-confirmation-input"
                                        v-model="form.password_confirmation"
                                        required
                                        type="password"
                                        placeholder="Confirm Password"
                                        name="password_confirmation"
                                    ></b-form-input>
                                </b-form-group>
                            </template>

                            <b-form-group label="Phone Number:" label-for="phone-number-input">
                                <b-form-input
                                    id="phone-number-input"
                                    v-model="form.phone_number"
                                    placeholder="Phone Number (Optional)"
                                    name="phone_number"
                                ></b-form-input>
                            </b-form-group>
                        </template>

                        <template v-if="type === 2">
                            <b-form-group label="Select User:" label-for="select-user-input">
                                <!-- @TODO multi input box to invite existing users -->

                            </b-form-group>
                        </template>

                        <b-button type="submit" variant="primary" :disabled="sending_request">Submit</b-button>
                    </div>
                </div>

            </b-form>
        </div>
    </div>
</template>

<script>
    import axios from 'axios'
    import Multiselect from 'vue-multiselect'

    export default {
        name: "CreateUserManagementComponent",
        components: {Multiselect},
        props: {
            shop_users: {
                type: [Array, Object]
            },
            is_modal: {
                type: Boolean,
                default: false
            },
            is_create: {
                type: [Boolean],
                default: false
            }
        },
        //props: ['shop_users', 'is_modal'],
        data() {
            return {
                type: 1,
                options: [
                    {text: 'Add New User', value: 1},
                    {text: 'Add Existing User', value: 2},
                ],
                form: {
                    name: '',
                    email: '',
                    old_password: '',
                    password: '',
                    password_confirmation: '',
                    phone_number: '',
                    force_email_notification: true
                },
                invite_email: [],
                sending_request: false,
            }
        },
        created() {
            if (this.shop_users) {
                this.form.name = this.shop_users.name;
                this.form.email = this.shop_users.email;
                this.form.phone_number = this.shop_users.phone_number;
            }
        },
        methods: {
            send() {
                if (this.shop_users && !this.is_create) {
                    this.update();
                } else {
                    this.create();
                }
            },
            async create() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                if (this.type === 1) {
                    notify('top', 'Info', 'Creating user..', 'center', 'info');

                    // Create user first
                    let user = null;
                    await axios.post('/web/users', this.form).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');

                            this.sending_request = false;
                        } else {
                            user = data.response;
                        }
                    }).catch((error) => {
                        if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }
                        this.sending_request = false;
                    });

                    // If user created successfully then assign to shop
                    if (user) {
                        notify('top', 'Info', 'Assigning shop to user..', 'center', 'info');
                        axios.post('/web/shops/assign/' + user.id, {}).then((response) => {
                            let data = response.data;
                            if (data.meta.error) {
                                notify('top', 'Error', data.meta.message, 'center', 'danger');
                            } else {
                                swal({
                                    title: 'Success',
                                    text: 'You have created the user successfully.',
                                    type: 'success',
                                    buttonsStyling: false,
                                    confirmButtonClass: 'btn btn-success'
                                }).then(() => {
                                    if (this.is_modal) {
                                        this.$emit('hideModal');
                                    } else {
                                        window.location = '/dashboard/shop/users/';
                                    }
                                });
                            }

                            this.sending_request = false
                        }).catch((error) => {
                            if (error.response && error.response.data && error.response.data.meta) {
                                notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                            } else {
                                notify('top', 'Error', error, 'center', 'danger');
                            }
                            this.sending_request = false;
                        });
                    }
                } else {
                    // @NOTE - not ready yet
                    return

                    // @TODO - get emails, and send it to new API
                    axios.post('/web/shops/invite', {
                        emails: this.invite_email
                    }).then((response) => {
                        let data = response.data;
                        if (data.meta.error) {
                            notify('top', 'Error', data.meta.message, 'center', 'danger');
                        } else {
                            swal({
                                title: 'Success',
                                text: 'You have invited the users successfully.',
                                type: 'success',
                                buttonsStyling: false,
                                confirmButtonClass: 'btn btn-success'
                            }).then(function () {
                                window.location = '/dashboard/shop/users/';
                            });
                        }
                    }).catch((error) => {
                        this.creating = false;
                        if (error.response && error.response.data && error.response.data.meta) {
                            notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                        } else {
                            notify('top', 'Error', error, 'center', 'danger');
                        }
                    });
                }

            },
            update() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Update user..', 'center', 'info');

                // updated user
                axios({
                    method: "put",
                    url: "/web/users/" + this.shop_users.id,
                    data: this.form
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');

                        this.sending_request = false;
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully edited the shop.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            window.location = '/dashboard/shop/users/';
                        });
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                    this.sending_request = false;
                });
            },
            generatePasswordButton(e) {
                // @NOTE - something which can use at later
                e.preventDefault();

                var password = this.generatePassword();

                this.form.password = password
                this.form.password_confirmation = password
            },
            generatePassword() {
                let text = "";
                let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

                for (var i = 0; i < 16; i++)
                    text += possible.charAt(Math.floor(Math.random() * possible.length));

                return text;
            }
        },
        /*created() {

        },*/
    }
</script>

<style scoped>

</style>
