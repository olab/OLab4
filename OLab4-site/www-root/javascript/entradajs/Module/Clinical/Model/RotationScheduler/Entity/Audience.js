/**
 * Audience.js
 * @author Scott Gibson
 */

const DateInterval = use('./../DateInterval');
const Rectangle = use('./../Layout/Rectangle');

module.exports = class Audience
{
    constructor(id, block, schedule, slot, type, value, startDate, endDate, title, courseCode, readonly = false) {
        this.id = id;
        this.block = block;
        this.schedule = schedule;
        this.slot = slot;
        this.type = type;
        this.value = value;
        this.startDate = startDate;
        this.endDate = endDate;
        this.title = title;
        this.courseCode = courseCode;
        this.readonly = readonly;

        this.interval = new DateInterval(startDate, endDate);
        this.shape = Rectangle.create();
    }

    dateInterval() {
        return this.interval;
    }

    toString() {
        return '[Audience ' + this.title + ']';
    }
};