<template>
    <div class="leave-tracking-dialog">
        <modal :title="title" v-on:ok="saveTracking()" v-on:hide="resetState()">
            <slot>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.start_date }">
                    <label for="start-date" class="control-label form-required">Start Date</label>
                    <div class="controls">
                        <div class="input-append">
                            <input id="start-date" ref="startDate" v-model="leaveTracking.start_date" type="date" @change="calculateTotalDays"/>
                            <span @click="openDate('startDate')" class="add-on"><i class="fa fa-calendar"></i></span>
                        </div>
                        <div class="input-append">
                            <input id="start-time" name="start_time" v-model="leaveTracking.start_time" type="time" class="span12" />
                            <span class="add-on"><i class="icon-time"></i></span>
                        </div>
                        <span class="help-block" v-if="validation_errors.start_date"> {{validation_errors.start_date[0]}} </span>
                    </div>
                </div>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.end_date }">
                    <label for="end-date" class="control-label form-required">End Date</label>
                    <div class="controls">
                        <div class="input-append">
                            <input id="end-date" ref="endDate" v-model="leaveTracking.end_date" type="date" @change="calculateTotalDays" />
                            <span @click="openDate('endDate')" class="add-on"><i class="fa fa-calendar"></i></span>
                        </div>
                        <div class="input-append">
                            <input id="end-time" name="end_time" v-model="leaveTracking.end_time" type="time" class="span12" />
                            <span class="add-on"><i class="icon-time"></i></span>
                        </div>
                        <span class="help-block" v-if="validation_errors.end_date"> {{validation_errors.end_date[0]}} </span>
                    </div>
                </div>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.days_used }">
                    <label for="days-used" class="control-label form-required">Total Days Used</label>
                    <div class="controls">
                        <input id="days-used" v-model="leaveTracking.days_used" type="text" class="span4" />
                        <span class="help-block" v-if="validation_errors.days_used"> {{validation_errors.days_used[0]}} </span>
                    </div>
                </div>
                <div class="control-group">
                    <label for="weekdays-used" class="control-label">Weekdays Used</label>
                    <div class="controls">
                        <input id="weekdays-used" v-model="leaveTracking.weekdays_used" type="text" class="span2" />
                    </div>
                </div>
                <div class="control-group">
                    <label for="weekend-days-used" class="control-label">Weekend Days Used</label>
                    <div class="controls">
                        <input id="weekend-days-used" v-model="leaveTracking.weekend_days_used" type="text" class="span2" />
                    </div>
                </div>
                <div :class="{ 'control-group' : true, 'error' : validation_errors.type_id }">
                    <label class="control-label form-required">Leave Type</label>
                    <div class="controls">
                        <select id="leave_type" v-model="leaveTracking.type_id" class="span12">
                            <option value="0">Please select a leave type</option>
                            <option v-for="leave_type in leaveTypes" :value="leave_type.id">
                                {{ leave_type.type }}
                            </option>
                        </select>
                        <span class="help-block" v-if="validation_errors.type_id"> {{validation_errors.type_id[0]}} </span>
                    </div>
                </div>
                <div class="control-group">
                    <label for="comments" class="control-label">Comments</label>
                    <div class="controls">
                        <textarea id="comments" v-model="leaveTracking.comments" class="expandable space-above"></textarea>
                    </div>
                </div>
            </slot>
        </modal>
    </div>
</template>

<script>
    const RestClient = use('EntradaJS/Http/RestClient');
    const Modal = use('./Modal.vue');
    const validationMixin = use('./validationMixin.js');

    module.exports = {
        name: 'leave-tracking-dialog',
        mixins: [validationMixin],
        props: {
            title: {
                type: String
            },
            track: {
                type: Object,
                required: false
            },
            user_id: {
                required: false
            },
        },
        data() {
            return {
                leaveTypes: null,
                leaveTracking: {
                    proxy_id: 0,
                    leave_id:0,
                    name: null,
                    start_date: null,
                    start_time: null,
                    end_date: null,
                    end_time: null,
                    days_used: 0,
                    weekdays_used: 0,
                    weekend_days_used: 0,
                    type_id: 0,
                    comments: ""
                },
                mode: "add",
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            this.fetchLeaveTypes();

            if (this.track) {
                this.leaveTracking = Object.assign({}, this.track);
                this.mode = "edit";
            }

            if (this.user_id) {
                this.leaveTracking.proxy_id = this.user_id;
            }
        },
        methods: {
            resetState() {
                this.leaveTracking = {
                    proxy_id: 0,
                    leave_id: 0,
                    name: null,
                    start_date: null,
                    start_time: null,
                    end_date: null,
                    end_time: null,
                    days_used: 0,
                    weekdays_used: 0,
                    weekend_days_used: 0,
                    type_id: null,
                    comments: ""
                };
                this.validation_errors = [];
                this.$emit('reset');
            },
            fetchLeaveTypes() {
                this.api.get('/clinical/leave_types').then(response => {
                    this.leaveTypes = response.json();
                });
            },
            saveTracking() {
                if (this.mode == "add") {
                    this.api.post('/clinical/leave_tracking', this.leaveTracking).then(result => {
                        this.$emit('hide');
                        this.$emit('reset');
                        this.$emit('refresh');
                    }).catch(error => {
                        this.catchError(error);
                    });
                } else {
                    this.api.put('/clinical/leave_tracking/' + this.leaveTracking.leave_id, this.leaveTracking).then(result => {
                        this.$emit('hide');
                        this.$emit('reset');
                        this.$emit('refresh');
                    }).catch(error => {
                        this.catchError(error);
                    });
                }
            },
            calculateTotalDays() {
                if (this.leaveTracking.end_date && this.leaveTracking.start_date) {
                    let one_day = 1000 * 60 * 60 * 24;
                    let days_used = (Date.parse(this.leaveTracking.end_date) - Date.parse(this.leaveTracking.start_date));
                    let num_days = Math.round(days_used / one_day) + 1;
                    this.leaveTracking.days_used = num_days;
                } else {
                    this.leaveTracking.days_used = 0;
                }
            },
            openDate(input) {
                this.$refs[input].click();
            },
        },
        components: {
            Modal,
        }
    };
</script>
<style>
</style>