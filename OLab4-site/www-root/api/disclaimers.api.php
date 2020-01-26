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
 * API for manage User Disclaimers
 *
 * @author Organisation: Queens University
 * @author Unit: Medtech Unit
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
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

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($request["organisation_id"]) && $tmp_input = clean_input($request["organisation_id"], "int")) {
        $ORGANISATION_ID = $tmp_input;
    } else {
        $ORGANISATION_ID = $ENTRADA_USER->getActiveOrganisation();
    }
    switch ($request_method) {
        case "GET" :
            switch ($request["method"]) {
                case "get-groups" :
                    $data = array();
                    $groups = Models_System_Group::fetchAllByOrganisationID($ORGANISATION_ID);
                    if ($groups) {
                        foreach ($groups as $group) {
                            $data[] = array("target_id" => $group["id"], "target_label" => $translate->_(ucfirst($group["group_name"])), "target_name" => "Groups", "target_children" => 1, "level_selectable" => 1);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No System Groups were found.")));
                    }
                    break;

                case "get-courses" :
                    $data = array();
                    $courses = Models_Course::fetchAllByOrg($ORGANISATION_ID);
                    if ($courses) {
                        foreach ($courses as $course) {
                            $target_label = $course->getCourseName();

                            if ($course->getCourseCode() != "") {
                                $target_label = $course->getCourseCode() . ": " . $target_label;
                            }

                            $data[] = array("target_id" => $course->getID(), "target_label" => $target_label, "target_name" => "Courses", "target_children" => 0, "level_selectable" => 1);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Courses were found.")));
                    }
                    break;

                case "get-communities" :
                    $data = array();
                    $communities = Models_Community::fetchAllByProxyID();
                    if ($communities) {
                        foreach ($communities as $community) {
                            $data[] = array("target_id" => $community->getID(), "target_label" => $translate->_(ucfirst($community->getTitle())), "target_name" => "Communities", "target_children" => 0, "level_selectable" => 1);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Communities were found.")));
                    }
                    break;

                case "get-roles" :
                    $data = array();

                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    } else {
                        $PROCESSED["group_id"] = 0;
                    }

                    $roles = Models_System_Role::fetchAllByGroupID($PROCESSED["group_id"], $ORGANISATION_ID, 1, true);
                    if ($roles) {
                        foreach ($roles as $role) {
                            $data[] = array("target_id" => $role["id"], "target_label" => $translate->_(ucfirst($role["role_name"])), "target_name" => "Roles", "target_children" => 0, "level_selectable" => 1);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => $PROCESSED["group_id"], "target_name" => "Roles", "level_selectable" => 1, "no_back_btn" => true));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;

                case "get-role-members" :
                    $data = array();

                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    if (isset($PROCESSED["parent_id"]) && $PROCESSED["parent_id"] != 0) {
                        $role = Models_System_Role::fetchRowByID($PROCESSED["parent_id"]);
                        if ($role) {
                            $group = Models_System_Group::fetchRowByID($role->getGroupsID());
                            if ($group) {
                                $users = Models_User_Access::getGroupRoleMembers($ORGANISATION_ID,
                                    $group->getGroupName(), $role->getRoleName());
                                if ($users) {
                                    foreach ($users as $user) {
                                        $data[] = array(
                                            "target_id" => $user["id"],
                                            "target_label" => $user["firstname"] . " " . $user["lastname"],
                                            "parent_id" => 0
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        $roles = Models_System_Role::fetchAllByOrganisationID($ORGANISATION_ID, 1);
                        if ($roles) {
                            foreach ($roles as $role) {
                                $data[] = array("target_id" => $role["id"], "target_label" => $translate->_(ucfirst($role["role_name"])), "target_name" => "Users", "target_children" => 1, "level_selectable" => 0, "no_back_btn" => true);
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => 0, "parent_name" => "0", "no_back_btn" => true));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;

                case "get-disclaimers" :
                    $data = array();

                    if (isset($_GET["trigger_type"]) && ($tmp_input = clean_input($_GET["trigger_type"], array("notags", "trim")))) {
                        $trigger_type = $tmp_input;
                    }

                    if (isset($_GET["trigger_value"]) && ($tmp_input = clean_input($_GET["trigger_value"], array("int")))) {
                        $trigger_value = (int) $tmp_input;
                    }

                    $disclaimers = Models_Disclaimers::getUserDisclaimerByProxyIDOrganisationID($ENTRADA_USER->getID(), $ORGANISATION_ID);
                    if ($disclaimers) {
                        foreach ($disclaimers as $disclaimer) {
                            switch ($disclaimer["trigger_type"]) {
                                case "course":
                                    if ($trigger_type) {
                                        if ($trigger_type == "community") {
                                            if ($trigger_value) {
                                                $courses = Models_Community_Course::fetchAllByCommunityID($trigger_value);
                                                if ($courses) {
                                                    foreach ($courses as $course) {
                                                        $course_id = $course->getCourseID();
                                                        if ($disclaimer_trigger = Models_Disclaimer_Trigger::fetchRowByDisclaimerIDTriggerTypeTriggerValue($disclaimer["disclaimer_id"], "course", $course_id)) {
                                                            $data[] = $disclaimer;
                                                        }
                                                    }
                                                }
                                            }
                                        } else if ($trigger_type == "course") {
                                            if ($disclaimer_trigger = Models_Disclaimer_Trigger::fetchRowByDisclaimerIDTriggerTypeTriggerValue($disclaimer["disclaimer_id"], $trigger_type, $trigger_value)) {
                                                $data[] = $disclaimer;
                                            }
                                        } else if ($trigger_type == "event") {
                                            if ($trigger_value) {
                                                $event = Models_Event::fetchRowByID($trigger_value);
                                                if ($event) {
                                                    $course_id = $event->getCourseID();
                                                    if ($disclaimer_trigger = Models_Disclaimer_Trigger::fetchRowByDisclaimerIDTriggerTypeTriggerValue($disclaimer["disclaimer_id"], "course", $course_id)) {
                                                        $data[] = $disclaimer;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    break;
                                case "community":
                                    if ($trigger_type && $trigger_value) {
                                        if ($disclaimer_trigger = Models_Disclaimer_Trigger::fetchRowByDisclaimerIDTriggerTypeTriggerValue($disclaimer["disclaimer_id"], $trigger_type, $trigger_value)) {
                                            $data[] = $disclaimer;
                                        }
                                    }
                                    break;
                                default:
                                    $data[] = $disclaimer;
                                    break;
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "accept_btn" => $translate->_("Accept"), "decline_btn" => $translate->_("Decline"), "trigger_type" => $trigger_type, "trigger_value" => $trigger_value));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No User Disclaimers were found.")));
                    }
                    break;
            }
            break;

        case "POST":
            switch ($request["method"]) {
                case "delete-disclaimer" :
                    $data = array();

                    if (isset($request["disclaimer_id"]) && $tmp_input = clean_input($request["disclaimer_id"], "int")) {
                        $PROCESSED["disclaimer_id"] = $tmp_input;
                    } else {
                        add_error("Invalid Disclaimer Id: " . $request["disclaimer_id"]);
                    }
                    if (!$ERROR) {
                        $disclaimer = Models_Disclaimers::fetchRowByID($PROCESSED["disclaimer_id"]);
                        if ($disclaimer) {
                            if ($disclaimer->delete()) {
                                add_statistic("disclaimers", "delete", "disclaimer_id", $PROCESSED["disclaimer_id"], $ENTRADA_USER->getID());
                                application_log("success", "User Disclaimer has been deleted. User Disclaimer ID: " . $PROCESSED["disclaimer_id"]);
                                echo json_encode(array("status" => "success", "data" => $disclaimer->toArray()));
                            } else {
                                application_log("error", "Failed to delete this User Disclaimer. Disclaimer ID: " . $PROCESSED["disclaimer_id"]);
                                echo json_encode(array("status" => "error", "data" => $translate->_("Failed to delete this User Disclaimer")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => sprintf($translate->_("Unable to find user disclaimer. Disclaimer ID: %d"), $PROCESSED["disclaimer_id"])));
                        }
                    } else {
                        application_log("error", $ERRORSTR);
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;

                case "approve-disclaimer" :
                    $data = array();

                    if (isset($request["disclaimer_id"]) && $tmp_input = clean_input($request["disclaimer_id"], "int")) {
                        $PROCESSED["disclaimer_id"] = $tmp_input;
                    } else {
                        add_error("Invalid Disclaimer Id: " . $request["disclaimer_id"]);
                    }
                    if (!$ERROR) {
                        $disclaimer = Models_Disclaimers::fetchRowByID($PROCESSED["disclaimer_id"]);
                        if ($disclaimer) {
                            $disclaimer_audience_user = Models_Disclaimer_Audience_Users::fetchRowByDisclaimerIDProxyID($PROCESSED["disclaimer_id"], $ENTRADA_USER->getID());
                            if (!$disclaimer_audience_user) {
                                $disclaimer_audience_user = new Models_Disclaimer_Audience_Users(array(
                                    "disclaimer_id" => $PROCESSED["disclaimer_id"],
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "approved" => 1,
                                    "updated_date" => time(),
                                    "updated_by" => $ENTRADA_USER->getID()
                                ));

                                if ($disclaimer_audience_user->insert()) {
                                    add_statistic("disclaimers", "approve", "disclaimer_id", $PROCESSED["disclaimer_id"], $ENTRADA_USER->getID());
                                    application_log("success", "The user with ID " . $ENTRADA_USER->getID() . " has approved User Disclaimer ID: " . $PROCESSED["disclaimer_id"]);
                                    echo json_encode(array("status" => "success", "data" => $disclaimer->toArray()));
                                } else {
                                    add_error($translate->_("Failed to decline User Disclaimer"));
                                    application_log("error", $ERRORSTR);
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            } else {
                                if (($disclaimer->getUponDecline() == "log_out" || $disclaimer->getUponDecline() == "deny_access") && $disclaimer_audience_user->getApproved() == 0) {
                                    $disclaimer_audience_user = new Models_Disclaimer_Audience_Users(array(
                                        "disclaimer_audience_users_id" => $disclaimer_audience_user->getID(),
                                        "disclaimer_id" => $PROCESSED["disclaimer_id"],
                                        "proxy_id" => $ENTRADA_USER->getID(),
                                        "approved" => 1,
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->getID()
                                    ));

                                    if ($disclaimer_audience_user->update()) {
                                        add_statistic("disclaimers", "approve", "disclaimer_id", $PROCESSED["disclaimer_id"], $ENTRADA_USER->getID());
                                        application_log("success", "The user with ID " . $ENTRADA_USER->getID() . " has approved User Disclaimer ID: " . $PROCESSED["disclaimer_id"]);
                                        echo json_encode(array("status" => "success", "data" => $disclaimer->toArray()));
                                    } else {
                                        add_error($translate->_("Failed to decline User Disclaimer"));
                                        application_log("error", $ERRORSTR);
                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                    }
                                } else {
                                    add_error($translate->_("This User Disclaimer has been already approved."));
                                    application_log("error", $ERRORSTR);
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            }
                        } else {
                            add_error($translate->_("Failed to get the user disclaimer information"));
                            application_log("error", $ERRORSTR . "Disclaimer Id: " . $request["disclaimer_id"]);
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        application_log("error", $ERRORSTR);
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;

                case "decline-disclaimer" :
                    $data = array();

                    if (isset($request["disclaimer_id"]) && $tmp_input = clean_input($request["disclaimer_id"], "int")) {
                        $PROCESSED["disclaimer_id"] = $tmp_input;
                    } else {
                        add_error("Invalid Disclaimer Id: " . $request["disclaimer_id"]);
                    }
                    if (!$ERROR) {
                        $disclaimer = Models_Disclaimers::fetchRowByID($PROCESSED["disclaimer_id"]);
                        if ($disclaimer) {
                            $email_admin = $disclaimer->getEmailAdmin();
                            $disclaimer_audience_user = Models_Disclaimer_Audience_Users::fetchRowByDisclaimerIDProxyID($PROCESSED["disclaimer_id"], $ENTRADA_USER->getID());
                            if (!$disclaimer_audience_user) {
                                $disclaimer_audience_user = new Models_Disclaimer_Audience_Users(array(
                                    "disclaimer_id" => $PROCESSED["disclaimer_id"],
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "approved" => 0,
                                    "updated_date" => time(),
                                    "updated_by" => $ENTRADA_USER->getID()
                                ));

                                if ($disclaimer_audience_user->insert()) {
                                    if ($email_admin && $email_admin == 1) {
                                        $template = simplexml_load_file($ENTRADA_TEMPLATE->absolute() . "/email/notification-user-disclaimers.xml");

                                        $mail = new Zend_Mail();
                                        $mail->setType(Zend_Mime::MULTIPART_RELATED);
                                        $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                                        $mail->setSubject($template->template->subject);

                                        $message_search = array("%disclaimer_title%", "%user_fullname%", "%user_email%", "%creator_name%", "%creator_email%");
                                        $message_replace = array($disclaimer->getDisclaimerTitle(), $ENTRADA_USER->getFirstname() . " " . $ENTRADA_USER->getLastname(), $ENTRADA_USER->getEmail(), $AGENT_CONTACTS["administrator"]["name"], $AGENT_CONTACTS["administrator"]["email"]);

                                        $final_message = str_ireplace($message_search, $message_replace, $template->template->body);

                                        $mail->setBodyText($final_message);
                                        $disclaimer_user = Models_User::fetchRowByID($disclaimer->getCreatedBy());
                                        $mail->addTo($disclaimer_user->getEmail(), $disclaimer_user->getFullname());


                                        if (!$mail->send()) {
                                            application_log("error", $translate->_("E-mail notice failed to send.") . $mail->Er);
                                        }
                                    }
                                    add_statistic("disclaimers", "decline", "disclaimer_id", $PROCESSED["disclaimer_id"], $ENTRADA_USER->getID());
                                    application_log("success", "The user with ID " . $ENTRADA_USER->getID() . " has declined User Disclaimer ID: " . $PROCESSED["disclaimer_id"]);
                                    echo json_encode(array("status" => "success", "data" => $disclaimer->toArray()));
                                } else {
                                    add_error($translate->_("Failed to decline User Disclaimer"));
                                    application_log("error", $ERRORSTR);
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            } else {
                                if (($disclaimer->getUponDecline() == "log_out" || $disclaimer->getUponDecline() == "deny_access") && $disclaimer_audience_user->getApproved() == 0) {
                                    echo json_encode(array("status" => "success", "data" => $disclaimer->toArray()));
                                } else {
                                    add_error($translate->_("This User Disclaimer has been already declined."));
                                    application_log("error", $ERRORSTR);
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            }
                        } else {
                            add_error($translate->_("Failed to get the user disclaimer information"));
                            application_log("error", $ERRORSTR . "Disclaimer Id: " . $request["disclaimer_id"]);
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        application_log("error", $ERRORSTR);
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
            }
            break;

    }
    exit;
}