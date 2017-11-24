<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for checking google mail-list vs local database list and fixing any issues.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
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
/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
$db->debug = true;

if (isset($MAILING_LISTS) && is_array($MAILING_LISTS) && $MAILING_LISTS["active"]) {
    require_once("Entrada/mail-list/mail-list.class.php");

    if ((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
        if (!file_exists(COMMUNITY_MAIL_LIST_CLEANUP_LOCK)) {
            if (@file_put_contents(COMMUNITY_MAIL_LIST_CLEANUP_LOCK, "L_O_C_K")) {

                $query = "SELECT *
                            FROM `community_mailing_lists`
                            WHERE `list_type` != 'inactive'
                            AND `community_id` = 1
                            ORDER BY `last_checked` ASC
                            LIMIT 10";
                if ($lists = $db->GetAll($query)) {
                    $count = 0;
                    foreach ($lists as $list) {
                        $query = "SELECT a.*, b.`cmlmember_id`, b.`member_active` as `list_member_active`, b.`list_administrator`, c.`email` FROM `community_members` AS a
                                    LEFT JOIN `community_mailing_list_members` AS b
                                    ON a.`community_id` = b.`community_id`
                                    AND a.`proxy_id` = b.`proxy_id`
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS c
                                    ON a.`proxy_id` = c.`id`
                                    WHERE a.`community_id` = ".$db->qstr($list["community_id"])."
                                    AND c.`email` LIKE '%@%'
                                    GROUP BY a.`community_id`, a.`proxy_id`
                                    UNION
                                    SELECT b.*, a.`cmlmember_id`, a.`member_active` as `list_member_active`, a.`list_administrator`, c.`email` FROM `community_mailing_list_members` AS a
                                    LEFT JOIN `community_members` AS b
                                    ON a.`community_id` = b.`community_id`
                                    AND a.`proxy_id` = b.`proxy_id`
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS c
                                    ON a.`proxy_id` = c.`id`
                                    WHERE a.`community_id` = ".$db->qstr($list["community_id"])."
                                    AND c.`email` LIKE '%@%'
                                    GROUP BY a.`community_id`, a.`proxy_id`";
                        $community_members = $db->GetAll($query);
                        foreach ($community_members as $community_member) {
                            $PROCESSED_MAIL_LIST_MEMBER = array();
                            if ($community_member["cmlmember_id"]) {
                                if ($community_member["member_active"] && $community_member["member_acl"] && !$community_member["list_administrator"]) {
                                    $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = ($community_member["list_member_active"] ? 2 : 1);
                                } elseif (!$community_member["member_acl"] && $community_member["list_administrator"]) {
                                    $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = -1;
                                }

                                if ($community_member["member_active"] && is_null($community_member["list_member_active"])) {
                                    $PROCESSED_MAIL_LIST_MEMBER["member_active"] = 0;
                                } elseif (!$community_member["member_active"] && $community_member["list_member_active"]) {
                                    $PROCESSED_MAIL_LIST_MEMBER["member_active"] = -1;
                                }
                                if (!empty($PROCESSED_MAIL_LIST_MEMBER)) {
                                    if (!$db->AutoExecute("`community_mailing_list_members`", $PROCESSED_MAIL_LIST_MEMBER, "UPDATE", "`cmlmember_id` = ".$db->qstr($community_member["cmlmember_id"]))) {
                                        application_log("error", "Unable to update `community_mailing_list_members` record. DB said: ".$db->ErrorMsg());
                                    }
                                }
                            } else {
                                if ($community_member["member_active"]) {
                                    $PROCESSED_MAIL_LIST_MEMBER["community_id"] = $community_member["community_id"];
                                    $PROCESSED_MAIL_LIST_MEMBER["proxy_id"] = $community_member["proxy_id"];
                                    $PROCESSED_MAIL_LIST_MEMBER["email"] = $community_member["email"];
                                    $PROCESSED_MAIL_LIST_MEMBER["member_active"] = 0;
                                    $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = ($community_member["member_acl"] ? 1 : 0);
                                    if (!$db->AutoExecute("`community_mailing_list_members`", $PROCESSED_MAIL_LIST_MEMBER, "INSERT")) {
                                        application_log("error", "Unable to insert new `community_mailing_list_members` record. DB said: ".$db->ErrorMsg());
                                    }
                                }
                            }
                        }
                        $google_list = new MailingList($list["community_id"]);
                        $google_list->fetch_current_list();
                        $google_list_members = $google_list->current_members;
                        $google_list_owners = $google_list->current_owners;
                        $query = "SELECT * FROM `community_mailing_list_members`
                                    WHERE `community_id` = ".$db->qstr($list["community_id"]);
                        $local_list_members = $db->GetAll($query);
                        if ($local_list_members) {
                            foreach ($local_list_members as $member) {
                                $add_member = false;
                                $set_owner = false;
                                $PROCESSED_MAIL_LIST_MEMBER = array();
                                $PROCESSED_MAIL_LIST_MEMBER["community_id"] = $member["community_id"];
                                $PROCESSED_MAIL_LIST_MEMBER["proxy_id"] = $member["proxy_id"];
                                $PROCESSED_MAIL_LIST_MEMBER["email"] = $member["email"];
                                $PROCESSED_MAIL_LIST_MEMBER["member_active"] = $member["member_active"];
                                $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = $member["list_administrator"];
                                if ($member["member_active"] == 1) {
                                    if (!in_array($member["email"], $google_list_members)) {
                                        $PROCESSED_MAIL_LIST_MEMBER["member_active"] = 0;
                                    }
                                    if ($member["list_administrator"] == 1) {
                                        if (!in_array($member["email"], $google_list_owners)) {
                                            $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = 2;
                                        } else {
                                            $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = 1;
                                        }
                                    } else {
                                        if (in_array($member["email"], $google_list_owners)) {
                                            $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = -1;
                                        } else {
                                            $PROCESSED_MAIL_LIST_MEMBER["list_administrator"] = 0;
                                        }
                                    }
                                } else {
                                    if (in_array($member["email"], $google_list_members)) {
                                        $PROCESSED_MAIL_LIST_MEMBER["member_active"] = -1;
                                    }
                                }
                                if (!$db->AutoExecute("`community_mailing_list_members`", $PROCESSED_MAIL_LIST_MEMBER, "UPDATE", "`cmlmember_id` = ".$db->qstr($member["cmlmember_id"]))) {
                                    application_log("error", "Unable to update `community_mailing_list_members` record. DB said: ".$db->ErrorMsg());
                                }
                            }
                        }
                        $query = "UPDATE `community_mailing_lists` SET `last_checked` = ".$db->qstr(time())." WHERE `community_id` = ".$db->qstr($list["community_id"]);
                        if (!$db->Execute($query)) {
                            application_log("error", "Unable to update the last_checked date for a `community_mailing_lists` record. DB said: ".$db->ErrorMsg());
                        }
                    }
                } else {
                    application_log("notice", "An issue occured when attempting mailing list cleanup. No active mailing lists found. DB said: ".$db->ErrorMsg());
                }

                if (!@unlink(COMMUNITY_MAIL_LIST_CLEANUP_LOCK)) {
                    application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_CLEANUP_LOCK);
                }

            } else {
                application_log("error", "Unable to open mail-list cleanup lock file: ".COMMUNITY_MAIL_LIST_CLEANUP_LOCK);
            }
        } else {
            /**
             * Found old lock file get rid of it
             */
            if (1 || filemtime(COMMUNITY_MAIL_LIST_CLEANUP_LOCK) < time() - COMMUNITY_MAIL_LIST_CLEANUP_TIMEOUT ) {
                if (!@unlink(COMMUNITY_MAIL_LIST_CLEANUP_LOCK)) {
                    application_log("error", "Unable to delete mail-list cleanup lock file: ".COMMUNITY_MAIL_LIST_CLEANUP_LOCK);
                }
            }
        }
    } else {
        application_log("error", "The specified CACHE_DIRECTORY [".CACHE_DIRECTORY."] either does not exist or is not writable.");
    }
}