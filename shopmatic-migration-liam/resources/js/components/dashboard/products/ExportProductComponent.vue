<template>
    <div>
        <div class="row" v-if="section === 1">
            <div class="col-md-12 col-lg-12 pt-3 pr-2">
                <h2 class="font-weight-light text-primary text-center">Where do you want to export to?</h2>
                <hr/>
                <div class="row">
                    <template v-if="accounts.length">
                        <div v-for="account in accounts" class="col-4 mb-4">
                            <div class="account-box" :class="{ 'bg-light' : account.status !== 0}" @click="selectAccount(account)">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <img :src="'/images/integrations/' + account.integration.name.toLowerCase() + '.png'" height="50" width="50" />
                                    </div>
                                    <div class="col-8">
                                        <small class="text-uppercase text-info">{{ account.name }}</small><br />
                                        <small class="text-muted">in {{ account.region.name }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="col">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
                                <span class="alert-text">No Account Found</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="row" v-if="section === 2">
            <div class="col-md-12 col-lg-12 pl-2 pr-0">
                <export-table-component :account="selected_account" v-on:change-section="changeSection($event)"></export-table-component>
            </div>
        </div>
    </div>
</template>

<script>
	import axios from 'axios'
	import ExportTableComponent from './partials/ExportTableComponent'

	export default {
		name: 'ExportProductComponent',
        components: { ExportTableComponent },
        props: [],
		data() {
			return {
			    section : 1,
                accounts: [],
                selected_account : null,
                accounts_request_url: '/web/accounts'
			}
		},

		methods : {
            retrieveAccounts() {
                this.accounts = [];
                let parameters = {with: 'locations'};

                axios.get(this.accounts_request_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.accounts = data.response.items;
                    }
                }).catch((error) => {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
			selectAccount (account) {
                // Only active account
                if (account.status === 0) {
                    this.section = 2;
                    this.selected_account = account;
                }
			},
            changeSection: function(ev) {
                this.section = ev;
            }
		},
        created() {
            this.retrieveAccounts();
        },
	}
</script>
