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
 * @author Unit: School of Medicine
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('event', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Copy Events");

	echo "<h1>Copy Events</h1>";

	$EVENT_IDS = array();

	// Error Checking
	switch($STEP) {
		case 2 :
			if ((isset($_POST["eventCopies"])) && ($event_copies = clean_input($_POST["eventCopies"], array("notags", "trim")))) {
				$event_copies = explode(',',$event_copies);
				$i = 0;
				foreach ($event_copies as $event_copy) {
					$event = explode('-',$event_copy);
					$year = (int) trim($event[1]);
					$month = (int) trim($event[2]);
					$day = (int) trim($event[3]);
					$hour = (int) trim($event[4]);
					$minute = (int) trim($event[5]);
					$EVENT_IDS[] = array((int) $event[0], mktime($hour, $minute, 0, $month, $day, $year));
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "There were no valid event dates to be copied.";
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			if ((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) {
				header("Location: ".ENTRADA_URL."/admin/events");
				exit;
			} else {
				foreach ($_POST["checked"] as $event_id) {
					$event_id = (int) trim($event_id);
					if ($event_id) {
						$EVENT_IDS[] = $event_id;
					}
				}

				if (!@count($EVENT_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid event identifiers provided to copy. Please ensure that you access this section through the event index.";
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			$copied = array();
			$USER_ID = $ENTRADA_USER->getID();

			foreach ($EVENT_IDS as $event) {
				$event_id = (int) $event[0];
				$event_start = $event[1];
				$updated_date = time();
				
				if ($event_id) {
					$query	= "	SELECT a.*
								FROM `events` AS a
								LEFT JOIN `courses` AS b
								ON b.`course_id` = a.`course_id`
								WHERE a.`event_id` = ".$db->qstr($event_id)."
								AND b.`course_active` = '1'";
					$event_info	= $db->GetRow($query);
					if ($event_info) {
						$original_start = $event_info["event_start"];

						$event_info["event_start"] = $event_start;
						$event_info["event_finish"] = ($event_start + ($event_info["event_duration"] * 60));
						$event_info["release_date"] = $event_info["release_date"] ? $event_start - ($original_start - $event_info["release_date"]) : 0;
						$event_info["release_until"] = $event_info["release_until"] ? $event_start - ($original_start - $event_info["release_until"]) : 0;
						$event_info["updated_date"] = $updated_date;
						$event_info["updated_by"] = $USER_ID;

						array_shift($event_info);
						if (($db->AutoExecute("events", $event_info, "INSERT")) && ($EVENT_ID = $db->Insert_Id())){
							$query = "	SELECT $EVENT_ID `event_id`, a.`eventtype_id`, a.`duration`
										FROM `event_eventtypes` a
										WHERE a.`event_id` = ".$db->qstr($event_id)."
										ORDER BY a.`eeventtype_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									if (!$db->AutoExecute("event_eventtypes", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>" . $translate->_("Event Type") . "</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_eventtype record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$query = "	SELECT $EVENT_ID `event_id`, a.`audience_type`, a.`audience_value`, a.`custom_time`, a.`custom_time_start`, a.`custom_time_end`, $updated_date `updated_date`, $USER_ID `updated_by`
										FROM `event_audience` a
										WHERE a.`event_id` = ".$db->qstr($event_id)."
										ORDER BY a.`eaudience_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
                                    //if a custom time is set, get the updated time and set it
                                    if ($result['custom_time']) {
                                        $custom_time_dif_start = $result['custom_time_start'] - $original_start;
                                        $custom_time_dif_end = $result['custom_time_end'] - $original_start;
                                        
                                        $result['custom_time_start'] = $event_info["event_start"] + $custom_time_dif_start;
                                        $result['custom_time_end'] = $event_info["event_start"] + $custom_time_dif_end;
                                    } else {
                                        $result['custom_time_start'] = 0;
                                        $result['custom_time_end'] = 0;
                                    }
                                    
									if (!$db->AutoExecute("event_audience", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>Event Audience</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_audience record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$query	= "	SELECT $EVENT_ID `event_id`, a.`proxy_id`, a.`contact_order`,a.`contact_role`, $updated_date `updated_date`, $USER_ID `updated_by`
										FROM `event_contacts` a
										WHERE a.`event_id` = ".$db->qstr($event_id)."
										ORDER BY a.`econtact_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									if (!$db->AutoExecute("event_contacts", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>Event Contacts</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_contact record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$query	= "	SELECT $EVENT_ID `event_id`, a.`topic_id`, a.`topic_coverage`, a.`topic_time`, $updated_date `updated_date`, $USER_ID `updated_by`
										FROM `event_topics` a
										WHERE a.`event_id` = ".$db->qstr($event_id)."
										ORDER BY a.`etopic_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									if (!$db->AutoExecute("event_topics", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>Event Topic</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_topic record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$query	= "	SELECT * FROM `event_files` WHERE `event_id` = ".$db->qstr($event_id)." ORDER BY `efile_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									if ($result["file_category"] != "podcast") {
										$original_file = FILE_STORAGE_PATH."/".$result["efile_id"];

										if (@file_exists($original_file)) {
											$result["event_id"]		= $EVENT_ID;
											$result["accesses"]		= 0;
											$result["release_date"]		= $result["release_date"] ? $event_start - ($original_start - $result["release_date"]) : 0;
											$result["release_until"]	= $result["release_until"] ? $event_start - ($original_start - $result["release_until"]) : 0;
											$result["updated_date"]		= $updated_date;
											$result["updated_by"]		= $USER_ID;

											array_shift($result);
											if (!(($db->AutoExecute("event_files", $result, "INSERT")) && ($FILE_ID = $db->Insert_Id()))) {
												$ERROR++;
												$ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

												application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
												break 2;
											} else {
                                                switch ($result["file_category"]) {
                                                    case "podcast" :
                                                        $entity_type = 1;
                                                    break;
                                                    case "lecture_notes" :
                                                        $entity_type = 5;
                                                    break;
                                                    case "lecture_slides" :
                                                        $entity_type = 6;
                                                    break;
                                                    case "other" :
                                                        $entity_type = 11;
                                                    break;
                                                    
                                                }
                                                
                                                $event_resource_entity = new Models_Event_Resource_Entity(array(
                                                    "event_id" => $EVENT_ID,
                                                    "entity_type" => $entity_type,
                                                    "entity_value" => $FILE_ID,
                                                    "release_date" => 0,
                                                    "release_until" => 0,
                                                    "updated_date" => time(),
                                                    "updated_by" => $ENTRADA_USER->getID(),
                                                    "active" => 1
                                                    )
                                                );
                                                
                                                if (!$event_resource_entity->insert()) {
                                                    $ERROR++;
                                                    $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                                    application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                                }
                                            }

											if (!copy($original_file, FILE_STORAGE_PATH."/".$FILE_ID)) {
												$ERROR++;
												$ERRORSTR[] = "Unable to copy file $original_file to new file $FILE_ID for event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

												application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
												break 2;

											}
										} else {
											$ERROR++;
											$ERRORSTR[] = "The original event file [".$original_file."] does not exist in the filesystem, so it cannot be copied.<br /><br /> There was an error copying event file from event $event_id  to $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

											application_log("error", "The original event file [".$original_file."] does not exist in the filesystem, so it cannot be copied. Event $EVENT_ID");
											break 2;
										}
									}
								}
							}

							$query	= "	SELECT * FROM `event_links` WHERE `event_id` = ".$db->qstr($event_id)." ORDER BY `elink_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									$result["event_id"]			= $EVENT_ID;
									$result["accesses"]			= 0;
									$result["release_date"]		= $result["release_date"] ? $event_start - ($original_start - $result["release_date"]) : 0;
									$result["release_until"]	= $result["release_until"] ? $event_start - ($original_start - $result["release_until"]) : 0;
									$result["updated_date"]		= $updated_date;
									$result["updated_by"]		= $USER_ID;
                                    $original_elink_id = $result["elink_id"];
									array_shift($result);
									if (!($db->AutoExecute("event_links", $result, "INSERT"))) {
										$ERROR++;
										$ERRORSTR[] = "Unable to copy link [".$result["link_title"]."] to new event_id [".$EVENT_ID."].<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to copy link [".$result["link_title"]."] to new event_id [".$EVENT_ID."]. Database said: ".$db->ErrorMsg());
										break 2;
									} else {
                                        $ELINK_ID = $db->Insert_Id();
                                        $resource = Models_Event_Resource_Entity::fetchRowByEventIDEntityValue($event_id, $original_elink_id);
                                        if ($resource) {
                                            $event_resource_entity = new Models_Event_Resource_Entity(array(
                                                "event_id" => $EVENT_ID,
                                                "entity_type" => $resource->getEntityType(),
                                                "entity_value" => $ELINK_ID,
                                                "release_date" => 0,
                                                "release_until" => 0,
                                                "updated_date" => time(),
                                                "updated_by" => $ENTRADA_USER->getID(),
                                                "active" => 1
                                                )
                                            );

                                            if (!$event_resource_entity->insert()) {
                                                $ERROR++;
                                                $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                                application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                    }
								}
							}

							$query	= "	SELECT $EVENT_ID `event_id`, a.`objective_id`, a.`objective_details`, a.`objective_type`, $updated_date `updated_date`, $USER_ID `updated_by`
										FROM `event_objectives` a
										WHERE a.`event_id` = ".$db->qstr($event_id)."
										ORDER BY a.`eobjective_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									if (!$db->AutoExecute("event_objectives", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>" . $translate->_("Event Objective") . "</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_objectives record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$query	= "	SELECT $EVENT_ID `event_id`, a.`related_type`, a.`related_value`, $updated_date `updated_date`, $USER_ID `updated_by`
										FROM `event_related` a
										WHERE a.`event_id` = ".$db->qstr($event_id)."
										ORDER BY a.`erelated_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									if (!$db->AutoExecute("event_related", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>Event Related</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_related record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

                            //copies MeSH keywords
							$query	= "	SELECT * FROM `event_keywords` WHERE `event_id` = ".$db->qstr($event_id)." ORDER BY `ekeyword_id` ASC";
							$results = $db->GetAll($query);

							if ($results) {
								foreach ($results as $result) {
                                    unset($result["ekeyword_id"]);
									$result["event_id"]			= $EVENT_ID;
                                    $result['keyword_id']       = $result['keyword_id'];
									$result["updated_date"]		= $updated_date;
									$result["updated_by"]		= $USER_ID;
									if (!$db->AutoExecute("event_keywords", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = $query . "There was an error while trying to save the selected <strong>Event Keywords</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_related record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$query	= "	SELECT * FROM `attached_quizzes` WHERE `content_type` = 'event' AND `content_id` = ".$db->qstr($event_id)." ORDER BY `aquiz_id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									$result["content_id"]		= $EVENT_ID;
									$result["accesses"]			= 0;
									$result["release_date"]		= $result["release_date"] ? $event_start - ($original_start - $result["release_date"]) : 0;
									$result["release_until"]	= $result["release_until"] ? $event_start - ($original_start - $result["release_until"]) : 0;
									$result["updated_date"]		= $updated_date;
									$result["updated_by"]		= $USER_ID;

									array_shift($result);
									if (!$db->AutoExecute("attached_quizzes", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "Unable to quiz link [".$result["quiz_title"]."] to new event_id [".$EVENT_ID."].<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to copy quiz [".$result["quiz_title"]."] to new event_id [".$EVENT_ID."]. Database said: ".$db->ErrorMsg());
										break 2;
									} else { 
                                        $AQUIZ_ID = $db->Insert_Id();
                                        $event_resource_entity = new Models_Event_Resource_Entity(array(
                                            "event_id" => $EVENT_ID,
                                            "entity_type" => 8,
                                            "entity_value" => $AQUIZ_ID,
                                            "release_date" => 0,
                                            "release_until" => 0,
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID(),
                                            "active" => 1
                                            )
                                        );

                                        if (!$event_resource_entity->insert()) {
                                            $ERROR++;
                                            $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                            application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                        }
                                    }
								}
							}

							$query	= "	SELECT * FROM `event_lti_consumers` WHERE `event_id` = ".$db->qstr($event_id)." ORDER BY `id` ASC";
							$results = $db->GetAll($query);
							if ($results) {
								foreach ($results as $result) {
									$result["event_id"]		    = $EVENT_ID;
									$result["valid_from"]		= $result["valid_from"] ? $event_start - ($original_start - $result["valid_from"]) : 0;
									$result["valid_until"]	    = $result["valid_until"] ? $event_start - ($original_start - $result["valid_until"]) : 0;
									$result["updated_date"]		= $updated_date;
									$result["updated_by"]		= $USER_ID;

									array_shift($result);
									if (!$db->AutoExecute("event_lti_consumers", $result, "INSERT")) {
										$ERROR++;
										$ERRORSTR[] = "There was an error while trying to save the selected <strong>LTI Event Consumer</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to insert a new event_lti_consumer record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
										break 2;
									}
								}
							}

							$copied[$event_id]["event_title"] = $event_info["event_title"];
                            
                            history_log($EVENT_ID, 'created this learning event.', $ENTRADA_USER->getID());
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem copying this event $event_id to $EVENT_ID into the system. The system administrator was informed of this error; please try again later.";

							application_log("error", "There was an error inserting a event $EVENT_ID. Database said: ".$db->ErrorMsg());
							break;
						}
					} else {
						add_error("Unable to located the requested event. Please ensure you select a valid event from the list on the Manage Events page and try again.");
					}
				}
			}

			$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events\\'', 5000)";

			if ($total_copied = @count($copied)) {
				$SUCCESS++;
				$SUCCESSSTR[$SUCCESS]  = "You have successfully copied ".$total_copied." event".(($total_copied != 1) ? "s" : "")." from the system:";
				$SUCCESSSTR[$SUCCESS] .= "<div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
				foreach ($copied as $result) {
					$SUCCESSSTR[$SUCCESS] .= html_encode($result["event_title"])."<br />";
				}
				$SUCCESSSTR[$SUCCESS] .= "</div>\n";
				$SUCCESSSTR[$SUCCESS] .= "You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/events\">click here</a> if you do not wish to wait.";

				echo display_success();

				application_log("success", "Successfully copied event ids: ".implode(", ", $EVENT_IDS));
			} else {
				$ERROR++;
				$ERRORSTR[] = "Unable to copy the requested events.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.";

				application_log("error", "Failed to copy all events from the copy request. Database said: ".$db->ErrorMsg());
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if ($ERROR) {
				echo display_error();
			} else {
				$total_events = count($EVENT_IDS);
				
				$query = "	SELECT a.`event_id`, a.`event_title`, a.`event_start`, a.`event_phase`, a.`release_date`, a.`release_until`, a.`updated_date`, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`, d.`organisation_id`, d.`course_id`
							FROM `events` AS a
							LEFT JOIN `event_contacts` AS b
							ON b.`event_id` = a.`event_id`
							AND b.`contact_order` = '0'
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
							ON c.`id` = b.`proxy_id`
							LEFT JOIN `courses` AS d
							ON d.`course_id` = a.`course_id`
							WHERE a.`event_id` IN (".implode(", ", $EVENT_IDS).")
							AND d.`course_active` = '1'
							ORDER BY a.`event_start` ASC";
				$results = $db->GetAll($query);
				if ($results) {
					echo display_notice(array("Select the event line".(($total_events != 1) ? "s" : "")." with the leading '+' to set event's copy new start time.<br /><br />All attached resources, contacts, etc. will be copied."));
					?>
					<script type="text/javascript">
					var copies = [];
					var tTitle = "";
					var tEv=0;
					var addActive=0;
					Array.prototype.find = function(searchStr) {
						var returnArray = false;
						for (i=0; i<this.length; i++) {
							if (typeof(searchStr) == "function") {
								if (searchStr.test(this[i])) {
									if (!returnArray) { returnArray = [] }
									returnArray.push(i);
								}
							} else {
								if (this[i]===searchStr) {
									if (!returnArray) { returnArray = [] }
									returnArray.push(i);
								}
							}
						}
						return returnArray;
					}
					function appendTableRows(row, html){
						var temp = document.createElement("div");
						var tbody = row.parentNode;
						temp.innerHTML = "<table><tbody>"+html;
						var rows = temp.firstChild.firstChild.childNodes;
						while(rows.length){
							tbody.insertBefore(rows[0], row.nextSibling);
						}
					}
					function stowRow() {
						var row = document.getElementById("dateRow");
						var tbody = row.parentNode;
						tbody.removeChild(row);
						tbody.insertBefore(row, tbody.rows[0]);
						row.style.display = "none";
						addActive=0;
						return ;
					}
					function newEventDate(id,title,start,hour,min) {
						if (addActive) { stowRow(); return; }
						addActive = 1;
						tTitle=title; tEv=id;
						var row = document.getElementById('dateRow');
						var fRow = document.getElementById("rEvent-"+tEv);
						var tbody = fRow.parentNode;
						tbody.removeChild(row);
						tbody.insertBefore(row, fRow.nextSibling);
						row.style.display="";
						setDateValue(document.getElementById("cEvent_date"),start);
						document.getElementById('cEvent_hour').value = hour;
						document.getElementById('cEvent_min').value = min;
						updateTime("cEvent");
						return;
					}
					function deleteNewEvent(rVal) {
						tRow = document.getElementById("tR"+rVal);
						tRow.parentNode.removeChild(tRow);
						copies.splice(copies.find(rVal),1);
						if (!copies.length) { document.getElementById("tSubmit").style.visibility="hidden"; }
						document.getElementById("eventCopies").value = copies.toString();
						return;
					}
					function acceptEvent() {
						var tDate = getDateValue(document.getElementById("cEvent_date"));
						stowRow();
						if (tDate.length < 10 ) return ;
						tHour = document.getElementById("cEvent_hour").value;
						tMin = document.getElementById("cEvent_min").value;
						tRow = tEv+"-"+tDate+"-"+tHour+"-"+tMin;
						if (copies.find(tRow)) {   // No duplicates
							alert('Important: Copies need unique start times.');
							return;
						}
						copies.push(tRow);
						document.getElementById("eventCopies").value = copies.toString();
						document.getElementById("tSubmit").style.visibility="visible";
						var line = "<tr id='tR"+tRow+"'><td align='center'><a href=javascript:deleteNewEvent('"+tRow+"')><img src='<?php echo ENTRADA_URL; ?>/images/action-delete.gif' width='12' height='12' alt='Remove this event copy' title='Remove this Event from copy list.' border='0' ></td><td>"+tDate+" "+tHour+":"+tMin+"</td><td style='font-size: 9px' colspan=2>to be copied (click '-' to remove)</td><td colspan=2>"+tTitle+"</td></a></tr>";
						appendTableRows(document.getElementById("rEvent-"+tEv), line);
						return;
					}
					</script>
					<form action="<?php echo ENTRADA_URL; ?>/admin/events?section=copy&amp;step=2" method="post">
					<table class="tableList" cellspacing="0" summary="List of Events" >
					<colgroup>
						<col class="modified" />
						<col class="date" />
						<col class="phase" />
						<col class="teacher" />
						<col class="title" />
						<col class="attachment" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified" style="font-size: 12px">&nbsp;</td>
							<td class="date sortedASC" style="font-size: 12px"><div class="noLink">Date &amp; Time</div></td>
							<td class="phase" style="font-size: 12px">Phase</td>
							<td class="teacher" style="font-size: 12px"><?php echo $translate->_("Teacher"); ?></td>
							<td class="title" style="font-size: 12px">Event Title</td>
							<td class="attachment" style="font-size: 12px">&nbsp;</td>
						</tr>
					</thead>
					<tfoot>
						<tr id="tSubmit" style="visibility: hidden"  >
							<td></td>
							<td colspan="3" style="padding-top: 10px">
								<input class="btn" type="button" name="Cancel" value="Cancel" onclick="history.go(-1)" />
							</td>
							<td style="padding-top: 10px; text-align: right">
								<input class="btn btn-primary" type="submit" value="Copy Events" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						echo "<tr id=\"dateRow\" style=\"background-color:#ffd;display:none\">";
						echo "<td /><td colspan=5><table><tr><td style=\"border: 0\"><table>" . generate_calendar("cEvent", "Set Copy's Date & Time", true) . "</table></td>";
						echo "<td style=\"border: 0\"><input type=\"button\" class=\"btn\" onclick=\"javascript:acceptEvent()\" value=\"Add\" style=\"vertical-align: middle\" /></td>";
						echo "</tr></table></td></tr>";
						foreach ($results as $result) {
							$url = "";
							$accessible = true;
							$administrator = false;

							if ($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result["course_id"], $result["organisation_id"]), 'update')) {
								$administrator = true;
							} else {
								$accessible = false;
							}
			
							if ($administrator) {
								//Escape event title by replacing double quotes with &quot; and single quotes with \'
								$event_title_escaped = str_replace(array('"', "'"), array('&quot;', "\\'"), $result["event_title"]);
								$url	= "javascript:newEventDate(". $result["event_id"]. ", '". $event_title_escaped . "', '".date('Y-m-d', $result["event_start"]). "', '".date('H', $result["event_start"]). "', '".date('i', $result["event_start"])."')";
								echo "<tr id=\"rEvent-".$result["event_id"]."\" class=\"event".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
								echo "	<td class=\"modified\"><a href=\"$url\"><img src=\"".ENTRADA_URL."/images/btn_add.gif\" alt=\"Copy Event and Content\" title=\"Copy Event and Content\" border=\"0\" /></a></td>\n";
								echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Date\">" : "").date(DEFAULT_DATE_FORMAT, $result["event_start"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"phase".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Intended For Phase ".html_encode($result["event_phase"])."\">" : "").html_encode($result["event_phase"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"teacher".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Primary Teacher: ".html_encode($result["fullname"])."\">" : "").html_encode($result["fullname"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Title: ".html_encode($result["event_title"])."\">" : "").html_encode($result["event_title"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"attachment\">".((false) ? "<a href=\"".ENTRADA_URL."/admin/events?section=content&amp;id=".$result["event_id"]."\"><img src=\"".ENTRADA_URL."/images/action-delete.gif\" width=\"16\" height=\"16\" alt=\"Copy Event and Content\" title=\"Copy Event and Content\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" alt=\"\" title=\"\" />")."</td>\n";
								echo "</tr>\n";
							}
						}
						?>
					</tbody>
					</table>
					<input type="hidden" id="eventCopies" name="eventCopies" />
					</form>
					<?php
				} else {
					application_log("error", "The copy of new events query returned no results... curious Database said: ".$db->ErrorMsg());
					
					header("Location: ".ENTRADA_URL."/admin/events");
					exit;	
				}
			}
		break;
	}
}
