<?php
/**
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Question_ImportPreview extends Views_Deprecated_Base {
    protected $question;

    public function __construct($question) {
        $this->question = $question;
    }

    public function render() {
        global $translate;

        $html = "";
        switch ($this->question["attributes"]["type"]) {
            case "mc_v":
                $div_class = "exam-vertical-choice-question";
                break;
            case "mc_h":
                $div_class = "exam-horizontal-choice-question";
                break;
            case "short":
                $div_class = "exam-short-question";
                break;
            case "essay":
                $div_class = "exam-essay-question";
                break;
            case "text":
                $div_class = "exam-text-question";
                break;
            case "fnb":
                $div_class = "exam-fnb-question";
                break;
            case "match":
                $div_class = "exam-match-question";
                break;
            default:
                $div_class = "";
                break;
        }
        $html .= "<div class=\"exam-question $div_class\">\n";
        $html .= "<table class=\"question-table mc-v\">\n";
        // Get the folder path from the folder id.
        $folder_path = "";
        $current_folder_id = $this->question["folder_id"];
        while (0 != $current_folder_id) {
            $folder = Models_Exam_Bank_Folders::fetchRowByID($current_folder_id);
            $folder_path = "/".$folder->getFolderTitle().$folder_path;
            $current_folder_id = $folder->getParentFolderID();
        }
        $header_text = "";
        if (isset($this->question["num"])) {
            $header_text .= "<strong>Question:</strong> #".$this->question["num"].", ";
        }
        $header_text .= "<strong>Folder:</strong> ".html_encode($folder_path);
        if (isset($this->question["question_id"])) {
            $header_text .= ", <strong>Already Imported</strong>";
        }
        if (isset($this->question["attributes"]["curriculum_tags"])) {
            $curriculum_tags_titles = array();
            foreach($this->question["attributes"]["curriculum_tags"] as $curriculum_tag_id){
                $objective = Models_Objective::fetchRow($curriculum_tag_id);
                array_push($curriculum_tags_titles, $objective->getName());
            }
            $header_text .= "<br/><strong>" . $translate->_("Curriculum Tags") . ":</strong> " . implode(", ", $curriculum_tags_titles);
        }
        $html .= "<tr class=\"type\"><td colspan=\"100\"><span class=\"question-type\" style=\"margin: 0; padding-left: 10px; line-height: 35px\">".$header_text."</span></td></tr>\n";
        $html .= "<tr class=\"heading\"><td colspan=\"100\"><div id=\"question_stem\"><div class=\"question_text\">";
        if ("fnb" === $this->question["attributes"]["type"]) {
            $html .= str_replace("_?_", "<input type=\"text\" />", $this->question["stem"]);
        } else {
            $html .= $this->question["stem"];
        }
        $html .= "</div></div></td></tr>\n";
        switch ($this->question["attributes"]["type"]) {
            case "mc_v":
            case "mc_v_m":
                $stripe = true;
                foreach ($this->question["choices"] as $letter => $choice) {
                    $stripe = !$stripe;
                    $multiple_answers = is_array($this->question["attributes"]["answer"]) && 1 !== count($this->question["attributes"]["answer"]);
                    $checked = (is_array($this->question["attributes"]["answer"]) && in_array($letter, $this->question["attributes"]["answer"])) ||
                            (!is_array($this->question["attributes"]["answer"]) && $letter === $this->question["attributes"]["answer"]);
                    $html .= "<tr class=\"".($stripe ? "row-stripe " : "")."question-answer-view\">\n";
                    $html .= "<td class=\"vertical-answer-input\">\n";
                    $html .= "<span class=\"question-letter\">".strtoupper($letter).". </span>\n";
                    $html .= "<input type=\"".($multiple_answers ? "checkbox" : "radio")."\" class=\"question-control\" disabled".($checked ? " checked" : "")." />\n";
                    $html .= "</td>\n";
                    $html .= "<td class=\"vertical-answer-label\">\n";
                    $html .= "<label>";
                    $html .= $choice;
                    if(isset($this->question["attributes"]["locked"]) && is_array($this->question["attributes"]["locked"]) && in_array($letter, $this->question["attributes"]["locked"])){
                        $html .= " <i class=\"fa-lock fa\"></i>\n";
                    }
                    $html .= "</label>\n";
                    $html .= "</td>\n";
                    $html .= "</tr>\n";
                }
                break;
            case "mc_h":
            case "mc_h_m":
                $width = round(100 / count($this->question["choices"]), 2);
                $html .= "<tr class=\"horizontal-answer-input question-answer-view\">\n";
                foreach ($this->question["choices"] as $letter => $choice) {
                    $multiple_answers = is_array($this->question["attributes"]["answer"]) && 1 !== count($this->question["attributes"]["answer"]);
                    $checked = (is_array($this->question["attributes"]["answer"]) && in_array($letter, $this->question["attributes"]["answer"])) ||
                            (!is_array($this->question["attributes"]["answer"]) && $letter === $this->question["attributes"]["answer"]);
                    $html .= "<td width=\"$width%\">\n";
                    $html .= "<span class=\"question-letter\">".strtoupper($letter).".</span>\n";
                    $html .= "<input type=\"".($multiple_answers ? "checkbox" : "radio")."\" class=\"question-control\" disabled".($checked ? " checked" : "")." />\n";
                    $html .= "</td>\n";
                }
                $html .= "</tr>\n";
                $html .= "<tr class=\"horizontal-answer-label question-answer-view\">\n";
                foreach ($this->question["choices"] as $letter => $choice) {
                    $html .= "<td width=\"$width%\">\n";
                    $html .= "<label>";
                    $html .= $choice;
                    if(isset($this->question["attributes"]["locked"]) && is_array($this->question["attributes"]["locked"]) && in_array($letter, $this->question["attributes"]["locked"])){
                        $html .= " <i class=\"fa-lock fa\"></i>\n";
                    }
                    $html .= "</label>\n";
                    $html .= "</td>\n";
                }
                $html .= "</tr>\n";
                break;
            case "short":
                $html .= "<tr class=\"question-answer-view\">\n";
                $html .= "<td class=\"question-type-control\">\n";
                $html .= "<input class=\"question-control\" type=\"text\" />\n";
                $html .= "</td>\n";
                $html .= "</tr>\n";
                break;
            case "essay":
                $html .= "<tr class=\"question-answer-view\">\n";
                $html .= "<td class=\"question-type-control\">\n";
                $html .= "<textarea class=\"expandable question-control\"></textarea>\n";
                $html .= "</td>\n";
                $html .= "</tr>\n";
                break;
            case "fnb":
                foreach ($this->question["attributes"]["answer"] as $i => $answer) {
                    $order = $i + 1;
                    $html .= "<tr>\n";
                    $html .= "<td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\">\n";
                    $html .= "<strong>Answer(s) for Blank $order:</strong> ".implode(" <em>or</em> ", $answer)."\n";
                    $html .= "</td>\n";
                    $html .= "</tr>\n";
                }
                break;
            case "match":
                foreach ($this->question["attributes"]["item"] as $i => $item) {
                    $order = $i + 1;
                    $striped = $i % 2 === 1;
                    $html .= "<tr class=\"question-answer-view".($striped ? " row-stripe" : "")."\">\n";
                    $html .= "<td class=\"vertical-answer-input\">\n";
                    $html .= "<div>$order. $item</div>\n";
                    $html .= "<div>\n";
                    $html .= "<select class=\"form-control question-control\">\n";
                    $html .= "<option>-- select an option --</option>\n";
                    $j = 0;
                    foreach ($this->question["choices"] as $letter => $choice) {
                        $selected = $this->question["attributes"]["answer"][$i] === $letter;
                        $html .= "<option".($selected ? " selected" : "").">".chr(ord("A")+$j).". $choice</option>\n";
                        $j++;
                    }
                    $html .= "</select>\n";
                    $html .= "</div>\n";
                    $html .= "</td>\n";
                    $html .= "</tr>\n";
                }
                break;
            case "text":
            default:
                break;
        }
        if (isset($this->question["attributes"]["description"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Description:</strong> ".html_encode($this->question["attributes"]["description"])."</td></tr>\n";
        }
        if (isset($this->question["attributes"]["rationale"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Rationale:</strong> ".html_encode($this->question["attributes"]["rationale"])."</td></tr>\n";
        }
        if (isset($this->question["attributes"]["correct_text"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Correct Text:</strong> ".html_encode($this->question["attributes"]["correct_text"])."</td></tr>\n";
        }
        if (isset($this->question["attributes"]["code"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Question Code:</strong> ".html_encode($this->question["attributes"]["code"])."</td></tr>\n";
        }
        if (isset($this->question["weight"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Weight:</strong> ".html_encode($this->question["weight"])."</td></tr>\n";
        }
        if (isset($this->question["group"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Group:</strong> ".html_encode($this->question["group"])."</td></tr>\n";
        }
        if (isset($this->question["author_name"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\"><strong>Author:</strong> ".html_encode($this->question["author_name"])."</td></tr>\n";
        }
        // COUNT_RECURSIVE - COUNT gives the number of objectives. COUNT_RECURSIVE gives
        // the number of objectives + the number of objective sets, while COUNT gives
        // only the number of objective sets. $this->question["objectives"] has a structure
        // like [ "objective_set_1" => [ "objective1", "objective2" ], "objective_set_2" => [ "objective3", "objective4" ] ]
        if (isset($this->question["objectives"]) && 0 < count($this->question["objectives"], COUNT_RECURSIVE) - count($this->question["objectives"])) {
            $html .= "<tr><td colspan=\"100\" style=\"padding: 5px 10px; border-top: 1px solid #e8e5e5\">\n";
            $html .= "<table>\n";
            $objective_label_output = false;
            foreach ($this->question["objectives"] as $objective_set_name => $objective_set) {
                foreach ($objective_set as $objective) {
                    $html .= "<tr>";
                    if (!$objective_label_output) {
                        $objective_label_output = true;
                        $html .= "<td><strong>Objectives:</strong></td>";
                    } else {
                        $html .= "<td></td>";
                    }
                    $html .= "<td>".html_encode($objective_set_name."/".$objective)."</td>";
                    $html .= "</tr>\n";
                }
            }
            $html .= "</table>\n";
            $html .= "</td></tr>\n";
        }
        $html .= "</table>\n";
        $html .= "</div>\n";
        return $html;
    }
}
