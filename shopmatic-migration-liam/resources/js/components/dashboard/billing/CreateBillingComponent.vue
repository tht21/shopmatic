<template>
	<div class="row justify-content-center">
        <div class="col-md-6 col-sm-12">
            <div class="card">
                <div class="card-header bg-cyan">
                    <div class="row justify-content-between align-items-center">
                        <div class="col">
                            <img src="/images/icons/visa.png" alt="Visa">
                            <img src="/images/icons/mastercard.png" alt="Mastercard">
                        </div>
                        <div class="col-auto">
                            <div class="d-flex align-items-center">
                                <small class="text-white font-weight-bold mr-3">Make default</small>
                                <div>
                                    <label class="custom-toggle  custom-toggle-white">
                                        <input type="checkbox" checked="" v-model="form.default">
                                        <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card body -->
                <div class="card-body bg-gradient-primary">
                    <!--<div class="row justify-content-between align-items-center">
                        <div class="col">
                            <img src="/images/icons/visa.png" alt="Visa">
                            <img src="/images/icons/mastercard.png" alt="Mastercard">
                        </div>
                        <div class="col-auto">
                            <div class="d-flex align-items-center">
                                <small class="text-white font-weight-bold mr-3">Make default</small>
                                <div>
                                    <label class="custom-toggle  custom-toggle-white">
                                        <input type="checkbox" checked="" v-model="form.default">
                                        <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>-->
                    <div class="mt-4">
                        <form role="form" class="form-primary billing-form">
                            <div class="form-group">
                                <div class="input-group input-group-alternative mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-single-02"></i></span>
                                    </div>
                                    <input class="form-control" placeholder="Name on card" type="text" v-model="form.cardHolderName">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group input-group-alternative mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-credit-card"></i></span>
                                    </div>
                                    <div id="card-number"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="input-group input-group-alternative mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                                            </div>
                                            <div id="card-expiry"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="input-group input-group-alternative">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                            </div>
                                            <div id="card-cvc"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">

                                    <!-- Stripe Elements Placeholder -->
                                    <div class="form-group">
                                        <div id="card-element"></div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-block btn-info" @click="submit">Save new card</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

	</div>
</template>

<script>
	export default {
		name: 'CreateBillingComponent',
		props: ['intent'],
		data() {
			return {
				sending_request: false,
				validated: {
					error: null,
					setupIntent: null
				},
				subscriptions: null,
				stripe : null,
				elements: null,
				cardElement: null,
				clientSecret: null,
				form: {
					cardHolderName: '',
					default: true
				},
				elementStyles: {
					base: {
						font: '400 13.3333px Arial',
						color: '#ffffff',
						fontSmoothing: 'antialiased',
						lineHeight: '2',

						':focus': {
							backgroundColor: 'rgba(42, 68, 219, 0.7)',
	    					border: '1px solid #2a44db',
	    					color: '#efefef'
						},

						'::placeholder': {
							color: '#fff',
						},

						':focus::placeholder': {
							color: '#fff',
						},
					},
					invalid: {
						color: '#fff',
							':focus': {
							color: '#FA755A',
						},
						'::placeholder': {
							color: '#FFCCA5',
						},
					}
				}
			}
		},

		mounted() {
			// @TODO - move key to config file
			this.stripe = Stripe('pk_test_hE69iTSm5wUIaSg48wq0vS2J00uT67T9EJ');
			this.elements = this.stripe.elements();
		    this.clientSecret = this.intent.client_secret

		    this.cardNumber = this.elements.create('cardNumber', {
		    	style: this.elementStyles,
				classes: {
					base: 'form-control'
				},
			});
			this.cardExpiry = this.elements.create('cardExpiry', {
				style: this.elementStyles,
				classes: {
					base: 'form-control'
				},
			});
			this.cardCvc = this.elements.create('cardCvc', {
				style: this.elementStyles,
				classes: {
					base: 'form-control'
				},
			});

			this.cardNumber.mount('#card-number');
			this.cardExpiry.mount('#card-expiry');
			this.cardCvc.mount('#card-cvc');

			this.retrieve()
		},

		methods: {
			retrieve() {
				axios.get('/web/subscriptions').then((response) => {
					response = response.data
					if (response.meta.error) {

					} else {
						this.subscriptions = response.response
					}

				})
			},
			async submit(e) {
				if (this.sending_request) {
                    return;
                }

				this.sending_request = true

				e.preventDefault()

				notify('top', 'Info', 'Verifying credit card..', 'center', 'info');

				// Validate card holder name
				if (this.form.cardHolderName == '') {
					notify('top', 'Error', 'Your card\'s name is incomplete.', 'center', 'danger');
					return;
				}

				if (this.validated.setupIntent == null) {
					const { setupIntent, error } = await this.stripe.handleCardSetup(
				        this.clientSecret, this.cardNumber, {
				            payment_method_data: {
				                billing_details: { name: this.form.cardHolderName }
				            }
				        }
				    );

                    this.validated.error = typeof error == 'undefined' ? null : error
					this.validated.setupIntent = typeof setupIntent == 'undefined' ? null : setupIntent
				}

			    if (this.validated.error) {
			    	// sample http://prntscr.com/qfrx3n
			        notify('top', 'Error', this.validated.error.message, 'center', 'danger');

			        this.sending_request = false
			    } else {
			        // sample http://prntscr.com/qfrx3n
			        // setupIntent.payment_method
			        if (this.validated.setupIntent.status == 'succeeded') {

			        	axios.post('/web/billing', {
			        		default: this.form.default,
				        	payment_method: this.validated.setupIntent.payment_method
				        }).then((response) => {
		                    let data = response.data;
		                    if (data.meta.error) {
		                        notify('top', 'Error', data.meta.message, 'center', 'danger');
		                        this.sending_request = false;
		                    } else {

		                        this.sending_request = false

		                        swal({
		                            title: 'Success',
		                            text: 'You have successfully added payment method.',
		                            type: 'success',
		                            buttonsStyling: false,
		                            confirmButtonClass: 'btn btn-success'
		                        }).then(() => {
		                        	if (!this.subscriptions || !this.subscriptions.subscribed) {
										window.location = '/dashboard/subscriptions'
									}
		                            window.location = '/dashboard/billing';
		                        })
		                    }
		                }).catch((error) => {
		                    this.sending_request = false;
		                    if (error.response && error.response.data && error.response.data.meta) {
		                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
		                    } else {
		                        notify('top', 'Error', error, 'center', 'danger');
		                    }
		                });
			        } else {
			        	notify('top', 'Error', 'Your card validation failed.', 'center', 'danger');

			        	this.sending_request =  false
			        }
			    }
			}
		}
	}
</script>

<style type="text/css">
	#card-number div, #card-number div iframe {
		height: 100% !important;
	}
</style>
