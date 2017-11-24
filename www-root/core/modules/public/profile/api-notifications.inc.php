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
 * API to handle interaction with the Assessment Item Bank.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("profile", "read", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }

    if (!$ERROR) {
        require_once("Classes/notifications/NotificationUser.class.php");
        switch ($request_method) {
            case "GET" :
                switch ($method) {
                    case "get-active-notifications" :

                        $column_check_array = array ("title");
                        $direction_check_array = array ("asc", "desc");

                        if (isset($request["proxy_id"]) && $tmp_input = clean_input(strtolower($request["proxy_id"]), array("trim", "int"))) {
                            $PROCESSED["proxy_id"] = $tmp_input;
                        } else {
                            $PROCESSED["proxy_id"] = 0;
                        }

                        if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                            $PROCESSED["search_term"] = "%".$tmp_input."%";
                        } else {
                            $PROCESSED["search_term"] = "";
                        }

                        if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = 25;
                        }

                        if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                            $PROCESSED["offset"] = $tmp_input;
                        } else {
                            $PROCESSED["offset"] = 0;
                        }

                        if (isset($request["col"]) && $tmp_input = clean_input(strtolower($request["col"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$column_check_array)) {
                                $PROCESSED["sort_column"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_column"] = "title";
                            }
                        } else {
                            $PROCESSED["sort_column"] = "title";
                        }

                        if (isset($request["ord"]) && $tmp_input = clean_input(strtolower($request["ord"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$direction_check_array)) {
                                $PROCESSED["sort_direction"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_direction"] = "asc";
                            }
                        } else {
                            $PROCESSED["sort_direction"] = "asc";
                        }

                        if ($ENTRADA_ACL->amIAllowed('course', 'update', false) && $PROCESSED["proxy_id"]) {
                            $active_notifications = NotificationUser::getAllActiveNotifications($PROCESSED["proxy_id"], 1, $PROCESSED["search_term"],$PROCESSED["offset"], $PROCESSED["limit"], $PROCESSED["sort_column"], $PROCESSED["sort_direction"]);
                            $total = NotificationUser::getTotalAllActiveNotifications($PROCESSED["proxy_id"], 1, $PROCESSED["search_term"]);
                        }

                        if ($active_notifications) {
                            $data = array();
                            foreach ($active_notifications as $active_notification) {

                                $active_notification_object = NotificationUser::fromArray($active_notification);

                                $notification_data = array(
                                    "notification_id"           => $active_notification["nuser_id"],
                                    "notification_title"         => ucwords($active_notification_object->getContentTitle())." (". ucwords($active_notification_object->getContentTypeName()) .")",
                                    "notification_url"         => $active_notification_object->getContentURL(),
                                    "digest_mode"         => ($active_notification_object->getContentType() == "logbook_rotation" ? "N/A" : $active_notification["digest_mode"]),
                                );

                                $data[] = $notification_data;
                            }
                            echo json_encode(array("results" => count($active_notifications), "data" => array("total_notifications" => $total["total_rows"], "notifications" => $data)));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No Notifications Found."))));
                        }
                    break;
                    case "get-community-notifications" :

                        $column_check_array = array ("title");
                        $direction_check_array = array ("asc", "desc");

                        if (isset($request["proxy_id"]) && $tmp_input = clean_input(strtolower($request["proxy_id"]), array("trim", "int"))) {
                            $PROCESSED["proxy_id"] = $tmp_input;
                        } else {
                            $PROCESSED["proxy_id"] = 0;
                        }

                        if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                            $PROCESSED["search_term"] = "%".$tmp_input."%";
                        } else {
                            $PROCESSED["search_term"] = "";
                        }

                        if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = 25;
                        }

                        if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                            $PROCESSED["offset"] = $tmp_input;
                        } else {
                            $PROCESSED["offset"] = 0;
                        }

                        if (isset($request["col"]) && $tmp_input = clean_input(strtolower($request["col"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$column_check_array)) {
                                $PROCESSED["sort_column"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_column"] = "title";
                            }
                        } else {
                            $PROCESSED["sort_column"] = "title";
                        }

                        if (isset($request["ord"]) && $tmp_input = clean_input(strtolower($request["ord"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$direction_check_array)) {
                                $PROCESSED["sort_direction"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_direction"] = "asc";
                            }
                        } else {
                            $PROCESSED["sort_direction"] = "asc";
                        }

                        if ($ENTRADA_ACL->amIAllowed('course', 'update', false) && $PROCESSED["proxy_id"]) {
                            $community_notifications = Models_Community_Notify_Member::getAllCommunityNotificationsByProxyID($PROCESSED["proxy_id"], $PROCESSED["search_term"],$PROCESSED["offset"], $PROCESSED["limit"], $PROCESSED["sort_column"], $PROCESSED["sort_direction"]);
                            $total = Models_Community_Notify_Member::getTotalCommunityNotificationsByProxyID($PROCESSED["proxy_id"], $PROCESSED["search_term"]);
                        }

                        if ($community_notifications) {
                            $data = array();
                            foreach ($community_notifications as $community_notification) {

                                $notification_data = array(
                                    "community_id"           => $community_notification["community_id"],
                                    "community_title"         => $community_notification["community_title"],
                                    "member_acl"         => $community_notification["member_acl"],
                                    "announcement"         => $community_notification["announcement"],
                                    "event"         => $community_notification["event"],
                                    "poll"         => $community_notification["poll"],
                                    "members"         => $community_notification["member"],
                                );

                                $data[] = $notification_data;
                            }
                            echo json_encode(array("results" => count($community_notifications), "data" => array("total_notifications" => $total["total_rows"], "notifications" => $data)));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No Notifications Found."))));
                        }
                    break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
                }
                break;
            case "POST" :
                switch ($method) {
                    case "delete-notifications" :
                        $PROCESSED["delete_ids"] = array();
                        if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                            foreach ($request["delete_ids"] as $course_id) {
                                $tmp_input = clean_input($course_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["delete_ids"][] = $tmp_input;
                                }
                            }
                        }

                        if (!empty($PROCESSED["delete_ids"])) {
                            $deleted_notifications = array();
                            foreach ($PROCESSED["delete_ids"] as $notification_id) {
                                $notification_user = NotificationUser::getByID($notification_id);
                                if ($notification_user) {
                                    if (!$notification_user->setNotifyActive(0)) {
                                        add_error($translate->_("Unable to delete an Active Notification"));
                                    } else {
                                        $ENTRADA_LOGGER->log("", "delete", "nuser_id", $notification_id, 4, __FILE__, $ENTRADA_USER->getID());
                                        $deleted_notifications[] = $notification_id;
                                    }
                                }
                            }
                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Notification(s)."), count($deleted_notifications)), "notification_ids" => $deleted_notifications));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete an Notification.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No notifications were selected for deletion.")));
                        }
                    break;

                    case "change-digest" :

                        if (isset($request["nuser_id"]) && $tmp_input = clean_input(strtolower($request["nuser_id"]), array("trim", "int"))) {
                            $PROCESSED["nuser_id"] = $tmp_input;
                        } else {
                            $PROCESSED["nuser_id"] = 0;
                        }

                        if (isset($request["digest"]) && $tmp_input = clean_input(strtolower($request["digest"]), array("trim", "int"))) {
                            $PROCESSED["digest"] = $tmp_input;
                        } else {
                            $PROCESSED["digest"] = 0;
                        }

                        if ($PROCESSED["nuser_id"]) {
                            $notification_user = NotificationUser::getByID($PROCESSED["nuser_id"]);
                            if ($notification_user) {
                                if (!$notification_user->setDigestMode($PROCESSED["digest"])) {
                                    add_error($translate->_("Unable to Update Digest Mode"));
                                } else {
                                    $ENTRADA_LOGGER->log("", "update", "nuser_id", $notification_id, 4, __FILE__, $ENTRADA_USER->getID());
                                }
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully updated digest mode for [%d] Notification(s)."), $PROCESSED["nuser_id"])));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to update Digest Mode.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No notifications were selected for update.")));
                        }
                    break;
                    case "change-community-notification" :

                        if (isset($request["community_id"]) && $tmp_input = clean_input(strtolower($request["community_id"]), array("trim", "int"))) {
                            $PROCESSED["community_id"] = $tmp_input;
                        } else {
                            $PROCESSED["community_id"] = 0;
                        }

                        if (isset($request["proxy_id"]) && $tmp_input = clean_input(strtolower($request["proxy_id"]), array("trim", "int"))) {
                            $PROCESSED["proxy_id"] = $tmp_input;
                        } else {
                            $PROCESSED["proxy_id"] = 0;
                        }

                        if (isset($request["value"]) && $tmp_input = clean_input(strtolower($request["value"]), array("trim", "int"))) {
                            $PROCESSED["value"] = $tmp_input;
                        } else {
                            $PROCESSED["value"] = 0;
                        }

                        if (isset($request["notify_type"]) && $tmp_input = clean_input(strtolower($request["notify_type"]), array("trim"))) {
                            $PROCESSED["notify_type"] = $tmp_input;
                        } else {
                            $PROCESSED["notify_type"] = null;
                        }


                        if ($PROCESSED["community_id"] && $PROCESSED["notify_type"] && $PROCESSED["proxy_id"]) {
                            $community_notification = Models_Community_Notify_Member::fetchRowByProxyIDCommunityIDNotifyType($PROCESSED["proxy_id"], $PROCESSED["community_id"], $PROCESSED["notify_type"]);

                            if ($community_notification) {
                                if (!$community_notification->fromArray(array("notify_active" => $PROCESSED["value"]))->update()) {
                                    add_error($translate->_("Unable to Update Community Notification"));
                                } else {
                                    $ENTRADA_LOGGER->log("", "update", "community_id", $community_id, 4, __FILE__, $ENTRADA_USER->getID());
                                }
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully updated %s mode for [%d] Notification(s)."), $PROCESSED["notify_type"], $PROCESSED["community_id"])));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to update Community Notification.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No notifications were selected for update.")));
                        }
                        break;
                }
                break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}