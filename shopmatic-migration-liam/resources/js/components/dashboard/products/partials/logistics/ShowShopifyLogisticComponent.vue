<template>
    <!--<div>
        <ul class="list-group list-group-flush list my&#45;&#45;3">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0" v-for="logistic in logistics" v-if="logistic.selected">
                {{ logistic.logistic_name }}
                <span>(${{ logistic.shipping_fee }})</span>
            </li>
        </ul>
    </div>-->
</template>

<script>
    export default {
        name: 'ShowShopifyLogisticComponent',
        props: ['logistics'],

        updated () {}
    }
</script>
