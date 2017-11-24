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
 * This file is the add/edit entry form.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ENCOUNTER_TRACKING"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('encounter_tracking', 'read') && !$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ((defined("IN_ENCOUNTER_TRACKING") && IN_ENCOUNTER_TRACKING) && ((defined("EDIT_ENTRY") && EDIT_ENTRY) || (defined("ADD_ENTRY") && ADD_ENTRY))) {
		
        $logbook = new Models_Logbook();
        
		if (isset($entry_id)) {
			$entry = Models_Logbook_Entry::fetchRow($entry_id);
		} else {
			$entry = new Models_Logbook_Entry();
		}

        $user_proxy_id = false;
        if ($ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
            if (isset($_GET["proxy_id"]) && $tmp_input = clean_input($_GET["proxy_id"], array("trim", "int"))) {
                $user_proxy_id = $tmp_input;
            }
        }
		
		switch ($STEP) {
			case 2 :
				
				if (isset($entry_id)) {
					$PROCESSED["lentry_id"] = $entry_id;
				}
		
                $encounter_date = validate_calendar("", "encounter", true);	
                if ((isset($encounter_date)) && ((int) $encounter_date)) {
                    $PROCESSED["encounter_date"]	= (int) $encounter_date;
                } else {
                    add_error("The <strong>Encounter Date</strong> field is required.");
                }
				
                /**
                 * Required field "rotation" / Rotation.
                 */
                if ((isset($_POST["course_id"])) && ($course_id = clean_input($_POST["course_id"], "int"))) {
                    $PROCESSED["course_id"] = $course_id;
                } else {
                    add_error("The <strong>Rotation</strong> field is required.");
                }

                /**
                 * Non-required field "patient" / Patient.
                 */
                if ((isset($_POST["patient_id"])) && ($patient_id = clean_input($_POST["patient_id"], Array("notags","trim")))) {
                    $PROCESSED["patient_info"] = $patient_id;
                }

                /**
                 * Required field "gender" / Gender.
                 */
                if ((isset($_POST["gender"])) && in_array($_POST["gender"], array("u", "m", "f")) && ($gender = clean_input($_POST["gender"], array("trim", "lower")))) {
                    $PROCESSED["gender"] = $gender;
                } else {
                    $PROCESSED["gender"] = "";
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
                 * Required field "reflection" / Reflection on learning experience.
                 */
                if ((isset($_POST["reflection"])) && ($reflection = clean_input($_POST["reflection"], Array("trim", "notags")))) {
                    $PROCESSED["reflection"] = $reflection;
                } else {
                    add_error("The <strong>Reflection on learning experience</strong> field is required. Please include at least a short description of this encounter before continuing.");
                }

                /**
                 * Non-required field "comments" / Comments.
                 */
                if ((isset($_POST["comments"])) && ($comments = clean_input($_POST["comments"], Array("trim", "notags")))) {
                    $PROCESSED["comments"] = $comments;
                } else {
                    $PROCESSED["comments"] = "";
                }
                $PROCESSED["objectives"] = array();
                /**
                 * Non-required field "objectives" / objectives
                 */
                if (is_array($_POST["objectives"]) && count($_POST["objectives"]) && (@count($_POST["objectives"]) == @count($_POST["obj_participation_level"]))) {
                    foreach ($_POST["objectives"] as $objective_id) {
                        $objective = Models_Objective::fetchRow($objective_id);
                        if ($objective) {
                            $objective_array = array();
                            $objective_array["objective"] = $objective;
                            $objective_array["objective_id"] = $objective_id;
                            $objective_array["lentry_id"] = (isset($entry_id) && $entry_id ? $entry_id : NULL);
                            $objective_array["participation_level"] = (isset($_POST["obj_participation_level"][$objective_id]) && $_POST["obj_participation_level"][$objective_id] ? $_POST["obj_participation_level"][$objective_id] : 3);
                            $objective_array["updated_date"] = time();
                            $objective_array["updated_by"] = $ENTRADA_USER->getID();
                            $objective_array["objective_active"] = 1;
                            $entry_objective = new Models_Logbook_Entry_Objective();
                            $PROCESSED["objectives"][$objective_id] = $entry_objective->fromArray($objective_array);
                        }
                    }
                }
                
				if (!$ERROR) {
                    $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
					$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
					$PROCESSED["updated_date"] = time();
					
					if (defined("EDIT_ENTRY") && EDIT_ENTRY) {
						if($entry->fromArray($PROCESSED)->update()) {
							add_success("The entry has successfully been updated. You will be redirected to the logbook index in 5 seconds, or you can <a href=\"".ENTRADA_URL ."/logbook\">click here</a> if you do not wish to wait.");
							add_statistic("encounter_tracking", "update", "lentry_id", $PROCESSED["lentry_id"], $ENTRADA_USER->getID());
						} else {
							add_error("An error occurred when attempting to update a logbook entry [".$PROCESSED["lentry_id"]."], an administrator has been informed, please try again later.");
							application_log("error", "Error occurred when updating logbook entry, DB said: ".$db->ErrorMsg());
						}
					} else {
                        if($entry->fromArray($PROCESSED)->insert()) {
                            add_success("The entry has successfully been updated. You will be redirected to the logbook index in 5 seconds, or you can <a href=\"".ENTRADA_URL ."/logbook\">click here</a> if you do not wish to wait.");
                            add_statistic("encounter_tracking", "insert", "lentry_id", $db->Insert_ID(), $ENTRADA_USER->getID());
                        } else {
                            add_error("An error occurred when attempting to create a new logbook entry, an administrator has been informed, please try again later.");
                            application_log("error", "Error occurred when updating logbook entry, DB said: ".$db->ErrorMsg());
                        }
					}
					
				} else {
					$entry = new Models_Logbook_Entry;
					$entry->fromArray($PROCESSED);
					$STEP = 1;
				}
				
			break;
			default:
			break;
		}
		
		switch ($STEP) {
			case 2 :
				if ($ERROR) {
					echo display_error();
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/logbook\\'', 5000)";
				}
				if ($SUCCESS) {
					echo display_success();
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/logbook\\'', 5000)";
				}
			break;
			case 1 :
			default:
				if ($ERROR) {
					echo display_error();
				}
				?>
                <script type="text/javascript">
                    function addObjective (objective_id, level) {
                        if (!$('objective_'+objective_id+'_row')) {
                            new Ajax.Updater('objective-list', '<?php echo ENTRADA_URL."/logbook?section=api-objective"; ?>', {
                                parameters: 'id='+objective_id+'&level='+level,
                                method:		'post',
                                insertion: 'bottom',
                                onComplete: function () {
                                    if (!$('objective-container').visible()) {
                                        $('objective-container').show();
                                    }
                                    if ($('objective-loading').visible()) {
                                        $('objective-loading').hide();
                                    }
                                },
                                onCreate: function () {
                                    if (!$('objective-loading').visible()) {
                                        $('objective-loading').show();
                                    }
                                }
                            });

                            $('objective_id').selectedIndex = 0;
                            jQuery('#objective-item-'+objective_id).attr('disabled', 'disabled');
                            var objective = jQuery('#objective-item-'+objective_id);
                            jQuery('#objective-item-'+objective_id).remove();
                            jQuery('#objectives-entry').append(objective);
                        }
                    }
	
                    function removeObjectives () {
                        var ids = new Array();
                        $$('.objective_delete').each(
                            function (element) { 
                                if (element.checked) {
                                    ids[element.value] = element.value;
                                }
                            }
                        );
                        ids.each(
                            function (id) {
                                if (id != null) {
                                    $('objective_'+id+'_row').remove(); 
                                    jQuery('#objective-item-'+id).removeAttr('disabled');
                                    var logged = (jQuery('#objective-item-'+id).hasClass('logged'));
                                    var objective = jQuery('#objective-item-'+id);
                                    jQuery('#objective-item-'+id).remove();
                                    jQuery('#objectives-'+(logged ? 'logged' : 'required')).append(objective);
                                }
                            }
                        );
                        var count = 0;
                        $$('.objective_delete').each(
                            function () { 
                                count++;
                            }
                        );
                        if (!count && $('objective-container').visible()) {
                            $('objective-container').hide();
                        }
                    }
                    
                    function loadObjectivesList() {
                        if (jQuery('#course_id').val()) {
                            var objective_ids = '';
                            jQuery('.objective_id').each(function() {
                                objective_ids = objective_ids + (objective_ids ? "," : "") +  jQuery(this).val();
                            });
                            new Ajax.Updater('objectives-list-container', '<?php echo ENTRADA_URL."/logbook?section=api-objectives-select&ajax=1"; ?>', {
                                parameters: '<?php echo (isset($entry_id) && $entry_id ? "entry_id=".((int)$entry_id)."&" : ""); ?>course_id='+jQuery('#course_id').val()+(objective_ids ? "&objective_ids="+objective_ids : ""),
                                method:		'post'
                            });
                        }
                    }
                </script>
				<form action="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=<?php echo isset($entry_id) ? "edit&entry_id=".html_encode($entry_id) : "add" ; ?>&step=2" method="POST" id="entry-form">
					<div class="row-fluid">
                        <h2>Encounter Details</h2>
                    </div>
                    <table>
                    <?php
                        echo generate_calendar("encounter", "Encounter Date", true, ($entry->getEncounterDate() ? $entry->getEncounterDate() : time()), true);
                    ?>
                    </table>
                    <br />
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Rotation</label>
                        <span class="controls span8">
                            <?php 
                            $courses = $logbook->getLoggingCourses();
                            ?>
                            <select id="course_id" name="course_id" style="width: 100%" onchange="loadObjectivesList()">
                            <option value="0">-- Select Rotation --</option>
                            <?php
                            if ($courses) {
                                $found_enrolled = false;
                                $enrolled_course_ids = groups_get_explicitly_enrolled_course_ids($ENTRADA_USER->getID(), true);
                                foreach ($courses as $course) {
                                    echo "<option value=\"".(int) $course["course_id"]."\"".(($entry->getCourseID() == (int)$course["course_id"]) || (!$entry->getCourseID() && in_array($course["course_id"], $enrolled_course_ids) && !$found_enrolled) ? " selected=\"selected\"" : "").">".$course["course_name"]."</option>\n";
                                    if (!$entry->getCourseID() && !$found_enrolled && in_array($course["course_id"], $enrolled_course_ids)) {
                                        $entry->setCourseID($course["course_id"]);
                                        $found_enrolled = true;
                                    }
                                }
                            }
                            ?>
                            </select>
                        </span>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="institution_id" class="form-required span3">Institution</label>
                        <span class="controls span8">
                            <select id="institution_id" name="institution_id" style="width: 100%">
                            <option value="0">-- Select Institution --</option>
                            <?php
                            $institutions = $logbook->getInstitutions();
                            if ($institutions) {
                                foreach ($institutions as $institution) {
                                    echo "<option value=\"".(int) $institution["lsite_id"]."\"".(($entry->getInstitutionID() == $institution["lsite_id"]) || (!$entry->getInstitutionID() && isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"] == $institution["lsite_id"]) ? " selected=\"selected\"" : "").">".$institution["site_name"]."</option>\n";
                                }
                            }
                            ?>
                            </select>
                        </span>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="llocation_id" class="form-required span3">Setting</label>
                        <span class="controls span8">
                            <select id="llocation_id" name="llocation_id" style="width: 100%">
                            <option value="0">-- Select Setting --</option>
                            <?php
                            $locations = $logbook->getLocations();
                            if ($locations) {
                                foreach ($locations as $location) {
                                    echo "<option value=\"".(int) $location["llocation_id"]."\"".($entry->getLocationID() == (int)$location["llocation_id"] ? " selected=\"selected\"" : "").">".$location["location"]."</option>\n";
                                }
                            }
                            ?>
                            </select>
                        </span>
                    </div>
                    <br />
                    <div class="control-group row-fluid">
                        <label for="agerange" class="form-required span3">Patient Age Range</label>
                        <span class="controls span8">
                            <select id="agerange" name="agerange" style="width: 257px">
                            <?php
                            $ageranges = $logbook->getAgeRanges();
                            if ($ageranges) {
                                echo "<option value=\"0\"".((!$entry->getAgeRangeID()) ? " selected=\"selected\"" : "").">-- Select Age Range --</option>\n";
                                foreach ($ageranges as $agerange) {
                                    echo "<option value=\"".(int) $agerange["agerange_id"]."\"".($entry->getAgeRangeID() == (int)$agerange["agerange_id"] ? " selected=\"selected\"" : "").">".$agerange["agerange"]."</option>\n";
                                }
                            }
                            ?>
                            </select>
                        </span>
                    </div>
                    <br />
                    <div class="control-group row-fluid">
                        <label for="gender" class="form-required span3">Patient Gender</label>
                        <span class="controls span8">
                            <input type="radio" name="gender" id="gender_unknown" value="m"<?php echo (((!$entry->getGender()) || ($entry->getGender() == "u")) ? " checked=\"checked\"" : ""); ?> /> <label for="gender_unknown">Unknown</label><br />
                            <input type="radio" name="gender" id="gender_female" value="f"<?php echo (($entry->getGender() == "f") ? " checked=\"checked\"" : ""); ?> /> <label for="gender_female">Female</label><br />
                            <input type="radio" name="gender" id="gender_male" value="m"<?php echo (($entry->getGender() == "m") ? " checked=\"checked\"" : ""); ?> /> <label for="gender_male">Male</label>
                        </span>
                    </div>
                    <br />
                    <div class="control-group row-fluid">
                        <?php
                        $course_id = $entry->getCourseID();
                        require_once("modules/public/logbook/api-objectives-select.inc.php");
                        ?>
                        <div style="display: none;" id="objective-loading" class="content-small">Loading... <img src="<?php echo ENTRADA_URL; ?>/images/indicator.gif" style="vertical-align: middle;" /></div>
                    </div>
                    <div class="control-group row-fluid" id="objective-container"<?php echo (!$entry->getObjectives() || !@count($entry->getObjectives()) ? " style=\"display: none;\"" : ""); ?>>
                        <span class="span3">&nbsp;</span>
                        <span class="span8">
                            <div id="objective-list" class="border-bottom margin-bottom-sm">
                                <?php 
                                if ($entry && @count($entry->getObjectives())) { 
                                    foreach ($entry->getObjectives() as $objective) {
                                    ?>
                                        <div class="row-fluid" id="objective_<?php echo $objective->getObjective()->getID(); ?>_row">
                                            <span class="span1">
                                                <input type="checkbox" class="objective_delete" value="<?php echo $objective->getObjective()->getID(); ?>" />
                                            </span>
                                            <label class="offset1 span5" for="delete_objective_<?php echo $objective->getObjective()->getID(); ?>">
                                                <?php echo $objective->getObjective()->getName(); ?>
                                            </label>
                                            <span class="span5 align-right">
                                                <input type="hidden" class="objective_id" name="objectives[<?php echo $objective->getObjective()->getID(); ?>]" value="<?php echo $objective->getObjective()->getID(); ?>" />
                                                <select name="obj_participation_level[<?php echo $objective->getObjective()->getID(); ?>]" id="obj_<?php echo $objective->getObjective()->getID(); ?>_participation_level" style="width: 150px" class="pull-right">
                                                    <option value="1" <?php echo ($objective->getParticipationLevel() == 1 || (!$objective->getParticipationLevel()) ? "selected=\"selected\"" : ""); ?>>Observed</option>
                                                    <option value="2" <?php echo ($objective->getParticipationLevel() == 2 ? "selected=\"selected\"" : ""); ?>>Performed with help</option>
                                                    <option value="3" <?php echo ($objective->getParticipationLevel() == 3 ? "selected=\"selected\"" : ""); ?>>Performed independently</option>
                                                </select>
                                            </span>
                                        </div>
                                    <?php 
                                    }
                                } 
                                ?>
                            </div>
                            <?php if (!$user_proxy_id): ?>
                            <input type="button" class="btn btn-danger" value="Remove Selected" onclick="removeObjectives()"/>
                            <?php endif ?>
                        </span>
                    </div>
                    <br />
                    <div class="control-group row-fluid">
                            <label for="reflection" class="form-required span3">Reflection on learning experience</label>
                        <span class="controls span8">
                            <textarea id="reflection" name="reflection" class="expandable" style="width: 100%"><?php echo $entry->getReflection(); ?></textarea>
                        </span>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="comments" class="form-nrequired span3">Additional Comments </label>
                        <span class="controls span8">
                            <textarea id="comments" name="comments" class="expandable" style="width: 100%"><?php echo $entry->getComments(); ?></textarea>
                        </span>
                    </div>
                    <div class="row-fluid">
                        <span class="span3">
                            <?php
                            $back_url = $user_proxy_id ? ENTRADA_URL . "/logbook?proxy_id=" . $user_proxy_id : ENTRADA_URL . "/clerkship";
                            $back_text = $user_proxy_id ? "Back" : "Cancel";
                            ?>
                            <input type="button" class="btn" value="<?php echo $back_text; ?>" onclick="window.location='<?php echo $back_url; ?>'" />
                        </span>
                        <span class="span7">
                            &nbsp;
                        </span>
                        <?php if (!$user_proxy_id): ?>
                        <span class="span2">
                            <input type="submit" class="btn btn-primary pull-right" value="Submit" />
                        </span>
                        <?php endif ?>
                    </div>
				</form>
				<?php
			break;
		}
	}
}