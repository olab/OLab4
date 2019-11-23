// audio
Vue.component('olab-audio', {
    template: `<audio v-bind:src="src" v-bind:alt='filename' controls="" preload="auto" autoplay="autoplay" autobuffer=""></audio>`,
    props:['src', 'filename']
});

// image
Vue.component('olab-image', {
    template: `<img v-bind:src='src' v-bind:alt='filename'></img>`,
    props: ['src', 'filename']
});

// image
Vue.component('olab-video', {
    template: `<video controls><source v-bind:type='file.mime' v-bind:src='file.content'></video>`,
    props: ['file']
});

var OlabMRTag = function(olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-download";

  var service = {
    render:render
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


  function render(wikiTagParts) {

    var element = "";

    try {

      var id = wikiTagParts[2];
      var tag = vm.OLAB_HTML_TAG;

      // get the view variable name so vue.js can bind to it
      var file = vm.olabNodePlayer.getFile(id);

      // test if image mime type
      if (IsImageType(file["mime"])) {
        tag = "olab-image";
      }
      // test if audio mime type
      else if (IsAudioType(file["mime"])) {
        tag = "olab-audio";
      }
      // test if video mime type
      else if (IsVideoType(file["mime"])) {
        tag = "olab-video";
      }

      // test if file has embedded content that will be put inline
      // in the HTML.  otherwise its a public-accessible URL for the src
      if ( file['isEmbedded'] == 1 ) {

        // handle special case of PDF file
          if (file["mime"] == "application/pdf") {

              element = "<embed src = '" +
                  vm.olabNodePlayer.mediaUrl +
                  file["path"] +
                  "' " +
                  " width = \"500\" height = \"375\" type = 'application/pdf' >";
          }
          //else {

          //  element = "<" + tag + " class='" + tag + "'" +
          //    " id='" + tag + id + "'" +
          //    " filename='" + file['name'] + "'" +
          //    " src='" + file["encodedContent"] + "'></" + tag + ">";
          //}

      } else {

          element = "<" + tag + " class='" + tag + "'" +
            " id='" + tag + id + "'" +
            " filename='" + file['name'] + "'" +
            " src='" + file["resourceUrl"] + "'></" + tag + ">";
      }

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