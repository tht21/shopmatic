<template>
    <div>
        <b-row class="pb-2">
            <b-col md="3" :class="[!isMobile() ? 'chat' : '']">
                <chat-channel-component @selectChannel="selectChannel"></chat-channel-component>
            </b-col>
            <b-col md="5" class="chat">
                <chat-room-component :id="id" :select_question="select_question"></chat-room-component>
            </b-col>
            <b-col md="4" :class="[!isMobile() ? 'chat' : '']">
                <b-row :class="[!isMobile() ? 'h-50' : 'my-2']">
                    <b-col class="h-100">
                        <chat-details-component :id="id"></chat-details-component>
                    </b-col>
                </b-row>
                <b-row :class="[!isMobile() ? 'h-50 pt-3' : 'my-2']">
                    <b-col class="h-100">
                        <chat-faq-component @selectQuestion="selectQuestion"></chat-faq-component>
                    </b-col>
                </b-row>
            </b-col>
        </b-row>
    </div>
</template>

<script>
    import ChatChannelComponent from "./components/ChatChannelComponent";
    import ChatRoomComponent from "./components/ChatRoomComponent";
    import ChatDetailsComponent from "./components/ChatDetailsComponent";
    import ChatFaqComponent from "./components/ChatFaqComponent";

    export default {
        name: "ChatIndexComponent",
        components: {ChatFaqComponent, ChatDetailsComponent, ChatRoomComponent, ChatChannelComponent},
        data() {
            return {
                id: null,
                select_question: null,
            }
        },
        methods: {
            selectChannel(id) {
                this.id = id
            },
            selectQuestion(question) {
                this.select_question = question;
            },
            isMobile() {
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    return true
                } else {
                    return false
                }
            }
        }
    }
</script>

<style scoped>
    .chat {
        max-height: calc(100vh - 14rem);
        min-height: calc(100vh - 15rem);
    }
</style>
