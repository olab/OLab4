<?php
/**
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Exam_Element extends Views_Deprecated_Base {
    protected $element, $highlight, $display_style;

    public function __construct(Models_Exam_Exam_Element $element) {
        $this->element = $element;
    }

    private function buildHeader(
        $exam_mode = false,
        array $control_array = NULL
    ) {
        $html = NULL;
        if (is_null($control_array)) {
            $html .= "      <span class=\"flat-btn btn select-item select-question\">";
            $html .= "          <i class=\"icon-select-question fa fa-2x fa-square-o\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i>";
            $html .= "      </span>";
            $html .= "      <div class=\"flat-btn btn-group\">";
            if ($edit === true) {
                $html .= "          <a href=\"" . ENTRADA_URL . "/admin/exams/questions?section=edit-question&id=" . $question->getQuestionID() . "&version_id=" . $question->getVersionID() . "\" title=\"Edit Question\" class=\"btn edit-question\"><i class=\"icon-pencil\"></i></a>";
            }
            $html .= "          <a href=\"#\" title=\"View Question Details\" class=\"flat-btn btn question-details\"><i class=\"fa fa-eye\"></i></a>";
            $html .= "          <a href=\"#\" title=\"Attach to Exam\" class=\"flat-btn btn attach-question\"><i class=\"icon-plus-sign\"></i></a>";
            $html .= "      </div>";
        } else {
            if (is_array($control_array) && !empty($control_array)) {
                foreach ($control_array as $control_group) {
                    $html .= "<div class=\"btn-group\">";
                    foreach ($control_group as $control) {
                        $html .= $control;
                    }
                    $html .= "</div>";
                }
            }
        }

        return $html;
    }

    public function renderListDisplay() {
        if ($this->element) {
            $element = $this->element;
            $element_order = ($element->getOrder()) + 1;
            $element_id = $element->getID();
            $display_style = $this->display_style;
        }

        switch ($element->getElementType()) {
            case "text":
            case "page_break":

                switch($element->getElementType()) {
                    case "text":
                        $type = "Free Text";
                        break;
                    case "page_break":
                        $type = "Page Break";
                        break;
                }

                $html = "<tr id=\"question-row-" . $element->getElementType() . "-" . $element->getID() ."\" class=\"exam-element question-row\" data-sortable-element-id=\"element_" . $element->getID() . "\" data-element-id=\"" . $element->getID() . "\" >";
                $html .= "    <td class=\"q-list-number\">";
                $html .= "        <span class=\"question-number\" data-element-id=\"" . $element_id . "\">";
                $html .= "            <input class=\"question-number-update\" type=\"text\" data-element-id=\"" . $element_id . "\" value=\"" . $element_order . "\" name=\"order[]\" />";
                $html .= "        </span>";
                $html .= "    </td>";
                $html .= "    <td class=\"q-list-id\">";
                $html .= "      ID: NA";
                $html .= "    </td>";
                $html .= "    <td class=\"q-list-desc\">";
                $html .= " " . $type;
                $html .= "    </td>";
                $html .= "    <td class=\"q-list-date\">";
                $html .= "      " . date("m-d-Y g:i a", $element->getUpdatedDate());
                $html .= "    </td>";
                $html .= "    <td class=\"q-list-edit\">";
                $html .= "        <div class=\"btn-group header-buttons\">";
                $html .= "            <span class=\"btn flat-btn select-item select-question\">";
                $html .= "              <i class=\"icon-select-item icon-select-question fa fa-2x fa-square-o\" data-element-id=\"" . $element->getID() . "\" ></i>";
                $html .= "            </span>";
                $html .= "        </div>";
                $html .= "        <div class=\"btn-group header-buttons\">";
                $html .= "            <a class=\"btn flat-btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>";
                $html .= "        </div>";
                $html .= "    </td>";

                switch ($display_style) {
                    case "details":

                        break;
                    case "list":

                        break;
                }


                $html .= "</tr>";

                break;
        }

        return $html;
    }

    /**
     * @param Models_Exam_Exam_Element $element
     * @return string
     */
    public function renderElement (Models_Exam_Exam_Element $element, $exam_mode = false) {
        $html = NULL;
        $display_style = $this->display_style;
        switch($element->getElementType()) {
            case "text" :
                if ($exam_mode) {
                    $highlight = $this->highlight;
                    $html = "<div class=\"exam-question\" data-element-id=\"" . $element->getID() . "\" spellcheck=\"false\">
                                <table class=\"question-table\">
                                    <tbody>
                                    <tr class=\"heading\">
                                        <td colspan=\"2\">";
                    $html .= "            <div class=\"question_text exam_element\">";
                    $html .= "              <span class=\"summernote_text\" data-type=\"element_text\" data-exam-element-id=\"" . $element->getID() . "\">";
                    if ($highlight) {
                        $html .=  $highlight->getElementText();
                    } else {
                        $html .=  $element->getElementText();
                    }
                    $html .= "            </div>";
                    $html .= "            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>";
                } else {
                    if ($display_style === "details") {
                        $html = "<div class=\"exam-element exam-question exam-text\" data-sortable-element-id=\"element_" . $element->getID() . "\" data-element-id=\"" . $element->getID() . "\" data-element-type=\"text\">
                                <table class=\"question-table\">
                                    <tr class=\"type\">
                                        <td>
                                            <span class=\"question-type\">Free Text</span>
                                            <div class=\"pull-right\">
                                                <div class=\"btn-group header-buttons\">
                                                    <span class=\"flat-btn btn select-item select-question\">
                                                        <i class=\"icon-select-question fa fa-2x fa-square-o\" data-element-id=\"" . $element->getID() . "\"></i>
                                                    </span>
                                                </div>
                                                <div class=\"btn-group header-buttons\">
                                                    <a class=\"flat-btn btn save-element\" data-text-element-id=\"" . $element->getID() . "\" title=\"Save\" href=\"#\">Save</a>
                                                    <a class=\"flat-btn btn item-details\" title=\"View Question Details\" href=\"#\"><i class=\"fa fa-eye\"></i></a>
                                                    <a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class=\"question-answer-view\">
                                        <td class=\"padding-left padding-right\">
                                            <div class=\"row-fluid space-above space-below\">
                                                <textarea id=\"element-" . $element->getID() . "\" name=\"text-element[" . $element->getID() . "]\">" . $element->getElementText() . "</textarea>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class=\"question-detail-view hide\">
                                        <td colspan=\"2\">
                                            <div class=\"question-details-container\">
                                                <h3>Details</h3>
                                                <blockquote><h5>Type: <span>Text</span></h5></blockquote>
                                                <ul>
                                                    <li>
                                                        <p class=\"text-right creation-date\">Question text was last updated on: " . date("Y-m-d", $element->getUpdatedDate());
                        $user = User::fetchRowByID($element->getUpdatedBy(), null, null, 1);
                        if ($user) {
                            $html .= " by <a href=\"" . ENTRADA_RELATIVE . "/people?id=" . $user->getID() . "\">";
                            $html .= $user->getFullname();
                            $html .= "</a>";
                        }
                        $html .= "                        </p>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>";
                    } else {
                        // list
                        $html = $this->renderListDisplay();
                    }
                }

                break;
            case "page_break" :
                if (!$exam_mode) {

                    if ($display_style === "details") {
                        $display_type = $element->getExam()->getDisplayQuestions();

                        $html = "<div class=\"exam-element exam-question" . (($display_type != "page_breaks") ? " disabled" : "") . "\" data-sortable-element-id=\"element_" . $element->getID() . "\" data-element-id=\"" . $element->getID() . "\" data-element-type=\"page_break\">
                                <table class=\"question-table\">
                                    <tbody>
                                    <tr class=\"heading\">
                                        <td colspan=\"2\">
                                            <div class=\"row-fluid text-center\">
                                                <div class=\"page-break span4 offset4\">
                                                    <i class=\"icon-file\"></i> Page Break" . (($display_type != "page_breaks") ? " (<em>disabled</em>)" : "") . "
                                                </div>
                                                <div class=\"element-controls span4 text-right\">
                                                    <div class=\"btn-group\">
                                                        <span class=\"flat-btn btn select-item select-question\">
                                                            <i class=\"icon-select-question fa fa-2x fa-square-o\" data-element-id=\"" . $element->getID() . "\"></i>
                                                        </span>
                                                    </div>
                                                    <div class=\"btn-group\">
                                                        <a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>";
                    } else {
                        $html = $this->renderListDisplay();
                    }
                }
                break;
                default :
                    $html = "<div class=\"exam-question\">
                            <table class=\"question-table\">
                                <tbody>
                                <tr class=\"heading\">
                                    <td colspan=\"2\">
                                        <h3>" . $element->getElementText() . "</h3>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>";
                break;
        }

        return $html;
    }

    public function renderElementAdminRow(Models_Exam_Exam_Element $element) {
        $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
        $question_type = $question_version->getQuestionType();

        if ($question_type->getShortname() != "text") {
            $html = "<tr class=\"response_record\" data-id=\"" . $element->getElementID() . "\">\n";
            $html .= "<td>\n";
            $html .= $element->getOrder();
            $html .= "</td>\n";
            $html .= "<td>\n";
            $html .= $question_version->getQuestionID();
            $html .= "</td>\n";
            $html .= "<td>\n";
            $html .= $question_version->getQuestionText();
            $html .= "</td>\n";
            $html .= "<td>\n";
            $html .= $question_version->getQuestionCode();
            $html .= "</td>\n";
            $html .= "<td>\n";
            $html .= $question_type->getShortname();
            $html .= "</td>\n";
            $html .= "<td>\n";
            $html .= "\n";
            $html .= "</td>\n";
            $html .= "<td>\n";
            $html .= "\n";
            $html .= "</td>\n";
            $html .= "<td class=\"edit_menu\">\n";
            $html .= "\n";
            $html .= "</td>\n";
            $html .= "</tr>\n";

            return $html;
        }
    }

    public function renderAdmin() {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        if ($this->element !== null) {
            return $this->renderElementAdminRow($this->element);
        } else {
            echo display_notice($MODULE_TEXT["element"]["text_no_available_element"]);
        }
    }

    public function render($exam_mode = false, $display_style = "details", Models_Exam_Progress_Responses $response = null) {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        $this->display_style = $display_style;
        if ($this->element !== null) {
            if ($response !== null) {
                $this->response = $response;
                $this->highlight = $this->response->getExamElementHighlight($this->element->getID());
            }

            return $this->renderElement($this->element, $exam_mode);
        } else {
            echo display_notice($MODULE_TEXT["element"]["text_no_available_element"]);
        }
    }
}