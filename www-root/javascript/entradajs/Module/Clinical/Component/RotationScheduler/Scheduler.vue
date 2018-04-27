<template>
    <div :class="{'scheduler': true, 'modal-loading-state': modalLoadingState }">
        <div class="scheduler-column-left">
            <div ref="corner-pane" class="scheduler-corner-pane">
                Learner
            </div>

            <div ref="side-pane" class="scheduler-side-pane">
                <div v-for="learner in learners" class="scheduler-row">
                    <learner-cell :learner="learner" :id="learner.id" :key="learner.id"></learner-cell>
                </div>
            </div>
        </div>

        <div class="scheduler-column-right">
            <div ref="header-pane" class="scheduler-header-pane">
                <section v-if="!isBlockView()">
                    <div v-for="(segment, index) in segments" class="scheduler-segment" :style="{ left: segment.x1 + 'px', width: segment.width + 'px' }" @click="navigator.scrollToSegment(segment)">
                        <span>{{ segment.name }}</span>
                    </div>
                </section>
                <section>
                    <div v-for="block in blocks" class="scheduler-block" :style="{ left: block.shape.x1 + 'px', width: block.shape.width + 'px' }" @click="navigator.scrollToBlock(block)">
                        <small>({{ block.startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }})</small>
                        <span v-if="isQuarterView()">BK {{ block.id }}</span>
                        <span v-else>Block {{ block.id }}</span>
                        <small>({{ block.endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }})</small>
                    </div>
                </section>
            </div>

            <div ref="content-pane" class="scheduler-content-pane">
                <div class="rail-set">
                    <rail v-for="learner in learners" :key="learner.id" :segments="segments" :entries="learner.audience" :blocks="blocks" :learner="learner" :increment-width="incrementWidth" :scale="scale" @smart-click="handleRailClick" :color-assignments="colorsByTitle"></rail>
                </div>
                <div ref="rail-set-graduation-layer" class="rail-set-graduation-layer">
                    <div v-for="segment in segments" class="segment" :style="{ left: segment.x1 + 'px', width: segment.width + 'px' }">
                        <div class="increment" v-for="index in segment.increments" :style="{ width: incrementWidth + 'px' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <modal v-if="modalState" title="Book Slot" :deletebutton="selected.entry ? 'Delete' : ''" v-on:ok="saveSelectedEntry" v-on:delete="deleteSelectedEntry" v-on:hide="resetState">
            <main>
                <div class="form-horizontal">
                    <div v-if="selected.entry" class="modal-title">
                        <strong>Edit Booking</strong>
                    </div>
                    <div v-else class="modal-title">
                        <strong>Add New Booking</strong>
                    </div>
                    <div :class="{ 'control-group' : true, 'error' : validation_errors.rotation_id }">
                        <label class="control-label">Rotation</label>
                        <div class="controls">
                            <select v-if="loadingState">
                                <option value="0">Loading Rotations ...</option>
                            </select>
                            <select v-else-if="selected.block.schedules" v-model="selected.rotation_id" v-on:change="fillBlocks">
                                <option disabled v-bind:value="0">Select a Rotation</option>
                                <optGroup v-if="selected.block.schedules.onService.length" label="On Service Rotations">
                                    <option v-for="rotation in selected.block.schedules.onService" v-bind:value="rotation.schedule_id">
                                        {{ rotation.title }}
                                    </option>
                                </optGroup>
                                <optGroup v-if="selected.block.schedules.offService.length" label="Off Service Rotations">
                                    <option v-for="rotation in selected.block.schedules.offService" v-bind:value="rotation.schedule_id">
                                        {{ rotation.course.course_code }} : {{ rotation.title }}
                                    </option>
                                </optGroup>
                            </select>
                            <select v-else>
                                <option value="0">No Rotations available in the selected Block</option>
                            </select>
                            <span class="help-block" v-if="validation_errors.rotation_id"> {{validation_errors.rotation_id[0]}} </span>
                        </div>
                    </div>
                    <div :class="{ 'control-group' : true, 'error' : validation_errors.rotation_block_id }" v-if="selected.rotation_id && selected.rotation.blocks">
                        <label class="control-label">Blocks</label>
                        <div class="controls">
                            <select v-if="loadingState">
                                <option value="0">Loading blocks ...</option>
                            </select>
                            <select v-else-if="selected.rotation.blocks.length" v-model="selected.rotation_block_id" v-on:change="fillDates">
                                <option disabled v-bind:value="0">Select a Block</option>
                                <option v-for="block in selected.rotation.blocks" v-bind:value="block.schedule_id">
                                    {{ block.title }} ({{ block.block_type.name }}) : {{ block.start_date.toDateString() }} - {{ block.end_date.toDateString() }}
                                </option>
                            </select>
                            <select v-else>
                                <option value="0">No Blocks available in the selected Rotation</option>
                            </select>
                            <span class="help-block" v-if="validation_errors.rotation_block_id"> {{validation_errors.rotation_block_id[0]}} </span>
                        </div>
                    </div>
                    <div v-if="selected.rotation_block_id && selected.rotationBlock.slots">
                        <div :class="{ 'control-group' : true, 'error' : validation_errors.slot_id }">
                            <label class="control-label">Select Slot</label>
                            <div class="controls">
                                <select v-if="selected.rotationBlock.slots.length" v-model="selected.slot_id" v-on:change="selectSlot">
                                    <option disabled v-bind:value="0">Select a Slot</option>
                                    <option v-for="slot in selected.rotationBlock.slots" v-bind:value="slot.schedule_slot_id">
                                        {{ (slot.slot_type_id) === 1 ? "On Service" : "Off Service" }}{{ slot.site_id ? ": Site - " + slot.site.site_name : "" }}
                                    </option>
                                </select>
                                <select v-else>
                                    <option value="0">No Slots available in the selected Block</option>
                                </select>
                                <span class="help-block" v-if="validation_errors.slot_id"> {{validation_errors.slot_id[0]}} </span>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="strict">Customize Dates</label>
                            <div class="controls">
                                <input type="checkbox" v-model="selected.customDates" id="strict"v-on:change="toggleCustomDate" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Rotation Start Date</label>
                            <div class="controls">
                                <input :disabled="!selected.customDates" id="start-date" ref="start-date" type="date" class="span3" v-model="selected.inputStartDate"/>
                                <span @click="openDate('start-date')" class="add-on"><i class="icon-calendar"></i></span>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Rotation End Date</label>
                            <div class="controls">
                                <input :disabled="!selected.customDates" id="end-date" type="date" ref="end-date" class="span3"  v-model="selected.inputEndDate"/>
                                <span @click="openDate('end-date')" class="add-on"><i class="icon-calendar"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </modal>
    </div>
</template>

<script>
    const LayoutManager = use('./../../Model/RotationScheduler/Layout/LayoutManager');
    const Navigator = use('./../../Model/RotationScheduler/Navigator');
    const SegmentGenerator = use('./../../Model/RotationScheduler/Segment/Generator');
    const Views = use('./../../Model/RotationScheduler/Views');
    const RestClient = use('EntradaJS/Http/RestClient');
    const Rotation = use('./../../Model/RotationScheduler/Entity/Rotation');
    const User = use('./../../Model/RotationScheduler/Entity/User');
    const Group = use('./../../Model/RotationScheduler/Entity/Group');
    const Audience = use('./../../Model/RotationScheduler/Entity/Audience');
    const SlotSelection = use('./../../Model/RotationScheduler/SlotSelection');
    const DateTools = use('./../../Model/Util/DateTools');
    const validationMixin = use('./../validationMixin.js');

    const importedComponents = {
        LearnerCell: use('./LearnerCell.vue'),
        Rail: use('./Rail.vue'),
        Modal: use('./../Modal.vue')
    };

    module.exports = {
        name: 'scheduler',
        components: importedComponents,
        mixins: [validationMixin],
        props: {
            learners: {
                type: Array,
                default: () => []
            },
            blocks: {
                type: Array,
                default: () => []
            },
            scale: {
                type: Number,
                default: 1
            },
            view: {
                type: String,
                required: false,
                default: Views.Month
            }
        },
        data() {
            return {
                modalState: false,
                segments: [],
                loadingState: false,
                selected: new SlotSelection(),
                refresh: false,
                colorChipCount: 0,
                colorShift: 17,
                colorsByTitle: [],
                modalLoadingState: false
            };
        },
        created() {
            this.api = new RestClient(API_URL, 'Bearer ' + JWT);
            if (typeof this.$getRoute().getParameter('draft_id') !== "undefined") {
                this.draft_id = this.$getRoute().getParameter('draft_id');
            }
            this.generateSegments();
            this.calculatePositions();
        },
        mounted() {
            this.navigator = new Navigator(this.$refs['content-pane'], this.view, this.blocks, this.segments, this.entries, this.incrementWidth);

            this.$refs['content-pane'].addEventListener('scroll', e => {
                this.$refs['rail-set-graduation-layer'].style.top = e.target.scrollTop + 'px';
                this.$refs['header-pane'].scrollLeft = e.target.scrollLeft;
                this.$refs['side-pane'].scrollTop = e.target.scrollTop;
            });

            this.$el.style.setProperty('--header-height', this.$refs['header-pane'].offsetHeight + 'px');

            this.generateColors();
        },
        watch: {
            learners(){
                this.generateColors();
            },
            scale() {
                this.generateSegments();
                this.calculatePositions();
            },
            view() {
                this.generateSegments();
                this.calculatePositions();

                this.$nextTick(() => {
                    this.$el.style.setProperty('--header-height', this.$refs['header-pane'].offsetHeight + 'px');
                });
            },
            refresh() {
                if (this.refresh === true) {
                    this.generateSegments();
                    this.calculatePositions();
                    this.refresh = false;
                }
            }
        },
        computed: {
            incrementWidth() {
                let baseWidth = 0;

                switch(this.view) {
                    case Views.Block:
                        baseWidth = 12;
                        break;
                    case Views.Month:
                        baseWidth = 10;
                        break;
                    case Views.Quarter:
                        baseWidth = 5;
                        break;
                }
                return Math.round(baseWidth * this.scale);
            },
        },
        methods: {
            generateColors() {
                var self = this;
                this.learners.forEach(function(learner) {
                    learner.audience.forEach(function(aud) {
                        if (!aud.readonly) {
                            var titleExists = false;
                            if (self.colorsByTitle.length != 0) {
                                self.colorsByTitle.forEach(function(color) {
                                    if (color.title === aud.title) {
                                        titleExists = true;
                                    }
                                });
                            }

                            if (!titleExists) {
                                self.colorsByTitle.push({
                                    title: aud.title,
                                    color: self.colorChipCount
                                });
                                if (self.colorChipCount < 360) {
                                    self.colorChipCount = self.colorChipCount +  40;
                                }
                                else if (self.colorChipCount >= 360) {
                                    self.colorChipCount = self.colorShift;
                                    self.colorShift = self.colorShift + 17;
                                }
                                else if (self.colorShift > 360){
                                    self.colorsByTitle = 0;
                                    self.colorShift = 17;
                                }
                            }
                        }
                    });
                });
            },
            generateSegments() {
                let generator = new SegmentGenerator();

                let startDate = this.blocks[0].startDate;
                let endDate = this.blocks[this.blocks.length - 1].endDate;

                switch(this.view) {
                    case Views.Block:
                        this.segments = generator.generateBlockSegments(this.blocks, this.incrementWidth);
                        break;
                    case Views.Month:
                        this.segments = generator.generateMonthSegmentsInRange(startDate, endDate, this.incrementWidth);
                        break;
                    case Views.Quarter:
                        this.segments = generator.generateQuarterSegmentsInRange(startDate, endDate, this.incrementWidth);
                        break;
                }
            },
            calculatePositions() {
                let layoutManager;
                let railHeight = 68;

                if(this.isQuarterView()) {
                    let quarterIndex = Math.floor(this.blocks[0].startDate.getMonth() / 3);
                    let startDate = new Date(this.blocks[0].startDate.getFullYear(), quarterIndex * 3, 1);

                    layoutManager = new LayoutManager(this.incrementWidth, railHeight, startDate, this.blocks[this.blocks.length - 1].endDate);
                }
                else {
                    layoutManager = new LayoutManager(this.incrementWidth, railHeight, this.blocks[0].startDate, this.blocks[this.blocks.length - 1].endDate);
                }

                for(let learner of this.learners) {
                    layoutManager.arrangeAudiences(learner.audience);
                }

                for(let block of this.blocks) {
                    layoutManager.positionShapeX(block.shape, block.startDate, block.endDate);
                }
            },
            saveSelectedEntry() {

                // validate
                this.validation_errors = {};
                if (!this.selected.rotation_id) {
                    this.validation_errors.rotation_id = ["Please select a rotation"];
                }
                if (!this.selected.rotation_block_id) {
                    this.validation_errors.rotation_block_id = ["Please select a block"];
                }
                if (!this.selected.slot_id) {
                    this.validation_errors.slot_id = ["Please select a Slot"];
                }

                if (!this.selected.rotation_id || !this.selected.rotation_block_id || !this.selected.slot_id) {
                    return;
                }

                // put the input start and end date back to a Date object so we can see if it is different from the block
                this.selected.customStartDate = DateTools.dateFromStartDate(this.selected.inputStartDate);
                this.selected.customEndDate = DateTools.dateFromEndDate(this.selected.inputEndDate);

                // for put/post to API, exact match to the database naming and with Unix timestamps
                let audience = {
                    saudience_id: (this.selected.audience_id ? this.selected.audience_id : null),
                    schedule_id: (this.selected.rotation_block_id ? this.selected.rotation_block_id : 0),
                    schedule_slot_id: (this.selected.slot_id ? this.selected.slot_id : 0),
                    audience_type: (this.selected.audience_type ? this.selected.audience_type : null),
                    audience_value: (this.selected.audience_value ? this.selected.audience_value : 0),
                    custom_start_date: (this.selected.customStartDate.getTime() !== this.selected.startDate.getTime() ? DateTools.timestampFromDate(this.selected.customStartDate) : null),
                    custom_end_date: (this.selected.customEndDate.getTime() !== this.selected.endDate.getTime() ? DateTools.timestampFromDate(this.selected.customEndDate) : null),
                };

                // New Audience object for display
                let newAudience = new Audience(
                    audience.saudience_id,
                    audience.schedule_id,
                    this.selected.rotation_id,
                    audience.schedule_slot_id,
                    audience.audience_type,
                    audience.audience_value,
                    audience.custom_start_date ? this.selected.customStartDate : this.selected.startDate,
                    audience.custom_end_date ? this.selected.customEndDate : this.selected.endDate,
                    this.selected.rotation.code,
                    this.selected.rotation.course.course_code
                );

                if (audience.schedule_id === 0 ||
                    audience.schedule_slot_id === 0 ||
                    audience.schedule_id === 0 ||
                    !audience.audience_type ||
                    audience.audience_value === 0) {
                    console.log("something missing!");
                } else {
                    if (this.selected.entry) {
                        // save existing entry
                        this.api.put("/clinical/schedules/" + this.draft_id + "/audience/" + this.selected.audience_id, audience).then(result => {
                            //this.removeAudienceEntry();
                            //this.addAudienceEntry(newAudience);
                            this.getLearner(this.selected.audience_type, this.selected.audience_value);
                            this.resetState();
                        }).catch(error => {
                            this.catchError(error);
                        });
                    } else {
                        // add new entry
                        this.api.post("/clinical/schedules/" + this.draft_id + "/audience", audience).then(result => {
                            //this.addAudienceEntry(newAudience);
                            this.getLearner(this.selected.audience_type, this.selected.audience_value);
                            this.resetState();
                        }).catch(error => {
                            this.catchError(error);
                        });
                    }
                }
            },
            deleteSelectedEntry() {
                if (this.selected.audience_id) {
                    this.api.delete("/clinical/schedules/" + this.draft_id + "/audience/" + this.selected.audience_id).then(response => {
                        //this.removeAudienceEntry();
                        this.getLearner(this.selected.audience_type, this.selected.audience_value);
                        this.resetState();
                    }).catch(error => {
                        this.catchError(error);
                    });
                } else {
                    console.log("delete called but there is no selected entry to delete");
                }
            },
            addAudienceEntry(audience) {
                let learnerIndex = this.learners.findIndex(item => {
                    return item.id === this.selected.audience_value;
                });
                if (learnerIndex >= 0) {
                    // this does not trigger Vue to redisplay, though
                    this.learners[learnerIndex].audience.push(audience);
                }
            },
            removeAudienceEntry() {
                let learnerIndex = this.learners.findIndex(item => {
                    return item.id === this.selected.audience_value;
                });
                if (learnerIndex >= 0) {
                    let audienceIndex = this.learners[learnerIndex].audience.findIndex(item => {
                        return item.id === this.selected.audience_id;
                    });
                    if (audienceIndex >= 0) {
                        this.learners[learnerIndex].audience.splice(audienceIndex, 1);
                    }
                }
                //let audienceIndex = this.selected.learner.audience.findIndex(item => {
                //    return item.id === this.selected.audience_id;
                //});
                //if (audienceIndex) {
                //    this.selected.learner.audience.splice(audienceIndex, 1);
                //}
            },
            openModal() {
                this.modalState = true;
            },
            resetState() {
                this.selected = null;
                this.modalState = false;
                this.validation_errors = {};
            },
            fillBlocks() {
                this.validation_errors = {};
                let promise = Promise.resolve();
                if (this.selected.rotation_id) {
                    // rotation changed, clear the selected block and slot
                    this.selected.rotation_block_id = 0;
                    this.selected.slot_id = 0;
                    this.selected.customDates = 0;
                    // find the rotation array entry
                    let onService = this.selected.block.schedules.onService.filter(item => {
                        return item.schedule_id === this.selected.rotation_id;
                    });
                    let offService = this.selected.block.schedules.offService.filter(item => {
                        return item.schedule_id === this.selected.rotation_id;
                    });

                    if (offService.length) {
                        this.selected.offService = true;
                        this.selected.rotation = offService[0];
                    } else if (onService.length) {
                        this.selected.offService = false;
                        this.selected.rotation = onService[0];
                    } else {
                        console.log("cannot find the selected rotation");
                    }

                    if (!this.selected.rotation.blocks.length) {
                        this.loadingState = true;
                        let parameters = {
                            start_date: this.selected.block.startDate.getTime() / 1000,
                            end_date: this.selected.block.endDate.getTime() / 1000
                        }
                        promise = this.api.get("/clinical/schedules/" + this.draft_id + "/rotations/" + this.selected.rotation_id + "/blocks" , parameters).then(response => {
                            let data = response.json();
                            let blocks = data.blocks;

                            let parsedBlocks = [];
                            for (let block of blocks) {
                                let rotation = this.populateRotation(block, this.selected.offService);
                                parsedBlocks.push(rotation);
                            }
                            this.selected.rotation.blocks = parsedBlocks;
                            this.loadingState = false;
                        });
                    }
                }
                return promise;
            },
            fillDates() {
                this.validation_errors = {};
                if (this.selected.rotation_block_id) {
                    // block changed, clear the selected slot
                    this.selected.slot_id = 0;
                    this.selected.customDates = 0;
                    let block = this.selected.rotation.blocks.filter(item => {
                        return item.schedule_id === this.selected.rotation_block_id;
                    });

                    if (block.length) {
                        this.selected.rotationBlock = block[0];
                        this.selected.startDate = block[0].start_date;
                        this.selected.endDate = block[0].end_date;
                        this.selected.customStartDate = this.selected.startDate;
                        this.selected.customEndDate = this.selected.endDate;
                        this.selected.inputStartDate = DateTools.formatDate(this.selected.startDate);
                        this.selected.inputEndDate = DateTools.formatDate(this.selected.endDate);
                    }
                }
            },
            selectSlot() {
                this.validation_errors = {};
            },
            toggleCustomDate() {
                if (!this.selected.customDates) {
                    this.selected.customStartDate = this.selected.startDate;
                    this.selected.customEndDate = this.selected.endDate;
                    this.selected.inputStartDate = DateTools.formatDate(this.selected.startDate);
                    this.selected.inputEndDate = DateTools.formatDate(this.selected.endDate);
                }
            },
            handleRailClick(payload) {
                if (!(payload.entry && payload.entry.readonly === true)) {
                    this.modalLoadingState = true;

                    let promises = [];
                    if(!payload.block.schedules) {
                        promises.push(this.getRotations(payload.block));
                    }

                    Promise.all(promises).then(() => {

                        let promisesInner = [];
                        this.selected = new SlotSelection();
                        this.selected.block = payload.block;
                        this.selected.learner = payload.learner;

                        if (payload.learner.username) {
                            this.selected.audience_type = "proxy_id";
                            this.selected.audience_value = payload.learner.id;
                        } else {
                            this.selected.audience_type = "cgroup_id";
                            this.selected.audience_value = payload.learner.id;
                        }

                        if(payload.entry) {
                            this.selected.entry = payload.entry;
                            this.selected.audience_id = payload.entry.id;
                            this.selected.rotation_id = payload.entry.schedule;
                            promisesInner.push(this.fillBlocks());
                        }

                        Promise.all(promisesInner).then(() => {
                            if(payload.entry) {
                                this.selected.rotation_block_id = payload.entry.block;
                                this.fillDates();
                                this.selected.slot_id = payload.entry.slot;
                                this.selected.customStartDate = payload.entry.startDate;
                                this.selected.customEndDate = payload.entry.endDate;
                                this.selected.inputStartDate = DateTools.formatDate(this.selected.customStartDate);
                                this.selected.inputEndDate = DateTools.formatDate(this.selected.customEndDate);
                                this.selected.customDates = (
                                    this.selected.customStartDate.getTime() !== this.selected.startDate.getTime() ||
                                    this.selected.customEndDate.getTime() !== this.selected.endDate.getTime()
                                );
                            }
                            this.modalLoadingState = false;
                            this.openModal();
                        });
                    });
                } else {
                    console.log('Ignoring click on readonly entry: ', payload.entry);
                }
            },
            isBlockView() {
                return this.view === Views.Block;
            },
            isMonthView() {
                return this.view === Views.Month;
            },
            isQuarterView() {
                return this.view === Views.Quarter;
            },
            setZoomScale(number) {
                this.scale = number;
            },
            getRotations(block) {
                this.loadingState = true;
                let parameters = {
                    start_date: block.startDate.getTime() / 1000,
                    end_date: block.endDate.getTime() / 1000
                };
                return this.api.get('/clinical/schedules/' + this.draft_id + '/rotations', parameters).then(response => {
                    let data = response.json();

                    let parsedOnServiceRotations = [];
                    let parsedOffServiceRotations = [];

                    if (data.rotations) {
                        for (let record of data.rotations) {
                            let rotation = this.populateRotation(record, false);
                            parsedOnServiceRotations.push(rotation);
                        }
                    }

                    if (data.off_service) {
                        for (let record of data.off_service) {
                            let rotation = this.populateRotation(record, true);
                            let blockList = [];
                            if (record.blocks) {
                                for (let blk of record.blocks) {
                                    let block = this.populateRotation(blk, true);
                                    blockList.push(block);
                                }
                                rotation.blocks = blockList;
                            }
                            parsedOffServiceRotations.push(rotation);
                        }
                    }
                    block.schedules = {
                        onService: parsedOnServiceRotations,
                        offService: parsedOffServiceRotations
                    };
                    this.loadingState = false;
                });
            },
            populateRotation(rec, offService) {
                let rotation = new Rotation(
                    rec.schedule_id,
                    rec.title,
                    rec.code,
                    rec.description,
                    rec.schedule_type,
                    rec.schedule_parent_id,
                    rec.organisation_id,
                    rec.course_id,
                    rec.region_id,
                    rec.facility_id,
                    rec.cperiod_id,
                    DateTools.dateFromTimestamp(rec.start_date),
                    DateTools.dateFromTimestamp(rec.end_date),
                    rec.block_type_id,
                    rec.draft_id,
                    rec.schedule_order,
                    rec.copied_from
                );
                rotation.sites = (rec.sites ? rec.sites : []);
                rotation.block_type = (rec.block_type ? rec.block_type : []);
                rotation.course = (rec.course ? rec.course : []);
                rotation.blocks = (rec.blocks ? rec.blocks : []);
                let slotList = [];
                if (rec.slots) {
                    for (let slot of rec.slots) {
                        if (offService && slot.slot_type_id === 2) {
                            slotList.push(slot);
                        } else if (!offService && slot.slot_type_id !== 2) {
                            slotList.push(slot);
                        }
                    }
                }
                rotation.slots = slotList;
                return rotation;
            },
            getSlots(block) {
                this.loadingState = true;
                let parameters = {
                    start_date: block.startDate.getTime() / 1000,
                    end_date: block.endDate.getTime() / 1000
                };
                this.api.get('/clinical/schedules/' + this.draft_id + '/slots' , parameters).then(response => {
                    let data = response.json();

                    let parsedSchedules = [];
                    for (let schedule of data) {
                        if (schedule.children.length) {
                            schedule.start_date = DateTools.dateFromTimestamp(schedule.start_date*1000);
                            schedule.end_date = DateTools.dateFromTimestamp(schedule.end_date*1000);
                            for (let block of schedule.children) {
                                block.start_date = DateTools.dateFromTimestamp(block.start_date*1000);
                                block.end_date = DateTools.dateFromTimestamp(block.end_date*1000);
                            }
                            parsedSchedules.push(schedule);
                        }
                    }
                    block.schedules = parsedSchedules;
                    this.loadingState = false;
                });
            },
            openDate(input) {
                this.$refs[input].click();
            },
            getLearner(type, id) {
                let parameters = {
                    audience_type: type,
                    audience_value: id
                };
                return this.api.get('/clinical/schedules/' + this.draft_id + '/learners' , parameters).then(response => {
                    let data = response.json();
                    let learners = data.learners;

                    let rec = learners[0];
                    let parsedLearner = {};
                    let parsedAudience = [];
                    if (rec.audience.length > 0) {
                        for (let aud of rec.audience) {
                            // get the dates of the assigned slot, either from the audience or the block
                            let startDate = aud.custom_start_date;
                            let endDate = aud.custom_end_date;
                            if (!startDate) {
                                startDate = aud.rotation_schedule.start_date;
                            }
                            if (!endDate) {
                                endDate = aud.rotation_schedule.end_date;
                            }

                            let tmpAudience = new Audience(
                                aud.saudience_id,
                                aud.schedule_id,
                                aud.rotation_schedule.schedule_parent_id,
                                aud.schedule_slot_id,
                                aud.audience_type,
                                aud.audience_value,
                                DateTools.dateFromTimestamp(startDate),
                                DateTools.dateFromTimestamp(endDate),
                                aud.rotation_schedule.parent.code,
                                aud.rotation_schedule.parent.course.course_code,
                                aud.read_only ? true : false
                            );
                            parsedAudience.push(tmpAudience);
                        }
                    }

                    if (data.audience_type === "proxy_id") {
                        parsedLearner = new User(rec.id, rec.number, rec.username, rec.firstname, rec.lastname, rec.email, rec.photo, rec.level, parsedAudience);
                        //parsedLearner = new User(rec.id, rec.number, rec.username, rec.firstname, rec.lastname, rec.email, rec.photo, rec.level, []);
                    } else {
                        parsedLearner = new Group(i+1);
                    }

                    let learnerIndex = this.learners.findIndex(item => {
                        return item.id === id;
                    });
                    if (learnerIndex >= 0) {
                        this.learners.splice(learnerIndex, 1, parsedLearner);
                        this.refresh = true;
                    }
                });
            }
        }
    };
</script>

<style>
    .scheduler {
        box-sizing:border-box;
        border:2px solid #D5DEE0;
        border-bottom:10px solid #D5DEE0;
        border-radius:10px;
        width:100%;
        height:40em;
        overflow:hidden;

        --header-height:0px;
    }

    .scheduler .scheduler-column-left {
        float:left;
        box-sizing:border-box;
        border-right:1px solid #D5DEE0;
        width:300px;
        height:100%;
    }

    .scheduler .scheduler-column-right {
        float:left;
        box-sizing:border-box;
        width:calc(100% - 300px);
        height:100%;
    }

    .scheduler .scheduler-corner-pane {
        box-sizing:border-box;
        box-shadow: 0 -1px 0 #D5DEE0 inset;
        font-weight:bold;
        text-align:center;
        width:100%;
        height:var(--header-height);
        padding:6px 0;
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
        justify-content: center;
    }

    .scheduler .scheduler-side-pane {
        box-sizing:border-box;
        width:100%;
        height:calc(100% - var(--header-height));
        overflow-x:hidden;
        overflow-y:hidden;
    }

    .scheduler .scheduler-side-pane .scheduler-row {
        box-sizing:border-box;
        box-shadow: 0 1px 0 #D5DEE0 inset;
        height:70px;
        padding:5px;
    }

    .scheduler .scheduler-side-pane .scheduler-row:first-child {
        box-shadow: 0 0 0;
    }

    .scheduler .scheduler-side-pane .scheduler-row:last-child {
        box-shadow: 0 1px 0 #D5DEE0 inset, 0 -1px 0 #D5DEE0 inset;
    }

    .scheduler .scheduler-header-pane {
        position:relative;
        font-weight:bold;
        overflow-x:hidden;
    }

    .scheduler .scheduler-header-pane section {
        width:fit-content;
        height:31px;
        white-space:nowrap;
        font-size:0;
    }

    .scheduler .scheduler-header-pane section div {
        display:inline-block;
        border-right:1px solid #D5DEE0;
        font-size:14px;
        box-sizing:border-box;
        text-align:center;
        width:300px;
        padding:5px 0;
        cursor:pointer;
        overflow:hidden;
        box-shadow: 0 -1px 0 #D5DEE0 inset;
    }

    .scheduler .scheduler-header-pane section div:last-of-type {
        border-right:1px solid #D5DEE0;
    }

    .scheduler .scheduler-header-pane section div small {
        font-weight:normal;
    }

    .scheduler .scheduler-content-pane {
        position:relative;
        box-sizing:border-box;
        border-left:none;
        border-right:none;
        width:100%;
        height:calc(100% - var(--header-height));
        overflow:auto;
    }

    .scheduler .scheduler-content-pane .rail-set-graduation-layer {
        position:absolute;
        pointer-events:none;
        width:100%;
        height:100%;
        top:0;
        left:0;
        z-index:20;
    }

    .scheduler .scheduler-content-pane .rail-set-graduation-layer .segment {
        position:absolute;
        box-sizing:border-box;
        font-size:8px;
        height:100%;
        overflow:hidden;
        background-color: white;
    }

    .scheduler .scheduler-content-pane .rail-set-graduation-layer .segment:last-of-type {
        border-right:1px solid #777;
    }

    .scheduler .scheduler-content-pane .rail-set-graduation-layer .increment {
        display:inline-block;
        box-sizing:border-box;
        width:10px;
        height:100%;
    }

    .scheduler .scheduler-content-pane .rail-set-graduation-layer .increment:hover {
        background-color: red;
    }


    .scheduler .scheduler-content-pane .rail-set-graduation-layer .increment:nth-last-of-type(odd) {
        background-color: #f8f8f8;
    }

    .scheduler .scheduler-content-pane .rail-set-graduation-layer .increment:last-child {
        box-shadow: -1px 0 0 #d5dee0 inset;
    }

    .scheduler .scheduler-segment {
        position:absolute;
        height:33px;
    }

    .scheduler .scheduler-block {
        position:absolute;
    }

    .scheduler .pane-shadow-right {
        box-shadow:inset -7px 0 5px -3px rgba(0,0,0,0.3);
    }

    .scheduler .pane-shadow-left {
        box-shadow:inset 7px 0 5px -3px rgba(0,0,0,0.3);
    }

    .scheduler select {
        width: 75%;
    }

    .modal-title {
        padding-bottom: 15px;
    }

    #start-date {
        padding: 5px 10px;
    }

    #start-date:hover {
        padding: 5px 10px;
    }

    #end-date {
        padding: 5px 10px;
    }

    #end-date:hover {
        padding: 5px 10px;
    }

    .modal-loading-state {
        opacity: 0.15;
        -webkit-transition: opacity 0.4s;
        -moz-transition: opacity 0.4s;
        -o-transition: opacity 0.4s;
        transition: opacity 0.4s;
    }

    .loader-wrapper {
        position: absolute;
        top: 775px;
        left: 0;
        bottom: 0;
        right: 0;
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
        justify-content: center;
    }
</style>
