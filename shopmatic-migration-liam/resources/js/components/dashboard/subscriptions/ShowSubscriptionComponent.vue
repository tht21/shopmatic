<template>
	<div class="row justify-content-center">
		<div class="card col-md-8 col-sm-12">
			<!-- Card header -->
			<div class="card-header">
				<!-- Title -->
				<div class="row align-items-center">
					<div class="col-8">
					<!-- Title -->
						<h5 class="h3 mb-0">Subscription</h5>
					</div>
					<div class="col-4 text-right">
						<a href="/dashboard/subscriptions" class="btn btn-sm btn-neutral">
							<span v-if="subscriptions && subscriptions.subscribed">Change Plan</span>
							<span v-else>Subscribe</span>
						</a>
					</div>
				</div>
			</div>
			<!-- Card body -->
			<div class="card-body" v-if="subscriptions && subscriptions.subscribed">
				<h3 class="card-title mb-3">You are currently subscribe to plan</h3>
				<p class="card-text mb-4">
					<span v-if="subscriptions.subscription.stripe_plan == 'starter_monthly'">
						Starter
					</span>
                    <span v-if="subscriptions.subscription.stripe_plan == 'professional_monthly'">
						Growth
					</span>
					<span v-if="subscriptions.subscription.stripe_plan == 'advance_monthly'">
						Full Service/MEP 
					</span>

					<span v-if="subscriptions.onGracePeriod" class="badge badge-warning">
						Grace Period
					</span>
				</p>
				<small v-if="subscriptions.onTrial && subscriptions.subscription.trial_ends_at != null">
					Trial ends at {{ subscriptions.subscription.trial_ends_at }}
					<br>
				</small>

				<small v-if="subscriptions.onTrial && subscriptions.subscription.ends_at != null">
					Subscription ends at {{ subscriptions.subscription.ends_at }}
				</small>

                <template v-if="subscriptions && subscriptions.subscribed && !subscriptions.onGracePeriod">
                    <button class="float-right btn btn-sm btn-danger" @click="cancel">Cancel</button>
                </template>
			</div>
			<div class="card-body" v-else>
				<h3 class="card-title mb-3">You are currently not subscribe to any plan yet</h3>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: 'ShowSubscriptionComponent',
		props: [],
		data() {
			return {
				sending_request: false,
				subscriptions: null
			}
		},

		mounted() {
			this.retrieve()
		},

		methods: {
			retrieve() {
				axios.get('/web/subscriptions').then((response) => {
					response = response.data
					if (response.meta.error) {
						notify('top', 'Error', response.data.meta.message, 'center', 'danger');
					} else {
						this.subscriptions = response.response
					}

				})
			},

			cancel(e) {
				e.preventDefault()

				if (this.sending_request) {
                    return;
                }

				this.sending_request = true

				swal({
                    title: 'Are you sure?',
                    text: 'All your services will be stop!',
                    type: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                    	notify('top', 'Info', 'Cancelling subscription..', 'center', 'info');

						axios.post('/web/subscriptions/cancel', {}).then((response) => {
		                    let data = response.data;
		                    if (data.meta.error) {
		                        notify('top', 'Error', data.meta.message, 'center', 'danger');
		                        this.sending_request = false;
		                    } else {
		                        this.sending_request = false;

		                        this.retrieve()

		                        swal({
		                            title: 'Success',
		                            text: 'You have successfully cancel your subscription. All your accounts are disabled.',
		                            type: 'success',
		                            buttonsStyling: false,
		                            confirmButtonClass: 'btn btn-success'
		                        }).then(() => {
		                            window.location = '/dashboard/subscriptions';
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
                        this.sending_request = false;
                    }
                })
			}
		}
	}
</script>
