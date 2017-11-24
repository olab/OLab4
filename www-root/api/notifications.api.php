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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    if (isset($_GET["nuser_id"]) && ($nuser_id = clean_input($_GET["nuser_id"], array("int")))) {
        require_once("Classes/notifications/NotificationUser.class.php");
        $notification_user = NotificationUser::getByID($nuser_id);
        if ($notification_user->getProxyID() == $ENTRADA_USER->getID()) {
            if (isset($_GET["action"]) && $_GET["action"] == "view") {
                echo "<span style=\"cursor: pointer;\" onclick=\"promptNotifications(".($notification_user->getNotifyActive() ? "'1'" : "'0'").", ".$nuser_id.", '".$notification_user->getContentTypeName()."')\"><img src=\"".ENTRADA_URL."/images/btn-".($notification_user->getNotifyActive() ? "approve.gif\" alt=\"Active\" />" : "unapprove.gif\" alt=\"Disabled\" />")."</span>";
            } elseif (isset($_GET["action"]) && $_GET["action"] == "edit") {
                if (isset($_GET["active"]) && $_GET["active"]) {
                    $notify_active = 1;
                } else {
                    $notify_active = 0;
                }
                if ($notification_user->setNotifyActive($notify_active)) {
                    echo ($notify_active == 1 ? "Activation" : "Deactivation")." of notifications for this ".$notification_user->getContentTypeName()." successful.";
                } else {
                    echo "There was an issue while trying to ".($notify_active ? "activate" : "deactivate")." notifications for this ".$notification_user->getContentTypeName().".";
                }
            } elseif (isset($_GET["action"]) && $_GET["action"] == "view-digest") {
                echo "<span style=\"cursor: pointer;\" onclick=\"promptNotificationsDigest(".($notification_user->getDigestMode() ? "'0'" : "'1'").", ".$nuser_id.", '".$notification_user->getContentTypeName()."')\"><img src=\"".ENTRADA_URL."/images/btn-".($notification_user->getDigestMode() ? "approve.gif\" alt=\"Active\" />" : "unapprove.gif\" alt=\"Disabled\" />")."</span>";
            } elseif (isset($_GET["action"]) && $_GET["action"] = "edit-digest") {
                if (isset($_GET["active"]) && $_GET["active"]) {
                    $digest_active = 1;
                } else {
                    $digest_active = 0;
                }
                if ($notification_user->setDigestMode($digest_active)) {
                    echo ($digest_active == 1 ? "Enabling" : "Disabling")." of digest mode for this ".$notification_user->getContentTypeName()." successful.";
                } else {
                    echo "There was an issue while trying to ".($notify_active ? "enable" : "disable")." digest mode for this ".$notification_user->getContentTypeName().".";
                }
            }
        } else {
            echo "There was an issue while trying to fetch the notification and digest status for this content.";
        }
    } elseif (isset($_GET["community_id"]) && ($community_id = clean_input($_GET["community_id"], array("int")))) {
        if (isset($_GET["action"]) && $_GET["action"] == "view") {
            if ((isset($_GET["type"]) && ($notify_type = clean_input($_GET["type"], array("string", "nows"))))
					&& (isset($_GET["id"]) && ($record_id = clean_input($_GET["id"], array("int"))))
			) {
                $active = $db->GetOne("SELECT `notify_active` FROM `community_notify_members` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `community_id` = ".$db->qstr($community_id)." AND `record_id` = ".$db->qstr($record_id)." AND `notify_type` = ".$db->qstr($notify_type));
                if ($active == null && ($notify_type == "announcements" || $notify_type == "events")) {
                    $active = true;
                }
                echo "<a href=\"#\" onclick=\"promptNotifications(".(isset($active) && $active == 1 ? "'1'" : "'0'").")\" title=\"".(isset($active) && $active == 1 ? "Unsubscribe from E-Mail Notifications" : "Subscribe to E-Mail Notifications")."\"><i class=\"fa ".(isset($active) && $active == 1 ? "fa-envelope" : "fa-envelope-o")." fa-lg fa-fw\"></i></a>";


            }
        } elseif (isset($_GET["action"]) && $_GET["action"] == "edit") {
            if ((isset($_GET["type"]) && ($notify_type = clean_input($_GET["type"], array("string", "nows"))))
                && (isset($_GET["id"]) && ($record_id = clean_input($_GET["id"], array("int"))))
					&& (isset($_GET["community_id"]) && ($community_id = clean_input($_GET["community_id"], array("int"))))
			) {
                if (isset($_GET["active"]) && $_GET["active"]) {
                    $notify_active = 1;
                } else {
                    $notify_active = 0;
                }
                $current_notify = $db->GetOne("SELECT `proxy_id` FROM `community_notify_members` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `community_id` = ".$db->qstr($community_id)." AND `record_id` = ".$db->qstr($record_id)." AND `notify_type` = ".$db->qstr($notify_type));
                if ($current_notify) {
                    if ($db->Execute("UPDATE `community_notify_members` SET `notify_active` = ".$db->qstr($notify_active)." WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `community_id` = ".$db->qstr($community_id)." AND `record_id` = ".$db->qstr($record_id)." AND `notify_type` = ".$db->qstr($notify_type))) {
                        echo ($notify_active == 1 ? "Activation" : "Deactivation")." of notifications for this resource successful.";
                    } else {
                        echo "There was an issue while trying to ".($notify_active ? "activate" : "deactivate")." notifications for this resource.";
                    }
                } else {
                    if ($db->Execute("INSERT INTO `community_notify_members` (`notify_active`, `proxy_id`, `community_id`, `record_id`, `notify_type`) VALUES (".$db->qstr($notify_active).", ".$db->qstr($ENTRADA_USER->getID()).", ".$db->qstr($community_id).", ".$db->qstr($record_id).", ".$db->qstr($notify_type).")")) {
                        echo ($notify_active == 1 ? "Activation" : "Deactivation")." of notifications for this resource successful.";
                    } else {
                        echo "There was an issue while trying to ".($notify_active ? "activate" : "deactivate")." notifications for this resource.";
                    }
                }
            }
        }
    } elseif (isset($_GET["record_id"]) && ($record_id = clean_input($_GET["record_id"], array("int")))) {
        require_once("Classes/notifications/NotificationUser.class.php");
        if (isset($_GET["action"]) && $_GET["action"] == "view") {
            if (isset($_GET["record_proxy_id"]) && $_GET["record_proxy_id"]) {
                $record_proxy_id = clean_input($_GET["record_proxy_id"], array("int"));
            } else {
                $record_proxy_id = 0;
            }
            if (isset($_GET["content_type"]) && $_GET["content_type"]) {
                $content_type = clean_input($_GET["content_type"], "module");
            } else {
                $content_type = "default";
            }
            $notification_user = NotificationUser::get($ENTRADA_USER->getID(), $content_type, $record_id, $record_proxy_id);
            if (!$notification_user || $notification_user->getProxyID() == $ENTRADA_USER->getID()) {
                if ($notification_user && $notification_user->getNotifyActive()) {
                    ?>
                    <i class="fa fa-envelope"></i> <a href="javascript:promptNotifications('1')">Unsubscribe from E-Mail Notifications</a>
                    <?php
                } else {
                    ?>
                    <i class="fa fa-envelope-o"></i> <a href="javascript:promptNotifications('0')">Subscribe to E-Mail Notifications</a>
                    <?php
                }
            }
        } elseif (isset($_GET["action"]) && $_GET["action"] == "edit") {
            if (isset($_GET["active"]) && $_GET["active"]) {
                $notify_active = 1;
            } else {
                $notify_active = 0;
            }
            if (isset($_GET["digest_mode"]) && ($_GET["digest_mode"] === "1" ||$_GET["digest_mode"] === "0")) {
                $digest_mode = $_GET["digest_mode"];
            } else {
                $digest_mode = 0;
            }
            if (isset($_GET["record_proxy_id"]) && $_GET["record_proxy_id"]) {
                $record_proxy_id = clean_input($_GET["record_proxy_id"], array("int"));
            } else {
                $record_proxy_id = 0;
            }
            if (isset($_GET["content_type"]) && $_GET["content_type"]) {
                $content_type = clean_input($_GET["content_type"], "module");
            } else {
                $content_type = "default";
            }
            $notification_user = NotificationUser::get($ENTRADA_USER->getID(), $content_type, $record_id, $record_proxy_id);
            if ($notification_user && $notification_user->getProxyID() == $ENTRADA_USER->getID()) {
                if ($notification_user->getNotifyActive() != $notify_active) {
                    if ($notification_user->setNotifyActive($notify_active)) {
                        echo ($notify_active == 1 ? "Activation" : "Deactivation")." of notifications for this ".$notification_user->getContentTypeName()." successful.";
                    } else {
                        echo "There was an issue while trying to ".($notify_active ? "activate" : "deactivate")." notifications for this ".$notification_user->getContentTypeName().".";
                    }
                } elseif ($notification_user->getDigestMode() != $digest_mode) {
                    if ($notification_user->setDigestMode($digest_mode)) {
                        echo ($digest_mode == 1 ? "Activation" : "Deactivation")." of digest mode for notifications regarding this ".$notification_user->getContentTypeName()." successful.";
                    } else {
                        echo "There was an issue while trying to ".($digest_mode ? "activate" : "deactivate")." digest mode for notifications regarding this ".$notification_user->getContentTypeName().".";
                    }
                } else {
                    echo "Notifications for this ".$notification_user->getContentTypeName()." are already ".($notify_active ? "activated" : "deactivated")." and digest mode is already ".($digest_mode ? "activated" : "deactivated").", no changes were made.";
                }
            } else {
                $notification_user = NotificationUser::add($ENTRADA_USER->getID(), $content_type, $record_id, $record_proxy_id, $notify_active, $digest_mode);
                if ($notification_user) {
                    echo ($notify_active == 1 ? "Activation" : "Deactivation")." of notifications for this ".$notification_user->getContentTypeName()." successful.";
                } else {
                    echo "There was an issue while trying to ".($notify_active ? "activate" : "deactivate")." notifications for this ".$notification_user->getContentTypeName().".";
                }
            }
        }
    }
}
