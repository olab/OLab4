/**
 * environment.js
 * @author Scott Gibson
 */

module.exports = {
    name: 'Entrada ME',
    version: '1.11',
    apiPath: '/api/v2',
    layoutNamespace: 'Layout',
    moduleNamespace: 'Module',
    registeredModules: [
        {
            name: 'Sandbox',
            pathPrefix: '/sandbox'
        },
        {
            name: 'Clinical',
            pathPrefix: '/clinical'
        },
        {
            name: 'Locations',
            pathPrefix: '/locations'
        },
        {
            name: 'ComponentDocs',
            pathPrefix: '/components'
        }
    ]
};