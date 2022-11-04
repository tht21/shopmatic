<template>
    <div>
        <b-button class="btn btn-sm btn-neutral" :pressed.sync="toggle">Add New</b-button>
    </div>
</template>

<script>
    export default {
        name: "CreateShopCompositeComponent",
        props: {
            shop: {
                type: Object,
                default: null,
            },
        },
        data() {
            return {
                toggle: false,
            }
        },
        watch: {
            toggle() {
                if (this.toggle) {
                    this.$emit('update', {
                        toggle: this.toggle
                    })
                    this.toggle = !this.toggle;
                }
            },
            global() {
                if (typeof this.global.toggle != 'undefined' && !this.global.toggle) {
                    this.toggle = false;
                }
            }
        },
    }
</script>

<style scoped>

</style>
