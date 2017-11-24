<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

/**
 * Delete the record - used by the annualreport module.
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

if((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) { 
	$proxy_id 			= $_GET['id'];
	$args				= $_GET['t'];
	$rid				= $_GET["rid"];
	
	$args 	= explode(",", $args);
	$table	= $args[0];
	
	if(strpos($rid, "|") !== false) {
		$ids 	= explode("|", $rid);
		
		for($i=0; $i<count($ids); $i++) {
			$query = "SELECT *
			FROM `".DATABASE_NAME."`.`".$table."` 
			WHERE `proxy_id` = ".$db->qstr($proxy_id);
			
			if($results = $db->GetAll($query)) {	
				$query = "DELETE FROM `".DATABASE_NAME."`.`".$table."` 
				WHERE `proxy_id` = ".$db->qstr($proxy_id)." AND `".$args[1]."` = ".$db->qstr($ids[$i]);
				
				if(!$db->Execute($query)) {
					echo $db->ErrorMsg();
					exit;
				}
			} else {
				echo '({"total":"0", "results":[]})';
			}
		}
	} else {
		$id 	= $rid;
		
		$query = "SELECT *
		FROM `".DATABASE_NAME."`.`".$table."` 
		WHERE `proxy_id` = ".$db->qstr($proxy_id);
		
		if($results = $db->GetAll($query)) {	
			$query = "DELETE FROM `".DATABASE_NAME."`.`".$table."` 
			WHERE `proxy_id` = ".$db->qstr($proxy_id)." AND `".$args[1]."` = ".$db->qstr($rid);
			
			if(!$db->Execute($query)) {
				echo $db->ErrorMsg();
				exit;
			}
		} else {
			echo '({"total":"0", "results":[]})';
		}
	}
} else {
	application_log("error", "Delete Annual Report Record (ar_delete) API accessed without valid session_id.");
}
?>