/**
 * LocationController.js
 * @author Jonatan Caraballo
 */

const Locations = use('./../Component/Locations.vue');
const BuildingForm = use('./../Component/Buildings.vue');
const SiteForm = use('./../Component/Sites.vue');

module.exports = class LocationsController
{
    indexAction() {
        // Return a stand-alone component
        return Locations;
    };

    addBuildingAction() {
        // Return a stand-alone component
        return BuildingForm;
    };

    editBuildingAction() {
        // Return a stand-alone component
        return BuildingForm;
    };

    addSiteAction() {
        // Return a stand-alone component
        return SiteForm;
    };

    editSiteAction() {
        // Return a stand-alone component
        return SiteForm;
    };
};