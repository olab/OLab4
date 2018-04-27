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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Ryan Sherrington <ryan.sherrington@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation()), "read")) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script>var ENTRADA_URL = \"" . ENTRADA_URL . "\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var ORGANISATION = '" . $ENTRADA_USER->getActiveOrganisation() . "';</script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/$MODULE/$MODULE.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/$MODULE/$SUBMODULE.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/$MODULE/summary-print.css?release=".html_encode(APPLICATION_VERSION)."\" media=\"print\" />";

    if ((isset($_GET["cunit_id"])) && ($tmp_input = clean_input($_GET["cunit_id"], array("nows", "int")))) {
        $CUNIT_ID = $tmp_input;
    } else {
        $CUNIT_ID = 0;
    }

    $objectives_repository = Models_Repository_Objectives::getInstance();
    $objective_rows = $objectives_repository->fetchAllByCourseUnitIDs(array($CUNIT_ID));
    $summary_view = new Zend_View();
    $summary_view->translate = $translate;
    $summary_view->navigation()->breadcrumbs()->setRenderInvisible(true);
    $course = Models_Course::fetchRowByID($COURSE_ID);
    $unit = Models_Course_Unit::fetchRowByID($CUNIT_ID);
    $summary_view->curriculum_period = Models_Curriculum_Period::fetchRowByID($unit->getCperiodID());
    $summary_view->course_code = $course->getCourseCode();
    $summary_view->unit = $unit;

    if ($unit->getWeekID()) {
        $summary_view->week = Models_Week::fetchRowByID($unit->getWeekID());
    } else {
        $summary_view->week = null;
    }

    $summary_view->setScriptPath(dirname(__FILE__));

    $summary_view->view_event_objectives = function() use ($CUNIT_ID, $objectives_repository) {
        $objective_rows = $objectives_repository->fetchAllByCourseUnitIDs(array($CUNIT_ID));
        $weekly_objective_results = $objective_rows[$CUNIT_ID];

        $objectives_view = new Zend_View();
        $objectives_view->setScriptPath(dirname(__FILE__));
        $objectives_view->week_and_event_objectives = [];
        $week_objective_idx = 1;

        foreach ($weekly_objective_results as $week_objective_id => $week_objective) {
            $objectives_view->week_and_event_objectives[$week_objective_id] = [
                'objective_description' => $week_objective->getDescription(),
                'objective_name' => $week_objective->getName(),
                'index' => $week_objective_idx
            ];

            $results = $objectives_repository->fetchEventObjectivesByWeekObjective($week_objective_id, $CUNIT_ID);
            $objectives_view->week_and_event_objectives[$week_objective_id]['events'] = array();
            foreach ($results as $result) {
                $objectives_view->week_and_event_objectives[$week_objective_id]['events'][$result['event_id']][] = $result;
            }

            $week_objective_idx++;
        }
        $objectives_view->hide_objectives_by_default = true;
        return $objectives_view->render("objectives.view.php");
    };

    echo $summary_view->render("summary.view.php");
}
