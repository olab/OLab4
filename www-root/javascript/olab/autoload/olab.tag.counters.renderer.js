// counter
Vue.component('olab-counter', {
    template: `<span v-html='counter.value'></span>`,
    props: ['counter']
});

// counters
Vue.component('olab-counters', {
    template: `<ul class="navigation">
              <li v-for='counter in server'>
                <a data-toggle="modal" href="#" data-target="#counter-debug">{{counter.name}}</a>&nbsp;{{counter.value}}
              </li>
              <li v-for='counter in map'>
                <a data-toggle="modal" href="#" data-target="#counter-debug">{{counter.name}}</a>&nbsp;{{counter.value}}
              </li>
              <li v-for='counter in node'>
                <a data-toggle="modal" href="#" data-target="#counter-debug">{{counter.name}}</a>&nbsp;{{counter.value}}
              </li>

            </ul>`,
    props: ['server', 'map', 'node']
});

var OlabCOUNTERSTag = function(olabNodePlayer) {

    var vm = this;
    vm.olabNodePlayer = olabNodePlayer;
    vm.OLAB_HTML_TAG = "olab-counters";

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
                "'" +
                " v-bind:server='server.counters' v-bind:map='map.counters' v-bind:node='node.counters'>" +
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