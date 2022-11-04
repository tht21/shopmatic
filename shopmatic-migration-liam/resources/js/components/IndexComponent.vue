<template>
    <div class="card">
        <!-- Card header -->
        <div class="card-header border-0">
            <h3 class="mb-0">{{ title}} <button class="btn btn-sm btn-info ml-3" @click="retrieve"><i class="fa fa-sync-alt"></i></button></h3>
        </div>
        <!-- Light table -->
        <div id="index-table" class="table-responsive">
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                <tr>
                    <th :class="'sort ' + (index === 0 ? 'desc' : '')" v-for="(header, index) in headers" :data-sort="fields[index]">{{ header }}</th>
                </tr>
                </thead>
                <tbody class="list">
                    <tr v-for="item in data">
                        <td v-for="field in fields" :class="field">
                            <template v-if="field === 'messages'">
                                <div style="max-height: 200px; overflow-y: scroll; white-space: break-spaces">{{ item[field] }}</div>
                            </template>
                            <template v-else>{{ item[field] }}</template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Card footer -->
        <div class="card-footer py-4">
            <pagination-component :details="pagination" :limit="limit" @paginated="paginate"></pagination-component>
        </div>
    </div>
</template>
<script>
    export default {
        name: "IndexComponent",
        props: [
            'title', 'request_url', 'parameters', 'headers', 'fields', 'auto_refresh'
        ],
        data() {
            return {
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
            }
        },
        methods: {
            retrieve: function() {
                let ctx = this;
                ctx.data = [];

                let params = this.parameters;
                if(!params) {
                    params = {};
                }
                params['page'] = this.pagination.current_page;
                params['limit'] = this.limit;

                axios.get(this.request_url, {
                    params: params
                }).then(function (response) {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        ctx.data = data.response.items;
                        ctx.pagination = data.response.pagination;
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
            },
            paginate(value, limit) {
                this.pagination = value;
                this.limit = limit
                this.retrieve();
            },
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
