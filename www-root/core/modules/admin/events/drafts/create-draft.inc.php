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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} else if (!$ENTRADA_ACL->amIAllowed('event', 'create', false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => "", "title" => "Create Draft Schedule");

	echo "<h1>Create Draft Schedule</h1>";

	switch ($STEP) {
		case 2 :
            $PROCESSED = array(
                "status" => "open",
                "options" => array(),
                "created" => time()
            );

            $i = 0;
			if (isset($_POST["options"]) && is_array($_POST["options"]) && !empty($_POST["options"])) {
			    foreach ($_POST["options"] as $option => $value) {
				    $PROCESSED["options"][$i]["option"] = clean_input($option, "alpha");
				    $PROCESSED["options"][$i]["value"] = 1;

                    $PROCESSED["draft_option_" . $option] = 1; // Used only to recheck checkboxes after a form error.

				    $i++;
				}
			}
			
			if (isset($_POST["draft_name"]) && !empty($_POST["draft_name"])) {
				$PROCESSED["name"] = clean_input($_POST["draft_name"], array("trim"));
			} else {
				add_error("The <strong>Draft Name</strong> is a required field.");
			}
			
			if (isset($_POST["draft_description"]) && !empty($_POST["draft_description"])) {
				$PROCESSED["description"] = clean_input($_POST["draft_description"], array("nohtml"));
			} else {
                $PROCESSED["description"] = "";
            }

            if (isset($_POST["course_array"])) {
                $course_array_decoded = json_decode($_POST["course_array"], true);
                if (is_string($course_array_decoded)) {
                    $course_array_decoded2 = json_decode($course_array_decoded, true);
                    $PROCESSED["course_ids"] = array();
                    if ($course_array_decoded2 && is_array($course_array_decoded2)) {
                        foreach ($course_array_decoded2 as $courses) {
                            $PROCESSED["course_ids"][] = array(
                                "source_course"         => (int)$courses["source_course_id"],
                                "destination_course"    => (int)$courses["destination_course_id"],
                            );
                        }
                    }
                }
			}

			/**
			 * Non-required field "draft_start_date" / Draft Start (validated through validate_calendars function).
			 * Non-required field "draft_finish_date" / Draft Finish (validated through validate_calendars function).
			 */
			$draft_date = Entrada_Utilities::validate_calendars("copy", true, true, false);
			if ((isset($draft_date["start"])) && ((int) $draft_date["start"])) {
				$PROCESSED["draft_start_date"] = (int) $draft_date["start"];
			} else {
				$PROCESSED["draft_start_date"] = 0;
			}

			if ((isset($draft_date["finish"])) && ((int) $draft_date["finish"])) {
				$PROCESSED["draft_finish_date"] = (int) $draft_date["finish"];
			} else {
				$PROCESSED["draft_finish_date"] = 0;
			}
			
			/**
			 * Required field "new_start" / Event Date & Time Start (validated through validate_calendars function).
			 */
			$start_date = Entrada_Utilities::validate_calendars("new", true, false, false);
			if ((isset($start_date["start"])) && ((int) $start_date["start"])) {
				$PROCESSED["new_start_day"] = (int) $start_date["start"];
			}
			
			if (has_error()) {
				$STEP = 1;
			} else {
                $drafts = new Models_Event_Draft($PROCESSED);
                
                if ($drafts->insert() && $draft_id = $db->Insert_ID()) {
                    $creators = array(
                        "draft_id" => $draft_id,
                        "proxy_id" => $ENTRADA_USER->getActiveId()
                    );

                    $draft_creator = new Models_Event_Draft_Creator($creators);
                    if (!$draft_creator->insert()) {
                        application_log("error", "Error when creating draft [".$draft_id."]. Unable to insert to the draft_creators table. Database said: ".$db->ErrorMsg());
                    }

                    if ($PROCESSED["options"]) {
                        // This is just to be safe I am assuming.
                        $query = "DELETE FROM `draft_options` WHERE `draft_id` = ".$db->qstr($draft_id);
                        $db->Execute($query);

                        foreach ($PROCESSED["options"] as $option) {
                            $option["draft_id"] = $draft_id;
                            
                            $draft_options = new Models_Event_Draft_Option($option);
                            if (!$draft_options->insert()) {
                                application_log("error", "Error when saving draft [".$draft_id."] options, DB said: ".$db->ErrorMsg());
                            }
                        }
                    }
				
                    if (isset($PROCESSED["course_ids"]) && is_array($PROCESSED["course_ids"])) {
                        foreach ($PROCESSED["course_ids"] as $course_id) {
                            //checks if the source course and destination course are the same or not
                            if ($course_id["destination_course"] == $course_id["source_course"]) {
                                $course_different = false;
                            } else {
                                $course_different = true;
                            }
                            
                            $e = new Models_Event();
                            
                            $events = $e->fetchAllByCourseIdStartDateFinishDate($course_id["source_course"], $PROCESSED["draft_start_date"], $PROCESSED["draft_finish_date"]);

                            if ($events && is_array($events) && !empty($events)) {
                                $date_diff = (int) ($PROCESSED["new_start_day"] - $events[0]->getEventStart());

                                foreach ($events as $event_object) {
                                    //converts the event object to an array
                                    $event = $event_object->toArray();
                                    $event["draft_id"] = $draft_id;

                                    // adds the offset time to the event year and week, preserves the day of the week
                                    $event["event_start"]  = strtotime((date("o", ($event["event_start"] + $date_diff)))."-W".date("W", ($event["event_start"] + $date_diff))."-".date("w", $event["event_start"])." ".date("H:i",$event["event_start"]));
                                    $event["event_finish"] = strtotime((date("o", ($event["event_finish"] + $date_diff)))."-W".date("W", ($event["event_finish"] + $date_diff))."-".date("w", $event["event_finish"])." ".date("H:i",$event["event_finish"]));

                                    if ($event["objectives_release_date"] != 0) {
                                        $event["objectives_release_date"] = strtotime((date("o", ($event["objectives_release_date"] + $date_diff)))."-W".date("W", ($event["objectives_release_date"] + $date_diff))."-".date("w", $event["objectives_release_date"])." ".date("H:i",$event["objectives_release_date"]));
                                    } else {
                                        $event["objectives_release_date"] = 0;
                                    }

                                    $event["course_id"] = $course_id["destination_course"];

                                    $draft_event = new Models_Event_Draft_Event($event);

                                    if ($draft_event->insert() && $devent_id = $db->Insert_ID()) {
                                        // Copy the audience for the event.//                                    
                                        $audiences = Models_Event_Audience::fetchAllByEventID($draft_event->getEventID());
                                        //check if there are course groups         
                                        
                                        if ($audiences && is_array($audiences)) {
                                            //if course groups then only copy course groups 
                                            $course_exist = Models_Event_Audience::onlyCourse($audiences);
                                            if (!$course_exist) {
                                                foreach ($audiences as $audience_object) {
                                                    $audience = $audience_object->toArray();
                                                    //get new course group value
                                                    if ($course_different && $audience["audience_type"] == "group_id") {
                                                        // check $audience['audience_value']
                                                        $query_group = "    SELECT `group_name` 
                                                                            FROM `course_groups`
                                                                            WHERE `cgroup_id` = " . $db->qstr($audience["audience_value"]);

                                                        $query = "  SELECT `cgroup_id`
                                                                    FROM `course_groups`
                                                                    WHERE `group_name` = ($query_group)
                                                                    AND `course_id` = " . $db->qstr($draft_event->getCourseID());
                                                        $cgroup_id = $db->GetOne($query);
                                                        if ($cgroup_id) {
                                                            $audience["audience_value"] = $cgroup_id;
                                                            //adds time diff for granular scheduling
                                                            if ($audience["custom_time"] == "1") {
                                                                $audience["custom_time_start"] = strtotime((date("o", ($audience["custom_time_start"] + $date_diff)))."-W".date("W", ($audience["custom_time_start"] + $date_diff))."-".date("w", $audience["custom_time_start"])." ".date("H:i",$audience["custom_time_start"]));
                                                                $audience["custom_time_end"]   = strtotime((date("o", ($audience["custom_time_end"] + $date_diff)))."-W".date("W", ($audience["custom_time_end"] + $date_diff))."-".date("w", $audience["custom_time_end"])." ".date("H:i",$audience["custom_time_end"]));
                                                            }
                                                        } else {
                                                            $cgroup_name = $db->GetOne($query_group);
                                                            //create new course group
                                                            $cgroup_new["course_id"] = $draft_event->getCourseID();
                                                            $cgroup_new["group_name"] = $cgroup_name;
                                                            $cgroup_new["group_type"] = "student";
                                                            $cgroup_new["active"] = 1;

                                                            $cgroup = new Models_Course_Group($cgroup_new);
                                                            $cgroup->insert();
                                                        }
                                                    }

                                                    $audience["devent_id"] = $devent_id;

                                                    $draft_audience = new Models_Event_Draft_Event_Audience($audience);
                                                    if (!$draft_audience->insert()) {
                                                        add_error("An error occurred when attempting to copy one of the Learning Events into the new draft. A system administrator has been notified of issue, we apologize for the inconvenience.");

                                                        application_log("error", "An error occurred when inserting a draft event audience into a draft event schedule. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            } else {
                                                $audience = $audiences[0]->toArray();
                                                $audience["devent_id"] = $devent_id;
                                                $audience["audience_type"] = "course_id";
                                                $audience["audience_value"] = $draft_event->getCourseID();
                                                $audience["custom_time"] = 0;
                                                $audience["custom_time_start"] = 0;
                                                $audience["custom_time_end"] = 0;
                                                $draft_audience = new Models_Event_Draft_Event_Audience($audience);
                                                
                                                if (!$draft_audience->insert()) {
                                                    add_error("An error occurred when attempting to copy one of the Learning Events into the new draft. A system administrator has been notified of issue, we apologize for the inconvenience.");

                                                    application_log("error", "An error occurred when inserting a draft event audience into a draft event schedule. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }

                                        $contacts = Models_Event_Contacts::fetchAllByEventID($draft_event->getEventID());
                                        if ($contacts && is_array($contacts)) {
                                            foreach ($contacts as $contact_object) {
                                                $contact = $contact_object->toArray();
                                                $contact["devent_id"] = $devent_id;

                                                $draft_contact = new Models_Event_Draft_Contacts($contact);
                                                if (!$draft_contact->insert()) {
                                                    add_error("An error occurred when attempting to copy one of the Learning Events into the new draft. A system administrator has been notified of issue, we apologize for the inconvenience.");

                                                    application_log("error", "An error occurred when inserting a draft event contact into a draft event schedule. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }

                                        // copy the event types for the event
                                        $eventTypes = Models_Event_EventType::fetchAllByEventID($draft_event->getEventID());
                                        if ($eventTypes && is_array($eventTypes)) {
                                            foreach ($eventTypes as $eventType_object) {
                                                $eventType = $eventType_object->toArray();
                                                $eventType["devent_id"] = $devent_id;

                                                $draft_eventtype = new Models_Event_Draft_EventType($eventType);
                                                if (!$draft_eventtype->insert()) {
                                                    add_error("An error occurred when attempting to copy one of the Learning Events into the new draft. A system administrator has been notified of issue, we apologize for the inconvenience.");

                                                    application_log("error", "An error occurred when inserting a draft eventtype into a draft event schedule. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }
                                    } else {
                                        add_error("An error occurred when attempting to copy one of the Learning Events into the new draft. A system administrator has been notified of issue, we apologize for the inconvenience.");

                                        application_log("error", "An error occurred when inserting an event into a draft event schedule. DB said: ".$db->ErrorMsg());
                                    }
                                }
                            } else {
                                add_error("An error occurred when attempting to copy one of the Learning Events into the new draft. No events found.");

                                application_log("error", "An error occurred when attempting to copy one of the Learning Events into the new draft. No events found.");
                            }
                        }
                    }
                } else {
                    add_error("An error occurred when attempting to create your new draft. A system administrator has been notified of issue, we apologize for the inconvenience.");

                    application_log("error", "Error occurred when creating a new Learning Event draft. Database said: ".$db->ErrorMsg());
                }

                if (has_error()) {
                    $STEP = 1;
                } else {
                    add_success("You have successfully create a new draft, and you will be <strong>automatically</strong> redirected to it in 5 seconds. You can also <a href=\"".ENTRADA_URL."/admin/events/drafts?section=edit&amp;draft_id=".$draft_id."\">click here</a> to be redirected immediately.");
                    display_success();

                    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id."\\'', 5000)";
                }
			}
		break;
		case 1 :
		default :
			continue;
		break;
	}
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
			
			if (has_error()) {
				echo display_error();
			}

			if (has_notice()) {
				echo display_notice();
			}

        $HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/events/admin/create-draft.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

        $ONLOAD[] = "$(\"courses_list\").style.display = \"none\"";

			/**
			* Fetch all courses into an array that will be used.
			*/
			$query = "SELECT * FROM `courses` WHERE `organisation_id` = ".$ENTRADA_USER->getActiveOrganisation()." AND `course_active` = 1 ORDER BY `course_code` ASC";
			$courses = $db->GetAll($query);
			if ($courses && is_array($courses)) {
				foreach ($courses as $course) {
					$course_list[$course["course_id"]] = array("code" => $course["course_code"], "name" => $course["course_name"]);
				}
			}
            $HEAD[] = "<script type=\"text/javascript\">var courses_php = ". json_encode($course_list) .";</script>\n";
			?>
            <script type="text/javascript">
//                var courses_php = <?php //echo json_encode($course_list);?>//;
            </script>
        
			<div class="no-printing">
				<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/events/drafts?section=create-draft&step=2" method="post" onsubmit="selIt()" class="form-horizontal">
                    <h2 class="collapsable" title="Draft Information Section">
                        Draft Information
                    </h2>
                    <div id="draft-information-section">
                        <div class="control-group">
                            <label class="control-label form-required" for="draft_name">Draft Name</label>
                            <div class="controls">
                                <input type="text" id="draft_name" name="draft_name" value="<?php echo ((isset($PROCESSED["name"])) ? html_encode($PROCESSED["name"]) : ""); ?>" maxlength="255" placeholder="Example: <?php echo date("Y"); ?> Draft Teaching Schedule" class="span10" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label form-nrequired" for="draft_description">Optional Description</label>
                            <div class="controls">
                                <textarea name="draft_description" id="draft_description" class="span10 expandable">
                                    <?php echo ((isset($PROCESSED["description"])) ? html_encode($PROCESSED["description"]) : ""); ?>
                                </textarea>
                            </div>
                        </div>
                    </div>

                    <h2 class="collapsable<?php echo (!isset($PROCESSED["course_ids"]) ? " collapsed" : ""); ?>" title="Copy Events Section">
                        Copy Forward Existing Learning Events
                    </h2>
                    <div id="copy-events-section">
                        <p>Previous Learning Events can be copied into this new draft schedule by selecting courses from the list below and setting the date range. Learning Events found in the selected courses during the selected date range will be automatically copied into the new draft, starting on the week selected in the <strong>New Start Date</strong> field.</p>

                        <div class="control-group">
                            <label class="control-label form-nrequired">Copying Learning Resources</label>
                            <div class="controls">
                                <div class="alert alert-info">
                                    <strong>Did you know:</strong> When you copy learning events forward you can select what learning resources are copied along with each event?
                                </div>
                                <label class="checkbox">
                                    <input type="checkbox" name="options[files]"<?php echo (!isset($_POST) || !$_POST || isset($PROCESSED["draft_option_files"]) ? " checked=\"checked\"" : ""); ?> />
                                    Copy all <strong>attached files</strong>.
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="options[links]"<?php echo (!isset($_POST) || !$_POST || isset($PROCESSED["draft_option_links"]) ? " checked=\"checked\"" : ""); ?> />
                                    Copy all <strong>attached links</strong>.
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="options[objectives]"<?php echo (!isset($_POST) || !$_POST || isset($PROCESSED["draft_option_objectives"]) ? " checked=\"checked\"" : ""); ?> />
                                    Copy all <strong>attached learning objectives</strong>.
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="options[keywords]"<?php echo (!isset($_POST) || !$_POST || isset($PROCESSED["draft_option_keywords"]) ? " checked=\"checked\"" : ""); ?> />
                                    Copy all <strong>attached keywords</strong>.
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="options[topics]"<?php echo (!isset($_POST) || !$_POST || isset($PROCESSED["draft_option_topics"]) ? " checked=\"checked\"" : ""); ?> />
                                    Copy all <strong>attached hot topics</strong>.
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="options[quizzes]"<?php echo (!isset($_POST) || !$_POST || isset($PROCESSED["draft_option_quizzes"]) ? " checked=\"checked\"" : ""); ?> />
                                    Copy all <strong>attached quizzes</strong>.
                                </label>
                            </div>
                        </div>

                        <div class="control-group" id="copy-course-events">
                            <label class="control-label form-nrequired">Courses Included</label>
                            <div class="controls">
                                <select id="destinationCourses" class="chosen-select" data-placeholder="Choose a course" >
                                    <option></option>
                                    <?php
                                        if ((is_array($course_list)) && (count($course_list))) {
                                            foreach ($course_list as $course_id => $course) {
                                                echo "<option data-id=\"course-id-" . (int) $course_id . "\" value=\"".(int) $course_id."\">";
                                                echo html_encode($course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"]);
                                                echo "</option>\n";
                                            }
                                        }
                                    ?>
                                </select>
                               
                                <table id="selectedCourses">
                                    <colgroup>
                                        <col class="selectedCourse"/>
                                        <col class="courseCopyIcon"/>
                                        <col class="destinationCourse"/>
                                        <col class="removeCourseBtn"/>
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <th class="selectedCourse">Source</th>
                                            <th class="courseCopyIcon"></th>
                                            <th class="destinationCourse">Destination</th>
                                            <th class="removeCourseBtn"></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="course_array" id="course_array" />
                        <?php echo Entrada_Utilities::generate_calendars("copy", "", true, true, ((isset($PROCESSED["draft_start_date"])) ? $PROCESSED["draft_start_date"] : strtotime("September 1st, ".(date("o") - 1))), true, true, ((isset($PROCESSED["draft_finish_date"])) ? $PROCESSED["draft_finish_date"] : time()), false); ?>

                        <?php echo Entrada_Utilities::generate_calendars("new", "New Start Date", true, true, ((isset($PROCESSED["new_start_day"])) ? $PROCESSED["new_start_day"] : ((isset($PROCESSED["draft_start_date"])) ? strtotime("+1 Year", $PROCESSED["draft_start_date"]) : strtotime("September 1st, ".(date("o"))))), false, false, 0, false, false, ""); ?>
                    </div>

                    <a href="<?php echo ENTRADA_RELATIVE; ?>/admin/events/drafts" class="btn">Cancel</a>
                    <input type="submit" class="btn btn-primary pull-right" value="Create Draft" />
				</form>
			</div>
		<?php
		break;
	}
}