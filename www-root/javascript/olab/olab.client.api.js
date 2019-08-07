"use strict";

class OLabQuestion {

  constructor(clientApi, params) {

    this.clientApi = clientApi;
    this.params = params;
    this.basePath = "div#" + "QU_" + params;

    this.target = jQuery(this.basePath);
    if (this.target.length === 1) {
      this.target = this.target[0];
    } else {
      throw "Object '" + params + "' multiple instances or not found.";
    }

  }

  hide() {
    this.target.hide();
  }

  show() {
    this.target.show();
  }

}

class OLabQuestionSingleLine extends OLabQuestion {

  constructor(clientApi, params) {
    super(clientApi, params);
  }

  get value() {
    return this.target.value();
  }

}

class OLabQuestionRadio extends OLabQuestion {

  constructor(clientApi, params) {
    super(clientApi, params);
  }

  get choices() {

    var choices = [];

    var choiceObjs = jQuery(this.target).find("input[type='radio']");

    for (var i = 0; i < choiceObjs.length; i++) {
      var item = choiceObjs[i];
      choices.push({ id: parseInt(item.attributes['response'].value),
                     name: item.name, 
                     text: item.attributes['data-val'].value });
    }

    return choices;
  }

  get selected() {

    var choices = [];
    var items = jQuery(this.target).find("input[type='radio']:checked");

    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      choices.push({ id: parseInt(item.attributes['response'].value),
        name: item.name, 
        text: item.attributes['data-val'].value });
    }

    return choices;
  }

  onChanged( func ) {
    jQuery(this.target).find("input[type='radio']").click(func);
  }

}

var OLabApiQuestionXX = function(clientApi, params) {

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

    vm.player.utilities.log.debug("Created OlabClientAPI.");

    return vm.service;

    function getQuestion(id) {

      try {

        var question = vm.player.getQuestion(id);
        var questionType = question.questionType;

        if (questionType === 1) {
          return new OLabQuestionSingleLine(vm, id);
        } 
        else if (questionType === 4) {
          return new OLabQuestionRadio(vm, id);
        } 

      } catch (e) {

        vm.player.utilities.log.error( e.message );

      } 

      return null;
    }

    function hello() {

      alert("hello from OlabClientAPI." );
      var t = vm.player.getConstant("SystemTime");
      alert("time is: " + t['value']);
    }

};
