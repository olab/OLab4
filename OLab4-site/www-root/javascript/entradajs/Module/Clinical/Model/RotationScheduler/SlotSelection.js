/**
 * Audience.js
 * @author Eric Howarth
 */

module.exports = class SlotSelection
{
    constructor() {
        this.entry = null;
        this.block = null;
        this.learner = null;
        this.rotation = [];
        this.rotationBlock = [];
        this.slot = [];
        this.startDate = null;
        this.endDate = null;
        this.customStartDate = null;
        this.customEndDate = null;
        this.inputStartDate = null;
        this.inputEndDate = null;
        this.rotation_id = 0;
        this.rotation_block_id = 0;
        this.slot_id = 0;
        this.audience_id = 0;
        this.audience_type = "proxy_id";
        this.audience_value = 0;
        this.offService = false;
        this.customDates = false;
    }
};