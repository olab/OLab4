var OlabMRTag = function ( olabNodePlayer ) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-download";

  var service = {
    render: render
  };

  return service;

  function IsAudioType(mimeType) {
    return (mimeType.indexOf("audio/") !== -1);
  }

  function IsImageType(mimeType) {
    return (mimeType.indexOf("image/") !== -1);
  }

  function IsVideoType(mimeType) {
    return (mimeType.indexOf("video/") !== -1);
  }


  function render( wikiTagParts ) {

    var element = "";

    try {

      var id = wikiTagParts[2];
      var tag = vm.OLAB_HTML_TAG;

      // get the view variable name so vue.js can bind to it
      var file = vm.olabNodePlayer.getFile(id);

      // test if image mime type
      if (IsImageType(file.item["mime"])) {
        tag = "olab-image";
      }

      // test if audio mime type
      if (IsAudioType(file.item["mime"])) {
        tag = "olab-audio";
      }

      // test if video mime type
      if (IsVideoType(file.item["mime"])) {
        tag = "olab-video";
      }

      // build the vue.js component tag markup
      element = "<" + tag +
                " class='" + tag + "'" +
                " id='" + tag + id + "'" +
                " v-bind:file='file(" + id + ")'" +
                " src='" + file["encodedContent"] + "'></" +
                tag + ">";

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