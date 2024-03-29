<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Organisation: Univerity of Calgary
 * @author Unit: Cumming School of Medicine
 * @author Developer: Corey Wirun
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 *
 */
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OLabUtilities;

// loads any system-level script files
function getKernelScripts($dir, &$results = array()){
  $files = scandir($dir);
  foreach($files as $key => $value){
    $path = $dir.DIRECTORY_SEPARATOR.$value;
    if(!is_dir($path)) {
      $results[] = str_replace( $dir, "", $path );
    }
  }
  return $results;
}
if((!defined("PARENT_INCLUDED")) || (!defined("IN_OLAB"))) {
  exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
  header("Location: ".ENTRADA_URL);
  exit;
} else {

  $script_version = OLabUtilities::get_script_version();

  OLabUtilities::addToHead( "<script>var WEBSITE_ROOT = \"" . HostSystemApi::getRootUrl() . "\";</script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/vue/vue.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/vee-validate/vee-validate.min.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/axios/axios.min.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.utilities.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.client.api.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.play.components.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.node.player.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.play.main.js?ver=" . $script_version . "\"></script>" );
  OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/loader.js?ver=" . $script_version . "\"></script>" );

  OLabUtilities::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/css/olab/olab.css?ver=" . $script_version . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );

  //OLabUtilities::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/javascript/olab/bootstrap-4.3.1-dist/css/bootstrap.min.css?ver=" . $script_version . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );
  //OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/bootstrap-4.3.1-dist/js/bootstrap.min.js?ver=" . $script_version . "\"></script>" );

  // autoload any kernel-level javascript extensions
  $jsPath = HostSystemApi::getFileRoot() . "/javascript/olab/autoload";
  $asJsFiles = getKernelScripts( $jsPath );
  foreach ( $asJsFiles as $sJsFile )
  {
    OLabUtilities::addToHead( "<script class=\"olab\" type=\"text/javascript\" src=\"" .
    HostSystemApi::getRelativePath() . "/javascript/olab/autoload" . $sJsFile . "?ver=" . $script_version . "\"></script>" );
  }
  HostSystemApi::UpdateBreadCrumb( HostSystemApi::getRootUrl()  . "/" . $MODULE, "Play " );
}
?>

<script>
  var H5PIntegration = null;
</script>

<!-- DIVs for Olab content binding.  DO NOT EDIT. -->
<div class="container">

  <div class="row">
    <div class="span12" id="olabHeaderContent" align="center"></div>
  </div>

  <div class="row">

<!--    <div class="span1" id="olabLeftContainer">
      <div id="olabLeftContent">left</div>
    </div>-->

    <div class="span12">
      <div id="olabNodeContent" align="center">
        <div v-show="loadingList">
          <center>
            <img id="loading" src="<?php echo HostSystemApi::getRelativePath(); ?>/images/loading.gif" />
            <p></p>
          </center>
        </div>
      </div>
    </div>

<!--
    <div class="span1" id="olabRightContainer">
      <div id="olabRightContent">right</div>
    </div>-->

  </div>

  <div class="row">
    <div class="span12" id="olabAnnotationContent" class=""></div>
    <div class="span12" id="olabFooterContent" align="center"></div>
  </div>

</div>



