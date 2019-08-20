Vue.component('olab-download', {
    template: `<div>
               <div style="display:none">
                 <a v-bind:id="'file-link-' + file.id"></a>
               </div>
               <a v-bind:onclick="'olabPlayer.downloadFile(' + file.id + ')'">
                {{file.name}}
               </a>
             </div>`,
    props: ['file']
});

var OlabDLTag = function ( olabNodePlayer ) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-download";

  var service = {
    render: render
  };

  return service;

  function render( wikiTagParts ) {

    var element = "";

    try {

      var id = wikiTagParts[2];
      // get the view variable name so vue.js can bind to it
      var file = vm.olabNodePlayer.getFile(id);

      // build the vue.js component tag markup
      element = "<" + vm.OLAB_HTML_TAG +
                " class='" + vm.OLAB_HTML_TAG + "'" +
                " id='" + vm.OLAB_HTML_TAG + id +
                "' v-bind:file='file(\"" + id + "\")'></" +
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