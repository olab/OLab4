// counter
Vue.component('olab-counter', {
    template: `<span v-html='counter.value'></span>`,
    props: ['counter']
});

var OlabCRTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-counter";

    var service = {
        render:render
    };

    return service;

    function render(wikiTagParts) {

        var element = "";

        try {

            var id = wikiTagParts[2];
            // get the view variable name so vue.js can bind to it
            var varName = vm.olabNodePlayer.getCounterBindingVariable(id);

            if (varName !== null) {
                // build the vue.js component tag markup
                element = "<" +
                    vm.OLAB_HTML_TAG +
                    " class='" +
                    vm.OLAB_HTML_TAG +
                    "'" +
                    " v-bind:counter='" +
                    varName +
                    "'>" +
                    "</" +
                    vm.OLAB_HTML_TAG +
                    ">";
            } else {
                throw new Error("not found.");
            }

            vm.olabNodePlayer.log.debug(element);

        } catch (e) {
            element = "[[" + wikiTagParts.join("") + " ERROR: '" + e.message + "']]";
            vm.olabNodePlayer.log.error(element);
        }

        return element;
    }

};

jQuery(window).ready(function ($) {

  try {
    //alert("OlabCRTag");
  } catch (e) {
    alert(e.message);
  }

});