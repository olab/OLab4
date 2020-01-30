#!/usr/bin/php
<?php
/**
 * Entrada Tools [ http://www.entrada-project.org ]
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
 * Run this script to connect a course (or multiple courses) to a community,
 * thus making transforming the Community into a "Course Website".
 *   
 * @author Unit: Medical Education Technology Unit
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    realpath(dirname(__FILE__) . "/includes"),
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

@ini_set("auto_detect_line_endings", 1);
@ini_set("display_errors", 1);
@ini_set("magic_quotes_runtime", 0);
set_time_limit(0);

if((!isset($_SERVER["argv"])) || (@count($_SERVER["argv"]) < 1)) {
	echo "<html>\n";
	echo "<head>\n";
	echo "	<title>Processing Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "This file should be run by command line only.";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

require_once("autoload.php");

require_once("config.inc.php");
require_once("dbconnection.inc.php");
require_once("functions.inc.php");

$ERROR = false;

output_notice("This script is used to sync the members of a group with the members of a community.");
output_notice("Step 1: Enter the group and community ids.");
print "\nPlease enter Group ID: ";
fscanf(STDIN, "%d\n", $GROUP_ID); // reads number from STDIN
$group = $db->GetRow("SELECT * FROM `groups` WHERE `group_id` = ".$db->qstr($GROUP_ID));
while (!$group) {
	print "\nPlease ensure you enter a valid Group ID: ".$db->ErrorMsg();
	fscanf(STDIN, "%d\n", $GROUP_ID); // reads number from STDIN
	$group = $db->GetRow("SELECT * FROM `groups` WHERE `group_id` = ".$db->qstr($GROUP_ID));
}

print "\nPlease enter Community ID: ";
fscanf(STDIN, "%d\n", $COMMUNITY_ID); // reads number from STDIN
$result = $db->GetRow("SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID));
while (!$result) {
	print "\nPlease ensure you enter a valid Community ID: ";
	fscanf(STDIN, "%d\n", $COMMUNITY_ID); // reads number from STDIN
	$result = $db->GetRow("SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID));
}

output_notice("Step 2: The group members will be synced with the community.");
$group_members = "SELECT `proxy_id` FROM `group_members` WHERE `group_id` = ".$db->qstr($GROUP_ID);
$new_community_members = array();

foreach($group_members as $gmember){
	if(!$community_members = "SELECT `proxy_id` FROM `community_members` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` = ".$db->qstr($gmember['proxy_id'])){
		$new_community_members[] = $gmember['proxy_id'];
	}
}

$community_member = array('community_id'=>$COMMUNITY_ID,'member_active'=>1,'member_joined'=>time(),'member_acl'=>0);
foreach($new_community_members as $member){
	$community_member['proxy_id'] = $member;
	$db->AutoExecute('community_members',$community_member,'INSERT');
}

print "\n\n";
?>