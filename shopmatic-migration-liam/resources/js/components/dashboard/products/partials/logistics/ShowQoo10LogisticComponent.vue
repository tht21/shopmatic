<template>
    <div>
        <ul class="list-group list-group-flush list my--3">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0" v-for="logistic in logistics" v-if="logistic.selected">
                {{ logistic.transcName }}
                <span>(${{ logistic.ShippingFee }})</span>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        name: 'ShowQoo10LogisticComponent',
        props: ['logistics'],

        updated () {}
    }
</script>
