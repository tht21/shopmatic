<template>
    <a class="btn btn-sm btn-neutral" href="#" @click="importCategories">Import</a>
</template>

<script>
    export default {
        name: "AccountCategoryImportComponent",
        props: [
            'global'
        ],
        data() {
            return {
                
            }
        },
        methods: {
            importCategories() {
            	console.log(this.global)

            	if (typeof this.global.name == 'undefined') {
            		notify('top', 'Error', 'Please select an account!', 'center', 'danger');
            		return
            	}

            	swal({
                    title: 'Are you sure?',
                    text: 'Import categories from account ' + this.global.name,
                    type: 'info',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonClass: 'btn btn-success',
                    cancelButtonClass: 'btn btn-danger'
                }).then((result) => {
                    if (result.value) {
                        notify('top', 'Info', 'Submitting import task..', 'center', 'info');

                        axios.post('/web/accounts/' + this.global.id + '/categories/import').then((response) => {
                        	let data = response.data.response;
		                    if (response.data.meta.error) {
		                        notify('top', 'Error', response.data.meta.message, 'center', 'danger');
		                    } else {
		                        swal({
                                	title: 'Success',
	                                html: 'We are now importing the categories from your account. It might take a while to complete the import.',
	                                type: 'success',
	                                buttonsStyling: false,
	                                confirmButtonClass: 'btn btn-success'
	                            })
		                    }
                        }).catch((error) => {
                        	if (error.response && error.response.data && error.response.data.meta) {
		                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
		                    } else {
		                        notify('top', 'Error', error, 'center', 'danger');
		                    }
                        })

                    } else {
                        this.sending_request = false;
                    }
                })
            }
        }
    }
</script>

<style scoped>

</style>
