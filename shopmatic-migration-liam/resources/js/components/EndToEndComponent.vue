<template>
    <div>
        <div class="header bg-darjer"><div class="separator separator-bottom separator-skew zindex-100"></div></div>
        <section id="end-to-end-top">
            <div class="container-fluid py-5 bg-darker">
                <div class="container py-5">
                    <div class="row">
                        <div class="col-md-6 py-5">
                            <h1 class="font-weight-light font-size-40 text-white wow fadeInRight mb-0"><strong>Selling on all e-sites</strong>
                                <br /><strong>with the</strong> <strong style="color: #fe6d00">most powerful</strong>
                                <br /><strong style="color: #fe6d00">e-commerce enabler!!</strong>
                            </h1>
                            <div class="pt-2 pl-4">
                                <p class="text-white border-left pl-4" style="border-color: #fe6d00 !important;">
                                    Start selling everywhere your customers shop, <br />
                                    CombineSell gives your business the seamlessly <br />
                                    and effortless experience other service provider can't.<br /><br />

                                    With Combinesell your business can: <br />

                                    <img src="images/checked.png" class="py-2 wow fadeInUp"> Professional e-commerce Setup <br />
                                    <img src="images/checked.png" class="py-2 wow fadeInUp"> Reduce Business Cost <br />
                                    <img src="images/checked.png" class="py-2 wow fadeInUp"> Go to Market Quickly <br />
                                    <img src="images/checked.png" class="py-2 wow fadeInUp"> Drive New Revenue Growth
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 py-3" style="background-color: #fe6d00">
                            <h1 class="text-white text-center wow bounceInUp">Get Started With a Free Consultation</h1>
                            <form>
                                <div class="row py-2">
                                    <div class="col">
                                        <b-form-group label-class="text-white" label="First Name*" label-for="first_name">
                                            <b-form-input id="first_name" v-model="form.first_name" :state="!$v.form.first_name.$invalid" required />
                                        </b-form-group>
                                    </div>
                                    <div class="col">
                                        <b-form-group label-class="text-white" label="Last Name*" label-for="last_name">
                                            <b-form-input id="last_name" v-model="form.last_name" :state="!$v.form.last_name.$invalid" required />
                                        </b-form-group>
                                    </div>
                                </div>
                                <div class="row py-2">
                                    <div class="col">
                                        <b-form-group label-class="text-white" label="Work Email*" label-for="work_email">
                                            <b-form-input type="email" id="work_email" v-model="form.work_email" :state="!$v.form.work_email.$invalid" aria-describedby="work-email-feedback" required />
                                            <b-form-invalid-feedback v-if="form.work_email !== ''" id="work-email-feedback">Invalid email format. (example: a@a.aa)</b-form-invalid-feedback>
                                        </b-form-group>
                                    </div>
                                    <div class="col">
                                        <b-form-group label-class="text-white" label="Company Name*" label-for="company_name">
                                            <b-form-input id="company_name" v-model="form.company_name" :state="!$v.form.company_name.$invalid" required />
                                        </b-form-group>
                                    </div>
                                </div>
                                <div class="row py-2">
                                    <div class="col">
                                        <b-form-group label-class="text-white" label="Phone Number*" label-for="phone_number">
                                            <b-form-input id="phone_number" v-model="form.phone_number" :state="!$v.form.phone_number.$invalid" required />
                                        </b-form-group>
                                    </div>
                                    <div class="col">
                                        <b-form-group label-class="text-white" label="Country*" label-for="country">
                                            <multiselect
                                                id="country"
                                                :class="!this.$v.form.country.$invalid? 'state-true' : 'state-false'"
                                                v-model="form.country"
                                                :options="country"
                                                placeholder="-- Please Select --"
                                                :show-labels="false"
                                                :allow-empty="false"
                                            />
                                        </b-form-group>
                                    </div>
                                </div>
                                <div class="row py-2">
                                    <div class="col-md-6">
                                        <b-form-group label-class="text-white" label="Department*" label-for="department">
                                            <multiselect
                                                id="department"
                                                :class="!this.$v.form.department.$invalid? 'state-true' : 'state-false'"
                                                v-model="form.department"
                                                :options="department"
                                                placeholder="-- Please Select --"
                                                :show-labels="false"
                                                :allow-empty="false"
                                            />
                                        </b-form-group>
                                    </div>
                                </div>
                                <div class="row py-4 mb-2">
                                    <div class="col">
                                        <b-form-checkbox id="customCheck1" v-model="auth_checker" required>
                                            <small class="text-white">
                                                By supplying my contact information, I authorize the Combinesell Group of Companies to contact <br/>
                                                me via personalized communications about Combinesell's products and services. <br/>
                                                See our <a href="/privacy-policy">Privacy Policy</a> for more details or to opt-out at anytime.
                                            </small>
                                        </b-form-checkbox>
                                    </div>
                                </div>
                                <div class="row pt-4">
                                    <div class="col">
                                        <button type="button" class="btn btn-default text-uppercase" :disabled="$v.$invalid" @click="submit">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="p-3 bg-white border-bottom" style="border: silver">
            <div class="container-fluid">
                <div class="row text-center">
                    <div class="col-12">
                        <h1 class="mt-4 wow fadeInUp">Trusted by over 3,000+ business and merchants.</h1>

                        <div class="my-4 justify-content-center">
                            <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.2s" src="/images/trusted/14.png">
                            <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.4s" src="/images/trusted/12.png">
                            <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.6s" src="/images/trusted/13.png">
                            <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.5s" src="/images/trusted/9.png">
                            <img alt="image" height="20" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.3s" src="/images/trusted/10.png">
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <section class="p-5 bg-white">
            <div class="container">
                <div class="col-12">
                    <div class="h1 mt-4 wow fadeIn text-center">
                        <strong>Give your business the</strong> <br />
                        <strong style="color: #fe6d00">Singapore #1 multichannel e-commerce engine.</strong>
                    </div>

                    <div class="my-4 justify-content-center">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <img class="wow bounceInUp" src="images/icons/shipping.png">
                                <div class="h1">Listing<br />Optimization</div>
                                <p>
                                    We help to manage all your product listing and optimization.
                                    Ensuring that you stand among your competitions.
                                </p>
                            </div>
                            <div class="col-md-3">
                                <img class="wow bounceInUp" src="images/icons/sale.png">
                                <div class="h1">Campaign<br />Optimization</div>
                                <p>
                                    Leave it to us to analyse your campaign data and tap on the
                                    insights to optimise your marketing campaign.
                                </p>
                            </div>
                            <div class="col-md-3">
                                <img class="wow bounceInUp" src="images/icons/headset.png">
                                <div class="h1">Customer<br />Support</div>
                                <p>
                                    Our professional team will help supporting your customer
                                    enquiries, up-selling and cross-selling products.
                                </p>
                            </div>
                            <div class="col-md-3">
                                <img class="wow bounceInUp" src="images/icons/delivery-truck.png">
                                <div class="h1">Order<br />Management</div>
                                <p>
                                    Opt-in for our preferred 3rd Party Logistic partner's service
                                    to support your daily door-to-door delivery.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="p-5" style="background-color: #fe6d00">
            <div class="container py-2">
                <div class="row align-items-center">
                    <div class="col-12 col-md-6 m-md-auto ml-lg-0 mr-lg-auto">
                        <div style="position: relative; left: 0; top: 0;">
                            <img alt="image" class="img-fluid wow flipInX" style="position: relative; top: 0; left: 0" src="/images/macbook_store.png">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 ml-sm-auto pt-5 pt-lg-0 pl-5">
                        <img src="/images/trusted/11.png">
                        <div class="text-white text-xl py-4">
                            "Engaging Combinesell has really make a great impact and change to
                            our company. Tapping on the multichannel e-commerce has always been
                            our plan to do so, with Combinesell, we are able to expedite on the progress
                            and work together hand in hand to do better online!
                            <br /><br />
                            Thanks, Combinesell!"
                        </div>
                        <p class="text-white">Ivy Ang<br />Director of Mattress International</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="p-5 bg-white">
            <div class="container align-items-center justify-content-center d-flex">
                <div class="row justify-content-center text-center">
                    <div class="col">
                        <h1 class="wow fadeInDown text-black text-left py-2">Combinesell by the Numbers</h1>
                        <h1 class="text-left py-2 wow bounceIn" style="color: #fe6d00">3,000+</h1>
                        <p class="text-left">merchants picked Combinesell</p>
                        <h1 class="text-left py-2 wow bounceIn" style="color: #fe6d00">78%</h1>
                        <p class="text-left">
                            faster sales growth for Combinesell Merchants compared to other
                            platforms (2019 Data of Combinesell).
                        </p>
                    </div>
                    <div class="col wow slideInRight">
                        <img src="/images/icons/cpu.png">
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-darker py-5">
            <div class="container my-5">
                <h1 class="wow fadeInDown text-center" style="font-size: 40px;"><strong class="text-white">Get started today with </strong><strong style="color: #fe6d00">70% Government Subsidy</strong></h1>
                <div class="text-center py-3">
                    <a href="#end-to-end-top"  class="btn text-uppercase text-white" style="background-color: #fe6d00">Request Free Consultation</a>
                </div>
            </div>
        </section>

        <section class="py-5 bg-white">
            <div class="container align-items-center justify-content-center">
                <div class="row justify-content-center text-left">
                    <div class="col">
                        <h1 class="wow slideInLeft">Are you a SME?</h1>
                        <h3 class="wow slideInLeft">
                            Together with CombineSell, SMEs in Singapore can enjoy <br />
                            up to S$22,500.00 Enterprise Development Grant in online <br />
                            sales expansion to sell everywhere their customers shop.
                        </h3>
                        <a href="#end-to-end-top"  class="py-3 h3" style="color: #fe6d00"><u class="wow slideInLeft">Request a free consultation now! ></u></a>
                    </div>
                    <div class="col">
                        <h1 class="wow slideInRight">Are you a partner or agency?</h1>
                        <h3 class="wow slideInRight">
                            We'd love to work with you. Partner with Combinesell and <br />
                            help shape the future of Singapore's e-commerce scene. <br />
                            Partner with us now.
                        </h3>
                        <a href="#end-to-end-top" class="py-3 h3" style="color: #fe6d00"><u class="wow slideInRight">Talk to our partner success team now! ></u></a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
    import {required, email} from "vuelidate/lib/validators";

    export default {
        name: "EndToEndComponent",
        props: ['csrf'],
        data() {
            return {
                auth_checker: false,
                form: {
                    _token: this.csrf,
                    first_name: '',
                    last_name: '',
                    work_email: '',
                    company_name: '',
                    phone_number: '',
                    country: '',
                    department: '',
                },
                country: [
                    'Singapore',
                    'Malaysia',
                    'Indonesia',
                    'Philippines',
                    'Thailand',
                    'India',
                    'UAE',
                    'Others'
                ],
                department: [
                    'Director',
                    'Ecommerce',
                    'Marketing',
                    'Procurement',
                    'Finance',
                    'Management',
                    'Admin',
                    'Others'
                ]
            }
        },
        methods: {
            submit(e) {
                e.preventDefault()
                if (!this.$v.$invalid) {
                    axios.post('/mep-esg-edg', this.form).then(response => {
                        if (response.status === 200) {
                            // GA Event
                            if (typeof ga != 'undefined') {
                                ga('send', 'event', 'mep', 'mep-form-submission', this.form.department)
                            }
                            
                            // Adwords
                            if (typeof gtag != 'undefined') {
                                gtag('event', 'conversion', {
                                    'send_to': 'AW-650067007/di0GCLDq780BEL_4_LUC'
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
                                first_name: '',
                                last_name: '',
                                work_email: '',
                                company_name: '',
                                phone_number: '',
                                country: '',
                                department: '',
                            };
                            this.auth_checker = false;
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
                auth_checker: {checked: value => value},
                form: {
                    first_name: {required},
                    last_name: {required},
                    work_email: {required, email},
                    company_name: {required},
                    phone_number: {required},
                    country: {required},
                    department: {required},
                }
            }
        }
    };
</script>

<style lang="scss" scoped>
    // set multiselect label font weight to normal
    .multiselect .multiselect__tags .multiselect__single strong {
        font-weight: normal;
    }

    .form-group{
        margin-bottom: 0 !important;
    }
</style>
