<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * Module:	Courses
 * Area:	Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @version 0.8.3
 * @copyright Copyright 2009 Queen's University, MEdTech Unit
 *
 * $Id: add.inc.php 505 2009-07-09 19:15:57Z jellis $
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

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	
	/**
	 * Clears all open buffers so we can return a simple REST response.
	 */
	ob_clear_open_buffers();
	
	$id = (int)$_GET["category_id"];
	$select = "a.*";

	$qu_arr = array("SELECT ".$select." FROM `".CLERKSHIP_DATABASE."`.`categories` AS a");
		
	$qu_arr[2] = "WHERE a.`category_parent` = ".$db->qstr($id)." 
				AND a.`category_status` != 'trash'";
	$qu_arr[4] = "ORDER BY a.`category_order`";
	$query = implode(" ",$qu_arr);
	$categories = $db->GetAll($query);
	if ($categories) {
		$obj_array = array();
		foreach($categories as $category){
			$fields = array(	'category_id'=>$category["category_id"],
									'category_code'=>$category["category_code"],
									'category_name'=>$category["category_name"],
									'category_desc'=>$category["category_desc"]
								);			
			$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` 
						WHERE `category_parent` = ".$db->qstr($category["category_id"]);
			$fields["has_child"] = $db->GetAll($query)?true:false;			
			$obj_array[] = $fields;
		}
		echo json_encode($obj_array);
	} else {
		echo json_encode(array('error'=>'No child categories found for the selected category.'));
	}
	
	exit;
}