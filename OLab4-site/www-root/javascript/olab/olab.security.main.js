/**
 * Main olab player class for info windows
 * @param {} authToken = current auth token
 * @param {} targetId = main content view div name for data binding
 * @param {} websiteRootUrl = root url for web site
 * @param {} pageUrl = current page url (document.location)
 * @returns {} Service definition
 */

"use strict";
var view = null;

var OlabSecurity = function(params) {

    var vm = this;

    vm.Utilities = new OLabUtilities(params.siteRoot, params.location, params.authToken);

    vm.targetUserTableId = vm.Utilities.normalizeIdAttribute(params.targetUserTableId);
    vm.targetRoleTableId = vm.Utilities.normalizeIdAttribute(params.targetRoleTableId);
    vm.targetObjectTableId = vm.Utilities.normalizeIdAttribute(params.targetObjectTableId);
    vm.targetInfoId = vm.Utilities.normalizeIdAttribute(params.targetInfoId);

    // get the map and nodeId from the url location hash
    var paramArray = vm.Utilities.getUrlParameters(params.location.hash);
    vm.urlParameters = {};

    vm.websiteUrl = params.siteRoot;
    vm.moduleUrl = params.siteRoot + '/olab';
    vm.restApiUrl = params.apiRoot + '/olab';
    vm.data = [
        { user: null, role: null, object: null },
        { selectedUsers: null, selectedRoles: null, selectedObjects: null }
    ];
    vm.userTable = null;
    vm.roleTable = null;
    vm.objectTable = null;
    vm.app = null;

    // these are the methods/properties we expose to the outside
    var service = {
        app:vm.app,
        data: vm.data,
        load: load,
        buildTableData: buildTableData,
        buildSelectionTableOptions: buildSelectionTableOptions,
        onClickLoadObjects: onClickLoadObjects,
        onClickLoadUserRoles: onClickLoadUserRoles
    };

    return service;

    function buildTableData(source) {

        // convert associative array into indexed array compatible with DataTable
        var tableData = [];
        for (var i = 0; i < source.length; i++) {
            var row = [];
            row.push(source[i]['id']);
            row.push(source[i]['name']);
            row.push(0);
            tableData.push(row);
        }

        return tableData;
    }

    /**
     * Build standard table options, including table data
     * @returns {} 
     */
    function buildSelectionTableOptions(data) {

        // convert associative array into indexed array compatible with DataTable
        var tableData = buildTableData(data);

        return {
            "paging": true,
            'pageLength': 10,
	        'responsive': true,
            'dom':'lfript',
            'order':[[1, 'asc']],
            'lengthMenu':[[5, 10, 25, -1], [5, 10, 25, "All"]],
            'data':tableData,
            'columns':[
                {'visible':false},
                {'title':'Name', 'width':'85%', "searchable": true},
                {
                    'title':'Multi-Select', 
                    'width':'215px',
                    'render':function(data, type, full, meta) 
                    {
                        var html = `<input type="checkbox" class="editor-active">`;                            
                        return html;
                    } 
                }
            ]
        };

    }

    /**
     * User pressed load object button
     * @returns {} 
     */
    function onClickLoadObjects(source) {

        try {

            vm.app.bLoadingObjects = true;
            var objectPromise = vm.Utilities.getJsonAsyc(vm.restApiUrl, null);

            jQuery.when(objectPromise)
                .done(function(data) {
                    onObjectLoadSuccess(data.data);
                    vm.app.bLoadingObjects = false;
                });

        } catch (e) {
            vm.app.bLoadingObjects = false;
        } 
    }

    function onObjectLoadSuccess(data) {

        vm.data['object'] = data;

        if (vm.objectTable != null) {
            vm.objectTable.destroy();
        }

        var tableOptions = buildSelectionTableOptions(vm.data['object']);
        vm.objectTable = jQuery('#olabObjectTable').DataTable(tableOptions);

        // configure row-selection handler
        jQuery(document).on("click",
            "#olabObjectTable tbody tr",
            function() {
                jQuery(this).toggleClass('selected');
                vm.app.setSelectedObjects(vm.objectTable.rows('.selected').data());
            });
    }

    /**
      * User pressed load object button
      * @returns {} 
      */
    function onClickLoadUserRoles(source) {

        try {

            vm.app.bLoadingUsersRoles = true;
            var objectPromise = null;

            if (jQuery("#userRoleSelector").val() == "users") {
                objectPromise = vm.Utilities.getJsonAsyc(vm.restApiUrl + '/admin/users', null);
            } else if (jQuery("#userRoleSelector").val() == "roles") {
                objectPromise = vm.Utilities.getJsonAsyc(vm.restApiUrl + '/admin/roles', null);
            }

            jQuery.when(objectPromise)
                .done(function(data) {
                    onUserLoadSuccess(data.data);
                    vm.app.bLoadingUsersRoles = false;
                });

        } catch (e) {
            vm.app.bLoadingUsersRoles = false;
        } 
    }

    function onUserLoadSuccess(data) {

        vm.data['user'] = data;


        if (vm.userTable != null) {
            vm.userTable.destroy();
        }

        var tableOptions = buildSelectionTableOptions(vm.data['user']);
        vm.userTable = jQuery('#olabUserRoleTable').DataTable(tableOptions);

        // configure row-selection handler
        jQuery(document).on("click",
            "#olabUserRoleTable tbody tr",
            function() {
                jQuery(this).toggleClass('selected');
                //alert(vm.userTable.rows('.selected').data().length + ' row(s) selected');
                vm.app.setUserRoleObjects(vm.userTable.rows('.selected').data());
            });

    }

    /**
     * Handle Map info server call failure
     * @param {} data 
     * @returns {} 
     */
    function onObjectListLoadFailure(data) {
        alert(data);
    }

    /**
     * Initial loading of form
     * @returns {} 
     */
    function load() {

        // spin up vue object
        vm.app = new Vue({
            el:vm.targetInfoId,

            data:{
                wizardStepCount:1,
                objectTypeSelection:'',
                userRoleSelection: '',
                bHaveObjectsSelected: false,
                bLoadingObjects:false,
                selectedObjects:[],

                bHaveUsersRolesSelected: false,
                bLoadingUsersRoles: false,
                selectedUserRoles: []
            },

            computed:{
                isStep1Active:function() {
                    return (this.wizardStepCount === 1);
                },

                isStep2Active:function() {
                    return (this.wizardStepCount === 2);
                },

                isStep3Active:function() {
                    return (this.wizardStepCount === 3);
                },

                isStep4Active:function() {
                    return (this.wizardStepCount === 4);
                },
            },

            methods:{
                wizardPageSelect:function(wizardStepNumber) {
                    this.wizardStepCount = wizardStepNumber;
                },

                setSelectedObjects: function(array) {
                    this.selectedObjects = [];
                    for (var i = 0; i < array.length; i++)
                        this.selectedObjects.push(array[i]);
                    this.bHaveObjectsSelected = (this.selectedObjects.length > 0);
                },

                setUserRoleObjects:function(array) {
                    this.selectedUserRoles = [];
                    for (var i = 0; i < array.length; i++)
                        this.selectedUserRoles.push(array[i]);
                    this.bHaveUsersRolesSelected = (this.selectedUserRoles.length > 0);
                }
            }

        });

        return;
    }
}

/**
 * Document onloaded function
 */

jQuery(document).ready(function($) {

  try {

    var params={
      siteRoot: ENTRADA_URL,
      apiRoot: API_URL,
      targetInfoId: 'olabContent',
      targetUserTableId: 'olabUserTable',
      targetRoleTableId: 'olabRoleTable',
      targetObjectTableId: 'olabObjectTable',
      location: document.location,
      authToken: JWT
    };

    // spin up helper class that does all the work
    view = new OlabSecurity(params);
    view.load();

  } catch(e) {
    alert(e.message);
  }

});