<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for generating course syllabi.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
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

require_once("init.inc.php");

$org_id = 0;

if (isset($argv[1])) {
	$org_id = (int) $argv[1];
}

if ($org_id && is_dir(CACHE_DIRECTORY) && is_writable(CACHE_DIRECTORY)) {
	/**
	 * Lock present: application busy: quit
	 */
	if (!file_exists(CACHE_DIRECTORY."/generate_syllabi.lck")) {
		if (@file_put_contents(CACHE_DIRECTORY."/generate_syllabi.lck", "L_O_C_K")) {
			application_log("notice", "Syllabus generation lock file created.");

			$query = "SELECT * FROM `courses` WHERE `organisation_id` = ".$db->qstr($org_id)." AND `course_active` = 1";
			$results = $db->GetAll($query);
			
			if ($results) {
				foreach ($results as $result) {
					$syllabus = Models_Syllabus::fetchRowByCourseID($result["course_id"], 1);
					echo "\n---------------------------------------------------------------------\n";
					echo "Starting syllabus generation for course: [".$result["course_code"]."-".$syllabus->getID()."]\n";
					if (!is_null($syllabus->getID())) {
						unset($pages_html);

						$query = "SELECT c.`curriculum_period_title` AS `curriculum_type_name`, c.`start_date`, c.`finish_date`
									FROM `courses` AS a 
									JOIN `course_audience` AS b
									ON a.`course_id` = b.`course_id`
									JOIN `curriculum_periods` AS c
									ON b.`cperiod_id` = c.`cperiod_id`
									WHERE a.`course_id` = ".$db->qstr($result["course_id"])."
                                    AND UNIX_TIMESTAMP(NOW()) > c.`start_date` - 1209600 
                                    AND UNIX_TIMESTAMP(NOW()) < c.`finish_date`
                                    AND c.`active` = 1";
						$eperiod_data = $db->GetRow($query);
                        if ($eperiod_data) {
                            $course = $syllabus->getCourse();
                            $course_contacts = $course->getContacts();
                            $enrolment_period = !empty($eperiod_data["curriculum_type_name"]) ? $eperiod_data["curriculum_type_name"] : date("F jS, Y", $eperiod_data["start_date"]) . " to " . date("F jS, Y", $eperiod_data["finish_date"]);

                            if(file_exists($ENTRADA_TEMPLATE->absolute()."/syllabus/cover.html")) {
                                $cover_template = file_get_contents($ENTRADA_TEMPLATE->absolute()."/syllabus/cover.html");
                                $cover_search_terms = array(
                                    "%COURSE_CODE%",
                                    "%COURSE_NAME%",
                                    "%E_PERIOD%",
                                    "%AGENT_CONTACT_NAME%",
                                    "%AGENT_CONTACT_EMAIL%",
                                    "%YEAR%",
                                    "%ENTRADA_URL%"
                                );
                                $cover_replace_values = array(
                                    $course->getCourseCode(),
                                    $course->getCourseName(),
                                    $enrolment_period,
                                    $AGENT_CONTACTS["general-contact"]["name"],
                                    $AGENT_CONTACTS["general-contact"]["email"],
                                    date("Y"),
                                    ENTRADA_URL
                                );
                                file_put_contents(SYLLABUS_STORAGE."/cover-".$syllabus->getID().".html", str_replace($cover_search_terms, $cover_replace_values, $cover_template));
                            }
                            $search_terms = array();
                            include $ENTRADA_TEMPLATE->absolute()."/syllabus/page-whitelist.inc.php";
                            if(file_exists($ENTRADA_TEMPLATE->absolute()."/syllabus/".$syllabus->getTemplate().".php")) {
                                $template = file_get_contents($ENTRADA_TEMPLATE->absolute()."/syllabus/".$syllabus->getTemplate().".php");
                                if (!empty($page_whitelist[$course->getOrganisationID()][$syllabus->getTemplate()])) {
                                    $whitelist = array_keys($page_whitelist[$course->getOrganisationID()][$syllabus->getTemplate()]);
                                    $cc_key = array_search("course_calendar", $whitelist);
                                    if ($cc_key) {
                                        unset($whitelist[$cc_key]);
                                    }
                                    $search_terms = $page_whitelist[$course->getOrganisationID()][$syllabus->getTemplate()];
                                } else {
                                    $whitelist = $page_whitelist[$course->getOrganisationID()]["default"];
                                }
                            }

                            $pages = $course->getPages(NULL, $whitelist);
                            $pages_html = array();
                            foreach ($pages as $page) {
                                if (strlen(trim($page["page_content"])) > 0) {
                                    $pages_html[$page["page_url"]] = "";
                                    $pages_html[$page["page_url"]] .= "<div class=\"page ".($level == 1 ? "break" : "")."\">";
                                    $pages_html[$page["page_url"]] .= "<h1>".$page["page_title"]."</h1>";
                                    $pages_html[$page["page_url"]] .= "<div class=\"page-content\">".$page["page_content"]."</div>";
                                    $pages_html[$page["page_url"]] .= "</div>";
                                }
                            }

                            $contact_types = array(
                                "director" => "Director",
                                "ccoordinator" => "Curricular Coordinator",
                                "pcoordinator" => "Program Coordinator"
                            );

                            $contacts_html = "";

                            if (is_array($course_contacts) && !empty($course_contacts)) {
                                $contacts_html .= "<h1>Course Contacts</h1>";
                                foreach ($course_contacts as $contact_type => $contacts) {
                                    $contacts_html .= "<p><strong>".  $contact_types[$contact_type] . (count($contacts) > 1 ? "s" : "") . "</strong></p>";
                                    foreach ($contacts as $contact_id => $contact) {
                                        $contacts_html .= "<div class=\"contact\">";
                                        $contacts_html .= "<p><strong>" . ($contact->getPrefix() ? $contact->getPrefix() . " " : "") . $contact->getFullName()."</strong></p>";
                                        if ($contact_type != "director") {
                                            $contacts_html .= "<p>".
                                                                ($contact->getTelephone() ? "Telephone: " . $contact->getTelephone() : "").
                                                                ($contact->getFax() ? ($contact->getTelephone() ? "<br />" : "") . "Fax: ".$contact->getFax() : "").
                                                                ($contact->getOfficeHours() ? ($contact->getFax() || $contact->getTelephone() ? "<br /><br />" : "") . "Office Hours: " . $contact->getOfficeHours() : "").
                                                              "</p>";
                                        }
                                        $contacts_html .= "<p><a href=\"mailto:".$contact->getEmail()."\">".$contact->getEmail()."</a></p>";
                                        $contacts_html .= "</div>";
                                    }
                                }
                            }

                            if (is_null($pages_html["course_contacts"]) && in_array("%COURSE_CONTACTS%", $search_terms)) {
                                $pages_html["course_contacts"] = $contacts_html;
                            }

                            $events = $course->getEvents($syllabus->getStart(), $syllabus->getFinish());
                            $calendar_html = "";
                            if (is_array($events) && !empty($events)) {
                                foreach ($events as $event) {
                                    $calendar_html .= "<div class=\"event\">";
                                    $calendar_html .= "<p><strong>".$event["event_title"]."</strong></p>";
                                    $calendar_html .= "<p><small>".date("l, F jS, Y, g:i A", $event["event_start"])." to ".date(date("z", $event["event_finish"]) == date("z", $event["event_start"]) ? "g:i A" : "l, F jS, Y, g:i A",  $event["event_finish"])."</small></p>";
                                    $calendar_html .= "<p>".$event["event_description"]."</p>";

                                    if ($event["objectives"]) {
                                        $calendar_html .= "<p style=\"margin:0px 30px;\">Event Objectives: <em>" . html_encode(implode(", ", $event["objectives"]))."</em>";
                                    }			

                                    $calendar_html .= "</div>";
                                }
                            }

                            // Event Types By Course Report Start
                            $output		= array();
                            $appendix	= array();

                            $courses_included	= array();
                            $eventtype_legend	= array();

                            $query = "	SELECT a.* FROM `events_lu_eventtypes` AS a 
                                        LEFT JOIN `eventtype_organisation` AS c 
                                        ON a.`eventtype_id` = c.`eventtype_id` 
                                        LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
                                        ON b.`organisation_id` = c.`organisation_id` 
                                        WHERE b.`organisation_id` = ".$db->qstr($course->getOrganisationID())."
                                        AND a.`eventtype_active` = '1' 
                                        ORDER BY a.`eventtype_order`";
                            $event_types = $db->GetAll($query);
                            if ($event_types) {
                                foreach ($event_types as $event_type) {
                                    $eventtype_legend[$event_type["eventtype_id"]] = $event_type["eventtype_title"];

                                    $query = "	SELECT a.`event_id`, b.`course_name`, a.`event_title`, a.`event_start`, c.`duration`, d.`eventtype_title`
                                                FROM `events` AS a
                                                LEFT JOIN `courses` AS b
                                                ON b.`course_id` = a.`course_id`
                                                LEFT JOIN `event_eventtypes` AS c
                                                ON c.`event_id` = a.`event_id`
                                                LEFT JOIN `events_lu_eventtypes` AS d
                                                ON d.`eventtype_id` = c.`eventtype_id`
                                                WHERE c.`eventtype_id` = ".$db->qstr($event_type["eventtype_id"])."
                                                AND (a.`parent_id` IS NULL OR a.`parent_id` = 0)
                                                AND (a.`event_start` BETWEEN ".$db->qstr($syllabus->getStart())." AND ".$db->qstr($syllabus->getFinish()).")
                                                AND a.`course_id` = ".$db->qstr($course->getID())."
                                                ORDER BY d.`eventtype_order` ASC, b.`course_name` ASC, a.`event_start` ASC";
                                    $results = $db->GetAll($query);
                                    if ($results) {
                                        $courses_included[$course_id] = $course_list[$course_id]["code"] . " - " . $course_list[$course_id]["name"];

                                        foreach ($results as $result) {
                                            $output[$course_id]["events"][$event_type["eventtype_id"]]["duration"] += $result["duration"];
                                            $output[$course_id]["events"][$event_type["eventtype_id"]]["events"] += 1;

                                            $appendix[$course_id][$result["event_id"]][] = $result;
                                        }

                                        $output[$course_id]["total_duration"] += $output[$course_id]["events"][$event_type["eventtype_id"]]["duration"];
                                        $output[$course_id]["total_events"] += $output[$course_id]["events"][$event_type["eventtype_id"]]["events"];
                                    }
                                }
                            }

                            if (count($output)) {
                                $eventtypes_html = "<h1>" . $translate->_("Learning Event Types") . "</h1>";
    //							@todo: move this to external api call
    //							$eventtypes_html .= "<div class=\"center\"><img src=\"".str_replace("https","http",ENTRADA_URL)."/cron/syllabus_gen.php?mode=graph&course_id=".$course->getID()."&start_date=".strtotime($start_string)."&end_date=".strtotime($end_string)."\" /></div>";
                                foreach ($output as $course_id => $result) {
                                    $STATISTICS					= array();
                                    $STATISTICS["labels"]		= array();
                                    $STATISTICS["legend"]		= array();
                                    $STATISTICS["results"]		= array();

                                    $eventtypes_html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
                                    $eventtypes_html .= "<thead>";
                                    $eventtypes_html .= "<tr>";
                                    $eventtypes_html .= "<th style=\"text-align:left;\"><strong>" . $translate->_("Event Type") . "</strong></td>";
                                    $eventtypes_html .= "<th style=\"text-align:left;\"><strong>Event Count</strong></td>";
                                    $eventtypes_html .= "<th style=\"text-align:left;\"><strong>Hour Count</strong></td>";
                                    $eventtypes_html .= "</tr>";		
                                    $eventtypes_html .= "</thead>";
                                    $eventtypes_html .= "<tbody>";

                                    foreach ($result["events"] as $eventtype_id => $event) {
                                        $STATISTICS["labels"][$eventtype_id] = $eventtype_legend[$eventtype_id];
                                        $STATISTICS["legend"][$eventtype_id] = $eventtype_legend[$eventtype_id];
                                        $STATISTICS["display"][$eventtype_id] = $event["events"];

                                        $all_events[] = $event["events"];
                                        $all_labels[] = $eventtype_legend[$eventtype_id];

                                        if ($result["total_events"] > 0) {
                                            $percent_events = round((($event["events"] / $result["total_events"]) * 100));
                                        } else {
                                            $percent_events = 0;
                                        }

                                        if ($result["total_duration"] > 0) {
                                            $percent_duration = round((($event["duration"] / $result["total_duration"]) * 100));
                                        } else {
                                            $percent_duration = 0;
                                        }

                                        $eventtypes_html .= "<tr>";
                                        $eventtypes_html .= "<td>".html_encode($eventtype_legend[$eventtype_id])."</td>";
                                        $eventtypes_html .= "<td class=\"report-hours large\" style=\"text-align: left\">".$event["events"]." (~ ".$percent_events."%)</td>";
                                        $eventtypes_html .= "<td class=\"report-hours large\" style=\"text-align: left\">".display_hours($event["duration"])." hrs (~ ".$percent_duration."%)</td>";
                                        $eventtypes_html .= "</tr>";
                                    }
                                    $eventtypes_html .= "</tbody>";
                                    $eventtypes_html .= "<tfoot>";
                                    $eventtypes_html .= "<tr>";
                                    $eventtypes_html .= "<td><strong>Totals</strong></td>";
                                    $eventtypes_html .= "<td><strong>".$result["total_events"]."</strong></td>";
                                    $eventtypes_html .= "<td><strong>".display_hours($result["total_duration"])." hrs</strong></td>";
                                    $eventtypes_html .= "</tr>";
                                    $eventtypes_html .= "</tfoot>";
                                    $eventtypes_html .= "</table>";
                                }
                            }

                            $ENTRADA_USER = new User();
                            $ENTRADA_USER->setActiveOrganisation($org_id);
                            list($objectives,$top_level_id) = courses_fetch_objectives($org_id, array($course->getID()), -1, 1, false);
                            $pages_html["course_objectives"] = course_objectives_in_list($objectives, $top_level_id, $top_level_id, false, false, 1, false);
                            if ($objectives) {
                                $pages_html["course_objectives"] = "<h1>Course Objectives</h1>" . $pages_html["course_objectives"];
                            } else {
                                unset($pages_html["course_objectives"]);
                            }

                            /* Gradebook */
                            $query =  "	SELECT a.`course_id`, a.`assessment_id`, a.`name`, a.`grade_weighting`, a.`order`, c.`title`
                                        FROM `assessments` AS a
                                        JOIN `course_audience` AS b
                                        ON a.`course_id` = b.`course_id`
                                        AND a.`cohort` = b.`audience_value`
                                        JOIN `assessments_lu_meta` AS c
                                        ON `a`.`characteristic_id` = c.`id`
                                        JOIN `curriculum_periods` as d
                                        ON b.`cperiod_id` = d.`cperiod_id`
                                        WHERE UNIX_TIMESTAMP(NOW()) > d.`start_date` - 1209600 
                                        AND UNIX_TIMESTAMP(NOW()) < d.`finish_date`
                                        AND a.`course_id` = ".$db->qstr($course->getID())."
									    AND a.`active` = 1";
                            $results = $db->GetArray($query);
                            $gradebook_html = "";
                            if ($results) {
                                $gradebook_html = "<h1>Gradebook</h1>";

                                $query = "SELECT *
                                            FROM `groups`
                                            WHERE `group_type` = 'course_list'
                                            AND `group_value` = ".$db->qstr($course->getID())."
                                            AND `group_active` = '1'
                                            ORDER BY `group_name`";
                                $course_lists = $db->GetAll($query);

                                if ($course_lists) {
                                    $cohorts = $course_lists;
                                    if (count($course_lists) == 1) {
                                        $output_cohort = $course_lists[0];
                                        $selected_cohort = $output_cohort["group_id"];
                                    } else {
                                        $output_cohort = false;
                                        $classlist_found = false;
                                        foreach ($course_lists as $key => $course_list) {
                                            if (!$classlist_found) {
                                                $output_cohort = $course_list;
                                                if (isset($selected_classlist) && $selected_classlist && $selected_classlist == $course_list["group_id"]) {
                                                    $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_list"] = $selected_classlist;
                                                    $classlist_found = true;
                                                }
                                                if ($key == (count($course_lists) - 1) && !$classlist_found) {
                                                    $selected_classlist = $course_list["group_id"];
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $query =  " SELECT a.`course_id`, b.`group_name`, b.`group_id`
                                                FROM `assessments` AS a
                                                JOIN `groups` AS b
                                                ON a.`cohort` = b.`group_id`
                                                JOIN `group_organisations` AS c
                                                ON b.`group_id` = c.`group_id`
                                                WHERE a.`course_id` =". $db->qstr($course->getID())."
                                                AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                                AND a.`active` = '1'
                                                GROUP BY b.`group_id`
                                                ORDER BY b.`group_name`";
                                    $cohorts = $db->GetAll($query);

                                    $output_cohort = false;
                                    $cohort_found = false;
                                    if ($cohorts) {
                                        foreach ($cohorts as $key => $cohort) {
                                            if (!$cohort_found) {
                                                $output_cohort = $cohort;
                                                if (isset($selected_cohort) && $selected_cohort && $selected_cohort == $cohort["group_id"]) {
                                                    $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["cohort"] = $selected_cohort;
                                                    $cohort_found = true;
                                                }
                                                if ($key == (count($cohorts) - 1) && !$cohort_found) {
                                                    $selected_cohort = $cohort["group_id"];
                                                }
                                            }
                                        }
                                    }
                                }

                                $assessments = Models_Gradebook_Assessment::fetchAllRecords($output_cohort["group_id"], $course->getID());
                                $total_grade_weight = 0;

                                foreach ($assessments as $assessment) {
                                    $result = $assessment->toArray();

                                    $query = "SELECT a.`objective_type`, b.`objective_name`
                                                FROM `assessment_objectives` AS a
                                                JOIN `global_lu_objectives` AS b
                                                ON a.`objective_id` = b.`objective_id`
                                                WHERE a.`assessment_id` = ".$db->qstr($result["assessment_id"])."
                                                ORDER BY a.`objective_type`";
                                    $objectives = $db->GetArray($query);
                                    if ($objectives) {
                                        foreach ($objectives as $objective) {
                                            $flat_objectives[$objective["objective_type"]][] = $objective["objective_name"];
                                        }
                                    }

                                    $gradebook_html .= "<div><strong>".$result["name"]."</strong></div>";
                                    $gradebook_html .= "<div>Grade Weight: ".$result["grade_weighting"]. "%</div>";
                                    $gradebook_html .= "<div>Assessment Type: ".$result["title"]."</div>";

                                    if (!empty($flat_objectives["curricular_objective"])) {
                                        $gradebook_html .= "<div>Objectives: ";
                                        $gradebook_html .= implode(", ", $flat_objectives["curricular_objective"]);
                                        $gradebook_html .= "</div>";
                                    }

                                    if (!empty($flat_objectives["clinical_presentation"])) {
                                        $gradebook_html .= "<div>MCC Presentations: ";
                                        $gradebook_html .= implode(", ", $flat_objectives["clinical_presentation"]);
                                        $gradebook_html .= "</div>";
                                    }

                                    $gradebook_html .= "<br />";

                                    $total_grade_weight += $result["grade_weighting"];

                                    unset($flat_objectives);
                                    unset($objectives);
                                }

                                if (isset($total_grade_weight)) {
                                    if ($total_grade_weight < '100') {
                                        $gradebook_html .= "<div><strong>Total Grade Weight:</strong> <font color=\"#ff2431\">" . $total_grade_weight . "%</font></div>";
                                    } else {
                                        $gradebook_html .= "<div><strong>Total Grade Weight:</strong> " . $total_grade_weight . "%</div>";
                                    }
                                }
                            }

                            if (is_null($pages_html["gradebook"]) && in_array("%GRADEBOOK%", $search_terms)) {
                                $pages_html["gradebook"] = $gradebook_html;
                            }

                            // Event Types by Course Report End
                            if (is_null($pages_html["learning_event_types"]) && in_array("%LEARNING_EVENT_TYPES%", $search_terms)) {
                                $pages_html["learning_event_types"] = $eventtypes_html;
                            }

                            $pages_html["course_calendar"] = "";
                            if (!empty($calendar_html) && in_array("%COURSE_CALENDAR%", $search_terms)) {
                                if ($calendar_html) {
                                    $pages_html["course_calendar"] .= "<div class=\"page ".($level == 1 ? "break" : "")."\">";
                                    $pages_html["course_calendar"] .= "<h1>Course Calendar</h1>";
                                    $pages_html["course_calendar"] .= $calendar_html;
                                    $pages_html["course_calendar"] .= "</div>";
                                }
                            }

                            echo "pages_html is :" . count($pages_html)."\n";
                            $replacement_values = $pages_html;

                            if (file_exists(SYLLABUS_STORAGE."/syllabus-".$syllabus->getID().".html")) {
                                if (!is_dir(SYLLABUS_STORAGE."/archive/")) {
                                    mkdir(SYLLABUS_STORAGE."/archive/");
                                }
                                copy(SYLLABUS_STORAGE."/cover-".$syllabus->getID().".html", SYLLABUS_STORAGE."/archive/cover-".$syllabus->getID()."-".time().".html");
                                copy(SYLLABUS_STORAGE."/syllabus-".$syllabus->getID().".html", SYLLABUS_STORAGE."/archive/syllabus-".$syllabus->getID()."-".time().".html");
                            }

                            file_put_contents(SYLLABUS_STORAGE."/syllabus-".$syllabus->getID().".html", str_replace($search_terms, $replacement_values, $template));
                            $command = $APPLICATION_PATH["wkhtmltopdf"]." cover ".SYLLABUS_STORAGE."/cover-".$syllabus->getID().".html toc page ".SYLLABUS_STORAGE."/syllabus-".$syllabus->getID().".html --footer-left \"[section]\" --footer-right \"[page]\" ".SYLLABUS_STORAGE."/".clean_input($course->getCourseCode(), "alphanumeric")."-syllabus-".date("Y")."-".date("n").".pdf";
                            exec($command);

                            application_log("success", "Generated syllabus: ".clean_input($course->getCourseCode(), "alphanumeric"). " - " . $course->getCourseName() . " syllabus in ".(time() - $g_start)." seconds.");
                        }
					}

					if (file_exists(CACHE_DIRECTORY."/generate_syllabi.lck")) {
						if (unlink(CACHE_DIRECTORY."/generate_syllabi.lck")) {
							application_log("success", "Lock file deleted.");
						} else {
							application_log("error", "Unable to delete syllabus generation lock file: ".CACHE_DIRECTORY."/generate_syllabi.lck");
						}
					}

					echo "Syllabus generation for course: [".$result["course_code"]."] finished\n";
					echo "---------------------------------------------------------------------\n\n";
				}
			} else {
				application_log("notice", "No syllabi found, no syllabi generated.");
			}
		} else {
			application_log("error", "Could not write syllabus generation lock file, exiting.");
		}
	} else {
		application_log("error", "Syllabus generation lock file found, exiting.");
	}
} else {
    application_log("error", "Error with cache directory [".CACHE_DIRECTORY."], not found or not writable.");
}
