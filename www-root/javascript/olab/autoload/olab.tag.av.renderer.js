// image
Vue.component('olab-image', {
    template: `<img v-bind:src='src' v-bind:alt='filename'></img>`,
    props: ['src', 'filename']
});

var OlabAVTag = function(olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-image";

  var service = {
    render:render
  };

  return service;

  function render(wikiTagParts) {

    var element = "";

    try {

      var id = wikiTagParts[2];
      var tag = vm.OLAB_HTML_TAG;

      // get the view variable name so vue.js can bind to it
      var file = vm.olabNodePlayer.getAvatar(id);

      element = "<" + vm.OLAB_HTML_TAG + " class='" + tag + "'" +
        " id='" + vm.OLAB_HTML_TAG + id + "'" +
        " filename='" + file.item['image'] + "'" +
        " src='" + vm.olabNodePlayer.mediaUrl + file.item["image"] + "'></" +
        vm.OLAB_HTML_TAG + ">";

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