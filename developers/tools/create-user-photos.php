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
 * Create User Photos
 * 
 * Run this script to create official user photos from JPG files in the
 * tools/data directory. Photos are created as proxy_id-official then stored in
 * the STORAGE_USER_PHOTOS directory that is defined in config.inc.php.
 * 
 * Instructions:
 * 1. Copy all original JPG files into the tools/data directory using the
 *    following format: Lastname_Firstname.jpg
 *    
 * 2. Run "./create-user-photos.php -unmatched" to see all of the files that
 *    this script was unable to match based on Lastname and Firstname from the
 *    AUTH_DATABASE.user_data table.
 *    
 *    Note: If the script cannot match a photo but you can, then you can add
 *    their proxy_id as a suffix to their name in the filename and the script
 *    use that information to match the user: e.g. Lastname_Firstname_1435.jpg
 * 
 * 3. When you are satisfied that you are able to process the majority of the
 *    photos run "./create-user-photos.php -create" to create both the full size
 *    image (216 x 300) and the thumbnail (75 x 98) and have them saved to the
 *    STORAGE_USER_PHOTOS directory that is defined in config.inc.php. 
 *   
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
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

// Name of the file to import.

$DATA_DIRECTORY	= dirname(__FILE__)."/data";
$ACTION			= ((isset($_SERVER["argv"][1])) ? trim($_SERVER["argv"][1]) : "-usage");

switch($ACTION) {
	case "-matched" :
		echo "\nShowing Matched Files:\n";
		if (is_dir($DATA_DIRECTORY)) {
			if ($handle = opendir($DATA_DIRECTORY)) {
				while (($filename = readdir($handle)) !== false) {
					if(substr($filename, 0, 1) != ".") {
						if(strstr($filename, "_") !== false) {
							$pieces = explode("_", $filename);

							if((isset($pieces[2])) && ((int) trim($pieces[2]))) {
								$query		= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr((int) trim($pieces[2]));
								$results	= $db->GetAll($query);
							} else {
								$query		= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `firstname` LIKE ".$db->qstr(str_ireplace(".jpg", "", $pieces[1]))." AND `lastname` LIKE ".$db->qstr($pieces[0]);
								$results	= $db->GetAll($query);
							}
							
							if(($results) && ($i = count($results) == 1)) {
								echo "\n".$filename." is proxy_id ".$results[0]["id"];
							}
						} else {
							echo "\nERROR: ".$filename." does not contain an underscore.";
						}
					}
				}
				closedir($handle);
			} else {
				echo "\nERROR: Unable to open your data directory.\n\n";
			}
		}
	break;
	case "-unmatched" :
		echo "\nShowing Unmatched Files:\n";
		if (is_dir($DATA_DIRECTORY)) {
			if ($handle = opendir($DATA_DIRECTORY)) {
				while (($filename = readdir($handle)) !== false) {
					if(substr($filename, 0, 1) != ".") {
						if(strstr($filename, "_") !== false) {
							$pieces = explode("_", $filename);
							if((isset($pieces[2])) && ((int) trim($pieces[2]))) {
								$query		= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr((int) trim($pieces[2]));
								$results	= $db->GetAll($query);
							} else {
								$query		= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `firstname` LIKE ".$db->qstr(str_ireplace(".jpg", "", $pieces[1]))." AND `lastname` LIKE ".$db->qstr($pieces[0]);
								$results	= $db->GetAll($query);
							}
							
							if((!$results) || (count($results) > 1)) {
								echo "\n".$filename." (".count($results)." results)";
							}
						} else {
							echo "\nERROR: ".$filename." does not contain an underscore.";
						}
					}
				}
				closedir($handle);
			} else {
				echo "\nERROR: Unable to open your data directory.\n\n";
			}
		} else {
			echo "\nERROR: Your data directory is not a directory.\n\n";	
		}
	break;
	case "-create" :
		$VALID_MIME_TYPES		= array("image/pjpeg" => "jpg", "image/jpeg" => "jpg", "image/jpg" => "jpg", "image/gif" => "gif", "image/png" => "png");
		$VALID_MAX_FILESIZE		= 2097512; // 2MB
		$VALID_MAX_DIMENSIONS	= array("photo-width" => 216, "photo-height" => 300, "thumb-width" => 75, "thumb-height" => 98);

		echo "\nResizing and Creating Files:\n";
		if (is_dir($DATA_DIRECTORY)) {
			if ($handle = opendir($DATA_DIRECTORY)) {
				while (($filename = readdir($handle)) !== false) {
					if(substr($filename, 0, 1) != ".") {
						if(strstr($filename, "_") !== false) {
							$pieces = explode("_", $filename);
							if((isset($pieces[2])) && ((int) trim($pieces[2]))) {
								$query		= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr((int) trim($pieces[2]));
								$results	= $db->GetAll($query);
							} else {
								$query		= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `firstname` LIKE ".$db->qstr(str_ireplace(".jpg", "", $pieces[1]))." AND `lastname` LIKE ".$db->qstr($pieces[0]);
								$results	= $db->GetAll($query);
							}
							
							if(($results) && ($i = count($results) == 1)) {
								if(process_user_photo_official($DATA_DIRECTORY."/".$filename, $results[0]["id"])) {
									echo "\nSUCCESS: Created official photo for ".$results[0]["firstname"]." ".$results[0]["lastname"]." [".$results[0]["id"]."]";
								} else {
									echo "\nFAILURE: Unable to create official photo for ".$results[0]["firstname"]." ".$results[0]["lastname"]." [".$results[0]["id"]."]";
								}
							}
						} else {
							echo "\nERROR: ".$filename." does not contain an underscore.";
						}
					}
				}
				closedir($handle);
			} else {
				echo "\nERROR: Unable to open your data directory.\n\n";	
			}
		}
	break;
	case "-usage" :
	default :
		echo "\nUsage: ".basename(__FILE__)." [options]";
		echo "\n   -usage                Brings up this help screen.";
		echo "\n   -unmatched            Shows all pictures which can not be matched.";
		echo "\n   -matched              Shows all pictures that can be matched.";
		echo "\n   -create               Creates all of the matchable pictures.";
	break;
}

echo "\n\n";
?>
