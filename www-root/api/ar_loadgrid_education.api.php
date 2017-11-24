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
	$organisations 	= $ENTRADA_USER->getAllOrganisations();
	$args		= html_decode($_GET["t"]);
	
	if(isset($_POST["sortname"]) && $_POST["sortname"] != '') {
		$sort 	= $_POST["sortname"];
	} else {
		$sort 	= 'event_start';
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
        if($_POST["qtype"] == "event_start") {
            $start = strtotime($_POST["query"] . " 12:00AM");
            $end =  strtotime($_POST["query"] . "11:59PM");
            $where = " AND `start_date` BETWEEN (\"$start\", \"$end\")";
        } else {
		    $where = " AND " . $_POST["qtype"] . " LIKE '%" . $_POST["query"] . "%'";
        }
	} else {
		$where 		= "";
	}
	
	$args 	= explode(",", $args);

	$query = "SELECT COUNT(proxy_id) AS total
	FROM `event_contacts`
	WHERE `proxy_id` = ".$db->qstr($proxy_id).$where;
	
	$result = $db->GetRow($query);
	$total = $result["total"];

    $query	= "SELECT a.`event_id`, a.`event_title`, a.`course_id`, a.`event_duration`,
	a.`event_start`, c.`course_name`, c.`course_code`, c.organisation_id, d.`audience_type`, d.`audience_value`
	FROM `events` AS a
	LEFT JOIN `event_contacts` AS b
	ON b.`event_id` = a.`event_id`
	AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
	LEFT JOIN `courses` AS c
	ON a.`course_id` = c.`course_id`
	LEFT JOIN `event_audience` AS d
	ON a.`event_id` = d.`event_id`
	WHERE b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).$where."
	GROUP BY a.`event_id`
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
            $query = "SELECT `duration` FROM `event_eventtypes` WHERE `event_id` = ".$db->qstr($row["event_id"]);
            $duration_results = $db->GetAll($query);
            $durationCount = 0;
            foreach($duration_results as $duration) {
                $durationCount = $duration["duration"] + $durationCount;
            }
            // Replace all line returns as to not break JSON output (grid will not load otherwise)
            $row["event_title"] = str_replace("\r\n", " ", $row["event_title"]);
            $row["course_code"] = str_replace("\n", " ", $row["course_code"]);
            $row["event_start"] = str_replace("\r", " ", $row["event_start"]);
            $row["event_start"] = date("Y-m-d", $row["event_start"]);
            $durationCount = round($durationCount / 60, 2);
            $organisation_installation = fetch_organisation_installation($row["organisation_id"]);
            $organisation_installation = "<a href=".$organisation_installation."/events?rid=".$row["event_id"]." style=\"cursor: pointer; cursor: hand\" text-decoration: none; target=\"_blank\"><i class=\"icon-search\"></i></a>";
            $rows[] = array(
                "id" => $row["event_id"],
                "cell" => array(
                $row["event_title"]
                ,$row["course_code"]
                ,$row["event_start"]
                ,$durationCount
                ,$organisation_installation
                )
            );

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