﻿/**
 * Main olab map list class
 * @param {} authToken = current auth token
 * @param {} contentDivBindName = main content view div name for data binding
 * @param {} websiteRootUrl = root url for web site
 * @param {} pageUrl = current page url (document.location)
 * @returns {} Service definition
 */

"use strict";

var OlabMapList= function(params) {

    var vm = this;

    vm.Utilities = new OLabUtilities(params.siteRoot, params.location, params.authToken);

    // get the map and nodeId from the url location hash
    var paramArray = vm.Utilities.getUrlParameters(params.location.hash);
    vm.urlParameters = {};
    vm.urlParameters.mapId = paramArray[0];
    vm.urlParameters.nodeId = paramArray[1];

    vm.targetTableId = vm.Utilities.normalizeIdAttribute(params.targetTableId);
    vm.targetInfoId = vm.Utilities.normalizeIdAttribute(params.targetInfoId);
    vm.websiteUrl = params.siteRoot;
    vm.moduleUrl = params.siteRoot + '/olab';
    vm.mapDataTable = null;

    vm.restApiUrl = params.apiRoot + '/olab';

    // spin up vue object
    vm.app = new Vue({

        el:vm.targetInfoId,

        data:{
            loadingDetail:false,
            detailDataPending:true,
            loadingConvert:false,
            convertDataPending:true,
            loadingList:true,
            haveInfo:true,
            data:{id:1999, title:'title'}
        }

    });

    // these are the methods/properties we expose to the outside
    var service = {
        app:vm.app,
        showDetail:showDetail,
        convertMap:convertMap,
        load:load,
        play:play,
        resume: resume
    };

    return service;

    /**
     * Build standard table options, including table data
     * @returns {} 
     */
    function buildTableOptions(data) {

        // convert associative array into indexed array compatible with DataTable
        var tableData = [];
        for (var i = 0; i < data.length; i++) {
            var row = [];
            row.push(data[i]['id']);
            row.push(data[i]['name']);
            row.push(data[i]['description']);
            row.push(data[i]['navigation']['mapId']);
            row.push(data[i]['userState']);
            row.push(data[i]['version']);
            row.push(0);
            tableData.push(row);
        }

        // get/default the initial pagelength
        var pageLength = vm.Utilities.readCookie("olab.maplist.pagesize");
        if (pageLength == null)
            pageLength = 5;

        var filterString = vm.Utilities.readCookie("olab.maplist.filter");

        return {
            'search': { 'search': filterString},
            'dom':'lfript',
            'order':[[1, 'asc']],
            'columns':[
                {'visible':false},
                {'title':'Name', 'width':'15%'},
                {'title':'Abstract'},
                {
                    'title':'Action',
                    'width':'17%',
                    'render':function(data, type, full, meta) {

                        var html = "";

                        // test if convertable-only map
                        if (full[5] == null) {

                            html =
                                '<a title="Convert to V4" onclick="view.convertMap(this);" class="btn btn-primary"><i class="fa fa-download"></i></a> ';

                        } else {
                            html =
                                '<a title="Map Info" onclick="view.showDetail(this);" class="btn btn-primary"><i class="fa fa-info-circle"></i></a> ';
                            html += '<a title="Play" onclick="view.play(' +
                                full[3] +
                                ',0 )" class="btn btn-primary"><i class="fa fa-play"></i></a> ';

                            if (typeof full[4] !== "undefined") {
                                html += '<a title="Resume from Checkpoint" onclick="view.resume(' +
                                    full[3] +
                                    ',' +
                                    full[4]["map_node_id"] +
                                    ')" class ="btn btn-primary"><i class ="fa fa-pause"></i></a>';
                            }
                        }
                        return html;
                    }
                },
            ],
            'lengthMenu':[[5, 10, 25, -1], [5, 10, 25, "All"]],
            "iDisplayLength": pageLength,
            "data":tableData
        };

    }

    function load() {

        var url = vm.restApiUrl;
        vm.Utilities.getJson(url, null, onMapListLoadSuccess, onMapListLoadFailure);
    }

    /**
     * Handle Map convert call success
     * @param {} data 
     * @returns {} 
     */
    function onMapConvertLoadSuccess(data) {

        if (vm.Utilities.testServerError(data))
            return;

        vm.app.data = data;
        vm.app.convertDataPending = false;
    }

    /**
     * Handle Map info server call success
     * @param {} data 
     * @returns {} 
     */
    function onMapDetailLoadSuccess(data) {

        if (vm.Utilities.testServerError(data))
            return;

        vm.app.data = data;
        vm.app.detailDataPending = false;
    }

    /**
     * Hanlde Map info server call failure
     * @param {} data 
     * @returns {} 
     */
    function onMapConvertLoadFailure(data) {
        alert(data);
    }

    /**
     * Hanlde Map info server call failure
     * @param {} data 
     * @returns {} 
     */
    function onMapDetailLoadFailure(data) {
        alert(data);
    }

    /**
     * Handle Map list load server call success
     * @param {} data 
     * @returns {} 
     */
    function onMapListLoadSuccess(data) {

        if (vm.Utilities.testServerError(data))
            return;

        vm.app.loadingList = false;
        var tableOptions = buildTableOptions(data.data);
        vm.mapDataTable = jQuery(vm.targetTableId).DataTable(tableOptions);

        // change the preferences any time the table changes
        vm.mapDataTable.on('draw', savePreferences);
    }

    /**
     * Handle map access server call failure
     * @param {any} data
     */
    function onMapListLoadFailure(data) {
        alert(data);
    }

    /**
     * Handle map access server call success
     * @param {any} data
     */
    function onMapResumeSuccess(data) {
        if (data.result) {
            var url = vm.moduleUrl + "/play#" + data.mapId + ":" + data.nodeId + "?resume=1";
            window.location.href = url;
        } else {
            alert(data.message);
        }
    }
    
    /**
     * Handle Map access server call failure
     * @param {} data 
     * @returns {} 
     */
    function onMapResumeFailure(data) {
        alert(data);

    }

      /**
     * Handle map access server call success
     * @param {any} data
     */
    function onMapPlaySuccess(data) {
        if (data.result) {
            var url = vm.moduleUrl + "/play/" + data.mapId + "#" + data.nodeId;
            window.location.href = url;
        } else {
            alert(data.message);
        }
    }
    
    /**
     * Handle Map access server call failure
     * @param {} data 
     * @returns {} 
     */
    function onMapPlayFailure(data) {
        alert(data);

    }


    /**
     * Play a new map/node
     * @param {} mapId 
     * @param {} nodeId 
     * @returns {} 
     */
    function play(mapId, nodeId) {

        try {

            var url = vm.restApiUrl + '/map/canopen/' + mapId + '/' + nodeId;
            vm.Utilities.getJson(url, null, onMapPlaySuccess, onMapPlayFailure);

        } catch (e) {
            vm.Utilities.testJavascriptError(e);
        }
    }

    /**
     * Resume a new map/node
     * @param {} mapId 
     * @param {} nodeId 
     * @returns {} 
     */
    function resume(mapId, nodeId) {

        try {

            var url = vm.restApiUrl + '/map/canopen/' + mapId + '/' + nodeId;
            vm.Utilities.getJson(url, null, onMapResumeSuccess, onMapResumeFailure);

        } catch (e) {
            vm.Utilities.testJavascriptError(e);
        }
    }

    /**
     * Converts a map to new format
     * @param {} name current map name 
     * @returns {} 
     */
    function convertMap(name) {

        try {

            vm.app.convertDataPending = true;
            vm.app.loadingConvert = true;
            vm.app.detailDataPending = false;
            vm.app.loadingDetail = false;

            var data = vm.mapDataTable.row(jQuery(name).parents('tr')).data();
            var url = vm.restApiUrl + '/convert/' + data[0];

            vm.Utilities.getJson(url, null, onMapConvertLoadSuccess, onMapConvertLoadFailure);

        } catch (e) {
            vm.Utilities.testJavascriptError(e);
        }
    }

    /**
     * Persists table preferences
     */
    function savePreferences() {

        vm.Utilities.createCookie("olab.maplist.filter", vm.mapDataTable.search(), 30);
        vm.Utilities.createCookie("olab.maplist.pagesize", vm.mapDataTable.page.len(), 30);

    }

    function showDetail(name) {

        try {

            vm.app.detailDataPending = true;
            vm.app.loadingDetail = true;
            vm.app.convertDataPending = false;
            vm.app.loadingConvert = false;


            var data = vm.mapDataTable.row(jQuery(name).parents('tr')).data();
            var url = vm.restApiUrl + '/map/info/' + data[0];

            vm.Utilities.getJson(url, null, onMapDetailLoadSuccess, onMapDetailLoadFailure);

        } catch (e) {
            vm.Utilities.testJavascriptError(e);
        }

    }

}

jQuery(document).ready(function($) {

  try {

    var params={
      siteRoot: ENTRADA_URL,
      apiRoot: API_URL,
      targetInfoId: 'olabContent',
      targetTableId: 'olabMapData',
      location: document.location,
      authToken: JWT
    };

    // spin up helper class that does all the work
    view=new OlabMapList(params);
    view.load();

  } catch(e) {
    alert(e.message);
  }

});