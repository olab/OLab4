/**
 * Navigator.js
 * @author Scott Gibson
 */

const DateInterval = use('./DateInterval');
const Point = use('./Layout/Point');
const Views = use('./Views');

module.exports = class Navigator
{
    constructor(element, view, blocks, segments, entries, incrementWidth) {
        this.element = element;
        this.view = view;
        this.blocks = blocks;
        this.segments = segments;
        this.entries = entries;
        this.incrementWidth = incrementWidth;
    }

    scrollToPoint(point) {
        let scrollDistance = Math.abs(this.element.scrollLeft - point.x);
        let animationDuration = scrollDistance / 3.5 + 150;
        let maxAnimationDuration = 1500;

        if(animationDuration > maxAnimationDuration) {
            animationDuration = maxAnimationDuration;
        }

        jQuery(this.element).stop().clearQueue('fx').animate({ 'scrollLeft': point.x }, animationDuration);
    }

    scrollToStart() {
        this.scrollToPoint(new Point(0, 0));
    }

    scrollToEnd() {
        this.scrollToPoint(new Point(this.element.scrollWidth, 0));
    }

    scrollToBlock(block) {
        this.scrollToPoint(block.shape.point1);
    }

    scrollToBlockByIndex(index) {
        this.scrollToPoint(this.blocks[index]);
    }

    scrollToBlockByDate(date) {
        for(let block of this.blocks) {
            if(date >= block.startDate && date < block.endDate) {
                this.scrollToBlock(block);
            }
        }
    }

    scrollToNextBlock() {
        for(let block of this.blocks) {
            if(block.shape.x1 > this.element.scrollLeft) {
                this.scrollToBlock(block);
                break;
            }
        }
    }

    scrollToPreviousBlock() {
        for(let i = 0; i < this.blocks.length; i++) {
            if(this.blocks[i].shape.x1 >= this.element.scrollLeft && this.blocks[i-1]) {
                this.scrollToBlock(this.blocks[i-1]);
                break;
            }
        }
    }

    scrollToEntry(entry) {
        this.scrollToPoint(entry.shape.point1);
    }

    scrollToEntryByIndex(index) {
        this.scrollToEntry(this.entries[index]);
    }

    scrollToSegment(segment) {
        this.scrollToPoint(new Point(segment.x1, 0));
    }

    scrollToSegmentByIndex(index) {
        this.scrollToSegment(this.segments[index]);
    }

    scrollToDate(date) {
        let startDate;

        if(this.view === Views.Quarter) {
            let quarterIndex = Math.floor(this.blocks[0].startDate.getMonth() / 3);
            startDate = new Date(this.blocks[0].startDate.getFullYear(), quarterIndex * 3, 1);
        }
        else {
            startDate = this.blocks[0].startDate;
        }

        let interval = new DateInterval(startDate, date);
        let x = Math.round(interval.days) * this.incrementWidth;

        this.scrollToPoint(new Point(x, 0));
    }
};