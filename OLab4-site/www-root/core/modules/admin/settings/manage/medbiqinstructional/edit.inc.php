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

if (!defined("PARENT_INCLUDED") || !defined("IN_MEDBIQINSTRUCTIONAL")) {
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
    <h1>Edit Medbiquitous Instructional Method</h1>
    <?php
    
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/medbiqinstructional?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID, "title" => "Edit Medbiquitous Instructional Method");
	
	if (isset($_GET["instructional_method_id"]) && ($instructional_method_id = clean_input($_GET["instructional_method_id"], array("notags", "trim")))) {
		$PROCESSED["instructional_method_id"] = $instructional_method_id;
	}
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "instructional_method" / Instructional Method
			 */
			if (isset($_POST["instructional_method"]) && ($instructional_method = clean_input($_POST["instructional_method"], array("htmlbrackets", "trim")))) {
				$PROCESSED["instructional_method"] = $instructional_method;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Instructional Method</strong> is a required field.";
			}

			/**
			 * Non-required field "instructional_method_description" / Description
			 */
			if (isset($_POST["instructional_method_description"]) && ($instructional_method_description = clean_input($_POST["instructional_method_description"], array("htmlbrackets", "trim")))) {
				$PROCESSED["instructional_method_description"] = $instructional_method_description;
			} else {
				$PROCESSED["instructional_method_description"] = "";
			}
            
            if (isset($_POST["code"]) && $tmp_input = clean_input($_POST["code"], array("trim", "striptags"))) {
                $PROCESSED["code"] = $tmp_input;
            } else {
                add_error("<strong>Instructional Code</strong> is a required field.");
            }
			
			/**
			 * Non-required field "fk_eventtype_id" / Mapped Event Types
			 */
			if (isset($_POST["fk_eventtype_id"]) && is_array($_POST["fk_eventtype_id"])) {
                foreach ($_POST["fk_eventtype_id"] as $event_type_id) {
                    if ($tmp_input = clean_input($event_type_id, array("trim", "int"))) {
                        $PROCESSED["event_types"][] = $tmp_input;
                    }
                }
			}
			
			if (!$ERROR) {
				$PROCESSED["updated_date"]	= time();
				$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
                $PROCESSED["active"] = 1;
                
                $medbiq_instructional_method = new Models_MedbiqInstructionalMethod($PROCESSED);
				
				if ($medbiq_instructional_method->update()) {
                    $mapped_event_types = Models_Event_MapEventsEventType::fetchAllByInstructionalMethodID($PROCESSED["instructional_method_id"]);
                    if ($mapped_event_types) {
                        foreach ($mapped_event_types as $mapped_event_type) {
                            $mapped_event_type->delete();
                        }
                    }
                    
                    if (isset($PROCESSED["event_types"]) && is_array($PROCESSED["event_types"])) {
                        // Insert keys into mapped table
                        $MAPPED_PROCESSED = array();
                        $MAPPED_PROCESSED["fk_instructional_method_id"] = $instructional_method_id;
                        $MAPPED_PROCESSED["updated_date"] = time();
                        $MAPPED_PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                        foreach($PROCESSED["event_types"] as $event_type_id) {
                            $mapped_event_types = Models_Event_MapEventsEventType::fetchAllByEventTypeID($event_type_id);
                            if ($mapped_event_types) {
                                foreach ($mapped_event_types as $event_type) {
                                    if (!$event_type->delete()) {
                                        //add_error("There was a problem mapping event types. The system administrator was informed of this error; please try again later.");
                                        application_log("error", "There was an error editing event mapping within medbiquitous instructional resources. Database said: ".$db->ErrorMsg());
                                    }
                                }
                            }
                            
                            $MAPPED_PROCESSED["fk_eventtype_id"] = (int) $event_type_id;
                            $mapped_event_type = new Models_Event_MapEventsEventType($MAPPED_PROCESSED);
                            if(!$mapped_event_type->insert()) {
                                add_error("There was a problem inserting this instructional method into the system. The system administrator was informed of this error; please try again later.");
                                application_log("error", "There was an error inserting an instructional method. Database said: ".$db->ErrorMsg());
                            }
                        }
                    }
                    
					if (!$ERROR) {
						$url = ENTRADA_URL . "/admin/settings/manage/medbiqinstructional?org=".$ORGANISATION_ID;
						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully edited <strong>".html_decode($PROCESSED["instructional_method"])."</strong> in the system.<br /><br />You will now be redirected to the Medbiquitous Instructional Methods index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
	
						application_log("success", "Edited Medbiquitous Instructional Method [".$instructional_method_id."] in the system.");
					} else {
						$ERROR++;
						$ERRORSTR[] = "There was a problem mapping event types. The system administrator was informed of this error; please try again later.";
	
						application_log("error", "There was an error editing event mapping within medbiquitous instructional resources. Database said: ".$db->ErrorMsg());
					}
				} else {				
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this Medbiquitous Instructional Method into the system. The system administrator was informed of this error; please try again later.";

					application_log("error", "There was an error inserting an Medbiquitous Instructional Method. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
                $medbiq_instructional_method = new Models_MedbiqInstructionalMethod($PROCESSED);
			}
		break;
		case 1 :
		default :
            $medbiq_instructional_method = Models_MedbiqInstructionalMethod::get($PROCESSED["instructional_method_id"]);
			$mapped_event_types = $medbiq_instructional_method->getMappedEventTypes();
            if ($mapped_event_types) {
                foreach($mapped_event_types as $mapped_event_type) {
					$PROCESSED["event_types"][] = $mapped_event_type->getEventTypeID();
				}
            }
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
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
			if ($ERROR) {
				echo display_error();
			}
            ?>
            <form action="<?php echo ENTRADA_URL."/admin/settings/manage/medbiqinstructional"."?".replace_query(array("action" => "edit", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post" class="form-horizontal">
                <div class="control-group">
                    <label for="instructional_method" class="control-label form-required">Instructional Method:</label>
                    <div class="controls">
                        <input type="text" id="instructional_method" name="instructional_method" value="<?php echo (isset($medbiq_instructional_method) ? html_decode($medbiq_instructional_method->getInstructionalMethod()): ""); ?>" />
                    </div>
                </div>
                <div class="control-group">
                    <label for="instructional_method_description" class="control-label form-nrequired">Description:</label>
                    <div class="controls">
                        <textarea id="instructional_method_description" name="instructional_method_description" style="width: 98%; height: 200px"><?php echo (isset($medbiq_instructional_method) ? html_decode($medbiq_instructional_method->getInstructionalMethodDescription()) : ""); ?></textarea>
                    </div>
                </div>
                <div class="control-group">
                    <label for="code" class="control-label form-required">Instructional Code:</label>
                    <div class="controls">
                        <input type="text" class="input-small" id="code" name="code" value="<?php echo (isset($medbiq_instructional_method) ? html_encode($medbiq_instructional_method->getCode()) : ""); ?>" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-nrequired">Mapped <?php echo $translate->_("Learning Event Types"); ?>:</label>
                    <div class="controls">
                    <?php
                    $event_types = Models_EventType::fetchAllByOrganisationID($ORGANISATION_ID);
                    if ($event_types) {
                        foreach($event_types as $eventtype) { ?>
                            <div class="checkbox">
                                <label for="event_type_<?php echo $eventtype->getID(); ?>">
                                    <input type="checkbox" id="event_type_<?php echo $eventtype->getID(); ?>" name="fk_eventtype_id[]" value="<?php echo $eventtype->getID(); ?>" <?php echo (isset($PROCESSED["event_types"]) && in_array($eventtype->getID(), $PROCESSED["event_types"]) ? "checked=\"checked\"" : ""); ?> />
                                    <?php echo $eventtype->getEventTypeTitle(); ?>
                                </label>
                            </div>
                        <?php    
                        }
                    }
                    ?>
                    </div>
                </div>
                <div class="control-group">
                    <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/medbiqinstructional?org=<?php echo $ORGANISATION_ID;?>'" />
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />                           
                </div>
            </form>
			<?php
		break;
	}
}
