<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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

if (isset($_POST["response_text"]) && $_POST["response_text"]) {
	$question_data = json_decode($_POST["response_text"], true);
}

if (isset($_GET["responses"]) && $_GET["responses"]) {
	$responses = (int) $_GET["responses"];
}

if (isset($_POST["questiontype"]) && $_POST["questiontype"]) {
	$questiontype = (int) $_POST["questiontype"];
    $query = "SELECT `questiontype_shortname` FROM `evaluations_lu_questiontypes` WHERE `questiontype_id` = ".$db->qstr($questiontype);
    $questiontype = $db->GetOne($query);
}

$question_data["responses_count"] = $responses;

echo Classes_Evaluation::getQuestionResponseList($question_data, $questiontype);

?>
