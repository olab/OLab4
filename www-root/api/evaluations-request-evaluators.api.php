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
        if (isset($_POST["id"]) && ($evaluation_id = clean_input($_POST["id"], "int"))) {
            $evaluators = Classes_Evaluation::getEvaluators($evaluation_id);
            if ($evaluators) {
                echo "<br /><label class=\"form-nrequired\" for=\"associated_evaluators\">Evaluators: </label><br />";
                echo "<select style=\"width: 150px; overflow: none;\" name=\"associated_evaluator\" id=\"associated_evaluator\">\n";
                echo "<option value=\"0\">-- Select an Evaluator --</option>";
                foreach ($evaluators as $evaluator) {
                    echo "<option value=\"".$evaluator["proxy_id"]."\">".html_encode($evaluator["fullname"])."</option>";
                }
                echo "</select>";
                echo "<br /><br /><input class=\"btn btn-primary btn-small\" type=\"submit\" value=\"Make Request\" />";
            }
        }
} else {
	application_log("error", "Evaluators API accessed without valid session_id.");
}