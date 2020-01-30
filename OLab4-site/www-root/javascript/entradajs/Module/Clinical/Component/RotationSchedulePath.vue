<template>
    <div class="rotation-schedule-path space-below">
        <div id="rotation_path" class="content-small muted" v-html="path"></div>
    </div>
</template>

<script>
    Array.prototype.filter = window.filter;
    const RestClient = use('EntradaJS/Http/RestClient');

    module.exports = {
        name: 'rotation-schedule-path',
        props: {
            course_id: {
                type: Number,
                required: true
            },
            cperiod_id: {
                type: Number,
                required: true
            }
        },
        data () {
            return {
                path: "",
                parameters: {
                    "course_id": 0,
                    "cperiod_id": 0
                }
            }
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.parameters.course_id = this.course_id;
            this.parameters.cperiod_id = this.cperiod_id;

            this.fetchPath();
        },
        methods: {
            fetchPath() {
                this.api.get('/clinical/rotation-schedule-path/' , this.parameters).then(response => {
                    this.path = response.json()["path"];
                });
            },
        },
    };
</script>

<style>
</style>