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
 * Primary controller file for the Assessments Form Bank submodule.
 * /admin/assessments/forms
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of the University of California. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES["exams"]["resource"], $MODULES["exams"]["permission"], false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_GRADE_EXAMS", true);
    if (isset($_GET["post_id"])) {
        $POST_ID = (int)$_GET["post_id"];
        $exam_post = Models_Exam_Post::fetchRowByExamIDNoPreview($POST_ID);
    }
    if (!isset($exam_post) || !$exam_post) {
        add_error($translate->_("You must provide a valid Exam Post ID."));
        echo display_error();
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/grade", "title" => $translate->_("Grading"));
    } else if (!Models_Exam_Grader::isExamPostGradableBy($exam_post->getID(), $ENTRADA_USER->getActiveId())) {
        $message = sprintf($translate->_("You do not have access to grade this Exam Post. If you believe you are receiving this message in error, please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));
        add_error($message);
        echo display_error();
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/grade?post_id=".$exam_post->getID(), "title" => $translate->_("Grading"));
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/grade?post_id=".$exam_post->getID(), "title" => sprintf($translate->_("Grading %s"), $exam_post->getTitle()));
        $HEAD[] = "<script type=\"text/javascript\">var EXAM_POST_ID = ".$exam_post->getID().";</script>";

        if (($router) && ($router->initRoute())) {
            $module_file = $router->getRoute();
            if ($module_file) {
                require_once($module_file);
            }
        }
    }
}
