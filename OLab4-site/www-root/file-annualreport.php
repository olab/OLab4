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
 * Outputs the requested annual report file id to the users web browser.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <ad29@queensu.ca>
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
} elseif (!$ENTRADA_ACL->amIAllowed('annualreport', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$FILE			= "";

	if((isset($_GET["file"])) && (trim($_GET["file"]))) {
		$FILE = clean_input($_GET["file"], "file");
	}
	
	if(file_exists(ANNUALREPORT_STORAGE."/".$FILE)) {
		$filesize = filesize(ANNUALREPORT_STORAGE."/".$FILE);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/pdf");
		header("Content-Disposition: attachment; filename=\"".$FILE."\"");
		header("Content-Length: ".$filesize);
		header("Content-Transfer-Encoding: binary");
					
		echo file_get_contents(ANNUALREPORT_STORAGE."/".$FILE, FILE_BINARY);
		
		$extension = explode(".", $FILE);
		$extension = $extension["1"];
		
		// delete the files (PDF and HTML or TXT).
		unlink(ANNUALREPORT_STORAGE."/".$FILE);
		if($extension == "pdf") {
			$FILE = str_replace(".pdf", ".html", $FILE);
			unlink(ANNUALREPORT_STORAGE."/".$FILE);
		}
		exit;
		
	} else {
		$TITLE	= "Not Found";
		$BODY	= display_notice(array("The file that you are trying to download (<strong>".html_encode($result["file_name"])."</strong>) does not exist in the filesystem.<br /><br />Please contact a system administrator or a teacher listed on the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

		$template_html = fetch_template("global/external");
		if ($template_html) {
			echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
		}
		exit;
	}
}