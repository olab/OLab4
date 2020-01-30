/**
 * manifest.js
 * @author Scott Gibson
 */

const Route = use('EntradaJS/Routing/Route');
const RouteCollection = use('EntradaJS/Routing/RouteCollection');

module.exports = {
    name: 'Sandbox',
    version: '1.0',
    routes: new RouteCollection('/sandbox', [
        // Legacy Route
        new Route('sandbox.index', '/', 'Sandbox.Sandbox.index'),

        // Simple Route
        new Route('sandbox.simple', '/simple', {
            _controller: 'Sandbox.Sandbox.simple'
        }),

        // Advanced Route
        new Route('sandbox.advanced', '/advanced/{param1}/{param2}/{param3}', {
            _controller: 'Sandbox.Sandbox.advanced'
        }, {
            param1: /\d+/,      // Regex matching an integer
            param2: /[A-Z]+/,   // Regex matching an uppercase string
            param3: /[a-z]+/    // Regex matching a lowercase string
        }),

        // Hybrid Route
        new Route('sandbox.hybrid', '/hybrid/{param1}/foo/{param2}', {
            _controller: 'Sandbox.Sandbox.hybrid'
        }, {
            param1: /\d+/,      // Regex matching an integer
            param2: /[A-Z]+/    // Regex matching an uppercase string
        })
    ])
};