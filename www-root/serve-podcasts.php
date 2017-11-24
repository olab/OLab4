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
 * Outputs a properly formatted podcast feed of any podcasts attached to
 * learning events in the requested channels. Please note this file uses HTTP
 * Authentication and requires mod_auth_mysql to available with Apache.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: serve-podcasts.php 1171 2010-05-01 14:39:27Z ad29 $
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

$ACTION				= "feed";

$USER_PROXY_ID		= 0;
$USER_FIRSTNAME		= "";
$USER_LASTNAME		= "";
$USER_EMAIL			= "";
$USER_ROLE			= "";
$USER_GROUP			= "";

$CHANNELS			= array();

if(!isset($_SERVER["PHP_AUTH_USER"])) {
	http_authenticate();
} else {
	require_once("Entrada/authentication/authentication.class.php");

	$username = clean_input($_SERVER["PHP_AUTH_USER"], "credentials");
	$password = clean_input($_SERVER["PHP_AUTH_PW"], "trim");

	$auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));	
	$auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
	$auth->setEncryption(AUTH_ENCRYPTION_METHOD);
	$auth->setUserAuthentication($username, $password, AUTH_METHOD);
	$result = $auth->Authenticate(array("id", "firstname", "lastname", "email", "role", "group"));

	$ERROR = 0;
	if($result["STATUS"] == "success") {
		if(($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
			$ERROR++;
			application_log("error", "User[".$username."] tried to access account prior to activation date.");
		} elseif(($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
			$ERROR++;
			application_log("error", "User[".$username."] tried to access account after expiration date.");
		} else {
			$USER_PROXY_ID	= $result["ID"];
			$USER_FIRSTNAME	= $result["FIRSTNAME"];
			$USER_LASTNAME	= $result["LASTNAME"];
			$USER_EMAIL		= $result["EMAIL"];
			$USER_ROLE		= $result["ROLE"];
			$USER_GROUP		= $result["GROUP"];
			$ENTRADA_USER = User::get($result["ID"]);
		}
	} else {
		$ERROR++;
		application_log("access", $result["MESSAGE"]);
	}
	
	if($ERROR) {
		http_authenticate();
	}

	unset($username, $password);
}

if((isset($_GET["request"])) && (trim($_GET["request"]))) {
	$pieces = explode("/", trim($_GET["request"]));

	if($pieces[0] == "download") {
		$ACTION = "download";

		if((isset($pieces[1])) && ((int) trim($pieces[1]))) {
			$EFILE_ID = (int) trim($pieces[1]);
		} else {
			$EFILE_ID = 0;
		}
	}
}

switch($ACTION) {
	case "download" :
		if($EFILE_ID) {
			$query	= "SELECT * FROM `event_files` WHERE `efile_id` = ".$db->qstr($EFILE_ID);
			$result	= $db->GetRow($query);
			if($result) {
				$download	= $result["accesses"];
				$filename	= $result["file_name"];
				$filetype	= $result["file_type"];
				$filesize	= $result["file_size"];

				if(((int) $result["release_date"]) && ($result["release_date"] > time())) {
					exit;
				} else {
					if(((int) $result["release_until"]) && ($result["release_until"] < time())) {
						exit;
					} else {
						if((@file_exists(FILE_STORAGE_PATH."/".$EFILE_ID)) && (@is_readable(FILE_STORAGE_PATH."/".$EFILE_ID))) {
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: ".$result["file_type"]."");
							header("Content-Disposition: attachment; filename=\"".$result["file_name"]."\"");
							header("Content-Length: ".filesize(FILE_STORAGE_PATH."/".$EFILE_ID));
							header("Content-Transfer-Encoding: binary");
							
							echo file_get_contents(FILE_STORAGE_PATH."/".$EFILE_ID, FILE_BINARY);
							
							$db->Execute("UPDATE `event_files` SET `accesses` = '".($download + 1)."' WHERE `efile_id` = ".$db->qstr($EFILE_ID));

							add_statistic("podcasts", "file_download", "file_id", $EFILE_ID, $USER_PROXY_ID);
							exit;
						} else {
							exit;
						}
					}
				}
			} else {
				exit;
			}
		} else {
			exit;
		}
	break;
	case "feed" :
	default :
		switch($USER_GROUP) {
			case "faculty" :
			case "resident" :
				/*
				This code block will get just the Podcasts in courses where the teacher is teaching this year.
				I've commented it out because at the beginning here, there aren't that many podcasts.
				if(date("n", time()) < 9) {
					$year_from	= date("Y", time());
					$year_until	= (date("Y", time()) + 1);
				} else {
					$year_from	= (date("Y", time()) - 1);
					$year_until	= date("Y", time());
				}

				$date_from	= mktime(0, 0, 0, 9, 1, $year_from);
				$date_until	= mktime(23, 59, 59, 5, 31, $year_until);

				$query	= "
						SELECT DISTINCT(a.`course_id`) AS `course_ids`
						FROM `events` AS a
						LEFT JOIN `event_contacts` AS b
						ON b.`event_id` = a.`event_id`
						WHERE b.`proxy_id` = ".$db->qstr($USER_PROXY_ID).")
						AND (a.`event_start` BETWEEN ".$db->qstr($date_from)." AND ".$db->qstr($date_until).")";
				$results	= $db->GetAll($query);
				if($results) {
					foreach($results as $result) {
						$CHANNELS[] = "course:".$result["course_ids"];
					}
				} else {
					$CHANNELS[] = "student";
				}
				*/
				$CHANNELS[] = "student";
			break;
			case "staff" :
				$CHANNELS[] = "student";
			break;
			case "medtech" :
				$CHANNELS[] = "student";
			break;
			case "student" :
			default :
				$cohort_array = groups_get_cohort($USER_PROXY_ID);
				$CHANNELS[] = "student:".$cohort_array["group_id"];
			break;
		}

		$CHANNELS = array_unique($CHANNELS);

		if((is_array($CHANNELS)) && (count($CHANNELS))) {
			foreach($CHANNELS as $channel) {
				$pieces = explode(":", $channel);

				switch($pieces[0]) {
					case "course" :
						if((!isset($pieces[1])) || (!$course_id = (int) $pieces[1])) {
							$course_id = 0;
						}

						$query	= "
								SELECT a.*, b.`efile_id`, b.`file_type`, b.`file_size`, b.`file_name`, b.`file_title`, b.`file_notes`, c.`audience_value` AS `event_cohort`
								FROM `events` AS a
								LEFT JOIN `event_files` AS b
								ON b.`event_id` = a.`event_id`
								LEFT JOIN `event_audience` AS c
								ON c.`event_id` = a.`event_id`
								WHERE ".(($course_id) ? "a.`course_id` = ".$db->qstr((int) $course_id)." AND " : "")."
								b.`file_category` = 'podcast'
								AND b.`file_type` IN ('".implode("', '", $VALID_PODCASTS)."')
								AND (b.`release_date` = '0' OR b.`release_date` <= ".$db->qstr(time()).")
								AND (b.`release_until` = '0' OR b.`release_until` > ".$db->qstr(time()).")
								AND c.`audience_type` = 'cohort'
								ORDER BY a.`event_start` DESC
								LIMIT 0, 150";

						$results = $db->GetAll($query);
						if($results) {
							foreach($results as $result) {
								if(!is_array($PODCAST_OUTPUT[$result["efile_id"]])) {
									$PODCAST_OUTPUT[$result["efile_id"]] = $result;
								}
							}
							
							unset($results);
						}
					break;
					case "student" :
						if((!isset($pieces[1])) || (!$cohort = (int) $pieces[1])) {
							$cohort = 0;
						}

						$query  	=	"
									SELECT a.*, b.`efile_id`, b.`file_type`, b.`file_size`, b.`file_name`, b.`file_title`, b.`file_notes`, c.`audience_value` AS `event_cohort`
									FROM `events` AS a
									LEFT JOIN `event_files` AS b
									ON b.`event_id` = a.`event_id`
									LEFT JOIN `event_audience` AS c
									ON c.`event_id` = a.`event_id`
									WHERE b.`file_category` = 'podcast'
									AND b.`file_type` IN ('".implode("', '", $VALID_PODCASTS)."')
									AND (b.`release_date` = '0' OR b.`release_date` <= ".$db->qstr(time()).")
									AND (b.`release_until` = '0' OR b.`release_until` > ".$db->qstr(time()).")
									AND (c.`audience_type` = 'cohort'".(((int) $cohort) ? " AND c.`audience_value` = ".$db->qstr((int) $cohort) : "").")
									ORDER BY a.`event_start` DESC
									LIMIT 0, 150";
						$results	= $db->GetAll($query);

						if($results) {
							foreach($results as $result) {
								if(!is_array($PODCAST_OUTPUT[$result["efile_id"]])) {
									$PODCAST_OUTPUT[$result["efile_id"]] = $result;
								}
							}
							
							unset($results);
						}
					break;
					default :
						continue;
					break;
				}
			}
		}

		$rss = new UniversalFeedCreator();
		$rss->useCached();
		$rss->title						= $USER_FIRSTNAME." ".$USER_LASTNAME."'s School of Medicine Podcasts";
		$rss->description				= "Learning event podcasts from the School of Medicine at Queen's University";
		$rss->copyright					= "Copyright ".date("Y", time())." Queen's University. All Rights Reserved.";
		$rss->link						= ENTRADA_URL;
		$rss->syndicationURL			= ENTRADA_URL."/podcasts";
		$rss->descriptionHtmlSyndicated	= true;

		$image							= new FeedImage();
		$image->title					= "Podcasts at School of Medicine, Queen's University";
		$image->url						= ENTRADA_URL."/images/podcast-school-of-medicine.png";
		$image->link					= ENTRADA_URL;
		$image->description				= "Podcast feed provided by School of Medicine, Powered by MEdTech";

		$rss->image						= $image;

		$rss->podcast = new Podcast();
		$rss->podcast->block			= "yes";
		$rss->podcast->subtitle			= "Latest podcasts from learning events in the School of Medicine at Queen's University.";
		$rss->podcast->author			= "School of Medicine, Queen's University";
		$rss->podcast->owner_email		= "medtech@queensu.ca";
		$rss->podcast->owner_name		= "School of Medicine, Queen's University";

		/**
		 * iTunes Podcast Category Details
		 */
		$category_1 = new PodcastCategory("Education");
		$category_2 = new PodcastCategory("Higher Education");
		$category_1->addCategory($category_2);
		$rss->podcast->addCategory($category_1);

		if((is_array($PODCAST_OUTPUT)) && (count($PODCAST_OUTPUT))) {
			foreach($PODCAST_OUTPUT as $result) {

				$primary_contact		= array();
				$other_contacts			= array();
				$other_contacts_names	= array();
				
				$squery		= "
							SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
							FROM `event_contacts` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON b.`id` = a.`proxy_id`
							WHERE a.`event_id` = ".$db->qstr($result["event_id"])."
							AND b.`id` IS NOT NULL
							ORDER BY a.`contact_order` ASC";
				$sresults	= $db->GetAll($squery);
				if($sresults) {
					foreach($sresults as $key => $sresult) {
						if(!(int) $key) {
							$primary_contact		= array("proxy_id" => $sresult["proxy_id"], "fullname" => $sresult["fullname"], "email" => $sresult["email"]);
						} else {
							$other_contacts[]		= array("proxy_id" => $sresult["proxy_id"], "fullname" => $sresult["fullname"], "email" => $sresult["email"]);
						}
					}
				}

				$description  = "Course: ".(($result["course_id"]) ? "<a href=\"".ENTRADA_URL."/courses?id=".$result["course_id"]."\">".fetch_course_title($result["course_id"])."</a> ".(($result["course_num"]) ? "(".$result["course_num"].")" : "") : "Not Filed")."<br  />";
				$description .= "Associated Faculty:";
				$description .= "<ol>";
				if(count($primary_contact)) {
					$description .= "<li>".html_encode($primary_contact["fullname"]).": <a href=\"mailto:".$primary_contact["email"]."\">".$primary_contact["email"]."</a></li>";
				
					if(count($other_contacts)) {
						foreach($other_contacts as $other_contact) {
							$description .= "<li>".html_encode($other_contact["fullname"]).": <a href=\"mailto:".$other_contact["email"]."\">".$other_contact["email"]."</a></li>";
						}
					}
				} else {
					$description .= "<li>To Be Announced</li>";
				}
				$description .= "</ol><br /><br />";

				$description .= "Cohort: ".html_encode(groups_get_name($result["event_cohort"]))."<br />";
				$description .= "Phase: ".strtoupper($result["event_phase"])."<br />";
				$description .= "Event Date/Time: ".date(DEFAULT_DATE_FORMAT, $result["event_start"])."<br />";
				$description .= "Event Duration: ".(($result["event_duration"]) ? $result["event_duration"]." minutes" : "Not provided")."<br />";
				$description .= "Event Location: ".(($result["event_location"]) ? $result["event_location"] : "Not provided")."<br />";
				$description .= "<br />Podcast Description / Details:<br />";
				$description .= html_encode($result["event_message"]);

				$item			= new FeedItem();
				$item->title	= $result["event_title"].": ".$result["file_title"];
				$item->link		= ENTRADA_URL."/events?id=".$result["event_id"];
				$item->date		= date("r", $result["event_start"]);

				$item->description	= $description;
				$item->descriptionHtmlSyndicated	= true;

				$item->podcast						= new PodcastItem();
				$item->podcast->block				= "yes";
				$item->podcast->author				= $primary_contact["fullname"];

				$item->podcast->duration			= ($result["event_duration"] * 60);
				$item->podcast->enclosure_url		= ENTRADA_URL."/podcasts/download/".$result["efile_id"]."/".$result["file_name"];
				$item->podcast->enclosure_length	= $result["file_size"];
				$item->podcast->enclosure_type		= $result["file_type"];

				$rss->addItem($item);
			}
		}
		echo $rss->createFeed("PODCAST");

		add_statistic("podcasts", "view", "proxy_id", $USER_PROXY_ID, $USER_PROXY_ID);
	break;
}