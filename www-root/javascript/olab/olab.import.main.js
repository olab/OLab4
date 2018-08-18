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

var OlabImportUpload = function(params) {

    var vm = this;

    vm.Utilities = new OLabUtilities(params.siteRoot, params.location, params.authToken);

    vm.onloadstartHandler = params.onloadstartHandler;
    vm.onprogressHandler = params.onprogressHandler;
    vm.onloadHandler = params.onloadHandler;
    vm.onreadystatechangeHandler = params.onreadystatechangeHandler;

    // get the map and nodeId from the url location hash
    var paramArray = vm.Utilities.getUrlParameters(params.location.hash);
    vm.urlParameters = {};

    vm.websiteUrl = params.siteRoot;
    vm.moduleUrl = params.siteRoot + '/olab';

    vm.restApiUrl = params.apiRoot + '/olab';

    // Actually confirm support
    if (supportAjaxUploadWithProgress()) {

        // Ajax uploads are supported!
        // Change the support message and enable the upload button
        var notice = document.getElementById('support-notice');
        var uploadBtn = document.getElementById('upload-button-id');

        notice.innerHTML = "Your browser supports HTML uploads. Go try me! :-)";
        uploadBtn.removeAttribute('disabled');

        // Init the Ajax form submission
        initFullFormAjaxUpload();

        // Init the single-field file upload
        initFileOnlyAjaxUpload();
    }

    // these are the methods/properties we expose to the outside
    var service = {
        importUpload:importUpload
    };

    return service;

    function importUpload(data ) {

        vm.Utilities.postData( "http://olab4.localhost/apidev/olab/import/upload", data );

    }

    function initFullFormAjaxUpload() {

        var form = document.getElementById('form-id');

        form.onsubmit = function () {
            // FormData receives the whole form
            var formData = new FormData(form);
            // We send the data where the form wanted
            var action = form.getAttribute('action');
            // Code common to both variants
            sendXHRequest(formData, action);
            // Avoid normal form submission
            return false;
        }
    }

    function initFileOnlyAjaxUpload() {

        var uploadBtn = document.getElementById('upload-button-id');

        uploadBtn.onclick = function (evt) {

            var formData = new FormData();
            // Since this is the file only, we send it to a specific location
            var action = 'http://olab4.localhost/apidev/api/v2/olab/import/upload';

            // FormData only has the file
            var fileInput = document.getElementById('file-id');

            var file = fileInput.files[0];
            formData.append('files', file);

            // Code common to both variants
            sendXHRequest(formData, action);
        }
    }

    // Once the FormData instance is ready and we know
    // where to send the data, the code is the same
    // for both variants of this technique
    function sendXHRequest(formData, uri) {

        // Get an XMLHttpRequest instance
        var xhr = new XMLHttpRequest();
        // Set up events
        xhr.upload.addEventListener('loadstart', vm.onloadstartHandler, false);
        xhr.upload.addEventListener('progress', vm.onprogressHandler, false);
        xhr.upload.addEventListener('load', vm.onloadHandler, false);
        xhr.addEventListener('readystatechange', vm.onreadystatechangeHandler, false);

        // Set up request
        xhr.open('POST', uri, true);

        // set auth token
        xhr.setRequestHeader('Authorization', vm.Utilities.getAuthHeader() );

        // Fire!
        xhr.send(formData);
    }

    // Function that will allow us to know if Ajax uploads are supported
    function supportAjaxUploadWithProgress() {

        return supportFileAPI() && supportAjaxUploadProgressEvents() && supportFormData();
        // Is the File API supported?
        function supportFileAPI() {
            var fi = document.createElement('INPUT');
            fi.type = 'file';
            return 'files' in fi;
        };
        // Are progress events supported?
        function supportAjaxUploadProgressEvents() {
            var xhr = new XMLHttpRequest();
            return !!(xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
        };
        // Is FormData supported?
        function supportFormData() {
            return !!window.FormData;
        }
    }

}

/**
 * Document onloaded function
 */
jQuery(document).ready(function ($) {

  try {

    var uploadBtn = document.getElementById('upload-button-id');

    uploadBtn.onclick = function (evt) {

        var formData = new FormData();
        // Since this is the file only, we send it to a specific location
        var action = 'http://olab4.localhost/apidev/api/v2/olab/import/upload';
        // FormData only has the file
        var fileInput = document.getElementById('file-id');
        var file = fileInput.files[0];
        formData.append('our-file', file);
        // Code common to both variants
        sendXHRequest(formData, action);
    }

    // Handle the start of the transmission
    function onloadstartHandler(evt) {
        var div = document.getElementById('upload-status');
        div.innerHTML = 'Upload started.';
    }

    // Handle the end of the transmission
    function onloadHandler(evt) {
        var div = document.getElementById('upload-status');
        div.innerHTML += '<' + 'br>File uploaded. Waiting for response.';
    }

    // Handle the progress
    function onprogressHandler(evt) {
        var div = document.getElementById('progress');
        var percent = evt.loaded / evt.total * 100;
        div.innerHTML = 'Progress: ' + percent + '%';
    }

    // Handle the response from the server
    function onreadystatechangeHandler(evt) {
        var status, text, readyState;
        try {
            readyState = evt.target.readyState;
            text = evt.target.responseText;
            status = evt.target.status;
        }
        catch (e) {
            return;
        }
        if (readyState == 4 && status == '200' && evt.target.responseText) {
            var status = document.getElementById('upload-status');
            status.innerHTML += '<' + 'br>Success!';
            var result = document.getElementById('result');
            result.innerHTML = '<p>Conversion status:</p><pre>' + evt.target.responseText + '</pre>';
        }
    }

    var params = {
      siteRoot: WEBSITE_ROOT,
      apiRoot: API_URL,
      location: document.location,
      authToken: JWT,
      onloadstartHandler: onloadstartHandler,
      onprogressHandler: onprogressHandler,
      onloadHandler: onloadHandler,
      onreadystatechangeHandler: onreadystatechangeHandler
    };

    // spin up class that does all the work
    var olab = new OlabImportUpload(params);

  } catch (e) {
    alert(e.name + ":" + e.message);
  }

});