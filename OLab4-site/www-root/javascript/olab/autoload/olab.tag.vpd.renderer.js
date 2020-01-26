Vue.component('olab-vpd',
{
    template:`<table rules="NONE" frame="BOX" width="100%" cellspacing="0" cellpadding="6" border="1">
                <tbody>
                    <tr>
                        <td width="30%" valign="top" align="left"><p><strong>Patient Data</strong></p></td>
                        <td valign="top" align="left"><p>Alice Bond : Name</p></td>
                    </tr>
                </tbody>
              </table>`,
    props:['src']
});

var OlabVPDTag = function(olabNodePlayer) {

  var vm = this;
  vm.olabNodePlayer = olabNodePlayer;
  vm.OLAB_HTML_TAG = "olab-vpd";

  var service = {
    render:render
  };

  return service;

  function render(wikiTagParts) {

    var element = "";

    try {

      var id = wikiTagParts[2];
      var tag = vm.OLAB_HTML_TAG;

      element = "<" + tag + " class='" + tag + "'></" + tag + ">";

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