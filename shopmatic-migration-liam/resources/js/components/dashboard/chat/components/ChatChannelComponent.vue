<template>
    <b-card no-body :class="[!isMobile() ? 'h-100' : 'my-2']">
        <b-card-header class="p-2">
            <b-button v-if="isMobile()" block href="#" v-b-toggle.chat-room-accordion variant="primary">Chat Rooms</b-button>
            <b-collapse id="chat-room-accordion" :visible="!isMobile()">
                <div :class="['p-0', isMobile() ? 'pt-3' : '']">
                    <b-row>
                        <b-col md="12">
                            <b-form-input
                                id="search-input"
                                v-model:sync="search"
                                placeholder="Search"
                                name="search-input"
                            />
                        </b-col>
                        <b-col md="12">
                            <h3 class="font-weight-light text-muted pt-2 px-1 mb-0">Integration </h3>
                            <span
                                v-bind:class="'badge mr-1 cursor-pointer px-3 py-2 mt-1 noselect ' + (search ? 'badge-disabled' : !account.disabled ? 'badge-primary' : 'badge-disabled')"
                                v-for="(account, index) in accounts" @click="toggleIntegration(index)">{{ account.integration.name }}</span>
                        </b-col>
                    </b-row>
                </div>
            </b-collapse>
        </b-card-header>
        <b-collapse class="position-relative overflow-auto p-0" id="chat-room-accordion" :visible="!isMobile()">
            <b-card-body :class="['p-0', isMobile() ? 'card-body-height' : '']">
                <ul class="list-group list-group-flush px-0 my-2">
                    <li v-for="(item, index)  in data"
                        :class="['list-group-item align-items-center d-flex p-3', item.id === select_channel ? 'active' : '']"
                        @click="selectChannel(item.id)">
                        <img width="15%" :src="item.image"
                             class="rounded-circle float-left">
                        <div class="d-inline-block w-80 pl-2 text-overflow">
                            <h4 class="mb-0">{{item.name}}</h4>
                            <small>{{item.is_me ? 'you' : item.name}}:
                                {{item.messages.slice(-1)[0].message}}</small><br>
                            <small>{{item.messages.slice(-1)[0].datetime | formatDate}}</small>
                        </div>
                    </li>
                </ul>
            </b-card-body>
        </b-collapse>
    </b-card>
</template>

<script>
    export default {
        name: "ChatChannelComponent",
        filters: {
            formatDate: function (date) {
                return moment(date).format('Do MMMM YYYY, h:mm:ss a');
            },
        },
        data() {
            return {
                request_url: '/web/chat',
                request_accounts_url: '/web/accounts',
                retrieving: false,
                data: null,
                select_channel: null,
                search: null,
                accounts: [],
            }
        },
        created() {
            this.retrieveAccounts();
        },
        watch: {
            search(data) {
                setTimeout(() => {
                    if (data == this.search) {
                        this.retrieve();
                    }
                }, 500)
            },
            data() {
                let data = this.data
                if (data.length > 0) {
                    this.selectChannel(data[0].id)
                } else {
                    this.selectChannel(null)
                }
            },
            select_channel() {
                this.$emit('selectChannel', this.select_channel)
            }
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                let filtered = null;
                this.accounts.forEach((account) => {
                    if (!account.disabled) {
                        if (filtered) {
                            filtered += "," + account.integration.name;
                        } else {
                            filtered = account.integration.name
                        }
                    }
                });

                let parameters = {
                    search: this.search,
                    filtered: filtered,
                };
                axios.get(this.request_url, {
                    params: parameters
                }).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.data = data.response.items;
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
            retrieveAccounts() {
                axios.get(this.request_accounts_url, {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        this.accounts = data.response.items;
                        this.retrieve()
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
            selectChannel(id) {
                this.select_channel = id
            },
            toggleIntegration(index) {
                if (!this.search) {
                    let account = this.accounts[index];
                    account.disabled = !account.disabled;
                    this.retrieve()
                }
            },
            isMobile() {
                if (/Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    return true
                } else {
                    return false
                }
            }
        }
    }
</script>

<style scoped>
    .text-overflow {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .card-body-height {
        min-height: 25px;
        max-height: 500px
    }
</style>
