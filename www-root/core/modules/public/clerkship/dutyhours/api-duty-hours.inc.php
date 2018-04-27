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
 * This file displays the edit entry interface.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
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

// Checking authorization
if ((!isset($_SESSION["isAuthorized"])) && (!(bool)$_SESSION["isAuthorized"])) {

    application_log("error", "Discussion API accessed without valid session_id.");
    header("Location: " . ENTRADA_URL);
    exit;

} elseif (!$ENTRADA_ACL->amIAllowed("dutyhours", "read")) {

    $ONLOAD[] = "setTimeout(\"window.location=\"" . ENTRADA_URL . "/admin/" . $MODULE . "\", 15000)";

    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br />" .
        "<br />If you believe you are receiving this message in error please contact <a href=\"mailto:" .
        html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" .
        html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";

    echo display_error();

} else {

    ob_clear_open_buffers();

    $ACTION = "";
    $EDISCUSSION_ID = 0;
    $PROCESSED = array();
    $STUDENT_ID = 1;

    if ((isset($_POST["action"])) && (trim($_POST["action"]))) {
        $ACTION = trim($_POST["action"]);
    }

    $PROCESSED = array();

    if ((isset($_POST["entry_id"])) && (clean_input($_POST["entry_id"], array("trim", "int")))) {
        $ENTRY_ID = clean_input($_POST["entry_id"], array("trim", "int"));
        $PROCESSED["dhentry_id"] = $ENTRY_ID;
    }

    if ((isset($_POST["student_id"])) && (clean_input($_POST["student_id"], array("trim", "int")))) {
        $STUDENT_ID = clean_input($_POST["student_id"], array("trim", "int"));
        $PROCESSED["proxy_id"] = $STUDENT_ID;
    } else {
        $PROCESSED["proxy_id"] = NULL;
    }

    if (((isset($_POST["encounter_date"])) && ($encounter_date = clean_input($_POST["encounter_date"], Array("notags", "trim"))))
        && ((isset($_POST["encounter_time"])) && ($encounter_time = clean_input($_POST["encounter_time"], Array("notags", "trim"))))) {
        $date = $encounter_date;
        $time = $encounter_time;
        if ($date && $time) {
            $PROCESSED["encounter_date"] = strtotime($date . "" . $time);
        } else {
            $PROCESSED["encounter_date"] = NULL;
        }
    } else {
        $PROCESSED["encounter_date"] = NULL;
    }

    if ((isset($_POST["updated_date"])) && (clean_input(preg_replace("/\([^)]+\)/", "", $_POST["updated_date"]), array("trim", "strtotime")))) {
        $UPDATED_DATE = clean_input(preg_replace("/\([^)]+\)/", "", $_POST["updated_date"]), array("trim", "strtotime"));
        $PROCESSED["updated_date"] = $UPDATED_DATE;
    } else {
        $PROCESSED["updated_date"] = time();
    }

    if ((isset($_POST["location_id"])) && (clean_input($_POST["location_id"], array("trim", "int")))) {
        $LOCATION_ID = clean_input($_POST["location_id"], array("trim", "int"));
        $PROCESSED["llocation_id"] = $LOCATION_ID;
    } else {
        $PROCESSED["llocation_id"] = 0;
    }

    if ((isset($_POST["site_id"])) && (clean_input($_POST["site_id"], array("trim", "int")))) {
        $SITE_ID = clean_input($_POST["site_id"], array("trim", "int"));
        $PROCESSED["lsite_id"] = $SITE_ID;
    } else {
        $PROCESSED["lsite_id"] = 0;
    }

    if ((isset($_POST["course_id"])) && (clean_input($_POST["course_id"], array("trim", "int")))) {
        $COURSE_ID = clean_input($_POST["course_id"], array("trim", "int"));
        $PROCESSED["course_id"] = $COURSE_ID;
    } else {
        $PROCESSED["course_id"] = 0;
    }

    if ((isset($_POST["cperiod_id"])) && (clean_input($_POST["cperiod_id"], array("trim", "int")))) {
        $CPERIOD_ID = clean_input($_POST["cperiod_id"], array("trim", "int"));
        $PROCESSED["cperiod_id"] = $CPERIOD_ID;
    } else {
        $PROCESSED["course_id"] = 0;
    }

    if ((isset($_POST["hours"])) && (clean_input($_POST["hours"], array("trim", "float")))) {
        $HOURS = clean_input($_POST["hours"], array("trim", "float"));
        $PROCESSED["hours"] = $HOURS;
    } else {
        $PROCESSED["hours"] = 0;
    }

    if ((isset($_POST["hours_type"])) && (clean_input($_POST["hours_type"], array("trim")))) {
        $HOURS_TYPE = clean_input($_POST["hours_type"], array("trim"));
        $PROCESSED["hours_type"] = $HOURS_TYPE;
        if ($HOURS_TYPE != "on_duty") {
            // only record hours if its an on_duty entry
            $PROCESSED["hours"] = 0;
        }
    } else {
        $PROCESSED["hours_type"] = NULL;
    }

    if ((isset($_POST["active"])) && $_POST["active"]) {
        $PROCESSED["entry_active"] = 1;
    } else {
        $PROCESSED["entry_active"] = 0;
    }

    if ((isset($_POST["comments"])) && (clean_input($_POST["comments"], array("trim")))) {
        $COMMENTS = clean_input($_POST["comments"], array("trim"));
        $PROCESSED["comments"] = $COMMENTS;
    } else {
        $PROCESSED["comments"] = NULL;
    }

    if ((isset($_POST["query_type"])) && (clean_input($_POST["query_type"], array("trim")))) {
        $QUERY_TYPE_RECEIVED = clean_input($_POST["query_type"], array("trim"));
        $query_types = array("course", "all");

        if (in_array($QUERY_TYPE_RECEIVED, $query_types)) {
            $QUERY_TYPE = $QUERY_TYPE_RECEIVED;
        } else {
            $QUERY_TYPE = "all";
        }
    } else {
        $QUERY_TYPE = "all";
    }

    switch ($ACTION) {
        case "getentry" :
            if ($ENTRY_ID) {

                $duty_hours = new Models_Duty_Hours();
                $result = $duty_hours->fetchRowByID($ENTRY_ID, 1);

                if ($result) {

                    $return_result = array();

                    $newval = $result->toArray();
                    $return_result[] = $newval;

                    $return_array = array();

                    if (count($return_result) >= 0) {
                        $return_array["data"] = $return_result;
                        $return_array["count"] = count($return_result);
                        $return_array["message"] = count($return_result) . " results found";
                        echo json_encode($return_array);
                    } else {
                        $return_array["data"] = NULL;
                        $return_array["count"] = 0;
                        $return_array["message"] = "0 results found";
                        echo json_encode($return_array);
                    }

                } else {
                    $return_array["data"] = NULL;
                    $return_array["count"] = 0;
                    $return_array["message"] = "0 results found";
                    echo json_encode($return_array);
                    application_log(
                        "error",
                        "Unable to locate the duty hours entry for this student id [" . $EDISCUSSION_ID . "]. Database said: " . $db->ErrorMsg()
                    );
                }
            } else {
                application_log("notice", "There was no valid id to the api-duty-hours");
            }
            break;
        case "getstudent" :

            if ($STUDENT_ID) {

                $duty_hours = new Models_Duty_Hours();
                $result = $duty_hours->fetchAllRecordsByColumn("proxy_id", $STUDENT_ID, 1);

                if ($result) {

                    $return_result = array();

                    foreach ($result as $value) {
                        $newval = $value->toArray();
                        $return_result[] = $newval;
                    }

                    $return_array = array();

                    if (count($return_result) > 0) {
                        $return_array["data"] = $return_result;
                        $return_array["count"] = count($return_result);
                        $return_array["message"] = count($return_result) . " results found";
                        echo json_encode($return_array);
                    } else {
                        $return_array["data"] = NULL;
                        $return_array["count"] = 0;
                        $return_array["message"] = "0 results found";
                        echo json_encode($return_array);
                    }

                } else {
                    $return_array["data"] = NULL;
                    $return_array["count"] = 0;
                    $return_array["message"] = "0 results found";
                    echo json_encode($return_array);
                    application_log(
                        "error",
                        "Unable to locate the provided student id [" . $EDISCUSSION_ID . "]. Database said: " . $db->ErrorMsg()
                    );
                }
            } else {
                application_log("notice", "There was no student provided to the api-duty-hours");
            }
            break;
        case "getstudenthours" :

            if ($STUDENT_ID) {

                $duty_hours = new Models_Duty_Hours();

                switch ($QUERY_TYPE) {
                    case "all":
                        $result = $duty_hours->fetchAllRecordsByColumn(
                            "proxy_id", $PROCESSED["proxy_id"], 1
                        );
                        break;
                    case "course":
                        $result = $duty_hours->fetchAllRecordsByColumnWithMultipleCriteria(
                            "proxy_id", $PROCESSED["proxy_id"],
                            "course_id", $PROCESSED["course_id"],
                            "cperiod_id", $PROCESSED["cperiod_id"],
                            1
                        );
                        break;
                }

                if ($result) {

                    $return_result = array();

                    foreach ($result as $value) {
                        $newval = $value->toArray();
                        $return_result[] = $newval;
                    }

                    $return_array = array();

                    if (count($return_result) > 0) {
                        $return_array["data"] = $return_result;
                        $return_array["count"] = count($return_result);
                        $return_array["message"] = count($return_result) . " results found";
                        echo json_encode($return_array);
                    } else {
                        $return_array["data"] = NULL;
                        $return_array["count"] = 0;
                        $return_array["message"] = "0 results found";
                        echo json_encode($return_array);
                    }

                } else {
                    $return_array["data"] = NULL;
                    $return_array["count"] = 0;
                    $return_array["message"] = "0 results found";
                    echo json_encode($return_array);
                    application_log(
                        "error",
                        "Unable to locate the provided student id [" . $EDISCUSSION_ID . "]. Database said: " . $db->ErrorMsg()
                    );
                }
            } else {
                application_log("notice", "There was no student provided to the api-duty-hours");
            }
            break;

        case "insertrecord" :

            if ($STUDENT_ID) {
                $duty_hours = new Models_Duty_Hours($PROCESSED);
                $return_array = array();
                // Check incoming data against validation rules
                $validation_string = $duty_hours->validation($PROCESSED);
                if (!empty($validation_string)) {
                    $return_array["fail_message"] = $validation_string;
                    echo json_encode($return_array);
                    break;
                }

                if ($duty_hours->insert()) {
                    $return_array["data"] = $PROCESSED;
                    $return_array["count"] = 1;
                    $return_array["message"] = $PROCESSED["proxy_id"] . " has been inserted";
                    echo json_encode($return_array);
                } else {
                    $return_array["data"] = NULL;
                    $return_array["count"] = 0;
                    $return_array["message"] = "Unable to insert record " . $db->ErrorMsg();
                    echo json_encode($return_array);
                    application_log("error", "Unable to insert record " . $db->ErrorMsg());
                }

            } else {
                $return_array["data"] = NULL;
                $return_array["count"] = 0;
                $return_array["message"] = "No student provided";
                application_log("notice", "There was no student provided to the api-duty-hours");
            }
            break;

        case "updaterecord" :

            $duty_hours = new Models_Duty_Hours();
            $entry = $duty_hours->fetchRowByID($PROCESSED["dhentry_id"]);

            // Check incoming data against validation rules
            $validation_string = $duty_hours->validation($PROCESSED);
            if (!empty($validation_string)) {
                $return_array["fail_message"] = $validation_string;
                echo json_encode($return_array);
                break;
            }

            if ($STUDENT_ID) {
                $UPDATED = array();
                foreach ($PROCESSED as $key => $value) {
                    if ($PROCESSED[$key] != NULL) {
                        $UPDATED[$key] = $value;

                    }
                }
                $UPDATED["entry_active"] = $PROCESSED["entry_active"];
                $UPDATED["hours"] = $PROCESSED["hours"];
                $UPDATED["off_day"] = $PROCESSED["off_day"];
                $UPDATED["comments"] = $PROCESSED["comments"];
                if ($entry) {
                    $return_array = array();
                    if ($entry->fromArray($UPDATED)->update()) {
                        $return_array["data"] = $PROCESSED;
                        $return_array["count"] = 1;
                        $return_array["message"] = $PROCESSED["dhentry_id"] . " has been updated";
                        echo json_encode($return_array);
                    } else {
                        $return_array["data"] = NULL;
                        $return_array["count"] = 0;
                        $return_array["message"] = "Unable to update record " . $db->ErrorMsg();
                        echo json_encode($return_array);
                        application_log("error", "Unable to update record " . $db->ErrorMsg());

                    }
                } else {
                    $return_array["data"] = NULL;
                    $return_array["count"] = 0;
                    $return_array["message"] = "Record does not exist";
                    application_log("notice", "There was no student provided to the api-duty-hours");
                }
            } else {
                $return_array["data"] = NULL;
                $return_array["count"] = 0;
                $return_array["message"] = "No student provided";
                application_log("notice", "There was no student provided to the api-duty-hours");
            }
            break;

        case "deactivaterecord" :

            $duty_hours = new Models_Duty_Hours();
            $entry = $duty_hours->fetchRowByID($PROCESSED["dhentry_id"]);

            if ($STUDENT_ID) {
                $UPDATED = array();
                $UPDATED["entry_active"] = 0;
                if ($entry) {
                    $return_array = array();
                    if ($entry->fromArray($UPDATED)->update()) {
                        $return_array["data"] = $PROCESSED;
                        $return_array["count"] = 1;
                        $return_array["message"] = $PROCESSED["dhentry_id"] . " has been deactivated";
                        echo json_encode($return_array);
                    } else {
                        $return_array["data"] = NULL;
                        $return_array["count"] = 0;
                        $return_array["message"] = "Unable to deactivate record " . $db->ErrorMsg();
                        echo json_encode($return_array);
                        application_log("error", "Unable to deactivate record " . $db->ErrorMsg());
                    }
                } else {
                    $return_array["data"] = NULL;
                    $return_array["count"] = 0;
                    $return_array["message"] = "Record does not exist";
                    application_log("notice", "There was no student provided to the api-duty-hours");
                }
            } else {
                $return_array["data"] = NULL;
                $return_array["count"] = 0;
                $return_array["message"] = "No student provided";
                application_log("notice", "There was no student provided to the api-duty-hours");
            }
            break;

        default :
            application_log(
                "error",
                "Discussion API accessed with an unknown action [" . $ACTION . "]."
            );
            break;
    }
    exit;
}

