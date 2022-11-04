<template>
	<div>
		<button class="btn btn-sm btn-neutral" @click="show = true">Create Composite</button>

		<b-modal id="create-composite-modal" ref="create-composite-modal" v-model="show" size="md" title="Create Inventory Composite" body-class="" hide-footer>

			<ol>
				<li>Filter the list to show not bundled inventory</li>
				<li>By clicking the <i class="fa fa-filter"></i> icon, and select <b>Not Bundled</b> in the <b>SHOW</b> option</li>
				<li>Click on the inventory which you wanna bundle, and add in the bundle inventory</li>
				<li>Click the proceed button below to start</li>
			</ol>
            
            <div class="pt-3">
                <b-button class="float-right" variant="primary" @click="filter">Proceed</b-button>
            </div>
        </b-modal>
	</div>
</template>

<script>
	export default {
		name: 'CreateInventoryCompositeComponent',
		props: ['global'],
		data() {
			return {
				show: false
			}
		},
		methods: {
			filter(e) {
				e.preventDefault();
				this.$emit('update', {
					filter: true
				});

				this.show = false
			}
		}
	}
</script>