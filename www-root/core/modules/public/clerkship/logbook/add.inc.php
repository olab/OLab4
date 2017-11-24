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
 * Allows students to add electives to the system which still need to be approved.
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
} elseif (!$ENTRADA_ACL->amIAllowed("logbook", "read")) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/clerkship/logbook?section=add", "title" => "Log Patient Encounter");

    echo "<h1>Log Patient Encounter</h1>\n";

    if ((isset($_POST["rotation_id"])) && ($rotation_id = clean_input($_POST["rotation_id"], "int"))) {
        $PROCESSED["event_id"] = $rotation_id;
    } else if (isset($_GET["event"]) && $_GET["event"] && (clean_input($_GET["event"], "int"))) {
        $PROCESSED["event_id"] = clean_input($_GET["event"], "int");
    }

    // Error Checking
    switch ($STEP) {
        case 2 :
            /**
             * Required field "rotation" / Rotation.
             */
            if ((isset($_POST["event_id"])) && ($event_id = clean_input($_POST["event_id"], "int"))) {
                $PROCESSED["rotation_id"] = $event_id;
            } else {
                add_error("The <strong>Rotation</strong> field is required.");
            }

            /**
             * Required field "gender" / Gender.
             */
            if ((isset($_POST["gender"])) && ($gender = ($_POST["gender"] == "m" ? "m" : "f"))) {
                $PROCESSED["gender"] = $gender;
            } else {
                $PROCESSED["gender"] = "";
            }

            /**
             * Required field "institution" / Institution.
             */
            if ((isset($_POST["institution_id"])) && ($institution_id = clean_input($_POST["institution_id"], "int"))) {
                $PROCESSED["lsite_id"] = $institution_id;
            } else {
                add_error("The <strong>Institution</strong> field is required.");
            }

            /**
             * Required field "location" / Location.
             */
            if ((isset($_POST["llocation_id"])) && ($location_id = clean_input($_POST["llocation_id"], "int"))) {
                $PROCESSED["llocation_id"] = $location_id;
            } else {
                add_error("The <strong>Setting</strong> field is required.");
            }

            /**
             * Non-required field "patient" / Patient.
             */
            if ((isset($_POST["patient_id"])) && ($patient_id = clean_input($_POST["patient_id"], array("notags", "trim")))) {
                $PROCESSED["patient_info"] = $patient_id;
            }

            /**
             * Required field "agerange" / Age Range.
             */
            if ((isset($_POST["agerange"])) && ($agerange = clean_input($_POST["agerange"], "int"))) {
                $PROCESSED["agerange_id"] = $agerange;
            } else {
                add_error("The <strong>Age Range</strong> field is required.");
            }

            /**
             * Required field "reflection" / Reflection on learning experience.
             */
            if ((isset($_POST["reflection"])) && ($reflection = clean_input($_POST["reflection"], Array("trim", "notags")))) {
                $PROCESSED["reflection"] = $reflection;
            } else {
                add_error("The <strong>Personal Reflection</strong> field is required. Please include at least a short personal reflection of this encounter before continuing.");
            }

            /**
             * Non-required field "comments" / Comments.
             */
            if ((isset($_POST["comments"])) && ($comments = clean_input($_POST["comments"], Array("trim", "notags")))) {
                $PROCESSED["comments"] = $comments;
            } else {
                $PROCESSED["comments"] = "";
            }

            /**
             * Required field "objectives" / Objectives
             */
            $PROCESSED_OBJECTIVES = array();
            if (is_array($_POST["objectives"]) && count($_POST["objectives"])) {
                foreach ($_POST["objectives"] as $objective_id) {
                    $PROCESSED_OBJECTIVES[] = array("objective_id" => $objective_id);
                }
            } else {
                add_error("The <strong>Clinical Presentations</strong> field is required. Please include at least one Clinical Presentation in this encounter before continuing.");
            }

            /**
             * Non-required field "procedures" / procedures
             */
            $PROCESSED_PROCEDURES = array();
            if (is_array($_POST["procedures"]) && count($_POST["procedures"]) && (@count($_POST["procedures"]) == @count($_POST["proc_participation_level"]))) {
                foreach ($_POST["procedures"] as $procedure_id) {
                    $PROCESSED_PROCEDURES[] = array("lprocedure_id" => $procedure_id, "level" => $_POST["proc_participation_level"][$procedure_id]);
                }
            }

            $encounter_date = Entrada_Utilities::validate_calendar("", "encounter", true);
            if ((isset($encounter_date)) && ((int) $encounter_date)) {
                $PROCESSED["encounter_date"] = (int) $encounter_date;
            } else {
                $PROCESSED["encounter_date"] = 0;
            }

            if (!$ERROR && (!isset($_POST["allow_save"]) || $_POST["allow_save"])) {
                $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                $PROCESSED["updated_date"] = time();

                if ($db->AutoExecute("`" . CLERKSHIP_DATABASE . "`.`logbook_entries`", $PROCESSED, "INSERT")) {
                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"] = $PROCESSED["lsite_id"];

                    if (($entry_id = $db->Insert_Id())) {
                        if (isset($PROCESSED_OBJECTIVES) && count($PROCESSED_OBJECTIVES)) {
                            foreach ($PROCESSED_OBJECTIVES as $objective) {
                                $objective["lentry_id"] = $entry_id;
                                $db->AutoExecute("`" . CLERKSHIP_DATABASE . "`.`logbook_entry_objectives`", $objective, "INSERT");
                            }
                        }
                        if (isset($PROCESSED_PROCEDURES) && count($PROCESSED_PROCEDURES)) {
                            foreach ($PROCESSED_PROCEDURES as $procedure) {
                                $procedure["lentry_id"] = $entry_id;
                                $db->AutoExecute("`" . CLERKSHIP_DATABASE . "`.`logbook_entry_procedures`", $procedure, "INSERT");
                            }
                        }

                        $url = ENTRADA_URL . "/" . $MODULE . "/logbook";

                        $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";

                        add_success("You have successfully added this <strong>Patient Encounter</strong> to the system.<br /><br />You will now be redirected to the list of all your current logbook entries; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\">click here</a> to continue.");

                        application_log("success", "New patient encounter [" . $entry_id . "] added to the system.");
                    } else {
                        add_error("There was a problem inserting this patient encounter into the system; please try again later.");

                        application_log("error", "There was an error inserting a clerkship logbook entry. Database said: ".$db->ErrorMsg());
                    }
                } else {
                    add_error("There was a problem inserting this patient encounter into the system; please try again later.");

                    application_log("error", "There was an error inserting a clerkship logbook entry. Database said: ".$db->ErrorMsg());
                }
            }

            if ($ERROR || (isset($_POST["allow_save"]) && !$_POST["allow_save"])) {
                $STEP = 1;
            }
        break;
        case 1 :
        default :
            continue;
        break;
    }

    // Display Content
    switch ($STEP) {
        case 2 :
            if ($SUCCESS) {
                echo display_success();
            }

            if ($NOTICE) {
                echo display_notice();
            }

            if ($ERROR) {
                echo display_error();
            }
        break;
        case 1 :
        default :
            $HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
            $HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/config/xc2_default.js\"></script>\n";
            $HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_inpage.js\"></script>\n";

            require_once(ENTRADA_ABSOLUTE . "/javascript/logbook.js.php");

            if ($ERROR && (!isset($_POST["allow_save"]) || $_POST["allow_save"])) {
                echo display_error();
            }
            ?>
            <form id="addEncounterForm" action="<?php echo ENTRADA_RELATIVE; ?>/clerkship/logbook?<?php echo replace_query(array("step" => 2)); ?>" method="post" class="form-horizontal">
                <input type="hidden" value="1" name="allow_save" id="allow_save" />

                <div class="row-fluid">
                    <h2>Encounter Details</h2>
                </div>

                <div class="row-fluid">
                    <?php echo Entrada_Utilities::generate_calendar("encounter", "Encounter Date", true, ((isset($PROCESSED["encounter_date"])) ? $PROCESSED["encounter_date"] : time()), true); ?>
                </div>

                <br />

                <div class="control-group">
                    <label for="rotation_id" class="form-required control-label">Rotation</label>
                    <div class="controls">
                        <?php
                        if (isset($PROCESSED["event_id"]) && $PROCESSED["event_id"]) {
                            $query = "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`events` AS a 
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b 
                                        ON a.`event_id` = b.`event_id` 
                                        WHERE b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())." 
                                        AND a.`event_id` = ".$db->qstr(((int)$PROCESSED["event_id"]))." 
                                        AND a.`event_type` = 'clinical'";
                            $found = ($db->GetRow($query) ? true : false);
                        } else {
                            $found  = false;
                        }
                        ?>
                        <select id="rotation_id" name="rotation_id" class="input-xlarge" style="<?php echo ($found ? "; display: none" : ""); ?>" onchange="$('allow_save').value = '0';$('addEncounterForm').submit();">
                        <option value="0">-- Select Rotation --</option>
                        <?php
                        $query = "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`events` AS a 
                                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b 
                                    ON a.`event_id` = b.`event_id` 
                                    WHERE b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())." 
                                    AND a.`event_type` = 'clinical'";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach ($results as $result) {
                                echo "<option value=\"".(int) $result["event_id"]."\"".(isset($PROCESSED["event_id"]) && $PROCESSED["event_id"] == (int)$result["event_id"] ? " selected=\"selected\"" : "").">".$result["event_title"]."</option>\n";
                                if (isset($PROCESSED["event_id"]) && $PROCESSED["event_id"] == (int)$result["event_id"]) {
                                    $rotation_title = $result["event_title"];
                                    $rotation_id = $result["event_id"];
                                }
                            }
                        }
                        ?>
                        </select>
                        <?php
                        if ($found && isset($rotation_title) && $rotation_title) {
                            echo "<div id=\"rotation-title\" style=\"margin-top: 3px\"><b>".$rotation_title."</b> <a href=\"javascript: void(0)\" class=\"btn btn-info btn-small space-left\" onclick=\"$('rotation-title').hide(); $('rotation_id').show();\"><i class=\"fa fa-pencil\" aria-hidden=\"true\"></i></a></div>\n";
                            echo "<input type=\"hidden\" value=\"".$rotation_id."\" name=\"event_id\" />";
                        }
                        ?>
                    </div>
                </div>

                <div class="control-group">
                    <label for="institution_id" class="form-required control-label">Institution</label>
                    <div class="controls">
                        <select id="institution_id" name="institution_id" class="input-xlarge">
                        <option value="0">-- Select Institution --</option>
                        <?php
                        $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_sites` 
                                    WHERE `site_type` = ".$db->qstr(CLERKSHIP_SITE_TYPE);
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach ($results as $result) {
                                echo "<option value=\"".(int) $result["lsite_id"]."\"".((isset($PROCESSED["lsite_id"]) && $PROCESSED["lsite_id"] == $result["lsite_id"]) || (!isset($PROCESSED["lsite_id"]) && isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"] == $result["lsite_id"]) ? " selected=\"selected\"" : "").">".$result["site_name"]."</option>\n";
                            }
                        }
                        ?>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label for="llocation_id" class="form-required control-label">Setting</label>
                    <div class="controls">
                        <select id="llocation_id" name="llocation_id" class="input-xlarge">
                        <option value="0">-- Select Setting --</option>
                        <?php
                        $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                        $location_types = $db->GetAll($query);
                        if ($location_types) {
                            foreach ($location_types as $location_type) {
                                echo "<optgroup label=\"".html_encode($location_type["location_type"])."\">\n";
                                $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS a
                                                        JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS b
                                                        ON a.`llocation_id` = b.`llocation_id`
                                                        WHERE b.`lltype_id` = ".$db->qstr($location_type["lltype_id"]);
                                $results = $db->GetAll($query);
                                if ($results) {
                                    foreach ($results as $result) {
                                        echo "<option value=\"".(int) $result["llocation_id"]."\"".(isset($PROCESSED["llocation_id"]) && $PROCESSED["llocation_id"] == (int)$result["llocation_id"] ? " selected=\"selected\"" : "").">".$result["location"]."</option>\n";
                                    }
                                }
                                echo "</optgroup>\n";
                            }
                        }
                        ?>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label for="patient_id" class="form-nrequired control-label">Patient ID</label>
                    <div class="controls">
                        <input type="text" id="patient_id" name="patient_id" class="input-medium" value="<?php echo html_encode((isset($PROCESSED["patient_info"]) && $PROCESSED["patient_info"] ? $PROCESSED["patient_info"] : "")); ?>" maxlength="50" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="agerange" class="form-required control-label">Patient Age Range</label>
                    <div class="controls">
                        <select id="agerange" name="agerange" class="input-xlarge">
                        <?php
                        if (((int) $_GET["event"]) || $PROCESSED["event_id"]) {
                            $query = "SELECT `category_type`
                                        FROM `".CLERKSHIP_DATABASE."`.`categories` AS a 
                                        JOIN `".CLERKSHIP_DATABASE."`.`events` AS b 
                                        ON a.`category_id` = b.`category_id` 
                                        WHERE b.`event_id` = ".$db->qstr((((int) $_GET["event"]) ? ((int) $_GET["event"]) : $PROCESSED["event_id"]));
                            $category_type = $db->GetOne($query);
                            if ($category_type == "family medicine") {
                                $agerange_cat = "5";
                            } else {
                                $agerange_cat = "0";
                            }
                        } else {
                            $agerange_cat = "0";
                        }

                        $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_agerange` WHERE `rotation_id` = ".$db->qstr($agerange_cat);
                        $results = $db->GetAll($query);
                        if ($results) {
                            echo "<option value=\"0\"".((!isset($PROCESSED["agerange_id"])) ? " selected=\"selected\"" : "").">-- Select Age Range --</option>\n";
                            foreach ($results as $result) {
                                echo "<option value=\"".(int) $result["agerange_id"]."\"".(isset($PROCESSED["agerange_id"]) && $PROCESSED["agerange_id"] == (int)$result["agerange_id"] ? " selected=\"selected\"" : "").">".$result["age"]."</option>\n";
                            }
                        } else {
                            echo "<option value=\"0\"".((!isset($PROCESSED["agerange_id"])) ? " selected=\"selected\"" : "").">-- Age Range --</option>\n";
                        }
                        ?>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label for="gender" class="form-required control-label">Patient Gender</label>
                    <div class="controls">
                        <label for="gender_female" class="radio"><input type="radio" name="gender" id="gender_female" value="f"<?php echo (((!isset($PROCESSED["gender"])) || ((isset($PROCESSED["gender"])) && ($PROCESSED["gender"]) == "f")) ? " checked=\"checked\"" : ""); ?> /> Female</label>
                        <label for="gender_male" class="radio"><input type="radio" name="gender" id="gender_male" value="m"<?php echo (((isset($PROCESSED["gender"])) && $PROCESSED["gender"] == "m") ? " checked=\"checked\"" : ""); ?> /> Male</label>
                    </div>
                </div>

                <div class="control-group">
                    <label for="objective_id" class="form-required control-label">Clinical Presentations</label>
                    <div class="controls">
                        <?php
                        $query = "SELECT c.`rotation_id`, a.`rotation_id` as `event_rotation_id` 
                                    FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                    JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
                                    ON b.`event_id` = a.`event_id`
                                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS c
                                    ON a.`category_id` = c.`category_id`
                                    WHERE b.`econtact_type` = 'student'
                                    AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())."
                                    AND a.`event_finish` < ".$db->qstr(time())."
                                    GROUP BY c.`rotation_id`";
                        $rotations = $db->GetAll($query);
                        if ($rotations) {
                            $past_rotations = "";
                            foreach ($rotations as $row) {
                                if ($row["rotation_id"]) {
                                    if ($past_rotations) {
                                        $past_rotations .= ",".$db->qstr($row["rotation_id"]);
                                    } else {
                                        $past_rotations = $db->qstr($row["rotation_id"]);
                                    }
                                } elseif ($row["event_rotation_id"]) {
                                    if ($past_rotations) {
                                        $past_rotations .= ",".$db->qstr($row["event_rotation_id"]);
                                    } else {
                                        $past_rotations = $db->qstr($row["event_rotation_id"]);
                                    }
                                }
                            }
                            $query = "SELECT `objective_id`, `lmobjective_id`, `rotation_id`, MAX(`number_required`) AS `required`
                                        FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives`
                                        WHERE `rotation_id` IN (".$past_rotations.")
                                        AND `grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                        AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
                                        GROUP BY `objective_id`";
                            $required_objectives = $db->GetAll($query);
                            if ($required_objectives) {
                                $query = "SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
                                            WHERE `entry_active` = '1' 
                                            AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                                $entry_ids = $db->GetAll($query);
                                $entry_ids_string = "";
                                if ($entry_ids) {
                                    foreach ($entry_ids as $entry_id) {
                                        if (!$entry_ids_string) {
                                            $entry_ids_string = $db->qstr($entry_id["lentry_id"]);
                                        } else {
                                            $entry_ids_string .= ", " . $db->qstr($entry_id["lentry_id"]);
                                        }
                                    }
                                }

                                $objective_ids_string = "";
                                foreach ($required_objectives as $required_objective) {
                                    $query = "SELECT `lentry_id`, `llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
                                                WHERE `entry_active` = '1' 
                                                ".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND `llocation_id` IN (
                                                    SELECT d.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
                                                    JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
                                                    ON a.`lmobjective_id` = b.`lmobjective_id`
                                                    JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
                                                    ON b.`lltype_id` = c.`lltype_id`
                                                    JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS d
                                                    ON c.`llocation_id` = d.`llocation_id`
                                                    WHERE a.`lmobjective_id` = ".$db->qstr($required_objective["lmobjective_id"])."
                                                    AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                                    AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
                                                    AND a.`rotation_id` = ".$db->qstr($required_objective["rotation_id"])."
                                                )" : "")."
                                                AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                                    $entry_ids = $db->GetAll($query);
                                    $entry_ids_string = "";
                                    $objective_ids_string_string = "";
                                    if ($entry_ids) {
                                        foreach ($entry_ids as $entry_id) {
                                            if (!$entry_ids_string) {
                                                $entry_ids_string = $db->qstr($entry_id["lentry_id"]);
                                            } else {
                                                $entry_ids_string .= ", " . $db->qstr($entry_id["lentry_id"]);
                                            }
                                        }
                                    }

                                    $query = "SELECT COUNT(`objective_id`) AS `recorded`
                                                FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives`
                                                WHERE `lentry_id` IN
                                                (".$entry_ids_string.")
                                                AND `objective_id` = ".$db->qstr($required_objective["objective_id"])."
                                                GROUP BY `objective_id`";
                                    $recorded = $db->GetOne($query);
                                    if ($recorded) {
                                        if ($required_objective["required"] > $recorded) {
                                            $objective_ids_string .= (isset($objective_ids_string) && $objective_ids_string ? "," : "").$db->qstr($required_objective["objective_id"]);
                                        }
                                    } else {
                                        $objective_ids_string .= (isset($objective_ids_string) && $objective_ids_string ? "," : "").$db->qstr($required_objective["objective_id"]);
                                    }
                                }
                            }

                            $query = "SELECT `lprocedure_id`, `lpprocedure_id`, `rotation_id`, MAX(`number_required`) AS `required`
                                        FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures`
                                        WHERE `rotation_id` IN (".$past_rotations.")
                                        AND `grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                        AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
                                        GROUP BY `lprocedure_id`";
                            $required_procedures = $db->GetAll($query);
                            if ($required_procedures) {
                                $query = "SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
                                            WHERE `entry_active` = '1' 
                                            AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                                $entry_ids = $db->GetAll($query);
                                $entry_ids_string = "";
                                if ($entry_ids) {
                                    foreach ($entry_ids as $entry_id) {
                                        if (!$entry_ids_string) {
                                            $entry_ids_string = $db->qstr($entry_id["lentry_id"]);
                                        } else {
                                            $entry_ids_string .= ", " . $db->qstr($entry_id["lentry_id"]);
                                        }
                                    }
                                }

                                foreach ($required_procedures as $required_procedure) {
                                    $query = "SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
                                                WHERE `entry_active` = '1' 
                                                ".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND `llocation_id` IN (
                                                    SELECT c.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
                                                    JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
                                                    ON a.`lmobjective_id` = b.`lmobjective_id`
                                                    JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
                                                    ON b.`lltype_id` = c.`lltype_id`
                                                    JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS d
                                                    ON c.`llocation_id` = d.`llocation_id`
                                                    WHERE a.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"])."
                                                    AND a.`rotation_id` = ".$db->qstr($required_procedure["rotation_id"])."
                                                    AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                                    AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
                                                )" : "")."
                                                AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                                    $entry_ids = $db->GetAll($query);
                                    $entry_ids_string = "";
                                    if ($entry_ids) {
                                        foreach ($entry_ids as $entry_id) {
                                            if (!$entry_ids_string) {
                                                $entry_ids_string = $db->qstr($entry_id["lentry_id"]);
                                            } else {
                                                $entry_ids_string .= ", ".$db->qstr($entry_id["lentry_id"]);
                                            }
                                        }
                                    }

                                    $procedures_required = $required_procedure["required"];

                                    $query = "SELECT COUNT(`lprocedure_id`) AS `recorded`
                                                FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures`
                                                WHERE `lentry_id` IN
                                                (".$entry_ids_string.")
                                                AND `lprocedure_id` = ".$db->qstr($required_procedure["lprocedure_id"])."
                                                GROUP BY `lprocedure_id`";
                                    $recorded = $db->GetOne($query);
                                    if ($recorded) {
                                        if ($required_procedure["required"] > $recorded) {
                                            if (isset($procedure_ids) && $procedure_ids) {
                                                $procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
                                            } else {
                                                $procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
                                            }
                                        }
                                    } else {
                                        if (isset($procedure_ids) && $procedure_ids) {
                                            $procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
                                        } else {
                                            $procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($PROCESSED["event_id"]) && $PROCESSED["event_id"]) {
                            $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` 
                                        WHERE `rotation_id` = (SELECT `rotation_id` 
                                        FROM `".CLERKSHIP_DATABASE."`.`events` 
                                        WHERE `event_id` = ".$db->qstr($PROCESSED["event_id"]).")";
                            $rotation = $db->GetRow($query);
                        } else {
                            $rotation = false;
                        }
                        ?>
                        <label for="objective_display_type_all" class="radio"><input type="radio" name="objective_display_type" id="objective_display_type_all" onclick="showAllObjectives()"<?php echo ((!$rotation) ? " checked=\"checked\"" : ""); ?> /> Show all clinical presentations.</label>
                        <?php
                        $rotation_id = false;
                        if ($rotation) {
                            $rotation_id = $rotation["rotation_id"];
                            ?>
                            <label for="objective_display_type_rotation" class="radio"><input type="radio" name="objective_display_type" id="objective_display_type_rotation" onclick="showRotationObjectives()" checked="checked" /> Show only clinical presentations for <b id="rotation_title_display"><?php echo $rotation["rotation_title"]; ?></b>.</label>
                            <?php
                        }

                        if (isset($objective_ids) && $objective_ids) {
                            ?>
                            <label for="objective_display_type_deficient" class="radio"><input type="radio" name="objective_display_type" id="objective_display_type_deficient" onclick="showDeficientObjectives()" /> Show only clinical presentations which are deficient from past rotations.</label>
                            <?php
                        }

                        if ($rotation_id) {
                            echo "<select id=\"rotation_objective_id\" class=\"input-xxlarge space-above\" name=\"rotation_objective_id\" onchange=\"addObjective(this.value, 0)\" style=\"".(!$rotation ? " display: none;" : "")."\">\n";
                            echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Presentation --</option>\n";
                            $query = "SELECT DISTINCT a.* FROM `global_lu_objectives` AS a
                                            JOIN `objective_organisation` AS b
                                            ON a.`objective_id` = b.`objective_id`
                                            WHERE a.`objective_parent` = '200' 
                                            AND a.`objective_active` = '1'
                                            AND 
                                            (
                                                a.`objective_id` IN 
                                                (
                                                    SELECT `objective_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` 
                                                    WHERE `rotation_id` = ".$db->qstr($rotation_id)." 
                                                )
                                            )
                                            AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                            ORDER BY a.`objective_name`";
                            $results = $db->GetAll($query);
                            if ($results) {
                                foreach ($results as $result) {
                                    $locations = false;
                                    $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
                                                JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
                                                ON a.`lmobjective_id` = b.`lmobjective_id`
                                                JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
                                                ON b.`lltype_id` = c.`lltype_id`
                                                WHERE a.`objective_id` = ".$db->qstr($result["objective_id"])."
                                                AND a.`rotation_id` = ".$db->qstr($rotation_id)." 
                                                AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                                AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
                                                GROUP BY c.`lltype_id`";
                                    $locations = $db->GetAll($query);
                                    if (!$locations) {
                                        $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                                        $locations = $db->GetAll($query);
                                    }

                                    $location_string = "";
                                    foreach ($locations as $location) {
                                        $location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
                                    }

                                    echo "<option id=\"rotation-obj-item-".$result["objective_id"]."\" value=\"".(int) $result["objective_id"]."\">".html_encode($result["objective_name"]." (".$location_string.")")."</option>\n";
                                    $query = "SELECT a.* FROM `global_lu_objectives` AS a
                                                JOIN `objective_organisation` AS b
                                                ON a.`objective_id` = b.`objective_id`
                                                WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
                                                AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                                AND a.`objective_active` = '1'";
                                    $children = $db->GetAll($query);
                                    if ($children) {
                                        foreach ($children as $child) {
                                            echo "<option id=\"rotation-obj-item-".$child["objective_id"]."\" value=\"".(int) $child["objective_id"]."\">".html_encode($child["objective_name"])."</option>\n";
                                        }
                                    }
                                }
                            }
                            echo "</select>\n";
                        }

                        echo "<select id=\"deficient_objective_id\" class=\"input-xxlarge space-above\" name=\"deficient_objective_id\" onchange=\"addObjective(this.value, 0)\" style=\"display: none;\">\n";
                        echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Presentation --</option>\n";

                        $query = "SELECT DISTINCT a.* FROM `global_lu_objectives` AS a
                                        JOIN `objective_organisation` AS b
                                        ON a.`objective_id` = b.`objective_id`
                                        WHERE a.`objective_parent` = '200' 
                                        AND a.`objective_active` = '1'
                                        AND 
                                        (
                                            a.`objective_id` IN (".$objective_ids.")
                                        )
                                        AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                        ORDER BY a.`objective_name`";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach ($results as $result) {
                                echo "<option id=\"deficient-obj-item-".$result["objective_id"]."\" value=\"".(int) $result["objective_id"]."\">".html_encode($result["objective_name"])."</option>\n";
                                $query = "SELECT * FROM `global_lu_objectives` AS a
                                            JOIN `objective_organisation` AS b
                                            ON a.`objective_id` = b.`objective_id`
                                            WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
                                            AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                            AND a.`objective_active` = '1'";
                                $children = $db->GetAll($query);
                                if ($children) {
                                    foreach ($children as $child) {
                                        echo "<option id=\"deficient-obj-item-".$child["objective_id"]."\" value=\"".(int) $child["objective_id"]."\">".html_encode($child["objective_name"])."</option>\n";
                                    }
                                }
                            }
                        }
                        echo "</select>\n";
                        echo "<select id=\"all_objective_id\" class=\"input-xxlarge space-above\" name=\"all_objective_id\" onchange=\"addObjective(this.value, 0)\" style=\"".($rotation ? " display: none;" : "")."\">\n";
                        echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Presentation --</option>\n";
                        $query = "SELECT a.* FROM `global_lu_objectives` AS a
                                        JOIN `objective_organisation` AS b
                                        ON a.`objective_id` = b.`objective_id`
                                        WHERE a.`objective_parent` = '200' 
                                        AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                        AND a.`objective_active` = '1'
                                        ORDER BY a.`objective_name`";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach ($results as $result) {
                                echo "<option id=\"all-obj-item-".$result["objective_id"]."\" value=\"".(int) $result["objective_id"]."\">".html_encode($result["objective_name"])."</option>\n";
                                $query = "SELECT a.* FROM `global_lu_objectives` AS a
                                            JOIN `objective_organisation` AS b
                                            ON a.`objective_id` = b.`objective_id`
                                            WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
                                            AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                            AND a.`objective_active` = '1'";
                                $children = $db->GetAll($query);
                                if ($children) {
                                    foreach ($children as $child) {
                                        echo "<option id=\"all-obj-item-".$child["objective_id"]."\" value=\"".(int) $child["objective_id"]."\">".html_encode($child["objective_name"])."</option>\n";
                                    }
                                }
                            }
                        }
                        echo "</select>\n";
                        ?>
                    </div>
                </div>

                <div class="control-group" id="objective-container"<?php echo (!isset($PROCESSED_OBJECTIVES) || !@count($PROCESSED_OBJECTIVES) ? " style=\"display: none;\"" : ""); ?>>
                    <div class="controls">
                        <div id="objective-list" class="space-left">
                            <?php 
                            if (isset($PROCESSED_OBJECTIVES) && count($PROCESSED_OBJECTIVES)) { 
                                foreach ($PROCESSED_OBJECTIVES as $objective_id) {
                                    $query = "SELECT a.* FROM `global_lu_objectives` AS a
                                                JOIN `objective_organisation` AS b
                                                WHERE a.`objective_id` = ".$db->qstr($objective_id["objective_id"])." 
                                                AND a.`objective_active` = '1'
                                                AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
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
                                        ?>
                                        <div class="row-fluid" id="objective_<?php echo $objective_id["objective_id"]; ?>_row">
                                            <label class="checkbox" for="delete_objective_<?php echo $objective_id["objective_id"]; ?>">
                                                <input type="checkbox" class="objective_delete" value="<?php echo $objective_id["objective_id"]; ?>" />
                                                <?php echo html_encode($objective["objective_name"]); ?>
                                            </label>
                                            <input type="hidden" name="objectives[<?php echo $objective_id["objective_id"]; ?>]" value="<?php echo $objective_id["objective_id"]; ?>" />
                                        </div>
                                        <?php
                                    }
                                }
                            } 
                            ?>
                            <input type="button" class="btn btn-danger space-above space-left" value="Remove Selected" onclick="removeObjectives()"/>
                       </div>
                    </div>
                </div>
                <br>

                <div class="control-group">
                    <label for="procedure_id" class="form-required control-label">Clinical Tasks</label>
                    <div class="controls">
                        <input type="hidden" id="default_procedure_involvement" value="Assisted" />
                        <?php
                        // Fetch any deficient procedures.
                        $deficient_procedures = false;
                        if (!empty($procedure_ids)) {
                            $query = "SELECT DISTINCT a.*
                                    FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
                                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
                                    ON b.`lprocedure_id` = a.`lprocedure_id`
                                    WHERE a.`lprocedure_id` IN (".$procedure_ids.")
                                    AND b.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()));
                            $deficient_procedures = $db->GetAll($query);
                        }
                        // Fetch any preferred procedures.
                        $preferred_procedures = false;
                        if (!empty($rotation["rotation_id"])) {
                            $query = "SELECT DISTINCT a.* FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
                                        ON b.`lprocedure_id` = a.`lprocedure_id`
                                        WHERE b.`rotation_id` = ".$db->qstr($rotation["rotation_id"])."
                                        AND b.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()));
                            $preferred_procedures = $db->GetAll($query);
                        }
                        ?>
                        <label for="procedure_display_type_all" class="radio"><input type="radio" name="procedure_display_type" id="procedure_display_type_all" onclick="showAllProcedures()"<?php echo ((!$rotation || !$preferred_procedures) ? " checked=\"checked\"" : ""); ?> /> Show all clinical tasks</label>
                        <?php
                        if ($rotation && $preferred_procedures) {
                            ?>
                            <label for="procedure_display_type_rotation" class="radio"><input type="radio" name="procedure_display_type" id="procedure_display_type_rotation" onclick="showRotationProcedures()" checked="checked" /> Show only clinical tasks for <b id="rotation_title_display"><?php echo $rotation["rotation_title"]; ?></b></label>
                            <?php
                        }

                        if ($deficient_procedures) {
                            ?>
                            <label for="procedure_display_type_deficient" class="radio"><input type="radio" name="procedure_display_type" id="procedure_display_type_deficient" onclick="showDeficientProcedures()" /> Show only clinical tasks which are deficient from past rotations.</label>
                            <?php
                        }

                        echo "<select id=\"rotation_procedure_id\" class=\"input-xxlarge space-above\" name=\"rotation_procedure_id\" onchange=\"addProcedure(this.value, 0)\" style=\"".(!isset($preferred_procedures) || !$preferred_procedures ? " display: none;" : "")."\">\n";
                        echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Tasks --</option>\n";
                        if ($preferred_procedures) {
                            foreach ($preferred_procedures as $result) {
                                $locations = false;
                                $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
                                            JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
                                            ON a.`lpprocedure_id` = b.`lpprocedure_id`
                                            JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
                                            ON b.`lltype_id` = c.`lltype_id`
                                            WHERE a.`lprocedure_id` = ".$db->qstr($result["lprocedure_id"])."
                                            AND a.`rotation_id` = ".$db->qstr($rotation_id)." 
                                            AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                            AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
                                            GROUP BY c.`lltype_id`";
                                $locations = $db->GetAll($query);
                                if (!$locations) {
                                    $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                                    $locations = $db->GetAll($query);
                                }
                                $location_string = "";
                                foreach ($locations as $location) {
                                    $location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
                                }
                                echo "<option id=\"rotation-proc-item-".$result["lprocedure_id"]."\" value=\"".(int) $result["lprocedure_id"]."\">".html_encode($result["procedure"]." (".$location_string.")")."</option>\n";
                            }
                        }
                        echo "</select>\n";

                        echo "<select id=\"deficient_procedure_id\" class=\"input-xxlarge space-above\" name=\"deficient_procedure_id\" onchange=\"addProcedure(this.value, 0)\" style=\"display: none;\">\n";
                        echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Tasks --</option>\n";
                        if ($deficient_procedures) {
                            foreach ($deficient_procedures as $result) {
                                echo "<option id=\"deficient-proc-item-".$result["lprocedure_id"]."\" value=\"".(int) $result["lprocedure_id"]."\">".html_encode($result["procedure"])."</option>\n";
                            }
                        }
                        echo "</select>\n";

                        $query = "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
                                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
                                    ON b.`lprocedure_id` = a.`lprocedure_id`
                                    WHERE b.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                    GROUP BY a.`lprocedure_id`
                                    ORDER BY a.`procedure`";
                        $results = $db->GetAll($query);
                        echo "<select id=\"all_procedure_id\" class=\"input-xxlarge space-above\" style=\"".(isset($preferred_procedures) && $preferred_procedures ? " display: none;" : "")."\" name=\"all_procedure_id\" onchange=\"addProcedure(this.value, 0)\">\n";
                        echo "<option value=\"0\"".((!isset($PROCESSED["procedure_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Tasks --</option>\n";
                        if ($results) {
                            foreach ($results as $result) {
                                echo "<option id=\"all-proc-item-".$result["lprocedure_id"]."\" value=\"".(int) $result["lprocedure_id"]."\">".html_encode($result["procedure"])."</option>\n";
                            }
                        }
                        echo "</select>\n";
                        ?>
                    </div>
                </div>

                <div class="control-group" id="procedure-container"<?php echo (!isset($PROCESSED_PROCEDURES) || !@count($PROCESSED_PROCEDURES) ? " style=\"display: none;\"" : ""); ?>>
                    <div class="controls">
                        <div id="procedure-list" class="space-left">
                            <?php 
                            if (isset($PROCESSED_PROCEDURES) && count($PROCESSED_PROCEDURES)) { 
                                foreach ($PROCESSED_PROCEDURES as $procedure_id) {
                                    $query = "SELECT *
                                              FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures`
                                              WHERE `lprocedure_id` = ".$db->qstr($procedure_id["lprocedure_id"])."
                                              ORDER BY `procedure`";
                                    $procedure = $db->GetRow($query);
                                    if ($procedure) {
                                        ?>
                                        <div class="row-fluid" id="procedure_<?php echo $procedure_id["lprocedure_id"]; ?>_row">
                                            <div class="span5">
                                                <label class="checkbox space-above">
                                                    <input type="checkbox" class="procedure_delete" value="<?php echo $procedure_id["lprocedure_id"]; ?>" />
                                                    <?php echo html_encode($procedure["procedure"]); ?>
                                                </label>
                                            </div>
                                            <div class="span7 space-below">
                                                <select name="proc_participation_level[<?php echo $procedure_id["lprocedure_id"]; ?>]" id="proc_<?php echo $procedure_id["lprocedure_id"]; ?>_participation_level" class="input-large">
                                                    <option value="1" <?php echo ($procedure_id["level"] == 1 || (!$procedure_id["level"]) ? "selected=\"selected\"" : ""); ?>>Observed</option>
                                                    <option value="2" <?php echo ($procedure_id["level"] == 2 ? "selected=\"selected\"" : ""); ?>>Performed with help</option>
                                                    <option value="3" <?php echo ($procedure_id["level"] == 3 ? "selected=\"selected\"" : ""); ?>>Performed independently</option>
                                                </select>
                                            </div>
                                            <input type="hidden" name="procedures[<?php echo $procedure_id["lprocedure_id"]; ?>]" value="<?php echo $procedure_id["lprocedure_id"]; ?>" />
                                        </div>
                                        <?php
                                    }
                                }
                            } 
                            ?>
                            <input type="button" class="btn btn-danger space-left" value="Remove Selected" onclick="removeProcedures()" />
                        </div>
                    </div>
                </div>
                <br>

                <div class="control-group">
                    <label for="reflection" class="form-required control-label">Personal Reflection</label>
                    <div class="controls">
                        <textarea id="reflection" name="reflection" class="expandable input-xxlarge"><?php echo ((isset($PROCESSED["reflection"])) ? html_encode($PROCESSED["reflection"]) : ""); ?></textarea>
                    </div>
                </div>

                <div class="control-group">
                    <label for="comments" class="form-nrequired control-label">Additional Comments</label>
                    <div class="controls">
                        <textarea id="comments" name="comments" class="expandable input-xxlarge"><?php echo ((isset($PROCESSED["comments"])) ? html_encode($PROCESSED["comments"]) : ""); ?></textarea>
                    </div>
                </div>

                <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship/logbook'" />

                <input type="submit" class="btn btn-primary pull-right" value="Submit" />
            </form>
            <?php
        break;
    }
}
