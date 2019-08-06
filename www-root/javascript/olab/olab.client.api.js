"use strict";

var OLabApiQuestion = function(clientApi, params) {

  var vm = this;
  vm.clientApi = clientApi;
  vm.params = params;
  vm.target = null;

  vm.service = {
    hide: hideObject,
    show: showObject,
    getObject: getObject
  }

  vm.target = jQuery("div#" + "QU_" + params).first();
  if (vm.target.length > 0) {
    return vm.service;
  } else {
    return null;
  }

  function hideObject() {
    vm.target.hide();
  }

  function showObject() {
    vm.target.show();
  }

  function getObject() {
    return vm.target;
  }
}

var OlabApiQuestionXXX = function(clientApi, params) {

  var vm = this;
  vm.clientApi = clientApi;
  vm.params = params;
  vm.target = null;

  vm.service = {
    hide: hideObject,
    show: showObject,
    getRadioValueId: getRadioValueId,
    getRadioValueText: getRadioValueText,
    getSingleText: getSingleText,
    getMultiText: getMultiText,
    getRawObject: getRawObject
  };

  vm.target = jQuery("div#" + "QU_" + params).first();
  if (vm.target.length > 0) {
    return vm.service;
  } else {
    return null;
  }

  function hideObject() {
    vm.target.hide();
  }

  function showObject() {
    vm.target.show();
  }

  function getRawObject() {
    return vm.target;
  }

  function getMultiText() {

  }

  function getSingleText() {
    var selected = vm.target.find("input");
    return selected.value();
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
      return new OLabApiQuestion(vm, id);
    }

    function hello() {

      alert("hello from OlabClientAPI." );
      var t = vm.player.getConstant("SystemTime");
      alert("time is: " + t['value']);
    }

};
