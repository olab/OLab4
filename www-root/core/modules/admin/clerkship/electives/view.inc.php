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
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('electives', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship?".replace_query(array("section" => "view")), "title" => "Clerkship Schedule");
	if (isset($_GET["ids"]) && $PROXY_ID = clean_input($_GET["ids"], "int")) {
		$student_name	= get_account_data("firstlast", $PROXY_ID);
		
		/**
		 * Process local page actions.
		 */
		$query		= "	SELECT a.*, c.*
						FROM `".CLERKSHIP_DATABASE."`.`events` AS a
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
						ON b.`event_id` = a.`event_id`
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
						ON c.`region_id` = a.`region_id`
						WHERE b.`econtact_type` = 'student'
						AND b.`etype_id` = ".$db->qstr($PROXY_ID)."
						ORDER BY a.`event_start` ASC";
		$results	= $db->GetAll($query);
		if($results) {
			$elective_weeks = clerkship_get_elective_weeks($PROXY_ID);
			$remaining_weeks = (int)$CLERKSHIP_REQUIRED_WEEKS - (int)$elective_weeks["approved"];
			
			$sidebar_html  = "<ul class=\"menu\">\n";
			$sidebar_html .= "	<li><strong>".$elective_weeks["approval"]."</strong> Pending Approval</li>\n";
			$sidebar_html .= "	<li class=\"checkmark\"><strong>".$elective_weeks["approved"]."</strong> Weeks Approved</li>\n";
			$sidebar_html .= "	<li class=\"incorrect\"><strong>".$elective_weeks["trash"]."</strong> Weeks Rejected</li>\n";
			$sidebar_html .= "	<br />";
			if((int)$elective_weeks["approval"] + (int)$elective_weeks["approved"] > 0) {
				$sidebar_html .= "	<li><a target=\"blank\" href=\"".ENTRADA_URL."/admin/clerkship/electives?section=disciplines&id=".$PROXY_ID."\">Discipline Breakdown</a></li>\n";
			}
			$sidebar_html .= "</ul>\n";
		
			$sidebar_html .= "<div style=\"margin-top: 10px\">\n";
			$sidebar_html .= $student_name. " has ".$remaining_weeks." required elective week".(($remaining_weeks != 1) ? "s" : "")." remaining.\n";
			$sidebar_html .= "</div>\n";
		
			new_sidebar_item("Elective Weeks", $sidebar_html, "page-clerkship", "open");
			?>
			<div style="float: right; padding-top: 8px">
			    <div id="module-content">
			        <ul class="page-action">
			            <li>
			                <a href = "<?php echo ENTRADA_URL."/admin/clerkship/electives?section=add_elective&ids=".$PROXY_ID;?>" class="strong-green">Add Elective</a>
			            </li>
			        </ul>
			    </div>
			</div>
			<div style="float: right; padding-top: 8px">
			    <div id="module-content">
			        <ul class="page-action">
			            <li>
			                <a href = "<?php echo ENTRADA_URL."/admin/clerkship/electives?section=add_core&ids=".$PROXY_ID;?>" class="strong-green">Add Core</a>
			            </li>
			        </ul>
			    </div>
			</div>
			<h1><?php echo $student_name.(substr($student_name, -1) != "s" ? "'s" : "'");?> Clerkship Schedule</h1>
			<table class="tableList" cellspacing="0" summary="List of Clerkship Schedule">
			<colgroup>
				<col class="modified" />
				<col class="type" />
				<col class="date" />
				<col class="date" />
				<col class="region" />
				<col class="title" />
			</colgroup>
			<thead>
				<tr>
					<td class="modified">&nbsp;</td>
					<td class="type"><?php echo $translate->_("Event Type"); ?></td>
					<td class="date-smallest">Start Date</td>
					<td class="date-smallest">Finish Date</td>
					<td class="region">Region</td>
					<td class="title">Category Title</td>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($results as $result) {
				if ((time() >= $result["event_start"]) && (time() <= $result["event_finish"])) {
					$bgcolour	= "#E7ECF4";
					$is_here	= true;
				} else {
					$bgcolour	= "#FFFFFF";
					$is_here	= false;
				}

				if ((bool) $result["manage_apartments"]) {
					$aschedule_id = regionaled_apartment_check($result["event_id"], $ENTRADA_USER->getActiveId());
					$apartment_available = (($aschedule_id) ? true : false);
				} else {
					$apartment_available = false;
				}

				if ($apartment_available) {
					$click_url = ENTRADA_URL."/clerkship?section=details&id=".$result["event_id"];
				} else {
					$click_url = "";
				}

				if (!isset($result["region_name"]) || $result["region_name"] == "") {
					$result_region = clerkship_get_elective_location($result["event_id"]);
					$result["region_name"] = $result_region["region_name"];
					$result["city"]		   = $result_region["city"];
				} else {
					$result["city"] = "";
				}
				
				$event_title = clean_input($result["event_title"], array("htmlbrackets", "trim"));
				
				$cssclass 	= "";
				$skip		= false;

				if ($result["event_type"] == "elective") {
					switch ($result["event_status"]) {
						case "approval":
							$elective_word = "Pending";
							$cssclass 	= " class=\"in_draft\"";
							$click_url 	= ENTRADA_URL."/admin/clerkship/electives?section=edit&id=".$result["event_id"];
							$skip		= false;
						break;
						case "published":
							$elective_word = "Approved";
							$cssclass 	= " class=\"published\"";
							$click_url 	= ENTRADA_URL."/admin/clerkship/electives?section=edit&id=".$result["event_id"];
							$skip		= false;
						break;
						case "trash":
							$elective_word = "Rejected";
							$cssclass 	= " class=\"rejected\"";
							$click_url 	= ENTRADA_URL."/admin/clerkship/electives?section=edit&id=".$result["event_id"];
							$skip		= true;
						break;
						default:
							$elective_word = "";
							$cssclass = "";
						break;
					}
					
					$elective	= true;					
				} else {
					$elective	= false;
					$skip		= false;
				}
				if (!$click_url) {
					$click_url 	= ENTRADA_URL."/admin/clerkship/electives?section=edit&id=".$result["event_id"];
				}
				if (!$skip) {
					echo "<tr".(($is_here) && $cssclass != " class=\"in_draft\"" ? " class=\"current\"" : $cssclass).">\n";
					echo "	<td class=\"modified\"><a href=\"".$click_url."\" style=\"font-size: 11px\"><img src=\"".ENTRADA_URL."/images/".(($apartment_available) ? "housing-icon-small.gif" : "pixel.gif")."\" width=\"16\" height=\"16\" alt=\"".(($apartment_available) ? "Detailed apartment information available." : "")."\" title=\"".(($apartment_available) ? "Detailed apartment information available." : "")."\" style=\"border: 0px\" /></a></td>\n";
					echo "	<td class=\"type\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".(($elective) ? "Elective".(($elective_word != "") ? " (".$elective_word.")" : "") : "Core Rotation")."</a>"."</td>\n";
					echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["event_start"])."</a></td>\n";
					echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["event_finish"])."</a></td>\n";
					echo "	<td class=\"region\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode((($result["city"] == "") ? limit_chars(($result["region_name"]), 30) : $result["city"]))."</a></td>\n";
					echo "	<td class=\"title\">";
					echo "		<a href=\"".$click_url."\" style=\"font-size: 11px\"><span title=\"".$event_title."\">".limit_chars(html_decode($event_title), 55)."</span></a>";
					echo "	</td>\n";
					echo "</tr>\n";
				}
			}
			?>
			</tbody>
			</table>
			<?php
		} else {
			$NOTICE++;
			$NOTICESTR[] = $student_name . " has no scheduled clerkship rotations / electives in the system at this time.  Click <a href = ".ENTRADA_URL."/admin/clerkship/electives?section=add_elective&ids=".$PROXY_ID." class=\"strong-green\">here</a> to add an elective.";

			echo display_notice();
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "You must provide a valid <strong>User ID</strong> to view.";
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."/".$SECTION."\\'', 15000)";

		echo display_error();
	}
}
?>
