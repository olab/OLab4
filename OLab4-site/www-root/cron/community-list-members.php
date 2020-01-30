<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for adding users to the google mail-list.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
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
ini_set("display_errors", 1);
if (isset($MAILING_LISTS) && is_array($MAILING_LISTS) && $MAILING_LISTS["active"]) {
	require_once("Entrada/mail-list/mail-list.class.php");

	if ((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
		/**
		 * Lock present: application busy: quit
		 */

		if (!file_exists(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
			if (@file_put_contents(COMMUNITY_MAIL_LIST_MEMBERS_LOCK, "L_O_C_K")) {
				try {
					$limit = COMMUNITY_MAIL_LIST_MEMBERS_LIMIT;
					$community_id = 0;
					$query		=  "	SELECT a.*, b.`list_type` FROM `community_mailing_list_members` AS a
										JOIN `community_mailing_lists` AS b
										ON a.`community_id` = b.`community_id`
										WHERE a.`member_active` < 0
										ORDER BY a.`community_id` ASC";

					if ($members = $db->GetAll($query)) {
						foreach ($members as $member) {
							if ($community_id != (int) $member["community_id"]) {
								$community_id = (int) $member["community_id"];

								$list = new MailingList($community_id, $member["list_type"]);
								$list->fetch_current_list();
								$current_users = array_merge($list->current_owners, $list->current_members);
							}

							echo "Delete: ".$member["email"]." -> ".$community_id."<br />";

							if (@in_array($member["email"], $current_users)) {
								try {
									if ($list->remove($member["email"])) {
										$list->base_remove_member($member["proxy_id"]);
									}
								} catch (Exception $e) {
									echo $e->getCode()." - ".$e->getMessage();
									if ($e->getCode() == "502") {
										exit;
									}
									application_log("notice", "An issue was encountered while attempting to remove a user's email [".$member["email"]."] from a community [".$community_id."] mailing list. The server said: ".$e->getCode()." - ".$e->getMessage());
								}
							} else {
								$list->base_remove_member($member["proxy_id"]);
								application_log("notice", "An issue was encountered while attempting to remove a user's email [".$member["email"]."] from a community [".$community_id."] mailing list. The member was not present in the list.");
							}

							/**
							 * Email limit so exit
							 */
							if (!(--$limit)) {
								if (!@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
									application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
								}
								exit;
							}
						}
					}

					$community_id = 0;
					$query		=  "	SELECT a.* FROM `community_mailing_list_members` AS a
										JOIN `community_mailing_lists` AS b
										ON a.`community_id` = b.`community_id`
										WHERE a.`member_active` = 0
										AND b.`list_type` != 'inactive'
										ORDER BY a.`community_id` ASC";

					if ($members = $db->GetAll($query)) {
						foreach ($members as $member) {
							if ($community_id != (int) $member["community_id"]) {
								$community_id = (int) $member["community_id"];

								$list = new MailingList($community_id);
								$list->fetch_current_list();
								$current_users = array_merge($list->current_owners, $list->current_members);
							}

							echo "Activate: ".$member["email"]." -> ".$community_id."<br/>";
							if (!in_array($member["email"], $current_users)) {
								try {
									$member["email"] = utf8_encode($member["email"]);
									if ($list->add($member["email"], 0)) {
										if ($member["list_administrator"]) {
											$list->add($member["email"], 1);
											$list->base_edit_member($member["proxy_id"], ((int)$member["list_administrator"]), "1");
										} else {
											$list->base_edit_member($member["proxy_id"], ((int)$member["list_administrator"]), "1");
										}
									}
								} catch (Exception $e) {
									echo $e->getMessage();
									if ($e->getCode() == "502") {
										exit;
									}
									application_log("notice", "An issue was encountered while attempting to add a user's email [".$member["email"]."] to a community [".$community_id."] mailing list. The server said: ".$e->getCode()." - ".$e->getMessage());
								}
							} else {
								$list->base_edit_member($member["proxy_id"], ((int)$member["list_administrator"]), "1");
								application_log("notice", "An issue was encountered while attempting to add a user's email [".$member["email"]."] to a community [".$community_id."] mailing list. The member was already present in the list.");
							}

							/**
							 * Email limit so exit
							 */
							if (!(--$limit)) {
								if (!@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
									application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
								}
								exit;
							}
						}
					}

					$community_id = 0;
					$query		=  "	SELECT a.* FROM `community_mailing_list_members` AS a
										JOIN `community_mailing_lists` AS b
										ON a.`community_id` = b.`community_id`
										WHERE a.`list_administrator` = '2'
										AND b.`list_type` != 'inactive'
										ORDER BY a.`community_id` ASC";
					if ($members = $db->GetAll($query)) {
						foreach ($members as $member) {
							if ($community_id != (int) $member["community_id"]) {
								$community_id = (int) $member["community_id"];

								$list = new MailingList($community_id);
								$list->fetch_current_list();
								$current_users = array_merge($list->current_owners, $list->current_members);
							}

							echo "Promote: ".$member["email"]." -> ".$community_id."<br/>";
							if (!in_array($member["email"], $current_users)) {
								try {
									if ($list->add($member["email"], 1)) {
										$list->base_edit_member($member["proxy_id"], "1", "1");
									}
								} catch (Exception $e) {
									echo $e->getMessage();
									if ($e->getCode() == "502") {
										exit;
									}
									application_log("notice", "An issue was encountered while attempting to promote a user's email [".$member["email"]."] to owner on a community [".$community_id."] mailing list. The server said: ".$e->getCode()." - ".$e->getMessage());
								}
							} else {
								try {
									// User already exists, add method will insert/update role accordingly.
									if ($list->add($member["email"], 1)) {
										$list->base_edit_member($member["proxy_id"], "1", "1");
									}
								} catch (Exception $e) {
									echo $e->getMessage();
									if ($e->getCode() == "502") {
										exit;
									}
									application_log("notice", "An issue was encountered while attempting to promote a user's email [".$member["email"]."] to owner on a community [".$community_id."] mailing list. The server said: ".$e->getCode()." - ".$e->getMessage());
								}
							}

							/**
							 * Email limit so exit
							 */
							if (!(--$limit)) {
								if (!@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
									application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
								}
								exit;
							}
						}
					}

					$community_id = 0;
					$query		=  "	SELECT a.* FROM `community_mailing_list_members` AS a
										JOIN `community_mailing_lists` AS b
										ON a.`community_id` = b.`community_id`
										WHERE a.`list_administrator` = '-1'
										AND a.`member_active` = '1'
										AND b.`list_type` != 'inactive'
										ORDER BY a.`community_id` ASC";

					if ($members = $db->GetAll($query)) {
						foreach ($members as $member) {
							if ($community_id != (int) $member["community_id"]) {
								$community_id = (int) $member["community_id"];

								$list = new MailingList($community_id);
								$list->fetch_current_list();
								$current_users = array_merge($list->current_owners, $list->current_members);
							}

							echo "Demote: ".$member["email"]." -> ".$community_id."<br/>";
							if (in_array($member["email"], $current_users)) {
								try {
									if ($list->remove($member["email"]) && $list->add($member["email"])) {
										$list->base_edit_member($member["proxy_id"], "0", "1");
									}
								} catch (Exception $e) {
									echo $e->getMessage();
									if ($e->getCode() == "502") {
										exit;
									}
									application_log("notice", "An issue was encountered while attempting to demote a user's email [".$member["email"]."] to owner on a community [".$community_id."] mailing list. The server said: ".$e->getCode()." - ".$e->getMessage());
								}
							} else {
								$list->base_edit_member($member["proxy_id"], "0", "1");
								application_log("notice", "An issue was encountered while attempting to demote a user's email [".$member["email"]."] to owner on a community [".$community_id."] mailing list. The user is not an owner.");
							}
							/**
							 * Email limit so exit
							 */
							if (!(--$limit)) {
								if (!@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
									application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
								}
								exit;
							}
						}
					}

					if (!@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
						application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
					}
				} catch (Exception $e) {
					@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
				}
			} else {
				application_log("error", "Unable to open mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
			}
		} else {
			/**
			 * Found old lock file get rid of it
			 */
			if (filemtime(COMMUNITY_MAIL_LIST_MEMBERS_LOCK) < time() - COMMUNITY_MAIL_LIST_MEMBERS_TIMEOUT ) {
				if (!@unlink(COMMUNITY_MAIL_LIST_MEMBERS_LOCK)) {
					application_log("error", "Unable to delete mail-list member lock file: ".COMMUNITY_MAIL_LIST_MEMBERS_LOCK);
				}
			}
		}
	} else {
		application_log("error", "The specified CACHE_DIRECTORY [".CACHE_DIRECTORY."] either does not exist or is not writable.");
	}
}


?>