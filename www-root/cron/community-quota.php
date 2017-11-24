<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: community-quota.php 1103 2010-04-05 15:20:37Z simpson $
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

$update_success	= 0;
$update_failed	= 0;
$time_start		= getmicrotime();

$query		= "SELECT * FROM `communities` WHERE `community_active` = '1' ORDER BY `community_id` ASC";
$results	= $db->GetAll($query);
if($results) {
	foreach($results as $result) {
		$current_usage	= (int) $result["storage_usage"];
		$storage_usage	= 0;
		if($community_id = (int) $result["community_id"]) {
			$query		= "
						SELECT a.`module_id`, b.`module_shortname`, b.`module_version`
						FROM `community_modules` AS a
						LEFT JOIN `communities_modules` AS b
						ON a.`module_id` = b.`module_id`
						WHERE a.`community_id` = ".$db->qstr($community_id)."
						AND a.`module_active` = '1'
						AND b.`module_active` = '1'";
			$modules	= $db->GetAll($query);
			if($modules) {
				foreach($modules as $module) {
					switch($module["module_shortname"]) {
						case "galleries" :
							$query		= "
										SELECT SUM(`photo_filesize`) AS `total_size`
										FROM `community_gallery_photos`
										WHERE `community_id` = ".$db->qstr($community_id)."
										AND `photo_active` = '1'";
							$galleries	= $db->GetRow($query);
							if(($galleries) && ((int) $galleries["total_size"])) {
								$storage_usage += (int) $galleries["total_size"];
							}
						break;
						case "shares" :
							$query		= "
										SELECT SUM(`file_filesize`) AS `total_size`
										FROM `community_share_file_versions`
										WHERE `community_id` = ".$db->qstr($community_id)."
										AND `file_active` = '1'";
							$shares	= $db->GetRow($query);
							if(($shares) && ((int) $shares["total_size"])) {
								$storage_usage += (int) $shares["total_size"];
							}
						break;
						case "announcements" :
						case "discussions" :
						default :
							continue;
						break;
					}
				}
			}

			if($storage_usage != $current_usage) {
				if(@$db->AutoExecute("communities", array("storage_usage" => $storage_usage), "UPDATE", "`community_id` = ".$db->qstr($community_id))) {
					$update_success++;
				} else {
					$update_failed++;
					@application_log("error", "Unable to update quota information for community_id [".$community_id."]. Database said: ".$db->ErrorMsg());
				}
			}
		}
	}
}

$time_end	= getmicrotime();
@application_log("cron", "Community quota updated in ".($time_end - $time_start)." seconds. Success: ".$update_success." / Failed: ".$update_failed);
?>