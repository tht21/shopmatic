<template>
    <div v-if="data">
        <b-row v-if="hideHeader" class="text-center py-3">
            <b-col md="12">
                <h3>{{data.datetime | formatHeaderDate}}</h3>
            </b-col>
        </b-row>
        <b-row :class="data.is_me ? 'text-right' : 'text-left'">
            <b-col md="12">
                <img v-if="hideImage()" height="40px" :src="data.image"
                     class="rounded-circle float-left">
                <div v-else-if="!data.is_me" class="float-left" style="width: 40px; height: 40px;"></div>
                <div class="d-inline-block w-75 pl-2">
                    <label :class="['text-white py-2 px-3 my-1', data.is_me ? 'bg-primary' : 'bg-info']"
                           style="white-space: pre-line; border-radius: 15px; max-width: 75%">{{data.message}}</label>
                    <h5 v-if="hideDateTime()" class="px-2">{{data.datetime | formatDate}}</h5>
                </div>
            </b-col>
        </b-row>
    </div>
</template>

<script>
    export default {
        name: "ChatMessageComponent",
        props: {
            data: {
                type: Object,
                default: null,
            },
            prev_data: {
                type: Object,
                default: null,
            },
            next_data: {
                type: Object,
                default: null,
            },
            hideHeader: {
                type: Boolean,
                default: false,
            }
        },
        filters: {
            formatHeaderDate: function (date) {
                if (moment().isSame(date, 'day')) {
                    return moment(date).format('[Today], h:mm a');
                }
                return moment(date).format('Do MMMM YYYY, h:mm a');
            },
            formatDate: function (date) {
                if (moment().isSame(date, 'day')) {
                    return moment(date).format('[Today], h:mm a');
                }
                return moment(date).format('Do MMMM YYYY, h:mm a');
            },
        },
        methods: {
            hideDateTime() {
                let status = false;
                let data = this.data;
                let next_data = this.next_data;
                if (!next_data) {
                    status = true;
                } else if (data.is_me != next_data.is_me) {
                    status = true
                } else if (!moment(data.datetime).isSame(next_data.datetime, 'day')) {
                    status = true
                } else if (!moment(data.datetime).isSame(next_data.datetime, 'minute')) {
                    status = true
                }
                return status;
            },
            hideImage() {
                let status = false;
                let data = this.data;
                let prev_data = this.prev_data;
                if (!data.is_me) {
                    if (!prev_data) {
                        status = true;
                    } else if (prev_data.is_me != data.is_me) {
                        status = true;
                    }
                }
                return status;
            }
        }
    }
</script>

<style scoped>

</style>
