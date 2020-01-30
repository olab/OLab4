/**
 * manifest.js
 * @author Thaisa Almeida
 */

const Route = use('EntradaJS/Routing/Route');
const RouteCollection = use('EntradaJS/Routing/RouteCollection');

module.exports = {
    name: 'Clinical',
    version: '1.0',
    routes: new RouteCollection('/clinical', [
        new Route('clinical.mylearners', '/mylearners', 'Clinical.Clinical.myLearners', {}, {
            page_name: "My Learners"
        }),
        new Route('clinical.rotationschedule', '/rotationschedule', 'Clinical.Clinical.rotationSchedule', {}, {
            page_name: "Rotation Schedule"
        }),
        new Route('clinical.rotationschedule.drafts', '/rotationschedule/drafts', 'Clinical.Clinical.draftRotationSchedule', {}, {
            page_name: "Drafts"
        }),
        new Route('clinical.rotationschedule.drafts.edit', '/rotationschedule/drafts/{draft_id}/{tab}', {
            _controller: 'Clinical.Clinical.editRotationSchedule',
            tab: 'rotations'
        }, {
            draft_id: /\d+/,      // Regex matching an integer
            tab: /rotations|learners/,    // Regex matching a lowercase string
        }, {
            page_name: "Edit Draft"
        }),
        new Route('clinical.rotationschedule.drafts.edit.add', '/rotationschedule/drafts/{draft_id}/import', {
            _controller: 'Clinical.Clinical.addRotationSchedule'
        }, {
            draft_id: /\d+/,      // Regex matching an integer
        },{
            page_name: "Import"
        }),
        new Route('clinical.rotationschedule.drafts.edit.blocks', '/rotationschedule/drafts/{draft_id}/blocks/{schedule_id}', {
            _controller: 'Clinical.Clinical.rotationScheduleBlocks'
        }, {
            draft_id: /\d+/,      // Regex matching an integer
            schedule_id: /\d+/,      // Regex matching an integer
        },{
            page_name: "Blocks"
        }),
        new Route('clinical.logbook', '/logbook', 'Clinical.Clinical.logbook', {}, {
            page_name: "Logbook"
        }),
        new Route('clinical.leavetracking', '/leavetracking/{user_id}', {
            _controller: 'Clinical.Clinical.leaveTrackingUser'
        }, {
            user_id: /\d+/,      // Regex matching an integer
        }, {
            page_name: "Leave Tracking"
        }),
    ])
};