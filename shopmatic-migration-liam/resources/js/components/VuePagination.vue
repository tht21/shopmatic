<template>
    <ul class="pagination justify-content-end mb-0">
        <li :class="{'disabled': isOnFirstPage}" class="page-item">
            <a href="" @click.prevent="loadPage('prev')" class="page-link" tabindex="-1">
                <i class="fas fa-angle-left"></i>
                <span class="sr-only">Previous</span>
            </a>
        </li>

        <template v-if="notEnoughPages">
            <li v-for="n in totalPage" :class="{'active': isCurrentPage(n)}" class="page-item">
                <a href="#" @click.prevent="loadPage(n)" v-html="n" class="page-link"></a>
            </li>
        </template>
        <template v-else>
            <li v-for="n in windowSize" :class="{'active': isCurrentPage(windowStart+n-1)}" class="page-item">
                <a href="#" @click.prevent="loadPage(windowStart+n-1)" v-html="windowStart+n-1" class="page-link"></a>
            </li>
        </template>

        <li :class="{'disabled': isOnLastPage}" class="page-item">
            <a href="" @click.prevent="loadPage('next')" class="page-link">
                <i class="fas fa-angle-right"></i>
                <span class="sr-only">Next</span>
            </a>
        </li>
    </ul>
</template>

<script>
    export default {
        name: 'VuePagination',
        props: {
            onEachSide: {
                type: Number,
                default () {
                    return 2
                }
            },
            pagination : {},
        },
        data() {
            return {

            }
        },
        computed: {
            totalPage () {
                return this.pagination === null
                    ? 0
                    : this.pagination.last_page
            },
            isOnFirstPage () {
                return this.pagination === null
                    ? false
                    : this.pagination.current_page === 1
            },
            isOnLastPage () {
                return this.pagination === null
                    ? false
                    : this.pagination.current_page === this.pagination.last_page
            },
            notEnoughPages () {
                return this.totalPage < (this.onEachSide * 2) + 4
            },
            windowSize () {
                return this.onEachSide * 2 +1;
            },
            windowStart () {
                if (!this.pagination || this.pagination.current_page <= this.onEachSide) {
                    return 1
                } else if (this.pagination.current_page >= (this.totalPage - this.onEachSide)) {
                    return this.totalPage - this.onEachSide*2
                }

                return this.pagination.current_page - this.onEachSide
            },
        },
        methods: {
            loadPage (page) {
                this.$emit('change-page', page)
            },
            isCurrentPage (page) {
                return page === this.pagination.current_page
            },
            setPaginationData (pagination) {
                this.pagination = pagination
            },
            resetData () {
                this.pagination = null
            }
        },
    }
</script>
