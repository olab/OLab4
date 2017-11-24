<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Controller file responsible for serving all of the feeds from each
 * community (i.e. RSS, ICS, Podcast, etc).
 *
 * @author Organisation: Queen's University
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * $Id: serve-feeds.php 1103 2010-04-05 15:20:37Z simpson $
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
require_once("Entrada/feedcreator/feedcreator.class.php");

ob_start("on_checkout");

$rss_output		= array();
$module_name 	= "";
$discussion_id	= 0;
$page_id		= 0;
$feed_type		= "";
$rss_version	= "0.91";
$user_proxy_id	= 0;
$user_firstname	= "";
$user_lastname	= "";
$user_email		= "";
$user_role		= "";
$user_group		= "";
$community_id	= 0;
$community_name	= "";
$community_url	= "";

$logged_in		= (((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) ? true : false);
$user_proxy_id 	= (isset($ENTRADA_USER) && $ENTRADA_USER ? $ENTRADA_USER->getID() : 0);
$user_private_hash = "";

/**
 * Check for PATH_INFO to process the url and get the module.
 */
if (isset($_SERVER["PATH_INFO"])) {
	$tmp_url		= array();
	$tmp_page_url 	= array();
	$path_info		= explode(":", clean_input($_SERVER["PATH_INFO"], array("trim", "lower")));

	/**
	 * Check if there is any path details provided
	 */
	if ((isset($path_info[0])) && ($tmp_path = explode("/", $path_info[0])) && (is_array($tmp_path))) {
		foreach($tmp_path as $directory) {
			$directory = clean_input($directory, array("trim", "credentials"));

			if ($directory != "rss" && $directory != "rss10" && $directory != "rss20" && $directory) {
				$tmp_url[] = $directory;
			} elseif ($directory == "rss" || $directory == "rss10" || $directory == "rss20") {
				$feed_type = $directory;
			}
		}

		if ((is_array($tmp_url)) && (count($tmp_url))) {
			$community_url = "/".implode("/", $tmp_url);
		}
	}

	/**
	 * Check if there is a requested page. This is done by looking for the colon set in the path_info.
	 */
	if ((isset($path_info[1])) && ($tmp_page = explode("/", $path_info[1])) && (is_array($tmp_page))) {
		foreach($tmp_page as $page_url) {
			$page_url = clean_input($page_url, array("trim", "credentials"));
			if (($page_url != "calendar.ics") && ($page_url != "ics") && ($page_url != "rss") && ($page_url != "rss10") && ($page_url != "rss20")) {
				$tmp_page_url[] = $page_url;
			} elseif (($page_url == "calendar.ics") || ($page_url == "ics") || ($page_url == "rss") || ($page_url == "rss10") || ($page_url == "rss20")) {
				$feed_type = $page_url;
			}
		}

		$page_url = implode("/", $tmp_page_url);
	}

	/**
	 * Check if there is a private hash
	 */
	if ((isset($path_info[2])) && (substr($path_info[2], 0, 8) == "private-") && ($tmp_input = str_ireplace("private-", "", $path_info[2]))) {
		$user_private_hash = $tmp_input;
	}
}

$query = "SELECT * FROM `communities` WHERE `community_url` = ".$db->qstr($community_url);
$community_result = $db->GetRow($query);
if ($community_result) {
	$community_id = $community_result["community_id"];
}

$query = "SELECT * FROM `community_pages` WHERE `page_url` = ".$db->qstr($page_url)." AND `community_id` = ".$db->qstr($community_id);
$page_result = $db->GetRow($query);
if ($page_result) {
	$module_name = $page_result["page_type"];
	$page_id = $page_result["cpage_id"];
}

if ($feed_type == "rss10") {
	$rss_version = "1.0";
} elseif ($feed_type == "rss20") {
	$rss_version = "2.0";
}

if ($page_id) {
	switch($module_name) {
		case "announcements" :
			$query 			= "	SELECT a.*, b.*, c.`option_value` FROM `community_pages` as a
								LEFT JOIN `communities` as b
								ON a.`community_id` = b.`community_id`
								LEFT JOIN `community_page_options` as c
								ON a.`community_id` = c.`community_id`
								AND a.`cpage_id` = c.`cpage_id`
								AND c.`option_title` = 'moderate_posts'
								WHERE a.`cpage_id` = ".$db->qstr($page_id)."
								AND b.`community_active` = '1'
								AND a.`page_active` = '1'";
			$page_record	= $db->GetRow($query);
			if (($page_record) && ((int) $page_record["community_protected"]) == 1 || ((int) $page_record["allow_public_view"]) == 0) {

				if (!$logged_in) {
					if ($user_private_hash) {
						$query = "  SELECT a.`id`, a.`username`, a.`firstname`, a.`lastname`, a.`email`, a.`grad_year`, b.`role`, b.`group`, b.`organisation_id`, b.`access_expires`
									FROM `" . AUTH_DATABASE . "`.`user_data` AS a
									LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
									ON b.`user_id` = a.`id`
									WHERE b.`private_hash` = " . $db->qstr($user_private_hash) . "
									AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
									AND b.`account_active` = 'true'
									AND (b.`access_starts`='0' OR b.`access_starts` <= " . $db->qstr(time()) . ")
									AND (b.`access_expires`='0' OR b.`access_expires` >= " . $db->qstr(time()) . ")
									GROUP BY a.`id`";
						$result = $db->GetRow($query);
						if ($result) {
							// If $ENTRADA_USER was previously initialized in init.inc.php before the
							// session was authorized it is set to false and needs to be re-initialized.
							if ($ENTRADA_USER == false) {
								$ENTRADA_USER = User::get($result["id"]);
							}
							$_SESSION["details"]["id"] = $user_proxy_id = $result["id"];
							$_SESSION["details"]["access_id"] = $ENTRADA_USER->getAccessId();
							$_SESSION["details"]["username"] = $user_username = $result["username"];
							$_SESSION["details"]["firstname"] = $user_firstname = $result["firstname"];
							$_SESSION["details"]["lastname"] = $user_lastname = $result["lastname"];
							$_SESSION["details"]["email"] = $user_email = $result["email"];
							$_SESSION["details"]["role"] = $user_role = $result["role"];
							$_SESSION["details"]["group"] = $user_group = $result["group"];
							$_SESSION["details"]["organisation_id"] = $user_organisation_id = $result["organisation_id"];
							$_SESSION["details"]["app_id"] = AUTH_APP_ID;
							$_SESSION["details"]["grad_year"] = $result["grad_year"];
							$_SESSION["details"]["expires"] = $result["access_expires"];
						} else {
							/**
							 * If the query above fails, redirect them back here but without the
							 * private hash which will trigger the HTTP Authentication.
							 */
							header("Location: ".COMMUNITY_URL."/feeds".$path_info[0].":".$path_info[1]);
							exit;
						}
					} else {
						/**
						 * If they are not already authenticated, and they don't have a private
						 * hash in the URL, then send them through to HTTP authentication.
						 */
						if (!isset($_SERVER["PHP_AUTH_USER"])) {
							http_authenticate();
						} else {
							require_once("Entrada/authentication/authentication.class.php");

							$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
							$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
							$auth->setEncryption(AUTH_ENCRYPTION_METHOD);

							$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
							$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

							$auth->setUserAuthentication($username, $password, AUTH_METHOD);
							$result = $auth->Authenticate(
								array(
									"id",
									"prefix",
									"firstname",
									"lastname",
									"email",
									"telephone",
									"role",
									"group",
									"access_starts",
									"access_expires",
									"last_login",
									"privacy_level"
								)
							);
							if ($result["STATUS"] == "success") {
								if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
									$ERROR++;
									application_log("error", "User[".$username."] tried to access account prior to activation date.");
								} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
									$ERROR++;
									application_log("error", "User[".$username."] tried to access account after expiration date.");
								} else {
									$user_proxy_id	= $result["ID"];
									$user_firstname	= $result["FIRSTNAME"];
									$user_lastname	= $result["LASTNAME"];
									$user_email		= $result["EMAIL"];
									$user_role		= $result["ROLE"];
									$user_group		= $result["GROUP"];

									$member = $db->GetRow("SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($user_proxy_id)." AND `community_id` = ".$db->qstr($page_record["community_id"])." AND `member_active` = '1'");
									if ((!$member && $page_record["allow_troll_view"] ==  0) || ($member && $page_record["allow_member_view"] == 0 && $member["member_acl"] == 0)) {
										exit;
									}
								}
							} else {
								$ERROR++;
								application_log("access", $result["MESSAGE"]);
							}

							if ($ERROR) {
								http_authenticate();
							}

							unset($username, $password);
						}
					}
				}
			}

			switch ($feed_type) {
				case "rss" :
				case "rss10" :
				case "rss20" :
				default :
					$query	= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) as `author_name`
								FROM `community_announcements` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON b.`id` = a.`proxy_id`
								WHERE a.`cpage_id` = ".$db->qstr($page_id)."
								AND a.`announcement_active` = '1'
								".(((int)$page_record["option_value"]) == 1 ? "AND a.`pending_moderation` = '0'" : "")."
								AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
								AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
								ORDER BY a.`release_date` DESC";
					$results = $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$rss_output[$result["cannouncement_id"]] = $result;
						}

						unset($results);
					}

					$rss = new UniversalFeedCreator();
					$rss->useCached();
					$rss->title						= $page_record["community_title"]." : ".$page_record["menu_title"];
					$rss->description				= "Announcements from the ".$page_record["menu_title"]." page of the ".$page_record["community_title"]." community.";

					$rss->copyright					= str_replace("&copy;", "", COPYRIGHT_STRING);
					$rss->link						= ENTRADA_URL."/community".$page_record["community_url"].":".$page_record["page_url"];
					$rss->syndicationURL			= ENTRADA_URL."/community".$page_record["community_url"].":".$page_record["page_url"];
					$rss->descriptionHtmlSyndicated	= true;

					if ((is_array($rss_output)) && (count($rss_output))) {
						foreach ($rss_output as $result) {
							$description = $result["announcement_description"];

							$item								= new FeedItem();
							$item->title						= $result["announcement_title"];

							$item->link							= ENTRADA_URL."/community".$page_record["community_url"].":".$page_record["page_url"]."?id=".$result["cannouncement_id"].($page_record["community_protected"] || !$page_record["allow_public_view"] ? "&auth=true" : "");
							$item->date							= ((int) date("U", $result["release_date"]));
							$item->author						= $result["author_name"];
							$item->description					= $description;
							$item->descriptionHtmlSyndicated 	= true;

							$rss->addItem($item);
						}
					}

					header("Content-type: text/xml");
					echo $rss->createFeed($rss_version);
				break;
			}
		break;
		case "events" :
			$query 			= "	SELECT a.*, b.*, c.`option_value` FROM `community_pages` as a
								LEFT JOIN `communities` as b
								ON a.`community_id` = b.`community_id`
								LEFT JOIN `community_page_options` as c
								ON a.`community_id` = c.`community_id`
								AND a.`cpage_id` = c.`cpage_id`
								AND c.`option_title` = 'moderate_posts'
								WHERE a.`cpage_id` = ".$db->qstr($page_id)."
								AND b.`community_active` = '1'
								AND a.`page_active` = '1'";
			$page_record 	= $db->GetRow($query);
			if (($page_record) && ((int) $page_record["community_protected"]) == 1 || ((int) $page_record["allow_public_view"]) == 0) {

				if (!$logged_in) {
					if ($user_private_hash) {
						$query = "  SELECT a.`id`, a.`username`, a.`firstname`, a.`lastname`, a.`email`, a.`grad_year`, b.`role`, b.`group`, b.`organisation_id`, b.`access_expires`
									FROM `" . AUTH_DATABASE . "`.`user_data` AS a
									LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
									ON b.`user_id` = a.`id`
									WHERE b.`private_hash` = " . $db->qstr($user_private_hash) . "
									AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
									AND b.`account_active` = 'true'
									AND (b.`access_starts`='0' OR b.`access_starts` <= " . $db->qstr(time()) . ")
									AND (b.`access_expires`='0' OR b.`access_expires` >= " . $db->qstr(time()) . ")
									GROUP BY a.`id`";
						$result = $db->GetRow($query);
						if ($result) {
							// If $ENTRADA_USER was previously initialized in init.inc.php before the
							// session was authorized it is set to false and needs to be re-initialized.
							if ($ENTRADA_USER == false) {
								$ENTRADA_USER = User::get($result["id"]);
							}
							$_SESSION["details"]["id"] = $user_proxy_id = $result["id"];
							$_SESSION["details"]["access_id"] = $ENTRADA_USER->getAccessId();
							$_SESSION["details"]["username"] = $user_username = $result["username"];
							$_SESSION["details"]["firstname"] = $user_firstname = $result["firstname"];
							$_SESSION["details"]["lastname"] = $user_lastname = $result["lastname"];
							$_SESSION["details"]["email"] = $user_email = $result["email"];
							$_SESSION["details"]["role"] = $user_role = $result["role"];
							$_SESSION["details"]["group"] = $user_group = $result["group"];
							$_SESSION["details"]["organisation_id"] = $user_organisation_id = $result["organisation_id"];
							$_SESSION["details"]["app_id"] = AUTH_APP_ID;
							$_SESSION["details"]["grad_year"] = $result["grad_year"];
							$_SESSION["details"]["expires"] = $result["access_expires"];
						} else {
							/**
							 * If the query above fails, redirect them back here but without the
							 * private hash which will trigger the HTTP Authentication.
							 */
							header("Location: ".COMMUNITY_URL."/feeds".$path_info[0].":".$path_info[1]);
							exit;
						}
					} else {
						/**
						 * If they are not already authenticated, and they don't have a private
						 * hash in the URL, then send them through to HTTP authentication.
						 */
						if (!isset($_SERVER["PHP_AUTH_USER"])) {
							http_authenticate();
						} else {
							require_once("Entrada/authentication/authentication.class.php");

							$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
							$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
							$auth->setEncryption(AUTH_ENCRYPTION_METHOD);

							$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
							$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

							$auth->setUserAuthentication($username, $password, AUTH_METHOD);
							$result = $auth->Authenticate(
								array(
									"id",
									"prefix",
									"firstname",
									"lastname",
									"email",
									"telephone",
									"role",
									"group",
									"access_starts",
									"access_expires",
									"last_login",
									"privacy_level"
								)
							);
							if ($result["STATUS"] == "success") {
								if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
									$ERROR++;
									application_log("error", "User[".$username."] tried to access account prior to activation date.");
								} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
									$ERROR++;
									application_log("error", "User[".$username."] tried to access account after expiration date.");
								} else {
									$user_proxy_id	= $result["ID"];
									$user_firstname	= $result["FIRSTNAME"];
									$user_lastname	= $result["LASTNAME"];
									$user_email		= $result["EMAIL"];
									$user_role		= $result["ROLE"];
									$user_group		= $result["GROUP"];

									$member = $db->GetRow("SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($user_proxy_id)." AND `community_id` = ".$db->qstr($page_record["community_id"])." AND `member_active` = '1'");
									if ((!$member && $page_record["allow_troll_view"] ==  0) || ($member && $page_record["allow_member_view"] == 0 && $member["member_acl"] == 0)) {
										exit;
									}
								}
							} else {
								$ERROR++;
								application_log("access", $result["MESSAGE"]);
							}

							if ($ERROR) {
								http_authenticate();
							}

							unset($username, $password);
						}
					}
				}
			}

			switch ($feed_type) {
				case "calendar.ics" :
				case "ics" :
					require_once("Entrada/icalendar/class.ical.inc.php");

					$query		= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`, c.*, d.`community_title`
									FROM `community_events` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON b.`id` = a.`proxy_id`
									LEFT JOIN `community_pages` AS c
									ON c.`cpage_id` = a.`cpage_id`
									LEFT JOIN `communities` AS d
									ON a.`community_id` = d.`community_id`
									WHERE c.`cpage_id` = ".$db->qstr($page_record["cpage_id"])."
									AND a.`event_active` = '1'
									".(((int)$page_record["option_value"]) == 1 ? "AND a.`pending_moderation` = '0'" : "")."
									AND c.`page_active` = '1'
									ORDER BY a.`event_start` ASC";
					$results	= $db->GetAll($query);
					if ($results) {
						$community_title = $results[0]["community_title"];
						$ical = new iCal("-//".html_encode($_SERVER["HTTP_HOST"])."//iCal Learning Events Calendar MIMEDIR//EN", 1, ENTRADA_ABSOLUTE."/community/feeds".$community_url.":".$page_url."/ics", (str_replace(array("/"," ","_"),"-",$community_title."->".$page_record["menu_title"]))); // (ProgrammID, Method (1 = Publish | 0 = Request), Download Directory)
						foreach ($results as $result) {
							$ical->addEvent(
								array((($result["fullname"] != "") ? $result["fullname"] : ""), (($result["email"]) ? $result["email"] : "")), // Organizer
								(int) $result["event_start"], // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
								(int) $result["event_finish"], // End Time (write 'allday' for an allday event instead of a timestamp)
								$result["event_location"], // Location
								1, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
								array($result["community_title"]), // Array with Strings
								strip_tags(str_replace("<br />", " ", $result["event_description"])), // Description
								strip_tags($result["event_title"]), // Title
								1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
								array(), // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
								5, // Priority = 0-9
								0, // frequency: 0 = once, secoundly - yearly = 1-7
								0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
								0, // Interval for frequency (every 2,3,4 weeks...)
								array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
								date("w",((int) $result["event_start"])), // Startday of the Week ( 0 = Sunday - 6 = Saturday)
								"", // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
								0,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
								1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
								str_replace("http://", "https://", COMMUNITY_URL).$page_record["community_url"].":".$page_record["page_url"]."?id=".(int) $result["cevent_id"], // optional URL for that event
								"en", // Language of the Strings
								""
							);
						}

						if (!isset($ical->output)) {
							$ical->generateOutput();
						}

						header("Content-Disposition: inline; filename=\"".(str_replace(array("/"," ","_"),"-",$page_record["menu_title"])).".ics\"");
						header("Content-Type: text/calendar");

						echo $ical->output;
					} else {
						$community_title = $db->GetOne("SELECT `community_title` FROM `communities` AS a JOIN `community_pages` AS b ON a.`community_id` = b.`community_id` WHERE b.`cpage_id` = ".$db->qstr($page_record["cpage_id"]));
						if ($community_title) {
							$ical = new iCal("-//".html_encode($_SERVER["HTTP_HOST"])."//iCal Learning Events Calendar MIMEDIR//EN", 1, ENTRADA_ABSOLUTE."/community/feeds".$community_url.":".$page_url."/ics", (str_replace(array("/"," ","_"),"-",$community_title."->".$page_record["menu_title"]))); // (ProgrammID, Method (1 = Publish | 0 = Request), Download Directory)
							$ical->generateOutput();
							header("Content-Disposition: inline; filename=\"".(str_replace(array("/"," ","_"),"-",$page_record["menu_title"])).".ics\"");
							header("Content-Type: text/calendar");
							echo $ical->output;
						}
					}
				break;
				case "rss" :
				case "rss10" :
				case "rss20" :
				default :
					$query	= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) as `author_name`
								FROM `community_events` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON b.`id` = a.`proxy_id`
								WHERE a.`cpage_id` = ".$db->qstr($page_id)."
								AND a.`event_active` = '1'
								".(((int)$page_record["option_value"]) == 1 ? "AND a.`pending_moderation` = '0'" : "")."
								AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
								AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
								ORDER BY a.`release_date` DESC";
					$results = $db->GetAll($query);

					if ($results) {
						foreach ($results as $result) {
							$rss_output[$result["cevent_id"]] = $result;
						}

						unset($results);
					}

					$rss = new UniversalFeedCreator();
					$rss->useCached();
					$rss->title						= $page_record["community_title"]." : ".$page_record["menu_title"];
					$rss->description				= "Events from the ".$page_record["menu_title"]." page of the ".$page_record["community_title"]." community.";

					$rss->copyright					= str_replace("&copy;", "", COPYRIGHT_STRING);
					$rss->link						= ENTRADA_URL."/community".$page_record["community_url"].":".$page_record["page_url"];
					$rss->syndicationURL			= ENTRADA_URL."/community".$page_record["community_url"].":".$page_record["page_url"];
					$rss->descriptionHtmlSyndicated	= true;


					if ((is_array($rss_output)) && (count($rss_output))) {
						foreach ($rss_output as $result) {

							$description = (isset($result["event_location"]) && trim($result["event_location"]) != "" ? "Location: ".$result["event_location"]."<br /><br />" : "")."From: ".date(DEFAULT_DATE_FORMAT, $result["event_start"])."<br />To: ".date(DEFAULT_DATE_FORMAT, $result["event_finish"])."<br /><br />\n";
							$description .= $result["event_description"];

							$item								= new FeedItem();
							$item->title						= $result["event_title"];

							$item->link							= ENTRADA_URL."/community".$page_record["community_url"].":".$page_record["page_url"]."?id=".$result["cevent_id"].($page_record["community_protected"] || !$page_record["allow_public_view"] ? "&auth=true" : "");
							$item->date							= ((int) date("U", $result["event_start"]));
							$item->author						= $result["author_name"];
							$item->description					= $description;
							$item->descriptionHtmlSyndicated 	= true;

							$rss->addItem($item);
						}
					}
					header("Content-type: text/xml");
					echo $rss->createFeed($rss_version);
				break;
			}
		break;
		case "discussions" :
			if (isset($_GET["id"]) && ($tmp_input = (int) $_GET["id"])) {
				$discussion_id = $tmp_input;
			}

			if (isset($discussion_id) && ($discussion_id > 0)) {
				$query 				= "	SELECT a.*, b.*, c.* FROM `community_discussions` as a
										LEFT JOIN `community_pages` as b
										ON b.`cpage_id` = a.`cpage_id`
										LEFT JOIN `communities` as c
										ON c.`community_id` = b.`community_id`
										WHERE a.`cdiscussion_id` = ".$db->qstr($discussion_id)."
										AND a.`forum_active` = '1'
										AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
										AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
										AND c.`community_active` = '1'
										AND b.`page_active` = '1'";
				$discussion_record 	= $db->GetRow($query);
				if (($discussion_record) && ((int) $discussion_record["community_protected"]) == 1 || ((int) $discussion_record["allow_public_view"]) == 0 || ((int) $discussion_record["allow_public_read"]) == 0) {

					if (!$logged_in) {
						if ($user_private_hash) {
							$query = "  SELECT a.`id`, a.`username`, a.`firstname`, a.`lastname`, a.`email`, a.`grad_year`, b.`role`, b.`group`, b.`organisation_id`, b.`access_expires`
									FROM `" . AUTH_DATABASE . "`.`user_data` AS a
									LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
									ON b.`user_id` = a.`id`
									WHERE b.`private_hash` = " . $db->qstr($user_private_hash) . "
									AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
									AND b.`account_active` = 'true'
									AND (b.`access_starts`='0' OR b.`access_starts` <= " . $db->qstr(time()) . ")
									AND (b.`access_expires`='0' OR b.`access_expires` >= " . $db->qstr(time()) . ")
									GROUP BY a.`id`";
							$result = $db->GetRow($query);
							if ($result) {
								// If $ENTRADA_USER was previously initialized in init.inc.php before the
								// session was authorized it is set to false and needs to be re-initialized.
								if ($ENTRADA_USER == false) {
									$ENTRADA_USER = User::get($result["id"]);
								}
								$_SESSION["details"]["id"] = $user_proxy_id = $result["id"];
								$_SESSION["details"]["access_id"] = $ENTRADA_USER->getAccessId();
								$_SESSION["details"]["username"] = $user_username = $result["username"];
								$_SESSION["details"]["firstname"] = $user_firstname = $result["firstname"];
								$_SESSION["details"]["lastname"] = $user_lastname = $result["lastname"];
								$_SESSION["details"]["email"] = $user_email = $result["email"];
								$_SESSION["details"]["role"] = $user_role = $result["role"];
								$_SESSION["details"]["group"] = $user_group = $result["group"];
								$_SESSION["details"]["organisation_id"] = $user_organisation_id = $result["organisation_id"];
								$_SESSION["details"]["app_id"] = AUTH_APP_ID;
								$_SESSION["details"]["grad_year"] = $result["grad_year"];
								$_SESSION["details"]["expires"] = $result["access_expires"];
							} else {
								/**
								 * If the query above fails, redirect them back here but without the
								 * private hash which will trigger the HTTP Authentication.
								 */
								header("Location: ".COMMUNITY_URL."/feeds".$path_info[0].":".$path_info[1]);
								exit;
							}
						} else {
							/**
							 * If they are not already authenticated, and they don't have a private
							 * hash in the URL, then send them through to HTTP authentication.
							 */
							if (!isset($_SERVER["PHP_AUTH_USER"])) {
								http_authenticate();
							} else {
								require_once("Entrada/authentication/authentication.class.php");

								$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
								$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
								$auth->setEncryption(AUTH_ENCRYPTION_METHOD);

								$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
								$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

								$auth->setUserAuthentication($username, $password, AUTH_METHOD);
								$result = $auth->Authenticate(
									array(
										"id",
										"prefix",
										"firstname",
										"lastname",
										"email",
										"telephone",
										"role",
										"group",
										"access_starts",
										"access_expires",
										"last_login",
										"privacy_level"
									)
								);
								if ($result["STATUS"] == "success") {
									if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
										$ERROR++;
										application_log("error", "User[".$username."] tried to access account prior to activation date.");
									} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
										$ERROR++;
										application_log("error", "User[".$username."] tried to access account after expiration date.");
									} else {
										$user_proxy_id	= $result["ID"];
										$user_firstname	= $result["FIRSTNAME"];
										$user_lastname	= $result["LASTNAME"];
										$user_email		= $result["EMAIL"];
										$user_role		= $result["ROLE"];
										$user_group		= $result["GROUP"];

										$member = $db->GetRow("SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($user_proxy_id)." AND `community_id` = ".$db->qstr($page_record["community_id"])." AND `member_active` = '1'");
										if ((!$member && $page_record["allow_troll_view"] ==  0) || ($member && $page_record["allow_member_view"] == 0 && $member["member_acl"] == 0)) {
											exit;
										}
									}
								} else {
									$ERROR++;
									application_log("access", $result["MESSAGE"]);
								}

								if ($ERROR) {
									http_authenticate();
								}

								unset($username, $password);
							}
						}
					}
				}

				switch ($feed_type) {
					case "rss" :
					case "rss10" :
					case "rss20" :
					default :
						$query	= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) as `author_name`
									FROM `community_discussion_topics` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON b.`id` = a.`proxy_id`
									WHERE a.`cdiscussion_id` = ".$db->qstr($discussion_id)."
									AND a.`topic_active` = '1'
									AND a.`cdtopic_parent` = '0'
									AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
									AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
									ORDER BY a.`release_date` DESC";
						$results = $db->GetAll($query);
						if ($results) {
							foreach ($results as $result) {
								$rss_output[$result["cdtopic_id"]] = $result;
							}

							unset($results);
						}

						$rss = new UniversalFeedCreator();
						$rss->useCached();
						$rss->title						= $discussion_record["menu_title"]." : ".$discussion_record["forum_title"];
						$rss->description				= $discussion_record["forum_description"];

						$rss->copyright					= str_replace("&copy;", "", COPYRIGHT_STRING);
						$rss->link						= html_encode(ENTRADA_URL."/community".$discussion_record["community_url"].":".$discussion_record["page_url"]."?section=view-forum&id=".$discussion_record["cdiscussion_id"]);
						$rss->syndicationURL			= html_encode(ENTRADA_URL."/community".$discussion_record["community_url"].":".$discussion_record["page_url"]."?section=view-forum&id=".$discussion_record["cdiscussion_id"]);
						$rss->descriptionHtmlSyndicated	= true;

						if ((is_array($rss_output)) && (count($rss_output))) {
							foreach ($rss_output as $result) {

								$description = $result["topic_description"];

								$item								= new FeedItem();
								$item->title						= $result["topic_title"];

								$item->link							= ENTRADA_URL."/community".$discussion_record["community_url"].":".$discussion_record["page_url"]."?section=view-post&id=".$result["cdtopic_id"].($discussion_record["community_protected"] || !$discussion_record["allow_public_view"] ? "&auth=true" : "");
								$item->date							= ((int) date("U", $result["release_date"]));
								$item->author						= $result["author_name"];
								$item->description					= $description;
								$item->descriptionHtmlSyndicated 	= true;

								$rss->addItem($item);
							}
						}
						header("Content-type: text/xml");
						echo $rss->createFeed($rss_version);
					break;
				}
			} else {
				$query	 			= "	SELECT a.*, b.*, c.* FROM `community_discussions` as a
										LEFT JOIN `community_pages` as b
										ON b.`cpage_id` = a.`cpage_id`
										LEFT JOIN `communities` as c
										ON c.`community_id` = b.`community_id`
										WHERE a.`cpage_id` = ".$db->qstr($page_id)."
										AND a.`forum_active` = '1'
										AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
										AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
										AND c.`community_active` = '1'
										AND b.`page_active` = '1'";
				$discussion_record 	= $db->GetRow($query);
				if (($discussion_record) && ((int) $discussion_record["community_protected"]) == 1 || ((int) $discussion_record["allow_public_view"]) == 0 || ((int) $discussion_record["allow_public_read"]) == 0) {
					if (!$logged_in) {
						if ($user_private_hash) {
							$query = "  SELECT a.`id`, a.`username`, a.`firstname`, a.`lastname`, a.`email`, a.`grad_year`, b.`role`, b.`group`, b.`organisation_id`, b.`access_expires`
									FROM `" . AUTH_DATABASE . "`.`user_data` AS a
									LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
									ON b.`user_id` = a.`id`
									WHERE b.`private_hash` = " . $db->qstr($user_private_hash) . "
									AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
									AND b.`account_active` = 'true'
									AND (b.`access_starts`='0' OR b.`access_starts` <= " . $db->qstr(time()) . ")
									AND (b.`access_expires`='0' OR b.`access_expires` >= " . $db->qstr(time()) . ")
									GROUP BY a.`id`";
							$result = $db->GetRow($query);
							if ($result) {
								// If $ENTRADA_USER was previously initialized in init.inc.php before the
								// session was authorized it is set to false and needs to be re-initialized.
								if ($ENTRADA_USER == false) {
									$ENTRADA_USER = User::get($result["id"]);
								}
								$_SESSION["details"]["id"] = $user_proxy_id = $result["id"];
								$_SESSION["details"]["access_id"] = $ENTRADA_USER->getAccessId();
								$_SESSION["details"]["username"] = $user_username = $result["username"];
								$_SESSION["details"]["firstname"] = $user_firstname = $result["firstname"];
								$_SESSION["details"]["lastname"] = $user_lastname = $result["lastname"];
								$_SESSION["details"]["email"] = $user_email = $result["email"];
								$_SESSION["details"]["role"] = $user_role = $result["role"];
								$_SESSION["details"]["group"] = $user_group = $result["group"];
								$_SESSION["details"]["organisation_id"] = $user_organisation_id = $result["organisation_id"];
								$_SESSION["details"]["app_id"] = AUTH_APP_ID;
								$_SESSION["details"]["grad_year"] = $result["grad_year"];
								$_SESSION["details"]["expires"] = $result["access_expires"];
							} else {
								/**
								 * If the query above fails, redirect them back here but without the
								 * private hash which will trigger the HTTP Authentication.
								 */
								header("Location: ".COMMUNITY_URL."/feeds".$path_info[0].":".$path_info[1]);
								exit;
							}
						} else {
							/**
							 * If they are not already authenticated, and they don't have a private
							 * hash in the URL, then send them through to HTTP authentication.
							 */
							if (!isset($_SERVER["PHP_AUTH_USER"])) {
								http_authenticate();
							} else {
								require_once("Entrada/authentication/authentication.class.php");

								$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
								$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
								$auth->setEncryption(AUTH_ENCRYPTION_METHOD);

								$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
								$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

								$auth->setUserAuthentication($username, $password, AUTH_METHOD);
								$result = $auth->Authenticate(
									array(
										"id",
										"prefix",
										"firstname",
										"lastname",
										"email",
										"telephone",
										"role",
										"group",
										"access_starts",
										"access_expires",
										"last_login",
										"privacy_level"
									)
								);
								if ($result["STATUS"] == "success") {
									if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
										$ERROR++;
										application_log("error", "User[".$username."] tried to access account prior to activation date.");
									} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
										$ERROR++;
										application_log("error", "User[".$username."] tried to access account after expiration date.");
									} else {
										$user_proxy_id	= $result["ID"];
										$user_firstname	= $result["FIRSTNAME"];
										$user_lastname	= $result["LASTNAME"];
										$user_email		= $result["EMAIL"];
										$user_role		= $result["ROLE"];
										$user_group		= $result["GROUP"];

										$member = $db->GetRow("SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($user_proxy_id)." AND `community_id` = ".$db->qstr($page_record["community_id"])." AND `member_active` = '1'");
										if ((!$member && $page_record["allow_troll_view"] ==  0) || ($member && $page_record["allow_member_view"] == 0 && $member["member_acl"] == 0)) {
											exit;
										}
									}
								} else {
									$ERROR++;
									application_log("access", $result["MESSAGE"]);
								}

								if ($ERROR) {
									http_authenticate();
								}

								unset($username, $password);
							}
						}
					}
				}

				switch ($feed_type) {
					case "rss" :
					case "rss10" :
					case "rss20" :
					default :
						$query	= "SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($user_proxy_id)." AND `community_id` = ".$db->qstr($discussion_record["community_id"])." AND `member_active` = '1'";
						$member = $db->GetRow($query);
						if ($member) {
							$query	= "	SELECT a.*, CONCAT_WS(' ', c.`firstname`, c.`lastname`) as `author_name`
										FROM `community_discussion_topics` AS a
										LEFT JOIN `community_discussions` AS b
										ON a.`cdiscussion_id` = b.`cdiscussion_id`
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
										ON c.`id` = a.`proxy_id`
										WHERE b.`cpage_id` = ".$db->qstr($page_id)."
										".((!$logged_in) ? " AND b.`allow_public_read` = '1'" : (($member) ? (($member["member_acl"] == '1') ? " AND b.`allow_member_read` = '1'" : "") : " AND b.`allow_troll_read` = '1'"))."
										".(($member["member_acl"] != '1') ? " AND (b.`release_date` = '0' OR b.`release_date` <= ".$db->qstr(time()).") AND (b.`release_until` = '0' OR b.`release_until` > ".$db->qstr(time()).")" : "")."
										AND b.`forum_active` = '1'
										AND a.`topic_active` = '1'
										AND a.`cdtopic_parent` = '0'
										AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
										AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
										ORDER BY a.`release_date` DESC";
							$results = $db->GetAll($query);

							if ($results) {
								foreach ($results as $result) {
									$rss_output[$result["cdtopic_id"]] = $result;
								}

								unset($results);
							}

							$rss = new UniversalFeedCreator();
							$rss->useCached();
							$rss->title						= $discussion_record["community_title"]." : ".$discussion_record["menu_title"];
							$rss->description				= $discussion_record["page_content"];

							$rss->copyright					= str_replace("&copy;", "", COPYRIGHT_STRING);
							$rss->link						= html_encode(ENTRADA_URL."/community".$discussion_record["community_url"].":".$discussion_record["page_url"]);
							$rss->syndicationURL			= html_encode(ENTRADA_URL."/community".$discussion_record["community_url"].":".$discussion_record["page_url"]);
							$rss->descriptionHtmlSyndicated	= true;

							if ((is_array($rss_output)) && (count($rss_output))) {
								foreach ($rss_output as $result) {

									$description = $result["topic_description"];

									$item								= new FeedItem();
									$item->title						= $result["topic_title"];

									$item->link							= ENTRADA_URL."/community".$discussion_record["community_url"].":".$discussion_record["page_url"]."?section=view-post&id=".$result["cdtopic_id"].($discussion_record["community_protected"] || !$discussion_record["allow_public_view"] ? "&auth=true" : "");
									$item->date							= ((int) date("U", $result["release_date"]));
									$item->author						= $result["author_name"];
									$item->description					= $description;
									$item->descriptionHtmlSyndicated 	= true;

									$rss->addItem($item);
								}
							}
							header("Content-type: text/xml");
							echo $rss->createFeed($rss_version);
						}
					break;
				}
			}
		break;
		default :
			application_log("error", "The community module [".$module_name."] requested by proxy_id [".$user_proxy_id."] was unable to be loaded by the serve-feed.php file.");
		break;
	}
	/**
	 * @todo Does this elseif need to be here? If $_SERVER["PATH_INFO"] is not set, $community_url and $feed_type never get initialized and thus could not be equal to $_SERVER["PATH_INFO"].
	 *
	 */
} elseif ($community_url.($feed_type ? "/".$feed_type : "") == $_SERVER["PATH_INFO"]) {
	$query				= "	SELECT *
							FROM `communities`
							WHERE `community_id` = ".$db->qstr($community_id)."
							AND `community_active` = '1'";
	$community_record	= $db->GetRow($query);
	if (($community_record) && ((int) $community_record["community_protected"]) == 1) {
		if (!$logged_in) {
			if (!isset($_SERVER["PHP_AUTH_USER"])) {
				http_authenticate();
			} else {
				require_once("Entrada/authentication/authentication.class.php");

				$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
				$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
				$auth->setEncryption(AUTH_ENCRYPTION_METHOD);

				$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
				$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

				$auth->setUserAuthentication($username, $password, AUTH_METHOD);
				$result = $auth->Authenticate(
										array(
												"id",
												"prefix",
												"firstname",
												"lastname",
												"email",
												"telephone",
												"role",
												"group",
												"access_starts",
												"access_expires",
												"last_login",
												"privacy_level"
											)
										);
				if ($result["STATUS"] == "success") {
					if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
						$ERROR++;
						application_log("error", "User[".$username."] tried to access account prior to activation date.");
					} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
						$ERROR++;
						application_log("error", "User[".$username."] tried to access account after expiration date.");
					} else {
						$user_proxy_id	= $result["ID"];
						$user_firstname	= $result["FIRSTNAME"];
						$user_lastname	= $result["LASTNAME"];
						$user_email		= $result["EMAIL"];
						$user_role		= $result["ROLE"];
						$user_group		= $result["GROUP"];

						$member = $db->GetRow("SELECT * FROM `community_members` WHERE `proxy_id` = ".$db->qstr($user_proxy_id)." AND `community_id` = ".$db->qstr($community_id)." AND `member_active` = '1'");
						if (!$member && $community_record["community_protected"] == 1) {
							exit;
						}
					}
				} else {
					$ERROR++;
					application_log("access", $result["MESSAGE"]);
				}

				if ($ERROR) {
					http_authenticate();
				}

				unset($username, $password);
			}
		}
	}

	switch ($feed_type) {
		case "calendar.ics" :
		case "ics" :
			require_once("Entrada/icalendar/class.ical.inc.php");

			$query		= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`, c.*, d.`community_title`
							FROM `community_events` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = a.`proxy_id`
							LEFT JOIN `community_pages` AS c
							ON c.`cpage_id` = a.`cpage_id`
							LEFT JOIN `communities` AS d
							ON a.`community_id` = d.`community_id`
							WHERE c.`cpage_id` = ".$db->qstr($page_record["cpage_id"])."
							AND a.`event_active` = '1'
							AND c.`page_active` = '1'
							ORDER BY a.`event_start` ASC";
			$results	= $db->GetAll($query);
			if ($results) {
				$community_title = $results[0]["community_title"];
				$ical = new iCal("-//".html_encode($_SERVER["HTTP_HOST"])."//iCal Learning Events Calendar MIMEDIR//EN", 1, ENTRADA_ABSOLUTE."/community/feeds".$community_url.":".$page_url."/ics", (str_replace(array("/"," ","_"),"-",$community_title."->".$page_record["menu_title"]))); // (ProgrammID, Method (1 = Publish | 0 = Request), Download Directory)
				foreach ($results as $result) {
					$ical->addEvent(
						array((($result["fullname"] != "") ? $result["fullname"] : ""), (($result["email"]) ? $result["email"] : "")), // Organizer
						(int) $result["event_start"], // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
						(int) $result["event_finish"], // End Time (write 'allday' for an allday event instead of a timestamp)
						$result["event_location"], // Location
						1, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
						array($result["community_title"]), // Array with Strings
						strip_tags(str_replace("<br />", " ", $result["event_description"])), // Description
						strip_tags($result["event_title"]), // Title
						1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
						array(), // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
						5, // Priority = 0-9
						0, // frequency: 0 = once, secoundly - yearly = 1-7
						0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
						0, // Interval for frequency (every 2,3,4 weeks...)
						array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
						date("w",((int) $result["event_start"])), // Startday of the Week ( 0 = Sunday - 6 = Saturday)
						"", // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
						0,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
						1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
						str_replace("http://", "https://", COMMUNITY_URL).$page_record["community_url"].":".$page_record["page_url"]."?id=".(int) $result["cevent_id"], // optional URL for that event
						"en", // Language of the Strings
						""
					);
				}

				if (!isset($ical->output)) {
					$ical->generateOutput();
				}

				header("Content-Disposition: inline; filename=\"".(str_replace(array("/"," ","_"),"-",$page_record["menu_title"])).".ics\"");
				header("Content-Type: text/calendar");

				echo $ical->output;
			}
		break;
		case "rss" :
		case "rss10" :
		case "rss20" :
		default :
			$query	= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) as `author_name`, b.`email`
						FROM `community_history` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON b.`id` = a.`proxy_id`
						WHERE a.`community_id` = ".$db->qstr($community_id)."
						AND a.`history_display` = '1'
						ORDER BY a.`history_timestamp` DESC
						LIMIT 0, 30";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$rss_output[$result["chistory_id"]] = $result;
				}

				unset($results);
			}

			$rss = new UniversalFeedCreator();
			$rss->useCached();
			$rss->title						= $community_record["community_title"];
			$rss->description				= "Activity in the ".$community_record["community_title"]." community.";

			$rss->copyright					= str_replace("&copy;", "", COPYRIGHT_STRING);
			$rss->link						= ENTRADA_URL."/community".$community_record["community_url"];
			$rss->syndicationURL			= ENTRADA_URL."/community".$community_record["community_url"];
			$rss->descriptionHtmlSyndicated	= true;

			if ((is_array($rss_output)) && (count($rss_output))) {
				/**
				 * Setup Zend_Translate for language file support.
				 */
				if ($ENTRADA_CACHE) Entrada_Translate::setCache($ENTRADA_CACHE);

				$translate = new Entrada_Translate("array", ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/languages/".DEFAULT_LANGUAGE.".lang.php", DEFAULT_LANGUAGE);

				foreach ($rss_output as $result) {

					if ((int)$result["cpage_id"] && ($result["history_key"] != "community_history_activate_module")) {
						$query		= "	SELECT `page_url`
										FROM `community_pages`
										WHERE `cpage_id` = ".$db->qstr($result["cpage_id"])."
										AND `community_id` = ".$db->qstr($result["community_id"])."
										AND	`page_active` = '1'";
						$page_url	= $db->GetOne($query);
					} elseif ($result["history_key"] == "community_history_activate_module") {
						$query		= "	SELECT a.`page_url`
										FROM `community_pages` AS a
										JOIN `communities_modules` AS b
										ON b.`module_shortname` = a.`page_type`
										WHERE b.`module_id` = ".$db->qstr($result["record_id"])."
										AND a.`community_id` = ".$db->qstr($result["community_id"])."
										AND a.`page_active` = '1'";
						$page_url	= $db->GetOne($query);
					}

					if ($result["history_key"]) {
						$history_message	= $translate->_($result["history_key"]);
						$record_title		= "";
						$parent_id			= 0;

						community_history_record_title($result["history_key"], $result["record_id"], $result["cpage_id"], $result["community_id"]);
					} else {
						$history_message	= $result["history_message"];
					}

					$content_search						= array("%SITE_COMMUNITY_URL%", "%SYS_PROFILE_URL%", "%PAGE_URL%", "%RECORD_ID%", "%RECORD_TITLE%", "%PARENT_ID%");
					$content_replace					= array(COMMUNITY_URL.$community_url, ENTRADA_URL."/people", $page_url, $result["record_id"], $record_title, $parent_id);
					$history_message					= str_replace($content_search, $content_replace, $history_message);

					$item								= new FeedItem();

					$link = substr($history_message, (stripos($history_message, "href=\"") + 6));
					$link = substr($link, 0, (stripos($link, "\"")));
					$item->link							= (isset($link) && $link ? $link : COMMUNITY_URL.$community_url).(strpos($link, "?") ? "&" : "?")."auth=true";
					$item->title						= strip_tags(html_decode($history_message));
					$item->date							= ((int) date("U", $result["history_timestamp"]));
					$item->author						= $result["email"]." (".$result["author_name"].")";
					$item->description					= strip_tags($history_message);
					$item->descriptionHtmlSyndicated 	= true;

					$rss->addItem($item);
				}
			}

			header("Content-type: text/xml");
			echo $rss->createFeed($rss_version);
		break;
	}
}
?>