<template>
    <div class="rotation-list">
        <div class="btn-group pull-right space-below">
            <a :href="this.add_url" class="btn btn-success">Add Rotation</a>
            <a  class="btn btn-success dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></a>
            <ul class="dropdown-menu">
                <li>
                    <a @click="show_import_rotation = true">Import Rotation Structure</a>
                </li>
                <li>
                    <a @click="show_copy_rotation_modal = true">Copy Existing Rotations</a>
                </li>
                <li>
                    <a @click="show_export_rotation_modal = true">Export Report</a>
                </li>
            </ul>
        </div>
        <!-- Copy Existing Rotations modal -->
        <modal v-if="show_copy_rotation_modal" title="Copy Existing Rotations" v-on:ok="copyExistingSchedule" v-on:hide="show_copy_rotation_modal = false" savebutton="Copy" saveclass="btn-success">
            <slot>
                <div class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label" v-i18n>Select a Schedule:</label>
                        <div class="controls">
                            <select v-show="!loading_live_rotations" v-model="copy_draft_id">
                                <option value="0" v-i18n>Select an option</option>
                                <option v-for="rotation in rotations_to_copy" :value="rotation.id">{{ rotation.draft_title }}</option>
                            </select>
                            <p v-show="loading_live_rotations" class="text-center text-muted"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p>
                            <div id="copy_rotation_modal_msg"></div>
                        </div>
                    </div>
                </div>
            </slot>
        </modal>
        <!-- Export Rotations modal -->

        <modal v-if="show_export_rotation_modal" title="Export Rotations" v-on:ok="exportRotationSchedule" v-on:hide="show_export_rotation_modal = false" savebutton="Export" saveclass="btn-success">
            <slot>
                <div class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label" v-i18n>Select a Block Type:</label>
                        <div class="controls">
                            <select v-show="!loading" v-model="block_type_id">
                                <option value="0" v-i18n>Select an option</option>
                                <option v-for="block in this.$parent.blocks" :value="block.block_type.block_type_id">{{ block.block_type.name }}</option>
                            </select>
                            <p v-show="loading" class="text-center text-muted"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p>
                            <div id="export_rotation_modal_msg"></div>
                        </div>
                    </div>
                </div>
            </slot>
        </modal>

        <!-- Import Rotation Structure -->

        <modal v-if="show_import_rotation" title="Import Rotation Schedule Structure" v-on:ok="importRotation" v-on:hide="resetImport">
            <slot>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.file }">
                    <label class="control-label form-required">Select a file</label>
                    <div class="controls">
                        <input ref="import_file" type="file">
                        <span class="help-block" v-if="validation_errors.file"> {{validation_errors.file[0]}} </span>
                    </div>
                </div>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.blocks }">
                    <label class="control-label form-required">Select a Template</label>
                    <div class="controls">
                        <table class="table table-striped table-bordered">
                            <tbody>
                            <tr v-show="loading">
                                <td colspan="6"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                            </tr>
                            <tr v-show="!loading"  v-for="block in this.$parent.blocks">
                                <td><input type="checkbox" v-model="import_rotation.selected_blocks" :value="block.schedule_id" checked="checked"/></td>
                                <td> {{ block.title }}</td>
                                <td>{{ block.start_date | formatDate}}</td>
                                <td>{{ block.end_date | formatDate }}</td>
                                <td>{{ block.block_type.number_of_blocks}} blocks</td>
                            </tr>
                            <tr v-show="!loading" v-if="this.$parent.blocks.length == 0">
                                <td colspan="6" v-i18n>No schedule templates have been found. Please ensure the Curriculum Period that you selected for this draft is correct.</td>
                            </tr>
                            </tbody>
                        </table>
                        <span class="help-block" v-if="validation_errors.blocks"> {{validation_errors.blocks[0]}} </span>
                    </div>
                </div>
            </slot>
            <div slot="footer">
                <a href="/templates/default/demo/demo_import_rotation_schedule_structure.csv" class="pull-left">Download Example CSV file</a>
                <a @click="resetImport" class="btn btn-default">Cancel</a>
                <input @click="importRotation" type="submit" class="btn btn-success" value="Import Rotations" />
            </div>
        </modal>

        <h4>My Rotations</h4>

        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th width="3%"></th>
                <th v-i18n>Name</th>
                <th width="15%" v-i18n>Shortname</th>
                <th width="12%" v-i18n>Start</th>
                <th width="12%" v-i18n>Finish</th>
            </tr>
            </thead>
            <tbody>
                <tr v-show="loading">
                    <td colspan="6"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                </tr>
                <tr v-show="!loading"  v-for="(rotationSchedule, index) in rotation_schedules">
                    <td><input type="checkbox"  :value="`${ rotationSchedule.schedule_id }`" v-model="selected_rotations" /></td>
                    <td><a :href="generateBlockUrl(rotationSchedule.schedule_id)"> {{ rotationSchedule.title }}</a>
                        <button v-if="cbme_enabled" @click="generateObjectivesUrl(rotationSchedule.schedule_id)" id="tag-objectives" class="btn pull-right"><i class="fa fa-map" aria-hidden="true"></i> CBME Objectives</button>
                    </td>
                    <td><a :href="generateBlockUrl(rotationSchedule.schedule_id)">{{ rotationSchedule.code }}</a></td>
                    <td><a :href="generateBlockUrl(rotationSchedule.schedule_id)">{{ rotationSchedule.start_date | formatDate}}</a></td>
                    <td><a :href="generateBlockUrl(rotationSchedule.schedule_id)">{{ rotationSchedule.end_date | formatDate }}</a></td>
                </tr>
                <tr v-show="!loading" v-if="rotation_schedules.length == 0">
                    <td colspan="6" v-i18n>There are no rotation schedules in the system</td>
                </tr>
            </tbody>
        </table>

        <div class="row-fluid space-below medium">
            <div class="pull-left">
                <button @click="show_delete_modal = true" class="btn btn-danger pull-left" v-if="!this.loading && selected_rotations.length > 0"><i class="fa fa-trash"></i> Delete Selected</button>
            </div>
        </div>

        <modal v-if="show_delete_modal" title="Delete Rotation Schedule" v-on:ok="deleteRotationSchedule" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
            <slot>
                <p v-i18n>Are you sure you would like to delete the selected rotation schedules(s)?</p>
            </slot>
        </modal>


        <h4>Available Off Service Rotations</h4>

        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th width="18%" v-i18n>Name</th>
                <th width="10%" v-i18n>Code</th>
                <th width="20%" v-i18n>Course</th>
                <th width="12%" v-i18n>Site</th>
                <th width="5%" v-i18n>Occupancy</th>
                <th width="12%" v-i18n>Start</th>
                <th width="12%" v-i18n>Finish</th>
            </tr>
            </thead>
            <tbody>
                <tr v-show="loading">
                    <td colspan="6"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                </tr>
                <tr v-show="!loading"  v-for="(off_service_slot, index) in off_service_slots">
                    <td>{{ off_service_slot.rotation_schedule.parent.title }}</td>
                    <td>{{ off_service_slot.rotation_schedule.parent.code }}</td>
                    <td>{{ off_service_slot.rotation_schedule.parent.course.course_code }} - {{ off_service_slot.rotation_schedule.parent.course.course_name }}</td>
                    <td>{{ (off_service_slot.site !== null ? off_service_slot.site.site_name : "None") }}</td>
                    <td>{{ off_service_slot.slot_spaces}}</td>
                    <td>{{ off_service_slot.rotation_schedule.start_date | formatDate}}</td>
                    <td>{{ off_service_slot.rotation_schedule.end_date | formatDate }}</td>
                </tr>
                <tr v-show="!loading" v-if="off_service_slots.length == 0">
                    <td colspan="6" v-i18n>There are currently no off service rotations available for this draft.</td>
                </tr>
            </tbody>
        </table>
        <iframe v-if="load_iframe"
                :src="src"
                frameborder="0"
        ></iframe>
    </div>
</template>

<script>
    const DateTools = use('./../Model/Util/DateTools');
    const RestClient = use('EntradaJS/Http/RestClient');
    const Modal = use('./Modal.vue');

    const validationMixin = use('./validationMixin.js');

    module.exports = {
        name: 'my-rotations',
        mixins: [validationMixin],
        props: ['title'],
        filters: {
            formatDate: DateTools.formatDate,
            formatDateString: DateTools.formatDateString
        },
        data() {
            return {
                rotation_schedules: [],
                off_service_slots: [],
                loading: true,
                loading_live_rotations: true,
                parameters: {
                    search: "",
                    cperiod: "0",
                    draft_id: 0,
                    type: "rotation_stream"
                },
                import_rotation: {
                    file: "",
                    selected_blocks: []
                },
                draft_id: 0,
                copy_draft_id: 0,
                add_url: "",
                show_copy_rotation_modal: false,
                show_export_rotation_modal: false,
                show_import_rotation: false,
                show_delete_modal: false,
                rotations_to_copy: [],
                block_type_id: 0,
                schedule_id: 0,
                load_iframe: false,
                cbme_enabled: false,
                selected_rotations: [],
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            if (typeof this.$getRoute().getParameter('draft_id') !== "undefined") {
                this.draft_id = this.$getRoute().getParameter('draft_id');
                this.parameters.draft_id = this.draft_id;
                this.add_url = '#' + this.$generatePath('clinical.rotationschedule.drafts.edit.add', { draft_id : this.draft_id });
            }
            this.fetchRotationSchedules();
            this.fetchPublishedSchedules();
        },
        methods: {
            fetchRotationSchedules() {
                this.loading = true;
                this.api.get('/clinical/rotation-schedule/', this.parameters).then(response => {
                    this.rotation_schedules = response.json()["rotation_schedules"];
                    this.off_service_slots = response.json()["off_service_slots"];
                    this.cbme_enabled = response.json()["cbme_enabled"];
                    this.loading = false;
                });
            },
            fetchPublishedSchedules() {
                this.loading_live_rotations = true;
                this.api.get('/clinical/draft-rotation-schedule/', {status: "live"}).then(response => {
                    this.rotations_to_copy = response.json();
                    this.loading_live_rotations = false;
                });
            },
            generateBlockUrl(id) {
                return '#' + this.$generatePath('clinical.rotationschedule.drafts.edit.blocks', { draft_id : this.draft_id, schedule_id : id });
            },
            copyExistingSchedule() {
                this.loading = true;
                this.api.post('/clinical/draft-rotation-schedule/copy', {copy_draft_id: this.copy_draft_id, draft_id: this.draft_id}).then(response => {
                    this.fetchRotationSchedules();
                    this.show_copy_rotation_modal = false;
                });
            },
            generateObjectivesUrl(id) {
                this.api.get('/clinical/rotation-schedule/mapping-url/' + id).then(result => {
                    this.src = result.json()["src"];
                    this.load_iframe = true;
                });
            },
            exportRotationSchedule() {
                this.loading = true;
                this.api.post('/clinical/draft-rotation-schedule/export', {draft_id: this.draft_id, block_type_id: this.block_type_id}).then(response => {
                    this.show_export_rotation_modal = false;
                    this.loading = false;
                    window.location = URL.createObjectURL(response.blob());
                });
            },
            importRotation() {
                let files = this.$refs.import_file.files;
                let data = new FormData();
                data.append('blocks', JSON.stringify(this.import_rotation.selected_blocks));
                data.append('draft_id', this.draft_id);
                data.append('file', files[0]);
                this.api.api('post', '/clinical/rotation-schedule/import', data).then(response => {
                    this.fetchRotationSchedules();
                    this.resetImport();
                }).catch(error => {
                    this.catchError(error);
                });
            },
            resetImport() {
                this.import_rotation = {
                    file: "",
                    selected_blocks: []
                };
                this.show_import_rotation = false;
            },
            deleteRotationSchedule() {
                this.api.delete('/clinical/rotation-schedule/' + this.selected_rotations).then(result => {
                    this.selected_rotations = [];
                    this.fetchRotationSchedules();
                    this.show_delete_modal = false
                });
            },
        },
        components: {
            Modal,
        }
    };
</script>

<style>
</style>