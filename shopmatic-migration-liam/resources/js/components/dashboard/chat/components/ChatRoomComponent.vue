<template>
    <b-card no-body class="h-100">
        <template v-if="data">
            <b-card-header class="py-3 align-items-center">
                <img height="40px" :src="data.image" class="rounded-circle align-middle">
                <h3 class="text-center d-inline-block pl-2">{{data.name}}</h3>
            </b-card-header>
            <b-card-body class="position-relative overflow-auto py-0 scroll">
                <chat-message-component v-for="(item, index) in data.messages"
                                        v-bind:key="'message-'+index" :data="item"
                                        :next_data="data.messages[index + 1]"
                                        :prev_data="data.messages[index - 1]"
                                        :hide-header="hideHeader(index, item.datetime)"></chat-message-component>
            </b-card-body>
            <b-card-footer class="py-2">
                <div>
                    <b-form-textarea v-model.trim="message"
                                     placeholder="Type something here..."
                                     rows="1"
                                     max-rows="6"
                                     @keyup.enter.native="sendMessage"
                    />
                </div>
            </b-card-footer>
        </template>
    </b-card>
</template>

<script>
    import ChatMessageComponent from "./ChatMessageComponent";
    import InputFieldComponent from "../../../utility/InputFieldComponent";

    export default {
        name: "ChatRoomComponent",
        components: {InputFieldComponent, ChatMessageComponent},
        props: {
            id: {
                type: Number,
                default: null,
            },
            select_question: {
                type: String,
                default: null,
            },
        },
        data() {
            return {
                request_url: '/web/chat/',
                retrieving: false,
                data: null,
                message: null,
            }
        },
        watch: {
            id() {
                this.data = null;
                this.header_datetime = null;
                if (this.id != null) {
                    this.retrieve();
                }
            },
            select_question() {
                this.message = this.select_question;
            },
        },
        created() {
            this.header_datetime = null;
        },
        updated() {
            this.scrollToEnd();
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                axios.get(this.request_url + this.id, {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        if (data.response) {
                            this.data = data.response;
                        }
                    }
                    this.retrieving = false;
                }).catch((error) => {
                    this.retrieving = false;
                    if (error.response && error.response.data && error.response.data.meta) {
                        notify('top', 'Error', error.response.data.meta.message, 'center', 'danger');
                    } else {
                        notify('top', 'Error', error, 'center', 'danger');
                    }
                })
            },
            hideHeader(index, datetime) {
                let status = false;
                if (index == 0) {
                    this.header_datetime = null;
                }
                if (!this.header_datetime) {
                    status = true;
                } else if (!moment(this.header_datetime).isSame(datetime, 'day')) {
                    status = true;
                }
                if (status) {
                    this.header_datetime = datetime
                }
                return status;
            },
            scrollToEnd() {
                let container = document.querySelector(".scroll");
                container.scrollTop = container.scrollHeight
            },
            sendMessage(event) {
                if (event.code === "Enter" && event.shiftKey) {
                    return
                }
                let message = this.message
                if (message) {
                    this.data.messages.push({
                        'message': message,
                        'image': null,
                        'datetime': moment(),
                        'is_me': true
                    });
                }
                this.message = null;
            }
        },
    }
</script>

<style scoped>

</style>
