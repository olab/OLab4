<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves a particular calendar in either JSON or ICS depending on the extension of the $_GET["request"];
 * http://www.yourschool.ca/calendars/username.json
 * http://www.yourschool.ca/calendars/username.ics
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
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

$request = explode("/", ((isset( $_GET["request"])) ? clean_input($_GET["request"], array("url", "lowercase", "nows")) : ""));
$selected_course = clean_input((isset($_GET["course"]) ? $_GET["course"] : 0), "int");
$user_proxy_id = 0;
$user_username = "";
$user_firstname = "";
$user_lastname = "";
$user_email = "";
$user_role = "";
$user_group = "";
$user_organisation_id = 0;

$calendar_type = "json";
$user_private_hash = "";

/**
 * Check if the request has multiple parts to it indicating the URL contains a private_hash,
 * which allows them to by-pass the authentication for this calendar, thus allowing them to
 * load a calendar into Google Calendar.
 *
 * http://demo.entrada-project.org/calendars/private-jd7ghr5ga5f7cc5bd4357ab6d707faaa/username.ics
 *
 */
if (is_array($request) && (count($request) == 2) && isset($request[0]) && (substr($request[0], 0, 8) == "private-") && ($tmp_input = str_ireplace("private-", "", $request[0]))) {
    $user_private_hash = $tmp_input;
    $request_filename = (isset($request[1]) ? $request[1] : "");
} else {
    $request_filename = (isset($request[0]) ? $request[0] : "");
}

/**
 * Determine the type of calendar the user is requesting.
 */
if (substr($request_filename, -4) == ".ics") {
    $calendar_type = "ics";
}

/**
 * Check if the user is already authenticated.
 */
if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
    $user_proxy_id = $ENTRADA_USER->getID();
    $user_username = $ENTRADA_USER->getUsername();
    $user_firstname = $ENTRADA_USER->getFirstname();
    $user_lastname = $ENTRADA_USER->getLastname();
    $user_email = $ENTRADA_USER->getEmail();
    $user_role = $ENTRADA_USER->getActiveRole();
    $user_group = $ENTRADA_USER->getActiveGroup();
    $user_organisation_id = $ENTRADA_USER->getActiveOrganisation();
} else {
    /**
     * If the are not already authenticated, check to see if they have provided
     * a private hash in the URL.
     */
    if ($user_private_hash) {
        /**
         * @todo Add a setUserHashAuthentication() method to the authentication client and server so we can use the
         * web-service instead of querying the data directly to authenticate a private-hash.
         */
        $query = "SELECT a.`id`, a.`username`, a.`firstname`, a.`lastname`, a.`email`, a.`grad_year`, b.`role`, b.`group`, b.`organisation_id`, b.`access_expires`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON b.`user_id` = a.`id`
                    WHERE b.`private_hash` = ".$db->qstr($user_private_hash)."
                    AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts`='0' OR b.`access_starts` <= ".$db->qstr(time()).")
                    AND (b.`access_expires`='0' OR b.`access_expires` >= ".$db->qstr(time()).")
                    GROUP BY a.`id`";
        $result = $db->GetRow($query);
        if ($result) {
            /**
             * If $ENTRADA_USER was previously initialized in init.inc.php before the session was authorized it is
             * set to false and needs to be re-initialized.
             */
            if ($ENTRADA_USER == false) {
                $ENTRADA_USER = User::get($result["id"]);
            }
            $_SESSION["details"]["id"] = $user_proxy_id = $result["id"];
            $_SESSION["details"]["access_id"] = $ENTRADA_USER->getAccessId();
            $_SESSION["details"]["username"] = $user_username = $result["username"];
            $_SESSION["details"]["firstname"] = $user_firstname = $result["firstname"];
            $_SESSION["details"]["lastname"] = $user_lastname = $result["lastname"];
            $_SESSION["details"]["email"] = $user_email = $result["email"];
            $_SESSION["details"]["role"] = $user_role = $result["role"];
            $_SESSION["details"]["group"] = $user_group = $result["group"];
            $_SESSION["details"]["organisation_id"] = $user_organisation_id = $result["organisation_id"];
            $_SESSION["details"]["app_id"] = AUTH_APP_ID;
            $_SESSION["details"]["grad_year"] = $result["grad_year"];
            $_SESSION["details"]["expires"] = $result["access_expires"];
        } else {
            /**
             * If the query above fails, redirect them back here but without the
             * private hash which will trigger the HTTP Authentication.
             */
            header("Location: ".ENTRADA_URL."/calendars/".$request_filename);
            exit;
        }
    } else {
        /**
         * If they are not already authenticated, and they don't have a private
         * hash in the URL, then send them through to HTTP authentication.
         */
        if (!isset($_SERVER["PHP_AUTH_USER"])) {
            http_authenticate();
        } else {
            require_once("Entrada/authentication/authentication.class.php");

            $username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
            $password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

            $auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
            $auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
            $auth->setEncryption(AUTH_ENCRYPTION_METHOD);
            $auth->setUserAuthentication($username, $password, AUTH_METHOD);
            $result = $auth->Authenticate(array("id", "username", "firstname", "lastname", "email", "role", "group", "organisation_id"));

            $ERROR = 0;
            if ($result["STATUS"] == "success") {
                $user_proxy_id = $result["ID"];
                $user_username = $result["USERNAME"];
                $user_firstname = $result["FIRSTNAME"];
                $user_lastname = $result["LASTNAME"];
                $user_email = $result["EMAIL"];
                $user_role = $result["ROLE"];
                $user_group = $result["GROUP"];
                $user_organisation_id = $result["ORGANISATION_ID"];

            } else {
                $ERROR++;
                application_log("access", $result["MESSAGE"]);
            }

            if($ERROR) {
                http_authenticate();
            }

            unset($username, $password);
        }
    }

    $ENTRADA_USER = User::get($user_proxy_id);

    $details = array();
    $details["app_id"] = (int) AUTH_APP_ID;
    $details["id"] = $user_proxy_id;
    $details["access_id"] = $ENTRADA_USER->getAccessId();
    $details["username"] = $user_username;
    $details["prefix"] = "";
    $details["firstname"] = $user_firstname;
    $details["lastname"] = $user_lastname;
    $details["email"] = $user_email;
    $details["telephone"] = "";
    $details["role"] = $user_role;
    $details["group"] = $user_group;
    $details["organisation_id"] = $user_organisation_id;

    $ENTRADA_ACL = new Entrada_Acl($details);
}

if ($user_proxy_id) {
    $event_start = strtotime("-12 months 00:00:00");
    $event_finish = strtotime("+12 months 23:59:59");

    if ((isset($_GET["start"])) && ($tmp_input = clean_input($_GET["start"], array("trim", "int")))) {
        $event_start = $tmp_input;
    }

    if ((isset($_GET["end"])) && ($tmp_input = clean_input($_GET["end"], array("trim", "int")))) {
        $event_finish = $tmp_input;
    }

    if ($user_group == "faculty" || $user_group == "staff" || $user_group == "medtech") {
        $learning_events = events_fetch_filtered_events(
            $user_proxy_id,
            $user_group,
            $user_role,
            $user_organisation_id,
            "date",
            "asc",
            "custom",
            $event_start,
            $event_finish,
            (isset($selected_course) && $selected_course ? events_filters_faculty($selected_course, $user_group, $user_role) : events_filters_defaults($user_proxy_id, $user_group, $user_role,  0, 0)),
            true,
            1,
            1750,
            0,
            ($user_group == "student" ? true : false),
            ($user_group == "student" ? true : false));
    } else {
        $learning_events = events_fetch_filtered_events(
            $user_proxy_id,
            $user_group,
            $user_role,
            $user_organisation_id,
            "date",
            "asc",
            "custom",
            $event_start,
            $event_finish,
            events_filters_defaults($user_proxy_id, $user_group, $user_role,  0, $selected_course),
            true,
            1,
            1750,
            0,
            ($user_group == "student" ? true : false),
            ($user_group == "student" ? true : false));
    }

    if ($ENTRADA_ACL->amIAllowed("clerkship", "read")) {
        $query = "SELECT c.*
                    FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
                    ON b.`event_id` = a.`event_id`
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
                    ON c.`rotation_id` = a.`rotation_id`
                    WHERE a.`event_finish` >= ".$db->qstr(strtotime("00:00:00"))."
                    AND (a.`event_status` = 'published' OR a.`event_status` = 'approval')
                    AND b.`econtact_type` = 'student'
                    AND b.`etype_id` = ".$db->qstr($user_proxy_id)."
                    ORDER BY a.`event_start` ASC";
        $clerkship_schedule = $db->GetRow($query);
        if ($clerkship_schedule && ($clerkship_schedule["rotation_id"] != MAX_ROTATION)) {
            $course_id = $clerkship_schedule["course_id"];
            $course_ids = array();

            $query = "SELECT `course_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
                        WHERE `course_id` <> ".$db->qstr($course_id)."
                        AND `course_id` <> 0";
            $course_ids_array = $db->GetAll($query);
            if ($course_ids_array) {
                foreach ($course_ids_array as $id) {
                    $course_ids[] = $id;
                }
            }

            foreach ($learning_events["events"] as $key => $event) {
                if (array_search($event["course_id"], $course_ids) !== false) {
                    unset($learning_events["events"][$key]);
                }
            }
        }
    }

    switch ($calendar_type) {
        case "ics" :
            add_statistic("calendar.api", "view", "type", "ics");

            require_once("Entrada/icalendar/class.ical.inc.php");

            $ical = new iCal("-//".html_encode($_SERVER["HTTP_HOST"])."//iCal ".APPLICATION_NAME." Calendar MIMEDIR//EN", 1, ENTRADA_ABSOLUTE."/calendars/", $user_username);

            if (!empty($learning_events["events"])) {
                foreach ($learning_events["events"] as $event) {
                    if ($event["custom_time"] == "1") {
                        $start = (int) $event["custom_time_start"];
                        $end = (int) $event["custom_time_end"];
                    } else {
                        $start = (int) $event["event_start"];
                        $end = (int) $event["event_finish"];
                    }

                    $ical->addEvent(
                        array(), // Organizer
                        $start, // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
                        $end, // End Time (write 'allday' for an allday event instead of a timestamp)
                        (($event["event_location"]) ? $event["event_location"] : "To Be Announced"), // Location
                        1, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
                        array(), // Array with Strings
                        strip_tags($event["event_message"]), // Description
                        strip_tags($event["event_title"]), // Title
                        1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
                        array(), // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
                        5, // Priority = 0-9
                        0, // frequency: 0 = once, secoundly - yearly = 1-7
                        0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
                        0, // Interval for frequency (every 2,3,4 weeks...)
                        array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
                        1, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
                        "", // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
                        0,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
                        1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
                        str_replace("http://", "https://", ENTRADA_URL)."/events?id=".(int) $event["event_id"], // optional URL for that event
                        "en", // Language of the Strings
                        md5((int) $event["event_id"])
                    );
                }
            }

            $ical->outputFile();
        break;
        case "json" :
        default :
            $events = array();

            if (!empty($learning_events["events"])) {
                foreach ($learning_events["events"] as $drid => $event) {
                    $cal_type = 1;
                    $cal_updated = "";

                    if ($event["audience_type"] == "proxy_id") {
                        $cal_type = 3;
                    }

                    if (isset($event["last_visited"]) && ((int) $event["last_visited"]) && ((int) $event["last_visited"] < (int) $event["updated_date"])) {
                        $cal_type = 2;

                        $cal_updated = date(DEFAULT_DATE_FORMAT, $event["updated_date"]);
                    }

                    if ($event["custom_time"] == "1") {
                        $start = date("c", $event["custom_time_start"]);
                        $end = date("c", $event["custom_time_end"]);
                    } else {
                        $start = date("c", $event["event_start"]);
                        $end = date("c", $event["event_finish"]);
                    }

                    $events[] = array (
                                "drid" => $event["event_id"],
                                "id" => $event["event_id"],
                                "start" => $start,
                                "end" => $end,
                                "title" => strip_tags($event["event_title"]),
                                "loc" => strip_tags($event["event_location"]),
                                "type" => $cal_type,
                                "color" => strip_tags($event["event_color"]),
                                "updated" => $cal_updated
                    );
                }
            }

            echo json_encode($events);
        break;
    }
}
