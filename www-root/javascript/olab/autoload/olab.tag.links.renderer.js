// node link
Vue.component('olab-node-links', {
    template: `<div id='links'>
                 <div v-for='link in links' class='olab-node-link' v-bind:id='"link" + link.DestinationNode.id' >
                    <a v-bind:class='classes(link)' v-bind:onclick='"olab.navigate(" + link.DestinationNode.id + ");"'>{{link.DestinationNode.title}}</a>
                    <br/>
                 </div>
               </div>`,
    props: ['links'],
    methods: {
        classes: function( link ) {
            var classes= 'olab-node-link';
            if (link.link_style_id === 5) {
                classes += " btn";
            }
            return classes;
        }
    }
});

var OlabLINKSTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-node-links";

    var service = {
        render:render
    };

    return service;

    function render(wikiTagParts) {

        var element = "";

        try {

            var id = wikiTagParts[2];

            // build the vue.js component tag markup
            element = "<" +
                vm.OLAB_HTML_TAG +
                " class='" +
                vm.OLAB_HTML_TAG +
                "'" +
                " v-bind:links='node.MapNodeLinks'>" +
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