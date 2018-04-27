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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM_REPORTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_RELATIVE);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("search", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => "", "title" => "Curriculum Tag Minutes and Mapping Report");

    $HEAD[] = "<link href=\"" . ENTRADA_RELATIVE . "/javascript/calendar/css/xc2_default.css?release=" . html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" media=\"all\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
    $HEAD[] = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/calendar/config/xc2_default.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/calendar/script/xc2_inpage.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".$ENTRADA_TEMPLATE->relative()."/js/libs/bootstrap-table.js?release=".APPLICATION_VERSION."\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".$ENTRADA_TEMPLATE->relative()."/js/libs/bootstrap-table-fixed-columns.js?release=".APPLICATION_VERSION."\"></script>\n";
    $HEAD[] = "<link href=\"" . $ENTRADA_TEMPLATE->relative() . "/css/bootstrap-table-fixed-columns.css?release=" . html_encode(APPLICATION_VERSION) . "\"rel=\"stylesheet\" media=\"all\"/>";
    $HEAD[] = "<link href=\"" . $ENTRADA_TEMPLATE->relative() . "/css/bootstrap-table.css?release=" . html_encode(APPLICATION_VERSION) . "\"rel=\"stylesheet\" media=\"all\"/>";


    /**
     * Add PlotKit to the beginning of the $HEAD array.
     */
    array_unshift($HEAD,
        "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/MochiKit/MochiKit.js\"></script>",
        "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/excanvas.js\"></script>",
        "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Base.js\"></script>",
        "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Layout.js\"></script>",
        "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Canvas.js\"></script>",
        "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/SweetCanvas.js\"></script>"
    );

    /**
     * Determine the organisation_id that has been selected.
     */
    $organisation_id_changed = false;
    list($all_organisations, $organisations) = Models_Organisation::fetchAllOrganisationsIAmAllowed();
    if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"])) {
        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = -1;
    } elseif ((isset($_GET["org_id"])) && ($tmp_input = clean_input($_GET["org_id"], "int"))) {
        $organisation_id_changed = true;
        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = $tmp_input;
    } else {
        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = (int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"];
    }
    if (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] != -1) && !$ENTRADA_ACL->amIAllowed("resourceorganisation".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"], "read")) {
        add_error("Your account does not have the permissions required to access this organisation.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
        echo display_error();
        application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this organisation [".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]."]");
    } else if (!$all_organisations && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] == -1) {
        add_error("Your account does not have the permissions required to access all organisations.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
        echo display_error();
        application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to all organisations.");
    } else {

        $objective_repository = Models_Repository_Objectives::getInstance();

        /**
         * Fetch all courses into an array that will be used.
         */
        if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] > 0) {
            $courses = Models_Course::fetchAllByOrg($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]);
        } else {
            $courses = Models_Course::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        }
        $course_list = array(0 => "All Courses");
        if ($courses) {
            foreach ($courses as $course) {
                $course_list[$course->getID()] = $course->getCourseText();
            }
        }

        /**
         * Determine selected course_ids.
         */
        if ((isset($_POST["course_ids"])) && (is_array($_POST["course_ids"]))) {
            $course_ids = array();
            foreach ($_POST["course_ids"] as $course_id) {
                if ($course_id == (int) $course_id) {
                    $course_ids[] = $course_id;
                }
            }
            if (count($course_ids)) {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = $course_ids;
            } else {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_keys($course_list);
            }
        } elseif (($organisation_id_changed) || (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"]))) {
            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"] = array_merge(array_keys($course_list), array(0));
        }

        /**
         * Fetch all curriculum tag sets (objectives with parent = 0) into an array that will be used.
         */
        if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] > 0) {
            $tag_sets = $group_by_tag_sets = $objective_repository->toArrays($objective_repository->fetchTagSetsByOrganisationID($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]));
        } else {
            $tag_sets = $group_by_tag_sets = $objective_repository->toArrays($objective_repository->fetchTagSetsByOrganisationID($ENTRADA_USER->getActiveOrganisation()));
        }

        /**
         * Determine what's the selected tag set.
         */
        if ((isset($_POST["main_tag_set_id"])) && ($tmp_input = clean_input($_POST["main_tag_set_id"], "int"))) {
            $main_tag_set_id = $tmp_input;
        } else {
            $main_tag_set_id = null;
        }

        /**
         * Determine what's the selected group-by tag set.
         */
        $group_by_tag_set_ids = array();
        if ((isset($_POST["group_by_tag_set_ids"])) && (is_array($_POST["group_by_tag_set_ids"]))) {
            $group_by_tag_set_ids = array();
            foreach ($_POST["group_by_tag_set_ids"] as $group_by_tag_set_id) {
                if ($group_by_tag_set_id = (int) $group_by_tag_set_id) {
                    $group_by_tag_set_ids[] = $group_by_tag_set_id;
                }
            }
        }

        /**
         * Determine what's the selected filter tag set.
         */
        if ((isset($_POST["filter_tag_set_id"])) && ($tmp_input = clean_input($_POST["filter_tag_set_id"], "int"))) {
            $filter_tag_set_id = $tmp_input;
        } else {
            $filter_tag_set_id = null;
        }

        /**
         * Determine what's the selected filter tag/objective.
         */
        if ((isset($_POST["filter_objective_id"])) && ($tmp_input = clean_input($_POST["filter_objective_id"], "int"))) {
            $filter_objective_id = $tmp_input;
        } else {
            $filter_objective_id = null;
        }
        if ($filter_tag_set_id && $filter_objective_id) {
            $filter_objective_ids_by_tag_set = array(
                $filter_tag_set_id => $filter_objective_id,
            );
        } else {
            $filter_objective_ids_by_tag_set = array();
        }

        /**
         * Fetch the possible filter objectives.
         */
        if ($filter_tag_set_id) {
            if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] == -1) {
                $filter_objectives = $objective_repository->toArrays(
                    $objective_repository->fetchAllByTagSetID($filter_tag_set_id));
            } else {
                $filter_objectives = $objective_repository->toArrays(
                    $objective_repository->fetchAllByTagSetIDAndOrganisationID($filter_tag_set_id, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]));
            }
            if (isset($filter_objectives[$filter_objective_id])) {
                $filter_objective_name = $filter_objectives[$filter_objective_id]["objective_name"];
            } else {
                $filter_objective_name = null;
            }
        } else {
            $filter_objectives = array();
            $filter_objective_name = null;
        }

        /**
         * Filter weeks
         */
        if (isset($_POST["filter_week_id"]) && ($tmp_input = clean_input($_POST["filter_week_id"], "int"))) {
            $filter_week_id = $_POST["filter_week_id"];
        } else {
            $filter_week_id = null;
        }

        $weeks = array();

        foreach (Models_Week::fetchAllByOrganisationID((int) $ENTRADA_USER->getActiveOrganisation()) as $week) {
            $weeks[$week->getID()] = $week;
        }

        /**
         * Determine whether we're being asked to report on event types.
         */
        if (isset($_POST["report_on_event_types"]) && $_POST["report_on_event_types"]) {
            $report_on_event_types = true;
        } else {
            $report_on_event_types = false;
        }

        /**
         * Determine whether we're being asked to report on mappings.
         */
        if (isset($_POST["report_on_mappings"]) && $_POST["report_on_mappings"]) {
            $report_on_mappings = true;
        } else {
            $report_on_mappings = false;
        }

        /**
         * Determine whether we're being asked to report on percentages.
         */
        if (isset($_POST["report_on_percentages"]) && $_POST["report_on_percentages"]) {
            $report_on_percentages = true;
        } else {
            $report_on_percentages = false;
        }

        /**
         * Determine whether we're being asked to show graph.
         */
        if (isset($_POST["show_graph"]) && $_POST["show_graph"]) {
            $show_graph = true;
        } else {
            $show_graph = false;
        }

        if (isset($_POST["export_csv"]) && $_POST["export_csv"]) {
            $export_csv = true;
        } else {
            $export_csv = false;
        }

        $form_view = new Zend_View();
        $form_view->setScriptPath(dirname(__FILE__));
        $form_view->translate = $translate;
        $form_view->reporting_start = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"];
        $form_view->reporting_finish = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"];
        $form_view->organisations = $organisations;
        $form_view->all_organisations = $all_organisations;
        $form_view->organisation_id = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"];
        $form_view->course_list = $course_list;
        $form_view->course_ids = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"];
        $form_view->main_tag_set_id = $main_tag_set_id;
        $form_view->tag_sets = $tag_sets;
        $form_view->group_by_tag_sets = $group_by_tag_sets;
        $form_view->group_by_tag_set_ids = $group_by_tag_set_ids;
        $form_view->filter_tag_set_id = $filter_tag_set_id;
        $form_view->filter_objective_id = $filter_objective_id;
        $form_view->filter_objectives = $filter_objectives;
        $form_view->filter_week_id = $filter_week_id;
        $form_view->filter_weeks = $weeks;
        $form_view->report_on_event_types = $report_on_event_types;
        $form_view->report_on_mappings = $report_on_mappings;
        $form_view->report_on_percentages = $report_on_percentages;
        $form_view->show_graph = $show_graph;
        $form_view->export_csv = $export_csv;

        echo $form_view->render("minutes-form.view.php");

        if ($STEP == 2) {
            list(
                $output,
                $group_by_tag_sets_included,
                $objectives_included,
                $graph_labels,
                $graph_values
                ) = Entrada_Curriculum_Reports::processMinutes(
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"],
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["course_ids"],
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"],
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"],
                $main_tag_set_id,
                $group_by_tag_sets,
                $group_by_tag_set_ids,
                $filter_objective_ids_by_tag_set,
                $filter_week_id,
                $report_on_mappings,
                $report_on_percentages,
                $report_on_event_types,
                $show_graph
            );

            $get_objective_text = 'get_objective_text';

            if (!$export_csv) {
                $report_view = new Zend_View();
                $report_view->setScriptPath(dirname(__FILE__));
                $report_view->translate = $translate;
                $report_view->get_objective_text = $get_objective_text;
                $report_view->reporting_start = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"];
                $report_view->reporting_finish = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"];
                $report_view->course_list = $course_list;
                $report_view->report_on_event_types = $report_on_event_types;
                $report_view->report_on_mappings = $report_on_mappings;
                $report_view->report_on_percentages = $report_on_percentages;
                $report_view->group_by_tag_set_ids = $group_by_tag_set_ids;
                $report_view->group_by_tag_sets_included = $group_by_tag_sets_included;
                $report_view->filter_objective_name = $filter_objective_name;
                $report_view->filter_week_id = $filter_week_id;
                $report_view->weeks = $weeks;
                $report_view->objectives_included = $objectives_included;
                $report_view->output = $output;
                $report_view->show_graph = $show_graph;
                $report_view->export_csv = $export_csv;
                $report_view->graph_labels = $graph_labels;
                $report_view->graph_values = $graph_values;
                echo $report_view->render("minutes-report.view.php");
            } else {
                if (count($output)) {
                    ob_clear_open_buffers();
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: text/csv");
                    header("Content-Disposition: attachment; filename=\"curriculum-search-" . date("Y-m-d_H:i:s") . ".csv\"");
                    header("Content-Transfer-Encoding: binary");

                    $csvFilePointer = fopen("php://output", "w");

                    foreach ($output as $course_id => $data) {
                        fputcsv($csvFilePointer, $course_list[$course_id]);

                        if ($group_by_tag_set_ids || $report_on_event_types) {
                            $csvRow = array();

                            if ($report_on_event_types) {
                                $csvRow[] = $this->translate->_("Event Type");
                            }

                            foreach ($group_by_tag_sets_included[$course_id] as $group_by_tag_set) {
                                $csvRow[] = $group_by_tag_set["objective_name"];
                            }

                            if (!$report_on_mappings) {
                                if ($report_on_percentages) {
                                    $csvRow[] = "Total Percentage";
                                } else {
                                    $csvRow[] = "Total Minutes";
                                }
                            } else {
                                $csvRow[] = "Total Mappings";
                            }

                            foreach ($objectives_included[$course_id] as $objective) {
                                $csvRow[] = $get_objective_text($objective);
                            }

                            fputcsv($csvFilePointer, $csvRow);

                            foreach ($data as $row) {
                                $csvRow = array();

                                if ($report_on_event_types) {
                                    if (isset($row["totals"]) && $row["totals"] === true) {
                                        $csvRow[] = "Total";
                                    } else {
                                        $csvRow[] = $row["event_type"]["eventtype_title"];
                                    }
                                }

                                if (isset($row["totals"]) && $row["totals"] === true) {
                                    foreach ($group_by_tag_sets_included[$course_id] as $group_by_tag_set) {
                                        $csvRow[] = "Total";
                                    }
                                } else {
                                    foreach ($row["group_objectives"] as $group_objective) {
                                        $csvRow[] = $get_objective_text($group_objective);
                                    }
                                }

                                $csvRow[] = round(array_sum($row["values"]), 1);

                                foreach ($row["values"] as $value) {
                                    $csvRow[] = round($value, 1);
                                }

                                fputcsv($csvFilePointer, $csvRow);
                            }
                        } else {
                            $csvRow = array();
                            $csvRow[] = "Curriculum Tag";

                            if ($report_on_mappings) {
                                $csvRow[] = "Mappings";
                            }

                            $csvRow[] = "Minutes";
                            $csvRow[] = "Hours";

                            if ($report_on_percentages) {
                                $csvRow[] = "Percentage";
                            }

                            fputcsv($csvFilePointer, $csvRow);

                            foreach ($data as $objective) {
                                $csvRow = array();
                                $csvRow[] = $get_objective_text($objective);

                                if ($report_on_mappings) {
                                    $csvRow[] = isset($objective["number_of_mappings"]) ? (int)$objective["number_of_mappings"] : "";
                                }

                                $csvRow[] = round($objective["duration"], 1);
                                $csvRow[] = round($objective["duration"] / 60.0, 1);

                                if ($report_on_percentages) {
                                    $csvRow[] = round($objective["percentage"], 1);
                                }

                                fputcsv($csvFilePointer, $csvRow);
                            }
                        }
                    }

                    exit;
                } else {
                    echo display_notice(array("There are no objectives linked to any events within the specified constraints."));
                }
            }
        }
    }
}
