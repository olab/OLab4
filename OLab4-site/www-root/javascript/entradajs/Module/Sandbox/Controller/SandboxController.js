/**
 * SandboxController.js
 * @author Scott Gibson
 */

const Sandbox = use('./../Component/Sandbox.vue');

module.exports = class SandboxController
{
    indexAction() {
        // Return a stand-alone component
        console.info('Running legacy route!');
        return Sandbox;
    }

    simpleAction() {
        console.info('Running simple route!');
        return Sandbox;
    }

    advancedAction() {
        console.info('Running advanced route!');
        return Sandbox;
    }

    hybridAction() {
        console.info('Running hybrid route!');
        return Sandbox;
    }
};