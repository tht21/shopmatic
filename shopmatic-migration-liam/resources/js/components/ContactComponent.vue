<template>
    <div>
        <div class="header bg-default pt-100" style="height: 70px;"><div class="separator separator-bottom separator-skew zindex-100"></div></div>
        <section class="full-background py-5 bg-white">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <h1 class="wow fadeInDown">Contact Us</h1>
                        <p class="lead wow fadeIn d-block" data-wow-delay="0.3s">Feel free to drop us a message and we'll get back to you as soon as possible. </p>
                    </div>
                    <div class="col-10 col-sm-6 m-auto col-md-4 pt-4 pt-md-0">
                        <img alt="image" class="img-fluid rounded-0 wow jackInTheBox" src="/images/chat.svg">
                    </div>
                </div>
            </div>
        </section>
        <section class="full-background bg-white py-5" :style="{ 'background-image': 'url(/images/background/8.svg)' }">
            <div class="container py-5">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-5">

                        <div class="wow fadeIn">
                            <h1>Singapore<br /><small>Headquarters</small></h1>
                            <p class="text-large"></p>
                            <div class="row">
                                <div class="col-1 d-flex align-items-center">
                                    <i class="fa fa-address-book" style="font-size: 18px;"></i>
                                </div>
                                <div class="col-11 d-flex align-items-center">
                                    <p class="text-large mb-0">
                                        600 North Bridge Rd, <br />
                                        #10-01, Singapore 188778
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-1 d-flex align-items-center">
                                    <i class="fa fa-mail-bulk" style="font-size: 18px;"></i>
                                </div>
                                <div class="col-11 d-flex align-items-center">
                                    <p class="text-large mb-0">
                                        <a href="mailto:support@combinesell.com">support@combinesell.com</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="wow fadeIn" data-wow-delay="0.3s">
                            <hr />
                            <h1>Malaysia</h1>
                            <p class="text-large"></p>
                            <div class="row">
                                <div class="col-1 d-flex align-items-center">
                                    <i class="fa fa-address-book" style="font-size: 18px;"></i>
                                </div>
                                <div class="col-11 d-flex align-items-center">
                                    <p class="text-large mb-0">
                                        13-05, Oval Damansara, Jalan Damansara, <br />
                                        Taman Tun Dr Ismail, <br />
                                        60000 Kuala Lumpur<br />
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-1 d-flex align-items-center">
                                    <i class="fa fa-mail-bulk" style="font-size: 18px;"></i>
                                </div>
                                <div class="col-11 d-flex align-items-center">
                                    <p class="text-large mb-0">
                                        <a href="mailto:support@combinesell.com">support@combinesell.com</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 ml-auto">
                        <h2 class="wow fadeIn">Drop us a line</h2>
                        <form class="wow fadeIn">
                            <div class="row">
                                <div class="col">
                                    <label>Your Name</label>
                                    <b-form-input v-model="form.name" :state="!$v.form.name.$invalid" required />
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col">
                                    <label>Your Email Address</label>
                                    <b-form-input type="email" v-model="form.email" :state="!$v.form.email.$invalid" aria-describedby="email-feedback" required />
                                    <b-form-invalid-feedback v-if="form.email !== ''" id="email-feedback">Invalid email format. (example: a@a.aa)</b-form-invalid-feedback>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col">
                                    <label>Subject (optional but helpful)</label>
                                    <b-form-input v-model="form.subject"/>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col">
                                    <label>How can we help?</label>
                                    <b-form-textarea v-model="form.body" :state="!$v.form.body.$invalid" rows="5" no-resize required></b-form-textarea>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col text-right">
                                    <button type="submit" class="btn btn-primary" :disabled="$v.$invalid" @click="submit">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <div class="p-0">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.7303853760104!2d103.96578271463034!3d1.3380501990241065!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31da3cdbb4265db1%3A0x3e218f040012b8d4!2s22%20Changi%20Business%20Park%20Central%202%2C%20Singapore%20486041!5e0!3m2!1sen!2smy!4v1570593110833!5m2!1sen!2smy" width="100%" height="400" frameborder="0" style="border:0;" allowfullscreen=""></iframe>
        </div>
    </div>
</template>

<script>
    import { required, email } from 'vuelidate/lib/validators';
    export default {
        name: "ContactComponent",
        props: ['csrf'],
        data() {
            return {
                form: {
                    _token: this.csrf,
                    name: '',
                    email: '',
                    subject: '',
                    body: '',
                }
            }
        },
        methods: {
            submit(e) {
                e.preventDefault()
                if (!this.$v.$invalid) {
                    axios.post('/contact', this.form).then(response => {
                        if (response.status === 200) {
                            // GA Event
                            if (typeof ga != 'undefined') {
                                ga('send', 'event', 'contact-us', 'contact-us-form-submission', this.form.subject);
                            }

                            // Adwords
                            if (typeof gtag != 'undefined') {
                                gtag('event', 'conversion', {
                                    'send_to': 'AW-650067007/SNAeCMaq8M0BEL_4_LUC'
                                });
                            }

                            swal({
                                title: 'Success',
                                html: response.data.message,
                                type: 'success',
                                buttonsStyling: false,
                                confirmButtonClass: 'btn btn-success'
                            });

                            // reset form
                            this.form = {
                                _token: this.csrf,
                                name: '',
                                email: '',
                                subject: '',
                                body: '',
                            };
                        } else {
                            notify('top', 'Error', response.data.message, 'center', 'danger');
                        }
                    }).catch(error => {
                        console.log(error);
                    });
                }
            }
        },
        validations() {
            return {
                form: {
                    name: {required},
                    email: {required, email},
                    body: {required},
                }
            }
        }
    }
</script>

<style scoped>

</style>
