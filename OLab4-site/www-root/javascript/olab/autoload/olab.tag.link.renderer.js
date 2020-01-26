// node link
Vue.component('olab-node-link', {

  template: `<div class='olab-node-link' 
                  v-bind:id='"link" + link.DestinationNode.id' 
                  v-bind:style='visibility(link)'>
               <a v-bind:class='classes(link)' 
                  v-bind:onclick='"olabPlayer.navigate(" + link.DestinationNode.id + ", " + link.id + " );"'>{{name(link)}}</a>
             </div>`,
  props: ['nodeLinkStyleId', 'link'],
  methods: {

    name: function (link) {

      if ((link.text != null) && (link.text.length !== 0)) {
        return link.text;
      } else {
        return link.DestinationNode.title;
      }
    },

    visibility: function (link) {

      return "display:inline;"

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
      else if (this.$props.nodeLinkStyleId == 5) {
        classes += " btn";
      }

      return classes;
    }

  },
  data() {

    return {
      isButton: this.type === 'button'
    };

  }

});

var OlabLINKTag = function (olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-node-link";

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
                  " v-bind:link='link(\"" + id + "\")'" +
                  " node-link-style-id='" + vm.olabNodePlayer.node.linkStyleId + "'>" +
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