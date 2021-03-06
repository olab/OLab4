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

$script_version = OLabUtilities::get_script_version();

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
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.utilities.js?ver=" . $script_version . "\"></script>" );
    OLabUtilities::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.import.main.js?ver=" . $script_version . "\"></script>" );

    OLabUtilities::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/css/olab/olab.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );

    HostSystemApi::UpdateBreadCrumb( HostSystemApi::getRootUrl()  . "/" . $MODULE, "Import " );
}
?>

<!--
  By default, we assume Ajax uploads are not supported.
  Later we'll detect support and change this message if found.
-->
<p id="support-notice">
    Your browser does not support Ajax uploads :-(
    <br />The form will be submitted as normal.
</p>

<!-- The form starts -->
<form action="/" method="post" enctype="multipart/form-data" id="uploadForm">

    <!-- The file to upload -->
    <p>
        <input id="file-id" type="file" name="our-file" />
        <input type="button" value="Upload" id="upload-button-id" disabled="disabled" />
    </p>

    <div id="spinner" style="display:none">
        <center>
            <img src="<?php echo HostSystemApi::getRelativePath(); ?>/images/loading.gif" />
        </center>
    </div>

    <!-- Placeholders for messages set by event handlers -->
    <p id="uploadStatus"></p>
    <p id="progress"></p>
    <pre id="result"></pre>
    <input id="copyToClipboard" type="button" value="Copy to Clipboard" style="display:none;"/>
    <textarea id="diagnosticData" style="display:none"></textarea>
</form>
