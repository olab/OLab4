"use strict";

class OLabClientObject {

  self = this;

  constructor(clientApi, params) {

    this.clientApi = clientApi;
    this.params = params;
    this.target = null;
    this.player = this.clientApi.player;
  }

}

class OLabCounter extends OLabClientObject {

  constructor(clientApi, params) {

    super(clientApi, params);
    this.target = this.clientApi.player.getCounter(this.params.id);
    this.setProgressObject( "progressSpinner" );
  }

  getValue() {

    if ( this.target == null ) {
      throw "Object '" + params.id + "' not found.";
    }

    return this.target['value'];
  }

  setValue( value, onStarted, onCompleted ) {

    this.onStartedUser = onStarted;
    this.onCompletedUser = onCompleted;

    this.player.instance.log.debug("Setting counter " + this.target.id + " = " + value );

    var url = this.player.instance.restApiUrl + "/counters/value/" + this.target.id;
    var payload = { "data": { "value": value } };

    this.onUpdateStarted();

    this.player.utilities.postJson( url, payload, this, this.onUpdateCompleted, this.onUpdateError );
  }

  onUpdateStarted( data ) {

    try {

      // if a progress object is set, make it visible 
      if ( this.progressTarget != null ) {
        this.progressTarget.show();
      }    

      if ( this.onStartedUser != null ) {
        this.onStartedUser();
      }    

      this.player.instance.log.debug("Counter " + this.target.id + " update started" );

    } catch (e) {
      alert(e.message);
    }


  }

  onUpdateCompleted( data, context ) {

    try {

      // if a progress object is set, make it visible 
      if ( context.progressTarget != null ) {
        context.progressTarget.hide();
      }  

      if ( context.onCompletedUser != null ) {
        context.onCompletedUser();
      }

      context.player.instance.log.debug("Counter " + context.target.id + 
        " set successfully. value = " + data.data.value );

    } catch (e) {
      alert(e.message);
    }

  }

  onUpdateError( data ) {

    try {

      // if a progress object is set, make it visible 
      if ( this.progressTarget != null ) {
        this.progressTarget.hide();
      }  

      if ( this.onCompletedUser != null ) {
        this.onCompletedUser();
      }

      this.player.instance.log.debug("Counter " + this.target.id + " update error" );

    } catch (e) {
      alert(e.message);
    }

    alert("error: " + data );
  }

  setProgressObject( divId ) {

    this.basePath = "img#" + divId;
    this.progressTarget = jQuery(this.basePath);
    if (this.progressTarget.length >= 1) {
      this.progressTarget = this.progressTarget[0];
    }
    else {
      this.progressTarget = null;
    }
  }

}

class OLabQuestion extends OLabClientObject {

  constructor(clientApi, params) {

    super(clientApi, params);
    this.basePath = "div#" + "QU_" + this.params.id;

    this.target = jQuery(this.basePath);
    if (this.target.length === 1) {
      this.target = this.target[0];
    } else {
      throw "Object '" + this.params.id + "' multiple instances or not found.";
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
      getCounter: getCounter,
      log: vm.player.instance.log
    };

    vm.player.utilities.log.debug("Created OlabClientAPI.");

    return vm.service;

    function getCounter(id) {

      try {

        var params = [];
        params.id = id;

        return new OLabCounter(vm, params);

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
