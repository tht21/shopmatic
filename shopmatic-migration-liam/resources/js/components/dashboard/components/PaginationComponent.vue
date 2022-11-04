<template>
    <div>
        <nav v-show="pagination.last_page > 1">
            <ul class="pagination justify-content-center mb-0">
                <li v-show="pagination.current_page > 2" class="page-item">
                    <a class="page-link" @click="changePage(1)" tabindex="-1">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
                <li v-show="pagination.current_page > 1" class="page-item">
                    <a class="page-link" @click="changePage(pagination.current_page - 1)" tabindex="-1">
                        <i class="fas fa-angle-left"></i>
                    </a>
                </li>
                <li v-show="pagination.current_page > 1" class="page-item">
                    <a class="page-link" @click="changePage(pagination.current_page - 1)">{{ pagination.current_page - 1
                        }}</a>
                </li>
                <li class="page-item active">
                    <a class="page-link" href="#!">{{ pagination.current_page }}</a>
                </li>
                <li v-show="pagination.current_page + 1 <= pagination.last_page" class="page-item">
                    <a class="page-link" @click="changePage(pagination.current_page + 1)">{{ pagination.current_page + 1
                        }}</a>
                </li>
                <li v-show="pagination.current_page < pagination.last_page" class="page-item">
                    <a class="page-link" @click="changePage(pagination.current_page + 1)">
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
                <li v-show="pagination.current_page + 1 < pagination.last_page" class="page-item">
                    <a class="page-link" @click="changePage(pagination.last_page)">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="text-center mt-2">
            <small class="text-uppercase">{{ pagination.total }} total. Last Page: {{ pagination.last_page }}</small>
        </div>
        <div class="float-right ml-auto">
            <b-row>
                <b-col>
                    <b-form-group :class="customClass && customClass.limit ? customClass.limit : ''" label="Limit" label-for="limit-input">
                        <select id="limit-input" v-model="limit" name="limit-input" class="form-control d-inline-block" style="width: 100px" @change="changeLimit(limit)">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group :class="customClass && customClass.jump_to ? customClass.jump_to : ''" label="Jump To" label-for="jump-to-input">
                        <b-form-input
                            id="jump-to-input"
                            v-model="jump_to"
                            placeholder="Page"
                            @change="changePage(jump_to)"
                            name="jump-to-input"
                            class="form-control d-inline-block"
                            style="width: 100px"
                        />
                    </b-form-group>
                </b-col>
            </b-row>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'PaginationComponent',
        props: ['details', 'limit', 'customClass'],
        data() {
            return {
                jump_to: '',
                pagination: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    to: 10,
                    total: 10,
                    limit: 5,
                }
            }
        },

        watch: {
            details(newValue, oldValue) {
                this.pagination = newValue
            }
        },

        mounted() {
            this.pagination.current_page = this.details.current_page;
            this.pagination.from = this.details.from;
            this.pagination.last_page = this.details.last_page;
            this.pagination.to = this.details.to;
            this.pagination.total = this.details.total;
        },

        methods: {
            changePage: function (page) {
                if (page < 1) {
                    page = 1;
                } else if (page > this.pagination.last_page) {
                    page = this.pagination.last_page;
                }
                this.pagination.current_page = page;

                this.$emit('paginated', this.pagination, this.limit)
            },
            changeLimit: function (limit) {
                this.limit = limit;
                this.pagination.current_page = 1;
                this.jump_to = '';
                this.$emit('paginated', this.pagination, this.limit)
            }
        }
    }
</script>
