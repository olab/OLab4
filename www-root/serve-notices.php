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
 * Outputs all Entrada Notices from the Manage Notices module as an RSS feed
 * for the requested target.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: serve-notices.php 1171 2010-05-01 14:39:27Z ad29 $
 * 
 * @example This can be used like serve-notices.php?g=2012 or if you have
 * the mod_rewrite rule configured like you should you simply go:
 * /notices/2012
 * 
 */

header("Content-type: text/xml");

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

$TARGET		= (date("Y", time()) + ((date("m", time()) < 7) ?  3 : 4));
$sanitized	= false;

/**
 * Check to see if the target is being provided.
 */
if((isset($_GET["g"])) && ($sanitized = clean_input($_GET["g"], array("notags", "nows", "lower", "credentials")))) {
	$TARGET = $sanitized;
}

switch($TARGET) {
	case "medtech" :
		$rss_feed_name			= "MEdTech Feed";
		$notice_where_clause	= "(a.`target` LIKE '%')";
	break;
	case "faculty" :
		$rss_feed_name			= "Faculty Feed";
		$notice_where_clause	= "(a.`target` = 'all' OR a.`target` = 'faculty')";
	break;
	case "staff" :
		$rss_feed_name			= "Staff Feed";
		$notice_where_clause	= "(a.`target` = 'all' OR a.`target` = 'staff')";
	break;
	case "student" :
	default :
		$rss_feed_name			= "Student Feed".(((int) $TARGET) ? ", Class of ".$TARGET : "");
		$notice_where_clause	= "(".(((int) $TARGET) ? "a.`target`=".$db->qstr($TARGET)." OR " : "")."a.`target` = 'all' OR a.`target` = 'students')";
	break;
}

if((isset($_GET["o"])) && ($sanitized = clean_input($_GET["o"], array("int")))) {
	$ORGANISATION = $sanitized;
	$notice_where_clause .= 'AND (a.`organisation_id` IS NULL OR a.`organisation_id` = '.$ORGANISATION.')';
}

$rss = new UniversalFeedCreator();
$rss->useCached();
$rss->title				= "Undergrad Notices: ".$rss_feed_name;
$rss->description		= "Announcements, Schedule Changes, Updates and more from the Undergraduate Medicial Education Office, Queen's University.";
$rss->link				= ENTRADA_URL."/dashboard";
$rss->syndicationURL	= ENTRADA_URL."/notices/".$TARGET;

$query	= "
		SELECT a.*
		FROM `notices` AS a
		WHERE ".(($notice_where_clause) ? $notice_where_clause." AND" : "")."
		(a.`display_from`='0' OR a.`display_from` <= '".time()."')
		ORDER BY a.`updated_date` DESC, a.`display_from` DESC";
$results	= ((USE_CACHE) ? $db->CacheGetAll(CACHE_TIMEOUT, $query) : $db->GetAll($query));
if($results) {
	foreach($results as $result) {
		$item = new FeedItem();
		$item->title		= $result["notice_summary"];
		$item->link			= ENTRADA_URL."/dashboard";
		$item->description	= $result["notice_summary"];
		$item->date			= unixstamp_to_iso8601(((int) $result["display_from"]) ? $result["display_from"] : time());
		$item->source		= ENTRADA_URL."/dashboard";
		$item->author		= "Undergraduate Medical Office";
		$rss->addItem($item);
	}
}

echo $rss->createFeed();