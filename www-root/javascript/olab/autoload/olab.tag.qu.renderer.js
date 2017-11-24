var OlabQUTag = function ( olabNodePlayer ) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-question-";

  var service = {
    render: render
  };

  return service;

  function render( wikiTagParts ) {

    var element = "";

    try {

      var id = wikiTagParts[2];
      // get the view variable name so vue.js can bind to it
      var question = vm.olabNodePlayer.getQuestion(id);

      if (question == null) {
        throw new Error("not found.");
      }

      var questionType = question.item['QuestionTypes']['value'];
      var questionTag = vm.OLAB_HTML_TAG + questionType;
      var changeHandler = "";

      switch (questionType) {
        case "radio":
          changeHandler = " v-bind:onChanged='onRadioResponseChanged' ";
          break;
        default:
      }
      // build the vue.js component tag markup
      element = "<" + questionTag +
                " class='question " + questionTag + "'" +
                " id='" + questionType + id + "'" +
                changeHandler +
                " v-bind:question='question(" + id + ")'></" +
                questionTag + ">";

      vm.olabNodePlayer.log.debug(element);

    } catch (e) {
      element = "[[" + wikiTagParts.join("") + " ERROR: '" + e.message + "']]";
      vm.olabNodePlayer.log.error(element);
    }

    return element;
  }

}

jQuery(window).ready(function ($) {

  try {
    //alert("OlabCRTag");
  } catch (e) {
    alert(e.message);
  }

});