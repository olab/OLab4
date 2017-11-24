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
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    ?>
    <h1>Add <?php echo $translate->_("Event Type"); ?></h1>
    <?php

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/eventtypes?".replace_query(array("section" => "add"))."&amp;org=".$ORGANISATION_ID, "title" => "Add " . $translate->_("Event Type"));
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "objective_name" / Objective Name
			 */
			if (isset($_POST["eventtype_title"]) && ($eventtype_title = clean_input($_POST["eventtype_title"], array("notags", "trim")))) {
				$PROCESSED["eventtype_title"] = $eventtype_title;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>" . $translate->_("Event Type") . " Name</strong> is a required field.";
			}

			/**
			 * Non-required field "objective_description" / Objective Description
			 */
			if (isset($_POST["eventtype_description"]) && ($eventtype_description = clean_input($_POST["eventtype_description"], array("notags", "trim")))) {
				$PROCESSED["eventtype_description"] = $eventtype_description;
			} else {
				$PROCESSED["eventtype_description"] = "";
			}
            
            if (isset($_POST["medbiq_instructional_method_id"]) && $tmp_input = clean_input($_POST["medbiq_instructional_method_id"], array("trim, int"))) {
                $PROCESSED["medbiq_instructional_method_id"] = $tmp_input;
            }
            
			$PROCESSED["eventtype_active"] = 1;
			$PROCESSED["eventtype_order"] = 0;
			
			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                
                $event_type = new Models_EventType($PROCESSED);
                
				if ($event_type->insert()) {
					if ($EVENTTYPE_ID = $db->Insert_Id()) {
						$params = array("eventtype_id" => $EVENTTYPE_ID, "organisation_id" => $ORGANISATION_ID);
                        $event_type_organisation = new Models_Event_EventTypeOrganisation($params);
                        
                        if (isset($PROCESSED["medbiq_instructional_method_id"])) {
                            $mapped_method = new Models_Event_MapEventsEventType(array("fk_instructional_method_id" => $PROCESSED["medbiq_instructional_method_id"], "fk_eventtype_id" => $EVENTTYPE_ID, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()));
                            if (!$mapped_method->insert()) {
                                application_log("error", "An error occured while attempting to insert the medbiq instructional method " . $mapped_method->getID() . " DB said: " . $db->ErrorMsg());
                                add_error("An error occured while attempting to save the selected medbiq instructional method");
                            }
                        }
						
						if(!$event_type_organisation->insert()) {
							application_log("error", "An error occured while attempting to save the event type DB said: " . $db->ErrorMsg());
                            add_error("An error occured while attempting to save the selected medbiq instructional method");
						}
					}
					else {
						$ERROR++;
						$ERRORSTR[] = "There was a problem inserting this " . $translate->_("Event Type") . " into the system. The system administrator was informed of this error; please try again later.";
						application_log("error", "There was an error inserting an event type. Database said: ".$db->ErrorMsg());
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this event typve into the system. The system administrator was informed of this error; please try again later.";
					application_log("error", "There was an error inserting an event type. Database said: ".$db->ErrorMsg());
				}
                
                if (!$ERROR) {
                    $url = ENTRADA_URL . "/admin/settings/manage/eventtypes?org=".$ORGANISATION_ID;
                    $SUCCESS++;
                    $SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["eventtype_title"])."</strong> to the system.<br /><br />You will now be redirected to the " . $translate->_("Learning Event Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                    application_log("success", "New Event Type [".$EVENTTYPE_ID."] added to the system.");
                }
			}

			if ($ERROR) {
				$STEP = 1;
                $event_type = new Models_EventType($PROCESSED);
			}
		break;
		case 1 :
		default :

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
		default:	
			if ($ERROR) {
				echo display_error();
			}

			$HEAD[]	= "	<script type=\"text/javascript\">
						var organisation_id = ".$ORGANISATION_ID.";
						function selectObjective(parent_id, objective_id) {
							new Ajax.Updater('selectObjectiveField', '".ENTRADA_URL."/api/objectives-list.api.php', {parameters: {'pid': parent_id, 'organisation_id': ".$ORGANISATION_ID."}});
							return;
						}
						function selectOrder(parent_id) {
							new Ajax.Updater('selectOrderField', '".ENTRADA_URL."/api/objectives-list.api.php', {parameters: {'type': 'order', 'pid': parent_id, 'organisation_id': ".$ORGANISATION_ID."}});
							return;
						}
						</script>";
			$ONLOAD[] = "selectObjective(".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
			$ONLOAD[] = "selectOrder(".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
						
			?>
			<form action="<?php echo ENTRADA_URL."/admin/settings/manage/eventtypes"."?".replace_query(array("action" => "add", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post" class="form-horizontal">
                <div class="control-group">
                    <label for="eventtype_title" class="form-required control-label"><?php echo $translate->_("Event Type"); ?> Name:</label>
                    <div class="controls">
                        <input type="text" class="input-xlarge" id="eventtype_title" name="eventtype_title" value="<?php echo ($event_type ? html_encode($event_type->getEventTypeTitle()) : ""); ?>" maxlength="60" />
                    </div>
                </div>
                <div class="control-group">
                    <label for="eventtype_description" class="form-nrequired control-label"><?php echo $translate->_("Event Type"); ?> Description:</label>
                    <div class="controls">
                        <textarea id="eventtype_description" name="eventtype_description" style="width: 98%; height: 200px" rows="20" cols="70"><?php echo ($event_type ? html_encode($event_type->getEventTypeDescription()): ""); ?></textarea>
                    </div>
                </div>
                <div class="control-group">
                    <label class="form-nrequired control-label">Medbiquitous Instructional Method:</label>
                    <div class="controls">
                    <?php
                    $medbiq_instructional_methods = Models_MedbiqInstructionalMethod::fetchAllMedbiqInstructionalMethods();
                    if ($medbiq_instructional_methods) {
                        foreach ($medbiq_instructional_methods as $medbiq_method) { ?>
                            <div class="radio">
                                <label for="instructional_method_<?php echo $medbiq_method->getID(); ?>">
                                    <input type="radio" name="medbiq_instructional_method_id" id="instructional_method_<?php echo $medbiq_method->getID(); ?>" value="<?php echo $medbiq_method->getID(); ?>" <?php echo (isset($PROCESSED["medbiq_instructional_method_id"]) && $medbiq_method->getID() == $PROCESSED["medbiq_instructional_method_id"] ? "checked=\"checked\"" : "") ?>>
                                    <?php echo $medbiq_method->getInstructionalMethod(); ?>
                                </label>
                            </div>
                        <?php
                        }
                    }
                    ?>
                    </div>
                </div>
                <div class="control-group">
                    <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/eventtypes?org=<?php echo $ORGANISATION_ID;?>'" />
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />
                </div>
			</form>
			<?php
		break;
	}

}
