<template>
    <div>
        <a class="btn btn-sm btn-neutral" :href="this.url + '?type=' + this.url_type">Past Exports</a>
    </div>
</template>

<script>
    export default {
        name: "ExportTaskComponent",
        props: [
            'global', 'url'
        ],
        data() {
          return {
              url_type: ""
          }
        },
        watch: {
            global(val) {
                this.url_type = (val == 2) ? 'excel' : '';
            }
        }
    }
</script>

<style scoped>

</style>
