<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to delete specific revisions of a file that was uploaded. This action
 * can be used by either the user who originally posted this revision, the user
 * who originally uploaded the first file or by any community administrator.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("IN_PUBLIC_ASSIGNMENTS"))) {
	exit;
}
if(isset($_GET["afversion_id"]) && $tmp_id = (int)$_GET["afversion_id"]){
	$AFVERSION_ID = $tmp_id;
}
if ($AFVERSION_ID) {
	$query = "SELECT a.*, b.`parent_id`  FROM `assignment_file_versions` AS a JOIN `assignment_files` AS b ON a.`afile_id` = b.`afile_id` WHERE a.`afversion_id` = ".$db->qstr($AFVERSION_ID);
	$file_version = $db->getRow($query);
	if ($file_version) {
		echo $ENTRADA_USER->getID();
		if ($ENTRADA_USER->getID() == $file_version["proxy_id"]) {
			if (((int) $file_version["file_active"])) {
				if ($db->AutoExecute("assignment_file_versions", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`afversion_id` = ".$db->qstr($AFVERSION_ID)." AND `afile_id` = ".$db->qstr($file_version["afile_id"]))) {
					$query = "	SELECT COUNT(*) FROM `assignment_file_versions` WHERE `afile_id` = ".$file_version["afile_id"]." AND `file_active` = '1'";
					if (!$db->GetOne($query)) {
						if ($db->AutoExecute("assignment_files", array("file_active" => 0, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "`afile_id` = ".$db->qstr($file_version["afile_id"]))) {

						}else{
							application_log("error", "Successfully deactivated file version [".$AFVERSION_ID."] but unable to remove associated file information.");
						}
					}
				} else {
					application_log("error", "Failed to deactivate [".$AFVERSION_ID."] file version. Database said: ".$db->ErrorMsg());
				}
			} else {
				application_log("error", "The provided file revision id [".$AFVERSION_ID."] is already deactivated.");
			}
			if ($file_version["parent_id"]) {
				$query = "SELECT `proxy_id` FROM `assignment_files` WHERE `afile_id` = ".$db->qstr($file_version["parent_id"]);
				$proxy = $db->GetOne($query);
				header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$file_version["assignment_id"]."&pid=".$proxy);
			} else {
				header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$file_version["assignment_id"]);
			}			
			exit;
		} else {
			application_log("error", "You are unauthorized to delete this file revision [".$AFVERSION_ID."].");	
		}
	} else {
		application_log("error", "The provided file revision id [".$AFVERSION_ID."] was invalid.");
	}
} else {
	application_log("error", "No file revision id was provided for deactivation.");
}
//header("Location: ".ENTRADA_URL."/profile/gradebook/assignments");
exit;
?>