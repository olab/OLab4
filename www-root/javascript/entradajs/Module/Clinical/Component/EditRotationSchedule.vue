<template>
    <div class="published-schedules">
        <clinical-tabs href="rotationschedule"></clinical-tabs>
        <div v-if="!loading_info">
            <rotation-schedule-path :course_id="draft.course_id" :cperiod_id="draft.cperiod_id"></rotation-schedule-path>
        </div>

        <h2 v-if="!isEditingRotationName">{{ this.draft.draft_title }}  Rotation Schedule {{ this.draft.status == "draft"  ? "Draft" : ""}}  <a class="btn btn-small space-left" @click="isEditingRotationName = true"><i class="fa fa-pencil"></i></a></h2>

        <div v-if="!loading_info" class="form-inline space-above" v-show="isEditingRotationName">
            <div :class="{ 'control-group' : true, 'error' : validation_errors.draft_title }">
                <label class="control-label">Title</label>
                <div class="controls">
                    <input id="draft-title" v-model="edit.draft_title" type="text" class="span6" />
                    <span class="help-block" v-if="validation_errors.draft_title"> {{validation_errors.draft_title[0]}} </span>
                </div>
            </div>
            <div class="control-group clearfix">
                <label class="control-label">Authors</label>
                <div class="controls">
                    <div class="span6">
                        <multiselect
                                v-model="edit.authors"
                                :options="users"
                                :multiple="true"
                                label="fullname"
                                :searchable="true"
                                placeholder="Type to search users"
                                track-by="id">
                        </multiselect>
                    </div>
                </div>
            </div>
            <div class="control-group clearfix">
                <div class="controls">
                    <button class="btn btn-default" @click="isEditingRotationName = false">Cancel</button>
                    <button class="btn btn-primary" @click="saveDraft">Save</button>
                </div>
            </div>

        </div>

        <ul class="nav nav-tabs space-above pointer-override">
            <li v-for="tab in tabs" :class="{'active': (tab.href == activeTab.href)}">
                <a @click="generateTabHref(tab.href); activateTab(tab)">{{tab.title}}</a>
            </li>
        </ul>

        <div class="tab-pane">
            <div class="row-fluid space-below">
                <keep-alive>
                    <component v-bind:is="activeTab.component" ></component>
                </keep-alive>
            </div>
        </div>
    </div>
</template>

<script>
    Array.prototype.filter = window.filter;
    const RestClient = use('EntradaJS/Http/RestClient');
    const ClinicalTabs = use('./Clinical.vue');
    const Modal = use('./Modal.vue');
    const MyRotations = use('./MyRotations.vue');
    const RotationLearners = use('./RotationLearners.vue');
    const RotationSchedulePath = use('./RotationSchedulePath.vue');
    const Multiselect = use('./Multiselect.vue');
    const validationMixin = use('./validationMixin.js');

    module.exports = {
        name: 'published-schedules',
        mixins: [validationMixin],
        props: ['title'],
        data() {
            return {
                loading_info: true,
                isEditingRotationName: false,
                parameters: {
                    draft_id: 0,
                    type: "rotation_stream"
                },
                draft: {
                    draft_title: "",
                },
                draft_id: 0,
                selected_drafts: [],
                activeTab: [],
                edit: {
                    draft_title: "",
                    authors: []
                },
                blocks: [],
                users: [],
                tabs: [
                    {
                        title: "Rotations",
                        component: "my-rotations",
                        href: "rotations"
                    },
                    {
                        title: "Learners",
                        component: "rotation-learners",
                        href: "learners"
                    },
                ]
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
            this.activeTab = this.tabs[0];

            if (typeof this.$getRoute().getParameter('tab') !== "undefined") {
                let active = this.tabs.filter(o => o.href ===  this.$getRoute().getParameter('tab'));
                this.activeTab = (active.length > 0 ? active[0] : this.tabs[0]);
            }

            if (typeof this.$getRoute().getParameter('draft_id') !== "undefined") {
                this.draft_id = this.$getRoute().getParameter('draft_id');
                this.parameters.draft_id = this.draft_id;
            }
            this.fetchUsers();
            this.fetchDraftInfo();
        },
        methods: {
            activateTab (tab) {
                this.activeTab = tab;
            },
            fetchDraftInfo() {
                this.loading_info = true;
                this.api.get('/clinical/draft-rotation-schedule/' + this.draft_id , this.parameters).then(response => {
                    this.draft = Object.assign({}, response.json());
                    this.edit.draft_title = this.draft.draft_title;
                    let authors = [];
                    this.draft.authors.forEach(function(author) {
                        if (author.user) {
                            authors.push({"id" : author.author_value, "fullname" : (author.user.lastname + ", " + author.user.firstname) });
                        }
                    });
                    this.edit.authors = authors;
                    this.fetchBlocks();
                    this.loading_info = false;
                });
            },
            saveDraft() {
                this.api.put('/clinical/draft-rotation-schedule/' + this.draft_id, this.edit).then(result => {
                    this.fetchDraftInfo();
                    this.isEditingRotationName = false;
                }).catch(error => {
                    this.catchError(error);
                });
            },
            fetchUsers() {
                this.api.get('/clinical/users/').then(response => {
                    this.users = response.json();
                });
            },
            fetchBlocks() {
                this.api.get('/clinical/rotation-schedule-templates/' + this.draft.cperiod_id).then(response => {
                    this.blocks = response.json();
                });
            },
            generateTabHref(href) {
                window.location.href ='#' + this.$generatePath('clinical.rotationschedule.drafts.edit', { draft_id : this.draft_id, tab: href });
            }
        },
        components: {
            ClinicalTabs,
            Modal,
            MyRotations,
            RotationLearners,
            RotationSchedulePath,
            Multiselect
        }
    };
</script>

<style>
    .pointer-override {
        cursor: pointer;
    }
</style>
