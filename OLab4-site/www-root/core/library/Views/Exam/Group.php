<?php
/**
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */

class Views_Exam_Group extends Views_Deprecated_Base {
    /**
     * @var Models_Exam_Group
     */
    protected $group;

    protected $display_style;
    protected $exam_id;
    protected $exam_in_progress;

    /**
     * @param Models_Exam_Group $group
     */
    public function __construct(Models_Exam_Group $group) {
        $this->group = $group;
    }

    /**
     * @param Models_Exam_Question_Versions $question
     * @return array
     */
    protected function getQuestionControlArray(Models_Exam_Question_Versions $question) {
        $display_style      = $this->display_style;
        $related_versions   = $question->fetchAllRelatedVersions();
        $highest_version    = $question->checkHighestVersion(0);
        $exam_in_progress   = $this->exam_in_progress;

        if ($exam_in_progress === false) {
            $control_group["edit"] = "<a href=\"" . ENTRADA_URL . "/admin/exams/questions?section=edit-question&id=" . $question->getQuestionID() . "&version_id=" . $question->getVersionID() . "\" title=\"Edit Question\" class=\"btn flat-btn edit-question\"><i class=\"fa fa-pencil\"></i></a>";
        }

        if ($display_style !== "questions" && $exam_in_progress === false) {
            $control_group["delete"] = "<a href=\"#\" title=\"Remove Question from Group\" class=\"btn flat-btn delete-group-question\"><i class=\"fa fa-trash-o\"></i></a>";
        }
        if ($display_style === "list") {
            $control_group["view"] = "<a class=\"flat-btn btn question-preview\" title=\"View Question Preview\" href=\"#\"><i class=\"fa fa-search-plus\" data-version-id=\"" . $question->getVersionID() . "\"></i></a>";
        } else {
            $control_group["view"] = "<a href=\"#\" title=\"View Question Details\" class=\"btn flat-btn item-details\"><i class=\"fa fa-eye\"></i></a>";
        }

        if ($exam_in_progress === false) {
            $control_group["move"] = "<a href=\"#\" title=\"Move Question\" class=\"btn flat-btn move\"><i class=\"fa fa-arrows\"></i></a>";

            if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {
                $control_group["versions"] = "<a href=\"#\" title=\"Select Related Version\" class=\"flat-btn btn dropdown-toggle related-group-questions" . ($highest_version ? "" : " updated-question-available") . "\" data-toggle=\"dropdown\"><i class=\"related-question-icon fa fa-exchange\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i></a>";
            }

            if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {
                $control_group["related"] = Views_Exam_Question::renderRelatedBrowser($related_versions, "group");
            }
        }

        return array(
            $control_group
        );
    }

    /**
     * @param array $control_array
     * @return string
     */
    protected function buildControlGroup(array $control_array) {
        if (is_array($control_array) && !empty($control_array)) {
            foreach ($control_array as $control_group) {
                $html = "<div class=\"btn-group\">";
                foreach ($control_group as $control) {
                    $html .= $control;
                }
                $html .= "</div>";
            }
            return $html;
        } else {
            return "Error: Control Array was empty!";
        }
    }

    /**
     * @return string
     */
    public function renderGroupDescription() {
        $group_description = $this->group->getGroupDescription();
        if (isset($group_description) && $group_description != "") {
            $html = "<div class=\"exam-question exam-question-group\">
                        <table class=\"question-table\">
                            <tbody>
                            <tr class=\"heading\">
                                <td colspan=\"2\">
                                    <h3>" . $group_description . "</h3>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>";

            return $html;
        }
    }

    /**
     * @param Models_Exam_Group $group
     * @param bool|false $display_mode
     * @param array|NULL $control_array
     * @param array|NULL $data_attr_array
     * @param array|NULL $data_attr_element_array
     * @return string
     */
    public function renderGroup(Models_Exam_Group $group, $display_mode = false, array $control_array = NULL, array $data_attr_array = NULL, array $data_attr_element_array = NULL) {
        $group_id               = $group->getGroupID();
        $group_questions        = $group->getGroupQuestions();
        $question_display_style = "details";
        $display_style          = $this->display_style;
        $exam_id                = $this->exam_id;
        $html                   = "";
        switch ($display_style) {
            case "group":
                $html .= "<div class=\"exam-element exam-question exam-question-group\"";
                if (!empty($data_attr_array)) {
                    foreach ($data_attr_array as $key => $data_attr) {
                        $html .= " data-" . $key . "=\"" . $data_attr . "\"" ;
                    }
                }
                $html .= ">";
                $html .= "<table class=\"group-table question-group-table mc-h-m question-detail-container\" data-group-id=\"" . $group_id . "\">";
                if ($display_mode === false) {
                    $html .= "<tr class=\"type\">";
                    $html .= "    <td colspan=\"2\">";
                    $html .= "        <div class=\"group-info pull-left\"><span class=\"group-type\">Group ID: " . $group_id . "</span> " . $group->getGroupTitle() . "</div>";
                    $html .= "        <div class=\"group-edit-buttons pull-right\">";
                    $html .= "            <div class=\"btn-group\">";
                    $html .= "                <span class=\"btn flat-btn select-item select-group\"><i class=\"select-item-icon question-icon-select fa fa-4x fa-square-o\"></i></span>";
                    $html .= "            </div>";
                    $html .= "            <div class=\"btn-group\">";
                    $html .= "                <a href=\"" . ENTRADA_URL . "/admin/exams/groups?section=edit-group&group_id=" . $group_id . "&exam_id=" . $exam_id . "\" class=\"btn flat-btn edit-group\"><i class=\"fa fa-pencil\"></i></a>";
                    $html .= "                <a href=\"#\" class=\"btn flat-btn move\"><i class=\"fa fa-arrows\"></i></a>";
                    $html .= "            </div>";
                    $html .= "        </div>";
                    $html .= "    </td>";
                    $html .= "</tr>";
                }
                $html .= "<tr class=\"heading\">";
                $html .= "    <td colspan=\"2\">";
                $html .= "      " . $group->getGroupDescription();
                $html .= "    </td>";
                $html .= "</tr>";
                $html .= "<tr class=\"group-questions\">";
                $html .= "    <td colspan=\"2\">";
                $html .= "        <div class=\"exam-question-group-elements\">";
                
                break;
            case "questions":
                $html = "<div class=\"exam-element group-container\" id=\"question-detail-container\" data-group-id=\"" . $group_id . "\">";
                break;
            case "list":
                $question_display_style = "list";
                $colspan = 6;
                $html = "<tr id=\"group-row-" . $group_id ."\" class=\"exam-element question-row group\" data-group-id=\"" . $group_id . "\"";
                if (!empty($data_attr_array)) {
                    foreach ($data_attr_array as $key => $data_attr) {
                        $html .= " data-" . $key . "=\"" . $data_attr . "\"" ;
                    }
                }
                $html .= ">";
                $html .= "<td colspan=\"" . $colspan . "\" class=\"list-top-level\">";
                $html .= "<table class=\"group-table question-group-table\">";

                break;
            default:
                break;
        }

        if ($group_questions && is_array($group_questions) && !empty($group_questions)) {
            foreach ($group_questions as $group_question) {
                $question       = Models_Exam_Question_Versions::fetchRowByVersionID($group_question->getVersionID());
                $html          .= $this->renderSingleGroupQuestion($question, $display_mode, $control_array, $data_attr_element_array, $group_question, $question_display_style);
            }
        }

        switch ($display_style)  {
            case "group":
                $html .= "                </div>";
                $html .= "            </td>";
                $html .= "        </tr>";
                $html .= "    </table>";
                $html .= "</div>";
                break;
            case "questions":
                $html .= "</div>";
                break;
            case "list":
                $html .= "        </table>";
                $html .= "    </td>";
                $html .= "</tr>";
                break;

            default:
                break;
        }
                
        return $html;
    }

    public function renderSingleGroupQuestion(Models_Exam_Question_Versions $question, $display_mode = false, array $control_array = NULL, array $data_attr_element_array = NULL, Models_Exam_Group_Question $group_question, $question_display_style) {
        $question_view  = new Views_Exam_Question($question);
        $question_control_array = (null == $control_array) ? $this->getQuestionControlArray($question) : $control_array;
//        $select_control_html = array("<span class=\"flat-btn btn select-item select-question\"><i class=\"select-item-icon question-icon-select fa fa-2x fa-square-o\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i></span>");
//        array_unshift($question_control_array, $select_control_html);
        $question_data["element-id"]            = $data_attr_element_array[$group_question->getVersionID()];
        $question_data["sortable-element-id"]   = "element_" . $data_attr_element_array[$group_question->getVersionID()];
        $question_data["egquestion-id"]         = "question_" . $group_question->getID();
        $question_data["question-id"]           = $group_question->getQuestionID();
        $question_data["version-id"]            = $group_question->getVersionID();
        $question_data["version-count"]         = $group_question->getQuestionVersion()->getVersionCount();

        return $question_view->render($display_mode, $question_control_array, $question_data, $question_display_style, false);
    }

    /**
     * @param bool|false $display_mode
     * @param array|NULL $control_array
     * @param array|NULL $data_attr_array
     * @param array|NULL $data_attr_element_array
     * @param string $display_style
     * @return string
     */
    public function render($display_mode = false, array $control_array = NULL, array $data_attr_array = NULL, array $data_attr_element_array = NULL, $display_style = "group", $exam_id = false) {
        global $translate;
        $MODULE_TEXT            = $translate->_("exams");
        $this->display_style    = $display_style;
        $this->exam_id          = $exam_id;
        $this->exam_in_progress = false;
        $exam                   = Models_Exam_Exam::fetchRowByID($this->exam_id);
        if ($exam && is_object($exam)) {
            $posts = Models_Exam_Post::fetchAllByExamID($exam_id);
            if ($posts && is_array($posts) && !empty($posts)) {
                foreach ($posts as $post) {
                    $progress = Models_Exam_Progress::fetchAllStudentsByPostID($post->getID());

                    if ($progress && is_array($progress) && !empty($progress)) {
                        $this->exam_in_progress = true;
                        break;
                    }
                }
            }
        }

        if ($this->group !== null) {
            return $this->renderGroup($this->group, $display_mode, $control_array, $data_attr_array, $data_attr_element_array);
        } else {
            echo display_notice($MODULE_TEXT["groups"]["group"]["no_available_questions"]);
        }
    }
}