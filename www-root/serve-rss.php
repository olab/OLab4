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
 * Outputs a properly formatted rss feed that will contain all notifications from
 * selected channels. Please note this file uses HTTP
 * Authentication and requires mod_auth_mysql to available with Apache.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: serve-podcasts.php 485 2009-06-29 18:04:21Z hbrundage $
 *
 */

header("Content-type: text/xml");

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/core",
    dirname(__FILE__) . "/core/includes",
    dirname(__FILE__) . "/core/library",
    dirname(__FILE__) . "/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
require_once("Entrada/feedcreator/feedcreator.class.php");

$PODCAST_OUTPUT		= array();

$ACTION				= "notices";

$USER_PROXY_ID		= 0;
$USER_FIRSTNAME		= "";
$USER_LASTNAME		= "";
$USER_EMAIL			= "";
$USER_ROLE			= "";
$USER_GROUP			= "";

if (!isset($_SERVER["PHP_AUTH_USER"])) {
	http_authenticate();
} else {
	require_once("Entrada/authentication/authentication.class.php");

	$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
	$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

	$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
	$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
	$auth->setEncryption(AUTH_ENCRYPTION_METHOD);
	$auth->setUserAuthentication($username, $password, AUTH_METHOD);
	$result = $auth->Authenticate(array("id", "firstname", "lastname", "email", "role", "group", "organisation_id"));

	$ERROR = 0;
	if ($result["STATUS"] == "success") {
		if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
			$ERROR++;
			application_log("error", "User[".$username."] tried to access account prior to activation date.");
		} elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
			$ERROR++;
			application_log("error", "User[".$username."] tried to access account after expiration date.");
		} else {
			$USER_PROXY_ID				= $result["ID"];
			$USER_FIRSTNAME				= $result["FIRSTNAME"];
			$USER_LASTNAME				= $result["LASTNAME"];
			$USER_EMAIL					= $result["EMAIL"];
			$USER_ROLE					= $result["ROLE"];
			$USER_GROUP					= $result["GROUP"];
			$USER_ORGANISATION_ID		= $result["ORGANISATION_ID"];
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

if (isset($_GET["c"]) && (trim($_GET["c"]))) {
	$ACTION = "channel";
	$CHANNEL_ID = clean_input($_GET["c"], array("int"));
}

switch ($ACTION) {
	case "channel":
		continue;
	break;
	case "notices":
		$organisation = $db->GetRow("SELECT organisation_title FROM ".AUTH_DATABASE.".organisations WHERE organisation_id = ".$USER_ORGANISATION_ID);
		$rss = new UniversalFeedCreator();
		$rss->useCached();
		$rss->title				= APPLICATION_NAME." RSS notices for ".$USER_FIRSTNAME.' '.$USER_LASTNAME;
		$rss->description		= html_decode("Announcements, Schedule Changes, Updates and more".isset($organisation['title']) ? ' from '.$organisation['title'] : '.');
		$rss->descriptionHtmlSyndicated = true;
		$rss->link				= ENTRADA_URL."/dashboard";
		$rss->syndicationURL	= ENTRADA_URL."/rss/";

		switch($USER_GROUP){
			case "student" :
			case "alumni" :				
				$corrected_role = "student";
				break;
			case "medtech" :
				$corrected_role = "staff";
				break;
			default :
				$corrected_role = $USER_GROUP;
		}
		
		
		
		$notice_where_clause = "(
						(
						c.`audience_type` = 'all:all'
						OR c.`audience_type` = 'all:".$corrected_role."'
						OR
						(
							c.`audience_type` = 'students' 
							OR c.`audience_type` = 'faculty' 
							OR c.`audience_type` = 'staff') 
							AND c.`audience_value` = ".$db->qstr($ENTRADA_USER->getID())."
						) 
						OR ((
							c.`audience_type` = 'cohorts' 
							OR c.`audience_type` = 'course_list') 
							AND c.`audience_value` IN (
								SELECT `group_id` 
								FROM `group_members` 
								WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID()).")
						)
					)";
//		switch ($USER_GROUP) {
//			case "alumni" :
//				$old_notice_where_clause	= "(a.`target` = 'all' OR a.`target` = 'alumni' OR a.`target` = ".$db->qstr("proxy_id:".((int) $USER_PROXY_ID)).")";
//			break;
//			case "faculty" :
//				$old_notice_where_clause	= "(a.`target` = 'all' OR a.`target` = 'faculty' OR a.`target` = ".$db->qstr("proxy_id:".((int) $USER_PROXY_ID)).")";
//			break;
//			case "medtech" :
//				$old_notice_where_clause	= "(a.`target` NOT LIKE 'proxy_id:%' OR a.`target` = ".$db->qstr("proxy_id:".((int) $USER_PROXY_ID)).")";
//			break;
//			case "resident" :
//				$old_notice_where_clause	= "(a.`target` = 'all' OR a.`target` = 'resident' OR a.`target` = ".$db->qstr("proxy_id:".((int) $USER_PROXY_ID)).")";
//			break;
//			case "staff" :
//				$old_notice_where_clause	= "(a.`target` = 'all' OR a.`target` = 'staff' OR a.`target` = ".$db->qstr("proxy_id:".((int) $USER_PROXY_ID)).")";
//			break;
//			case "student" :
//			default :
//				if ($_SESSION["details"]["group"] == "student") {
//					$cohort = groups_get_cohort($USER_PROXY_ID);
//				}
//				$old_notice_where_clause	= "(".(($_SESSION["details"]["group"] == "student") ? "a.`target`='".(int) $cohort["group_id"]."' OR " : "")."a.`target` = 'all' OR a.`target` = 'students' OR a.`target` = ".$db->qstr("proxy_id:".((int) $USER_PROXY_ID)).")";
//			break;
//		}
		$notice_where_clause .= 'AND (a.`organisation_id` IS NULL OR a.`organisation_id` = '.$USER_ORGANISATION_ID.')';
		$organisation = $db->GetRow("SELECT `organisation_title` FROM ".AUTH_DATABASE.".`organisations` WHERE `organisation_id` = ".$USER_ORGANISATION_ID);
		$query = "	SELECT a.*
					FROM `notices` AS a
					JOIN `notice_audience` AS c
					ON a.`notice_id` = c.`notice_id`
					WHERE ".(($notice_where_clause) ? $notice_where_clause." AND" : "")."
					(a.`display_from`='0' OR a.`display_from` <= '".time()."')
					ORDER BY a.`updated_date` DESC, a.`display_from` DESC";
		$results = ((USE_CACHE) ? $db->CacheGetAll(CACHE_TIMEOUT, $query) : $db->GetAll($query));
		if ($results) {
			foreach ($results as $result) {
				$item = new FeedItem();
				$item->title = "New Notice: ".date(DEFAULT_DATE_FORMAT, (int) $result["display_from"]);
				$item->link = ENTRADA_URL."/dashboard";
				$item->description = html_decode($result["notice_summary"]);
				$item->descriptionHtmlSyndicated = true;
				$item->date = unixstamp_to_iso8601(((int) $result["display_from"]) ? $result["display_from"] : time());
				$item->source = ENTRADA_URL."/dashboard";
				$item->author = isset($organisation["title"]) ? $organisation["title"] : APPLICATION_NAME;
				$rss->addItem($item);
			}
		}
		
		echo $rss->createFeed();

		add_statistic("rss", "view", "proxy_id", $USER_PROXY_ID);
	break;
}