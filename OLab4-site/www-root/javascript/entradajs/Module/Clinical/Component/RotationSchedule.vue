<template>
    <div class="published-schedules">
        <clinical-tabs href="rotationschedule"></clinical-tabs>
        <div class="clearfix">
            <div class="span8">
                <h2>Published Schedules</h2>
            </div>
            <div class="span4">
                <div class="space-above">
                    <cohort-select v-model="parameters.cperiod" @change="fetchPublishedSchedules()"></cohort-select>
                </div>
            </div>
        </div>
        <div class="search-bar" class="search-bar clearfix" >
            <input type="text" placeholder="Search" class="input-large search-icon pull-left" v-model="parameters.search" @keyup.enter="fetchPublishedSchedules" v-i18n />
            <a :href="drafts" class="btn pull-right"><i class="fa fa-file"></i> Manage My Drafts</a>
        </div>
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th width="5%"></th>
                <th width="40%" v-i18n>Title</th>
                <th width="35%" v-i18n>Course</th>
            </tr>
            </thead>
            <tbody>
                <tr v-show="loading">
                    <td colspan="3"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                </tr>
                <tr v-show="!loading"  v-for="publishedSchedule in publishedSchedules">
                    <td><input type="checkbox" :value="publishedSchedule.id" v-model="selected_published_rotations" /></td>
                    <td><a :href="generateEditPath(publishedSchedule.id)"> {{ publishedSchedule.draft_title }}</a></td>
                    <td><a :href="generateEditPath(publishedSchedule.id)">{{ publishedSchedule.course_name }}</a></td>
                </tr>
                <tr v-show="!loading" v-if="publishedSchedules.length == 0">
                    <td colspan="3" v-i18n>There are no rotation schedules in the system</td>
                </tr>
            </tbody>
        </table>
        <modal v-if="show_modal" title="Withdraw Rotation Schedule" v-on:ok="withdrawRotation" v-on:hide="show_modal = false" savebutton="Withdraw">
            <slot>
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="40%" v-i18n>Title</th>
                        <th width="35%" v-i18n>Course</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-show="loading">
                        <td colspan="3"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                    </tr>
                    <tr v-show="!loading"  v-for="selected_rotation in selected_rotations_withdraw">
                        <td>{{ selected_rotation.draft_title}} </td>
                        <td>{{ selected_rotation.course_name }}</td>
                    </tr>
                    <tr v-show="!loading" v-if="selected_rotations_withdraw.length == 0">
                        <td colspan="3" v-i18n>There are no selected rotation schedules</td>
                    </tr>
                    </tbody>
                </table>
            </slot>
        </modal>
        <button @click="show_modal = true" class="btn btn-danger pull-left" v-if="!this.loading && selected_published_rotations.length > 0"><i class="fa fa-trash"></i> Withdraw Rotation Schedule</button>
    </div>
</template>

<script>
    const CohortSelect = use('./CohortSelect.vue');
    const RestClient = use('EntradaJS/Http/RestClient');
    const ClinicalTabs = use('./Clinical.vue');
    const Modal = use('./Modal.vue');

    module.exports = {
        name: 'published-schedules',
        props: ['title'],
        data() {
            return {
                publishedSchedules: [],
                loading: true,
                parameters: {
                    search: "",
                    cperiod: "0",
                    status: "live",
                },
                show_modal: false,
                selected_published_rotations: [],
                drafts: "#" + this.$generatePath('clinical.rotationschedule.drafts'),
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
        },
        computed: {
            selected_rotations_withdraw () {
                return this.publishedSchedules.filter(o => this.selected_published_rotations.includes(o.id));
            }
        },
        methods: {
            fetchPublishedSchedules() {
                this.loading = true;
                this.api.get('/clinical/draft-rotation-schedule', this.parameters).then(response => {
                    this.publishedSchedules = response.json();
                    this.loading = false;
                });
            },
            generateEditPath(id) {
                return '#' + this.$generatePath('clinical.rotationschedule.drafts.edit', { draft_id : id});
            },
            withdrawRotation() {
                this.api.put('/clinical/draft-rotation-schedule/change-status/' + this.selected_published_rotations, {status: "draft"}).then(result => {
                    this.fetchPublishedSchedules();
                    this.show_modal = false;
                });
            },
        },
        components: {
            CohortSelect,
            ClinicalTabs,
            Modal
        }
    };
</script>

<style>
</style>