<?php
/**
 * Module:    Exam
 * Area:    Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @version 0.8.3
 * @copyright Copyright 2018 Queen's University, MEdTech Unit
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;

} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "update", true)) {
    add_error(
        sprintf(
            $translate->_(
                "You do not have the permissions required to use this module.<br />" .
                "<br />If you believe you are receiving this message in error Please contact " .
                "<a href=\"mailto:%1\$s\">%2\$s</a> for assistance."
            ),
            html_encode($AGENT_CONTACTS["administrator"]["email"]),
            html_encode($AGENT_CONTACTS["administrator"]["name"])
        )
    );
    echo display_error();
    application_log(
        "error",
        "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] " .
        "and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have" .
        " access to this module [" . $MODULE . "]"
    );

} else {

    /**
     * Clears all open buffers so we can return a simple REST response.
     */
    ob_clear_open_buffers();

    //Set Globals
    $response = "";

    //Set parameters
    $editing_mode = (isset($_POST["editing_mode"]) ? $_POST["editing_mode"] : $ADD_TAGS_EDITING_MODE);
    $checked_questions_ids = (isset($_POST["checked_questions_ids"]) ? $_POST["checked_questions_ids"] : array());
    $mapped_curriculum_tags = (isset($_POST["mapped_curriculum_tags"]) ? $_POST["mapped_curriculum_tags"] : array());

    //Treat every editing mode
    switch ($editing_mode) {
        case "add_tags" :
            $status = "error";
            $questions_tagged = array();
            $questions_already_tagged = array();
            $errors = array();
            $response = array("status" => $status);
            //Swipe all questions ids
            //Verify if question already has that, if not add it
            foreach ($checked_questions_ids as $question_id) {
                foreach ($mapped_curriculum_tags as $objective_id) {
                    $is_question_tagged = Models_Exam_Question_Objectives::fetchRowByQuestionIdObjectiveId(
                        $question_id, $objective_id
                    );
                    if (!$is_question_tagged) {
                        $question_objective = new Models_Exam_Question_Objectives(array(
                            "question_id" => $question_id,
                            "objective_id" => $objective_id,
                            "created_date" => time(),
                            "created_by" => $ENTRADA_USER->getProxyID(),
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getProxyID()
                        ));
                        if (!$question_objective->insert()) {
                            array_push(
                                $errors,
                                array(
                                    "error_description" => "failed to tag question $question_id",
                                    "question_id" => $question_id,
                                    "objective_id" => $objective_id
                                )
                            );
                        } else {
                            array_push(
                                $questions_tagged,
                                array(
                                    "question_id" => $question_id,
                                    "objective_id" => $objective_id
                                )
                            );
                        }
                    } else {
                        array_push(
                            $questions_already_tagged,
                            array(
                                "question_id" => $question_id,
                                "objective_id" => $objective_id
                            )
                        );
                    }
                }
            }
            $status = "success";
            $response = array(
                "status" => $status,
                "questions_tagged" => $questions_tagged,
                "questions_already_tagged" => $questions_already_tagged,
                "errors" => $errors
            );
            break;
        case "replace_tags" :
            $status = "error";
            $questions_tagged = array();
            $errors = array();
            $response = array("status" => $status);
            //Swipe all questions ids
            //Remove all tags for every question
            foreach ($checked_questions_ids as $question_id) {
                $this_question_objectives = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question_id);
                foreach ($this_question_objectives as $question_obj) {
                    $question_obj->delete();
                }
            }
            //Add new tags for every question
            foreach ($checked_questions_ids as $question_id) {
                foreach ($mapped_curriculum_tags as $objective_id) {
                    $question_objective = new Models_Exam_Question_Objectives(array(
                        "question_id" => $question_id,
                        "objective_id" => $objective_id,
                        "created_date" => time(),
                        "created_by" => $ENTRADA_USER->getProxyID(),
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$question_objective->insert()) {
                        array_push(
                            $errors,
                            Array(
                                "error_description" => "failed to tag question $question_id",
                                "question_id" => $question_id,
                                "objective_id" => $objective_id
                            )
                        );
                    } else {
                        array_push(
                            $questions_tagged,
                            Array(
                                "question_id" => $question_id,
                                "objective_id" => $objective_id
                            )
                        );
                    }
                }
            }
            $status = "success";
            $response = array(
                "status" => $status,
                "questions_tagged" => $questions_tagged,
                "errors" => $errors
            );
            break;
        case "remove_tags" :
            $status = "error";
            $errors = array();
            $removed_tags = array();
            $response = array("status" => $status);
            //Swipe all questions ids
            //Swipe all objectives, if question had it, remove that tag
            foreach ($checked_questions_ids as $question_id) {
                foreach ($mapped_curriculum_tags as $objective_id) {
                    $question_tagged = Models_Exam_Question_Objectives::fetchRowByQuestionIdObjectiveId(
                        $question_id, $objective_id
                    );
                    if (is_object($question_tagged)) {
                        if ($question_tagged->delete()) {
                            array_push(
                                $removed_tags,
                                Array(
                                    "question_id" => $question_id,
                                    "objective_id" => $objective_id
                                )
                            );
                        } else {
                            array_push(
                                $errors,
                                Array(
                                    "error_description" => "failed to delete tag from question $question_id",
                                    "question_id" => $question_id,
                                    "objective_id" => $objective_id
                                )
                            );
                        }
                    }
                }
            }
            $status = "success";
            $response = array(
                "status" => $status,
                "removed_tags" => $removed_tags,
                "errors" => $errors
            );
            break;
        default:
            $response = array(
                "status_code" => 500,
                "status" => "error",
                "error_description" => "No editing mode was specified"
            );
    }

    echo json_encode($response);
    exit;

}

