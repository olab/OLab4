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
 * Records user responses and displays responses from Entrada's Manage Polls
 * module.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: serve-polls.php 1171 2010-05-01 14:39:27Z ad29 $
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

if((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
	
	$pentry_id = clean_input($_GET["entry_id"], "int");
	
	$pentry = Models_Eportfolio_Entry::fetchRow($pentry_id);
	
	$pfartifact = $pentry->getPfartifact();
	
	$pfolder = $pfartifact->getFolder();
	
	$portfolio = $pfolder->getPortfolio();
	$file_realpath = EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID()."/user-".$pentry->getProxyID()."/".$pentry->getID();

	if (file_exists($file_realpath)) {
		add_statistic("eportfolio", "download_file", "pentry_id", $pentry_id, $_SESSION["details"]["id"]);
		$finfo = $finfo = new finfo(FILEINFO_MIME);
		
		$edata = $pentry->getEdataDecoded();
		$filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '', str_replace(" ", "-", trim($edata["filename"])));
		
		header('Content-Description: File Transfer');
		header('Content-Type: '.$finfo->file($file_realpath));
		header('Content-Disposition: attachment; filename='.strtolower($filename));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_realpath));

		ob_clear_open_buffers();

		readfile($file_realpath);
		
		exit;
	} else {
		application_log("error", "");
	}
	
} else {
	application_log("notice", "Unauthorised access to the serve-eportfolio-entry.php file.");
}