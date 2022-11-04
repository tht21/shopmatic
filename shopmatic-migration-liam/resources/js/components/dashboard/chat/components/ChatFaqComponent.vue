<template>
    <b-card no-body :class="[!isMobile() ? 'h-100' : 'my-2']">
        <template>
            <b-card-header class="p-2 text-center">
                <b-button v-if="isMobile()" block href="#" v-b-toggle.faq-details-accordion variant="primary">Client
                    Details
                </b-button>
                <b-collapse id="faq-details-accordion" :visible="!isMobile()">
                    <h1>How can we help you?</h1>
                    <b-col v-if="!selected_category">
                        <h3>Choose a category to find the help you need</h3>
                    </b-col>
                    <b-col v-else class="pt-1">
                        <b-input-group>
                            <b-input-group-prepend is-text @click="selectBack"><i class="fas fa-arrow-left"></i>
                            </b-input-group-prepend>
                            <b-form-input
                                id="search-input"
                                v-model:sync="search"
                                placeholder="Search for help by keywords"
                                name="search-input"
                            />
                            <b-input-group-append>
                                <b-button variant="primary" @click="clickSearch">Search for help</b-button>
                            </b-input-group-append>
                        </b-input-group>
                    </b-col>
                </b-collapse>
            </b-card-header>
            <b-collapse class="position-relative overflow-auto p-0" id="faq-details-accordion"
                        :visible="!isMobile()">
                <b-card-body :class="['p-0 mt-3', isMobile() ? 'card-body-height' : '']">
                    <b-col>
                        <b-row v-if="!selected_category">
                            <b-col md="4" class="mb-3" v-for="(item, index) in data" v-bind:key="'faq-'+index">
                                <div class="border border-light rounded text-center" @click="selectCategory(item)">
                                    <img width="75%" :src="item.icon" class="rounded-circle">
                                    <h3>{{item.name}}</h3>
                                </div>
                            </b-col>
                        </b-row>
                        <b-row v-else>
                            <b-col md="4" class="text-center" v-for="(item, index) in filtered_answear"
                                   v-bind:key="'questions-'+index">
                                <h3>{{item.name}}</h3>
                                <b-link v-for="(question, i) in item.questions" @click="selectQuestion(question)"
                                        v-bind:key="'questions-'+index+'-question-'+i">
                                    <h4>{{question}}</h4>
                                </b-link>
                            </b-col>
                        </b-row>
                    </b-col>
                </b-card-body>
            </b-collapse>
        </template>
    </b-card>
</template>

<script>
    export default {
        name: "ChatFaqComponent",
        data() {
            return {
                request_url: '/web/chat/faq/category',
                data: null,
                selected_category: null,
                filtered_answear: null,
                search: null,
            }
        },
        created() {
            this.retrieve();
        },
        methods: {
            retrieve() {
                if (this.retrieving) {
                    return;
                }
                this.retrieving = true;
                axios.get(this.request_url, {}).then((response) => {
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
            selectCategory(category) {
                this.selected_category = category;
                this.filtered_answear = category.items
            },
            selectBack() {
                this.selected_category = null;
            },
            selectQuestion(question) {
                this.selected_category = null;;
                this.$emit('selectQuestion', question);
            },
            clickSearch() {

                let items = this.selected_category.items;
                let data = items;
                let search = this.search;
                if (search) {
                    data = [];
                    data = items.filter((d) => {
                        return d.name.toLowerCase().includes(search.toLowerCase());
                    });
                }

                this.filtered_answear = data;
            },
            isMobile() {
                if (/Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                    return true
                } else {
                    return false
                }
            },
        }
    }
</script>

<style scoped>
    .card-body-height {
        min-height: 25px;
        max-height: 500px
    }
</style>


