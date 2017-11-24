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
} elseif (!$ENTRADA_ACL->amIAllowed("clerkshipschedules", "read")) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship/electives?".replace_query(array("section" => "view")), "title" => "Viewing Electives");
	if (isset($_GET["ids"]) && $PROXY_ID	= clean_input($_GET["ids"], "int")) {
		
		$student_name = get_account_data("firstlast", $PROXY_ID);
		
		$query = "	SELECT a.*, c.`region_name`, d.`aschedule_id`, d.`apartment_id`, e.`rotation_title`
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
					ON c.`region_id` = a.`region_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS d
					ON d.`event_id` = a.`event_id`
					AND d.`proxy_id` = ".$db->qstr($PROXY_ID)."
					AND d.`aschedule_status` = 'published'
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS e
					ON e.`rotation_id` = a.`rotation_id`
					WHERE b.`econtact_type` = 'student'
					AND b.`etype_id` = ".$db->qstr($PROXY_ID)."
					ORDER BY a.`event_start` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			?>
			<h1><?php echo $student_name.(substr($student_name, -1) != "s" ? "'s" : "'");?> Clerkship Schedule</h1>
			<table class="tableList" cellspacing="0" summary="List of Clerkship Rotations">
				<colgroup>
					<col class="modified" />
					<col class="type" />
					<col class="title" />
					<col class="region" />
					<col class="date-smallest" />
					<col class="date-smallest" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="type"><?php echo $translate->_("Event Type"); ?></td>
						<td class="title">Rotation Name</td>
						<td class="region">Region</td>
						<td class="date-smallest">Start Date</td>
						<td class="date-smallest">Finish Date</td>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($results as $result) {
					if ((time() >= $result["event_start"]) && (time() <= $result["event_finish"])) {
						$bgcolour = "#E7ECF4";
						$is_here = true;
					} else {
						$bgcolour = "#FFFFFF";
						$is_here = false;
					}

					if ((int) $result["aschedule_id"]) {
						$apartment_available = true;
					} else {
						$apartment_available = false;
					}

					if (!isset($result["region_name"]) || $result["region_name"] == "") {
						$result_region = clerkship_get_elective_location($result["event_id"]);
						$result["region_name"] = $result_region["region_name"];
						$result["city"] = $result_region["city"];
					} else {
						$result["city"] = "";
					}

					$cssclass = "";
					$skip = false;

					if ($result["event_type"] == "elective") {
						switch ($result["event_status"]) {
							case "approval":
								$elective_word = "Pending";
								$cssclass = " class=\"in_draft\"";
								$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
								$skip = false;
							break;
							case "published":
								$elective_word = "Approved";
								$cssclass = " class=\"published\"";
								$click_url = ENTRADA_URL."/clerkship/electives?section=view&id=".$result["event_id"];
								$skip = false;
							break;
							case "trash":
								$elective_word = "Rejected";
								$cssclass = " class=\"rejected\"";
								$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
								$skip = true;
							break;
							default:
								$elective_word = "";
								$cssclass = "";
							break;
						}

						$elective = true;
					} else {
						$elective = false;
						$skip = false;
					}

					if (!$skip) {
						echo "<tr".(($is_here) && $cssclass != " class=\"in_draft\"" ? " class=\"current\"" : $cssclass).">\n";
						echo "	<td class=\"modified\"><img src=\"".ENTRADA_URL."/images/".(($apartment_available) ? "housing-icon-small.gif" : "pixel.gif")."\" width=\"16\" height=\"16\" alt=\"\" title=\"\" style=\"border: 0px\" /></td>\n";
						echo "	<td class=\"type\">".(($elective) ? "Elective".(($elective_word != "") ? " (".$elective_word.")" : "") : "Core Rotation")."</td>\n";
						echo "	<td class=\"title\">".html_encode($result["rotation_title"])."</td>\n";
						echo "	<td class=\"region\">".html_encode((($result["city"] == "") ? limit_chars(($result["region_name"]), 30) : $result["city"]))."</td>\n";
						echo "	<td class=\"date-smallest\">".date("D M d/y", $result["event_start"])."</td>\n";
						echo "	<td class=\"date-smallest\">".date("D M d/y", $result["event_finish"])."</td>\n";
						echo "</tr>\n";
					}
				}
				?>
				</tbody>
			</table>
			<?php
		} else {
			$NOTICE++;
			$NOTICESTR[] = $student_name . " has no scheduled clerkship rotations / electives in the system at this time.";

			echo display_notice();
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "You must provide a valid <strong>User ID</strong> to view.";
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."/".$SECTION."\\'', 15000)";

		echo display_error();
	}
}
?>
