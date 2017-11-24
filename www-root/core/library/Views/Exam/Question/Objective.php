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
 *
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Question_Objective extends Views_Deprecated_Base {
    protected $default_fieldset = array("qobjective_id", "question_id", "objective_id", "created_date", "created_by", "updated_date", "updated_by", "active", "deleted_date");

    protected $table_name           = "exam_question_objectives";
    protected $primary_key          = "qobjective_id";
    protected $default_sort_column  = "`exam_question_objectives`.`qobjective_id`";
    protected $joinable_tables      = array(
        "global_lu_objectives" => array(
            "fields" => array(
                "objective_id" => "objective_id",
                "objective_code" => "objective_code",
                "objective_name" => "objective_name",
                "objective_description" => "objective_description",
                "objective_parent" => "objective_parent",
                "objective_order" => "objective_order",
                "overall_order" => "overall_order",
            ),
            "join_conditions" => "`exam_question_objectives`.`objective_id` = `global_lu_objectives`.`objective_id`",
            "left" => false
        )
    );
    
    public function fetchQuestionObjectives($question_id) {
        $query = "SELECT * FROM `global_lu_objectives` AS a";

        $fieldset[] = "objective_name";
        $fieldset[] = "objective_id";
        $fieldset[] = "objective_description";
        $this->setFields($fieldset);
        $constraints = array(
            array("key" => "`".DATABASE_NAME."`.`exam_question_objectives`.`question_id`", "value" => $question_id),
            array("key" => "`".DATABASE_NAME."`.`global_lu_objectives`.`objective_active`", "value" => "1"),
            array("key" => "deleted_date", "value" =>  NULL, "method" => "IS")
        );

        return $this->fetchAll($constraints);
    }

    public function fetchTopLevelQuestionObjectives($question_id) {
        $fieldset[] = "objective_code";
        $fieldset[] = "objective_name";
        $fieldset[] = "objective_description";
        $fieldset[] = "objective_parent";
        $fieldset[] = "objective_order";
        $fieldset[] = "overall_order";
        $this->setFields($fieldset);
        $constraints = array(
            array("key" => "`".DATABASE_NAME."`.`exam_question_objectives`.`question_id`", "value" => $question_id),
            array("key" => "`".DATABASE_NAME."`.`global_lu_objectives`.`objective_parent`", "value" => "0"),
            array("key" => "`".DATABASE_NAME."`.`global_lu_objectives`.`objective_active`", "value" => "1"),
        );

        return $this->fetchAll($constraints);
    }

    public function fetchChildObjectives($question_id, $parent_id) {
        $fieldset[] = "objective_name";
        $this->setFields($fieldset);
        $constraints = array(
            array("key" => "`".DATABASE_NAME."`.`exam_question_objectives`.`question_id`", "value" => $question_id),
            array("key" => "`".DATABASE_NAME."`.`global_lu_objectives`.`objective_parent`", "mode" => "=", "value" => $parent_id)
        );

        return $this->fetchAll($constraints);
    }

    public static function renderObjectives($question_id = null, $details = false) {
        $self = new self();
        $html = "";
        $objectives = $self->fetchQuestionObjectives($question_id);
        if ($objectives) {
            $html .= "<ul class=\"objective-list\">";
            foreach ($objectives as $key => $objective) {
                if ($details == true) {
                    $html .= "<li class=\"mapped-objective\">";
                    $html .= "<strong class=\"objective-title\">" . html_encode($objective["objective_name"]) . "</strong>\n";
                    $html .= "<div class=\"objective-description\">\n";
                    $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                    if ($set) {
                        $html .= "From the Curriculum Tag Set: <strong>" . $set["objective_name"] . "</strong><br/>\n";
                    }
                    $html .= $objective["objective_description"] . "\n";
                    $html .= "</div>\n";
                    $html .= "</li>";
                } else {
                    $html .= "  <li>". $objective["objective_name"] ."</li>";
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }

    public static function renderObjectiveControls($organisation_id, $question_identifier = NULL, $objective_ids = array()) {
        global $db, $translate;

        $self = new self();
        $html = "";

        $objective_ids_string = "";
        if ($objective_ids && count($objective_ids)) {
            foreach ($objective_ids as $objective_id) {
                $objective_ids_string .= ($objective_ids_string ? ", " : "").$db->qstr(((int)$objective_id));
            }
        }
        $objectives = Models_Objective::fetchAllByParentID($organisation_id, 0);
        if ($objectives) {
            $tagged_objectives      = Views_Exam_Question_Objective::renderTaggedObjectivesList($objective_ids_string);
            $objective_name         = $translate->_("events_filter_controls");
            $hierarchical_name      = $objective_name["co"]["global_lu_objectives_name"];
            $nonhierarchical_name   = $objective_name["cp"]["global_lu_objectives_name"];
            $html .= "<a name=\"exam-question-objectives-section\"></a>\n";
            $html .= "<h2 title=\"Exam Question Objectives Section\">Curriculum Tags</h2>\n";
            $html .= "<div id=\"exam-objectives-section\">\n";
            $html .= "    <div class=\"objectives half left\">\n";
            $html .= "        <h3>Curriculum Tag Sets</h3>\n";
            $html .= "        <ul class=\"tl-objective-list\" id=\"objective_list_0\">\n";
            foreach($objectives as $objective) {
                $objective = $objective->toArray();
                $title = ($objective["objective_code"] ? $objective["objective_code"].': '.$objective["objective_name"] : $objective["objective_name"]);
                $html .= "          <li class=\"objective-container objective-set\"
                                        id=\"objective_".$objective["objective_id"]."\"
                                        data-list=\"".(((!isset($hierarchical_name) || !$hierarchical_name) && (!isset($nonhierarchical_name) || !$nonhierarchical_name || $nonhierarchical_name != $objective["objective_name"])) || $objective["objective_name"] == $hierarchical_name ? 'hierarchical' : 'flat')."\"
                                        data-id=\"".$objective["objective_id"]."\">\n";
                $html .= "              <div class=\"objective-title\"
                                            id=\"objective_title_".$objective["objective_id"]."\"
                                            data-title=\"".$title."\"
                                            data-id=\"".$objective["objective_id"]."\"
                                            data-code=\"".$objective["objective_code"]."\"
                                            data-name=\"".$objective["objective_name"]."\"
                                            data-description=\"".$objective["objective_description"]."\">\n";
                $html .= "                  <h4>".html_encode($title)."</h4>\n";
                $html .= "              </div>\n";
                $html .= "              <div class=\"objective-controls\" id=\"objective_controls_".$objective["objective_id"]."\">\n";
                $html .= "              </div>\n";
                $html .= "              <div class=\"objective-children\" id=\"children_".$objective["objective_id"]."\">\n";
                $html .= "                  <ul class=\"objective-list\" id=\"objective_list_".$objective["objective_id"]."\">\n";
                $html .= "                  </ul>\n";
                $html .= "              </div>\n";
                $html .= "          </li>\n";
            }
            $html .= "        </ul>\n";
            $html .= "    </div>\n";
            $query = "SELECT a.*, COUNT(b.`objective_id`) AS `mapped` FROM `global_lu_objectives` AS a
                LEFT JOIN `global_lu_objectives` AS b
                ON a.`objective_id` = b.`objective_id`
                WHERE a.`objective_active` = '1'
                ".(isset($objective_ids_string) && $objective_ids_string ? "AND b.`objective_id` IN (".$objective_ids_string.")" : "AND b.`objective_id` IS NULL")."
                GROUP BY a.`objective_id`
                ORDER BY a.`objective_id` ASC";
            $mapped_objectives = $db->GetAll($query);
            $explicit_exam_question_objectives = false;//array();
            $mapped_exam_question_objectives = array();
            if ($mapped_objectives) {
                foreach ($mapped_objectives as $objective) {
                    if ($objective["mapped"]) {
                        $explicit_exam_question_objectives[] = $objective;
                        $mapped_exam_question_objectives[] = $objective;
                    }
                }
            }
            $html .= "    <style type=\"text/css\">\n";
            $html .= "        .mapped-objective{\n";
            $html .= "            padding-left: 30px!important;\n";
            $html .= "        }\n";
            $html .= "    </style>\n";
            $html .= "    <div class=\"mapped_objectives right droppable\" id=\"mapped_objectives\" data-resource-type=\"exam_question\" data-resource-id=\"".$question_identifier."\">\n";
            $html .= "        <h3>Mapped Curriculum Tags</h3>\n";
            $html .= "        <p class=\"well well-small content-small\" id=\"alternate_objective_notice\" style=\"display: none;\">\n";
            $html .= "            <strong>Helpful Tip:</strong> Select a curriculum tag set from the list on the left and it will expand to show objectives from that set. Then, click a curriculum tag from that list to further expand, or if it has no children, it will be mapped to the exam question. Alternatively, click the checkbox to the right of a curriculum tag at any level to map it.\n";
            $html .= "        </p>\n";
            $html .= $tagged_objectives;
            $html .= "        <select id=\"checked_objectives_select\" name=\"checked_objectives[]\" multiple=\"multiple\" style=\"display:none;\">\n";
            if ($mapped_exam_question_objectives) {
                foreach($mapped_exam_question_objectives as $objective){
                    if(in_array($objective["objective_type"], array("curricular_objective","course"))) {
                        $title = ( $objective["objective_code"] ? $objective["objective_code"].": ".$objective["objective_name"] : $objective["objective_name"]);
                        $html .= "            <option value = \"".((int)$objective["objective_id"])."\" selected=\"selected\">".html_encode($title)."</option>\n";
                    }
                }
            }
            $html .= "        </select>\n";
            $html .= "        <select id=\"clinical_objectives_select\" name=\"clinical_presentations[]\" multiple=\"multiple\" style=\"display:none;\">\n";
            if ($mapped_exam_question_objectives) {
                foreach($mapped_exam_question_objectives as $objective){
                    if(in_array($objective["objective_type"], array("clinical_presentation","event"))) {
                        $title = ( $objective["objective_code"] ? $objective["objective_code"].": ".$objective["objective_name"] : $objective["objective_name"] );
                        $html .= "            <option value = \"".((int)$objective["objective_id"])."\" selected=\"selected\">".html_encode($title)."</option>\n";
                    }
                }
            }
            $html .= "        </select>\n";
            $html .= "    </div>\n";
            $html .= "</div>\n";
            $html .= "<input type=\"hidden\" id=\"qrow\" value=\"".((int)$question_identifier)."\" />\n";
        }
        return $html;
    }

    public static function renderTaggedObjectivesList($objective_ids_string) {
        global $db;

        $query = "SELECT a.*, COUNT(b.`objective_id`) AS `mapped` FROM `global_lu_objectives` AS a
                LEFT JOIN `global_lu_objectives` AS b
                ON a.`objective_id` = b.`objective_id`
                WHERE a.`objective_active` = '1'
                ".(isset($objective_ids_string) && $objective_ids_string ? "AND b.`objective_id` IN (".$objective_ids_string.")" : "AND b.`objective_id` IS NULL")."
                GROUP BY a.`objective_id`
                ORDER BY a.`objective_id` ASC";
        $mapped_objectives = $db->GetAll($query);
        $explicit_exam_question_objectives = false;//array();
        $mapped_exam_question_objectives = array();
        if ($mapped_objectives) {
            foreach ($mapped_objectives as $objective) {
                if ($objective["mapped"]) {
                    $explicit_exam_question_objectives[] = $objective;
                    $mapped_exam_question_objectives[] = $objective;
                }
            }
        }

        $html = "";
        $html .= "        <div id=\"exam-question-list-wrapper\">\n";
        $html .= "            <a name=\"exam-question-objective-list\"></a>\n";
        $html .= "            <h2 id=\"exam-question-toggle\"  title=\"exam question Objective List\" class=\"list-heading nocollapse\">Curriculum Tags</h2>\n";
        $html .= "            <div id=\"exam-question-objective-list\">\n";
        $html .= "                <ul class=\"objective-list mapped-list mapped_exam_question_objectives\" data-importance=\"exam-question\">\n";
        if ($explicit_exam_question_objectives) {
            foreach($explicit_exam_question_objectives as $objective){
                $title = ( $objective["objective_code"] ? $objective["objective_code"].": ".$objective["objective_name"] : $objective["objective_name"] );
                $html .= "                    <li class=\"mapped-objective mapped_objective_".$objective["objective_id"]."\" data-id=\"".$objective["objective_id"]."\" data-title=\"".html_encode($title)."\" data-description=\"".htmlentities($objective["objective_description"])."\" data-mapped=\"".($objective["mapped_to_course"] ? 1 : 0)."\">\n";
                $html .= "                        <div class=\"exam-question-objective-controls\">\n";
                $html .= "                            <i class=\"icon-remove-sign pull-right objective-remove list-cancel-image\" id=\"objective_remove_".$objective["objective_id"]."\" data-id=\"".$objective["objective_id"]."\"></i>\n";
                $html .= "                        </div>\n";
                $html .= "                        <strong>".html_encode($title)."</strong>\n";
                $html .= "                        <div class=\"objective-description\">\n";
                $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                if ($set) {
                    $html .= "                            From the Curriculum Tag Set: <strong>".$set["objective_name"]."</strong><br/>\n";
                }
                $html .= "                            ".$objective["objective_description"]."\n";
                $html .= "                        </div>\n";
                $html .= "                    </li>\n";
            }
        }
        $html .= "                </ul>\n";
        $html .= "                <div class=\"objectives-empty-notice\"".(!isset($explicit_exam_question_objectives) || !$explicit_exam_question_objectives ? "" : " style=\"display: none;\"").">";
        $html .= "                    ".display_notice("No objectives currently linked to this question.");
        $html .= "                </div>";
        $html .= "            </div>\n";
        $html .= "        </div>\n";

        return $html;
    }

}