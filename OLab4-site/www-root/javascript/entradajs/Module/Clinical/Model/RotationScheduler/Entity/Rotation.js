/**
 * Rotation.js
 * @author Rotation
 */

module.exports = class Rotation
{
    constructor(schedule_id, title, code, description, schedule_type, schedule_parent_id, organisation_id, course_id, region_id, facility_id, cperiod_id, start_date, end_date, block_type_id, draft_id, schedule_order, copied_from) {
        this.schedule_id = schedule_id;
        this.title = title;
        this.code = code;
        this.description = description;
        this.schedule_type = schedule_type;
        this.schedule_parent_id = schedule_parent_id;
        this.organisation_id = organisation_id;
        this.course_id = course_id;
        this.region_id = region_id;
        this.facility_id = facility_id;
        this.cperiod_id = cperiod_id;
        this.start_date = start_date;
        this.end_date = end_date;
        this.block_type_id = block_type_id;
        this.draft_id = draft_id;
        this.schedule_order = schedule_order;
        this.copied_from = copied_from;
        this.sites = [];
        this.block_type = [];
        this.course = [];
        this.blocks = [];
        this.slots = [];
    }
};