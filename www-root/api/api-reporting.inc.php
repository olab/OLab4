<?php
/**
 * Entrada [ http://www.entrada-project.org ]
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
 * API to gather task information
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan lackey <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
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
if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	http_response_code(403);
	exit;
} else {
	ob_clear_open_buffers();
	
	$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));	
	$request = ${"_" . $request_method};
	
	$method = "";
	if ($_POST["method"]) {
	    $method = $_POST["method"];
	}else if ($_GET["method"]) {
	    $method = $_GET["method"];
	}
	
	switch ($request_method) {
		case "POST" :
			$finalResult = array();
			if ($request["data"]) {
			   try{
			       $finalResult = Entrada_Reporting_JSONProcessor::processData($request["data"]);
			       if ($method == "csv") {
			           $report = new Entrada_Reporting_CSVReportHandler("report.csv");
			           $report->_ExcelExportData($finalResult);
			       } else {
			           http_response_code(400);
                       application_log("error", "Error: unknown export method " . $method);
                       exit;
			       }
				} catch (InvalidArgumentException $e) {
					http_response_code(400);
					application_log("error", "Invalid Argument ".$e->getMessage());
					exit;
				} catch (UnexpectedValueException $e) {
					http_response_code(500);
					application_log("error", "UnexpectedValueException ".$e->getMessage());
					exit;
				}
			} else {
				http_response_code(400);
				application_log("error", "Error: no data to export");
				exit;
			}

			break;
		
		default:
		    application_log("error", "invalid request method: ".$request_method);
			http_response_code(405);
			exit;
	}
	exit;
}