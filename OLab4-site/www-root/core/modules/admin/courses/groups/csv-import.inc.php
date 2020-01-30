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
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSE_GROUPS"))) {
    exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseResource($_GET["course_id"], $ENTRADA_USER->getActiveOrganisation()), "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));
    $request = ${"_" . $request_method};

    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], ["trim", "int"])) {
        $course_id = $tmp_input;
    } else {
        add_error($translate->_("Invalid course ID."));
    }
    if (isset($request["cperiod_import_select"]) && $tmp_input = clean_input($request["cperiod_import_select"], ["trim", "int"])) {
        $cperiod_id = $tmp_input;
    } else {
        add_error($translate->_("Invalid curriculum period ID."));
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "import" :
                    if (isset($course_id) && isset($cperiod_id)) {
                        if (isset($_FILES["file"])) {
                            switch ($_FILES["file"]["error"]) {
                                case 1 :
                                case 2 :
                                case 3 :
                                    add_error($translate->_("The file that uploaded did not complete the upload process or was interupted. Please try your CSV again."));
                                    break;
                                case 4 :
                                    add_error($translate->_("You did not select a file on your computer to upload."));
                                    break;
                                case 6 :
                                case 7 :
                                    add_error($translate->_("Unable to store the new file on the server, please try again."));
                                    break;
                                default :
                                    continue;
                                    break;
                            }
                        } else {
                            add_error($translate->_("To import groups you must select a file to upload from your computer."));
                        }

                        if (!in_array(mime_content_type($_FILES["file"]["tmp_name"]), array("text/csv", "text/plain", "application/vnd.ms-excel","text/comma-separated-values","application/csv", "application/excel", "application/vnd.ms-excel", "application/vnd.msexcel","application/octet-stream"))) {
                            add_error($translate->_("Invalid <strong>file type</strong> uploaded. Must be a CSV file in the proper format."));
                        }

                        if (!has_error()) {
                            $PROCESSED["group_name"] = "";
                            $PROCESSED["course_id"] = $course_id;
                            $PROCESSED["cperiod_id"] = $cperiod_id;
                            $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                            $PROCESSED["active"] = 1;

                            $associated_tutors = array();

                            $fh = fopen($_FILES["file"]["tmp_name"], 'r');
                            $line = 0;
                            while (($data = fgetcsv($fh, 1000, ",")) !== FALSE) {
                                if ($line++ && !$ERROR) {
                                    if ($num != count($data)) {
                                        add_error($translate->_("The file appears as an <strong>inconsistent</strong> csv file: varying field number."));
                                    }
                                }
                                $num = count($data);
                            }
                            if ($line < 2) {
                                add_error($translate->_("The file has <strong>no data</strong> or only a header line."));
                            }

                            fclose($fh);

                            $audience_object = new Models_Course_Audience();
                            $members_results = $audience_object->getAllUsersByCourseIDCperiodIDOrganisationID($course_id, $cperiod_id, $PROCESSED["organisation_id"]);
                            $allowed_members = array();
                            if ($members_results) {
                                foreach ($members_results as $result_object) {
                                    if ($proxy_id = $result_object["proxy_id"]) {
                                        $allowed_members[] = $proxy_id;
                                    }
                                }
                            }

                            if (!has_error()) {
                                $fh = fopen($_FILES["file"]["tmp_name"], 'r');
                                $line = $count_users = $group_count = 0;
                                $GROUP_IDS = array();
                                while (($data = fgetcsv($fh, 1000, ",")) !== FALSE) {
                                    if (!$line++) {  // Skip header
                                        continue;
                                    }

                                    if (!strlen($data[0])) {
                                        add_notice(sprintf($translate->_("Line [%d] is missing required field [group name]."), $line));
                                        continue;
                                    } else if (!strlen($data[4])) {
                                        add_notice(sprintf($translate->_("Line [%d] is missing required field [learner number]."), $line));
                                        continue;
                                    }

                                    if (strcmp($data[0], $PROCESSED["group_name"])) {
                                        $PROCESSED["group_name"] = $data[0];
                                        $result = Models_Course_Group::fetchRowByGroupNameCourseIDCperiodID($PROCESSED["group_name"], $course_id, $cperiod_id);
                                        if ($result) {
                                            $PROCESSED["cgroup_id"] = $result->getID();
                                        } else {
                                            unset($PROCESSED["cgroup_id"]);
                                            $group = new Models_Course_Group();
                                            if (!$insert = $group->fromArray($PROCESSED)->insert()) {
                                                add_error(sprintf($translate->_("There was an error while trying to add the <strong>Group</strong> %s. <br /><br />The system administrator was informed of this error; please try again later."), $PROCESSED["group_name"]));
                                                continue;
                                            } else {
                                                add_success(sprintf($translate->_("You have successfully added new group <strong>%s</strong> to the system."), $PROCESSED["group_name"]));
                                                $PROCESSED["cgroup_id"] = $group->getID();
                                            }
                                        }
                                        if (!in_array($PROCESSED["cgroup_id"], $GROUP_IDS)) {
                                            $GROUP_IDS[] = $PROCESSED["cgroup_id"];
                                            $group_count++;
                                        }
                                    }

                                    $number = clean_input($data[4], ["nows", "striptags"]);
                                    if ($number) {
                                        $user = Models_User::fetchRowByNumber($number);
                                        if ($user) {
                                            $PROCESSED["proxy_id"] = $user->getID();
                                        } else {
                                            add_notice(sprintf($translate->_("Unable to find the student number provided on line [%d]"), $line));
                                            continue;
                                        }
                                    } else {
                                        add_notice(sprintf($translate->_("Learner Name [%s] on line [%d] did not have a University ID specified."), ($data[3]), $line));
                                        continue;
                                    }

                                    if (isset($PROCESSED["proxy_id"])) {
                                        if (in_array($PROCESSED["proxy_id"], $allowed_members)) {
                                            $insert = array();
                                            $insert["proxy_id"] = $PROCESSED["proxy_id"];
                                            $insert["updated_date"] = time();
                                            $insert["updated_by"] = $ENTRADA_USER->getID();
                                            $insert["cgroup_id"] = $PROCESSED["cgroup_id"];
                                            $insert["active"] = 1;
                                            $group_audiences = Models_Course_Group_Audience::fetchAllByCGroupIDProxyID($insert["cgroup_id"], $insert["proxy_id"]);
                                            if ($group_audiences) {
                                                foreach ($group_audiences as $group_audience) {
                                                    // if the user was previously in the audience, just re-enable them
                                                    if (!($group_audience->getActive())) {
                                                        if (!$group_audience->setActive(1)->update()) {
                                                            add_error(sprintf($translate->_("Failed to update learner on line [%d]. Please contact a system administrator if this problem persists."), $line));
                                                            application_log("error", $translate->_("Error while updating a Course Group Audience learner into database. Database server said: " . $db->ErrorMsg()));
                                                        }
                                                    } else {
                                                        add_notice(sprintf($translate->_("'%s' is already a learner of the '%s' group. <br />"), Models_User::fetchRowByID($PROCESSED["proxy_id"])->getFullname(false), $PROCESSED["group_name"]));
                                                    }
                                                }
                                            } else {
                                                $audience_object = new Models_Course_Group_Audience();
                                                if (!$audience_object->fromArray($insert)->insert()) {
                                                    add_error(sprintf($translate->_("Failed to insert learner on line [%d] into the group. Please contact a system administrator if this problem persists."), $line));
                                                    application_log("error", $translate->_("Error while inserting learner into database. DB said: " . $db->ErrorMsg()));
                                                } else {
                                                    $count_users++;
                                                }
                                            }
                                        } else {
                                            add_error(sprintf($translate->_("Failed to insert learner on line [%d], This learner is not in the audience for the selected curriculum period. <br />"), $line));
                                        }
                                        unset($PROCESSED["proxy_id"]);
                                    }

                                    if (($data[2]) && ($associated_tutors !== explode(";", $data[2])) && ($associated_tutors = explode(";", $data[2])) && (@is_array($associated_tutors)) && (@count($associated_tutors))) {
                                        $associated_tutors_name = explode(";", $data[1]);
                                        $order = 0;
                                        $group_contacts_object = new Models_Course_Group_Contact();
                                        foreach ($associated_tutors as $key => $tutor_number) {
                                            if ($tutor_number = clean_input($tutor_number, array("trim", "int"))) {
                                                $group_contacts_object = new Models_Course_Group_Contact();
                                                $user = Models_User::fetchRowByNumber($tutor_number);
                                                if ($user) {
                                                    $PROCESSED_CONTACT["proxy_id"] = $user->getID();
                                                    $PROCESSED_CONTACT["cgroup_id"] = $PROCESSED["cgroup_id"];
                                                    if (!$group_contacts_object->fetchRowByProxyIDCGroupID($PROCESSED_CONTACT["proxy_id"], $PROCESSED_CONTACT["cgroup_id"])) {
                                                        $PROCESSED_CONTACT["contact_order"] = $order;
                                                        $PROCESSED_CONTACT["updated_date"] = time();
                                                        $PROCESSED_CONTACT["updated_by"] = $ENTRADA_USER->getID();

                                                        if (!$result = $group_contacts_object->fromArray($PROCESSED_CONTACT)->insert()) {
                                                            add_error(sprintf($translate->_("There was an error when trying to insert Tutor Name [%s] into the system. The system administrator was informed of this error; please try again later."), $associated_tutors_name[$key]));
                                                            application_log("error", $translate->_("Unable to insert a new group_contact to the database when updating an event. DB said: " . $db->ErrorMsg()));
                                                        } else {
                                                            $order++;
                                                        }
                                                    }
                                                } else {
                                                    add_notice(sprintf($translate->_("Unable to find the tutor number [%d] provided on line [%d]"), $tutor_number, $line));
                                                    continue;
                                                }
                                            } else {
                                                add_notice(sprintf($translate->_("Tutor Name [%s] on line [%d] did not have a University ID specified."), $associated_tutors_name[$key], $line));
                                            }
                                        }
                                    }
                                }
                                fclose($fh);
                            }
                        }
                        echo json_encode(array("status" => "success", "dataSuccess" => $SUCCESSSTR, "dataNotice" => $NOTICESTR, "dataError" => $ERRORSTR));
                        break;
                    } else {
                        echo json_encode(array("status" => "error", "dataError" => $ERRORSTR));
                    }
            }
            break;

        case "GET" :
            switch ($request["method"]) {
                case "demo" :
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: text/csv");
                    header("Content-Disposition: attachment; filename=group-template.csv");
                    header("Content-Transfer-Encoding: binary");
                    $fp = fopen("php://output", "w");
                    $row = array("group_name" => $translate->_("Group Name"), "tutors" => $translate->_("Tutors"), "tutors_number" => $translate->_("Tutors Number"), "learner_name" => $translate->_("Learner Name"), "learner_number" => $translate->_("Learner Number"));
                    fputcsv($fp, $row);
                    fputcsv($fp, array("Group 1", "Wright, Brian; Fry, Adam" , "123456788; 12345677", "Morin, Kathy", "123456789"));
                    fputcsv($fp, array("Group 1", "Wright, Brian; Fry, Adam", "123456788; 12345677", "123456790"));
                    fputcsv($fp, array("Group 2", "", "", "Martinez, Molly", "123456791"));
                    fputcsv($fp, array("Group 2", "", "", "Henderson, Travis", "123456791"));
                    fclose($fp);
                    break;
            }
            break;
    }
}
exit();