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

if (isset($_GET["columns"]) && $_GET["columns"]) {
	$columns = (int) $_GET["columns"];
}

if (isset($_GET["categories"]) && $_GET["categories"]) {
	$categories = (int) $_GET["categories"];
}

$question_data["categories_count"] = $categories;
$question_data["columns_count"] = $columns;
echo Classes_Evaluation::getRubricCategoryList($question_data);

?>
