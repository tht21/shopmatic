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
    import VuetablePaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin'

    export default {
        mixins: [VuetablePaginationMixin]
    }
</script>
