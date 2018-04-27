/**
 * Increment.js
 * @author Scott Gibson
 */

const DateInterval = use('./../DateInterval');
const Rectangle = use('./../Layout/Rectangle');

module.exports = class Increment
{
    constructor(id, type, startDate, endDate, x1, x2) {
        this.id = id;
        this.type = type;
        this.interval = new DateInterval(startDate, endDate);
        this.shape = Rectangle.create(x1, 0, x2, 0);
    }
};