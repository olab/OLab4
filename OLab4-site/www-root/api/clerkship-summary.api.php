<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
*/
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	if (isset($_REQUEST["id"]) && ((int)$_REQUEST["id"])) {
		$PROXY_ID = clean_input($_REQUEST["id"], array("int"));
	} else {
		$PROXY_ID = $ENTRADA_USER->getID();
		$STUDENT_VIEW = true;
	}
	$grad_year = get_account_data("grad_year", $PROXY_ID);
	$query = "	SELECT DISTINCT(b.`rotation_id`), c.`rotation_title` FROM
				`".CLERKSHIP_DATABASE."`.`event_contacts` AS a
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
				ON a.`event_id` = b.`event_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
				ON b.`rotation_id` = c.`rotation_id`
				WHERE a.`etype_id` = ".$db->qstr($PROXY_ID)."
				AND a.`econtact_type` = 'student'
				AND b.`event_start` < ".$db->qstr(time());
	$rotations = $db->GetAll($query);
	?>
	<div style="clear: both"></div>
	<?php 
	$summary_shown = false;
	if ($rotations) {
		?>
		<form action="<?php echo ENTRADA_URL ?>/admin/clerkship/flag" method="post">
			<table class="tableList" cellspacing="0" summary="Clerkship Progress Summary">
				<colgroup>
					<col class="modified" />
					<col class="region" />
					<col class="date" />
					<col class="date" />
					<col class="date" />
					<col class="date" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="region-large">Rotation</td>
						<td class="date-smallest">Objectives Logged</td>
						<td class="date-smallest">Objectives Required</td>
						<td class="date-smallest">Procedures Logged</td>
						<td class="date-smallest">Procedures Required</td>
					</tr>
				</thead>
				<tbody>									
				<?php
				foreach ($rotations as $rotation) {
					if ($rotation["rotation_id"] && $rotation["rotation_id"] != 12) {
						$procedures_required = 0;
					    $objectives_required = 0;
					    $objectives_recorded = 0;
					    $procedures_recorded = 0;
					    $grad_year = get_account_data("grad_year", $PROXY_ID);
					    
						$query = "	SELECT `objective_id`, `lmobjective_id`, MAX(`number_required`) AS `required`
									FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives`
									WHERE `rotation_id` = ".$db->qstr($rotation["rotation_id"])."
									AND `grad_year_min` <= ".$db->qstr($grad_year)."
									AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr($grad_year).")
									GROUP BY `objective_id`";
						$required_objectives = $db->GetAll($query);
						if ($required_objectives) {
							foreach ($required_objectives as $required_objective) {
								$objectives_required += $required_objective["required"];
								$llocation_ids_string = "";
								if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
									$query = "SELECT c.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS b
												ON a.`lltype_id` = b.`lltype_id`
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS c
												ON b.`llocation_id` = c.`llocation_id`
												WHERE a.`lmobjective_id` = ".$db->qstr($required_objective["lmobjective_id"]);
									$valid_locations = $db->GetAll($query);
									if ($valid_locations) {
										foreach ($valid_locations as $location) {
											if ($llocation_ids_string) {
												$llocation_ids_string .= ", ".$db->qstr($location["llocation_id"]);
											} else {
												$llocation_ids_string = $db->qstr($location["llocation_id"]);
											}
										}
									}
								}
								$query = "SELECT COUNT(`objective_id`) AS `recorded`
											FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
											ON a.`lentry_id` = b.`lentry_id`
											WHERE b.`entry_active` = '1'
											AND b.`proxy_id` = ".$db->qstr($PROXY_ID)."
											AND a.`objective_id` = ".$db->qstr($required_objective["objective_id"])."
											".($llocation_ids_string ? "AND b.`llocation_id` IN (".$llocation_ids_string.")" : "")."
											GROUP BY a.`objective_id`";
//								$query = "SELECT COUNT(`objective_id`) AS `recorded`
//											FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
//											JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
//											ON a.`lentry_id` = b.`lentry_id`
//											WHERE a.`lentry_id` IN
//											(
//												SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
//												WHERE `entry_active` = '1'
//												AND `proxy_id` = ".$db->qstr($PROXY_ID)."
//											)
//											AND a.`objective_id` = ".$db->qstr($required_objective["objective_id"])."
//											".($llocation_ids_string ? "AND b.`llocation_id` IN (".$llocation_ids_string.")" : "")."
//											GROUP BY a.`objective_id`";
								$recorded = $db->GetOne($query);
								
								if ($recorded) {
									$objectives_recorded += ($recorded <= $required_objective["required"] ? $recorded : $required_objective["required"]);
								}
							}
						}
						$query = "SELECT `lprocedure_id`, `lpprocedure_id`, MAX(`number_required`) AS `required`
									FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures`
									WHERE `rotation_id` = ".$db->qstr($rotation["rotation_id"])."
									AND `grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $PROXY_ID))."
									AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $PROXY_ID)).")
									GROUP BY `lprocedure_id`";
						$required_procedures = $db->GetAll($query);
						if ($required_procedures) {
							foreach ($required_procedures as $required_procedure) {
								$procedures_required += $required_procedure["required"];
								$llocation_ids_string = "";
								if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
									$query = "SELECT b.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS a
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS b
												ON a.`lltype_id` = b.`lltype_id
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS c
												ON b.`llocation_id` = c.`llocation_id`
												WHERE a.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"]);
									$valid_locations = $db->GetAll($query);
									if ($valid_locations) {
										foreach ($valid_locations as $location) {
											if ($llocation_ids_string) {
												$llocation_ids_string .= ", ".$db->qstr($location["llocation_id"]);
											} else {
												$llocation_ids_string = $db->qstr($location["llocation_id"]);
											}
										}
									}
								}
								$query = "SELECT COUNT(`lprocedure_id`) AS `recorded`
										FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures`
										WHERE `lentry_id` IN
										(
											SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
											WHERE `entry_active` = '1' 
											AND `proxy_id` = ".$db->qstr($PROXY_ID)."
										)
										AND `lprocedure_id` = ".$db->qstr($required_procedure["lprocedure_id"])."
										".($llocation_ids_string ? "AND a.`llocation_id` IN (".$llocation_ids_string.")" : "")."
										GROUP BY `lprocedure_id`";
								$recorded = $db->GetOne($query);
								
								if ($recorded) {
									$procedures_recorded += ($recorded <= $required_procedure["required"] ? $recorded : $required_procedure["required"]);
								}
							}
						}
						$url = ENTRADA_URL."/clerkship/logbook?section=view&type=missing&core=".$rotation["rotation_id"].(!isset($STUDENT_VIEW) || !$STUDENT_VIEW ? "&id=".$PROXY_ID : "");
						$summary_shown = true;
						?>
						<tr class="entry-log">
							<td class="modified">&nbsp;</td>
							<td class="region-large"><a href="<?php echo $url; ?>"><?php echo $rotation["rotation_title"]; ?></a></td>
							<td class="date-smallest"><a href="<?php echo $url; ?>"><?php echo $objectives_recorded; ?></a></td>
							<td class="date-smallest"><a href="<?php echo $url; ?>"><?php echo $objectives_required; ?></a></td>
							<td class="date-smallest"><a href="<?php echo $url; ?>"><?php echo $procedures_recorded; ?></a></td>
							<td class="date-smallest"><a href="<?php echo $url; ?>"><?php echo $procedures_required; ?></a></td>
						</tr>
						<?php
					}
				}
				?>		
				</tbody>
			</table>
		</form>
		<?php
	}
	if (!$summary_shown) {
        $student_name = get_account_data("firstlast", $PROXY_ID);
		add_notice($student_name . " has not begun any core rotations in the system at this time.");
        echo "<div style=\"width: 100%; text-align: center; margin-top: 80px;\">\n";
		echo display_notice();
        echo "</div>\n";
	}
}