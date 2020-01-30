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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 */

class Views_Exam_Exam extends Views_Deprecated_Base {
    /**
     * @var array
     */
    protected $default_fieldset = array(
        "exam_id", "organisation_id", "title", "description", "created_date", "created_by", "updated_date", "updated_by", "deleted_date"
    );

    /**
     * @var string
     */
    protected $table_name               = "exams";
    /**
     * @var string
     */
    protected $primary_key              = "exam_id";
    /**
     * @var string
     */
    protected $default_sort_column      = "`exams`.`exam_id`";
    /**
     * @var array
     */
    protected $joinable_tables          = array(
        
    );
    /**
     * @var Models_Exam_Exam
     */
    protected $exam;

    /**
     * @var int
     */
    protected $show_select_exam;

    /**
     * @param Models_Exam_Exam $exam
     */
    public function __construct(Models_Exam_Exam $exam) {
        $this->exam = $exam;
    }

    public function createExamCheck() {
        $exam = $this->exam;
        $posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
        $exam_in_progress = false;
        if (isset($posts) && is_array($posts)) {
            foreach ($posts as $post) {
                if (isset($post) && is_object($post)) {
                    $progress = Models_Exam_Progress::fetchAllByPostID($post->getID());
                    if (isset($progress) && is_array($progress) && !empty($progress)) {
                        $exam_in_progress = true;
                    }
                }
            }
        }
        return $exam_in_progress;
    }

    public function randomizeExam(array $exam_elements) {
        $element_count          = count($exam_elements);
        $group_array_by_group   = array();
        $exam_element_array     = array();
        $exam_order_randomized  = array();
        // we need to check for group questions before when doing random
        // and get the element count array we use to generate the random order
        foreach ($exam_elements as $key => $exam_element) {
            $element_id = $exam_element->getID();

            $group_id = $exam_element->getGroupID();
            if ($group_id) {
                if (isset($group_array_by_group[$group_id])) {
                    $group_array_by_group[$group_id][] = (int)$key;
                } else {
                    $group_array_by_group[$group_id] = array((int)$key);
                }
            }

            if ($element_id) {
                $exam_element_array[] = (int)$key;
            }
        }
        for ($i = 0; $i < $element_count; $i++) {
            //gets random number based on the length of the element array
            //the values of this array $exam_element_array are the keys to $exam_elements
            if (count($exam_element_array) >= 1) {
                $random_key     = mt_rand(0, count($exam_element_array) - 1);
                $key            = $exam_element_array[$random_key];
                $random_element = $exam_elements[$key];


                if (isset($random_element) && is_object($random_element)) {
                    $group_id = $random_element->getGroupID();
                    if (isset($group_id)) {
                        //get all the members in the group
                        $current_group = $group_array_by_group[$group_id];
                        foreach ($current_group as $current_group_element) {
                            //gets the updated key based on the value
                            $current_key            = array_search($current_group_element, $exam_element_array);
                            $key                    = $exam_element_array[$current_key];
                            $random_element         = $exam_elements[$key];
                            //adds the grouped question to the new order
                            $exam_order_randomized[] = $random_element;

                            //now we get rid of the used element id and reset the array
                            unset($exam_element_array[$current_key]);
                            $exam_element_array = array_values($exam_element_array);
                        }
                    } else {
                        //regular insert
                        $exam_order_randomized[] = $random_element;

                        //now we get rid of the used element id and reset the array
                        unset($exam_element_array[$random_key]);
                        $exam_element_array = array_values($exam_element_array);
                    }
                }
            }
        }

        return $exam_order_randomized;
    }

    /**
     * @param Models_Exam_Exam $exam
     * @param string $sort_field
     * @param string $sort_direction
     * @return array
     */
    public static function renderExamElements(Models_Exam_Exam $exam, $sort_field = "order", $sort_direction = "asc") {
        global $translate, $ENTRADA_USER, $ENTRADA_ACL;
        $MODULE_TEXT = $translate->_("exams");
        $exam_elements = $exam->getExamElements();
        $exam_element_view = NULL;
        $exam_list_view_array = array();
        $exam_list_view = NULL;

        $question_count = 0;
        $point_count    = 0;

        if ($exam_elements && is_array($exam_elements)) {
            $groups_displayed = array();
            foreach ($exam_elements as $key => $exam_element) {
                if ($exam_element && is_object($exam_element)) {
                    switch ($exam_element->getElementType()) {
                        case "question" :
                            $question = Models_Exam_Question_Versions::fetchRowByVersionID($exam_element->getElementID());
                            if ($question && is_object($question)) {
                                $question_count++;
                                $points = $exam_element->getAdjustedPoints();
                                $point_count = $point_count + $points;
                                if ($exam_element->getGroupID() !== NULL) {
                                    if (!isset($groups_displayed[$exam_element->getGroupID()])) {
                                        $group = Models_Exam_Group::fetchRowByID($exam_element->getGroupID());
                                        if ($group && is_object($group)) {
                                            $data_attr_array["question-id"]         = $question->getQuestionID();
                                            $data_attr_array["version-count"]       = $question->getVersionCount();
                                            $data_attr_array["version-id"]          = $exam_element->getQuestionVersion()->getID();
                                            $data_attr_array["element-id"]          = "group-id-" . $group->getID();
                                            $data_attr_array["sortable-element-id"] = "element_" . $exam_element->getID();
                                            $data_attr_array["group-id"]            = $group->getID();
                                            foreach ($exam_elements as $exam_element_item) {
                                                if ($exam_element_item->getGroupID() !== NULL) {
                                                    $data_attr_element_array[$exam_element_item->getQuestionVersion()->getID()] = $exam_element_item->getID();
                                                }
                                            }

                                            $group_view         = new Views_Exam_Group($group);
                                            $exam_element_view  .= $group_view->render(false, NULL, $data_attr_array, $data_attr_element_array, "group", $exam->getID());

                                            $exam_list_view_array[$exam_element->getID()]["order"]      = (int) $exam_element->getOrder();
                                            $exam_list_view_array[$exam_element->getID()]["version"]    = 0;
                                            $exam_list_view_array[$exam_element->getID()]["description"] = "[Group]";
                                            $exam_list_view_array[$exam_element->getID()]["updated"]    = (int) $group->getUpdatedDate();
                                            $exam_list_view_array[$exam_element->getID()]["html"]       = $group_view->render(false, NULL, $data_attr_array, $data_attr_element_array, "list", $exam->getID());

                                            $groups_displayed[$exam_element->getGroupID()] = 1; // Marks the question group as rendered
                                        }
                                    }
                                } else {
                                    $question_view = new Views_Exam_Question($question);

                                    $data_attr_array    = $question_view->buildDataAttrArray($question, $exam_element);
                                    $control_array      = $question_view->buildExamHeaderEditButton($question, $exam_element, "details", $exam->getID());
                                    $exam_element_view .= $question_view->render(false, $control_array, $data_attr_array, "details", false);
                                    $control_array      = $question_view->buildExamHeaderEditButton($question, $exam_element, "list", $exam->getID());
                                    $exam_list_view_array[$exam_element->getID()]["html"] = $question_view->render(false, $control_array, $data_attr_array, "list", false);
                                    $exam_list_view_array[$exam_element->getID()]["order"] = (int) $exam_element->getOrder();
                                    $exam_list_view_array[$exam_element->getID()]["version"] = (int) $question->getVersionID();
                                    $exam_list_view_array[$exam_element->getID()]["description"] = ($question->getQuestionDescription() != "" ? $question->getQuestionDescription() : "N/A");
                                    $exam_list_view_array[$exam_element->getID()]["updated"] = (int) $question->getUpdatedDate();
                                }
                            }
                            break;
                        case "text" :
                            $edit = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($exam_element->getID(), true), "update");
                            $control_group = array();
                            if ($edit) {
                                $control_group[] = "<a class=\"flat-btn btn save-element\" data-text-element-id=\"" . $exam_element->getID() . "\" title=\"Save\" href=\"#\">Save</a>";
                                $control_group[] = "<a href=\"#\" title=\"Remove Text\" class=\"flat-btn btn delete-text\"><i class=\"fa fa-trash\"></i></a>";
                            }
                            $control_group[] = "<a class=\"flat-btn btn item-details\" title=\"View Question Details\" href=\"#\"><i class=\"fa fa-eye\"></i></a>";
                            $control_group[] = "<a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>";

                            $control_array = array(
                                array(
                                    "<span class=\"flat-btn btn select-item select-question\"><i class=\"select-item-icon question-icon-select fa fa-2x fa-square-o\" data-element-id=\"" . $exam_element->getID() . "\" \"></i></span>"
                                ),
                                $control_group
                            );

                            $data_attr_array["element-id"] = $exam_element->getID();
                            $data_attr_array["sortable-element-id"] = "element_" . $exam_element->getID();
                            $text_view = new Views_Exam_Exam_Element($exam_element);
                            $exam_element_view .= $text_view->render(false);

                            $exam_list_view_array[$exam_element->getID()]["order"] = (int) $exam_element->getOrder();
                            $exam_list_view_array[$exam_element->getID()]["version"] = 0;
                            $exam_list_view_array[$exam_element->getID()]["description"] = "[FreeText]";
                            $exam_list_view_array[$exam_element->getID()]["updated"] = (int) $exam_element->getUpdatedDate();
                            $exam_list_view_array[$exam_element->getID()]["html"] = $text_view->render(false, "list");

                            break;
                        case "page_break" :
                            $edit = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($exam_element->getID(), true), "update");
                            $control_group = array();
                            if ($edit) {
                                $control_group[] = "<a href=\"#\" title=\"Remove Question from Group\" class=\"flat-btn btn delete-group-question\"><i class=\"fa fa-trash\"></i></a>";
                            }
                            $control_group[] = "<a class=\"flat-btn btn move\" title=\"Move\" href=\"#\"><i class=\"fa fa-arrows\"></i></a>";

                            $control_array = array(
                                array(
                                    "<span class=\"flat-btn btn select-item select-question\"><i class=\"select-item-icon question-icon-select fa fa-2x fa-square-o\" data-element-id=\"" . $exam_element->getID() . "\"></i></span>"
                                ),
                                $control_group
                            );

                            $data_attr_array["element-id"] = $exam_element->getID();
                            $data_attr_array["sortable-element-id"] = "element_" . $exam_element->getID();
                            $page_break_view = new Views_Exam_Exam_Element($exam_element);
                            $exam_element_view .= $page_break_view->render(false);

                            $exam_list_view_array[$exam_element->getID()]["order"] = (int)$exam_element->getOrder();
                            $exam_list_view_array[$exam_element->getID()]["version"] = 0;
                            $exam_list_view_array[$exam_element->getID()]["description"] = "[PageBreak]";
                            $exam_list_view_array[$exam_element->getID()]["updated"] = (int)$exam_element->getUpdatedDate();
                            $exam_list_view_array[$exam_element->getID()]["html"] = $page_break_view->render(false, "list");

                            break;
                        default:
                            $exam_element_view .= $exam_element->getElementType();
                            break;
                    }
                }
            }
        } else {
            $exam_element_view = $MODULE_TEXT["exams"]["edit-form"]["no_form_elements"];
        }

        $sort = new Views_Exam_Exam_Sort($sort_field, $sort_direction);
        if ($sort) {
            switch($sort_field) {
                case "order":
                case "version":
                case "update":
                    $exam_list_view_array = $sort->sort_field_numeric($exam_list_view_array);
                    break;
                case "description" :
                    $exam_list_view_array = $sort->sort_field_alpha($exam_list_view_array);
                    break;
            }

            if ($exam_list_view_array && is_array($exam_list_view_array)) {
                foreach ($exam_list_view_array as $exam_element_id => $exam_list) {
                    $exam_list_view .= $exam_list["html"];
                }
            }
        }

        return array("detail_view" => $exam_element_view, "list_view" => $exam_list_view, "point_count" => $point_count, "question_count" => $question_count);
    }

    public static function getExamElementOrder(Models_Exam_Exam $exam, $sort_field = "order", $sort_direction = "asc") {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        $exam_elements = $exam->getExamElements();
        $exam_element_view = NULL;
        $exam_list_view_array = array();
        $exam_list_view = NULL;

        if ($exam_elements) {
            $groups_displayed = array();
            foreach ($exam_elements as $key => $exam_element) {
                switch ($exam_element->getElementType()) {
                    case "question" :
                        $question = Models_Exam_Question_Versions::fetchRowByVersionID($exam_element->getElementID());
                        if ($exam_element->getGroupID() !== NULL) {
                            if (!isset($groups_displayed[$exam_element->getGroupID()])) {
                                $group = Models_Exam_Group::fetchRowByID($exam_element->getGroupID());

                                $exam_list_view_array[$exam_element->getID()]["element_id"] = (int) $exam_element->getID();
                                $exam_list_view_array[$exam_element->getID()]["order"]      = (int) $exam_element->getOrder();
                                $exam_list_view_array[$exam_element->getID()]["version"]    = 0;
                                $exam_list_view_array[$exam_element->getID()]["description"] = "[Group]";
                                $exam_list_view_array[$exam_element->getID()]["update"]    = (int) $group->getUpdatedDate();

                                $groups_displayed[$exam_element->getGroupID()] = 1; // Marks the question group as rendered
                            }

                        } else {
                            $question_view = new Views_Exam_Question($question);

                            $exam_list_view_array[$exam_element->getID()]["element_id"] = (int) $exam_element->getID();
                            $exam_list_view_array[$exam_element->getID()]["order"] = (int) $exam_element->getOrder();
                            $exam_list_view_array[$exam_element->getID()]["version"] = (int) $question->getVersionID();
                            $exam_list_view_array[$exam_element->getID()]["description"] = ($question->getQuestionDescription() != "" ? $question->getQuestionDescription() : "N/A");
                            $exam_list_view_array[$exam_element->getID()]["update"] = (int) $question->getUpdatedDate();
                        }
                        break;
                    case "text" :
                        $exam_list_view_array[$exam_element->getID()]["element_id"] = (int) $exam_element->getID();
                        $exam_list_view_array[$exam_element->getID()]["order"] = (int) $exam_element->getOrder();
                        $exam_list_view_array[$exam_element->getID()]["version"] = 0;
                        $exam_list_view_array[$exam_element->getID()]["description"] = "[FreeText]";
                        $exam_list_view_array[$exam_element->getID()]["update"] = (int) $exam_element->getUpdatedDate();
                        break;
                    case "page_break" :
                        $exam_list_view_array[$exam_element->getID()]["element_id"] = (int) $exam_element->getID();
                        $exam_list_view_array[$exam_element->getID()]["order"] = (int)$exam_element->getOrder();
                        $exam_list_view_array[$exam_element->getID()]["version"] = 0;
                        $exam_list_view_array[$exam_element->getID()]["description"] = "[PageBreak]";
                        $exam_list_view_array[$exam_element->getID()]["update"] = (int)$exam_element->getUpdatedDate();
                        break;
                    default:
                        $exam_element_view .= $exam_element->getElementType();
                        break;
                }
            }
        } else {
            $exam_element_view = $MODULE_TEXT["exams"]["edit-form"]["no_form_elements"];
        }

        $sort = new Views_Exam_Exam_Sort($sort_field, $sort_direction);

        switch($sort_field) {
            case "order":
            case "version":
            case "update":
                $exam_list_view_array = $sort->sort_field_numeric($exam_list_view_array);
                break;
            case "description" :
                $exam_list_view_array = $sort->sort_field_alpha($exam_list_view_array);
                break;
        }

        return array("list_view_order" => array_reverse($exam_list_view_array));
    }

    public static function getExamPoints(Models_Exam_Exam $exam, $curriculum_tag_select = 1) {
        global $translate, $ENTRADA_USER, $ENTRADA_ACL;
        $MODULE_TEXT = $translate->_("exams");
        $exam_elements = $exam->getExamElements();
        $categories = array();

        $question_count = 0;
        $point_count    = 0;

        if ($exam_elements && is_array($exam_elements)) {
            foreach ($exam_elements as $key => $exam_element) {
                if ($exam_element && is_object($exam_element)) {
                    if ($exam_element->getElementType() == "question") {
                        $question = Models_Exam_Question_Versions::fetchRowByVersionID($exam_element->getElementID());
                        if ($question && is_object($question)) {
                            $type = $question->getQuestionType()->getShortname();
                            if ($type != "text") {
                                $question_count++;
                                $points = $exam_element->getAdjustedPoints();
                                $point_count = $point_count + $points;

                                if ($curriculum_tag_select == 1) {
                                    $curriculum_tags = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question->getQuestionID());

                                    if ($curriculum_tags && is_array($curriculum_tags) && !empty($curriculum_tags)) {
                                        foreach ($curriculum_tags as $tag) {
                                            if ($tag && is_object($tag)) {
                                                $objective_id = $tag->getObjectiveID();
                                                $global_objective = Models_Objective::fetchRow($objective_id);

                                                if ($global_objective && is_object($global_objective)) {
                                                    // get the parent...
                                                    $parent_id = (int)$global_objective->getParent();
                                                    if ($parent_id > 0) {
                                                        $parent_objective = Models_Objective::fetchRow($parent_id);
                                                        if ($parent_objective && is_object($parent_objective)) {
                                                            $parent_parent_id = (int)$parent_objective->getParent();
                                                            if ($parent_parent_id > 0) {

                                                            } else if ($parent_parent_id == 0) {
                                                                $set_parent = $parent_objective;
                                                                $set = $set_parent->getID();
                                                            }
                                                        }
                                                    } else if ($parent_id == 0) {
                                                        $set_parent = $global_objective;
                                                        $set = $set_parent->getID();
                                                    }

                                                    if (!array_key_exists($set, $categories)) {
                                                        $categories[$set] = array();
                                                    }

                                                    if (!array_key_exists($global_objective->getName(), $categories[$set])) {
                                                        $categories[$set][$global_objective->getName()] = 1;
                                                    } else {
                                                        $categories[$set][$global_objective->getName()] = $categories[$set][$global_objective->getName()] + 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array("point_count" => $point_count, "question_count" => $question_count, "categories" => $categories);
    }

    public function renderExamRowAdmin(Models_Exam_Exam $exam) {
        global $translate;

        $create_date = $exam->getCreatedDate();
        $updated_date = $exam->getUpdatedDate();
        $show_select_exam = $this->show_select_exam;

        $html = "<tr class=\"exam-row\" data-id=\"" . $exam->getID() . "\">";

        if (isset($show_select_exam) && $show_select_exam === 1) {
            $html .= "<td class=\"text-center\"><span class=\"select-exam\">\n";
            $html .= "<i class=\"select-exam-icon fa fa-square-o\" data-exam-id=\"" . $exam->getID() . "\" data-title=\"" . $exam->getTitle() . "\"></i>\n";
            $html .= "</span></td>";
        }
            $html .= "<td>";
                $html .= "<a href=\"".ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID()."\">".$exam->getTitle()."</a>";
            $html .= "</td>";
            $html .= "<td>";
            $html .= ($updated_date && !is_null($updated_date) ? date("m-d-Y", $updated_date) : $translate->_("N/A"));
            $html .= "</td>";
            $html .= "<td>";
                $html .= $exam->countExamQuestions();
            $html .= "</td>";
            $html .= "<td>";
                $html .= "<a class=\"get-post-targets\" href=\"#\" data-toggle=\"modal\" data-target=\"#post-info--modal\" data-id=\"" . $exam->getID() . "\">" . $exam->countPosts() . "</a>";
            $html .= "</td>";
            $html .= "<td>";
                $html .= $this->getPostEditMenu();
            $html .= "</td>";
        $html .= "</tr>";
        return $html;
    }

    public function getPostEditMenu() {
        global $ENTRADA_ACL, $translate;
        $MODULE_TEXT = $translate->_("exams");
        $MENU_TEXT = $MODULE_TEXT["exams"]["index"]["edit_menu"];

        $exam = $this->exam;
        $show_select_exam = $this->show_select_exam;

        $html = "";

        $html .= "<div class=\"btn-group\">\n";
        $html .= "<button class=\"flat-btn btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">\n";
        $html .= "<i class=\"fa fa-cog\"></i>\n";
        $html .= "</button>\n";
        $html .= "<ul class=\"dropdown-menu toggle-left\">\n";

        $can_update = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
        $can_view   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read");
        $can_delete = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete");

        $links = array();
        if ($can_view) {
            $links[$MENU_TEXT["view_posts"]] = "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=post&id=" . $exam->getID() . "\">" . $MENU_TEXT["view_posts"] . "</a></li>\n";
            $links[$MENU_TEXT["preview_post"]] = "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=preview&id=" . $exam->getID() . "\">" . $MENU_TEXT["preview_post"] . "</a></li>\n";
            $links[$MENU_TEXT["print_view"]] = "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=print&id=" . $exam->getID() . "\">" . $MENU_TEXT["print_view"] . "</a></li>\n";
            $links[$MENU_TEXT["reports"]] = "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=reports&id=" . $exam->getID() . "\">" . $MENU_TEXT["reports"] . "</a></li>\n";
            $links[$MENU_TEXT["export_word"]] = "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=print-word&id=" . $exam->getID() . "\">" . $MENU_TEXT["export_word"] . "</a></li>\n";
        }

        if ($can_update) {
            $links[$MENU_TEXT["edit_exam"]] = "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=edit-exam&id=" . $exam->getID() . "\">" . $MENU_TEXT["edit_exam"] . "</a></li>\n";
            $links[$MENU_TEXT["adjust_scoring"]] = "<li><a href=\"" . ENTRADA_URL."/admin/exams/exams?section=adjust&id=" . $exam->getID() . "\">" . $MENU_TEXT["adjust_scoring"] . "</a></li>\n";
        }

        ksort($links);

        foreach ($links as $link) {
            $html .= $link;
        }

        $html .= "</ul>\n";
        $html .= "</div>\n";
        return $html;
    }

    public static function GetQuestionsSubnavigation($tab = "questions") {
        global $ENTRADA_ACL, $ENTRADA_USER, $translate;

        $html = "<div class=\"no-printing\">\n";
        $html .= "    <ul class=\"nav nav-tabs\">\n";
        $permission = $ENTRADA_ACL->amIAllowed("examquestion", "create", false);
        if ($permission) {
            $html .= "<li".($tab=="questions"?" class=\"active\"":"")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/questions\">".$translate->_("Questions")."</a></li>\n";
            $html .= "<li".($tab=="import" ? " class=\"active\"" : "")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/import\">".$translate->_("Import")."</a></li>\n";
        }
        if ($ENTRADA_ACL->amIAllowed("examquestiongroupindex", "read", false)) {
            $html .= "<li".($tab=="groups" ?" class=\"active\"":"")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/groups\">".$translate->_("Grouped Q's")."</a></li>\n";
        }
        if ($ENTRADA_USER->getActiveGroup() === "medtech" && $ENTRADA_USER->getActiveRole() === "admin") {
            $html .= "<li".($tab=="migrate"?" class=\"active\"" : "")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/migrate\">".$translate->_("Migrate Q's")."</a></li>\n";
            $html .= "<li".($tab=="migrateimages"?" class=\"active\"" : "")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/migrateimages\">".$translate->_("Migrate Images")."</a></li>\n";
            $html .= "<li".($tab=="migrateresponses"?" class=\"active\"" : "")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/migrateresponses\">".$translate->_("Migrate Responses")."</a></li>\n";
            $html .= "<li".($tab=="flagged"?" class=\"active\"" : "")."><a href=\"".ENTRADA_RELATIVE."/admin/exams/flagged\">".$translate->_("Flagged Q's")."</a></li>\n";
        }
        $html .= "	</ul>\n";
        $html .= "</div>\n";

        return $html;
    }

    public function examNavigationTabs($section) {
        $exam = $this->exam;

        switch ($section) {
            case "form-post":
            case "graders":
            case "activity":
            case "preview":
                $section = "post";
                break;
            case "analysis":
            case "learner-comments":
            case "learner-responses":
            case "report-faculty-feedback":
            case "category":
            case "add-category":
            case "edit-category":
            case "category-result":
            case "category-learner-setup":
            case "summary":
            case "score":
            case "print":
                $section = "reports";
                break;
            case "history":
                $section = "history";
                break;
        }

        $nav = "<ul class=\"nav nav-tabs\">\n";
        $nav .= "   <li" . ($section == "edit-exam" ? " class=\"active\"" : "") . ">\n";
        $nav .= "       <a href=\"" . ENTRADA_URL . "/admin/exams/exams/?section=edit-exam&id=" . $exam->getID() . "\">Questions</a>\n";
        $nav .= "    </li>\n";
        $nav .= "   <li" . ($section == "exam-settings" ? " class=\"active\"" : "") . ">\n";
        $nav .= "       <a href=\"" . ENTRADA_URL . "/admin/exams/exams/?section=exam-settings&id=" . $exam->getID() . "\">Information & Settings</a>\n";
        $nav .= "   <li" . ($section == "post" ? " class=\"active\"" : "") . ">\n";
        $nav .= "       <a href=\"" . ENTRADA_URL . "/admin/exams/exams/?section=post&id=" . $exam->getID() . "\">Posts</a>\n";
        $nav .= "    </li>\n";
        $nav .= "   <li" . ($section == "adjust" ? " class=\"active\"" : "") . ">\n";
        $nav .= "       <a href=\"" . ENTRADA_URL . "/admin/exams/exams/?section=adjust&id=" . $exam->getID() . "\">Adjust Scoring</a>\n";
        $nav .= "    </li>\n";
        $nav .= "   <li" . ($section == "reports" ? " class=\"active\"" : "") . ">\n";
        $nav .= "       <a href=\"" . ENTRADA_URL . "/admin/exams/exams/?section=reports&id=" . $exam->getID() . "\">Reports</a>\n";
        $nav .= "    </li>\n";
        $nav .= "   <li" . ($section == "history" ? " class=\"active\"" : "") . ">\n";
        $nav .= "       <a href=\"" . ENTRADA_URL . "/admin/exams/exams/?section=history&id=" . $exam->getID() . "\">History</a>\n";
        $nav .= "    </li>\n";
        $nav .= "</ul>\n";

        return $nav;
    }

    /**
     * @param bool|Models_Exam_Exam_Element $element
     * @param ArrayObject|Models_Exam_Progress[] $submissions
     * @param array $top_27
     * @param array $bottom_27
     * @param $stdev
     * @param int $num_scores
     * @param array $options
     */
    public function renderExamAnalysisTable($element, $submissions, $top_27, $bottom_27, $stdev, $num_scores, $options = array()) {
        global $translate;
        $exam = $this->exam;

        $MODULE_TEXT    = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["exams"];
        $SECTION_TEXT   = $SUBMODULE_TEXT["analysis"];

        // $exam_element
        $order = $element->getOrder() + 1;
        $question_version = $element->getQuestionVersion();
        $question_text = $question_version->getQuestionText();
        $responses = array();
        foreach ($submissions as $submission) {
            $new_responses = Models_Exam_Progress_Responses::fetchAllByProgressIDExamIDPostIDProxyIDElementID(
            $submission->getID(), $exam->getID(), $submission->getPostID(), $submission->getProxyID(), $element->getID());
            $responses = array_merge($responses, $new_responses);
        }
        $letters = array();
        $choices = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
        foreach ($choices as $choice) {
            $letter = chr(ord("A") + (int)$choice->getOrder() - 1);
            if (!in_array($letter, $letters)) {
                $letters[] = $letter;
            }
        }
        sort($letters);

        $score_func             = function($a) { return $a->getScore(); };
        $difficulty_index       = Models_Exam_Exam::get_difficulty_index($responses);
        $upper_27               = Models_Exam_Exam::get_percent_correct($responses, $top_27, $score_func);
        $lower_27               = Models_Exam_Exam::get_percent_correct($responses, $bottom_27, $score_func);
        $discrim_index          = Models_Exam_Exam::get_discrimination_index($responses, $top_27, $bottom_27, $score_func);
        $biserial_correlation   = Models_Exam_Exam::get_point_biserial_correlation($submissions, $element, (float)$stdev, $score_func);

        $correct_answer = $element->getQuestionVersion()->getAdjustedMultipleChoiceCorrectText($element->getID(), $exam->getID());

        $frequencies = array();
        foreach ($letters as $letter) {
            $frequencies[$letter] = 0;
        }
        foreach ($responses as $response) {
            $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());
            foreach ($answers as $answer) {
                $letter = $answer->getResponseElementLetter();
                $frequencies[$letter]++;
            }
        }
        $item_analysis = "";
        // Output table headers
        $item_analysis .= "<table class=\"table table-bordered table-striped\" id=\"analysis-table\" style=\"background: #fff; page-break-inside: avoid;\">\n";
        $item_analysis .= "<thead>\n";
        $item_analysis .= "<tr>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["order"] . "</th>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["difficulty_index"] . "</th>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["upper_27"] . "</th>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["lower_27"] . "</th>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["disc_index"] . "</th>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["point_biserial"] . "</th>\n";
        $item_analysis .= "<th>" . $SECTION_TEXT["table_headings"]["correct_answer"] . "</th>\n";
        foreach ($letters as $letter) {
            $item_analysis .= "<th>$letter</th>\n";
        }
        $item_analysis .= "</tr>\n";
        $item_analysis .= "</thead>\n";
        $item_analysis .= "<tbody>\n";

        // Standard column headers and frequencies
        $item_analysis .= "<tr>\n";
        $item_analysis .= "<td><strong>" . $order . "</strong></td>\n";
        $item_analysis .= "<td>" . $difficulty_index . "</td>\n";
        $item_analysis .= "<td>" . (false === $upper_27 ? "N/A" : round($upper_27, 2)."%") . "</td>\n";
        $item_analysis .= "<td>" . (false === $lower_27 ? "N/A" : round($lower_27, 2)."%") . "</td>\n";
        $item_analysis .= "<td>" . $discrim_index . "</td>\n";
        $item_analysis .= "<td>" . $biserial_correlation . "</td>\n";
        $item_analysis .= "<td>" . $correct_answer . "</td>\n";
        foreach ($frequencies as $i => $count) {
            if ($num_scores != 0) {
                $freq = round(100 * $count / $num_scores, 2);
            } else {
                $freq = 0;
            }
            $item_analysis .= "<td>" . $count . "(" . $freq . "%)</td>\n";
        }
        $item_analysis .= "</tr>\n";

        // Individual answer choices point biserial correlation
        if ($options && is_array($options) && in_array("point_biserial", $options)) {
            $item_analysis .= "<tr>\n";
            $item_analysis .= "<td colspan=\"7\" style=\"text-align: right\">Point Biserial</td>\n";
            foreach ($frequencies as $i => $count) {
                $letter_func = function($a) use ($i) {
                    $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                    return $answers && $i === $answers[0]->getResponseElementLetter();
                };
                $biserial = Models_Exam_Exam::get_point_biserial_correlation($submissions, $element, (float)$stdev, $letter_func);
                $item_analysis .= "<td>$biserial</td>\n";
            }
            $item_analysis .= "</tr>\n";
        }

        // Individual answer choices discrimination index
        if ($options && is_array($options) && in_array("discrim_index", $options)) {
            $item_analysis .= "<tr>\n";
            $item_analysis .= "<td colspan=\"7\" style=\"text-align: right\">Disc. Index</td>\n";
            foreach ($frequencies as $i => $count) {
                $letter_func = function($a) use ($i) {
                    $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                    return $answers && $i === $answers[0]->getResponseElementLetter();
                };
                $discrim = Models_Exam_Exam::get_discrimination_index($responses, $top_27, $bottom_27, $letter_func);
                $item_analysis .= "<td>$discrim</td>\n";
            }
            $item_analysis .= "</tr>\n";
        }

        // Individual answer choices upper 27%
        if ($options && is_array($options) && in_array("upper_27", $options)) {
            $item_analysis .= "<tr>\n";
            $item_analysis .= "<td colspan=\"7\" style=\"text-align: right\">Upper 27%</td>\n";
            foreach ($frequencies as $i => $count) {
                $letter_func = function($a) use ($i) {
                    $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                    return $answers && $i === $answers[0]->getResponseElementLetter();
                };
                $individual_top_27 = Models_Exam_Exam::get_percent_correct($responses, $top_27, $letter_func);
                $item_analysis .= "<td>".round($individual_top_27, 2)."%</td>\n";
            }
            $item_analysis .= "</tr>\n";
        }

        // Individual answer choices lower 27%
        if ($options && is_array($options) && in_array("lower_27", $options)) {
            $item_analysis .= "<tr>\n";
            $item_analysis .= "<td colspan=\"7\" style=\"text-align: right\">Lower 27%</td>\n";
            foreach ($frequencies as $i => $count) {
                $letter_func = function($a) use ($i) {
                    $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                    return $answers && $i === $answers[0]->getResponseElementLetter();
                };
                $individual_bottom_27 = Models_Exam_Exam::get_percent_correct($responses, $bottom_27, $letter_func);
                $item_analysis .= "<td>".round($individual_bottom_27, 2)."%</td>\n";
            }
            $item_analysis .= "</tr>\n";
        }

        // Question text
        if ($options && is_array($options) && in_array("question_text", $options)) {
            $item_analysis .= "<tr><td colspan=\"100\">\n";
            $item_analysis .= "<strong>" . $SECTION_TEXT["table_headings"]["question_text"] . ":</strong>" . $question_text . "\n";
            $item_analysis .= "</td></tr>\n";
        }

        // Question choices
        if ($options && is_array($options) && in_array("answer_text", $options)) {
            $item_analysis .= "<tr><td colspan=\"100\">\n";
            $choices = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
            $choices_text = array();
            foreach ($choices as $choice) {
                $letter = chr(ord("A") + (int)$choice->getOrder() - 1);
                $choices_text[] = "<strong>" . $letter . "</strong> " . $choice->getAnswerText() . "\n";
            }
            $item_analysis .= implode("<br />", $choices_text);
            $item_analysis .= "</td></tr>\n";
        }

        // Rationale
        if ($options && is_array($options) && in_array("rationale", $options) && $question_version->getRationale()) {
            $item_analysis .= "<tr>\n";
            $item_analysis .= "<td colspan=\"100\">\n";
            $item_analysis .= "<strong>Rationale:</strong> " . $question_version->getRationale() . "\n";
            $item_analysis .= "</td>\n";
            $item_analysis .= "</tr>\n";
        }
        $item_analysis .= "</tbody>\n";
        $item_analysis .= "</table>\n";

        return $item_analysis;
    }

    public function renderPrintView($exam_elements, $SECTION_TEXT) {
        $exam = $this->exam;
        $content = "<div class=\"print-friendly\">";
        $content .= "<div class=\"exam_id\">";
        $content .= "<strong>" . $SECTION_TEXT["options"]["exam_id"] . ": </strong>" . $exam->getID();
        $content .= "</div>";
        $content .= "<div class=\"exam_created_date\"><strong>" . $SECTION_TEXT["options"]["exam_created_date"] . "</strong> " . date("D, M j, Y @ H:i:s", $exam->getCreatedDate());
        $content .= "</div>";
        $content .= "<div class=\"num_questions\">";
        $content .= "<strong>" . $SECTION_TEXT["options"]["num_questions"] . ": </strong>" . count($exam_elements);
        $content .= "</div>";
        $content .= "<div class=\"total_exam_points\">";
        $content .= "<strong>" . $SECTION_TEXT["options"]["total_exam_points"] . ": </strong>" . $exam->getTotalExamPoints();
        $content .= "</div>";

        $content .= "<br/>";

        foreach ($exam_elements as $element) {
            if ($element && is_object($element)) {
                $content .= "<div class=\"print-question\">";
                $content .= "<div class=\"question_stem\">";
                $content .= "<div>";
                $content .= "<strong>" . $SECTION_TEXT["question_number"] . "</strong>" . ($element->getOrder() + 1);
                $content .= "</div>";
                switch ($element->getElementType()) {
                    case "text":
                        $content .= $element->getElementText();
                        break;
                    case "question":
                        $question_version = $element->getQuestionVersion();
                        if ($question_version) {
                            $folder = Models_Exam_Bank_Folders::fetchRowByID($question_version->getFolderID());
                            $short_name = $question_version->getQuestionType()->getShortname();
                            switch ($short_name) {
                                case "mc_h":
                                case "mc_h_m":
                                case "mc_v":
                                case "mc_v_m":
                                    $content .=  "<div>";
                                    $content .=  $question_version->getQuestionText();
                                    $content .=  "</div>";
                                    $content .=  "<div>";
                                    $content .=  "    <table>";
                                    $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
                                    foreach ($answers as $answer) {
                                        $letter = chr(ord("A") + $answer->getOrder() - 1);
                                        $content .= "<tr>";
                                        $content .= "<td class=\"correct\">" . ($answer->getCorrect() ? "&#x2713;" : "");
                                        $content .= "</td>";
                                        $content .= "<td>" . $letter . ". " . $answer->getAnswerText();
                                        $content .= "</td>";
                                        $content .= "</tr>";
                                    }
                                    $content .= "    </table>";
                                    $content .= "</div>";
                                    break;
                                case "match":
                                    $content .= "<div>" . $question_version->getQuestionText() . "</div>";
                                    $content .= "<div>";
                                    $content .= "<br />";
                                    $content .= "<table>";
                                    $content .= "<tr>";
                                    $content .= "<th>Order</th>";
                                    $content .= "<th>Stem</th>";
                                    $content .= "<th>Correct</th>";
                                    $content .= "</tr>";
                                    $matching_stems = Models_Exam_Question_Match::fetchAllRecordsByVersionID($question_version->getVersionID());
                                    if ($matching_stems && is_array($matching_stems)) {
                                        foreach ($matching_stems as $stem) {
                                            if ($stem && is_object($stem)) {
                                                $matching_correct = Models_Exam_Question_Match_Correct::fetchRowByMatchID($stem->getID());
                                                if ($matching_correct && is_object($matching_correct)) {
                                                    $answer = $matching_correct->getAnswer();
                                                    if ($answer && is_object($answer)) {
                                                        $answer_text = $answer->getAnswerText();
                                                    }
                                                }
                                                $content .= "<tr>";
                                                $content .= "<td>" . $stem->getOrder() . "</td>";
                                                $content .= "<td>" . $stem->getMatchText() . "</td>";
                                                $content .= "<td>" . $answer_text . "</td>";
                                                $content .= "</tr>";
                                            }
                                        }
                                    }
                                    $content .= "</table>";
                                    $content .= "<br />";
                                    $content .= "</div>";
                                    break;
                                case "fnb":
                                    $question_text      = $question_version->getQuestionText();
                                    $fnb_count = substr_count($question_version->getQuestionText(), "_?_");
                                    $question_stem = "";
                                    $question_text_array = explode("_?_", $question_text);
                                    if (isset($question_text_array) && is_array($question_text_array)) {
                                        $part_counter = 1;
                                        foreach ($question_text_array as $key => $question_part) {
                                            if ($question_part != "") {
                                                $question_stem .= $question_part;
                                                if ($part_counter <= $fnb_count) {
                                                    // get all correct answers for fnb item
                                                    $answer = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $part_counter);
                                                    if ($answer && is_object($answer)) {
                                                        $correct_options = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getID());
                                                        $correct = array();
                                                        foreach ($correct_options as $option) {
                                                            if ($option && is_object($option)) {
                                                                $correct[] = $option->getText();
                                                            }
                                                        }
                                                        if ($correct && is_array($correct)) {
                                                            $question_stem .= "<span class=\"print_fnb_text\">" . implode("/", $correct) . "</span>";
                                                        }
                                                    }
                                                    $part_counter++;
                                                }
                                            }
                                        }
                                    }
                                    $content .=  "<div>";
                                    $content .=  $question_stem;
                                    $content .=  "</div>";
                                    break;
                                default:
                                    $content .=  "<div>";
                                    $content .=  $question_version->getQuestionText();
                                    $content .=  "</div>";
                                    if ($question_version->getCorrectText()) {
                                        $content .= "<div class=\"correct\">";
                                        $content .= "<strong>" . $SECTION_TEXT["options"]["correct"] . ":</strong>" . $question_version->getCorrectText();
                                        $content .= "</div>";
                                    }
                                    break;
                            }
                            $content .= "</div>";
                            if ($question_version->getRationale()) {
                                $content .= "<div class=\"rationale\">";
                                $content .= "<strong>" . $SECTION_TEXT["options"]["rationale"] . ":</strong>" . $question_version->getRationale() . "</div>";
                            }
                            $content .= "<div class=\"entrada_id\">";
                            $content .= "<strong>" . $SECTION_TEXT["options"]["entrada_id"] . ":</strong>";
                            $content .= $question_version->getQuestionID() . "/" .  $question_version->getVersionCount();
                            $content .= "</div>";
                            $content .= "<div class=\"examsoft_id\">";
                            $content .= "<strong>" . $SECTION_TEXT["options"]["examsoft_id"] . ":</strong>";
                            $content .= ($question_version->getExamsoftID() ? $question_version->getExamsoftID() : "N/A");
                            $content .= "</div>";

                            if ($question_version->getQuestionDescription()) {
                                $content .= "<div class=\"description\">";
                                $content .= "<strong>" . $SECTION_TEXT["options"]["description"] . ":</strong>";
                                $content .= $question_version->getQuestionDescription();
                                $content .= "</div>";
                            }
                            $content .= "<div class=\"weight\">";
                            $content .= "<strong>" . $SECTION_TEXT["options"]["weight"] . ":</strong>" . $element->getPoints();
                            $content .= "</div>";
                            $content .= "<div class=\"question_folder\">";
                            $content .= "<strong>";
                            $content .= $SECTION_TEXT["options"]["question_folder"] . ":</strong>" . $folder->getCompleteFolderTitle() . "</div>";
                            $content .= "<div class=\"curriculum_tags\">";
                            $content .= "<strong>" . $SECTION_TEXT["options"]["curriculum_tags"] . ":</strong>";

                            $objectives = Views_Exam_Question_Objective::renderObjectives($question_version->getQuestionID(), true);
                            if ($objectives) {
                                $content .= $objectives;
                            }

                            $content .= "</div>";

                            break;
                        }

                }
                $content .= "</div>";
            }
            $content .= "<br/>";
        }
        $content .= "</div>";

        return $content;
    }

    public static function getStyles($string, $styles_array) {
        $style_found    = strpos($string, "style=\"");
        $style_start    = $style_found + (strlen("style=\""));
        $style_end      = strpos($string, "\"", $style_start);
        $styles         = substr($string, $style_start, $style_end - $style_start);

        $tag = stristr($styles, ":", true);

        if ($tag) {
            $style_value_pos_1    = strpos($styles, ":");
            $style_value_pos_2    = strpos($styles, ";", $style_value_pos_1);

            $style_value        = substr($styles, $style_value_pos_1 + 1, $style_value_pos_2 - $style_value_pos_1);
            $style_value        = str_replace(";", "", $style_value);

            switch ($tag) {
                case "color":
                case "background-color":
                    $value_pos          = strpos($style_value, "#");
                    $value_rgb          = strpos($style_value, "rgb");

                    if ($value_pos === 0) {
                        $value = substr($style_value, 1);
                    } else if ($value_rgb === 0) {
                        $style_value_pos_1    = strpos($styles, "(");
                        $style_value_pos_2    = strpos($styles, ")", $style_value_pos_1);

                        $rgb_values           = substr($styles, $style_value_pos_1 + 1, $style_value_pos_2 - $style_value_pos_1);

                        if ($rgb_values) {
                            $rgb_values = explode(",", $rgb_values);
                            if ($rgb_values && is_array($rgb_values)) {
                                $value = sprintf("%02x%02x%02x", $rgb_values[0], $rgb_values[1], $rgb_values[2]);
                            }
                        }
                    }
                    break;
            }

            if ($value) {
                if (!is_array($styles_array)) {
                    $styles_array = array();
                }

                if (is_array($styles_array)) {
                    switch ($tag) {
                        case "color":
                            $styles_array["color"] = $value;
                            break;
                        case "background-color":
                            $styles_array["fgColor"] = "yellow";
                            break;
                    }
                }
            }
        }

        return $styles_array;
    }

    public static function parseWordHtml($string, $object, $images_removed) {
        $split = explode("<", $string);

        if ($split && is_array($split) && !empty($split)) {
            if ($split && count($split) == 1) {
                \PhpOffice\PhpWord\Shared\Html::addHtml($object, $split[0]);
            } else {
                $css_styles = array();
                foreach ($split as $item) {
                    $tag = stristr($item, ">", true);
                    if ($tag) {
                        switch ($tag) {
                            case "":
                                break;
                            case "br":
                                $object->addTextBreak(1);
                                $object->addText(strip_tags("<" . $item));
                                break;
                            case "p":
                                $object->addTextBreak(1);
                                $object->addText(strip_tags("<" . $item));
                                break;
                            case "/p":
                                $object->addTextBreak(1);
                                break;
                            case "span":
                                $object->addText(strip_tags("<" . $item));
                                break;
                            case "/span":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles = array();
                                $object->addText($text, $css_styles);
                                break;
                            case "em":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["italic"] = true;
                                $object->addText($text, $css_styles);
                                break;
                            case "/em":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["italic"] = false;
                                $object->addText($text, $css_styles);
                                break;
                            case "strong":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["bold"] = true;
                                $object->addText($text, $css_styles);
                                break;
                            case "/strong":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["bold"] = false;
                                $object->addText($text, $css_styles);
                                break;
                            case "u":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["underline"] = true;
                                $object->addText($text, $css_styles);
                                break;
                            case "/u":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["underline"] = false;
                                $object->addText($text, $css_styles);
                                break;
                            case "s":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["strikethrough"] = true;
                                $object->addText($text, $css_styles);
                                break;
                            case "/s":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["strikethrough"] = false;
                                $object->addText($text, $css_styles);
                                break;

                            case "sub":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["subScript"] = true;
                                $object->addText($text, $css_styles);
                                break;
                            case "/sub":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["subScript"] = false;
                                $object->addText($text, $css_styles);
                                break;
                            case "sup":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["superScript"] = true;
                                $object->addText($text, $css_styles);
                                break;
                            case "/sup":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $css_styles["superScript"] = false;
                                $object->addText($text, $css_styles);
                                break;
                            case "li":
                                $object->addTextBreak(1);
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $object->addText("* " . $text);
                                break;
                            case "/li":
                                $text = strip_tags(substr($item, strpos($item, ">") + 1));
                                $object->addText($text);
                                break;
                            case "ol":
                                $object->addTextBreak(1);
                                break;
                            case "/ol":
                                $object->addTextBreak(1);
                                break;
                            default:
                                $tag_styles = stristr($item, " ", true);
                                if ($tag_styles) {
                                    switch ($tag_styles) {
                                        case "span":
                                            // style
                                            $css_styles = self::getStyles($item, $css_styles);
                                            $check_end = strpos($item, ">");
                                            if ($check_end == strlen($item) - 1) {
                                                // have to pass on to next object
                                            } else {
                                                $object->addText(strip_tags("<" . $item), $css_styles);
                                                $css_styles = array();
                                            }
                                            break;
                                        case "img":
                                            $images = self::getImages($item);
                                            $exam_temp_dir = EXAM_STORAGE_PATH . "/file_temp/";

                                            if ($images && is_array($images)) {
                                                foreach ($images as $id => $image) {
                                                    $path = $exam_temp_dir . $image;
                                                    if ($object->addImage($path)) {
                                                        // add image to an array to clear images after they are loaded
                                                        $images_removed[] = $path;
                                                    }
                                                }
                                            }
                                            break;
                                        case "p":
                                            $object->addTextBreak(1);
                                            $object->addText(strip_tags("<" . $item));
                                            $object->addTextBreak(1);
                                            break;
                                        case "br":
                                            $object->addTextBreak(1);
                                            $object->addText(strip_tags("<" . $item));
                                            break;
                                        default:
                                            \PhpOffice\PhpWord\Shared\Html::addHtml($object, "<" . $item);
                                            break;
                                    }
                                } else {
                                    \PhpOffice\PhpWord\Shared\Html::addHtml($object, "<" . $item);
                                }

                                break;

                        }
                    } else {
                        $object->addText(strip_tags($item));
                    }
                }
            }
        }
        return $images_removed;
    }

    public function renderWordExport($exam_elements, $SECTION_TEXT, $images_removed) {
        $exam = $this->exam;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $bold_on  = array(
            "bold" => true,
        );

        $bold_off = array(
            "bold" => false,
        );

        $title_style = array(
            "bold" => true,
            "size" => 18,
        );

        $section->addText($exam->getTitle(), $title_style, array("alignment" => "center"));
        $section->addTextBreak(2);

        $header = $section->addTextRun();
        $header->addText($SECTION_TEXT["options"]["exam_id"] . ": ", $bold_on);
        $header->addText($exam->getID(), $bold_off);
        $header->addTextBreak(1);
        $header->addText($SECTION_TEXT["options"]["exam_created_date"] . ": ", $bold_on);
        $header->addText(date("D, M j, Y @ H:i:s", $exam->getCreatedDate()), $bold_off);
        $header->addTextBreak(1);
        $header->addText($SECTION_TEXT["options"]["num_questions"] . ": ", $bold_on);
        $header->addText(count($exam_elements), $bold_off);
        $header->addTextBreak(1);
        $header->addText($SECTION_TEXT["options"]["total_exam_points"] . ": ", $bold_on);
        $header->addText($exam->getTotalExamPoints(), $bold_off);
        $section->addTextBreak(2);

        foreach ($exam_elements as $element) {
            if ($element && is_object($element)) {
                $section_q = $phpWord->addSection();
                $question = $section_q->addTextRun();
                $question->addText(($element->getOrder() + 1) . ". ", $bold_off);
                $question->addTextBreak(1);
                switch ($element->getElementType()) {
                    case "text":
                        \PhpOffice\PhpWord\Shared\Html::addHtml($question, $element->getElementText());
                        $question->addTextBreak(1);
                        break;
                    case "question":
                        $question_version = $element->getQuestionVersion();
                        if ($question_version) {
                            $folder = Models_Exam_Bank_Folders::fetchRowByID($question_version->getFolderID());
                            $short_name = $question_version->getQuestionType()->getShortname();
                            switch ($short_name) {
                                case "mc_h":
                                case "mc_h_m":
                                case "mc_v":
                                case "mc_v_m":
                                    $clean = self::cleanHtml($question_version->getQuestionText());
                                    $images_removed = self::parseWordHtml($clean, $question, $images_removed);

                                    $question->addTextBreak(1);
                                    $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());

                                    $answer_text = $section_q->addTextRun();
                                    $answer_letter = "";
                                    $correct_answer = array();
                                    foreach ($answers as $answer) {
                                        if ($answer && is_object($answer)) {
                                            $letter = chr(ord("A") + $answer->getOrder() - 1);
                                            if ($answer->getCorrect()) {
                                                $answer_letter = strtolower($letter);
                                                $correct_answer[] = $answer_letter;
                                            }
                                            $answer_text->addText(strtolower($letter) . ". " . strip_tags(self::cleanHtml($answer->getAnswerText())));
                                            $answer_text->addTextBreak(1);
                                        }
                                    }

                                    if ($answer_letter != "") {
                                        $answer_text->addText("answer: " . (count($correct_answer) > 1 ? implode("," ,$correct_answer) : $answer_letter ));
                                    }

                                    break;
                                case "match":
                                    \PhpOffice\PhpWord\Shared\Html::addHtml($question, $question_version->getQuestionText());

                                    $tableStyle = array(
                                        "borderColor" => "006699",
                                        "borderSize"  => 6,
                                        "cellMargin"  => 50
                                    );
                                    $firstRowStyle = array("bgColor" => "66BBFF");
                                    $phpWord->addTableStyle("myTable", $tableStyle, $firstRowStyle);
                                    $table = $section_q->addTable("myTable");

                                    $table->addRow();
                                    $table->addCell()->addText("Order");
                                    $table->addCell()->addText("Stem");
                                    $table->addCell()->addText("Correct");

                                    $matching_stems = Models_Exam_Question_Match::fetchAllRecordsByVersionID($question_version->getVersionID());
                                    if ($matching_stems && is_array($matching_stems)) {
                                        foreach ($matching_stems as $stem) {
                                            if ($stem && is_object($stem)) {
                                                $matching_correct = Models_Exam_Question_Match_Correct::fetchRowByMatchID($stem->getID());
                                                if ($matching_correct && is_object($matching_correct)) {
                                                    $answer = $matching_correct->getAnswer();
                                                    if ($answer && is_object($answer)) {
                                                        $answer_text = $answer->getAnswerText();
                                                    }
                                                }
                                                $table->addRow();
                                                $table->addCell()->addText($stem->getOrder());
                                                $table->addCell()->addText(strip_tags(self::cleanHtml($stem->getMatchText())));
                                                $table->addCell()->addText(strip_tags(self::cleanHtml($answer_text)));
                                            }
                                        }
                                    }
                                    break;
                                case "fnb":
                                    $question_text = $question_version->getQuestionText();
                                    $fnb_count = substr_count($question_version->getQuestionText(), "_?_");
                                    $question_stem = "";
                                    $question_text_array = explode("_?_", $question_text);
                                    if (isset($question_text_array) && is_array($question_text_array)) {
                                        $part_counter = 1;
                                        foreach ($question_text_array as $key => $question_part) {
                                            if ($question_part != "") {
                                                $question_stem .= $question_part;
                                                if ($part_counter <= $fnb_count) {
                                                    // get all correct answers for fnb item
                                                    $answer = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $part_counter);
                                                    if ($answer && is_object($answer)) {
                                                        $correct_options = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getID());
                                                        $correct = array();
                                                        foreach ($correct_options as $option) {
                                                            if ($option && is_object($option)) {
                                                                $correct[] = $option->getText();
                                                            }
                                                        }
                                                        if ($correct && is_array($correct)) {
                                                            $question_stem .= "<span class=\"print_fnb_text\">" . implode("/", $correct) . "</span>";
                                                        }
                                                    }
                                                    $part_counter++;
                                                }
                                            }
                                        }
                                    }

                                    \PhpOffice\PhpWord\Shared\Html::addHtml($question, $question_stem);
                                    break;
                                default:
                                    \PhpOffice\PhpWord\Shared\Html::addHtml($question, $question_version->getQuestionText());

                                    if ($question_version->getCorrectText()) {
                                        $question->addText($SECTION_TEXT["options"]["correct"] . ":");
                                        \PhpOffice\PhpWord\Shared\Html::addHtml($question, $question_version->getCorrectText());
                                    }
                                    break;
                            }

                            $question_footer = $section_q->addTextRun();

                            if ($question_version->getRationale()) {
                                $question_footer->addTextBreak(1);
                                $question_footer->addText($SECTION_TEXT["options"]["rationale"] . ": ", $bold_on);
                                $question_footer->addText(strip_tags(self::cleanHtml($question_version->getRationale())), $bold_off);
                            }

                            $question_footer->addTextBreak(1);
                            $question_footer->addText($SECTION_TEXT["options"]["entrada_id"] . ": ", $bold_on);
                            $question_footer->addText($question_version->getQuestionID() . "/" . $question_version->getVersionCount(), $bold_off);

                            if ($question_version->getExamsoftID()) {
                                $question_footer->addTextBreak(1);
                                $question_footer->addText($SECTION_TEXT["options"]["examsoft_id"] . ": ", $bold_on);
                                $question_footer->addText($question_version->getExamsoftID(), $bold_off);
                            }

                            if ($question_version->getQuestionDescription()) {
                                $question_footer->addTextBreak(1);
                                $question_footer->addText($SECTION_TEXT["options"]["description"] . ": ", $bold_on);
                                $question_footer->addText(strip_tags(self::cleanHtml($question_version->getQuestionDescription())), $bold_off);
                            }

                            $question_footer->addTextBreak(1);
                            $question_footer->addText($SECTION_TEXT["options"]["weight"] . ": ", $bold_on);
                            $question_footer->addText($element->getPoints(), $bold_off);

                            $question_footer->addTextBreak(1);
                            $question_footer->addText($SECTION_TEXT["options"]["question_folder"] . ": ", $bold_on);
                            $question_footer->addText($folder->getCompleteFolderTitle(), $bold_off);

                            $question_footer->addTextBreak(1);
                            $question_footer->addText($SECTION_TEXT["options"]["curriculum_tags"] . ": ", $bold_on);
                            $question_footer->addTextBreak(1);

                            $objectives_view = new Views_Exam_Question_Objective();
                            $objectives = $objectives_view->fetchQuestionObjectives($question_version->getQuestionID());
                            if ($objectives) {
                                foreach ($objectives as $key => $objective) {

                                    $text = strip_tags(self::cleanHtml($objective["objective_name"]));
                                    $question_footer->addText("* " . $text);
                                    $question_footer->addTextBreak(1);

                                    $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                    if ($set) {
                                        $question_footer->addText("From the Curriculum Tag Set: ");
                                        $question_footer->addText(strip_tags(self::cleanHtml($set["objective_name"])), array("bold" => true));
                                        $question_footer->addTextBreak(1);
                                    }
                                    $question_footer->addText(strip_tags(self::cleanHtml($objective["objective_description"])));
                                    $question_footer->addTextBreak(1);
                                }

                            }

                            break;
                        }
                    break;
                }
            }
        }

        return array("phpWord" => $phpWord, "images_removed" => $images_removed);
    }

    public static function cleanHtml($html, $fullHTML = false) {
        $html = str_replace(array("\n", "\r"), '', $html);
        $html = str_replace(array("&lt;", "&gt;", "&amp;"), array("_lt_", "_gt_", "_amp_"), $html);
        $html = html_entity_decode($html, ENT_QUOTES, "UTF-8");
        $html = str_replace("&", "&amp;", $html);
        $html = str_replace(array("_lt_", "_gt_", "_amp_"), array("&lt;", "&gt;", "&amp;"), $html);

        return $html;
    }

    public static function getImages($content) {
        $image_array = false;
        // set all JPG and PNG to lowercase
        $content = str_replace(".PNG", ".png", $content);
        $content = str_replace(".JPG", ".jpg", $content);
        $content = str_replace(".JPEG", ".jpg", $content);
        $content = str_replace(".jpeg", ".jpg", $content);
        $content = str_replace(".GIF", ".gif", $content);

        $lor_address = "/api/serve-learning-object.api.php?id=";

        $url = ENTRADA_URL . $lor_address;

        $occurrences = Entrada_Utilities::strpos_all($content, $url);

        if ($occurrences && is_array($occurrences) && !empty($occurrences)) {
            $image_array = array();
            $files_to_delete = array();
            $found_images = array();
            $replacement_images = array();
            foreach ($occurrences as $position1) {
                $position2 = strpos($content, "=", $position1);
                $position3 = strpos($content, "&", $position2 + 1);
                // @todo Seems like this may be a mess.
                $lor_id = substr($content, $position2 + 1, $position3 - $position2 - 1);
                $lor_id = (int) $lor_id;

                // now use the LOR to load copy the image to the /api folder which is publicly accessible.
                // find the type by loading the LOR.
                $lo_file = Models_LearningObject_Files::fetchRowByID($lor_id);
                if ($lo_file) {
                    $file_realpath = LOR_STORAGE_PATH . "/" . $lo_file->getProxyID() . "/" . $lor_id;
                    if (file_exists($file_realpath) && is_readable($file_realpath)) {
                        $mime_type = $lo_file->getMimeType();
                        $file_extension = "";
                        switch ($mime_type) {
                            case "image/jpeg":
                                $file_extension = ".jpg";
                                break;
                            case "image/png":
                                $file_extension = ".png";
                                break;
                            case "image/gif":
                                $file_extension = ".gif";
                                break;
                        }

                        if ($file_extension != "") {


                            $file_name = $lor_id . $file_extension;

                            if ($temp_file = file_get_contents($file_realpath)) {

                                $exam_temp_dir = EXAM_STORAGE_PATH . "/file_temp";
                                if (!is_dir($exam_temp_dir)) {
                                    mkdir($exam_temp_dir);
                                }

                                if (file_put_contents($exam_temp_dir . "/" . $file_name, $temp_file)) {
                                    $image_array[$lor_id] = $file_name;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $image_array;
    }

    public function render($show_select_exam = 1) {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        $this->show_select_exam = $show_select_exam;
        if ($this->exam !== null) {
            return $this->renderExamRowAdmin($this->exam);
        } else {
            echo display_notice($MODULE_TEXT["exams"]["text_no_available_exam"]);
        }
    }
}