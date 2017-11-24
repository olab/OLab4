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
 * This file is used to author and copy an existing quiz.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'create', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($RECORD_ID) {
		$quiz = Models_Quiz::fetchRowByID($RECORD_ID);
        $quiz_record	= $quiz->toArray();
		if ($quiz_record && $ENTRADA_ACL->amIAllowed(new QuizResource($quiz_record["quiz_id"]), 'update')) {
            $BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID, "title" => limit_chars($quiz_record["quiz_title"], 32));
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=copy&id=".$RECORD_ID, "title" => "Copying Quiz");

			/**
			 * Required field "quiz_title" / Quiz Title.
			 */
			if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
				$PROCESSED["quiz_title"]		= $tmp_input;
				$PROCESSED["quiz_description"]	= $quiz_record["quiz_description"];
				$PROCESSED["quiz_active"]		= 1;
				$PROCESSED["updated_date"]		= time();
				$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();
                $PROCESSED["created_by"]        = $ENTRADA_USER->getID();
				if($ENTRADA_ACL->amIAllowed('quiz', 'create')) {
                    $quiz = new Models_Quiz($PROCESSED);
					if ($quiz->insert()) {
                        $new_quiz_id = $quiz->getQuizID();
						if ($new_quiz_id) {
                            
                            $quiz_contacts = Models_Quiz_Contact::fetchAllRecords($RECORD_ID);
                            if ($quiz_contacts) {
                                foreach ($quiz_contacts as $q) {
                                    $contact = $q->toArray();
                                    unset($contact["qcontact_id"]);
                                    $contact["quiz_id"] = $new_quiz_id;
                                    $contact["updated_date"] = time();
                                    $contact["updated_by"] = $ENTRADA_USER->getActiveID();
                                    $q_c = new Models_Quiz_Contact($contact);
                                    if (!$q_c->insert()) {
                                        $ERROR++;
                                    }
                                }
                            }
							
							if (!$ERROR) {
								$questions = Models_Quiz_Question::fetchAllRecords($RECORD_ID);
                                if ($questions) {
									$new_qquestion_ids = array();

									foreach ($questions as $q) {
                                        $question = $q->toArray();
										$old_qquestion_id = $question["qquestion_id"];
										unset($question["qquestion_id"]);
										$question["quiz_id"] = $new_quiz_id;
                                        $new_question = new Models_Quiz_Question($question);
										if ($new_question->insert()) {
											$new_qquestion_id = $new_question->getQquestionID();
                                            
											if ($new_question->getQuestiontypeID() == "1" || $new_question->getQuestiontypeID() == "4") {
                                                $responses = Models_Quiz_Question_Response::fetchAllRecords($old_qquestion_id);
												foreach ($responses as $r) {
                                                    $response_data = $r->toArray();
                                                    unset($response_data["qqresponse_id"]);
                                                    $response_data["qquestion_id"] = $new_qquestion_id;
                                                    $response = new Models_Quiz_Question_Response($response_data);
                                                    if (!$response->insert()) {
                                                        $ERROR++;
                                                    }
                                                }
											}
										} else {
											application_log("error", "Unable to insert new quiz_questions record when attempting to copy quiz_id [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
										}
									}
                                    
									if ($ERROR) {
										if (count($new_qquestion_ids) > 0) {
                                            foreach ($new_qquestion_ids as $new_qquestion_id) {
                                                $qquestion_responses = Models_Quiz_Question_Response::fetchAllRecords($new_qquestion_id);
                                                if ($qquestion_responses) {
                                                    foreach ($qquestion_responses as $qquestion_response) {
                                                        $qquestion_response->delete();
                                                    }
                                                }
                                            }
										}

                                        $quiz_questions = Models_Quiz_Question::fetchAllRecords($new_quiz_id);
                                        if ($quiz_questions) {
                                            foreach ($quiz_questions as $quiz_question) {
                                                $quiz_question->delete();
                                            }
                                        }
                                        
                                        $quiz_contacts = Models_Quiz_Contact::fetchAllRecords($new_quiz_id);
                                        if ($quiz_contacts) {
                                            foreach ($quiz_contacts as $quiz_contact) {
                                                $quiz_contact->delete();
                                            }
                                        }
                                        
                                        $quiz = Models_Quiz::fetchRowByID($new_quiz_id);
										if ($quiz) {
                                            $quiz->delete();
                                        }

										add_error("There was a problem creating the new quiz at this time. The system administrator was informed of this error; please try again later.");
									}
								}
							} else {
								$quiz = Models_Quiz::fetchRowByID($new_quiz_id);
                                if ($quiz) {
                                    $quiz->delete();
                                }
								
								add_error("Unable to copy the existing quiz authors from the original quiz. The system administrator was informed of this error; please try again later.");

								application_log("error", "Unable to copy any quiz authors when attempting to copy quiz_id [".$RECORD_ID."] authors to quiz_id [".$new_quiz_id."]. Database said: ".$db->ErrorMsg());
							}
						} else {
							add_error("There was a problem creating the new quiz at this time. The system administrator was informed of this error; please try again later.");

							application_log("error", "There was an error inserting a copied quiz, as there was no new_quiz_id available from Insert_Id(). Database said: ".$db->ErrorMsg());
						}
					} else {
						add_error("There was a problem creating the new quiz at this time. The system administrator was informed of this error; please try again later.");

						application_log("error", "There was an error inserting a new copied quiz. Database said: ".$db->ErrorMsg());
					}
				} else {
					add_error("You do not have permission to create a new quiz with these parameters.");

					application_log("error", "There was an error inserting a new copied quiz due to lack of permissions");
				}

			} else {
				add_error("Unable to copy this quiz because the <strong>New Quiz Title</strong> field is required, and was not provided.");
			}

			if (!$ERROR) {
				$url = ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$new_quiz_id;

				add_success("You have successfully created a new quiz (<strong>".html_encode($PROCESSED["quiz_title"])."</strong>) based on <strong>".html_encode($quiz_record["quiz_title"])."</strong>.<br /><br />You will now be redirected to the <strong>newly copied</strong> quiz; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

				application_log("success", "Original quiz_id [".$RECORD_ID."] has successfully been copied to new quiz_id [".$new_quiz_id."].");

				echo display_success();
			} else {
				$url = ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$quiz_record["quiz_id"];

				add_error("<br /><br />You will now be redirected to the <strong>original</strong> quiz; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
				
				echo display_error();
			}

			$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
		} else {
			add_error("In order to copy a quiz, you must provide a valid quiz identifier.");

			echo display_error();

			application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to copy a quiz.");
		}
	} else {
		add_error("In order to copy a quiz, you must provide a quiz identifier.");

		echo display_error();

		application_log("notice", "Failed to provide a quiz identifier to copy a quiz.");
	}
}
