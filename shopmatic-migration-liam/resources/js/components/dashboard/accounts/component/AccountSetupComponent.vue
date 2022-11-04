<template>
    <div>
        <h1 class="text-center">Setting up your account</h1>
        <div v-if="Object.keys(actions).length > 0" class="mb-2">
            <div v-for="(action) in actions">
                <span class="font-weight-600 text-capitalize">{{ action.name }}</span>
                <template v-if="action.progress">
                    <b-badge class="mb-1 mx-2" :variant="action.progress.state | stateToVariant">
                        {{action.progress.state}}
                    </b-badge>
                    <b-button v-if="action.progress.state === 'Failed'" class="p-1 badge-button" variant="info"
                              size="sm" @click="accountAction(action.id)">Retry
                    </b-button>
                    <b-progress max="100" height="20px" :animated="true">
                        <b-progress-bar :value="action.progress.value"
                                        :variant="action.progress.state | stateToVariant"
                                        :label=" action.progress.value.toFixed(2) + '%'"></b-progress-bar>
                    </b-progress>
                    <div v-if="action.progress.error" class="bg-danger rounded mb-2 p-2 text-white">
                        {{ action.progress.error }}
                    </div>
                </template>
            </div>
            <hr>
        </div>
        <template v-else>
            <h3 class="text-center">Nothing to setup</h3>
        </template>
    </div>
</template>

<script>
    export default {
        name: "AccountSetupComponent",
        props: ['account', 'currectIndex'],
        filters: {
            stateToVariant(state) {
                if (state === 'Waiting') return 'secondary';
                else if (state === 'Processing') return 'primary';
                else if (state === 'Success') return 'success';
                else if (state === 'Failed') return 'danger';
                else return '';
            }
        },
        watch: {
            is_proccess_finish() {
                if (this.is_proccess_finish) {
                    setTimeout(() => {
                        this.$emit('onChange', this.currectIndex, this.currectIndex + 1);
                    }, 500)
                }
            },
        },
        data() {
            return {
                is_proccess_finish: false,
                actions: [],
                retrieving: false,
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
                this.actions = [];
                axios.get('/web/accounts/' + this.account.id + '/setup', {}).then((response) => {
                    let data = response.data;
                    if (data.meta.error) {
                        notify('top', 'Error', data.meta.message, 'center', 'danger');
                    } else {
                        let actions = data.response;

                        if (Object.keys(actions).length > 0) {
                            Object.keys(actions).map((key) => {

                                this.actions.push({
                                    id: key,
                                    name: actions[key],
                                    progress: {
                                        state: 'Waiting',
                                        value: 0,
                                    },
                                })
                                this.accountAction(key);
                            })
                        } else {
                            this.is_proccess_finish = true;
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
                });
            },
            accountAction(action) {

                let index = this.indexOfActions(action);

                this.actions[index]['isLoadProgress'] = true;
                this.updateActionData(action, 'Processing', 0)

                axios.get('/web/accounts/' + this.account.id + '/setup/' + action, {}).then((response) => {
                    let data = response.data;
                    this.actions[index]['isLoadProgress'] = false;
                    if (data.meta.error) {
                        this.updateActionData(action, 'Failed', 0, data.meta.error)
                    } else {
                        this.updateActionData(action, 'Success', 100)
                    }
                }).catch((error) => {

                    let message = '';

                    if (error.response && error.response.data && error.response.data.meta) {
                        message = error.response.data.meta.message;
                    } else {
                        message = error;
                    }
                    this.actions[index]['isLoadProgress'] = false;
                    this.updateActionData(action, 'Failed', 100, message)
                });

            },
            updateActionData(action, state, value, error = null) {

                let index = this.indexOfActions(action)

                this.actions[index]['progress'] = {
                    state: state,
                    value: value,
                    error: error,
                };

                this.updateSaveButton();
                if (!this.actions[index].isLoadProgress) {
                    return
                }

                setTimeout(() => {
                    if (this.actions[index].progress.state === 'Processing' && this.actions[index].progress.value < 80) {
                        this.updateActionData(action, state, value + (Math.random() * 5), error)
                    }
                }, 1000);
            },
            updateSaveButton() {

                let result = Object.keys(this.actions).filter((key) => {
                    return this.actions[key].progress.state === 'Processing' || this.actions[key].progress.state === 'Waiting';
                });
                this.is_proccess_finish = result.length <= 0;
            },
            indexOfActions(id) {
                return this.actions.findIndex(x => x.id === id);
            }
        }
    }
</script>

<style scoped>

</style>
