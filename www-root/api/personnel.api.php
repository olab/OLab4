<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: personnel.api.php 1140 2010-04-27 18:59:15Z simpson $
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

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	if (isset($_POST["fullname"]) && ($tmp_input = clean_input($_POST["fullname"], array("trim", "notags")))) {
		$fullname = $tmp_input;
	} elseif (isset($_GET["fullname"]) && ($tmp_input = clean_input($_GET["fullname"], array("trim", "notags")))) {
		$fullname = $tmp_input;
	} else {
		$fullname = "";
	}

	if (isset($_POST["term"]) && ($tmp_input = clean_input($_POST["term"], array("trim", "notags")))) {
		$term = $tmp_input;
	} elseif (isset($_GET["term"]) && ($tmp_input = clean_input($_GET["term"], array("trim", "notags")))) {
		$term = $tmp_input;
	} else {
		$term = "";
	}

	if (empty($fullname) && !empty($term))
	{
		$fullname = $term;
	}

	if (isset($_POST["email"]) && ($tmp_input = clean_input($_POST["email"], array("trim", "notags")))) {
		$email = $tmp_input;
	} elseif (isset($_GET["email"]) && ($tmp_input = clean_input($_GET["email"], array("trim", "notags")))) {
		$email = $tmp_input;
	} else {
		$email = "";
	}

	if (isset($_POST["type"]) && ($tmp_input = clean_input($_POST["type"], array("trim", "notags")))) {
		$type = $tmp_input;
	} elseif (isset($_GET["type"]) && ($tmp_input = clean_input($_GET["type"], array("trim", "notags")))) {
		$type = $tmp_input;
	} else {
		$type = "";
	}
    
    if (isset($_POST["organisation_id"]) && ($tmp_input = (int)$_POST["organisation_id"])) {
        $organisation_id = $tmp_input;
    } else if (isset($_GET["organisation_id"]) && ($tmp_input = (int)$_GET["organisation_id"])) {
        $organisation_id = $tmp_input;
    } else {
        $organisation_id = null;
    }
	$out = "html";

	if (isset($_POST["out"]) && ($tmp_input = clean_input($_POST["out"], array("trim", "notags")))) {
		$out = $tmp_input;
	} elseif (isset($_GET["out"]) && ($tmp_input = clean_input($_GET["out"], array("trim", "notags")))) {
		$out = $tmp_input;
	}

	$out = "html";

	if (isset($_POST["out"]) && ($tmp_input = clean_input($_POST["out"], array("trim", "notags")))) {
		$out = $tmp_input;
	} elseif (isset($_GET["out"]) && ($tmp_input = clean_input($_GET["out"], array("trim", "notags")))) {
		$out = $tmp_input;
	}

	$course_id = "";

	if (isset($_POST["course_id"]) && ($tmp_input = (int)$_POST["course_id"])) {
		$course_id = $tmp_input;
	} elseif (isset($_GET["course_id"]) && ($tmp_input = (int)$_GET["course_id"])) {
		$course_id = $tmp_input;
	}

	if ($fullname) {
		$query = "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`email`, c.`organisation_title`, b.`group`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON b.`user_id` = a.`id`
					LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
					ON c.`organisation_id` = b.`organisation_id`
					WHERE CONCAT_WS(', ', a.`lastname`, a.`firstname`) LIKE ".$db->qstr("%".$fullname."%")."
					AND (b.`group` <> 'guest')";
        if ($organisation_id) {
            $query .= " AND c.`organisation_id` = ".$db->qstr($organisation_id);
        }

		switch ($type) {
			case "facultyorstaff":
				$query .= "	AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer') OR b.`group` = 'staff' OR b.`group` = 'medtech')";
			break;
			case "staff":
				$query .= "	AND (b.`group` = 'staff' OR b.`group` = 'medtech')";
			break;
			case "faculty" :
			case "evalfaculty" :
				$query .= "	AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer'))";
			break;
			case "resident" :
			case "postgrad" :
			case "undergrad" :
			case "student" :
			case "clerk" :
				$query .= "	AND b.`group` = 'student' AND b.`role` >= '".(date("Y") - ((date("m") < 7) ? 2 : 1))."'";
			break;
			case "learners" :
				$query .= "	AND (b.`group` = 'resident' OR (b.`group` = 'student' AND b.`role` >= '".(date("Y") - ((date("m") < 7) ? 2 : 1))."'))";
			break;
			case "director" :
				$query .= "	AND b.`group` = 'faculty' AND (b.`role` = 'director' OR b.`role` = 'admin')";
			break;
			case "coordinator" :
				$query .= "	AND b.`group` = 'staff' AND b.`role` = 'admin'";
			break;
			case "program_coordinator" :
			case "pcoordinator" :
				$query .= " AND b.`role` = 'pcoordinator'";
			break;
			case "evaluationrep" :
				$query .= " AND b.`group` = 'faculty'";
			break;
			case "ta" :
			case "studentrep" :
				$query .= " AND b.`group` = 'student'";
			break;
			case "evaluators" :
				$evaluator_ids_string = "";
				if (isset($_GET["id"]) && ($evaluation_id = clean_input($_GET["id"], "int"))) {
					$evaluators = Classes_Evaluation::getEvaluators($evaluation_id);
					if ($evaluators) {
						foreach ($evaluators as $evaluator) {
							$evaluator_ids_string .= ($evaluator_ids_string ? ", " : "").$db->qstr($evaluator["proxy_id"]);
						}
					}
				}
				$query .= " AND a.`id` IN (".$evaluator_ids_string.")";
			break;
			case "course_contacts" :
				$grader_ids = array();
				$course_contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id);

				foreach ($course_contacts as $contact) {
					$grader_ids[$contact->getProxyID()] = 1;
				}
				$query .= " AND a.`id` IN (" . implode(",", array_keys($grader_ids)) . ")";
			break;
			case "people" : // include every active user in the organisation
				continue;
			break;
		}
		$query .= "	AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					AND b.`account_active` = 'true'
					AND (b.`access_starts`='0' OR b.`access_starts` <= ".$db->qstr(time()).")
					AND (b.`access_expires`='0' OR b.`access_expires` >= ".$db->qstr(time()).")
					GROUP BY a.`id`
					ORDER BY `fullname` ASC";

		$results = $db->GetAll($query);

		$output = "";
		if ($results) {
			if ($out == "html") {
				$output .= "<ul>\n";
				foreach ($results as $result) {
					$output .= "\t<li id=\"" . (int)$result["proxy_id"] . "\">" . html_encode($result["fullname"]) . "<span class=\"informal content-small\"><br />" . html_encode($result["organisation_title"]) . " - " . html_encode(ucfirst($result["group"]))
					. "<br />" . html_encode($result["email"]) . "</span></li>\n";
				}
 				$output .= "</ul>\n";
 			} else {
				$output .= json_encode($results);
			}
		} else {
			if ($out == "html") {
				$output .= "<ul>\n";
				$output .= "\t<li id=\"0\"><span class=\"informal\">&quot;<strong>".html_encode($fullname)."&quot;</strong> was not found</span></li>";
				$output .= "</ul>\n";
			} else {
				$output .= json_encode(array(array("response" => $fullname." was not found")));
			}
		}
		echo $output;
	}
	
	if ($email) {
		$query = "	SELECT a.`id` AS `proxy_id`, a.`lastname`, a.`firstname`, a.`email`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON b.`user_id` = a.`id`
					AND (b.`group` <> 'guest')
					WHERE (a.`email` = ".$db->qstr($email)." OR a.`email_alt` = ".$db->qstr($email).")";
		$result = $db->GetRow($query);
		if ($result) {
			echo json_encode(array("status" => "success", "data" => $result));
		} else {
			echo json_encode(array("status" => "error", "data" => array("msg" => "That email address is not registered in the system.")));
		}
	}
} else {
	application_log("error", "Personnel API accessed without valid session_id.");
}
