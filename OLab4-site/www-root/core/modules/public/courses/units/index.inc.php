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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation()), "read")) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if ((isset($_GET["cunit_id"])) && ($tmp_input = clean_input($_GET["cunit_id"], array("nows", "int")))) {
        $CUNIT_ID = $tmp_input;
    } else {
        $CUNIT_ID = 0;
    }

    $COURSE = Models_Course::fetchRowByID($COURSE_ID);
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE."?id=".$COURSE_ID, "title" => ($COURSE->getCourseCode() ? $COURSE->getCourseCode() . ": " : "") . $COURSE->getCourseName());

    $UNIT_URL = ENTRADA_URL."/".$MODULE."/".$SUBMODULE."?id=".$COURSE_ID."&cunit_id=".$CUNIT_ID;

    if ($COURSE_ID) {
        if ($ENTRADA_ACL->amIAllowed(new CourseUnitResource($CUNIT_ID), "update", true)) {
            switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]) {
            case "pcoordinator" :
                $admin_wording	= "Coordinator View";
                $admin_url		= ENTRADA_URL."/admin/courses/units?".replace_query(array("section" => "edit"));
                break;
            case "director" :
                $admin_wording	= "Director View";
                $admin_url		= ENTRADA_URL."/admin/courses/units?".replace_query(array("section" => "edit"));
                break;
            case "admin" :
            default:
                $admin_wording	= "Administrator View";
                $admin_url		= ENTRADA_URL."/admin/courses/units?".replace_query(array("section" => "edit"));
                break;
            }
            ob_start();
            ?>
            <ul class="menu">
                <li class="on">
                    <a href="<?php echo ENTRADA_URL."/courses/units?".replace_query(array("action" => false)); ?>.">Learner View</a>
                </li>
                <?php if ($admin_wording && $admin_url): ?>
                    <li class="off">
                        <a href="<?php echo $admin_url; ?>"><?php echo html_encode($admin_wording); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
            <?php
            $sidebar_html = ob_get_clean();
            new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
        }

        if ($CUNIT_ID) {
            $unit = Models_Course_Unit::fetchRowByID($CUNIT_ID);
            if ($unit) {
                try {
                    $HEAD[] = "<script>var SITE_URL = '".ENTRADA_URL."';</script>";
                    $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/$MODULE/$MODULE.css?release=".html_encode(APPLICATION_VERSION)."\" />";
                    $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/$MODULE/$SUBMODULE.css?release=".html_encode(APPLICATION_VERSION)."\" />";
                    $BREADCRUMB[] = array("url" => $UNIT_URL, "title" => $unit->getUnitText());
                    $unit_view = new Zend_View();
                    $unit_view->setScriptPath(dirname(__FILE__));
                    $unit_view->translate = $translate;
                    $unit_view->unit = $unit;
                    $unit_view->cunit_id = $CUNIT_ID;
                    $unit_view->course_id = $COURSE_ID;
                    $events_repository = Models_Repository_Events::getInstance();
                    $event_ids = array();
                    $events = $unit->getEvents();
                    foreach ($events as $event) {
                        $event_ids[] = $event->getID();
                    }
                    $efile_ids = $events_repository->fetchEventResourcesByEventIDs($event_ids);
                    $unit_view->hasEventFiles = (count($efile_ids) > 0);
                    $unit_view->associated_faculty = array_map(function ($proxy_id) {
                        return Models_User::fetchRowByID($proxy_id);
                    }, $unit->getAssociatedFaculty());
                    if ($unit->getWeekID()) {
                        $unit_view->week = Models_Week::fetchRowByID($unit->getWeekID());
                    } else {
                        $unit_view->week = null;
                    }
                    $unit_view->curriculum_period = Models_Curriculum_Period::fetchRowByID($unit->getCperiodID());
                    $unit_view->events = $unit->getEvents();
                    $objectives_repository = Models_Repository_Objectives::getInstance();
                    $unit_view->view_tags = function() use ($translate, $ENTRADA_USER, $COURSE_ID, $COURSE, $unit, $objectives_repository) {
                        if (defined("WEEK_OBJECTIVES_SHOW_LINKS") && WEEK_OBJECTIVES_SHOW_LINKS) {
                            $objective_rows = $objectives_repository->toArrays($unit->getObjectives());
                            $objectives_by_tag_set = $objectives_repository->groupArraysByTagSet($objective_rows);
                            $map_version = $unit->getCurriculumMapVersion();
                            $version_id = $map_version ? $map_version->getID() : null;

                            $objectives_view = new Zend_View();
                            $objectives_view->setScriptPath(ENTRADA_ABSOLUTE . "/core/includes/views/");
                            $objectives_view->translate = $translate;
                            $objectives_view->element_id = "week-objectives-section";
                            $objectives_view->anchor_name = "week-objectives-section";
                            $objectives_view->direction = "both";
                            $objectives_view->version_id = $version_id;
                            $objectives_view->objectives = $objectives_by_tag_set;
                            $objectives_view->cunit_id = $unit->getID();
                            $objectives_view->course_id  = $unit->getCourseID();
                            $objectives_view->cperiod_id  = $unit->getCperiodID();
                            $exclude_tag_set_ids = array();
                            foreach (explode(",", OBJECTIVE_LINKS_VIEW_EXCLUDE) as $exclude_tag_set_name) {
                                $exclude_tag_set = Models_Objective::fetchRowByNameParentID($ENTRADA_USER->getActiveOrganisation(), $exclude_tag_set_name, 0);
                                if ($exclude_tag_set) {
                                    $exclude_tag_set_ids[] = $exclude_tag_set->getID();
                                }
                            }
                            $objectives_view->exclude_tag_set_ids = $exclude_tag_set_ids;
                            return $objectives_view->render("objectives.inc.php");
                        } else {
                            return "";
                        }
                    };

                    $unit_view->view_event_objectives = function() use ($CUNIT_ID, $objectives_repository) {
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
                        $objectives_view->hide_objectives_by_default = false;
                        return $objectives_view->render("objectives.view.php");
                    };

                    echo $unit_view->render("detail.view.php");
                } catch (Exception $e) {
                    echo display_error($e->getMessage());
                }
            } else {
                $BREADCRUMB[] = array("url" => $UNIT_URL, "title" => $translate->_("View Unit"));
                echo display_error("Unit not found");
            }
        } else {
            $BREADCRUMB[] = array("url" => $UNIT_URL, "title" => $translate->_("View Unit"));
            echo display_error("Unit not specified");
        }
    } else {
        echo display_error("Course not specified");
    }
}
