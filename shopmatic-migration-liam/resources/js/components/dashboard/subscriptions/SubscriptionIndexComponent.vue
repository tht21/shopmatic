<template>
	<div>
		<show-subscription-component v-if="subscription"></show-subscription-component>
	    <!-- Page content -->
	    <div class="container pb-5">
	        <div class="row">
	            <div class="col-12">
	                <div class="pricing card-group flex-column flex-md-row mb-3">
	                    <div class="card card-pricing border-0 text-center mb-4">
	                        <div class="card-header bg-transparent wow fadeIn">
	                            <h4 class="text-uppercase ls-1 text-primary py-3 mb-0 wow fadeIn">Starter</h4>
	                        </div>
	                        <div class="card-body">
	                            <div class="display-3 wow fadeIn">S$29.90</div>
	                            <span class="d-inline-block text-muted wow fadeIn">per month</span>
	                            <p class="mt-3">
	                                For individuals who's just started e-commerce and anyone who wants to explore Combinesell.
	                            </p>
	                        </div>
	                        <div v-if="!subscription" class="card-footer">
	                            <div class="btn btn-primary mb-3 wow fadeInUp d-block mx-auto" @click="subscribe(0, true)">Start free trial</div>
	                            <a href="#" @click="subscribe(0, false)">Subscribe Now</a>
	                        </div>
	                        <div v-else class="card-footer">
	                            <div v-if="subscription.stripe_plan.indexOf('starter') == -1" class="btn btn-primary mb-3 wow fadeInUp d-block mx-auto" @click="subscribe(0, false)">Subscribe</div>
	                            <div class="btn mb-3 wow fadeInUp d-block mx-auto" disabled v-else>
	                            	Subscribed
	                            </div>
	                            <a href="#">&nbsp;</a>
	                        </div>
	                    </div>
                        <div class="card card-pricing border-0 text-center mb-4">
                            <div class="card-header bg-transparent wow fadeIn">
                                <h4 class="text-uppercase ls-1 py-3 mb-0 text-info">Growth<br />
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="display-3 wow fadeIn">S$99.90</div>
                                <span class="d-inline-block wow fadeIn">per month</span>
                                <p class="mt-3">
                                    Businesses requiring more features, advanced reporting and prioritized support.
                                </p>
                                <ul class="list-unstyled my-4">
                                    <li class="wow fadeInRight">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </div>
                                            <div class="text-left pl-2">
                                                <span>Multichannel Sales Performance</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="wow fadeInRight" data-wow-delay="0.4s">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="pl-2">Dedicated Support</span>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div v-if="!subscription" class="card-footer bg-transparent">
                                <div class="btn btn-info mb-3 wow fadeInUp d-block mx-auto" @click="subscribe(1, true)">Start free trial</div>
                                <a href="#" @click="subscribe(1, false)">Subscribe Now</a>
                            </div>
                            <div v-else class="card-footer bg-transparent">
                                <div class="btn btn-info mb-3 wow fadeInUp d-block mx-auto" @click="subscribe(1, false)">Subscribe</div>
                                <a href="#">&nbsp;</a>
                            </div>
                        </div>
	                    <div class="card card-pricing border-0 text-center mb-4">
	                        <div class="card-header bg-transparent wow fadeIn">
	                            <h4 class="text-uppercase ls-1 py-3 mb-0 text-success">Full Service/MEP <br />
	                            </h4>
	                        </div>
	                        <div class="card-body">
	                            <div class="display-3 wow fadeIn">S$149.90</div>
	                            <span class="d-inline-block wow fadeIn">per month</span>
	                            <p class="mt-3">
	                                Businesses requiring more features, advanced reporting and prioritized support.
	                            </p>
	                            <ul class="list-unstyled my-4">
                                    <li class="wow fadeInRight">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </div>
                                            <div class="text-left pl-2">
                                                <span>Multichannel Sales Performance</span>
                                            </div>
                                        </div>
                                    </li>
	                                <li class="wow fadeInRight" data-wow-delay="0.4s">
	                                    <div class="d-flex align-items-center">
	                                        <div>
	                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
	                                                <i class="fas fa-check"></i>
	                                            </div>
	                                        </div>
	                                        <div>
	                                            <span class="pl-2">Dedicated Support</span>
	                                        </div>
	                                    </div>
	                                </li>
                                    <li class="wow fadeInRight" data-wow-delay="0.4s">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="pl-2">Unlimited Sku</span>
                                            </div>
                                        </div>
                                    </li>
	                            </ul>
	                        </div>
	                        <div v-if="!subscription" class="card-footer bg-transparent">
	                            <div class="btn btn-success mb-3 wow fadeInUp d-block mx-auto" @click="subscribe(2, true)">Start free trial</div>
	                            <a href="#" @click="subscribe(2, false)">Subscribe Now</a>
	                        </div>
	                        <div v-else class="card-footer bg-transparent">
	                            <div class="btn btn-success mb-3 wow fadeInUp d-block mx-auto" @click="subscribe(2, false)">Subscribe</div>
	                            <a href="#">&nbsp;</a>
	                        </div>
	                    </div>
	                    <div class="card card-pricing border-0 text-center mb-4">
	                        <div class="card-header bg-transparent">
	                            <h4 class="text-uppercase ls-1 text-orange py-3 mb-0 wow fadeIn">Enterprise</h4>
	                        </div>
	                        <div class="card-body">
	                            <div class="display-3 font-weight-light wow fadeIn">S$2,100.00</div>
                                <span class="d-inline-block wow fadeIn">per month</span>
	                            <p class="mt-4 pt-3">
	                                For companies that wants to grow and scale their business easily with Combinesell.
	                            </p>
	                            <ul class="list-unstyled my-4">
	                                <li class="wow fadeInRight">
	                                    <div class="d-flex align-items-center">
	                                        <div>
	                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
	                                                <i class="fas fa-check"></i>
	                                            </div>
	                                        </div>
	                                        <div>
	                                            <span class="pl-2">Support Staff</span>
	                                        </div>
	                                    </div>
	                                </li>
	                                <li class="wow fadeInRight" data-wow-delay="0.2s">
	                                    <div class="d-flex align-items-center">
	                                        <div>
	                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
	                                                <i class="fas fa-check"></i>
	                                            </div>
	                                        </div>
	                                        <div>
	                                            <span class="pl-2">Campaign Handling</span>
	                                        </div>
	                                    </div>
	                                </li>
	                                <li class="wow fadeInRight" data-wow-delay="0.4s">
	                                    <div class="d-flex align-items-center">
	                                        <div>
	                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
	                                                <i class="fas fa-check"></i>
	                                            </div>
	                                        </div>
	                                        <div>
	                                            <span class="pl-2">Product Listing & Design</span>
	                                        </div>
	                                    </div>
	                                </li>
                                    <li class="wow fadeInRight" data-wow-delay="0.4s">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="pl-2">5% revenue sharing</span>
                                            </div>
                                        </div>
                                    </li>
	                            </ul>
	                        </div>
	                        <div class="card-footer">
	                            <a href="/contact" class="btn bg-orange text-white mb-3 wow fadeInUp d-block">Contact Us</a>
	                            <a href="/enterprise" target="_blank" class="text-primary wow fadeIn">Learn More</a>
	                        </div>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class="d-flex justify-content-lg-center px-3 mt-5 mb-5 wow fadeIn">
	            <div>
	                <div class="icon icon-shape bg-gradient-white shadow rounded-circle text-primary">
	                    <i class="ni ni-building text-primary"></i>
	                </div>
	            </div>
	            <div class="col-lg-7">
	                <p class="">We also offer custom packages. Contact <a href="mailto:sales@combinesell.com" class="text-info">sales@combinesell.com</a> for more information!</p>
	            </div>
	        </div>
	        <div class="row row-grid justify-content-center bg-default">
	            <div class="col-lg-10">
	                <div class="table-responsive">
	                    <table class="table table-dark mt-5">
	                        <thead>
	                        <tr>
	                            <th class="px-0 bg-transparent" scope="col">
	                                <span class="text-light font-weight-700">Features</span>
	                            </th>
	                            <th class="text-center bg-transparent" scope="col">Starter</th>
	                            <th class="text-center bg-transparent" scope="col">Growth</th>
                                <th class="text-center bg-transparent" scope="col">Full Service/MEP</th>
	                        </tr>
	                        </thead>
	                        <tbody>
	                        <tr>
	                            <td class="px-0">Multichannel Listing Management</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">Multichannel Bulk Export</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">Multichannel Order Management</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">Multichannel Inventory Sync</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr>
	                        <!-- <tr>
	                            <td class="px-0">Multichannel Enquiry Management</td>
	                            <td class="text-center">-</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
	                            </td>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr> -->
	                        <tr>
	                            <td class="px-0">Multichannel Profit & Loss</td>
	                            <td class="text-center">-</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">Dedicated Support</td>
	                            <td class="text-center">-</td>
	                            <td class="text-center"><i class="fas fa-check text-success"></i>
                                <td class="text-center"><i class="fas fa-check text-success"></i>
                                </td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">Additional Users</td>
	                            <td class="text-center">-</td>
	                            <td class="text-center text-success font-weight-bold">3</td>
                                <td class="text-center text-success font-weight-bold">10</td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">Integrations</td>
	                            <td class="text-center text-success font-weight-bold">*10</td>
	                            <td class="text-center text-success font-weight-bold">25</td>
                                <td class="text-center text-success font-weight-bold">25</td>
	                        </tr>
	                        <tr>
	                            <td class="px-0">SKUs</td>
	                            <td class="text-center text-success font-weight-bold">1000</td>
	                            <td class="text-center text-success font-weight-bold">8000</td>
                                <td class="text-center text-success font-weight-bold">unlimited</td>
	                        </tr>
	                        </tbody>
	                    </table>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
</template>

<script>
	export default {
		name: 'SubscriptionIndexComponent',
		props: ['subscription'],
		data() {
			return {
				sending_request: false,
				plan: ['Starter', 'Growth', 'Full Service/MEP'],
				price: ['29.90', '99.90', '149.90']
			}
		},

		mounted() {
			console.log(this.subscription)
		},

		methods: {
			subscribe(plan, trial) {
				if (this.sending_request) {
                    return;
                }

				this.sending_request = true

				notify('top', 'Info', 'Subscribing to ' + this.plan[plan] + '..', 'center', 'info');

				axios.post('/web/subscriptions', {
					plan: plan,
					trial: trial
				}).then((response) => {
                    let data = response.data;
                        this.sending_request = false;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully subscribed to ' + this.plan[plan],
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
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

			}
		}
	}
</script>
