/**
 * Segment.js
 * @author Scott Gibson
 */

module.exports = class Segment
{
    constructor(name, index, x1, x2, increments = 0) {
        this.name = name;
        this.index = index;
        this.x1 = x1;
        this.x2 = x2;
        this.width = x2 - x1;
        this.increments = increments;
    }
};