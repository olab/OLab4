<template>
    <div class="rotation-schedule-blocks">
        <clinical-tabs href="rotationschedule"></clinical-tabs>
        <div v-if="!loading">
            <rotation-schedule-path :course_id="rotation.course_id" :cperiod_id="rotation.cperiod_id"></rotation-schedule-path>
        </div>
        <h2>{{ pageTitle }}</h2>
        <div v-if="msg !== ''" class="alert alert-success">
            {{ msg }}
        </div>
        <p v-show="loading" class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p>
        <div class="form-horizontal" v-show="!loading">
            <div :class="{ 'control-group' : true, 'error' : validation_errors.title }">
                <label for="draft-title" class="control-label">Title</label>
                <div class="controls">
                    <input id="draft-title" v-model="rotation.title" type="text" class="span8" />
                    <span class="help-block no-space-above" v-if="validation_errors.title"> {{validation_errors.title[0]}} </span>
                </div>
            </div>
            <div class="control-group">
                <label for="description" class="control-label">Description</label>
                <div class="controls">
                    <textarea id="description" v-model="rotation.description" type="text" class="span8" />
                </div>
            </div>
            <div v-if="rotation.schedule_type === 'rotation_stream'">
                <div :class="{ 'control-group' : true, 'error' : validation_errors.code }">
                    <label for="code" class="control-label">Code</label>
                    <div class="controls">
                        <div class="input-prepend span12">
                            <span class="add-on">{{rotation.course.course_code}}-</span><input type="text" id="code" name="code" class="span3" v-model="rotation.code" />
                        </div>
                        <span class="help-block" v-if="validation_errors.code"> {{validation_errors.code[0]}} </span>
                    </div>
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
            <div :class="{ 'control-group' : true, 'error' : validation_errors.start_date }">
                <label for="start-date" class="control-label">Start Date</label>
                <div class="controls">
                    <div class="input-append span12">
                        <input id="start-date" ref="start-date" type="date" class="span3" v-model="rotation.input_start_date" :readonly="rotation.schedule_type === 'rotation_stream'"/>
                        <span @click="openDate('start-date')" class="add-on"><i class="icon-calendar"></i></span>
                    </div>
                    <span class="help-block" v-if="validation_errors.start_date"> {{validation_errors.start_date[0]}} </span>
                </div>
            </div>
            <div :class="{ 'control-group' : true, 'error' : validation_errors.end_date }">
                <label class="control-label">End Date</label>
                <div class="controls">
                    <div class="input-append span12">
                        <input id="end-date" type="date" ref="end-date" class="span3"  v-model="rotation.input_end_date" :readonly="rotation.schedule_type === 'rotation_stream'"/>
                        <span @click="openDate('end-date')" class="add-on"><i class="icon-calendar"></i></span>
                    </div>
                    <span class="help-block" v-if="validation_errors.end_date"> {{validation_errors.end_date[0]}} </span>
                </div>
            </div>
        </div>

        <div class="row-fluid space-above space-below">
            <button @click="generateBackUrl" class="btn btn-default pull-left space-right">Back</button>
            <button v-if="this.rotation.schedule_parent_id !== 0" @click="show_modal = true; mode = 'add';msg = ''" class="btn btn-success pull-right">Add Slot</button>
            <button v-else="this.rotation.schedule_parent_id === 0" @click="show_shift_modal = true;" class="btn btn-default pull-right">Shift Blocks</button>
            <button @click="saveRotation" class="btn btn-primary pull-right space-right">Save</button>
        </div>

        <div v-if="Object.keys(childrens_block).length !== 0" v-for="(length_children, block_type_id) in childrens_block">
            <h2>{{block_type_id}} Block</h2>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th width="5%"></th>
                    <th>Name</th>
                    <th width="8%" v-i18n>Slots</th>
                    <th width="12%" v-i18n>Start</th>
                    <th width="12%" v-i18n>Finish</th>
                </tr>
                </thead>
                <tbody>
                    <tr v-show="loading">
                        <td colspan="5"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                    </tr>
                    <tr v-show="!loading"  v-for="child in length_children">
                        <td><input type="checkbox" name="remove_ids[]" :value="`${ child.schedule_id }`" v-model="selected_blocks" /></td>
                        <td><a @click="generateBlockUrl(child.schedule_id)" style="cursor:pointer;"> {{ child.title }}</a></td>
                        <td><a @click="generateBlockUrl(child.schedule_id)" style="cursor:pointer;">{{child.slots.length}}</a></td>
                        <td><a @click="generateBlockUrl(child.schedule_id)" style="cursor:pointer;">{{child.start_date | formatDate}}</a></td>
                        <td><a @click="generateBlockUrl(child.schedule_id)" style="cursor:pointer;">{{child.end_date | formatDate}}</a></td>
                    </tr>
                    <tr v-show="!loading" v-if="length_children.length == 0">
                        <td colspan="5" v-i18n>There are no blocks in the system</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="rotation.schedule_type === 'rotation_block'">
            <h2>Rotation Slots</h2>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th width="5%"></th>
                    <th width="20%">Slot Type</th>
                    <th width="30%">Site</th>
                    <th width="25%">Enforce occupancy limits</th>
                    <th width="20%">Occupancy Limits</th>
                </tr>
                </thead>
                <tbody>
                    <tr v-show="loading">
                        <td colspan="3"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                    </tr>
                    <tr v-show="!loading"  v-for="(slot, index) in rotation.slots">
                        <td><input type="checkbox" :value="`${ slot.schedule_slot_id }`" v-model="selected_slots" /></td>
                        <td><a @click="openEditDialog(index)"> {{ slot.slot_type.slot_type_description }}</a></td>
                        <td><a @click="openEditDialog(index)"> {{ (slot.site !== null ? slot.site.site_name : "None") }}</a></td>
                        <td><a @click="openEditDialog(index)"> {{ (slot.strict_spaces ? "Yes" : "No") }}</a></td>
                        <td><a @click="openEditDialog(index)"> Min: {{ slot.slot_min_spaces }}, Max: {{ slot.slot_spaces }}</a></td>
                    </tr>
                    <tr v-show="!loading" v-if="rotation.slots.length == 0">
                        <td colspan="3" v-i18n>There are no slots in the system</td>
                    </tr>
                </tbody>
            </table>
            <button @click="show_delete_modal = true" class="btn btn-danger pull-left" v-if="!this.loading && selected_slots.length > 0"><i class="fa fa-trash"></i> Delete Selected</button>
        </div>
        <div v-else>
            <button @click="show_delete_block_modal = true" class="btn btn-danger pull-left" v-if="!this.loading && selected_blocks.length > 0"><i class="fa fa-trash"></i> Delete Selected</button>
        </div>
        <modal v-if="show_modal" :title="mode  + ' Rotation Slot' | capitalize" v-on:ok="saveSlot" v-on:hide="resetState">
            <slot>
                <div class="form-horizontal">
                    <div :class="{ 'control-group' : true, 'error' : validation_errors.slot_type_id }">
                        <label class="control-label" for="slot-type">Slot Type</label>
                        <div class="controls">
                            <select id="slot-type" v-model="slot.slot_type_id" class="span10">
                                <option value="0">Please select a slot type</option>
                                <option v-for="slot_type in slot_types" :value="slot_type.slot_type_id">
                                    {{ slot_type.slot_type_description }}
                                </option>
                            </select>
                            <span class="help-block" v-if="validation_errors.slot_type_id"> {{validation_errors.slot_type_id[0]}} </span>
                        </div>
                    </div>
                    <div class="control-group" v-show="isOffService(slot.slot_type_id)">
                        <label for="course-id" class="control-label">Course</label>
                        <div class="controls">
                            <select id="course-id" v-model="slot.course_id" class="span10">
                                <option value="">Available to all courses</option>
                                <option v-for="course in courses" :value="course.id">
                                    {{ course.title }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Site</label>
                        <div class="controls">
                            <select id="leave_type" v-model="slot.site_id" class="span10">
                                <option value="">Select a site</option>
                                <option v-for="site in slot_sites" :value="site.site_id">
                                    {{ site.site_name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="strict">Enforce occupancy limits</label>
                        <div class="controls">
                            <input type="checkbox" v-model="slot.strict_spaces" id="strict"/>
                        </div>
                    </div>
                    <div :class="{ 'control-group' : true, 'error' : validation_errors.slot_min_spaces }">
                        <label class="control-label" for="min-spaces">Min Spaces</label>
                        <div class="controls">
                            <input class="span2" type="text" id="min-spaces" name="slot_spaces" v-model="slot.slot_min_spaces"/>
                            <span class="help-block" v-if="validation_errors.slot_min_spaces"> {{validation_errors.slot_min_spaces[0]}} </span>
                        </div>
                    </div>
                    <div :class="{ 'control-group' : true, 'error' : validation_errors.slot_spaces }">
                        <label class="control-label" for="max-spaces">Max Spaces</label>
                        <div class="controls">
                            <input class="span2" type="text" name="slot_spaces" id="max-spaces" v-model="slot.slot_spaces"/>
                            <span class="help-block" v-if="validation_errors.slot_spaces"> {{validation_errors.slot_spaces[0]}} </span>
                        </div>
                    </div>
                </div>
            </slot>
        </modal>
        <modal v-if="show_shift_modal" title="Shift Blocks" v-on:ok="shiftBlocks" v-on:hide="resetShiftBlock">
            <slot>
                <div class="form-horizontal">
                    <div :class="{ 'control-group' : true, 'error' : validation_errors.days }">
                        <label class="control-label" for="slot-type">Number of Days</label>
                        <div class="controls">
                            <input id="number-of-days" name="number_of_days" class="span2" value="1" type="text"  v-model="shift.days">
                            <span class="help-block" v-if="validation_errors.days"> {{validation_errors.days[0]}} </span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Shift Direction:</label>
                        <div class="controls">
                            <div class="radio">
                                <label><input v-model="shift.shift_direction" type="radio" value="future" /> Shift blocks into the future</label>
                            </div>
                            <div class="radio">
                                <label><input v-model="shift.shift_direction" type="radio" value="past" /> Shift blocks into the past</label>
                            </div>
                        </div>
                    </div>
                </div>
            </slot>
        </modal>
        <modal v-if="show_delete_modal" title="Delete slot" v-on:ok="deleteSlot" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
            <slot>
                <p v-i18n>Are you sure you would like to delete the selected slot(s)?</p>
            </slot>
        </modal>
        <modal v-if="show_delete_block_modal" title="Delete Block" v-on:ok="deleteBlock" v-on:hide="show_delete_block_modal = false" savebutton="Delete" saveclass="btn-danger">
            <slot>
                <p v-i18n>Are you sure you would like to delete the selected block(s)?</p>
            </slot>
        </modal>
    </div>
</template>

<script>
    const RestClient = use('EntradaJS/Http/RestClient');
    const ClinicalTabs = use('./Clinical.vue');
    const Modal = use('./Modal.vue');
    const Multiselect = use('./Multiselect.vue');
    const RotationSchedulePath = use('./RotationSchedulePath.vue');
    const validationMixin = use('./validationMixin.js');
    const DateTools = use('./../Model/Util/DateTools');

    module.exports = {
        name: 'rotation-schedule-blocks',
        mixins: [validationMixin],
        props: ['title'],
        filters: {
            formatDate: DateTools.formatDate,
            formatDateString: DateTools.formatDateString,
            capitalize: function(string) {
                if (!string) return '';
                return string.toString().charAt(0).toUpperCase() + string.slice(1);
            }
        },
        data() {
            return {
                loading: true,
                show_modal: false,
                show_shift_modal: false,
                show_delete_modal: false,
                show_delete_block_modal: false,
                slot_types: [],
                mode: "add",
                msg: "",
                slot: {
                    schedule_slot_id: 0,
                    schedule_id: 0,
                    slot_type_id: 0,
                    slot_min_spaces: 0,
                    slot_spaces: 0,
                    strict_spaces: 0,
                    site_id: "",
                    course_id: ""
                },
                rotation: {
                    title : "",
                    code: "",
                    description: "",
                    selected_sites: [],
                    start_date: null,
                    end_date: null,
                    input_start_date: null,
                    input_end_date: null
                },
                shift: {
                    days: 1,
                    shift_direction: "future"
                },
                selected_blocks: [],
                selected_slots: [],
                schedule_id: 0,
                childrens_block: {},
                selected: null,
                show_id : 0,
                sites: [],
                slot_sites: [],
                courses: [],
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            if (typeof this.$getRoute().getParameter('schedule_id') !== "undefined") {
                this.schedule_id = this.$getRoute().getParameter('schedule_id');
            }

            this.fetchRotationInfo();
            this.fetchSlotTypes();
            this.fetchCourses();
        },
        computed: {
            pageTitle () {
                return this.rotation.schedule_type === 'rotation_block' ? "Block Details" : "Rotation Details";
            }
        },
        methods: {
            fetchRotationInfo() {
                this.api.get('/clinical/rotation-schedule/' + this.schedule_id).then(response => {
                    this.rotation = Object.assign({}, response.json());
                    let selected_sites = [];
                    this.sites = [];
                    this.slot_sites = [];
                    this.rotation.sites.forEach(function(site) {
                        selected_sites.push({"site_id" : site.site_id, "site_name" : site.site.site_name});
                    });
                    this.rotation.selected_sites = selected_sites;
                    this.populateGroupedChildren(this.rotation.children);
                    this.populateSites();
                    this.loading = false;

                    this.rotation.input_start_date = DateTools.formatDate(this.rotation.start_date);
                    this.rotation.input_end_date =  DateTools.formatDate(this.rotation.end_date);
                    this.validation_errors = [];
                });
            },
            populateGroupedChildren(children) {
                let grouped = children.reduce(function (blocks, a) {
                    blocks[a.block_type.name] = blocks[a.block_type.name] || [];
                    blocks[a.block_type.name].push(a);
                        return blocks;
                    }, Object.create(null));

                this.childrens_block = Object.assign({}, grouped);
            },
            populateSites() {
                let parent = this.rotation.parent;
                if (parent !== null && Object.keys(parent).length > 0 && parent.sites.length > 0) {
                    let available_sites = [];
                    parent.sites.forEach(function(site) {
                        available_sites.push({"site_id" : site.site_id, "site_name" : site.site.site_name});
                    });
                    this.sites = available_sites;
                } else {
                    this.fetchSites();
                }

                if (this.rotation.sites.length > 0) {
                    let available_sites = [];
                    this.rotation.sites.forEach(function(site) {
                        available_sites.push({"site_id" : site.site_id, "site_name" : site.site.site_name});
                    });
                    this.slot_sites = available_sites;
                } else {
                    this.fetchSites(true);
                }
            },
            fetchCourses() {
                this.api.get('/clinical/courses').then(response => {
                    this.courses = response.json();
                });
            },
            isOffService(id) {
                let index = this.slot_types.filter(o => o.slot_type_id === id)[0];
                return (index ? (index.slot_type_code === "OFFSL" ? true : false) : false)
            },
            fetchSites(set_slot_sides = false) {
                this.api.get('/locations/sites/').then(response => {
                    this.sites = response.json().sites;
                    if (set_slot_sides) {
                        this.slot_sites = this.sites
                    }
                });
            },
            fetchSlotTypes() {
                this.api.get('/clinical/slot-types/').then(response => {
                    this.slot_types = response.json();
                });
            },
            saveRotation() {
                this.rotation.start_date = DateTools.timestampFromStartDate(this.rotation.input_start_date);
                this.rotation.end_date =  DateTools.timestampFromEndDate(this.rotation.input_end_date);
                this.api.put('/clinical/rotation-schedule/' + this.rotation.schedule_id, this.rotation).then(result => {
                    this.fetchRotationInfo();
                    this.msg = "You have successfully updated " + this.rotation.title;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            saveSlot() {
                this.slot.schedule_id = this.rotation.schedule_id;

                if (this.mode == "add") {
                    this.api.post('/clinical/rotation-schedule-slot', this.slot).then(result => {
                        this.fetchRotationInfo();
                        this.resetState();
                    }).catch(error => {
                        this.catchError(error);
                    });
                } else {
                    this.api.put('/clinical/rotation-schedule-slot/' + this.slot.schedule_slot_id, this.slot).then(result => {
                        this.fetchRotationInfo();
                        this.resetState();
                    }).catch(error => {
                        this.catchError(error);
                    });
                }
            },
            shiftBlocks() {
                this.api.get('/clinical/rotation-schedule/shift-blocks/' + this.rotation.schedule_id, this.shift).then(result => {
                    this.fetchRotationInfo();
                    this.resetShiftBlock();
                }).catch(error => {
                    this.catchError(error);
                });
            },
            generateBlockUrl(id) {
                window.location.href ='#' + this.$generatePath('clinical.rotationschedule.drafts.edit.blocks', { draft_id : this.rotation.draft_id, schedule_id : id });
                this.schedule_id = id;
                this.fetchRotationInfo();
                this.msg = "";
            },
            generateBackUrl() {
                if (this.rotation.schedule_parent_id === 0) {
                    window.location = '#' + this.$generatePath('clinical.rotationschedule.drafts.edit', { draft_id : this.rotation.draft_id });
                } else {
                    this.generateBlockUrl(this.rotation.schedule_parent_id);
                }
            },
            openEditDialog(index) {
                this.slot = this.rotation.slots[index];
                if (this.slot.site_id !== null) {
                    if (!this.slot_sites.some((site) => site.site_id === this.slot.site_id )) {
                        this.api.get('/locations/sites/' + this.slot.site_id ).then(response => {
                            let site = response.json();
                            this.slot_sites.push({"site_id" : this.slot.site_id, "site_name" : site.site_name})
                        });
                    }
                } else {
                    this.slot.site_id = "";
                }

                this.show_modal = true;
                this.msg = "";
                this.mode = "edit";
            },
            deleteSlot() {
                this.api.delete('/clinical/rotation-schedule-slot/' + this.selected_slots).then(result => {
                    this.selected_slots = [];
                    this.fetchRotationInfo();
                    this.show_delete_modal = false
                });
            },
            deleteBlock() {
                this.api.delete('/clinical/rotation-schedule/' + this.selected_blocks).then(result => {
                    this.selected_blocks = [];
                    this.fetchRotationInfo();
                    this.show_delete_block_modal = false
                });
            },
            resetState() {
                this.slot = {
                    schedule_slot_id: 0,
                    schedule_id: 0,
                    slot_type_id: 0,
                    slot_min_spaces: 0,
                    slot_spaces: 0,
                    strict_spaces: 0,
                    site_id: "",
                    course_id: ""
                };
                this.show_modal = false;
                this.validation_errors = [];

                if (this.rotation.sites.length > 0) {
                    for (let i = this.slot_sites.length - 1; i >= 0; i--) {
                        if (!this.rotation.selected_sites.some((site) => site.site_id === this.slot_sites[i].site_id)) {
                            this.slot_sites.splice(i, 1);
                        }
                    }
                }
            },
            resetShiftBlock() {
                this.shift = {
                    days: 1,
                    shift_direction: "future"
                };
                this.show_shift_modal = false;
                this.validation_errors = [];
            },
            openDate(input) {
                this.$refs[input].click();
            },
        },
        components: {
            ClinicalTabs,
            Modal,
            Multiselect,
            RotationSchedulePath
        }
    };
</script>

<style>
    a {
        cursor: pointer;
    }
</style>