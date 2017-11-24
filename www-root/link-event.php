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
 * Redirects the user to the requested learning event link id.
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
	$ELINK_ID			= 0;

	if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$ELINK_ID = (int) trim($_GET["id"]);
	}

	if($ELINK_ID) {
		$query	= "	SELECT el.*, c.`course_id`, c.`organisation_id` 
					FROM `event_links` el
					JOIN `events` e
					ON el.`event_id` = e.`event_id`
					JOIN `courses` c
					ON e.`course_id` = c.`course_id`
					WHERE `elink_id` = ".$db->qstr($ELINK_ID);
		$result	= ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query) : $db->GetRow($query));		
		if($result) {			
			if ($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result['course_id'], $result['organisation_id']), 'read')) {
                $is_administrator = false;

                if ($ENTRADA_ACL->amIAllowed(new EventContentResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
                    $is_administrator	= true;
                }
				$accesses = $result["accesses"];
				if(!$is_administrator && ($result["release_date"]) && ($result["release_date"] > time())) {
					$TITLE	= "Not Available";
					$BODY	= display_notice(array("The link that you are trying to access is not available until <strong>".date(DEFAULT_DATE_FORMAT, $result["release_date"])."</strong>.<br /><br />For further information or to contact a teacher, please see the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

					$template_html = fetch_template("global/external");
					if ($template_html) {
						echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
					}
					exit;
				} else {
					if(!$is_administrator && ($result["release_until"]) && ($result["release_until"] < time())) {
						$TITLE	= "Not Available";
						$BODY	= display_notice(array("The link that you are trying to access was only available until <strong>".date(DEFAULT_DATE_FORMAT, $result["release_until"])."</strong>.<br /><br />For further information or to contact a teacher, please see the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

						$template_html = fetch_template("global/external");
						if ($template_html) {
							echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
						}
						exit;
					} else {
						$db->Execute("UPDATE `event_links` SET `accesses` = '".($accesses + 1)."' WHERE `elink_id` = ".$db->qstr($ELINK_ID));

						add_statistic("events", "link_access", "link_id", $ELINK_ID);

						if(($result["proxify"] == "1") && (check_proxy("default")) && (isset($PROXY_URLS["default"]["active"])) && ($PROXY_URLS["default"]["active"] != "")) {
							header("Location: ".$PROXY_URLS["default"]["active"].$result["link"]);
						} else {
							header("Location: ".$result["link"]);
						}
						exit;
					}
				}
			} else {				
				$TITLE	= "Not Authorized";
				$BODY	= display_notice(array("The link that you are trying to access is only accessible by authorized users."));

				$template_html = fetch_template("global/external");
				if ($template_html) {
					echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
				}
				exit;
			}
		} else {
			$TITLE	= "Not Found";
			$BODY	= display_notice(array("The link that you are trying to access cannot be found.<br /><br />Please contact a system administrator or a teacher listed on the <a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">event page</a>."));

			$template_html = fetch_template("global/external");
			if ($template_html) {
				echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
			}
			exit;
		}
	} else {
		$TITLE	= "Not Found";
		$BODY	= display_notice(array("The link that you are trying to access does not exist in our system. This link may have been removed by a teacher or system administrator or the link identifier may have been mistyped in the URL."));

		$template_html = fetch_template("global/external");
		if ($template_html) {
			echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
		}
		exit;
	}
}