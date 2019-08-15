"use strict";

class OLabQuestion {

  constructor(clientApi, params) {

    this.clientApi = clientApi;
    this.params = params;
    this.basePath = "div#" + "QU_" + params.id;

    this.target = jQuery(this.basePath);
    if (this.target.length === 1) {
      this.target = this.target[0];
    } else {
      throw "Object '" + params.id + "' multiple instances or not found.";
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

class OLabChoicesQuestion extends OLabQuestion {

  constructor(clientApi, params) {
    super(clientApi, params);

    this.choiceObjs = jQuery(this.target).find("input[type='" + params.inputType + "']");   
  }

  get rawChoices() {
    return this.choiceObjs;
  }

  disable() {

    try {

      jQuery.each( this.choiceObjs, function(index, value) {
        jQuery(value).prop('disabled', true);
      });

    } catch (e) {
      alert(e.message);
    }
  }

  enable() {

    try {

      jQuery.each( this.choices, function(index, value) {
        jQuery(value).prop('disabled', true);
      });

    } catch (e) {
      alert(e.message);
    }

  }

}

class OLabQuestionMultipleChoice extends OLabChoicesQuestion {

  constructor(clientApi, params) {
    params['inputType'] = 'checkbox';
    super(clientApi, params);
  }

  get choices() {

    var choiceObjs = this.rawChoices();
    for (var i = 0; i < choiceObjs.length; i++) {
      var item = choiceObjs[i];
      choices.push({ id: parseInt(item.attributes['response'].value),
        name: item.name, 
        text: item.attributes['data-val'].value });
    }

    return choices;
  }

  get selected() {

    var choiceObjs = this.rawChoices();
    for (var i = 0; i < choiceObjs.length; i++) {
      var item = items[i];
      choices.push({ id: parseInt(item.attributes['response'].value),
        name: item.name, 
        text: item.attributes['data-val'].value });
    }

    return choices;
  }

  onChanged( func ) {
    this.choiceObjs.click(func);
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

  disable() {

    try {

      var choiceObjs = jQuery(this.target).find("input[type='radio']");
      jQuery.each( choiceObjs, function(index, value) {
        jQuery(value).prop('disabled', true);
      });

    } catch (e) {
      alert(e.message);
    }
  }

  enable() {

    try {

      var choiceObjs = jQuery(this.target).find("input[type='radio']");
      jQuery.each( choiceObjs, function(index, value) {
        jQuery(value).prop('disabled', true);
      });

    } catch (e) {
      alert(e.message);
    }

  }

  onChanged( func ) {
    jQuery(this.target).find("input[type='radio']").click(func);
  }

}

// main view class
var OlabClientAPI = function(params) {

    var vm = this;

    vm.player = params.olabPlayer;

    // these are the methods/properties we expose to the outside
    vm.service = {
      hello: hello,
      getQuestion: getQuestion,
      getCounter: getCounter
    };

    vm.player.utilities.log.debug("Created OlabClientAPI.");

    return vm.service;

    function getCounter(id) {

      try {

        var counter = vm.player.getCounter(id);
        return counter;

      } catch (e) {

        vm.player.utilities.log.error( e.message );

      }

      return null;

    }

    function getQuestion(id) {

      try {

        var params = [];
        params.id = id;

        var question = vm.player.getQuestion( params.id );
        var questionType = question.questionType;

        if (questionType === 1) {
          return new OLabQuestionSingleLine(vm, params);
        } 
        else if (questionType === 3) {
          return new OLabQuestionMultipleChoice(vm, params);
        } 
        else if (questionType === 4) {
          return new OLabQuestionRadio(vm, params);
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
