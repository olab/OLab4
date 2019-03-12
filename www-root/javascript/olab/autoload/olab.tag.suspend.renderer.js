// node link
Vue.component('olab-suspend', {

    template: `<div class='olab-suspend'>
                 <a v-bind:class='classes()' title='Click to checkpoint current node' v-on:click='onClicked(nodeId)'>Suspend</a>
               </div>`,
    props: ['nodeId'],
    methods: {

      classes: function( link ) {
        var classes= 'olab-suspend';
        classes += " btn";
        return classes;
      },

      onClicked: function(nodeId) {

        var form = jQuery("form#olab");
        if (form.length === 0)
          return;

        var formData = jQuery("form#olab").serializeArray();
        if (formData.length === 0)
          return;

        var payload = {
           'nodeId': nodeId,
           'formData': formData
        };
        this.$parent.onSuspendClicked(payload);
      }
    }
});

var OlabSUSPENDTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-suspend";

    var service = {
        render:render
    };

    return service;

    function render(wikiTagParts) {

        var element = "";

        try {

            // build the vue.js component tag markup
            element = "<" +
                vm.OLAB_HTML_TAG +
                " class='" +
                vm.OLAB_HTML_TAG +
                "' nodeId='" + vm.olabNodePlayer.urlParameters.nodeId + "'>" +
                "</" +
                vm.OLAB_HTML_TAG +
                ">";

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