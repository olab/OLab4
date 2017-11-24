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
 * This file is used to delete objectives from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer:James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_OBJECTIVES")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("objective", "delete", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["objective_id"]) && ($id = clean_input($_GET["objective_id"], array("notags", "trim")))) {
		$OBJECTIVE_ID = $id;
	}
	
	if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
		$MODE = "ajax";
	}
	
	if ($MODE == "ajax" && $OBJECTIVE_ID) {
		ob_clear_open_buffers();
		
		switch($STEP) {
			case 2 :
				if ($_POST["confirm"] == "on") {
					$query = "	SELECT a.*, GROUP_CONCAT(b.`organisation_id`) AS `organisations` FROM `global_lu_objectives` AS a
								LEFT JOIN `objective_organisation` AS b
								ON a.`objective_id` = b.`objective_id`
								WHERE a.`objective_id` = ".$db->qstr($OBJECTIVE_ID)."
								AND a.`objective_active` = '1'
								GROUP BY `objective_id`";
					$objectives = $db->GetAll($query);
					if ($objectives) {
						foreach ($objectives as $objective) {
							$organisations = explode(",", $objective["organisations"]);
							/*
							* Remove the objective_organisation record.
							*/
							$query = "DELETE FROM `objective_organisation` WHERE `organisation_id` = ".$db->qstr($ORGANISATION_ID)." AND `objective_id` = ".$db->qstr($objective["objective_id"]);
							if (!$db->Execute($query)) {
								application_log("Failed to remove entry from [objective_organisation], DB said: ".$db->ErrorMsg());
							}
							/*
							* If $organisations has more than 1 entry the objective is active across multiple, and should not be deactivated in `global_lu_objectives`
							*/
							if (count($organisations) <= 1) {
								$query = "UPDATE `global_lu_objectives` SET `objective_active` = '0', `updated_date` = " . $db->qstr(time()) . ", `updated_by` = " . $db->qstr($ENTRADA_USER->getID()) . " WHERE `objective_id` = ".$db->qstr($objective["objective_id"]);
								if (!$db->Execute($query)) {
									application_log("Failed to update [global_lu_objectives], DB said: ".$db->ErrorMsg());
								}
							}
						}
						deactivate_objective_children($OBJECTIVE_ID, $ORGANISATION_ID);
						echo json_encode(array("status" => "success"));
					}
				} else {
					echo json_encode(array("status" => "error"));
				}

			break;
			case 1 :
			default :
			
				$query	= "	SELECT a.*, b.`organisation_id` FROM `global_lu_objectives` AS a
							LEFT JOIN `objective_organisation` AS b
							ON a.`objective_id` = b.`objective_id`
							WHERE a.`objective_id` = ".$db->qstr($OBJECTIVE_ID)."
							AND a.`objective_active` = '1'";
				$objective	= $db->GetRow($query);
				
				if ($objective) {
					?>
					<div class="display-generic">
						<p>You are about to delete the objective <strong><?php echo $objective["objective_name"]; ?></strong>. Please click the <strong>delete</strong> button below to remove it from the system.</p>
						<p><strong>Please note:</strong> Any children of this objective will be removed as well.</p>
					</div>
					<form id="objective-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives?".replace_query(array("step" => "2")); ?>" method="post">
						<input type="checkbox" name="confirm" /> Please check this box to confirm you wish to remove the objective and its children.
					</form>
					<?php
				} else {
					echo $db->ErrorMsg();
				}
				
			break;
		}
		
		exit;
	} else {
		$BREADCRUMB[] = array("url" => "", "title" => "Deactivate Curriculum Tag Set");

		$redirect_url = ENTRADA_URL . "/admin/settings/manage/objectives?org=".$ORGANISATION_ID;

		echo "<h1>Deactivate Curriculum Tag Sets</h1>\n";

		$objectives = array();
		$objective_ids = array();

		if (isset($_POST["deactivate"]) && is_array($_POST["deactivate"]) && count($_POST["deactivate"])) {
			foreach ($_POST["deactivate"] as $objective_id) {
				$objective_id = (int) $objective_id;
				if ($objective_id) {
					$objective_ids[] = $objective_id;
				}
			}
		}

		if ($objective_ids) {
			$query = "SELECT a.*
								FROM `global_lu_objectives` AS a
								JOIN `objective_organisation` AS b
								ON a.`objective_id` = b.`objective_id`
								WHERE a.`objective_parent` = '0'
								AND a.`objective_active` = '1'
								AND a.`objective_id` IN ('" . implode("', '", $objective_ids) . "')
								AND b.`organisation_id` = " . $db->qstr($ORGANISATION_ID) . "
								ORDER BY a.`objective_order` ASC";
			$objectives = $db->GetAll($query);
			if ($objectives) {
				$total_objectives = count($objectives);
			} else {
				$total_objectives = 0;
			}
		}

		if (!$total_objectives) {
			header("Location: " . ENTRADA_URL . "/admin/settings/manage/objectives");
			exit;
		}

		// Error Checking
		switch ($STEP) {
			case 2 :
				if (isset($_POST["confirmed"]) && $_POST["confirmed"] == 1) {
					$deactiviated = array();
					$failed = array();

					foreach ($objectives as $objective) {
						if ($db->AutoExecute("global_lu_objectives", array("objective_active" => 0), "UPDATE", "objective_id = ".(int) $objective["objective_id"])) {
							$deactiviated[] = html_encode($objective["objective_name"]);

							application_log("success", "Deactivated global_lu_objectives.objective_id [" . $objective["objective_id"] . "].");
						} else {
							$failed[] = html_encode($objective["objective_name"]);

							application_log("error", "Unable to deactivate global_lu_objectives.objective_id [" . $objective["objective_id"] . "]. Database said: ".$db->ErrorMsg());
						}
					}


					$total_deactivated = count($deactiviated);
					$total_failed = count($failed);

					if ($total_deactivated) {
						add_success("You have successfully deactivated <strong>" . $total_deactivated . "</strong> curriculum tag set" . ($total_deactivated != 1 ? "s" : "") . ".<br /><br />You will now be redirected to the objectives index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $redirect_url . "\" style=\"font-weight: bold\">click here</a> to continue.");
					} else {
						add_error("We were unable to deactivate  <strong>" . $total_failed . "</strong> requested curriculum tag set" . ($total_failed != 1 ? "s" : "") . ". We will be looking into this issue shortly, and we apologize for any inconvenience.<br /><br />You will now be redirected to the objectives index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $redirect_url . "\" style=\"font-weight: bold\">click here</a> to continue.");
					}
				}
			break;
			case 1 :
			default :
				continue;
			break;
		}

		// Display Page
		switch ($STEP) {
			case 2 :
				$ONLOAD[] = "setTimeout('window.location=\\'".$redirect_url."\\'', 5000)";

				if (has_error()) {
					echo display_error();
				}

				if (has_notice()) {
					echo display_notice();
				}

				if (has_success()) {
					echo display_success();
				}
			break;
			case 1 :
			default :
				if (has_error()) {
					echo display_error();
				} else {
					if (count($objectives) != 1) {
						add_notice("Please review the following <strong>curriculum tag sets</strong> to ensure that you wish to <strong>deactivate them</strong>.");
					} else {
						add_notice("Please review the following <strong>curriculum tag set</strong> to ensure that you wish to <strong>deactivate it</strong>.");
					}

					echo display_notice();

					?>
					<form action="<?php echo ENTRADA_URL."/admin/settings/manage/objectives?section=delete&amp;org=".$ORGANISATION_ID; ?>&amp;step=2" method="post">
						<input type="hidden" name="confirmed" value="1" />

						<table class="table table-striped" summary="Curriculum Tag Sets For Deactivation">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 97%" />
							</colgroup>
							<tbody>
							<?php
							foreach ($objectives as $result) {
								echo "<tr>";
								echo "	<td><input type=\"checkbox\" name=\"deactivate[]\" value=\"".$result["objective_id"]."\" checked=\"checked\" /></td>";
								echo"	<td><a href=\"".ENTRADA_URL."/admin/settings/manage/objectives?section=edit&amp;org=$ORGANISATION_ID&amp;id=".$result["objective_id"]."\">" . html_encode($result["objective_name"]) . "</a></td>";
								echo "</tr>";
							}
							?>
							</tbody>
						</table>
						<input type="submit" class="btn btn-danger" value="Deactivate Selected" />
					</form>
					<?php
				}
			break;
		}
	}
}
