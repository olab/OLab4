<template>
    <div class="published-schedules">
        <clinical-tabs href="rotationschedule"></clinical-tabs>
        <div v-if="!loading_info">
            <rotation-schedule-path :course_id="draft.course_id" :cperiod_id="draft.cperiod_id"></rotation-schedule-path>
        </div>
        <h2>Add Rotation</h2>
        <div v-if="loading_info">
            <p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p>
        </div>
        <div v-else class="form-horizontal">
            <div :class="{ 'control-group' : true, 'error' : validation_errors.title }">
                <label for="draft-title" class="control-label form-required">Title</label>
                <div class="controls">
                    <input id="draft-title" v-model="rotation.title" type="text" class="span8" />
                    <span class="help-block no-space-above" v-if="validation_errors.title"> {{validation_errors.title[0]}} </span>
                </div>
            </div>
            <div :class="{ 'control-group' : true, 'error' : validation_errors.code }">
                <label for="code" class="control-label form-required">Code</label>
                <div class="controls">
                    <div class="input-prepend span12">
                        <span class="add-on">{{draft.course.course_code}}-</span><input type="text" id="code" name="code" v-model="rotation.code" />
                    </div>
                    <span class="help-block" v-if="validation_errors.code"> {{validation_errors.code[0]}} </span>
                </div>
            </div>
            <div class="control-group">
                <label for="description" class="control-label">Description</label>
                <div class="controls">
                    <textarea id="description" v-model="rotation.description" type="text" class="span8" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Sites</label>
                <div class="controls">
                    <div class="input-append span8">
                        <multiselect
                                v-model="rotation.selected_sites"
                                :options="sites"
                                :multiple="true"
                                label="site_name"
                                :searchable="false"
                                track-by="site_id">
                        </multiselect>
                    </div>
                </div>
            </div>
            <div :class="{ 'control-group' : true, 'error' : validation_errors.blocks }">
                <div class="controls">
                    <p class="content-small space-below form-required">Apply the selected block schedule(s) from this curriculum period</p>
                    <div v-for="block in blocks">
                        <label class="checkbox" style="display: inline-block">
                            <input type="checkbox" id="blocks" v-model="rotation.blocks" :value="block.schedule_id" checked="checked"/>
                            {{ block.title }}
                        </label>

                        <i v-if="block.schedule_id !== show_id" class="fa fa-chevron-circle-down" @click="show_id = block.schedule_id" style="cursor: pointer"></i>
                        <i class="fa fa-chevron-circle-up" @click="show_id = 0" style="cursor: pointer" v-else></i>

                        <div class="content-small" v-for="block_c in block.children" v-show="block.schedule_id === show_id">
                            {{block_c.title + " - "}} {{ block_c.start_date | formatDateString }}  -  {{block_c.end_date | formatDateString}}
                        </div>
                    </div>
                    <span class="help-block" v-if="validation_errors.blocks"> {{validation_errors.blocks[0]}} </span>
                </div>
            </div>
        </div>

        <button @click="saveRotation" class="btn btn-primary pull-right">Save</button>

        <a :href="draft_url" class="btn btn-default pull-left">Back</a>

    </div>
</template>

<script>
    const DateTools = use('./../Model/Util/DateTools');
    const RestClient = use('EntradaJS/Http/RestClient');
    const ClinicalTabs = use('./Clinical.vue');
    const RotationSchedulePath = use('./RotationSchedulePath.vue');
    const Multiselect = use('./Multiselect.vue');
    const validationMixin = use('./validationMixin.js');

    module.exports = {
        name: 'add-rotation-schedule',
        mixins: [validationMixin],
        filters: {
            formatDateString: DateTools.formatDateString
        },
        props: ['title'],
        data() {
            return {
                blocks: [],
                checked: true,
                draft_id: 0,
                loading_info : true,
                parameters: {
                    search: "",
                    cperiod: "0",
                    status: "draft"
                },
                draft: {
                    course: {
                        course_name : "",
                        course_code : "",
                    }
                },
                rotation: {
                    title : "",
                    code: "",
                    draft_id: 0,
                    cperiod_id: 0,
                    course_id: 0,
                    blocks: [],
                    selected_sites: [],
                },
                draft_url: "",
                show_id : 0,
                sites: [],
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            if (typeof this.$getRoute().getParameter('draft_id') !== "undefined") {
                this.draft_id = this.$getRoute().getParameter('draft_id');
                this.draft_url = '#' + this.$generatePath('clinical.rotationschedule.drafts.edit', { draft_id : this.draft_id });
            }

            this.fetchDraftInfo();
            this.fetchSites();
        },
        methods: {
            fetchDraftInfo() {
                this.api.get('/clinical/draft-rotation-schedule/' + this.draft_id , this.parameters).then(response => {
                    this.draft = Object.assign({}, response.json());
                    this.rotation.draft_id = this.draft_id;
                    this.rotation.cperiod_id = this.draft.cperiod_id;
                    this.rotation.course_id = this.draft.course_id;
                    this.fetchBlocks();
                });
            },
            fetchBlocks() {
                this.api.get('/clinical/rotation-schedule-templates/' + this.draft.cperiod_id).then(response => {
                    this.blocks = response.json();
                    this.rotation.blocks =  this.blocks.map(a => a.schedule_id);
                    this.loading_info = false;
                });
            },
            fetchSites() {
                this.api.get('/locations/sites/').then(response => {
                    this.sites = response.json().sites;
                });
            },
            saveRotation() {
                this.api.post('/clinical/rotation-schedule', this.rotation).then(result => {
                    window.location = this.draft_url;
                }).catch(error => {
                    this.catchError(error);
                });
            },
        },
        components: {
            ClinicalTabs,
            RotationSchedulePath,
            Multiselect,
        }
    };
</script>

<style>
</style>