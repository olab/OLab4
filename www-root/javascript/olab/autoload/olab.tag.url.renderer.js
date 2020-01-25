var OlabURLTag = function (olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;

  var service = {
    render: render
  };

  return service;

  function render(wikiTagParts) {

    var element = "";
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