<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for importing draft schedule information into system
 * 
 * Setup to run the end of each week in CRON.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's Univerity. All Rights Reserved.
 *
 */
@set_time_limit(0);
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

if ((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
		/**
		 * Lock present: application busy: quit
		 */
		if (!file_exists(CACHE_DIRECTORY."/import_draft.lck")) {
			if (@file_put_contents(CACHE_DIRECTORY."/import_draft.lck", "L_O_C_K")) {
				application_log("notice", "Draft import lock file created.");
				/*
				 * Fetch approved drafts
				 */
				$query = "	SELECT *
							FROM `drafts`
							WHERE `status` = 'approved'";
				if ($drafts = $db->GetAll($query)) {

					application_log("notice", "Draft schedule importer found ".count($drafts)." approved drafts and started importing.");
                    
					foreach ($drafts as $draft) {
						$msg[$draft["draft_id"]][] = "Imported draft: \"".$draft["draf_title"]."\" on ".date("Y-m-d H:i", time());
						$notification_events = "";

						/*
						 *  fetch the draft events
						 */

						$query = "	SELECT a.`proxy_id`, CONCAT(b.`firstname`, ' ', b.`lastname`) AS `name`, b.`email`
									FROM `draft_creators` AS a
									JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON a.`proxy_id` = b.`id`
									WHERE `draft_id` = ".$db->qstr($draft["draft_id"]);
						$draft_creators = $db->GetAll($query);

						$query = "	SELECT `option`, `value`
									FROM `draft_options` 
									WHERE `draft_id` = ".$db->qstr($draft["draft_id"]);
						$draft_options = $db->GetAssoc($query);
						
						$query = "	SELECT *
									FROM `draft_events`
									WHERE `draft_id` = ".$db->qstr($draft["draft_id"])."
									ORDER BY `parent_id` ASC";
						$events = $db->GetAll($query);
						if ($events) {
                            $recurring_event_id_map = array();

							application_log("notice", "Draft schedule importer found ".count($events)." events in draft ".$draft["draft_id"].".");

							foreach ($events as $event) {
                                // Get the community id, if one exists
                                $community_course = Models_Community_Course::fetchRowByCourseID($event["course_id"]);
                                if ($community_course) {
                                    $community_id = $community_course->getCommunityID();
                                }
                                
                                $recurring_id_updated = false;

                                if (isset($event["recurring_id"]) && $event["recurring_id"] && array_key_exists($event["recurring_id"], $recurring_event_id_map)) {
                                    $event["recurring_id"] = $recurring_event_id_map[$event["recurring_id"]];
                                    $recurring_id_updated = true;
                                }

								if ($event["event_id"]) {
                                    $has_event_id = true;
									$old_event_id = $event["event_id"];
									$old_events[$old_event_id] = $event;
									unset($event["event_id"]);
								} else {
                                    $has_event_id = false;
									$old_event_id = $event["devent_id"];
                                    $old_events[$event["devent_id"]] = $event;
                                    unset($event["devent_id"]);
								}

								$event["updated_date"]	= time();
								$event["updated_by"]	= $draft_creators[0]["proxy_id"];
								if (empty($event["event_children"])) {
									$event["event_children"] = 0;
								}

								if (isset($event["parent_id"]) && in_array($event["parent_id"], $old_events[$event["parent_id"]])) {
									$event["parent_id"] = $old_events[$event["parent_id"]]["new_event_id"];
								}

								if ($db->AutoExecute("`events`", $event, 'INSERT')) {
									$event_id = $db->Insert_ID();

                                    if (!$recurring_id_updated && isset($event["recurring_id"]) && $event["recurring_id"] && !array_key_exists($event["recurring_id"], $recurring_event_id_map)) {
                                        $recurring_event_id_map[$event["recurring_id"]] = $event_id;
                                        $query = "UPDATE `events` SET `recurring_id` = ".$db->qstr($event_id)." WHERE `event_id` = ".$db->qstr($event_id);
                                        if (!$db->Execute($query)) {
                                            application_log("error", "An error occurred when updating a draft event with a recurring event id. DB said: ".$db->ErrorMsg());
                                        }
                                    }
									$old_events[$old_event_id]["new_event_id"] = $event_id;
									application_log("success", "Successfully created event [".$event_id."]");
                                    
                                    //inserts creation log
                                    history_log($event_id, 'created this learning event.', $event["updated_by"]);
								} else {
									$error++;
									application_log("error", "Error inserting event [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
								}

								$notification_events .= $event["event_title"]." at ".date("Y-m-d H:i", $event["event_start"])."\n";

								/*
								*  add the eventtypes associated with the draft event
								*/
								$query = "	SELECT *
											FROM `draft_eventtypes`
											WHERE `devent_id` = ".$db->qstr($event["devent_id"]);
								if ($eventtypes = $db->GetAll($query)) {
									application_log("notice", "Found ".count($eventtypes)." eventtypes for draft event [".$event["devent_id"]."].");
									foreach ($eventtypes as $eventtype) {
										$eventtype["event_id"]		= $event_id;
										unset($eventtype["deventtype_id"]);
										unset($eventtype["eeventtype_id"]);
										unset($eventtype["devent_id"]);
										if ($db->AutoExecute("`event_eventtypes`", $eventtype, "INSERT")) {
											application_log("success", "Successfully inserted eventtype [".$db->Insert_ID()."] for event [".$event_id."].");
										} else {
											$error++;
											application_log("error", "Error inserting event_eventtype [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
										}
									}
								} else {
									application_log("notice", "Found no eventtypes for draft event [".$event["devent_id"]."].");
								}


								/*
								*  add the event contacts associated with the draft event
								*/
								$query = "	SELECT *
											FROM `draft_contacts`
											WHERE `devent_id` = ".$db->qstr($event["devent_id"]);
								if ($eventcontacts = $db->GetAll($query)) {
									application_log("notice", "Found ".count($eventcontacts)." event contacts for draft event [".$event["devent_id"]."].");
									foreach ($eventcontacts as $contact) {
										$contact["event_id"] = $event_id;
										$contact["updated_date"] = time();
										$contact["updated_by"] =  $draft_creators[0]["proxy_id"];
										unset($contact["dcontact_id"]);
										unset($contact["econtact_id"]);
										unset($contact["devent_id"]);
										if ($db->AutoExecute("`event_contacts`", $contact, "INSERT")) {
                                            // Add the event contact to the community, if there is one.
                                            if (isset($community_id)) {
                                                Models_Community_Member::insert_members($contact["proxy_id"], $community_id);
                                            }
											application_log("success", "Successfully inserted event contact [".$db->Insert_ID()."] for event [".$event_id."].");
											$msg[$draft["draft_id"]]["contacts"][$contact["proxy_id"]][] = $contact["email"];
											$msg[$draft["draft_id"]]["contacts"][$contact["proxy_id"]][] = $contact["fullname"];
										} else {
											$error++;
											application_log("error", "Error inserting event_contact [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
										}
									}
								}

								/*
								* add the event audience associated with the draft event
								*/
								$query = "	SELECT *
											FROM `draft_audience`
											WHERE `devent_id` = ".$db->qstr($event["devent_id"]);
								if ($eventaudience = $db->GetAll($query)) {
									application_log("notice", "Found ".count($eventaudience)." event audience members for draft event [".$event["devent_id"]."].");
									foreach ($eventaudience as $audience) {
                                        
										$audience["event_id"] = $event_id;
										$audience["updated_date"] = time();
										$audience["updated_by"] =  $draft_creators[0]["proxy_id"];
										unset($audience["daudience_id"]);
										unset($audience["eaudience_id"]);
										unset($audience["devent_id"]);

										if ($db->AutoExecute("`event_audience`", $audience, "INSERT")) {
											application_log("success", "Successfully inserted event audience [".$db->Insert_ID()."] for event [".$event_id."].");
										} else {
											$error++;
											application_log("error", "Error inserting event_audience [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
										}
                                        
                                        if ($audience["audience_type"] == "group_id") {
                                            /*
                                             * If the draft event audience type is a group check the course group for contacts, 
                                             * if found add them to `event_contacts` as tutors.
                                             */
                                            $contacts = Models_Course_Group_Contact::fetchAllByCgroupID($audience["audience_value"]);
                                            if ($contacts) {
                                                $i = 0;
                                                foreach ($contacts as $contact) {
                                                    $event_contact_data = array(
                                                        "event_id"      => $event_id,
                                                        "proxy_id"      => $contact->getProxyID(),
                                                        "contact_role"  => "tutor",
                                                        "contact_order" => $i,
                                                        "updated_date"  => time(),
                                                        "updated_by"    => "1"
                                                    );
                                                    if (!$db->AutoExecute("event_contacts", $event_contact_data, "INSERT")) {
                                                        application_log("error", "Failed to insert event contact, DB said: ".$db->ErrorMsg());
                                                    }
                                                    $i++;
                                                }
                                            }
                                        }
									}
								}

								if ($has_event_id) {
									if ($draft_options["files"]) {
										/*
										*  add the event files associated with the event
										*/
										$query = "	SELECT *
													FROM `event_files`
													WHERE `event_id` = ".$db->qstr($old_event_id)."
													AND `file_category` != 'podcast'";
										if ($event_files = $db->GetAll($query)) {
											application_log("notice", "Found ".count($event_files)." event files attached to original event [".$old_event_id."], will be ported over to new event [".$event_id."].");
											foreach ($event_files as $file) {
												$old_event_file = (int) $file["efile_id"];
												unset($file["efile_id"]);
												$file["event_id"]		= $event_id;
												$file["accesses"]		= 0;
												$file["updated_by"]		= $draft_creators[0]["proxy_id"];
												if ($db->AutoExecute("`event_files`", $file, "INSERT")) {
													application_log("success", "Successfully inserted file [".$db->Insert_ID()."] from old event [".$old_event_id."], for new event [".$event_id."].");

													$new_file_id = (int) $db->Insert_ID();
													if (file_exists(FILE_STORAGE_PATH."/".$old_event_file)) {
														if (copy(FILE_STORAGE_PATH."/".$old_event_file, FILE_STORAGE_PATH."/".$new_file_id)) {
															application_log("success", "Successfully copied file [".$old_event_file."] to file [".$new_file_id."], for new event [".$event_id."].");
															$copied_files[] = $processed_file["file_name"];
														} else {
															application_log("error", "Failed to copy file [".$old_event_file."] to file [".$new_file_id."].");
														}
													}
                                                    
                                                    switch ($file["file_category"]) {
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
                                                        "event_id" => $event_id,
                                                        "entity_type" => $entity_type,
                                                        "entity_value" => $new_file_id,
                                                        "release_date" => 0,
                                                        "release_until" => 0,
                                                        "updated_date" => time(),
                                                        "updated_by" => $draft_creators[0]["proxy_id"],
                                                        "active" => 1
                                                        )
                                                    );

                                                    if (!$event_resource_entity->insert()) {
                                                        $ERROR++;
                                                        $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                                        application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                                    }
                                                    
												} else {
													$error++;
													application_log("error", "Error inserting event_files [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
												}
											}
										} else {
											application_log("notice", "Found no event files attached to original event [".$old_event_id."].");
										}
									}
									
									if ($draft_options["links"]) {
										/*
										*  add the event links associated with the event
										*/
										$query = "	SELECT *
													FROM `event_links`
													WHERE `event_id` = ".$db->qstr($old_event_id);
										if ($event_links = $db->GetAll($query)) {
											application_log("notice", "Found ".count($event_links)." event links attached to original event [".$old_event_id."], will be ported over to new event [".$event_id."].");
											foreach ($event_links as $link) {
                                                $original_elink_id = $link["elink_id"];
												unset($link["elink_id"]);
												$link["event_id"]		= $event_id;
												$link["accesses"]		= 0;
												$link["updated_by"]		= $draft_creators[0]["proxy_id"];
												if ($db->AutoExecute("`event_links`", $link, "INSERT")) {
													application_log("success", "Successfully inserted link [".$db->Insert_ID()."] from old event [".$old_event_id."], for new event [".$event_id."].");
                                                    
                                                    $new_link_id = (int) $db->Insert_ID();
                                                    $resource = Models_Event_Resource_Entity::fetchRowByEventIDEntityValue($old_event_id, $original_elink_id);
                                                    if ($resource) {
                                                        $event_resource_entity = new Models_Event_Resource_Entity(array(
                                                            "event_id" => $event_id,
                                                            "entity_type" => $resource->getEntityType(),
                                                            "entity_value" => $new_link_id,
                                                            "release_date" => 0,
                                                            "release_until" => 0,
                                                            "updated_date" => time(),
                                                            "updated_by" => $draft_creators[0]["proxy_id"],
                                                            "active" => 1
                                                            )
                                                        );

                                                        if (!$event_resource_entity->insert()) {
                                                            $ERROR++;
                                                            $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                                            application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                                        }
                                                    }
												} else {
													$error++;
													application_log("error", "Error inserting event_links [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
												}
											}
										} else {
											application_log("notice", "Found no event links attached to original event [".$old_event_id."].");
										}
									}
									
									if ($draft_options["objectives"]) {
										/*
										 *  add the event objectives associated with the draft event
										 */
										$query = "	SELECT *
													FROM `event_objectives`
													WHERE `event_id` = ".$db->qstr($old_event_id);
										if ($event_objectives = $db->GetAll($query)) {
											foreach ($event_objectives as $objective) {
												unset($objective["eobjective_id"]);
												$objective["event_id"]		= $event_id;
												$objective["updated_by"]	= $draft_creators[0]["proxy_id"];
												if ($db->AutoExecute("`event_objectives`", $objective, "INSERT")) {
													application_log("success", "Successfully inserted objective [".$db->Insert_ID()."] from old event [".$old_event_id."], for new event [".$event_id."].");
												} else {
													$error++;
													application_log("error", "Error inserting event_objectives [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
												}
											}
										} else {
											application_log("notice", "Found no event objectives attached to original event [".$old_event_id."].");
										}
									}
									
									if ($draft_options["keywords"]) {
										/*
										 *  add the event objectives associated with the draft event
										 */
                                        $event_keywords = Models_Event_Keywords::fetchAllByEventID($old_event_id);
										if ($event_keywords) {
                                            if (is_array($event_keywords) && !empty($event_keywords)) {
                                                foreach ($event_keywords as $keyword_object) {
                                                    $keyword = $keyword_object->toArray();
                                                    unset($keyword["ekeyword_id"]);
                                                    $keyword["event_id"]    = $event_id;
                                                    $keyword["updated_by"]	= $draft_creators[0]["proxy_id"];
                                                    $new_keyword = new Models_Event_Keywords();
                                                    $new_keyword->fromArray($keyword);
                                                    if ($new_keyword->insert()) {
                                                        application_log("success", "Successfully inserted keyword [".$db->Insert_ID()."] from old event [".$old_event_id."], for new event [".$event_id."].");
                                                    } else {
                                                        $error++;
                                                        application_log("error", "Error inserting event_keywords [".$event_id."] on draft schedule import. DB said: " . $db->ErrorMsg());
                                                    }
                                                }
                                            } else {
                                                application_log("notice", "No event keywords found attached to original event [".$old_event_id."].");
                                            }
										} else {
											application_log("error", "Error selecting event keywords [".$old_event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
										}
									}
									
									if ($draft_options["topics"]) {
                                        
										/*
										 *  add the event topics associated with the draft event
										 */
										$query = "	SELECT *
													FROM `event_topics`
													WHERE `event_id` = ".$db->qstr($old_event_id);
										if ($event_topics = $db->GetAll($query)) {
											foreach ($event_topics as $topic) {
												unset($topic["etopic_id"]);
												$topic["event_id"]		= $event_id;
												$topic["updated_by"]	= $draft_creators[0]["proxy_id"];
												if ($db->AutoExecute("`event_topics`", $topic, "INSERT")) {
													application_log("success", "Successfully inserted topic [".$db->Insert_ID()."] from old event [".$old_event_id."], for new event [".$event_id."].");
												} else {
													$error++;
													application_log("error", "Error inserting event_topics [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
												}
											}
										} else {
											application_log("notice", "Found no event topics attached to original event [".$old_event_id."].");
										}
									}
									
									if ($draft_options["quizzes"]) {
										/*
										*  add the event objectives associated with the draft event
										*/
										$query = "	SELECT *
													FROM `attached_quizzes`
													WHERE `content_type` = 'event'
													AND `content_id` = ".$db->qstr($old_event_id);
										if ($event_quizzes = $db->GetAll($query)) {
											foreach ($event_quizzes as $quiz) {
												unset($quiz["aquiz_id"]);
												$quiz["content_id"]		= $event_id;
												$quiz["accesses"]		= 0;
												$quiz["updated_by"]	= $draft_creators[0]["proxy_id"];
												if ($db->AutoExecute("`attached_quizzes`", $quiz, "INSERT")) {
													application_log("success", "Successfully inserted quiz [".$db->Insert_ID()."] from old event [".$old_event_id."], for new event [".$event_id."].");
                                                    
                                                    $new_quiz_id = $db->Insert_Id();
                                                    $event_resource_entity = new Models_Event_Resource_Entity(array(
                                                        "event_id" => $event_id,
                                                        "entity_type" => 8,
                                                        "entity_value" => $new_quiz_id,
                                                        "release_date" => 0,
                                                        "release_until" => 0,
                                                        "updated_date" => time(),
                                                        "updated_by" => $draft_creators[0]["proxy_id"],
                                                        "active" => 1
                                                        )
                                                    );

                                                    if (!$event_resource_entity->insert()) {
                                                        $ERROR++;
                                                        $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                                        application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                                    }
												} else {
													$error++;
													application_log("error", "Error inserting event_objectives [".$event_id."] on draft schedule import. DB said: ".$db->ErrorMsg());
												}
											}
										} else {
											application_log("notice", "Found no event quizzes attached to original event [".$old_event_id."].");
										}
									}

								}

								if (!$error) {
									$count++;
									$msg[$draft["draft_id"]][] = $event["event_title"]." - ".date("Y-m-d H:i",$event["event_start"]);
								}
								
								unset ($old_event_id);
							}

						} else {
							application_log("error", "Draft [".$draft["draft_id"]."] did not contain any events.");
						}

						if (!$error) {
							// notify the draft creators that their draft has been imported
							$message = "This email is to notify you that the draft learning event schedule \"".$draft["name"]."\" was successfully imported on ".date("Y-m-d H:i", time()).".\n\n";
							$message .= "The following learning events were imported into the system:\n";
							$message .= "------------------------------------------------------------\n\n";
							$message .= $notification_events;

							if ($draft_creators) {
								$mail = new Zend_Mail(DEFAULT_CHARSET);
                                $mail->setReturnPath($config->admin->email);
								$mail->addHeader("X-Section", "Learning Events Notification System", true);
								$mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
								$mail->clearSubject();
								$mail->setSubject("Draft Learning Event Schedule Imported");
								$mail->setBodyText($message);
								$mail->clearRecipients();

								foreach ($draft_creators as $result) {
									$mail->addTo($result["email"], $result["name"]);
								}

								$mail->addTo($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);

								if ($mail->send()) {
									application_log("success", "Successfully sent email to draft [".$draft_id."] creators.");
								} else {
									application_log("error", "Failed to sent email to draft [".$draft_id."] creators.");
								}
							}
						
							$query = "UPDATE `drafts` SET `status` = 'closed' WHERE `draft_id` = ".$db->qstr($draft["draft_id"]);
							if ($db->Execute($query)) {
							   /*
								* draft successfully imported and draft deleted from drafts tables
								*/
								application_log("success", "Successfully closed draft [draft_id-".$draft["draft_id"]."]. ".$count." records imported.");
							} else {
								/*
								 * something went wrong
								 */
								application_log("error", "Failed to close draft [draft_id-".$draft["draft_id"]."], DB said: ".$db->ErrorMsg());
							}
						}
					}
				} else {
					application_log("notice", "Draft schedule importer found no approved drafts and exited.");
				}
				
				if (unlink(CACHE_DIRECTORY."/import_draft.lck")) {
					application_log("success", "Lock file deleted.");
				} else {
					application_log("error", "Unable to delete draft import lock file: ".CACHE_DIRECTORY."/import_draft.lck");
				}
			} else {
				application_log("error", "Could not write draft import lock file, exiting.");
			}
		} else {
			application_log("error", "Draft import lock file found, exiting.");
		}
} else {
	application_log("error", "Error with cache directory [".CACHE_DIRECTORY."], not found or not writable.");
}