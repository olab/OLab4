<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Class responsible for managing google mail-list.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

abstract class MailingListBase
{

	var $users 			= array();

	var $list_name		= "";

	var $type			= "";

	var $community_id	= 0;

	public function MailingListBase($community_id = 0, $list_type = "inactive") {
		global $db;
		if ($community_id) {
			$query = "SELECT * FROM `community_mailing_lists` WHERE `community_id` = ".$db->qstr($community_id);
			$result = $db->GetRow($query);

			if ($result) {
				$this->list_name 	= $result["list_name"];
				$this->type 		= $result["list_type"];
				$this->community_id = $result["community_id"];
			} elseif (($list_type == "announcements") || ($list_type == "discussion") || ($list_type == "inactive")) {

				$community_query = "SELECT `community_shortname` FROM `communities` WHERE `community_id` = ".$db->qstr($community_id);

				$list_name = $db->GetOne($community_query);

				if ($list_name) {
					$list_name .= "-community";
					$this->community_id = $community_id;
					$query = "	INSERT INTO `community_mailing_lists`
								SET `list_name` = ".$db->qstr($list_name).",
									`community_id` = ".$db->qstr($community_id).",
									`list_type` = ".$db->qstr($list_type);
					if ($db->Execute($query)) {
						$this->list_name	= $list_name;
						$this->type			= $list_type;
					}
				}
			}

			$query = "	SELECT *
						FROM `community_mailing_list_members`
						WHERE `community_id` = ".$db->qstr($community_id);
			$users = $db->GetAll($query);

			if ($users) {
				foreach ($users as $user) {
					$this->users[$user["proxy_id"]] = Array(
						"proxy_id" => $user["proxy_id"],
						"email" => $user["email"],
						"owner" => (((int)$user["list_administrator"]) == 1 ? true : false),
						"member_active" => $user["member_active"]
					);
				}
			} else {

				$query = "SELECT b.`email`, b.`id`, a.`member_acl`
						  FROM `community_members` AS a
						  LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						  ON a.`proxy_id` = b.`id`
						  WHERE a.`community_id` = ".$db->qstr($community_id)."
						  AND a.`member_active` = 1";

				$users = $db->GetAll($query);
				$this->users = Array();
				foreach ($users as $user) {
					$this->users[$user["proxy_id"]] = Array(
						"proxy_id" => $user["id"],
						"email" => $user["email"],
						"owner" => (((int)$user["member_acl"]) ? true : false),
						"member_active" => 0
					);
					$this->base_add_member($user["id"], $user["email"], 0, $user["member_acl"]);
				}
			}
		}
	}

	public function base_mode_change($type) {
		global $db;
		if ($type == "inactive") {
			$this->type = $type;
			$query = "	UPDATE `community_mailing_list_members`
    					SET `member_active` = '-1'
    					WHERE `community_id` = ".$db->qstr($this->community_id);
			$db->Execute($query);
		}
		if ($type == "inactive" || $type == "discussion" || $type == "announcements") {
			$query = "	UPDATE `community_mailing_lists`
    					SET `list_type` = ".$db->qstr($type)."
    					WHERE `community_id` = ".$db->qstr($this->community_id);
			return (bool)$db->Execute($query);
		}
		return false;
	}

	public function base_remove_member($proxy_id) {
		global $db;

		$email = $this->users[$proxy_id]["email"];

		$query = "	DELETE FROM `community_mailing_list_members`
					WHERE `community_id` = ".$db->qstr($this->community_id)."
					AND `email` = ".$db->qstr($email);
		$result = $db->Execute($query);
		return $result;
	}

	public function base_add_member($proxy_id, $email, $member_active = 0, $is_admin = 0) {
		global $db;
		$result = $db->Execute("INSERT INTO `community_mailing_list_members`
								SET `proxy_id` = ".$db->qstr($proxy_id).",
								`email` = ".$db->qstr($email).",
								`member_active` = ".$db->qstr($member_active).",
								`list_administrator` = ".$db->qstr($is_admin).",
								`community_id` = ".$db->qstr($this->community_id));
		return $result;
	}

	public function base_edit_member($proxy_id, $is_admin = NULL, $member_active = NULL) {
		global $db;
		$result = $db->Execute("UPDATE `community_mailing_list_members`
									SET ".( $is_admin != NULL ? "`list_administrator` = ".$db->qstr($is_admin).", " : "")."
										".( $member_active != NULL ? "`member_active` = ".$db->qstr($member_active) : "")."
									WHERE `proxy_id` = ".$db->qstr($proxy_id)."
									AND `community_id` = ".$db->qstr($this->community_id));
		return $result;
	}
}

class GoogleMailingList extends MailingListBase
{
	var $service = null;
	var $current_members = null;
	var $current_owners = null;

	public function GoogleMailingList($community_id, $type = "inactive") {
		global $db, $GOOGLE_APPS, $GOOGLE_V3_REST_API;
		$query = "SELECT `cmlist_id` FROM `community_mailing_lists` WHERE `community_id` = ".$db->qstr($community_id);
		$result = $db->GetOne($query);

		$this->MailingListBase($community_id, $type);

		try {
			if (isset($GOOGLE_V3_REST_API) && $GOOGLE_V3_REST_API) {
				$client = new Google_Client();
				$client->setApplicationName(APPLICATION_NAME);
				$cache = new Google_Cache_Null($client);
				$client->setCache($cache);
				$private_key = file_get_contents($GOOGLE_V3_REST_API["key_file_location"]);
				$scopes = array("https://www.googleapis.com/auth/admin.directory.group",
					"https://www.googleapis.com/auth/admin.directory.notifications",
					"https://www.googleapis.com/auth/admin.directory.orgunit",
					"https://www.googleapis.com/auth/admin.directory.user",
					"https://www.googleapis.com/auth/admin.directory.user.security",
					"https://www.googleapis.com/auth/apps.groups.settings"
				);
				$credentials = new Google_Auth_AssertionCredentials(
					$GOOGLE_V3_REST_API["service_account_name"],
					$scopes,
					$private_key
				);
				$credentials->sub = "system.api@qmed.ca";

				$client = new Google_Client();
				$cache = new Google_Cache_Null($client);
				$client->setCache($cache);
				$client->setAssertionCredentials($credentials);
				if ($client->getAuth()->isAccessTokenExpired()) {
					$client->getAuth()->refreshTokenWithAssertion();
				}

				$service = new Google_Service_Directory($client);
				$settings = new Google_Service_Groupssettings($client);
			}
		} catch (Google_Auth_Exception $e) {
			echo ("Google Members API: Could not establish auth credentials. Error: ".$e->getMessage());
		}
		$this->service = $service;
		$this->settings = $settings;
		if ($this->type == "discussion" || $this->type == "announcements") {
			try {
				$found_group = $this->service->groups->get($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""));
			} catch (Google_Exception $e) {
				//Do nothing, we just need to catch the exception thrown when the mailing list doesn't exist already
			}

			if (!isset($found_group) || !$found_group) {
				$this->create_group($this->type, $this->list_name);
			}
		}
	}

	public function fetch_current_list() {
		global $GOOGLE_APPS;
		try {
			if ($list_members = $this->service->members->listMembers($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""))) {

				$members = array();

				foreach ($list_members as $member) {
					$members[] = $member->email;
				}

				$this->current_members = $members;

			}

			if ($list_owners = $this->service->members->listMembers($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), array("roles" => "OWNER"))) {

				$owners = array();

				foreach ($list_owners as $owner) {
					$owners[] = $owner->email;
				}

				$this->current_owners = $owners;

			}
		} catch (Google_Exception $e) {
			echo ("Google Members API: Could not fetch users from list. Error: ".$e->getMessage());
		}

		return $this;

	}

	public function add($memberId, $role = NULL) {
		global $GOOGLE_APPS;

		$return = false;

		if (!($this->is_member($memberId, $this->list_name) && ($role == 'member' || $role == NULL)) || !($this->is_owner($memberId, $this->list_name) && $role == 'owner')) {

			$success = false;
			$role = ($role ? "OWNER" : "MEMBER");
			try {
				$member_record = $this->service->members->get($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $memberId);
			} catch (Google_Exception $e) {
				//Don't do anything, this just means the user wasn't found.
			}

			if (isset($member_record) && $member_record) {
				try {
					$member_record->setRole($role);
					$this->service->members->update($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $memberId, $member_record);
					$success = true;
				} catch (Google_Exception $e) {
					application_log("error", "Unable to update role for a mailing list member. Google said: [".$e->getCode()."] ".$e->getMessage());
				}
			} else {
				try {
					$member_record = new Google_Service_Directory_Member(array("email" => $memberId, "role" => $role));
					$this->service->members->insert($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $member_record);
					$success = true;
				} catch (Google_Exception $e) {
					application_log("error", "Unable to add a mailing list member. Google said: [".$e->getCode()."] ".$e->getMessage());
				}
			}

			if ($success) {
				$this->fetch_current_list($this->list_name);
				$return = true;
			}

		}

		return $return;
	}

	public function extended_add_member($email, $is_owner = false) {
		return $this->add($email, $is_owner);
	}

	public function remove($memberId) {
		global $GOOGLE_APPS;

		$return = false;
		try {
			if ($this->service->members->delete($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $memberId)) {
				$return = true;
			}
		} catch (Google_Exception $e) {
			application_log("error", "Unable to remove a mailing list member. Google said: [".$e->getCode()."] ".$e->getMessage());
		}

		$this->fetch_current_list($this->list_name);

		return $return;
	}

	public function extended_remove_member($proxy_id) {
		$email = $this->users[$proxy_id]["email"];
		return $this->remove($email);
	}

	public function extended_edit_member($proxy_id, $is_admin) {
		$email = $this->users[$proxy_id]["email"];
		try {
			if (!$is_admin) {
				if ($this->is_owner($email)) {
					$this->remove($email);
					$this->add($email);
				}
				$this->fetch_current_list();
			} elseif ($is_admin) {
				if ($this->is_member($email) && !$this->is_owner($email)) {
					$this->remove($email);
					$this->add($email, "Owner");
				}
				$this->fetch_current_list();
			}
		} catch (Exception $e) {
			return false;
		}
	}

	public function is_owner($memberId) {
		global $GOOGLE_APPS;

		$return = false;
		try {
			$member_record = $this->service->members->get($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $memberId);

			if ($member_record->getRole() == "OWNER") {
				$return = true;
			}
		} catch (Google_Exception $e) {
			application_log("error", "Unable to find a mailing list member. Google said: [".$e->getCode()."] ".$e->getMessage());
		}

		return $return;

	}

	public function is_member($memberId) {
		global $GOOGLE_APPS;
		$return = false;
		try {
			$member_record = $this->service->members->get($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $memberId);
			if (isset($member_record) && $member_record) {
				$return = true;
			}
		} catch (Google_Exception $e) {
			application_log("error", "Unable to find a mailing list member. Google said: [".$e->getCode()."] ".$e->getMessage());
		}

		return $return;

	}

	public function change_mode($mode) {
		global $GOOGLE_APPS;
		$return = false;
		$list_status_map = array("discussion" => "ALL_MEMBERS_CAN_POST", "announcements" => "ALL_MANAGERS_CAN_POST");
		if (array_key_exists($mode, $list_status_map) && ($list_status_map[$mode] == "ALL_MANAGERS_CAN_POST" || $list_status_map[$mode] == "ALL_MEMBERS_CAN_POST")) {
			try {
				$found_group = $this->settings->groups->get($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), array("alt" => "json"));
			} catch (Google_Exception $e) {
				application_log("error", "Unable to find a mailing list group. Google said: [".$e->getCode()."] ".$e->getMessage());
			}
			if (!isset($found_group) || !$found_group) {
				$this->create_group($mode, $this->list_name);
			} else {
				try {
					$found_group->setWhoCanPostMessage($list_status_map[$mode]);
					if ($this->settings->groups->update($this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), $found_group)) {
						$return = true;
					}
				} catch (Google_Exception $e) {
					application_log("error", "Unable to update a mailing list. Google said: [".$e->getCode()."] ".$e->getMessage());
				}
			}
		} elseif ($mode == "inactive") {
			$return = true;
		}

		return $return;
	}

	public function create_group($mode, $list_name) {
		global $GOOGLE_APPS;

		$return = false;
		try {
			$list_status_map = array("discussion" => "ALL_MEMBERS_CAN_POST", "announcements" => "ALL_MANAGERS_CAN_POST");
			if (array_key_exists($mode, $list_status_map) && ($list_status_map[$mode] == "ALL_MANAGERS_CAN_POST" || $list_status_map[$mode] == "ALL_MEMBERS_CAN_POST")) {
				$group = new Google_Service_Directory_Group(array(
					"email" => $this->list_name . "@" . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""),
					"name" => $this->list_name
				));
				if ($this->service->groups->insert($group)) {
					$group_settings_record = $this->settings->groups->get($list_name . (isset($GOOGLE_APPS["domain"]) && $GOOGLE_APPS["domain"] ? $GOOGLE_APPS["domain"] : ""), array("alt" => "json"));
					$group_settings_record->setWhoCanPostMessage($list_status_map[$mode]);
					if ($this->settings->groups->update($list_name . "@qmed.ca", $group_settings_record)) {
						$return = true;
					}
				}
			}
		} catch (Google_Exception $e) {
			application_log("error", "Unable to create a mailing list. Google said: [".$e->getCode()."] ".$e->getMessage());
		}

		return $return;
	}

	public function extended_mode_change($mode) {
		return $this->change_mode($mode);
	}

}

class MailingList extends GoogleMailingList
{

	public function MailingList($community_id, $type = "unset") {
		global $db;
		if ($type == "unset") {
			$type = $db->GetOne("SELECT `list_type` FROM `community_mailing_lists` WHERE `community_id` = ".$db->qstr($community_id));
			if (!isset($type) || !$type) {
				$type = "inactive";
			}
		}
		$this->GoogleMailingList($community_id, $type);
	}


	public function activate_member($proxy_id, $is_owner = false) {
		global $db;
		$email = $db->GetOne("SELECT `email` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($proxy_id));
		if ($email) {
			if ($this->extended_add_member($email, $is_owner)) {
				return $this->base_edit_member($proxy_id, $is_owner, 1);
			}
		}
		return false;
	}

	public function add_member($proxy_id, $is_owner = false) {
		global $db;
		$email = $db->GetOne("SELECT `email` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($proxy_id));
		if ($email) {
			return $this->base_add_member($proxy_id, $email, 0, $is_owner);
		}
		return false;
	}

	public function remove_member($proxy_id) {
		if ($this->extended_remove_member($proxy_id)) {
			return $this->base_remove_member($proxy_id);
		}
		return false;
	}

	public function edit_member($proxy_id, $is_admin) {
		if ($this->extended_edit_member($proxy_id, $is_admin)) {
			return $this->base_edit_member($proxy_id, $is_admin);
		}
		return false;
	}

	public function mode_change($type) {
		if ($this->extended_mode_change($type)) {
			return $this->base_mode_change($type);
		}
		return false;
	}

	public function member_active($proxy_id) {
		return ($this->users[$proxy_id]["member_active"] > 0 ? true : false);
	}

	public function deactivate_member($proxy_id) {
		if ($this->users[$proxy_id]["member_active"] > 0) {
			$this->base_edit_member($proxy_id, 0, '-1');
		} else {
			$this->base_remove_member($proxy_id);
		}
	}

	public function demote_administrator($proxy_id) {
		if (((int)$this->users[$proxy_id]["member_active"]) >= 1) {
			$this->base_edit_member($proxy_id, '-1');
		} else {
			$this->base_edit_member($proxy_id, '0');
		}
	}

	public function promote_administrator($proxy_id) {
		if ($this->users[$proxy_id]["member_active"] > 0) {
			$this->base_edit_member($proxy_id, '2');
		} else {
			$this->base_edit_member($proxy_id, '1');
		}
	}

}

?>