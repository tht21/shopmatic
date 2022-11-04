<template>
	<div>
		<div class="row justify-content-center">
			<div class="card col-md-8 col-sm-12">
				<!-- Card header -->
				<div class="card-header">
					<!-- Title -->
					<div class="row align-items-center">
						<div class="col-8">
						<!-- Title -->
							<h5 class="h3 mb-0">Invoices</h5>
						</div>
					</div>
				</div>
				<!-- Card body -->
				<div class="card-body">
					
					<b-list-group v-if="invoices.length > 0" class="list-group-flush list my--3">
						<b-list-group-item v-for="(item, index) in invoices" :key="index">
							<b-row>
								<b-col cols="5">
									<h4>
										{{ item.created }}
									</h4>
									<small>
										Period <br>
										{{ item.period_start }} - {{ item.period_end }}
									</small>
								</b-col>
    							<b-col cols="5">
    								<h4>{{ item.description }}</h4>
    							</b-col>
    							<b-col cols="2">
    								<button type="button" class="btn btn-sm btn-primary" @click="download(item.id)">Download</button>
    							</b-col>
    						</b-row>
						</b-list-group-item>
					</b-list-group>

					<h3 v-else class="card-title mb-3">There's no invoice available</h3>

					<div class="card-footer py-4" v-if="pagination != null && invoices.length > 0">
						<pagination-component :details="pagination" @paginated="paginate"></pagination-component>
					</div>
					
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: 'InvoiceIndexComponent',
		props: [],
		data() {
			return {
				sending_request: false,
				invoices: [],
				pagination: null
			}
		},

		mounted() {
			this.retrieve()
		},

		methods: {
			retrieve() {
				axios.get('/web/invoices', {
					params: {
						page: this.pagination != null ? this.pagination.current_page : 1
					}
				}).then((response) => {
					response = response.data

					if (response.meta.error) {

					} else {
						this.invoices = response.response.items
						this.pagination = response.response.pagination
					}
					
				})
			},

			download(id) {
				if (this.sending_request) {
                    return;
                }

				this.sending_request = true

				notify('top', 'Info', 'Downloading invoice..', 'center', 'info');
				setTimeout(() => {
					window.open('/web/invoices/' + id)
				}, 1000)
			},

			paginate(value) {
				this.pagination = value
				this.retrieve()
			}
		}
	}
</script>