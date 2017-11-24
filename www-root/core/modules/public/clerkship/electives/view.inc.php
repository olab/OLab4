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
 * Allows the student to view their elective details.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('clerkship', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if(isset($_GET["id"])) {
		$EVENT_ID = clean_input($_GET["id"], "int");
		
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/clerkship/electives?section=view", "title" => "Viewing Elective Details");
		
		echo "<h1>Details</h1>\n";
		
		$query		= "	SELECT *
						FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`electives`, `".CLERKSHIP_DATABASE."`.`event_contacts`
						WHERE `".CLERKSHIP_DATABASE."`.`events`.`event_id` = ".$db->qstr($EVENT_ID)."
						AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
						AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`";
		$event_info	= $db->GetRow($query);
		
		if($event_info) {
			$PROCESSED = $event_info;
			?>
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Viewing Elective">
					<colgroup>
						<col style="width: 25%" />
						<col style="width: 75%" />
					</colgroup>
					<tfoot>
						<tr>
							<td style="width: 25%; text-align: left">
								<input type="button" class="btn" value="Back" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship?section=clerk&ids=<?php echo $event_info["etype_id"]; ?>'" />
							</td>
							<td style="width: 75%; text-align: right">&nbsp;</td>
						</tr>
					</tfoot>
					<tbody>
					<tr>
						<td colspan="2"><h2>Elective Details</h2></td>
					</tr>
					<tr>
						<td style="width: 25%">Geographic Location</td>
						<td style="width: 75%"><?php echo $PROCESSED["geo_location"]; ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Elective Period</td>
						<td style="width: 75%"><?php echo $PROCESSED["event_title"]; ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Elective Department</td>
						<?php
						$query		= "	SELECT `category_name`
						FROM `".CLERKSHIP_DATABASE."`.`categories`
						WHERE `category_id` = ".$db->qstr($PROCESSED["department_id"]);
						
						$result	= $db->GetRow($query);
						
						?>
						<td style="width: 75%"><?php echo $result["category_name"]; ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Elective Discipline</td>
						<td style="width: 75%"><?php echo clerkship_fetch_specific_discipline($PROCESSED["discipline_id"]); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Sub-Discipline</td>
						<td style="width: 75%"><?php echo (!isset($PROCESSED["sub_discipline"]) || $PROCESSED["sub_discipline"] == "" ? "N/A" : $PROCESSED["sub_discipline"]); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Host School</td>
						<td style="width: 75%"><?php echo clerkship_fetch_specific_school($PROCESSED["schools_id"]); ?></td>
					</tr>
					<?php
						if(isset($PROCESSED["other_medical_school"]) && $PROCESSED["other_medical_school"] != "") {
							?>
							<tr>
								<td style="width: 25%">Other School</td>
								<td style="width: 75%"><?php echo $PROCESSED["other_medical_school"]; ?></td>
							</tr>
							<?php
						}
					?>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td style="width: 25%">Start Date</td>
						<td style="width: 75%"><?php echo date("Y-m-d", $PROCESSED["event_start"]); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">End Date</td>
						<td style="width: 75%"><?php echo date("Y-m-d", $PROCESSED["event_finish"]); ?></td>
					</tr>
					<?php 
						$duration = ceil(($PROCESSED["event_finish"] - $PROCESSED["event_start"]) / 604800);
					?>
					<tr>
						<td style="width: 25%">Elective Weeks</td>
						<td style="width: 75%"><?php echo $duration; ?></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td style="width: 25%">Planned Experience</td>
						<td style="width: 75%"><?php echo $PROCESSED["objective"]; ?></td>
					</tr>
					<tr>
						<td colspan="2" style="padding-top: 15px"><h2>Preceptor Details</h2></td>
					</tr>
					<tr>
						<td style="width: 25%">Preceptor Prefix</td>
						<td style="width: 75%"><?php echo (isset($PROCESSED["preceptor_prefix"]) && $PROCESSED["preceptor_prefix"] != "" ? $PROCESSED["preceptor_prefix"] : "N/A"); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Preceptor First Name</td>
						<td style="width: 75%"><?php echo (isset($PROCESSED["preceptor_first_name"]) && $PROCESSED["preceptor_first_name"] != "" ? $PROCESSED["preceptor_first_name"] : "N/A"); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Preceptor Last Name</td>
						<td style="width: 75%"><?php echo $PROCESSED["preceptor_last_name"]; ?></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td style="width: 25%">Country</td>
						<td style="width: 75%"><?php echo fetch_specific_country($PROCESSED["countries_id"]); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Province</td>
						<td style="width: 75%"><?php echo $PROCESSED["prov_state"]; ?></td>
					</tr>
					<tr>
						<td style="width: 25%">City</td>
						<td style="width: 75%"><?php echo $PROCESSED["city"]; ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Address</td>
						<td style="width: 75%"><?php echo $PROCESSED["address"]; ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Postal / Zip Code</td>
						<td style="width: 75%"><?php echo (isset($PROCESSED["postal_zip_code"]) && $PROCESSED["postal_zip_code"] != "" ? $PROCESSED["postal_zip_code"] : "N/A"); ?></td>
					</tr>
					
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td style="width: 25%">Phone</td>
						<td style="width: 75%"><?php echo (isset($PROCESSED["phone"]) && $PROCESSED["phone"] != "" ? $PROCESSED["phone"] : "N/A"); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Fax</td>
						<td style="width: 75%"><?php echo (isset($PROCESSED["fax"]) && $PROCESSED["fax"] != "" ? $PROCESSED["fax"] : "N/A"); ?></td>
					</tr>
					<tr>
						<td style="width: 25%">Email</td>
						<td style="width: 75%"><?php echo $PROCESSED["email"]; ?></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
			</tbody>
			</table>
			<?php
		} else {
			$query		= "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`events` AS a
							JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
							ON a.`event_id` = b.`event_id`
							JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
							ON a.`region_id` = c.`region_id`
							JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS d
							ON a.`rotation_id` = d.`rotation_id`
							WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
			$event_info	= $db->GetRow($query);
			if ($event_info) {
				$category_string = "";
				$category_id = clean_input($event_info["category_id"], array("int"));
				if ($category_id) {
					$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
								WHERE `category_id` = ".$db->qstr($category_id);
					$category = $db->GetRow($query);
					if ($category) {
						$parent_id 						= $category["category_parent"];
						$category_selected_reverse[]	= $category["category_name"];
						while ($parent_id != 49) {
							$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
										WHERE `category_id` = ".$db->qstr($parent_id);
							$parent_category = $db->GetRow($query);
							$category_selected_reverse[]	= $parent_category["category_name"];
							$parent_id 						= $parent_category["category_parent"];
						}
						$category_selected = array_reverse($category_selected_reverse);
						for ($i = 0; $i <= count($category_selected)-1; $i++) {
							$category_string .= $category_selected[$i].($i != count($category_selected)-1 ? " > " : "");
						}
					}
				}
				$PROCESSED = $event_info;
				?>
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Viewing Elective">
						<colgroup>
							<col style="width: 25%" />
							<col style="width: 75%" />
						</colgroup>
						<tfoot>
							<tr>
								<td style="width: 25%; text-align: left">
									<input type="button" class="btn" value="Back" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship?section=clerk&ids=<?php echo $event_info["etype_id"]; ?>'" />
								</td>
								<td style="width: 75%; text-align: right">&nbsp;</td>
							</tr>
						</tfoot>
						<tbody>
						<tr>
							<td colspan="2"><h2>Event Details</h2></td>
						</tr>
						<tr>
							<td style="width: 25%">Event Region</td>
							<td style="width: 75%"><?php echo $PROCESSED["region_name"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Event Title</td>
							<td style="width: 75%"><?php echo $PROCESSED["event_title"]; ?></td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td style="width: 25%">Event Rotation</td>
							<td style="width: 75%"><?php echo $PROCESSED["rotation_title"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Event Takes Place In:</td>
							<td style="width: 75%"><?php echo $category_string; ?></td>
						</tr>
						<tr>
							<td colspan="2">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td style="width: 25%">Start Date</td>
							<td style="width: 75%"><?php echo date("Y-m-d", $PROCESSED["event_start"]); ?></td>
						</tr>
						<tr>
							<td style="width: 25%">End Date</td>
							<td style="width: 75%"><?php echo date("Y-m-d", $PROCESSED["event_finish"]); ?></td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td style="width: 25%">Save State: </td>
							<td style="width: 75%"><?php echo ucfirst($PROCESSED["event_status"]); ?></td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
				</tbody>
				</table>
				<?php
			} else {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";
	
				$ERROR++;
				$ERRORSTR[]	= "This Event ID is not valid<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
			
				echo display_error();
			
				application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for viewing a clerkship elective in module [".$MODULE."].");
			}
		}
	} else {		
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/clerkship/electives?section=view", "title" => "Viewing Electives");
		
		echo "<h1>My Clerkship Electives</h1>\n";
	
		if (isset($_GET["type"])) {
			switch ($_GET["type"]) {
				case "approval":
					$where = " AND a.`event_status` = 'approval' ";
					$noticemsg = "You do not have any electives pending approval.";
				break;
				case "published":
					$where = " AND a.`event_status` = 'published' ";
					$noticemsg = "You do not have any electives approved in the system.";
				break;
				case "rejected":
					$where = " AND a.`event_status` = 'trash' ";
					$noticemsg = "You do not have any electives rejected in the system.";
				break;
				default:
					$where = "";
					$noticemsg = "You do not have any electives in the system.";
				break;
			}
		} else {
			$where		= "";
			$noticemsg	= "You do not have any electives in the system.";
		}
	
		$query = "	SELECT a.*, c.*
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
					ON c.`region_id` = a.`region_id`
					WHERE b.`econtact_type` = 'student'
					AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())."
					AND a.`event_type` = 'elective'
					".$where."
					ORDER BY a.`event_start` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			?>
			<div style="float: right; padding-top: 2px">
				<div id="module-content">
					<ul class="page-action">
						<li>
							<a href="<?php echo ENTRADA_URL; ?>/clerkship/electives?section=add" class="strong-green">Add Elective</a>
						</li>
					</ul>
				</div>
			</div>
			<div style="clear: both"></div>
	
			<table class="tableList" cellspacing="0" summary="List of Clerkship Electives">
			<colgroup>
				<col class="modified" />
				<col class="date" />
				<col class="date" />
				<col class="region" />
				<col class="title" />
			</colgroup>
			<thead>
				<tr>
					<td class="type borderl"><?php echo $translate->_("Event Type"); ?></td>
					<td class="date-smallest">Start Date</td>
					<td class="date-smallest">Finish Date</td>
					<td class="region">Region</td>
					<td class="title">Category Title</td>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($results as $result) {
				if((time() >= $result["event_start"]) && (time() <= $result["event_finish"])) {
					$bgcolour	= "#E7ECF4";
					$is_here	= true;
				} else {
					$bgcolour	= "#FFFFFF";
					$is_here	= false;
				}
	
				if((bool) $result["manage_apartments"]) {
					$aschedule_id = regionaled_apartment_check($result["event_id"], $ENTRADA_USER->getID());
					$apartment_available = (($aschedule_id) ? true : false);
				} else {
					$apartment_available = false;
				}
	
				if(!isset($result["region_name"]) || $result["region_name"] == "") {
					$result_region = clerkship_get_elective_location($result["event_id"]);
					$result["region_name"] = $result_region["region_name"];
					$result["city"] = $result_region["city"];
				} else {
					$result["city"] = "";
				}
	
				$cssclass = "";
	
				if ($result["event_type"] == "elective") {
					switch($result["event_status"]) {
						case "approval":
							$elective_word = "Pending";
							$cssclass = " class=\"in_draft\"";
							$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
						break;
						case "published":
							$elective_word = "Approved";
							$cssclass = " class=\"published\"";
							$click_url = ENTRADA_URL."/clerkship/electives?section=view&id=".$result["event_id"];
						break;
						case "trash":
							$elective_word = "Rejected";
							$cssclass = " class=\"rejected\"";
							$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
						break;
						default:
							$elective_word = "";
							$cssclass = "";
							$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
						break;
					}
				}
	
				$event_title = clean_input($result["event_title"], array("htmlbrackets", "trim"));
	
				echo "<tr".(($is_here) && $cssclass == "" ? " class=\"current\"" : $cssclass).">\n";
				echo "	<td class=\"type\"><a href=\"".$click_url."\" style=\"font-size: 11px\">Elective".(($elective_word != "") ? " (".$elective_word.")" : "")."</a></td>\n";
				echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["event_start"])."</a></td>\n";
				echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["event_finish"])."</a></td>\n";
				echo "	<td class=\"region\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["city"])."</a></td>\n";
				echo "	<td class=\"title\"><a href=\"".$click_url."\" style=\"font-size: 11px\"><span title=\"".$event_title."\">".limit_chars(html_decode($event_title), 55)."</span></a></td>\n";
				echo "</tr>\n";
			}
			?>
			</tbody>
			</table>
			<?php
		} else {
			$NOTICE++;
			$NOTICESTR[] = $noticemsg." Go <a href=\"".ENTRADA_URL."/clerkship/electives?section=add\">here</a> to add an elective.";
	
			echo display_notice();
		}
	}
}
