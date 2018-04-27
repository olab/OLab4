<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * Module:	Reports
 * Area:		Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2007 Queen's University, MEdTech Unit
 *
 * $Id: teaching-report-by-department-workforce.inc.php 957 2009-12-18 14:14:32Z simpson $
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} else if (!$ENTRADA_ACL->amIAllowed('report', 'read', false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $report_view = new Zend_View();
    $report_view->setScriptPath(dirname(__FILE__));
    $report_view->translate = $translate;
    $report_view->APPLICATION_IDENTIFIER = APPLICATION_IDENTIFIER;
    $report_view->SECTION = $SECTION;
    $report_view->MODULE = $MODULE;
    $report_view->STEP = $STEP;

    ini_set("max_execution_time", 120);
    $BREADCRUMB[]	= array("url" => "", "title" => "Faculty Teaching Report By Department" );

    $report_view->PROCESSED = $PROCESSED = array();
    $report_view->PROCESSED["show_all_teachers"]	= $PROCESSED["show_all_teachers"]	= true;

    if ((isset($_POST["update"])) && ((!isset($_POST["show_all_teachers"])) || ($_POST["show_all_teachers"] != "1"))) {
        $report_view->PROCESSED["show_all_teachers"] = $PROCESSED["show_all_teachers"] = false;
    }

    function display_half_days($convert = 0, $type = "lecture") {
		if (defined(REPORTS_CALC_HALF_DAYS) && REPORTS_CALC_HALF_DAYS !== false) {
			if ($convert = (int) $convert) {
				switch($type) {
					case "lecture" :
					case "lab" :
					case "exam" :
					case "interview" :
						// 2 HD's per session.
						$number = round(($convert * 2), 2);
						break;
					case "small_group" :
					case "review" :
					case "patient_contact" :
					case "symposium" :
					case "clerkship_seminar" :
					case "directed_learning" :
					case "observership" :
						// 1 HD's per session.
						$number = $convert;
						break;
					case "events" :
					default :
						// 2 HD's per hour.
//							$number =  round((round(($convert / 60), 2) * 2), 2);
						// 2 HD's per session.
						$number =  round(($convert * 2), 2);

						break;
				}

				return $number.(($number != 1) ? " HDs" : " HD");
			}

			return "";
		} else {
        	return $convert;
		}
    }

    if ($STEP < 2 || !$STEP) {
        echo $report_view->render("teaching-report-by-department.view.php");
    } else if ($STEP == 2) {
        $report_view->DEFAULT_DATE_FORMAT = DEFAULT_DATE_FORMAT;
        $int_use_cache		= true;
        $event_ids			= array();
        $report_results		= array();
        $report_view->view_data = array();
        $report_view->view_data['no_staff_number']	= array();
        $report_view->view_data['department_sidebar']	= array();
		$report_view->view_data['event_types'] = array();

        $default_na_name	= "Department";

        $query	= "	SELECT a.`id` AS `proxy_id`, a.`number` AS `staff_number`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON b.`user_id` = a.`id`
                    AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND b.`group` = 'faculty'
                    ORDER BY `fullname`";
        if ($int_use_cache) {
            $results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
        } else {
            $results = $db->GetAll($query);
        }

        if ($results) {
			$report_results["courses"]["other_event_types"]	= array();

            foreach ($results as $result_index => $result) {
                $department_id	= $default_na_name;
                $division_id	= "Division";

                $query = "SELECT a.`department_title`
                            FROM `".AUTH_DATABASE."`.`departments` AS a
                            JOIN `".AUTH_DATABASE."`.`user_departments` AS b
                            ON b.`dep_id` = a.`department_id`
                            AND b.`user_id` = ".$db->qstr($result["proxy_id"]);
                $dresult	= $db->GetRow($query);

                if ($dresult) {
                    $department_id = $dresult["department_title"];
                    $division_id = $dresult["department_title"];
                }

                $i = @count($report_results["departments"][$department_id][$division_id]["people"]);
                $report_results["departments"][$department_id][$division_id]["people"][$i]["fullname"]				= $result["fullname"];
                $report_results["departments"][$department_id][$division_id]["people"][$i]["number"]				= $result["staff_number"];
                $report_results["departments"][$department_id][$division_id]["people"][$i]["contributor"]			= false;
				$report_results["departments"][$department_id][$division_id]["people"][$i]["other_event_types"]		= array();

                $query = "	SELECT 
                				a.`event_id`, 
                				a.`event_title`, 
                				a.`course_id`, 
                				c.`eventtype_id`, 
                				c.`duration` AS `segment_duration`,
                				et.eventtype_title
                            FROM `events` AS a
                            JOIN `event_contacts` AS b
                            ON b.`event_id` = a.`event_id`
                            JOIN `event_eventtypes` AS c
                            ON c.`event_id` = a.`event_id`
                            LEFT JOIN events_lu_eventtypes et
                            ON et.eventtype_id = c.eventtype_id
                            JOIN `courses` AS d
                            ON d.`course_id` = a.`course_id`
                            WHERE b.`proxy_id` = ".$db->qstr($result["proxy_id"])."
                            AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
                            AND d.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());

                $sresults	= $db->GetAll($query);

                if ($sresults) {
                    $report_results["departments"][$department_id][$division_id]["people"][$i]["contributor"]	= true;

                    foreach($sresults as $sresult) {
                        if (!in_array($sresult["event_id"], $event_ids)) {
                            $event_ids[]		= $sresult["event_id"];
                            $increment_total	= true;
                        } else {
                            $increment_total	= false;
                        }

						if (!isset($report_view->view_data['event_types'][$sresult["eventtype_id"]])) {
							$report_view->view_data['event_types'][$sresult["eventtype_id"]] = $sresult['eventtype_title'];
						}

						/* need to add an array index here for the specific event type, will try just using eventtype_id
						*/
						if (!isset($report_results["departments"][$department_id][$division_id]["people"][$i]["other_event_types"][$sresult["eventtype_id"]])) {
							$report_results["departments"][$department_id][$division_id]["people"][$i]["other_event_types"][$sresult["eventtype_id"]] = array();
						}

						$report_results["departments"][$department_id][$division_id]["people"][$i]["other_event_types"][$sresult["eventtype_id"]]["total_events"] += 1;
						$report_results["departments"][$department_id][$division_id]["people"][$i]["other_event_types"][$sresult["eventtype_id"]]["total_minutes"]	+= (int) $sresult["segment_duration"];

						if ($increment_total) {
							if (!isset($report_results["courses"]["other_event_types"][$sresult["eventtype_id"]])) {
								$report_results["courses"]["other_event_types"][$sresult["eventtype_id"]] = array(
									"total_minutes" => 0,
									"total_events" => 0,
									"events_calculated" => 0,
									"events_minutes" => 0
								);
							}

							$report_results["courses"]["other_event_types"][$sresult["eventtype_id"]]["total_minutes"]	+= (int) $sresult["segment_duration"];

							if (!isset($report_results["departments"][$department_id][$division_id]["courses"]["other_event_types"][$sresult["eventtype_id"]])) {
								$report_results["departments"][$department_id][$division_id]["courses"]["other_event_types"][$sresult["eventtype_id"]] = array("total_events" => 0, "total_minutes" => 0);
							}

							$report_results["departments"][$department_id][$division_id]["courses"]["other_event_types"][$sresult["eventtype_id"]]["total_events"]	+= 1;
							$report_results["departments"][$department_id][$division_id]["courses"]["other_event_types"][$sresult["eventtype_id"]]["total_minutes"]	+= (int) $sresult["segment_duration"];
						}

						$report_results["courses"]["other_event_types"][$sresult["eventtype_id"]]["total_events"] += 1;
						$report_results["courses"]["other_event_types"][$sresult["eventtype_id"]]["events_calculated"] += 1;
						$report_results["courses"]["other_event_types"][$sresult["eventtype_id"]]["events_minutes"] += (int) $sresult["segment_duration"];
						$report_results["departments"][$department_id][$division_id]["courses"]["other_event_types"][$sresult["eventtype_id"]]["events_calculated"]	+= 1;
						$report_results["departments"][$department_id][$division_id]["courses"]["other_event_types"][$sresult["eventtype_id"]]["events_minutes"] += (int) $sresult["segment_duration"];
                    }
                }

                $query = "SELECT * FROM `ar_internal_contributions`
                            WHERE `proxy_id` = ".$db->qstr($result["proxy_id"])."
                            AND `year_reported` BETWEEN 
                                ".date("Y", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." 
                                    AND 
                                ".date("Y", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"])."
                            AND `role_description` IN ('Interviewer', 'Reader')";
                if ($int_use_cache) {
                    $iresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
                } else {
                    $iresults	= $db->GetAll($query);
                }
                if ($iresults) {
                    foreach ($iresults as $iresult) {
                        if ($iresult["role_description"] == "Interviewer") {
                            $sessions = ceil((($iresult["time_commitment"] / 9) * 2));
                        } else {
                            $sessions = round(($iresult["time_commitment"] / 4));
                        }
                        if (!$sessions) {
                            $sessions = 1;
                        }
                        $report_results["departments"][$department_id][$division_id]["people"][$i]["interview"]["total_events"]		+= $sessions;

                        if ($increment_total) {
                            $report_results["courses"]["interview"]["total_events"]													+= $sessions;
                            $report_results["departments"][$department_id][$division_id]["courses"]["interview"]["total_events"]	+= $sessions;
                        }

                        $report_results["courses"]["interview"]["events_calculated"]												+= $sessions;
                        $report_results["departments"][$department_id][$division_id]["courses"]["interview"]["events_calculated"]	+= $sessions;
                    }
                }

                $query = "SELECT * FROM `student_observerships`
                            WHERE `preceptor_proxy_id` = ".$db->qstr($result["proxy_id"])."
                            AND `start` BETWEEN 
                                ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." 
                                    AND 
                                ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);

                if ($int_use_cache) {
                    $oresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
                } else {
                    $oresults	= $db->GetAll($query);
                }
                if ($oresults) {
                    foreach ($oresults as $oresult) {
                        if ($oresult["end"]) {
                            $time_period = $oresult["end"] - $oresult["start"];
                            $days = round(($time_period / 86400));
                            if (!$days) {
                                $days = 1;
                            }
                        } else {
                            $days = 1;
                        }
                        $report_results["departments"][$department_id][$division_id]["people"][$i]["observership"]["total_events"]		+= $days;

                        if ($increment_total) {
                            $report_results["courses"]["observership"]["total_events"]													+= $days;
                            $report_results["departments"][$department_id][$division_id]["courses"]["observership"]["total_events"]		+= $days;
                        }

                        $report_results["courses"]["observership"]["events_calculated"]													+= $days;
                        $report_results["departments"][$department_id][$division_id]["courses"]["observership"]["events_calculated"]	+= $days;
                    }
                }
            }
        }

        if (isset($report_results) && !empty($report_results)) {
            ksort($report_results["departments"]);

            $department_list = array_keys($report_results["departments"]);
            foreach($department_list as $department) {
                ksort($report_results["departments"][$department]);

                if (is_array($report_results["departments"][$department][$default_na_name])) {
                    $tmp_array = $report_results["departments"][$department][$default_na_name];
                    unset($report_results["departments"][$department][$default_na_name]);
                    $report_results["departments"][$department][$default_na_name] = $tmp_array;
                }
            }

            if (is_array($report_results["departments"][$default_na_name])) {
                $tmp_array = $report_results["departments"][$default_na_name];
                unset($report_results["departments"][$default_na_name]);
                $report_results["departments"][$default_na_name] = $tmp_array;
            }

            if ((is_array($report_results["departments"])) && (count($report_results["departments"]))) {
                foreach($report_results["departments"] as $department_name => $department_entries) {
                    $report_view->view_data['departments'][$department_name] = array(
                        'department_entries' => $department_entries
                    );
                }

                if ((is_array($report_results["courses"])) && (count($report_results["courses"]))) {
                    $report_view->view_data["courses"] = array();
                }

                foreach($report_view->view_data['departments'] as $department_name => &$department_data) {
                    $department_data['department_duration_final_total']	= 0;
                    $department_data['department_session_final_total'] = 0;
                    $department_link		= clean_input($department_name, "credentials");
                    $report_view->view_data['department_sidebar'][]	= array("department_name" => $department_name, "department_link" => "#".$department_link);
                    $department_data['department_link'] = $department_link;

                    if ((is_array($department_data['department_entries'])) && (count($department_data['department_entries']))) {
                        foreach($department_data['department_entries'] as $division_name => &$division_entries) {
                            $division_duration_final_total				= 0;

                            if ((is_array($division_entries["people"])) && (count($division_entries["people"]))) {
                                $i = 0;

                                foreach($division_entries["people"] as &$result) {
                                    if (!$result["number"]) {
                                        $report_view->view_data['no_staff_number'][] = array("fullname" => $result["fullname"], "email" => $result["email"]);
                                    }

                                    $result['duration_total'] = 0;

									foreach ($result["other_event_types"] as $other_event_type_result) {
										$result['duration_total'] += $other_event_type_result["total_minutes"];
									}

                                    $result['duration_total']		= ($duration_lecture + $duration_lab + $duration_small_group + $duration_patient_contact + $duration_symposium + $duration_directed_learning + $duration_review + $duration_exam + $duration_clerkship_seminar/* + $duration_events */);

                                    $session_total		= 0;
									$result['session_other_event_types'] = array();
									$result['session_total'] = 0;

									foreach ($result["other_event_types"] as $event_type_id => $other_event_type_result) {
										if (!isset($other_event_type_result["total_events"])) {
											$result['session_other_event_types'][$event_type_id] = 0;
										} else {
											$result['session_other_event_types'][$event_type_id] = $other_event_type_result["total_events"];
											$result['session_total'] += $other_event_type_result["total_events"];
										}
									}
                                }
                            }

                            if ((is_array($division_entries["courses"])) && (count($division_entries["courses"]))) {
								$division_entries['division_duration_final_total'] = 0;

								foreach ($division_entries["courses"]["other_event_types"] as $other_event_type_entry) {
									$division_entries['division_duration_final_total'] += $other_event_type_entry["events_minutes"];
								}

								$division_entries['division_session_total_other_event_types'] = array();

								foreach ($division_entries["courses"]["other_event_types"] as $event_type_id => $other_event_type_entry) {
									if (!isset($other_event_type_entry["events_calculated"])) {
										$division_entries['division_session_total_other_event_types'][$event_type_id] = 0;
									} else {
										$division_entries['division_session_total_other_event_types'][$event_type_id] = $other_event_type_entry["events_calculated"];
									}
								}

                                $division_entries['division_duration_final_total']		+= ($division_duration_total_lecture + $division_duration_total_lab + $division_duration_total_small_group + $division_duration_total_patient_contact + $division_duration_total_symposium + $division_duration_total_directed_learning + $division_duration_total_review + $division_duration_total_exam + $division_duration_total_clerkship_seminar /*+ $division_duration_total_events*/);

                                if ($division_entries['division_duration_final_total'] && $division_entries['division_session_final_total']) {
                                    $department_data['department_duration_final_total']	+= $division_entries['division_duration_final_total'];
                                    $department_data['department_session_final_total']		+= $division_entries['division_session_final_total'];
                                }
                            }
                        }

                        $absolute_duration_final_total				+= $department_data['department_duration_final_total'];
                        $absolute_session_final_total				+= $department_data['department_session_final_total'];
                    }
                }
            }

            if ((is_array($report_results["courses"])) && (count($report_results["courses"]))) {
				$report_view->view_data["courses"]['session_final_total'] = 0;
				$report_view->view_data["courses"]['duration_final_total'] = 0;

				foreach ($report_results["courses"]["other_event_types"] as $event_type_id => $other_event_type_entry) {
					if (isset($other_event_type_entry["events_minutes"])) {
						$report_view->view_data["courses"]['duration_final_total'] += $other_event_type_entry["events_minutes"];
					}
				}

				$report_view->view_data["courses"]["session_total_other_event_types"] = array();

				foreach ($report_results["courses"]["other_event_types"] as $event_type_id => $other_event_type_entry) {
					if (!isset($other_event_type_entry["events_calculated"])) {
						$report_view->view_data["courses"]["session_total_other_event_types"][$event_type_id] = 0;
					} else {
						$report_view->view_data["courses"]["session_total_other_event_types"][$event_type_id] = $other_event_type_entry["events_calculated"];
						$report_view->view_data["courses"]['session_final_total'] += $other_event_type_entry["events_calculated"];
					}
				}

                $report_view->view_data["courses"]['duration_final_total'] = ($duration_total_lecture + $duration_total_lab + $duration_total_pbl + $duration_total_small_group + $duration_total_patient_contact + $duration_total_symposium + $duration_total_directed_learning + $duration_total_review + $duration_total_exam + $duration_total_clerkship_seminar/* + $duration_total_events*/);
                $report_view->view_data["courses"]['session_final_total']	= ($session_total_lecture + $session_total_lab + $session_total_pbl + $session_total_small_group + $session_total_patient_contact + $session_total_symposium + $session_total_directed_learning + $session_total_review + $session_total_exam + $session_total_clerkship_seminar/* + $session_total_events*/);
            }

            $report_view->view_data['total_no_staff_number'] = count($report_view->view_data['no_staff_number']);
            echo $report_view->render("teaching-report-by-department.view.php");
        } else {
            echo '<div class="display-notice">There were no faculty found using the specified parameters. Try selecting a different organisation or date range.</div>';
        }
    }
}
