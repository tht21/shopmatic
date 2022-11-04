<template>
    <div>
        <button class="btn btn-sm btn-danger" @click="clickDelete"><i class="fas fa-trash"></i> Delete</button>
        <!--<button class="btn btn-sm btn-neutral" @click="clickEdit"><i class="far fa-edit"></i> Edit</button>-->
        <b-modal size="lg" :ref="ref_name.delete" title="Shop details" :header-bg-variant="'primary'" :hide-footer="true">
            <template v-slot:modal-header="{ close }">
               <span>
                   <h3 class="text-white">Delete Product</h3>
               </span>
            </template>

            <div>
                <b-form-checkbox
                    id="checkbox"
                    name="checkbox"
                    :value="true"
                    :unchecked-value="false"
                    @change="changeCheckBox"
                >
                    <h3>Delete From Combinesell</h3>
                </b-form-checkbox>


                <b-form-checkbox
                    v-for="(listing, key) in product.listings"
                    v-bind:key="'checkbox-' + key"
                    :id="'checkbox-' + key"
                    name="'checkbox-' + key"
                    :value="listing"
                    :unchecked-value="listing.id"
                    class="mt-1"
                    @change="changeCheckBox"
                >
                    <h3>Delete From {{listing.account.integration.name}} {{listing.account.region.name}} ({{listing.account.name}})</h3>
                </b-form-checkbox>

            </div>

            <div class="mt-3">
                <b-button variant="link" @click="closeCancel()">Close</b-button>
                <b-button variant="danger" class="ml-auto float-right" @click="confirmDelete">Delete</b-button>
            </div>
        </b-modal>
    </div>
</template>

<script>
    export default {
        name: "ProductEditHeaderComponent",
        props: ['product'],
        data() {
          return {
              request_url: '/web/products',
              retrieving: false,
              ref_name: {
                  delete: "pre_delete_modal",
              },
              form: {
                  delete_local: false,
                  delete_listing_ids: [],
              },
              sending_request: false,
          }
        },
        created() {
            this.product.listings = this.product.listings.filter(item => item.account);
        },
        methods: {
            clickEdit() {
                window.location = "/dashboard/products/" + this.product.slug + "/edit"
            },
            clickDelete() {
                this.$refs[this.ref_name.delete].show();
            },
            closeCancel() {
                this.$refs[this.ref_name.delete].hide();
            },
            confirmDelete() {
                if(!this.validation()) {
                    return;
                }
                if (this.sending_request) {
                    return;
                }
                // this.sending_request = true;
                notify('top', 'Info', 'Deleting..', 'center', 'info');
                axios({method: "delete", url: this.request_url + "/" + this.product.slug, data: this.form}).then((response) => {
                    this.sending_request = false;
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        swal({
                            title: 'Success',
                            text: 'You have successfully initated the product deletion!',
                            type: 'success',
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            window.location.replace('/dashboard/products');
                        });
                    }


                }).catch((error) => {
                    this.sending_request = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            changeCheckBox(item) {
                if(typeof item === 'object') {
                    this.form.delete_listing_ids.push(item.id)
                }else if(typeof item === 'boolean') {
                    this.form.delete_local = item
                }else {
                    this.form.delete_listing_ids = this.form.delete_listing_ids.filter((d) => {
                        return d !== item;
                    })
                }
            },
            validation() {
                if(!this.form.delete_local && this.form.delete_listing_ids.length <= 0) {
                    notify('top', 'Error', "Select at least one", 'center', 'danger');
                    return false
                }
                return true
            }
        },
    }
</script>

<style scoped>

</style>
