<template>
    <div class="rotation-learners">
        <div>
            <div class="pull-left space-right">
                <span><strong>Go To</strong></span>
                <ul class="nav nav-tabs">
                    <li><a href="#" @click.prevent="$refs.scheduler.navigator.scrollToPreviousBlock()"><i class="fa fa-arrow-left"></i></a></li>
                    <li><a href="#" @click.prevent="$refs.scheduler.navigator.scrollToBlockByDate(new Date())">Current Block</a></li>
                    <li><a href="#" @click.prevent="$refs.scheduler.navigator.scrollToNextBlock()"><i class="fa fa-arrow-right"></i></a></li>
                    <li><a href="#" @click.prevent="$refs.scheduler.navigator.scrollToDate(new Date())">Today</a></li>
                    <li><a href="#" @click.prevent="$refs.scheduler.navigator.scrollToStart()">Start</a></li>
                    <li><a href="#" @click.prevent="$refs.scheduler.navigator.scrollToEnd()">End</a></li>
                </ul>
            </div>

            <div class="pull-left space-left space-right">
                <span><strong>View</strong></span>
                <ul class="nav nav-tabs">
                    <li :class="{ 'active': view === Views.Block }"><a href="#" @click.prevent="setView(Views.Block)">Block</a></li>
                    <li :class="{ 'active': view === Views.Month }"><a href="#" @click.prevent="setView(Views.Month)">Month</a></li>
                    <li :class="{ 'active': view === Views.Quarter }"><a href="#" @click.prevent="setView(Views.Quarter)">Quarter</a></li>
                </ul>
            </div>

            <div class="pull-left space-left">
                <span style="display:inline-block;width:100px;"><strong>Zoom ({{ Math.round(scale * 100) }}%)</strong></span>
                <div class="range-input">
                    <a href=#"" :class="{'btn btn-small space-right': true, 'disabled-controls': minZoom}" @click.prevent="zoomOut"><i class="fa fa-minus"></i></a>
                    <input type="range" :min="0.5" :max="3.00" :step="0.1" :value="scale" @input="scale = Number($event.target.value)">
                    <a href="#" :class="{'btn btn-small space-left': true, 'disabled-controls': maxZoom}" @click.prevent="zoomIn"><i class="fa fa-plus"></i></a>
                    <a class="btn btn-small space-left" href="" @click.prevent="scale = 1">Reset <i class="fa fa-repeat"></i></a>
                </div>
            </div>
        </div>

        <div v-if="!isReady" class="clear">
            <p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i>Loading</p>
        </div>

        <rotation-scheduler ref="scheduler" v-if="isReady" :view="view" :blocks="blocks" :learners="learners" :scale="scale"></rotation-scheduler>
    </div>
</template>

<script>
    const Audience = use('./../Model/RotationScheduler/Entity/Audience');
    const Block = use('./../Model/RotationScheduler/Entity/Block');
    const BlockType = use('./../Model/RotationScheduler/Entity/BlockType');
    const User = use('./../Model/RotationScheduler/Entity/User');
    const Group = use('./../Model/RotationScheduler/Entity/Group');
    const AudienceType = use('./../Model/RotationScheduler/AudienceType');
    const Views = use('./../Model/RotationScheduler/Views');
    const DateTools = use('./../Model/Util/DateTools');

    const RestClient = use('EntradaJS/Http/RestClient');

    const importedComponents = {
        RotationScheduler: use('./RotationScheduler/Scheduler.vue')
    };

    module.exports = {
        name: 'rotation-learners',
        components: importedComponents,

        data() {
            return {
                isReady: false,
                learners: [],
                view: Views.Month,
                mode: 'learner',
                blocks: [],
                blockType: [],
                scale: 1,
                Views
            };
        },

        created() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
            if (typeof this.$getRoute().getParameter('draft_id') !== "undefined") {
                this.draft_id = this.$getRoute().getParameter('draft_id');
            }

            this.isReady = false;

            let promises = [];
            promises.push(this.getBlocks());
            promises.push(this.getLearners());
            Promise.all(promises).then(() => {
                this.isReady = true;
            });
        },
        activated() {
            sidebarBegone();
        },

        deactivated() {
            sidebarBeback();
        },
        computed: {
            minZoom() {
                if (this.scale > 0.5) {
                    return false;
                }
                else {
                    return true;
                }
            },
            maxZoom() {
                if (this.scale < 3.00) {
                    return false;
                }
                else {
                    return true;
                }
            }
        },

        methods: {
            zoomOut() {
                if (this.scale > 0.5) {
                    this.scale = this.scale - 0.5;
                }
            },
            zoomIn() {
                if (this.scale < 3.00) {
                    this.scale = this.scale + 0.5;
                }
            },
            setMode(mode) {
                this.mode = mode;
            },
            setView(view) {
                this.view = view;
            },
            getBlocks() {
                return this.api.get('/clinical/schedules/' + this.draft_id + '/template' , this.parameters).then(response => {
                    let data = response.json();

                    let bt = data.block_type;
                    this.blockType = new BlockType(bt.block_type_id, bt.name, bt.number_of_blocks);

                    let blocks = data.children;

                    let parsedBlocks = [];
                    let dayCount = 52 / this.blockType.number_of_blocks * 7;
                    for (let i = 0; i < blocks.length; i++) {
                        parsedBlocks.push(new Block(i + 1, DateTools.dateFromTimestamp(blocks[i].start_date), DateTools.dateFromTimestamp(blocks[i].end_date), dayCount));
                    }
                    this.blocks = parsedBlocks;
                });
            },
            getLearners() {
                return this.api.get('/clinical/schedules/' + this.draft_id + '/learners' , this.parameters).then(response => {
                    let data = response.json();
                    let learners = data.learners;

                    let parsedLearners = [];
                    for (let i = 0; i < learners.length; i++) {

                        let rec = learners[[i]];
                        let aud = rec.audience;

                        let parsedAudience = [];
                        if (aud.length > 0) {
                            for (let j=0; j < aud.length; j++) {
                                // get the dates of the assigned slot, either from the audience or the block
                                let startDate = aud[j].custom_start_date;
                                let endDate = aud[j].custom_end_date;
                                if (!startDate) {
                                    startDate = aud[j].rotation_schedule.start_date;
                                }
                                if (!endDate) {
                                    endDate = aud[j].rotation_schedule.end_date;
                                }

                                let tmpAudience = new Audience(
                                    aud[j].saudience_id,
                                    aud[j].schedule_id,
                                    aud[j].rotation_schedule.schedule_parent_id,
                                    aud[j].schedule_slot_id,
                                    aud[j].audience_type,
                                    aud[j].audience_value,
                                    DateTools.dateFromTimestamp(startDate),
                                    DateTools.dateFromTimestamp(endDate),
                                    aud[j].rotation_schedule.parent.code,
                                    aud[j].rotation_schedule.parent.course.course_code,
                                    aud[j].read_only ? true : false
                                );
                                parsedAudience.push(tmpAudience);
                            }
                        }

                        if (data.audience_type === AudienceType.User) {
                            parsedLearners.push(new User(rec.id, rec.number, rec.username, rec.firstname, rec.lastname, rec.email, rec.photo, rec.level, parsedAudience));
                        } else {
                            parsedLearners.push(new Group(i+1));
                        }
                    }
                    this.learners = parsedLearners;
                });
            }
        }
    };
</script>

<style>
    .range-input {
        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: flex;
        -webkit-box-align: center;
        -moz-box-align: center;
        -ms-flex-align: center;
        -webkit-align-items: center;
        align-items: center;
        height: 38px;
    }

    .reset-btn {
        margin-left: 10px;
    }

    .disabled-controls {
        background-color: #e1e7ec !important;
        cursor: not-allowed;
    }

    .disabled-controls i {
        color: #888f9f;
    }

    .disabled-controls:hover {
        background-color: #e1e7ec !important;
    }
</style>
