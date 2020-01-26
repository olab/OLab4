/**
 * Group.js
 * @author Eric Howarth
 */

module.exports = class Group
{
    constructor(id, courseId, cperiodId, groupName, audience) {
        this.id = id;
        this.courseId = courseId;
        this.cperiodId = cperiodId;
        this.groupName = groupName;
        this.audience = audience;
    }
};