// node jump
Vue.component('olab-node-jump', {

  template: `<div class='olab-node-jump' v-bind:id='"jump" + jump.DestinationNode.id' v-bind:style='visibility(jump)'>
               <a v-bind:class='classes(jump)' v-bind:onclick='"olabPlayer.navigate(" + jump.DestinationNode.id + ", " + jump.id + " );"'>{{name(jump)}}</a>
             </div>`,
  props: ['jump'],
  methods: {

    name: function (jump) {

      if ((jump.text != null) && (jump.text.length !== 0)) {
        return jump.text;
      } else {
        return jump.DestinationNode.title;
      }
    },

    visibility: function (jump) {

      if (jump.hidden == "0") {
        return "display:inline;"
      }

      return "display:none;"

    },

    classes: function (jump) {

      var classes = 'olab-node-jump';
      if (this.$props.jump.linkStyleId === 5) {
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

var OlabJUMPTag = function (olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-node-jump";

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
                  " v-bind:jump='jump(\"" + id + "\")'>" +
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