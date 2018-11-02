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

    vm.targetUserTableId = vm.Utilities.normalizeDivId(params.targetUserTableId);
    vm.targetRoleTableId = vm.Utilities.normalizeDivId(params.targetRoleTableId);
    vm.targetObjectTableId = vm.Utilities.normalizeDivId(params.targetObjectTableId);
    vm.targetInfoId = vm.Utilities.normalizeDivId(params.targetInfoId);

    // get the map and nodeId from the url location hash
    var paramArray = vm.Utilities.getUrlParameters(params.location.hash);
    vm.urlParameters = {};

    vm.websiteUrl = params.siteRoot;
    vm.moduleUrl = params.siteRoot + '/olab';

    var data = { 
        "roles": 
            [
                { "id": 0, "name": "OLab:Superuser" },
                { "id": 1, "name": "OLab:Learner" },
                { "id": 2, "name": "OLab:Admin" }
            ], 
        "users": 
            [
                { "id": 0, "name": "olabadmin" },
                { "id": 1, "name": "aopps" },
                { "id": 2, "name": "ltopps" },
                { "id": 3, "name": "rtopps" },
                { "id": 3, "name": "dtopps" }
            ], 
        "objectTypes": 
            [
                { "id": 0, "name": "Maps" },
                { "id": 1, "name": "Map Nodes" }
            ] };


    var tableUserOptions = buildSelectionTableOptions(data.users);
    vm.userDataTable = jQuery(vm.targetUserTableId).DataTable(tableUserOptions);

    var tableRoleOptions = buildSelectionTableOptions(data.roles);
    vm.roleDataTable = jQuery(vm.targetRoleTableId).DataTable(tableRoleOptions);

        // spin up vue object
    vm.app = new Vue({

        el:vm.targetInfoId,

        data() {
            return {
                pageMode: "users"
            }
        }

    });

    // these are the methods/properties we expose to the outside
    var service = {
        app:vm.app
    };

    /**
     * Build standard table options, including table data
     * @returns {} 
     */
    function buildSelectionTableOptions(data) {

        // convert associative array into indexed array compatible with DataTable
        var tableData = [];
        for (var i = 0; i < data.length; i++) {
            var row = [];
            row.push(data[i]['id']);
            row.push(data[i]['name']);
            row.push(0);
            tableData.push(row);
        }

        // get/default the initial pagelength
        var pageLength = vm.Utilities.readCookie("olab.maplist.pagesize");
        if (pageLength == null)
            pageLength = 5;

        var filterString = vm.Utilities.readCookie("olab.maplist.filter");

        return {
            'dom':'lfript',
            'order':[[1, 'asc']],
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
            ],
            'lengthMenu':[[5, 10, 25, -1], [5, 10, 25, "All"]],
            'iDisplayLength': pageLength,
            'data': tableData
        };

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
    view=new OlabSecurity(params);

  } catch(e) {
    alert(e.message);
  }

});