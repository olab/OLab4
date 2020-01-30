<template>
    <div class="my-learners">
        <clinical-tabs href="mylearners"></clinical-tabs>
        <div class="clearfix">
            <div class="span8">
                <div class="search-bar" class="search-bar" >
                    <div class="row-fluid space-below medium">
                        <div class="pull-left">
                            <input type="text" placeholder="Search Learners" class="input-large search-icon" v-model="parameters.search_term" @keyup.enter="fetchMyLearners()" v-i18n />
                            <button class="btn btn-success pull-right space-left" @click="exportLearners()" v-i18n>Download Enrolment</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <cohort-select v-model="parameters.cperiod" @change="fetchMyLearners()" ></cohort-select>
            </div>
        </div>
        <div id="search-container" class="hide space-below medium"></div>
        <div id="learner-summary"></div>
        <div id="learner-detail-container">
            <ul id="learner-cards" class="user-list-card">
                <p v-show="loading" class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p>
                <li v-show="!loading" class="learner-card visible" v-for="learner in learners" v-bind:value="learner.id">
                    <div class="user-card-wrapper">
                        <div class="user-card-container">
                            <img :src="learner.photo" class="img-circle" width="42">
                            <h3>
                                {{ learner.lastname }}, {{ learner.firstname }}
                                <span> {{ learner.number}} </span>
                            </h3>
                            <a :href="'mailto:' + learner.email">{{learner.email}}</a>
                        </div>
                        <div class="user-card-parent">
                            <div v-if="settings.cbme_enabled" class="user-card-child user-card-child-divider">
                                <a :href="learner.cbme_link" class="all-assessments learner-dashboard" v-i18n> CBME &rtrif;</a>
                            </div>
                            <div class="user-card-child user-card-child-divider">
                                <a :href="learner.assessment" class="all-assessments" v-i18n>Assessments &rtrif;</a>
                            </div>
                            <div :class="{'user-card-child user-card-child-divider': learner.logbook, 'user-card-child': !learner.logbook }">
                                <a :href="generateLeavePath(learner.id)" class="all-assessments" v-i18n>Leave tracking &rtrif; </a>
                            </div>
                            <div v-if="learner.logbook" class="user-card-child">
                                <a :href="learner.logbook_link" class="all-assessments" v-i18n>Logbook &rtrif; </a>
                            </div>
                        </div>
                    </div>
                </li>
                <div v-show="!loading" v-if="learners.length == 0">
                    <p class="no-search-targets" v-i18n>No users found matching your search</p>
                </div>
            </ul>
        </div>
    </div>
</template>

<script>
    const RestClient = use('EntradaJS/Http/RestClient');
    const CohortSelect = use('./CohortSelect.vue');
    const ClinicalTabs = use('./Clinical.vue');
    module.exports = {
        name: 'my-learners',
        props: ['title'],
        data() {
            return {
                loading: true,
                learners: [],
                parameters: {
                    search_term: "",
                    cperiod: "0",
                },
                settings: [],
            };
        },
        mounted() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
        },
        methods: {
            fetchMyLearners() {
                this.api.get('/clinical/my_learners', this.parameters).then(response => {
                    let data = response.json();
                    this.learners = data["learners"];
                    this.settings = data["settings"];
                    this.loading = false;
                });
            },
            generateLeavePath(user_id) {
                return "#" + this.$generatePath('clinical.leavetracking', {user_id: user_id })
            },
            exportLearners() {
                //ToDo: export learners
            }
        },
        components: {
            CohortSelect,
            ClinicalTabs,
        }
    };
</script>

<style>
    .img-circle {
        border-radius: 100%;
    }

    .user-list-card {
        list-style-type: none;
        margin: 0 -10px;
        padding: 0;
    }

    .user-list-card li {
        width: 50%;
        float: left;
        margin: 0 !important;
        padding: 10px;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }

    .user-card-wrapper {
        border: 1px solid #d9dee2;
        border-radius: 4px;
        overflow: hidden;
        background-color: #f4f7fa;
    }

    .user-card-container {
        padding: 20px;
    }

    .user-list-card img {
        float: left;
        margin-right: 15px;
        height:42px;
        width: 42px;
    }

    .user-list-card h3{
        line-height: 20px;
        margin-bottom: 2px;
        margin-top: 0;
    }

    .user-list-card h3 span,
    .assessment-target-media-list h3 span {
        color: #8b959d;
        display: inline-block;
        font-size: 12px;
        font-weight: normal;
        margin-left: 10px;
        float: right;
    }

    .user-list-card a {
        display: inline-block;
        font-size: 15px;
    }

    .user-card-child a {
        background: #fff;
        border-top: 1px solid #d9dee2;
        display: block;
        padding-bottom: 10px;
        padding-top: 10px;
        color: #000;
        font-family: "Lucida Grande", Geneva, Verdana, Arial, Helvetica, sans-serif;
        font-size: 13px;
        text-align: center;
        -webkit-transition: .2s;
        -moz-transition: .2s;
        -o-transition: .2s;
        transition: .2s;
    }

    .user-card-child a:hover,
    .user-card-child a:focus {
        text-decoration: none;
        background-color: #ecf0f3;
    }

    .user-card-child a:active,
    .user-card-child a:focus:active {
        text-decoration: none;
        background-color: #e1e7ec;
    }

    .user-card-parent {
        width: 100%;
        display: table;
        table-layout: fixed;
    }

    .user-card-child {
        display: table-cell;
        text-align: center;
    }

    .user-card-child-divider {
        border-right: 1px solid #d9dee2;
    }

    p.no-search-targets {
        color: #858B93;
        text-align: center;
    }
</style>