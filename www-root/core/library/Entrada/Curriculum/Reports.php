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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Entrada_Curriculum_Reports
{
    private static function objectivesByTagSet($parent_id, $organisation_id) {
        $objective_repository = Models_Repository_Objectives::getInstance();
        if ($organisation_id == -1) {
            return $objective_repository->toArrays($objective_repository->fetchAllByTagSetID($parent_id));
        } else {
            return $objective_repository->toArrays($objective_repository->fetchAllByTagSetIDAndOrganisationID($parent_id, $organisation_id));
        }
    }

    private static function eventTypeTitles($organisation_id) {
        global $db;
        if ($organisation_id > 0) {
            $event_types = Models_EventType::fetchAllByOrganisationID($organisation_id);
            if ($event_types === false) {
                $event_types = array();
            }
        } else {
            $event_type_rows = $db->GetAll("SELECT * FROM events_lu_eventtypes WHERE eventtype_active = 1");
            if ($event_type_rows === false) {
                $event_types = array();
            } else {
                $event_types = array();
                foreach ($event_type_rows as $eventtype_row) {
                    $event_types[] = new Models_EventType($eventtype_row);
                }
            }
        }
        $event_type_titles = array();
        foreach ($event_types as $event_type) {
            $event_type_titles[$event_type->getID()] = $event_type->getEventTypeTitle();
        }
        return $event_type_titles;
    }

    private static function objectivesIncluded(array $values_by_group_objectives, array $objectives) {

        $objectives_included = array();

        foreach ($objectives as $objective) {

            $objective_id = $objective["objective_id"];

            foreach ($values_by_group_objectives as $grouped_data) {

                list($group_objective_ids, $values_by_objective) = $grouped_data;

                if (!empty($values_by_objective[$objective_id])) {
                    $objectives_included[$objective_id] = $objective;
                    break;
                }
            }
        }

        return $objectives_included;
    }

    private static function groupByTagSetsIncluded(array $values_by_group_objectives, array $group_by_tag_sets, array $group_objectives) {
        $group_by_tag_sets_included = array();
        foreach ($values_by_group_objectives as $grouped_data) {
            list($group_objective_ids, $values_by_objective) = $grouped_data;
            foreach ($group_by_tag_sets as $group_by_tag_set) {
                $group_by_tag_set_id = $group_by_tag_set["objective_id"];
                if (isset($group_objectives[$group_by_tag_set_id])) {
                    $group_by_tag_sets_included[$group_by_tag_set_id] = $group_by_tag_set;
                }
            }
        }
        return $group_by_tag_sets_included;
    }

    private static function groupObjectiveValues(array $values_by_group_objectives, array $objectives_included, array $group_by_tag_sets_included, array $group_objectives, $report_on_percentages, array $total_values_by_objective = array())
    {
        $output = array();

        foreach ($values_by_group_objectives as $grouped_data) {

            list($group_objective_ids, $values_by_objective) = $grouped_data;
            $my_group_objectives = array();

            foreach ($group_by_tag_sets_included as $group_by_tag_set) {

                $group_by_tag_set_id = $group_by_tag_set["objective_id"];

                if (isset($group_objective_ids[$group_by_tag_set_id])) {
                    $group_objective_id = $group_objective_ids[$group_by_tag_set_id];
                    $my_group_objectives[$group_by_tag_set_id] = $group_objectives[$group_by_tag_set_id][$group_objective_id];
                } else {
                    $my_group_objectives[$group_by_tag_set_id] = null;
                }
            }

            $sum = array_sum($values_by_objective);

            if ($sum > 0) {

                $values = array();

                foreach (array_keys($objectives_included) as $objective_id) {

                    if (!isset($total_values_by_objective[$objective_id])) {
                        $total_values_by_objective[$objective_id] = 0;
                    }

                    if (isset($values_by_objective[$objective_id])) {

                        if ($report_on_percentages) {
                            $value = $values_by_objective[$objective_id] / $sum * 100;
                        } else {
                            $value = $values_by_objective[$objective_id];
                        }

                        $total_values_by_objective[$objective_id] += $values_by_objective[$objective_id];
                    } else {
                        $value = 0;
                    }

                    $values[] = $value;
                }

                $output[] = array(
                    "group_objectives" => $my_group_objectives,
                    "values" => $values,
                );
            }
        }

        return array($output, $total_values_by_objective);
    }

    private static function totalValuesForObjectives(array $total_values_by_objective, array $objectives_included, $report_on_percentages) {

        $total_sum = array_sum($total_values_by_objective);
        $total_values = array();

        foreach (array_keys($objectives_included) as $objective_id) {

            if (isset($total_values_by_objective[$objective_id])) {
                if ($report_on_percentages) {
                    $total_values[] = $total_values_by_objective[$objective_id] / $total_sum * 100;
                } else {
                    $total_values[] = $total_values_by_objective[$objective_id];
                }
            } else {
                $total_values[] = 0;
            }
        }

        return array(
            "totals" => true,
            "values" => $total_values,
        );
    }

    private static function graphLabelsValues(array $total_values_by_objective, array $objectives) {
        $graph_labels = array();
        $graph_values = array();
        $total_sum = array_sum($total_values_by_objective);
        foreach (array_keys($total_values_by_objective) as $objective_id) {
            if ($total_values_by_objective[$objective_id] / $total_sum * 100 >= 1) {
                $graph_labels[$objective_id] = $objectives[$objective_id]["objective_name"];
            }
            $graph_values[$objective_id] = $total_values_by_objective[$objective_id];
        }
        return array($graph_labels, $graph_values);
    }

    private static function sortGroupedObjectives(array &$output, array $group_objectives) {

        uasort($output, function (array $row1, array $row2) {

            /**
             * Compare the sort orders of each group of objectives using
             * an array comparison.
             */
            $group_objectives1 = $row1["group_objectives"];
            $objective_orders1 = array();
            foreach ($group_objectives1 as $group_by_tag_set_id => $group_objective_id) {
                $objective_orders1[] = $group_objectives[$group_by_tag_set_id][$group_objective_id]["objective_order"];
            }

            $group_objectives2 = $row2["group_objectives"];
            foreach ($group_objectives2 as $group_by_tag_set_id => $group_objective_id) {
                $objective_orders2[] = $group_objectives[$group_by_tag_set_id][$group_objective_id]["objective_order"];
            }

            if ($objective_orders1 == $objective_orders2) {
                return 0;
            } else if ($objective_orders1 < $objective_orders2) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    /**
     * @param int|string $organisation_id
     * @param array $course_ids[] = int|string
     * @param int|string $reporting_start
     * @param int|string $reporting_finish
     * @param int|string $main_tag_set_id
     * @param array $group_by_tag_sets[int $group_by_tag_set_id] = Models_Objective
     * @param array $group_by_tag_set_ids[] = int $group_by_tag_set_id
     * @param array $filter_objective_ids_by_tag_set[int $tag_set_id] = int $objective_id
     * @param int|string $filter_week_id
     * @param bool $report_on_mappings
     * @param bool $report_on_percentages
     * @param bool $report_on_event_types
     * @param bool $show_graph
     */
    public static function processMinutes($organisation_id, array $course_ids, $reporting_start, $reporting_finish, $main_tag_set_id, array $group_by_tag_sets, array $group_by_tag_set_ids, array $filter_objective_ids_by_tag_set, $filter_week_id, $report_on_mappings, $report_on_percentages, $report_on_event_types, $show_graph) {

        $output = array();
        $objectives_included = array();
        $group_by_tag_sets_included = array();
        $graph_labels = array();
        $graph_values = array();

        $report_model = new Models_Reports_ObjectiveMappings();

        if ((int) $filter_week_id) {
            $cunit_ids = array_map(function ($course_unit) { return $course_unit->getID(); }, Models_Course_Unit::fetchAllByWeekID($filter_week_id));
        } else {
            $cunit_ids = array();
        }

        if ($group_by_tag_set_ids || $report_on_event_types) {

            if (!$report_on_event_types) {
                $objectives = self::objectivesByTagSet($main_tag_set_id, $organisation_id);
                $group_objectives = array();

                foreach ($group_by_tag_set_ids as $group_by_tag_set_id) {
                    $group_objectives[$group_by_tag_set_id] = self::objectivesByTagSet($group_by_tag_set_id, $organisation_id);
                }

                foreach ($course_ids as $course_id) {

                    if (!$report_on_mappings) {
                        $values_by_group_objectives = $report_model->durationsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $reporting_start, $reporting_finish, $filter_objective_ids_by_tag_set);
                    } else {
                        $values_by_group_objectives = $report_model->mappingsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $reporting_start, $reporting_finish, $filter_objective_ids_by_tag_set);
                    }

                    $group_by_tag_sets_included[$course_id] = self::groupByTagSetsIncluded($values_by_group_objectives, $group_by_tag_sets, $group_objectives);
                    $objectives_included[$course_id] = self::objectivesIncluded($values_by_group_objectives, $objectives);
                    list($output[$course_id], $total_values_by_objective) = self::groupObjectiveValues($values_by_group_objectives, $objectives_included[$course_id], $group_by_tag_sets_included[$course_id], $group_objectives, $report_on_percentages);
                    self::sortGroupedObjectives($output[$course_id], $group_objectives);

                    if (!empty($total_values_by_objective)) {
                        $output[$course_id][] = self::totalValuesForObjectives($total_values_by_objective, $objectives_included[$course_id], $report_on_percentages);
                        list($graph_labels[$course_id], $graph_values[$course_id]) = self::graphLabelsValues($total_values_by_objective, $objectives);
                    }
                }
            } else {
                $objectives = self::objectivesByTagSet($main_tag_set_id, $organisation_id);
                $group_objectives = array();

                foreach ($group_by_tag_set_ids as $group_by_tag_set_id) {
                    $group_objectives[$group_by_tag_set_id] = self::objectivesByTagSet($group_by_tag_set_id, $organisation_id);
                }

                foreach ($course_ids as $course_id) {

                    if (!$report_on_mappings) {
                        $values_by_group_objectives_by_event_type = $report_model->eventTypeDurationsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $reporting_start, $reporting_finish, $filter_objective_ids_by_tag_set);
                    } else {
                        $values_by_group_objectives_by_event_type = $report_model->eventTypeMappingsForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $reporting_start, $reporting_finish, $filter_objective_ids_by_tag_set);
                    }

                    $event_type_titles = self::eventTypeTitles($organisation_id);
                    $group_by_tag_sets_included[$course_id] = array();
                    $objectives_included[$course_id] = array();

                    foreach ($values_by_group_objectives_by_event_type as $values_by_group_objectives) {
                        $group_by_tag_sets_included[$course_id] += self::groupByTagSetsIncluded($values_by_group_objectives, $group_by_tag_sets, $group_objectives);
                        $objectives_included[$course_id] += self::objectivesIncluded($values_by_group_objectives, $objectives);
                    }

                    $output[$course_id] = array();
                    $total_values_by_objective = array();

                    foreach ($values_by_group_objectives_by_event_type as $event_type_id => $values_by_group_objectives) {
                        list($new_output, $total_values_by_objective) = self::groupObjectiveValues($values_by_group_objectives, $objectives_included[$course_id], $group_by_tag_sets_included[$course_id], $group_objectives, $report_on_percentages, $total_values_by_objective);

                        foreach (array_keys($new_output) as $i) {
                            $new_output[$i]["event_type"] = array(
                                "eventtype_id" => $event_type_id,
                                "eventtype_title" => $event_type_titles[$event_type_id],
                            );
                        }
                        $output[$course_id] = array_merge($output[$course_id], $new_output);
                    }

                    if (!empty($total_values_by_objective)) {
                        $output[$course_id][] = self::totalValuesForObjectives($total_values_by_objective, $objectives_included[$course_id], $report_on_percentages);
                        list($graph_labels[$course_id], $graph_values[$course_id]) = self::graphLabelsValues($total_values_by_objective, $objectives);
                    }
                }
            }
        } else {
            $objectives = self::objectivesByTagSet($main_tag_set_id, $organisation_id);

            foreach ($course_ids as $course_id) {

                $values_by_objective = $report_model->valuesByObjectives($main_tag_set_id, $organisation_id, $course_id, $cunit_ids, $reporting_start, $reporting_finish, $filter_objective_ids_by_tag_set);
                $durations_sum = array_sum(array_map(function ($values) { return $values["duration"]; }, $values_by_objective));

                foreach ($objectives as $objective_id => $objective) {

                    if (isset($values_by_objective[$objective_id])) {
                        $values = $values_by_objective[$objective_id];

                        if ($durations_sum > 0) {
                            $percentage = $values["duration"] / $durations_sum * 100;
                        }
                        else {
                            $percentage = 0;
                        }

                        $output[$course_id][] = array(
                            "objective_id" => $objective_id,
                            "objective_name" => $objective["objective_name"],
                            "objective_description" => $objective["objective_description"],
                            "duration" => $values["duration"],
                            "percentage" => $percentage,
                            "number_of_mappings" => $values["mappings"],
                        );

                        if ($show_graph) {
                            if ($percentage >= 1) {
                                $graph_labels[$course_id][$objective_id] = $objective["objective_name"];
                            }

                            if (!$report_on_mappings) {
                                $graph_values[$course_id][$objective_id] = $values["duration"];
                            } else {
                                $graph_values[$course_id][$objective_id] = $values["mappings"];
                            }
                        }
                    }
                }
            }
        }

        return array(
            $output,
            $group_by_tag_sets_included,
            $objectives_included,
            $graph_labels,
            $graph_values,
        );
    }
}
