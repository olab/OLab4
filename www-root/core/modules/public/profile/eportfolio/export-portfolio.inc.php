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
 * ePortfolio public index
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @author Developer: Josh Dillon <josh.dillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eportfolio", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
		
	$zip_archive = new ZipArchive();

	if (!is_dir(EPORTFOLIO_STORAGE_PATH."/zip_archives")) {
		mkdir(EPORTFOLIO_STORAGE_PATH."/zip_archives");
	}

	$zip_archive->open(EPORTFOLIO_STORAGE_PATH."/eportfolio-".$ENTRADA_USER->getID().".zip", ZipArchive::CREATE);

	$zip_archive->addEmptyDir("css");
	$zip_archive->addFromString("css/bootstrap.min.css", file_get_contents($ENTRADA_TEMPLATE->absolute()."/css/bootstrap.min.css"));
	$eportfolio = Models_Eportfolio::fetchRowByGroupID($ENTRADA_USER->getCohort());

	$output[] = "<!DOCTYPE html>\n";
	$output[] = "<html>\n";
	$output[] = "<head>\n";
	$output[] = "<title>".$ENTRADA_USER->getFullname(true)." Portfolio</title>\n";
	$output[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/bootstrap.min.css\" />\n";
	$output[] = "<style type=\"text/css\">
					body {font-family:Helvetica, Arial, sans-serif;margin:1cm;}
					.well .well {background:white!important;}
				 </style>\n";
	$output[] = "</head>\n";
	$output[] = "<body>\n";
	$output[] = "<div class=\"row-fluid space-below\">\n";
	$output[] = "<div class=\"span6\">";
	if ($ENTRADA_USER->getFullname()) {
		$output[] = "<strong>Name:</strong> " . $ENTRADA_USER->getFullname(false)."\n<br />";
	}
	if ($ENTRADA_USER->getEmail()) {
		$output[] = "<strong>Email:</strong> " . $ENTRADA_USER->getEmail()."\n<br />";
	}
	if ($ENTRADA_USER->getTelephone()) {
		$output[] = "<strong>Telephone:</strong> " . $ENTRADA_USER->getTelephone()."\n<br />";
	}
	if ($ENTRADA_USER->getFax()) { 
		$output[] = "<strong>Fax:</strong> " . $ENTRADA_USER->getFax()."\n<br />";
	}
	if ($ENTRADA_USER->getAddress()) { 
		$output[] = "<strong>Address:</strong> " . $ENTRADA_USER->getAddress()."\n<br />";
	}
	if ($ENTRADA_USER->getCity()) {
		$output[] = "<strong>City:</strong> " . $ENTRADA_USER->getCity()."\n<br />";
	}
	$output[] = "</div>\n";
	$output[] = "<div class=\"span6 text-right\">\n";
	$output[] = $eportfolio->getPortfolioName();
	$output[] = "</div>\n";
	$output[] = "</div>\n";

	if (isset($eportfolio) && !empty($eportfolio)) {
		$user_entries = false;
		$folders = $eportfolio->getFolders();
		if (isset($folders) && $folders) {
			foreach ($folders as $folder) {

				$folder_name = preg_replace('/[^a-zA-Z0-9-_\.]/', '', str_replace(" ", "-", trim($folder->getTitle())));
				$artifacts = $folder->getArtifacts();
				if (isset($artifacts) && $artifacts) {
					$folder_title_added = false;
					foreach ($artifacts as $artifact) {

						$artifact_name = preg_replace('/[^a-zA-Z0-9-_\.]/', '', str_replace(" ", "-", trim($artifact->getTitle())));
						$entries = $artifact->getEntries($ENTRADA_USER->getID());

						if (isset($entries) && $entries != false) {
							$user_entries = true;
							if ($folder_title_added === false) {
								$output[] = "<h1>".$folder->getTitle()."</h1>\n";
								$folder_title_added = true;
							}

							$title = $artifact->getTitle();
							$description = $artifact->getDescription();
							$output[] = "<div class=\"well\">\n";
							$output[] = "<h3>" . $title . "</h3>\n";
							if (isset($description) && !empty($description)) {
								$output[] = "<div style=\"font-style:italic\">" . $description. "</div>\n";
							}

							foreach ($entries as $entry) {

								$entry_edata = $entry->getEdataDecoded();

								$output[] = "<div class=\"well\">\n";
								$output[] = "<p>Submitted: <span class=\"muted\">" . date("M j, Y", $entry->getSubmittedDate()) . "</span></p>\n";
								if (isset($entry_edata["title"]) && !empty($entry_edata["title"])) {
									$output[] = "<p><strong>".$entry_edata["title"]."</strong></p>\n";
								}
								if (isset($entry_edata["description"]) && !empty($entry_edata["description"])) {
									$output[] = $entry_edata["description"]."\n";
								}
								if (isset($entry_edata["filename"])) {
									$file_realpath = EPORTFOLIO_STORAGE_PATH."/portfolio-".$eportfolio->getID()."/folder-".$folder->getID()."/artifact-".$artifact->getID()."/user-".$entry->getProxyID()."/".$entry->getID();
									if (file_exists($file_realpath)) {
										$zip_archive->addEmptyDir($folder_name."/".$artifact_name);
										$file_contents = file_get_contents($file_realpath);
										$zip_archive->addFromString($folder_name."/".$artifact_name."/".$entry->getID()."-".$entry_edata["filename"], $file_contents);
									}
									$output[] = "<p><a href=\"".$folder_name."/".$artifact_name."/".$entry->getID()."-".$entry_edata["filename"]."\">".$entry_edata["filename"]."</a></p>\n";
								}
								$output[] = "</div>\n";

							}
							$output[] = "</div>\n";
						}
					}
				}
			}
		}
	}

	$output[] = "</body>\n";
	$output[] = "</html>\n";

	$zip_archive->addFromString("portfolio.html", str_replace("<p>&nbsp;</p>", "", implode("", $output)));
	$zip_archive->close();

	$file_realpath = EPORTFOLIO_STORAGE_PATH."/eportfolio-".$ENTRADA_USER->getID().".zip";
	if ($user_entries) {
		if (file_exists($file_realpath)) {

			ob_clear_open_buffers();

			$finfo = $finfo = new finfo(FILEINFO_MIME);
			header('Content-Description: File Transfer');
			header('Content-Type: '.$finfo->file($file_realpath));
			header('Content-Disposition: attachment; filename='.strtolower("my-portfolio.zip"));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file_realpath));
			readfile($file_realpath);
			unlink($file_realpath);

			exit;

		}
	} else {
		echo "<h1>Export My Eportfolio</h1>";
		echo display_notice("Your eportfolio artifacts do not have any entries. Please return to <a href=\"".ENTRADA_URL."/profile/eportfolio\">your eportfolio</a> and add entries to the appropriate artifacts before exporting it.");
		echo "<div class=\"row-fluid\"><a href=\"".ENTRADA_URL."/profile/eportfolio\" class=\"btn btn-primary\">Back</a></div>";
	}
	
}