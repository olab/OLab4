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
 * A Model for parsing imported exam questions.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Parser {
    protected static $types = array(
        "mc_h", "mc_h_m", "mc_v", "mc_v_m", "short", "essay", "text", "fnb", "match"
    );
    
    protected static function isBlank($line) {
        return 0 === strlen(trim(preg_replace("/[\\x00-\\x1F\x80-\\xFF]/", "", $line)));
    }
    
    /**
     * Parses the question text in the default import format and returns an
     * array of questions.
     * 
     * @param string $question_text
     * @return mixed
     */
    public static function parse($question_text) {
        $output = array();
        $state = "new";
        $lines = explode("\r\n", $question_text);
        $current_question = null;
        $choice_regex = "/^([a-zA-Z])\.(.*)$/";
        $attribute_regex = "/^([^\s]+)\:(.*)/";
        $question_stem_regex = "/^(?:Q\:|\d+\.)(.*)$/";
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $line_num = $i + 1;
            switch ($state) {
                case "new":
                default:
                    if (!static::isBlank($line)) {
                        // This must start a question stem.
                        $matches = array();
                        if (preg_match($question_stem_regex, $line, $matches)) {
                            $current_question = array("stem" => html_encode(trim($matches[1])), "choices" => array());
                            $state = "stem";
                        } else {
                            add_error("Error on line $line_num, first line of a new question must begin a question stem.");
                            return false;
                        }
                    }
                    break;
                case "stem":
                    if (preg_match($choice_regex, $line) || preg_match($attribute_regex, $line)) {
                        // Go into attributes state, we will re-parse this line
                        $i--;
                        $state = "attributes";
                    } else {
                        $current_question["stem"] .= "<br />".html_encode($line);
                    }
                    break;
                case "attributes":
                    $matches = array();
                    if (static::isBlank($line)) {
                        $state = "new";
                        $output[] = $current_question;
                        $current_question = null;
                    } else if (preg_match($choice_regex, $line, $matches)) {
                        // Check for answer choices, which start with a letter followed by a period.
                        $letter = strtolower($matches[1]);
                        if (isset($current_question["choices"][$letter])) {
                            add_error("Error on line $line_num, answer choice ".strtoupper($letter)." defined multiple times.");
                            return false;
                        }
                        $current_question["choices"][$letter] = html_encode(trim($matches[2]));
                    } else if (preg_match($attribute_regex, $line, $matches)) {
                        $key = strtolower($matches[1]);
                        $value = trim($matches[2]);
                        if (isset($current_question["attributes"][$key])) {
                            // We can define multiple correct answer choices or item stems but defining
                            // more than one of another attribute is an error.
                            if (in_array($key, array("answer", "item"))) {
                                $current_question["attributes"][$key] = (array)$current_question["attributes"][$key];
                                $current_question["attributes"][$key][] = $value;
                            } else {
                                add_error("Error on line $line_num, attribute \"".html_encode($key)."\" defined multiple times.");
                                return false;
                            }
                        } else {
                            $current_question["attributes"][$key] = $value;
                        }
                    } else {
                        add_error("Error on line $line_num, expected answer choice or attribute.");
                        return false;
                    }
                    break;
            }
        }
        if (null !== $current_question) {
            $output[] = $current_question;
        }
        return $output;
    }
    
    /**
     * Parses a series of questions stored in the ExamSoft export format. Returns
     * exam info and an array of questions on success, or false on failure.
     * 
     * @param string $question_text
     * @return mixed
     */
    public static function parseExamsoft($question_text) {
        $output = array();
        $lines = explode("\n", $question_text);
        
        // Get the exam information
        if (count($lines) < 9) {
            add_error("Fewer lines than expected in input. Must have at least 9 lines.");
            return false;
        }
        if (static::isBlank($lines[0])) {
            $start_line = 0;
        } else if ("DRAFT" === substr($lines[0], strlen($lines[0]) - strlen("DRAFT")) &&
                   "Do Not Use Until Posted." === $lines[1]) {
            $start_line = 6;
            $output["exam"]["name"] = $lines[5];
        } else {
            $start_line = 1;
            $output["exam"]["name"] = $lines[0];
        }
        if (!preg_match("/^Exam ID: (\d+)\s*Course Name: -$/", $lines[$start_line + 2], $matches)) {
            add_error("Unable to find Examsoft Exam ID on line ".($start_line + 3).".");
            return false;
        } else {
            $output["exam"]["examsoft_exam_id"] = (int)$matches[1];
        }
        if (!preg_match("/^# of Questions: (\d+)$/", $lines[$start_line + 3], $matches)) {
            add_error("Unable to find number of questions on line ".($start_line + 4).".");
            return false;
        } else {
            $output["exam"]["num_questions"] = (int)$matches[1];
        }
        if (!preg_match("/^Exam Folder: ([^\\\\]+)\\\\.+$/", $lines[$start_line + 5], $matches)) {
            add_notice("Unable to find course name on line ".($start_line + 6).".");
        } else {
            $output["exam"]["objective_set_name"] = $matches[1]." Keywords";
        }
        if (!preg_match("/^Total Exam Points: (\d+\.\d+)$/", $lines[$start_line + 6], $matches)) {
            add_error("Unable to find total exam points on line ".($start_line + 7).".");
            return false;
        } else {
            $output["exam"]["total_points"] = (float)$matches[1];
        }
        
        // Get the questions
        $state = "item_info";
        $current_choice_letter = "";
        $current_question = null;
        $question_num_regex = "/^Question #: (\d+)$/";
        $choice_regex = "/^(✓?)([A-Z])\. (.*?)\s*$/u";
        $rationale_regex = "/^Rationale: (.+)$/";
        $attachment_regex = "/^Attachment: (.+)$/";
        $item_id_regex = "/^Item ID: (\d+) \\/ (\d+)$/";
        $item_desc_regex = "/^Item Description: (.+)$/";
        $item_group_regex = "/^Item Group: (.+)$/";
        $item_weight_regex = "/^Item Weight: (\d+\.\d+)$/";
        $item_categories_regex = "/^Item Categories:.+$/";
        $item_category_path_regex = "/^Category Path$/";
        $item_creator_regex = "/^Item Creator: (.+)$/";
        $separator_regex = "/^_+$/";
        for ($i = 0; $i < count($lines); $i++) {
            if ($i < $start_line) {
                continue;
            }
            $line = $lines[$i];
            $next_line = $i + 1 < count($lines) ? $lines[$i + 1] : "";
            $line_num = $i + 1;
            switch ($state) {
                case "consume_blanks":
                    if (!static::isBlank($line)) {
                        $state = $next_state;
                        $i--;
                    }
                    break;
                case "new":
                    $current_question = array(
                        "attributes" => array("type" => "mc_v"),
                        "objectives" => array(
                            $output["exam"]["objective_set_name"] => array(),
                            "USMLE" => array()
                        )
                    );
                    if (!preg_match($question_num_regex, $line, $matches)) {
                        add_error("Unexpected question number format on line $line_num.");
                        return false;
                    } else {
                        $current_question["num"] = (int)$matches[1];
                    }
                    $state = "consume_blanks";
                    $next_state = "stem";
                    break;
                case "stem":
                    if (preg_match($choice_regex, $line) || preg_match($rationale_regex, $line)) {
                        // Remove extraneous line breaks at the end of the question stem and move on to question choices
                        $current_question["stem"] = preg_replace("|(<br />)+$|", "", $current_question["stem"]);
                        $state = "choices";
                        $i--;
                    } else if (preg_match($item_id_regex, $line)) {
                        // Remove extraneous line breaks at the end of the question stem and move on to item info
                        $current_question["stem"] = preg_replace("|(<br />)+$|", "", $current_question["stem"]);
                        $state = "item_info";
                        $i--;
                    } else {
                        if (isset($current_question["stem"])) {
                            $current_question["stem"] .= "<br />".trim($line);
                        } else {
                            $current_question["stem"] = trim($line);
                        }
                    }
                    break;
                case "choices":
                    if (preg_match($item_id_regex, $line)) {
                        $state = "item_info";
                        $current_choice_letter = "";
                        $i--;
                    } else if (preg_match($choice_regex, $line, $matches)) {
                        $correct = $matches[1] === "✓";
                        $current_choice_letter = strtolower($matches[2]);
                        $choice = $matches[3];
                        if (isset($current_question["choices"][$current_choice_letter])) {
                            add_error("Answer choice ".strtoupper($current_choice_letter)." defined multiple times on line $line_num.");
                            return false;
                        }
                        $current_question["choices"][$current_choice_letter] = html_encode($choice);
                        if ($correct) {
                            if (isset($current_question["attributes"]["answer"])) {
                                $current_question["attributes"]["type"] = "mc_v_m";
                                if (is_array($current_question["attributes"]["answer"])) {
                                    $current_question["attributes"]["answer"][] = $current_choice_letter;
                                } else {
                                    $current_question["attributes"]["answer"] = array($current_question["attributes"]["answer"], $current_choice_letter);
                                }
                            } else {
                                $current_question["attributes"]["answer"] = $current_choice_letter;
                            }
                        }
                    } else if (preg_match($rationale_regex, $line, $matches)) {
                        $current_question["attributes"]["rationale"] = $matches[1];
                        $state = "rationale";
                        $current_choice_letter = "";
                    } else if (preg_match($attachment_regex, $line, $matches)) {
                        add_notice("Skipping attachment '".html_encode($matches[1])."' on line $line_num.");
                    } else if ($current_choice_letter) {
                        $current_question["choices"][$current_choice_letter] .= "<br />".html_encode($line);
                    } else {
                        add_notice("Line $line_num not in the required format, skipping question #".$current_question["num"].".");
                        $current_question = null;
                        $state = "consume_blanks";
                        $next_state = "item_info";
                        $current_choice_letter = "";
                    }
                    break;
                case "rationale":
                    if (preg_match($item_id_regex, $next_line, $matches)) {
                        $state = "consume_blanks";
                        $next_state = "item_info";
                    } else {
                        $current_question["attributes"]["rationale"] .= " ".$line;
                    }
                    break;
                case "item_info":
                    if (null !== $current_question && !isset($current_question["set_to_essay_flag"]) &&
                            (0 === count($current_question["choices"]) || !isset($current_question["attributes"]["answer"]))) {
                        $current_question["attributes"]["type"] = "essay";
                        if (0 !== count($current_question["choices"])) {
                            // If there are choices set, they should be added back to the question stem.
                            foreach ($current_question["choices"] as $letter => $choice) {
                                $current_question["stem"] .= "<br />".strtoupper($letter).". ".$choice;
                            }
                        }
                        $current_question["set_to_essay_flag"] = true;
                    }
                    if (preg_match($item_id_regex, $line, $matches) && null !== $current_question) {
                        // Check if this question has already been imported
                        $current_question["examsoft_id"] = (int)$matches[1]."/".(int)$matches[2];
                    } else if (preg_match($item_desc_regex, $line, $matches) && null !== $current_question) {
                        $current_question["attributes"]["description"] = $matches[1];
                    } else if (preg_match($item_group_regex, $line, $matches)) {
                        $current_question["group"] = $matches[1];
                    } else if (preg_match($item_weight_regex, $line, $matches) && null !== $current_question) {
                        $current_question["weight"] = (float)$matches[1];
                    } else if (preg_match($item_categories_regex, $line, $matches) && null !== $current_question) {
                        $state = "consume_blanks";
                        $next_state = "item_categories";
                    } else {
                        $state = "wait_for_separator";
                    }
                    break;
                case "item_categories":
                    if (preg_match($item_category_path_regex, $line, $matches)) {
                        $state = "item_category_name";
                    }
                    break;
                case "item_category_name":
                    if (preg_match($item_creator_regex, $line, $matches)) {
                        $author_email = $matches[1];
                        $author = User::fetchRowByEmail($author_email);
                        if ($author) {
                            $current_question["author_name"] = $author->getFullname(false);
                            $current_question["author_id"] = $author->getProxyID();
                        }
                        $state = "wait_for_separator";
                    } else {
                        $state = "item_category_path";
                    }
                    break;
                case "item_category_path":
                    $state = "item_category_name";
                    if (null !== $current_question) {
                        $topics = "Topics\\";
                        $usmle = "USMLE Subject\\";
                        if (0 === strpos($line, $topics)) {
                            $category = substr($line, strlen($topics));
                            $current_question["objectives"][$output["exam"]["objective_set_name"]][] = $category;
                        } else if (0 === strpos($line, $usmle)) {
                            $category = substr($line, strlen($usmle));
                            $current_question["objectives"]["USMLE"][] = $category;
                        }
                    }
                    break;
                case "wait_for_separator":
                default:
                    if (preg_match($separator_regex, $line)) {
                        if (null !== $current_question) {
                            $output["questions"][] = $current_question;
                            $current_question = null;
                        }
                        $state = "consume_blanks";
                        $next_state = "new";
                    }
                    break;
            }
        }
        if (null !== $current_question) {
            $output["questions"][] = $current_question;
        }
        
        if (count($output["questions"]) !== $output["exam"]["num_questions"]) {
            add_notice("Parsed only ".count($output["questions"])." of expected ".$output["exam"]["num_questions"]." questions.");
        }
        $total_parsed_points = array_reduce($output["questions"], function($carry, $item) { return $carry + $item["weight"]; }, 0);
        if ($total_parsed_points !== $output["exam"]["total_points"]) {
            add_notice("Parsed only ".$total_parsed_points." points worth of questions, expected ".$output["exam"]["total_points"]." total points.");
        }
        
        return $output;
    }
    
    public static function parseExamsoftImages($text) {
        $output = array();
        $lines = explode("\n", $text);
        
        $image_regex = '/<img src="([^"]+)"[^>]*width="(\d+)"[^>]*height="(\d+)"[^>]*>/';
        $question_id_regex = '/^ID: (<[^>]*>)+(\d+)$/';
        $question_version_regex = '|^/ (\d+)(<[^>]*>)+$|';
        $current_images = array();
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $next_line = $i + 1 < count($lines) ? $lines[$i + 1] : "";
            if (preg_match($image_regex, $line, $matches)) {
                $current_images[] = array("src" => $matches[1], "width" => (int)$matches[2], "height" => (int)$matches[3]);
            } else if (preg_match($question_id_regex, $line, $matches1) && preg_match($question_version_regex, $next_line, $matches2)) {
                if (0 < count($current_images)) {
                    $question_id = (int)$matches1[2];
                    $question_version = (int)$matches2[1];
                    $examsoft_id = $question_id."/".$question_version;
                    $question = Models_Exam_Question_Versions::fetchRowByExamsoftID($examsoft_id);
                    if ($question) {
                        if ($question->getExamsoftImagesAdded()) {
                            add_notice("Examsoft question with ID ".$examsoft_id." has had its images added already, ".count($current_images)." image(s) skipped.");
                        } else {
                            $output[] = array("question_id" => $question->getID(), "images" => $current_images);
                        }
                    } else {
                        add_notice("Examsoft question with ID ".$examsoft_id." not found in the system; ".count($current_images)." image(s) skipped.");
                    }
                    $current_images = array();
                }
            }
        }
        
        return $output;
    }
    
    /**
     * Validates the question's fields based on its type. If the question is
     * not found to be valid, add an error.
     * @param mixed $question
     * @param int $num The number of the question being processed, used to report more
     * @param int $default_folder_id If no folder: attribute is set, use this folder id.
     * specific errors to the user.
     */
    public static function validate(&$question, $num, $default_folder_id) {
        if (!is_array($question)) {
            return false;
        }
        $error = false;
        // Make sure the question text is set.
        if (!isset($question["stem"])) {
            add_error("Question $num does not include required question stem text.");
            $error = true;
        }
        
        // Make sure the "type" of question is set and is in the whitelist.
        if (!isset($question["attributes"]["type"])) {
            $question["attributes"]["type"] = "mc_v";
        } else if (!in_array(strtolower($question["attributes"]["type"]), static::$types)) {
            add_error("Question $num is of an unsupported type \"".html_encode($question["attributes"]["type"])."\".");
            $error = true;
        }

        $question["attributes"]["type"] = strtolower($question["attributes"]["type"]);

        // Make sure the parent folder is set.
        if (!isset($question["attributes"]["folder"]) && !$default_folder_id) {
            add_error("Question $num does not include required parent folder via the \"folder\" option and there is no default folder set.");
            $error = true;
        }
        if ($error) {
            return false;
        }
        // Make sure answers are provided for multiple choice questions.
        if (in_array($question["attributes"]["type"], array("mc_v", "mc_v_m", "mc_h", "mc_h_m"))) {
            if (is_array($question["attributes"]["answer"])) {
                $question["attributes"]["answer"] = array_map("trim", array_map("strtolower", $question["attributes"]["answer"]));
            } else {
                $question["attributes"]["answer"] = trim(strtolower($question["attributes"]["answer"]));
            }
            if (0 === count($question["choices"])) {
                add_error("Question $num is a multiple choice question and must provide answer choices.");
                $error = true;
            } else if (!isset($question["attributes"]["answer"])) {
                add_error("Question $num is missing a required \"answer\" option.");
                $error = true;
            } else if (in_array($question["attributes"]["type"], array("mc_v", "mc_h")) && is_array($question["attributes"]["answer"])) {
                add_error("Question $num cannot have multiple correct answer choices.");
                $error = true;
            } else if ("mc_h" === $question["attributes"]["type"] && 5 < count($question["choices"])) {
                add_error("Question $num has more than the maximum of 5 answers allowed for a horizontal multiple choice question.");
                $error = true;
            } else if (!is_array($question["attributes"]["answer"]) && !isset($question["choices"][$question["attributes"]["answer"]])) {
                add_error("Question $num has an \"answer\" option that does not match any answer choices.");
                $error = true;
            } else if (is_array($question["attributes"]["answer"])) {
                foreach ($question["attributes"]["answer"] as $answer) {
                    if (!isset($question["choices"][$answer])) {
                        add_error("Question $num has an \"answer\" option that does not match any answer choices.");
                        $error = true;
                    }
                }
            }
        }
        // Make sure fill in the blank questions do not have more answers than blanks. Also
        // make sure there is at least one blank.
        if ("fnb" === $question["attributes"]["type"]) {
            // Build an array of unique, trimmed, non-blank correct answers for each blank
            $answers = array();
            foreach ((array)$question["attributes"]["answer"] as $answer) {
                $choices = array_filter(array_unique(array_map("trim", explode("|", $answer))));
                $answers[] = $choices;
            }
            $question["attributes"]["answer"] = $answers;
            $num_blanks = substr_count($question["stem"], "_?_");
            $num_answers = count($question["attributes"]["answer"]);
            if (0 === $num_blanks) {
                add_error("Question $num must have at least one blank.");
                $error = true;
            } else if ($num_blanks < $num_answers) {
                add_error("Question $num has $num_answers answer(s) but only $num_blanks blank(s).");
                $error = true;
            } else {
                // Add empty correct answer arrays until we have equal numbers of answers and blanks
                while (count($question["attributes"]["answer"]) < $num_blanks) {
                    $question["attributes"]["answer"][] = array();
                }
            }
        }
        // Make sure matching questions do not have fewer answer choices than item stems
        if ("match" === $question["attributes"]["type"]) {
            $question["attributes"]["item"] = (array)$question["attributes"]["item"];
            $question["attributes"]["answer"] = array_map("trim", array_map("strtolower", (array)$question["attributes"]["answer"]));
            $num_items = count($question["attributes"]["item"]);
            $num_answers = count($question["attributes"]["answer"]);
            $num_choices = count($question["choices"]);
            if (0 === $num_items) {
                add_error("Question $num must have at least one item stem.");
                $error = true;
            } else if ($num_answers !== $num_items) {
                add_error("Question $num must have an answer for each item stem.");
                $error = true;
            } else if ($num_choices < $num_items) {
                add_error("Question $num has $num_items item(s) but only $num_choices answer choice(s).");
                $error = true;
            } else {
                foreach ($question["attributes"]["answer"] as $answer) {
                    if (!isset($question["choices"][$answer])) {
                        add_error("Question $num has an answer '$answer' that does not correspond to an answer choice.");
                        $error = true;
                    }
                }
            }
        }
        // Get the folder ID from the given folder name or the default folder ID
        if (isset($question["attributes"]["folder"])) {
            // Find the folder_id for this question. Start with the root folder as the
            // parent, then look for each child folder, separated by a "/" in the input.
            $folder_hierarchy = explode("/", $question["attributes"]["folder"]);
            // Remove empty strings at the beginning and end, if found, because these are caused
            // by leading and trailing slashes, respectively.
            if (0 < count($folder_hierarchy) && "" === $folder_hierarchy[count($folder_hierarchy) - 1]) {
                unset($folder_hierarchy[count($folder_hierarchy) - 1]);
            }
            if (0 < count($folder_hierarchy) && "" === $folder_hierarchy[0]) {
                unset($folder_hierarchy[0]);
            }
            $folder_id = 0;
            foreach ($folder_hierarchy as $folder_name) {
                $folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID($folder_id);
                $found = false;
                foreach ($folders as $folder) {
                    if ($folder->getFolderTitle() === $folder_name) {
                        $folder_id = (int)$folder->getID();
                        $found = true;
                    }
                }
                if (!$found) {
                    add_error("Question $num: could not find folder \"".html_encode($question["attributes"]["folder"])."\".");
                    return false;
                }
            }
            if (0 === $folder_id) {
                add_error("Question $num: folder cannot be the root folder.");
                return false;
            }
            $question["folder_id"] = $folder_id;
        } else {
            $question["folder_id"] = $default_folder_id;
        }
        // Everything checks out.
        return !$error;
    }
    
    /**
     * Imports a question
     * @param type $question_input
     * @param int $num The number of the question being processed, used to report more
     * specific errors to the user.
     */
    public static function import($question_input, $num) {
        global $db, $ENTRADA_USER;
        // Get the questiontype_id
        $questiontype = Models_Exam_Lu_Questiontypes::fetchRowByShortname($question_input["attributes"]["type"]);
        if ($questiontype) {
            $questiontype_id = $questiontype->getID();
        } else {
            add_error("Question $num: Unsupported question type '".$question_input["attributes"]["type"]."'.");
            return false;
        }
        // Start database transaction
        $db->StartTrans();
        // Create a new question_id
        $examsoft_id_parts = explode("/", $question_input["examsoft_id"]);
        $question = Models_Exam_Questions::fetchRowByExamsoftID($examsoft_id_parts[0]);
        if (!$question) {
            $question = new Models_Exam_Questions();
            if (!$question->insert()) {
                add_error("Question $num: Error inserting new question_id into database. Please try again later.");
                $db->FailTrans();
                $db->CompleteTrans();
                return false;
            }
        }
        // Create a new question version. If the version_count from the examsoft ID is in use,
        // increment the version_count until we find one that is not in use.
        if ($question_input["examsoft_id"]) {
            $new_version_count = (int)$examsoft_id_parts[1];
            $previous_versions = Models_Exam_Question_Versions::fetchAllByQuestionID($question->getID());
            do {
                $version_count_exists = false;
                foreach ($previous_versions as $version) {
                    if ($version->getVersionCount() == $new_version_count) {
                        $new_version_count++;
                        $version_count_exists = true;
                        break;
                    }
                }
            } while ($version_count_exists);
        } else {
            $new_version_count = 1;
        }
        $question_version = new Models_Exam_Question_Versions(array(
            "version_count" => $new_version_count,
            "grading_scheme" => "full",
            "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "created_date" => time(),
            "created_by" => $ENTRADA_USER->getProxyID(),
            "updated_date" => time(),
            "updated_by" => $ENTRADA_USER->getProxyID(),
            "examsoft_id" => (isset($question_input["examsoft_id"]) ? $question_input["examsoft_id"] : null),
            "examsoft_flagged" => (isset($question_input["examsoft_flagged"]) ? $question_input["examsoft_flagged"] : 0)
        ));
        if ($question_version) {
            $question_version->setQuestionID($question->getID());
            $question_version->setFolderID($question_input["folder_id"]);
            $question_version->setQuestionText($question_input["stem"]);
            $question_version->setQuestiontypeID($questiontype_id);
        }

        if (isset($question_input["attributes"]["description"])) {
            $question_version->setQuestionDescription($question_input["attributes"]["description"]);
        }
        if (isset($question_input["attributes"]["rationale"])) {
            $question_version->setRationale($question_input["attributes"]["rationale"]);
        }
        if (isset($question_input["attributes"]["correct_text"])) {
            $question_version->setCorrectText($question_input["attributes"]["correct_text"]);
        }
        if (isset($question_input["attributes"]["code"])) {
            $question_version->setQuestionCode($question_input["attributes"]["code"]);
        }
        if (!$question_version->insert() || !$question_version->getQuestion()->update()) {
            add_error("Question $num: Error inserting new question version into database. Please try again later.");
            $db->FailTrans();
            $db->CompleteTrans();
            return false;
        }
        // Handle extra details for different question types.
        switch ($question_input["attributes"]["type"]) {
            case "mc_v":
            case "mc_v_m":
            case "mc_h":
            case "mc_h_m":
                $order = 0;
                foreach ($question_input["choices"] as $letter => $answer_choice) {
                    $order++;
                    $question_answer = new Models_Exam_Question_Answers(array(
                        "question_id" => $question->getID(),
                        "version_id" => $question_version->getID(),
                        "answer_text" => $answer_choice,
                        "correct" => (in_array($letter, (array)$question_input["attributes"]["answer"]) ? 1 : 0),
                        "order" => $order
                    ));
                    if (!$question_answer->insert()) {
                        add_error("Question $num: Error inserting multiple choice answer into database. Please try again later.");
                        $db->FailTrans();
                        $db->CompleteTrans();
                        return false;
                    }
                }
                break;
            case "fnb":
                foreach ($question_input["attributes"]["answer"] as $i => $answers) {
                    $order = $i + 1;
                    $question_answer = new Models_Exam_Question_Answers(array(
                        "question_id" => $question->getID(),
                        "version_id" => $question_version->getID(),
                        "answer_text" => null,
                        "answer_rationale" => null,
                        "correct" => null,
                        "weight" => null,
                        "order" => $order,
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$question_answer->insert()) {
                        add_error("Question $num: Error inserting fill in the blank answer into database. Please try again later.");
                        $db->FailTrans();
                        $db->CompleteTrans();
                        return false;
                    }
                    foreach ($answers as $answer) {
                        $question_fnb_text = new Models_Exam_Question_Fnb_Text(array(
                            "qanswer_id" => $question_answer->getID(),
                            "text" => $answer,
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getProxyID()
                        ));
                        if (!$question_fnb_text->insert()) {
                            add_error("Question $num: Error inserting fill in the blank correct text into database. Please try again later.");
                            $db->FailTrans();
                            $db->CompleteTrans();
                            return false;
                        }
                    }
                }
                break;
            case "match":
                $answer_ids = array();
                $order = 0;
                foreach ($question_input["choices"] as $letter => $choice_text) {
                    $order++;
                    $question_answer = new Models_Exam_Question_Answers(array(
                        "question_id" => $question->getID(),
                        "version_id" => $question_version->getID(),
                        "answer_text" => $choice_text,
                        "answer_rationale" => "",
                        "correct" => 0,
                        "weight" => "",
                        "order" => $order,
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$question_answer->insert()) {
                        add_error("Question $num: Error inserting matching answer into database. Please try again later.");
                        $db->FailTrans();
                        $db->CompleteTrans();
                        return false;
                    }
                    $answer_ids[$letter] = array("order" => $order, "qanswer_id" => $question_answer->getID());
                }
                foreach ($question_input["attributes"]["item"] as $i => $item_text) {
                    $order = $i + 1;
                    $question_item = new Models_Exam_Question_Match(array(
                        "version_id" => $question_version->getID(),
                        "match_text" => $item_text,
                        "order" => $order,
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$question_item->insert()) {
                        add_error("Question $num: Error inserting matching item stem into database. Please try again later.");
                        $db->FailTrans();
                        $db->CompleteTrans();
                        return false;
                    }
                    $answer_id = $answer_ids[$question_input["attributes"]["answer"][$i]];
                    $question_correct = new Models_Exam_Question_Match_Correct(array(
                        "match_id" => $question_item->getID(),
                        "qanswer_id" => $answer_id["qanswer_id"],
                        "correct" => $answer_id["order"]
                    ));
                    if (!$question_correct->insert()) {
                        add_error("Question $num: Error inserting correct answer for matching into database. Please try again later.");
                        $db->FailTrans();
                        $db->CompleteTrans();
                        return false;
                    }
                }
                break;
            case "short":
            case "essay":
            case "text":
            default:
                // Do nothing, no extra details to add.
                break;
        }
        
        // Add the question author.
        if (isset($question_input["author_id"])) {
            $author_id = $question_input["author_id"];
        } else {
            $author_id = $ENTRADA_USER->getProxyID();
        }
        $question_author = new Models_Exam_Question_Authors(array(
            "question_id" => $question->getID(),
            "version_id" => $question_version->getID(),
            "author_type" => "proxy_id",
            "author_id" => $author_id,
            "created_date" => time(),
            "created_by" => $author_id
        ));
        if (!$question_author->insert()) {
            add_error("Question $num: Error inserting question author into database. Please try again later.");
        }
        
        // Add the question objectives
        if (isset($question_input["objectives"])) {
            foreach ($question_input["objectives"] as $objective_set_name => $objectives) {
                // Get curriculum tag set here, or create it if it does not exist
                $objective_set = null;
                $all_objective_sets = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), 0);
                if ($all_objective_sets) {
                    foreach ($all_objective_sets as $obj_set) {
                        if ($objective_set_name === $obj_set->getName()) {
                            $objective_set = $obj_set;
                            break;
                        }
                    }
                }
                if (!$objective_set) {
                    $objective_set = new Models_Objective(
                            null, // objective_id
                            "", // objective_code
                            $objective_set_name, // objective_name
                            "", // objective_description
                            0, // objective_parent
                            0, // objective_order
                            0, // objective_loggable
                            1, // objective_active
                            time(), // updated_date
                            $ENTRADA_USER->getProxyID() // updated_by
                    );
                    if (!$objective_set->insert()) {
                        add_error("Question $num: Error creating new curriculum tag set. Please try again later.");
                        $db->FailTrans();
                        $objective_set = null;
                    } else if (!$objective_set->insertOrganisationId($ENTRADA_USER->getActiveOrganisation())) {
                        add_error("Question $num: Error associating new curriculum tag set with the active organisation. Please try again later.");
                        $db->FailTrans();
                        $objective_set = null;
                    }
                }
                // Insert objectives into the curriculum tag set as children
                if ($objective_set) {
                    foreach ($objectives as $objective_name) {
                        // Get objective or create it if it does not exist
                        $objective = null;
                        $all_objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $objective_set->getID());
                        if ($all_objectives) {
                            foreach ($all_objectives as $obj) {
                                if ($objective_name === $obj->getName()) {
                                    $objective = $obj;
                                    break;
                                }
                            }
                        }
                        if (!$objective) {
                            $objective = new Models_Objective(
                                    null, // objective_id
                                    "", // objective_code
                                    $objective_name, // objective_name
                                    "", // objective_description
                                    $objective_set->getID(), // objective_parent
                                    0, // objective_order
                                    0, // objective_loggable
                                    1, // objective_active
                                    time(), // updated_date
                                    $ENTRADA_USER->getProxyID() // updated_by
                            );
                            if (!$objective->insert()) {
                                add_error("Question $num: Error creating new curriculum tag. Please try again later.");
                                $db->FailTrans();
                                $objective = null;
                            } else if (!$objective->insertOrganisationId($ENTRADA_USER->getActiveOrganisation())) {
                                add_error("Question $num: Error associating new curriculum tag with the active organisation. Please try again later.");
                                $db->FailTrans();
                                $objective = null;
                            }
                        }
                        // Attach objective to question
                        if ($objective) {
                            // check if objectives is already on the question.
                            $question_objective_exists = Models_Exam_Question_Objectives::fetchRowByQuestionIdObjectiveId($question->getID(), $objective->getID());

                            if ($question_objective_exists && is_object($question_objective_exists)) {
                                // item exists
                            } else {
                                // add new objective
                                $question_objective = new Models_Exam_Question_Objectives(array(
                                    "question_id"   => $question->getID(),
                                    "objective_id"  => $objective->getID(),
                                    "created_date"  => time(),
                                    "created_by"    => $ENTRADA_USER->getProxyID(),
                                    "updated_date"  => time(),
                                    "updated_by"    => $ENTRADA_USER->getProxyID()
                                ));
                                if (!$question_objective->insert()) {
                                    add_error("Question $num: Error adding an curriculum tag to the question. Please try again later.");
                                    $db->FailTrans();
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $db->CompleteTrans();
        return $question_version->getID();
    }
}
