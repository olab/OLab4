<template>
    <div class="published-schedules">
        <clinical-tabs href="rotationschedule"></clinical-tabs>

        <div class="clearfix">
            <div class="span8">
                <h2>My Drafts</h2>
            </div>
            <div class="span4">
                <div class="space-above">
                    <cohort-select v-model="parameters.cperiod" @change="fetchDraftSchedules()"></cohort-select>
                </div>
            </div>
        </div>

        <div class="search-bar" class="search-bar clearfix space-above" >
            <input type="text" placeholder="Search" class="input-large search-icon pull-left" v-model="parameters.search" @keyup.enter="fetchDraftSchedules" v-i18n />
            <button @click="show_modal = true" class="btn btn-success pull-right"><i class="fa fa-plus-circle"></i> New Draft</button>
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
                <tr v-show="!loading"  v-for="(draftSchedule, index) in draftSchedules">
                    <td><input type="checkbox" name="remove_ids[]" :value="draftSchedule.id" v-model="selected_drafts" /></td>
                    <td><a :href="generateDraftPath(draftSchedule.id)"> {{ draftSchedule.draft_title }}</a></td>
                    <td><a :href="generateDraftPath(draftSchedule.id)">{{ draftSchedule.course_name }}</a></td>
                </tr>
                <tr v-show="!loading" v-if="draftSchedules.length == 0">
                    <td colspan="3" v-i18n>There are no rotation schedules in the system</td>
                </tr>
            </tbody>
        </table>
        <modal v-if="show_modal" title="New Draft" v-on:ok="saveDraft" v-on:hide="resetState()">
            <slot>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.draft_title }">
                    <label for="draft-title" class="control-label form-required">Draft Title</label>
                    <div class="controls">
                        <input id="draft-title" v-model="draft.draft_title" type="text" class="span8" />
                        <span class="help-block" v-if="validation_errors.draft_title"> {{validation_errors.draft_title[0]}} </span>
                    </div>
                </div>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.course_id }">
                    <label for="course-id" class="control-label form-required">Course</label>
                    <div class="controls">
                        <select id="course-id" v-model="draft.course_id" class="span8">
                            <option value="0">Please select a course</option>
                            <option v-for="course in courses" :value="course.id">
                                {{ course.title }}
                            </option>
                        </select>
                        <span class="help-block" v-if="validation_errors.course_id"> {{validation_errors.course_id[0]}} </span>
                    </div>
                </div>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.cperiod_id }">
                    <label for="cperiod-id" class="control-label form-required">Curriculum Period</label>
                    <div class="controls">
                        <select id="cperiod-id" v-model="draft.cperiod_id" class="span8">
                            <option value="0">Please select a curriculum period</option>
                            <optgroup v-for="(curriculum_period, curriculum_layout) in cohorts" :label="curriculum_layout">
                                <option v-for="period in curriculum_period" :value="period.cperiod_id">
                                    {{ period.display_title }}
                                </option>
                            </optgroup>
                        </select>
                        <br><span class="help-block" v-if="validation_errors.cperiod_id"> {{validation_errors.cperiod_id[0]}} </span>
                    </div>
                </div>
            </slot>
        </modal>

        <button @click="show_publish_modal = true" class="btn btn-primary pull-right" v-if="!this.loading && selected_drafts.length > 0"><i class="fa fa-trash"></i> Publish</button>

        <modal v-if="show_publish_modal" title="Publish Draft Schedule" v-on:ok="publishDraft" v-on:hide="show_publish_modal = false" savebutton="Publish">
            <slot>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="40%" v-i18n>Draft</th>
                            <th width="35%" v-i18n>Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-show="loading">
                            <td colspan="3"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                        </tr>
                        <tr v-show="!loading"  v-for="selected_draft in selected_drafts_to_publish">
                            <td>{{ selected_draft.draft_title}} </td>
                            <td>{{ selected_draft.course_name }}</td>
                        </tr>
                        <tr v-show="!loading" v-if="selected_drafts_to_publish.length == 0">
                            <td colspan="3" v-i18n>There are no selected drafts to publish</td>
                        </tr>
                    </tbody>
                </table>
            </slot>
        </modal>

        <button @click="show_delete_modal = true" class="btn btn-danger pull-left" v-if="!this.loading && selected_drafts.length > 0"><i class="fa fa-trash"></i> Delete Selected</button>

        <modal v-if="show_delete_modal" title="Delete Draft Schedule" v-on:ok="deleteDraftSchedule" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
            <slot>
                <p v-i18n>Are you sure you would like to delete the selected draft schedules(s)?</p>
            </slot>
        </modal>
    </div>
</template>

<script>
    const RestClient = use('EntradaJS/Http/RestClient');
    const CohortSelect = use('./CohortSelect.vue');
    const ClinicalTabs = use('./Clinical.vue');
    const Modal = use('./Modal.vue');
    const validationMixin = use('./validationMixin.js');

    module.exports = {
        name: 'published-schedules',
        mixins: [validationMixin],
        props: ['title'],
        data() {
            return {
                draftSchedules: [],
                loading: true,
                show_modal: false,
                show_delete_modal: false,
                show_publish_modal: false,
                cohorts: [],
                courses: [],
                parameters: {
                    search: "",
                    cperiod: "0",
                    status: "draft"
                },
                draft: {
                    id: 0,
                    draft_title: "",
                    course_id: 0,
                    cperiod_id: 0,
                },
                selected_drafts: [],
            };
        },
        computed: {
            selected_drafts_to_publish () {
                return this.draftSchedules.filter(o => this.selected_drafts.includes(o.id));
            }
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
            this.fetchCohorts();
            this.fetchCourses();
        },
        methods: {
            resetState() {
                this.draft = {
                    id: 0,
                    draft_title: "",
                    course_id: 0,
                    cperiod_id: 0,
                };
                this.show_modal = false;
                this.validation_errors = [];
            },
            fetchDraftSchedules() {
                this.loading = true;
                this.api.get('/clinical/draft-rotation-schedule', this.parameters).then(response => {
                    this.draftSchedules = response.json();
                    this.loading = false;
                });
            },
            fetchCohorts() {
                this.api.get('/clinical/curriculum_period').then(response => {
                    this.cohorts = response.json()["cperiods"];
                });
            },
            fetchCourses() {
                this.api.get('/clinical/courses').then(response => {
                    this.courses = response.json();
                });
            },
            saveDraft() {
                this.api.post('/clinical/draft-rotation-schedule', this.draft).then(result => {
                    let url = '#' + this.$generatePath('clinical.rotationschedule.drafts.edit', { draft_id : result.json()["cbl_schedule_draft_id"] });
                    window.location = url;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            deleteDraftSchedule() {
                this.api.delete('/clinical/draft-rotation-schedule/' + this.selected_drafts).then(result => {
                    this.selected_drafts = [];
                    this.fetchDraftSchedules();
                    this.show_delete_modal = false
                });
            },
            publishDraft() {
                this.api.put('/clinical/draft-rotation-schedule/change-status/' + this.selected_drafts, {status: "live"}).then(result => {
                    this.fetchDraftSchedules();
                    this.show_publish_modal = false;
                });
            },
            generateDraftPath(id) {
                return '#' + this.$generatePath('clinical.rotationschedule.drafts.edit', { draft_id : id });
            }
        },
        components: {
            CohortSelect,
            ClinicalTabs,
            Modal,
        }
    };
</script>

<style>
</style>