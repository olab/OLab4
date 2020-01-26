<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for sending out community notifications to people.
 *
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2008 University of Calgary. All Rights Reserved.
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

$mail = new Zend_Mail();
if(isset($_SERVER["REMOTE_ADDR"])){
	$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
}
$mail->addHeader("X-Section", "Communities Notify System",true);

/**
 * Function generates Subject and Body of notification posts.
 *
 * @param array $post elements of post subject and body
 */
function build_post($post) {
	global $mail, $AGENT_CONTACTS, $db, $ENTRADA_TEMPLATE;

	$author_email_address = "";
	$author_id = $post["author_id"];
	$type = $post["type"];
	if ($type == "event") {
		if ($author_id) {
			$user_details = Models_User::fetchRowByID($author_id);
			if ($user_details) {
				$author_email_address = $user_details->getEmail();
			}
		}
	}

	if (isset($author_email_address) && $author_email_address != "") {
		$from_email_address = $author_email_address;
	} else {
		$from_email_address = $AGENT_CONTACTS["community-notifications"]["email"];
	}

	$mail->clearFrom();
	$mail->clearSubject();
	$mail->setFrom($from_email_address, $post['community']. ' Community');
	$mail->setSubject($post['subject']);
	switch ($post["type"]) {
		case "announcement_moderate" :
		case "announcement_release" :
		case "announcement_delete" :
			$search		= array(
				"%COMMUNITY_TITLE%",
				"%CONTENT_BODY%",
				"%URL%"
			);
			$query 		= "	SELECT a.*, c.`community_id`, CONCAT_WS(' ', b.firstname, b.lastname) as `fullname`, c.`community_title`, c.`community_url`, d.`page_url`
							FROM `community_announcements` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = ".$db->qstr($post["author_id"])."
							LEFT JOIN `communities` AS c
							ON a.`community_id` = c.`community_id`
							LEFT JOIN `community_pages` AS d
							ON a.`cpage_id` = d.`cpage_id`
							WHERE a.`cannouncement_id` = ".$db->qstr($post["record_id"]);

			$result		= $db->GetRow($query);
			if ($result) {
				$ENTRADA_URL = ENTRADA_URL;
				$COMMUNITY_URL = COMMUNITY_URL;

				$replace	= array(
					$result["community_title"],
					clean_input($result["announcement_description"],array("notags")),
					$COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?section=moderate"
				);
			}
			break;
		case "announcement" :
		case "event"		:
			$search		= array(
				"%AUTHOR_FULLNAME%",
				"%TITLE%",
				"%COMMUNITY_TITLE%",
				"%CONTENT_BODY%",
				"%URL%",
				"%UNSUBSCRIBE_URL%",
				"%SENDER_NAME%",
				"%ENTRADA_URL%",
				"%APPLICATION_NAME%"
			);
			$query 		= "	SELECT a.*, c.`community_id`, CONCAT_WS(' ', b.firstname, b.lastname) as `fullname`, c.`community_title`, c.`community_url`, d.`page_url`
							FROM `community_".$post["type"]."s` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = ".$db->qstr($post["author_id"])."
							LEFT JOIN `communities` AS c
							ON a.`community_id` = c.`community_id`
							LEFT JOIN `community_pages` AS d
							ON a.`cpage_id` = d.`cpage_id`
							WHERE a.`c".$post["type"]."_id` = ".$db->qstr($post["record_id"]);
			$result		= $db->GetRow($query);
			if ($result) {
				$query = "	SELECT c.`organisation_installation`
							FROM `community_courses` AS a
							JOIN `courses` AS b
							ON a.`course_id` = b.`course_id`
							JOIN `".AUTH_DATABASE."`.`organisations` AS c
							ON b.`organisation_id` = c.`organisation_id`
							WHERE a.`community_id` = ".$db->qstr($result["community_id"]);
				$course_url = $db->GetOne($query);
				if ($course_url) {
					$ENTRADA_URL = $course_url;
					$COMMUNITY_URL = $course_url.'/community';
				}else {
					$ENTRADA_URL = ENTRADA_URL;
					$COMMUNITY_URL = COMMUNITY_URL;
				}
				$replace	= array(
					$result["fullname"],
					$result[$post["type"]."_title"],
					$result["community_title"],
					clean_input($result[$post["type"]."_description"],array("notags")),
					$COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?id=".$post["record_id"],
					$COMMUNITY_URL.$result["community_url"].":".$result["page_url"],
					$result["fullname"],
					$ENTRADA_URL,
					APPLICATION_NAME
				);
			}
			break;
		case "poll"		:
			$search		= array(
				"%AUTHOR_FULLNAME%",
				"%TITLE%",
				"%COMMUNITY_TITLE%",
				"%CONTENT_BODY%",
				"%URL%",
				"%UNSUBSCRIBE_URL%",
				"%APPLICATION_NAME%",
				"%ENTRADA_URL%"
			);
			$query 		= "	SELECT a.*, CONCAT_WS(' ', b.firstname, b.lastname) as `fullname`, c.`community_title`, c.`community_url`, d.`page_url`
							FROM `community_polls` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = ".$db->qstr($post["author_id"])."
							LEFT JOIN `communities` AS c
							ON a.`community_id` = c.`community_id`
							LEFT JOIN `community_pages` AS d
							ON a.`cpage_id` = d.`cpage_id`
							WHERE a.`cpolls_id` = ".$db->qstr($post["record_id"]);
			$result		= $db->GetRow($query);
			if ($result) {
				$replace	= array(
					$result["fullname"],
					$result[$post["type"]."_title"],
					$result["community_title"],
					clean_input($result[$post["type"]."_description"],array("notags", "utf8_convert")),
					COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=vote-poll&id=".$post["record_id"],
					COMMUNITY_URL.$result["community_url"].":".$result["page_url"],
					APPLICATION_NAME,
					ENTRADA_URL
				);
			}
			break;
		case "file" :
		case "file-comment" :
		case "file-revision" :
			$search		= array(
				"%AUTHOR_FULLNAME%",
				"%TITLE%",
				"%FOLDER_TITLE%",
				"%URL%",
				"%UNSUBSCRIBE_URL%",
				"%APPLICATION_NAME%",
				"%ENTRADA_URL%"
			);
			$query 		= "	SELECT a.*, CONCAT_WS(' ', b.firstname, b.lastname) as `fullname`, c.`community_title`, c.`community_url`, d.`page_url`, e.`folder_title`
							FROM `community_share_files` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = ".$db->qstr($post["author_id"])."
							LEFT JOIN `communities` AS c
							ON a.`community_id` = c.`community_id`
							LEFT JOIN `community_shares` AS e
							ON a.`cshare_id` = e.`cshare_id`
							LEFT JOIN `community_pages` AS d
							ON e.`cpage_id` = d.`cpage_id`
							WHERE a.`csfile_id` = ".$db->qstr($post["record_id"]);
			$result		= $db->GetRow($query);
			if ($result) {
				$replace	= array(
					$result["fullname"],
					$result["file_title"],
					$result["folder_title"],
					COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-file&id=".$post["record_id"],
					($post["type"] == "file" ? COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-folder&id=".$result["cshare_id"] : COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-file&id=".$post["record_id"] ),
					APPLICATION_NAME,
					ENTRADA_URL
				);
			}
			break;
		case "photo" :
		case "photo-comment" :
			$search		= array(
				"%AUTHOR_FULLNAME%",
				"%TITLE%",
				"%GALLERY_TITLE%",
				"%URL%",
				"%UNSUBSCRIBE_URL%",
				"%APPLICATION_NAME%",
				"%ENTRADA_URL%"
			);
			$query 		= "	SELECT a.*, CONCAT_WS(' ', b.firstname, b.lastname) as `fullname`, c.`community_title`, c.`community_url`, d.`page_url`, e.`gallery_title`
							FROM `community_gallery_photos` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = ".$db->qstr($post["author_id"])."
							LEFT JOIN `communities` AS c
							ON a.`community_id` = c.`community_id`
							LEFT JOIN `community_galleries` AS e
							ON a.`cgallery_id` = e.`cgallery_id`
							LEFT JOIN `community_pages` AS d
							ON e.`cpage_id` = d.`cpage_id`
							WHERE a.`cgphoto_id` = ".$db->qstr($post["record_id"]);
			$result		= $db->GetRow($query);
			if ($result) {
				$replace	= array(
					$result["fullname"],
					$result["photo_title"],
					$result["gallery_title"],
					COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-photo&id=".$post["record_id"],
					($post["type"] == "photo" ? COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-gallery&id=".$result["cgallery_id"] : COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-photo&id=".$post["record_id"]),
					APPLICATION_NAME,
					ENTRADA_URL
				);
			}
			break;
		case "post" :
		case "reply" :
			$search		= array(
				"%AUTHOR_FULLNAME%",
				"%TITLE%",
				"%FORUM_TITLE%",
				"%CONTENT_BODY%",
				"%URL%",
				"%UNSUBSCRIBE_URL%",
				"%APPLICATION_NAME%",
				"%ENTRADA_URL%"
			);
			$query 		= "	SELECT a.*, CONCAT_WS(' ', b.firstname, b.lastname) as `fullname`, b.`organisation_id`, c.`community_title`, c.`community_url`, e.`page_url`, f.`forum_title`, ".($post["type"] == "reply" ? "g" : "a").".`topic_title` AS `record_title`
							FROM `community_discussion_topics` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = ".$db->qstr($post["author_id"])."
							LEFT JOIN `communities` AS c
							ON a.`community_id` = c.`community_id`
							LEFT JOIN `community_discussions` AS d
							ON a.`cdiscussion_id` = d.`cdiscussion_id`
							LEFT JOIN `community_pages` AS e
							ON d.`cpage_id` = e.`cpage_id`
							LEFT JOIN `community_discussions` AS f
							ON a.`cdiscussion_id` = f.`cdiscussion_id`
							".($post["type"] == "reply" ? "LEFT JOIN `community_discussion_topics` AS g
							ON a.`cdtopic_parent` = g.`cdtopic_id`" : "")."
							WHERE a.`cdtopic_id` = ".$db->qstr($post["record_id"]);
			$result		= $db->GetRow($query);
			if ($result) {
				$replace	= array(
					$result["fullname"],
					$result["record_title"],
					$result["forum_title"],
					clean_input($result["topic_description"],array("notags", "utf8_convert")),
					COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-post&id=".$post["record_id"],
					($post["type"] == "post" ? COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-forum&id=".$result["cdiscussion_id"] : COMMUNITY_URL.$result["community_url"].":".$result["page_url"]."?action=view-post&id=".$post["record_id"]),
					APPLICATION_NAME,
					ENTRADA_URL
				);
			}
			break;
		case "join" :
		case "leave" :
			$search		= array(
				"%AUTHOR_FULLNAME%",
				"%COMMUNITY_TITLE%",
				"%URL%",
				"%UNSUBSCRIBE_URL%",
				"%APPLICATION_NAME%",
				"%ENTRADA_URL%"
			);
			$query 		= "	SELECT CONCAT_WS(a.firstname, a.lastname) as `fullname`, b.`community_title`, b.`community_url`, b.`community_title`
							FROM `".AUTH_DATABASE."`.`user_data` AS a
							LEFT JOIN `communities` AS b
							ON b.`community_id` = ".$db->qstr($post["record_id"])."
							WHERE a.`id` = ".$db->qstr($post["author_id"]);
			$result		= $db->GetRow($query);
			if ($result) {
				$replace	= array(
					$result["fullname"],
					$result["community_title"],
					ENTRADA_URL."/people?id=".$post["author_id"],
					ENTRADA_URL."/profile",
					APPLICATION_NAME,
					ENTRADA_URL
				);
			}
			break;
		default :
			break;
	}

	$NOTIFICATION_MESSAGE = array();
	$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/".$post['body']);

	$mail->setBodyText(clean_input(str_replace($search, $replace, $NOTIFICATION_MESSAGE["textbody"]), array("postclean","decode")));
}

/**
 * Function mails out community notification to recipient
 *
 * @param array $email - recipient's address and name
 */
function send_member($email) {
	global $mail;
	$mail->clearRecipients();
	if (strlen($email['email'])) {
		$mail->addTo($email['email'], $email['fullname']);
		$mail->send();
	}
}

if ((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
	/**
	 * Lock present: application busy: quit
	 */

	if (!@file_exists(COMMUNITY_NOTIFY_LOCK)) {
		if (@file_put_contents(COMMUNITY_NOTIFY_LOCK, 'L_O_C_K')) {
			$limit	= COMMUNITY_NOTIFY_LIMIT;

			$query	=  "SELECT * FROM `community_notifications` WHERE `release_time` < ". $db->qstr(time());
			$posts	= $db->GetAll($query);
			if ($posts) {
				foreach ($posts as $post) {
					build_post($post);

					$query = "SELECT `ccnotification_id`, `proxy_id` FROM `cron_community_notifications`
								WHERE `cnotification_id` = ".$db->qstr($post['cnotification_id']);
					$proxies = $db->GetAll($query);
					foreach ($proxies as $proxy) {
						$query = "SELECT `email`, CONCAT_WS(' ', `firstname`, `lastname`) AS `fullname`, `notifications` FROM `".AUTH_DATABASE."`.`user_data`
									WHERE `id` = ". $db->qstr($proxy['proxy_id']);
						$result  = $db->GetRow($query);
						if ($result && $result["notifications"]) send_member($result);

						if (!$db->Execute("DELETE FROM `cron_community_notifications` WHERE `ccnotification_id` = ".$db->qstr($proxy['ccnotification_id']))) {	// Delete the recipient
							application_log("error", "Failed to delete record with `ccnotification_id` $proxy[ccnotification_id] from table `cron_community_notifications`. Database said: ".$db->ErrorMsg());
						}

						/**
						 * Email limit so exit
						 */
						if (!(--$limit)) {
							if (!@unlink(COMMUNITY_NOTIFY_LOCK)) {
								application_log("error", "Unable to delete email lock file: ".COMMUNITY_NOTIFY_LOCK);
							}
							exit;
						}
					}

					if (!$db->Execute("DELETE FROM `community_notifications` WHERE `cnotification_id` = ".$db->qstr($post['cnotification_id']))) {	// Delete the notification
						application_log("error", "Failed to delete record with `cnotification_id` $post[cnotification_id] from table `community_notifications`. Database said: ".$db->ErrorMsg());
					}
				}
			}

			if (!@unlink(COMMUNITY_NOTIFY_LOCK)) {
				application_log("error", "Unable to delete email lock file: ".COMMUNITY_NOTIFY_LOCK);
			}
		} else {
			application_log("error", "Unable to open email lock file: ".COMMUNITY_NOTIFY_LOCK);
		}
	} else {
		/**
		 * Found old lock file get rid of it
		 */
		if (filemtime(COMMUNITY_NOTIFY_LOCK) < time() - COMMUNITY_NOTIFY_TIMEOUT ) {
			if (!@unlink(COMMUNITY_NOTIFY_LOCK)) {
				application_log("error", "Unable to delete email lock file: ".COMMUNITY_NOTIFY_LOCK);
			}
		}
	}
}
?>