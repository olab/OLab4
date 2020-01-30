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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("unitcontent", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/courses/units?id=".$COURSE_ID."&section=add", "title" => $translate->_("Add Unit"));

    $unit = new Models_Course_Unit(array("course_id" => $COURSE_ID));
    $allowed_linked_objective_ids = $unit->getAllowedLinkedObjectiveIDs($ENTRADA_USER->getActiveOrganisation());

    $allowed_tag_set_ids = Models_Objective_LinkedTagSet::fetchAllowedTagSetIDs("course_unit", $ENTRADA_USER->getActiveOrganisation());

    $view = function (array $PROCESSED) use (&$HEAD, &$ONLOAD, $translate, $COURSE_ID, $COURSE, $PREFERENCES, $ENTRADA_USER, $MODULE, $SUBMODULE, $allowed_tag_set_ids, $allowed_linked_objective_ids) {
        $HEAD[] = "<link rel=\"stylesheet\" href=\"".ENTRADA_URL."/css/".$MODULE."/".$MODULE.".css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" href=\"".ENTRADA_URL."/css/".$MODULE."/".$SUBMODULE.".css\" />";
        $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/objective-link-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/objectives/link-admin.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" media=\"all\"></link>";
        load_rte("course_units");

        $form_view = new Zend_View();
        $form_view->setScriptPath(dirname(__FILE__));
        $form_view->onload = function ($code) use (&$ONLOAD) {
            $ONLOAD[] = $code;
        };
        $form_view->translate = $translate;
        $form_view->curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeIDCourseID($COURSE->getCurriculumTypeID(), $COURSE->getID());
        $form_view->weeks = Models_Week::fetchAllByCurriculumType($COURSE->getCurriculumTypeID());
        if (isset($PROCESSED["cperiod_id"])) {
            $form_view->cperiod_id = isset($PROCESSED["cperiod_id"]) ? $PROCESSED["cperiod_id"] : null;
        } else {
            $form_view->cperiod_id = isset($PREFERENCES["selected_curriculum_period"]) ? $PREFERENCES["selected_curriculum_period"] : null;
        }
        $form_view->unit_code = isset($PROCESSED["unit_code"]) ? $PROCESSED["unit_code"] : "";
        $form_view->unit_title = isset($PROCESSED["unit_title"]) ? $PROCESSED["unit_title"] : "";
        $form_view->unit_description = isset($PROCESSED["unit_description"]) ? $PROCESSED["unit_description"] : "";
        $form_view->unit_order = isset($PROCESSED["unit_order"]) ? $PROCESSED["unit_order"] : 0;
        $form_view->faculty_list = Controllers_CourseUnitForm::getFacultyList();
        $form_view->associated_faculty = isset($PROCESSED["associated_faculty"]) ? $PROCESSED["associated_faculty"] : array();
        $form_view->objectives = isset($PROCESSED["objectives"]) ? $PROCESSED["objectives"] : array();
        $form_view->user = $ENTRADA_USER;
        $form_view->week_id = isset($PROCESSED["week_id"]) ? $PROCESSED["week_id"] : null;
        $form_view->course_id = $COURSE_ID;
        if (isset($PROCESSED["version_id"])) {
            $form_view->version_id = $PROCESSED["version_id"];
        } else {
            $version_repository = Models_Repository_CurriculumMapVersions::getInstance();
            $versions = $version_repository->fetchVersionsByCourseIDCperiodID($form_view->course_id, $form_view->cperiod_id);
            if ($versions) {
                $first = current($versions);
                $map_version = $first;
                $form_view->version_id = $map_version->getID();
            } else {
                $form_view->version_id = null;
            }
        }
        $form_view->linked_objectives = isset($PROCESSED["linked_objectives"]) ? $PROCESSED["linked_objectives"] : array();
        $form_view->allowed_tag_set_ids = $allowed_tag_set_ids;
        $form_view->allowed_linked_objective_ids = $allowed_linked_objective_ids;
        $form_view->action_url = ENTRADA_URL."/admin/courses/units?section=add&id=".$COURSE_ID."&step=2";
        $form_view->mode = "add";
        echo $form_view->render("form.view.php");
    };

    switch ($STEP) {
    case 2:
        list($saved, $PROCESSED) = Controllers_CourseUnitForm::processAdd();
        if (!$saved) {
            $view($PROCESSED);
        }
        break;
    case 1:
    default:
        $view(array());
        break;
    }
}
