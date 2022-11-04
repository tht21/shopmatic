<template>
	<div>
		<show-subscription-component></show-subscription-component>
		<div class="row justify-content-center">
			<div class="card col-md-8 col-sm-12">
				<!-- Card header -->
				<div class="card-header">
					<!-- Title -->
					<div class="row align-items-center">
						<div class="col-8">
						<!-- Title -->
							<h5 class="h3 mb-0">Payment Methods</h5>
						</div>
						<div class="col-4 text-right">
							<a href="/dashboard/billing/create" class="btn btn-sm btn-neutral">Add</a>
						</div>
					</div>
				</div>
				<!-- Card body -->
				<div class="card-body">
					<h3 class="card-title mb-3">Credit or debit cards</h3>
					<p class="card-text mb-4">
						Card will be charged monthly.
						All major credit / debit cards accepted.
					</p>

					<div class="card">
						<div class="list-group list-group-flush">
							<div class="list-group-item list-group-item-action flex-column align-items-start py-4 px-4"
							v-for="(item, index) in payment_methods">
								<div class="card-body">
									<div class="row justify-content-between align-items-center">
										<div class="col">
											<img v-if="item.card.brand == 'visa'" src="/images/icons/visa.png" alt="Visa">
											<img v-if="item.card.brand == 'mastercard'" src="/images/icons/mastercard.png" alt="Mastercard">
										</div>
										<div class="col-auto">
											<span v-if="item.card.default" class="badge badge-lg badge-success">Default</span>
											<button v-else class="btn btn-sm btn-neutral" @click="setAsDefault(item)">Set as default</button>
										</div>
									</div>
									<div class="row">
										<div class="my-4 col-md-4 col-sm-12">
											<span class="h6 surtitle text-muted">
											Card number
											</span>
											<div class="card-serial-number h1">
												<div>XXXX</div>
												<div>XXXX</div>
												<div>XXXX</div>
												<div>{{ item.card.last4 }}</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-3 col-sm-6">
											<span class="h6 surtitle text-muted">Name</span>
											<span class="d-block h3">{{ item.card.name }}</span>
										</div>
										<div class="col-md-3 col-sm-6">
											<span class="h6 surtitle text-muted">Expiry date</span>
											<span class="d-block h3">{{ item.card.exp_month }}/{{ item.card.exp_year }}</span>
										</div>
									</div>

									<button class="float-right btn btn-sm btn-danger" @click="remove(item)">Remove</button>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<invoice-index-component></invoice-index-component>
	</div>
</template>

<script>
	export default {
		name: 'BillingIndexComponent',
		props: [],
		data() {
			return {
				sending_request: false,
				payment_methods: []
			}
		},

		mounted() {
			this.retrieve()
		},

		methods: {
			retrieve() {
				axios.get('/web/billing').then((response) => {
					console.log(response)
					response = response.data

					if (response.meta.error) {

					} else {
						this.payment_methods = response.response
					}

				})
			},

			remove(item) {
				if (this.sending_request) {
                    return;
                }

				this.sending_request = true

				swal({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    type: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                    	notify('top', 'Info', 'Removing credit card..', 'center', 'info');

						axios.delete('/web/billing/' + item.card.id, {}).then((response) => {
		                    let data = response.data;
		                    if (data.meta.error) {
		                        notify('top', 'Error', data.meta.message, 'center', 'danger');
		                    } else {
		                        this.retrieve()

		                        swal({
		                            title: 'Success',
		                            text: 'You have successfully removed your credit/debit card.',
		                            type: 'success',
		                            buttonsStyling: false,
		                            confirmButtonClass: 'btn btn-success'
		                        })
		                    }

		                   this.sending_request = false;
		                }).catch((error) => {
		                    this.sending_request = false;
		                    if (error.response && error.response.data && error.response.data.meta) {
		                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
		                    } else {
		                        notify('top', 'Error', error, 'center', 'danger');
		                    }
		                });
                    } else {
                        this.sending_request = false;
                    }
                })
			},

			setAsDefault(item) {
				if (this.sending_request) {
                    return;
                }

				this.sending_request = true

				notify('top', 'Info', 'Setting credit card as default payment..', 'center', 'info');

				axios.post('/web/billing/' + item.card.id + '/default', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.retrieve()

                        swal({
                            title: 'Success',
                            text: 'You have successfully set ade default payment.',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            window.location = '/dashboard/billing';
                        })
                    }

                   this.sending_request = false;
                }).catch((error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
			}
		}
	}
</script>
