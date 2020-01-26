/**
 * Store.js
 * @author Scott Gibson
 */

module.exports = new Vuex.Store({
    state: {
        mainNavCollapsed: false,
        sidebarCollapsed: true,
        dataTableDocColumns: [
            {
                title:"ID",
            },

            {
                title:"Name",
                visible: true,
                editable: true,
            },

            {
                title:"Age",
                visible: true,
                editable: true,
            },

            {
                title:"Country",
                visible: true,
                editable: true,
            }
        ],

        dataTableDocValues: [
            {
                "ID": 1,
                "Name": "John",
                "Country": "UK",
                "Age": 25,
            },

            {
                "ID": 2,
                "Name": "Mary",
                "Country": "France",
                "Age": 30,
            },

            {
                "ID": 3,
                "Name": "Ana",
                "Country": "Portugal",
                "Age": 20,
            }
        ],

        componentsData: [
            /*{
                title: 'Advanced Tables',
                link: '#/components/components/advanced-table',
                icon: 'fa fa-table',
                description: 'Uses Bootstrap Table to provide advanced table functionality.'
            },*/

            {
                title: 'Alerts',
                link: '#/components/components/alert',
                icon: 'fa fa-exclamation-triangle',
                description: 'Used for explaining functionality or rendering success/error messages.'
            },

            {
                title: 'Badges',
                link: '#/components/components/badge',
                icon: 'fa fa-certificate',
                description: 'Container for rendering tags or counters.'
            },

            {
                title: 'Buttons',
                link: '#/components/components/button',
                icon: 'fa fa-caret-square-o-right',
                description: 'Styled button or anchor tags for toggling data or sending users to an external page.'
            },

            {
                title: 'Cards',
                link: '#/components/components/card',
                icon: 'fa fa-window-maximize',
                description: 'Structured content containers that includes a space for a header and footer.'
            },

            {
                title: 'Checkboxes',
                link: '#/components/components/checkbox',
                icon: 'fa fa-check-square',
                description: 'Styled checkboxes for toggling data true or false.'
            },

            {
                title: 'Layouts',
                link: '#/components/components/layout',
                icon: 'fa fa-th-large',
                description: 'Used for creating columns on a page.'
            },

            {
                title: 'List Items',
                link: '#/components/components/list-item',
                icon: 'fa fa-list',
                description: 'Styled lists used for links or displaying content.'
            },

            {
                title: 'Media',
                link: '#/components/components/media',
                icon: 'fa fa-film',
                description: 'Used to render images, videos, or audio.'
            },

            {
                title: 'Modals',
                link: '#/components/components/modal',
                icon: 'fa fa-window-maximize',
                description: 'Overlayed cards that is launched when an element is clicked.'
            },

            {
                title: 'Multiple Line Input',
                link: '#/components/components/multiple-line-input',
                icon: 'fa fa-window-maximize',
                description: 'Text input that allows the user to write on multiple lines.'
            },

            {
                title: 'Popovers',
                link: '#/components/components/popover',
                icon: 'fa fa-window-maximize',
                description: 'Description'
            },

            {
                title: 'Radios',
                link: '#/components/components/radio',
                icon: 'fa fa-circle',
                description: 'Styled radios for setting values to data.'
            },

            {
                title: 'Select Inputs',
                link: '#/components/components/select-input',
                icon: 'fa fa-angle-down',
                description: 'Dropdown style selectors that can be setup to allow a single selection or multiple selections.'
            },

            {
                title: 'Simple Tables',
                link: '#/components/components/simple-table',
                icon: 'fa fa-table',
                description: 'Used to render information in a table that doesn\'t require advanced table functionality.'
            },

            {
                title: 'Single Line Input',
                link: '#/components/components/single-line-input',
                icon: 'fa fa-i-cursor',
                description: 'Used for rendering configurable inputs.'
            },

            {
                title: 'Submit Inputs',
                link: '#/components/components/submit',
                icon: 'fa fa-paper-plane',
                description: 'Used to render styled form submission inputs.'
            },

            {
                title: 'Switch Toggle',
                link: '#/components/components/switch-toggle',
                icon: 'fa fa-toggle-on',
                description: 'Styled checkboxes for toggling data true or false.'
            },

            {
                title: 'Tabs',
                link: '#/components/components/tab',
                icon: 'fa fa-folder',
                description: 'Used for breaking up pages into sections that can be switched between using a horizontal menu.'
            },

            {
                title: 'Text Editor',
                link: '#/components/components/text-editor',
                icon: 'fa fa-pencil',
                description: 'Uses CKEditor to render WYSIWYG textarea inputs.'
            },

            {
                title: 'Tooltips',
                link: '#/components/components/tooltip',
                icon: 'fa fa-question-circle',
                description: 'Used to provide descriptive text on hover to components.'
            },

            {
                title: 'Well',
                link: '#/components/components/well',
                icon: 'fa fa-square-o',
                description: 'Used to contain content within a bordered box.'
            }
        ],

        compositesData: [
            {
                title: 'Permission Card',
                link: '/composites/permission-card',
                icon: 'fa fa-fa fa-window-maximize',
                description: 'Description'
            },

            {
                title: 'Role Card',
                link: '/composites/role-card',
                icon: 'fa fa fa-window-maximize',
                description: 'Description'
            }
        ],

        roles: [
            {
                id: 1,
                title: 'Role 1',
                description: 'Role 1 description',
                users: 7,
                permissions: [ 2, 3 ]
            },

            {
                id: 2,
                title: 'Role 2',
                description: 'Role 2 description',
                users: 0,
                permissions: [ 3, 2 ]
            },

            {
                id: 3,
                title: 'Role 3',
                description: 'Role 3 description',
                users: 11,
                permissions: [ 1 ]
            }
        ],

        permissions: [
            {
                id: 1,
                title: 'Grade Assignments',
                description: 'Allows course contact to grade course assignments.',
                users: 3,
                courseSpecific: true,
                roles: [ 'Lead TA' ]
            },

            {
                id: 2,
                title: 'Edit Core Course Details',
                description: 'Allows course admin to edit core course details.',
                users: 10,
                courseSpecific: false,
                roles: [
                    'Role 1', 'Role 2'
                ]
            },

            {
                id: 3,
                title: 'Setup Gradebook',
                description: 'Allows course admin to setup course gradebook.',
                users: 7,
                courseSpecific: false,
                roles: [
                    'Role 1', "Role 2"
                ]
            }
        ]
    },

    mutations: {
        toggleMainNavCollapsed (state) {
            state.mainNavCollapsed = !state.mainNavCollapsed
        },

        mainNavIsCollapsed (state) {
            state.mainNavCollapsed = true
        },

        toggleSidebarCollapsed (state) {
            state.sidebarCollapsed = !state.sidebarCollapsed
        }
    }
});
