<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">All Accounts<button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button></h3>
        </div>
        <div class="card-body">
            <div class="card" v-for="account in data">
                <!-- Card body -->
                <div class="card-body">
                    <div class="row justify-content-between align-items-center">
                        <div class="col">
                            <img src="#" alt="Image placeholder">
                        </div>
                        <div class="col-auto">
                            <span class="badge badge-lg badge-success">Active</span>
                        </div>
                    </div>
                    <div class="my-4">
                <span class="h6 surtitle text-muted">
                  PayPal E-mail
                </span>
                        <div class="h1">john.snow@gmail.com</div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <span class="h6 surtitle text-muted">Name</span>
                            <span class="d-block h3">John Snow</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer py-4 text-center text-muted text-uppercase">
            {{ data.length }} account(s)
        </div>
    </div>
</template>
<script>
    export default {
        name: "ShopComponent",
        props: [
            'request_url'
        ],
        data() {
            return {
                data: [],
                list: null,
            }
        },
        methods: {
            retrieve: function() {
                let ctx = this;
                ctx.data = [];
                axios.get(this.request_url, {
                    params: this.parameters
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.data = data.response.items;
                        ctx.updateList();
                    }
                }).catch(function (error) {
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                });
            },
            updateList: function() {
                if (this.data.length) {
                    if (!this.list) {
                        let options = {
                            valueNames: this.fields,
                        };
                        this.list = new List('index-table', options);
                    } else {
                        this.list.reIndex();
                    }
                }
            }
        },
        updated() {
            this.updateList();
        },
        created() {
            this.retrieve();

            if (this.auto_refresh > 0) {
                this.interval = setInterval(() => {
                    this.retrieve();
                }, this.auto_refresh);
            }

        },
    }
</script>