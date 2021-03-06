﻿// node link
Vue.component('olab-node-links', {
  template: `<div id='links'>
                 <div v-for='link in node.MapNodeLinks' 
                      class='olab-node-link' v-bind:id='"link" + link.DestinationNode.id' 
                      v-bind:style='visibility(link)'>
                   <a v-bind:class='classes(link)' 
                      v-bind:onclick='"olabPlayer.navigate(" + link.DestinationNode.id + ", " + link.id + " );"'>{{name(link)}}</a>
                   <br/>
                 </div>
               </div>`,
  props: ['node'],
  methods: {

    name: function (link) {

      if ((link.text != null) && (link.text.length !== 0)) {
        return link.text;
      } else {
        return link.DestinationNode.title;
      }
    },

    visibility: function (link) {

      if (link.hidden == "0") {
        return "display:inline;"
      }

      return "display:none;"

    },

    classes: function (link) {

      var classes = 'olab-node-link';

      // if link has hyperlink style, then done
      if (link.linkStyleId === 1) {
        return classes;
      }

      // if link has btn style, then add btn class
      if (link.linkStyleId === 5) {
        classes += " btn";
      }

      // if node has default links btn style, then add btn class
      else if (this.$props.node.linkStyleId === 5) {
        classes += " btn";
      }

      return classes;
    }
  }
});

var OlabLINKSTag = function (olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-node-links";

  var service = {
    render: render
  };

  return service;

  function render(wikiTagParts) {

    var element = "";

    try {

      var id = wikiTagParts[2];

      // build the vue.js component tag markup
      element = "<" + vm.OLAB_HTML_TAG +
                  " class='" + vm.OLAB_HTML_TAG + "'" +
                  " v-bind:node='node'>" + 
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