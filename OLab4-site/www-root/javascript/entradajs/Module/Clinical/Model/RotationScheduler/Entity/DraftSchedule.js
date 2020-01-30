/**
 * Schedule.js
 * @author Scott Gibson
 */

module.exports = class DraftSchedule
{
    constructor(id, title, status, course_id, cperiod_id) {
        this.id = id;
        this.title = title;
        this.status = status;
        this.course_id = course_id;
        this.cperiod_id = cperiod_id;
    }
};