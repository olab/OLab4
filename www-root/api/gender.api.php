<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 * 
 * $Id: gender.api.php 1103 2010-04-05 15:20:37Z simpson $
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
$VALID_TYPES		= array("staff", "medtech", "faculty", "resident", "student", "alumni");

$req_type			= trim($PATHINFO[1]);
$req_number			= (int) trim($PATHINFO[2]);

if((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {

	if((@in_array($req_type, $VALID_TYPES)) && ($req_number)) {
		switch($req_type) {
			case "default" :
				$gender = "unknown";
				
				switch($gender) {
					case "female" :
						echo "female";
					break;
					case "male" :
						echo "male";
					break;
					default :
						echo "unknown";
					break;
				}
			break;
		}
	} else {
		echo "unknown";	
	}
} else {
	echo "unknown";	
}
?>