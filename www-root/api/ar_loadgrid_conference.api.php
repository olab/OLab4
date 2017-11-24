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
 * Load the grid - used by the annualreport module.
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
	$proxy_id 	= $ENTRADA_USER->getActiveId();
	$args		= html_decode($_GET["t"]);
	
	if(isset($_POST["sortname"]) && $_POST["sortname"] != '') {
		$sort 	= $_POST["sortname"];
	} else {
		$sort 	= 'year_reported';
	}
	
	if(isset($_POST["sortorder"]) && $_POST["sortorder"] != '') {
		$dir 	= $_POST["sortorder"];
	} else {
		$dir 	= 'DESC';
	}
	
	if(isset($_POST["rp"]) && $_POST["rp"] != '') {
		$limit 	= $_POST["rp"];
	} else {
		$limit 	= '10';
	}
	
	if(isset($_POST["page"]) && $_POST["page"] != '') {
		$page 		= $_POST['page'];
		if($page == 1) {
			$start 	= '0';
		} else {
			$start 	= ((int)$page * (int)$limit) - (int)$limit;
		}
	} else {
		$page		= '1';
		$start 		= '0';
	}
	
	if(isset($_POST["query"]) && $_POST["query"] != '') {
		$where 		= " AND " . $_POST["qtype"] . " LIKE '%" . $_POST["query"] . "%'";
	} else {
		$where 		= "";
	}
	
	$args 	= explode(",", $args);
	$table	= $args[0];
	
	$query = "SELECT COUNT(proxy_id) AS total
	FROM `".$table."` 
	WHERE `proxy_id` = ".$db->qstr($proxy_id).$where;
	
	$result = $db->GetRow($query);
	$total = $result["total"];
	
	$query = "SELECT *
	FROM `".$table."` 
	WHERE `proxy_id` = ".$db->qstr($proxy_id).$where."
	ORDER BY ".$sort." ".$dir."
	LIMIT ".$start." , ".$limit;
	
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
	header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
	header("Cache-Control: no-cache, must-revalidate" );
	header("Pragma: no-cache" );
	header("Content-type: text/json");
	
	if($results = $db->GetAll($query)) {
		$data['page'] = $page;
		$data['total'] = $total;
		foreach($results as $row) {
			for($i=2;$i<count($args);$i++) {
				// Replace all line returns as to not break JSON output (grid will not load otherwise)
				$row[$args[$i]] = str_replace("\r\n", " ", $row[$args[$i]]);
				$row[$args[$i]] = str_replace("\n", " ", $row[$args[$i]]);
				$row[$args[$i]] = str_replace("\r", " ", $row[$args[$i]]);
				
				if($row[$args[$i]] == "") {
					$row[$args[$i]] = addslashes("N/A");
				}
			}
			
			if($row['year_reported'] < '2011') {
				$rows[] = array(
	                "id" => $row[$args[1]],
	                "cell" => array(
	               	$row[$args[2]]
	                ,$row[$args[3]]
	                ,$row[$args[4]]
	                ,$row[$args[5]]
	                )
	        	);	
			} else {
				$location = $row['city'] . ", " . $row['prov_state'];
				$rows[] = array(
	                "id" => $row[$args[1]],
	                "cell" => array(
	               	$row[$args[2]]
	                ,$row[$args[3]]
	                ,$location
	                ,$row[$args[5]]
	                )
	        	);
			}
		}
		$data['rows'] = $rows;
		$data['params'] = $_POST;
		
		echo json_encode($data); 
	} else {
		$data['page'] = 1;
		$data['total'] = 0;
		$rows[] = array();
		$data['rows'] = $rows;
		echo json_encode($data);
	}
} else {
	application_log("error", "Annual Report grid loader (ar_loadgrid) API accessed without valid session_id.");
}
?>