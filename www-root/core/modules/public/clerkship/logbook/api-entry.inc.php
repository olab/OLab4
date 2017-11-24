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
 * Allows students to delete an elective in the system if it has not yet been approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("logbook", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	ob_clear_open_buffers();

	if (isset($_GET["id"]) && (clean_input($_GET["id"], "int"))) {
		$RECORD_ID = clean_input($_GET["id"], "int");
	}

	if ($RECORD_ID) {
		$PROCESSED = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
		if ($PROCESSED) {
			$clinical_presentations = $db->GetAll("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
			$clinical_tasks = $db->GetAll("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
			?>
            <h2>Encounter Details</h2>
            <table class="table">
                <colgroup>
                    <col style="width: 30%" />
                    <col style="width: 70%" />
                </colgroup>
                <tbody>
                    <tr>
                        <td>Encounter Date</td>
                        <td>
                            <?php echo date(DEFAULT_DATE_FORMAT, $PROCESSED["encounter_date"]); ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Rotation</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["rotation_id"]) && $PROCESSED["rotation_id"]) {
                                $query = "SELECT a.`event_title`
                                          FROM `" . CLERKSHIP_DATABASE . "`.`events` AS a
                                          LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`event_contacts` AS b
                                          ON a.`event_id` = b.`event_id`
                                          WHERE b.`etype_id` = " . $db->qstr($PROCESSED["proxy_id"]) . "
                                          AND a.`event_id` = " . $db->qstr($PROCESSED["rotation_id"]);
                                $rotation_title = $db->GetOne($query);
                                if ($rotation_title) {
                                    echo "<div id=\"rotation-title\">" . html_encode($rotation_title) . "</div>";
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Institution</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["lsite_id"]) && $PROCESSED["lsite_id"]) {
                                $query = "SELECT `site_name` FROM `" . CLERKSHIP_DATABASE . "`.`logbook_lu_sites` WHERE `site_type` = " . $db->qstr(CLERKSHIP_SITE_TYPE) . " AND `lsite_id` = " . $db->qstr($PROCESSED["lsite_id"]);
                                $site_name = $db->GetOne($query);
                                if ($site_name) {
                                    echo html_encode($site_name);
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Setting</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["llocation_id"]) && $PROCESSED["llocation_id"]) {
                                $query = "SELECT `location` FROM `" . CLERKSHIP_DATABASE . "`.`logbook_lu_locations` WHERE `llocation_id` = " . $db->qstr($PROCESSED["llocation_id"]);
                                $location = $db->GetOne($query);
                                if ($location) {
                                    echo html_encode($location);
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Patient ID</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["patient_info"]) && $PROCESSED["patient_info"]) {
                                echo html_encode($PROCESSED["patient_info"]);
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Patient Age Range</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["agerange_id"]) && $PROCESSED["agerange_id"]) {
                                $query = "SELECT `age` FROM `" . CLERKSHIP_DATABASE . "`.`logbook_lu_agerange` WHERE `agerange_id` = " . $db->qstr($PROCESSED["agerange_id"]);
                                $age = $db->GetOne($query);
                                if ($age) {
                                    echo html_encode($age);
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Patient Gender</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["gender"]) && $PROCESSED["gender"]) {
                                echo (($PROCESSED["gender"]) == "f" ? "Female" : "Male");
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Clinical Presentations</td>
                        <td>
                            <?php
                            if ($clinical_presentations) {
                                echo "<ol>";
                                foreach ($clinical_presentations as $objective_id) {
                                    $query = "SELECT a.* FROM `global_lu_objectives` AS a
                                                JOIN `objective_organisation` AS b
                                                ON a.`objective_id` = b.`objective_id`
                                                AND b.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                                                WHERE a.`objective_id` = " . $db->qstr($objective_id["objective_id"]) . " 
                                                AND a.`objective_active` = '1'
                                                AND 
                                                (
                                                    a.`objective_parent` = '200' 
                                                    OR a.`objective_parent` IN 
                                                    (
                                                        SELECT `objective_id` FROM `global_lu_objectives` 
                                                        WHERE `objective_parent` = '200'
                                                        AND `objective_active` = '1'
                                                    )
                                                )";
                                    $objective = $db->GetRow($query);
                                    if ($objective) {
                                        echo "<li>" . html_encode($objective["objective_name"]) . "</li>";
                                    }
                                }
                                echo "</ol>";
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Clinical Tasks</td>
                        <td>
                            <?php
                            if ($clinical_tasks) {
                                echo "<ol>";
                                foreach ($clinical_tasks as $procedure_id) {
                                    $procedure = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` WHERE `lprocedure_id` = ".$db->qstr($procedure_id["lprocedure_id"]));
                                    if ($procedure) {
                                        echo "<li>";
                                        echo html_encode($procedure["procedure"]) . ", ";
                                        switch ($procedure_id["level"]) {
                                            case 3 :
                                                echo "Observed";
                                                break;
                                            case 2 :
                                                echo "Performed with help";
                                                break;
                                            case 1 :
                                            default :
                                                echo "Performed independently";
                                                break;
                                        }
                                        echo "</li>";
                                    }
                                }
                                echo "</ol>";
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Personal Reflection</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["reflection"]) && $PROCESSED["reflection"]) {
                                echo html_encode($PROCESSED["reflection"]);
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Additional Comments</td>
                        <td>
                            <?php
                            if (isset($PROCESSED["comments"]) && $PROCESSED["comments"]) {
                                echo html_encode($PROCESSED["comments"]);
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
			</table>
			<?php
		} else {
			add_error("This Entry ID is not valid<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
		
			echo display_error();
		
			application_log("error", "Error, invalid Entry ID [".$RECORD_ID."] supplied for deleting a clerkship logbook entry in module [".$MODULE."].");
		}
	} else {
		add_error("You must provide a valid Entry ID<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	
		echo display_error();
	
		application_log("error", "Error, invalid Entry ID [".$RECORD_ID."] supplied for clerkship logbook entry in module [".$MODULE."].");
	}
}

exit;
