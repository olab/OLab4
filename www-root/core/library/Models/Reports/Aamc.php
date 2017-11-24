<?php
/**
 * AAMC Curriculum Inventory Reporting
 *
 * @author Organisation: Queen's University
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 * This file is not open source, and may not be distributed with the Entrada project.
 *
 */

//function tmp_display_utsw_name($string) {
//    if (strstr($string, " ")) {
//        return trim(substr($string, 0, strpos($string, " ")));
//    } else {
//        return trim($string);
//    }
//}

class Models_Reports_Aamc {

    /**
     * @var int
     */
    protected $org_id = 0;

    /**
     * @var string
     */
    protected $hostname = "";

    /**
     * @var array
     */
    protected $report = array();

    /**
     * @var array
     */
    protected $learners = array();

    /**
     * @var array
     */
    protected $events = array();

    /**
     * @var array
     */
    protected $courses = array();

    /**
     * @var array
     */
    protected $courses_excluded = array();

    /**
     * @var array
     */
    protected $academic_levels = array();

    /**
     * @var array
     */
    protected $curriculum_type_ids = array();

    /**
     * @var array
     */
    protected $event_ids = array();

    /**
     * @var array
     */
    protected $event_segment_ids = array();

    /**
     * @var array
     */
    protected $course_ids = array();

    /**
     * @var array
     */
    protected $event_objective_ids = array();

    /**
     * @var array
     */
    protected $event_assessments = array(); // Used by InstructionalMethods to record all Learning Events with eventtype_ids that match $this->assessment_method_eventtype_ids.

    /**
     * @var array
     */
    protected $course_objective_ids = array();

    /**
     * @var array
     */
    protected $assessment_method_eventtype_ids = array(
        13, // Examinations
    );

    protected $fallback_instructional_method = "IM030"; // Workshop

    /**
     * @var string
     */
    protected $fallback_assessment_method = "AM004"; // Exam - Institutionally Developed, Written/Computer-based

    /**
     * @var string
     */
    protected $fallback_assessment_purpose = "Formative"; // Formative or Summative

    /**
     * @var array
     */
    protected $assessment_events = array(); // Used by AssessmentMethod to record all Assessment mapped Learning Events.

    /**
     * Unfortunately we are going to need to have the hard coded objective set ID
     * (entrada.global_lu_objectives.objective_id) of the PCRS Objective Set. Sorry about that.
     * We'll fix this later.
     *
     * @var int
     */
    protected $pcrs_objective_set_id = 2328;

    /**
     * @var array
     */
    protected $rotations = array();

    public function __construct($org_id = 0) {
        global $ACTIVE_ORG;

        $org_id = (int) $org_id;

        if ($org_id) {
            $this->org_id = $org_id;
        } else if ($ACTIVE_ORG->getID()) {
            $this->org_id = $ACTIVE_ORG->getID();
        } else {
            throw new Exception("Unable to locate the organisation id.");
        }
    }

    public function setHostname($hostname = "") {
        $this->hostname = $hostname;
    }

    public function setReport($report_id = 0) {
        global $db;

        $report_id = (int) $report_id;

        if ($report_id) {
            $query = "SELECT * FROM `reports_aamc_ci` WHERE `raci_id` = ? AND `organisation_id` = ?";
            $result = $db->GetRow($query, array($report_id, $this->org_id));
            if ($result) {
                $this->report = $result;
            } else {
                throw new Exception("Unable to locate the provided raci_id [".$report_id."] in org_id [".$this->org_id."].");
            }
        }
    }

    public function setLearners($learners = array()) {
        $this->learners = $learners;
    }

    private function getEvents($proxy_id = 0) {
        global $db;

        $proxy_id = (int) $proxy_id;

        if (!isset($this->report) || empty($this->report)) {
            throw new Exception("The report information needs to be set prior to requesting events for the report.");
        }

        if ($proxy_id) {
            $u = User::fetchRowByID($proxy_id);

            if ($u) {
                $events = events_fetch_filtered_events(
                    $proxy_id,
                    $u->getGroup(),
                    $u->getRole(),
                    $this->org_id,
                    "course",
                    "ASC",
                    "custom",
                    $this->report["collection_start"],
                    $this->report["collection_finish"],
                    array("student" => array($proxy_id)),
                    false,
                    1,
                    15,
                    false,
                    false);
                if ($events && is_array($events["events"])) {
                    foreach ($events["events"] as $event) {
                        if ((int)$event["curriculum_type_id"]) {
                            if (!in_array($event["curriculum_type_id"], $this->curriculum_type_ids)) {
                                $this->curriculum_type_ids[] = $event["curriculum_type_id"];
                            }

                            if (!in_array($event["course_id"], $this->course_ids)) {
                                $this->course_ids[] = $event["course_id"];

                                if (!isset($this->courses[$event["course_id"]])) {
                                    $this->courses[$event["course_id"]] = array(
                                        "curriculum_type_id" => $event["curriculum_type_id"],
                                        "is_clerkship" => false,
                                        "rotation_ids" => array()
                                    );
                                }

                                $query = "SELECT *
                                            FROM `" . CLERKSHIP_DATABASE . "`.`global_lu_rotations`
                                            WHERE `course_id` = ?";
                                $rotations = $db->GetAll($query, array($event["course_id"]));
                                if ($rotations) {
                                    foreach ($rotations as $rotation) {
                                        $this->courses[$event["course_id"]]["is_clerkship"] = true;
                                        $this->courses[$event["course_id"]]["rotation_ids"][] = $rotation["rotation_id"];

                                        if (!array_key_exists($rotation["rotation_id"], $this->rotations)) {
                                            $this->rotations[$rotation["rotation_id"]] = $rotation;
                                        }
                                    }
                                }
                            }

                            if (!in_array($event["event_id"], $this->event_ids)) {
                                $this->event_ids[] = $event["event_id"];
                            }

                            $this->events[$event["curriculum_type_id"]][$event["course_id"]][$event["event_id"]] = $event;
                        } else {
                            $this->courses_excluded[] = $event["course_id"];
                        }
                    }

                    if (is_array($this->courses) && !empty($this->courses)) {
                        foreach ($this->courses as $course_id => $course) {
                            if (isset($course["is_clerkship"]) && (bool)$course["is_clerkship"] && isset($course["rotation_ids"]) && is_array($course["rotation_ids"]) && !empty($course["rotation_ids"])) {
                                // @todo This is being disabled for now as Clerkship clinical events may not need to be included in this report.
                                //                            $query = "SELECT a.*
                                //                                        FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                //                                        JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
                                //                                        ON b.`event_id` = a.`event_id`
                                //                                        AND b.`econtact_type` = 'student'
                                //                                        AND b.`etype_id` = ".$db->qstr($proxy_id)."
                                //                                        WHERE a.`rotation_id` IN (".implode(", ", $course["rotation_ids"]).")";
                                //                            $events = $db->GetAll($query);
                                //                            if ($events) {
                                //                                foreach ($events as $event) {
                                //                                    $this->events[$course["curriculum_type_id"]][$course_id]["C".$event["event_id"]] = array(
                                //                                        "respect_time_release" => 0,
                                //                                        "event_id" => "C".$event["event_id"],
                                //                                        "course_id" => $course_id,
                                //                                        "parent_id" => 0,
                                //                                        "event_title" => $this->rotations[$event["rotation_id"]]["rotation_title"],
                                //                                        "event_description" => "",
                                //                                        "event_duration" => (($event["event_finish"] - $event["event_start"]) / 60), // Minutes
                                //                                        "event_message" => "",
                                //                                        "event_location" => "",
                                //                                        "event_start" => $event["event_start"],
                                //                                        "event_finish" => $event["event_finish"],
                                //                                        "release_date" => $event["accessible_start"],
                                //                                        "release_until" => $event["accessible_finish"],
                                //                                        "updated_date" => $event["modified_last"],
                                //                                        "objectives_release_date" => 0,
                                //                                        "audience_type" => "proxy_id",
                                //                                        "organisation_id" => $this->org_id,
                                //                                        "course_code" => "",
                                //                                        "course_name" => "",
                                //                                        "permission" => "closed",
                                //                                        "curriculum_type_id" => $course["curriculum_type_id"],
                                //                                        "event_phase" => "",
                                //                                        "event_term" => "",
                                //                                        "fullname" => ""
                                //                                    );
                                //                                }
                                //                            }
                            }
                        }
                    }
                }
            } else {
                throw new Exception("A student id that was provided [" . $proxy_id . "] was not found in this system.");
            }
        }
    }

    public function fetchEvents() {
        global $db;

        if (!isset($this->learners) || !is_array($this->learners) || empty($this->learners)) {
            return false;
        }

        /**
         * Sets the order of the curriculum_type_id's in the $events array
         * so that the order appears as expected in the XML.
         */
        foreach (array_keys($this->learners) as $curriculum_type_id) {
            $this->events[$curriculum_type_id] = array();
        }

        foreach ($this->learners as $curriculum_type_id => $learners) {
            if (!is_array($learners)) {
                $learners = array($learners);
            }

            foreach ($learners as $learner) {
                $this->getEvents($learner);
            }
        }

        if ($this->events && is_array($this->events)) {
            /**
             * Remove any curriculum_type_id's within events that don't have
             * data in them.
             */
            foreach (array_keys($this->events) as $curriculum_type_id) {
                if (empty($this->events[$curriculum_type_id])) {
                    unset($this->events[$curriculum_type_id]);
                }
            }

            $output["Event"] = array();

            foreach ($this->events as $curriculum_type_id => $courses) {
                foreach ($courses as $course_id => $events) {
                    foreach ($events as $event_id => $event) {
                        $event_segments = array();

                        if (substr($event_id, 0, 1) == "C") {
                            $clerkship_event = true;
                        } else {
                            $clerkship_event = false;
                        }

                        /*
                         * Gather all of the InstructionalMethods used in this event.
                         */
                        if ($clerkship_event) {
                            $event_segments[] = array (
                                "eventtype_id" => 0,
                                "eventtype_title" => "Clinical Experience - Inpatient",
                                "duration" => 0,
                                "code" => "IM003", // Hard coding all clerkship events as Clinical Experience - Inpatient.
                            );
                        } else {
                            $query = "SELECT b.`eventtype_id`, b.`eventtype_title`, a.`duration`, d.`code`
                                        FROM `event_eventtypes` AS a
                                        JOIN `events_lu_eventtypes` AS b
                                        ON a.`eventtype_id` = b.`eventtype_id`
                                        LEFT JOIN `map_events_eventtypes` AS c
                                        ON c.`fk_eventtype_id` = b.`eventtype_id`
                                        LEFT JOIN `medbiq_instructional_methods` AS d
                                        ON d.`instructional_method_id` = c.`fk_instructional_method_id`
                                        WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
                                        ORDER BY a.`eeventtype_id`";
                            $eventtypes = $db->GetAll($query);
                            if ($eventtypes) {
                                foreach ($eventtypes as $eventtype) {
                                    if (!isset($eventtype["code"]) || !$eventtype["code"]) {
                                        $eventtype["code"] = $this->fallback_instructional_method;
                                    }

                                    $event_segments[] = $eventtype;
                                }
                            }
                        }

                        if ($event_segments) {
                            $total_segments = count($event_segments);

                            $entrada_event_id = $event_id;

                            $this->events[$curriculum_type_id][$course_id][$entrada_event_id]["cip_event_ids"] = array();

                            foreach ($event_segments as $segment => $event_segment) {
                                /*
                                 * Convert a single Entrada Learning Event with multiple event types, to multiple XML
                                 * events to more accurately represent the data in the Curriculum Inventory Report.
                                 */
                                if ($total_segments > 1) {
                                    /*
                                     * event_id 29473 with multiple event types is represented in the XML as:
                                     * E29473S1, E29473S2, E29473S3, etc.
                                     */
                                    $event_id = $entrada_event_id . "S" . ($segment + 1);
                                }

                                $this->events[$curriculum_type_id][$course_id][$entrada_event_id]["cip_event_ids"][] = $event_id;

                                if (isset($event_segment["duration"]) && (int) $event_segment["duration"]) {
                                    $duration = $event_segment["duration"];
                                } else {
                                    $duration = $event["event_duration"];
                                }

                                $is_assessment = false;

                                $description = clean_input($event["event_description"], array("striptags", "decode", "trim"));

                                $output["Event"][$event_id] = array();
                                $output["Event"][$event_id]["@attributes"]   = array("id" => "E" . $event_id);
                                $output["Event"][$event_id]["Title"]         = $event["event_title"];
                                $output["Event"][$event_id]["EventDuration"] = "PT" . $duration . "M";
                                $output["Event"][$event_id]["Description"]   = ($description ? substr($description, 0, 4000) : "No description provided.");

                                $output["Event"][$event_id]["Keyword"] = array();
                                $output["Event"][$event_id]["CompetencyObjectReference"] = array();
                                $output["Event"][$event_id]["InstructionalMethod"] = array();
                                $output["Event"][$event_id]["AssessmentMethod"] = array();

                                /*
                                 * Keywords: MeSH
                                 */
                                $query = "SELECT a.*, b.`descriptor_name`
                                    FROM `event_keywords` AS a
                                    JOIN `mesh_descriptors` AS b
                                    ON a.`keyword_id` = b.`descriptor_ui`
                                    WHERE a.`event_id` = ".$db->qstr($event["event_id"]);
                                $event_keywords = $db->GetAll($query);
                                if ($event_keywords) {
                                    if (!isset($output["Event"][$event_id]["Keyword"])) {
                                        $output["Event"][$event_id]["Keyword"] = array();
                                    }

                                    foreach ($event_keywords as $keyword) {
                                        $output["Event"][$event_id]["Keyword"][] = array(
                                            "@attributes" => array(
                                                "hx:source" => "MeSH",
                                                "hx:id"     => $keyword["keyword_id"]
                                            ),
                                            "hx:string" => $keyword["descriptor_name"]
                                        );
                                    }
                                }

                                /*
                                 * Keywords: Hot Topics (ED10)
                                 */
                                $query = "SELECT a.`topic_id` AS `keyword_id`, b.`topic_name` AS `descriptor_name`
                                    FROM `event_topics` AS a
                                    JOIN `events_lu_topics` AS b
                                    ON a.`topic_id` = b.`topic_id`
                                    WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
                                    GROUP BY a.`topic_id`";
                                $event_topics = $db->GetAll($query);
                                if ($event_topics) {
                                    if (!isset($output["Event"][$event_id]["Keyword"])) {
                                        $output["Event"][$event_id]["Keyword"] = array();
                                    }

                                    foreach ($event_topics as $key => $keyword) {
                                        $output["Event"][$event_id]["Keyword"][$key] = array(
                                            "@attributes" => array(
                                                "hx:source" => "Hot Topics (ED10)",
                                                "hx:id"     => $keyword["keyword_id"]
                                            ),
                                            "hx:string" => $keyword["descriptor_name"]
                                        );
                                    }
                                }

                                /*
                                 * CompetencyObjectReference
                                 */
                                $query = "SELECT b.`objective_id`, b.`objective_name`
                                    FROM `event_objectives` AS a
                                    JOIN `global_lu_objectives` AS b
                                    ON b.`objective_id` = a.`objective_id`
                                    WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
                                    ORDER BY b.`objective_name` ASC;";
                                $objectives = $db->GetAll($query);
                                if ($objectives) {
                                    $output["Event"][$event_id]["CompetencyObjectReference"] = array();

                                    foreach ($objectives as $objective) {
                                        if (!in_array($objective["objective_id"], $this->event_objective_ids)) {
                                            $this->event_objective_ids[] = $objective["objective_id"];
                                        }

                                        $output["Event"][$event_id]["CompetencyObjectReference"][] = "/CurriculumInventory/Expectations/CompetencyObject[lom:lom/lom:general/lom:identifier/lom:entry='http://".$this->hostname."/pcrs/objective/".$objective["objective_id"]."']";
                                    }
                                }

                                /*
                                 * InstructionalMethod
                                 */
                                $output["Event"][$event_id]["InstructionalMethod"][] = array(
                                    "@attributes" => array(
                                        "primary" => "true",
                                    ),
                                    "@value" => $event_segment["code"],
                                );

                                // If this is classified as an "Assessment" Learning Event Type, mark it as such.
                                if (isset($event_segment["eventtype_id"]) && $event_segment["eventtype_id"] && in_array($event_segment["eventtype_id"], $this->assessment_method_eventtype_ids)) {
                                    $this->event_assessments[] = $event_id;

                                    $is_assessment = true;
                                }

                                /*
                                 * AssessmentMethod
                                 */
                                $query = "SELECT *
                                    FROM `assessment_events` AS a
                                    JOIN `assessments` AS b
                                    ON b.`assessment_id` = a.`assessment_id`
                                    LEFT JOIN `map_assessments_meta` AS c
                                    ON c.`fk_assessments_meta_id` = b.`characteristic_id`
                                    LEFT JOIN `medbiq_assessment_methods` AS d
                                    ON d.`assessment_method_id` = c.`fk_assessment_method_id`
                                    WHERE a.`event_id` = ?";
                                $assessment = $db->GetRow($query, array($event["event_id"]));
                                if ($assessment) {
                                    if (isset($assessment["code"]) && $assessment["code"]) {
                                        $code = $assessment["code"];
                                    } else {
                                        $code = $this->fallback_assessment_method;
                                    }

                                    /*
                                     * Take the title of the assessment over the title of the learning event.
                                     */
                                    $output["Event"][$event_id]["Title"] = $assessment["name"];

                                    // <AssessmentMethod purpose="Summative">AM004</AssessmentMethod>
                                    $output["Event"][$event_id]["AssessmentMethod"][] = array(
                                        "@attributes" => array(
                                            "purpose" => $assessment["type"],
                                        ),
                                        "@value" => $code,
                                    );

                                    $is_assessment = true;
                                } elseif ($is_assessment) {
                                    /**
                                     * If a Learning Event has an event type that is internally classified as an
                                     * Assessment Method (in the assessment_method_eventtype_ids array), but has not been
                                     * linked to a Gradebook Assessment, then the fallback classification is used.
                                     */
                                    $output["Event"][$event_id]["AssessmentMethod"][] = array(
                                        "@attributes" => array(
                                            "purpose" => $this->fallback_assessment_purpose,
                                        ),
                                        "@value" => $this->fallback_assessment_method,
                                    );
                                }

                                if (empty($output["Event"][$event_id]["Keyword"])) {
                                    unset($output["Event"][$event_id]["Keyword"]);
                                }

                                if (empty($output["Event"][$event_id]["CompetencyObjectReference"])) {
                                    unset($output["Event"][$event_id]["CompetencyObjectReference"]);
                                }

                                if ($is_assessment || empty($output["Event"][$event_id]["InstructionalMethod"])) {
                                    unset($output["Event"][$event_id]["InstructionalMethod"]);
                                }

                                if (empty($output["Event"][$event_id]["AssessmentMethod"])) {
                                    unset($output["Event"][$event_id]["AssessmentMethod"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function fetchEventsOriginal() {
        global $db;

        if (!isset($this->learners) || !is_array($this->learners) || empty($this->learners)) {
            return false;
        }

        foreach ($this->learners as $curriculum_type_id => $learners) {
            if (!is_array($learners)) {
                $learners = array($learners);
            }

            foreach ($learners as $learner) {
                $this->getEvents($learner);
            }
        }

        if ($this->events && is_array($this->events)) {
            $output["Event"] = array();

            foreach ($this->events as $curriculum_type_id => $courses) {
                foreach ($courses as $course_id => $events) {
                    foreach ($events as $event_id => $event) {
                        if (substr($event_id, 0, 1) == "C") {
                            $clerkship_event = true;
                        } else {
                            $clerkship_event = false;
                        }

                        $is_assessment = false;

                        $description = clean_input($event["event_description"], array("striptags", "decode", "trim"));

                        $output["Event"][$event_id] = array();
                        $output["Event"][$event_id]["@attributes"]   = array("id" => "E".$event_id);
                        $output["Event"][$event_id]["Title"]         = $event["event_title"];
                        $output["Event"][$event_id]["EventDuration"] = "PT".$event["event_duration"]."M";
                        $output["Event"][$event_id]["Description"]   = ($description ? substr($description, 0, 4000) : "No description provided.");

                        $output["Event"][$event_id]["Keyword"] = array();
                        $output["Event"][$event_id]["CompetencyObjectReference"] = array();
                        $output["Event"][$event_id]["InstructionalMethod"] = array();
                        $output["Event"][$event_id]["AssessmentMethod"] = array();

                        /*
                         * Keywords: MeSH
                         */
                        $query = "SELECT a.*, b.`descriptor_name`
                                    FROM `event_keywords` AS a
                                    JOIN `mesh_descriptors` AS b
                                    ON a.`keyword_id` = b.`descriptor_ui`
                                    WHERE a.`event_id` = ".$db->qstr($event["event_id"]);
                        $event_keywords = $db->GetAll($query);
                        if ($event_keywords) {
                            if (!isset($output["Event"][$event_id]["Keyword"])) {
                                $output["Event"][$event_id]["Keyword"] = array();
                            }

                            foreach ($event_keywords as $keyword) {
                                $output["Event"][$event_id]["Keyword"][] = array(
                                    "@attributes" => array(
                                        "hx:source" => "MeSH",
                                        "hx:id"     => $keyword["keyword_id"]
                                    ),
                                    "hx:string" => $keyword["descriptor_name"]
                                );
                            }
                        }

                        /*
                         * Keywords: Hot Topics (ED10)
                         */
                        $query = "SELECT a.`topic_id` AS `keyword_id`, b.`topic_name` AS `descriptor_name`
                                    FROM `event_topics` AS a
                                    JOIN `events_lu_topics` AS b
                                    ON a.`topic_id` = b.`topic_id`
                                    WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
                                    GROUP BY a.`topic_id`";
                        $event_topics = $db->GetAll($query);
                        if ($event_topics) {
                            if (!isset($output["Event"][$event_id]["Keyword"])) {
                                $output["Event"][$event_id]["Keyword"] = array();
                            }

                            foreach ($event_topics as $key => $keyword) {
                                $output["Event"][$event_id]["Keyword"][$key] = array(
                                    "@attributes" => array(
                                        "hx:source" => "Hot Topics (ED10)",
                                        "hx:id"     => $keyword["keyword_id"]
                                    ),
                                    "hx:string" => $keyword["descriptor_name"]
                                );
                            }
                        }

                        /*
                         * CompetencyObjectReference
                         */
                        $query = "SELECT b.`objective_id`, b.`objective_name`
                                    FROM `event_objectives` AS a
                                    JOIN `global_lu_objectives` AS b
                                    ON b.`objective_id` = a.`objective_id`
                                    WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
                                    ORDER BY b.`objective_name` ASC;";
                        $objectives = $db->GetAll($query);
                        if ($objectives) {
                            $output["Event"][$event_id]["CompetencyObjectReference"] = array();

                            foreach ($objectives as $objective) {
                                if (!in_array($objective["objective_id"], $this->event_objective_ids)) {
                                    $this->event_objective_ids[] = $objective["objective_id"];
                                }

                                $output["Event"][$event_id]["CompetencyObjectReference"][] = "/CurriculumInventory/Expectations/CompetencyObject[lom:lom/lom:general/lom:identifier/lom:entry='http://".$this->hostname."/pcrs/objective/".$objective["objective_id"]."']";
                            }
                        }

                        /*
                         * InstructionalMethod
                         */
                        if ($clerkship_event) {
                            $output["Event"][$event_id]["InstructionalMethod"][] = array(
                                "@attributes" => array(
                                    "primary" => "true"
                                ),
                                "@value" => "IM003", // Hard coding all clerkship events as Clinical Experience - Inpatient.
                            );
                        } else {
                            $query = "SELECT b.`eventtype_id`, b.`eventtype_title`
                                        FROM `event_eventtypes` AS a
                                        JOIN `events_lu_eventtypes` AS b
                                        ON a.`eventtype_id` = b.`eventtype_id`
                                        WHERE a.`event_id` = ".$db->qstr($event["event_id"])."
                                        ORDER BY a.`eeventtype_id`";
                            $eventtypes = $db->GetAssoc($query);
                            if ($eventtypes) {
                                $output["Event"][$event_id]["InstructionalMethod"] = array();

                                $j = 0;

                                foreach ($eventtypes as $eventtype_id => $eventtype_title) {

                                    $query = "SELECT a.code
                                                FROM medbiq_instructional_methods AS a
                                                JOIN map_events_eventtypes AS b
                                                ON b.fk_instructional_method_id = a.instructional_method_id
                                                AND b.fk_eventtype_id = ?";
                                    $code = $db->GetOne($query, array($eventtype_id));
                                    if (!$code) {
                                        $code = $this->fallback_instructional_method;
                                    }

                                    $output["Event"][$event_id]["InstructionalMethod"][] = array(
                                        "@attributes" => array(
                                            "primary" => ($j == 0 ? "true" : "false")
                                        ),
                                        "@value" => $code,
                                    );
                                    $j++;

                                    // If this is an assessment, break out of here.
                                    if (in_array($eventtype_id, $this->assessment_method_eventtype_ids)) {
                                        $this->event_assessments[] = $event_id;

                                        $is_assessment = true;
                                        break;
                                    }
                                }
                            }
                        }

                        /*
                         * AssessmentMethod
                         */
                        $query = "SELECT *
                                    FROM `assessment_events` AS a
                                    JOIN `assessments` AS b
                                    ON b.`assessment_id` = a.`assessment_id`
                                    LEFT JOIN `map_assessments_meta` AS c
                                    ON c.`fk_assessments_meta_id` = b.`characteristic_id`
                                    LEFT JOIN `medbiq_assessment_methods` AS d
                                    ON d.`assessment_method_id` = c.`fk_assessment_method_id`
                                    WHERE a.`event_id` = ?";
                        $assessment = $db->GetRow($query, array($event_id));
                        if ($assessment) {
                            if (isset($assessment["code"]) && $assessment["code"]) {
                                $code = $assessment["code"];
                            } else {
                                $code = $this->fallback_assessment_method;
                            }

                            /*
                             * Take the title of the assessment over the title of the learning event.
                             */
                            $output["Event"][$event_id]["Title"] = $assessment["name"];

                            // <AssessmentMethod purpose="Summative">AM004</AssessmentMethod>
                            $output["Event"][$event_id]["AssessmentMethod"][] = array(
                                "@attributes" => array(
                                    "purpose" => $assessment["type"],
                                ),
                                "@value" => $code,
                            );

                            $is_assessment = true;
                        } elseif ($is_assessment) {
                            /**
                             * If a Learning Event has an event type that is internally classified as an
                             * Assessment Method (in the assessment_method_eventtype_ids array), but has not been
                             * linked to a Gradebook Assessment, then the fallback classification is used.
                             */
                            $output["Event"][$event_id]["AssessmentMethod"][] = array(
                                "@attributes" => array(
                                    "purpose" => $this->fallback_assessment_purpose,
                                ),
                                "@value" => $this->fallback_assessment_method,
                            );
                        }

                        if (empty($output["Event"][$event_id]["Keyword"])) {
                            unset($output["Event"][$event_id]["Keyword"]);
                        }

                        if (empty($output["Event"][$event_id]["CompetencyObjectReference"])) {
                            unset($output["Event"][$event_id]["CompetencyObjectReference"]);
                        }

                        if ($is_assessment || empty($output["Event"][$event_id]["InstructionalMethod"])) {
                            unset($output["Event"][$event_id]["InstructionalMethod"]);
                        }

                        if (empty($output["Event"][$event_id]["AssessmentMethod"])) {
                            unset($output["Event"][$event_id]["AssessmentMethod"]);
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function fetchExpectations() {
        global $db;

        $output = array();

        $pcrs_objectives = array();
        $objective_sets = array();

        /**
         * Fetch all of the PCRS objectives from the hard coded pcrs_objective_set_id, which is set
         * at the top of this file. Sorry about that.
         */
        $objectives = array();
        Models_Objective::fetchObjectives($this->pcrs_objective_set_id, $objectives, false);
        if ($objectives) {
            foreach ($objectives as $result) {
                $pcrs_objectives[$result["objective_id"]] = array(
                    "objective" => $result,
                    "mapped_to" => array()
                );
            }
            unset($objectives);
        }

        /**
         * Fetch all of the linked course objectives.
         */
        if ($this->course_ids) {
            foreach ($this->course_ids as $course_id) {
                $query = "SELECT b.`objective_id`, b.`objective_name`
                            FROM `course_objectives` AS a
                            JOIN `global_lu_objectives` AS b
                            ON b.`objective_id` = a.`objective_id`
                            WHERE a.`course_id` = ".$db->qstr($course_id)."
                            ORDER BY b.`objective_name` ASC;";
                $objectives = $db->GetAll($query);
                if ($objectives) {
                    foreach ($objectives as $objective) {
                        if (!in_array($objective["objective_id"], $this->course_objective_ids)) {
                            $this->course_objective_ids[] = $objective["objective_id"];
                        }

                        /**
                         * Record this objective for use in fetchSequence().
                         */
                        $this->courses[$course_id]["objectives"][] = $objective["objective_id"];
                    }
                }
            }
        }

        /**
         * Fetch all of the Objective Sets that are presently active and that are open to all courses.
         */
        $query = "SELECT a.*
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `objective_audience` AS c
                    ON a.`objective_id` = c.`objective_id`
                    WHERE a.`objective_parent` = '0'
                    AND a.`objective_active` = '1'
                    AND b.`organisation_id` = ?
                    AND c.`audience_value` = 'all'
                    AND a.`objective_id` <> ?
                    ORDER BY a.`objective_order` ASC";
        $results = $db->GetAll($query, array($this->org_id, $this->pcrs_objective_set_id));
        if ($results) {
            foreach ($results as $result) {
                $objective_set_id = $result["objective_id"];

                /**
                 * If this report has Program Level Objectives defined and this Objective Set is it, then it will need to be recorded.
                 */
                if (isset($this->report["program_level_objective_id"]) && ($this->report["program_level_objective_id"] == $result["objective_id"])) {
                    $program_mapped = true;
                } else {
                    $program_mapped = false;
                }

                /**
                 * Fetch all of the Objectives within this Objective Set.
                 */
                $objectives = array();
                Models_Objective::fetchObjectives($result["objective_id"], $objectives, false);
                if ($objectives) {
                    /**
                     * Record the new Objective Set.
                     */
                    $objective_sets[$objective_set_id] = array(
                        "set" => $result,
                        "objectives" => array(),
                        "mapped_pcrs" => array()
                    );

                    foreach ($objectives as $objective) {
                        $objective["mapped_to"] = array();
                        $objective["mapped_from"] = array();
                        
                        $course_mapped = false;
                        $event_mapped  = false;

                        if (in_array($objective["objective_id"], $this->course_objective_ids)) {
                            $course_mapped = true;
                        }

                        if (in_array($objective["objective_id"], $this->event_objective_ids)) {
                            $event_mapped = true;
                        }

                        /**
                         * If this Objective is mapped at either the program, course, or event level, include it in the
                         * list. Otherwise AAMC doesn't want to know about it.
                         */
                        if ($program_mapped || $course_mapped || $event_mapped) {
                            $pcrs_positive = false;

                            /**
                             * Check to see if any other objectives in the system map to this objective_id. If they do,
                             * then this objective_id is considered http://www.w3.org/2004/02/skos/core#narrower than
                             * each of the found results.
                             */
                            $mapped_from = Models_Objective::fetchObjectivesMappedFrom($objective["objective_id"]);
                            if ($mapped_from) {
                                foreach ($mapped_from as $mapped_objective) {
                                    if (array_key_exists($mapped_objective["objective_id"], $pcrs_objectives)) {
                                        $pcrs_positive = true;

                                        $pcrs_objectives[$mapped_objective["objective_id"]]["mapped_to"][] = $objective["objective_id"];
                                        $objective_sets[$objective_set_id]["mapped_pcrs"][] = $mapped_objective["objective_id"];

                                    } else {
                                        $objective["mapped_from"][] = $mapped_objective["objective_id"];
                                    }
                                }
                            }

                            /**
                             * Check to see if this objective_id mapps to any other objective_ids.
                             */
                            $mapped_to = Models_Objective::fetchObjectivesMappedTo($objective["objective_id"]);
                            if ($mapped_to) {
                                foreach ($mapped_to as $mapped_objective) {
                                    $objective["mapped_to"][] = $mapped_objective["objective_id"];
                                }
                            }

                            if ($pcrs_positive || $program_mapped) {
                                $objective["aamc_category_term"] = "program-level-competency";
                            } else if ($course_mapped && !$event_mapped) {
                                $objective["aamc_category_term"] = "sequence-block-level-competency";
                            } else if (!$course_mapped && $event_mapped) {
                                $objective["aamc_category_term"] = "event-level-competency";
                            } else if ($course_mapped && $event_mapped) {
                                $objective["aamc_category_term"] = "sequence-block-level-competency";
                            } else {
                                $objective["aamc_category_term"] = "sequence-block-level-competency";
                            }

                            $objective_sets[$objective_set_id]["objectives"][$objective["objective_id"]] = $objective;
                        }
                    }

                    /**
                     * If there are no mapped objectives in this set, then remove the set altogether.
                     */
                    if (empty($objective_sets[$objective_set_id]["objectives"]) && empty($objective_sets[$objective_set_id]["mapped_pcrs"])) {
                        unset($objective_sets[$objective_set_id]);
                    }
                }
            }
        }

        if ($objective_sets) {
            $output["CompetencyObject"] = array();
            $output["CompetencyFramework"] = array();

            foreach ($objective_sets as $objective_set_id => $objective_set) {
                foreach ($objective_set["objectives"] as $objective_id => $objective) {
                    //if (!empty($objective["mapped_to"]) || !empty($objective["mapped_from"]) || ($objective["aamc_category_term"] == "event-level-competency")) {
                        $output["CompetencyObject"][] = array(
                            "lom:lom" => array(
                                "lom:general" => array(
                                    "lom:identifier" => array(
                                        "lom:catalog" => "URI",
                                        "lom:entry" => "http://" . $this->hostname . "/pcrs/objective/" . $objective["objective_id"],
//                                      "lom:testing" => "http://www.utsouthwestern.net/intranet/education/medical-school/med-ed/objectives#" . tmp_display_utsw_name($objective["objective_name"]),
                                    ),
                                    "lom:title" => array(
                                        "lom:string" => !empty($objective["objective_description"]) ? $objective["objective_description"] : $objective["objective_name"],
                                    ),
                                ),
                            ),
                            "co:Category" => array(
                                "@attributes" => array(
                                    "term" => $objective["aamc_category_term"],
                                ),
                            ),
                        );
                    //}
                }
            }

            $output["CompetencyFramework"]["lom:lom"] = array(
                "lom:general" => array(
                    "lom:identifier" => array(
                        "lom:catalog" => "URI",
                        "lom:entry" => "http://".$this->hostname."/framework",
                    ),
                    "lom:title" => array(
                        "lom:string" => "Competency Framework for ".$this->report["report_title"]
                    ),
                ),
            );

            $output["CompetencyFramework"]["cf:Includes"] = array();

            if ($pcrs_objectives) {
                foreach ($pcrs_objectives as $pcrs_objective_id => $pcrs_info) {
                    if (!empty($pcrs_info["mapped_to"]) && $pcrs_info["objective"]) {
                        $output["CompetencyFramework"]["cf:Includes"][] = array(
                            "cf:Catalog" => "URI",
                            "cf:Entry" => "https://services.aamc.org/30/ci-school-web/pcrs/PCRS.html#".$pcrs_info["objective"]["objective_code"],
                        );
                    }
                }
            }

            foreach ($objective_sets as $objective_set_id => $objective_set) {
                foreach ($objective_set["objectives"] as $objective_id => $objective) {
                    $output["CompetencyFramework"]["cf:Includes"][] = array(
                        "cf:Catalog" => "URI",
                        "cf:Entry" => "http://".$this->hostname."/pcrs/objective/".$objective_id,
//                      "cf:Testing" => "http://www.utsouthwestern.net/intranet/education/medical-school/med-ed/objectives#" . tmp_display_utsw_name($objective["objective_name"]),
                    );
                }
            }

            $output["CompetencyFramework"]["cf:Relation"] = array();

            if ($pcrs_objectives) {
                foreach ($pcrs_objectives as $pcrs_objective_id => $pcrs_objective) {
                    if (!empty($pcrs_objective["mapped_to"]) && $pcrs_objective["objective"]) {
                        foreach ($pcrs_objective["mapped_to"] as $objective_id) {
                            // @todo REMOVE THIS.
                            $objective_testing = Models_Objective::fetchRow($objective_id);

                            $output["CompetencyFramework"]["cf:Relation"][] = array(
                                "cf:Reference1" => array(
                                    "cf:Catalog" => "URI",
                                    "cf:Entry" => "http://" . $this->hostname . "/pcrs/objective/" . $objective_id,
//                                  "cf:Testing" => "http://www.utsouthwestern.net/intranet/education/medical-school/med-ed/objectives#" . tmp_display_utsw_name($objective_testing->getName()),
                                ),
                                "cf:Relationship" => "http://www.w3.org/2004/02/skos/core#related",
                                "cf:Reference2" => array(
                                    "cf:Catalog" => "URI",
                                    "cf:Entry" => "https://services.aamc.org/30/ci-school-web/pcrs/PCRS.html#" . $pcrs_objective["objective"]["objective_code"],
                                ),
                            );
                        }
                    }
                }
            }

            /**
             * Iterates through each objective again and expresses where the objective is mapped *from*.
             */
            foreach ($objective_sets as $objective_set_id => $objective_set) {
                foreach ($objective_set["objectives"] as $objective_id => $objective) {
                    if ($objective["mapped_from"]) {
                        foreach ($objective["mapped_from"] as $mapped_objective_id) {
                            $objective_parent = Models_Objective::fetchRow($mapped_objective_id);
                            if ($objective_parent) {
                                $output["CompetencyFramework"]["cf:Relation"][] = array(
                                    "cf:Reference1" => array(
                                        "cf:Catalog" => "URI",
                                        "cf:Entry" => "http://" . $this->hostname . "/pcrs/objective/" . $objective_parent->getID(),
//                                      "cf:Testing" => "http://www.utsouthwestern.net/intranet/education/medical-school/med-ed/objectives#" . tmp_display_utsw_name($objective_parent->getName()),
                                    ),
                                    "cf:Relationship" => "http://www.w3.org/2004/02/skos/core#narrower",
                                    "cf:Reference2" => array(
                                        "cf:Catalog" => "URI",
                                        "cf:Entry" => "http://" . $this->hostname . "/pcrs/objective/" . $objective_id,
//                                      "cf:Testing" => "http://www.utsouthwestern.net/intranet/education/medical-school/med-ed/objectives#" . tmp_display_utsw_name($objective["objective_name"]),
                                    ),
                                );
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function getCurriculumTypes() {
        global $db;

        $query = "SELECT a.`curriculum_type_id`, a.`curriculum_type_name`, a.`curriculum_type_active`
                    FROM `curriculum_lu_types` AS a
                    JOIN `curriculum_type_organisation` AS b
                    ON b.`curriculum_type_id` = a.`curriculum_type_id`
                    AND b.`organisation_id` = ".$db->qstr($this->org_id)."
                    ORDER BY a.`curriculum_type_order` ASC";
        $output = $db->GetAll($query);

        return $output;
    }

    private function getAcademicLevels() {
        global $db;

        if (!isset($this->academic_levels) || !is_array($this->academic_levels) || empty($this->academic_levels)) {
            $query = "SELECT a.`curriculum_type_id`, a.`curriculum_type_name`, a.`curriculum_type_description`
                        FROM `curriculum_lu_types` AS a
                        JOIN `curriculum_type_organisation` AS b
                        ON b.`curriculum_type_id` = a.`curriculum_type_id`
                        AND b.`organisation_id` = ".$db->qstr($this->org_id)."
                        WHERE a.`curriculum_type_id` IN (".implode(", ", $this->curriculum_type_ids).")
                        ORDER BY a.`curriculum_type_order` ASC";
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $order => $result) {
                    $this->academic_levels[($order + 1)] = $result;
                }
            }
        }

        return $this->academic_levels;
    }

    public function fetchAcademicLevels() {
        $output = array();

        $academic_levels = $this->getAcademicLevels();
        if ($academic_levels) {
            $output["LevelsInProgram"] = count($academic_levels);
            $output["Level"] = array();

            foreach ($academic_levels as $number => $level) {
                $output["Level"][] = array(
                    "@attributes" => array(
                        "number" => $number
                    ),
                    "Label" => trim(substr($level["curriculum_type_name"], 0, 10)),
                    "Description" => ($level["curriculum_type_description"] ? clean_input($level["curriculum_type_description"], array("striptags", "decode", "trim")) : "Not Available")
                );
            }
        }

        return $output;
    }

    public function fetchSequence() {
        global $db;

        $output = array();

        $academic_levels = $this->getAcademicLevels();
        if ($academic_levels) {

            $output["SequenceBlock"] = array();

            foreach ($academic_levels as $number => $level) {
                $query = "SELECT a.*, b.`rotation_id`
                            FROM `courses` AS a
                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS b
                            ON b.`course_id` = a.`course_id`
                            WHERE a.`organisation_id` = ?
                            AND a.`curriculum_type_id` = ?
                            AND a.`course_id` IN (".implode(", ", $this->course_ids).")";
                $courses = $db->GetAll($query, array($this->org_id, $level["curriculum_type_id"]));
                if ($courses) {
                    foreach ($courses as $course) {
                        $course_id = $course["course_id"];

                        $description = clean_input($course["course_description"], array("striptags", "decode", "trim"));

                        $course_duration = 0;
                        $course_start_date = 0;
                        $course_end_date = 0;

                        if (isset($course["rotation_id"]) && (int) $course["rotation_id"]) {
                            $is_clerkship = true;
                        } else {
                            $is_clerkship = false;
                        }

                        /**
                         * Loop through the events initially to get the course start and end dates, then calculate
                         * the duration of the course at the end.
                         */
                        if (is_array($this->events[$course["curriculum_type_id"]][$course_id])) {
                            foreach ($this->events[$course["curriculum_type_id"]][$course_id] as $event) {
                                /**
                                 * Set this event start date as the course start date, if it's the earliest
                                 * date in the series of events.
                                 */
                                if (!$course_start_date || $course_start_date > $event["event_start"]) {
                                    $course_start_date = $event["event_start"];
                                }

                                /**
                                 * Set this event end date as the course end date, if it's the latest
                                 * date in the series of events.
                                 */
                                if (!$course_end_date || $course_end_date < $event["event_finish"]) {
                                    $course_end_date = $event["event_finish"];
                                }
                            }

                            /**
                             * Calculate the course duration in days.
                             */
                            $course_duration = ceil(($course_end_date - $course_start_date) / 60 / 60 / 24);
                        }

                        $output["SequenceBlock"][$course_id] = array(
                            "@attributes" => array(
                                "id" => $course_id,
                                "required" => "Required",
                                "order" => "Ordered",
                                "track" => "false"
                            ),
                            "Title" => $course["course_code"] . ": " . $course["course_name"],
                            "Description" => ($description ? $description : "No description provided."),
                            "Timing" => array(
                                "Duration" => "P".$course_duration."D",
                                "Dates" => array(
                                    "StartDate" => date("Y-m-d", $course_start_date),
                                    "EndDate" => date("Y-m-d", $course_end_date)
                                ),
                            ),
                            "Level" => "/CurriculumInventory/AcademicLevels/Level[@number='".$number."']",
                        );

                        /*
                         * ClerkshipModel
                         */
                        if ($is_clerkship) {
                            $output["SequenceBlock"][$course_id]["ClerkshipModel"] = "rotation";
                        }

                        /*
                         * CompetencyObjectReference
                         */
                        if (is_array($this->courses[$course_id]["objectives"])) {
                            $output["SequenceBlock"][$course_id]["CompetencyObjectReference"] = array();

                            foreach ($this->courses[$course_id]["objectives"] as $objective_id) {
                                $output["SequenceBlock"][$course_id]["CompetencyObjectReference"][] = "/CurriculumInventory/Expectations/CompetencyObject[lom:lom/lom:general/lom:identifier/lom:entry='http://".$this->hostname."/pcrs/objective/".$objective_id."']";
                            }
                        }

                        /*
                         * SequenceBlockEvent
                         */
                        if (is_array($this->events[$course["curriculum_type_id"]][$course_id])) {
                            $output["SequenceBlock"][$course_id]["SequenceBlockEvent"] = array();

                            foreach ($this->events[$course["curriculum_type_id"]][$course_id] as $event) {
                                if (isset($event["cip_event_ids"]) && $event["cip_event_ids"]) {
                                    foreach ($event["cip_event_ids"] as $event_id) {
                                        $output["SequenceBlock"][$course_id]["SequenceBlockEvent"][] = array(
                                            "@attributes" => array(
                                                "required" => ((!isset($event["attendance_required"]) || $event["attendance_required"] == 1) ? "true" : "false")
                                            ),
                                            "EventReference" => "/CurriculumInventory/Events/Event[@id='E".$event_id."']",
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }

    public function missingEventTypeMapping() {
        global $db;

        $query = "SELECT *
                    FROM `events_lu_eventtypes` AS a
                    JOIN `eventtype_organisation` AS b
                    ON b.`eventtype_id` = a.`eventtype_id`
                    LEFT JOIN `map_events_eventtypes` AS c
                    ON c.`fk_eventtype_id` = a.`eventtype_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`eventtype_active` = 1
                    AND c.`map_events_eventtypes_id` IS NULL
                    ORDER BY `eventtype_order` ASC";

        $results = $db->GetAll($query, array($this->org_id));
        if ($results) {
            return $results;
        }

        return false;
    }


    public function missingAssessmentMethodMapping() {
        global $db;

        $query = "SELECT *
                    FROM `assessments_lu_meta` AS a
                    LEFT JOIN `map_assessments_meta` AS b
                    ON b.`fk_assessments_meta_id` = a.`id`
                    LEFT JOIN `medbiq_assessment_methods` AS c
                    ON c.`assessment_method_id` = b.`fk_assessment_method_id`
                    WHERE a.`organisation_id` = ?
                    AND a.`active` = 1
                    AND b.`fk_assessments_meta_id` IS NULL";

        $results = $db->GetAll($query, array($this->org_id));
        if ($results) {
            return $results;
        }

        return false;
    }
}