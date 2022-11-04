<template>
  <div>
    <b-row class="mb-4" align-h="between">
      <b-col cols="auto" >
        <h2>Shops</h2>
      </b-col>
      <b-col cols="auto">
        <b-button @click="openShopModal">Create Shop</b-button>
      </b-col>
    </b-row>
    <b-card no-body>
      <!-- Card header -->
      <b-card-header>
        <h3 class="mb-0">{{ title }} <b-button size="sm" variant="info" class="ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></b-button></h3>
      </b-card-header>
      <!-- Light table -->
      <b-table id="index-table" :fields="fields" :items="data" striped show-empty selectable select-mode="single" @row-clicked="selectShop" >
        <template v-slot:cell(e2e)="data">
          {{ data.item.e2e == 1 ? 'Yes' : 'No' }}
        </template>

        <template v-slot:cell(batch)="data">
          {{ data.item.batch == 1 ? 'Yes' : 'No' }}
        </template>

        <template v-slot:cell(action)="data">
          <b-button @click="settingShop(data.item)" variant="primary">Edit</b-button>
          <b-button @click="deleteShop(data.item)" variant="danger">Delete</b-button>
        </template>
      </b-table>

      <!-- Card footer -->
      <b-card-footer class=" py-4" v-if="!retrieving && data.length !== 0">
        <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
      </b-card-footer>

    </b-card>

    <b-modal size="lg" :ref="ref_name.shop" title="Shop details" :header-bg-variant="'primary'" :hide-footer="true" body-class="p-0">
      <template v-slot:modal-header="{ close }">
                <span>
                    <h3 class="">{{selected_shop ? 'Edit Shop' : 'Create Shop'}}</h3>
                    <h4 class="" v-if="selected_shop">{{selected_shop.name}}</h4>
                </span>
        <button type="button" aria-label="Close" class="close" @click="close()">×</button>
      </template>
      <create-shop-component :is_modal="true" @hideModal="hideShopModal"
                             :shop="selected_shop" :user="user" :is_admin="true"></create-shop-component>
    </b-modal>
  </div>
</template>
<script>
export default {
  props: ['user'],
  data() {
    return {
      title: 'Shops',
      request_url: '/web/shops',
      filters: {
        user_id: 0
      },
      fields: [
        { key: 'name', label: 'name', sortable: true },
        { key: 'currency', label: 'currency', sortable: true },
        { key: 'e2e', label: 'e2e', sortable: true },
        { key: 'batch', label: 'batch', sortable: true },
        { key: 'created_at', label: 'created_at', sortable: true },
        { key: 'action', label: 'Action' }
      ],

      retrieving: false,
      data: [],
      pagination: {
        current_page: 1,
        from: 1,
        last_page: 1,
        to: 10,
        total: 0,
      },
      limit: 10,
      list: null,

      selected_shop: 0,
      ref_name: {
        shop: "admin_shop_modal",
        user: "admin_user_modal",
        create_user: "admin_create_user_modal",
      },
      testing: [],
    }
  },
  methods: {
    retrieve: function() {
      if (this.retrieving) {
        return true
      }
      this.retrieving = true
      this.data = [];

      let params = {
        page: this.pagination.current_page,
        limit: this.limit,
        user_id: this.user.id,
      };

      axios.get(this.request_url, {
        params: params
      }).then((response) => {
        let data = response.data;
        if (data.meta.error) {
          notify('top', 'Error', data.meta.message, 'center', 'danger');
        } else {
          this.data = data.response.items;
          this.pagination = data.response.pagination;
          this.testing = data.response.pagination;
          //this.updateList();
        }

        this.retrieving = false
      }).catch((error) => {
        if (error.response && error.response.data && error.response.data.meta) {
          notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
        } else {
          notify('top', 'Error', error, 'center', 'danger');
        }
        this.retrieving = false
      });
    },
    paginate(value, limit) {
      this.pagination = value;
      this.limit = limit
      this.retrieve();
    },

    openShopModal() {
      this.selected_shop = null;
      this.$refs[this.ref_name.shop].show();
    },
    hideShopModal() {
      this.selected_shop = null;
      this.$refs[this.ref_name.shop].hide();
      this.retrieve();
    },
    settingShop(shop) {
      this.$refs[this.ref_name.shop].show();
      this.selected_shop = shop;
    },
    deleteShop(shop) {
      swal({
        title: 'Deleting ' + (shop.name),
        text: "Are you sure? You won't be able to revert this!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel!',
        buttonsStyling: true
      }).then( (isConfirm) => {
        if(isConfirm.value === true) {

          notify('top', 'Info', 'Deleting...', 'center', 'info');

          axios.delete('/web/shops/' + shop.id).then((response) => {
            let data = response.data;
            if (data.meta.error) {
              notify('top', 'Error', data.meta.message, 'center', 'danger');
            } else {
              swal({
                title: 'Success',
                text: 'You have successfully initated the shop deletion!',
                type: 'success',
                buttonsStyling: false,
                confirmButtonClass: 'btn btn-success'
              }).then(() => {
                this.retrieve()
              });
            }
          }).catch((error) => {
            this.saving = false;
            if (error.response && error.response.data && error.response.data.meta) {
              notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
            } else {
              notify('top', 'Error', error, 'center', 'danger');
            }
          });
        }
      });
    },

    selectShop(shop) {
      window.location.href = '/admin/user/' + this.user.id + '/shop/' + shop.id;
    },
  },
  created() {
    this.retrieve();

  },
}
</script>
