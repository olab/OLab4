/**
 * Block.js
 * @author Scott Gibson
 */

const DateInterval = use('./../DateInterval');
const Rectangle = use('./../Layout/Rectangle');

module.exports = class Block
{
    constructor(id, startDate, endDate, increments) {
        this.id = id;
        this.startDate = startDate;
        this.endDate = endDate;
        this.increments = increments;

        this.shape = Rectangle.create();
    }

    dateInterval() {
        return new DateInterval(this.startDate, this.endDate);
    }
};