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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES[strtolower($MODULE)]["resource"], $MODULES[strtolower($MODULE)]["permission"], false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_ASSESSMENTS", true);

    if (isset($_POST["ajax"]) && $_POST["ajax"] === "ajax") {
		
		ob_clear_open_buffers();
		
			$method = clean_input($_POST["method"], array("trim", "striptags"));

			switch ($method) {
				case "store-resubmit" :
					
					if (isset($_POST["aovalue_id"]) && $tmp_input = clean_input($_POST["aovalue_id"], "int")) {
						$PROCESSED["aovalue_id"] = $tmp_input;
						$MODE = "UPDATE";
						$WHERE = "`aovalue_id` = ".$db->qstr($tmp_input);
					} else {
						$MODE = "INSERT";
						$WHERE = "'1' = '1'";
					}
					if (isset($_POST["proxy_id"]) && $tmp_input = clean_input($_POST["proxy_id"], "int")) {
						$PROCESSED["proxy_id"] = $tmp_input;
					} else {
						add_error("Invalid proxy ID supplied.");
					}
					if (isset($_POST["aoption_id"]) && $tmp_input = clean_input($_POST["aoption_id"], "int")) {
						$PROCESSED["aoption_id"] = $tmp_input;
					} else {
						add_error("Invalid assessment option ID provided.");
					}
					if (isset($_POST["value"]) && $tmp_input = clean_input($_POST["value"], "int")) {
						$PROCESSED["value"] = clean_input($_POST["value"], "int");
					} else {
						$PROCESSED["value"] = 0;
					}
					
					if (!$ERROR) {
						if ($db->AutoExecute("assessment_option_values", $PROCESSED, $MODE, $WHERE)) {
							if (!isset($PROCESSED["aovalue_id"])) {
								$PROCESSED["aovalue_id"] = $db->Insert_ID();
							}
							echo json_encode(array("status" => "success", "data" => $PROCESSED));
						} else {
							echo json_encode(array("status" => "error", "data" => $ERRORSTR));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
					
				break;
				case "store-late" :
					
					if (isset($_POST["aovalue_id"]) && $tmp_input = clean_input($_POST["aovalue_id"], "int")) {
						$PROCESSED["aovalue_id"] = $tmp_input;
						$MODE = "UPDATE";
						$WHERE = "`aovalue_id` = ".$db->qstr($tmp_input);
					} else {
						$MODE = "INSERT";
						$WHERE = "'1' = '1'";
					}
					if (isset($_POST["proxy_id"]) && $tmp_input = clean_input($_POST["proxy_id"], "int")) {
						$PROCESSED["proxy_id"] = $tmp_input;
					} else {
						add_error("Invalid proxy ID supplied.");
					}
					if (isset($_POST["aoption_id"]) && $tmp_input = clean_input($_POST["aoption_id"], "int")) {
						$PROCESSED["aoption_id"] = $tmp_input;
					} else {
						add_error("Invalid assessment option ID provided.");
					}
					if (isset($_POST["value"]) && $_POST["value"] == "1") {
						$PROCESSED["value"] = 1;
					} else {
						$PROCESSED["value"] = 0;
					}
					
					if (!$ERROR) {
						if ($db->AutoExecute("assessment_option_values", $PROCESSED, $MODE, $WHERE)) {
							if (!isset($PROCESSED["aovalue_id"])) {
								$PROCESSED["aovalue_id"] = $db->Insert_ID();
							}
							echo json_encode(array("status" => "success", "data" => $PROCESSED));
						} else {
							echo json_encode(array("status" => "error", "data" => $ERRORSTR));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
					
				break;
			}
		
		exit;
	}

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
			$COURSE_ID = $tmp_input;
            $course = Models_Course::fetchRowByID($COURSE_ID);
            $BREADCRUMB[] = array("title" => $course->getCourseCode());
		} else {
			$COURSE_ID = 0;
		}
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook?".replace_query(array("section" => "view", "id" => $COURSE_ID, "step" => false)), "title" => $translate->_("Gradebook"));

        if ((isset($_GET["assessment_id"])) && ($tmp_input = clean_input($_GET["assessment_id"], array("nows", "int")))) {
			$ASSESSMENT_ID = $tmp_input;
		} else {
			$ASSESSMENT_ID = 0;
		}
		
		$query = "	SELECT `organisation_id`
					FROM `courses`
					WHERE `course_id` = " . $db->qstr($COURSE_ID);
		
		$organisation_id = $db->getOne($query);
		
		if (!$ENTRADA_ACL->amIAllowed(new CourseContentResource($COURSE_ID, $organisation_id), "update")) {
			$url = ENTRADA_URL."/admin/gradebook";
			$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
			
			$ERROR++;
			$ERRORSTR[]	= "You do not have the permissions required to access this assessment.<br /><br />You will now be redirected to the <strong>Gradebook index</strong> page.  This will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

			echo display_error();

			application_log("error", "User: " . $ENTRADA_USER->getActiveId() . ", does not have access to this assessment id [".$ASSESSMENT_ID."]");
		} else {		
			$module_file = $router->getRoute();
			if ($module_file) {
				require_once($module_file);
			}
		}

		/**
		 * Check if preferences need to be updated on the server at this point.
		 */
		preferences_update($MODULE, $PREFERENCES);
	}
}