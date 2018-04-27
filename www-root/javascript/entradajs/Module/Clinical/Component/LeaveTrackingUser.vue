<template>
    <div class="leave-tracking">
        <clinical-tabs href="leavetracking"></clinical-tabs>
        <h2 class="space-below"> Leave Tracking for {{this.user_fullname}}</h2>

        <div class="clearfix">
            <div class="span8">
                <div class="search-bar" class="search-bar" >
                    <div class="row-fluid space-below medium">
                        <div class="pull-left">
                            <input type="text" placeholder="Search" class="input-large search-icon" v-model="parameters.search_user" @keyup.enter="getTrackedVacations" v-i18n />
                            <button @click="openCreateDialog()" class="btn btn-success pull-right space-left"><i class="fa fa-plus-circle"></i> Record New Leave</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <cohort-select v-model="parameters.cperiod" @change="getTrackedVacations()"></cohort-select>
            </div>
        </div>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th width="25%" v-i18n>Leave Type</th>
                    <th width="13%" v-i18n>Days Used</th>
                    <th width="13%" v-i18n>Start Date</th>
                    <th width="12%" v-i18n>End Date</th>
                    <th width="32%" v-i18n>Comments</th>
                    <th width="5%"><i class="icon-trash"></i></th>
                </tr>
            </thead>
            <tbody>
                <tr v-show="loading">
                    <td colspan="6"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                </tr>
                <tr v-show="!loading" v-for="(trackedVacation, index) in trackedVacations">
                    <td><a data-target="#edit-leave" role="button" data-toggle="modal" @click="openEditDialog(index)"> {{ trackedVacation.type_value }}</a></td>
                    <td><a role="button" @click="openEditDialog(index)">{{ trackedVacation.days_used }}</a></td>
                    <td><a role="button" @click="openEditDialog(index)">{{ trackedVacation.start_date }}</a></td>
                    <td><a role="button" @click="openEditDialog(index)">{{ trackedVacation.end_date }}</a></td>
                    <td><a role="button" @click="openEditDialog(index)">{{ trackedVacation.comments }}</a></td>
                    <td><input type="checkbox" name="remove_ids[]" :value="`${ trackedVacation.leave_id }`" v-model="selected_leavetracking" /></td>
                </tr>
                <tr v-show="!loading" v-if="trackedVacations.length == 0">
                    <td colspan="6" v-i18n>There is currently no leave tracked by the system. Use the add leave button above to create a new leave record.</td>
                </tr>
            </tbody>
        </table>

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th width="25%" v-i18n>Leave Type</th>
                    <th width="13%" v-i18n>Total Days</th>
                </tr>
            </thead>
            <tbody>
                <tr v-show="loading">
                    <td colspan="2"><p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p></td>
                </tr>
                <tr v-show="!loading" v-for="trackedVacationByType in trackedVacationsByType">
                    <td>{{ trackedVacationByType.type_value }}</td>
                    <td>{{ trackedVacationByType.days_used }}</td>
                </tr>
            </tbody>
        </table>

        <leave-tracking-dialog v-if="isCreating()" :title="'New leave for ' + user_fullname" v-on:reset="resetState()" :user_id="user_id" v-on:refresh="getTrackedVacations">
        </leave-tracking-dialog>

        <leave-tracking-dialog v-if="isEditing()" :title="'Edit ' + user_fullname + ' leave'" :track="leaveTracking" v-on:reset="resetState"  v-on:refresh="getTrackedVacations" :user_id="user_id">
        </leave-tracking-dialog>

        <button @click="show_delete_modal = true" class="btn btn-danger pull-right" v-if="!this.loading && selected_leavetracking.length > 0"><i class="fa fa-trash"></i> Delete Selected</button>

        <modal v-if="show_delete_modal" title="Delete leave tracking" v-on:ok="deleteLeaveTracking" v-on:hide="show_delete_modal = false" savebutton="Delete" saveclass="btn-danger">
            <slot>
                <p v-i18n>Are you sure you would like to delete the selected leave tracking(s)?</p>
            </slot>
        </modal>
    </div>
</template>

<script>
    const CohortSelect = use('./CohortSelect.vue');
    const RestClient = use('EntradaJS/Http/RestClient');
    const ClinicalTabs = use('./Clinical.vue');
    const LeaveTrackingDialog = use('./LeaveTrackingDialog.vue');
    const Modal = use('./Modal.vue');
    const DateTools = use('./../Model/Util/DateTools');

    const STATES = {
        Ready: 0,
        Loading: 1,
        Creating: 2,
        Editing: 3,
        Deleting: 4
    };

    module.exports = {
        name: 'leave-tracking-user',
        props: ['title'],
        data() {
            return {
                state: 0,
                show_delete_modal: false,
                trackedVacations: [],
                trackedVacationsByType: [],
                loading: true,
                user_id: 0,
                user_fullname : "",
                parameters: {
                    search_user: "",
                    cperiod: "0",
                },
                leaveTracking: {
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
                },
                selected_leavetracking: [],
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);

            if (typeof this.$getRoute().getParameter('user_id') !== "undefined") {
                this.user_id = this.$getRoute().getParameter('user_id');
            }
        },
        methods: {
            setState(state) {
                this.state = state;
            },
            resetState() {
                this.state = STATES.Ready
            },
            getTrackedVacations() {
                this.loading = true;
                this.api.get('/clinical/leave_tracking/' + this.user_id, this.parameters).then(response => {
                    this.user_fullname = response.json()["user"];
                    this.populateLeaveTrackingList(response.json()["leave_trackings"]);
                    this.populateLeaveTrackingByType(response.json()["leave_trackings"]);
                    this.loading = false;
                });
            },
            populateLeaveTrackingList (leave_trackings) {
                for(let leave_tracking of leave_trackings) {
                    let start = DateTools.dateFromTimestamp(leave_tracking.start_date);
                    let end = DateTools.dateFromTimestamp(leave_tracking.end_date);

                    leave_tracking.start_date = DateTools.formatDate(start);
                    leave_tracking.end_date =  DateTools.formatDate(end);

                    leave_tracking.start_time = DateTools.formatTime(start);
                    leave_tracking.end_time =  DateTools.formatTime(end);
                }
                this.trackedVacations = leave_trackings;
            },
            populateLeaveTrackingByType (leave_trackings) {

                let grouped = [];

                leave_trackings.forEach(function (o) {
                    if (!this[o.type_id]) {
                        this[o.type_id] = { days_used: o.days_used, type_value: o.type_value };
                        grouped.push(this[o.type_id]);
                    } else {
                        this[o.type_id].days_used += o.days_used;
                    }
                }, Object.create(null));

                this.trackedVacationsByType = grouped;
            },
            isReady() {
                return this.state === STATES.Ready;
            },
            isLoading() {
                return this.state === STATES.Loading;
            },
            isCreating() {
                return this.state === STATES.Creating;
            },
            isEditing() {
                return this.state === STATES.Editing;
            },
            isDeleting() {
                return this.state === STATES.Deleting;
            },
            openCreateDialog() {
                if(this.isReady()) {
                    this.setState(STATES.Creating);

                }
            },
            openEditDialog(index) {
                if(this.isReady()) {
                    this.setState(STATES.Editing);
                    this.leaveTracking = this.trackedVacations[index];
                }
            },
            deleteLeaveTracking() {
                this.state = STATES.Loading;

                this.api.delete('/clinical/leave_tracking/' + this.selected_leavetracking).then(result => {
                    this.selected_leavetracking = [];
                    this.getTrackedVacations();
                    this.resetState();
                    this.show_delete_modal = false
                });
            }
        },
        components: {
            CohortSelect,
            ClinicalTabs,
            LeaveTrackingDialog,
            Modal,
        }
    };
</script>
<style>
</style>