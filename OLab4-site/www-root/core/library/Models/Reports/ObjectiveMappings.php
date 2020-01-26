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

class Models_Reports_ObjectiveMappings extends Models_Reports_Base {

    protected $events_linking_to_cache = array();
    protected function eventsLinkingTo(array $to_objective_ids, $course_id, array $cunit_ids, $start, $end)
    {
        global $db;
        $durations_by_objective_event = array();
        $uncached_objective_ids = array();
        foreach ($to_objective_ids as $to_objective_id) {
            if (isset($this->events_linking_to_cache[$course_id][$start][$end][$to_objective_id])) {
                $durations_by_objective_event[$to_objective_id] = $this->events_linking_to_cache[$course_id][$start][$end][$to_objective_id];
            } else {
                $uncached_objective_ids[] = $to_objective_id;
            }
        }
        if (!empty($uncached_objective_ids)) {
            $event_repository = Models_Repository_Events::getInstance();
            $objective_repository = Models_Repository_Objectives::getInstance();
            $events_by_objective = $event_repository->fetchAllByObjectiveIDsAndFilters($uncached_objective_ids, array(
                "course_ids" => array($course_id),
                "cunit_ids" => $cunit_ids,
                "start" => $start,
                "end" => $end,
            ));
            if ($events_by_objective) {
                $event_ids = array_unique(array_map(function (Models_Event $event) { return $event->getID(); }, $event_repository->flatten($events_by_objective)));
                $total_mappings_by_event = $event_repository->fetchTotalMappingsByEventIDs($event_ids);
                $event_types_by_event = $event_repository->fetchEventTypesByEventIDs($event_ids);
                $objectives = $objective_repository->fetchAllByIDs(array_keys($events_by_objective));
                foreach ($events_by_objective as $objective_id => $events) {
                    foreach ($events as $event_id => $event) {
                        $objective_parent_id = $objectives[$objective_id]->getParent();
                        if (!empty($total_mappings_by_event[$event_id][$objective_parent_id])) {
                            $total_mappings = $total_mappings_by_event[$event_id][$objective_parent_id];
                        } else {
                            $total_mappings = 0;
                        }
                        if (isset($event_types_by_event[$event_id])) {
                            $event_types = $event_types_by_event[$event_id];
                        } else {
                            $event_types = array();
                        }
                        if ($total_mappings > 0) {
                            $durations_by_objective_event[$objective_id][$event_id][0]["total_mappings"] = $total_mappings;
                            $durations_by_objective_event[$objective_id][$event_id][0]["event_duration"] = (float) $event->getEventDuration();
                            foreach ($event_types as $event_type_id => $event_eventtype) {
                                $durations_by_objective_event[$objective_id][$event_id][0]["event_type_duration"][$event_type_id] = (float) $event_eventtype->getDuration();
                            }
                        } else {
                            $durations_by_objective_event[$objective_id][$event_id] = array();
                        }
                    }
                    $this->events_linking_to_cache[$course_id][$start][$end][$objective_id] = $durations_by_objective_event[$objective_id];
                }
            }
        }
        return $durations_by_objective_event;
    }

    protected $objectives_linking_to_by_parent_cache = array();
    protected function objectivesLinkingToByParent($version_id, array $to_objective_ids, $course_id, array $cunit_ids, $start, $end) {
        global $db;
        $objective_links = array();
        $uncached_objective_ids = array();
        foreach ($to_objective_ids as $to_objective_id) {
            if (isset($this->objectives_linking_to_by_parent_cache[$version_id][$to_objective_id])) {
                $objective_links[$to_objective_id] = $this->objectives_linking_to_by_parent_cache[$version_id][$to_objective_id];
            } else {
                $uncached_objective_ids[] = $to_objective_id;
            }
        }
        if ($uncached_objective_ids) {
            $objective_repository = Models_Repository_Objectives::getInstance();
            $from_objectives_by_event = $objective_repository->flatten($objective_repository->fetchLinkedObjectivesByIDsAndEvents("to", $uncached_objective_ids, $version_id, false, array(
                "course_ids" => array($course_id),
                "cunit_ids" => $cunit_ids,
                "start" => $start,
                "end" => $end,
            )));
            foreach ($from_objectives_by_event as $event_id => $from_objectives_by_to_objective) {
                foreach ($from_objectives_by_to_objective as $to_objective_id => $from_objectives) {
                    foreach ($from_objectives as $from_objective_id => $from_objective) {
                        $from_parent_id = $from_objective->getParent();
                        $objective_links[$to_objective_id][$event_id][$from_parent_id][$from_objective_id] = true;
                        $this->objectives_linking_to_by_parent_cache[$version_id][$to_objective_id][$event_id][$from_parent_id][$from_objective_id] = true;
                    }
                }
            }
        }
        return $objective_links;
    }

    protected $total_mappings_by_objectives_cache = array();
    protected function totalMappingsByObjectivesTo($version_id, $to_tag_set_id, array $from_objective_ids, array $event_ids)
    {
        global $db;
        $mappings_by_objective = array();
        $uncached_objective_ids = array();
        foreach ($from_objective_ids as $from_objective_id) {
            if (isset($this->total_mappings_by_objectives_cache[$version_id][$to_tag_set_id][$from_objective_id])) {
                $mappings_by_objective[$from_objective_id] = $this->total_mappings_by_objectives_cache[$version_id][$to_tag_set_id][$from_objective_id];
            } else {
                $uncached_objective_ids[] = $from_objective_id;
            }
        }
        $objective_repository = Models_Repository_Objectives::getInstance();
        $uncached_mappings_by_objective = $objective_repository->fetchTotalMappingsByObjectivesTo($version_id, $to_tag_set_id, $uncached_objective_ids, $event_ids);
        foreach ($uncached_mappings_by_objective as $objective_id => $uncached_mappings_by_event) {
            foreach ($uncached_mappings_by_event as $event_id => $mappings) {
                $mappings_by_objective[$objective_id][$event_id] = $mappings;
                $this->total_mappings_by_objectives_cache[$version_id][$to_tag_set_id][$objective_id][$event_id] = $mappings;
            }
        }
        return $mappings_by_objective;
    }

    protected function eventsLinkedToObjectiveSet($tag_set_id, array $to_objective_ids, $version_id, $course_id, array $cunit_ids, $start, $end)
    {
        $objective_links = $this->objectivesLinkingToByParent($version_id, $to_objective_ids, $course_id, $cunit_ids, $start, $end);
        $data = array();
        $from_objective_ids = array();
        $event_ids = array();

        foreach ($objective_links as $from_objectives_by_event) {
            foreach ($from_objectives_by_event as $event_id => $from_objectives_by_tag_set) {
                $event_ids[$event_id] = $event_id;
                foreach ($from_objectives_by_tag_set as $from_objectives) {
                    foreach ($from_objectives as $from_objective_id => $is_linked) {
                        $from_objective_ids[$from_objective_id] = $from_objective_id;
                    }
                }
            }
        }

        $total_mappings_by_objective = $this->totalMappingsByObjectivesTo($version_id, $tag_set_id, $from_objective_ids, $event_ids);

        foreach ($objective_links as $to_objective_id => $from_objectives_by_event) {

            foreach ($from_objectives_by_event as $event_id => $from_objectives_by_tag_set) {

                foreach ($from_objectives_by_tag_set as $from_tag_set_id => $from_objectives) {

                    $from_objective_ids = array_keys($from_objectives);
                    $my_data = $this->eventsLinkedToObjectiveSet($from_tag_set_id, $from_objective_ids, $version_id, $course_id, $cunit_ids, $start, $end);

                    if (isset($my_data["objectives"][$event_id][$from_tag_set_id])) {
                        $my_data_by_objective = $my_data["objectives"][$event_id][$from_tag_set_id];

                        foreach (array_keys($my_data_by_objective) as $from_objective_id) {

                            if (isset($total_mappings_by_objective[$from_objective_id][$event_id])) {
                                $total_mappings = $total_mappings_by_objective[$from_objective_id][$event_id];
                            } else if (isset($total_mappings_by_objective[$from_objective_id][""])) {
                                $total_mappings = $total_mappings_by_objective[$from_objective_id][""];
                            } else {
                                $total_mappings = 0;
                            }

                            $my_data_by_objective[$from_objective_id]["total_mappings"] = $total_mappings;
                        }
                        $data["objectives"][$event_id][$tag_set_id][$to_objective_id]["objectives"][$event_id][$from_tag_set_id] = $my_data_by_objective;
                    } else if (!$event_id && isset($my_data["objectives"])) {

                        foreach ($my_data["objectives"] as $my_event_id => $my_data_by_from_tag_set) {

                            if (isset($my_data_by_from_tag_set[$from_tag_set_id])) {
                                $my_data_by_objective = $my_data_by_from_tag_set[$from_tag_set_id];
                            }

                            foreach (array_keys($my_data_by_objective) as $from_objective_id) {

                                if (isset($total_mappings_by_objective[$from_objective_id][$my_event_id])) {
                                    $total_mappings = $total_mappings_by_objective[$from_objective_id][$my_event_id];
                                } else if (isset($total_mappings_by_objective[$from_objective_id][""])) {
                                    $total_mappings = $total_mappings_by_objective[$from_objective_id][""];
                                } else {
                                    $total_mappings = 0;
                                }

                                $my_data_by_objective[$from_objective_id]["total_mappings"] = $total_mappings;
                            }

                            $data["objectives"][$my_event_id][$tag_set_id][$to_objective_id]["objectives"][$my_event_id][$from_tag_set_id] = $my_data_by_objective;
                        }
                    }
                }
            }
        }

        $event_data = $this->eventsLinkingTo($to_objective_ids, $course_id, $cunit_ids, $start, $end);

        foreach ($event_data as $to_objective_id => $duration_data_by_event) {
            foreach ($duration_data_by_event as $event_id => $duration_data) {
                $data["objectives"][$event_id][$tag_set_id][$to_objective_id]["event"] = $duration_data;
            }
        }

        return $data;
    }

    protected function mergeData(array $data, array $my_data)
    {
        $new_data = $data;
        if (isset($my_data["objectives"])) {
            foreach ($my_data["objectives"] as $my_event_id => $my_data_by_tag_set) {
                foreach ($my_data_by_tag_set as $my_tag_set_id => $my_data_by_objective) {
                    if (isset($data["objectives"][$my_event_id][$my_tag_set_id])) {
                        foreach ($my_data_by_objective as $my_objective_id => $my_data_for_objective) {
                            if (isset($data["objectives"][$my_event_id][$my_tag_set_id][$my_objective_id])) {
                                $data_for_objective = $data["objectives"][$my_event_id][$my_tag_set_id][$my_objective_id];
                                $new_data_for_objective = $this->mergeData($data_for_objective, $my_data_for_objective);
                                $new_data["objectives"][$my_event_id][$my_tag_set_id][$my_objective_id] = $new_data_for_objective;
                            } else {
                                $new_data["objectives"][$my_event_id][$my_tag_set_id][$my_objective_id] = $my_data_for_objective;
                            }
                        }
                    } else {
                        $new_data["objectives"][$my_event_id][$my_tag_set_id] = $my_data_by_objective;
                    }
                }
            }
        }
        if (isset($my_data["event"])) {
            $my_duration_data = $my_data["event"];
            if (isset($my_data["total_mappings"])) {
                $total_mappings = $my_data["total_mappings"];
                $map_event_values = function ($event_values) use ($total_mappings) {
                    $event_values["total_mappings"] = $event_values["total_mappings"] * $total_mappings;
                    return $event_values;
                };
                $new_duration_data = array_map($map_event_values, $my_duration_data);
            } else {
                $new_duration_data = $my_duration_data;
            }
            if (isset($data["event"])) {
                $old_duration_data = $data["event"];
                $new_data["event"] = array_merge($old_duration_data, $new_duration_data);
            } else {
                $new_data["event"] = $new_duration_data;
            }
        }
        return $new_data;
    }

    protected function collapseData(array $data, array $tag_sets_included)
    {
        $collapsed_data = $data;
        if (isset($data["event"]) && isset($data["objectives"])) {
            throw new InvalidArgumentException(sprintf("Tags are corrupt in the system. Tags are assigned to events %s at multiple levels. Please contact the curriculum manager.", implode(", ", array_keys($data["objectives"]))));
        }
        if (isset($data["objectives"])) {
            foreach ($data["objectives"] as $event_id => $data_by_tag_set) {
                foreach ($data_by_tag_set as $tag_set_id => $data_by_objective) {
                    if (in_array($tag_set_id, $tag_sets_included)) {
                        foreach ($data_by_objective as $objective_id => $my_data) {
                            $my_collapsed_data = $this->collapseData($my_data, $tag_sets_included);
                            $collapsed_data["objectives"][$event_id][$tag_set_id][$objective_id] = $my_collapsed_data;
                            if (empty($collapsed_data["objectives"][$event_id][$tag_set_id][$objective_id])) {
                                unset($collapsed_data["objectives"][$event_id][$tag_set_id][$objective_id]);
                            }
                        }
                        if (empty($collapsed_data["objectives"][$event_id][$tag_set_id])) {
                            unset($collapsed_data["objectives"][$event_id][$tag_set_id]);
                        }
                    } else {
                        unset($collapsed_data["objectives"][$event_id][$tag_set_id]);
                        foreach ($data_by_objective as $objective_id => $my_data) {
                            $my_collapsed_data = $this->collapseData($my_data, $tag_sets_included);
                            if (!empty($my_collapsed_data)) {
                                $collapsed_data = $this->mergeData($collapsed_data, $my_collapsed_data);
                            }
                        }
                    }
                }
                if (empty($collapsed_data["objectives"][$event_id])) {
                    unset($collapsed_data["objectives"][$event_id]);
                }
            }
            if (empty($collapsed_data["objectives"])) {
                unset($collapsed_data["objectives"]);
            }
        }
        return $collapsed_data;
    }

    protected function groupEventsLinkedToObjectives($tag_set_id, array $objective_ids, array $group_by_tag_set_ids, $organisation_id, $version_id, $course_id, array $cunit_ids, $start, $end)
    {
        $data = $this->eventsLinkedToObjectiveSet($tag_set_id, $objective_ids, $version_id, $course_id, $cunit_ids, $start, $end);
        $tag_sets_included = array_merge(array($tag_set_id), $group_by_tag_set_ids);
        $collapsed_data = $this->collapseData($data, $tag_sets_included);
        return $collapsed_data;
    }

    protected function groupEventsLinkedToObjectiveSet($main_tag_set_id, array $group_by_tag_set_ids, $organisation_id, $version_id, $course_id, array $cunit_ids, $start, $end)
    {
        $objective_repository = Models_Repository_Objectives::getInstance();
        if ($organisation_id == -1) {
            $main_objectives = $objective_repository->toArrays($objective_repository->fetchAllByTagSetID($main_tag_set_id));
        } else {
            $main_objectives = $objective_repository->toArrays($objective_repository->fetchAllByTagSetIDAndOrganisationID($main_tag_set_id, $organisation_id));
        }
        $main_objective_ids = array_keys($main_objectives);
        $collapsed_main_data = $this->groupEventsLinkedToObjectives($main_tag_set_id, $main_objective_ids, $group_by_tag_set_ids, $organisation_id, $version_id, $course_id, $cunit_ids, $start, $end);
        return $collapsed_main_data;
    }

    protected function collectGroupedData(array $data, array $objective_id_by_tag_set = array(), $total_mappings = 1, $event_id = null)
    {
        $rows = array();
        if (isset($data["total_mappings"])) {
            $new_total_mappings = $total_mappings * $data["total_mappings"];
        } else {
            $new_total_mappings = $total_mappings;
        }
        if (isset($data["objectives"])) {
            foreach ($data["objectives"] as $event_id => $data_by_tag_set) {
                foreach ($data_by_tag_set as $tag_set_id => $data_by_objective) {
                    foreach ($data_by_objective as $objective_id => $my_data) {
                        $new_objective_id_by_tag_set = array($tag_set_id => $objective_id) + $objective_id_by_tag_set;
                        $new_rows = $this->collectGroupedData($my_data, $new_objective_id_by_tag_set, $new_total_mappings, $event_id);
                        $rows = array_merge($rows, $new_rows);
                    }
                }
            }
        }
        if (isset($data["event"])) {
            if (empty($event_id)) {
                throw new Exception();
            }
            $duration_data = $data["event"];
            $event_data = array();
            $total_count = 0;
            $duration_minutes = 0.0;
            $duration_minutes_by_event_type = array();
            foreach ($duration_data as $event_values) {
                $event_duration_minutes = $event_values["event_duration"];
                if ($event_values["total_mappings"] > 0 && $new_total_mappings > 0) {
                    $duration_minutes += $event_values["event_duration"] / $event_values["total_mappings"] / $new_total_mappings;
                    foreach ($event_values["event_type_duration"] as $event_type_id => $event_type_duration_minutes) {
                        if (!isset($duration_minutes_by_event_type[$event_type_id])) {
                            $duration_minutes_by_event_type[$event_type_id] = 0.0;
                        }
                        $duration_minutes_by_event_type[$event_type_id] += $event_type_duration_minutes / $event_values["total_mappings"] / $new_total_mappings;
                    }
                }
                $total_count += 1;
            }
            $event_data[$event_id] = array(
                "event_duration" => $event_duration_minutes,
                "duration" => $duration_minutes,
                "event_type_duration" => $duration_minutes_by_event_type,
                "mappings" => $total_count
            );
            $rows[] = array($objective_id_by_tag_set, $event_data);
        }
        return $rows;
    }

    protected function collateGroupedData(array $rows, array $join_with_rows)
    {
        $new_rows = array();
        foreach ($join_with_rows as $join_with_row) {
            list($join_with_objective_id_by_tag_set, $join_with_event_data) = $join_with_row;
            foreach ($rows as $row) {
                list($objective_id_by_tag_set, $event_data) = $row;
                $new_objective_id_by_tag_set = $objective_id_by_tag_set + $join_with_objective_id_by_tag_set;
                $new_event_data = array();
                $event_ids = array_intersect(array_keys($event_data), array_keys($join_with_event_data));
                foreach ($event_ids as $event_id) {
                    $event_values = $event_data[$event_id];
                    $join_with_event_values = $join_with_event_data[$event_id];
                    $event_duration_minutes = $event_values["event_duration"];
                    $new_total_count = $event_values["mappings"] * $join_with_event_values["mappings"];
                    $portion = $join_with_event_values["duration"] / $join_with_event_values["event_duration"];
                    $new_duration_minutes = $event_values["duration"] * $portion;
                    $new_duration_minutes_by_event_type = array();
                    $event_type_ids = array_intersect(array_keys($event_values["event_type_duration"]), array_keys($join_with_event_values["event_type_duration"]));
                    foreach ($event_type_ids as $event_type_id) {
                        $new_duration_minutes_by_event_type[$event_type_id] = $event_values["event_type_duration"][$event_type_id] * $portion;
                    }
                    $new_event_data[$event_id] = array(
                        "event_duration" => $event_duration_minutes,
                        "duration" => $new_duration_minutes,
                        "event_type_duration" => $new_duration_minutes_by_event_type,
                        "mappings" => $new_total_count,
                    );
                }
                $new_rows[] = array($new_objective_id_by_tag_set, $new_event_data);
            }
        }
        return $new_rows;
    }

    protected function aggregateEventValues(array $rows) {
        $new_rows = array();
        foreach ($rows as $row) {
            list($objective_id_by_tag_set, $event_data) = $row;
            $total_duration_minutes = 0.0;
            $total_count = 0;
            $total_duration_minutes_by_event_type = array();
            foreach ($event_data as $event_id => $event_values) {
                $total_duration_minutes += $event_values["duration"];
                foreach ($event_values["event_type_duration"] as $event_type_id => $event_type_duration_minutes) {
                    if (!isset($total_duration_minutes_by_event_type[$event_type_id])) {
                        $total_duration_minutes_by_event_type[$event_type_id] = 0.0;
                    }
                    $total_duration_minutes_by_event_type[$event_type_id] += $event_type_duration_minutes;
                }
                $total_count += $event_values["mappings"];
            }
            $values = array(
                "duration" => $total_duration_minutes,
                "event_type_duration" => $total_duration_minutes_by_event_type,
                "mappings" => $total_count);
            $new_rows[] = array($objective_id_by_tag_set, $values);
        }
        return $new_rows;
    }

    protected function valuesForObjectives($main_tag_set_id, array $group_by_tag_set_ids, $organisation_id, $course_id, array $cunit_ids, $start, $end, array $filter_objective_ids_by_tag_set)
    {
        $all_tag_set_ids = array_merge(array($main_tag_set_id), $group_by_tag_set_ids, array_keys($filter_objective_ids_by_tag_set));
        $used_tag_set_ids = array();
        $rows_by_tag_set = array();
        $version_ids = array_unique(array_merge(Models_Curriculum_Map_Versions::toIDs(Models_Repository_CurriculumMapVersions::getInstance()->fetchVersionsByDateRange($start, $end, $course_id))));
        if (empty($version_ids)) {
            $version_ids = array(null);
        }
        foreach ($all_tag_set_ids as $tag_set_id) {
            foreach ($version_ids as $version_id) {
                if (!isset($used_tag_set_ids[$tag_set_id])) {
                    $rest_of_tag_set_ids = array_diff($all_tag_set_ids, array($tag_set_id));
                    if (isset($filter_objective_ids_by_tag_set[$tag_set_id])) {
                        $filter_objective_id = $filter_objective_ids_by_tag_set[$tag_set_id];
                        $data = $this->groupEventsLinkedToObjectives($tag_set_id, array($filter_objective_id), $rest_of_tag_set_ids, $organisation_id, $version_id, $course_id, $cunit_ids, $start, $end);
                    } else {
                        $data = $this->groupEventsLinkedToObjectiveSet($tag_set_id, $rest_of_tag_set_ids, $organisation_id, $version_id, $course_id, $cunit_ids, $start, $end);
                    }
                    if (!empty($data)) {
                        $rows = $this->collectGroupedData($data);
                        foreach ($rows as $i => $row) {
                            list($objective_id_by_tag_set, $values) = $row;
                            foreach ($objective_id_by_tag_set as $group_by_tag_set_id => $group_by_objective_id) {
                                if (isset($filter_objective_ids_by_tag_set[$group_by_tag_set_id])) {
                                    $filter_objective_id = $filter_objective_ids_by_tag_set[$group_by_tag_set_id];
                                    if ($group_by_objective_id != $filter_objective_id) {
                                        unset($rows[$i]);
                                    }
                                }
                                $used_tag_set_ids[$group_by_tag_set_id] = $group_by_tag_set_id;
                                unset($rows_by_tag_set[$group_by_tag_set_id]);
                            }
                        }
                        if (isset($rows_by_tag_set[$tag_set_id])) {
                            $rows = array_merge($rows_by_tag_set[$tag_set_id], $rows);
                        }
                        $rows_by_tag_set[$tag_set_id] = $rows;
                    }
                }
            }
        }
        $joined_rows = array();
        foreach ($rows_by_tag_set as $tag_set_id => $rows) {
            if (!empty($joined_rows)) {
                $joined_rows = $this->collateGroupedData($joined_rows, $rows);
            } else {
                $joined_rows = $rows;
            }
        }
        $agg_rows = $this->aggregateEventValues($joined_rows);
        return $agg_rows;
    }

    protected function aggregateForTagSet($main_tag_set_id, array $rows, $value_by_values)
    {
        if (!is_callable($value_by_values)) {
            throw new InvalidArgumentException("Expected first parameter to be callable");
        }
        $objective_groups = array();
        foreach ($rows as $row) {
            list($objective_id_by_tag_set, $values) = $row;
            if (isset($objective_id_by_tag_set[$main_tag_set_id])) {
                $group_objectives = $objective_id_by_tag_set;
                unset($group_objectives[$main_tag_set_id]);
                if (!in_array($group_objectives, $objective_groups)) {
                    $objective_groups[] = $group_objectives;
                }
            }
        }
        $new_rows = array();
        foreach ($objective_groups as $group_objectives) {
            $values_by_objective = array();
            foreach ($rows as $row) {
                list($objective_id_by_tag_set, $values) = $row;
                if (isset($objective_id_by_tag_set[$main_tag_set_id])) {
                    $objective_id = $objective_id_by_tag_set[$main_tag_set_id];
                    $my_group_objectives = $objective_id_by_tag_set;
                    unset($my_group_objectives[$main_tag_set_id]);
                    if ($group_objectives == $my_group_objectives) {
                        $value = $value_by_values($values);
                        if (!isset($values_by_objective[$objective_id])) {
                            $values_by_objective[$objective_id] = $value;
                        } else {
                            if (is_array($value)) {
                                foreach ($value as $key => $val) {
                                    $values_by_objective[$objective_id][$key] += $val;
                                }
                            } else {
                                $values_by_objective[$objective_id] += $value;
                            }
                        }
                    }
                }
            }
            $new_rows[] = array($group_objectives, $values_by_objective);
        }
        return $new_rows;
    }

    public function durationsForObjectives($main_tag_set_id, array $group_by_tag_set_ids, $organisation_id, $course_id, array $cunit_ids, $start, $end, array $filter_objective_ids_by_tag_set)
    {
        $rows = $this->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $agg_rows = $this->aggregateForTagSet($main_tag_set_id, $rows, function ($values) { return $values["duration"]; });
        return $agg_rows;
    }

    public function mappingsForObjectives($main_tag_set_id, array $group_by_tag_set_ids, $organisation_id, $course_id, array $cunit_ids, $start, $end, array $filter_objective_ids_by_tag_set)
    {
        $rows = $this->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $agg_rows = $this->aggregateForTagSet($main_tag_set_id, $rows, function ($values) { return $values["mappings"]; });
        return $agg_rows;
    }

    protected function aggregateByEventTypes($agg_rows)
    {
        $rows_by_event_type = array();
        foreach ($agg_rows as $row) {
            list($objective_id_by_tag_set, $values_by_objective) = $row;
            $values_by_objective_by_event_type = array();
            foreach ($values_by_objective as $objective_id => $values_by_event_type) {
                foreach ($values_by_event_type as $event_type_id => $value) {
                    $values_by_objective_by_event_type[$event_type_id][$objective_id] = $value;
                }
            }
            foreach ($values_by_objective_by_event_type as $event_type_id => $new_values_by_objective) {
                $rows_by_event_type[$event_type_id][] = array($objective_id_by_tag_set, $new_values_by_objective);
            }
        }
        return $rows_by_event_type;
    }

    public function eventTypeDurationsForObjectives($main_tag_set_id, array $group_by_tag_set_ids, $organisation_id, $course_id, array $cunit_ids, $start, $end, array $filter_objective_ids_by_tag_set)
    {
        $rows = $this->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $agg_rows = $this->aggregateForTagSet($main_tag_set_id, $rows, function ($values) { return $values["event_type_duration"]; });
        $rows_by_event_type = $this->aggregateByEventTypes($agg_rows);
        return $rows_by_event_type;
    }

    public function eventTypeMappingsForObjectives($main_tag_set_id, array $group_by_tag_set_ids, $organisation_id, $course_id, array $cunit_ids, $start, $end, array $filter_objective_ids_by_tag_set)
    {
        $rows = $this->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $agg_rows = $this->aggregateForTagSet($main_tag_set_id, $rows, function ($values) {
            $mappings_by_event_type = array();
            foreach ($values["event_type_duration"] as $event_type_id => $event_type_duration_minutes) {
                $number_of_mappings = (float) $values["mappings"] * $event_type_duration_minutes / $values["duration"];
                $mappings_by_event_type[$event_type_id] = $number_of_mappings;
            }
            return $mappings_by_event_type;
        });
        $rows_by_event_type = $this->aggregateByEventTypes($agg_rows);
        return $rows_by_event_type;
    }

    protected function aggregateByTagSet($main_tag_set_id, array $rows) {
        $values_by_objective = array();
        foreach ($rows as $row) {
            list($objective_id_by_tag_set, $values) = $row;
            if (isset($objective_id_by_tag_set[$main_tag_set_id])) {
                $objective_id = $objective_id_by_tag_set[$main_tag_set_id];
                if (!isset($values_by_objective[$objective_id])) {
                    $values_by_objective[$objective_id] = $values;
                } else {
                    foreach ($values as $key => $value) {
                        $values_by_objective[$objective_id][$key] += $value;
                    }
                }
            }
        }
        return $values_by_objective;
    }

    public function valuesByObjectives($main_tag_set_id, $organisation_id, $course_id, array $cunit_ids, $start, $end, array $filter_objective_ids_by_tag_set)
    {
        $group_by_tag_set_ids = array();
        $rows = $this->valuesForObjectives($main_tag_set_id, $group_by_tag_set_ids, $organisation_id, $course_id, $cunit_ids, $start, $end, $filter_objective_ids_by_tag_set);
        $values_by_objective = $this->aggregateByTagSet($main_tag_set_id, $rows);
        return $values_by_objective;
    }
}
