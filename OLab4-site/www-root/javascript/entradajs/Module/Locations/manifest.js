/**
 * manifest.js
 * @author Jonatan Caraballo
 */

const Route = use('EntradaJS/Routing/Route');
const RouteCollection = use('EntradaJS/Routing/RouteCollection');

module.exports = {
    name: 'Locations',
    version: '1.0',
    routes: new RouteCollection('/locations', [
        new Route('locations.index', '/', 'Locations.Locations.index'),
        new Route('locations.add_building', '/building/site/{site_id}', {
            _controller: 'Locations.Locations.addBuilding'
        }, {
            site_id: /\d+/,      // Regex matching an integer
        }),
        new Route('locations.edit_building', '/building/site/{site_id}/{building_id}', {
            _controller: 'Locations.Locations.editBuilding'
        }, {
            site_id: /\d+/,
            building_id: /\d+/,      // Regex matching an integer
        }),
        new Route('locations.add_site', '/site/', 'Locations.Locations.addSite'),
        new Route('locations.edit_site', '/site/{site_id}/', {
            _controller: 'Locations.Locations.editSite'
        }, {
            site_id: /\d+/,      // Regex matching an integer
        }),
    ])
};