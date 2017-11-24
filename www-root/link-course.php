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
 * Redirects the user to the requested course link id.
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

require_once("Entrada/xoft/xoft.class.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
	exit;
} else {
	$LINK_ID			= 0;

	if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$LINK_ID = (int) trim($_GET["id"]);
	}

	if($LINK_ID) {
		$query	= "	SELECT cl.*, c.`organisation_id` 
					FROM `course_links` cl
					JOIN `courses`c
					ON cl.`course_id` = c.`course_id`
					WHERE cl.`id`=".$db->qstr($LINK_ID);
		$result	= ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if($result) {
			if ($ENTRADA_ACL->amIAllowed(new CourseResource($result['course_id'], $result['organisation_id']), 'read')) {

                $is_administrator = false;

                if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($result["course_id"], $result["organisation_id"]), "update")) {
                    $is_administrator = true;
                }

				$accesses = $result["accesses"];
				if(!$is_administrator && ($result["valid_from"]) && ($result["valid_from"] > time())) {
					$TITLE	= "Not Available";
					$BODY	= display_notice(array("The link that you are trying to access is not available until <strong>".date(DEFAULT_DATE_FORMAT, $result["valid_from"])."</strong>.<br /><br />For further information or to contact the course director, please see the <a href=\"".ENTRADA_URL."/courses?id=".$result["course_id"]."\" style=\"font-weight: bold\">course website</a>."));

					$template_html = fetch_template("global/external");
					if ($template_html) {
						echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
					}
					exit;
				} else {
					if(!$is_administrator && ($result["valid_until"]) && ($result["valid_until"] < time())) {
						$TITLE	= "Not Available";
						$BODY	= display_notice(array("The link that you are trying to access was only available until <strong>".date(DEFAULT_DATE_FORMAT, $result["valid_from"])."</strong>.<br /><br />For further information or to contact the course director, please see the <a href=\"".ENTRADA_URL."/courses?id=".$result["course_id"]."\" style=\"font-weight: bold\">course website</a>."));

						$template_html = fetch_template("global/external");
						if ($template_html) {
							echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
						}
						exit;
					} else {
						$db->Execute("UPDATE `course_links` SET `accesses`='".($accesses + 1)."' WHERE `id`=".$db->qstr($LINK_ID));

						add_statistic("courses", "link_access", "link_id", $LINK_ID);

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
			$BODY	= display_notice(array("The link that you are trying to access cannot be found.<br /><br />Please contact a system administrator or the course director listed on the <a href=\"".ENTRADA_URL."/courses?id=".$result["course_id"]."\" style=\"font-weight: bold\">course website</a>."));

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