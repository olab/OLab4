/**
 * manifest.js
 * @author Scott Gibson
 */

const Route = use('EntradaJS/Routing/Route');
const RouteCollection = use('EntradaJS/Routing/RouteCollection');

module.exports = {
    name: 'ComponentDocs',
    version: '1.0',
    routes: new RouteCollection('/components', [
        new Route('components.index', '/', 'ComponentDocs.Default.index'),
    ], [
        new RouteCollection('/components', [
            new Route('components.components.index', '/', 'ComponentDocs.Components.index'),
            new Route('components.components.advancedTable', '/advanced-table', 'ComponentDocs.Components.advancedTable'),
            new Route('components.components.alert', '/alert', 'ComponentDocs.Components.alert'),
            new Route('components.components.badge', '/badge', 'ComponentDocs.Components.badge'),
            new Route('components.components.button', '/button', 'ComponentDocs.Components.button'),
            new Route('components.components.card', '/card', 'ComponentDocs.Components.card'),
            new Route('components.components.checkbox', '/checkbox', 'ComponentDocs.Components.checkbox'),
            new Route('components.components.layout', '/layout', 'ComponentDocs.Components.layout'),
            new Route('components.components.listItem', '/list-item', 'ComponentDocs.Components.listItem'),
            new Route('components.components.media', '/media', 'ComponentDocs.Components.media'),
            new Route('components.components.modal', '/modal', 'ComponentDocs.Components.modal'),
            new Route('components.components.multipleLineInput', '/multiple-line-input', 'ComponentDocs.Components.multipleLineInput'),
            new Route('components.components.popover', '/popover', 'ComponentDocs.Components.popover'),
            new Route('components.components.radio', '/radio', 'ComponentDocs.Components.radio'),
            new Route('components.components.selectInput', '/select-input', 'ComponentDocs.Components.selectInput'),
            new Route('components.components.simpleTable', '/simple-table', 'ComponentDocs.Components.simpleTable'),
            new Route('components.components.singleLineInput', '/single-line-input', 'ComponentDocs.Components.singleLineInput'),
            new Route('components.components.submit', '/submit', 'ComponentDocs.Components.submit'),
            new Route('components.components.switchToggle', '/switch-toggle', 'ComponentDocs.Components.switchToggle'),
            new Route('components.components.tab', '/tab', 'ComponentDocs.Components.tab'),
            new Route('components.components.textEditor', '/text-editor', 'ComponentDocs.Components.textEditor'),
            new Route('components.components.tooltip', '/tooltip', 'ComponentDocs.Components.tooltip'),
            new Route('components.components.well', '/well', 'ComponentDocs.Components.well')
        ])
    ])
};
