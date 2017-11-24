<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@qmed.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

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

$isAuthenticated = false;
$user_details = array();

if (!isset($_POST["method"]) || !$_POST["method"]) {
    $_POST = $_GET;
}

if (isset($_POST["method"]) && $tmp_input = clean_input($_POST["method"], "alphanumeric")) {
	$method = $tmp_input;
}

if (isset($_POST["sub_method"]) && $tmp_input = clean_input($_POST["sub_method"], "alphanumeric")) {
	$sub_method = $tmp_input;
}

if (isset($_POST["course_id"]) && ((int)$_POST["course_id"])) {
    $course_id = ((int) $_POST["course_id"]);
}

if (isset($_POST["entry_id"]) && ((int)$_POST["entry_id"])) {
    $entry_id = ((int) $_POST["entry_id"]);
}

if (isset($_POST["notice_id"]) && $tmp_input = clean_input($_POST["notice_id"], "alphanumeric")) {
	$notice_id = $tmp_input;
}

if (isset($_POST["message_id"]) && $tmp_input = clean_input($_POST["message_id"], "alphanumeric")) {
	$message_id = $tmp_input;
}

if (isset($_POST["group"]) && $tmp_input = clean_input($_POST["group"], "alphanumeric")) {
	$group = $tmp_input;
}

if (isset($_POST["role"]) && $tmp_input = clean_input($_POST["role"], "alphanumeric")) {
	$role = $tmp_input;
}

if (isset($_POST["org_id"]) && $tmp_input = clean_input($_POST["org_id"], "int")) {
	$org_id = $tmp_input;
}

if (isset($_POST["total_notices"]) && $tmp_input = clean_input($_POST["total_notices"], "alphanumeric")) {
	$total_notices = $tmp_input;
}

if (isset($_POST["evaluation_id"]) && $tmp_input = clean_input($_POST["evaluation_id"], "alphanumeric")) {
	$evaluation_id = $tmp_input;
}

if (isset($_POST["total_evaluations"]) && $tmp_input = clean_input($_POST["total_evaluations"], "alphanumeric")) {
	$total_evaluations = $tmp_input;
}

if (isset($_POST["device_token"]) && $tmp_input = clean_input($_POST["device_token"], "alphanumeric")) {
	$device_token = $tmp_input;
}

if (isset($_POST["max_notice_id"]) && $tmp_input = clean_input($_POST["max_notice_id"], "int")) {
	$new_max_notice_id = $tmp_input;
}

if (isset($_POST["username"]) && isset($_POST["password"]) && !empty($_POST["username"]) && !empty($_POST["password"])) {
	require_once("Entrada/authentication/authentication.class.php");
	$username = clean_input($_POST["username"], "credentials");
	$password = clean_input($_POST["password"], "trim");


	$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
	$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
	$auth->setEncryption(AUTH_ENCRYPTION_METHOD);
	$auth->setUserAuthentication($username, $password, AUTH_METHOD);
	$result = $auth->Authenticate(
		array(
			"id",
			"firstname",
			"lastname",
			"role",
			"group",
			"organisation_id",
			"private_hash"
		)
	);

	if ($ERROR == 0 && $result["STATUS"] == "success") {

		$GUEST_ERROR = false;

		if ($result["GROUP"] == "guest") {
			$query = "	SELECT COUNT(*) AS total
						FROM `community_members`
						WHERE `proxy_id` = ".$db->qstr($result["ID"])."
						AND `member_active` = 1";
			$community_result	= $db->GetRow($query);
			if ((!$community_result) || ($community_result["total"] == 0)) {
				/**
				* This guest user doesn't belong to any communities, don't let them log in.
				*/
				$GUEST_ERROR = true;
			}
		}

		if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
			$ERROR++;
			$ERRORSTR[] = "Your access to this system does not start until ".date("r", $result["ACCESS_STARTS"]);

			application_log("error", "User[".$username."] tried to access account prior to activation date.");
		} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
			$ERROR++;
			$ERRORSTR[] = "Your access to this system expired on ".date("r", $result["ACCESS_EXPIRES"]);

			application_log("error", "User[".$username."] tried to access account after expiration date.");
		} elseif ($GUEST_ERROR) {
			$ERROR++;
			$ERRORSTR[] = "To log in using guest credentials you must be a member of at least one community.";

			application_log("error", "Guest user[".$username."] tried to log in and isn't a member of any communities.");
		} else {

			application_log("access", "User[".$username."] successfully logged in.");
			$isAuthenticated  = true;
			$user_details["authenticated"] = true;
			$user_details["id"] = $result["ID"];
			$user_details["firstname"] = $result["FIRSTNAME"];
			$user_details["lastname"] = $result["LASTNAME"];
			$user_details["role"] = $result["ROLE"];
			$user_details["group"] = $result["GROUP"];
			$user_details["organisation_id"] = $result["ORGANISATION_ID"];
			$user_details["private_hash"] = $result["PRIVATE_HASH"];
		}
	}

	unset($result, $username, $password);
} else {
	/**
 	 * Authenticate the user via their provided private hash.
	 */
	if (isset($_POST["hash"]) && $user_hash = clean_input($_POST["hash"], "alphanumeric")) {
		$query = "SELECT a.`id`, a.`username`, a.`firstname`, a.`lastname`, a.`email`, a.`grad_year`, b.`role`, b.`group`, a.`organisation_id`, b.`access_expires`
				FROM `".AUTH_DATABASE."`.`user_data` AS a
				LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
				ON b.`user_id` = a.`id`
				WHERE b.`private_hash` = ".$db->qstr($user_hash)."
				AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
				AND b.`account_active` = 'true'
				AND (b.`access_starts`='0' OR b.`access_starts` <= ".$db->qstr(time()).")
				AND (b.`access_expires`='0' OR b.`access_expires` >= ".$db->qstr(time()).")
				GROUP BY a.`id`";
		$result = $db->GetRow($query);
		if ($result) {
			$isAuthenticated = true;
			$user_details["id"] = $result["id"];
			$user_details["firstname"] = $result["firstname"];
			$user_details["lastname"] = $result["lastname"];
			$user_details["role"] = $result["role"];
			$user_details["group"] = $result["group"];
			$user_details["organisation_id"] = $result["organisation_id"];
		}
	}

}

if ($isAuthenticated) {
	
	$ENTRADA_USER = User::get($user_details["id"]);
	$user_details["access_id"] = $ENTRADA_USER->getAccessId();
	if ($ENTRADA_USER->getActiveGroup() == "student") {
		$user_details["grad_year"] = $ENTRADA_USER->getGradYear();
	}
	
	$ENTRADA_ACL = new Entrada_Acl($user_details);
	
	if (isset($_GET["api_version"]) && $tmp_input = clean_input($_GET["api_version"], "int")) {
		$api_version = $tmp_input;
	} else {
		$api_version = 1;
	}
	
	
	switch ($api_version) {
		case 2 :
			switch ($method) {
                case "logbook" :
                    if (isset($sub_method)) {
                        $logbook = new Models_Logbook();
                        switch ($sub_method) {
                            case "list" :
                                $entries = Models_Logbook_Entry::fetchAll($ENTRADA_USER->GetID());
                                if ($entries) {
                                    $entries_array = array();
                                    foreach ($entries as $entry) {
                                        $entry_data = $entry->toArray();
                                        $course_name = $entry->getCourseName();
                                        $entry_data["course_name"] = $course_name;
                                        $entry_data["encounter_date"] = html_encode(date("F jS, Y", $entry->getEncounterDate()));
                                        $entries_array[] = $entry_data;
                                    }
                                    echo json_encode(array("status" => "success", "data" => $entries_array));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => array("msg" => "There are currently no entries in the system. Use the Add Log Entry button to create a new encounter tracking entry.")));
                                }    
                            break;
                            case "fetch" :
                                if (isset($entry_id)) {
                                    $entry = Models_Logbook_Entry::fetchRow($entry_id);
                                    if ($entry) {
                                    	$objectives = array();
                                        $entry_array = $entry->toArray();
                                    	
                                    	if (is_array($entry_array["objectives"]) && !empty($entry_array["objectives"])) {
                                    		foreach ($entry_array["objectives"] as $objective) {
	                                    		$objectives[$objective->getID()] = $objective->toArray();
	                                            $objectives[$objective->getID()]["name"] = $objective->getObjective()->getName();
	                                    	}
                                    	}

                                    	$entry_array["encounter_date"] = date("Y-m-d", $entry->getEncounterDate());
                                    	$entry_array["encounter_time"] = date("H:i", $entry->getEncounterDate());
                                    	$entry_array["objectives"] = $objectives;

                                        echo json_encode(array("status" => "success", "data" => $entry_array));
                                    } else {
                                        echo json_encode(array("status" => "error", "data" => array("msg" => "An error occured whule fetching rotation data")));
                                    }
                                }
                            break;
                            case "rotation" :
                                $courses = $logbook->getLoggingCourses();
                                if ($courses) {
                                    echo json_encode(array("status" => "success", "data" => $courses));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => array("msg" => "An error occured whule fetching rotation data")));
                                }
                            break;
                            case "institution" :
                                $institutions = $logbook->getInstitutions();
                                if ($institutions) {
                                    echo json_encode(array("status" => "success", "data" => $institutions));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => array("msg" => "An error occured whule fetching institution data")));
                                }
                            break;
                            case "setting" :
                                $locations = $logbook->getLocations();
                                if ($locations) {
                                    echo json_encode(array("status" => "success", "data" => $locations));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => array("msg" => "An error occured whule fetching setting data")));
                                }
                            break;
                            case "range" :
                                $age_ranges = $logbook->getAgeRanges();
                                if ($age_ranges) {
                                    echo json_encode(array("status" => "success", "data" => $age_ranges));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => array("msg" => "An error occured whule fetching age range data")));
                                }
                            break;
                            case "skills" :
                                if (isset($course_id) && $course_id) {
                                    if (!isset($entry)) {
                                        if (isset($entry_id) && $entry_id) {
                                            $entry = Models_Logbook_Entry::fetchRow($entry_id);
                                            $entry->setCourseID($course_id);
                                        } else {
                                            $entry = new Models_Logbook_Entry();
                                            $entry->setCourseID($course_id);
                                        }
                                    }
                                    $objectives = $entry->getCourseObjectivesMobile();
                                    if (@count($objectives["required"]) || @count($objectives["logged"]) || @count($objectives["disabled"])) {
                                        echo json_encode(array("status" => "success", "data" => $objectives));
                                    } else {
                                        echo json_encode(array("status" => "error", "data" => array("msg" => "An error occured whule fetching objective data")));
                                    }
                                }
                            break;
                            case "submit" :
                                
                                if (isset($entry_id)) {
                                    $PROCESSED["lentry_id"] = $entry_id;
                                }
                                
                                /**
                                * Required field "rotation" / Rotation.
                                */
                            	if ((isset($_POST["course_id"])) && ($course_id = clean_input($_POST["course_id"], "int"))) {
                                   $PROCESSED["course_id"] = $course_id;
                               	} else {
								   add_error("The <strong>Rotation</strong> field is required.");
								}
                                
                                /*
                                 * Required fields date and time
                                 */
                                if ((isset($_POST["date"])) && ($date = clean_input($_POST["date"], array("trim", "striptags"))) && (isset($_POST["time"])) && ($time = clean_input($_POST["time"], array("trim", "striptags")))) {
                                    $date_pieces = explode("-", $date);
                                    $time_pieces = explode(":", $time);
                                    $hour	= (int) $time_pieces[0];
                                    $minute	= (int) $time_pieces[1];
                                    $second	= 0;
                                    $month	= (int) trim($date_pieces[1]);
                                    $day	= (int) trim($date_pieces[2]);
                                    $year	= (int) trim($date_pieces[0]);

                                    $PROCESSED["encounter_date"] = mktime($hour, $minute, $second, $month, $day, $year);
                                } else {
                                    add_error("The date and time fields are required");
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

								/**
                                * Non-required field "objectives" / objectives
                                */
								if (is_array($_POST["objectives"]) && count($_POST["objectives"])) {
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
								} else {
                                    add_error("Please select at least one objective");
                                }

								if (!$ERROR) {
									$entry = new Models_Logbook_Entry();

									$PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                                	$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                                    $PROCESSED["updated_date"] = time();
                                    
                                    if (isset($entry_id)) {
                                        if($entry->fromArray($PROCESSED)->update()) {
                                            add_statistic("encounter_tracking", "update", "lentry_id", $PROCESSED["lentry_id"], $ENTRADA_USER->getID());
                                            echo json_encode(array("status" => "success", "data" => "Successfully updated entry."));
                                        } else {
                                            application_log("error", "Error occurred when updating logbook entry, DB said: ".$db->ErrorMsg());
                                            echo json_encode(array("status" => "error", "data" => array("An error occured while attemptin to update this entry.")));
                                        }
                                    } else {
                                        if($entry->fromArray($PROCESSED)->insert()) {
                                            add_statistic("encounter_tracking", "insert", "lentry_id", $db->Insert_ID(), $ENTRADA_USER->getID());
                                            echo json_encode(array("status" => "success", "data" => array("The entry has been saved successfully.")));
                                        } else {
                                            application_log("error", "Error occurred when updating logbook entry, DB said: ".$db->ErrorMsg());
                                            echo json_encode(array("status" => "error", "data" => array("An error occurred when attempting to create a new logbook entry, an administrator has been informed, please try again later.")));
                                        }
                                    }
								} else {
								   echo json_encode(array("status" => "error", "data" => array("Please ensure all required fields are complete.")));
								}
                            break;
                        }
                    }
                break;
				case "hash" :
					echo json_encode(array('authenticated' => 'true', 'hash' => $user_details["private_hash"], 'firstname' => $user_details['firstname'], 'lastname' => $user_details['lastname'], 'logbook_access' => $ENTRADA_ACL->amIAllowed('encounter_tracking', 'read')));
					break;
				case "credentials" :
					echo true;
					break;
				case "registertoken" :
					if (isset($device_token)) {
						$query = "SELECT * FROM `". AUTH_DATABASE ."`.`apns_device_tokens` WHERE `proxy_id` = ?";
						$results = $db->GetAll($query, array($ENTRADA_USER->getId()));
						if ($results) {
							$user_device_tokens = array();
							foreach ($results as $result) {
								$user_device_tokens[] = $result["device_token"];
							}
							if (!in_array($device_token, $user_device_tokens)) {
								if (!$db->AutoExecute(AUTH_DATABASE . ".apns_device_tokens", array("proxy_id"=> $result["proxy_id"], "device_token" => $device_token, "max_notice_id" => $result["max_notice_id"], "new_notices" => $result["new_notices"], "updated_date" => time()), "INSERT")) { 
									application_log("error", "An error occured while attempting to update the user device id in the apns_device_token table, proxy_id: " . $ENTRADA_USER->getId() . " device_token: " . $device_token . " Database said: ".$db->ErrorMsg());
									echo json_encode(array("staus" => "error"));
								}
							}
							echo json_encode(array("staus" => "success"));
						} else {
							if (!$db->AutoExecute(AUTH_DATABASE . ".apns_device_tokens", array("proxy_id" => $user_details["id"], "device_token" => $device_token, "updated_date" => time()), "INSERT")) { 
								application_log("error", "An error occured while attempting to insert the user device id into the apns_device_token table, proxy_id: " . $ENTRADA_USER->getId() . " device_token: " . $device_token . " Database said: ".$db->ErrorMsg());
								echo json_encode(array("staus" => "error"));
							} else {
								echo json_encode(array("staus" => "success"));
							}
						}
					}
					break;
				case "updatenoticeid" :
						if (!$db->AutoExecute(AUTH_DATABASE . ".apns_device_tokens", array("max_notice_id" => $new_max_notice_id, "new_notices" => "0", "updated_date" => time()), "UPDATE", "proxy_id = ".$db->qstr($ENTRADA_USER->getId()))) { 
							application_log("error", "An error occured while attempting to update the user device id in the apns_device_token table, proxy_id: " . $ENTRADA_USER->getId() . " device_token: " . $device_token . " Database said: ".$db->ErrorMsg());
							echo json_encode(array("staus" => "error"));
						} else {
							echo json_encode(array("staus" => "success"));
						}
					break;
				case "agenda":

					$user_proxy_id = $user_details["id"];
					$user_role = strtolower($role);
					$user_group = strtolower($group);
					
					$ENTRADA_USER->setActiveGroup($user_group);
					$ENTRADA_USER->setActiveRole($user_role);
					$ENTRADA_USER->setActiveOrganisation($org_id);
					
					$event_start = strtotime("-12 months 00:00:00");
					$event_finish = strtotime("+12 months 23:59:59");
					$learning_events = events_fetch_filtered_events(
                        $ENTRADA_USER->getActiveId(),
                        $ENTRADA_USER->getActiveGroup(),
						$ENTRADA_USER->getActiveRole(),
                        $ENTRADA_USER->getActiveOrganisation(),
                        "date",
                        "asc",
                        "custom",
                        $event_start,
						$event_finish,
                        events_filters_defaults($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveGroup(), $ENTRADA_USER->getActiveRole(), $ENTRADA_USER->getActiveOrganisation()),
                        false,
                        0,
                        0,
                        0,
                        false);
					
					/*
					$learning_events = events_fetch_filtered_events(
                        $ENTRADA_USER->getActiveId(),
                        $user_group,
                        $user_role,
                        $org_id,
                        "date",
                        "asc",
                        "custom",
                        $event_start,
						$event_finish,
                        events_filters_defaults($ENTRADA_USER->getActiveId(), $user_group, $user_role, $org_id),
                        false,
                        0,
                        0,
                        0,
                        false);*/
					 
					
					
					/*$learning_events = events_fetch_filtered_events(
						$ENTRADA_USER->getActiveId(),
						$ENTRADA_USER->getActiveGroup(),
						$ENTRADA_USER->getActiveRole(),
						$ENTRADA_USER->getActiveOrganisation(),
						0,
						0,
						0,
						0,
						0,
						0,
						true,
						(isset($_GET["pv"]) ? (int) trim($_GET["pv"]) : 1),
						$_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"]);*/

					$events = array();
					if (!empty($learning_events["events"])) {
						foreach ($learning_events["events"] as $drid => $event) {
							$cal_type = 1;
							$cal_updated = "";

							if ($event["audience_type"] == "proxy_id") {
								$cal_type = 3;
							}

							if (((int) $event["last_visited"]) && ((int) $event["last_visited"] < (int) $event["updated_date"])) {
								$cal_type = 2;

								$cal_updated = date(DEFAULT_DATE_FORMAT, $event["updated_date"]);
							}

							$events[] = array(
								"id" => $event["event_id"],
								"start_date"	=> date("o-m-d G:i", $event["event_start"]),
								"end_date" => date("o-m-d G:i", $event["event_finish"]),
								"text" => strip_tags($event["event_title"]),
								"details" => $event["event_description"]. "<br /><b>Event Duration: </b>". $event["event_duration"] . " minutes <br /><b>Location: </b>". ($event["event_location"] == "" ? "To be announced" : $event["event_location"]) ."<br /><br /><a href='#' data-role='button' class='btn' style='padding:10px' onclick='window.open(\"". ENTRADA_URL ."/events?rid=" . $event['event_id'] . "\", \"_blank\", \"location=yes\");'>Review Learning Event</a><br><br><br>",
							);
						}
					}

					echo json_encode(array("events" => $events, "org_id" => $org_id, "group" => $user_group, "role" => $user_role));
					break;
				case "notices" :
					$query = "SELECT `max_notice_id` FROM `". AUTH_DATABASE ."`.`apns_device_tokens` WHERE `proxy_id` = ?";
					$max_notice_id = $db->GetRow($query, array($ENTRADA_USER->getId()));
					$notices_to_display = Models_Notice::fetchUserNotices(true);
					if ($notices_to_display) {
						$rows = 0;
						$output = array();
						foreach ($notices_to_display as $result) {
							if ((!$result["statistic_id"]) || ($result["last_read"] <= $result["updated_date"])) {
								$result['notice_status'] = 'new';
								$result["updated_date"] = date(DEFAULT_DATE_FORMAT, $result["updated_date"]);
								$output[] = $result;
							} else {
								$result['notice_status'] = 'read';
								$result["updated_date"] = date(DEFAULT_DATE_FORMAT, $result["updated_date"]);
								$output[] = $result;
							}
							$rows ++;
						}
						echo json_encode(array("status" => "success", "data" => $output, "max_notice_id" => $max_notice_id["max_notice_id"], "rows" => $rows));
					} else {
						echo json_encode(array("status" => "error", "data" => "No notices to display"));
					}

					break;
				case "notice" :
					$notice = Models_Notice::fetchNotice($message_id);
					if ($notice) {
						echo json_encode(array("status" => "success", "data" => $notice));
					} else {
						echo json_encode(array("status" => "error"));
					}
					break;
				case "evaluations" :

					$evaluations_list = array();
					$evaluations = Classes_Evaluation::getEvaluatorEvaluations($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());

					if ($evaluations) {				
						if (count($evaluations)) {
							foreach ($evaluations as $evaluation) {
								if ($evaluation["max_submittable"] > $evaluation["completed_attempts"]) {
									$evaluations_list[] = $evaluation;
								}
							}
							if (count($evaluations_list)) {
								echo json_encode($evaluations_list, JSON_FORCE_OBJECT);
							} else {
								echo "false";
							}
						} else {
								echo "false";
						}
					}
					break;
				case "evaluationattempt" :

					$content = array("evaluation_attempt" => "<div id=\"evaluation_attempt\">\n", "success_status" => "false");

					//require_once("Models/evaluation/Evaluation.class.php");
					$cohort = groups_get_cohort($ENTRADA_USER->getID());

					$query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
								JOIN `course_groups` AS b
								ON a.`cgroup_id` = b.`cgroup_id`
								WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
								AND a.`active` = 1
								AND b.`active` = 1";
					$course_groups = $db->GetAll($query);


					$cgroup_ids_string = "";
					if (isset($course_groups) && is_array($course_groups)) {
						foreach ($course_groups as $course_group) {
							if ($cgroup_ids_string) {
								$cgroup_ids_string .= ", ".$db->qstr($course_group["cgroup_id"]);
							} else {
								$cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
							}
						}
					}

					$query			= "SELECT a.*, c.`eprogress_id`, e.`target_title`, c.`etarget_id`, b.`eevaluator_id`, e.`target_shortname`
										FROM `evaluations` AS a
										LEFT JOIN `evaluation_evaluators` AS b
										ON a.`evaluation_id` = b.`evaluation_id`
										LEFT JOIN `evaluation_progress` AS c
										ON a.`evaluation_id` = c.`evaluation_id`
										AND c.`progress_value` = 'inprogress'
										AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
										LEFT JOIN `evaluation_responses` AS cr
										ON c.`eprogress_id` = cr.`eprogress_id`
										LEFT JOIN `evaluation_targets` AS d
										ON a.`evaluation_id` = d.`evaluation_id`
										LEFT JOIN `evaluation_forms` AS ef
										ON a.`eform_id` = ef.`eform_id`
										LEFT JOIN `evaluations_lu_targets` AS e
										ON ef.`target_id` = e.`target_id`
										WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
										AND 
										(
											(
												b.`evaluator_type` = 'proxy_id'
												AND b.`evaluator_value` = ".$db->qstr($ENTRADA_USER->getID())."
											)
											OR
											(
												b.`evaluator_type` = 'organisation_id'
												AND b.`evaluator_value` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
											)".($ENTRADA_USER->getActiveGroup() == "student" ? " OR (
												b.`evaluator_type` = 'cohort'
												AND b.`evaluator_value` = ".$db->qstr($cohort["group_id"])."
											)" : "").($cgroup_ids_string ? " OR (
												b.`evaluator_type` = 'cgroup_id'
												AND b.`evaluator_value` IN (".$cgroup_ids_string.")
											)" : "")."
										)
										AND a.`evaluation_active` = '1'
										GROUP BY cr.`eprogress_id`";

					$evaluation_record	= $db->GetRow($query);

					if ($evaluation_record) {
						$query = "SELECT a.`equestion_id` FROM `evaluations_lu_questions` AS a
									JOIN `evaluation_form_questions` AS b
									ON a.`equestion_id` = b.`equestion_id`
									JOIN `evaluations_lu_questiontypes` AS c
									ON a.`questiontype_id` = c.`questiontype_id`
									WHERE b.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
									AND c.`questiontype_shortname` = 'rubric'";
						$rubric_found = $db->GetOne($query);
						if (!$rubric_found) {
							$PROCESSED = $evaluation_record;



							if (array_search($PROCESSED["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false) {
									$full_evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation_id, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), true, false);
									$evaluation_targets_count = count($full_evaluation_targets_list);
									if (isset($full_evaluation_targets_list) && $evaluation_targets_count) {
											$evaluation_record["max_submittable"] = ($evaluation_targets_count * (int) $evaluation_record["max_submittable"]);
									}
							}

							$query = "SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
										WHERE `evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])."
										AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
										AND `progress_value` = 'complete'";
							$completed_attempts = $db->GetOne($query);

							if ((!isset($completed_attempts) || !$evaluation_record["max_submittable"] || $completed_attempts < $evaluation_record["max_submittable"]) 
								&& (((int) $evaluation_record["release_date"] === 0) || ($evaluation_record["release_date"] <= time()))) {

								$completed_attempts = evaluations_fetch_attempts($evaluation_id);

								$evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation_id, $evaluation_record["eevaluator_id"], $ENTRADA_USER->getID());
								$max_submittable = $evaluation_record["max_submittable"];
								if ($evaluation_targets_list) {
									$evaluation_targets_count = count($evaluation_targets_list);
									if (array_search($evaluation_record["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation_record["max_submittable"]) {
										$max_submittable = ($evaluation_targets_count * (int) $evaluation_record["max_submittable"]);
									} elseif ($evaluation_record["target_shortname"] == "peer" && $evaluation_record["max_submittable"] == 0) {
										$max_submittable = $evaluation_targets_count;
									}
									if (isset($max_submittable) && $max_submittable) {
										$evaluation_record["max_submittable"] = $max_submittable;
									}
								}

								/**
								 * Providing they can still still make attempts at this evaluation, allow them to continue.
								 */
								if (((int) $evaluation_record["max_submittable"] === 0) || ($completed_attempts < $evaluation_record["max_submittable"])) {
									$content["evaluation_attempt"] .= "<div class=\"content-small\">".clean_input($evaluation_record["target_title"], array("trim", "encode"))." Form</div>";
									$content["evaluation_attempt"] .= "<h1 class=\"evaluation-title\">".html_encode($evaluation_record["evaluation_title"])."</h1>";
									// Error checking
									if (isset($_POST["step"]) && ((int) $_POST["step"])) {
										$STEP = $_POST["step"];
									}

									switch ($STEP) {
										case 2 :

											$PROCESSED_CLERKSHIP_EVENT = array();
											if ((isset($_POST["event_id"])) && ($event_id = clean_input($_POST["event_id"], array("trim", "int"))) && array_search($PROCESSED["target_shortname"], array("rotation_core", "rotation_elective", "preceptor")) !== false) {
												$PROCESSED_CLERKSHIP_EVENT["event_id"] = $event_id;
												$query = "SELECT a.`etarget_id` FROM `evaluation_targets` AS a
															JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
															ON a.`target_value` = b.`rotation_id`
															AND a.`target_type` = 'rotation_id'
															WHERE a.`evaluation_id` = ".$db->qstr($PROCESSED["evaluation_id"])."
															AND b.`event_id` = ".$db->qstr($PROCESSED_CLERKSHIP_EVENT["event_id"]);
												$etarget_id = $db->GetOne($query);
												$PROCESSED["target_record_id"] = $event_id;
											}
											if ($PROCESSED["target_shortname"] == "preceptor") {
												if (isset($_POST["preceptor_proxy_id"]) && ($preceptor_proxy_id = clean_input($_POST["preceptor_proxy_id"]))) {
													$PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] = $preceptor_proxy_id;
												} else {
													$ERROR++;
													$ERRORSTR[] = "Please ensure you have selected a valid preceptor to evaluate from the list.";
												}
											}

											if ((isset($etarget_id) && $etarget_id) || ((isset($_POST["evaluation_target"])) && ($etarget_id = clean_input($_POST["evaluation_target"], array("trim", "int"))))) {
													$query = "SELECT * FROM `evaluation_targets` AS a 
																JOIN `evaluations_lu_targets` AS b 
																ON a.`target_id` = b.`target_id` 
																WHERE a.`evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])." 
																AND a.`etarget_id` = ".$db->qstr($etarget_id);
													$target_record = $db->GetRow($query);

													//If course_id or proxy_id, set based on target_value
													switch ($target_record["target_type"]) {
														case "cgroup_id" :
														case "cohort" :
															if (isset($_POST["target_record_id"]) && ($tmp_value = clean_input($_POST["target_record_id"], array("trim", "int")))) {
																$target_record_id = $tmp_value;
															}
														break;
														case "proxy_id" :
														case "course_id" :
														case "rotation_id" :
														default :
															$target_record_id = $target_record["target_value"];
														break;
													}
													if ((isset($target_record_id) && $target_record_id) || ((isset($_POST["target_record_id"])) && ($target_record_id = clean_input($_POST["target_record_id"], array("trim", "int"))))) {

														$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation_id, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), false, true);

														foreach ($evaluation_targets as $evaluation_target) {
															switch ($evaluation_target["target_type"]) {
																case "cgroup_id" :
																case "cohort" :
																case "proxy_id" :
																	if ($evaluation_target["proxy_id"] == $target_record_id) {
																		$target_record = $evaluation_target;
																	}
																break;
																case "rotation_core" :
																case "rotation_elective" :
																case "preceptor" :
																	if ($evaluation_target["event_id"] == $target_record_id) {
																		$target_record = $evaluation_target;
																	}
																break;
																case "self" :
																	$target_record = $evaluation_target;
																break;
																case "course" :
																default :
																	if ($evaluation_target["course_id"] == $target_record_id) {
																		$target_record = $evaluation_target;
																	}
																break;
															}
															if (isset($target_record)) {
																break;
															}
														}

														if ($target_record) {
															if ($target_record["target_type"] == "proxy_id") {
																$query = "SELECT `etarget_id` FROM `evaluations_progress`
																			WHERE `evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])."
																			AND `progress_value` = 'complete'
																			AND `target_record_id` = ".$db->qstr($target_record_id)."
																			AND `etarget_id` = ".$db->qstr($etarget_id);
																if ($db->GetOne($query)) {
																	$ERROR++;
																	$ERRORSTR[] = "You have already evaluated this ".$target_record["target_shortname"].". Please choose a new target to evaluate.";
																} else {
																	$PROCESSED["etarget_id"] = $etarget_id;
																	$PROCESSED["target_record_id"] = $target_record_id;
																}
															} else {
																$PROCESSED["etarget_id"] = $etarget_id;
																$PROCESSED["target_record_id"] = $target_record_id;
															}
														} else {
															$ERROR++;
															$ERRORSTR[] = "There was an issue with the target you have selected to evaluate. An administrator has been notified, please try again later.";
														}
													} else {
														$ERROR++;
														$ERRORSTR[] = "Please ensure you have selected a valid target to evaluate from the list.";
													}
											} else {
												$ERROR++;
												$ERRORSTR[] = "Please ensure you have selected a valid target to evaluate from the list.";
											}

											/**
											 * Check to see if they currently have any evaluation attempts underway, if they do then
											 * restart their session, otherwise start them a new session.
											 */
											$query = "SELECT *
														FROM `evaluation_progress` AS a
														JOIN `evaluations` AS b
														ON a.`evaluation_id` = b.`evaluation_id`
														WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
														AND a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
														AND a.`progress_value` = 'inprogress'
														ORDER BY a.`updated_date` ASC";
											$progress_record	= $db->GetRow($query);
											if ($progress_record) {
													$eprogress_id		= $progress_record["eprogress_id"];
													$PROCESSED_CLERKSHIP_EVENT["eprogress_id"] = $eprogress_id;
													if (((isset($_POST["responses"])) && (is_array($_POST["responses"])) && (count($_POST["responses"]) > 0)) || (isset($_POST["comments"]) && (count($_POST["comments"]) > 0))) {
														$questions_found = false;
														/**
														 * Get a list of all of the multiple choice questions in this evaluation so we
														 * can run through a clean set of questions.
														 */
														$query = "SELECT a.*, b.*
																	FROM `evaluation_form_questions` AS a
																	JOIN `evaluations_lu_questions` AS b
																	ON a.`equestion_id` = b.`equestion_id`
																	WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
																	AND `questiontype_id` NOT IN (2, 4)
																	ORDER BY a.`question_order` ASC";
														$questions	= $db->GetAll($query);
														if ($questions) {
																$questions_found = true;
																if ((count($_POST["responses"])) != (count($questions))) {
																	$ERROR++;
																	$ERRORSTR[] = "In order to submit your evaluation, you must first answer all of the questions.";
																}

																foreach ($questions as $question) {
																	/**
																	 * Checking to see if the equestion_id was submitted with the
																	 * response $_POST, and if they've actually answered the question.
																	 */
																	if ((isset($_POST["responses"][$question["equestion_id"]])) && ($eqresponse_id = clean_input($_POST["responses"][$question["equestion_id"]], "int"))) {
																		if ((isset($_POST["comments"][$question["equestion_id"]])) && clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"))) {
																			$comments = clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"));
																		} else {
																			$comments = NULL;
																		}
																		if (!Classes_Evaluation::evaluation_save_response($eprogress_id, $progress_record["eform_id"], $question["equestion_id"], $eqresponse_id, $comments)) {
																			$ERROR++;
																			$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";

																			$problem_questions[] = $question["equestion_id"];
																		}
																	} else {
																		$ERROR++;
																		$problem_questions[] = $question["equestion_id"];
																	}
																}
																if ($ERROR && empty($ERRORSTR)) {
																	$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";
																}
														}
														$query = "SELECT a.*, b.*
																	FROM `evaluation_form_questions` AS a
																	JOIN `evaluations_lu_questions` AS b
																	ON a.`equestion_id` = b.`equestion_id`
																	WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
																	AND `questiontype_id` = (4)
																	ORDER BY a.`question_order` ASC";
														$questions	= $db->GetAll($query);
														if ($questions) {
															foreach ($questions as $question) {
																if ((isset($_POST["comments"][$question["equestion_id"]])) && clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"))) {
																	$comments = clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"));
																} else {
																	$comments = NULL;
																}
																if (!Classes_Evaluation::evaluation_save_response($eprogress_id, $progress_record["eform_id"], $question["equestion_id"], 0, $comments)) {
																	$ERROR++;
																	$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";

																	$problem_questions[] = $question["equestion_id"];
																}
															}
														} elseif (!$questions_found) {
															$ERROR++;
															$ERRORSTR[] = "An error occurred while attempting to save your evaluation responses. The system administrator has been notified of this error; please try again later.";

															application_log("error", "Unable to find any evaluation questions for evaluation_id [".$progress_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
														}

														/**
														 * We can now safely say that all questions have valid responses
														 * and that we have stored those responses evaluation_responses table.
														 */
														if (!$ERROR) {
															$evaluation_progress_array = array (
																							"progress_value" => "complete",
																							"evaluation_id" => $evaluation_record["evaluation_id"],
																							"etarget_id" => $PROCESSED["etarget_id"],
																							"target_record_id" => (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : 0),
																							"updated_date" => time(),
																							"updated_by" => $ENTRADA_USER->getID()
																						);

															if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "UPDATE", "eprogress_id = ".$db->qstr($eprogress_id))) {
																if ($evaluation_record["threshold_notifications_type"] != "disabled") {
																	$is_below_threshold = Classes_Evaluation::responsesBelowThreshold($evaluation_record["evaluation_id"], $eprogress_id);
																	if ($is_below_threshold) {
																		if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
																			require_once("Classes/notifications/NotificationUser.class.php");
																			require_once("Classes/notifications/Notification.class.php");
																			$threshold_notification_recipients = Classes_Evaluation::getThresholdNotificationRecipients($evaluation_record["evaluation_id"], $eprogress_id, $PROCESSED["eevaluator_id"]);
																			if (isset($threshold_notification_recipients) && $threshold_notification_recipients) {
																				foreach ($threshold_notification_recipients as $threshold_notification_recipient) {
																					$notification_user = NotificationUser::get($threshold_notification_recipient["proxy_id"], "evaluation_threshold", $evaluation_record["evaluation_id"], $ENTRADA_USER->getID());
																					if (!$notification_user) {
																						$notification_user = NotificationUser::add($threshold_notification_recipient["proxy_id"], "evaluation_threshold", $evaluation_record["evaluation_id"], $ENTRADA_USER->getID());
																					}
																					Notification::add($notification_user->getID(), $ENTRADA_USER->getID(), $eprogress_id);
																				}
																			}
																		}
																	}
																}
																if (array_search($PROCESSED["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false) {
																	if (!$db->AutoExecute("evaluation_progress_clerkship_events", $PROCESSED_CLERKSHIP_EVENT, "INSERT")) {
																		application_log("error", "Unable to record the final clerkship event details for eprogress_id [".$eprogress_id."] in the evaluation_progress_clerkship_events table. Database said: ".$db->ErrorMsg());

																		$ERROR++;
																		$ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
																	} else {
																		/**
																		 * Add a completed evaluation statistic.
																		 */
																		add_statistic("evaluations", "evaluation_complete", "evaluation_id", $evaluation_id);

																		application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$evaluation_id."].");

																		$url = ENTRADA_URL."/evaluations";

																		$SUCCESS++;
																		$SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.";

																		$content["success_status"] = "true";
																	}
																} else {
																	/**
																	 * Add a completed evaluation statistic.
																	 */
																	add_statistic("evaluations", "evaluation_complete", "evaluation_id", $evaluation_id);

																	application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$evaluation_id."].");

																	$url = ENTRADA_URL."/evaluations";

																	$SUCCESS++;
																	$SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.";

																	$content["success_status"] = "true";
																}
															} else {
																application_log("error", "Unable to record the final evaluation results for evaluation_id [".$evaluation_id."] in the evaluation_progress table. Database said: ".$db->ErrorMsg());

																$ERROR++;
																$ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
															}
														}
													} else {
															$ERROR++;
															$ERRORSTR[] = "In order to submit your evaluation for marking, you must first answer some of the questions.";
													}
											} else {
												$ERROR++;
												$ERRORSTR[] = "We were unable to locate an evaluation that is currently in progress.<br /><br />If you pressed your web-browsers back button, please refrain from doing this when you are posting evaluation information.";

												application_log("error", "Unable to locate an evaluation currently in progress when attempting to save an evaluation.");
											}

											if ($ERROR) {
													$STEP = 1;
											}
										break;
										case 1 :
										default :
												continue;
										break;
									}
									if (((int) $evaluation_record["max_submittable"] === 0) || ($completed_attempts < $evaluation_record["max_submittable"])) {

										// Display Content
										switch ($STEP) {
											case 2 :
												if ($SUCCESS) {
													$content["evaluation_attempt"] = display_success(array(), true);
												}
											break;
											case 1 :

											default :
												if ($evaluation_record["evaluation_finish"] < time() && $evaluation_record["min_submittable"] > $completed_attempts) {

													$NOTICE++;
													$NOTICESTR[] = "This evaluation has not been completed and was marked as to be completed by ".date(DEFAULT_DATE_FORMAT, $evaluation_record["evaluation_finish"]).". Please complete this evaluation now to continue using ".APPLICATION_NAME.".";
												}

												if (isset($evaluation_record["evaluation_description"]) && $evaluation_record["evaluation_description"]) {
													$content["evaluation_attempt"] .= "<div class=\"display-generic\">".$evaluation_record["evaluation_description"]."</div>";
												}
												/**
												 * Check to see if they currently have any evaluation attempts underway, if they do then
												 * restart their session, otherwise start them a new session.
												 */
												$query				= "	SELECT *
																		FROM `evaluation_progress`
																		WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
																		AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
																		AND `progress_value` = 'inprogress'
																		ORDER BY `updated_date` ASC";
												$progress_record	= $db->GetRow($query);

												if ($progress_record) {
													$eprogress_id		= $progress_record["eprogress_id"];
													$evaluation_start_time	= $progress_record["updated_date"];
												} else {
													$evaluation_start_time	= time();
													$evaluation_progress_array	= array (
																				"evaluation_id" => $evaluation_id,
																				"proxy_id" => $ENTRADA_USER->getID(),
																				"progress_value" => "inprogress",
																				"etarget_id" => (isset($PROCESSED["etarget_id"]) && $PROCESSED["etarget_id"] ? $PROCESSED["etarget_id"] : 0),
																				"target_record_id" => (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : 0),
																				"updated_date" => $evaluation_start_time,
																				"updated_by" => $ENTRADA_USER->getID()
																			);
													if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "INSERT"))  {
														$eprogress_id = $db->Insert_Id();
													} else {
														$ERROR++;
														$ERRORSTR[] = "Unable to create a progress entry for this evaluation, it is not advisable to continue at this time. The system administrator was notified of this error; please try again later.";

														application_log("error", "Unable to create an evaluation_progress entery when attempting complete an evaluation. Database said: ".$db->ErrorMsg());
													}
												}

												if ($eprogress_id) {
													if ((isset($_GET["proxy_id"])) && ($proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"))) && array_search($PROCESSED["target_shortname"], array("peer", "student", "teacher", "resident")) !== false) {
														$PROCESSED["target_record_id"] = $proxy_id;
													}
													$content["evaluation_attempt"] .= "<form name=\"evaluation-form\" id=\"evaluation-form\" method=\"post\">\n";
													$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"hash\" id=\"evalhash\" value=\"".(isset($user_hash) && $user_hash ? $user_hash : "")."\" />\n";
													$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"username\" id=\"evalusername\" value=\"\" />\n";
													$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"password\" id=\"evalpassword\" value=\"\" />\n";
													$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"method\" value=\"evaluationattempt\" />\n";
													$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"evaluation_id\" value=\"".$evaluation_id."\" />\n";
													add_statistic("evaluation", "evaluation_view", "evaluation_id", $evaluation_id);
													if (!isset($evaluation_targets) || !count($evaluation_targets)) {
														$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation_id, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), false, true);
													}

													if ($evaluation_targets) {
														if (count($evaluation_targets) == 1) {
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
															if ($PROCESSED["target_shortname"] == "teacher") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
																$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
															} elseif ($PROCESSED["target_shortname"] == "resident") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
																$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
															} elseif ($PROCESSED["target_shortname"] == "course") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["course_id"]."\" />";
																$target_name = $db->GetOne("SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_targets[0]["target_value"]));
															} elseif ($PROCESSED["target_shortname"] == "rotation_core" || $PROCESSED["target_shortname"] == "rotation_elective") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"event_id\" name=\"event_id\" value=\"".$evaluation_targets[0]["event_id"]."\" />";
																$target_name = $evaluation_targets[0]["event_title"];
															} elseif ($PROCESSED["target_shortname"] == "self") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$ENTRADA_USER->getID()."\" />";
																$target_name = "Yourself";
															} else {
																if ($evaluation_targets[0]["target_type"] == "proxy_id") {
																	$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
																	$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
																} elseif ($evaluation_targets[0]["target_type"] == "cohort" || $evaluation_targets[0]["target_type"] == "cgroup_id") {
																	$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
																	$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
																	$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
																}
															}
															if ($target_name) {
																$content["evaluation_attempt"] .= "<div class=\"content-small\">Evaluating <strong>".$target_name."</strong>.</div>";
															}
														} elseif ($PROCESSED["target_shortname"] == "teacher") {
															$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a teacher to evaluate: \n";
															$content["evaluation_attempt"] .= "<select id=\"evaluation_target\" name=\"evaluation_target\">";
															$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a teacher --</option>\n";
															foreach ($evaluation_targets as $evaluation_target) {
																if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																	$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["etarget_id"]."\"".($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
																}
															}
															$content["evaluation_attempt"] .= "</select>";
															$content["evaluation_attempt"] .= "</div>";
														} elseif ($PROCESSED["target_shortname"] == "rotation_core" || $PROCESSED["target_shortname"] == "rotation_elective" || $PROCESSED["target_shortname"] == "preceptor") {
															$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a clerkship service to evaluate: \n";
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
															$content["evaluation_attempt"] .= "<select id=\"event_id\" name=\"event_id\"".($PROCESSED["target_shortname"] == "preceptor" ? " onchange=\"loadPreceptors(this.options[this.selectedIndex].value)\"" : "").">";
															$content["evaluation_attempt"] .= "<option value=\"0\">-- Select an event --</option>\n";
															foreach ($evaluation_targets as $evaluation_target) {
																$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["event_id"]."\"".($PROCESSED["event_id"] == $evaluation_target["event_id"] ? " selected=\"selected\"" : "").">".(strpos($evaluation_target["event_title"], $evaluation_target["rotation_title"]) === false ? $evaluation_target["rotation_title"]." - " : "").$evaluation_target["event_title"]."</option>\n";
															}
															$content["evaluation_attempt"] .= "</select>";
															if ($PROCESSED["target_shortname"] == "preceptor") {
																$content["evaluation_attempt"] .= "<div id=\"preceptor_select\" data-role=\"fieldcontain\">\n";
																if (isset($PROCESSED["event_id"]) && $PROCESSED["event_id"]) {
																	$content["evaluation_attempt"] .= Classes_Evaluation::getPreceptorSelect($evaluation_id, $PROCESSED["event_id"], $ENTRADA_USER->getID(), (isset($PROCESSED["preceptor_proxy_id"]) && $PROCESSED["preceptor_proxy_id"] ? $PROCESSED["preceptor_proxy_id"] : 0));
																} else {
																	$content["evaluation_attempt"] .= display_notice("Please select a <strong>Clerkship Service</strong> to evaluate a <strong>Preceptor</strong> for.", true);
																}
																$content["evaluation_attempt"] .= "</div>\n";
															} 
															$content["evaluation_attempt"] .= "</div>";
														} elseif ($PROCESSED["target_shortname"] == "course") {
															$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a course to evaluate: \n";
															$content["evaluation_attempt"] .= "<select id=\"evaluation_target\" name=\"evaluation_target\">";
															$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a course --</option>\n";
															foreach ($evaluation_targets as $evaluation_target) {
																if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																	$target_name = $db->GetOne("SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_target["target_value"]));
																	if ($target_name) {
																		$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["etarget_id"]."\"".($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] ? " selected=\"selected\"" : "").">".$target_name."</option>\n";
																	}
																}
															}
															$content["evaluation_attempt"] .= "</select>";
															$content["evaluation_attempt"] .= "</div>";
														} elseif ($PROCESSED["target_shortname"] == "peer" || $PROCESSED["target_shortname"] == "student") {
															$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a learner to assess: \n";
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
															$content["evaluation_attempt"] .= "<select id=\"target_record_id\" name=\"target_record_id\">";
															$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a learner --</option>\n";
															foreach ($evaluation_targets as $evaluation_target) {
																if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																	$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["proxy_id"]."\"".($PROCESSED["target_record_id"] == $evaluation_target["proxy_id"] ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
																}
															}
															$content["evaluation_attempt"] .= "</select>";
															$content["evaluation_attempt"] .= "</div>";
														} elseif ($PROCESSED["target_shortname"] == "resident") {
															$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a resident to evaluate: \n";
															$content["evaluation_attempt"] .= "<select id=\"evaluation_target\" name=\"evaluation_target\">";
															$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a resident --</option>\n";
															foreach ($evaluation_targets as $evaluation_target) {
																if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																	$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["etarget_id"]."\"".($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] || $PROCESSED["target_record_id"] == $evaluation_target["proxy_id"] ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
																}
															}
															$content["evaluation_attempt"] .= "</select>";
															$content["evaluation_attempt"] .= "</div>";
														}
													}

													$content["evaluation_attempt"] .= "<div id=\"display-unsaved-warning\" class=\"display-notice\" style=\"display: none\">\n";
													$content["evaluation_attempt"] .= "    <ul>\n";
													$content["evaluation_attempt"] .= "        <li><strong>Warning Unsaved Response:</strong><br />Your response to the question indicated by a yellow background was not automatically saved.</li>\n";
													$content["evaluation_attempt"] .= "    </ul>\n";
													$content["evaluation_attempt"] .= "</div>\n";
													if ($ERROR) {
														$content["evaluation_attempt"] .= display_error(array(), true);
													}
													if ($NOTICE) {
														$content["evaluation_attempt"] .= display_notice(array(), true);
													}
													$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"step\" value=\"2\" />\n";
													$query				= "	SELECT a.*, b.*, c.`questiontype_shortname`
																			FROM `evaluation_form_questions` AS a
																			JOIN `evaluations_lu_questions` AS b
																			ON a.`equestion_id` = b.`equestion_id`
																			JOIN `evaluations_lu_questiontypes` AS c
																			ON b.`questiontype_id` = c.`questiontype_id`
																			WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
																			ORDER BY a.`question_order` ASC";
													$questions			= $db->GetAll($query);

													$total_questions	= 0;
													if ($questions) {
														$total_questions = count($questions);
														$content["evaluation_attempt"] .= Classes_Evaluation::getMobileQuestionAnswerControls($questions, $PROCESSED["eform_id"], $eprogress_id);
													} else {
														$ERROR++;
														$ERRORSTR[] = "There are no questions currently available for this evaluation. This problem has been reported to a system administrator; please try again later.";

														application_log("error", "Unable to locate any questions for evaluation [".$evaluation_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
													}
													$content["evaluation_attempt"] .= "<div style=\"border-top: 2px #CCCCCC solid; margin-top: 10px; padding-top: 10px\">\n";
													$content["evaluation_attempt"] .= "    <input type=\"button\" id=\"evaluation-submit\" value=\"Submit Evaluation\" />\n";
													$content["evaluation_attempt"] .= "</div>\n";
													$content["evaluation_attempt"] .= "<div class=\"clear\"></div>\n";
													$content["evaluation_attempt"] .= "</form>\n";
													$content["evaluation_attempt"] .= "<script type=\"text/javascript\">\n";
													$content["evaluation_attempt"] .= "function loadPreceptors(event_id) {\n";
													$content["evaluation_attempt"] .= "    var preceptor_proxy_id = 0;\n";
													$content["evaluation_attempt"] .= "    if ($('#preceptor_proxy_id') && $('#preceptor_proxy_id').selectedIndex > 0) {\n";
													$content["evaluation_attempt"] .= "        preceptor_proxy_id = $('#preceptor_proxy_id').options[$('#preceptor_proxy_id').selectedIndex].value;\n";
													$content["evaluation_attempt"] .= "    }\n";

													$content["evaluation_attempt"] .= "$.ajax({
																							url: '".ENTRADA_URL."/api/mobile.api.php',
																							type: 'post',
																							data: ({method:'preceptor', evaluation_id : '".$evaluation_id."', event_id : event_id, preceptor_proxy_id : preceptor_proxy_id, hash: '".$user_hash."'}),
																							dataType: 'text',
																							success:function (data) {
																								$('#preceptor_select').html(data);
																								if ($('#preceptor_select').hasClass('notice')) {
																									$('#preceptor_select').removeClassName('notice');
																								}
																							}, error: function (error1, error2, data) {
																								$('#preceptor_select').addClassName('notice');\n
			//                                                                                  $('#preceptor_select').update('<ul><li>No <strong>Preceptors</strong> available for evaluation found in the system.</li></ul>');
																							}
																						}).done(function() {
																							if (typeof $('#preceptor_proxy_id') !== 'undefined') {
																								$('#preceptor_proxy_id').selectmenu();
																							}
																						});";
			//                                        $content["evaluation_attempt"] .= "    new Ajax.Updater('preceptor_select', '".ENTRADA_URL."/".$MODULE."?section=api-preceptor-select', {\n";
			//                                        $content["evaluation_attempt"] .= "        method: 'post',\n";
			//                                        $content["evaluation_attempt"] .= "        parameters: { 'id' : '".$evaluation_id."', 'event_id' : event_id, 'preceptor_proxy_id' : preceptor_proxy_id},\n";
			//                                        $content["evaluation_attempt"] .= "        onSuccess: function(transport) {\n";
			//                                        $content["evaluation_attempt"] .= "             console.log('working');\n";
			//                                        $content["evaluation_attempt"] .= "            $('preceptor_select').removeClassName('notice');\n";
			//                                        $content["evaluation_attempt"] .= "        },\n";
			//                                        $content["evaluation_attempt"] .= "        onError: function() {\n";
			//                                        $content["evaluation_attempt"] .= "                 console.log('broke');\n";
			//                                        $content["evaluation_attempt"] .= "                $('preceptor_select').addClassName('notice');\n";
			//                                        $content["evaluation_attempt"] .= "                $('preceptor_select').update('<ul><li>No <strong>Preceptors</strong> available for evaluation found in the system.</li></ul>');\n";
			//                                        $content["evaluation_attempt"] .= "        }\n";
			//                                        $content["evaluation_attempt"] .= "    });\n";
													$content["evaluation_attempt"] .= "}\n";
													$content["evaluation_attempt"] .= "</script>\n";
												} else {
													$ERROR++;
													$ERRORSTR[] = "Unable to locate your progress information for this evaluation at this time. The system administrator has been notified of this error; please try again later.";

													$content["evaluation_attempt"] .= display_error(array(), true);

													application_log("error", "Failed to locate a eprogress_id [".$eprogress_id."] (either existing or created) when attempting to complete evaluation_id [".$evaluation_id."] (eform_id [".$evaluation_record["eform_id"]."]).");
												}
											break;
										}
									}
									$content["evaluation_attempt"] .= "</div>\n";
									echo json_encode($content, JSON_FORCE_OBJECT);
								}

							} else {
								echo "false";
							}
						} else {
							$content = array("success_status" => "false");
							$content["evaluation_attempt"] .= display_notice("Evaluations containing rubrics cannot currently be completed from within the mobile application. To complete this evaluation, please log into ".APPLICATION_NAME.", or <a href=\"".ENTRADA_URL."/evaluations?section=attempt&id=".$evaluation_record["evaluation_id"]."\" onclick=\"window.open(this.href,'_system'); return false;\">click here</a> to complete the evaluation on your device.", true);
							echo json_encode($content, JSON_FORCE_OBJECT);
						}
					} else {
						application_log("error", "Database said: ".$db->ErrorMsg());
						echo "false";
					}
					break;
				case 'preceptor' :
					if (isset($_POST["event_id"]) && ($event_id = clean_input($_POST["event_id"], "int"))) {
						if (isset($_POST["preceptor_proxy_id"]) && ($tmp_input = clean_input($_POST["preceptor_proxy_id"], "int"))) {
							$preceptor_proxy_id = $tmp_input;
						}
						$output = Classes_Evaluation::getPreceptorSelect($evaluation_id, $event_id, $ENTRADA_USER->getID(), (isset($preceptor_proxy_id) && $preceptor_proxy_id ? $preceptor_proxy_id : 0));
						if ($output) {
							echo "<br /><div class=\"content-small\">Please choose a clerkship preceptor to evaluate: \n";
							echo $output;
							echo "</div>\n";
						}
					}
					break;
				case 'mark':
					add_statistic("notices", "read", "notice_id", $notice_id, $user_details['id']);
					echo $notice_id;
					break;
				case 'groups':
					/*if (($ENTRADA_USER->getAllOrganisations() && count($ENTRADA_USER->getAllOrganisations()) > 1) || ($ENTRADA_USER->getOrganisationGroupRole() && max(array_map('count', $ENTRADA_USER->getOrganisationGroupRole())) > 1)) {
						$org_group_role = $ENTRADA_USER->getOrganisationGroupRole();
						foreach ($ENTRADA_USER->getAllOrganisations() as $key => $organisation_title) {
							if ($org_group_role && !empty($org_group_role)) {
								foreach ($org_group_role[$key] as $group_role) {
									$data[] = array (
										'group' => ucfirst($group_role['group']),
										'role' => ucfirst($group_role['role']),
										'organisation_id' => $key
									);
								}
							}
						}
					}*/
					
					
					$data = array();
					$organisation_groups_roles = $ENTRADA_USER->getOrganisationGroupRole();

					foreach ($organisation_groups_roles as $key => $groups_roles) {
						foreach ($groups_roles as $group_role) {
							$data[] = array (
								'group' => ucfirst($group_role['group']),
								'role' => ucfirst($group_role['role']),
								'organisation_id' => $key
							);
						}

					}
					echo json_encode($data);
					break;
				case 'objectives':
					$query = "  SELECT a.* FROM `global_lu_objectives` a
								LEFT JOIN `objective_organisation` b
								ON a.`objective_id` = b.`objective_id`
								AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								WHERE a.`objective_parent` = '1'
								AND a.`objective_active` = '1'
								ORDER BY a.`objective_order`";

					$objectives = $db->GetAll($query);

					if ($objectives) {
						echo json_encode($objectives);
					}

					break;
			}
		break;
		default:
		case 1 :
			switch ($method) {
				case "hash" :
					echo json_encode(array('authenticated' => 'true', 'hash' => $user_details["private_hash"], 'firstname' => $user_details['firstname'], 'lastname' => $user_details['lastname']));
					break;
				case "credentials" :
					echo true;
					break;
				case "agenda":

					$user_proxy_id = $user_details["id"];
					//$user_role = $user_details["role"];
					//$user_group = $user_details["group"];
					$user_role = strtolower($role);
					$user_group = strtolower($group);
					$user_organisation_id = $user_details["organisation_id"];

					$event_start = strtotime("-12 months 00:00:00");
					$event_finish = strtotime("+12 months 23:59:59");
					/*$learning_events = events_fetch_filtered_events(
						$user_proxy_id,
						$user_group,
						$user_role,
						$user_organisation_id,
						"date",
						"asc",
						"custom",
						$event_start,
						$event_finish,
						events_filters_defaults($user_proxy_id, $user_group, $user_role),
						true,
						1,
						1750);*/
					
					$learning_events = events_fetch_filtered_events(
                        $ENTRADA_USER->getActiveId(),
                        $user_group,
						$user_role,
                        $ENTRADA_USER->getActiveOrganisation(),
                        "date",
                        "asc",
                        "custom",
                        $event_start,
						$event_finish,
                        events_filters_defaults($ENTRADA_USER->getActiveId(), $user_group, $user_role, $ENTRADA_USER->getActiveOrganisation()),
                        false,
                        0,
                        0,
                        0,
                        false);

					$events = array();
					if (!empty($learning_events["events"])) {
						foreach ($learning_events["events"] as $drid => $event) {
							$cal_type = 1;
							$cal_updated = "";

							if ($event["audience_type"] == "proxy_id") {
								$cal_type = 3;
							}

							if (((int) $event["last_visited"]) && ((int) $event["last_visited"] < (int) $event["updated_date"])) {
								$cal_type = 2;

								$cal_updated = date(DEFAULT_DATE_FORMAT, $event["updated_date"]);
							}

							$events[] = array(
								"id" => $event["event_id"],
								"start_date"	=> date("o-m-d G:i", $event["event_start"]),
								"end_date" => date("o-m-d G:i", $event["event_finish"]),
								"text" => strip_tags($event["event_title"]),
								"details" => $event["event_description"]. "<br /><b>Event Duration: </b>". $event["event_duration"] . " minutes <br /><b>Location: </b>". ($event["event_location"] == "" ? "To be announced" : $event["event_location"]) ."<br /><br /><a href='#' data-role='button' class='back' onclick='window.open(\"". ENTRADA_URL ."/events?rid=" . $event['event_id'] . "\", \"_blank\", \"location=yes\");'>Review Learning Event</a><br><br><br>",
							);
						}
					}

					echo json_encode($events);
					break;
				case "notices" :

					$notices_to_display = Models_Notice::fetchUserNotices(true);

					if ($notices_to_display) {
						$rows = 0;
						foreach ($notices_to_display as $result) {
							if ((!$result["statistic_id"]) || ($result["last_read"] <= $result["updated_date"])) {
								$result['notice_status'] = 'new';
								$result["updated_date"] = date(DEFAULT_DATE_FORMAT, $result["updated_date"]);
								$notices_to_display[] = $result;
							} else {
								$result['notice_status'] = 'read';
								$result["updated_date"] = date(DEFAULT_DATE_FORMAT, $result["updated_date"]);
								$notices_to_display[] = $result;
							}
							$rows ++;
						}

						echo json_encode($notices_to_display, JSON_FORCE_OBJECT);	
					}

					break;
				case "evaluations" :

					$evaluations_list = array();
					$evaluations = Classes_Evaluation::getEvaluatorEvaluations($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());

					if ($evaluations) {				
						if (count($evaluations)) {
							foreach ($evaluations as $evaluation) {
								if ($evaluation["max_submittable"] > $evaluation["completed_attempts"]) {
									$evaluations_list[] = $evaluation;
								}
							}
							if (count($evaluations_list)) {
								echo json_encode($evaluations_list, JSON_FORCE_OBJECT);
							} else {
								echo "false";
							}
						} else {
								echo "false";
						}
					}
					break;
				case "evaluationattempt" :

					$content = array("evaluation_attempt" => "<div id=\"evaluation_attempt\">\n", "success_status" => "false");

					//require_once("Models/evaluation/Evaluation.class.php");
					$cohort = groups_get_cohort($ENTRADA_USER->getID());

					$query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
								JOIN `course_groups` AS b
								ON a.`cgroup_id` = b.`cgroup_id`
								WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
								AND a.`active` = 1
								AND b.`active` = 1";
					$course_groups = $db->GetAll($query);


					$cgroup_ids_string = "";
					if (isset($course_groups) && is_array($course_groups)) {
						foreach ($course_groups as $course_group) {
							if ($cgroup_ids_string) {
								$cgroup_ids_string .= ", ".$db->qstr($course_group["cgroup_id"]);
							} else {
								$cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
							}
						}
					}

					$query			= "SELECT a.*, c.`eprogress_id`, e.`target_title`, c.`etarget_id`, b.`eevaluator_id`, e.`target_shortname`
										FROM `evaluations` AS a
										LEFT JOIN `evaluation_evaluators` AS b
										ON a.`evaluation_id` = b.`evaluation_id`
										LEFT JOIN `evaluation_progress` AS c
										ON a.`evaluation_id` = c.`evaluation_id`
										AND c.`progress_value` = 'inprogress'
										AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
										LEFT JOIN `evaluation_responses` AS cr
										ON c.`eprogress_id` = cr.`eprogress_id`
										LEFT JOIN `evaluation_targets` AS d
										ON a.`evaluation_id` = d.`evaluation_id`
										LEFT JOIN `evaluation_forms` AS ef
										ON a.`eform_id` = ef.`eform_id`
										LEFT JOIN `evaluations_lu_targets` AS e
										ON ef.`target_id` = e.`target_id`
										WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
										AND 
										(
											(
												b.`evaluator_type` = 'proxy_id'
												AND b.`evaluator_value` = ".$db->qstr($ENTRADA_USER->getID())."
											)
											OR
											(
												b.`evaluator_type` = 'organisation_id'
												AND b.`evaluator_value` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
											)".($ENTRADA_USER->getActiveGroup() == "student" ? " OR (
												b.`evaluator_type` = 'cohort'
												AND b.`evaluator_value` = ".$db->qstr($cohort["group_id"])."
											)" : "").($cgroup_ids_string ? " OR (
												b.`evaluator_type` = 'cgroup_id'
												AND b.`evaluator_value` IN (".$cgroup_ids_string.")
											)" : "")."
										)
										AND a.`evaluation_active` = '1'
										GROUP BY cr.`eprogress_id`";

					$evaluation_record	= $db->GetRow($query);

					if ($evaluation_record) {
						$PROCESSED = $evaluation_record;



						if (array_search($PROCESSED["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false) {
								$full_evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation_id, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), true, false);
								$evaluation_targets_count = count($full_evaluation_targets_list);
								if (isset($full_evaluation_targets_list) && $evaluation_targets_count) {
										$evaluation_record["max_submittable"] = ($evaluation_targets_count * (int) $evaluation_record["max_submittable"]);
								}
						}

						$query = "SELECT COUNT(`eprogress_id`) FROM `evaluation_progress`
									WHERE `evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])."
									AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
									AND `progress_value` = 'complete'";
						$completed_attempts = $db->GetOne($query);

						if ((!isset($completed_attempts) || !$evaluation_record["max_submittable"] || $completed_attempts < $evaluation_record["max_submittable"]) 
							&& (((int) $evaluation_record["release_date"] === 0) || ($evaluation_record["release_date"] <= time()))) {

							$completed_attempts = evaluations_fetch_attempts($evaluation_id);

							$evaluation_targets_list = Classes_Evaluation::getTargetsArray($evaluation_id, $evaluation_record["eevaluator_id"], $ENTRADA_USER->getID());
							$max_submittable = $evaluation_record["max_submittable"];
							if ($evaluation_targets_list) {
								$evaluation_targets_count = count($evaluation_targets_list);
								if (array_search($evaluation_record["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation_record["max_submittable"]) {
									$max_submittable = ($evaluation_targets_count * (int) $evaluation_record["max_submittable"]);
								} elseif ($evaluation_record["target_shortname"] == "peer" && $evaluation_record["max_submittable"] == 0) {
									$max_submittable = $evaluation_targets_count;
								}
								if (isset($max_submittable) && $max_submittable) {
									$evaluation_record["max_submittable"] = $max_submittable;
								}
							}

							/**
							 * Providing they can still still make attempts at this evaluation, allow them to continue.
							 */
							if (((int) $evaluation_record["max_submittable"] === 0) || ($completed_attempts < $evaluation_record["max_submittable"])) {
								$content["evaluation_attempt"] .= "<div class=\"content-small\">".clean_input($evaluation_record["target_title"], array("trim", "encode"))." Form</div>";
								$content["evaluation_attempt"] .= "<h1 class=\"evaluation-title\">".html_encode($evaluation_record["evaluation_title"])."</h1>";
								// Error checking
								if (isset($_POST["step"]) && ((int) $_POST["step"])) {
									$STEP = $_POST["step"];
								}

								switch ($STEP) {
									case 2 :

										$PROCESSED_CLERKSHIP_EVENT = array();
										if ((isset($_POST["event_id"])) && ($event_id = clean_input($_POST["event_id"], array("trim", "int"))) && array_search($PROCESSED["target_shortname"], array("rotation_core", "rotation_elective", "preceptor")) !== false) {
											$PROCESSED_CLERKSHIP_EVENT["event_id"] = $event_id;
											$query = "SELECT a.`etarget_id` FROM `evaluation_targets` AS a
														JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
														ON a.`target_value` = b.`rotation_id`
														AND a.`target_type` = 'rotation_id'
														WHERE a.`evaluation_id` = ".$db->qstr($PROCESSED["evaluation_id"])."
														AND b.`event_id` = ".$db->qstr($PROCESSED_CLERKSHIP_EVENT["event_id"]);
											$etarget_id = $db->GetOne($query);
											$PROCESSED["target_record_id"] = $event_id;
										}
										if ($PROCESSED["target_shortname"] == "preceptor") {
											if (isset($_POST["preceptor_proxy_id"]) && ($preceptor_proxy_id = clean_input($_POST["preceptor_proxy_id"]))) {
												$PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] = $preceptor_proxy_id;
											} else {
												$ERROR++;
												$ERRORSTR[] = "Please ensure you have selected a valid preceptor to evaluate from the list.";
											}
										}

										if ((isset($etarget_id) && $etarget_id) || ((isset($_POST["evaluation_target"])) && ($etarget_id = clean_input($_POST["evaluation_target"], array("trim", "int"))))) {
												$query = "SELECT * FROM `evaluation_targets` AS a 
															JOIN `evaluations_lu_targets` AS b 
															ON a.`target_id` = b.`target_id` 
															WHERE a.`evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])." 
															AND a.`etarget_id` = ".$db->qstr($etarget_id);
												$target_record = $db->GetRow($query);

												//If course_id or proxy_id, set based on target_value
												switch ($target_record["target_type"]) {
													case "cgroup_id" :
													case "cohort" :
														if (isset($_POST["target_record_id"]) && ($tmp_value = clean_input($_POST["target_record_id"], array("trim", "int")))) {
															$target_record_id = $tmp_value;
														}
													break;
													case "proxy_id" :
													case "course_id" :
													case "rotation_id" :
													default :
														$target_record_id = $target_record["target_value"];
													break;
												}
												if ((isset($target_record_id) && $target_record_id) || ((isset($_POST["target_record_id"])) && ($target_record_id = clean_input($_POST["target_record_id"], array("trim", "int"))))) {

													$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation_id, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), false, true);

													foreach ($evaluation_targets as $evaluation_target) {
														switch ($evaluation_target["target_type"]) {
															case "cgroup_id" :
															case "cohort" :
															case "proxy_id" :
																if ($evaluation_target["proxy_id"] == $target_record_id) {
																	$target_record = $evaluation_target;
																}
															break;
															case "rotation_core" :
															case "rotation_elective" :
															case "preceptor" :
																if ($evaluation_target["event_id"] == $target_record_id) {
																	$target_record = $evaluation_target;
																}
															break;
															case "self" :
																$target_record = $evaluation_target;
															break;
															case "course" :
															default :
																if ($evaluation_target["course_id"] == $target_record_id) {
																	$target_record = $evaluation_target;
																}
															break;
														}
														if (isset($target_record)) {
															break;
														}
													}

													if ($target_record) {
														if ($target_record["target_type"] == "proxy_id") {
															$query = "SELECT `etarget_id` FROM `evaluations_progress`
																		WHERE `evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])."
																		AND `progress_value` = 'complete'
																		AND `target_record_id` = ".$db->qstr($target_record_id)."
																		AND `etarget_id` = ".$db->qstr($etarget_id);
															if ($db->GetOne($query)) {
																$ERROR++;
																$ERRORSTR[] = "You have already evaluated this ".$target_record["target_shortname"].". Please choose a new target to evaluate.";
															} else {
																$PROCESSED["etarget_id"] = $etarget_id;
																$PROCESSED["target_record_id"] = $target_record_id;
															}
														} else {
															$PROCESSED["etarget_id"] = $etarget_id;
															$PROCESSED["target_record_id"] = $target_record_id;
														}
													} else {
														$ERROR++;
														$ERRORSTR[] = "There was an issue with the target you have selected to evaluate. An administrator has been notified, please try again later.";
													}
												} else {
													$ERROR++;
													$ERRORSTR[] = "Please ensure you have selected a valid target to evaluate from the list.";
												}
										} else {
											$ERROR++;
											$ERRORSTR[] = "Please ensure you have selected a valid target to evaluate from the list.";
										}

										/**
										 * Check to see if they currently have any evaluation attempts underway, if they do then
										 * restart their session, otherwise start them a new session.
										 */
										$query = "SELECT *
													FROM `evaluation_progress` AS a
													JOIN `evaluations` AS b
													ON a.`evaluation_id` = b.`evaluation_id`
													WHERE a.`evaluation_id` = ".$db->qstr($evaluation_id)."
													AND a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
													AND a.`progress_value` = 'inprogress'
													ORDER BY a.`updated_date` ASC";
										$progress_record	= $db->GetRow($query);
										if ($progress_record) {
												$eprogress_id		= $progress_record["eprogress_id"];
												$PROCESSED_CLERKSHIP_EVENT["eprogress_id"] = $eprogress_id;
												if (((isset($_POST["responses"])) && (is_array($_POST["responses"])) && (count($_POST["responses"]) > 0)) || (isset($_POST["comments"]) && (count($_POST["comments"]) > 0))) {
													$questions_found = false;
													/**
													 * Get a list of all of the multiple choice questions in this evaluation so we
													 * can run through a clean set of questions.
													 */
													$query = "SELECT a.*, b.*
																FROM `evaluation_form_questions` AS a
																JOIN `evaluations_lu_questions` AS b
																ON a.`equestion_id` = b.`equestion_id`
																WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
																AND `questiontype_id` NOT IN (2, 4)
																ORDER BY a.`question_order` ASC";
													$questions	= $db->GetAll($query);
													if ($questions) {
															$questions_found = true;
															if ((count($_POST["responses"])) != (count($questions))) {
																$ERROR++;
																$ERRORSTR[] = "In order to submit your evaluation, you must first answer all of the questions.";
															}

															foreach ($questions as $question) {
																/**
																 * Checking to see if the equestion_id was submitted with the
																 * response $_POST, and if they've actually answered the question.
																 */
																if ((isset($_POST["responses"][$question["equestion_id"]])) && ($eqresponse_id = clean_input($_POST["responses"][$question["equestion_id"]], "int"))) {
																	if ((isset($_POST["comments"][$question["equestion_id"]])) && clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"))) {
																		$comments = clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"));
																	} else {
																		$comments = NULL;
																	}
																	if (!Classes_Evaluation::evaluation_save_response($eprogress_id, $progress_record["eform_id"], $question["equestion_id"], $eqresponse_id, $comments)) {
																		$ERROR++;
																		$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";

																		$problem_questions[] = $question["equestion_id"];
																	}
																} else {
																	$ERROR++;
																	$problem_questions[] = $question["equestion_id"];
																}
															}
															if ($ERROR && empty($ERRORSTR)) {
																$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";
															}
													}
													$query = "SELECT a.*, b.*
																FROM `evaluation_form_questions` AS a
																JOIN `evaluations_lu_questions` AS b
																ON a.`equestion_id` = b.`equestion_id`
																WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
																AND `questiontype_id` = (4)
																ORDER BY a.`question_order` ASC";
													$questions	= $db->GetAll($query);
													if ($questions) {
														foreach ($questions as $question) {
															if ((isset($_POST["comments"][$question["equestion_id"]])) && clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"))) {
																$comments = clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"));
															} else {
																$comments = NULL;
															}
															if (!Classes_Evaluation::evaluation_save_response($eprogress_id, $progress_record["eform_id"], $question["equestion_id"], 0, $comments)) {
																$ERROR++;
																$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";

																$problem_questions[] = $question["equestion_id"];
															}
														}
													} elseif (!$questions_found) {
														$ERROR++;
														$ERRORSTR[] = "An error occurred while attempting to save your evaluation responses. The system administrator has been notified of this error; please try again later.";

														application_log("error", "Unable to find any evaluation questions for evaluation_id [".$progress_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
													}

													/**
													 * We can now safely say that all questions have valid responses
													 * and that we have stored those responses evaluation_responses table.
													 */
													if (!$ERROR) {
														$evaluation_progress_array = array (
																						"progress_value" => "complete",
																						"evaluation_id" => $evaluation_record["evaluation_id"],
																						"etarget_id" => $PROCESSED["etarget_id"],
																						"target_record_id" => (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : 0),
																						"updated_date" => time(),
																						"updated_by" => $ENTRADA_USER->getID()
																					);

														if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "UPDATE", "eprogress_id = ".$db->qstr($eprogress_id))) {
															if ($evaluation_record["threshold_notifications_type"] != "disabled") {
																$is_below_threshold = Classes_Evaluation::responsesBelowThreshold($evaluation_record["evaluation_id"], $eprogress_id);
																if ($is_below_threshold) {
																	if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
																		require_once("Classes/notifications/NotificationUser.class.php");
																		require_once("Classes/notifications/Notification.class.php");
																		$threshold_notification_recipients = Classes_Evaluation::getThresholdNotificationRecipients($evaluation_record["evaluation_id"], $eprogress_id, $PROCESSED["eevaluator_id"]);
																		if (isset($threshold_notification_recipients) && $threshold_notification_recipients) {
																			foreach ($threshold_notification_recipients as $threshold_notification_recipient) {
																				$notification_user = NotificationUser::get($threshold_notification_recipient["proxy_id"], "evaluation_threshold", $evaluation_record["evaluation_id"], $ENTRADA_USER->getID());
																				if (!$notification_user) {
																					$notification_user = NotificationUser::add($threshold_notification_recipient["proxy_id"], "evaluation_threshold", $evaluation_record["evaluation_id"], $ENTRADA_USER->getID());
																				}
																				Notification::add($notification_user->getID(), $ENTRADA_USER->getID(), $eprogress_id);
																			}
																		}
																	}
																}
															}
															if (array_search($PROCESSED["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false) {
																if (!$db->AutoExecute("evaluation_progress_clerkship_events", $PROCESSED_CLERKSHIP_EVENT, "INSERT")) {
																	application_log("error", "Unable to record the final clerkship event details for eprogress_id [".$eprogress_id."] in the evaluation_progress_clerkship_events table. Database said: ".$db->ErrorMsg());

																	$ERROR++;
																	$ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
																} else {
																	/**
																	 * Add a completed evaluation statistic.
																	 */
																	add_statistic("evaluations", "evaluation_complete", "evaluation_id", $evaluation_id);

																	application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$evaluation_id."].");

																	$url = ENTRADA_URL."/evaluations";

																	$SUCCESS++;
																	$SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.";

																	$content["success_status"] = "true";
																}
															} else {
																/**
																 * Add a completed evaluation statistic.
																 */
																add_statistic("evaluations", "evaluation_complete", "evaluation_id", $evaluation_id);

																application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$evaluation_id."].");

																$url = ENTRADA_URL."/evaluations";

																$SUCCESS++;
																$SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.";

																$content["success_status"] = "true";
															}
														} else {
															application_log("error", "Unable to record the final evaluation results for evaluation_id [".$evaluation_id."] in the evaluation_progress table. Database said: ".$db->ErrorMsg());

															$ERROR++;
															$ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
														}
													}
												} else {
														$ERROR++;
														$ERRORSTR[] = "In order to submit your evaluation for marking, you must first answer some of the questions.";
												}
										} else {
											$ERROR++;
											$ERRORSTR[] = "We were unable to locate an evaluation that is currently in progress.<br /><br />If you pressed your web-browsers back button, please refrain from doing this when you are posting evaluation information.";

											application_log("error", "Unable to locate an evaluation currently in progress when attempting to save an evaluation.");
										}

										if ($ERROR) {
												$STEP = 1;
										}
									break;
									case 1 :
									default :
											continue;
									break;
								}
								if (((int) $evaluation_record["max_submittable"] === 0) || ($completed_attempts < $evaluation_record["max_submittable"])) {

									// Display Content
									switch ($STEP) {
										case 2 :
											if ($SUCCESS) {
												$content["evaluation_attempt"] = display_success();
											}
										break;
										case 1 :

										default :
											if ($evaluation_record["evaluation_finish"] < time() && $evaluation_record["min_submittable"] > $completed_attempts) {

												$NOTICE++;
												$NOTICESTR[] = "This evaluation has not been completed and was marked as to be completed by ".date(DEFAULT_DATE_FORMAT, $evaluation_record["evaluation_finish"]).". Please complete this evaluation now to continue using ".APPLICATION_NAME.".";
											}

											if (isset($evaluation_record["evaluation_description"]) && $evaluation_record["evaluation_description"]) {
												$content["evaluation_attempt"] .= "<div class=\"display-generic\">".$evaluation_record["evaluation_description"]."</div>";
											}
											/**
											 * Check to see if they currently have any evaluation attempts underway, if they do then
											 * restart their session, otherwise start them a new session.
											 */
											$query				= "	SELECT *
																	FROM `evaluation_progress`
																	WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
																	AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
																	AND `progress_value` = 'inprogress'
																	ORDER BY `updated_date` ASC";
											$progress_record	= $db->GetRow($query);

											if ($progress_record) {
												$eprogress_id		= $progress_record["eprogress_id"];
												$evaluation_start_time	= $progress_record["updated_date"];
											} else {
												$evaluation_start_time	= time();
												$evaluation_progress_array	= array (
																			"evaluation_id" => $evaluation_id,
																			"proxy_id" => $ENTRADA_USER->getID(),
																			"progress_value" => "inprogress",
																			"etarget_id" => (isset($PROCESSED["etarget_id"]) && $PROCESSED["etarget_id"] ? $PROCESSED["etarget_id"] : 0),
																			"target_record_id" => (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : 0),
																			"updated_date" => $evaluation_start_time,
																			"updated_by" => $ENTRADA_USER->getID()
																		);
												if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "INSERT"))  {
													$eprogress_id = $db->Insert_Id();
												} else {
													$ERROR++;
													$ERRORSTR[] = "Unable to create a progress entry for this evaluation, it is not advisable to continue at this time. The system administrator was notified of this error; please try again later.";

													application_log("error", "Unable to create an evaluation_progress entery when attempting complete an evaluation. Database said: ".$db->ErrorMsg());
												}
											}

											if ($eprogress_id) {
												if ((isset($_GET["proxy_id"])) && ($proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"))) && array_search($PROCESSED["target_shortname"], array("peer", "student", "teacher", "resident")) !== false) {
													$PROCESSED["target_record_id"] = $proxy_id;
												}
												$content["evaluation_attempt"] .= "<form name=\"evaluation-form\" id=\"evaluation-form\" method=\"post\">\n";
												$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"hash\" id=\"evalhash\" value=\"".(isset($user_hash) && $user_hash ? $user_hash : "")."\" />\n";
												$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"username\" id=\"evalusername\" value=\"\" />\n";
												$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"password\" id=\"evalpassword\" value=\"\" />\n";
												$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"method\" value=\"evaluationattempt\" />\n";
												$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"evaluation_id\" value=\"".$evaluation_id."\" />\n";
												add_statistic("evaluation", "evaluation_view", "evaluation_id", $evaluation_id);
												if (!isset($evaluation_targets) || !count($evaluation_targets)) {
													$evaluation_targets = Classes_Evaluation::getTargetsArray($evaluation_id, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), false, true);
												}

												if ($evaluation_targets) {
													if (count($evaluation_targets) == 1) {
														$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
														if ($PROCESSED["target_shortname"] == "teacher") {
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
															$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
														} elseif ($PROCESSED["target_shortname"] == "resident") {
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
															$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
														} elseif ($PROCESSED["target_shortname"] == "course") {
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["course_id"]."\" />";
															$target_name = $db->GetOne("SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_targets[0]["target_value"]));
														} elseif ($PROCESSED["target_shortname"] == "rotation_core" || $PROCESSED["target_shortname"] == "rotation_elective") {
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"event_id\" name=\"event_id\" value=\"".$evaluation_targets[0]["event_id"]."\" />";
															$target_name = $evaluation_targets[0]["event_title"];
														} elseif ($PROCESSED["target_shortname"] == "self") {
															$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$ENTRADA_USER->getID()."\" />";
															$target_name = "Yourself";
														} else {
															if ($evaluation_targets[0]["target_type"] == "proxy_id") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
																$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
															} elseif ($evaluation_targets[0]["target_type"] == "cohort" || $evaluation_targets[0]["target_type"] == "cgroup_id") {
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
																$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
																$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
															}
														}
														if ($target_name) {
															$content["evaluation_attempt"] .= "<div class=\"content-small\">Evaluating <strong>".$target_name."</strong>.</div>";
														}
													} elseif ($PROCESSED["target_shortname"] == "teacher") {
														$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a teacher to evaluate: \n";
														$content["evaluation_attempt"] .= "<select id=\"evaluation_target\" name=\"evaluation_target\">";
														$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a teacher --</option>\n";
														foreach ($evaluation_targets as $evaluation_target) {
															if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["etarget_id"]."\"".($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
															}
														}
														$content["evaluation_attempt"] .= "</select>";
														$content["evaluation_attempt"] .= "</div>";
													} elseif ($PROCESSED["target_shortname"] == "rotation_core" || $PROCESSED["target_shortname"] == "rotation_elective" || $PROCESSED["target_shortname"] == "preceptor") {
														$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a clerkship service to evaluate: \n";
														$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
														$content["evaluation_attempt"] .= "<select id=\"event_id\" name=\"event_id\"".($PROCESSED["target_shortname"] == "preceptor" ? " onchange=\"loadPreceptors(this.options[this.selectedIndex].value)\"" : "").">";
														$content["evaluation_attempt"] .= "<option value=\"0\">-- Select an event --</option>\n";
														foreach ($evaluation_targets as $evaluation_target) {
															$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["event_id"]."\"".($PROCESSED["event_id"] == $evaluation_target["event_id"] ? " selected=\"selected\"" : "").">".(strpos($evaluation_target["event_title"], $evaluation_target["rotation_title"]) === false ? $evaluation_target["rotation_title"]." - " : "").$evaluation_target["event_title"]."</option>\n";
														}
														$content["evaluation_attempt"] .= "</select>";
														if ($PROCESSED["target_shortname"] == "preceptor") {
															$content["evaluation_attempt"] .= "<div id=\"preceptor_select\">\n";
															if (isset($PROCESSED["event_id"]) && $PROCESSED["event_id"]) {
																$content["evaluation_attempt"] .= Classes_Evaluation::getPreceptorSelect($evaluation_id, $PROCESSED["event_id"], $ENTRADA_USER->getID(), (isset($PROCESSED["preceptor_proxy_id"]) && $PROCESSED["preceptor_proxy_id"] ? $PROCESSED["preceptor_proxy_id"] : 0));
															} else {
																$content["evaluation_attempt"] .= display_notice("Please select a <strong>Clerkship Service</strong> to evaluate a <strong>Preceptor</strong> for.");
															}
															$content["evaluation_attempt"] .= "</div>\n";
														} 
														$content["evaluation_attempt"] .= "</div>";
													} elseif ($PROCESSED["target_shortname"] == "course") {
														$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a course to evaluate: \n";
														$content["evaluation_attempt"] .= "<select id=\"evaluation_target\" name=\"evaluation_target\">";
														$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a course --</option>\n";
														foreach ($evaluation_targets as $evaluation_target) {
															if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																$target_name = $db->GetOne("SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_target["target_value"]));
																if ($target_name) {
																	$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["etarget_id"]."\"".($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] ? " selected=\"selected\"" : "").">".$target_name."</option>\n";
																}
															}
														}
														$content["evaluation_attempt"] .= "</select>";
														$content["evaluation_attempt"] .= "</div>";
													} elseif ($PROCESSED["target_shortname"] == "peer" || $PROCESSED["target_shortname"] == "student") {
														$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a learner to assess: \n";
														$content["evaluation_attempt"] .= "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
														$content["evaluation_attempt"] .= "<select id=\"target_record_id\" name=\"target_record_id\">";
														$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a learner --</option>\n";
														foreach ($evaluation_targets as $evaluation_target) {
															if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["proxy_id"]."\"".($PROCESSED["target_record_id"] == $evaluation_target["proxy_id"] ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
															}
														}
														$content["evaluation_attempt"] .= "</select>";
														$content["evaluation_attempt"] .= "</div>";
													} elseif ($PROCESSED["target_shortname"] == "resident") {
														$content["evaluation_attempt"] .= "<div class=\"content-small\">Please choose a resident to evaluate: \n";
														$content["evaluation_attempt"] .= "<select id=\"evaluation_target\" name=\"evaluation_target\">";
														$content["evaluation_attempt"] .= "<option value=\"0\">-- Select a resident --</option>\n";
														foreach ($evaluation_targets as $evaluation_target) {
															if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
																$content["evaluation_attempt"] .= "<option value=\"".$evaluation_target["etarget_id"]."\"".($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] || $PROCESSED["target_record_id"] == $evaluation_target["proxy_id"] ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
															}
														}
														$content["evaluation_attempt"] .= "</select>";
														$content["evaluation_attempt"] .= "</div>";
													}
												}

												$content["evaluation_attempt"] .= "<div id=\"display-unsaved-warning\" class=\"display-notice\" style=\"display: none\">\n";
												$content["evaluation_attempt"] .= "    <ul>\n";
												$content["evaluation_attempt"] .= "        <li><strong>Warning Unsaved Response:</strong><br />Your response to the question indicated by a yellow background was not automatically saved.</li>\n";
												$content["evaluation_attempt"] .= "    </ul>\n";
												$content["evaluation_attempt"] .= "</div>\n";
												if ($ERROR) {
													$content["evaluation_attempt"] .= display_error();
												}
												if ($NOTICE) {
													$content["evaluation_attempt"] .= display_notice();
												}
												$content["evaluation_attempt"] .= "<input type=\"hidden\" name=\"step\" value=\"2\" />\n";
												$query				= "	SELECT a.*, b.*, c.`questiontype_shortname`
																		FROM `evaluation_form_questions` AS a
																		JOIN `evaluations_lu_questions` AS b
																		ON a.`equestion_id` = b.`equestion_id`
																		JOIN `evaluations_lu_questiontypes` AS c
																		ON b.`questiontype_id` = c.`questiontype_id`
																		WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
																		ORDER BY a.`question_order` ASC";
												$questions			= $db->GetAll($query);

												$total_questions	= 0;
												if ($questions) {
													$total_questions = count($questions);
													$content["evaluation_attempt"] .= Classes_Evaluation::getMobileQuestionAnswerControls($questions, $PROCESSED["eform_id"], $eprogress_id);
												} else {
													$ERROR++;
													$ERRORSTR[] = "There are no questions currently available for this evaluation. This problem has been reported to a system administrator; please try again later.";

													application_log("error", "Unable to locate any questions for evaluation [".$evaluation_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
												}
												$content["evaluation_attempt"] .= "<div style=\"border-top: 2px #CCCCCC solid; margin-top: 10px; padding-top: 10px\">\n";
												$content["evaluation_attempt"] .= "    <input type=\"button\" id=\"evaluation-submit\" value=\"Submit Evaluation\" />\n";
												$content["evaluation_attempt"] .= "</div>\n";
												$content["evaluation_attempt"] .= "<div class=\"clear\"></div>\n";
												$content["evaluation_attempt"] .= "</form>\n";
												$content["evaluation_attempt"] .= "<script type=\"text/javascript\">\n";
												$content["evaluation_attempt"] .= "function loadPreceptors(event_id) {\n";
												$content["evaluation_attempt"] .= "    var preceptor_proxy_id = 0;\n";
												$content["evaluation_attempt"] .= "    if ($('preceptor_proxy_id') && $('preceptor_proxy_id').selectedIndex > 0) {\n";
												$content["evaluation_attempt"] .= "        preceptor_proxy_id = $('preceptor_proxy_id').options[$('preceptor_proxy_id').selectedIndex].value;\n";
												$content["evaluation_attempt"] .= "    }\n";
												$content["evaluation_attempt"] .= "    new Ajax.Updater('preceptor_select', '".ENTRADA_URL."/".$MODULE."?section=api-preceptor-select', {\n";
												$content["evaluation_attempt"] .= "        method: 'post',\n";
												$content["evaluation_attempt"] .= "        parameters: { 'id' : '".$evaluation_id."', 'event_id' : event_id, 'preceptor_proxy_id' : preceptor_proxy_id},\n";
												$content["evaluation_attempt"] .= "        onSuccess: function(transport) {\n";
												$content["evaluation_attempt"] .= "            $('preceptor_select').removeClassName('notice');\n";
												$content["evaluation_attempt"] .= "        },\n";
												$content["evaluation_attempt"] .= "        onError: function() {\n";
												$content["evaluation_attempt"] .= "                $('preceptor_select').addClassName('notice');\n";
												$content["evaluation_attempt"] .= "                $('preceptor_select').update('<ul><li>No <strong>Preceptors</strong> available for evaluation found in the system.</li></ul>');\n";
												$content["evaluation_attempt"] .= "        }\n";
												$content["evaluation_attempt"] .= "    });\n";
												$content["evaluation_attempt"] .= "}\n";
												$content["evaluation_attempt"] .= "</script>\n";
												$sidebar_html = evaluation_generate_description($evaluation_record["min_submittable"], $total_questions, $evaluation_record["max_submittable"], $evaluation_record["evaluation_finish"]);
												new_sidebar_item("Evaluation Statement", $sidebar_html, "page-anchors", "open", "1.9");
											} else {
												$ERROR++;
												$ERRORSTR[] = "Unable to locate your progress information for this evaluation at this time. The system administrator has been notified of this error; please try again later.";

												$content["evaluation_attempt"] .= display_error();

												application_log("error", "Failed to locate a eprogress_id [".$eprogress_id."] (either existing or created) when attempting to complete evaluation_id [".$evaluation_id."] (eform_id [".$evaluation_record["eform_id"]."]).");
											}
										break;
									}
								}
								$content["evaluation_attempt"] .= "</div>\n";
								echo json_encode($content, JSON_FORCE_OBJECT);
							}

						} else {
							echo "false";
						}
					} else {
						application_log("error", "Database said: ".$db->ErrorMsg());
						echo "false";
					}
					break;
				case 'mark':
					add_statistic("notices", "read", "notice_id", $notice_id, $user_details['id']);
					echo $notice_id;
					break;
				case 'groups':
					$data = array();
					$organisation_groups_roles = $ENTRADA_USER->getOrganisationGroupRole();

					foreach ($organisation_groups_roles as $groups_roles) {
						foreach ($groups_roles as $group_role) {
							$data[] = array (
								'group' => ucfirst($group_role['group']),
								'role' => ucfirst($group_role['role'])
							);
						}

					}
					echo json_encode($data);
					break;
				case 'objectives':
					$query = "  SELECT a.* FROM `global_lu_objectives` a
								LEFT JOIN `objective_organisation` b
								ON a.`objective_id` = b.`objective_id`
								AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								WHERE a.`objective_parent` = '1'
								AND a.`objective_active` = '1'
								ORDER BY a.`objective_order`";

					$objectives = $db->GetAll($query);

					if ($objectives) {
						echo json_encode($objectives);
					}

					break;
			}
		break;
		
	}
} else {
	echo json_encode(array('authenticated' => 'false'));
	exit;
}
?>