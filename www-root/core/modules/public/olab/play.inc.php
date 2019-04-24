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

    OLabUtilities::addToHead( "<script>var WEBSITE_ROOT = \"" . HostSystemApi::getRootUrl() . "\";</script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/vue/vue.js\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/vee-validate/vee-validate.min.js\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/axios/axios.min.js\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.utilities.js\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.play.components.js\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.node.player.js\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.play.main.js\"></script>" );
    OLabUtilities::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/css/olab/olab.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );

    // autoload any kernel-level javascript extensions
    $jsPath = HostSystemApi::getFileRoot() . "/javascript/olab/autoload";
    $asJsFiles = getKernelScripts( $jsPath );
    foreach ( $asJsFiles as $sJsFile )
    {
        OLabUtilities::addToHead( "<script class=\"olab\" type=\"text/javascript\" src=\"" .
        HostSystemApi::getRelativePath() . "/javascript/olab/autoload" . $sJsFile . "\"></script>" );
    }
    HostSystemApi::UpdateBreadCrumb( HostSystemApi::getRootUrl()  . "/" . $MODULE, "Play " );
}
?>

<!-- DIVs for Olab content binding.  DO NOT EDIT. -->
<div id="olabHeaderContent" align="center"></div>
<div id="olabNodeContent" align="center">
    <div v-show="loadingList">
        <center>
            <img src="<?php echo HostSystemApi::getRelativePath(); ?>/images/loading.gif" /><p/>
        </center>
    </div>
</div>
<div id="olabAnnotationContent" class=""></div>
<div id="olabFooterContent" align="center"></div>



