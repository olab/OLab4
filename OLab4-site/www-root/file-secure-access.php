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
 * Outputs the requested event file id to the users web browser.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/core",
    dirname(__FILE__) . "/core/includes",
    dirname(__FILE__) . "/core/library",
    dirname(__FILE__) . "/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
	exit;
} else {
	if((isset($_SESSION['app-'.AUTH_APP_ID]['secure']['active_resource_id'])) && ((int) trim($_SESSION['app-'.AUTH_APP_ID]['secure']['active_resource_id']))) {
		$RESOURCE_ID = (int) trim($_SESSION['app-'.AUTH_APP_ID]['secure']['active_resource_id']);
	}
    if((isset($_SESSION['app-'.AUTH_APP_ID]['secure']['active_resource_type'])) && (trim($_SESSION['app-'.AUTH_APP_ID]['secure']['active_resource_type']))) {
        $RESOURCE_TYPE = trim($_SESSION['app-'.AUTH_APP_ID]['secure']['active_resource_type']);
    }
	if(isset($RESOURCE_ID) && $RESOURCE_ID != "" && isset($RESOURCE_TYPE) && $RESOURCE_TYPE != "") {
        
        $access_file = Models_Secure_AccessFiles::fetchRowByResourceTypeResourceID($RESOURCE_TYPE, $RESOURCE_ID);

		if($access_file) {
            
			/*
             * @todo Add security checks
             */
            if((@file_exists(SECURE_ACCESS_STORAGE_PATH."/".$access_file->getID())) && (@is_readable(SECURE_ACCESS_STORAGE_PATH."/".$access_file->getID()))) {

                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: ".$access_file->getFileType()."");
                header("Content-Disposition: attachment; filename=\"".$access_file->getFileName()."\"");
                header("Content-Length: ".@filesize(SECURE_ACCESS_STORAGE_PATH."/".$access_file->getID()));
                header("Content-Transfer-Encoding: binary");
                
                readfile(SECURE_ACCESS_STORAGE_PATH."/".$access_file->getID());
                add_statistic("secureaccessfile", "file_download", "file_id", $access_file->getID());
                
                exit;
            } else {
                $TITLE	= "Not Found: ".html_encode($access_file->getFileName());
                $BODY	= display_notice(array("The file that you are trying to download (<strong>".html_encode($access_file->getFileName())."</strong>) does not exist in the filesystem.<br /><br />Please contact a system administrator.</a>."));

                $template_html = fetch_template("layouts/global/external");
                if ($template_html) {
                    echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
                }
                exit;
            }
			
		} else {
			$TITLE	= "Not Found";
			$BODY	= display_notice(array("The file you are trying to download does not exist in our system. This file may have been removed by a teacher or system administrator or the file identifier may have been mistyped in the URL."));

			$template_html = fetch_template("layouts/global/external");
			if ($template_html) {
				echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
			}
			exit;
		}
	} else {
        $TITLE	= "Not Found";
        $BODY	= display_notice(array("The file you are trying to download does not exist in our system. This file may have been removed by a teacher or system administrator or the file identifier may have been mistyped in the URL."));

        $template_html = fetch_template("layouts/global/external");
        if ($template_html) {
            echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
        }
        exit;
	}
}