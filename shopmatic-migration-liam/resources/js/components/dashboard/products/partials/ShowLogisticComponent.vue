<template>
	<div>
		<show-qoo10-logistic-component v-if="integration.id == 11004" :logistics="logistics"></show-qoo10-logistic-component>
		<show-shopee-logistic-component v-if="integration.id == 11003" :logistics="logistics"></show-shopee-logistic-component>
		<show-shopify-logistic-component v-if="integration.id == 11002" :logistics="logistics"></show-shopify-logistic-component>
	</div>
</template>

<script>
	import ShowShopeeLogisticComponent from './logistics/ShowShopeeLogisticComponent'
	import ShowShopifyLogisticComponent from './logistics/ShowShopifyLogisticComponent'
    import ShowQoo10LogisticComponent from "./logistics/ShowQoo10LogisticComponent";

	export default {
		name: 'ProductLogisticComponent',
		components: {
            ShowShopeeLogisticComponent,
            ShowShopifyLogisticComponent,
            ShowQoo10LogisticComponent,
		},
		props: ['logistics', 'integration'],

		updated () {
			/*console.log(this.logistics);
			console.log(this.integration);*/
		}
	}
</script>
