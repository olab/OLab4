<template>
    <div class="rail" :style="{ width: railWidth + 'px' }" @click="handleClick">
        <div ref="backgroundLayer" class="layer background-layer"></div>

        <div ref="dataLayer" class="layer data-layer">
            <div v-for="entry in entries" :class="{'entry': true, 'readonly': entry.readonly }"
                 :style="{ left: addOne(entry.shape.x1) + 'px', top: addOne(entry.shape.y1) + 'px', width: subtractThree(entry.shape.width) + 'px', height: subtractTwo(entry.shape.height) + 'px', backgroundColor: assignBackgroundColor(entry.title, entry.readonly) }"
            >
                <span class="entry-title" v-if="entry.readonly === false" :title="entry.title">{{ entry.title }}</span>
                <span class="entry-title" v-else :title="entry.title">{{ entry.courseCode }}:{{ entry.title }}</span>
                <div :class="{'action-btns': true, 'vert': determineVert(entry.shape.width)}">
                    <span :class="{'btn btn-small hover-btn': true, 'vert-btn': determineVert(entry.shape.width), 'hori-btn': determineHori(entry.shape.width)}" @mouseover="preventer = true" @mouseout="preventer = false" :style="{ borderColor: assignButtonBorderColor(entry.title, entry.readonly), backgroundColor: assignButtonBackgroundColor(entry.title, entry.readonly), color: assignButtonBorderColor(entry.title, entry.readonly) }"><i class="fa fa-plus"></i></span>
                    <span v-if="entry.readonly === false" :class="{'btn btn-small hover-btn': true, 'vert-btn': determineVert(entry.shape.width), 'hori-btn': determineHori(entry.shape.width)}" :style="{ borderColor: assignButtonBorderColor(entry.title, entry.readonly), backgroundColor: assignButtonBackgroundColor(entry.title, entry.readonly), color: assignButtonBorderColor(entry.title, entry.readonly) }"><i class="fa fa-pencil"></i></span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    const IncrementType = use('./../../Model/RotationScheduler/Increment/IncrementType');
    const Point = use('./../../Model/RotationScheduler/Layout/Point');

    module.exports = {
        name: 'rail',
        props: {
            segments: {
                type: Array,
                default: () => []
            },
            entries: {
                type: Array,
                default: () => []
            },
            blocks: {
                type: Array,
                default: () => []
            },
            learner: {
                type: Object,
                default: () => {}
            },
            incrementWidth: {
                type: Number,
                default: 10
            },
            incrementType: {
                type: String,
                default: IncrementType.Day
            },
            scale: {
                type: Number,
                default: 1
            },
            colorAssignments: {
                type: Array,
                required: true
            }
        },

        data() {
            return {
                preventer: false
            };
        },

        computed: {
            railWidth() {
                return this.segments.reduce((total, segment) => total + segment.increments * this.incrementWidth, 0);
            }
        },

        methods: {
            subtractTwo(number) {
                var newNumber = 0;

                newNumber = number - 2;
                return newNumber;
            },
            subtractThree(number) {
                var newNumber = 0;

                newNumber = number - 3;
                return newNumber;
            },
            addOne(number) {
                var newNumber = 0;

                newNumber = number + 1;
                return newNumber;
            },
            assignBackgroundColor(title, readonly) {
                var hue = 0;
                for(let color of this.colorAssignments) {
                    if (title === color.title) {
                        hue = color.color;
                    }
                }

                if (readonly) {
                    return '#E1E7EC'
                }
                else {
                    return 'hsl(' + hue + ', 82%, 87%)';
                }
            },

            assignButtonBorderColor(title, readonly) {
                var hue = 0;
                for(let color of this.colorAssignments) {
                    if (title === color.title) {
                        hue = color.color;
                    }
                }

                if (readonly) {
                    return '#888f9f'
                }
                else {
                    return 'hsl(' + hue + ', 82%, 47%)';
                }
            },

            assignButtonBackgroundColor(title, readonly) {
                var hue = 0;
                for(let color of this.colorAssignments) {
                    if (title === color.title) {
                        hue = color.color;
                    }
                }

                if (readonly) {
                    return '#E1E7EC'
                }
                else {
                    return 'hsl(' + hue + ', 82%, 93%)';
                }
            },

            determineClickedSegment(clickedX) {
                for(let segment of this.segments) {
                    if(clickedX >= segment.x1 && clickedX < segment.x2) {
                        return segment;
                    }
                }

                return null;
            },
            determineClickedBlock(clickedX) {
                for(let block of this.blocks) {
                    if(clickedX >= block.shape.x1 && clickedX < block.shape.x2) {
                        return block;
                    }
                }

                return null;
            },
            determineClickedEntry(clickedX, clickedY) {
                for(let entry of this.entries) {
                    if(clickedX >= entry.shape.x1 && clickedX < entry.shape.x2 &&
                        clickedY >= entry.shape.y1 && clickedY < entry.shape.y2
                    ) {
                        return entry;
                    }
                }

                return null;
            },
            determineClickedDate(clickedX, block) {
                let date = null;
                let workingDate = new Date(block.startDate);
                let count = 0;
                let dayFactor = 1;

                switch(this.incrementType) {
                    case IncrementType.Day:
                        count = Math.round(block.dateInterval().days);
                        dayFactor = 1;
                        break;
                    case IncrementType.Week:
                        count = Math.round(block.dateInterval().weeks);
                        dayFactor = 7;
                        break;
                }

                for(let i = 0; i <= count; i++) {
                    let x1 = block.shape.x1 + i * dayFactor * this.incrementWidth;
                    let x2 = x1 + this.incrementWidth;

                    if(clickedX >= x1 && clickedX < x2) {
                        workingDate.setDate(workingDate.getDate() + i * dayFactor);
                        date = workingDate;
                    }
                }

                return date;
            },
            determineVert(width) {
                if (width < 36) {
                    return true;
                }
                else {
                    return false
                }
            },
            determineHori(width) {
                if (width > 35) {
                    return true;
                }
                else {
                    return false
                }
            },
            calculateClickedPoint(event) {
                let x = event.offsetX + event.target.offsetLeft;
                let y = event.offsetY + event.target.offsetTop;
                let parents = jQuery(event.target).parentsUntil('.rail');

                for(let i = 0; i < parents.length; i++) {
                    x += parents[i].offsetLeft;
                    y += parents[i].offsetTop;
                }

                return new Point(x, y);
            },
            handleClick(event) {
                let clickedPoint = this.calculateClickedPoint(event);

                let clickedX = clickedPoint.x;
                let clickedY = clickedPoint.y;

                let segment = this.determineClickedSegment(clickedX);
                let block = this.determineClickedBlock(clickedX);
                let entry = this.preventer ? null : this.determineClickedEntry(clickedX, clickedY);
                let date = this.determineClickedDate(clickedX, block);
                let learner = this.learner;

                if(segment) {
                    this.$emit('segment-click', segment);
                }

                if(block) {
                    this.$emit('block-click', block);
                }

                if(entry) {
                    this.$emit('entry-click', entry);
                }

                this.$emit('smart-click', {
                    date, segment, block, entry, learner
                });
            }
        }
    };
</script>

<style>
    .rail {
        position:relative;
        box-sizing:border-box;
        border-top:1px solid #D5DEE0;
        border-bottom:1px solid #D5DEE0;
        height:70px;
        cursor:pointer;
        white-space: nowrap;
    }

    .rail .layer {
        position:absolute;
        width:100%;
        height:100%;
        top:0;
        left:0;
    }

    .rail .layer.background-layer {
        background-color: white;
        cursor:cell;
        z-index:10;
    }

    .rail .layer.data-layer {
        z-index:30;
        pointer-events:none;
        border-bottom: 1px solid #d5dee0;
    }

    .rail .entry {
        position:absolute;
        box-sizing:border-box;
        font-size:12px;
        font-family: "Lucida Grande", Geneva, Verdana, Arial, Helvetica, sans-serif;
        padding:0;
        top: 0;
        overflow:hidden;
        cursor: pointer;
        pointer-events: all;
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
        position: absolute;
        border-radius: 5px;
    }

    .rail .entry.readonly {
        border-width: 0;
        color: #8b959d;
    }

    .rail .entry.readonly:hover {
        cursor: not-allowed;
        opacity: 1;
    }

    .rail .entry:hover {
        opacity: 0.6;
    }

    .entry span {
        padding: 5px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    .entry .action-btns {
        opacity: 0;
        position: absolute;
        right: 0;
        top: 0;
        padding: 5px;
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
        justify-content: flex-end;
    }

    .entry .action-btns.vert {
        flex-direction: column;
    }

    .entry:hover .action-btns {
        opacity: 1;
        -webkit-transition: opacity 0.25s;
        -moz-transition: opacity 0.25s;
        -o-transition: opacity 0.25s;
        transition: opacity 0.25s;
    }

    .entry .hover-btn {
        padding: 1px 3px;
        width: 15px;
    }

    .entry .hover-btn i {
        pointer-events: none;
    }

    .entry .vert-btn:first-child {
        border-radius: 5px 5px 0 0;
    }

    .entry .vert-btn:last-child {
        border-radius: 0 0 5px 5px;
        border-top: 0;
    }

    .entry .vert-btn:only-child {
        border-radius: 5px;
        border-top: 1px solid !important;
    }

    .entry .hori-btn:first-child {
        border-radius: 5px 0 0 5px;
    }

    .entry .hori-btn:last-child {
        border-radius: 0 5px 5px 0;
        border-left: 0;
    }

    .entry .hori-btn:only-child {
        border-radius: 5px;
        border-left: 1px solid !important;
    }

    .entry .hover-btn:hover {
        background-color: white !important;
    }

    .entry-title {
        pointer-events: none;
    }
</style>
