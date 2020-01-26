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

class Entrada_Curriculum_Export {

    public function fetchRelatedObjectivesForEvents(array &$events, array $tag_set_ids)
    {
        $event_ids = array_map(function ($event) { return (int) $event["event_id"]; }, $events);
        $objectives_by_event_tag_set = array();
        if (!empty($event_ids) && !empty($tag_set_ids)) {
            $used_tag_set_ids = array();
            $version_repository = Models_Repository_CurriculumMapVersions::getInstance();
            $versions_by_event = Models_Curriculum_Map_Versions::toIDs($version_repository->fetchLatestVersionsByEventIDs($event_ids));
            list($objective_ids, $objective_ids_by_event, $objective_parent_ids_by_event) = $this->fetchEventObjectives($objectives_by_event_tag_set, $used_tag_set_ids, $event_ids, $tag_set_ids);
            $objective_links = array();
            while (!empty($objective_ids) && (count($used_tag_set_ids) < count($tag_set_ids))) {
                list($objective_ids, $objective_ids_by_event, $objective_parent_ids_by_event) = $this->fetchLinkedObjectives($objectives_by_event_tag_set, $used_tag_set_ids, $objective_links, $objective_ids, $objective_ids_by_event, $objective_parent_ids_by_event, $versions_by_event, $tag_set_ids);
            }
        }
        foreach ($objectives_by_event_tag_set as $event_id => $objectives_by_tag_set) {
            foreach ($events as &$event) {
                if ($event["event_id"] == $event_id) {
                    $event["objectives"] = $objectives_by_tag_set;
                    break;
                }
            }
        }
    }

    private function fetchEventObjectives(array &$objectives_by_event_tag_set, array &$used_tag_set_ids, array $event_ids, array $tag_set_ids) {
        $objectives_by_event = Models_Repository_Objectives::getInstance()->fetchAllByEventIDs($event_ids);
        $objective_ids = array();
        $objective_ids_by_event = array();
        $objective_parent_ids_by_event = array();
        foreach ($objectives_by_event as $event_id => $objectives) {
            foreach ($objectives as $objective_id => $objective) {
                $objective_parent_id = $objective->getParent();
                $objective_ids[$objective_id] = $objective_id;
                $objective_ids_by_event[$event_id][$objective_id] = $objective_id;
                $objective_parent_ids_by_event[$event_id][$objective_id] = $objective_parent_id;
                if (in_array($objective_parent_id, $tag_set_ids)) {
                    $used_tag_set_ids[$objective_parent_id] = $objective_parent_id;
                    $objectives_by_event_tag_set[$event_id][$objective_parent_id][$objective_id] = $objective->toArray();
                    $objectives_by_event_tag_set[$event_id][$objective_parent_id][$objective_id]["event_id"] = $event_id;
                }
            }
        }
        return array($objective_ids, $objective_ids_by_event, $objective_parent_ids_by_event);
    }

    private function fetchLinkedObjectives(array &$objectives_by_event_tag_set, array &$used_tag_set_ids, array &$objective_links, array $objective_ids, array $objective_ids_by_event, $objective_parent_ids_by_event, array $versions_by_event, array $tag_set_ids)
    {
        $new_objective_ids = array();
        $new_objective_ids_by_event = array();
        $new_objective_parent_ids_by_event = array();
        $linked_objectives_by_version = array_reduce(
            $this->getVersionIDs($versions_by_event),
            function (array $linked_objectives_by_version, $version_id) use ($objective_ids, $objective_ids_by_event) {
                $objective_repository = Models_Repository_Objectives::getInstance();
                $next_linked_objectives_by_version = $objective_repository->fetchLinkedObjectivesByIDsAndEvents("from", array_values($objective_ids), $version_id, array_keys($objective_ids_by_event));
                return $next_linked_objectives_by_version + $linked_objectives_by_version;
            }, array());
        foreach ($linked_objectives_by_version as $version_id => $linked_objectives_by_event) {
            if (isset($linked_objectives_by_event[null])) {
                foreach (array_keys($linked_objectives_by_event[null]) as $from_objective_id) {
                    foreach (array_keys($objective_ids_by_event) as $event_id) {
                        if (isset($objective_ids_by_event[$event_id][$from_objective_id])) {
                            $linked_objectives_by_event[$event_id][$from_objective_id] = $linked_objectives_by_event[null][$from_objective_id];
                        }
                    }
                }
                unset($linked_objectives_by_event[null]);
            }
            foreach ($linked_objectives_by_event as $event_id => $objectives_by_from_objective) {
                foreach ($objectives_by_from_objective as $from_objective_id => $objectives) {
                    foreach ($objectives as $objective_id => $objective) {
                        if ($versions_by_event[$event_id] == $version_id && isset($objective_parent_ids_by_event[$event_id][$from_objective_id])) {
                            $objective_parent_id = $objective->getParent();
                            $new_objective_ids[$objective_id] = $objective_id;
                            $new_objective_ids_by_event[$event_id][$objective_id] = $objective_id;
                            $new_objective_parent_ids_by_event[$event_id][$objective_id] = $objective_parent_id;
                            $from_parent_id = $objective_parent_ids_by_event[$event_id][$from_objective_id];
                            if (isset($objective_links[$version_id][$from_objective_id])) {
                                if (!isset($objective_links[$version_id][$objective_id])) {
                                    $objective_links[$version_id][$objective_id] = $objective_links[$version_id][$from_objective_id];
                                } else {
                                    foreach ($objective_links[$version_id][$from_objective_id] as $from_from_parent_id => $from_from_objective_ids) {
                                        foreach ($from_from_objective_ids as $from_from_objective_id) {
                                            $objective_links[$version_id][$objective_id][$from_from_parent_id][$from_from_objective_id] = $from_from_objective_id;
                                        }
                                    }
                                }
                            }
                            $objective_links[$version_id][$objective_id][$from_parent_id][$from_objective_id] = $from_objective_id;
                            if (in_array($objective_parent_id, $tag_set_ids)) {
                                $used_tag_set_ids[$objective_parent_id] = $objective_parent_id;
                                $result = $objective->toArray();
                                $result["version_id"] = $version_id;
                                $result["from_objective_id"] = $from_objective_id;
                                $result["linked_objectives"] = $objective_links[$version_id][$objective_id];
                                $objectives_by_event_tag_set[$event_id][$objective_parent_id][$objective_id] = $result;
                            }
                        }
                    }
                }
            }
        }
        return array($new_objective_ids, $new_objective_ids_by_event, $new_objective_parent_ids_by_event);
    }

    private function getVersionIDs($versions_by_event) {
        $version_ids = array_unique(array_values($versions_by_event));
        if ($version_ids) {
            return $version_ids;
        } else {
            return array(null);
        }
    }

    /**
     * Convert search results to rows that can be put in a CSV file.
     * @param array $results
     * @param array $tag_set_ids
     * @param array $tag_sets
     * @return array $rows
     */
    public function toRows(array $results, array $tag_set_ids, array $tag_sets, $get_objective_text, $group_by_event = false) {
    	$default_column_count = 9;
	    $activity_objectives_index = null;

        if (!$group_by_event) {
            $headings = array(
                "course_name",
                "event_id",
                "event_title",
                "url",
                "course_unit",
                "event_date",
                "event_duration",
                "event_description",
                "event_type",
                "duration",
                "teachers"
            );
        } else {
            $headings = array(
                "course_name",
                "event_id",
                "event_title",
                "url",
                "course_unit",
                "event_date",
                "event_duration",
                "event_description",
                "event_type",
                "teachers"
            );
        }

        $headings_index = sizeof($headings);

        foreach ($tag_set_ids as $tag_set_id) {
            $objective_name = $tag_sets[$tag_set_id]["objective_name"];

            if (strtolower($objective_name) == 'activity objectives') {
        		$activity_objectives_index = $headings_index;
	        }

	        $headings[] = $objective_name;
	        $headings_index++;
        }

        $rows = array($headings);
        if (!empty($results)) {
            foreach ($results as $result) {
                if (!$group_by_event) {
                    foreach ($result["event_types"] as $event_type) {
                        $row = array(
                            $result["course_code"]." ".$result["course_name"],
                            $result["event_id"],
                            $result["event_title"],
                            ENTRADA_URL . "/events?id=" . $result["event_id"],
                            $result["course_unit"],
                            date(DEFAULT_DATETIME_FORMAT, $result["event_start"]),
                            $result["event_duration"],
                            strip_tags($result["event_description"]),
                            $event_type["eventtype_title"],
                            $event_type["duration"]
                        );

                        $row = $this->addTeachersToRow($row, $result);

                        if (!empty($result["objectives"])) {
                            $linkages = $this->traverseObjectivesByTagSet($result["objectives"]);
                            foreach ($linkages as $objectives) {
                                $new_row = $row;
                                $new_row[$default_column_count] = round((float)$new_row[$default_column_count] / count($linkages), 2);

                                foreach ($tag_set_ids as $tag_set_id) {
                                    if (isset($objectives[$tag_set_id])) {
                                        $objective = $objectives[$tag_set_id];
                                        $new_row[] = $get_objective_text($objective);
                                    } else {
                                        $new_row[] = "";
                                    }
                                }
                                $rows[] = $new_row;
                            }
                        } else {
                            foreach ($tag_set_ids as $tag_set_id) {
                                $row[] = "";
                            }
                            $rows[] = $row;
                        }
                    }
                } else {
                    // $group_by_event is true
                    $event_type_titles = array();

                    foreach ($result["event_types"] as $event_type) {
	                    $event_type_titles[] = $event_type["eventtype_title"];
                    }

                    $row = array(
                        $result["course_code"]." ".$result["course_name"],
                        $result["event_id"],
                        $result["event_title"],
                        ENTRADA_URL . "/events?id=" . $result["event_id"],
                        $result["course_unit"],
                        date(DEFAULT_DATETIME_FORMAT, $result["event_start"]),
                        $result["event_duration"],
                        strip_tags($result["event_description"]),
                        implode('; ', $event_type_titles)
                    );

                    $row = $this->addTeachersToRow($row, $result);

					if (!empty($result["objectives"])) {
						$linkages = $this->traverseObjectivesByTagSet($result["objectives"]);
						$new_row = $row;
						$activity_index = 1;

						foreach ($linkages as $objectives) {
							$tag_set_idx = 1;

							foreach ($tag_set_ids as $tag_set_id) {
								if (!isset($new_row[$default_column_count + $tag_set_idx])) {
									$new_row[$default_column_count + $tag_set_idx] = '';
								}

								if (isset($objectives[$tag_set_id])) {
									$objective = $objectives[$tag_set_id];
									$objective_text = $get_objective_text($objective);

									if (($default_column_count + $tag_set_idx) == $activity_objectives_index) {
										$tag_prefix = "$activity_index. ";
										$tag_prefix_separator = "\n";
									} else {
										$tag_prefix = "";
										$tag_prefix_separator = "; ";
									}

									if ($new_row[$default_column_count + $tag_set_idx] && (strpos($new_row[$default_column_count + $tag_set_idx], $objective_text) === FALSE)) {
										$new_row[$default_column_count + $tag_set_idx] .= $tag_prefix_separator . $tag_prefix . $objective_text;

										if (($default_column_count + $tag_set_idx) == $activity_objectives_index) {
											$activity_index++;
										}
									} elseif (!$new_row[$default_column_count + $tag_set_idx]) {
										// only clobber it if it's empty
										$new_row[$default_column_count + $tag_set_idx] = $tag_prefix . $objective_text;

										if (($default_column_count + $tag_set_idx) == $activity_objectives_index) {
											$activity_index++;
										}
									}
								}

								$tag_set_idx++;
							}
						}

						$new_row[$activity_objectives_index] .= "\r\n";
						$rows[] = $new_row;
					} else {
						foreach ($tag_set_ids as $tag_set_id) {
							$row[] = "";
						}

						$rows[] = $row;
					}
				}
            }
        }

        return $rows;
    }

    private function addTeachersToRow($row, $result)
    {
        if (isset($result["contacts"]["teacher"])) {
            $row[] = implode(", ", array_map(function ($teacher) {
                return $teacher["firstname"] . " " . $teacher["lastname"];
            }, $result["contacts"]["teacher"]));
        } else {
            $row[] = "";
        }

        return $row;
    }

    private function traverseObjectivesByTagSet(array $objectives_by_tag_set, array $objective_links = array())
    {
        if (empty($objective_links)) {
            $objective_links = $this->objectiveLinksByTagSet($objectives_by_tag_set);
        }
        if (!empty($objectives_by_tag_set)) {
            $tag_set_id = key($objectives_by_tag_set);
            $objectives = current($objectives_by_tag_set);
            $rest_of_objectives_by_tag_set = $objectives_by_tag_set;
            unset($rest_of_objectives_by_tag_set[$tag_set_id]);
            if (!empty($rest_of_objectives_by_tag_set)) {
                $this->putLinkedTagSetFirst($rest_of_objectives_by_tag_set, $tag_set_id, $objective_links);
                $rest_of_linkages = $this->traverseObjectivesByTagSet($rest_of_objectives_by_tag_set, $objective_links);
                $linkages = array();
                $undealtwith_linkages = $rest_of_linkages;
                foreach ($objectives as $objective_id => $objective) {
                    foreach ($rest_of_linkages as $linkage_index => $linkage) {
                        $is_linked_to_some_objective = false;
                        $is_linked_to_different_objective = false;
                        foreach ($linkage as $other_tag_set_id => $other_objective) {
                            $other_objective_id = $other_objective["objective_id"];
                            if (isset($objective_links[$other_objective_id][$tag_set_id][$objective_id])) {
                                $is_linked_to_some_objective = true;
                            }
                        }
                        if ($is_linked_to_some_objective) {
                            $new_linkage = $linkage;
                            $new_linkage[$tag_set_id] = $objective;
                            $linkages[] = $new_linkage;
                            unset($undealtwith_linkages[$linkage_index]);
                        }
                    }
                    if (!isset($new_linkage)) {
                        $new_linkage = array($tag_set_id => $objective);
                        $linkages[] = $new_linkage;
                    }
                    unset($new_linkage);
                }
                $linkages = array_merge($undealtwith_linkages, $linkages);
                return $linkages;
            } else {
                $linkages = array();
                foreach ($objectives as $objective_id => $objective) {
                    $linkages[] = array($tag_set_id => $objective);
                }
                return $linkages;
            }
        } else {
            return array();
        }
    }

    private function putLinkedTagSetFirst(&$rest_of_objectives_by_tag_set, $tag_set_id, $objective_links)
    {
        $linked_tag_set_id = null;
        foreach ($rest_of_objectives_by_tag_set as $other_tag_set_id => $other_objectives) {
            $is_linked_to_some_objective = false;
            foreach ($other_objectives as $other_objective_id => $other_objective) {
                if (isset($objective_links[$other_objective_id][$tag_set_id])) {
                    $is_linked_to_some_objective = true;
                    break;
                }
            }
            if ($is_linked_to_some_objective) {
                $linked_tag_set_id = $other_tag_set_id;
                $linked_objectives = $other_objectives;
                break;
            }
        }
        if (isset($linked_tag_set_id)) {
            unset($rest_of_objectives_by_tag_set[$linked_tag_set_id]);
            $rest_of_objectives_by_tag_set = array($linked_tag_set_id => $linked_objectives) + $rest_of_objectives_by_tag_set;
        }
    }

    private function objectiveLinksByTagSet(array $objectives_by_tag_set) {
        $objective_links = array();
        foreach ($objectives_by_tag_set as $tag_set_id => $objectives) {
            foreach ($objectives as $objective_id => $objective) {
                $rest_of_objectives_by_tag_set = $objectives_by_tag_set;
                if (isset($objective["linked_objectives"])) {
                    foreach ($objective["linked_objectives"] as $to_tag_set_id => $linked_objectives) {
                        foreach ($linked_objectives as $to_objective_id) {
                            $objective_links[$to_objective_id][$tag_set_id][$objective_id] = $objective_id;
                            $objective_links[$objective_id][$to_tag_set_id][$to_objective_id] = $to_objective_id;
                        }
                    }
                }
            }
            foreach ($objectives as $objective_id => $objective) {
                if (isset($objective_links[$objective_id])) {
                    foreach ($objective_links[$objective_id] as $to_tag_set_id => $to_objective_ids) {
                        foreach ($to_objective_ids as $to_objective_id) {
                            if (!isset($objective_links[$to_objective_id][$tag_set_id][$objective_id])) {
                                throw new Exception("Expected back link");
                            }
                        }
                    }
                }
            }
        }
        return $objective_links;
    }
}
