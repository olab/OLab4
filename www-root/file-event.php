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
	$EFILE_ID			= 0;

	if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$EFILE_ID = (int) trim($_GET["id"]);
	}

	if($EFILE_ID) {
		$query	= "	SELECT a.*, b.`course_id`, c.`organisation_id`
					FROM `event_files` AS a
					LEFT JOIN `events` AS b
					ON a.`event_id` = b.`event_id`
					LEFT JOIN `courses` AS c
					ON b.`course_id` = c.`course_id`
					WHERE a.`efile_id` = ".$db->qstr($EFILE_ID);
		$result	= ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if($result) {
			if ($ENTRADA_ACL->amIAllowed(new EventContentResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "read")) {
				$accesses		= $result["accesses"];
				$access_method	= (int) $result["access_method"];
				$filename		= $result["file_name"];
				$filetype		= $result["file_type"];
				$filesize		= $result["file_size"];

				$is_administrator = false;

				if ($ENTRADA_ACL->amIAllowed(new EventContentResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
					$is_administrator	= true;
				}

				if ((!$is_administrator) && ((int) $result["release_date"]) && ($result["release_date"] > time())) {
					$TITLE	= "Not Available: ".html_encode($result["file_name"]);
					$BODY	= display_notice(array("The file that you are trying to download (<strong>".html_encode($result["file_name"])."</strong>) is not available for downloading until <strong>".date(DEFAULT_DATE_FORMAT, $result["release_date"])."</strong>.<br /><br />For further information or to contact a teacher, please see the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

					$template_html = fetch_template("global/external");
					if ($template_html) {
						echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
					}
					exit;
				} else {
					if((!$is_administrator) && ((int) $result["release_until"]) && ($result["release_until"] < time())) {
						$TITLE	= "Not Available: ".html_encode($result["file_name"]);
						$BODY	= display_notice(array("The file that you are trying to download (<strong>".html_encode($result["file_name"])."</strong>) was only available for download until <strong>".date(DEFAULT_DATE_FORMAT, $result["release_until"])."</strong>.<br /><br />For further information or to contact a teacher, please see the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

						$template_html = fetch_template("global/external");
						if ($template_html) {
							echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
						}
						exit;
					} else {
						if((@file_exists(FILE_STORAGE_PATH."/".$EFILE_ID)) && (@is_readable(FILE_STORAGE_PATH."/".$EFILE_ID))) {
							/**
							* Determine method that the file should be accessed (downloaded or viewed)
							* and send the proper headers to the client.
							*/
							switch($access_method) {
								case 1 :
									header("Pragma: public");
									header("Expires: 0");
									header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
									header("Content-Type: ".$result["file_type"]."");
									header("Content-Disposition: inline; filename=\"".$result["file_name"]."\"");
									header("Content-Length: ".@filesize(FILE_STORAGE_PATH."/".$EFILE_ID));
									header("Content-Transfer-Encoding: binary");
								break;
								case 0 :
								default :
									header("Pragma: public");
									header("Expires: 0");
									header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
									header("Content-Type: application/force-download");
									header("Content-Type: application/octet-stream");
									header("Content-Type: ".$result["file_type"]."");
									header("Content-Disposition: attachment; filename=\"".$result["file_name"]."\"");
									header("Content-Length: ".@filesize(FILE_STORAGE_PATH."/".$EFILE_ID));
									header("Content-Transfer-Encoding: binary");
								break;
							}

							echo file_get_contents(FILE_STORAGE_PATH."/".$EFILE_ID, FILE_BINARY);

							$db->Execute("UPDATE `event_files` SET `accesses` = '".($accesses + 1)."' WHERE `efile_id` = ".$db->qstr($EFILE_ID));

							add_statistic("events", "file_download", "file_id", $EFILE_ID);
							exit;
						} else {
							$TITLE	= "Not Found: ".html_encode($result["file_name"]);
							$BODY	= display_notice(array("The file that you are trying to download (<strong>".html_encode($result["file_name"])."</strong>) does not exist in the filesystem.<br /><br />Please contact a system administrator or a teacher listed on the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

							$template_html = fetch_template("global/external");
							if ($template_html) {
								echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
							}
							exit;
						}
					}
				}
			} else {				
				$TITLE	= "Not Authorized";
				$BODY	= display_notice(array("The file that you are trying to access is only accessible by authorized users."));

				$template_html = fetch_template("global/external");
				if ($template_html) {
					echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
				}
				exit;
			}
		} else {
			$TITLE	= "Not Found";
			$BODY	= display_notice(array("The file you are trying to download does not exist in our system. This file may have been removed by a teacher or system administrator or the file identifier may have been mistyped in the URL."));

			$template_html = fetch_template("global/external");
			if ($template_html) {
				echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
			}
			exit;
		}
	} else {
		header("Location: ".ENTRADA_URL);
		exit;
	}
}