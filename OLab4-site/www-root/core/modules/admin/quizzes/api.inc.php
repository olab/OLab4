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
 * This file looks a bit different because it is called only by AJAX requests
 * and returns status codes based on it's ability to complete the requested
 * action. In this case, the requested action is to re-order quiz questions.
 * 
 * 0	Unable to start processing request.
 * 200	There were no errors, everything was updated successfully.
 * 400	Cannot update question order becuase no id was provided.
 * 401	Cannot update question order because quiz could not be found.
 * 402	Cannot update question order, because it's in use.
 * 403	Unable to find a valid order array.
 * 404	Order array is empty, unable to process.
 * 405	There were errors in the update SQL execution, check the error_log.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: add.inc.php 317 2009-01-19 19:26:35Z simpson $
 * 
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quizquestion', 'update', false)) {
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");

	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} else {
    
    ob_clear_open_buffers();
    
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request_var = "_".$request;
    
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));
    
    switch ($request) {
		case "POST" :
			switch ($method) {
                case "add-question-group" :
                    
                    if(isset(${$request_var}["quiz_id"]) && $tmp_input = clean_input(${$request_var}["quiz_id"], "int")) {
						$PROCESSED["quiz_id"] = $tmp_input;
					} else {
                        add_error("Invalid quiz question ID.");
                    }
                    
                    if (!$ERROR) {
                        $quiz_question_record = array(
                            "quiz_id" => $PROCESSED["quiz_id"],
                            "updated_date" => time(), 
                            "updated_by" => $ENTRADA_USER->getID(), 
                            "active" => "1"
                        );
                        $quiz_question_group = new Models_Quiz_Question_Group();
                        if ($quiz_question_group->fromArray($quiz_question_record)->insert()) {
                            echo json_encode(array("status" => "success", "data" => array("quiz_question_group_id" => $quiz_question_group->getQquestionGroupID())));
                        } else {
                            application_log("error", "Failed to add question to question group, db said: ".$db->ErrorMsg());
                            echo json_encode(array("status" => "error", "data" => array("Question unsuccessfully added to group")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    
                break;
                case "add-question-group-member" :
                    
                    if(isset(${$request_var}["qquestion_id"]) && $tmp_input = clean_input(${$request_var}["qquestion_id"], "int")) {
						$PROCESSED["qquestion_id"] = $tmp_input;
					} else {
                        add_error("Invalid quiz question ID.");
                    }
                    
                    if(isset(${$request_var}["qquestion_group_id"]) && $tmp_input = clean_input(${$request_var}["qquestion_group_id"], "int")) {
						$PROCESSED["qquestion_group_id"] = $tmp_input;
					} else {
                        add_error("Invalid quiz question group ID.");
                    }
                    
                    if (!$ERROR) {
                        $qquestion = Models_Quiz_Question::fetchRowByID($PROCESSED["qquestion_id"]);
                        if ($qquestion->fromArray(array("qquestion_group_id" => $PROCESSED["qquestion_group_id"]))->update()) {
                            echo json_encode(array("status" => "success", "data" => array("Question successfully added to group")));
                        } else {
                            application_log("error", "Failed to add question to question group, db said: ".$db->ErrorMsg());
                            echo json_encode(array("status" => "error", "data" => array("Question unsuccessfully added to group")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    
                break;
                case "update-question-order" :
                    
                    if(isset(${$request_var}["qquestion_id"]) && $tmp_input = clean_input(${$request_var}["qquestion_id"], "int")) {
						$PROCESSED["qquestion_id"] = $tmp_input;
					} else {
                        add_error("Invalid quiz question ID.");
                    }
                    
                    if(isset(${$request_var}["order"]) && $tmp_input = clean_input(${$request_var}["order"], "int")) {
						$PROCESSED["question_order"] = $tmp_input;
					} else {
                        add_error("Invalid order number.");
                    }
                    
                    if(isset(${$request_var}["group"]) && $tmp_input = clean_input(${$request_var}["group"], "int")) {
						$PROCESSED["qquestion_group_id"] = $tmp_input;
					} else {
                        $PROCESSED["qquestion_group_id"] = NULL;
                    }
                    
                    if (!$ERROR) {
                        $qquestion = Models_Quiz_Question::fetchRowByID($PROCESSED["qquestion_id"]);
                        if ($qquestion->fromArray(array("question_order" => $PROCESSED["question_order"], "qquestion_group_id" => $PROCESSED["qquestion_group_id"]))->update()) {
                            echo json_encode(array("status" => "success", "data" => array("qquestion_id" => $qquestion->getQquestionID(), "question_order" => $qquestion->getQuestionOrder())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("Failed to update quiz question order.")));
                        }
                    }
                    
                break;
                case "delete-question" :
                    if(isset(${$request_var}["qquestion_ids"])) {
						$question_ids = explode(",", ${$request_var}["qquestion_ids"]);
                        if (!empty($question_ids)) {
                            foreach ($question_ids as $question_id) {
                                $tmp_input = clean_input($question_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["qquestion_ids"][] = $tmp_input;
                                } else {
                                    add_error("Invalid quiz question ID");
                                    application_log("API passed [".$question_id."] which is an invalid question ID.");
                                }
                            }
                        } else {
                            add_error("No question IDs were passed to delete.");
                        }
					} else {
                        add_error("Invalid quiz question ID.");
                    }
                    
                    if (!$ERROR && !empty($PROCESSED["qquestion_ids"])) {
                        foreach ($PROCESSED["qquestion_ids"] as $question_id) {
                            $qquestion = Models_Quiz_Question::fetchRowByID($question_id);
                            if (!$qquestion->fromArray(array("question_active" => "0"))->update()) {
                                add_error("Failed to deactivate question.");
                                applicaiton_log("Failed to deactivate quiz question, DB said: ".$db->ErrorMsg());
                            }
                        }
                        
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("qquestion_ids" => $PROCESSED["qquestion_ids"])));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("Failed to delete quiz question.")));
                        }
                    }
                break;
            }
        break;
        case "GET" :
        break;
    }
    
    exit;
	
}
echo 0;
exit;