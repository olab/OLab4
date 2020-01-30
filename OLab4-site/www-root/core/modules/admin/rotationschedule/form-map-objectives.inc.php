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
 * The default file that is loaded when /admin/learners is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ROTATION_SCHEDULE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {

    $schedule = null;
    if ($PROCESSED["schedule_id"]) {
        $schedule = Models_Schedule::fetchRowByID($PROCESSED["schedule_id"]);
        $schedule->fromArray($PROCESSED);
        if ($schedule) {

            $draft = Models_Schedule_Draft::fetchRowByID($schedule->getDraftID());
            if ($draft) {
                if ($draft && $draft->getStatus() == "draft") {
                    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts", "title" => "My Drafts");
                }
                $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $draft->getID(), "title" => $draft->getTitle());
            }

            $schedule_breadcrumb = $schedule->getBreadCrumbData();
            if ($schedule_breadcrumb) {
                $schedule_breadcrumb = array_reverse($schedule_breadcrumb);
                foreach ($schedule_breadcrumb as $breadcrumb_data) {
                    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&schedule_id=" . $breadcrumb_data["schedule_id"], "title" => $breadcrumb_data["title"]);
                }
            }
        }
    }

    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE, "title" => $translate->_("Map Objectives"));
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/rotationschedule/map-objectives.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    Entrada_Utilities::addJavascriptTranslation("Map", "map_objective");
    Entrada_Utilities::addJavascriptTranslation("Unmap", "unmap_objective");

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";

    $likelihood_datasource = array();
    $likelihood_model = new Models_Likelihood();
    $likelihoods = $likelihood_model->fetchAllRecords();
    if ($likelihoods) {
        foreach ($likelihoods as $likelihood) {
            $likelihood_datasource[] = $likelihood->toArray();
        }
    }

    $all_course_objectives = array();
    $mapped_objectives = array();
    $mapped_objectives_count = 0;
    $all_course_objectives_count = 0;
    $epa_mapped_percentage = 0;

    if ($schedule) {

        $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(), "course_id" => $schedule->getCourseID()));
        $all_course_objectives = $tree_object->fetchTreeNodesAtDepth(2, "o.objective_order");

        if ($all_course_objectives) {

            $mapped_objective_model = new Models_Schedule_CourseObjective();
            $mapped_objectives = $mapped_objective_model->fetchAllByScheduleIDCourseIDJoinRelevantInfo($schedule->getID(), $schedule->getCourseID());

            // Process the mapped objectives vs the course objectives to determine what will be selected in the view.
            foreach ($all_course_objectives as $key => $course_objective) {

                $likelihood_id = false;
                $likelihood_title = false;
                $priority = false;
                if ($mapped_objectives) {
                    foreach ($mapped_objectives as $mapped_objective) {
                        if ($mapped_objective["objective_id"] == $course_objective["objective_id"]) {
                            $likelihood_id = $mapped_objective["likelihood_id"];
                            $likelihood_title = $mapped_objective["likelihood_title"];
                            $priority = $mapped_objective["priority"];
                        }
                    }
                }
                $course_objective["selected_likelihood_id"] = $likelihood_id ? $likelihood_id : null;
                $course_objective["selected_likelihood_title"] = $likelihood_title ? $likelihood_title : null;
                $course_objective["mapped"] = $likelihood_id ? true : false;
                $course_objective["priority"] = $priority ? true : false;

                // We also want to determine all rotations the objective is mapped to in the course.
                $rotations = $mapped_objective_model->getCourseRotationsObjectiveID($course_objective["objective_id"], $schedule->getCourseID());
                $course_objective["rotations"] = $rotations ? $rotations : array();

                $all_course_objectives[$key] = $course_objective;
            }

            // Determine the overall percentage of EPAs for the course mapped to this schedule.
            $mapped_objectives_count = count($mapped_objectives);
            $all_course_objectives_count = count($all_course_objectives);

            if ($mapped_objectives) {
                $epa_mapped_percentage = round(($mapped_objectives_count / $all_course_objectives_count) * 100);
            }
        } else {
            $all_course_objectives = array();
        }
        ?>
        <script type="text/javascript">
            var schedule_id = <?php echo $schedule->getID(); ?>;
            var course_id = <?php echo $schedule->getCourseID(); ?>;
        </script>
    <?php } ?>

    <form id="rotation-objectives-mapping-form">
        <?php
        $objective_list = new Views_Schedule_Course_ObjectiveList();
        $objective_list->render(array(
            "id"                        => "rotation-objectives-table",
            "class"                     => "rotation-objectives-table table table-bordered",
            "title"                     => $translate->_("Objective Mapping") . " - " . $schedule->getTitle(),
            "no_results_label"          => $translate->_("There are currently no course objectives uploaded"),
            "progress_bar_label"        => sprintf($translate->_("%d of %d EPAs Mapped to this Rotation (%s)"), $mapped_objectives_count, $all_course_objectives_count, $epa_mapped_percentage . "%"),
            "objectives"                => $all_course_objectives,
            "mapped_percentage"         => $epa_mapped_percentage,
            "likelihood_datasource"     => $likelihood_datasource,
            "course_id"                 => $schedule->getCourseID(),
            "schedule_id"               => $schedule->getID()
        ));
        ?>
    </form>

    <?php
}