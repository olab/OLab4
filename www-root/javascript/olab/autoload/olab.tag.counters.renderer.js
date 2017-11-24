var OlabCOUNTERTag = function ( olabNodePlayer ) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-counters";

  var service = {
    render: render
  };

  return service;

  function render( wikiTagParts ) {

    var element = "";

    try {

      // build the vue.js component tag markup
      element = "<" + vm.OLAB_HTML_TAG +
                " class='" + vm.OLAB_HTML_TAG + "'" +
                " v-bind:server='server.Counters' v-bind:map='map.Counters' v-bind:node='node.Counters'>" +
                "</" + vm.OLAB_HTML_TAG + ">";

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