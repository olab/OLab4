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
 * Primary controller file for the Events module.
 * /admin/notices
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	echo "Header";
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed("notice", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_NOTICES", true);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/notices", "title" => $MODULES[strtolower($MODULE)]["title"]);

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);
	
		$ORGANISATION_ID = $ENTRADA_USER->getActiveOrganisation();
		$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["organisation_id"] = $ORGANISATION_ID;

		$NOTICE_TARGETS = array();
		$NOTICE_TARGETS["all"] = "Visible to all students, faculty &amp; staff";
		$NOTICE_TARGETS["students"] = "Visible to all students";
		
		$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
		if (isset($active_cohorts) && !empty($active_cohorts)) {
			foreach ($active_cohorts as $cohort) {
				$NOTICE_TARGETS["cohort:".$cohort["group_id"]] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Visible to ".html_encode($cohort["group_name"]);
			}
		}

		$NOTICE_TARGETS["faculty"] = "Visible to all faculty";
		$NOTICE_TARGETS["staff"] = "Visible to all staff";
	
		if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
			$NOTICE_ID = $tmp_input;
		} else {
			$NOTICE_ID = 0;
		}

		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}

		if ((is_array($NOTICE_TARGETS)) && (count($NOTICE_TARGETS))) {
			$query = "	SELECT a.* FROM `".AUTH_DATABASE."`.`organisations` AS a
						JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON a.`organisation_id` = b.`organisation_id`
						WHERE b.`user_id` = ".$db->qstr($ENTRADA_USER->getID())."
						GROUP BY a.`organisation_id`
						ORDER BY `organisation_title` ASC";
			$organisations = $db->GetAll($query);

			$sidebar_html = '';
			foreach ($organisations as $organisation) {
				if ($ENTRADA_ACL->amIAllowed(new NoticeResource($organisation["organisation_id"]), 'create')) {
					$sidebar_html .= "<div onclick=\"rssOpen('".$organisation["organisation_id"]."_notice_list');\" class=\"rsslist".($organisation["organisation_id"] == $_SESSION["details"]["organisation_id"] ? " expanded\"" : "")."\">".html_encode($organisation["organisation_title"])."</div>";
					$sidebar_html .= "<ul class=\"menu\" id=\"".$organisation["organisation_id"]."_notice_list');\"".($organisation["organisation_id"] == $_SESSION["details"]["organisation_id"] ? "" : " style=\"display: none\"").">";

					foreach ($NOTICE_TARGETS as $key => $target_name) {
						$sidebar_html .= "<li class=\"rss\"><a href=\"".ENTRADA_URL."/notices/".$key."/".$organisation["organisation_id"]."\">".str_replace("&nbsp;", "", $target_name)."</a></li>\n";
					}

					$sidebar_html .= "</ul>";
				}
			}

			if ($sidebar_html != "") {
				$sidebar_html .= "
				<script type=\"text/javascript\">
				function rssOpen(id) {
					id = $(id);

					if (id.visible()) {
						new Effect.BlindUp(id,{duration: 0.4});
					} else {
						new Effect.BlindDown(id,{duration: 0.4});
					}

					id.previousSibling.toggleClassName('expanded');
				}
				</script>";

				new_sidebar_item("RSS Notice Feeds", $sidebar_html, "rss-notice-feeds", "open");
			}
		}

		/**
		 * Check if preferences need to be updated on the server at this point.
		 */
		preferences_update($MODULE, $PREFERENCES);
	}
}
