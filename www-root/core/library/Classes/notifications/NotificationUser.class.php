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
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */
require_once("Classes/utility/SimpleCache.class.php");

/**
 * Class to model Notification instances including basic data and relationships to users/content
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 */
class NotificationUser {
	private $nuser_id;
	private $proxy_id;
	private $notification_user_type;
	private $content_type;
	private $record_id;
	private $record_proxy_id;
	private $notify_active;
	private $digest_mode;
	private $next_notification_date;

	function __construct(	$nuser_id,
							 $proxy_id,
							 $content_type,
							 $record_id,
							 $record_proxy_id,
							 $notify_active,
							 $digest_mode,
							 $next_notification_date,
							 $notification_user_type = "proxy_id") {

		$this->nuser_id = $nuser_id;
		$this->proxy_id = $proxy_id;
		$this->notification_user_type = $notification_user_type;
		$this->content_type = $content_type;
		$this->record_id = $record_id;
		$this->record_proxy_id = $record_proxy_id;
		$this->notify_active = $notify_active;
		$this->digest_mode = $digest_mode;
		$this->next_notification_date = $next_notification_date;

		//be sure to cache this whenever created.
		$cache = SimpleCache::getCache();
		$cache->set($this,"NotificationUser",$this->nuser_id);
	}

	/**
	 * Returns the id of the notification user
	 * @return int
	 */
	public function getID() {
		return $this->nuser_id;
	}

	/**
	 * Returns the proxy ID associated with the user
	 * @return int
	 */
	public function getProxyID() {
		return $this->proxy_id;
	}

	/**
	 * Returns the notification user type associated with the user
	 * @return int
	 */
	public function getNotificationUserType() {
		return $this->notification_user_type;
	}

	/**
	 * Returns the type of content this notification_user refers to
	 * @return string
	 */
	public function getContentType() {
		return $this->content_type;
	}

	/**
	 * Returns the record id of the content which the notification_user has
	 * requested to be notified of changes (or comments) to.
	 * @return int
	 */
	public function getRecordID() {
		return $this->record_id;
	}

	/**
	 * Returns the proxy_id of the creator/owner of the content
	 * which this user has requested to be notified of.
	 * @return int
	 */
	public function getRecordProxyID() {
		return $this->record_proxy_id;
	}

	/**
	 * Returns a boolean value representing whether this user wishes to be notified
	 * about changes or comments associated with the content.
	 * @return bool
	 */
	public function getNotifyActive() {
		return $this->notify_active;
	}

	/**
	 * Returns a boolean value representing whether this user wishes to be notified
	 * once a day (in digest mode), or each time new changes/comments are made related to the content.
	 * @return bool
	 */
	public function getDigestMode() {
		return $this->digest_mode;
	}

	/**
	 * Returns a unix timestamp which represents the next time which should trigger the cron job
	 * to send a notification to the user.
	 * @return int
	 */
	public function getNextNotificationDate() {
		return $this->next_notification_date;
	}

	/**
	 * Returns a string which describes the type of content
	 * @return bool
	 */
	public function getContentTypeName() {
		global  $db;
		switch ($this->content_type) {
			case "event_discussion" :
				$content_type_name = "event discussion";
				break;
			case "logbook_rotation" :
				$content_type_name = "logbook rotation";
				break;
			case "evaluation" :
			case "evaluation_overdue" :
			case "evaluation_threshold" :
			case "evaluation_request" :
				$query = "SELECT `target_title` FROM `evaluations_lu_targets` AS a
							JOIN `evaluation_forms` AS b
							ON a.`target_id` = b.`target_id`
							JOIN `evaluations` AS c
							ON b.`eform_id` = c.`eform_id`
							WHERE c.`evaluation_id` = ".$db->qstr($this->record_id);
				$target_title = $db->GetOne($query);
				if ($target_title) {
					$content_type_name = strtolower($target_title);
				} else {
					$content_type_name = "evaluation";
				}
				break;
			default :
				$content_type_name = "content";
				break;
		}
		return $content_type_name;
	}

	/**
	 * Returns a string which describes or is the title of the content related to this `notification_users` record.
	 * @return bool
	 */
	public function getContentTitle() {
		global $db;
		switch ($this->content_type) {
			case "logbook_rotation" :
				if ($this->proxy_id != $this->record_proxy_id) {
					$query = "SELECT CONCAT_WS(' - ', a.`rotation_title`, CONCAT_WS(' ', b.`firstname`, b.`lastname`)) FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
								JOIN `".AUTH_DATABASE."`.`user_data` AS b
								WHERE b.`id` = ".$db->qstr($this->record_proxy_id)."
								AND a.`rotation_id` = ".$db->qstr($this->record_id);
					$content_title = $db->GetOne($query);
				} else {
					$query = "SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
								WHERE `rotation_id` = ".$db->qstr($this->record_id);
					$content_title = $db->GetOne($query);
				}
				break;
			case "evaluation" :
			case "evaluation_threshold" :
			case "evaluation_overdue" :
			case "evaluation_request" :
				$query = "SELECT `evaluation_title` FROM `evaluations` WHERE `evaluation_id` = ".$db->qstr($this->record_id);
				if ($evaluation_title = $db->GetOne($query)) {
					$content_title = $evaluation_title;
				}
				break;
			case "event_discussion" :
			default :
				$query = "SELECT `event_title`, `event_start` FROM `events` WHERE `event_id` = ".$db->qstr($this->record_id);
				if ($event = $db->GetRow($query)) {
					$content_title = $event["event_title"]." - ".date(DEFAULT_DATE_FORMAT, $event["event_start"]);
				}
				break;
		}
		if ($content_title) {
			return $content_title;
		}
		return "Not found";
	}

	/**
	 * Returns a string which contains the text of the content related to this `notification_users` record.
	 * @return bool
	 */
	public function getContentBody($content_id) {
		global $db;
		switch ($this->content_type) {
			case "logbook_rotation" :
				$query = "SELECT `comments` FROM `".CLERKSHIP_DATABASE."`.`logbook_rotation_comments`
							WHERE `lrcomment_id` = ".$db->qstr($content_id);
				$content_body = $db->GetOne($query);
				break;
			case "evaluation" :
			case "evaluation_threshold" :
			case "evaluation_overdue" :
			case "evaluation_request" :
				$query = "SELECT `evaluation_description` FROM `evaluations` WHERE `evaluation_id` = ".$db->qstr($this->record_id);
				if ($evaluation_description = $db->GetOne($query)) {
					$content_body = $evaluation_description;
				} else {
					$content_body = "No description found.";
				}
				break;
			case "event_discussion" :
			default :
				$query = "SELECT `discussion_comment` FROM `event_discussions` WHERE `ediscussion_id` = ".$db->qstr($content_id);
				$content_body = $db->GetOne($query);
				break;
		}
		if ($content_body) {
			return $content_body;
		}
		return "Not found";
	}

	/**
	 * Returns a link to the appropriate piece of content based on a passed record ID and the content type of this NotificationUser.
	 * @return bool
	 */
	public function getContentURL() {
		switch ($this->content_type) {
			case "logbook_rotation" :
				$content_url = ENTRADA_URL."/clerkship/logbook?section=view&core=".$this->getRecordID().($this->getRecordProxyID() != $this->getProxyID() ? "&id=".$this->getRecordProxyID() : "");
				break;
			case "evaluation" :
			case "evaluation_overdue" :
			case "evaluation_request" :
				$content_url = ENTRADA_URL."/evaluations?section=attempt&id=".$this->getRecordID();
				break;
			case "evaluation_threshold" :
				$content_url = ENTRADA_URL."/evaluations?section=review&id=".$this->getRecordID();
				break;
			case "event_discussion" :
			default :
				$content_url = ENTRADA_URL."/events?id=".$this->getRecordID()."#event-comments-section";
				break;
		}
		if ($content_url) {
			return $content_url;
		}
		return "Not found";
	}

	/**
	 * Sets a boolean value representing whether this user wishes to be notified
	 * about changes or comments associated with the content.
	 * @return bool $success
	 */
	public function setNotifyActive($active) {
		global $db;
		$active = (bool) $active;
		if ($active != $this->notify_active) {
			if ($db->AutoExecute("notification_users", array("notify_active" => $active), "UPDATE", "`nuser_id` = ".$db->qstr($this->nuser_id))) {
				$this->notify_active = $active;
				return true;
			} else {
				application_log("error", "An error was encountered when attempting to update the `notify_active` field for a `notification_user` [".$this->nuser_id."]");
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Sets a boolean value representing whether this user wishes to be notified
	 * once a day (in digest mode), or each time new changes/comments are made related to the content.
	 * @return bool $success
	 */
	public function setDigestMode($digest) {
		global $db;
		$digest = (bool) $digest;
		if ($digest != $this->digest_mode) {
			if ($db->AutoExecute("notification_users", array("digest_mode" => $digest), "UPDATE", "`nuser_id` = ".$db->qstr($this->nuser_id))) {
				$this->digest_mode = $digest;
				return true;
			} else {
				application_log("error", "An error was encountered when attempting to update the `digest_mode` field for a `notification_user` [".$this->nuser_id."]");
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Sets a boolean value representing whether this user wishes to be notified
	 * once a day (in digest mode), or each time new changes/comments are made related to the content.
	 * @return bool $success
	 */
	public function setNextNotificationDate() {
		global $db;
		if (($this->digest_mode && $this->next_notification_date != mktime(0, 0, 0, date("n"), (date("j") + 1))) || !$this->next_notification_date) {
			if ($this->digest_mode) {
				$next_notification_date = mktime(0, 0, 0, date("n"), (date("j") + 1));
			} else {
				$next_notification_date = time() + 300;
			}
			if ($db->AutoExecute("notification_users", array("next_notification_date" => $next_notification_date), "UPDATE", "`nuser_id` = ".$db->qstr($this->nuser_id))) {
				$this->next_notification_date = $next_notification_date;
				return true;
			} else {
				application_log("error", "An error was encountered when attempting to update the `next_notification_date` field for a `notification_user` [".$this->nuser_id."]. Database said: ".$db->ErrorMsg());
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Sets a integer value representing when this user will next be notified
	 * back to 0, to indicate they have been sent all pending notifications already.
	 * @return bool $success
	 */
	public function clearNextNotificationDate() {
		global $db;
		$next_notification_date = 0;
		if ($db->AutoExecute("notification_users", array("next_notification_date" => $next_notification_date), "UPDATE", "`nuser_id` = ".$db->qstr($this->nuser_id))) {
			$this->next_notification_date = $next_notification_date;
			return true;
		} else {
			application_log("error", "An error was encountered when attempting to update the `next_notification_date` field for a `notification_user` [".$this->nuser_id."]. Database said: ".$db->ErrorMsg());
			return false;
		}
	}

	/**
	 * Returns a NotificationUser specified by the provided ID
	 * @param unknown_type $event_id
	 * @return unknown
	 */
	public static function getByID($nuser_id) {
		$cache = SimpleCache::getCache();
		$notification_user = $cache->get("NotificationUser",$nuser_id);
		if (!$notification_user) {
			global $db;
			$query = "SELECT * FROM `notification_users` WHERE `nuser_id` = ".$db->qstr($nuser_id);
			$result = $db->getRow($query);
			if ($result) {
				$notification_user = self::fromArray($result);
			}
		}
		return $notification_user;
	}

	/**
	 * Returns an Event specified by the provided proxy_id, content_type and record_id, as well
	 * as a record_proxy_id in the case of some types of content.
	 *
	 * @param int $proxy_id
	 * @param string $content_type
	 * @param int $record_id
	 * @param int $record_proxy_id
	 * @return array
	 */
	public static function get($proxy_id, $content_type, $record_id, $record_proxy_id = 0, $notification_user_type = "proxy_id") {
		global $db;
		$query = "SELECT * FROM `notification_users` 
					WHERE `proxy_id` = ".$db->qstr($proxy_id)."
					AND `notification_user_type` = ".$db->qstr($notification_user_type)."
					AND `content_type` = ".$db->qstr($content_type)."
					AND `record_id` = ".$db->qstr($record_id).
			($record_proxy_id ? "
					AND `record_proxy_id` = ".$db->qstr($record_proxy_id) : "");
		$result = $db->getRow($query);
		if ($result) {
			$notification_user = self::fromArray($result);
			return $notification_user;
		}
		return false;
	}

	/**
	 * Returns an Event specified by the provided proxy_id, content_type and record_id, as well
	 * as a record_proxy_id in the case of some types of content.
	 *
	 * @param string $content_type
	 * @param int $record_id
	 * @param int $record_proxy_id
	 * @return array
	 */
	public static function getAll($content_type, $record_id, $record_proxy_id = 0) {
		global $db;
		$query = "SELECT * FROM `notification_users` 
					WHERE `content_type` = ".$db->qstr($content_type)."
					AND `notify_active` = 1
					AND `record_id` = ".$db->qstr($record_id).
			($record_proxy_id ? "
					AND `record_proxy_id` = ".$db->qstr($record_proxy_id) : "");
		$results = $db->getAll($query);
		if ($results) {
			$notification_users = array();
			foreach ($results as $result) {
				$notification_users[] = self::fromArray($result);
			}
			return $notification_users;
		}
		return false;
	}

	static public function fromArray($array) {
		return new NotificationUser($array["nuser_id"], $array["proxy_id"], $array["content_type"], $array["record_id"], $array["record_proxy_id"], $array["notify_active"], $array["digest_mode"], $array["next_notification_date"], $array["notification_user_type"]);
	}

	/**
	 * Creates a new notification_user record for the given user/content
	 *
	 * @param int $proxy_id
	 * @param string $content_type
	 * @param int $record_id
	 * @param int $record_proxy_id
	 * @param bool $notify_active
	 * @param bool $digest_mode
	 */
	public static function add(	$proxy_id,
								   $content_type,
								   $record_id,
								   $record_proxy_id = 0,
								   $notify_active = 1,
								   $digest_mode = 0,
								   $next_notification_date = 0,
								   $notification_user_type = "proxy_id") {
		global $db;

		$new_notification_user = array(	"proxy_id" => $proxy_id,
			"notification_user_type" => $notification_user_type,
			"content_type" => $content_type,
			"record_id" => $record_id,
			"record_proxy_id" => $record_proxy_id,
			"notify_active" => $notify_active,
			"digest_mode" => $digest_mode,
			"next_notification_date" => $next_notification_date);
		$db->AutoExecute("notification_users", $new_notification_user, "INSERT");
		if (!($nuser_id = $db->Insert_Id())) {
			application_log("error", "There was an issue attempting to add a notification_user record to the database. Database said: ".$db->ErrorMsg());
		} else {
			$new_notification_user["nuser_id"] = $nuser_id;
			$notification_user = self::fromArray($new_notification_user);
			return $notification_user;
		}
		return false;
	}

	/**
	 * Returns an Event specified by the provided proxy_id, content_type and record_id, as well
	 * as a record_proxy_id in the case of some types of content.
	 *
	 * @param string $content_type
	 * @param int $record_id
	 * @param int $record_proxy_id
	 * @return array
	 */
	public static function addAllNotifications($content_type, $record_id, $record_proxy_id = 0, $proxy_id, $content_id) {
		global $db;
		$query = "SELECT * FROM `notification_users` 
					WHERE `content_type` = ".$db->qstr($content_type)."
					AND `notify_active` = 1
					AND `record_id` = ".$db->qstr($record_id).
			($record_proxy_id ? "
					AND `record_proxy_id` = ".$db->qstr($record_proxy_id) : "");
		$results = $db->getAll($query);
		if ($results) {
			require_once("Classes/notifications/Notification.class.php");
			foreach ($results as $result) {
				if ($result["proxy_id"] != $proxy_id) {
					$notification = Notification::add($result["nuser_id"], $proxy_id, $content_id);
				}
			}
		}
		return false;
	}
	
	public static function getAllActiveNotifications($proxy_id, $active = 1, $search_term = "", $offset = null, $limit = null, $sort_column = null, $sort_direction = null) {
		global $db;

		$sort_columns_array = array(
			"title" => "`title`"
		);

		$order_sql = " ORDER BY ".$sort_columns_array[$sort_column]. " ".$sort_direction." " ;

		$search_sql = "";
		if(!empty($search_term)) {
			$search_sql = " AND  (`b`.`title` LIKE (". $db->qstr($search_term) . ")
								OR `c`.`title` LIKE (". $db->qstr($search_term) . ")
								OR `d`.`event_title` LIKE (". $db->qstr($search_term) . ")
								OR `e`.`evaluation_title` LIKE (". $db->qstr($search_term) . ")
								OR `f`.`rotation_title` LIKE (". $db->qstr($search_term) . ")
								OR `g`.`rotation_title` LIKE (". $db->qstr($search_term) . ")
								) ";
		}

		$query = "SELECT `a`.*, CONCAT_WS('',`b`.`title`, `c`.`title`, `d`.`event_title`, `e`.`evaluation_title`, `f`.`rotation_title`, CONCAT_WS(' - ', `g`.`rotation_title`, CONCAT_WS(' ', `h`.`firstname`, `h`.`lastname`))) AS title
						FROM `".DATABASE_NAME."`.`notification_users` AS a 
						LEFT JOIN `".DATABASE_NAME."`.`cbl_distribution_assessments` AS i ON (`i`.`dassessment_id` = `a`.`record_id` AND `a`.`content_type` = \"assessment\")
						LEFT JOIN `".DATABASE_NAME."`.`cbl_assessment_distributions` AS b ON `b`.`adistribution_id` = `i`.`adistribution_id`
						LEFT JOIN `".DATABASE_NAME."`.`cbl_assessment_distributions` AS c ON (`c`.`adistribution_id` = `a`.`record_id` AND `a`.`content_type` = \"assessment_delegation\" AND `c`.`deleted_date` IS NULL)
						LEFT JOIN `".DATABASE_NAME."`.`events` AS d ON (`d`.`event_id` = `a`.`record_id` AND `a`.`content_type` = \"event_discussion\")
						LEFT JOIN `".DATABASE_NAME."`.`evaluations` AS e ON (`e`.`evaluation_id` = `a`.`record_id` AND (`a`.`content_type` = \"evaluation\" OR `a`.`content_type` = \"evaluation_threshold\" OR `a`.`content_type` = \"evaluation_overdue\" OR `a`.`content_type` = \"evaluation_request\"))
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS f ON (`f`.`rotation_id` = `a`.`record_id` AND `a`.`content_type` = \"logbook_rotation\" AND `a`.`proxy_id` = `a`.`record_proxy_id`)
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS g ON (`g`.`rotation_id` = `a`.`record_id` AND `a`.`content_type` = \"logbook_rotation\" AND `a`.`proxy_id` != `a`.`record_proxy_id`)
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS h ON (`h`.`id` = `a`.`record_proxy_id` AND `g`.`rotation_id` = `a`.`record_id` AND `a`.`content_type` = \"logbook_rotation\" AND `a`.`proxy_id` != `a`.`record_proxy_id`)
						WHERE `a`.`proxy_id` = ? AND `a`.`notify_active` = ? 
						" . $search_sql . "
						" . $order_sql . "
						LIMIT ?, ?";


		$results = $db->getAll($query, array($proxy_id, $active, $offset, $limit));

		if ($results) {
			return $results;
		}
		return false;
	}

	public static function getTotalAllActiveNotifications($proxy_id, $active = 1) {
		global $db;

		$query = "SELECT COUNT(*) as total_rows
						FROM `notification_users` AS a 
						WHERE a.proxy_id = ? AND a.notify_active = ? ";
		$results = $db->getRow($query, array($proxy_id, $active));

		if ($results) {
			return $results;
		}

		return false;
	}
}