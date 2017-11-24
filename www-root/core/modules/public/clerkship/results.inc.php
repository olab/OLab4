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
 * Displays accommodation details to the user based on a particular event_id.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('clerkship', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Clerk Search Results");
	
	?>
	<h1>Student Search Results</h1>
	<?php
	if (((isset($_GET["year"]) && trim($_GET["year"]) != "") || (isset($_POST["year"]) && trim($_POST["year"]) != ""))) {
		if (trim($_POST["year"]) != "") {
			$query_year = trim($_POST["year"]);
		} else {
			$query_year = trim($_GET["year"]);
		}
		
		$query = "	SELECT a.*, a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`, d.`group_name`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON b.`user_id` = a.`id`
					AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
					JOIN `group_members` AS c
					ON a.`id` = c.`proxy_id`
					AND c.`member_active` = 1
					JOIN `groups` AS d
					ON c.`group_id` = d.`group_id`
					AND d.`group_active` = 1
					WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
					AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					AND b.`group` = 'student'
					AND d.`group_id` = ".$db->qstr($query_year)."
					GROUP BY a.`id`
					ORDER BY `fullname` ASC";

		$results	= $db->GetAll($query);
		
		if ($results) {
			$counter	= 0;
			$total	= count($results);
			$split	= (round($total / 2) + 1);

			echo "<div class=\"display-generic\">";
			echo "There are a total of <b>".$total."</b> student".(($total != "1") ? "s" : "")." in the <b>".checkslashes(trim($results[0]["group_name"]))."</b>.";
	        echo "</div>";

			echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
			echo "<tr>\n";
			echo "	<td style=\"vertical-align: top\">\n";
			echo "		<ol start=\"1\">\n";
			foreach ($results as $result) {
				
				$elective_weeks = clerkship_get_elective_weeks($result["proxy_id"]);
				$remaining_weeks = (int)$CLERKSHIP_REQUIRED_WEEKS - (int)$elective_weeks["approved"];
				
				switch (htmlentities($_POST["qualifier"])) {
					case "*":
					default:
						$show 			= true;
						$weeksOutput 	= "";
						$noResults		= "No Results";
						break;
					case "deficient":
						if ($remaining_weeks > 0) {
							$show 			= true;
							$weeksOutput 	= " <span class=\"content-small\">(".$remaining_weeks." weeks remaining)</span>";									
						} else {
							$show 			= false;
						}
						$noResults		= "There are no students in the class of <b>".checkslashes(trim($query_year))."</b> that do not have 14 weeks of electives approved in the system.";
						break;
					case "attained":
						if ($remaining_weeks <= 0) {
							$show 			= true;
							$weeksOutput 	= "";
						} else {
							$show 			= false;
						}
						$noResults		= "There are no students in the class of <b>".checkslashes(trim($query_year))."</b> that have 14 weeks of electives approved in the system.";
						break;
				}
				
				if ($show) {
					$counter++;
					if ($counter == $split) {
						echo "		</ol>\n";
						echo "	</td>\n";
						echo "	<td style=\"vertical-align: top\">\n";
						echo "		<ol start=\"".$split."\">\n";
					}
					echo "	<li><a href=\"".ENTRADA_URL."/clerkship/clerk?ids=".$result["proxy_id"]."\">".$result["fullname"]."</a>".$weeksOutput."</li>\n";
				}
			}
			
			if ($counter == 0) {
				echo "	<li>".$noResults."</li>\n";
			}
			echo "		</ol>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
		} else {
			$ERROR++;
			$ERRORSTR[] = "Unable to find students in the database with a graduating year of <b>".trim($query_year)."</b>. It's possible that these students are not yet added to this system, so please check the User Management module.";
	
			echo "<br />";
			echo display_error($ERRORSTR);
		}
	} elseif (trim($_GET["name"]) != "" || trim($_POST["name"]) != "") {
		if (trim($_POST["name"]) != "") {
			$query_name = trim($_POST["name"]);
		} else {
			$query_name = trim($_GET["name"]);
		}
        $query	= "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`grad_year` AS `gradyear`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON b.`user_id` = a.`id`
                    WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ".($db->qstr("%".trim($query_name)."%"))."
                    AND `group` = 'student'
                    ORDER BY a.`lastname`, a.`firstname` ASC";
		$results	= $db->GetAll($query);
		if ($results) {
			$counter	= 0;
			$total	= count($results);
			$split	= (round($total / 2) + 1);

			echo "<div class=\"display-generic\">";
			echo "There are a total of <b>".$total."</b> student".(($total != "1") ? "s" : "")." that match the search term of <b>".checkslashes(trim($query_name), "display")."</b>.";
			echo "</div>";
	
			echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
			echo "<tr>\n";
			echo "	<td style=\"vertical-align: top\">\n";
			echo "		<ol start=\"1\">\n";
			foreach ($results as $result) {
				$counter++;
				if ($counter == $split) {
					echo "		</ol>\n";
					echo "	</td>\n";
					echo "	<td style=\"vertical-align: top\">\n";
					echo "		<ol start=\"".$split."\">\n";
				}
				echo "	<li><a href=\"".ENTRADA_URL."/clerkship/clerk?ids=".$result["proxy_id"]."\">".$result["fullname"]."</a> <span class=\"content-small\">(Class of ".$result["gradyear"].")</span></li>\n";
			}
			echo "		</ol>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
		} else {
			$ERROR++;
			$ERRORSTR[] = "Unable to find any students in the database matching <b>".checkslashes(trim($query_name), "display")."</b>. It's possible that the student you're looking for is not yet added to this system, so please check the User Management module.";
	
			echo "<br />";
			echo display_error($ERRORSTR);
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "You must search either by graduating year or by students name at this time, please try again.";
		
		echo "<br />";
		echo display_error($ERRORSTR);
	}
}
?>