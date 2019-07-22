/**
 * Main olab client api class
 * @param {} authToken = current auth token
 * @param {} targetId = main content view div name for data binding
 * @param {} websiteRootUrl = root url for web site
 * @param {} pageUrl = current page url (document.location)
 * @returns {} Service definition
 */

"use strict";
// main view class
var OlabClientAPI = function(params) {

    var vm = this;

    vm.player = params.olabPlayer;

    // these are the methods/properties we expose to the outside
    vm.service = {
      hello: hello
    };

    return vm.service;

    function hello() {

      alert("hello from OlabClientAPI." );
      var t = vm.player.getConstant("SystemTime");
      alert("time is: " + t['value']);
    }

};
