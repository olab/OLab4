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
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update",false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    ?>
    <h1>Edit <?php echo $translate->_("Event Type"); ?></h1>
    <?php
    
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/eventtypes?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID, "title" => "Edit " . $translate->_("Event Type"));
    
	if (isset($_GET["type_id"]) && ($type = clean_input($_GET["type_id"], array("notags", "trim")))) {
		$PROCESSED["eventtype_id"] = $type;
	} else {
        add_error("No " . $translate->_("Event Type") . " found with the specified ID.");
    }
    
    $event_type = Models_EventType::get($PROCESSED["eventtype_id"]);
    
    if ($event_type) {
        $mapped_medbiq_instructional_method = $event_type->getMappedMedbiqInstructionalMethod();
        if ($mapped_medbiq_instructional_method) {
            $PROCESSED["medbiq_instructional_method_id"] = $mapped_medbiq_instructional_method->getInstructionalMethodID();
        }
    }
    
    
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
            
            if (isset($_POST["medbiq_instructional_method_id"]) && ($tmp_input = clean_input($_POST["medbiq_instructional_method_id"], array("trim", "int")))) {
                $PROCESSED["medbiq_instructional_method_id"] = $tmp_input;
            }
			
			if (!$ERROR) {
                
                $params = array("eventtype_title" => $PROCESSED["eventtype_title"], "eventtype_description" => $PROCESSED["eventtype_description"], "medbiq_instructional_method_id" => $PROCESSED["medbiq_instructional_method_id"], "eventtype_order" => "0", "updated_date"=> time(),"updated_by" => $ENTRADA_USER->getID(), "eventtype_active" => 1);
                
                // Check to see if the eventtype_id is used in more than one organisation
                $eventtype_organisation = Models_Event_EventTypeOrganisation::get($PROCESSED["eventtype_id"]);
                // if the eventtype_id is used in multiple organisations we are going to create a new entry and remove the old ones
                $eventtype = new Models_EventType($params);
				if ($eventtype_organisation) {
                    if ($eventtype->insert()) {
                        
                        // if creating a new eventtype we will need to delete the old one, then update all of the previously fetched events to the new one.
						$eventtype_id = $eventtype->getID();
                        $eto = new Models_Event_EventTypeOrganisation(array("eventtype_id" => $PROCESSED["eventtype_id"], "organisation_id" => $ORGANISATION_ID));
						
                        if (!$eto->delete()) {
                            application_log("error", "An error occured while attempting to delete the organisation eventtype " . $eto->getEventTypeID() . " DB said: " . $db->ErrorMsg());
                            add_error("An error while attempting to delete the organisation " . $translate->_("Event Type"));
                        } else {
                            $eto->setEventTypeID($eventtype_id);
                            if(!$eto->insert()) {
                                application_log("error", "An error occured while attempting to insert the organisation eventtype " . $eto->getEventTypeID() . " DB said: " . $db->ErrorMsg());
                                add_error("An error while attempting to insert the organisation " . $translate->_("Event Type"));
                            }
                        }
                        
                        $mapped_instructional_method = Models_Event_MapEventsEventType::fetchRowByEventTypeID($PROCESSED["eventtype_id"]);
                        if ($mapped_instructional_method) {
                            $mapped_instructional_method->delete();
                        }
                        
                        if (isset($PROCESSED["medbiq_instructional_method_id"])) {
                            $mapped_method = new Models_Event_MapEventsEventType(array("fk_instructional_method_id" => $PROCESSED["medbiq_instructional_method_id"], "fk_eventtype_id" => $eventtype_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()));
                            if (!$mapped_method->insert()) {
                                application_log("error", "An error occured while attempting to insert the medbiq instructional method " . $mapped_method->getID() . " DB said: " . $db->ErrorMsg());
                                add_error("An error occured while attempting to save the selected medbiq instructional method " . $db->ErrorMsg());
                            }
                        }
                        
                        
                        // we need a list of event_ids that are associated with this eventtype_id
                        $query = "	SELECT b.`event_id`, c.*
                                    FROM `courses` AS a
                                    LEFT JOIN `events` AS b
                                    ON a.`course_id` = b.`course_id`
                                    LEFT JOIN `event_eventtypes` AS c
                                    ON b.`event_id` = c.`event_id`
                                    WHERE a.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
                                    AND c.`eventtype_id` = ".$db->qstr($PROCESSED["eventtype_id"]);
                        $events_list = $db->GetAssoc($query);
                        
                        if ($events_list) {
                            foreach ($events_list as $event_id => $event) {
                                $event_eventtype = new Models_Event_EventType(array("eeventtype_id" => $event["eeventtype_id"], "event_id" => $event_id, "eventtype_id" => $eventtype_id, "duration" => $event["duration"]));
                                $event_eventtype->update();
                            }
                        }
                    } else {
                        application_log("error", "An error occured while attempting to insert the eventtype " . $PROCESSED["eventtype_id"] . " DB said: " . $db->ErrorMsg());
                        add_error("An error while attempting to insert the " . $translate->_("Event Type"));
                    }
				} else {
                    $eventtype->update();
				}
				
				if (!$ERROR) {	
					$url = ENTRADA_URL . "/admin/settings/manage/eventtypes?org=".$ORGANISATION_ID;
					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["eventtype_title"])."</strong> to the system.<br /><br />You will now be redirected to the " . $translate->_("Learning Event Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
					application_log("success", "New Event Type [".$PROCESSED["eventtype_id"]."] added to the system.");
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
            
            if (isset($event_type) && $event_type) { ?>
            <form class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/settings/manage/eventtypes"."?".replace_query(array("action" => "edit", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post">
                <div class="control-group">
                    <label for="eventtype_title" class="form-required control-label"><?php echo $translate->_("Event Type"); ?> Name:</label>
                    <div class="controls">
                        <input type="text" id="eventtype_title" name="eventtype_title" value="<?php echo ($event_type ? html_encode($event_type->getEventTypeTitle()) : ""); ?>" maxlength="60" />
                    </div>
                </div>
                <div class="control-group">
                    <label for="eventtype_description" class="form-nrequired control-label"><?php echo $translate->_("Event Type"); ?> Description: </label>
                    <div class="controls">
                        <textarea id="eventtype_description" name="eventtype_description" style="width: 98%; height: 200px"><?php echo ($event_type ? html_encode($event_type->getEventTypeDescription()): ""); ?></textarea>
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
                                    <input type="radio" name="medbiq_instructional_method_id" id="instructional_method_<?php echo $medbiq_method->getID(); ?>" value="<?php echo $medbiq_method->getID(); ?>" <?php echo (isset($PROCESSED["medbiq_instructional_method_id"]) && $PROCESSED["medbiq_instructional_method_id"] == $medbiq_method->getID() ? "checked=\"checked\"" : ""); ?>>
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
            }
		break;
	}

}
