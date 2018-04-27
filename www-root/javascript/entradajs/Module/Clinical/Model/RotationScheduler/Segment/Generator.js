/**
 * Segment/Generator.js
 * @author Scott Gibson
 */

const DateInterval = use('./../DateInterval');
const Increment = use('./../Increment/Increment');
const IncrementType = use('./../Increment/IncrementType');
const Segment = use('./Segment');

module.exports = class Generator
{
    daysInMonth(year, monthIndex) {
        return new Date(year, monthIndex + 1, 0).getDate();
    }

    daysInQuarter(year, quarterIndex) {
        let daysInQuarter = 0;
        let startMonth = quarterIndex * 3;
        let endMonth = startMonth + 2;

        for(let i = startMonth; i <= endMonth; i++) {
            daysInQuarter += this.daysInMonth(year, i);
        }

        return daysInQuarter;
    }

    generateIncrements(type, startDate, endDate, incrementWidth = 10) {
        let increments = [];
        let interval = new DateInterval(startDate, endDate);

        let count = 0;
        let dayFactor = 1;

        switch(type) {
            case IncrementType.Day:
                count = Math.round(interval.days);
                dayFactor = 1;
                break;
            case IncrementType.Week:
                count = Math.round(interval.weeks);
                dayFactor = 7;
                break;
        }

        for(let i = 0; i < count; i++) {
            let incrementStartDate = new Date(startDate);
            let incrementEndDate = new Date(startDate);
            let incrementX1 = 0;
            let incrementX2 = 0;

            incrementStartDate.setDate(incrementStartDate.getDate() + i * dayFactor);
            incrementEndDate.setDate(incrementEndDate.getDate() + i * dayFactor + i);

            increments.push(new Increment(i, type, incrementStartDate, incrementEndDate, incrementX1, incrementX2));
        }

        return increments;
    }

    generateBlockSegments(blocks, incrementWidth = 10) {
        let segments = [];
        let highestX = 0;

        for(let i = 0; i < blocks.length; i++) {
            let daysInBlock = Math.ceil(blocks[i].dateInterval().days);
            let width = daysInBlock * incrementWidth;
            let segment = new Segment('Block ' + i, i, highestX, highestX + width, daysInBlock);

            segments.push(segment);

            highestX += width;
        }

        return segments;
    }

    generateMonthSegmentsInRange(startDate, endDate, incrementWidth = 10) {
        if(startDate instanceof Date === false) {
            throw new TypeError('Generator#generateMonthSegmentsInRange() expects its startDate parameter to be a Date object.');
        }

        let interval = new DateInterval(startDate, endDate);
        let monthCount = interval.months;
        let workingDate = new Date(startDate);
        let segments = [];
        let highestX = 0;

        for(let i = 0; i < monthCount; i++) {
            let dateString = workingDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            let daysInMonth = this.daysInMonth(workingDate.getFullYear(), workingDate.getMonth());
            let width = incrementWidth * daysInMonth;

            let segment = new Segment(dateString, i, highestX, highestX + width, daysInMonth);

            segments.push(segment);

            highestX += width;
            workingDate.setMonth(workingDate.getMonth() + 1);
        }

        return segments;
    }

    generateQuarterSegmentsInRange(startDate, endDate, incrementWidth = 5) {
        if(startDate instanceof Date === false) {
            throw new TypeError('Generator#generateQuarterSegmentsInRange() expects its startDate parameter to be a Date object.');
        }

        if(endDate instanceof Date === false) {
            throw new TypeError('Generator#generateQuarterSegmentsInRange() expects its endDate parameter to be a Date object.');
        }

        let interval = new DateInterval(startDate, endDate);
        let quarterCount = Math.ceil(interval.months / 3);
        let workingDate = new Date(startDate);
        let segments = [];
        let highestX = 0;

        for(let i = 0; i < quarterCount; i++) {
            let daysInQuarter = this.daysInQuarter(workingDate.getFullYear(), i);

            let quarterNumber = Math.ceil((workingDate.getMonth() + 1) / 3);
            let dateString = 'Q' + quarterNumber + ' ' + workingDate.getFullYear();
            let width = incrementWidth * daysInQuarter;

            let segment = new Segment(dateString, i, highestX, highestX + width, daysInQuarter);

            segments.push(segment);

            highestX += width;
            workingDate.setMonth(workingDate.getMonth() + 3);
        }

        return segments;
    }
};