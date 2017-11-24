<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 * 
 * $Id: photo.api.php 1200 2010-06-10 19:07:17Z simpson $
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

$PATHINFO			= explode("/", str_replace(array("..", "\\", " "), "", urldecode($_SERVER["PATH_INFO"])));

$PROXY_ID			= (int) ((isset($PATHINFO[1])) ? trim($PATHINFO[1]) : 0);
$PHOTO_TYPE			= ((isset($PATHINFO[2]) && ($PATHINFO[2] == "official")) ? "official" : "upload");
$PHOTO_SIZE			= ((isset($PATHINFO[3]) && ($PATHINFO[3] == "thumbnail")) ? "thumbnail" : "");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	
	switch($PHOTO_TYPE) {
		case "upload" :
			$query			= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username` AS `username`, b.`privacy_level`
								FROM `".AUTH_DATABASE."`.`user_photos` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								WHERE a.`proxy_id` = ".$db->qstr($PROXY_ID)."
								AND a.`photo_active` = '1'
								AND a.`photo_type` = '1'";
			$photo_record	= $db->GetRow($query);
			
			$photo_suffix	= "-upload".(($PHOTO_SIZE == "thumbnail") ? "-thumbnail" : "");
		break;
		case "official" :
			$query			= "	SELECT CONCAT_WS(' ', `firstname`, `lastname`) AS `fullname`, `username`, `privacy_level`
								FROM `".AUTH_DATABASE."`.`user_data`
								WHERE `id` = ".$db->qstr($PROXY_ID);
			$photo_record	= $db->GetRow($query);	
			
			$photo_suffix	= "-official".(($PHOTO_SIZE == "thumbnail") ? "-thumbnail" : "");
		break;
		default :
			$photo_record = false;

			application_log("error", "An unrecognized photo type [".$PHOTO_TYPE."] was requested in photo.api.php.");
		break;
	}

	/**
	 * If there is a succesfully photo record, and either
	 * 	If the user is in an administration group, or
	 *  If the user is trying to view their own photo, or either
	 *  	If the photo_type is official, and the proxy_id has their privacy set to "Any Information", or
	 * 		If the photo_type is uploaded, and the proxy_id has their privacy set to "Basic Information"
	 */
	if (($photo_record) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($PROXY_ID, $photo_record["privacy_level"], $PHOTO_TYPE), "read"))) {
		$display_file = false;
	
		if ((@file_exists(STORAGE_USER_PHOTOS."/".$PROXY_ID.$photo_suffix)) && (@is_readable(STORAGE_USER_PHOTOS."/".$PROXY_ID.$photo_suffix))) {
			$display_file = STORAGE_USER_PHOTOS."/".$PROXY_ID.$photo_suffix;
		}
	
		if (!$display_file) {
			$display_file = ENTRADA_ABSOLUTE."/images/headshot-male.gif";
		}
	
		header("Cache-Control: max-age=2592000");
		header("Content-Type: ".(isset($photo_record["photo_mimetype"]) ? $photo_record["photo_mimetype"] : "image/jpeg"));
		header("Content-Length: ".@filesize($display_file));
		header("Content-Disposition: inline; filename=\"".$PROXY_ID.$photo_suffix.".jpg\"");
		header("Content-Transfer-Encoding: binary\n");
		
		echo @file_get_contents($display_file, FILE_BINARY);
		exit;
	} else {
		header("Cache-Control: max-age=2592000\n");
		header("Content-type: image/gif\n");
		header("Content-Disposition: filename=".$PROXY_ID.$photo_suffix.".gif\n");
		header("Content-Transfer-Encoding: binary\n");

		echo @file_get_contents(ENTRADA_ABSOLUTE."/images/headshot-male.gif", FILE_BINARY);
		exit;
	}
} else {
	header("Cache-Control: max-age=2592000\n");
	header("Content-type: image/gif\n");
	header("Content-Disposition: filename=not-available.gif\n");
	header("Content-Transfer-Encoding: binary\n");

	echo @file_get_contents(ENTRADA_ABSOLUTE."/images/headshot-male.gif", FILE_BINARY);
	exit;
}
?>