/**
 * Entry.js
 * @author Scott Gibson
 */

const DateInterval = use('./../DateInterval');
const Rectangle = use('./../Layout/Rectangle');

module.exports = class Entry
{
    constructor(id, name, startDate, endDate) {
        this.id = id;
        this.name = name;
        this.startDate = startDate;
        this.endDate = endDate;
        this.interval = new DateInterval(startDate, endDate);

        this.shape = Rectangle.create();
    }

    dateInterval() {
        return this.interval;
    }
};