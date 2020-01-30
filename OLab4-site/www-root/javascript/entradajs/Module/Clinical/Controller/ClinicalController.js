/**
 * ClinicalController.js
 * @author Thaisa Almeida
 */

const ControllerAbstract = use('EntradaJS/Controller/ControllerAbstract');

const preloadedComponents = {
    Clinical: use('./../Component/Clinical.vue')
};

module.exports = class ClinicalController extends ControllerAbstract
{
    indexAction() {
        return preloadedComponents.Clinical;
    };

    myLearnersAction() {
        return this.respond('MyLearners.vue');
    };

    rotationScheduleAction() {
        return this.respond('RotationSchedule.vue');
    };

    draftRotationScheduleAction() {
        return this.respond('DraftRotationSchedule.vue');
    };

    editRotationScheduleAction() {
        return this.respond('EditRotationSchedule.vue');
    };

    editRotationScheduleLLearnersAction() {
        return this.respond('RotationScheduleLearners.vue');
    };

    addRotationScheduleAction() {
        return this.respond('AddRotationSchedule.vue');
    };

    rotationScheduleBlocksAction() {
        return this.respond('RotationScheduleBlocks.vue');
    };

    leaveTrackingUserAction() {
        return this.respond('LeaveTrackingUser.vue');
    };

    logbookAction() {
        return this.respond('LogBook.vue');
    };
};