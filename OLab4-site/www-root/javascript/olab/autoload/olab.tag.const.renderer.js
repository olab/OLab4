// constant
Vue.component('olab-constant', {
    template: `<span v-html='constant.value'></span>`,
    props: ['constant']
});

var OlabCONSTTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-constant";

    var service = {
        render:render
    };

    return service;

    function render(wikiTagParts) {

        var element = "";

        try {

            var id = '"' + wikiTagParts[2] + '"';

            // build the vue.js component tag markup
            element = "<" +
                vm.OLAB_HTML_TAG +
                " id='CONST_" + wikiTagParts[2] + "'" +
                " class='" + vm.OLAB_HTML_TAG + "'" +
                " v-bind:constant='constant(" + id + ")'>" +
                "</" + vm.OLAB_HTML_TAG + ">";

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