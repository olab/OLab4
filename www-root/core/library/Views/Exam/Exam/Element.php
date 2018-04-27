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
            $html .= "          <i class=\"question-icon-select fa fa-2x fa-square-o\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i>";
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
                $html = "";

                $html .= "<tr id=\"question-row-" . $element->getElementType() . "-" . $element->getID() ."\" class=\"exam-element question-row\" data-sortable-element-id=\"element_" . $element->getID() . "\" data-element-id=\"" . $element->getID() . "\" >";
                $html .= "    <td class=\"span1 text-center q-list-edit\">";
                $html .= "        <span class=\"select-item select-question\">";
                $html .= "            <i class=\"select-item-icon question-icon-select fa fa-square-o\" data-element-id=\"" . $element->getID() . "\" ></i>";
                $html .= "        </span>";
                $html .= "    </td>";
                $html .= "    <td class=\"span2\">";
                $html .= "        <span class=\"question-number\" data-element-id=\"" . $element_id . "\">";
                $html .= "            <input class=\"question-number-update\" type=\"text\" data-element-id=\"" . $element_id . "\" value=\"" . $element_order . "\" name=\"order[]\" />";
                $html .= "        </span>";
                $html .= "    </td>";
                $html .= "    <td class=\"span2\">";
                $html .= "      ID: NA";
                $html .= "    </td>";
                $html .= "    <td class=\"span5\">";
                $html .= " " . $type;
                $html .= "    </td>";
                $html .= "    <td class=\"span3\">";
                $html .= "      " . date("m-d-Y g:i a", $element->getUpdatedDate());
                $html .= "    </td>";
                $html .= "    <td class=\"span1 text-center\">";
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
    public function renderElement(Models_Exam_Exam_Element $element, $exam_mode = false) {
        $html = NULL;
        $display_style = $this->display_style;
        switch($element->getElementType()) {
            case "text" :
                if ($exam_mode) {
                    $highlight = $this->highlight;
                    $html = "";
                    $html .=    "<div class=\"exam-question\" data-element-id=\"" . $element->getID() . "\" spellcheck=\"false\">";
                    $html .=    "    <table class=\"question-table\">";
                    $html .=    "        <tbody>";
                    $html .=    "            <tr class=\"heading\">";
                    $html .=    "                <td colspan=\"2\">";
                    $html .=    "                    <div class=\"question_text exam_element\">";
                    $html .=    "                        <span class=\"summernote_text\" data-type=\"element_text\" data-exam-element-id=\"" . $element->getID() . "\">";
                    $html .=    "                        " . ($highlight ? $highlight->getElementText() : $element->getElementText());
                    $html .=    "                        </span>";
                    $html .=    "                    </div>";
                    $html .=    "                </td>";
                    $html .=    "             </tr>";
                    $html .=    "         </tbody>";
                    $html .=    "   </table>";
                    $html .=    "</div>";
                } else {
                    if ($display_style === "details") {
                        $html = $this->renderFreeTextDetailDisplay($element);
                    } else {
                        // list
                        $html = $this->renderListDisplay();
                    }
                }

                break;
            case "page_break" :
                if (!$exam_mode) {
                    if ($display_style === "details") {
                        $html = $this->renderPageBreakDetailDisplay($element);
                    } else {
                        $html = $this->renderListDisplay();
                    }
                }
                break;
                default :
                    $html = "";
                    $html .= "<div class=\"exam-question\">";
                    $html .= "    <table class=\"question-table\">";
                    $html .= "        <tbody>";
                    $html .= "            <tr class=\"heading\">";
                    $html .= "                <td colspan=\"2\">";
                    $html .= "                    <h3>" . $element->getElementText() . "</h3>";
                    $html .= "                </td>";
                    $html .= "            </tr>";
                    $html .= "        </tbody>";
                    $html .= "    </table>";
                    $html .= "</div>";
                break;
        }

        return $html;
    }

    public function renderPageBreakDetailDisplay(Models_Exam_Exam_Element $element) {
        $display_type = $element->getExam()->getDisplayQuestions();

        $html = "";
        $html .=    "<div class=\"exam-element exam-question" . (($display_type != "page_breaks") ? " disabled" : "") . "\" data-sortable-element-id=\"element_" . $element->getID() . "\" data-element-id=\"" . $element->getID() . "\" data-element-type=\"page_break\">";
        $html .=    "    <table class=\"question-table\">";
        $html .=    "    <tbody>";
        $html .=    "        <tr class=\"heading\">";
        $html .=    "            <td colspan=\"2\">";
        $html .=    "                <div class=\"row-fluid text-center\">";
        $html .=    "                    <div class=\"span4 text-left\">";
        $html .=    "                        <span class=\"select-item select-question\">";
        $html .=    "                           <i class=\"select-item-icon question-icon-select fa fa-square-o\" data-element-id=\"" . $element->getID() . "\"></i>";
        $html .=    "                        </span>";
        $html .=    "                    </div>";
        $html .=    "                    <div class=\"page-break span4\">";
        $html .=    "                        <i class=\"icon-file\"></i> Page Break" . (($display_type != "page_breaks") ? " (<em>disabled</em>)" : "") . "";
        $html .=    "                    </div>";
        $html .=    "                    <div class=\"element-controls span4 text-right\">";
        $html .=    "                        <div class=\"btn-group\">";
        $html .=    "                            <a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>";
        $html .=    "                        </div>";
        $html .=    "                    </div>";
        $html .=    "                </div>";
        $html .=    "            </td>";
        $html .=    "        </tr>";
        $html .=    "    </tbody>";
        $html .=    "    </table>";
        $html .=    "</div>";
        return $html;
    }

    /**
     * This function renders the html for the exam element type of free text
     *
     * @param Models_Exam_Exam_Element $element
     * @return string
     */
    public function renderFreeTextDetailDisplay(Models_Exam_Exam_Element $element) {
        $user = Models_User::fetchRowByID($element->getUpdatedBy());
        $html =     "<div class=\"exam-element exam-question exam-text\" data-sortable-element-id=\"element_" . $element->getID() . "\" data-element-id=\"" . $element->getID() . "\" data-element-type=\"text\">";
        $html .=    "    <table class=\"question-table\">";
        $html .=    "        <tr class=\"type\">";
        $html .=    "            <td>";
        $html .=    "                <div class=\"span1 text-left\">";
        $html .=    "                    <span class=\"select-item select-question\">";
        $html .=    "                       <i class=\"select-item-icon question-icon-select fa fa-square-o\" data-element-id=\"" . $element->getID() . "\"></i>";
        $html .=    "                    </span>";
        $html .=    "                </div>";
        $html .=    "                <span class=\"question-type\">Free Text</span>";
        $html .=    "                <div class=\"pull-right\">";
        $html .=    "                    <div class=\"btn-group header-buttons\">";
        $html .=    "                        <a class=\"flat-btn btn save-element\" data-text-element-id=\"" . $element->getID() . "\" title=\"Save\" href=\"#\">Save</a>";
        $html .=    "                        <a class=\"flat-btn btn item-details\" title=\"View Question Details\" href=\"#\"><i class=\"fa fa-eye\"></i></a>";
        $html .=    "                        <a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>";
        $html .=    "                    </div>";
        $html .=    "                </div>";
        $html .=    "            </td>";
        $html .=    "        </tr>";
        $html .=    "        <tr class=\"question-answer-view\">";
        $html .=    "            <td class=\"padding-left padding-right\">";
        $html .=    "                <div class=\"row-fluid space-above space-below\">";
        $html .=    "                   <textarea id=\"element-" . $element->getID() . "\" name=\"text-element[" . $element->getID() . "]\">" . $element->getElementText() . "</textarea>";
        $html .=    "                </div>";
        $html .=    "            </td>";
        $html .=    "        </tr>";
        $html .=    "        <tr class=\"question-detail-view hide\">";
        $html .=    "            <td colspan=\"2\">";
        $html .=    "                <div class=\"question-details-container\">";
        $html .=    "                    <h3>Details</h3>";
        $html .=    "                    <blockquote><h5>Type: <span>Text</span></h5></blockquote>";
        $html .=    "                    <ul>";
        $html .=    "                        <li>";
        $html .=    "                            <p class=\"text-right creation-date\">";
        $html .=    "   Question text was last updated on: " . date("Y-m-d", $element->getUpdatedDate());
        $html .=    ($user ? " by <a href=\"" . ENTRADA_RELATIVE . "/people?id=" . $user->getID() . "\">" . $user->getFullname() . "</a>" : "");
        $html .=    "                            </p>";
        $html .=    "                        </li>";
        $html .=    "                    </ul>";
        $html .=    "                </div>";
        $html .=    "            </td>";
        $html .=    "        </tr>";
        $html .=    "    </table>";
        $html .=    "</div>";

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