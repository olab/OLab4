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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_OLAB"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    HostSystemApi::addToHead( "<script>var WEBSITE_ROOT = \"" . HostSystemApi::getRootUrl() . "\";</script>" );
    HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/vue/vue.js\"></script>" );
    HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/vee-validate/vee-validate.min.js\"></script>" );
    HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/axios/axios.min.js\"></script>" );
    HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.utilities.js\"></script>" );
    HostSystemApi::addToHead( "<script type=\"text/javascript\" src=\"" . HostSystemApi::getRelativePath() . "/javascript/olab/olab.import.main.js\"></script>" );

    HostSystemApi::addToHead( "<link href=\"". HostSystemApi::getRootUrl() ."/css/olab/olab.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />" );

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
<form action="/" method="post" enctype="multipart/form-data" id="form-id">

    <!-- The file to upload -->
    <p>
        <input id="file-id" type="file" name="our-file" />

        <!--
    Also by default, we disable the upload button.
    If Ajax uploads are supported we'll enable it.
  -->
        <input type="button" value="Upload" id="upload-button-id" disabled="disabled" />
    </p>

    <!-- Placeholders for messages set by event handlers -->
    <p id="upload-status"></p>
    <p id="progress"></p>
    <pre id="result"></pre>

</form>
