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
 * This file is used to modify content (i.e. goals, objectives, file resources
 * etc.) within a learning event from the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/eventtypes_list.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	?>
	<script type="text/javascript">
		var EVENT_LIST_STATIC_TOTAL_DURATION = true;
	</script>
	<?php
	if ($EVENT_ID) {
        $event = Models_Event::get($EVENT_ID);
        $course_id = $event->getCourseID();

        $EXAM_TEXT = $translate->_("exams");
        $POST_TEXT = $EXAM_TEXT["exams"]["posting"];
        $DEFAULT_LABELS = $translate->_("default");
        $attached_exams = $event->getAttachedExams();
        
        $HEAD[] = "<script>var SITE_URL = '".ENTRADA_URL."';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>\n";
        $HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives_event.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/jquery/jquery.timepicker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.inputselector.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/keywords_event.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/event_resources.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/events_nl_form.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.timepicker.css?release=".html_encode(APPLICATION_VERSION)."\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.inputselector.css?release=".html_encode(APPLICATION_VERSION)."\" />";

		$event_info = Models_Event::fetchEventById($EVENT_ID);
		if ($event_info) {
            if ($event_info["recurring_id"]) {
                $recurring_event_array  = Models_Event::getRecurringEventIds($EVENT_ID);
                $recurring_events       = $recurring_event_array["recurring_events"];
                $re_bool                = ($recurring_events && is_array($recurring_events) ? 1 : 0 );
                $re_ids                 = $recurring_event_array["recurring_event_ids"];
            } else {
                $recurring_events       = false;
                $re_bool                = 0;
                $re_ids                 = 0;
            }

            $PROCESSED["keywords_hidden"] = $event_info["keywords_hidden"];
			$PROCESSED["keywords_release_date"] = $event_info["keywords_release_date"];
			$PROCESSED["objectives_release_date"] = $event_info["objectives_release_date"];
			$COURSE_ID = $event_info["course_id"];

			if (!$ENTRADA_ACL->amIAllowed(new EventContentResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), "update")) {
				application_log("error", "Someone attempted to modify content for an event [".$EVENT_ID."] that they were not the coordinator for.");

				header("Location: ".ENTRADA_URL."/admin/".$MODULE);
				exit;
			} else {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "content", "id" => $EVENT_ID)), "title" => "Event Content");

				$HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

				/**
				 * Load the rich text editor.
				 */
				load_rte("events");

				/**
				 * Fetch event content history
				 */
				$history = $db->GetRow("SELECT * FROM `event_history` WHERE `event_id`  = ".$db->qstr($EVENT_ID));

				if (!$history) { // Create the first history record of the event's creation when another user updates the event
					if (count($_POST) && ($ENTRADA_USER->getID() != $event_info["updated_by"])) {	// Ignore starting history when it's the sole author initially adding content.
						history_log($EVENT_ID, 'created this learning event.', $event_info["updated_by"], $event_info["updated_date"]);
					}
				}

				if (($event_info["release_date"]) && ($event_info["release_date"] > time())) {
					add_notice("This event is not yet visible to students due to Time Release Options set by an administrator. The release date is set to ".date("r", $event_info["release_date"]));
				}

				if (($event_info["release_until"]) && ($event_info["release_until"] < time())) {
					add_notice("This event is no longer visible to students due to Time Release Options set by an administrator. The expiry date was set to ".date("r", $event_info["release_until"]));
				}

				/**
				 * Fetch the event audience information.
				 */
				$event_audience_type		= "";
				$associated_grad_year		= "";
				$associated_group_ids		= array();
				$associated_proxy_ids		= array();
				$associated_organisation	= "";

				$query		= "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($EVENT_ID);
				$results	= $db->GetAll($query);
				if ($results) {
					$event_audience_type = $results[0]["audience_type"];

					foreach ($results as $result) {
						if ($result["audience_type"] == $event_audience_type) {
							switch ($result["audience_type"]) {
								case "grad_year" :
									$associated_grad_year = clean_input($result["audience_value"], "alphanumeric");
								break;
								case "group_id" :
									$associated_group_ids[] = (int) $result["audience_value"];
								break;
								case "proxy_id" :
									$associated_proxy_ids[] = (int) $result["audience_value"];
								break;
								case "organisation_id" :
									$query = "SELECT `organisation_title` FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ".$db->qstr($result["audience_value"]);
									$associated_organisation = $db->GetOne($query);
								break;
							}
						}
					}
				}

				/**
				 * Fetch the Clinical Presentation details.
				 */
				$clinical_presentations_list = array();
				$clinical_presentations = array();

				$results = fetch_clinical_presentations(0, array(), $event_info["course_id"]);
				if ($results) {
					foreach ($results as $result) {
						$clinical_presentations_list[$result["objective_id"]] = $result["objective_name"];
					}
				}

                if (((isset($_POST["clinical_presentations"])) && (is_array($_POST["clinical_presentations"])) && (count($_POST["clinical_presentations"])))) {
                    foreach ($_POST["clinical_presentations"] as $objective_id) {
                        if ($objective_id = clean_input($objective_id, array("trim", "int"))) {
                            $query	= "	SELECT a.`objective_id`
                                    FROM `global_lu_objectives` AS a
                                    JOIN `course_objectives` AS b
                                    ON b.`course_id` = ".$event_info["course_id"]."
                                    AND a.`objective_id` = b.`objective_id`
                                    JOIN `objective_organisation` AS c
                                    ON a.`objective_id` = c.`objective_id`
                                    WHERE a.`objective_id` = ".$db->qstr($objective_id)."
                                    AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                    AND b.`objective_type` = 'event'
                                    AND a.`objective_active` = '1'
                                    AND b.`active` = '1'";
                            $result	= $db->GetRow($query);
                            if ($result) {
                                $clinical_presentations[$objective_id] = $clinical_presentations_list[$objective_id];
                            }
                        }
                    }
                } else {
                    $clinical_presentations = array();
                }

                /**
				 * Fetch the Curriculum Objective details.
				 */
				list($curriculum_objectives_list,$top_level_id) = courses_fetch_objectives($event_info["organisation_id"],array($event_info["course_id"]),-1, 1, false, false, $EVENT_ID, true);

                $curriculum_objectives = array();

				if (isset($_POST["checked_objectives"]) && ($checked_objectives = $_POST["checked_objectives"]) && (is_array($checked_objectives))) {
					foreach ($checked_objectives as $objective_id) { // => $status
						if ($objective_id = (int) $objective_id) {
							if (isset($_POST["objective_text"][$objective_id]) && ($tmp_input = clean_input($_POST["objective_text"][$objective_id], array("notags")))) {
								$objective_text = $tmp_input;
							} else {
								$objective_text = false;
							}

							$curriculum_objectives[$objective_id] = $objective_text;
						}
					}

					history_log($EVENT_ID, "updated clinical objectives.");
				}

				$query = "SELECT `objective_id` FROM `event_objectives` WHERE `event_id` = ".$db->qstr($EVENT_ID)." AND `objective_type` = 'course'";
				$results = $db->GetAll($query);
				if ($results) {
					foreach ($results as $result) {
						$curriculum_objectives_list["objectives"][$result["objective_id"]]["event_objective"] = true;
					}
				}

				/**
				 * Fetch the event type information.
				 */
				$event_eventtypes_list	= array();
				$event_eventtypes		= array();

				$query		= "	SELECT a.* FROM `events_lu_eventtypes` AS a
								LEFT JOIN `eventtype_organisation` AS c
								ON a.`eventtype_id` = c.`eventtype_id`
								LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
								ON b.`organisation_id` = c.`organisation_id`
								WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								AND a.`eventtype_active` = '1'
								ORDER BY a.`eventtype_title`";
				$results	= $db->GetAll($query);
				if ($results) {
					foreach ($results as $result) {
						$event_eventtypes_list[] = array("id" => $result["eventtype_id"], "title" => $result["eventtype_title"], "description" => $result["eventtype_description"]);
					}
				}

				$query		= "SELECT a.*, b.`eventtype_title` FROM `event_eventtypes` AS a LEFT JOIN `events_lu_eventtypes` AS b ON a.`eventtype_id` = b.`eventtype_id` WHERE a.`event_id` = ".$db->qstr($EVENT_ID)." ORDER BY `eeventtype_id` ASC";
				$results	= $db->GetAll($query);
				$initial_duration = 0;
				if ($results) {
					foreach ($results as $result) {
						$initial_duration += $result["duration"];
						$event_eventtypes[] = $result;
					}
				}
				?>
				<script type="text/javascript" charset="utf-8">
					var INITIAL_EVENT_DURATION = <?php echo $initial_duration; ?>
				</script>
				<?php
				if (isset($_POST["eventtype_duration_order"])) {
					$old_event_eventtypes = $event_eventtypes;
					$event_eventtypes = array();
					$eventtype_durations = $_POST["duration_segment"];

					$event_types = explode(",", trim($_POST["eventtype_duration_order"]));

					if (is_array($event_types) && count($event_types)) {
						foreach ($event_types as $order => $eventtype_id) {
							if (($eventtype_id = clean_input($eventtype_id, array("trim", "int"))) && ($duration = clean_input($eventtype_durations[$order], array("trim", "int")))) {
								if (!($duration >= LEARNING_EVENT_MIN_DURATION)) {
									add_error("Event type <strong>durations</strong> may not be less than ".LEARNING_EVENT_MIN_DURATION." minutes.");
								}

								$query	= "SELECT `eventtype_title` FROM `events_lu_eventtypes` WHERE `eventtype_id` = ".$db->qstr($eventtype_id);
								$result	= $db->GetRow($query);
								if ($result) {
									$event_eventtypes[] = array("eventtype_id"=>$eventtype_id, "duration"=>$duration, "eventtype_title"=>$result["eventtype_title"]);
								}
							}
						}

						$event_duration	= 0;
						$old_event_duration = 0;
						foreach ($event_eventtypes as $event_type) {
							$event_duration += $event_type["duration"];
						}

						foreach($old_event_eventtypes as $event_type) {
							$old_event_duration += $event_type["duration"];
						}

						if ($old_event_duration != $event_duration) {
							add_error("The modified <strong>" . $translate->_("Event Types") . "</strong> duration specified is different than the existing one, please ensure the event's duration remains the same.");
						}
					} else {
						add_error("At least one event type in the <strong>" . $translate->_("Event Types") . "</strong> field is required.");
					}
				}

				if (isset($_POST["type"])) {
					switch ($_POST["type"]) {
						case "content" :
                            /**
                             * Event keywords hidden from students
                             */
                            $PROCESSED["keywords_hidden"] = 0;
                            if (isset($_POST["keywords_hidden"]) && $tmp_input = clean_input($_POST["keywords_hidden"], array("int"))) {
                                $PROCESSED["keywords_hidden"] = (int) $tmp_input;
                            }
                            
                            /**
                             * Event keyword release date
                             */
                            if (isset($_POST["keywords_release_date"]) && $tmp_input = clean_input($_POST["keywords_release_date"], array("notags", "nows"))) {
                                switch ($tmp_input) {
                                    case "now" :
                                        $PROCESSED["keywords_release_date"] = 0;
                                    break;
                                    case "delay" :
                                        $PROCESSED["delay_release_keywords"] = true;
                                        $release_date = Entrada_Utilities::validate_calendar("Delay release until", "delay_release_keywords_option", true, true);

                                        if (!$ERROR) {
                                            $PROCESSED["keywords_release_date"] = (int) $release_date;
                                        }
                                    break;
                                    case "never" :
                                        $PROCESSED["keywords_release_date"] = null;
                                    break;
                                }
                            }

                            /**
                            * Event objective release date
                            */
                            if (isset($_POST["objectives_release_date"]) && $tmp_input = clean_input($_POST["objectives_release_date"], array("notags", "nows"))) {
                                switch ($tmp_input) {
                                    case "now" :
                                        $PROCESSED["objectives_release_date"] = 0;
                                    break;
                                    case "delay" :
                                        $PROCESSED["delay_release"] = true;
                                        $release_date = Entrada_Utilities::validate_calendar("Delay release until", "delay_release_option", true, true);

                                        if (!$ERROR) {
                                            $PROCESSED["objectives_release_date"] = (int) $release_date;
                                        }
                                    break;
                                    case "never" :
                                        $PROCESSED["objectives_release_date"] = null;
                                    break;
                                }
                            }

                            if (!$ERROR) {
                                $history_updates = array();

                                /**
                                 * Event Description
                                 */
                                
                                $changed = false;
                                $changed = md5_change_value($EVENT_ID, 'event_id', 'event_description', $_POST["event_description"], 'events');

                                if ($changed) {
                                    $history_updates[] = "Event Description";
                                }

                                if ((isset($_POST["event_description"])) && (clean_input($_POST["event_description"], array("allowedtags", "nows")))) {
                                    $event_description = clean_input($_POST["event_description"], array("allowedtags"));
                                } else {
                                    $event_description = "";
                                }

                                /**
                                 * Free-Text Objectives
                                 */

                                $changed = false;
                                $changed = md5_change_value($EVENT_ID, 'event_id', 'event_objectives', $_POST["event_objectives"], 'events');
                                if ($changed) {
                                    $history_updates[] = $translate->_("Event Objectives");
                                }

                                if ((isset($_POST["event_objectives"])) && (clean_input($_POST["event_objectives"], array("allowedtags", "nows")))) {
                                    $event_objectives = clean_input($_POST["event_objectives"], array("allowedtags"));
                                } else {
                                    $event_objectives = "";
                                }

                                /**
                                 * Required Preparation
                                 */
                                
                                $changed = false;
                                $changed = md5_change_value($EVENT_ID, 'event_id', 'event_message', $_POST["event_message"], 'events');
                                
                                if ($changed) {
                                    $history_updates[] = "Event Preparation";
                                }
                                if ((isset($_POST["event_message"])) && (clean_input($_POST["event_message"], array("notags", "nows")))) {
                                    $event_message = clean_input($_POST["event_message"], array("allowedtags"));
                                } else {
                                    $event_message = "";
                                }

                                $event_finish	= $event_info["event_start"];
                                $event_duration	= 0;

                                foreach ($event_eventtypes as $event_type) {
                                    $event_finish += ($event_type["duration"] * 60);
                                    $event_duration += $event_type["duration"];
                                }

                                /**
                                 * Update base Learning Event.
                                 */
                                if ($db->AutoExecute("events", array("event_objectives" => $event_objectives, "keywords_hidden" => $PROCESSED["keywords_hidden"], "keywords_release_date" => $PROCESSED["keywords_release_date"], "objectives_release_date" => $PROCESSED["objectives_release_date"] , "event_description" => $event_description, "event_message" => $event_message, "event_finish" => $event_finish, "event_duration" => $event_duration, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`event_id` = ".$db->qstr($EVENT_ID))) {
                                    add_success("You have successfully updated the event setup for this learning event.");
                                    application_log("success", "Updated learning event content.");
                                } else {
                                    application_log("error", "Failed to update learning event content. Database said: ".$db->ErrorMsg());
                                }

                                /**
                                 * Update Event Types.
                                 */

                                $query = "DELETE FROM `event_eventtypes` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                if ($db->Execute($query)) {
                                    foreach ($event_eventtypes as $event_type) {
                                        if (!$db->AutoExecute("event_eventtypes", array("event_id" => $EVENT_ID, "eventtype_id" => $event_type["eventtype_id"], "duration" => $event_type["duration"]), "INSERT")) {
                                            add_error("There was an error while trying to save the selected <strong>" . $translate->_("Event Type") . "</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                            application_log("error", "Unable to insert a new event_eventtype record while adding a new event. Database said: ".$db->ErrorMsg());
                                        }
                                    }
                                } else {
                                    add_error("There was an error while trying to update the selected <strong>" . $translate->_("Event Types") . "</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                    application_log("error", "Unable to delete any eventtype records while editing an event. Database said: ".$db->ErrorMsg());
                                }

                                /**
                                 * Update Clinical Presentations.
                                 */
                                $query = "DELETE FROM `event_objectives` WHERE `objective_type` = 'event' AND `event_id` = ".$db->qstr($EVENT_ID);
                                if ($db->Execute($query)) {
                                    if (is_array($clinical_presentations) && count($clinical_presentations)) {
                                        foreach ($clinical_presentations as $objective_id => $presentation_name) {
                                            if (isset($_POST["objective_text"][$objective_id]) && ($tmp_input = clean_input($_POST["objective_text"][$objective_id], array("notags")))) {
                                                $objective_text = $tmp_input;
                                            } else {
                                                $objective_text = false;
                                            }
                                            if (!$db->AutoExecute("event_objectives", array("event_id" => $EVENT_ID, "objective_details" => $objective_text, "objective_id" => $objective_id, "objective_type" => "event", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
                                                add_error("There was an error when trying to insert a &quot;clinical presentation&quot; into the system. System administrators have been informed of this error; please try again later.");
                                                application_log("error", "Unable to insert a new clinical presentation to the database when adding a new event. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                        history_log($EVENT_ID, "updated clinical presentations.");
                                    }
                                }

                                /**
                                 * Update Curriculum Objectives.
                                 */
                                $query = "DELETE FROM `event_objectives` WHERE `objective_type` = 'course' AND `event_id` = ".$db->qstr($EVENT_ID);
                                if ($db->Execute($query)) {
                                    if ((isset($curriculum_objectives)) && (is_array($curriculum_objectives)) && (count($curriculum_objectives))) {
                                        foreach ($curriculum_objectives as $objective_id => $objective_text) {
                                            if ($objective_id = (int) $objective_id) {
                                                $query	= "	SELECT a.* FROM `global_lu_objectives` AS a
                                                        JOIN `objective_organisation` AS b
                                                        ON a.`objective_id` = b.`objective_id`
                                                        WHERE a.`objective_id` = ".$db->qstr($objective_id)."
                                                        AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                                        AND a.`objective_active` = '1'";
                                                $result	= $db->GetRow($query);
                                                if ($result) {
                                                    if (!$db->AutoExecute("event_objectives", array("event_id" => $EVENT_ID, "objective_details" => $objective_text, "objective_id" => $objective_id, "objective_type" => "course", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
                                                        add_error("There was an error when trying to insert a &quot;course objective&quot; into the system. System administrators have been informed of this error; please try again later.");

                                                        application_log("error", "Unable to insert a new course objective to the database when adding a new event. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        }

                                        /**
                                         * Changes have been made so update the $curriculum_objectives_list variable.
                                         */
                                        list($curriculum_objectives_list,$top_level_id) = courses_fetch_objectives($event_info["organisation_id"],array($event_info["course_id"]), -1, 1, false, false, $EVENT_ID, true);
                                    }
                                }

                                // Update MeSH keywords
                                if (isset($_POST["delete_keywords"])) {
                                    if (trim($_POST["delete_keywords"][0]) !== ""){                                                                        
                                        $lis = explode(",", $_POST["delete_keywords"][0]);
                                        $count = count($lis);                                                                 

                                        if ($count > 0){
                                            // Removed the keywords in the delete array.
                                            for ($i=0; $i<$count; $i++) {
                                                if (trim($lis[$i]) != "") {
                                                    $query = "  DELETE 
                                                                FROM `event_keywords` 
                                                                WHERE keyword_id = ". $db->qstr($lis[$i])." AND event_id = ".$db->qstr($EVENT_ID);
                                                    $db->Execute($query);
                                                }
                                            }

                                            if (!in_array('Event Keywords Removed', $history_updates)) {
                                                $history_updates[] = 'Event Keywords Removed';
                                            }
                                        }
                                    }
                                }

                                if (isset($_POST["add_keywords"][0])) {
                                    if (trim($_POST["add_keywords"][0]) !== "") {
                                        $lis = explode(",", $_POST["add_keywords"][0]);
                                        $count = count($lis);                                                                 

                                        if ($count > 0) {
                                            // Add the keywords n the add array.
                                            for ($i=0; $i<$count; $i++) {
                                                if (trim($lis[$i]) != "") {
                                                    $query = "  INSERT INTO `event_keywords` (event_id, keyword_id, updated_date, updated_by) 
                                                                VALUES (".$db->qstr($EVENT_ID).", ". $db->qstr($lis[$i]).", ". $db->qstr(time()). ", ". $db->qstr($ENTRADA_USER->getID()).")";
                                                    $db->Execute($query);
                                                }
                                            }

                                            if (!in_array('Event Keywords Added', $history_updates)) {
                                                $history_updates[] = 'Event Keywords Added';
                                            }
                                        }
                                    }                                                                                                                             
                                }

                                /**
                                 * Update Event Topics information.
                                 */

                                $query = "SELECT `topic_id`, `topic_coverage`, `etopic_id` FROM `event_topics` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                $current_hot_topics_results = $db->GetAll($query);
                                
                                if (isset($current_hot_topics_results) && is_array($current_hot_topics_results)) {                                    
                                    foreach ($current_hot_topics_results as $current_hot_topics_result) {
                                        $current_hot_topics[$current_hot_topics_result['topic_id']] = $current_hot_topics_result['topic_coverage'];
                                    }
                                }
                                
                                $hot_topic_update = false;
                                if ((isset($_POST["event_topic"])) && (is_array($_POST["event_topic"])) && (count($_POST["event_topic"]))) {
                                    if (isset($current_hot_topics) && is_array($current_hot_topics)) {
                                        $remove_topics_array = array_diff_assoc($current_hot_topics, $_POST["event_topic"]);
                                        $add_topics_array = array_diff_assoc($_POST["event_topic"], $current_hot_topics);
                                    } else {
                                        //no current record so insert all
                                        $add_topics_array = $_POST["event_topic"];
                                    }
                                    
                                    if (isset($add_topics_array) && is_array($add_topics_array) && (count($add_topics_array))) {
                                        foreach ($add_topics_array as $topic_id => $value) {
                                            if ($topic_id = clean_input($topic_id, array("trim", "int"))) {
                                                $squery		= "SELECT * FROM `events_lu_topics` WHERE `topic_id` = ".$db->qstr($topic_id);
                                                $sresult	= $db->GetRow($squery);
                                                if ($sresult) {
                                                    if ($value == "major") {
                                                        if (!$db->AutoExecute("event_topics", array("event_id" => $EVENT_ID, "topic_id" => $topic_id, "topic_coverage" => "major", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
                                                            add_error("There was an error when trying to insert an Event Topic response into the system. System administrators have been informed of this error; please try again later.");

                                                            application_log("error", "Unable to insert a new event_topic entry into the database while modifying event contents. Database said: ".$db->ErrorMsg());
                                                        }
                                                    } elseif ($value == "minor") {
                                                        if (!$db->AutoExecute("event_topics", array("event_id" => $EVENT_ID, "topic_id" => $topic_id, "topic_coverage" => "minor", "topic_time" => "0", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
                                                            add_error("There was an error when trying to insert an Event Topic response into the system. System administrators have been informed of this error; please try again later.");

                                                            application_log("error", "Unable to insert a new event_topic response to the database while modifying event contents. Database said: ".$db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            }
                                            //log history add hot topic
                                            if (!in_array('Event Topics Added', $history_updates)) {
                                                $history_updates[] = 'Event Topics Added';
                                            }
                                        }
                                    }
                                } else {
                                    if (isset($current_hot_topics)) {
                                        $remove_topics_array = array_diff_assoc($current_hot_topics, array());
                                    }
                                }

                                if (isset($remove_topics_array) && is_array($remove_topics_array) && (count($remove_topics_array))) {
                                    foreach($remove_topics_array as $topic_id => $value) {
                                        $query = "DELETE FROM `event_topics` WHERE `event_id` = ".$db->qstr($EVENT_ID) . 'AND `topic_id` = ' . $db->qstr($topic_id);
                                        if (!$db->Execute($query)) {
                                            add_error("There was an error when trying to delete an Event Topic response from the system. System administrators have been informed of this error; please try again later.");
                                            application_log("error", "Unable to delete an Event Topic response entry from the database while modifying event contents. Database said: ".$db->ErrorMsg());
                                        }
                                    }
                                    //log history delete hot topic
                                    if (!in_array('Event Topics Removed', $history_updates)) {
                                        $history_updates[] = 'Event Topics Removed';
                                    }
                                }

                                /**
                                 * Refresh the event_info array based on new details.
                                 */
                                $query = "	SELECT a.*, b.`organisation_id`
                                            FROM `events` AS a
                                            LEFT JOIN `courses` AS b
                                            ON b.`course_id` = a.`course_id`
                                            WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
                                $event_info	= $db->GetRow($query);
                                if (!$event_info) {
                                    application_log("error", "After updating the text content of event_id [".$EVENT_ID."] the select query failed.");
                                }
                                if ($history_updates) {
                                    history_log($EVENT_ID, "updated event content: [".implode(",", $history_updates)."]", $PROXY_ID);
                                }

                                if ($recurring_events && $event_info && isset($_POST["recurring_event_ids"]) && @count($_POST["recurring_event_ids"]) && isset($_POST["update_recurring_fields"]) && @count($_POST["update_recurring_fields"])) {
                                    $updating_recurring_events = array();
                                    $query = "SELECT * FROM `events`
                                            WHERE `recurring_id` = ".$db->qstr($event_info["recurring_id"])."
                                            AND `event_id` != ".$db->qstr($EVENT_ID)."
                                            ORDER BY `event_start` ASC";
                                    $temp_recurring_events = $db->GetAll($query);
                                    if ($temp_recurring_events) {
                                        foreach ($temp_recurring_events as $temp_event) {
                                            if (in_array($temp_event["event_id"], $_POST["recurring_event_ids"])) {
                                                $updating_recurring_events[] = $temp_event;
                                            }
                                        }
                                    }
                                    $PROCESSED_RECURRING_EVENT = array();
                                    unset($PROCESSED_RECURRING_EVENT["recurring_id"]);
                                    if ($updating_recurring_events) {
                                        if (isset($_POST["update_recurring_fields"]) && in_array("mapped_objectives", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["mapped_objectives"] = array();
                                            $query = "SELECT `objective_id`, `objective_details`, `objective_type` FROM `event_objectives` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach ($results as $result) {
                                                    $PROCESSED_RECURRING_EVENT["mapped_objectives"][] = $result;
                                                }
                                            }
                                        }

                                        if (isset($_POST["update_recurring_fields"]) && in_array("mesh_keywords", $_POST["update_recurring_fields"])) {
                                            $keyword_ids = array();
                                            $PROCESSED_RECURRING_EVENT["mesh_keywords"] = array();
                                            $query = "SELECT `keyword_id`, `updated_date`, `updated_by` FROM `event_keywords` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach ($results as $result) {
                                                    $keyword_ids[] = $result["keyword_id"];
                                                    $PROCESSED_RECURRING_EVENT["mesh_keywords"][] = $result;
                                                }
                                            }
                                        }

                                        if (isset($_POST["update_recurring_fields"]) && in_array("event_topics", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["event_topics"] = array();
                                            $query = "SELECT `topic_id`, `topic_coverage`, `topic_time` FROM `event_topics` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach ($results as $result) {
                                                    $PROCESSED_RECURRING_EVENT["event_topics"][$result['topic_id']] = $result['topic_coverage'];
                                                }
                                            }
                                        }

                                        if (in_array("event_description", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["event_description"] = $event_info["event_description"];
                                        }

                                        if (in_array("event_message", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["event_message"] = $event_info["event_message"];
                                        }

                                        if (in_array("event_objectives", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["event_objectives"] = $event_objectives;
                                        }

                                        if (in_array("mesh_keywords", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["keywords_hidden"] = $PROCESSED["keywords_hidden"];
                                            if ($PROCESSED["delay_release_keywords"]) {
                                                $PROCESSED_RECURRING_EVENT["keywords_release_date"] = $PROCESSED["keywords_release_date"];
                                            } else {
                                                $PROCESSED_RECURRING_EVENT["keywords_release_date"] = 0;
                                            }
                                        }

                                        foreach ($updating_recurring_events as $order => $recurring_event) {
                                            $recurring_history_updates = array();
                                            
                                            if (isset($PROCESSED_RECURRING_EVENT["mapped_objectives"])) {
                                                $query = "DELETE FROM `event_objectives` WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]);
                                                if ($db->Execute($query)) {
                                                    if (@count($PROCESSED_RECURRING_EVENT["mapped_objectives"])) {
                                                        foreach($PROCESSED_RECURRING_EVENT["mapped_objectives"] as $event_objective) {
                                                            $event_objective_data = $event_objective;
                                                            $event_objective_data["event_id"] = $recurring_event["event_id"];
                                                            $event_objective_data["updated_date"] = time();
                                                            $event_objective_data["updated_by"] = $ENTRADA_USER->GetID();
                                                            if (!$db->AutoExecute("`event_objectives`", $event_objective_data, "INSERT")) {
                                                                add_error("There was an error while trying to save the selected <strong>" . $translate->_("Event Objective") . "</strong> for a recurring event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                application_log("error", "Unable to insert a new event_objectives record while editing a recurring event. Database said: ".$db->ErrorMsg());
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    add_error("There was an error while trying to update the selected <strong>" . $translate->_("Event Objectives") . "</strong> for a recurring event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                    application_log("error", "Unable to delete any event_objectives records while editing an event. Database said: ".$db->ErrorMsg());
                                                }
                                            }

                                            if (isset($PROCESSED_RECURRING_EVENT["mesh_keywords"])) {
                                                $query = "SELECT * FROM `event_keywords` WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]);
                                                $existing_keywords = $db->GetAll($query);
                                                $remove_keywords_string = "";
                                                if ($existing_keywords) {
                                                    $existing_keyword_ids = $keyword_ids;
                                                    foreach ($existing_keywords as $existing_keyword) {
                                                        if (!in_array($existing_keyword["keyword_id"], $existing_keyword_ids)) {
                                                            $remove_keywords_string .= ($remove_keywords_string ? "," : "").$db->qstr($existing_keyword["keyword_id"]);
                                                        } else {
                                                            unset($existing_keyword_ids[array_search($existing_keyword["keyword_id"], $existing_keyword_ids)]);
                                                        }
                                                    }
                                                }

                                                if ($remove_keywords_string) {
                                                    $query = "DELETE FROM `event_keywords`
                                                                WHERE `keyword_id` IN (".$remove_keywords_string.")
                                                                AND `event_id` = ".$db->qstr($recurring_event["event_id"]);

                                                    if ($deleted_keywords_query = $db->Execute($query)) {
                                                        if (!in_array('Event Keywords Removed', $recurring_history_updates)) {
                                                            $recurring_history_updates[] = 'Event Keywords Removed';
                                                        }
                                                    }
                                                }
                                                
                                                if (!$remove_keywords_string || $deleted_keywords_query) {
                                                    if (@count($PROCESSED_RECURRING_EVENT["mesh_keywords"])) {
                                                        $added_mesh_keywords = 0;
                                                        foreach($PROCESSED_RECURRING_EVENT["mesh_keywords"] as $mesh_keyword) {
                                                            //checks to see if keyword is already on event so we don't get duplicates.
                                                            $query = "  SELECT * FROM `event_keywords`
                                                                        WHERE `event_id` = " . $db->qstr($recurring_event["event_id"]) . "
                                                                        AND `keyword_id` = " . $db->qstr($mesh_keyword['keyword_id']);
                                                            $existing_keywords = $db->GetAll($query);
                                                            
                                                            if (!$existing_keywords) {
                                                                $mesh_keyword_data = $mesh_keyword;
                                                                $mesh_keyword_data["updated_date"] = time();
                                                                $mesh_keyword_data["updated_by"] = $ENTRADA_USER->GetID();
                                                                $mesh_keyword_data["event_id"] = $recurring_event["event_id"];

                                                                if ($db->AutoExecute("`event_keywords`", $mesh_keyword_data, "INSERT")) {
                                                                    $added_mesh_keywords++;
                                                                } else {
                                                                    add_error("There was an error while trying to save the selected <strong>Event Keyword</strong> for a recurring event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                    application_log("error", "Unable to insert a new event keyword record while editing a recurring event. Database said: ".$db->ErrorMsg());
                                                                }
                                                            }
                                                        }

                                                        if ($added_mesh_keywords > 0) {
                                                            if (!in_array('Event Keywords Added', $recurring_history_updates)) {
                                                                $recurring_history_updates[] = 'Event Keywords Added';
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    add_error("There was an error while trying to update the selected <strong>Event Keywords</strong> for a recurring event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                    application_log("error", "Unable to delete any event keyword records while editing an event. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                            if (isset($PROCESSED_RECURRING_EVENT["event_topics"])) {
                                                //select current topics
                                                $query = "SELECT `topic_id`, `topic_coverage`, `etopic_id` FROM `event_topics` WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]);
                                                $current_hot_topics_results = $db->GetAll($query);
                                                
                                                $current_hot_topics = array();
                                                if (isset($current_hot_topics_results) && is_array($current_hot_topics_results)) {                                    
                                                    foreach ($current_hot_topics_results as $current_hot_topics_result) {
                                                        $current_hot_topics[$current_hot_topics_result['topic_id']] = $current_hot_topics_result['topic_coverage'];
                                                    }
                                                }

                                                if ((isset($PROCESSED_RECURRING_EVENT["event_topics"])) && (is_array($PROCESSED_RECURRING_EVENT["event_topics"])) && (count($PROCESSED_RECURRING_EVENT["event_topics"]))) {
                                                    if (isset($current_hot_topics) && is_array($current_hot_topics)) {
                                                        $remove_topics_array = array_diff_assoc($current_hot_topics, $PROCESSED_RECURRING_EVENT["event_topics"]);
                                                        $add_topics_array = array_diff_assoc($PROCESSED_RECURRING_EVENT["event_topics"], $current_hot_topics);
                                                    } else {
                                                        //no current record so insert all
                                                        $add_topics_array = $PROCESSED_RECURRING_EVENT["event_topics"];
                                                    }

                                                    if (isset($remove_topics_array) && is_array($remove_topics_array) && (count($remove_topics_array))) {
                                                        foreach($remove_topics_array as $topic_id => $value) {
                                                            $query = "DELETE FROM `event_topics` WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]) . 'AND `topic_id` = ' . $db->qstr($topic_id);
                                                            if (!$db->Execute($query)) {
                                                                add_error("There was an error when trying to delete an Event Topic response from the system. System administrators have been informed of this error; please try again later.");
                                                                application_log("error", "Unable to delete an Event Topic response entry from the database while modifying event contents. Database said: ".$db->ErrorMsg());
                                                            }
                                                        }

                                                        //log history delete hot topic
                                                        if (!in_array('Event Topics Removed', $recurring_history_updates)) {
                                                            $recurring_history_updates[] = 'Event Topics Removed';
                                                        }
                                                    }

                                                    if (isset($add_topics_array) && is_array($add_topics_array) && (count($add_topics_array))) {
                                                        foreach ($add_topics_array as $topic_id => $value) {
                                                            if ($topic_id = clean_input($topic_id, array("trim", "int"))) {
                                                                $squery		= "SELECT * FROM `events_lu_topics` WHERE `topic_id` = ".$db->qstr($topic_id);
                                                                $sresult	= $db->GetRow($squery);
                                                                if ($sresult) {
                                                                    if (!$db->AutoExecute("event_topics", array("event_id" => $recurring_event["event_id"], "topic_id" => $topic_id, "topic_coverage" => $value, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
                                                                        add_error("There was an error when trying to insert an Event Topic response into the system. System administrators have been informed of this error; please try again later.");
                                                                        application_log("error", "Unable to insert a new event_topic entry into the database while modifying event contents. Database said: ".$db->ErrorMsg());
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        //log history add hot topic
                                                        if (!in_array('Event Topics Added', $recurring_history_updates)) {
                                                            $recurring_history_updates[] = 'Event Topics Added';
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            if (!has_error() && @array_intersect($_POST["update_recurring_fields"], array("mesh_keywords", "event_description", "event_message", "event_objectives"))) {

                                                //checks if the description, objectives, or message has changed before saving.
                                                $changed_event_description = false;
                                                $changed_event_description = md5_change_value($recurring_event["event_id"], 'event_id', 'event_description', $PROCESSED_RECURRING_EVENT["event_description"], 'events');

                                                $changed_event_objectives = false;
                                                $changed_event_objectives = md5_change_value($recurring_event["event_id"], 'event_id', 'event_objectives', $PROCESSED_RECURRING_EVENT["event_objectives"], 'events');

                                                $changed_event_message = false;
                                                $changed_event_message = md5_change_value($recurring_event["event_id"], 'event_id', 'event_message', $PROCESSED_RECURRING_EVENT["event_message"], 'events');
                                                
                                                if (!$db->AutoExecute("`events`", $PROCESSED_RECURRING_EVENT, "UPDATE", "`event_id` = ".$db->qstr($recurring_event["event_id"]))) {
                                                    add_error("There was an error while trying to save changes to the selected <strong>Recurring Event</strong>.<br /><br />The system administrator was informed of this error; please try again later.");
                                                    application_log("error", "Unable to update an event record while editing a recurring event. Database said: ".$db->ErrorMsg());
                                                } else {
                                                    if ($changed_event_description || $changed_event_objectives || $changed_event_message) {
                                                        if ($changed_event_description) {
                                                            $recurring_history_updates[] = "Event Description";
                                                        }

                                                        if ($changed_event_objectives) {
                                                            $recurring_history_updates[] = $translate->_("Event Objectives");
                                                        }

                                                        if ($changed_event_message) {
                                                            $recurring_history_updates[] = "Event Preparation";
                                                        }
                                                    }
                                                }
                                            }

                                            if (!empty($recurring_history_updates)) {
                                                history_log($recurring_event["event_id"], "updated event content: [".implode(",", $recurring_history_updates)."]");
                                            }
                                        }
                                        if (!has_error()) {
                                            $query = "	SELECT b.*
                                                    FROM `community_courses` AS a
                                                    LEFT JOIN `community_pages` AS b
                                                    ON a.`community_id` = b.`community_id`
                                                    LEFT JOIN `community_page_options` AS c
                                                    ON b.`community_id` = c.`community_id`
                                                    WHERE c.`option_title` = 'show_history'
                                                    AND c.`option_value` = 1
                                                    AND b.`page_url` = 'course_calendar'
                                                    AND b.`page_active` = 1
                                                    AND a.`course_id` = ".$db->qstr($event_info["course_id"]);
                                            $result = $db->GetRow($query);
                                            if ($result) {
                                                $COMMUNITY_ID = $result["community_id"];
                                                $PAGE_ID = $result["cpage_id"];
                                                communities_log_history($COMMUNITY_ID, $PAGE_ID, $event_info["recurring_id"], "community_history_edit_recurring_events", 1);
                                            }

                                            add_success("You have successfully edited the recurring events associated with <strong>".html_encode($event_info["event_title"])."</strong> in the system.");

                                            application_log("success", "Recurring Events [".$event_info["recurring_id"]."] have been modified.");
                                        }
                                    }
                                }
                            }
						break;
						case "files" :
									history_log($EVENT_ID, "deleted ". count($FILE_IDS) ." resource file".(count($FILE_IDS)>1?"s":""), $PROXY_ID);
						    break;
						case "links" :
									history_log($EVENT_ID, "deleted ". count($LINK_IDS) ." resource file".(count($LINK_IDS)>1?"s":""), $PROXY_ID);
						    break;
						case "quizzes" :
									history_log($EVENT_ID, "deleted ". count($QUIZ_IDS) ." quiz".(count($QUIZ_IDS)>1?"es":""), $PROXY_ID);
						    break;
                        case "lti" :
                            continue;
                        break;
						default :
							continue;
						break;
					}
				}
                ?>
                <style>
                textarea.expandable {
                    width: 90%;
                }
                
                .datepicker, .timepicker {
                    z-index: 1151 !important;
                }
                </style>
                <a id="false-link" href="#placeholder"></a>
                <div id="placeholder" style="display: none"></div>
                <?php
                $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                ?>
                <script>
                jQuery(document).ready(function () {
//                    jQuery("#delay_release_keywords_option_date").css("margin", "0");
//                    jQuery("#delay_release_keywords").is(":checked") ? jQuery("#delay_release_keywords_controls").show() : jQuery("#delay_release_keywords_controls").hide();
//                    jQuery("#delay_release_keywords").on("click", function() {
//                        jQuery("#delay_release_keywords_controls").toggle(this.checked);
//                    });

//                    jQuery("#delay_release_option_date").css("margin", "0");

                    jQuery(".remove-hottopic").on("click", function(e) {
                        jQuery("#topic_"+jQuery(this).attr("data-id")+"_major").removeAttr("checked");
                        jQuery("#topic_"+jQuery(this).attr("data-id")+"_minor").removeAttr("checked");
                        e.preventDefault();
                    });
                });

                var ajax_url = '';
                var modalDialog;
                document.observe('dom:loaded', function() {
                    modalDialog = new Control.Modal($('false-link'), {
                        position:		'center',
                        overlayOpacity:	0.75,
                        closeOnClick:	'overlay',
                        className:		'modal',
                        fade:			true,
                        fadeDuration:	0.30,
                        beforeOpen: function(request) {
                            eval($('scripts-on-open').innerHTML);
                        },
                        afterClose: function() {
                            if (uploaded == true) {
                                location.reload();
                            }

                            maxSteps = 3;
                        }
                    });
                });

                function openDialog (url) {
                    if (url) {
                        ajax_url = url;
                        new Ajax.Request(ajax_url, {
                            method: 'get',
                            onComplete: function(transport) {
                                modalDialog.container.update(transport.responseText);
                                modalDialog.open();
                                var windowHeight = jQuery(window).outerHeight();
                                var modalHeight = jQuery("#placeholder.modal").outerHeight();
                                if (modalHeight >= windowHeight) {
                                    jQuery(document).scrollTop(0);
                                }
                            }
                        });
                    } else {
                        $('scripts-on-open').update();
                        modalDialog.open();
                    }
                }

                function confirmFileDelete() {
                    ask_user = confirm("Press OK to confirm that you would like to delete the selected file or files from this event, otherwise press Cancel.");
                    
                    if (ask_user == true) {
                        $('file-listing').submit();
                    } else {
                        return false;
                    }
                }

                function confirmLinkDelete() {
                    ask_user = confirm("Press OK to confirm that you would like to delete the selected link or links from this event, otherwise press Cancel.");

                    if (ask_user == true) {
                        $('link-listing').submit();
                    } else {
                        return false;
                    }
                }

                function confirmQuizDelete() {
                    ask_user = confirm("Press OK to confirm that you would like to detach the selected quiz or quizzes from this event, otherwise press Cancel.");

                    if (ask_user == true) {
                        $('quiz-listing').submit();
                    } else {
                        return false;
                    }
                }

                function confirmLTIDelete() {
                    ask_user = confirm("Press OK to confirm that you would like to delete the selected LTI Provider or LTI Providers from this event, otherwise press Cancel.");

                    if (ask_user == true) {
                        $('lti-listing').submit();
                    } else {
                        return false;
                    }
                }

                function updateEdChecks(obj) {
                    return true;
                }
                var text = new Array();

                function objectiveClick(element, id, default_text) {
                    if (element.checked) {
                        var textarea = document.createElement('textarea');
                        textarea.name = 'objective_text['+id+']';
                        textarea.id = 'objective_text_'+id;
                        if (text[id] != null) {
                            textarea.innerHTML = text[id];
                        } else {
                            textarea.innerHTML = default_text;
                        }
                        textarea.className = "expandable objective";
                        $('objective_'+id+"_append").insert({after: textarea});
                        setTimeout('jQuery("#objective_text_'+id+'").textareaAutoSize();', 100);
                    } else {
                        if ($('objective_text_'+id)) {
                            text[id] = $('objective_text_'+id).value;
                            $('objective_text_'+id).remove();
                        }
                    }
                }
                </script>
                <?php
                events_subnavigation($event_info,'content');

                echo "<div class=\"content-small\">".fetch_course_path($event_info["course_id"])."</div>\n";
                echo "<h1 id=\"page-top\" class=\"event-title\">".html_encode($event_info["event_title"])."</h1>\n";

                if ($SUCCESS) {
                    fade_element("out", "display-success-box");
                    echo display_success();
                }

                if ($NOTICE) {
                    echo display_notice();
                }

                if ($ERROR) {
                    echo display_error();
                }
                ?>
                <form id="content_form" class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/events?<?php echo replace_query(); ?>" method="post">
                    <input type="hidden" name="type" value="content" />
                    <a name="event-details-section"></a>
                    <div id="event-details-section">
                        <table class="table" summary="Learning Event Setup">
                            <colgroup>
                                <col style="width: 20%" />
                                <col style="width: 80%" />
                            </colgroup>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="borderless">
                                        <input type="submit" class="btn btn-primary pull-right" value="Save" />
                                    </td>
                                </tr>
                            </tfoot>
                            <tbody>
                                <tr>
                                    <td>Event Date &amp; Time</td>
                                    <td><?php echo date(DEFAULT_DATE_FORMAT, $event_info["event_start"]); ?></td>
                                </tr>
                                <tr>
                                    <td>Event Duration</td>
                                    <td><?php echo (($event_info["event_duration"]) ? $event_info["event_duration"]." minutes" : "To Be Announced"); ?></td>
                                </tr>
                                <tr>
                                    <td>Event Location</td>
                                    <?php

                                    ?>
                                    <td><?php echo (($event_info["event_location"]) ? $event_info["event_location"] : "To Be Announced"); ?></td>
                                </tr>
                                <?php
                                if ($event_audience_type == "grad_year") {
                                    $query		= "	SELECT a.`event_id`, a.`event_title`, b.`audience_value` AS `event_grad_year`
                                                    FROM `events` AS a
                                                    LEFT JOIN `event_audience` AS b
                                                    ON b.`event_id` = a.`event_id`
                                                    JOIN `courses` AS c
                                                    ON a.`course_id` = c.`course_id`
                                                    AND c.`organisation_id` = ".$db->qstr($event_info["organisation_id"])."
                                                    WHERE (a.`event_start` BETWEEN ".$db->qstr($event_info["event_start"])." AND ".$db->qstr(($event_info["event_finish"] - 1)).")
                                                    AND a.`event_id` <> ".$db->qstr($event_info["event_id"])."
                                                    AND b.`audience_type` = 'grad_year'
                                                    AND b.`audience_value` = ".$db->qstr((int) $associated_grad_year)."
                                                    ORDER BY a.`event_title` ASC";
                                    $results	= $db->GetAll($query);
                                    if ($results) {
                                        echo "<tr>\n";
                                        echo "	<td colspan=\"2\">&nbsp;</td>\n";
                                        echo "</tr>\n";
                                        echo "<tr>\n";
                                        echo "	<td style=\"vertical-align: top\">Overlapping Event".((count($results) != 1) ? "s" : "")."</td>\n";
                                        echo "	<td>\n";
                                        foreach ($results as $result) {
                                            echo "	<a href=\"".ENTRADA_URL."/admin/events?id=".$result["event_id"]."&section=content\">".html_encode($result["event_title"])."</a><br />\n";
                                        }
                                        echo "	</td>\n";
                                        echo "</tr>\n";
                                    }
                                }
                                ?>
                                <tr>
                                    <td colspan="2">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: top">Associated Faculty</td>
                                    <td>
                                        <?php
                                        $query		= "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, a.`contact_role`, b.`email`
                                                        FROM `event_contacts` AS a
                                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                                        ON b.`id` = a.`proxy_id`
                                                        WHERE a.`event_id` = ".$db->qstr($event_info["event_id"])."
                                                        AND b.`id` IS NOT NULL
                                                        ORDER BY a.`contact_order` ASC";
                                        $results	= $db->GetAll($query);
                                        if ($results) {
                                            foreach ($results as $key => $result) {
                                                switch ($result["contact_role"]) {
                                                case "ta":
                                                    $display_contact_role = "Teacher's Assistant";
                                                    break;
                                                case "teacher":
                                                    $display_contact_role = $translate->_("Teacher");
                                                    break;
                                                default:
                                                    $display_contact_role = html_encode(ucwords($result["contact_role"]));
                                                    break;
                                                }
                                                echo "<a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["fullname"])."</a> - ".$display_contact_role."<br />\n";
                                            }
                                        } else {
                                            echo "To Be Announced";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="borderless" style="vertical-align: top"><label for="eventtype_ids" class="form-required"><?php echo $translate->_("Event Types"); ?></label></td>
                                    <td id="event-type-search-container" class="borderless">
                                        <script>
                                            var event_types = [];
                                        </script>

                                        <?php
                                        if ((is_array($event_eventtypes_list)) && (count($event_eventtypes_list))) {
                                            foreach ($event_eventtypes_list as $eventtype) {
                                                $description = nl2br($eventtype["description"]);
                                                $description = str_replace(array("\r\n", "\n\r", "\n", "\r"), "", $description);
                                                ?>

                                                <script>
                                                    var event_type = [];
                                                    event_type["target_id"] = "<?php echo (int) $eventtype["id"]; ?>";
                                                    event_type["target_label"] = "<?php echo html_encode($eventtype["title"]); ?>";
                                                    event_type["description"] = "<?php echo addslashes($description); ?>";
                                                    event_types.push(event_type);
                                                </script>

                                                <?php
                                            }
                                            ?>

                                            <button id="eventtype_ids" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse Event Types"); ?><i class="icon-chevron-down btn-icon pull-right"></i></button>

                                            <?php
                                        } else {
                                            echo display_error("No Event Types were found. You will need to add at least one Event Type before continuing.");
                                        }
                                        ?>

                                        <div id="duration_notice" class="content-small">Use the list above to select the different components of this event. When you select one, it will appear here and you can change the order and duration.</div>
                                        <?php
                                        echo "<ol id=\"duration_container\" class=\"sortableList\" style=\"display: none\">\n";
                                        if (is_array($event_eventtypes)) {
                                            foreach ($event_eventtypes as $eventtype) {
                                                echo "<li id=\"type_".(int) $eventtype["eventtype_id"]."\" class=\"\">".html_encode($eventtype["eventtype_title"])."
                                                    <a href=\"#\" onclick=\"$(this).up().remove(); cleanupList(); return false;\" class=\"remove\">
                                                        <img src=\"".ENTRADA_RELATIVE."/images/action-delete.gif\">
                                                    </a>
                                                    <span class=\"duration_segment_container\">
                                                        Duration: <input type=\"text\" class=\"input-mini duration_segment\" name=\"duration_segment[]\" onchange=\"cleanupList();\" value=\"".(int) $eventtype["duration"]."\"> minutes
                                                    </span>
                                                </li>";
                                            }
                                        echo "</ol>";
                                        }
                                        ?>
                                        <div id="total_duration" class="content-small">Total time: 0 minutes.</div>
                                        <input id="eventtype_duration_order" name="eventtype_duration_order" style="display: none;" />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="borderless">
                                        <label for="event_description" class="form-nrequired">Event Description</label>
                                        <textarea id="event_description" name="event_description" style="width: 100%; height: 100px" cols="70" rows="10"><?php echo html_encode(trim(strip_selected_tags($event_info["event_description"], array("font")))); ?></textarea>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" class="borderless">
                                        <label for="event_message" class="form-nrequired">Required Preparation</label>
                                        <textarea id="event_message" name="event_message" style="width: 100%; height: 100px" cols="70" rows="10"><?php echo html_encode(trim(strip_selected_tags($event_info["event_message"], array("font")))); ?></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    /**
                     * Test to see if the MeSH tables have been loaded or not,
                     * since this is an optional Entrada feature.
                     */
                    $query = "SELECT 1 FROM `mesh_terms` LIMIT 1";
                    if ($db->GetRow($query)) {
                        ?>
                        <a name="event-keywords-section"></a>
                        <h1 class="collapsable" title="Event Keywords Section">Event Keywords</h1>
                        <div id="event-keywords-section">
                            <div class="row-fluid space-below">
                                <div id="keyword-display" class="nl-form">
                                    I want to
                                    <select id="keywords_release_date" name="keywords_release_date">
                                        <option value="heading" disabled="disabled">Learner View Release Options</option>
                                        <option value="now"<?php echo ($PROCESSED["keywords_release_date"] === 0 && !$PROCESSED["delay_release_keywords"]) ? " selected=\"selected\"" : ""; ?>>release all keywords now</option>
                                        <option value="delay"<?php echo ($PROCESSED["keywords_release_date"] > 0 || $PROCESSED["delay_release_keywords"]) ? " selected=\"selected\"" : ""; ?>>delay the release of all keywords</option>
                                        <option value="never"<?php echo ($PROCESSED["keywords_release_date"] === null && !$PROCESSED["delay_release_keywords"]) ? " selected=\"selected\"" : ""; ?>>never release keywords</option>
                                    </select>
                                    on the Learner View.
                                    <div class="nl-overlay"></div>
                                </div>
                                <script type="text/javascript">
                                    var keywordDisplay = new NLForm(document.getElementById("keyword-display"));
                                </script>
                            </div>
                            <div class="row-fluid">
                                <div id="delay_release_keywords_controls" class="space-below<?php echo ($PROCESSED["keywords_release_date"] > 0 || $PROCESSED["delay_release_keywords"]) ? "" : " hide"; ?>">
                                    <?php echo Entrada_Utilities::generate_calendar("delay_release_keywords_option", "Delay release until", true, $PROCESSED["keywords_release_date"], true, false, false, false, false); ?>
                                </div>
                            </div>

                            <div class="keywords half left">
                                <h3>Keyword Search</h3>
                                <div>Search MeSH Keywords
                                    <input id="search" autocomplete="off" type="text" name="keyword" />
                                    <input id="event_id" type="hidden" name="event_id" value="<?php echo $EVENT_ID; ?>" />
                                </div>

                                <div id="search_results">
                                    <div id="inserted"></div>
                                    <div id="results"><ul></ul></div>
                                </div>
                            </div>
                            <div class="mapped_keywords right">
                                <h3>Attached Keywords</h3>
                                <div class="clearfix">
                                    <ul class="page-action" style="float: right">
                                        <div class="row-fluid space-below">
                                            <a href="javascript:void(0)" class="keyword-toggle btn btn-success btn-small pull-right" keyword-toggle="show" id="toggle_sets">
                                                <i class="icon-plus-sign icon-white"></i>
                                                 Show Keyword Search
                                             </a>
                                        </div>
                                    </ul>
                                </div>
                                <div id="tagged">
                                    <div id="right1">
                                        <ul>
                                            <?php
                                            $query = "  SELECT ek.`keyword_id`, d.`descriptor_name`
                                                        FROM `event_keywords` AS ek
                                                        JOIN `mesh_descriptors` AS d
                                                        ON ek.`keyword_id` = d.`descriptor_ui`
                                                        AND ek.`event_id` = " . $db->qstr($EVENT_ID) . "
                                                        ORDER BY `descriptor_name`";
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach($results as $result) {
                                                    echo "<li data-dui=\"" . $result['keyword_id'] . "\" data-dname=\"" . $result['descriptor_name'] . "\" id=\"tagged_keyword\" onclick=\"removeval(this, '" . $result['keyword_id'] . "')\"><i class=\"icon-minus-sign \"></i> " . $result['descriptor_name'] . "</li>";
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="delete_keywords[]" id="delete_keywords" value=""/>
                            <input type="hidden" name="add_keywords[]" id="add_keywords" value=""/>
                            <div style="clear:both;"></div>
                            <div class="pull-right">
                                <input type="submit" value="Save" class="btn btn-primary" />
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                    <a name="event-objectives-section"></a>
                    <h1 class="collapsable" title="<?php echo $translate->_("Event Objectives Section"); ?>"><?php echo $translate->_("Event Objectives"); ?></h1>
                    <div id="event-objectives-section">
                        <div class="row-fluid space-below">
                            <div id="objective-display" class="nl-form">
                                I want to
                                <select id="objectives_release_date" name="objectives_release_date">
                                    <option value="heading" disabled="disabled">Learner View Release Options</option>
                                    <option value="now"<?php echo ($PROCESSED["objectives_release_date"] === 0 && !$PROCESSED["delay_release"]) ? " selected=\"selected\"" : ""; ?>>release all objectives now</option>
                                    <option value="delay"<?php echo ($PROCESSED["objectives_release_date"] > 0 || $PROCESSED["delay_release"]) ? " selected=\"selected\"" : ""; ?>>delay the release of all objectives</option>
                                    <option value="never"<?php echo ($PROCESSED["objectives_release_date"] === null && !$PROCESSED["delay_release"]) ? " selected=\"selected\"" : ""; ?>>never release objectives</option>
                                </select>
                                on the Learner View.
                                <div class="nl-overlay"></div>
                            </div>
                            <script type="text/javascript">
                                var objectiveDisplay = new NLForm(document.getElementById("objective-display"));
                            </script>
                        </div>
                        <div class="row-fluid">
                            <div id="delay_release_controls" class="space-below<?php echo ($PROCESSED["objectives_release_date"] > 0 || $PROCESSED["delay_release"]) ? "" : " hide"; ?>">
                                <?php echo Entrada_Utilities::generate_calendar("delay_release_option", "Delay release until", true, $PROCESSED["objectives_release_date"], true, false, false, false, false); ?>
                            </div>
                        </div>

                        <h2 title="Freetext Objectives Section">Free-Text Objectives</h2>
                        <div id="freetext-objectives-section">
                            <textarea id="event_objectives" name="event_objectives" style="width: 100%; height: 100px" cols="70" rows="10"><?php echo html_encode(trim(strip_selected_tags($event_info["event_objectives"], array("font")))); ?></textarea>
                        </div>

                        <?php
                        $query = "	SELECT a.* FROM `global_lu_objectives` a
                                    JOIN `objective_audience` b
                                    ON a.`objective_id` = b.`objective_id`
                                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                    WHERE (
                                            (b.`audience_value` = 'all')
                                            OR
                                            (b.`audience_type` = 'course' AND b.`audience_value` = ".$db->qstr($COURSE_ID).")
                                            OR
                                            (b.`audience_type` = 'event' AND b.`audience_value` = ".$db->qstr($EVENT_ID).")
                                        )
                                    AND a.`objective_parent` = '0'
                                    AND a.`objective_active` = '1'";
                        $objectives = $db->GetAll($query);

                        if ($objectives) {
                            $objective_name = $translate->_("events_filter_controls");
                            $hierarchical_name = $objective_name["co"]["global_lu_objectives_name"];
                            ?>
                            <style type="text/css">
                                .mapped-objective{
                                    padding-left: 30px!important;
                                }
                            </style>
                            <div class="objectives half left">
                                <h3><?php echo $translate->_("Curriculum Tag Sets"); ?></h3>
                                <ul class="tl-objective-list" id="objective_list_0">
                                <?php
                                foreach($objectives as $objective) {
                                    ?>
                                    <li class = "objective-container objective-set"
                                        id = "objective_<?php echo $objective["objective_id"]; ?>"
                                        data-list="<?php echo $objective["objective_name"] == $hierarchical_name?'hierarchical':'flat'; ?>"
                                        data-id="<?php echo $objective["objective_id"]; ?>">
                                        <?php $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]); ?>
                                        <div 	class="objective-title"
                                                id="objective_title_<?php echo $objective["objective_id"]; ?>"
                                                data-title="<?php echo $title;?>"
                                                data-id = "<?php echo $objective["objective_id"]; ?>"
                                                data-code = "<?php echo $objective["objective_code"]; ?>"
                                                data-name = "<?php echo $objective["objective_name"]; ?>"
                                                data-description = "<?php echo $objective["objective_description"]; ?>">
                                            <h4><?php echo $title; ?></h4>
                                        </div>
                                        <div class="objective-controls" id="objective_controls_<?php echo $objective["objective_id"];?>">
                                        </div>
                                        <div class="objective-children" id="children_<?php echo $objective["objective_id"]; ?>">
                                            <ul class="objective-list" id="objective_list_<?php echo $objective["objective_id"]; ?>"></ul>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                                </ul>
                            </div>
                            <?php
                            $query = "	SELECT a.*, COALESCE(b.`objective_details`,a.`objective_description`) AS `objective_description`, COALESCE(b.`objective_type`,c.`objective_type`) AS `objective_type`,
                                    b.`importance`,c.`objective_details`, COALESCE(c.`eobjective_id`,0) AS `mapped`,
                                    COALESCE(b.`cobjective_id`,0) AS `mapped_to_course`
                                    FROM `global_lu_objectives` a
                                    LEFT JOIN `course_objectives` b
                                    ON a.`objective_id` = b.`objective_id`
                                    AND b.`active` = '1'
                                    AND b.`course_id` = ".$db->qstr($COURSE_ID)."
                                    LEFT JOIN `event_objectives` c
                                    ON c.`objective_id` = a.`objective_id`
                                    AND c.`event_id` = ".$db->qstr($EVENT_ID)."
                                    WHERE a.`objective_active` = '1'
                                    AND (c.`event_id` = ".$db->qstr($EVENT_ID)." OR b.`course_id` = ".$db->qstr($COURSE_ID).")
                                    GROUP BY a.`objective_id`
                                    ORDER BY a.`objective_id` ASC";
                            $mapped_objectives = $db->GetAll($query);
                            $primary = false;
                            $secondary = false;
                            $tertiary = false;
                            $hierarchical_objectives = array();
                            $flat_objectives = array();
                            $explicit_event_objectives = false;//array();
                            $mapped_event_objectives = array();
                            if ($mapped_objectives) {
                                foreach ($mapped_objectives as $objective) {
                                    //if its mapped to the event, but not the course, then it belongs in the event objective list
                                    //echo $objective["objective_name"].' is '.$objective["mapped"].' and '.$objective["mapped_to_course"]."<br/>";
                                    if ($objective["mapped"] && !$objective["mapped_to_course"]) {
                                        if (!event_objective_parent_mapped_course($objective["objective_id"],$EVENT_ID)) {
                                            $explicit_event_objectives[] = $objective;
                                        } else {
                                            if ($objective["objective_type"] == "course") {
                                                //$objective_id = $objective["objective_id"];
                                                $hierarchical_objectives[] = $objective;
                                            } else {
                                                $flat_objectives[] = $objective;
                                            }
                                        }
                                    } else {
                                        if ($objective["objective_type"] == "course") {
                                            //$objective_id = $objective["objective_id"];
                                            $hierarchical_objectives[] = $objective;
                                        } else {
                                            $flat_objectives[] = $objective;
                                        }
                                    }

                                    if ($objective["mapped"]) {
                                        $mapped_event_objectives[] = $objective;
                                    }
                                }
                            }
                            ?>

                            <div class="mapped_objectives right droppable" id="mapped_objectives" data-resource-type="event" data-resource-id="<?php echo $EVENT_ID;?>">
                                <h2><?php echo $translate->_("Mapped Objectives"); ?></h2>

                                <div class="row-fluid space-below">
                                    <a href="javascript:void(0)" class="mapping-toggle btn btn-success btn-small pull-right" data-toggle="show" id="toggle_sets"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Map Additional Objectives"); ?></a>
                                </div>
                                <?php
                                if ($hierarchical_objectives) {
                                    // function loads bottom leaves and displays them
                                    event_objectives_display_leafs($hierarchical_objectives, $COURSE_ID, $EVENT_ID);
                                }
                                if ($flat_objectives) {
                                    ?>
                                    <div id="clinical-list-wrapper">
                                        <a name="clinical-objective-list"></a>
                                        <h3 id="flat-toggle"  title="<?php echo $translate->_("Clinical Objective List"); ?>" class="collapsable <?php echo empty($objective_name["cp"]["global_lu_objectives_name"]) ? "collapsed" : ""; ?> list-heading"><?php echo $objective_name["cp"]["global_lu_objectives_name"] ? $objective_name["cp"]["global_lu_objectives_name"] : "Other " . $translate->_("Objectives"); ?></h3>
                                        <div id="clinical-objective-list">
                                            <ul class="objective-list mapped-list" id="mapped_flat_objectives" data-importance="flat">
                                                <?php
                                                foreach ($flat_objectives as $objective) {
                                                    $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                                    ?>
                                                    <li class = "mapped-objective"
                                                        id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                                        data-id = "<?php echo $objective["objective_id"]; ?>"
                                                        data-title="<?php echo $title;?>"
                                                        data-description="<?php echo htmlentities($objective["objective_description"]);?>">
                                                        <strong><?php echo $title; ?></strong>
                                                        <div class="objective-description">
                                                            <?php
                                                            $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                                            if ($set) {
                                                                echo "From the " . $translate->_("Curriculum Tag Set") . ": <strong>".$set["objective_name"]."</strong><br/>";
                                                            }

                                                            echo $objective["objective_description"];
                                                            ?>
                                                        </div>

                                                        <div class="event-objective-controls">
                                                            <input type="checkbox" class="checked-mapped" id="check_mapped_<?php echo $objective['objective_id'];?>" value="<?php echo $objective['objective_id'];?>" <?php echo $objective["mapped"]?' checked="checked"':''; ?>/>
                                                        </div>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div id="event-list-wrapper" <?php echo ($explicit_event_objectives)?'':' style="display:none;"';?>>
                                    <a name="event-objective-list"></a>
                                    <h2 id="event-toggle" title="<?php echo $translate->_("Event Objective List"); ?>" class="collapsable collapsed list-heading"><?php echo $translate->_("Event Specific Objectives"); ?></h2>
                                    <div id="event-objective-list">
                                        <ul class="objective-list mapped-list" id="mapped_event_objectives" data-importance="event">
                                            <?php
                                            if ($explicit_event_objectives) {
                                                foreach ($explicit_event_objectives as $objective) {
                                                    $title = ($objective["objective_code"] ? $objective["objective_code"] . ': ' . $objective["objective_name"] : $objective["objective_name"]);
                                                    ?>
                                                    <li class = "mapped-objective"
                                                        id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                                        data-id = "<?php echo $objective["objective_id"]; ?>"
                                                        data-title="<?php echo $title;?>"
                                                        data-description="<?php echo htmlentities($objective["objective_description"]);?>"
                                                        data-mapped="<?php echo $objective["mapped_to_course"]?1:0;?>">
                                                        <strong><?php echo $title; ?></strong>
                                                        <div class="objective-description">
                                                            <?php
                                                            $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                                            if ($set) {
                                                                echo "From the " . $translate->_("Curriculum Tag Set") . ": <strong>".$set["objective_name"]."</strong><br/>";
                                                            }

                                                            echo $objective["objective_description"];
                                                            ?>
                                                        </div>

                                                        <div class="event-objective-controls">
                                                            <img 	src="<?php echo ENTRADA_RELATIVE;?>/images/action-delete.gif"
                                                                    class="objective-remove list-cancel-image"
                                                                    id="objective_remove_<?php echo $objective["objective_id"];?>"
                                                                    data-id="<?php echo $objective["objective_id"];?>">
                                                        </div>
                                                    </li>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <select id="checked_objectives_select" name="checked_objectives[]" multiple="multiple" style="display:none;">
                                    <?php
                                    if ($mapped_event_objectives) {
                                        foreach ($mapped_event_objectives as $objective) {
                                            if ($objective["objective_type"] == "course") {
                                                $title = ($objective["objective_code"] ? $objective["objective_code"] . ': ' . $objective["objective_name"] : $objective["objective_name"]);
                                                ?>
                                                <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <select id="clinical_objectives_select" name="clinical_presentations[]" multiple="multiple" style="display:none;">
                                    <?php
                                    if ($mapped_event_objectives) {
                                        foreach($mapped_event_objectives as $objective){
                                            if($objective["objective_type"] == "event") {
                                                $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                                ?>
                                                <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="clearfix"></div>

                            <div class="pull-right">
                                <input type="submit" class="btn btn-primary" value="Save" />
                            </div>
                            <?php
                        }

                        $query = "	SELECT a.`topic_id`,a.`topic_name`, e.`topic_coverage`,e.`topic_time`
                                    FROM `events_lu_topics` AS a
                                    LEFT JOIN `topic_organisation` AS b
                                    ON a.`topic_id` = b.`topic_id`
                                    LEFT JOIN `courses` AS c
                                    ON b.`organisation_id` = c.`organisation_id`
                                    LEFT JOIN `events` AS d
                                    ON c.`course_id` = d.`course_id`
                                    LEFT JOIN `event_topics` AS e
                                    ON d.`event_id` = e.`event_id`
                                    AND a.`topic_id` = e.`topic_id`
                                    WHERE d.`event_id` = ".$db->qstr($EVENT_ID)."
                                    ORDER BY a.`topic_name`";
                        $topic_results = $db->GetAll($query);
                        if ($topic_results) {
                            ?>
                        <div style="clear:both;"></div>
                            <a name="event-topics-section"></a>
                            <h2 class="collapsable collapsed" title="Event Topics Section">Event Topics</h2>
                            <div id="event-topics-section">
                                <div class="content-small">
                                    <p>Please check off a topic as <strong>MAJOR</strong> if it is encompassed in a learning objective of your session, or if you have taught enough about it that it would be reasonable to include an assessment item about the topic.</p>
                                    <p>Please check off a topic as <strong>MINOR</strong> if you mentioned the topic but only briefly.</p>
                                </div>
                                <table style="width: 100%" cellspacing="0" summary="List of ED10">
                                    <colgroup>
                                        <col style="width: 76%" />
                                        <col style="width: 8%" />
                                        <col style="width: 8%" />
                                        <col style="width: 8%" />
                                    </colgroup>
                                    <tfoot>
                                    <tr>
                                        <td colspan="4" style="text-align: right; padding-top: 5px">
                                            <input type="submit" class="btn btn-primary" value="Save" />
                                        </td>
                                    </tr>
                                    </tfoot>
                                    <tr>
                                        <td><span style="font-weight: bold; color: #003366;">Hot Topic</span></td>
                                        <td align="center"><span style="font-weight: bold; color: #003366;">Major</span></td>
                                        <td align="center"><span style="font-weight: bold; color: #003366;">Minor</span></td>
                                        <td align="center"><span style="font-weight: bold; color: #003366;">Remove</span></td>
                                    </tr>
                                    <?php
                                    foreach ($topic_results as $topic_result) {
                                        echo "<tr>\n";
                                        echo "	<td>".html_encode($topic_result["topic_name"])."</td>\n";
                                        echo "	<td align=\"center\">";
                                        echo "		<input type=\"radio\" id=\"topic_".$topic_result["topic_id"]."_major\" name=\"event_topic[".$topic_result["topic_id"]."]\" value=\"major\" onclick=\"updateEdChecks(this)\"".(($topic_result["topic_coverage"] == "major") ? " checked=\"checked\"" : "")." />";
                                        echo "	</td>\n";
                                        echo "	<td align=\"center\">";
                                        echo "		<input type=\"radio\" id=\"topic_".$topic_result["topic_id"]."_minor\" name=\"event_topic[".$topic_result["topic_id"]."]\" value=\"minor\" ".(($topic_result["topic_coverage"] == "minor") ? " checked=\"checked\"" : "")."/>";
                                        echo "	</td>\n";
                                        echo "  <td align=\"center\"><a href=\"#\" class=\"remove-hottopic\" data-id=\"".$topic_result["topic_id"]."\"><i class=\"icon-remove\"></i></a></td>";
                                        echo "</tr>\n";
                                    }
                                    echo "<tr><td colspan=\"3\">&nbsp;</td></tr>";
                                    ?>
                                </table>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                    if (isset($recurring_events) && $recurring_events) {
                        $sidebar_html = "<div class=\"content-small\">Please select which fields (if any) you would like to apply to related recurring events: </div>\n";
                        $sidebar_html .= "<div class=\"pad-left\">\n";
                        $sidebar_html .= "  <ul class=\"menu none\">\n";
                        $sidebar_html .= "      <li><label for=\"cascade_event_description\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_description\" id=\"cascade_event_description\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Event Description</label></li>\n";
                        $sidebar_html .= "      <li><label for=\"cascade_event_message\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_message\" id=\"cascade_event_message\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Required Preparation</label></li>\n";
                        $sidebar_html .= "      <li><label for=\"cascade_mesh_keywords\" class=\"checkbox\"><input type=\"checkbox\" value=\"mesh_keywords\" id=\"cascade_mesh_keywords\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Event Keywords</label></li>\n";
                        $sidebar_html .= "      <li><label for=\"cascade_event_objectives\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_objectives\" id=\"cascade_event_objectives\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Free-Text Objectives</label></li>\n";
                        $sidebar_html .= "      <li><label for=\"cascade_mapped_objectives\" class=\"checkbox\"><input type=\"checkbox\" value=\"mapped_objectives\" id=\"cascade_mapped_objectives\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> " . $translate->_("Event Objectives") . "</label></li>\n";
                        $sidebar_html .= "      <li><label for=\"cascade_event_topics\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_topics\" id=\"cascade_event_topics\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Event Topics</label></li>\n";
                        $sidebar_html .= "  </ul>\n";
                        $sidebar_html .= "</div>\n";
                        $sidebar_html .= "<div><strong><a href=\"#recurringEvents\" data-toggle=\"modal\" data-target=\"#recurringEvents\"><i class=\"icon-edit\"></i> <span id=\"recurring_events_count\">".(isset($_POST["recurring_event_ids"]) && @count($_POST["recurring_event_ids"]) ? @count($_POST["recurring_event_ids"]) : @count($recurring_events))."</span> Recurring Events Selected</a></strong></div>";
                        new_sidebar_item("Recurring Events", $sidebar_html, "recurring-events-sidebar");
                        ?>
                        <style type="text/css">
                            #recurring-events-sidebar.fixed {
                                position: fixed;
                                top: 20px;
                                z-index: 1;
                                width: 22%;
                                max-width: 313px;
                                min-width: 206px;
                            }
                        </style>
                        <script type="text/javascript">
                            var shown = false;
                            jQuery(document).ready(function () {
                                var top = jQuery('#recurring-events-sidebar').offset().top - parseFloat(jQuery('#recurring-events-sidebar').css('marginTop').replace(/auto/, 100)) + 320;
                                jQuery(window).scroll(function (event) {
                                    var y = jQuery(this).scrollTop();
                                    if (y >= top) {
                                        jQuery('#recurring-events-sidebar').addClass('fixed');
                                    } else {
                                        jQuery('#recurring-events-sidebar').removeClass('fixed');
                                    }
                                });
                                if (jQuery(this).scrollTop() >= top) {
                                    jQuery('#recurring-events-sidebar').addClass('fixed');
                                }
                            });

                            function toggleRecurringEventField(checked, fieldname) {
                                if (checked && jQuery('#update_' + fieldname).length < 1) {
                                    jQuery('#content_form').append('<input type="hidden" name="update_recurring_fields[]" value="'+fieldname+'" id="update_'+fieldname+'" />');
                                } else if (!checked && jQuery('#update_' + fieldname).length >= 1) {
                                    jQuery('#update_' + fieldname).remove();
                                }
                            }
                        </script>
                        <div id="recurringEvents" class="modal fade hide" tabindex="-1" role="dialog" aria-labelledby="Select Associated Recurring Events" aria-hidden="true">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h3>Associated Recurring Events</h3>
                            </div>
                            <div class="modal-body">
                                <div id="display-generic-box" class="alert alert-block alert-info">
                                    <ul>
                                        <li>
                                            Please select which of the following related recurring events you would like to apply the selected changes to:
                                        </li>
                                    </ul>
                                </div>
                                <?php
                                foreach ($recurring_events as $recurring_event) {
                                    $recurring_event = $recurring_event->toArray();
                                    ?>
                                    <div class="row-fluid">
                                    <span class="span1">
                                        &nbsp;
                                    </span>
                                    <span class="span1">
                                        <input type="checkbox" id="recurring_event_<?php echo $recurring_event["event_id"] ?>" class="recurring_events" onclick="jQuery('#recurring_events_count').html(jQuery('.recurring_events:checked').length)" name="recurring_event_ids[]" value="<?php echo $recurring_event["event_id"]; ?>"<?php echo (!isset($_POST["recurring_event_ids"]) || in_array($recurring_event["event_id"], $_POST["recurring_event_ids"]) ? " checked=\"checked\"" : ""); ?> />
                                    </span>
                                    <label class="span10" for="recurring_event_<?php echo $recurring_event["event_id"] ?>">
                                        <strong class="space-right">
                                            <?php echo html_encode($recurring_event["event_title"]); ?>
                                        </strong>
                                        [<span class="content-small"><?php echo html_encode(date(DEFAULT_DATE_FORMAT, $recurring_event["event_start"])); ?></span>]
                                    </label>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="modal-footer">
                                <a href="#" class="btn" data-dismiss="modal">Close</a>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </form>
                <a name="event-resources-section" id="event-resources-section"></a>
                <h1 class="space-above large">Event Resources</h1>
                <div id="event-resources-delete-confirmation"></div>
                <div id="event-resources-container-loading" class="hide">
                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                    <p id="event_resources_loading_msg">Loading Event Resources...</p>
                </div>
                <div id="event-resources-container">
                    <div class="row">
                        <a  href="#" id="event-resource-toggle" class="btn pull-right">Add a Resource</a>
                    </div>
                    <div id="event-resources-msgs"></div>
                </div>
                    <?php
                    $attached_gradebook_assessment = Models_Assessment_AssessmentEvent::fetchRowByEventID($EVENT_ID);
                    if ($attached_gradebook_assessment) {
                        $assessment = $attached_gradebook_assessment->getAssessment();
                        if ($assessment) { ?>
                            <div class="space-below">
                                <h3>Attached Gradebook Assessments</h3>
                                <table class="tableList" cellspacing="0" summary="List of Attached LTI Providers">
                                    <colgroup>
                                        <col class="modified wide"/>
                                        <col class="title" />
                                        <col class="title" />
                                        <col class="date" />
                                        <col class="date" />
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <td class="modified">&nbsp;</td>
                                            <td class="title sortedASC"><div class="noLink">Assessment Name</div></td>
                                            <td class="title">Assessment Type</td>
                                            <td class="date-small">Assessment Points</td>
                                            <td class="date-small">Assessment Weight</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td></td>
                                            <td><a href="<?php echo ENTRADA_URL; ?>/admin/gradebook/assessments/?section=grade&id=<?php echo $assessment->getCourseID(); ?>&assessment_id=<?php echo $assessment->getAssessmentID(); ?>"><strong><?php echo $assessment->getName(); ?></strong></a></td>
                                            <td><?php echo $assessment->getType(); ?></td>
                                            <td><?php echo $assessment->getNumericGradePointsTotal(); ?></td>
                                            <td><?php echo $assessment->getGradeWeighting(); ?>%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php
                        }
                    }
                    ?>

                <div id="delete-event-resource-modal" class="modal scrollable fade hide" style="max-height: 314px;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Delete Event Resource</h4>
                            </div>
                            <div class="modal-body">
                                <div id="delete-event-resource-msgs"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                                <button id="delete-event-resource" type="button" class="btn btn-danger">Delete Resource</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="event-resource-view-modal" class="modal fade hide">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                <h4 id="event-resource-view-modal-heading" class="modal-title"></h4>
                            </div>
                            <div class="modal-body">
                                <div id="event-resource-view-msgs"></div>
                                <table id="resource-views-table" class="table table-striped table-bordered datatable">
                                    <thead>
                                        <tr>
                                            <th width="40%">Name</th>
                                            <th width="15%">Views</th>
                                            <th width="45%">Last Viewed</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                                <div id="event-resources-views-loading" class="hide">
                                    <p id="event_resources_views-loading_msg" class="muted text-center">
                                        <i class="fa fa-circle-o-notch fa-spin fa-fw"></i>
                                        <span class="sr-only">Loading...</span> Loading resource views...
                                    </p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="event-resource-modal" class="modal scrollable fade hide">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                <h4 id="event_resource_modal_title" class="modal-title">Add Event Resource</h4>
                            </div>
                            <div class="modal-body">
                                <form class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/events?section=api-resource-wizard" ?>" method="post" id="event_resource_form" enctype="multipart/form-data">
                                    <input id="event_id" type="hidden" name="event_id" value="<?php echo $EVENT_ID; ?>" />
                                    <input id="re_bool" type="hidden" name="re_bool" value="<?php echo $re_bool; ?>" />
                                    <input id="re_ids" type="hidden" name="re_ids" value="<?php echo htmlentities(json_encode($re_ids)); ?>" />
                                    <input id="event_resource_entity_id" type="hidden" name="event_resource_entity_id" value="" />
                                    <input id="resource_id" type="hidden" name="resource_id" value="" />
                                    <input id="resource_step" type="hidden" name="step" value="1" />
                                    <input id="resource_substep" type="hidden" name="resource_substep" value="1" />
                                    <input id="resource_next_step" type="hidden" name="next_step" value="0" />
                                    <input id="resource_previous_step" type="hidden" name="previous_step" value="0" />
                                    <input id="event_resource_type_value" type="hidden" name="event_resource_type_value" value="11" />
                                    <input id="event_resource_required_value" type="hidden" name="event_resource_required_value" value="no" />
                                    <input id="event_resource_timeframe_value" type="hidden" name="event_resource_timeframe_value" value="none" />
                                    <input id="event_resource_release_value" type="hidden" name="event_resource_release_value" value="no" />
                                    <input id="event_resource_release_start_value" type="hidden" name="event_resource_release_start_value" value="" />
                                    <input id="event_resource_release_start_time_value" type="hidden" name="event_resource_release_start_time_value" value="" />
                                    <input id="event_resource_release_finish_value" type="hidden" name="event_resource_release_finish_value" value="" />
                                    <input id="event_resource_release_finish_time_value" type="hidden" name="event_resource_release_finish_time_value" value="" />
                                    <input id="event_resource_attach_file" type="hidden" name="event_resource_attach_file" value="no" />
                                    <input id="upload" type="hidden" name="upload" value="upload" />
                                    <input id="" type="hidden" name="method" value="add" />
                                    <div id="event-resource-msgs"></div>
                                    <div id="event-resource-step"></div>
                                </form>
                                <div id="event_resource_loading">
                                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                                    <p id="event_resource_loading_msg"></p>
                                </div>
                                <div id="event_resource_drop_overlay" class="hide">
                                    <div id="event_resource_drop_box"></div>
                                    <p id="event_resource_loading_msg">Drop the selected file anywhere to upload.</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                                <button id="event-resource-previous" type="button" class="btn btn-default hide">Previous Step</button>
                                <button id="event-resource-next" type="button" class="btn btn-primary">Next Step</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        $("#eventtype_ids").advancedSearch({
                            resource_url: ENTRADA_URL,
                            filters: {
                                event_types: {
                                    label: "<?php echo $translate->_("Event Types"); ?>",
                                    data_source: event_types,
                                    mode: "radio",
                                    set_button_text_to_selected_option: false
                                }
                            },
                            no_results_text: "<?php echo $translate->_("No Event Types found matching the search criteria"); ?>",
                            parent_form: $("#content_form"),
                            width: 300
                        });

                        var popover_options = {
                            animation: false,
                            container: "body",
                            selector: "[rel=\"popover\"]",
                            html: true,
                            trigger: "hover",
                            placement: "left",
                            content: function () {
                                var target_id = $(this).attr("data-id");
                                var index;

                                for (index = 0; index < event_types.length; index++) {
                                    if (event_types[index]["target_id"] == target_id) {
                                        break;
                                    }
                                }

                                return event_types[index]["description"];
                            }
                        };

                        $("#eventtype_ids").click(function (e) {
                            $.each($("#event-type-search-container .search-filter-item"), function (index, value) {
                                $(this).attr("rel", "popover");
                            });

                            $("#event-type-search-container").on("mouseenter", ".search-filter-item", function (e) {
                                e.stopPropagation();

                                $(".popover").remove();
                                $("[rel=\"popover\"]").popover(popover_options);
                                $(this).popover("show");
                            });

                            $("#event-type-search-container").on("mouseleave", ".search-filter-item", function (e) {
                                e.stopPropagation();

                                if (!$(".search-filter-item:hover").length) {
                                    setTimeout(function () {
                                        if (!$(".popover:hover").length) {
                                            $(".popover").remove();
                                        }
                                    }, 300);
                                }
                            });

                            $("#event-type-search-container").on("click", ".search-filter-item", function (e) {
                                $(".popover").remove();
                            });
                        });

                        $("body").on("mouseleave", ".popover", function (e) {
                            e.stopPropagation();

                            setTimeout(function () {
                                if (!$(".search-filter-item:hover").length) {
                                    $(".popover").remove();
                                }
                            }, 300);
                        });

                        $("body").on("click", ".popover", function (e) {
                            $(".popover").remove();
                        });

                        $("#event-type-search-container").on("change", ".search-target-input-control", function () {
                            if ($(this).is(":checked")) {
                                var li = $(document.createElement("li")).attr({id: "type_" + this.value}).html($(this).attr("data-label"));
                                var a = $(document.createElement("a")).attr({href: "#", onclick: "$(this).up().remove(); cleanupList(); return false;"}).addClass("remove");
                                var img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/action-delete.gif"});
                                var span = $(document.createElement("span")).addClass("duration_segment_container").html("Duration: ");
                                var input = $(document.createElement("input")).attr({type: "text", name: "duration_segment[]", onchange: "cleanupList();", value: "60"}).addClass("input-mini duration_segment");

                                a.append(img);
                                span.append(input).append(" minutes");
                                li.append(a).append(span);

                                $("#duration_container").append(li);

                                cleanupList();
                                
                                $("#" + $(this).attr("data-filter") + "_" + $(this).val()).remove();
                            }
                        });
                    });

                    $$('select.ed_select_off').each(function(el) {
                        $(el).disabled = true;
                        $(el).fade({ duration: 0.3, to: 0.25 });
                    });
                </script>
                <?php
                /**
                 * Sidebar item that will provide the links to the different sections within this page.
                 */
                $sidebar_html  = "<ul class=\"menu\">\n";
                $sidebar_html .= "	<li class=\"link\"><a href=\"#event-details-section\" onclick=\"$('event-details-section').scrollTo(); return false;\" title=\"Event Setup\">" . $translate->_("Event Setup") . "</a></li>\n";
                $sidebar_html .= "	<li class=\"link\"><a href=\"#event-objectives-section\" onclick=\"$('event-objectives-section').scrollTo(); return false;\" title=\"" . $translate->_("Event Objectives") . "\">" . $translate->_("Event Objectives") . "</a></li>\n";
                $sidebar_html .= "	<li class=\"link\"><a href=\"#event-resources-section\" onclick=\"$('event-resources-section').scrollTo(); return false;\" title=\"Event Resources\">Event Resources</a></li>\n";
                $sidebar_html .= "</ul>\n";

                new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open", "1.9");
			}
		} else {
			add_error("In order to edit a event you must provide a valid event identifier. The provided ID does not exist in this system.");

			echo display_error();

			application_log("notice", "Failed to provide a valid event identifier when attempting to edit a event.");
		}
	} else {
		add_error("In order to edit a event you must provide the events identifier.");

		echo display_error();

		application_log("notice", "Failed to provide event identifier when attempting to edit a event.");
	}
}
