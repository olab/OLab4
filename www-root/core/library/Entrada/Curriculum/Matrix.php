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
 * A class to handle the data requirements for the curriculum matrix.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Entrada_Curriculum_Matrix {
    
    public static function getCurriculumMatrixData($objective_id = 1, $depth = 1) {
        global $db, $ENTRADA_USER;
        $query = "SELECT a.*, b.`curriculum_type_name` FROM `courses` AS a
                    LEFT JOIN `curriculum_lu_types` AS b
                    ON a.`curriculum_type_id` = b.`curriculum_type_id`
                    WHERE (
                        a.`course_id` IN (
                            SELECT DISTINCT(`course_id`) FROM `course_objectives`
                            WHERE `active` = '1'
                        )
                        OR b.`curriculum_type_active` = '1'
                    )
                    AND a.`course_active` = 1
                    AND a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND a.`curriculum_type_id` > '0'
                    ORDER BY a.`curriculum_type_id` ASC, a.`course_code` ASC";
        $courses = $db->GetAll($query);
        if ($courses) {
            $last_term_name = "";
            $term_course_id = 0;
            $count = 0;
            $i = 0;
            foreach ($courses as $course) {
                $courses_array["courses"][$i]["objectives"] = array();
                $courses_array["courses"][$i]["course_id"] = $course["course_id"];
                $courses_array["courses"][$i]["course_name"] = $course["course_name"];
                $courses_array["courses"][$i]["course_code"] = $course["course_code"];
                $courses_array["courses"][$i]["term_name"] = (isset($course["curriculum_type_name"]) && $course["curriculum_type_name"] ? $course["curriculum_type_name"] : "Other courses");
                $i++;
            }
            $reorder_courses = $courses_array["courses"];
            $courses_array["courses"]       = array();
            $courses_array["objectives"]    = array();
            $courses_array["terms"]         = array();
            foreach ($reorder_courses as $course_id => $course) {
                if (!isset($courses_array["terms"][$course["term_name"]])) {
                    $courses_array["terms"][$course["term_name"]] = array();
                }
                if (isset($course["term_name"]) && $course["term_name"] != "Other courses") {
                    $courses_array["courses"][] = $course;
                    $courses_array["terms"][$course["term_name"]]++;
                }
            }
            foreach ($reorder_courses as $course_id => $course) {
                if (!isset($courses_array["terms"][$course["term_name"]])) {
                    $courses_array["terms"][$course["term_name"]] = array();
                }
                if (!isset($course["term_name"]) || $course["term_name"] == "Other courses") {
                    $courses_array["courses"][] = $course;
                    $courses_array["terms"][$course["term_name"]]++;
                }
            }
            foreach ($courses_array["courses"] as $course_id => &$course) {
                $course["new_term"] = ((isset($last_term_name) && $last_term_name && $last_term_name != $course["term_name"]) ? true : false);
                if ($last_term_name != $course["term_name"]) {
                    $last_term_name = (isset($course["term_name"]) && $course["term_name"] ? $course["term_name"] : "Other courses");
                    if ($term_course_id) {
                        $courses_array["courses"][$term_course_id]["total_in_term"] = $count;
                    }
                    $term_course_id = $course_id;
                    $count = 1;
                } else {
                    $count++;
                }
            }
            if ($term_course_id) {
                $courses_array["courses"][$term_course_id]["total_in_term"] = $count;
            }

            $objectives = self::fetchObjectives($objective_id, $depth);
            if ($objectives && count($objectives)) {
                foreach ($objectives as $objective) {
                    $has_children = false;
                    $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_parent` = ".$db->qstr($objective["objective_id"]);
                    $children = $db->GetAll($query);
                    if (isset($children) && is_array($children) && !empty($children)) {
                        $has_children = true;
                    }
                    $courses_array["objectives"][] = array("objective_name" => $objective["objective_name"], "objective_id" => $objective["objective_id"], "objective_description" => $objective["objective_description"], "has_children" => $has_children);
                    $objective_ids_string = objectives_build_objective_descendants_id_string($objective["objective_id"], $db->qstr($objective["objective_id"]));
                    if ($objective_ids_string) {
                        foreach ($courses_array["courses"] as $course_id => &$course) {
                            $query = "	SELECT IF(`objective_type` = 'course', `importance`, '*') AS `importance` FROM `course_objectives`
                                        WHERE `course_id` = ".$db->qstr($course["course_id"])."
                                        AND `objective_id` IN (".$objective_ids_string.")
                                        AND `active` = '1'";
                            $found = $db->GetRow($query);
                            $course["objectives"][] = array("objective_id" => $objective["objective_id"], "importance" => ($found ? $found["importance"] : false));
                        }
                    }
                } 
           }
        }
        return $courses_array;
    }
    
    public static function fetchObjectives($objective_id, $target_depth, $depth = 1) {
        global $db, $ENTRADA_USER;
        $query = "	SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE (a.`objective_parent` = ".$db->qstr($objective_id).")
                    AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND a.`objective_active` = '1'
                    ORDER BY a.`objective_order`";
        $results = $db->GetAll($query);
        
        if ($results && $depth < $target_depth) {
            $depth++;
            foreach ($results as $result) {
                $children = self::fetchObjectives($result["objective_id"], $target_depth, $depth);
                if (!empty($children)) {
                    $output[] = $children;
                }
            }

            if (!empty($output)) {
                $results = array();
                foreach ($output as $o) {
                    $results = array_merge($results, $o);
                }
            }
        }
        return $results;
    }
    
    public static function getMapping($course_id, $objective_id) {
        global $db, $ENTRADA_USER;
        
        $objective_ids_string = objectives_build_course_objectives_id_string($course_id);
		$competency_ids_string = objectives_build_objective_descendants_id_string($objective_id);
        
        if (!$competency_ids_string) {
            $bottom_level = true;
        }
        
        if (!stripos("'".$objective_id."', ", $competency_ids_string)) {
            $competency_ids_string = "'".$objective_id."'" . (strlen($competency_ids_string) > 0 ? ", " : "") .  $competency_ids_string;
        }

        $query = "	SELECT * FROM `course_objectives` AS a
					JOIN `global_lu_objectives` AS b
					ON a.`objective_id` = b.`objective_id`
					JOIN `objective_organisation` AS c
					ON b.`objective_id` = c.`objective_id`
					AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					WHERE a.`course_id` = ".$db->qstr($course_id)."
					AND (
						b.`objective_id` IN (".$competency_ids_string.")".
						"AND b.`objective_parent` NOT IN (".$objective_ids_string.")
					) 
					AND a.`objective_type` = 'course'
                    AND a.`active` = '1'
					AND b.`objective_active` = 1
                    GROUP BY a.`objective_id`
					ORDER BY a.`importance` ASC";
        $objectives = $db->GetAll($query);
		if ($objectives) {
			foreach ($objectives AS $objective) {
				$query = "	SELECT a.*, b.`objective_details` FROM `global_lu_objectives` AS a
							LEFT JOIN `course_objectives` AS b
							ON a.`objective_id` = b.`objective_id`
                            AND b.`course_id` = ".$db->qstr($course_id)."
							AND b.`objective_type` = 'course'
                            AND b.`active` = '1'
							JOIN `objective_organisation` AS c
							ON a.`objective_id` = c.`objective_id`
							AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							WHERE a.`objective_parent` = ".$db->qstr($objective["objective_id"])."
							AND a.`objective_active` = 1
							ORDER BY a.`objective_order` ASC";
				$child_objectives = $db->GetAll($query);
				if ($objective["importance"] == 1) {
					$primary[$objective["objective_id"]]["children"] = array();
				} elseif ($objective["importance"] == 2) {
					$secondary[$objective["objective_id"]]["children"] = array();
				} elseif ($objective["importance"] == 3) {
					$tertiary[$objective["objective_id"]]["children"] = array();
				}

				foreach ($child_objectives as $child_objective) {
					if ($objective["importance"] == 1) {
						$primary[$objective["objective_id"]]["children"][] = $child_objective;
					} elseif ($objective["importance"] == 2) {
						$secondary[$objective["objective_id"]]["children"][] = $child_objective;
					} elseif ($objective["importance"] == 3) {
						$tertiary[$objective["objective_id"]]["children"][] = $child_objective;
					}
				}

				if ($objective["importance"] == 1) {
					$primary[$objective["objective_id"]]["objective"] = $objective;
				} elseif ($objective["importance"] == 2) {
					$secondary[$objective["objective_id"]]["objective"] = $objective;
				} elseif ($objective["importance"] == 3) {
					$tertiary[$objective["objective_id"]]["objective"] = $objective;
				}
			}
		}
		
		$competency = $db->GetRow("SELECT a.* 
									FROM `global_lu_objectives` AS a
									JOIN `objective_organisation` AS b
									ON a.`objective_id` = b.`objective_id`
									AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
									WHERE a.`objective_id` = ".$db->qstr($objective_id)."
									AND a.`objective_active` = 1");

		$course = $db->GetRow("SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($course_id));
        $output = array();
		$output[] = "<h1>".html_encode($course["course_name"])."</h1>";
        if ($competency) {
            $output[] = "<h2>" . html_encode($competency["objective_name"]) . " Objectives</h2>\n";
        }

		if ($primary) {
			$output[] = "<h3>Primary Objectives</h3>\n";
			$output[] = "<ul>\n";
			foreach ($primary as $objective) {
				$output[] = "<li>\n<span title=\"View events in this course related to this objective.\">".$objective["objective"]["objective_name"]."</span><div class=\"content-small\">".(isset($objective["objective"]["objective_details"]) && $objective["objective"]["objective_details"] ? $objective["objective"]["objective_details"] : $objective["objective"]["objective_description"])."</div>\n";
				if (isset($objective["children"]) && count($objective["children"])) {
					$output[] = "<ul class=\"pad\">\n";
					foreach ($objective["children"] as $objective_child) {
						$output[] = "<li class=\"pad-top\"><span title=\"View events in this course related to this objective.\">".$objective_child["objective_name"]."</span><div class=\"content-small\">".(isset($objective_child["objective_details"]) && $objective_child["objective_details"] ? $objective_child["objective_details"] : $objective_child["objective_description"])."</div></li>\n";
					}
					$output[] = "</ul>\n";
				}
				$output[] = "</li>\n";
			}
			$output[] = "</ul>\n";
		}
		if ($secondary) {
			$output[] = "<h3>Secondary Objectives</h3>\n";
			$output[] = "<ul>\n";
			foreach ($secondary as $objective) {
				$output[] = "<li>\n<span title=\"View events in this course related to this objective.\">".$objective["objective"]["objective_name"]."</span><div class=\"content-small\">".(isset($objective["objective"]["objective_details"]) && $objective["objective"]["objective_details"] ? $objective["objective"]["objective_details"] : $objective["objective"]["objective_description"])."</div>\n";
				if (isset($objective["children"]) && count($objective["children"])) {
					$output[] = "<ul class=\"pad\">\n";
					foreach ($objective["children"] as $objective_child) {
						$output[] = "<li class=\"pad-top\"><span title=\"View events in this course related to this objective.\">".$objective_child["objective_name"]."</span><div class=\"content-small\">".(isset($objective_child["objective_details"]) && $objective_child["objective_details"] ? $objective_child["objective_details"] : $objective_child["objective_description"])."</div></li>\n";
					}
					$output[] = "</ul>\n";
				}
				$output[] = "</li>\n";
			}
			$output[] = "</ul>\n";
		}
		if ($tertiary) {
			$output[] = "<h3>Tertiary Objectives</h3>\n";
			$output[] = "<ul>\n";
			foreach ($tertiary as $objective) {
				$output[] = "<li>\n<span title=\"View events in this course related to this objective.\">".$objective["objective"]["objective_name"]."</span><div class=\"content-small\">".(isset($objective["objective"]["objective_details"]) && $objective["objective"]["objective_details"] ? $objective["objective"]["objective_details"] : $objective["objective"]["objective_description"])."</div>\n";
				if (isset($objective["children"]) && count($objective["children"])) {
					$output[] = "<ul class=\"pad\">\n";
					foreach ($objective["children"] as $objective_child) {
						$output[] = "<li class=\"pad-top\"><span title=\"View events in this course related to this objective.\">".$objective_child["objective_name"]."</span><div class=\"content-small\">".(isset($objective_child["objective_details"]) && $objective_child["objective_details"] ? $objective_child["objective_details"] : $objective_child["objective_description"])."</div></li>\n";
					}
					$output[] = "</ul>\n";
				}
				$output[] = "</li>\n";
			}
			$output[] = "</ul>\n";
		}
        return $output;
    }
    
}

?>
