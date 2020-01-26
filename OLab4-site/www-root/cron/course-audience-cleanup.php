<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for cleaning up the course audience members.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <bt37@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
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

$today = mktime(0, 0, 0, date("m"), date("d"), date("y"));

$query = "	SELECT * FROM `course_audience` WHERE `enroll_finish` < ".$db->qstr($today)." AND `enroll_finish` != 0 AND `audience_active` = 1";
$results = $db->GetAll($query);
if ($results) {
	foreach($results as $result){
		$query = "UPDATE `course_audience` SET `audience_active` = 0 WHERE `caudience_id` = ".$db->qstr($result["caudience_id"]);
		if(!$db->Execute($query)){
			echo "Unable to de-activate id: ".$result["caudience_id"];
		}
	}
}