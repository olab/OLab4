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
 * API to handle interaction with assessments
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    $method     = isset($_GET["method"]) ? clean_input($_GET["method"], array("notags", "trim")) : "";
    $course_id  = isset($_GET["course_id"]) ? clean_input($_GET["course_id"], "int") : 0;
    $cperiod_id  = isset($_GET["cperiod_id"]) ? clean_input($_GET["cperiod_id"], "int") : 0;
    if (!$course_id) {
        echo json_encode(array("status" => "error", "data" => $translate->_("No course id in the query")));
        exit(); 
    }

    $contact_roles = array(
        "director" => $translate->_("Course Directors"),
        "ccoordinator" => $translate->_("Curriculum Coordinators"),
        "faculty" => $translate->_("Associated Faculty"),
        "pcoordinator" => $translate->_("Program Coordinator"),
        "evaluationrep" => $translate->_("Evaluation Rep"),
        "studentrep" => $translate->_("Student Rep")
    );

    $data = array();
    switch ($method) {
        case "exam_title_search" :
            if (isset($request["course_id"])) {
                $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"));
                $PROCESSED["course_id"] = $tmp_input;
            } else {
                add_error("No course ID provided.");
            }

            if (isset($request["assessment_id"])) {
                $tmp_input = clean_input(strtolower($request["assessment_id"]), array("trim", "int"));
                $PROCESSED["assessment_id"] = $tmp_input;
            } else {
                $PROCESSED["assessment_id"] = 0;
            }

            if (isset($request["title"]) && $tmp_input = clean_input(strtolower($request["title"]), array("trim", "striptags"))) {
                $PROCESSED["search_term"] = "%".$tmp_input."%";
            } else {
                $PROCESSED["search_term"] = "";
            }

            if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                $PROCESSED["post_id"] = $tmp_input;
                $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                if ($post) {
                    $exam_id = $post->getExamID();
                }
            }

            if (!has_error()) {
                $exams = Models_Exam_Exam::fetchAllRecordsBySearchTermCourseLimit($PROCESSED["search_term"], $PROCESSED["course_id"], $PROCESSED["assessment_id"]);
                $exams_array = array();
                if ($exams) {
                    foreach ($exams as $exam) {
                        if ($exam["post_id"] && $exam["post_id"] > 0) {
                            if ((isset($exam_id) && $exam_id && $exam_id == $exam["exam_id"]) || !isset($exam_id)) {
                                $exam_post = Models_Exam_Post::fetchRowByID($exam["post_id"]);
                                if (isset($exam_post) && is_object($exam_post)) {
                                    $exams_array[] = array(
                                        "exam_title" => $exam["title"],
                                        "post_id" => $exam_post->getID(),
                                        "post_title" => $exam_post->getTitle(),
                                        "post_start" => date("D M d/y g:ia", $exam_post->getStartDate())
                                    );
                                }
                            }
                        }
                    }
                }
                if (!empty($exams_array)) {
                    echo json_encode(array("status" => "success", "data" => $exams_array));
                } else {
                    echo json_encode(array("status" => "error", "data" => array("No posts found with an exam title containing <strong>". $PROCESSED["search_term"] ."</strong>")));
                }
            } else {
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            }

            break;
        case "get-contacts-group":
            foreach ($contact_roles as $role_id => $role_name) {
                $contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, $role_id);
                if (count($contacts)) {
                    $data[] = array("target_id" => $role_id, "target_parent" => "0", "target_label" => $role_name, "target_children" => count($contacts), "level_selectable" => false);
                }
            }

            if (count($data)) {
                echo json_encode(array("status" => "success", "data" => $data, "parent_id" => 0, "parent_name" => 0));
            } else {
                echo json_encode(array("status" => "error", "data" => $translate->_("No contacts for that course were found.")));
            }
            break;

        case "get-contacts":
            // gets the contacts for a given parent type. If no parent type is supplied, return all the top level contact roles (the same as case 'get-contacts-group')
            $parent_id = isset($_GET["parent_id"]) ? clean_input($_GET["parent_id"], array("notags", "trim")) : "";
            if (!$parent_id) {
                foreach ($contact_roles as $role_id => $role_name) {
                    $contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, $role_id);
                    if (count($contacts)) {
                        $data[] = array("target_id" => $role_id, "target_parent" => "0", "target_label" => $role_name, "target_children" => count($contacts), "level_selectable" => false);
                    }
                }
            } else {
                $contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, $parent_id);

                if ($contacts) {
                    foreach ($contacts as $contact) {
                        $user = Models_User::fetchRowByID($contact->getProxyID());
                        $data[] = array("target_id" => $user->getID(), "target_parent" => $parent_id, "target_label" => $user->getFullname());
                    }
                }
            }
            if (count($data)) {
                echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => ($parent_id ? $contact_roles[$parent_id] : "0"), "level_selectable" => 1));
            } else {
                echo json_encode(array("status" => "error", "data" => print_r($_GET, 1)));
            }
            break;

        case "get-groups":
            $limit = isset($_GET["limit"]) ? clean_input($_GET["limit"], array("int", "trim")) : 50;

            $groups_model = new Models_Course_Group();
            $groups = $groups_model->getGroupsByCourseID($course_id, $cperiod_id, null, 0, $limit, "name");

            if ($groups) {
                foreach($groups as $group) {
                    $data[] = array("target_id" => $group["cgroup_id"], "target_label" => $group["group_name"]);
                }
               
            }

            if ($data) {
                echo json_encode(array("status" => "success", "data" => $data));
            }
            else {
                echo json_encode(array("status" => "error", "data" => $translate->_("No groups found.")));
            }

            break;

        case "get-notify-details":
            $user_list = array();
            $user_sort = array();

            $notify_list = isset($_GET["notify_list"]) ? $_GET["notify_list"] : array();

            foreach ($notify_list as $notify) {
                $notify = clean_input($notify, array("notags", "trim"));
                $role_list = array();
                $role_id_list = array();

                if (! intval($notify)) {
                    $contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, $notify);
                    foreach ($contacts as $contact) {
                        $user = Models_User::fetchRowByID($contact->getProxyID());
                        $roles = Models_Course_Contact::fetchByProxyAndCourse($user->getID(), $course_id);
                        $roles = Models_Course_Contact::fetchByProxyAndCourse($user->getID(), $course_id);
                        if (count($roles)) {
                            foreach ($roles as $role) {
                                $role_list[] = $contact_roles[$role->getContactType()];
                                $role_id_list[] = $role->getContactType();
                            }
                            $role_list = array_unique($role_list);
                            $role_id_list = array_unique($role_id_list);
                        }
                        $user_list[] = array(
                            "contact_id" => $user->getId(),
                            "contact_name" => $user->getFullname(),
                            "contact_type" => $role_list,
                            "contact_type_ids" => $role_id_list
                        );

                        $user_sort[] = $user->getFullname();
                    }
                } else {
                    $user = Models_User::fetchRowByID($notify);
                    $roles = Models_Course_Contact::fetchByProxyAndCourse($user->getID(), $course_id);
                    if (count($roles)) {
                        foreach ($roles as $role) {
                            $role_list[] = $contact_roles[$role->getContactType()];
                            $role_id_list[] = $role->getContactType();
                        }

                        $role_list = array_unique($role_list);
                        $role_id_list = array_unique($role_id_list);
                    }
                    $user_list[] = array(
                        "contact_id" => $user->getId(),
                        "contact_name" => $user->getFullname(),
                        "contact_type" => $role_list,
                        "contact_type_ids" => $role_id_list
                    );
                    $user_sort[] = $user->getFullname();
                }
            }

            array_multisort($user_list, $user_sort);
            $user_list = array_map("unserialize", array_unique(array_map("serialize", $user_list)));

            echo json_encode(array("status" => "success", "data" => $user_list));
            break;

        default:
            echo json_encode(array("status" => "error", "data" => $translate->_("Unsupported query")));
    }

    exit;
}
