<?php
/**Received*/

class Views_Exam_Question extends Views_Deprecated_Base {
    protected $default_fieldset = array(
        "question_id",
        "created_date",
        "created_by",
        "deleted_date"
    );

    protected $table_name           = "exam_questions";
    protected $primary_key          = "question_id";
    protected $default_sort_column  = "`exam_questions`.`question_id`";

    protected $joinable_tables          = array(
        "exam_question_versions" => array(
            "fields" => array(
                "version_id" => "version_id",
                "questiontype_id" => "questiontype_id",
                "version_count" => "version_count",
                "question_text" => "question_text",
                "question_description" => "question_description",
                "question_rationale" => "question_rationale",
                "question_code" => "question_code",
                "organisation_id" => "organisation_id",
                "created_date" => "created_date",
                "created_by" => "created_by",
                "updated_date" => "updated_date",
                "updated_by" => "updated_by",
            ),
            "join_conditions" => "`exam_questions`.`question_id` = `exam_question_versions`.`question_id`",
            "left" => false
        ),
        "exam_question_answers" => array(
            "fields" => array(
                "qanswer_id" => "qanswer_id",
                "answer_text" => "answer_text",
                "answer_rationale" => "answer_rationale",
                "correct" => "correct",
                "order" => "order",
                "updated_date" => "updated_date",
                "updated_by" => "updated_by"
            ),
            "join_conditions" => "`exam_questions`.`question_id` = `exam_question_answers`.`question_id`
                                   AND `exam_question_answers`.`deleted_date` IS NULL",
            "left" => true
        ),
        "exam_question_authors" => array(
            "fields" => array(
                "author_type" => "author_type",
                "author_id" => "author_id"
            ),
            "join_conditions" => "`exam_questions`.`question_id` = `exam_question_authors`.`question_id`
                                   AND `exam_question_authors`.`deleted_date` IS NULL",
            "left" => false
        ),
        "exam_question_objectives" => array(
            "fields" => array(
                "qobjective_id" => "qobjective_id",
                "objective_id" => "objective_id"
            ),
            "join_conditions" => "`exam_questions`.`question_id` = `exam_question_objectives`.`question_id`
                                    AND `exam_question_objectives`.`deleted_date` IS NULL",
            "left" => true
        ),
        "global_lu_objectives" => array(
            "fields" => array(
                "objective_id" => "objective_id"
            ),
            "join_conditions" => "`exam_question_objectives`.`objective_id` = `global_lu_objectives`.`objective_id`",
            "left" => false
        ),
        "exam_elements" => array(
            "fields" => array(
                "exam_id" => "exam_id",
                "element_text" => "element_text",
                "group_id" => "group_id",
                "element_order" => "order",
                "element_allow_comments" => "allow_comments"
            ),
            "join_conditions" => "`exam_elements`.`element_type` = 'question'
                                  AND `exam_questions`.`question_id` = `exam_elements`.`element_id`",
            "left" => true
        ),
        "exam_lu_questiontypes" => array(
            "fields" => array(
                "shortname" => "shortname",
                "question_type_name" => "name",
                "question_type_description" => "description"
            ),
            "join_conditions" => "`exam_question_versions`.`questiontype_id` = `exam_lu_questiontypes`.`questiontype_id`",
            "left" => false
        )
    );
    protected $question;
    protected $element;
    protected $view_data = array();
    protected $distribution_data = array();
    protected $response_answer, $progress, $post, $response, $allow_view, $type, $short_name, $display_style, $exam_mode, $echo_mode, $feedback, $highlight, $active_details, $randomize_answers;

    protected $exam_in_progress;

    public function __construct(Models_Exam_Question_Versions $question) {
        $this->question = $question;
    }

    public function fetchQuestionObjectives($question_id, $version_id) {
        $this->addTableJoins("exam_question_objectives");
        $this->addTableJoins("global_lu_objectives");
        $fieldset = $this->default_fieldset;
        $fieldset[] = "qobjective_id";
        $fieldset[] = "objective_id";
        $this->setFields($fieldset);
        $constraints = array(
            array("key" => "`".DATABASE_NAME."`.`exam_questions`.`question_id`", "value" => $question_id),
            array("key" => "`".DATABASE_NAME."`.`exam_question_objectives`.`version_id`", "value" => $version_id),
        );
        $results = $this->fetchAll($constraints);
        return $results;
    }

    public function createNewVersionCheck() {
        $question_version = $this->question;
        $exam_elements = Models_Exam_Exam_Element::fetchAllByElementIDElementType($question_version->getID(), "question");
        $update_current_version = true;

        // question version has been used on an exam, check if the exam's been taken yet.
        if (isset($exam_elements) && is_array($exam_elements)) {
            foreach ($exam_elements as $element) {
                // if the question has already been used then there should be an entry in Exam_Progress_Responses
                // we not checking if it's been answered yet as they might attempt to answer it while it's being updated.
                $exam_progress_response = Models_Exam_Progress_Responses::fetchAllByExamElementID($element->getExamElementID());

                if (isset($exam_progress_response) && is_array($exam_progress_response) && !empty($exam_progress_response)) {
                    $update_current_version = false;
                }
            }
        }

        return $update_current_version;
    }

    public function fetchCreatorPath($proxy_id) {
        $user = User::fetchRowByID($proxy_id, null, null, 1);
        if ($user) {
            $people_link = "<a href=\"" . ENTRADA_RELATIVE . "/people?id=" . $proxy_id . "\">";
            $people_link .= $user->getFullname();
            $people_link .= "</a>";
            return $people_link;
        }
    }

    private function buildHeader(
        Models_Exam_Question_Versions $question,
        $count,
        $exam_mode = false,
        array $control_array = NULL,
        $question_order_number = NULL,
        Models_Exam_Lu_Questiontypes $type = NULL,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        if (isset($this->element)) {
            $element = $this->element;
            $element_id = $element->getID();
            $element_order = ($element->getOrder()) + 1;
        }

        $post = ($progress) ? $progress->getExamPost(): NULL;
        $exam = ($post) ? $post->getExam() : NULL;
        $shortname = $this->short_name;

        if (isset($response) && is_object($response) && isset($post) && is_object($post) && isset($exam) && is_object($exam)) {
            $response_flag = $response->getFlagQuestion();
            $scratch_pad = $response->getLearnerComments();
            $highlight = $this->highlight;
        } else {
            $response_flag = NULL;
            $scratch_pad = NULL;
            $highlight = NULL;
        }

        $table_classes = "question-table table table-bordered " . str_replace("_", "-", $question->getQuestionType()->getShortname());

        if ($exam_mode === false) {
            $table_classes .= " admin-table";
        }
        if (isset($response_flag) && $response_flag == 1 ) {
            $table_classes .= " flagged";
        }

        if ($feedback_array["incorrect"] === 1) {
            $table_classes .= " incorrect-highlight";
        }

        $html = "<table class=\"" . $table_classes . "\" spellcheck=\"false\">";
        if ($exam_mode === false) {
            $html .= "  <tr class=\"type\">";
            $html .= "      <td colspan=\"" . $count . "\">";
            if (isset($element_id)) {
                $html .= "          <span class=\"question-number\" data-element-id=\"" . $element_id . "\">" . $element_order . ".</span>";
            }
            $html .= "      <span class=\"select-item select-question\">";
            $html .= "          <i class=\"select-item-icon question-icon-select fa fa-square-o\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i>";
            $html .= "      </span>";
            $html .= "          <span class=\"question-type\">ID. " . $question->getQuestionID() . " Ver. " . $question->getVersionCount() . "</span>";
            $html .= "          <div class=\"pull-right\">";
            $html .= $this->buildHeaderEditButtons($question, $control_array);
            $html .= "        </div>";
            $html .= "      </td>";
            $html .= "  </tr>";

            if ($this->element) {
                $html .= "  <tr class=\"heading\">";
                $html .= "      <td colspan=\"". $count ."\">";
                $points = (NULL === $this->element->getPoints()) ? 1 : $this->element->getPoints();

                $html .= "<div class=\"btn-group scoring\">
                                <div class=\"btn-group scoring-method\">
                                <button class=\"btn btn-primary dropdown-toggle scoring-method inline ".($this->element->isScored() ? "state-scored" : "state-not-scored")."\" data-toggle=\"dropdown\">
                                        ".($this->element->isScored() ? "Scored" : "Not Scored")."
                                         <span class=\"caret\"></span>
                                    </button>
                                    <ul class=\"dropdown-menu\">
                                        <li".($this->element->isScored() ? " class=\"active\"" : "")."><a class=\"scoring-option scored\" href=\"#\">Scored</a></li>
                                        <li".($this->element->isScored() ? "" : " class=\"active\"")."><a class=\"scoring-option not-scored\" href=\"#\">Not Scored</a></li>
                                    </ul>
                                </div>
                            <div class=\"btn-group\">
                                <div class=\"input-append\">
                                    <input class=\"points span6\" id=\"appendedInput\" type=\"text\" size=\"2\" name=\"points\" value=\"".$points."\">
                                    <span class=\"add-on\">pt(s)</span>
                                </div>
                            </div>
                          </div>";
                $html .= "      </td>";
                $html .= "  </tr>";
            }
        }


        $html .= "  <tr>";
        $html .= "      <td colspan=\"". $count ."\">";

        if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
            if ($shortname != "text") {
                $html .= "    <div class=\"exam-control-group btn-group pull-right\">";
                $html .= "        <span class=\"btn flag-question" . (isset($response_flag) && $response_flag == 1 ? " flagged" : "") ."\" alt=\"Flag question for follow-up\">";
                $html .= "            <i class=\"fa fa-flag\" data-version-id=\"" . $question->getVersionID() . "\" data-question-id=\"" . $question->getQuestionID() . "\"></i>";
                $html .= "        </span>";
                $html .= "        <span class=\"btn comment-question"  . ($scratch_pad ? " active" : "") . "\" alt=\"Add a comment for this question\">";
                $html .= "            <i class=\"fa fa-commenting\" data-version-id=\"" . $question->getVersionID() . "\" data-question-id=\"" . $question->getQuestionID() . "\"></i>";
                $html .= "        </span>";
                $html .= "    </div>";
            }
        }

        $html .= "        <div class=\"pull-left\" id=\"question_stem\">";
        $html .= "              <div class=\"question_number pull-left\">" . ($question_order_number ? $question_order_number . ". " : "") . "</div>";

        if ($this->allow_view) {
            switch($shortname) {
                case "fnb":
                    //build FNB question stem
                    $html .= $this->buildFnbHeader();
                    break;
                default:
                    $html .= "          <div class=\"question_text pull-left\">";
                    $html .= "              <span class=\"summernote_text\" data-type=\"question_text\" data-version-id=\"" . $question->getVersionID() . "\">";
                    if ($highlight) {
                        $html .= $highlight->getQuestionText();
                    } else {
                        $html .= $question->getQuestionText();
                    }
                    $html .= "            </span>";
                    $html .= "          </div>";
                    $html .= (isset($this->element) && !$this->element->isScored() ? " <span class=\"label label-info\">Not Scored</span>" : "");
                    break;
            }
        } else {
            $html .= "This question has already been viewed.";
        }
        $html .= "         </div>";
        $html .= "    </td>";
        $html .= "  </tr>";
        return $html;
    }

    public function buildFnbHeader() {
        $question           = $this->question;
        $answer_responses   = $this->compileAnswerChoiceResponses();
        $response           = $this->response;
        $progress           = $this->progress;
        $feedback_array     = $this->feedback;
        $response_color     = "";
        $html               = "";

        if (isset($this->element)) {
            $element_id     = $this->element->getID();
            $element        = $this->element;
            $element_order  = ($element->getOrder()) + 1;
        } else {
            $element_id     = NULL;
        }

        if (isset($progress)) {
            $exam_progress_id = $progress->getID();
            $proxy_id         = $progress->getProxyID();
        } else {
            $exam_progress_id = NULL;
            $proxy_id         = NULL;
        }

        $question_body = "";

        $question_text      = $question->getQuestionText();
        $question_text_array = explode("_?_", $question_text);
        if (isset($question_text_array) && is_array($question_text_array)) {
            $question_body .= "<div class=\"question_text pull-left\">";
            foreach ($question_text_array as $key => $question_part) {
                if ($question_part != "") {
                    $order = $key + 1;

                    // Shows the highlighted text if it was done
                    $question_body .= "    <span class=\"summernote_text\" data-type=\"fnb_text\" data-version-id=\"" . $question->getVersionID() . "\" data-order=\"" . $order . "\">";
                    $highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($exam_progress_id, $proxy_id, $question->getVersionID(), $order, "fnb_text");
                    if ($highlight) {
                        $question_body .= $highlight->getQuestionText();
                    } else {
                        $question_body .= $question_part;
                    }
                    $question_body .= "    </span>";

                    //get input element for the $order
                    $input = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question->getID(), $order);
                    if (isset($input) && is_object($input)) {
                        $input_view         = new Views_Exam_Question_Answer($input);
                        $qanswer_id         = $input->getID();

                        $data_answer_attr_array = array(
                            "question-id"   => $question->getQuestionID(),
                            "version-id"    => $question->getVersionID(),
                            "element-id"    => $element_id,
                            "type"          => "fnb",
                            "qanswer-id"    => $qanswer_id,
                            "answer-order"  => $order
                        );

                        if (isset($answer_responses) && is_array($answer_responses)) {
                            $response_value     = $answer_responses[$qanswer_id];
                        } else {
                            $response_value     = NULL;
                        }

                        if (isset($feedback_array) && $feedback_array["score"] == 1) {
                            $possible_correct   = $input_view->compileFnbArray();
                            $response_color     = $this->getResponseColorFNB($response_value, $possible_correct);
                            $response_value     = $order . ". " . $response_value;
                        }

                        $class = "question-control fnb_input" . $response_color;

                        $question_body .= "<input
                                class=\"" . $class . "\"" .
                            ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                type=\"text\"";
                        $question_body .= $this->renderDataArray($data_answer_attr_array);
                        $question_body .= "name=\"questionVersion[" . $question->getVersionID() ."]\"
                                value=\"" . html_encode($response_value) . "\"
                            />";
                    }
                }
            }
            $question_body .= "</div>";
        }

        return $question_body;
    }

    public function buildHeaderEditButtons(
        Models_Exam_Question_Versions $question,
        array $control_array = NULL,
        $view_type = "details"
    ) {
        global $ENTRADA_ACL;

        $related_versions = $question->fetchAllRelatedVersions();
        $groups = Models_Exam_Group_Question::fetchAllByVersionID($question->getVersionID());
        $edit = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($question->getVersionID(), true), "update");;

        $html = "";
        if (is_null($control_array)) {
            $html .= "      <div class=\"btn-group header-buttons\">";
            if ($edit === true) {
                $html .= "          <a href=\"" . ENTRADA_URL . "/admin/exams/questions?section=edit-question&id=" . $question->getQuestionID() . "&version_id=" . $question->getVersionID() . "\" title=\"Edit Question\" class=\"flat-btn btn edit-question\"><i class=\"fa fa-pencil\"></i></a>\n";
            }
            if ($view_type === "details") {
                $html .= "          <a href=\"#\" title=\"View Question Details\" class=\"flat-btn btn question-details" . ($this->active_details ? " active": "") . "\"><i class=\"fa fa-eye" . ($this->active_details ? " white-icon": "") . "\"></i></a>";
            } else {
                $html .= "          <a href=\"#\" title=\"View Question Preview\" class=\"flat-btn btn question-preview\"><i class=\"fa fa-search-plus\" data-version-id=\"" . $question->getVersionID() . "\"></i></a>";
            }

            $html .= !empty($groups) ? "           <a href=\"#\" title=\"View Linked Questions\" class=\"flat-btn btn btn-linked-question\"><i class=\"fa fa-link\"></i></a>" : "";
            //$html .= "          <a href=\"#\" title=\"Attach to Exam\" class=\"flat-btn btn attach-question\"><i class=\"fa fa-plus-circle\"></i></a>";
            if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {
                $html .= "          <a href=\"#\" title=\"Select Related Version\" class=\"flat-btn btn dropdown-toggle related-questions\" data-toggle=\"dropdown\"><i class=\"related-question-icon fa fa-exchange\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i></a>";
            }
            if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {
                $html .= self::renderRelatedBrowser($related_versions);
            }
            $html .= "      </div>";
        } else {
            if (is_array($control_array) && !empty($control_array)) {
                foreach ($control_array as $control_group) {
                    $html .= "<div class=\"btn-group header-buttons\">";
                    foreach ($control_group as $control) {
                        $html .= $control;
                    }
                    $html .= "</div>";
                }
            }
        }
        return $html;
    }

    public function buildExamHeaderEditButton(
        Models_Exam_Question_Versions $question,
        Models_Exam_Exam_Element $exam_element,
        $display_style = "details",
        $exam_id = false) {
        global $ENTRADA_ACL;

        $this->exam_in_progress = false;
        $exam                   = Models_Exam_Exam::fetchRowByID($exam_id);
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
        $exam_in_progress   = $this->exam_in_progress;

        $groups = Models_Exam_Group_Question::fetchAllByVersionID($question->getVersionID());
        $edit = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($exam_element->getElementID(), true), "update");
        $related_versions = $question->fetchAllRelatedVersions();
        $highest_version  = $question->checkHighestVersion(0);
        $control_group = array();
        if ($edit && !$exam_in_progress) {
            $control_group[]    = "<a class=\"flat-btn btn edit-item\" title=\"Edit Item\" href=\"" . ENTRADA_URL . "/admin/exams/questions?section=edit-question&id=" . $question->getQuestionID() . "&version_id=" . $exam_element->getElementID() . ($exam_id ? "&exam_id=" . $exam_id : "") . "\"><i class=\"fa fa-pencil\"></i></a>";
        }
        if ($display_style === "details") {
            $control_group[] = "<a class=\"flat-btn btn item-details\" title=\"View Question Details\" href=\"#\"><i class=\"fa fa-eye\"></i></a>";
        } else {
            $control_group[] = "<a class=\"flat-btn btn question-preview\" title=\"View Question Preview\" href=\"#\"><i class=\"fa fa-search-plus\" data-version-id=\"" . $question->getVersionID() . "\"></i></a>";
        }
        // If the question belongs to a group, but has not been added as a grouped question, display the "linked" button
        if (!empty($groups)) {
            $control_group[]    = "<a href=\"#\" title=\"View Linked Questions\" class=\"flat-btn btn btn-linked-question\"><i class=\"fa fa-link\"></i></a>";
        }
        if (!$exam_in_progress) {
            $control_group[]        = "<a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>";

            if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {
                $control_group[] = " <a href=\"#\" title=\"Select Related Version\" class=\"flat-btn btn dropdown-toggle related-questions" . ($highest_version ? "" : " updated-question-available") . "\" data-toggle=\"dropdown\"><i class=\"related-question-icon fa fa-exchange\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i></a>";
            }
            if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {
                $control_group[] = Views_Exam_Question::renderRelatedBrowser($related_versions);
            }
        }

        return array(
            $control_group
        );
    }

    public function buildDataAttrArray(Models_Exam_Question_Versions $question, Models_Exam_Exam_Element $exam_element) {
        $data_attr_array = array();
        $type = $question->getQuestionType();
        $data_attr_array["question-id"]         = $question->getQuestionID();
        $data_attr_array["version-count"]       = $question->getVersionCount();
        $data_attr_array["version-id"]          = $exam_element->getQuestionVersion()->getID();
        $data_attr_array["element-id"]          = $exam_element->getID();
        $data_attr_array["element-type"]        = ($type ? $type->getShortname() : $exam_element->getElementType());
        $data_attr_array["sortable-element-id"] = "element_" . $exam_element->getID();

        return $data_attr_array;
    }

    /**
     * @param ArrayObject|Models_Exam_Question_Versions[] $related_versions
     * @param string $type
     * @return string $html
     */
    public static function renderRelatedBrowser(array $related_versions, $type = "question") {
        if (isset($related_versions) && is_array($related_versions) && !empty($related_versions)) {

            $html = "<ul class=\"dropdown-menu pull-left related_versions_menu\">";
            $html .= "<li class=\"disabled\"><a tabindex=\"-1\" href=\"#\">Related Versions</a></li>";
            foreach ($related_versions as $version) {
                if ($version) {
                    $html .= "<li class=\"related_version_link\" data-version-id=\"" . $version->getVersionID() . "\" data-type=\"" . $type . "\">";
                    $html .= "    <a tabindex=\"-1\" href=\"#\">Ver: " . $version->getVersionCount() . " - Editor: " . $version->getUpdaterUser()->getFullname() . "</a>";
                    $html .= "</li>";
                }
            }
            $html .= "</ul>";
        }

        return $html;
    }

    public function renderQuestionDetails(Models_Exam_Lu_Questiontypes $type, $count) {
        $question = $this->question;
        $folder = Models_Exam_Bank_Folders::fetchRowByID($question->getFolderID());
        if (isset($folder) && is_object($folder)) {
            $folder_name = $folder->getFolderTitle();
            $folder_url = ENTRADA_URL . "/admin/exams/questions?folder_id=" . $question->getFolderID();
        } else {
            $folder_name = NULL;
        }

        $examSoftId = $question->getExamsoftId();

        $active = $this->active_details;

        $objectives = Views_Exam_Question_Objective::renderObjectives($question->getQuestionID(), true);
        $html = "  <tr class=\"question-detail-view" . ($active ? "" : " hide") . "\">";
        $html .= "      <td colspan=\"". $count ."\">";
        $html .= "          <div class=\"question-details-container\">";
        $html .= "              <h3>Question Details</h3>";
        $html .= "              <blockquote>";

        $html .= "                  <div class=\"row-fluid\">";
        $html .= "                      <span class=\"span2\">";
        $html .= "                          <h5>Type: </h5>";
        $html .= "                      </span>";
        $html .= "                      <span class=\"question-type span10\">". ($type->getName() ? $type->getName() : "N/A") ."</span>";
        $html .= "                  </div>";

        if ($question->getQuestionCode()) {
            $html .= "                  <div class=\"row-fluid\">";
            $html .= "                      <span class=\"span2\">";
            $html .= "                          <h5>Code: </h5>";
            $html .= "                      </span>";
            $html .= "                      <span class=\"question-code span10\">". ($question->getQuestionCode() ? html_encode($question->getQuestionCode()) : "N/A") ."</span>";
            $html .= "                  </div>";
        }

        if ($examSoftId) {
            $html .= "                  <div class=\"row-fluid\">";
            $html .= "                      <span class=\"span2\">";
            $html .= "                          <h5>ExamSoft ID: </h5>";
            $html .= "                      </span>";
            $html .= "                      <span class=\"question-examsoft-id span10\">". $examSoftId ."</span>";
            $html .= "                  </div>";
        }
        $html .= "                  <div class=\"row-fluid\">";
        $html .= "                      <span class=\"span2\">";
        $html .= "                          <h5>Description: </h5>";
        $html .= "                      </span>";
        $html .= "                      <span class=\"question-description span10\">". ($question->getQuestionDescription() ? html_encode($question->getQuestionDescription()) : "N/A") ."</span>";
        $html .= "                  </div>";

        $html .= "                  <div class=\"row-fluid\">";
        $html .= "                      <span class=\"span2\">";
        $html .= "                          <h5>Rationale: </h5>";
        $html .= "                      </span>";
        $html .= "                      <span class=\"question-rationale span10\">" . ($question->getRationale() ? $question->getRationale() : "N/A") . "</span>";
        $html .= "                  </div>";

        $html .= "                  <div class=\"row-fluid\">";
        $html .= "                      <span class=\"span2\">";
        $html .= "                          <h5>Folder: </h5>";
        $html .= "                      </span>";
        $html .= "                  <span class=\"question-folder span10\">" . ($question->getFolderID() ? "<a href=\"" . $folder_url . "\">" . $folder_name . "</a>" : "N/A") . "</span>";
        $html .= "                  </div>";
        $html .= "              </blockquote>";
        if ($objectives) {
            $html .= "            <h3>Curriculum Tags </h3>";
            $html .= "            <blockquote class=\"objective-blockquote\">";
            $html .=              $objectives;
            $html .= "            </blockquote>";
        }
        $html .= "              <ul>";
        $html .= "                  <li><p class=\"text-right creation-date\">Question was created on: " . date("Y-m-d", $question->getCreatedDate()) ." by " . $this->fetchCreatorPath($question->getCreatedBy()) . ($question->getUpdatedBy() > $question->getCreatedBy() ? " and updated on: " . date("Y-m-d", $question->getUpdatedDate()) . " by " . $this->fetchCreatorPath($question->getUpdatedBy()) : "" ) . " </p></li>";
        $html .= "              </ul>";
        $html .= "          </div>";
        $html .= "     </td>";
        $html .= "  </tr>";

        return $html;
    }

    public function renderListDetails(Models_Exam_Lu_Questiontypes $type, $count) {
        $question = $this->question;
        $folder = Models_Exam_Bank_Folders::fetchRowByID($question->getFolderID());
        if (isset($folder) && is_object($folder)) {
            $folder_name = $folder->getFolderTitle();
            $folder_url = ENTRADA_URL . "/admin/exams/questions?folder_id=" . $question->getFolderID();
        } else {
            $folder_name = NULL;
        }

        $objectives = Views_Exam_Question_Objective::renderObjectives($question->getQuestionID(), true);
        $html = "  <tr class=\"question-detail-view hide\">";
        $html .= "      <td colspan=\"". $count ."\">";
        $html .= "          <div class=\"question-details-container\">";
        $html .= "              <h3>Question Details</h3>";
        $html .= "              <blockquote>";
        $html .= "                  <h5>Type: <span class=\"question-type\">". ($type->getName() ? $type->getName() : "N/A") ."</span></h5>";
        $html .= "                  <h5>Code: <span class=\"question-code\">". ($question->getQuestionCode() ? html_encode($question->getQuestionCode()) : "N/A") ."</span></h5>";
        $html .= "                  <h5>Description: <span class=\"question-description\">". ($question->getQuestionDescription() ? html_encode($question->getQuestionDescription()) : "N/A") ."</span></h5>";
        $html .= "                  <h5>Rationale: <span class=\"question-rationale\">" . ($question->getRationale() ? html_encode($question->getRationale()) : "N/A") . "</span></h5>";
        $html .= "                  <h5>Folder: <span class=\"question-folder\">" . ($question->getFolderID() ? "<a href=\"" . $folder_url . "\">" . $folder_name . "</a>" : "N/A") . "</span></h5>";
        $html .= "              </blockquote>";
        if ($objectives) {
            $html .= "            <h3>Curriculum Tags </h3>";
            $html .= "            <blockquote class=\"objective-blockquote\">";
            $html .=              $objectives;
            $html .= "            </blockquote>";
        }
        $html .= "              <ul>";
        $html .= "                  <li><p class=\"text-right creation-date\">Question was created on: " . date("Y-m-d", $question->getCreatedDate()) ." by " . $this->fetchCreatorPath($question->getCreatedBy()) . ($question->getUpdatedBy() > $question->getCreatedBy() ? " and updated on: " . date("Y-m-d", $question->getUpdatedDate()) . " by " . $this->fetchCreatorPath($question->getUpdatedBy()) : "" ) . " </p></li>";
        $html .= "              </ul>";
        $html .= "          </div>";
        $html .= "     </td>";
        $html .= "  </tr>";

        return $html;
    }

    public function renderListDisplay(Models_Exam_Lu_Questiontypes $type, array $control_array = NULL, array $data_attr_array = NULL, $include_edit_buttons = true) {
        $question = $this->question;
        $element = $this->element;
        $colspan = 5;
        if ($element != NULL) {
            $element_order = ($element->getOrder()) + 1;
            $element_id = $element->getID();
            $colspan = 6;
        }

        $html = "<tr id=\"question-row-" . $question->getVersionID() ."\" class=\"exam-element question-row question\" data-version-id=\"" . $question->getVersionID() . "\"";
        $html .= $this->renderDataArray($data_attr_array);
        $html .= ">";
        $html .= "<td class=\"span1 text-center q-list-edit\">
                    <span class=\"select-item select-question\">
                        <i class=\"select-item-icon question-icon-select fa fa-square-o\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id = \"" . $question->getVersionID() . "\" data-version-count = \"" . $question->getVersionCount() . "\"></i>
                    </span>
                  </td>";
        if (isset($element_order)) {
            $html .= "    <td class=\"span2\">";
            $html .= "        <span class=\"question-number\" data-element-id=\"" . $element_id . "\">";
            $html .= "            <input class=\"question-number-update\" type=\"text\" data-element-id=\"" . $element_id . "\" value=\"" . $element_order . "\" name=\"order[]\" />";
            $html .= "        </span>";
            $html .= "    </td>";
        }
        $html .= "  <td class=\"span2\">
                        ID. " . $question->getQuestionID() . " Ver. " . $question->getVersionCount() . "
                    </td>
                    <td class=\"span5\">
                        " . ($question->getQuestionDescription() ? html_encode($question->getQuestionDescription()) : "N/A") . "
                    </td>
                    <td class=\"span3\">
                        " . date("m-d-Y g:i a", $question->getUpdatedDate()) . "
                    </td>";
        if (true === $include_edit_buttons) {
            $html .= "<td class=\"span1 text-center\">" .
                $this->buildHeaderEditButtons($question, $control_array, "list") . "
                    </td>";
        }
        $html .= "</tr>";

        return $html;
    }

    public function renderFnbCorrectArray($answer) {
        $answer_view = new Views_Exam_Question_Answer($answer);
        $possible_correct_array = $answer_view->compileFnbArray();
        $html = "";
        if (isset($possible_correct_array) && is_array($possible_correct_array)) {
            $html .= "<br />" . $answer->getOrder() . ": " . html_encode(implode(", ", $possible_correct_array));
        }

        return $html;
    }

    /**
     * @param $count
     * @param null $correct_answer
     * @return string
     */
    public function renderFeedback($count, $correct_answer = NULL) {
        $question       = $this->question;
        $short_name     = $this->short_name;
        $response       = $this->response;
        $exam_element   = $response->getElement();
        $feedback_array = $this->feedback;

        $html = "<tr class=\"feedback-report\">";
        $html .= "<td colspan=\"" . $count . "\">";
        $html .= "<div class=\"well\">";

        if ($exam_element->isScored()) {
            if (isset($feedback_array) && $feedback_array["score"] == 1) {
                $is_thrown_out = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($response->getExamElementID(), $response->getExamID(), "throw_out");
                if ($is_thrown_out) {
                    $html .= "<h5 style=\"color: red\">Thrown out</h5>\n";
                }
                $is_bonus = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($response->getExamElementID(), $response->getExamID(), "make_bonus");
                if ($is_bonus) {
                    $html .= "<h5 style=\"color: green\">Bonus question</h5>\n";
                }
                $is_full_credit = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($response->getExamElementID(), $response->getExamID(), "full_credit");
                if ($is_full_credit) {
                    $html .= "<h5 style=\"color: green\">Full credit</h5>\n";
                }

                $score = $response->getScore();
                $points = $is_bonus ? 0 : $exam_element->getAdjustedPoints();
                $html .= "<h5>Points: <span>" . ($score ? $score : 0) . " / " . $points . "</span></h5>";

                switch ($short_name) {
                    case "mc_v":
                    case "mc_v_m":
                    case "mc_h":
                    case "mc_h_m":
                        $correct_text = $question->getAdjustedMultipleChoiceCorrectText($exam_element->getID(), $response->getExamID());
                        $html .= "<h5>Correct Answer: <span>" . $correct_text . "</span></h5>";
                        break;
                    case "fnb":
                        $correct_text = "";
                        $answers = $question->getQuestionAnswers();
                        if (isset($answers) && is_array($answers) && !empty($answers)) {
                            foreach ($answers as $answer) {
                                $correct_text .= $this->renderFnbCorrectArray($answer);
                            }
                        }

                        $html .= "<h5>Correct Answer: <span>" . $correct_text . "</span></h5>";

                        break;
                    case "essay":
                    case "short":
                        $grader_date = $response->getGradedDate();
                        if (isset($grader_date) && $grader_date != 0) {
                            $grader_id = $response->getGradedBy();
                            if (isset($grader_id) && $grader_id > 0) {
                                $grader = User::fetchRowByID($grader_id, null, null, 1);
                                if ($grader) {
                                    $grader_name = $grader->getName();
                                } else {
                                    $grader_name = NULL;
                                }
                            } else {
                                $grader_name = NULL;
                            }
                            $comments = $response->getGraderComments();
                            $grader_date_formatted = "Question was graded on: " . date("m-d-Y", $response->getGradedDate()) . " by " . $grader_name;
                        } else {
                            $comments = "Not graded yet";
                            $grader_date_formatted = "N/A";
                        }
                        /*

                        // Correct Text wont be displayed to learners anymore on the feedback
                        $correct_text = $question->getCorrectText();

                        if (!$correct_text) {
                            $correct_text = "N/A";
                        }
                        $html .= "<h5>Correct Answer: <span>" . (html_encode($correct_text)) . "</span></h5>";

                        */
                        $html .= "<h5>Grader Comments: <span>" . html_encode($comments) . "</span></h5>";
                        $html .= "<h5>Grader: <span>" . $grader_date_formatted . "</span></h5>";
                        break;
                    case "match":
                        $match_stems = $question->getMatchStems();
                        $answers = $question->getQuestionAnswers();
                        $correct_a = array();
                        foreach ($match_stems as $mc) {
                            $correct_option = Models_Exam_Question_Match_Correct::fetchRowByMatchID($mc->getID());
                            $correct_order = $correct_option->getCorrect();
                            foreach ($answers as $a) {
                                if ($a->getOrder() == $correct_order) {
                                    $correct_a[] = (
                                        "<strong>" . $mc->getOrder() . ".</strong>" .
                                        html_encode($mc->getMatchText()) .
                                        "<br/><strong> - Correct match: </strong>" .
                                        html_encode($a->getAnswerText())
                                    );
                                }
                            }
                        }
                        $correct_a_text = implode("<br/>", $correct_a);
                        $html .= "<h5>Correct Answer(s): <span><br/>" . ($correct_a_text) . "</span></h5>";
                        break;
                    default:
                        $html .= "<h5>Correct Answer: <span>" . (html_encode($correct_answer)) . "</span></h5>";
                        break;
                }
            }

            if (isset($feedback_array) && $feedback_array["feedback"] == 1) {
                $html .= "<h5>Rationale: <span>" . ($question->getRationale() ? $question->getRationale() : "N/A") . "</span></h5>";
            }
        } else {
            $html .= "<h3 class=\"text-center\">This question was not scored</h3>";
        }
        $html .= "</div>";
        $html .= "</td>";
        $html .= "</tr>";
        return $html;
    }

    public function compileAnswerChoiceResponses() {
        $answer_responses           = array();
        $answer_choice_responses    = $this->response_answer;
        $short_name                 = $this->short_name;
        if (isset($answer_choice_responses) && is_array($answer_choice_responses) && !empty($answer_choice_responses)) {
            foreach ($answer_choice_responses as $response_answer) {
                if ($response_answer->getResponseValue()) {
                    switch ($short_name) {
                        case "fnb":
                            $answer_responses[$response_answer->getAnswerElementID()] = $response_answer->getResponseValue();
                            break;
                        case "match" :
                            $answer_responses[$response_answer->getMatchID()] = $response_answer->getResponseValue();
                            break;
                        case "essay":
                        case "short":
                            $answer_responses = $response_answer->getResponseValue();
                            break;
                        default:
                            $answer_responses[] = $response_answer->getAnswerElementID();
                            break;
                    }
                }
            }
        }
        return $answer_responses;
    }

    public function renderDataArray($data = array()) {
        $html = "";
        if (isset($data) && is_array($data) && !empty($data)) {
            foreach ($data as $key => $data_attr) {
                $html .= " data-" . $key . "=\"" . $data_attr . "\"";
            }
        }
        return $html;
    }

    /**
     * @param Models_Exam_Progress_Responses $response
     * @param Models_Exam_Question_Answers $answer
     * @return string
     */
    public function getResponseColor($response, $answer) {
        if ($response && is_object($response) && $answer && is_object($answer)) {
            $feedback_array = $this->feedback;
            $response_color = "";
            $answer_response = Models_Exam_Progress_Response_Answers::fetchRowByAnswerElement($response->getID(), $answer->getID());
            $adjustment     = Models_Exam_Adjustment::fetchRowByElementIDExamIDValue($response->getExamElementID(), $response->getExamID(), $answer->getID());

            if (!$adjustment) {
                if (isset($answer_response) && is_object($answer_response) && $answer_response->getResponseValue() == 1) {
                    if ($answer->getAdjustedCorrect($response->getExamElementID(), $response->getPostID()) === 1) {
                        $response_color = " answer-correct";
                    } else {
                        $response_color = " answer-incorrect";
                    }
                } else {
                    //they didn't click this option
                    if ($answer->getAdjustedCorrect($response->getExamElementID(), $response->getPostID()) === 1) {
                        $response_color = " answer-correct";
                    }
                }
            } else {
                // answer was adjusted after the exam
                if ($adjustment && is_object($adjustment)) {
                    switch ($adjustment->getType()) {
                        case "correct":
                            $response_color = " answer-correct";
                            break;
                        case "incorrect":
                            $response_color = " answer-incorrect";
                            break;
                    }
                }
            }

            if ($feedback_array["incorrect"] === 1 && $response_color === " answer-correct") {
                $response_color = "";
            }

            return $response_color;
        }
        return false;
    }

    public function getResponseColorFNB($response_value, $possible_correct) {
        $feedback_array = $this->feedback;
        $response_color = "";
        if (isset($possible_correct) && is_array($possible_correct) && !empty($possible_correct)) {
            if (!in_array($response_value, $possible_correct)) {
                $response_color = " answer-incorrect";
            } else {
                $response_color = " answer-correct";
            }
        }

        if ($feedback_array["incorrect"] === 1 && $response_color === " answer-correct") {
            $response_color = "";
        }

        return $response_color;
    }

    public function getResponseColorMatching(Models_Exam_Question_Answers $answer, Models_Exam_Question_Match_Correct $match_correct) {
        $feedback_array = $this->feedback;

        if (isset($match_correct) && is_object($match_correct)) {
            $match_correct_value = (int)$match_correct->getCorrect();
        } else {
            $match_correct_value = NULL;
        }

        if (isset($answer) && is_object($answer)) {
            $response_order     = (int)$answer->getOrder();
        } else {
            $response_order     = NULL;
        }

        if (isset($match_correct_value) && !empty($match_correct_value)) {
            if ($match_correct_value === $response_order) {
                $response_color = " answer-correct";
            } else {
                $response_color = " answer-incorrect";
            }
        }

        if ($feedback_array["incorrect"] === 1 && $response_color === " answer-correct") {
            $response_color = "";
        }

        return $response_color;
    }

    public function renderLearnerComments($count) {
        global $translate;
        $question       = $this->question;
        $response       = $this->response;
        $element        = $this->element;
        $post           = $this->post;
        $comment_true   = 0;
        $checked_true   = 0;
        $review         = 0;

        $exam_text      = $translate->_("exams");
        $module_text    = $exam_text["attempt"];
        $scratch_pad    = $module_text["title_scratch_pad"];

        if ($element) {
            $element_id = $element->getID();
        }
        if ($response) {
            $response_id = $response->getID();
            $comments    = $response->getLearnerComments();
            $checked     = $response->getMarkFacultyReview();

            if (isset($comments) && $comments != "") {
                $comment_true = 1;
            }

            if (isset($checked) && $checked == 1) {
                $checked_true = 1;
            }
        }

        if ($post && $post->getAllowFeedback() == 1) {
            $review = 1;
        }

        $html = "<tr class=\"learner_comments" . ($checked_true || $comment_true ? " active" : "") . "\" data-version-id=\"" . $question->getVersionID() . "\">";
        $html .= "    <td colspan=\"". $count ."\">";
        $html .= "        <table>";
        $html .= "            <tr>";
        $html .= "                <td colspan=\"". $count ."\">";
        $html .= "                    <h3 class=\"title\">" . $scratch_pad . "</h3>";
        $html .= "                </td>";
        $html .= "            </tr>";
        $html .= "            <tr>";
        $html .= "                <td colspan=\"". $count ."\">";
        $html .= "                    <textarea class=\"learner_comments_text_area\" data-element-id=\"" . $element_id . "\" data-type=\"learner_comments\">" . $comments . "</textarea>";
        $html .= "                </td>";
        $html .= "            </tr>";
        if ($review) {
            $html .= "            <tr>";
            $html .= "                <td colspan=\"". $count ."\" class=\"learner_comments_checkbox\">";
            $html .= "                    <span class=\"btn learner_comments_mark_faculty_review" . ($checked ? " selected" : "") . "\">";
            $html .= "                        <i class=\"fa fa-2x"  . ($checked ? " fa-check-square-o" : " fa-square-o") . " \" data-element-id=\"" . $element_id . "\" data-response-id=\"" . $response_id . "\" data-question-id=\"" . $question->getQuestionID() . "\" data-version-id=\"" . $question->getVersionID() . "\" data-version-count=\"" . $question->getVersionCount() . "\"></i>";
            $html .= "                    </span>";
            $html .= "                    <span class=\"learner_comments_text\">";
            $html .= "                        Mark question for faculty review";
            $html .= "                    </span>";
            $html .= "                </td>";
            $html .= "            </tr>";
        }
        $html .= "        </table>";
        $html .= "    </td>";
        $html .= "</tr>";

        return $html;
    }

    public function renderStrikeOutButton($strike_out_class) {
        $html = "        <span class=\"span1\">\n";
        $html .= "            <button class=\"btn strikeout-choice" . $strike_out_class . "\">\n";
        $html .= "                <i class=\"fa fa-strikethrough \"></i>\n";
        $html .= "            </button>\n";
        $html .= "        </span>\n";
        return $html;
    }


    /**
     *  Inserts an intem into the array at the specified index
     *
     * @param array      $array
     * @param int|string $position
     * @param mixed      $insert
     */
    function array_insert(&$array, $position, $insert)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos   = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                array($insert),
                array_slice($array, $pos)
            );
        }
    }

    /**
     *  Randomizes the order of answers if they aren't locked at a position
     *
     * @param array $answers
     * @param int|string $question_id
     * @param int|string $progress_id
     *
     * @return array
     * */
    public function randomizeAnswers($answers, $question_id, $progress_id) {
        /*
         *  Randomized answers should be in the same order they were loaded at the first time
         *  So if the student Resume or go back one question the answers should stay in the same order.
         *  To implement that I will use cookies to store the order of these answers
         * */
        $cookie_key = "ANSWER_ORDER_" . $question_id . "_" . $progress_id;
        $final_answers = array();
        if (isset($_COOKIE[$cookie_key])) {
            $answers_generated_orders = json_decode($_COOKIE[$cookie_key]);
            foreach ($answers_generated_orders as $answer_id) {
                foreach ($answers as $answer) {
                    if ($answer->getID() == $answer_id) {
                        array_push($final_answers, $answer);
                    }
                }
            }
        } else {
            //First lets separate the locked answers
            $locked_answers = array();
            foreach ($answers as $answer) {
                if ($answer->getLocked()) {
                    array_push($locked_answers, $answer);
                }
            }
            //Get the unlocked answers from the original array and the shuffle them
            foreach ($answers as $answer) {
                if (!$answer->getLocked()) {
                    array_push($final_answers, $answer);
                }
            }
            shuffle($final_answers); //Here not locked answers will be randomized
            // Now, add the locked answers back at their right position on the randomized array
            foreach ($locked_answers as $locked_answer) {
                $this->array_insert($final_answers, $locked_answer->getOrder(), $locked_answer);
            }
            //I need to reverse the array as well to keep the right position on the exam view
            $final_answers =  array_reverse($final_answers);

            //Now lets store the randomized answers in cookies
            $answers_generated_orders = array();
            foreach ($final_answers as $final_answer) {
                array_push($answers_generated_orders, $final_answer->getID());
            }
            setcookie($cookie_key, json_encode($answers_generated_orders), time() + 43200);
        }
        return $final_answers;
    }


    public function renderHorizontalChoiceSingleAnswer(
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $answers = $question->getQuestionAnswers();
            $count = count($answers);
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
            } else {
                $answer_responses = array();
                $response_flag  = NULL;
                $elements_strike_out = NULL;
            }

            if ($progress) {
                $exam_progress_id = $progress->getID();
                $proxy_id = $progress->getProxyID();
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-horizontal-choice-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            if ($answers) {
                $alphas = range("A", "Z");
                $column_width = (100 / $count);
                $correct_answer = $question->getCorrectQuestionAnswer();
                $correct_o_r    = $this->correct;
                $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);
                if ($this->allow_view == 1) {
                    $html .= "  <tr class=\"horizontal-answer-input question-answer-view\">";
                    $answer_count = 0;

                    if ($this->randomize_answers && $exam_mode && empty($feedback_array)) {
                        $answers = $this->randomizeAnswers($answers, $question->getID(), $progress->getID());
                    }

                    foreach ($answers as $answer) {
                        $response_color = "";
                        if (isset($feedback_array) && $feedback_array["score"] == 1 && $this->element->isScored()) {
                            $response_color = $this->getResponseColor($response, $answer);
                        } elseif ($correct_o_r && is_array($correct_o_r) && !empty($correct_o_r)) {
                            // $correct_o_r == $answer->getID()
                            foreach ($correct_o_r as $id => $value) {
                                if ($id == $answer->getID()) {
                                    if ($value == "correct") {
                                        $response_color = " answer-correct";
                                    } elseif ($value == "incorrect") {
                                        $response_color = " answer-incorrect";
                                    }
                                }
                            }
                        }

                        $data_answer_attr_array = array(
                            "question-id"   => $question->getQuestionID(),
                            "version-id"    => $question->getVersionID(),
                            "element-id"    => $element_id,
                            "type"          => $type->getShortname(),
                            "qanswer-id"    => $answer->getID(),
                            "answer-order"  => $answer->getOrder(),
                            "answer-letter" => $alphas[$answer_count]
                        );

                        $html .= "<td class=\"" . $response_color . "\" width=\"" . $column_width . "%\">
                                <span class=\"question-letter\">" . $alphas[$answer_count] .".</span>
                                <input type=\"radio\"
                                    class=\"question-control\"" .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                    id=\"question-" . $question->getQuestionID() . "-answer-" . $answer->getID() . "\"";
                        $html .= $this->renderDataArray($data_answer_attr_array);
                        $html .= " name=\"question[" . $question->getQuestionID() . "\" value=\"" . $answer->getID() . "\"
                                    " . ((in_array($answer->getID(), $answer_responses) || ($exam_mode == false && $answer->getCorrect() == 1)) ? " checked=\"checked\"" : "") . "
                                 />
                            </td>";
                        $answer_count++;
                    }
                    $html .= "  </tr>";
                    $html .= "  <tr class=\"horizontal-answer-label question-answer-view\">";

                    $answer_count = 0;
                    foreach ($answers as $answer) {
                        $highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($exam_progress_id, $proxy_id, $question->getVersionID(), $answer->getOrder(), "answer_text");

                        $response_color = "";
                        if (isset($feedback_array) && $feedback_array["score"] == 1 && $this->element->isScored()) {
                            $response_color = $this->getResponseColor($response, $answer);
                        } elseif ($correct_o_r && is_array($correct_o_r) && !empty($correct_o_r)) {
                            // $correct_o_r == $answer->getID()
                            foreach ($correct_o_r as $id => $value) {
                                if ($id == $answer->getID()) {
                                    if ($value == "correct") {
                                        $response_color = " answer-correct";
                                    } elseif ($value == "incorrect") {
                                        $response_color = " answer-incorrect";
                                    }
                                }
                            }
                        }

                        $html .= "  <td class=\"" . $response_color . "\"  width=\"". $column_width ."%\">";
                        $html .= "  <label for=\"question-".$question->getQuestionID()."-answer-".$answer->getID()."\">";
                        $html .= "          <span class=\"summernote_text\" data-type=\"answer_text\" data-version-id=\"" . $question->getVersionID() . "\" data-order=\"" . $answer->getOrder() . "\">";
                        if ($highlight) {
                            $html .=              $highlight->getQuestionText();
                        } else {
                            $html .=              $answer->getAnswerText();
                        }
                        $html .= "            </span>";
                        $html .= "    </label>";
                        $html .= "</td>";
                        $answer_count++;
                    }
                }
            } else {
                $html .= "<tr><td colspan=\"". $count ."\">There are no answers...</td></tr>";
            }

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback($count, $correct_answer);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderVerticalChoiceSingleAnswer(
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;

        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $answers = $question->getQuestionAnswers();
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;
            $elements_strike_out = NULL;

            if (isset($response) && is_object($response)) {
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                if (!$feedback_array) {
                    $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
                }
            } else {
                $answer_responses = array();
            }

            if ($progress) {
                $exam_progress_id = $progress->getID();
                $proxy_id = $progress->getProxyID();
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id"  => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-vertical-choice-question" . (isset($this->element) && !$this->element->isScored() ? " not-scored": "") . "\" id=\"" . $question->getVersionID() . "\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            if ($answers) {
                $alphas         = range("A", "Z");
                $count          = count($answers);
                $correct_answer = $question->getCorrectQuestionAnswer();
                $correct_o_r    = $this->correct;

                $html .= $this->buildHeader($question, 3, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

                $answer_count = 0;
                if ($this->allow_view == 1) {

                    if ($this->randomize_answers && $exam_mode && empty($feedback_array)) {
                        $answers = $this->randomizeAnswers($answers, $question->getID(), $progress->getID());
                    }

                    foreach ($answers as $answer) {
                        $highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($exam_progress_id, $proxy_id, $question->getVersionID(), $answer->getOrder(), "answer_text");

                        if (isset($elements_strike_out[$answer->getID()]) && $elements_strike_out[$answer->getID()] == 1) {
                            $strike_out_class = " strike";
                        } else {
                            $strike_out_class = "";
                        }

                        $response_color = "";
                        if (isset($feedback_array) && $feedback_array["score"] == 1) {
                            $response_color = $this->getResponseColor($response, $answer);
                        } elseif ($correct_o_r && is_array($correct_o_r) && !empty($correct_o_r)) {
                            // $correct_o_r == $answer->getID()
                            foreach ($correct_o_r as $id => $value) {
                                if ($id == $answer->getID()) {
                                    if ($value == "correct") {
                                        $response_color = " answer-correct";
                                    } elseif ($value == "incorrect") {
                                        $response_color = " answer-incorrect";
                                    }
                                }
                            }
                        }

                        $row_class = "question-answer-view" . $strike_out_class . $response_color;

                        $data_answer_attr_array = array(
                            "question-id"   => $question->getQuestionID(),
                            "version-id"    => $question->getVersionID(),
                            "element-id"    => $element_id,
                            "type"          => $type->getShortname(),
                            "qanswer-id"    => $answer->getID(),
                            "answer-order"  => $answer->getOrder(),
                            "answer-letter" => $alphas[$answer_count]
                        );

                        $html .= "<tr class=\"" . $row_class ."\" data-element-id=\"" . $element_id . "\" data-qanswer-id=\"" . $answer->getID() . "\">
                                    <td class=\"vertical-answer-input row-fluid\">
                                        <span class=\"space-right\">
                                            <span class=\"question-letter\">
                                            <label for=\"question-" . $question->getQuestionID() . "-answer-" . $answer->getID() . "\">" . $alphas[$answer_count] . ". </label></span>
                                            <input type=\"radio\"
                                                class=\"question-control\" " .
                                                 ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                                id=\"question-" . $question->getQuestionID() . "-answer-" . $answer->getID() . "\"";
                                                $html .= $this->renderDataArray($data_answer_attr_array);
                                                $html .= " name=\"question-" . $question->getQuestionID() . "\"
                                                value=\"" . $answer->getID() . "\" " .
                                            ((in_array($answer->getID(), $answer_responses) || ($exam_mode == false && $answer->getCorrect() == 1)) ? " checked=\"checked\"" : "") . "
                                        />
                                        </span>";
                        $html .= "        <span class=\"space-left\">";
                        $html .= "        <label for=\"question-" . $question->getQuestionID() . "-answer-" . $answer->getID()."\">";
                        $html .= "          <span class=\"summernote_text\" data-type=\"answer_text\" data-version-id=\"" . $question->getVersionID() . "\" data-order=\"" . $answer->getOrder() . "\">";
                        if ($highlight) {
                            $html .=              $highlight->getQuestionText();
                        } else {
                            $html .=              $answer->getAnswerText();
                        }
                        $html .= "                </span>\n";
                        $html .= "            </label>\n";
                        $html .= "        </span>\n";

                        if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                            $html .= $this->renderStrikeOutButton($strike_out_class);
                        }

                        $html .= "    </td>\n";
                        $html .= "</tr>\n";
                        $answer_count++;
                    }
                }
            }

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, 2);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback(2, $correct_answer);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderHorizontalChoiceMultipleAnswer(
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $answers = $question->getQuestionAnswers();
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
            } else {
                $answer_responses = array();
                $response_flag  = NULL;
                $elements_strike_out = NULL;
            }

            if ($progress) {
                $exam_progress_id = $progress->getID();
                $proxy_id = $progress->getProxyID();
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-horizontal-choice-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            if ($answers) {
                $alphas = range("A", "Z");
                $count = count($answers);
                $correct_answer = $question->getCorrectQuestionAnswer();
                $correct_o_r    = $this->correct;
                $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

                $column_width = (100 / $count);
                $answer_count = 0;

                if ($this->allow_view == 1) {
                    $html .= "  <tr class=\"horizontal-answer-input question-answer-view\">";

                    if ($this->randomize_answers && $exam_mode && empty($feedback_array)) {
                        $answers = $this->randomizeAnswers($answers, $question->getID(), $progress->getID());
                    }

                    foreach ($answers as $answer) {
                        $response_color = "";
                        if (isset($feedback_array) && $feedback_array["score"] == 1 && $this->element->isScored()) {
                            $response_color = $this->getResponseColor($response, $answer);
                        } elseif ($correct_o_r && is_array($correct_o_r) && !empty($correct_o_r)) {
                            // $correct_o_r == $answer->getID()
                            foreach ($correct_o_r as $id => $value) {
                                if ($id == $answer->getID()) {
                                    if ($value == "correct") {
                                        $response_color = " answer-correct";
                                    } elseif ($value == "incorrect") {
                                        $response_color = " answer-incorrect";
                                    }
                                }
                            }
                        }

                        $data_answer_attr_array = array(
                            "question-id"   => $question->getQuestionID(),
                            "version-id"    => $question->getVersionID(),
                            "element-id"    => $element_id,
                            "type"          => $type->getShortname(),
                            "qanswer-id"    => $answer->getID(),
                            "answer-order"  => $answer->getOrder(),
                            "answer-letter" => $alphas[$answer_count]
                        );

                        $html .= "<td class=\"" . $response_color . "\" width=\"" . $column_width . "%\">
                                <span class=\"question-letter\">
                                    <label for=\"question-" . $question->getQuestionID() . "-answer-" . $answer->getID()."\">". $alphas[$answer_count] .". </label>
                                </span>
                                <input
                                    type=\"checkbox\"
                                    class=\"question-control\" " .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                    id=\"question-" . $question->getQuestionID() . "-answer-" . $answer->getID() . "\"";
                            $html .= $this->renderDataArray($data_answer_attr_array);
                            $html .= " name=\"question-" . $question->getQuestionID() . "\"
                                    value=\"" . $answer->getID() . "\"".(in_array($answer->getID(), $answer_responses) || ($exam_mode == false && $answer->getCorrect() == 1 && $feedback_array["incorrect"] === 0) ? " CHECKED" : "")."
                                />
                            </td>";
                        $answer_count++;
                    }
                    $html .= "  </tr>";
                    $html .= "  <tr class=\"horizontal-answer-label question-answer-view\">";

                    $answer_count = 0;
                    foreach ($answers as $answer) {
                        $highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($exam_progress_id, $proxy_id, $question->getVersionID(), $answer->getOrder(), "answer_text");

                        $response_color = "";
                        if (isset($feedback_array) && $feedback_array["score"] == 1 && $this->element->isScored()) {
                            $response_color = $this->getResponseColor($response, $answer);
                        } elseif ($correct_o_r && is_array($correct_o_r) && !empty($correct_o_r)) {
                            // $correct_o_r == $answer->getID()
                            foreach ($correct_o_r as $id => $value) {
                                if ($id == $answer->getID()) {
                                    if ($value == "correct") {
                                        $response_color = " answer-correct";
                                    } elseif ($value == "incorrect") {
                                        $response_color = " answer-incorrect";
                                    }
                                }
                            }
                        }

                        $html .= "<td class=\"" . $response_color . "\" width=\"". $column_width ."%\">";
                        $html .= "      <label for=\"question-".$question->getQuestionID()."-answer-".$answer->getID()."\">";
                        $html .= "          <span class=\"summernote_text\" data-type=\"answer_text\" data-version-id=\"" . $question->getVersionID() . "\" data-order=\"" . $answer->getOrder() . "\">";
                        if ($highlight) {
                            $html .=              $highlight->getQuestionText();
                        } else {
                            $html .=              $answer->getAnswerText();
                        }
                        $html .= "          </span>";
                        $html .= "      </label>";
                        $html .= "</td>";
                        $answer_count++;
                    }
                    $html .= "  </tr>";
                }
            }
            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback($count, $correct_answer);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderVerticalChoiceMultipleAnswer (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $answers = $question->getQuestionAnswers();
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;
            $elements_strike_out    = NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                if (!$feedback_array) {
                    $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
                }
            } else {
                $answer_responses = array();
                $response_flag          = NULL;
            }

            if ($progress) {
                $exam_progress_id = $progress->getID();
                $proxy_id = $progress->getProxyID();
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $div_class = "exam-element exam-question question exam-vertical-choice-question";
            if (isset($this->element) && !$this->element->isScored()) {
                $div_class .= " not-scored";
            }

            $html = "<div class=\"" . $div_class . "\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            $html .= $this->buildHeader($question, 3, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);
            if ($answers && $this->allow_view == 1) {
                $alphas = range("A", "Z");
                $count = count($answers);
                $answer_count = 0;
                $correct_o_r    = $this->correct;

                $correct_answers = explode(", ", $question->getCorrectQuestionAnswer());

                if ($this->randomize_answers && $exam_mode && empty($feedback_array)) {
                    $answers = $this->randomizeAnswers($answers, $question->getID(), $progress->getID());
                }

                foreach ($answers as $answer) {

                    $highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($exam_progress_id, $proxy_id, $question->getVersionID(), $answer->getOrder(), "answer_text");

                    $answer_id = $answer->getID();

                    if (isset($elements_strike_out[$answer_id]) && $elements_strike_out[$answer_id] == 1) {
                        $strike_out_class = " strike";
                    } else {
                        $strike_out_class = "";
                    }

                    $response_color = "";
                    if (isset($feedback_array) && $feedback_array["score"] == 1 && $this->element->isScored()) {
                        $response_color = $this->getResponseColor($response, $answer);
                    } elseif ($correct_o_r && is_array($correct_o_r) && !empty($correct_o_r)) {
                        // $correct_o_r == $answer->getID()
                        foreach ($correct_o_r as $id => $value) {
                            if ($id == $answer->getID()) {
                                if ($value == "correct") {
                                    $response_color = " answer-correct";
                                } elseif ($value == "incorrect") {
                                    $response_color = " answer-incorrect";
                                }
                            }
                        }
                    }

                    if ($feedback_array["incorrect"] === 1) {
                        $checked = "";
                        $response_color = "";
                    } else if (in_array($answer->getID(), $answer_responses) || ($exam_mode == false && $answer->getCorrect() == 1 && $feedback_array["incorrect"] != 1)) {
                        $checked = " CHECKED";
                    } else {
                        $checked = "";
                    }

                    $row_class = "question-answer-view" . $strike_out_class . $response_color;

                    $data_answer_attr_array = array(
                        "question-id"   => $question->getQuestionID(),
                        "version-id"    => $question->getVersionID(),
                        "element-id"    => $element_id,
                        "type"          => $type->getShortname(),
                        "qanswer-id"    => $answer->getID(),
                        "answer-order"  => $answer->getOrder(),
                        "answer-letter" => $alphas[$answer_count]
                    );

                    $html .= "<tr class=\"" . $row_class . "\" data-element-id=\"" . $element_id . "\" data-qanswer-id=\"" . $answer->getID() . "\">
                                <td class=\"vertical-answer-input row-fluid\">
                                    <span class=\"space-right\">
                                        <span class=\"question-letter\">
                                            <label for=\"question-" . $question->getQuestionID() . "-answer-" . $answer_id."\">" .
                                                $alphas[$answer_count] .".
                                            </label>
                                        </span>
                                        <input type=\"checkbox\"
                                            class=\"question-control\"" .
                                            ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                            id=\"question-" . $question->getQuestionID() . "-answer-" . $answer_id . "\"";
                                $html .= $this->renderDataArray($data_answer_attr_array);
                                $html .= " name=\"question[" . $question->getVersionID() . "]\"
                                            value=\"" . $answer_id . "\" " . $checked . "
                                        />
                                    </span>\n";
                    $html .= "     <span class=\"space-left\">";
                    $html .= "     <label for=\"question-".$question->getQuestionID()."-answer-".$answer_id."\">";
                    $html .= "          <span class=\"summernote_text\" data-type=\"answer_text\" data-version-id=\"" . $question->getVersionID() . "\" data-order=\"" . $answer->getOrder() . "\">";
                    if ($highlight) {
                        $html .=              $highlight->getQuestionText();
                    } else {
                        $html .=              $answer->getAnswerText();
                    }
                    $html .= "          </span>";
                    $html .= "        </label>";
                    $html .= "      </span>\n";

                    if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                        $html .= $this->renderStrikeOutButton($strike_out_class);
                    }
                    
                    $html .= "    </td>\n";
                    $html .= "</tr>";
                    $answer_count++;
                }
            }

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback($count);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderDropDownSingleAnswer  (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $answers = $question->getQuestionAnswers();
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
            } else {
                $answer_responses = array();
                $response_flag  = NULL;
                $elements_strike_out = NULL;
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-dropdown-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            if ($answers) {
                $count = count($question);
                $correct_answer = $question->getCorrectQuestionAnswer();
                $correct_o_r    = $this->correct;
                $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

                if ($this->allow_view == 1) {
                    $html .= "<tr class=\"question-answer-view\">
                            <td class=\"question-type-control\">";

                    $html .= "      <select
                                    id=\"question-".$question->getQuestionID()."\"
                                    name=\"questionVersion[" . $question->getVersionID() . "]\"
                                    data-element-id=\"" . $element_id . "\" data-type=\"" . $type->getShortname() . "\"
                                    class=\"question-control\" " .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                >";

                    foreach ($answers as $answer) {
                        $html .= "<option value=\"" . $answer->getID() . "\"" . (in_array($answer->getID(), $answer_responses) ? " SELECTED" : "") . ">" . $answer->getAnswerText() . "</option>";
                    }
                    $html .= "      </select>
                                 </td>
                            </tr>";
                }
            } else {
                if ($this->allow_view == 1) {
                    $html .= "<tr><td>This question has no answers</td></tr>";
                }
            }
            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback($count, $correct_answer);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderDropDownMultipleAnswer (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $answers = $question->getQuestionAnswers();
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
            } else {
                $answer_responses = array();
                $response_flag  = NULL;
                $elements_strike_out = NULL;
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }
            $html = "<div class=\"exam-element exam-question question exam-dropdown-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            if ($answers) {
                $alphas = range("A", "Z");
                $count = 1;
                $correct_answer = $question->getCorrectQuestionAnswer();
                $correct_o_r    = $this->correct;
                $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);
                if ($this->allow_view == 1) {
                    $html .= "<tr class=\"question-answer-view\">
                             <td class=\"question-type-control\">";
                    $html .= "      <select
                                    class=\"form-control question-control\"" .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                    multiple size=\"10\"
                                    data-element-id=\"" . $element_id . "\" data-type=\"" . $type->getShortname() . "\"
                                    name=\"questionVersion[" . $question->getVersionID() ."]\"
                                >";
                            foreach ($answers as $answer) {
                                $html .= "<option value=\"" . $answer->getID() . "\"" . (in_array($answer->getID(), $answer_responses) ? " SELECTED" : "") . ">" . $answer->getAnswerText() . "</option>";
                            }
                    $html .= "      </select>
                                </td>
                            </tr>";
                }

            }
            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback($count, $correct_answer);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderText (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            if (isset($response) && is_object($response)) {
                $element_id             = $response->getExamElementID();
            }

            if (!isset($data_attr_array)) {
                $data_attr_array = array();
            }

            if (isset($element_id)) {
                $data_attr_array["element-id"] = $element_id;
            }

            $data_attr_array["question-id"] = $question->getQuestionID();
            $data_attr_array["version-id"]  = $question->getVersionID();

            $html = "<div class=\"exam-element exam-question question exam-instructionText".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            $count = 1;
            $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, NULL, $type, $progress, $response, $feedback_array);

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }
            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderShortAnswer (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
            } else {
                $answer_responses = NULL;
                $response_flag  = NULL;
                $elements_strike_out = NULL;
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-short-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            $count = 1;
            $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

            if ($this->allow_view == 1) {
                $html .= "    <tr class=\"question-answer-view\">";
                $html .= "        <td class=\"question-type-control\">";
                $html .= "          <input
                                    class=\"question-control\"" .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                    type=\"text\"
                                    data-element-id=\"" . $element_id . "\" data-type=\"" . $type->getShortname() . "\"
                                    name=\"questionVersion[" . $question->getVersionID() ."]\"
                                    value=\"" . ($answer_responses && is_string($answer_responses) ? html_encode($answer_responses) : "") . "\"
                                />";
                $html .= "        </td>";
                $html .= "    </tr>";
            }

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback(2, NULL);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderEssayAnswer (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $count = 1;
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $answer_responses       = $this->compileAnswerChoiceResponses();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
            } else {
                $answer_responses = NULL;
                $response_flag  = NULL;
                $elements_strike_out = NULL;
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-essay-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

            if ($this->allow_view == 1) {
                $html .= "    <tr class=\"question-answer-view\">";
                $html .= "        <td class=\"question-type-control\">";
                $html .= "          <textarea
                                    class=\"expandable question-control\"" .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "") . "
                                    data-element-id=\"" . $element_id . "\" data-type=\"" . $type->getShortname() . "\"
                                    name=\"questionVersion[" . $question->getVersionID() ."]\"
                                    >";
                $html .= ($answer_responses && is_string($answer_responses) ? html_encode($answer_responses) : "");
                $html .= "</textarea>";
                $html .= "        </td>";
                $html .= "    </tr>";
            }

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback(2, NULL);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";
        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderMatchAnswer (
        Models_Exam_Question_Versions $question,
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = true,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $feedback_array = $this->feedback;
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());

        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $response_flag          = $response->getFlagQuestion();
                $elements_strike_out    = unserialize($response->getStrikeOutAnswers());
                $answer_responses       = $this->compileAnswerChoiceResponses();
            } else {
                $response_flag          = NULL;
                $elements_strike_out    = NULL;
                $answer_responses       = array();
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-match-question".(isset($this->element) && !$this->element->isScored() ? " not-scored": "")."\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            $count = 1;
            $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

            if ($this->allow_view == 1) {
                $match_stems    = $question->getMatchStems();
                $answers        = $question->getQuestionAnswers();
                if (isset($match_stems) && is_array($match_stems)) {
                    $match_count = 0;
                    foreach ($match_stems as $match) {
                        $response_color     = "";
                        $strike_out_class   = "";
                        $correct            = NULL;
                        $response_value     = NULL;
                        $alphas             = range("A", "Z");
                        $row_check          = $match_count + 1;

                        if (isset($answer_responses) && is_array($answer_responses)) {
                            $response_value     = $answer_responses[$match->getID()];
                        }

                        if (isset($progress)) {
                            $exam_progress_id   = $progress->getID();
                            $proxy_id           = $progress->getProxyID();
                        } else {
                            $exam_progress_id   = NULL;
                            $proxy_id           = NULL;
                        }

                        $correct_option     = Models_Exam_Question_Match_Correct::fetchRowByMatchID($match->getID());
                        if ($correct_option) {
                            $correct = $correct_option->getCorrect();

                            if (isset($response_value)) {
                                $response_object = Models_Exam_Question_Answers::fetchRowByID($response_value);

                                if (isset($feedback_array) && $feedback_array["score"] == 1 && isset($response_object) && is_object($response_object)) {
                                    $response_color = $this->getResponseColorMatching($response_object, $correct_option);
                                }
                            }
                        }

                        $row_class = "question-answer-view" . $strike_out_class;

                        $data_select_attr_array = array(
                            "question-id"   => $question->getQuestionID(),
                            "version-id"    => $question->getVersionID(),
                            "type"          => $type->getShortname(),
                            "order"         => $match->getOrder(),
                            "match-id"      => $match->getID(),
                        );

                        if (isset($element_id)) {
                            $data_select_attr_array["element-id"] = $element_id;
                        }

                        $html .= "<tr class=\"" . $row_class . "\">";
                        $html .= "<td class=\"vertical-answer-input\">";
                        $html .= "<div>";
                        $html .= $match->getOrder() .". ";

                        $html .= "    <span class=\"summernote_text\" data-type=\"match_text\" data-version-id=\"" . $question->getVersionID() . "\" data-order=\"" . $match->getOrder() . "\">";

                        $highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($exam_progress_id, $proxy_id, $question->getVersionID(), $match->getOrder(), "match_text");
                        if ($highlight) {
                            $html .= $highlight->getQuestionText();
                        } else {
                            $match_text = $match->getMatchText();
                            $paragraph_count = substr_count($match_text, "<p>");
                            if ($paragraph_count === 1) {
                                // strip out p tag
                                $match_text = str_replace("<p>", "", $match_text);
                                $match_text = str_replace("</p>", "", $match_text);
                            }
                            $html .= $match_text;

                        }

                        $html .= "</span>";
                        $html .= "</div>";
                        $html .= "<div>";

                        $html .= "  <select
                                    class=\"form-control question-control " . $response_color . "\"" .
                                    ($progress && $progress->getProgressValue() != "inprogress" ? "DISABLED" : "");
                        $html .= $this->renderDataArray($data_select_attr_array);
                        $html .= "   name=\"questionVersion[" . $question->getVersionID() ."]\"
                                >";
                        $html .= "<option disabled selected> -- select an option -- </option>";
                        if (isset($answers) && is_array($answers)) {
                            $answer_count = 0;
                            foreach ($answers as $answer) {
                                if (isset($answer) && is_object($answer)) {
                                    $data_answer_attr_array = array(
                                        "question-id"   => $question->getQuestionID(),
                                        "version-id"    => $question->getVersionID(),
                                        "element-id"    => $element_id,
                                        "type"          => $type->getShortname(),
                                        "match-id"      => $match->getID(),
                                        "order"         => $match->getOrder(),
                                        "qanswer-id"    => $answer->getID(),
                                        "answer-order"  => $answer->getOrder(),
                                        "answer-letter" => $alphas[$answer_count]
                                    );

                                    if ($exam_mode === true && ($answer->getID() === $response_value)) {
                                        $selected = " SELECTED";
                                    } else if ($exam_mode === false && ($correct === $answer->getOrder())) {
                                        $selected = " SELECTED";
                                    } else {
                                        $selected = "";
                                    }

                                    $html .= "<option value=\"" . $answer->getID() . "\"" . $selected;
                                    $html .= $this->renderDataArray($data_answer_attr_array);
                                    $html .= " >" . $alphas[$answer_count] . ". " . $answer->getAnswerText() . "</option>";

                                    $answer_count++;
                                }
                            }
                        }
                        $html .= "      </select>";
                        if ($response_color === " answer-incorrect") {
                            $correct_answer = $answers[$correct - 1];
                            $html .= "<span class=\"correct-match-feedback\">Correct: "  . $alphas[$correct - 1] . ". " . strip_tags($correct_answer->getAnswerText()) . "</span>";
                        }
                        $html .= "</div>";
                        $html .= "</td>";
                        $html .= "</tr>";
                        $match_count++;
                    }
                }

            }

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderFeedback(2, NULL);
                $html .= $this->renderLearnerComments($count);
            }

            $html .= "</table>";
            $html .= "</div>";

        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }

    public function renderFillInTheBlank(
        array $control_array = NULL,
        array $data_attr_array = NULL,
        array $feedback_array = NULL
    ) {
        global $ENTRADA_USER;
        $question       = $this->question;
        $progress       = $this->progress;
        $response       = $this->response;
        $exam_mode      = $this->exam_mode;
        $echo_mode      = $this->echo_mode;
        $display_style  = $this->display_style;
        $feedback_array = $this->feedback;

        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($question->getQuestiontypeID());
        if ($display_style == "list") {
            $html = $this->renderListDisplay($type, $control_array, $data_attr_array);
        } else if ($display_style == "details") {
            $post = ($progress) ? $progress->getExamPost(): NULL;
            $exam = ($post) ? $post->getExam() : NULL;

            if (isset($response) && is_object($response)) {
                $question_order_number  = $response->getQuestionCount();
                $element_id             = $response->getExamElementID();
                $response_flag          = $response->getFlagQuestion();
            } else {
                $response_flag  = NULL;
            }

            if (empty($data_attr_array)) {
                $data_attr_array = array(
                    "question-id" => $question->getQuestionID(),
                    "version-id" => $question->getVersionID()
                );

                if (isset($element_id)) {
                    $data_attr_array["element-id"] = $element_id;
                }
            }

            $html = "<div class=\"exam-element exam-question question exam-match-question" . (isset($this->element) && !$this->element->isScored() ? " not-scored": "") . "\" id=\"" . $question->getVersionID() ."\"";
            $html .= $this->renderDataArray($data_attr_array);
            $html .= ">";

            $count = 1;
            $html .= $this->buildHeader($question, $count, $exam_mode, $control_array, $question_order_number, $type, $progress, $response, $feedback_array);

            if ($exam_mode === false) {
                $html .= $this->renderQuestionDetails($type, $count);
            }

            if (isset($progress) && $progress->getProgressValue() == "inprogress" && $this->allow_view == 1) {
                $html .= $this->renderLearnerComments($count);
            }

            if (isset($feedback_array) && ($feedback_array["score"] == 1 || $feedback_array["feedback"] == 1) && $feedback_array["incorrect"] != 1) {
                $html .= $this->renderLearnerComments($count);
                $html .= $this->renderFeedback(2, NULL);
            }

            $html .= "</table>";
            $html .= "</div>";

        }

        if ($echo_mode == true) {
            echo $html;
        } else {
            return $html;
        }
    }
    
    public function renderLearnerCommentsFacultyView($exam_element, $comments) {
        $type = Models_Exam_Lu_Questiontypes::fetchRowByID($this->question->getQuestiontypeID());
        $short_name = $type->getShortname();
        $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($this->question->getID());
        
        $num_cols = max(2, count($answers));
        
        switch ($short_name) {
            case "fnb":
            case "match":
                $container_class = "exam-match-question";
                break;
            case "essay":
                $container_class = "exam-essay-question";
                break;
            case "short":
                $container_class = "exam-short-question";
                break;
            case "mc_v":
            case "mc_v_m":
                $container_class = "exam-vertical-choice-question";
                break;
            case "mc_h":
            case "mc_h_m":
                $container_class = "exam-horizontal-choice-question";
                break;
        }
        
        $html = "<div class=\"exam-element exam-question question $container_class\">\n";
        $html .= "<table class=\"question-table table table-bordered admin-table\">\n";
        // Output heading for all questions
        $html .= "<tr class=\"type\">\n";
        $html .= "<td colspan=\"$num_cols\">\n";
        $html .= "    <span class=\"select-item select-question\">";
        $html .= "        <i class=\"select-item-icon question-icon-select fa fa-square-o\" data-question-id=\"" . $this->question->getQuestionID() . "\" data-version-id=\"" . $this->question->getVersionID() . "\" data-version-count=\"" . $this->question->getVersionCount() . "\"></i>";
        $html .= "    </span>";
        $html .= "    <span class=\"question-type\">ID. ".$this->question->getQuestionID()." Ver. ".$this->question->getVersionCount()."</span>\n";
        $html .= "</td>\n";
        $html .= "</tr>\n";
        // Output question text
        $question_text = $this->question->getQuestionText();
        if ("fnb" === $short_name) {
            $question_text = str_replace("_?_", "<input type=\"text\" disabled />", $question_text);
        }
        $html .= "<tr class=\"heading\">\n";
        $html .= "<td colspan=\"$num_cols\">\n";
        $html .= "<div class=\"pull-left\" id=\"question_stem\">\n";
        $html .= "<div class=\"question_number pull-left\"></div>\n";
        $html .= "<div class=\"question_text pull-left\">".$question_text."</div>\n";
        $html .= "</td>\n";
        $html .= "</tr>\n";
        // Output specific to question type
        switch ($short_name) {
            case "mc_h":
            case "mc_h_m":
                $input_type = "mc_h" === $short_name ? "radio" : "checkbox";
                $width = 100 / count($answers);
                // Output input fields
                $html .= "<tr class=\"horizontal-answer-input question-answer-view\">\n";
                foreach ($answers as $answer) {
                    $letter = chr(ord("A") + $answer->getOrder() - 1);
                    $correct = $answer->getAdjustedCorrect($exam_element->getID(), $exam_element->getExamID());
                    $html .= "<td width=\"".$width."%\">\n";
                    $html .= "<span class=\"question-letter\">".$letter.".</span>\n";
                    $html .= "<input type=\"".$input_type."\" ".($correct ? "checked" : "")." onclick=\"return false;\" />\n";
                    $html .= "</td>\n";
                }
                $html .= "</tr>\n";
                // Output answer choices
                $html .= "<tr class=\"horizontal-answer-label question-answer-view\">\n";
                foreach ($answers as $answer) {
                    $html .= "<td width=\"".$width."%\">\n";
                    $html .= "<span>".$answer->getAnswerText()."</span>\n";
                    $html .= "</td>\n";
                }
                $html .= "</tr>\n";
                break;
            case "mc_v":
            case "mc_v_m":
                $input_type = "mc_v" === $short_name ? "radio" : "checkbox";
                foreach ($answers as $answer) {
                    $letter = chr(ord("A") + $answer->getOrder() - 1);
                    $correct = $answer->getAdjustedCorrect($exam_element->getID(), $exam_element->getExamID());
                    $html .= "<tr class=\"question-answer-view\">\n";
                    $html .= "<td class=\"vertical-answer-input\">";
                    $html .= "<span class=\"question-letter\">".$letter.".</span>\n";
                    $html .= "<input type=\"".$input_type."\" ".($correct ? "checked" : "")." onclick=\"return false;\" />";
                    $html .= "</td>\n";
                    $html .= "<td class=\"vertical-answer-label\">".$answer->getAnswerText()."</td>\n";
                    $html .= "</tr>\n";
                }
                break;
            case "short":
                $html .= "<tr class=\"question-answer-view\">\n";
                $html .= "<td class=\"question-type-control\" colspan=\"$num_cols\">\n";
                $html .= "<input class=\"question-control\" type=\"text\" />\n";
                $html .= "</td>\n";
                $html .= "</tr>\n";
                break;
            case "essay":
                $html .= "<tr class=\"question-answer-view\">\n";
                $html .= "<td class=\"question-type-control\" colspan=\"$num_cols\">\n";
                $html .= "<textarea class=\"question-control\"></textarea>\n";
                $html .= "</td>\n";
                $html .= "</tr>\n";
                break;
            case "match":
                $matches = Models_Exam_Question_Match::fetchAllRecordsByVersionID($this->question->getID());
                foreach ($matches as $match) {
                    $match_correct = Models_Exam_Question_Match_Correct::fetchRowByMatchID($match->getID());
                    $html .= "<tr class=\"question-answer-view\">\n";
                    $html .= "<td class=\"vertical-answer-input\">\n";
                    $html .= "<div>".$match->getOrder().". <span>".$match->getMatchText()."</span></div>\n";
                    $html .= "<div>\n";
                    $html .= "<select class=\"form-control question-control\">\n";
                    foreach ($answers as $answer) {
                        $correct = $match_correct->getCorrect() == $answer->getOrder();
                        $html .= "<option ".($correct ? "selected" : "").">".$answer->getAnswerText()."</option>\n";
                    }
                    $html .= "</select>\n";
                    $html .= "</div>\n";
                    $html .= "</td>\n";
                    $html .= "</tr>\n";
                }
                break;
            case "drop_s":
            case "drop_m":
                // Not supported right now
                break;
            case "fnb":
            case "text":
                // Don't need to output anything extra
                break;
        }
        // Show the comments
        $html .= "<tr class=\"question-detail-view\">\n";
        $html .= "<td colspan=\"$num_cols\">\n";
        $html .= "<div class=\"question-details-container\">\n";
        $html .= "<h3>Learner Comments</h3>\n";
        $html .= "<blockquote>\n";
        foreach ($comments as $comment) {
            $html .= "<div class=\"row-fluid\">\n";
            $html .= "<span class=\"span2\"><h5>".date("m/d/y", $comment["date"]).":</h5></span>\n";
            $html .= "<span class=\"question-type span10\">".html_encode($comment["text"])."</span>\n";
            $html .= "</div>\n";
        }
        $html .= "</blockquote>\n";
        $html .= "</div>\n";
        $html .= "</td>\n";
        $html .= "</tr>\n";
        $html .= "</table>\n";
        $html .= "</div>\n";
        return $html;
    }

    /**
     * @param bool|false $exam_mode
     * @param array|NULL $control_array
     * @param array|NULL $data_attr_array
     * @param string $display_style
     * @param bool|false $echo_mode
     * @param Models_Exam_Progress|NULL $progress
     * @param Models_Exam_Progress_Responses|NULL $response
     * @param array|NULL $feedback
     * @param int $allow_view
     * @param int $active_details
     * @param array|NULL $correct
     * @param bool|false $randomize_answers
     * @return string
     */
    public function render (
        $exam_mode = false,
        array $control_array = NULL,
        array $data_attr_array = NULL,
        $display_style = "details",
        $echo_mode = false,
        Models_Exam_Progress $progress = NULL,
        Models_Exam_Progress_Responses $response = NULL,
        array $feedback = NULL,
        $allow_view = 1,
        $active_details = 0,
        $correct = array(),
        $randomize_answers = false
    ) {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");

        if (isset($response)) {
            $this->response_answer = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());
        }

        if (!empty($correct)) {
            $this->correct = $correct;
        } else {
            $this->correct = NULL;
        }

        if (!empty($this->question)) {
            $type = Models_Exam_Lu_Questiontypes::fetchRowByID($this->question->getQuestiontypeID());
            $short_name = $type->getShortname();
            //Try to set the exam element using the responses first, then see if element-id is set
            if (NULL !== $response) {
                $this->element      = $response->getElement();
                $this->highlight    = $response->getHighlight();
            } elseif (isset($data_attr_array["element-id"])) {
                $this->element      = Models_Exam_Exam_Element::fetchRowByID($data_attr_array["element-id"]);
            } else {
                $this->element      = NULL;
            }

            $this->allow_view   = $allow_view;
            $this->progress     = $progress;
            $this->response     = $response;
            $this->type         = $type;
            $this->short_name   = $short_name;
            $this->display_style = $display_style;
            $this->exam_mode    = $exam_mode;
            $this->echo_mode    = $echo_mode;
            $this->feedback     = $feedback;
            $this->active_details = $active_details;
            $this->randomize_answers = $randomize_answers;

            /**
             *  Answer randomization is only possible for the following question types: mc_h, mc_v, mc_h_m, mc_v_m
             * */

            if (NULL !== $this->progress) {
                $this->post = $this->progress->getExamPost();
            }

            switch ($short_name) {
                case "mc_h":
                    $question_render = $this->renderHorizontalChoiceSingleAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "mc_v":
                    $question_render = $this->renderVerticalChoiceSingleAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "mc_h_m":
                    $question_render = $this->renderHorizontalChoiceMultipleAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "mc_v_m":
                    $question_render = $this->renderVerticalChoiceMultipleAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "drop_s":
                    $question_render = $this->renderDropDownSingleAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "drop_m":
                    $question_render = $this->renderDropDownMultipleAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "short":
                    $question_render = $this->renderShortAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "essay":
                    $question_render = $this->renderEssayAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "match":
                    $question_render = $this->renderMatchAnswer($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "text":
                    $question_render = $this->renderText($this->question, $exam_mode, $control_array, $data_attr_array, $display_style, $echo_mode, $progress, $response, $feedback);
                    break;
                case "fnb":
                    $question_render = $this->renderFillInTheBlank($control_array, $data_attr_array);
                    break;
            }

            if ($echo_mode == true) {
                echo $question_render;
            } else {
                return $question_render;
            }

        } else {
            echo display_notice($MODULE_TEXT["exam"]["add-element"]["no_available_questions"]);
        }
    }

    public function renderQuestion ($exam_id = 0) {
        global $ENTRADA_USER;
        $questions = $this->fetchQuestionsByAuthor($ENTRADA_USER->getID(), $exam_id);
        if (isset($questions) && is_array($questions)) {
            foreach ($questions as $question) {
                $question->render();
            }
        }
    }
}