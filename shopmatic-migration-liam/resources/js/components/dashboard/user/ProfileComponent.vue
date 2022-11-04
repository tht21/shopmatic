<template>
    <b-card class="mx-md-auto col-md-8">
        <h1 class="text-center"><i class="ni ni-settings-gear-65 text-info text-white mr-2" style="font-size: 15px;"></i>User Settings</h1>
        <b-form id="create-form" role="form" >
            <div class="row">
                <div class="col-md-12 col-lg-12 py-3 pr-4">
                    <template  v-if="section === 0 || section === 1">
                        <b-form-group label="Name:" label-for="name-input">
                            <b-form-input
                                id="name-input"
                                v-model="form.name"
                                required
                                placeholder="Name"
                                name="name"
                                :state="getState('name')"
                                :disabled="section === 0"
                            ></b-form-input>
                            <b-form-invalid-feedback :state="getState('name')">
                                Name is required.
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group label="Email:" label-for="email-input">
                            <b-form-input
                                id="email-input"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="Email"
                                name="email"
                                disabled
                            ></b-form-input>
                        </b-form-group>

                        <b-form-group label="Phone Number:" label-for="phone-number-input">
                            <b-form-input
                                id="phone-number-input"
                                v-model="form.phone_number"
                                placeholder="Phone Number (Optional)"
                                name="phone_number"
                                :state="getState('phone_number')"
                                :disabled="section === 0"
                            ></b-form-input>
                            <b-form-invalid-feedback v-if="!getState('phone_number', 'isPhoneOrEmpty')">
                                Invalid phone number format.
                            </b-form-invalid-feedback>
                            <b-form-invalid-feedback v-if="!getState('phone_number', 'maxLength')">
                                Phone number must be less than 15 character.
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </template>

                    <template  v-if="section === 2">
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

                        <b-form-group label="Password:" label-for="password-input">
                            <b-form-input
                                id="password-input"
                                v-model="form.password"
                                type="password"
                                placeholder="Password"
                                name="password"
                                :state="getState('password')"
                                :disabled="!form.old_password"
                            ></b-form-input>
                            <b-form-invalid-feedback :state="getState('old_password')">
                                Password is required.
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group label="Confirm Password:" label-for="password-confirmation-input">
                            <b-form-input
                                id="password-confirmation-input"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Confirm Password"
                                name="password_confirmation"
                                :state="getState('password_confirmation')"
                                :disabled="!form.old_password"
                            ></b-form-input>
<!--                            <b-form-invalid-feedback v-if="!getState('password_confirmation', 'required')">-->
<!--                                Confirm Password is Required.-->
<!--                            </b-form-invalid-feedback>-->
                            <b-form-invalid-feedback v-if="!getState('password_confirmation', 'sameAsPassword')">
                                Password not identical.
                            </b-form-invalid-feedback>
                        </b-form-group>
                    </template>

                    <div class="text-left" v-if="section === 0">
                        <b-button variant="primary"  @click="section = 1">Edit</b-button>
                        <b-button variant="secondary"  @click="section = 2">Change Password</b-button>
                    </div>
                    <div class="text-right" v-if="section === 1 || section === 2">
                        <b-button variant="default" @click="section = 0">Cancel</b-button>
                        <b-button variant="primary" :disabled="sending_request || !getState()" @click="update()">Save</b-button>
                    </div>
                </div>
            </div>
        </b-form>
    </b-card>
</template>

<script>
    import axios from 'axios'
    import { required, maxLength, sameAs } from 'vuelidate/lib/validators'

    const isPhoneOrEmpty = (value) => value === null || /^(\d+|\d+-\d+|)$/.test(value)

    export default {
        name: "ProfileComponent",
        props: ['auth_user', 'is_modal'],
        data() {
            return {
                section: 0,
                form: {
                    name: this.auth_user.name,
                    email: this.auth_user.email,
                    old_password: '',
                    password: '',
                    password_confirmation: '',
                    phone_number: this.auth_user.phone_number,
                    force_email_notification: true
                },
                invite_email: [],
                sending_request: false,
            }
        },
        methods: {
            update() {
                if (this.sending_request) {
                    return;
                }
                this.sending_request = true;

                notify('top', 'Info', 'Update user..', 'center', 'info');

                // updated user
                axios({
                    method: "put",
                    url: "/web/users/" + this.auth_user.id,
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
                            location.reload();
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
            getState(key = null, subKey = null) {
                if (typeof this.$v.form !== 'undefined' && key === null) {
                    return !this.$v.form.$invalid;
                } else if (typeof this.$v.form !== 'undefined' && typeof this.$v.form[key] !== "undefined") {
                    if (subKey === null) {
                        return !this.$v.form[key].$invalid;
                    } else {
                        return this.$v.form[key][subKey];
                    }
                }
                return null;
            },
        },
        validations() {
            if (this.section === 1) {
                return {
                    form: {
                        name: {required},
                        phone_number: {
                            isPhoneOrEmpty,
                            maxLength: maxLength(15)
                        }
                    }
                };
            } else if (this.section === 2 && this.form.old_password) {
                return {
                    form: {
                        old_password: {},
                        password: {required},
                        password_confirmation: {
                            required,
                            sameAsPassword: sameAs(() => this.form.password)
                        }
                    }
                };
            }
            return {};
        }
    }
</script>

<style scoped>

</style>
