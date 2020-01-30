/**
 * Schedule.js
 * @author Scott Gibson
 */

module.exports = class Schedule
{
    constructor() {
        this.name = '';
        this.code = '';
        this.type = null;
        this.description = '';
        this.slots = [];
        this.rotations = [];
        this.parent = null;
        this.blockType = null;
        this.startDate = 0;
        this.endDate = 0;
        this.createdDate = 0;
    }
};