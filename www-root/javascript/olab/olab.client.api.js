"use strict";

/**
 * Main olab client api class
 * @param {} authToken = current auth token
 * @param {} targetId = main content view div name for data binding
 * @param {} websiteRootUrl = root url for web site
 * @param {} pageUrl = current page url (document.location)
 * @returns {} Service definition
 */

var OlabApiQuestion = function(clientApi, params) {

  var vm = this;
  vm.clientApi = clientApi;
  vm.params = params;
  vm.target = null;

  vm.service = {
    getRadioValueId: getRadioValueId,
    getRadioValueText: getRadioValueText
  };

  vm.target = jQuery("div#" + params);
  if (vm.target.length > 0) {
    return vm.service;
  } else {
    return null;
  }

  function getRadioValueId() {
    var selected = vm.target.find("input:checked");
    return selected.attr("response");
  }

  function getRadioValueText() {
    var selected = vm.target.find("input:checked");
    return selected.attr("data-val");
  }

}

// main view class
var OlabClientAPI = function(params) {

    var vm = this;

    vm.player = params.olabPlayer;

    // these are the methods/properties we expose to the outside
    vm.service = {
      hello: hello,
      getQuestion: getQuestion
    };

    return vm.service;

    function getQuestion(id) {
      return new OlabApiQuestion(vm, id);
    }

    function hello() {

      alert("hello from OlabClientAPI." );
      var t = vm.player.getConstant("SystemTime");
      alert("time is: " + t['value']);
    }

};
